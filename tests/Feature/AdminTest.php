<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\Test as QuizTest;
use App\Models\User;
use App\Models\UserStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private array $spa = ['Referer' => 'http://localhost/', 'Accept' => 'application/json'];

    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->makeUser('admin@example.test', 'admin');
        $this->user = $this->makeUser('ana@example.test');
    }

    private function makeUser(string $email, string $role = 'user'): User
    {
        return User::create(['name' => strtok($email, '@'), 'email' => $email, 'password_hash' => Hash::make('secret-pass-1'), 'role' => $role]);
    }

    private function makeTest(array $attrs = []): QuizTest
    {
        return QuizTest::create(array_merge([
            'user_id' => $this->user->id, 'title' => 'Test', 'is_public' => true, 'status' => 'published',
        ], $attrs));
    }

    private function asAdmin(): static
    {
        return $this->actingAs($this->admin);
    }

    public function test_admin_routes_require_admin_role(): void
    {
        $this->getJson('/api/admin/metrics', $this->spa)->assertUnauthorized();
        $this->actingAs($this->user)->getJson('/api/admin/metrics', $this->spa)->assertForbidden();
        $this->actingAs($this->user)->patchJson('/api/admin/users/'.$this->user->id, ['role' => 'admin'], $this->spa)->assertForbidden();
        $this->assertSame('user', $this->user->fresh()->role);

        $this->asAdmin()->getJson('/api/admin/metrics', $this->spa)->assertOk()
            ->assertJsonPath('users.total', 2)
            ->assertJsonCount(30, 'series');
    }

    public function test_any_admin_role_works_not_just_one_email(): void
    {
        // Antes solo valía admin@example.com.
        $other = $this->makeUser('jefa@example.test', 'admin');
        $this->actingAs($other)->getJson('/api/admin/tests', $this->spa)->assertOk();
    }

    public function test_featuring_a_test_shows_it_on_home_and_is_audited(): void
    {
        $test = $this->makeTest(['title' => 'Estrella']);
        $draft = $this->makeTest(['status' => 'draft']);

        $this->asAdmin()->patchJson("/api/admin/tests/{$test->id}", ['is_featured' => true], $this->spa)->assertOk();
        $this->asAdmin()->patchJson("/api/admin/tests/{$draft->id}", ['is_featured' => true], $this->spa)
            ->assertStatus(422)->assertJsonValidationErrors('is_featured');

        $this->getJson('/api/tests/featured', $this->spa)->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', 'Estrella');

        // Archivar lo quita de destacados.
        $this->asAdmin()->patchJson("/api/admin/tests/{$test->id}", ['status' => 'archived'], $this->spa)->assertOk();
        $this->assertFalse($test->fresh()->is_featured);
        $this->getJson('/api/tests/featured', $this->spa)->assertJsonCount(0);

        $this->assertSame(
            ['test.featured', 'test.status', 'test.unfeatured'],
            AdminAuditLog::orderBy('id')->pluck('action')->all(),
        );
        $this->assertSame($this->admin->id, AdminAuditLog::first()->admin_id);
    }

    public function test_admin_deleting_a_test_updates_the_authors_counters(): void
    {
        $category = Category::create(['name' => 'Historia', 'slug' => 'historia', 'tests_count' => 1]);
        $test = $this->makeTest();
        $test->categories()->attach($category->id);
        UserStat::create(['user_id' => $this->user->id, 'tests_created' => 1]);
        UserStat::create(['user_id' => $this->admin->id, 'tests_created' => 3]);

        $this->asAdmin()->deleteJson("/api/admin/tests/{$test->id}", [], $this->spa)->assertOk();

        $this->assertNull(QuizTest::find($test->id));
        $this->assertSame(0, UserStat::where('user_id', $this->user->id)->value('tests_created'));
        $this->assertSame(3, UserStat::where('user_id', $this->admin->id)->value('tests_created'));
        $this->assertSame(0, $category->fresh()->tests_count);
        $this->assertSame('Test', AdminAuditLog::firstWhere('action', 'test.deleted')->details['title']);
    }

    public function test_suspending_blocks_login_and_kills_open_sessions(): void
    {
        DB::table('sessions')->insert([
            'id' => 'sesion-de-ana', 'user_id' => $this->user->id, 'payload' => '', 'last_activity' => time(),
        ]);

        $this->asAdmin()->patchJson("/api/admin/users/{$this->user->id}", ['suspended' => true], $this->spa)->assertOk();

        $this->assertNotNull($this->user->fresh()->suspended_at);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $this->user->id)->count());

        // Una sesión que siguiera viva pierde el acceso en la siguiente petición.
        $this->actingAs($this->user->fresh())->getJson('/api/me', $this->spa)->assertForbidden()->assertJson(['suspended' => true]);

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', ['email' => 'ana@example.test', 'password' => 'secret-pass-1'], $this->spa)
            ->assertForbidden()->assertJson(['suspended' => true]);
        // Con contraseña incorrecta no se revela la suspensión.
        $this->postJson('/api/login', ['email' => 'ana@example.test', 'password' => 'mal'], $this->spa)->assertStatus(422);

        $this->asAdmin()->patchJson("/api/admin/users/{$this->user->id}", ['suspended' => false], $this->spa)->assertOk();
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/login', ['email' => 'ana@example.test', 'password' => 'secret-pass-1'], $this->spa)->assertOk();
    }

    public function test_admins_cannot_lock_themselves_out_or_suspend_other_admins(): void
    {
        $this->asAdmin()->patchJson("/api/admin/users/{$this->admin->id}", ['role' => 'user'], $this->spa)
            ->assertStatus(422)->assertJsonValidationErrors('user');
        $this->asAdmin()->patchJson("/api/admin/users/{$this->admin->id}", ['suspended' => true], $this->spa)->assertStatus(422);

        $other = $this->makeUser('otra@example.test', 'admin');
        $this->asAdmin()->patchJson("/api/admin/users/{$other->id}", ['suspended' => true], $this->spa)
            ->assertStatus(422)->assertJsonValidationErrors('suspended');

        // Quitarle el rol y suspender en la misma petición tampoco cuela... salvo que se quite primero.
        $this->asAdmin()->patchJson("/api/admin/users/{$other->id}", ['role' => 'user'], $this->spa)->assertOk();
        $this->asAdmin()->patchJson("/api/admin/users/{$other->id}", ['suspended' => true], $this->spa)->assertOk();

        $this->assertSame('admin', $this->admin->fresh()->role);
        $this->assertEquals(['from' => 'admin', 'to' => 'user'], AdminAuditLog::firstWhere('action', 'user.role')->details);
    }

    public function test_promoting_a_user_gives_admin_access(): void
    {
        $this->asAdmin()->patchJson("/api/admin/users/{$this->user->id}", ['role' => 'admin'], $this->spa)->assertOk();
        $this->actingAs($this->user->fresh())->getJson('/api/admin/users', $this->spa)->assertOk()->assertJsonPath('total', 2);
    }

    public function test_category_crud_with_unique_slugs_and_safe_icons(): void
    {
        $id = $this->asAdmin()->postJson('/api/admin/categories', [
            'name' => 'Ciencias Sociales', 'icon' => 'fa-solid fa-earth-europe', 'color' => '#2f6feb',
        ], $this->spa)->assertCreated()->assertJsonPath('category.slug', 'ciencias-sociales')->json('category.id');

        $this->asAdmin()->postJson('/api/admin/categories', ['name' => 'Ciencias Sociales'], $this->spa)
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $this->asAdmin()->postJson('/api/admin/categories', ['name' => 'X', 'icon' => '"><script>'], $this->spa)
            ->assertStatus(422)->assertJsonValidationErrors('icon');
        $this->asAdmin()->postJson('/api/admin/categories', ['name' => 'Y', 'color' => 'red'], $this->spa)
            ->assertStatus(422)->assertJsonValidationErrors('color');

        $this->asAdmin()->putJson("/api/admin/categories/{$id}", ['name' => 'Sociales'], $this->spa)
            ->assertOk()->assertJsonPath('category.slug', 'sociales');

        $test = $this->makeTest();
        $test->categories()->attach($id);
        $this->asAdmin()->deleteJson("/api/admin/categories/{$id}", [], $this->spa)->assertOk();
        $this->assertNotNull($test->fresh(), 'Borrar la categoría no borra sus tests');
        $this->assertSame(0, $test->categories()->count());

        $this->assertSame(
            ['category.created', 'category.updated', 'category.deleted'],
            AdminAuditLog::orderBy('id')->pluck('action')->all(),
        );
    }

    public function test_lists_search_and_filter(): void
    {
        $this->makeTest(['title' => 'Fotosíntesis']);
        $this->makeTest(['title' => 'Otro', 'status' => 'draft']);
        $this->makeUser('suspendida@example.test')->forceFill(['suspended_at' => now()])->save();

        $this->asAdmin()->getJson('/api/admin/tests?search=foto', $this->spa)->assertJsonPath('total', 1);
        $this->asAdmin()->getJson('/api/admin/tests?search=ana', $this->spa)->assertJsonPath('total', 2);
        $this->asAdmin()->getJson('/api/admin/tests?status=draft', $this->spa)->assertJsonPath('total', 1);
        $this->asAdmin()->getJson('/api/admin/users?status=suspended', $this->spa)->assertJsonPath('total', 1);
        $this->asAdmin()->getJson('/api/admin/users?role=admin', $this->spa)->assertJsonPath('total', 1);
    }
}
