<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Genera los tests del catálogo (database/data/exams/catalog.json) llamando a la CLI
 * de Claude Code en modo headless (`claude -p`), que usa la suscripción Pro/Max del
 * usuario en lugar de una API key.
 *
 * Es reanudable: los tests que ya tienen generated/<slug>.json se saltan, así que si se
 * agota el límite de uso basta con volver a ejecutarlo más tarde.
 */
class GenerateExams extends Command
{
    protected $signature = 'exams:generate
        {--only=* : Generar solo estos slugs}
        {--limit=0 : Máximo de tests a generar en esta ejecución (0 = todos)}
        {--model=sonnet : Modelo de Claude (sonnet, haiku, opus...)}
        {--force : Regenerar aunque ya exista el fichero}
        {--claude=claude : Ruta al ejecutable de Claude Code}';

    protected $description = 'Genera tests estilo Daypo (oposiciones y FP) con Claude Code headless';

    private const MAX_ATTEMPTS = 2;

    /** Tipos que puede devolver el modelo, ya con el formato que entiende la app. */
    private const TYPES = ['multiple_choice', 'multiple_select', 'true_false', 'short_answer'];

    public function handle(): int
    {
        $catalog = json_decode(file_get_contents(database_path('data/exams/catalog.json')), true, flags: JSON_THROW_ON_ERROR);
        $outDir = database_path('data/exams/generated');
        if (! is_dir($outDir)) {
            mkdir($outDir, 0775, true);
        }

        $categories = collect($catalog['categories'])->keyBy('slug');
        $only = $this->option('only');
        $limit = (int) $this->option('limit');

        $pending = collect($catalog['tests'])
            ->filter(fn ($t) => ! $only || in_array($t['slug'], $only, true))
            ->filter(fn ($t) => $this->option('force') || ! is_file("{$outDir}/{$t['slug']}.json"))
            ->values();

        if ($limit > 0) {
            $pending = $pending->take($limit);
        }

        if ($pending->isEmpty()) {
            $this->info('No hay tests pendientes de generar.');

            return self::SUCCESS;
        }

        $this->info("Generando {$pending->count()} tests con el modelo {$this->option('model')}…");
        $done = 0;
        $consecutiveFailures = 0;

        foreach ($pending as $i => $test) {
            $label = sprintf('[%d/%d] %s', $i + 1, $pending->count(), $test['slug']);
            $category = $categories[$test['cat']] ?? null;
            $parent = $category && isset($category['parent']) ? $categories[$category['parent']] : null;

            $result = null;
            $error = null;
            for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS && ! $result; $attempt++) {
                try {
                    $result = $this->generate($test, $category, $parent);
                } catch (\RuntimeException $e) {
                    $error = $e->getMessage();
                    if ($this->looksLikeUsageLimit($error)) {
                        $this->error("{$label}: límite de uso alcanzado. Vuelve a ejecutar el comando más tarde; continuará donde lo dejó.");

                        return self::FAILURE;
                    }
                }
            }

            if (! $result) {
                $this->warn("{$label}: fallido ({$error})");
                if (++$consecutiveFailures >= 3) {
                    $this->error('Tres fallos seguidos, se detiene la generación.');

                    return self::FAILURE;
                }

                continue;
            }

            $consecutiveFailures = 0;
            file_put_contents(
                "{$outDir}/{$test['slug']}.json",
                json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
            );
            $done++;
            $this->line("{$label}: <info>" . count($result['questions']) . ' preguntas</info>');
        }

        $this->info("Listo: {$done} tests generados. Impórtalos con: php artisan db:seed --class=ExamLibrarySeeder");

