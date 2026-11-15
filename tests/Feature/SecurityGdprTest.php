<?php

namespace Tests\Feature;

use App\Models\Test as QuizTest;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityGdprTest extends TestCase
{
    use RefreshDatabase;

    /** Las peticiones SPA llegan desde un dominio "stateful". */
    private array $spa = ['Referer' => 'http://localhost/', 'Accept' => 'application/json'];

    private function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Ana',
            'email' => 'ana' . uniqid() . '@example.test',
            'password_hash' => Hash::make('secret-pass-1'),
        ], $attrs));
    }

    private function makeTest(User $owner, array $attrs = []): QuizTest
    {
        return QuizTest::create(array_merge([
            'user_id' => $owner->id,
            'title' => 'Quiz',
            'is_public' => true,
            'status' => 'published',
        ], $attrs));
    }

    public function test_register_requires_consent_and_records_it(): void
    {
        $payload = [
            'name' => 'Bea', 'email' => 'bea@example.test',
            'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ];

        $this->postJson('/api/register', $payload, $this->spa)
            ->assertStatus(422)->assertJsonValidationErrors('accept_terms');

        $this->postJson('/api/register', $payload + ['accept_terms' => true], $this->spa)
            ->assertCreated()->assertJsonMissingPath('token');

        $user = User::where('email', 'bea@example.test')->first();
        $this->assertNotNull($user->privacy_accepted_at);
        $this->assertSame('2026-11-15', $user->privacy_policy_version);
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_login_is_rate_limited(): void
    {
        $this->makeUser(['email' => 'victim@example.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'victim@example.test', 'password' => 'wrong'], $this->spa)
                ->assertStatus(422);
        }

        $this->postJson('/api/login', ['email' => 'victim@example.test', 'password' => 'wrong'], $this->spa)
            ->assertStatus(429);
    }

    public function test_avatar_ignores_client_extension(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();

        $file = UploadedFile::fake()->image('evil.html', 10, 10); // contenido PNG, nombre .html

        $res = $this->actingAs($user)->post('/api/profile/avatar', ['avatar' => $file], $this->spa)->assertOk();

        $this->assertStringEndsWith('.png', $res->json('avatar'));
        $this->assertStringNotContainsString('.html', $res->json('avatar'));
    }

    public function test_public_profile_hides_private_fields(): void
    {
        $owner = $this->makeUser(['role' => 'admin', 'last_login' => now()]);
        $test = $this->makeTest($owner);
        TestAttempt::create(['user_id' => $owner->id, 'test_id' => $test->id, 'is_completed' => true, 'completed_at' => now(), 'score' => 50]);

        $res = $this->getJson("/api/profile/{$owner->id}")->assertOk();

        $this->assertNull($res->json('user.email'));
        $this->assertNull($res->json('user.role'));
        $this->assertNull($res->json('user.last_login'));
        $this->assertSame([], $res->json('completed_tests'));
    }

    public function test_questions_do_not_leak_explanations(): void
    {
        $owner = $this->makeUser();
        $test = $this->makeTest($owner);
        $test->questions()->create(['type' => 'short_answer', 'content' => 'Q', 'explanation' => 'La respuesta es 42', 'points' => 1, 'order_index' => 1]);

        $res = $this->actingAs($owner)->getJson("/api/tests/{$test->id}/questions", $this->spa)->assertOk();

        $this->assertArrayNotHasKey('explanation', $res->json('questions.0'));
    }

    public function test_export_returns_own_data(): void
    {
        $user = $this->makeUser();
        $this->makeTest($user);

        $this->actingAs($user)->getJson('/api/account/export', $this->spa)
            ->assertOk()
            ->assertJsonPath('account.email', $user->email)
            ->assertJsonCount(1, 'tests_created');
    }

    public function test_delete_account_anonymizes_public_tests_by_default(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $public = $this->makeTest($user);
        $draft = $this->makeTest($user, ['status' => 'draft']);
        TestAttempt::create(['user_id' => $user->id, 'test_id' => $public->id, 'is_completed' => true, 'completed_at' => now(), 'score' => 80]);
        $othersAttempt = TestAttempt::create(['user_id' => $other->id, 'test_id' => $public->id, 'is_completed' => true, 'completed_at' => now(), 'score' => 90]);

        $this->actingAs($user)->deleteJson('/api/account', ['password' => 'wrong'], $this->spa)->assertStatus(422);
        $this->actingAs($user)->deleteJson('/api/account', ['password' => 'secret-pass-1'], $this->spa)->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('tests', ['id' => $public->id, 'user_id' => null]);
        $this->assertDatabaseMissing('tests', ['id' => $draft->id]);
        $this->assertDatabaseMissing('test_attempts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('test_attempts', ['id' => $othersAttempt->id]);

        $this->getJson("/api/tests/{$public->id}")->assertOk()->assertJsonPath('user', null);
    }

    public function test_delete_account_can_delete_all_tests(): void
    {
        $user = $this->makeUser();
        $public = $this->makeTest($user);

        $this->actingAs($user)->deleteJson('/api/account', ['password' => 'secret-pass-1', 'delete_tests' => true], $this->spa)
            ->assertOk();

        $this->assertDatabaseMissing('tests', ['id' => $public->id]);
    }
}
