<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Test as QuizTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TestPasswordTest extends TestCase
{
    use RefreshDatabase;

    private array $spa = ['Referer' => 'http://localhost/', 'Accept' => 'application/json'];

    private User $owner;

    private User $student;

    private QuizTest $test;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create(['name' => 'Autor', 'email' => 'autor@example.test', 'password_hash' => Hash::make('secret-pass-1')]);
        $this->student = User::create(['name' => 'Alumno', 'email' => 'alumno@example.test', 'password_hash' => Hash::make('secret-pass-1')]);

        $id = $this->actingAs($this->owner)->postJson('/api/tests', [
            'title' => 'Con clave',
            'status' => 'published',
            'password' => 'abre-1234',
            'questions' => [['type' => 'true_false', 'content' => 'q', 'correct' => 'true']],
        ], $this->spa)->assertCreated()->json('test.id');

        $this->test = QuizTest::findOrFail($id);
        $this->question = $this->test->questions()->first();
    }

    private function asStudent(): static
    {
        return $this->actingAs($this->student);
    }

    public function test_password_is_hashed_and_never_exposed(): void
    {
        $raw = $this->test->getAttributes()['password'];
        $this->assertNotSame('abre-1234', $raw);
        $this->assertTrue(Hash::check('abre-1234', $raw));

        $show = $this->asStudent()->getJson("/api/tests/{$this->test->id}", $this->spa)->assertOk();
        $show->assertJsonMissingPath('password')->assertJson(['has_password' => true, 'locked' => true]);

        $list = $this->getJson('/api/tests', $this->spa)->assertOk()->json('data.0');
        $this->assertArrayNotHasKey('password', $list);
        $this->assertTrue($list['has_password']);

        $edit = $this->actingAs($this->owner)->getJson("/api/tests/{$this->test->id}/edit", $this->spa)->json('test');
        $this->assertArrayNotHasKey('password', $edit);
        $this->assertTrue($edit['has_password']);
    }

    public function test_locked_test_cannot_be_taken_until_unlocked(): void
    {
        $this->asStudent()->getJson("/api/tests/{$this->test->id}/questions", $this->spa)
            ->assertStatus(423)->assertJson(['locked' => true]);
        $this->asStudent()->postJson('/api/attempts/submit', [
            'test_id' => $this->test->id, 'answers' => [$this->question->id => 'true'], 'started_at' => time() - 5,
        ], $this->spa)->assertStatus(423);

        $this->asStudent()->postJson("/api/tests/{$this->test->id}/unlock", ['password' => 'mal'], $this->spa)
            ->assertStatus(422)->assertJsonValidationErrors('password');

        $this->asStudent()->postJson("/api/tests/{$this->test->id}/unlock", ['password' => 'abre-1234'], $this->spa)->assertOk();

        $this->asStudent()->getJson("/api/tests/{$this->test->id}", $this->spa)->assertJson(['locked' => false]);
        $this->asStudent()->getJson("/api/tests/{$this->test->id}/questions", $this->spa)->assertOk();
        $this->asStudent()->postJson('/api/attempts/submit', [
            'test_id' => $this->test->id, 'answers' => [$this->question->id => 'true'], 'started_at' => time() - 5,
        ], $this->spa)->assertOk();
    }

    public function test_owner_never_needs_the_password(): void
    {
        $this->actingAs($this->owner)->getJson("/api/tests/{$this->test->id}", $this->spa)->assertJson(['locked' => false]);
        $this->actingAs($this->owner)->getJson("/api/tests/{$this->test->id}/questions", $this->spa)->assertOk();
    }

    public function test_changing_the_password_locks_again_and_removing_it_unlocks(): void
    {
        $this->asStudent()->postJson("/api/tests/{$this->test->id}/unlock", ['password' => 'abre-1234'], $this->spa)->assertOk();

        // Editar sin enviar `password` no la cambia.
        $this->actingAs($this->owner)->putJson("/api/tests/{$this->test->id}", ['title' => 'Nuevo'], $this->spa)->assertOk();
        $this->asStudent()->getJson("/api/tests/{$this->test->id}", $this->spa)->assertJson(['locked' => false]);

        $this->actingAs($this->owner)->putJson("/api/tests/{$this->test->id}", ['password' => 'otra-5678'], $this->spa)->assertOk();
        $this->asStudent()->getJson("/api/tests/{$this->test->id}", $this->spa)->assertJson(['locked' => true]);

        $this->actingAs($this->owner)->putJson("/api/tests/{$this->test->id}", ['password' => null], $this->spa)->assertOk();
        $this->asStudent()->getJson("/api/tests/{$this->test->id}", $this->spa)->assertJson(['has_password' => false, 'locked' => false]);
    }

    public function test_unlock_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->asStudent()->postJson("/api/tests/{$this->test->id}/unlock", ['password' => "mal-{$i}"], $this->spa)->assertStatus(422);
        }

        $this->asStudent()->postJson("/api/tests/{$this->test->id}/unlock", ['password' => 'abre-1234'], $this->spa)->assertStatus(429);
    }
}
