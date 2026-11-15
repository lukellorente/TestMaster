<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Test as QuizTest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuestionTypesTest extends TestCase
{
    use RefreshDatabase;

    private array $spa = ['Referer' => 'http://localhost/', 'Accept' => 'application/json'];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Ana',
            'email' => 'ana@example.test',
            'password_hash' => Hash::make('secret-pass-1'),
        ]);
    }

    /** Un test con una pregunta de cada tipo, tal y como lo envía el editor. */
    private function allTypesPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Todos los tipos',
            'status' => 'published',
            'is_public' => true,
            'questions' => [
                ['type' => 'multiple_choice', 'content' => '2 + 2', 'options' => [
                    ['content' => '3', 'is_correct' => false],
                    ['content' => '4', 'is_correct' => true],
                ]],
                ['type' => 'multiple_select', 'content' => 'Números pares', 'options' => [
                    ['content' => '2', 'is_correct' => true],
                    ['content' => '3', 'is_correct' => false],
                    ['content' => '4', 'is_correct' => true],
                    ['content' => '5', 'is_correct' => false],
                ]],
                ['type' => 'true_false', 'content' => 'El agua moja', 'correct' => 'true'],
                ['type' => 'short_answer', 'content' => 'Capital de Italia', 'correct_answer' => 'Roma'],
                ['type' => 'fill_blank', 'content' => 'La capital de Francia es ___ y la de España es ___.',
                    'case_sensitive' => false,
                    'blanks' => [['answers' => ['París', 'Paris']], ['answers' => ['Madrid']]]],
                ['type' => 'order_items', 'content' => 'Ordena de menor a mayor', 'items' => ['uno', 'dos', 'tres']],
                ['type' => 'numeric', 'content' => 'Valor de pi', 'numeric_answer' => '3,14', 'numeric_tolerance' => '0.01'],
            ],
        ], $overrides);
    }

    private function createTest(array $payload): QuizTest
    {
        $id = $this->actingAs($this->user)->postJson('/api/tests', $payload, $this->spa)
            ->assertCreated()
            ->json('test.id');

        return QuizTest::findOrFail($id);
    }

    /** @return array<string, Question> preguntas indexadas por tipo */
    private function questionsByType(QuizTest $test): array
    {
        return Question::where('test_id', $test->id)
            ->with(['options', 'blanks', 'orderItems'])
            ->get()
            ->keyBy('type')
            ->all();
    }

    private function correctOptionIds(Question $question): array
    {
        return $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
    }

    private function submit(QuizTest $test, array $answers): array
    {
        $attemptId = $this->actingAs($this->user)->postJson('/api/attempts/submit', [
            'test_id' => $test->id,
            'answers' => $answers,
            'started_at' => time() - 30,
        ], $this->spa)->assertOk()->json('attempt_id');

        return $this->actingAs($this->user)
            ->getJson("/api/attempts/{$attemptId}/results", $this->spa)
            ->assertOk()
            ->json();
    }

    private function perfectAnswers(array $q): array
    {
        return [
            $q['multiple_choice']->id => $this->correctOptionIds($q['multiple_choice'])[0],
            $q['multiple_select']->id => $this->correctOptionIds($q['multiple_select']),
            $q['true_false']->id => 'true',
            $q['short_answer']->id => '  roma ',
            $q['fill_blank']->id => ['paris', 'MADRID'],
            $q['order_items']->id => $q['order_items']->orderItems->pluck('id')->all(),
            $q['numeric']->id => '3.145',
        ];
    }

    public function test_all_types_are_stored_and_a_perfect_attempt_scores_100(): void
    {
        $test = $this->createTest($this->allTypesPayload());
        $q = $this->questionsByType($test);

        $this->assertCount(7, $q);
        $this->assertCount(2, $q['fill_blank']->blanks);
        $this->assertSame(['uno', 'dos', 'tres'], $q['order_items']->orderItems->pluck('content')->all());
        $this->assertEqualsWithDelta(3.14, $q['numeric']->numeric_answer, 1e-9);

        $results = $this->submit($test, $this->perfectAnswers($q));

        $this->assertEquals(100, $results['attempt']['score']);
        $this->assertSame(7, $results['attempt']['correct_answers']);
        $this->assertTrue($results['attempt']['passed']);
        $this->assertSame(['ok'], array_values(array_unique(array_column($results['responses'], 'status'))));
    }

    public function test_taking_payload_never_reveals_the_solution(): void
    {
        $test = $this->createTest($this->allTypesPayload());

        $payload = $this->actingAs($this->user)->getJson("/api/tests/{$test->id}/questions", $this->spa)->assertOk();
        $json = json_encode($payload->json());

        foreach (['is_correct', 'correct_order', 'correct_answers', 'numeric_answer', 'numeric_tolerance', 'explanation', 'Madrid'] as $secret) {
            $this->assertStringNotContainsString($secret, $json, "Se filtra «{$secret}» al alumno");
        }

        $byType = collect($payload->json('questions'))->keyBy('type');
        $this->assertSame(2, $byType['fill_blank']['blank_count']);
        $this->assertCount(3, $byType['order_items']['items']);
        $this->assertNotSame(
            ['uno', 'dos', 'tres'],
            array_column($byType['order_items']['items'], 'content'),
            'Los elementos a ordenar no deben llegar ya ordenados',
        );
    }

    public function test_partial_credit_for_multi_select_blanks_and_ordering(): void
    {
        $test = $this->createTest($this->allTypesPayload());
        $q = $this->questionsByType($test);
        [$even2, $even4] = $this->correctOptionIds($q['multiple_select']);
        $odd = (string) $q['multiple_select']->options->firstWhere('is_correct', false)->id;
        [$one, $two, $three] = $q['order_items']->orderItems->pluck('id')->all();

        $results = $this->submit($test, [
            // 1 acierto de 2 correctas, sin errores → 0,5
            $q['multiple_select']->id => [$even2],
            // 1 de 2 huecos → 0,5
            $q['fill_blank']->id => ['Paris', 'Barcelona'],
            // solo «uno» en su sitio → 1/3
            $q['order_items']->id => [$one, $three, $two],
        ]);

        $rows = collect($results['responses'])->keyBy('type');
        $this->assertSame('partial', $rows['multiple_select']['status']);
        $this->assertEquals(0.5, $rows['multiple_select']['points_earned']);
        $this->assertEquals(0.5, $rows['fill_blank']['points_earned']);
        $this->assertEquals(0.33, $rows['order_items']['points_earned']);

        // Las parciales cuentan como no correctas (para el repaso de fallos).
        $this->assertSame(0, $results['attempt']['correct_answers']);
        $this->assertSame(3, $results['attempt']['incorrect_answers']);
        $this->assertSame(4, $results['attempt']['unanswered']);
        $this->assertEquals(round(1.33 / 7 * 100, 2), $results['attempt']['score']);

        // Una correcta y otra incorrecta marcadas se anulan → 0.
        $results = $this->submit($test, [$q['multiple_select']->id => [$even4, $odd]]);
        $this->assertSame('bad', collect($results['responses'])->firstWhere('type', 'multiple_select')['status']);
    }

    public function test_wrong_penalty_only_applies_to_fully_wrong_answers_and_score_floors_at_zero(): void
    {
        $test = $this->createTest($this->allTypesPayload(['wrong_penalty' => 0.5]));
        $q = $this->questionsByType($test);
        $wrongOption = (string) $q['multiple_choice']->options->firstWhere('is_correct', false)->id;

        // Correcta (+1), parcial 0,5 (sin penalización), incorrecta (−0,5), resto en blanco (0).
        $results = $this->submit($test, [
            $q['true_false']->id => 'true',
            $q['fill_blank']->id => ['Paris', 'x'],
            $q['multiple_choice']->id => $wrongOption,
        ]);

        $rows = collect($results['responses'])->keyBy('type');
        $this->assertEquals(-0.5, $rows['multiple_choice']['points_earned']);
        $this->assertEquals(0.5, $rows['fill_blank']['points_earned']);
        $this->assertEquals(0, $rows['numeric']['points_earned']);
        $this->assertEquals(1.0, $results['attempt']['points_earned']);
        $this->assertEquals(round(1 / 7 * 100, 2), $results['attempt']['score']);

        // Todo mal: puntos negativos, pero la nota no baja de 0 %.
        $results = $this->submit($test, [
            $q['multiple_choice']->id => $wrongOption,
            $q['true_false']->id => 'false',
            $q['numeric']->id => '99',
        ]);
        $this->assertEquals(-1.5, $results['attempt']['points_earned']);
        $this->assertEquals(0, $results['attempt']['score']);
    }

    public function test_blank_answers_are_not_penalized(): void
    {
        $test = $this->createTest($this->allTypesPayload(['wrong_penalty' => 1]));
        $q = $this->questionsByType($test);

        $results = $this->submit($test, [
            $q['fill_blank']->id => ['', '  '],
            $q['multiple_select']->id => [],
            $q['short_answer']->id => '   ',
        ]);

        $this->assertSame(7, $results['attempt']['unanswered']);
        $this->assertEquals(0, $results['attempt']['points_earned']);
    }

    public function test_numeric_accepts_comma_and_respects_tolerance(): void
    {
        $test = $this->createTest($this->allTypesPayload());
        $numeric = $this->questionsByType($test)['numeric'];

        $status = fn (string $answer) => collect($this->submit($test, [$numeric->id => $answer])['responses'])
            ->firstWhere('type', 'numeric')['status'];

        $this->assertSame('ok', $status('3,15'));
        $this->assertSame('ok', $status('3.13'));
        $this->assertSame('bad', $status('3.16'));
        $this->assertSame('bad', $status('tres'));
    }

    public function test_results_show_readable_labels_for_new_types(): void
    {
        $test = $this->createTest($this->allTypesPayload());
        $q = $this->questionsByType($test);
        [$one, $two, $three] = $q['order_items']->orderItems->pluck('id')->all();

        $rows = collect($this->submit($test, [
            $q['order_items']->id => [$three, $two, $one],
            $q['fill_blank']->id => ['Lyon', ''],
            $q['numeric']->id => '3',
        ])['responses'])->keyBy('type');

        $this->assertSame('tres → dos → uno', $rows['order_items']['given_answer_label']);
        $this->assertSame('uno → dos → tres', $rows['order_items']['correct_answer_label']);
        $this->assertSame('Lyon · —', $rows['fill_blank']['given_answer_label']);
        $this->assertSame('París / Paris · Madrid', $rows['fill_blank']['correct_answer_label']);
        $this->assertSame('3,14 (± 0,01)', $rows['numeric']['correct_answer_label']);
    }

    public function test_invalid_questions_are_rejected_with_a_clear_message(): void
    {
        $cases = [
            ['type' => 'fill_blank', 'content' => 'Sin huecos', 'blanks' => [['answers' => ['x']]]],
            ['type' => 'fill_blank', 'content' => 'Dos ___ huecos ___', 'blanks' => [['answers' => ['x']]]],
            ['type' => 'order_items', 'content' => 'Ordena', 'items' => ['solo uno', '']],
            ['type' => 'numeric', 'content' => 'Número', 'numeric_answer' => 'abc'],
            ['type' => 'multiple_select', 'content' => 'Ninguna', 'options' => [['content' => 'a'], ['content' => 'b']]],
            ['type' => 'multiple_choice', 'content' => 'Dos', 'options' => [
                ['content' => 'a', 'is_correct' => true], ['content' => 'b', 'is_correct' => true],
            ]],
        ];

        foreach ($cases as $question) {
            $this->actingAs($this->user)
                ->postJson('/api/tests', ['title' => 'X', 'questions' => [$question]], $this->spa)
                ->assertStatus(422)
                ->assertJsonValidationErrors('questions.0');
        }

        $this->assertSame(0, QuizTest::count());
    }

    public function test_edit_round_trip_keeps_new_type_data(): void
    {
        $test = $this->createTest($this->allTypesPayload(['wrong_penalty' => 0.25]));

        $edit = $this->actingAs($this->user)->getJson("/api/tests/{$test->id}/edit", $this->spa)->assertOk()->json('test');

        $this->assertEquals(0.25, $edit['wrong_penalty']);
        $byType = collect($edit['questions'])->keyBy('type');
        $this->assertSame(['París', 'Paris'], $byType['fill_blank']['blanks'][0]['correct_answers']);
        $this->assertSame('tres', $byType['order_items']['order_items'][2]['content']);

        $this->actingAs($this->user)->putJson("/api/tests/{$test->id}", [
            'wrong_penalty' => 0,
            'questions' => [['type' => 'numeric', 'content' => 'Raíz de 16', 'numeric_answer' => 4]],
        ], $this->spa)->assertOk();

        $this->assertEquals(0, $test->fresh()->wrong_penalty);
        $this->assertSame(['numeric'], Question::where('test_id', $test->id)->pluck('type')->all());
    }
}
