<?php
session_start();
require_once '../config/database.php';

// Requiere login
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$attempt_id = $_GET['attempt_id'] ?? 0;

// Obtener intento
$attempt = fetch_one("
    SELECT a.*, t.title as test_title, t.show_correct_answers, t.show_explanations, t.id as test_id
    FROM test_attempts a
    JOIN tests t ON a.test_id = t.id
    WHERE a.id = ? AND a.user_id = ?
", [$attempt_id, get_user_id()]);

if (!$attempt) {
    die("Intento no encontrado");
}

// Obtener respuestas del usuario
$responses = fetch_all("
    SELECT r.*, q.content as question_text, q.type, q.explanation, q.points
    FROM question_responses r
    JOIN questions q ON r.question_id = q.id
    WHERE r.attempt_id = ?
    ORDER BY q.order_index
", [$attempt_id]);

// Para cada respuesta, obtener las opciones si aplica
foreach ($responses as &$response) {
    if (in_array($response['type'], ['multiple_choice', 'true_false', 'short_answer'])) {
        $response['options'] = fetch_all("
            SELECT * FROM question_options 
            WHERE question_id = ?
            ORDER BY order_index
        ", [$response['question_id']]);
    }
}

$page_title = 'Resultados del Test';
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
    <style>
        .result-card {
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }
        
        .correct {
            border-left: 4px solid #10b981;
        }
        
        .incorrect {
            border-left: 4px solid #ef4444;
        }
        
        .option-correct {
            background: #d1fae5;
            border-color: #10b981;
        }
        
        .option-incorrect {
            background: #fee2e2;
            border-color: #ef4444;
        }
        
        .option-user {
            border: 2px solid #667eea;
            background: #ede9fe;
        }
    </style>
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
                <a href="view-test.php?id=<?= $attempt['test_id'] ?>" class="link">volver al test</a>
                <a href="/testmaster/pages/perfil.php?id=<?= get_user_id() ?>" class="link">mi panel</a>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main style="max-width: 900px; margin: 0 auto; padding: 2rem 1.5rem;">
        <!-- Resumen de resultados -->
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1 style="font-size: 2rem; margin-bottom: 1rem;">
                <?= $attempt['passed'] ? '¡Aprobado! <i class="fa-solid fa-champagne-glasses"></i>' : 'No Aprobado <i class="fa-solid fa-face-sad-tear"></i>' ?>
            </h1>
            
            <div style="display: inline-block; background: <?= $attempt['passed'] ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' ?>; 
                        color: white; padding: 2rem 3rem; border-radius: 16px; margin-bottom: 2rem;">
                <div style="font-size: 4rem; font-weight: 700; margin-bottom: 0.5rem;">
                    <?= number_format($attempt['score'], 1) ?>%
                </div>
                <div style="font-size: 1.2rem; opacity: 0.9;">
                    <?= $attempt['points_earned'] ?> de <?= $attempt['total_points'] ?> puntos
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div style="background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-check"></i></div>
                <div style="font-size: 2rem; font-weight: 600; color: #10b981;">
                    <?= $attempt['correct_answers'] ?>
                </div>
                <div style="color: #718096; font-size: 0.9rem;">Correctas</div>
            </div>

            <div style="background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-xmark"></i></div>
                <div style="font-size: 2rem; font-weight: 600; color: #ef4444;">
                    <?= $attempt['incorrect_answers'] ?>
                </div>
                <div style="color: #718096; font-size: 0.9rem;">Incorrectas</div>
            </div>

            <?php if ($attempt['unanswered'] > 0): ?>
            <div style="background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-regular fa-circle"></i></div>
                <div style="font-size: 2rem; font-weight: 600; color: #a0aec0;">
                    <?= $attempt['unanswered'] ?>
                </div>
                <div style="color: #718096; font-size: 0.9rem;">Sin responder</div>
            </div>
            <?php endif; ?>

            <div style="background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-clock"></i></div>
                <div style="font-size: 2rem; font-weight: 600; color: #667eea;">
                    <?= gmdate("i:s", $attempt['time_spent']) ?>
                </div>
                <div style="color: #718096; font-size: 0.9rem;">Tiempo</div>
            </div>
        </div>

        <!-- Detalle de respuestas -->
        <?php if ($attempt['show_correct_answers']): ?>
            <h2 style="margin-bottom: 1.5rem;">Detalle de Respuestas</h2>
            
            <?php foreach ($responses as $index => $response): ?>
                <div class="result-card <?= $response['is_correct'] ? 'correct' : 'incorrect' ?>">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <span style="background: <?= $response['is_correct'] ? '#10b981' : '#ef4444' ?>; 
                                     color: white; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">
                            Pregunta <?= $index + 1 ?>
                        </span>
                        <span style="color: <?= $response['is_correct'] ? '#10b981' : '#ef4444' ?>; 
                                     font-weight: 600; font-size: 1.1rem;">
                            <?= $response['is_correct'] ? '✓ Correcta' : '✗ Incorrecta' ?>
                        </span>
                    </div>

                    <div style="font-size: 1.1rem; color: #1a202c; margin-bottom: 1.5rem;">
                        <?= nl2br(htmlspecialchars($response['question_text'])) ?>
                    </div>

                    <?php if ($response['type'] === 'multiple_choice'): ?>
                        <?php foreach ($response['options'] as $option): ?>
                            <?php 
                            $is_user_answer = ($response['given_answer'] == $option['id']);
                            $is_correct_answer = $option['is_correct'];
                            ?>
                            <div style="padding: 0.75rem 1rem; border: 2px solid #e2e8f0; border-radius: 8px; margin-bottom: 0.5rem;
                                        <?php if ($is_user_answer && $is_correct_answer): ?>
                                            background: #d1fae5; border-color: #10b981;
                                        <?php elseif ($is_user_answer && !$is_correct_answer): ?>
                                            background: #fee2e2; border-color: #ef4444;
                                        <?php elseif ($is_correct_answer): ?>
                                            background: #d1fae5; border-color: #10b981;
                                        <?php endif; ?>">
                                <?php if ($is_user_answer): ?>
                                    <strong style="color: #667eea;">Tu respuesta: </strong>
                                <?php endif; ?>
                                <?= htmlspecialchars($option['content']) ?>
                                <?php if ($is_correct_answer && !$is_user_answer): ?>
                                    <span style="color: #10b981; font-weight: 600;"> ← Respuesta correcta</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                    <?php elseif ($response['type'] === 'true_false'): ?>
                        <div style="padding: 0.75rem 1rem; border: 2px solid; border-radius: 8px; margin-bottom: 0.5rem;
                                    <?= $response['is_correct'] ? 'background: #d1fae5; border-color: #10b981;' : 'background: #fee2e2; border-color: #ef4444;' ?>">
                            Tu respuesta: <strong><?= $response['given_answer'] === 'true' ? 'Verdadero' : 'Falso' ?></strong>
                        </div>

                    <?php elseif ($response['type'] === 'short_answer'): ?>
                        <div style="padding: 0.75rem 1rem; border: 2px solid; border-radius: 8px; margin-bottom: 0.5rem;
                                    <?= $response['is_correct'] ? 'background: #d1fae5; border-color: #10b981;' : 'background: #fee2e2; border-color: #ef4444;' ?>">
                            Tu respuesta: <strong><?= htmlspecialchars($response['given_answer']) ?></strong>
                        </div>
                        <?php if (!$response['is_correct'] && !empty($response['options'])): ?>
                            <div style="padding: 0.75rem 1rem; border: 2px solid #10b981; background: #d1fae5; border-radius: 8px;">
                                Respuesta correcta: <strong><?= htmlspecialchars($response['options'][0]['content']) ?></strong>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($attempt['show_explanations'] && $response['explanation']): ?>
                        <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #667eea;">
                            <strong style="color: #667eea;"><i class="fa-solid fa-lightbulb"></i> Explicación:</strong><br>
                            <?= nl2br(htmlspecialchars($response['explanation'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                El creador del test ha desactivado la visualización de respuestas correctas.
            </div>
        <?php endif; ?>

        <!-- Botones de acción -->
        <div style="text-align: center; margin-top: 3rem;">
            <a href="view-test.php?id=<?= $attempt['test_id'] ?>" class="btn" style="padding: 0.75rem 2rem; margin-right: 1rem;">
                Ver Información del Test
            </a>
            <a href="take-test.php?id=<?= $attempt['test_id'] ?>" class="link" style="padding: 0.75rem 2rem;">
                Volver a Intentar
            </a>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>