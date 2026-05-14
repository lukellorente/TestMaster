<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPoint;
use App\Models\UserStat;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $seedUser = $this->resolveSeedUser();
        $now = Carbon::now();

        $this->truncateQuestionDomainTables();

        [$categoriesBySlug, $categoriesCount] = $this->seedCategories($now);
        [$testsBySlug, $testsCount] = $this->seedTests($seedUser->id, $now);

        $this->seedTestCategories($testsBySlug, $categoriesBySlug);
        [$questionsCount, $optionsCount] = $this->seedQuestions($testsBySlug, $now);
        $this->syncCategoryCounters();

        if ($this->command) {
            $this->command->info("Seed completado: {$categoriesCount} categorias, {$testsCount} tests, {$questionsCount} preguntas, {$optionsCount} opciones.");
            $this->command->info('Tipos activos: multiple_choice, true_false, short_answer');
        }
    }

    private function resolveSeedUser(): User
    {
        $seedUser = User::where('email', 'admin@example.com')->first();

        if (! $seedUser) {
            $seedUser = User::first();
        }

        if (! $seedUser) {
            $adminPassword = env('SEED_ADMIN_PASSWORD', 'adminpassword');

            $seedUser = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password_hash' => Hash::make($adminPassword),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
        }

        UserStat::firstOrCreate(['user_id' => $seedUser->id]);
        UserPoint::firstOrCreate(['user_id' => $seedUser->id]);

        return $seedUser;
    }

    private function truncateQuestionDomainTables(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'question_responses',
            'test_attempts',
            'question_order_items',
            'question_blanks',
            'question_options',
            'questions',
            'test_categories',
            'tests',
            'categories',
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function seedCategories(Carbon $now): array
    {
        $categories = [
            [
                'name' => 'Cultura General',
                'slug' => 'cultura-general',
                'description' => 'Preguntas variadas de conocimiento general al estilo Daypo.',
                'icon' => 'sparkles',
                'color' => '#1D4ED8',
            ],
            [
                'name' => 'Programacion',
                'slug' => 'programacion',
                'description' => 'Lenguajes, frameworks y logica de desarrollo.',
                'icon' => 'code',
                'color' => '#0F766E',
            ],
            [
                'name' => 'Tecnologia',
                'slug' => 'tecnologia',
                'description' => 'Conceptos de software, internet y sistemas digitales.',
                'icon' => 'cpu',
                'color' => '#0369A1',
            ],
            [
                'name' => 'Historia',
                'slug' => 'historia',
                'description' => 'Hechos y personajes clave de la historia universal.',
                'icon' => 'book-open',
                'color' => '#92400E',
            ],
            [
                'name' => 'Geografia',
                'slug' => 'geografia',
                'description' => 'Paises, capitales y geografia fisica.',
                'icon' => 'globe',
                'color' => '#0E7490',
            ],
            [
                'name' => 'Idiomas',
                'slug' => 'idiomas',
                'description' => 'Vocabulario y gramatica basica de idiomas.',
                'icon' => 'language',
                'color' => '#BE185D',
            ],
            [
                'name' => 'Ciencia',
                'slug' => 'ciencia',
                'description' => 'Fisica, quimica, biologia y matematicas.',
                'icon' => 'flask',
                'color' => '#166534',
            ],
            [
                'name' => 'Entretenimiento',
                'slug' => 'entretenimiento',
                'description' => 'Cine, series y cultura pop.',
                'icon' => 'tv',
                'color' => '#7C3AED',
            ],
            [
                'name' => 'Deportes',
                'slug' => 'deportes',
                'description' => 'Futbol y deportes internacionales.',
                'icon' => 'trophy',
                'color' => '#B91C1C',
            ],
            [
                'name' => 'Arte y Literatura',
                'slug' => 'arte-literatura',
                'description' => 'Libros, autores y arte clasico y moderno.',
                'icon' => 'palette',
                'color' => '#7E22CE',
            ],
        ];

        $categoriesBySlug = [];

        foreach ($categories as $category) {
            $id = DB::table('categories')->insertGetId([
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => $category['description'],
                'icon' => $category['icon'],
                'color' => $category['color'],
                'parent_id' => null,
                'tests_count' => 0,
                'created_at' => $now,
            ]);

            $categoriesBySlug[$category['slug']] = $id;
        }

        return [$categoriesBySlug, count($categoriesBySlug)];
    }

    private function seedTests(int $seedUserId, Carbon $now): array
    {
        $tests = [
            [
                'slug' => 'laravel-fundamentos',
                'title' => 'Laravel - Fundamentos para crear tests',
                'description' => 'Quiz para repasar rutas, Eloquent, middlewares y Blade.',
                'difficulty' => 'medium',
                'pass_score' => 70,
                'time_limit' => 900,
                'category_slugs' => ['programacion', 'tecnologia'],
            ],
            [
                'slug' => 'javascript-esencial',
                'title' => 'JavaScript esencial y moderno',
                'description' => 'Preguntas sobre ES6+, async/await y metodos de arrays.',
                'difficulty' => 'medium',
                'pass_score' => 68,
                'time_limit' => 840,
                'category_slugs' => ['programacion', 'tecnologia'],
            ],
            [
                'slug' => 'sql-relacional',
                'title' => 'SQL y bases de datos relacionales',
                'description' => 'SELECT, JOIN, GROUP BY, claves y transacciones.',
                'difficulty' => 'medium',
                'pass_score' => 70,
                'time_limit' => 900,
                'category_slugs' => ['programacion', 'tecnologia'],
            ],
            [
                'slug' => 'historia-universal',
                'title' => 'Historia universal en 10 minutos',
                'description' => 'Fechas y acontecimientos clasicos de historia general.',
                'difficulty' => 'easy',
                'pass_score' => 65,
                'time_limit' => 720,
                'category_slugs' => ['historia', 'cultura-general'],
            ],
            [
                'slug' => 'geografia-mundial',
                'title' => 'Geografia mundial: capitales y continentes',
                'description' => 'Capitales, rios, montanas y geografia fisica.',
                'difficulty' => 'easy',
                'pass_score' => 65,
                'time_limit' => 720,
                'category_slugs' => ['geografia', 'cultura-general'],
            ],
            [
                'slug' => 'ingles-basico',
                'title' => 'Ingles basico para quiz rapido',
                'description' => 'Gramatica y vocabulario elemental.',
                'difficulty' => 'easy',
                'pass_score' => 60,
                'time_limit' => 600,
                'category_slugs' => ['idiomas'],
            ],
            [
                'slug' => 'ciencia-general',
                'title' => 'Ciencia general y matematicas basicas',
                'description' => 'Conceptos de fisica, biologia y quimica.',
                'difficulty' => 'easy',
                'pass_score' => 60,
                'time_limit' => 780,
                'category_slugs' => ['ciencia', 'cultura-general'],
            ],
            [
                'slug' => 'cine-series-pop',
                'title' => 'Cine y series de cultura pop',
                'description' => 'Preguntas populares para dinamicas tipo Daypo.',
                'difficulty' => 'easy',
                'pass_score' => 60,
                'time_limit' => 600,
                'category_slugs' => ['entretenimiento', 'cultura-general'],
            ],
            [
                'slug' => 'deportes-global',
                'title' => 'Deportes globales: futbol y mas',
                'description' => 'Competiciones, reglas basicas y datos historicos.',
                'difficulty' => 'easy',
                'pass_score' => 65,
                'time_limit' => 660,
                'category_slugs' => ['deportes', 'cultura-general'],
            ],
            [
                'slug' => 'arte-literatura-clasicos',
                'title' => 'Arte y literatura clasicos',
                'description' => 'Autores, obras y movimientos artisticos.',
                'difficulty' => 'medium',
                'pass_score' => 68,
                'time_limit' => 780,
                'category_slugs' => ['arte-literatura', 'cultura-general'],
            ],
        ];

        $testsBySlug = [];

        foreach ($tests as $test) {
            $testId = DB::table('tests')->insertGetId([
                'user_id' => $seedUserId,
                'title' => $test['title'],
                'description' => $test['description'],
                'is_public' => true,
                'is_featured' => false,
                'password' => null,
                'time_limit' => $test['time_limit'],
                'max_attempts' => -1,
                'pass_score' => $test['pass_score'],
                'shuffle_questions' => true,
                'shuffle_answers' => true,
                'show_correct_answers' => true,
                'show_explanations' => true,
                'difficulty' => $test['difficulty'],
                'status' => 'published',
                'views_count' => random_int(30, 600),
                'attempts_count' => random_int(10, 150),
                'average_score' => random_int(60, 89),
                'created_at' => $now,
                'updated_at' => $now,
                'published_at' => $now,
            ]);

            $testsBySlug[$test['slug']] = [
                'id' => $testId,
                'category_slugs' => $test['category_slugs'],
            ];
        }

        return [$testsBySlug, count($testsBySlug)];
    }

    private function seedTestCategories(array $testsBySlug, array $categoriesBySlug): void
    {
        $rows = [];

        foreach ($testsBySlug as $testData) {
            foreach ($testData['category_slugs'] as $categorySlug) {
                if (! isset($categoriesBySlug[$categorySlug])) {
                    continue;
                }

                $rows[] = [
                    'test_id' => $testData['id'],
                    'category_id' => $categoriesBySlug[$categorySlug],
                ];
            }
        }

        if ($rows !== []) {
            DB::table('test_categories')->insert($rows);
        }
    }

    private function seedQuestions(array $testsBySlug, Carbon $now): array
    {
        $tests = $this->questionBank();
        $questionsCount = 0;
        $optionsCount = 0;

        foreach ($tests as $testSlug => $questions) {
            if (! isset($testsBySlug[$testSlug])) {
                continue;
            }

            foreach (array_values($questions) as $index => $question) {
                $questionId = DB::table('questions')->insertGetId([
                    'test_id' => $testsBySlug[$testSlug]['id'],
                    'type' => $question['type'],
                    'content' => $question['content'],
                    'explanation' => $question['explanation'] ?? null,
                    'points' => $question['points'] ?? 1,
                    'order_index' => $index + 1,
                    'time_limit' => $question['time_limit'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $questionsCount++;

                foreach ($question['options'] as $optionIndex => $option) {
                    DB::table('question_options')->insert([
                        'question_id' => $questionId,
                        'content' => $option['content'],
                        'is_correct' => (bool) $option['is_correct'],
                        'order_index' => $optionIndex + 1,
                        'created_at' => $now,
                    ]);

                    $optionsCount++;
                }
            }
        }

        return [$questionsCount, $optionsCount];
    }

    private function syncCategoryCounters(): void
    {
        DB::table('categories')->update(['tests_count' => 0]);

        $counts = DB::table('test_categories')
            ->select('category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')
            ->get();

        foreach ($counts as $count) {
            DB::table('categories')
                ->where('id', $count->category_id)
                ->update(['tests_count' => $count->total]);
        }
    }

    private function questionBank(): array
    {
        return [
            'laravel-fundamentos' => [
                [
                    'type' => 'multiple_choice',
                    'content' => 'Cual comando crea un controlador de recurso en Laravel?',
                    'explanation' => 'make:controller con --resource genera metodos REST.',
                    'points' => 2,
                    'time_limit' => 50,
                    'options' => [
                        ['content' => 'php artisan make:controller PostController --resource', 'is_correct' => true],
                        ['content' => 'php artisan controller:create PostController', 'is_correct' => false],
                        ['content' => 'php artisan make:resource-controller PostController', 'is_correct' => false],
                        ['content' => 'php artisan route:resource PostController', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Eloquent permite definir relaciones entre modelos.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Que comando ejecuta migraciones pendientes?',
                    'points' => 2,
                    'options' => [
                        ['content' => 'php artisan migrate', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Que archivo suele contener las variables de entorno?',
                    'points' => 1,
                    'options' => [
                        ['content' => '.env', 'is_correct' => true],
                        ['content' => 'config/app.php', 'is_correct' => false],
                        ['content' => 'routes/web.php', 'is_correct' => false],
                        ['content' => 'composer.lock', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Blade es el motor de plantillas de Laravel.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Nombre de la fachada para cache en Laravel.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Cache', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Cual relacion representa uno a muchos?',
                    'points' => 2,
                    'options' => [
                        ['content' => 'hasMany', 'is_correct' => true],
                        ['content' => 'belongsToManyOne', 'is_correct' => false],
                        ['content' => 'singleRelation', 'is_correct' => false],
                        ['content' => 'belongsOne', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Laravel usa Composer para dependencias.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
            ],
            'javascript-esencial' => [
                [
                    'type' => 'multiple_choice',
                    'content' => 'Que metodo crea un nuevo array transformando cada elemento?',
                    'points' => 2,
                    'options' => [
                        ['content' => 'map()', 'is_correct' => true],
                        ['content' => 'filter()', 'is_correct' => false],
                        ['content' => 'forEach()', 'is_correct' => false],
                        ['content' => 'every()', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Las arrow functions tienen this propio.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => false],
                        ['content' => 'Falso', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Palabra clave para declarar una constante en JavaScript.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'const', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Que devuelve una funcion async sin return?',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Promise resuelta con undefined', 'is_correct' => true],
                        ['content' => 'null', 'is_correct' => false],
                        ['content' => 'error de sintaxis', 'is_correct' => false],
                        ['content' => '0', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Template literals permiten multilinea.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Metodo para unir elementos de un array en un string.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'join', 'is_correct' => true],
                        ['content' => 'join()', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Diferencia principal entre let y var?',
                    'points' => 2,
                    'options' => [
                        ['content' => 'let tiene scope de bloque y var de funcion', 'is_correct' => true],
                        ['content' => 'var no existe en JS moderno', 'is_correct' => false],
                        ['content' => 'let solo funciona en arrays', 'is_correct' => false],
                        ['content' => 'No hay diferencia', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'async/await funciona sobre promesas.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
            ],
            'sql-relacional' => [
                [
                    'type' => 'multiple_choice',
                    'content' => 'Que comando recupera datos en SQL?',
                    'points' => 2,
                    'options' => [
                        ['content' => 'SELECT', 'is_correct' => true],
                        ['content' => 'GET', 'is_correct' => false],
                        ['content' => 'READ', 'is_correct' => false],
                        ['content' => 'FETCH ROWS', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'PRIMARY KEY permite NULL.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => false],
                        ['content' => 'Falso', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Comando para insertar filas nuevas.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'INSERT', 'is_correct' => true],
                        ['content' => 'INSERT INTO', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Clausula para filtrar registros.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'WHERE', 'is_correct' => true],
                        ['content' => 'SORT', 'is_correct' => false],
                        ['content' => 'LIMITER', 'is_correct' => false],
                        ['content' => 'DISTINCT BY', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'DELETE borra estructura y datos de la tabla.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => false],
                        ['content' => 'Falso', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Comando para modificar registros existentes.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'UPDATE', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Que representa ACID?',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Atomicity, Consistency, Isolation, Durability', 'is_correct' => true],
                        ['content' => 'Access, Control, Integration, Data', 'is_correct' => false],
                        ['content' => 'Add, Compute, Index, Deploy', 'is_correct' => false],
                        ['content' => 'Auto, Cache, Input, Data', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Una FOREIGN KEY crea relacion entre tablas.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
            ],
            'historia-universal' => [
                [
                    'type' => 'multiple_choice',
                    'content' => 'En que anio se produjo la llegada de Colon a America?',
                    'points' => 1,
                    'options' => [
                        ['content' => '1492', 'is_correct' => true],
                        ['content' => '1480', 'is_correct' => false],
                        ['content' => '1505', 'is_correct' => false],
                        ['content' => '1460', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'La Segunda Guerra Mundial termino en 1945.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Primer presidente de Estados Unidos.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'George Washington', 'is_correct' => true],
                        ['content' => 'Washington', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Caida del Muro de Berlin?',
                    'points' => 2,
                    'options' => [
                        ['content' => '1989', 'is_correct' => true],
                        ['content' => '1991', 'is_correct' => false],
                        ['content' => '1985', 'is_correct' => false],
                        ['content' => '1979', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'La Revolucion Francesa comenzo en 1789.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Ciudad asociada al tratado de 1919 que cerro la Primera Guerra Mundial.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Versalles', 'is_correct' => true],
                        ['content' => 'Versailles', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Quien lidero la URSS durante gran parte de la Segunda Guerra Mundial?',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Stalin', 'is_correct' => true],
                        ['content' => 'Lenin', 'is_correct' => false],
                        ['content' => 'Trotsky', 'is_correct' => false],
                        ['content' => 'Jruschov', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Roma nunca conquisto Britania.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => false],
                        ['content' => 'Falso', 'is_correct' => true],
                    ],
                ],
            ],
            'geografia-mundial' => [
                [
                    'type' => 'multiple_choice',
                    'content' => 'Capital de Espana.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Madrid', 'is_correct' => true],
                        ['content' => 'Barcelona', 'is_correct' => false],
                        ['content' => 'Valencia', 'is_correct' => false],
                        ['content' => 'Sevilla', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'El oceano Pacifico es el mas grande del planeta.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Continente donde se encuentra Egipto.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Africa', 'is_correct' => true],
                        ['content' => 'africa', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Montana mas alta sobre el nivel del mar.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Monte Everest', 'is_correct' => true],
                        ['content' => 'K2', 'is_correct' => false],
                        ['content' => 'Aconcagua', 'is_correct' => false],
                        ['content' => 'Kilimanjaro', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Australia es el continente mas pequeno.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Numero de continentes segun el modelo escolar mas comun.',
                    'points' => 2,
                    'options' => [
                        ['content' => '7', 'is_correct' => true],
                        ['content' => 'siete', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Capital de Japon.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Tokio', 'is_correct' => true],
                        ['content' => 'Osaka', 'is_correct' => false],
                        ['content' => 'Kioto', 'is_correct' => false],
                        ['content' => 'Nagoya', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'El Sahara es un desierto en Africa.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
            ],
            'ingles-basico' => [
                [
                    'type' => 'multiple_choice',
                    'content' => 'Past tense of go.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'went', 'is_correct' => true],
                        ['content' => 'goed', 'is_correct' => false],
                        ['content' => 'gone', 'is_correct' => false],
                        ['content' => 'going', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Their and there are homophones.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'True', 'is_correct' => true],
                        ['content' => 'False', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Plural of child.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'children', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Choose the correct sentence.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'How much money do you have?', 'is_correct' => true],
                        ['content' => 'How many money do you have?', 'is_correct' => false],
                        ['content' => 'How much moneys do you have?', 'is_correct' => false],
                        ['content' => 'How many moneys do you have?', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Information is countable.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'True', 'is_correct' => false],
                        ['content' => 'False', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Opposite of easy.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'hard', 'is_correct' => true],
                        ['content' => 'difficult', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'I saw ___ elephant at the zoo.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'an', 'is_correct' => true],
                        ['content' => 'a', 'is_correct' => false],
                        ['content' => 'the', 'is_correct' => false],
                        ['content' => 'no article', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'In English, adjectives usually go before nouns.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'True', 'is_correct' => true],
                        ['content' => 'False', 'is_correct' => false],
                    ],
                ],
            ],
            'ciencia-general' => [
                [
                    'type' => 'multiple_choice',
                    'content' => 'Formula quimica del agua.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'H2O', 'is_correct' => true],
                        ['content' => 'CO2', 'is_correct' => false],
                        ['content' => 'O2', 'is_correct' => false],
                        ['content' => 'H2O2', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'La velocidad de la luz ronda 300000 km/s.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Cuantos planetas tiene oficialmente el sistema solar?',
                    'points' => 2,
                    'options' => [
                        ['content' => '8', 'is_correct' => true],
                        ['content' => 'ocho', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Resultado de 2x + 5 = 15.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'x = 5', 'is_correct' => true],
                        ['content' => 'x = 10', 'is_correct' => false],
                        ['content' => 'x = 7.5', 'is_correct' => false],
                        ['content' => 'x = 2', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Las plantas convierten luz solar en energia por fotosintesis.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Capa atmosferica que nos protege de gran parte de la radiacion UV.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'capa de ozono', 'is_correct' => true],
                        ['content' => 'ozono', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Valor aproximado de pi con dos decimales.',
                    'points' => 1,
                    'options' => [
                        ['content' => '3.14', 'is_correct' => true],
                        ['content' => '3.41', 'is_correct' => false],
                        ['content' => '2.14', 'is_correct' => false],
                        ['content' => '4.13', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'El carbono puede formar diamantes.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
            ],
            'cine-series-pop' => [
                [
                    'type' => 'multiple_choice',
                    'content' => 'Como se llama la galaxia donde ocurre Star Wars?',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Una galaxia muy muy lejana', 'is_correct' => true],
                        ['content' => 'Andromeda', 'is_correct' => false],
                        ['content' => 'Via Lactea', 'is_correct' => false],
                        ['content' => 'Naboo Prime', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'The Simpsons es una serie animada.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Apellido del protagonista principal de Indiana Jones.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Jones', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Que pelicula tiene al personaje Jack Sparrow?',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Piratas del Caribe', 'is_correct' => true],
                        ['content' => 'Titanic', 'is_correct' => false],
                        ['content' => 'Avatar', 'is_correct' => false],
                        ['content' => 'Gladiator', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Netflix comenzo como servicio de streaming.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => false],
                        ['content' => 'Falso', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Nombre de la ciudad de Batman.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Gotham', 'is_correct' => true],
                        ['content' => 'Gotham City', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Quien dirigio Titanic?',
                    'points' => 1,
                    'options' => [
                        ['content' => 'James Cameron', 'is_correct' => true],
                        ['content' => 'Steven Spielberg', 'is_correct' => false],
                        ['content' => 'Ridley Scott', 'is_correct' => false],
                        ['content' => 'Christopher Nolan', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Friends tiene 10 temporadas.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
            ],
            'deportes-global' => [
                [
                    'type' => 'multiple_choice',
                    'content' => 'Cuantos jugadores tiene un equipo de futbol en campo?',
                    'points' => 1,
                    'options' => [
                        ['content' => '11', 'is_correct' => true],
                        ['content' => '10', 'is_correct' => false],
                        ['content' => '9', 'is_correct' => false],
                        ['content' => '12', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Los Juegos Olimpicos se celebran cada 4 anos.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Pais ganador del Mundial de futbol 2010.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Espana', 'is_correct' => true],
                        ['content' => 'España', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Deporte donde se usa la palabra triple doble con frecuencia.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Baloncesto', 'is_correct' => true],
                        ['content' => 'Tenis', 'is_correct' => false],
                        ['content' => 'Golf', 'is_correct' => false],
                        ['content' => 'Natacion', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'El tenis se juega siempre sobre cesped.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => false],
                        ['content' => 'Falso', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Apellido de Michael, leyenda de la NBA.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Jordan', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Que torneo de tenis se juega en tierra batida en Paris?',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Roland Garros', 'is_correct' => true],
                        ['content' => 'Wimbledon', 'is_correct' => false],
                        ['content' => 'US Open', 'is_correct' => false],
                        ['content' => 'Australian Open', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'La maraton oficial mide 42.195 km.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
            ],
            'arte-literatura-clasicos' => [
                [
                    'type' => 'multiple_choice',
                    'content' => 'Quien escribio Don Quijote de la Mancha?',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Miguel de Cervantes', 'is_correct' => true],
                        ['content' => 'Lope de Vega', 'is_correct' => false],
                        ['content' => 'Federico Garcia Lorca', 'is_correct' => false],
                        ['content' => 'Benito Perez Galdos', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'La Mona Lisa fue pintada por Leonardo da Vinci.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Autor de Cien anos de soledad.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Gabriel Garcia Marquez', 'is_correct' => true],
                        ['content' => 'Gabo', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Movimiento artistico de Picasso y Braque.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Cubismo', 'is_correct' => true],
                        ['content' => 'Impresionismo', 'is_correct' => false],
                        ['content' => 'Barroco', 'is_correct' => false],
                        ['content' => 'Surrealismo', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'Shakespeare escribio Romeo y Julieta.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'short_answer',
                    'content' => 'Apellido de Frida, pintora mexicana.',
                    'points' => 2,
                    'options' => [
                        ['content' => 'Kahlo', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'multiple_choice',
                    'content' => 'Quien escribio La Odisea segun la tradicion clasica?',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Homero', 'is_correct' => true],
                        ['content' => 'Virgilio', 'is_correct' => false],
                        ['content' => 'Socrates', 'is_correct' => false],
                        ['content' => 'Platon', 'is_correct' => false],
                    ],
                ],
                [
                    'type' => 'true_false',
                    'content' => 'El surrealismo exploro mucho el subconsciente.',
                    'points' => 1,
                    'options' => [
                        ['content' => 'Verdadero', 'is_correct' => true],
                        ['content' => 'Falso', 'is_correct' => false],
                    ],
                ],
            ],
        ];
    }
}