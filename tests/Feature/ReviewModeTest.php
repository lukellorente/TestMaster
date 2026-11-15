<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionResponse;
use App\Models\Test as QuizTest;
use App\Models\TestAttempt;
use App\Models\User;
use App\Models\UserStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReviewModeTest extends TestCase
{
    use RefreshDatabase;

    private array $spa = ['Referer' => 'http://localhost/', 'Accept' => 'application/json'];

    private User $user;

    private QuizTest $test;

    /** @var array<string, Question> preguntas indexadas por enunciado */
    private array $q;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Ana',
            'email' => 'ana@example.test',
            'password_hash' => Hash::make('secret-pass-1'),
        ]);

        $id = $this->actingAs($this->user)->postJson('/api/tests', [
            'title' => 'Repaso',
            'status' => 'published',
            'max_attempts' => 1,
            'time_limit' => 10,
            'questions' => $this->editorQuestions(),
        ], $this->spa)->assertCreated()->json('test.id');

        $this->test = QuizTest::findOrFail($id);
        $this->q = Question::where('test_id', $id)->with(['options', 'blanks'])->get()->keyBy('content')->all();
    }

    private function editorQuestions(array $ids = []): array
    {
        return [
            ['id' => $ids['mc'] ?? null, 'type' => 'multiple_choice', 'content' => 'mc', 'options' => [
                ['content' => 'mal', 'is_correct' => false],
                ['content' => 'bien', 'is_correct' => true],
            ]],
            ['id' => $ids['tf'] ?? null, 'type' => 'true_false', 'content' => 'tf', 'correct' => 'true'],
            ['id' => $ids['fill'] ?? null, 'type' => 'fill_blank', 'content' => 'fill ___ ___',
                'blanks' => [['answers' => ['a']], ['answers' => ['b']]]],
            ['id' => $ids['num'] ?? null, 'type' => 'numeric', 'content' => 'num', 'numeric_answer' => 7],
        ];
    }

    private function option(string $question, bool $correct): string
    {
        return (string) $this->q[$question]->options->firstWhere('is_correct', $correct)->id;
    }

    private function submit(array $answers, string $mode = 'exam', ?array $order = null): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user)->postJson('/api/attempts/submit', array_filter([
            'test_id' => $this->test->id,
            'answers' => $answers,
            'started_at' => time() - 10,
            'mode' => $mode,
            'question_order' => $order,
        ], fn ($v) => $v !== null), $this->spa);
    }

    /** Examen: mc mal, tf bien, fill parcial, num en blanco → pendientes mc, fill y num. */
    private function failSomeInExam(): int
    {
        return $this->submit([
            $this->q['mc']->id => $this->option('mc', false),
            $this->q['tf']->id => 'true',
            $this->q['fill ___ ___']->id => ['a', 'x'],
        ])->assertOk()->json('attempt_id');
    }

    private function reviewQuestionIds(): array
    {
        return collect($this->actingAs($this->user)
            ->getJson("/api/tests/{$this->test->id}/questions?mode=review", $this->spa)
            ->assertOk()
            ->json('questions'))->pluck('id')->sort()->values()->all();
    }

    public function test_review_lists_wrong_partial_and_unanswered_questions(): void
    {
        $this->assertSame([], $this->reviewQuestionIds());

        $this->failSomeInExam();

        $expected = collect([$this->q['mc'], $this->q['fill ___ ___'], $this->q['num']])->pluck('id')->sort()->values()->all();
        $this->assertSame($expected, $this->reviewQuestionIds());

        $show = $this->actingAs($this->user)->getJson("/api/tests/{$this->test->id}", $this->spa)->json();
        $this->assertSame(3, $show['pending_review_count']);

        // El repaso no tiene límite de tiempo.
        $payload = $this->actingAs($this->user)->getJson("/api/tests/{$this->test->id}/questions?mode=review", $this->spa)->json('test');
        $this->assertSame('review', $payload['mode']);
        $this->assertNull($payload['time_limit']);
    }

    public function test_a_question_leaves_the_queue_once_answered_correctly(): void
    {
        $this->failSomeInExam();

        $this->submit([
            $this->q['mc']->id => $this->option('mc', true),   // bien → sale
            $this->q['fill ___ ___']->id => ['a', 'zzz'],      // sigue parcial → se queda
            $this->q['num']->id => '6',                         // mal → se queda
        ], 'review')->assertOk()->assertJson(['mode' => 'review']);

        $this->assertSame(
            collect([$this->q['fill ___ ___'], $this->q['num']])->pluck('id')->sort()->values()->all(),
            $this->reviewQuestionIds(),
        );
    }

    public function test_review_does_not_spend_attempts_or_touch_stats(): void
    {
        $this->failSomeInExam();
        $statsBefore = UserStat::where('user_id', $this->user->id)->first()->toArray();

        // max_attempts = 1: otro examen está bloqueado, pero el repaso no.
        $this->submit([$this->q['tf']->id => 'true'])->assertStatus(422);
        $reviewId = $this->submit([$this->q['mc']->id => $this->option('mc', true)], 'review')->assertOk()->json('attempt_id');

        $this->assertEquals($statsBefore, UserStat::where('user_id', $this->user->id)->first()->toArray());

        $show = $this->actingAs($this->user)->getJson("/api/tests/{$this->test->id}", $this->spa)->json();
        $this->assertSame(1, $show['completed_attempts_count']);
        $this->assertSame(1, $show['user_attempts']);
        $this->assertNotEquals(100, round($show['avg_score']), 'El 100 % del repaso no debe entrar en la media');

        $results = $this->actingAs($this->user)->getJson("/api/attempts/{$reviewId}/results", $this->spa)->assertOk()->json();
        $this->assertSame('review', $results['attempt']['mode']);
        $this->assertNull($results['comparison']);
        $this->assertSame(2, $results['pending_review_count']);
    }

    public function test_review_only_grades_pending_questions(): void
    {
        $this->failSomeInExam();

        // tf ya estaba bien: aunque se envíe en el repaso, no se corrige ni cuenta.
        $id = $this->submit([
            $this->q['tf']->id => 'false',
            $this->q['mc']->id => $this->option('mc', true),
        ], 'review', [$this->q['tf']->id, $this->q['mc']->id])->assertOk()->json('attempt_id');

        $graded = QuestionResponse::where('attempt_id', $id)->pluck('question_id')->all();
        $this->assertNotContains($this->q['tf']->id, $graded);
        $this->assertCount(3, $graded);
        $this->assertNotContains($this->q['tf']->id, $this->reviewQuestionIds());
    }

    public function test_review_without_pending_questions_is_rejected(): void
    {
        $this->submit([$this->q['mc']->id => $this->option('mc', true)], 'review')
            ->assertStatus(422)
            ->assertJsonPath('message', 'No tienes preguntas pendientes de repasar en este test.');
    }

    public function test_editing_a_test_keeps_previous_answers_and_option_ids(): void
    {
        $attemptId = $this->failSomeInExam();
        $responsesBefore = QuestionResponse::where('attempt_id', $attemptId)->count();
        $mcOptionIds = $this->q['mc']->options->pluck('id')->all();

        $ids = ['mc' => $this->q['mc']->id, 'tf' => $this->q['tf']->id, 'fill' => $this->q['fill ___ ___']->id];
        $questions = $this->editorQuestions($ids);
        $questions[0]['content'] = 'mc (editada)';
        unset($questions[3]); // se elimina la numérica

        $this->actingAs($this->user)->putJson("/api/tests/{$this->test->id}", ['questions' => array_values($questions)], $this->spa)
            ->assertOk();

        $this->assertSame($this->q['mc']->id, Question::where('content', 'mc (editada)')->value('id'));
        $this->assertSame($mcOptionIds, Question::find($this->q['mc']->id)->options()->pluck('id')->all());
        $this->assertNull(Question::find($this->q['num']->id));

        // Solo desaparece la respuesta de la pregunta borrada.
        $this->assertSame($responsesBefore - 1, QuestionResponse::where('attempt_id', $attemptId)->count());

        $rows = collect($this->actingAs($this->user)->getJson("/api/attempts/{$attemptId}/results", $this->spa)->json('responses'))->keyBy('question_id');
        $this->assertSame('mal', $rows[$this->q['mc']->id]['given_answer_label']);
        $this->assertSame('mc (editada)', $rows[$this->q['mc']->id]['question_text']);
    }

    public function test_foreign_or_mismatched_ids_create_new_questions(): void
    {
        $other = QuizTest::create(['user_id' => $this->user->id, 'title' => 'Otro', 'status' => 'published']);
        $foreign = Question::create(['test_id' => $other->id, 'type' => 'true_false', 'content' => 'ajena']);

        $this->actingAs($this->user)->putJson("/api/tests/{$this->test->id}", ['questions' => [
            // id de otro test → nueva
            ['id' => $foreign->id, 'type' => 'true_false', 'content' => 'robada', 'correct' => 'true'],
            // mismo id pero otro tipo → nueva
            ['id' => $this->q['tf']->id, 'type' => 'short_answer', 'content' => 'cambio de tipo', 'correct_answer' => 'x'],
        ]], $this->spa)->assertOk();

        $this->assertSame('ajena', $foreign->fresh()->content);
        $ids = Question::where('test_id', $this->test->id)->pluck('id')->all();
        $this->assertCount(2, $ids);
        $this->assertNotContains($foreign->id, $ids);
        $this->assertNotContains($this->q['tf']->id, $ids);
        $this->assertSame(0, TestAttempt::count());
    }
}
