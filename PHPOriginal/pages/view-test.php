<?php
session_start();
require_once '../config/database.php';

$test_id = $_GET['id'] ?? 0;

// Obtener información del test
$test = fetch_one("
    SELECT t.*, u.name as author_name, u.id as author_id,
           (SELECT COUNT(*) FROM questions WHERE test_id = t.id) as num_questions,
           (SELECT COUNT(*) FROM test_attempts WHERE test_id = t.id AND is_completed = 1) as attempts_count,
           (SELECT AVG(score) FROM test_attempts WHERE test_id = t.id AND is_completed = 1) as avg_score
    FROM tests t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
", [$test_id]);

if (!$test) {
    die("Test no encontrado");
}

// Obtener categorías del test
$categories = fetch_all("
    SELECT c.name, c.icon 
    FROM categories c
    JOIN test_categories tc ON c.id = tc.category_id
    WHERE tc.test_id = ?
", [$test_id]);

// Verificar si el usuario ya ha hecho el test
$user_attempts = 0;
$last_attempt = null;
if (is_logged_in()) {
    $user_attempts = fetch_one("
        SELECT COUNT(*) as count 
        FROM test_attempts 
        WHERE user_id = ? AND test_id = ? AND is_completed = 1
    ", [get_user_id(), $test_id])['count'];
    
    $last_attempt = fetch_one("
        SELECT score, completed_at 
        FROM test_attempts 
        WHERE user_id = ? AND test_id = ? AND is_completed = 1 
        ORDER BY completed_at DESC 
        LIMIT 1
    ", [get_user_id(), $test_id]);
}

$page_title = $test['title'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
</head>
<body>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
    <!-- Header -->
    <header>
        <div class="header-content">
            <a href="/testmaster/index.php">
                <img src="/TestMaster/assets/images/logo.png" alt="TestMaster Logo" class="logo" width="250">
            </a>
            <div class="header-actions">
                <div class="theme-toggle" onclick="toggleTheme()" title="Cambiar tema">
                    <span class="theme-toggle-sr">Cambiar tema</span>
                    <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="theme-toggle__within" height="1em" width="1em" viewBox="0 0 32 32" fill="currentColor">
                        <clipPath id="theme-toggle__within__clip"><path d="M0 0h32v32h-32ZM6 16A1 1 0 0026 16 1 1 0 006 16" /></clipPath>
                        <g clip-path="url(#theme-toggle__within__clip)"><path d="M30.7 21.3 27.1 16l3.7-5.3c.4-.5.1-1.3-.6-1.4l-6.3-1.1-1.1-6.3c-.1-.6-.8-.9-1.4-.6L16 5l-5.4-3.7c-.5-.4-1.3-.1-1.4.6l-1 6.3-6.4 1.1c-.6.1-.9.9-.6 1.3L4.9 16l-3.7 5.3c-.4.5-.1 1.3.6 1.4l6.3 1.1 1.1 6.3c.1.6.8.9 1.4.6l5.3-3.7 5.3 3.7c.5.4 1.3.1 1.4-.6l1.1-6.3 6.3-1.1c.8-.1 1.1-.8.7-1.4zM16 25.1c-5.1 0-9.1-4.1-9.1-9.1 0-5.1 4.1-9.1 9.1-9.1s9.1 4.1 9.1 9.1c0 5.1-4 9.1-9.1 9.1z" /></g>
                        <path class="theme-toggle__within__circle" d="M16 7.7c-4.6 0-8.2 3.7-8.2 8.2s3.6 8.4 8.2 8.4 8.2-3.7 8.2-8.2-3.6-8.4-8.2-8.4zm0 14.4c-3.4 0-6.1-2.9-6.1-6.2s2.7-6.1 6.1-6.1c3.4 0 6.1 2.9 6.1 6.2s-2.7 6.1-6.1 6.1z" />
                        <path class="theme-toggle__within__inner" d="M16 9.5c-3.6 0-6.4 2.9-6.4 6.4s2.8 6.5 6.4 6.5 6.4-2.9 6.4-6.4-2.8-6.5-6.4-6.5z" />
                    </svg>
                </div>
                <a href="../index.php" class="link">volver</a>
                <?php if (is_logged_in()): ?>
                    <a href="/testmaster/pages/perfil.php?id=<?= get_user_id() ?>" class="link">mi panel</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main>
        <!-- Título y descripción -->
        <div style="max-width: 800px; margin: 0 auto 2rem;">
            <div style="margin-bottom: 1rem;">
                <?php foreach ($categories as $cat): ?>
                    <span style="background: #f8f9fa; color: #667eea; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; margin-right: 0.5rem;">
                        <i class="<?= $cat['icon'] ?>"></i> <?= htmlspecialchars($cat['name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
            
            <h1 style="font-size: 2rem; margin-bottom: 1rem;"><?= htmlspecialchars($test['title']) ?></h1>
            
            <?php if ($test['description']): ?>
                <p style="color: #718096; font-size: 1.1rem; line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($test['description'])) ?>
                </p>
            <?php endif; ?>
            
            <div style="margin-top: 1.5rem; color: #a0aec0; font-size: 0.9rem;">
                Creado por <strong style="color: #4a5568;"><?= htmlspecialchars($test['author_name']) ?></strong>
                el <?= date('d/m/Y', strtotime($test['created_at'])) ?>
            </div>
        </div>

        <!-- Información del test -->
        <div style="max-width: 800px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <!-- Preguntas -->
                <div style="background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-file-lines"></i></div>
                    <div style="font-size: 1.5rem; font-weight: 600; color: #667eea;">
                        <?= $test['num_questions'] ?>
                    </div>
                    <div style="color: #718096; font-size: 0.9rem;">Preguntas</div>
                </div>

                <!-- Tiempo -->
                <div style="background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-clock"></i></div>
                    <div style="font-size: 1.5rem; font-weight: 600; color: #667eea;">
                        <?= $test['time_limit'] ? round($test['time_limit'] / 60) . ' min' : 'Sin límite' ?>
                    </div>
                    <div style="color: #718096; font-size: 0.9rem;">Tiempo</div>
                </div>

                <!-- Dificultad -->
                <div style="background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">
                        <?php 
                        $diff_icons = ['easy' => '<i class="fa-solid fa-face-smile"></i>', 'medium' => '<i class="fa-solid fa-face-meh"></i>', 'hard' => '<i class="fa-solid fa-face-frown"></i>'];
                        echo $diff_icons[$test['difficulty']] ?? '<i class="fa-solid fa-question"></i>';
                        ?>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 600; color: #667eea;">
                        <?php 
                        $diff_text = ['easy' => 'Fácil', 'medium' => 'Medio', 'hard' => 'Difícil'];
                        echo $diff_text[$test['difficulty']] ?? 'N/A';
                        ?>
                    </div>
                    <div style="color: #718096; font-size: 0.9rem;">Dificultad</div>
                </div>

                <!-- Intentos -->
                <div style="background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-users"></i></div>
                    <div style="font-size: 1.5rem; font-weight: 600; color: #667eea;">
                        <?= $test['attempts_count'] ?>
                    </div>
                    <div style="color: #718096; font-size: 0.9rem;">Personas</div>
                </div>
            </div>

            <!-- Tu progreso (si ha hecho el test) -->
            <?php if (is_logged_in() && $last_attempt): ?>
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem;">Tu mejor resultado</h3>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 2rem; font-weight: 600;">
                                <?= number_format($last_attempt['score'], 1) ?>%
                            </div>
                            <div style="opacity: 0.9; font-size: 0.9rem;">
                                Realizado el <?= date('d/m/Y', strtotime($last_attempt['completed_at'])) ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.2rem;"><?= $user_attempts ?> intento<?= $user_attempts > 1 ? 's' : '' ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Configuración del test -->
            <div style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1.5rem; color: #1a202c;">Configuración del Test</h3>
                <div style="display: grid; gap: 1rem;">
                    <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f7fafc;">
                        <span style="color: #718096;">Nota mínima para aprobar</span>
                        <strong style="color: #667eea;"><?= $test['pass_score'] ?>%</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f7fafc;">
                        <span style="color: #718096;">Intentos permitidos</span>
                        <strong style="color: #667eea;"><?= $test['max_attempts'] == -1 ? 'Ilimitados' : $test['max_attempts'] ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f7fafc;">
                        <span style="color: #718096;">Orden de preguntas</span>
                        <strong style="color: #667eea;"><?= $test['shuffle_questions'] ? 'Aleatorio' : 'Fijo' ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.75rem 0;">
                        <span style="color: #718096;">Mostrar respuestas correctas</span>
                        <strong style="color: #667eea;"><?= $test['show_correct_answers'] ? 'Sí' : 'No' ?></strong>
                    </div>
                </div>
            </div>

            <!-- Botón para realizar el test -->
            <div style="text-align: center; margin-bottom: 2rem;">
                <?php if (!is_logged_in()): ?>
                    <p style="color: #718096; margin-bottom: 1rem;">
                        Debes iniciar sesión para realizar este test
                    </p>
                    <a href="login.php" class="btn" style="padding: 1rem 3rem; font-size: 1.1rem;">
                        Iniciar Sesión
                    </a>
                <?php elseif ($test['max_attempts'] != -1 && $user_attempts >= $test['max_attempts']): ?>
                    <div class="alert alert-info">
                        Has alcanzado el número máximo de intentos para este test.
                    </div>
                <?php else: ?>
                    <a href="take-test.php?id=<?= $test['id'] ?>" class="btn" style="padding: 1rem 3rem; font-size: 1.1rem;">
                        Realizar Test
                    </a>
                    <?php if ($user_attempts > 0): ?>
                        <p style="color: #718096; margin-top: 1rem; font-size: 0.9rem;">
                            Intento <?= $user_attempts + 1 ?> de <?= $test['max_attempts'] == -1 ? '∞' : $test['max_attempts'] ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Estadísticas -->
            <?php if ($test['avg_score']): ?>
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 12px; text-align: center;">
                    <h4 style="color: #4a5568; margin-bottom: 1rem;">Estadísticas Generales</h4>
                    <div style="font-size: 2rem; font-weight: 600; color: #667eea; margin-bottom: 0.5rem;">
                        <?= number_format($test['avg_score'], 1) ?>%
                    </div>
                    <div style="color: #718096; font-size: 0.9rem;">
                        Puntuación media de <?= $test['attempts_count'] ?> persona<?= $test['attempts_count'] != 1 ? 's' : '' ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

   <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>