        return self::SUCCESS;
    }

    private function generate(array $test, ?array $category, ?array $parent): array
    {
        $process = new Process(
            [
                $this->option('claude'), '-p',
                '--model', $this->option('model'),
                '--output-format', 'json',
                '--tools', '',
                '--no-session-persistence',
                '--system-prompt', $this->systemPrompt(),
                '--json-schema', json_encode($this->schema(), JSON_UNESCAPED_SLASHES),
            ],
            // Fuera del proyecto para no cargar CLAUDE.md ni memoria del repo en cada llamada.
            sys_get_temp_dir(),
            timeout: 900,
        );
        $process->setInput($this->userPrompt($test, $category, $parent));
        $process->run();

        $raw = json_decode($process->getOutput(), true);
        if (! is_array($raw)) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'respuesta vacía');
        }
        if (($raw['is_error'] ?? false) || ! isset($raw['structured_output'])) {
            throw new \RuntimeException((string) ($raw['result'] ?? $raw['subtype'] ?? 'sin structured_output'));
        }

        $out = $raw['structured_output'];
        $questions = $this->normalizeQuestions($out['questions'] ?? []);

        // Menos del 80% de lo pedido suele ser una respuesta truncada o de mala calidad.
        if (count($questions) < (int) floor($test['n'] * 0.8)) {
            throw new \RuntimeException('solo ' . count($questions) . " preguntas válidas de {$test['n']}");
        }

        return [
            'slug' => $test['slug'],
            'title' => $test['title'],
            'description' => trim((string) ($out['description'] ?? '')),
            // Claude Code también usa un modelo pequeño para tareas internas: nos quedamos con el que más escribió.
            'model' => collect($raw['modelUsage'] ?? [])->sortByDesc('outputTokens')->keys()->first() ?? $this->option('model'),
            'generated_at' => now()->toIso8601String(),
            'questions' => $questions,
        ];
    }

    /** Descarta preguntas mal formadas y las pasa al formato de opciones de la app. */
    private function normalizeQuestions(array $questions): array
    {
        $result = [];
        $seen = [];

        foreach ($questions as $q) {
            $type = $q['type'] ?? null;
            $content = trim((string) ($q['content'] ?? ''));
            $key = mb_strtolower($content);
            if (! in_array($type, self::TYPES, true) || $content === '' || isset($seen[$key])) {
                continue;
            }

            $options = match ($type) {
                'true_false' => is_bool($q['answer'] ?? null) ? [
                    ['content' => 'Verdadero', 'is_correct' => $q['answer']],
                    ['content' => 'Falso', 'is_correct' => ! $q['answer']],
                ] : null,
                'short_answer' => $this->shortAnswerOptions($q['accepted_answers'] ?? []),
                default => $this->choiceOptions($q['options'] ?? [], $type),
            };

            if (! $options) {
                continue;
            }

            $seen[$key] = true;
            $result[] = [
                'type' => $type,
                'content' => $content,
                'explanation' => trim((string) ($q['explanation'] ?? '')) ?: null,
                'options' => $options,
            ];
        }

        return $result;
    }

    private function choiceOptions(array $options, string $type): ?array
    {
        $clean = [];
        foreach ($options as $o) {
            $text = trim((string) ($o['content'] ?? ''));
            if ($text !== '' && ! in_array(mb_strtolower($text), array_map(fn ($c) => mb_strtolower($c['content']), $clean), true)) {
                $clean[] = ['content' => $text, 'is_correct' => (bool) ($o['is_correct'] ?? false)];
            }
        }

        $correct = count(array_filter($clean, fn ($o) => $o['is_correct']));
        $valid = count($clean) >= 3 && count($clean) <= 5 && match ($type) {
            'multiple_choice' => $correct === 1,
            'multiple_select' => $correct >= 2 && $correct < count($clean),
        };

        return $valid ? $clean : null;
    }

    private function shortAnswerOptions(array $answers): ?array
    {
        $answers = array_values(array_unique(array_filter(array_map(fn ($a) => trim((string) $a), $answers))));

        return $answers ? array_map(fn ($a) => ['content' => $a, 'is_correct' => true], $answers) : null;
    }

    private function looksLikeUsageLimit(string $message): bool
    {
        return (bool) preg_match('/usage limit|rate limit|limit reached|resets? at|quota|429/i', $message);
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
Eres un generador de bancos de preguntas para una web española de tests online al estilo de Daypo.
Escribes en español de España, con ortografía y tildes correctas.
Prioridad absoluta: que cada respuesta marcada como correcta sea verdaderamente correcta según la normativa y el conocimiento vigentes en noviembre de 2026.
Si no estás seguro de un número de artículo, de una cifra o de una fecha, no lo uses en la pregunta: pregunta otra cosa de la que estés seguro.
No inventes normas, artículos ni datos.
TXT;
    }

    private function userPrompt(array $test, ?array $category, ?array $parent): string
    {
        $area = $parent['name'] ?? 'General';
        $catName = $category['name'] ?? '';
        $difficulty = ['easy' => 'fácil', 'medium' => 'media', 'hard' => 'difícil'][$test['diff'] ?? 'medium'];
        $mix = match ($test['mix'] ?? ($test['cat'] === 'fp' || str_starts_with($test['cat'], 'fp-') ? 'fp' : 'oposicion')) {
            'oposicion' => <<<'TXT'
- Unas 9 de cada 10 preguntas son "multiple_choice" con 4 opciones y una sola correcta, como en un examen tipo test oficial de oposición.
- El resto son "true_false".
- Usa el estilo de examen de oposición: «Según el artículo X de la Ley Y, …», «Señale la respuesta incorrecta», «¿Cuál de las siguientes…?». Los distractores deben ser plausibles: plazos, órganos o porcentajes cercanos al correcto.
TXT,
            'short' => <<<'TXT'
- Unas 6 de cada 10 preguntas son "short_answer", ejercicios con una respuesta concreta y corta (por ejemplo «192.168.1.63» o «/26»). En accepted_answers incluye todas las formas válidas de escribir la respuesta.
- El resto son "multiple_choice" con 4 opciones y una sola correcta.
TXT,
            default => <<<'TXT'
- Unas 6 de cada 10 son "multiple_choice" (de 3 a 4 opciones, una sola correcta).
- Unas 1,5 de cada 10 son "true_false".
- Unas 1,5 de cada 10 son "short_answer", con una respuesta de 1 a 3 palabras o un comando o valor exacto. En accepted_answers incluye las variantes válidas (mayúsculas, con o sin artículo, siglas y nombre completo).
- Alrededor de 1 de cada 10 son "multiple_select" (4 o 5 opciones, al menos 2 correctas y al menos 1 incorrecta; el enunciado debe decir que hay varias correctas).
- Usa el estilo de examen de módulo de FP: preguntas de teoría y preguntas prácticas (fragmentos de código, comandos o casos breves).
TXT,
        };

        return <<<TXT
Crea un test para la sección «{$area} › {$catName}».

Título del test: {$test['title']}
Contenido que debe cubrir: {$test['brief']}
Número de preguntas: exactamente {$test['n']}
Dificultad: {$difficulty}

Reparto de tipos de pregunta:
{$mix}

Reglas:
- Reparte las preguntas por todo el contenido indicado, sin repetir conceptos.
- En multiple_choice, varía la posición de la opción correcta. Usa como mucho una vez «Todas las anteriores son correctas» o «Ninguna es correcta».
- No pongas letras (a, b, c…) delante de las opciones.
- La explicación es de 1 o 2 frases y justifica la respuesta (cita la norma o el artículo solo si estás seguro).
- En true_false, indica en "answer" si la afirmación es verdadera (true) o falsa (false), y no rellenes las opciones.
- En short_answer, deja "options" vacío y rellena "accepted_answers".
- La descripción del test es de 1 o 2 frases, escritas como las escribiría un usuario que sube su test a Daypo (por ejemplo «Preguntas del tema 4 para repasar antes del examen, sacadas del temario de 2026»). Puede ser informal, pero sin faltas de ortografía.
TXT;
    }

    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'description' => ['type' => 'string'],
                'questions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string', 'enum' => self::TYPES],
                            'content' => ['type' => 'string'],
                            'explanation' => ['type' => 'string'],
                            'options' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'content' => ['type' => 'string'],
                                        'is_correct' => ['type' => 'boolean'],
                                    ],
                                    'required' => ['content', 'is_correct'],
                                ],
                            ],
                            'answer' => ['type' => 'boolean'],
                            'accepted_answers' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                        'required' => ['type', 'content', 'explanation'],
                    ],
                ],
            ],
            'required' => ['description', 'questions'],
        ];
    }
}
