<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPoint;
use App\Models\UserStat;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Importa los tests de database/data/exams/generated/*.json (creados con `php artisan exams:generate`).
 *
 * Es idempotente: borra los tests de las cuentas de autor del catálogo y los vuelve a crear.
 * Los números (visitas, intentos, fechas, autor) son deterministas por slug, así que no
 * cambian entre ejecuciones.
 */
class ExamLibrarySeeder extends Seeder
{
    /** Cuentas de autor de los tests importados. No tienen contraseña utilizable. */
    private const AUTHORS = [
        'oposiciones' => ['opositora_madrid', 'Laura G.', 'aux_admin_2026', 'Javi_C1', 'TemarioLibre', 'oposito_sevilla', 'M. Ruiz'],
        'fp' => ['dam2_valencia', 'ProfeFOL', 'Carlos_ASIR', 'smr_repaso', 'Nerea DAW', 'Tcae_Lucia', 'apuntesFP'],
    ];

    public function run(): void
    {
        $catalog = json_decode(file_get_contents(database_path('data/exams/catalog.json')), true, flags: JSON_THROW_ON_ERROR);
        $dir = database_path('data/exams/generated');

        $categoryIds = $this->seedCategories($catalog['categories']);
        $parents = collect($catalog['categories'])->pluck('parent', 'slug');
        $authors = $this->seedAuthors();

        // Fuera los tests de una importación anterior (preguntas, opciones e intentos caen en cascada).
        DB::table('tests')->whereIn('user_id', collect($authors)->flatten())->delete();

        $imported = 0;
        $questions = 0;
        $missing = [];

        foreach ($catalog['tests'] as $test) {
            $file = "{$dir}/{$test['slug']}.json";
            if (! is_file($file)) {
                $missing[] = $test['slug'];

                continue;
            }

            $data = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
            $area = $parents[$test['cat']] ?? 'fp';
            $questions += $this->importTest($test, $data, $area, $authors[$area], [$categoryIds[$test['cat']], $categoryIds[$area]]);
            $imported++;
        }

        $this->syncCategoryCounters();

        if ($this->command) {
            $this->command->info("Biblioteca importada: {$imported} tests, {$questions} preguntas.");
            if ($missing) {
                $this->command->warn(count($missing) . ' tests del catálogo sin generar todavía (php artisan exams:generate).');
            }
        }
    }

    private function seedCategories(array $categories): array
    {
        $ids = [];
        // Primero los padres, para poder enlazar parent_id.
        usort($categories, fn ($a, $b) => isset($a['parent']) <=> isset($b['parent']));

        foreach ($categories as $c) {
            DB::table('categories')->updateOrInsert(['slug' => $c['slug']], [
                'name' => $c['name'],
                'description' => $c['description'],
                'icon' => $c['icon'],
                'color' => $c['color'],
                'parent_id' => isset($c['parent']) ? $ids[$c['parent']] : null,
                'created_at' => now(),
            ]);
            $ids[$c['slug']] = DB::table('categories')->where('slug', $c['slug'])->value('id');
        }

        return $ids;
    }

    /** @return array<string, int[]> ids de autor por área */
    private function seedAuthors(): array
    {
        $ids = [];
        foreach (self::AUTHORS as $area => $names) {
            foreach ($names as $name) {
                $user = User::firstOrCreate(
                    ['email' => Str::slug($name, '.') . '@autores.testmaster.invalid'],
                    [
                        'name' => $name,
                        'password_hash' => Hash::make(Str::random(40)),
                        'role' => 'user',
                        'email_verified_at' => now(),
                    ]
                );
                UserStat::firstOrCreate(['user_id' => $user->id]);
                UserPoint::firstOrCreate(['user_id' => $user->id]);
                $ids[$area][] = $user->id;
            }
        }

        return $ids;
    }

    private function importTest(array $test, array $data, string $area, array $authorIds, array $categoryIds): int
    {
        mt_srand(crc32($test['slug']));
        $isOpo = $area === 'oposiciones';
        $count = count($data['questions']);

        // Fechas entre enero de 2025 y hoy; los tests más populares son algo más antiguos.
        $created = Carbon::create(2025, 1, 1)->addMinutes(mt_rand(0, (int) Carbon::create(2025, 1, 1)->diffInMinutes(now()->subDays(3))));
        $attempts = mt_rand(15, $isOpo ? 2400 : 900);

        $testId = DB::table('tests')->insertGetId([
            'user_id' => $authorIds[mt_rand(0, count($authorIds) - 1)],
            'title' => $data['title'] ?? $test['title'],
            'description' => $data['description'] ?: null,
            'is_public' => true,
            'is_featured' => false,
            'password' => null,
            // Oposiciones: ~1 min por pregunta, como en examen. FP: sin límite la mayoría de las veces.
            'time_limit' => $isOpo ? $count * 60 : (mt_rand(0, 2) === 0 ? $count * 75 : null),
            'max_attempts' => -1,
            'pass_score' => $isOpo ? 50 : mt_rand(0, 1) * 10 + 50,
            'wrong_penalty' => $test['penalty'] ?? ($isOpo ? [0.3333, 0.3333, 0.25, 0][mt_rand(0, 3)] : 0),
            'shuffle_questions' => (bool) mt_rand(0, 1),
            'shuffle_answers' => true,
            'show_correct_answers' => true,
            'show_explanations' => true,
            'display_mode' => mt_rand(0, 3) === 0 ? 'all' : 'paged',
            'difficulty' => $test['diff'] ?? 'medium',
            'status' => 'published',
            'views_count' => $attempts * mt_rand(2, 4) + mt_rand(0, 200),
            'attempts_count' => $attempts,
            'average_score' => mt_rand(4800, 7900) / 100,
            'created_at' => $created,
            'updated_at' => $created->copy()->addDays(mt_rand(0, 60))->min(now()),
            'published_at' => $created,
        ]);

        DB::table('test_categories')->insert(array_map(
            fn ($categoryId) => ['test_id' => $testId, 'category_id' => $categoryId],
            array_unique($categoryIds)
        ));

        foreach ($data['questions'] as $index => $q) {
            $questionId = DB::table('questions')->insertGetId([
                'test_id' => $testId,
                'type' => $q['type'],
                'content' => $q['content'],
                'explanation' => $q['explanation'] ?? null,
                'points' => 1,
                'order_index' => $index + 1,
                'created_at' => $created,
                'updated_at' => $created,
            ]);

            DB::table('question_options')->insert(array_map(fn ($o, $i) => [
                'question_id' => $questionId,
                'content' => $o['content'],
                'is_correct' => (bool) $o['is_correct'],
                'order_index' => $i + 1,
                'created_at' => $created,
            ], $q['options'], array_keys($q['options'])));
        }

        mt_srand();

        return $count;
    }

    private function syncCategoryCounters(): void
    {
        DB::table('categories')->update(['tests_count' => 0]);

        DB::table('test_categories')
            ->select('category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')
            ->get()
            ->each(fn ($row) => DB::table('categories')->where('id', $row->category_id)->update(['tests_count' => $row->total]));
    }
}
