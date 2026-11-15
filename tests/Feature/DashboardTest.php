<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Question;
use App\Models\Test as QuizTest;
use App\Models\TestAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private array $spa = ['Referer' => 'http://localhost/', 'Accept' => 'application/json'];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->makeUser('ana@example.test');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function makeUser(string $email): User
    {
        return User::create(['name' => 'U', 'email' => $email, 'password_hash' => Hash::make('secret-pass-1')]);
    }

    private function makeTest(array $attrs = [], ?User $owner = null): QuizTest
    {
        return QuizTest::create(array_merge([
            'user_id' => ($owner ?? $this->user)->id, 'title' => 'T', 'is_public' => true, 'status' => 'published',
        ], $attrs));
    }

    /** Intento terminado con fecha controlada (UTC). */
    private function attemptAt(string $utc, string $mode = 'exam', ?QuizTest $test = null, float $score = 50): TestAttempt
    {
        return TestAttempt::create([
            'user_id' => $this->user->id,
            'test_id' => ($test ?? $this->makeTest())->id,
            'mode' => $mode,
            'score' => $score,
            'is_completed' => true,
            'completed_at' => $utc,
            'started_at' => $utc,
        ]);
    }

    private function dashboard(string $tz = 'Europe/Madrid'): array
    {
        return $this->actingAs($this->user)->getJson('/api/dashboard?tz='.urlencode($tz), $this->spa)->assertOk()->json();
    }

    public function test_dashboard_requires_login(): void
    {
        $this->getJson('/api/dashboard', $this->spa)->assertUnauthorized();
        $this->getJson('/api/dashboard/attempts', $this->spa)->assertUnauthorized();
    }

    public function test_streak_counts_consecutive_days_including_reviews(): void
    {
        CarbonImmutable::setTestNow('2026-11-15 12:00:00');
        $test = $this->makeTest();

        // Racha antigua de 4 días (1–4 nov), hueco, y racha actual de 3 (13–15 nov).
        foreach (['01', '02', '03', '04'] as $d) {
            $this->attemptAt("2026-11-{$d} 10:00:00", 'exam', $test);
        }
        $this->attemptAt('2026-11-13 10:00:00', 'exam', $test);
        $this->attemptAt('2026-11-14 10:00:00', 'review', $test);
        $this->attemptAt('2026-11-15 09:00:00', 'exam', $test);
        $this->attemptAt('2026-11-15 18:00:00', 'review', $test);

        $activity = $this->dashboard()['activity'];

        $this->assertSame(3, $activity['current_streak']);
        $this->assertSame(4, $activity['longest_streak']);
        $this->assertTrue($activity['studied_today']);
        $this->assertSame(7, $activity['active_days']);
        $this->assertSame(2, $activity['days']['2026-11-15']);
    }

    public function test_streak_is_still_alive_today_until_midnight(): void
    {
        CarbonImmutable::setTestNow('2026-11-15 12:00:00');
        $this->attemptAt('2026-11-13 10:00:00');
        $this->attemptAt('2026-11-14 10:00:00');

        $activity = $this->dashboard()['activity'];
        $this->assertSame(2, $activity['current_streak']);
        $this->assertFalse($activity['studied_today']);

        CarbonImmutable::setTestNow('2026-11-16 12:00:00');
        $this->assertSame(0, $this->dashboard()['activity']['current_streak']);
    }

    public function test_days_are_counted_in_the_users_timezone(): void
    {
        // 23:30 UTC del 14 = 00:30 del 15 en Madrid (UTC+1 en noviembre).
        CarbonImmutable::setTestNow('2026-11-15 12:00:00');
        $this->attemptAt('2026-11-14 23:30:00');

        $this->assertArrayHasKey('2026-11-15', $this->dashboard('Europe/Madrid')['activity']['days']);
        $this->assertArrayHasKey('2026-11-14', $this->dashboard('UTC')['activity']['days']);

        // Zona horaria inventada → la de la app, sin error.
        $this->assertSame('UTC', $this->dashboard('Mars/Olympus')['timezone']);
    }

    public function test_summary_excludes_reviews_from_exam_stats(): void
    {
        $test = $this->makeTest();
        $this->attemptAt('2026-11-10 10:00:00', 'exam', $test, 40);
        $this->attemptAt('2026-11-11 10:00:00', 'exam', $test, 80);
        $this->attemptAt('2026-11-12 10:00:00', 'review', $test, 100);

        $summary = $this->dashboard()['summary'];
        $this->assertSame(2, $summary['exams_completed']);
        $this->assertEquals(60, $summary['average_score']);
        $this->assertSame(1, $summary['reviews_completed']);
    }

    public function test_mastery_by_category_uses_latest_answer_and_partial_credit(): void
    {
        $history = Category::create(['name' => 'Historia', 'slug' => 'historia']);
        $math = Category::create(['name' => 'Mates', 'slug' => 'mates']);

        $create = fn (array $questions, ?int $categoryId) => QuizTest::findOrFail($this->actingAs($this->user)->postJson('/api/tests', [
            'title' => 'X', 'status' => 'published', 'category_id' => $categoryId, 'questions' => $questions,
        ], $this->spa)->assertCreated()->json('test.id'));

        $historyTest = $create([
            ['type' => 'true_false', 'content' => 'h1', 'correct' => 'true'],
            ['type' => 'fill_blank', 'content' => '___ ___', 'blanks' => [['answers' => ['a']], ['answers' => ['b']]]],
        ], $history->id);
        $mathTest = $create([['type' => 'numeric', 'content' => 'm1', 'numeric_answer' => 2]], $math->id);
        $uncategorized = $create([['type' => 'true_false', 'content' => 'u1', 'correct' => 'false']], null);

        $q = Question::whereIn('test_id', [$historyTest->id, $mathTest->id, $uncategorized->id])->get()->keyBy('content');
        $submit = fn (QuizTest $t, array $answers, string $mode = 'exam') => $this->actingAs($this->user)
            ->postJson('/api/attempts/submit', ['test_id' => $t->id, 'answers' => $answers, 'started_at' => time() - 5, 'mode' => $mode], $this->spa)
            ->assertOk();

        // Historia: h1 bien (1/1) + huecos a medias (1/2) → 1,5 de 2 puntos = 75 %
        $submit($historyTest, [$q['h1']->id => 'true', $q['___ ___']->id => ['a', 'x']]);
        // Mates: mal en el examen, bien en el repaso → cuenta la última: 100 %
        $submit($mathTest, [$q['m1']->id => '3']);
        $submit($mathTest, [$q['m1']->id => '2'], 'review');
        // Sin categoría: mal → 0 %
        $submit($uncategorized, [$q['u1']->id => 'true']);

        $mastery = collect($this->dashboard()['mastery'])->keyBy('name');

        $this->assertEquals(75, $mastery['Historia']['mastery']);
        $this->assertSame(2, $mastery['Historia']['questions']);
        $this->assertSame(1, $mastery['Historia']['mastered']);
        $this->assertEquals(100, $mastery['Mates']['mastery']);
        $this->assertEquals(0, $mastery['Sin categoría']['mastery']);
        $this->assertSame(['Sin categoría', 'Historia', 'Mates'], $mastery->keys()->all(), 'De menos a más dominio');

        $pending = collect($this->dashboard()['pending_reviews'])->keyBy('test_id');
        $this->assertSame(1, $pending[$historyTest->id]['pending']);
        $this->assertFalse($pending->has($mathTest->id));
    }

    public function test_pending_reviews_hide_tests_the_user_can_no_longer_open(): void
    {
        $owner = $this->makeUser('owner@example.test');
        $test = $this->makeTest(['title' => 'Ajeno'], $owner);
        $question = Question::create(['test_id' => $test->id, 'type' => 'true_false', 'content' => 'q', 'points' => 1]);
        $question->options()->createMany([
            ['content' => 'Verdadero', 'is_correct' => true, 'order_index' => 1],
            ['content' => 'Falso', 'is_correct' => false, 'order_index' => 2],
        ]);

        $this->actingAs($this->user)->postJson('/api/attempts/submit', [
            'test_id' => $test->id, 'answers' => [$question->id => 'false'], 'started_at' => time() - 5,
        ], $this->spa)->assertOk();

        $this->assertCount(1, $this->dashboard()['pending_reviews']);

        $test->update(['is_public' => false]);
        $this->assertCount(0, $this->dashboard()['pending_reviews']);
    }

    public function test_attempt_history_is_paginated_filtered_and_private(): void
    {
        $test = $this->makeTest(['title' => 'Historial']);
        for ($i = 1; $i <= 17; $i++) {
            $this->attemptAt(sprintf('2026-11-%02d 10:00:00', $i), $i % 4 === 0 ? 'review' : 'exam', $test);
        }

        $other = $this->makeUser('otro@example.test');
        TestAttempt::create(['user_id' => $other->id, 'test_id' => $test->id, 'is_completed' => true, 'completed_at' => now()]);

        $page1 = $this->actingAs($this->user)->getJson('/api/dashboard/attempts', $this->spa)->assertOk()->json();
        $this->assertCount(15, $page1['data']);
        $this->assertSame(17, $page1['meta']['total']);
        $this->assertSame(2, $page1['meta']['last_page']);
        $this->assertSame('2026-11-17', substr($page1['data'][0]['completed_at'], 0, 10), 'Lo más reciente primero');
        $this->assertSame('Historial', $page1['data'][0]['test_title']);

        $reviews = $this->actingAs($this->user)->getJson('/api/dashboard/attempts?mode=review', $this->spa)->json();
        $this->assertSame(4, $reviews['meta']['total']);
        $this->assertSame(['review'], array_values(array_unique(array_column($reviews['data'], 'mode'))));
    }

    public function test_test_page_includes_own_exam_score_history(): void
    {
        $test = $this->makeTest(['max_attempts' => -1]);
        $this->attemptAt('2026-11-10 10:00:00', 'exam', $test, 30);
        $this->attemptAt('2026-11-11 10:00:00', 'review', $test, 100);
        $this->attemptAt('2026-11-12 10:00:00', 'exam', $test, 70);

        $history = $this->actingAs($this->user)->getJson("/api/tests/{$test->id}", $this->spa)->assertOk()->json('score_history');

        $this->assertSame([30.0, 70.0], array_map('floatval', array_column($history, 'score')));

        // Sin sesión no hay historial.
        $this->app['auth']->forgetGuards();
        $this->getJson("/api/tests/{$test->id}", $this->spa)->assertOk()->assertJsonMissingPath('score_history');
    }
}
