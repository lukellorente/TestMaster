<?php
session_start();
require_once '../config/database.php';

// Requiere login
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

$test_id = $_GET['id'] ?? 0;

// Obtener test
$test = fetch_one("SELECT * FROM tests WHERE id = ?", [$test_id]);
if (!$test) {
    die("Test no encontrado");
}

// Verificar intentos restantes
$user_attempts = fetch_one("
    SELECT COUNT(*) as count 
    FROM test_attempts 
    WHERE user_id = ? AND test_id = ? AND is_completed = 1
", [get_user_id(), $test_id])['count'];

if ($test['max_attempts'] != -1 && $user_attempts >= $test['max_attempts']) {
    die("Has alcanzado el número máximo de intentos");
}

// Obtener preguntas
$questions = fetch_all("
    SELECT * FROM questions 
    WHERE test_id = ? 
    ORDER BY " . ($test['shuffle_questions'] ? 'RAND()' : 'order_index'), 
    [$test_id]
);

// Obtener opciones para cada pregunta
foreach ($questions as $key => $question) {
    // Inicializar options como null para todas las preguntas
    $questions[$key]['options'] = null;
    
    if (in_array($question['type'], ['multiple_choice', 'true_false'])) {
        $questions[$key]['options'] = fetch_all("
            SELECT * FROM question_options 
            WHERE question_id = ? 
            ORDER BY " . ($test['shuffle_answers'] ? 'RAND()' : 'order_index'),
            [$question['id']]
        );
    }
}

$page_title = 'Realizar: ' . $test['title'];
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
        .question-card {
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }
        
        .question-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .question-text {
            font-size: 1.1rem;
            color: #1a202c;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .option-label {
            display: block;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .option-label:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
        
        .option-label input[type="radio"],
        .option-label input[type="checkbox"] {
            margin-right: 0.75rem;
        }
        
        .option-label input:checked + .option-text {
            font-weight: 600;
            color: #667eea;
        }
        
        .timer {
            position: fixed;
            top: 100px;
            right: 2rem;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .timer.warning {
            border-color: #f5576c;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .short-answer {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
        }
        
        .short-answer:focus {
            outline: none;
            border-color: #667eea;
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
                <span class="link"><?= htmlspecialchars($test['title']) ?></span>
                <a href="../index.php" class="link">volver</a>
                <?php if (is_logged_in()): ?>
                    <a href="/testmaster/pages/perfil.php?id=<?= get_user_id() ?>" class="link">mi panel</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Timer (si tiene límite de tiempo) -->
    <?php if ($test['time_limit']): ?>
        <div id="timer" class="timer">
            <div style="font-size: 0.85rem; color: #718096; margin-bottom: 0.25rem;">Tiempo restante</div>
            <div id="timer-display" style="font-size: 1.5rem; font-weight: 600; color: #667eea;">
                <?= gmdate("i:s", $test['time_limit']) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main -->
    <main style="max-width: 900px; margin: 0 auto; padding: 2rem 1.5rem;">
        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
            <h1 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($test['title']) ?></h1>
            <p style="color: #718096;">
                <?= count($questions) ?> pregunta<?= count($questions) != 1 ? 's' : '' ?>
                <?php if ($test['time_limit']): ?>
                    • <?= round($test['time_limit'] / 60) ?> minutos
                <?php endif; ?>
            </p>
        </div>

        <form id="test-form" method="POST" action="submit-test.php">
            <input type="hidden" name="test_id" value="<?= $test_id ?>">
            <input type="hidden" name="started_at" value="<?= time() ?>">

            <?php foreach ($questions as $index => $question): ?>
                <div class="question-card">
                    <span class="question-number">Pregunta <?= $index + 1 ?></span>
                    
                    <div class="question-text">
                        <?= nl2br(htmlspecialchars($question['content'])) ?>
                    </div>

                    <?php if ($question['image_url']): ?>
                        <img src="../uploads/<?= htmlspecialchars($question['image_url']) ?>" 
                             alt="Imagen de la pregunta" 
                             style="max-width: 100%; border-radius: 8px; margin-bottom: 1rem;">
                    <?php endif; ?>

                    <?php if ($question['type'] === 'multiple_choice'): ?>
                        <?php foreach ($question['options'] as $option): ?>
                            <label class="option-label">
                                <input type="radio" 
                                       name="answers[<?= $question['id'] ?>]" 
                                       value="<?= $option['id'] ?>" 
                                       required>
                                <span class="option-text"><?= htmlspecialchars($option['content']) ?></span>
                            </label>
                        <?php endforeach; ?>

                    <?php elseif ($question['type'] === 'true_false'): ?>
                        <label class="option-label">
                            <input type="radio" 
                                   name="answers[<?= $question['id'] ?>]" 
                                   value="true" 
                                   required>
                            <span class="option-text"><i class="fa-solid fa-check"></i> Verdadero</span>
                        </label>
                        <label class="option-label">
                            <input type="radio" 
                                   name="answers[<?= $question['id'] ?>]" 
                                   value="false" 
                                   required>
                            <span class="option-text"><i class="fa-solid fa-xmark"></i> Falso</span>
                        </label>

                    <?php elseif ($question['type'] === 'short_answer'): ?>
                        <input type="text" 
                               name="answers[<?= $question['id'] ?>]" 
                               class="short-answer" 
                               placeholder="Escribe tu respuesta aquí..."
                               required>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn" style="padding: 1rem 3rem; font-size: 1.1rem;">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Respuestas
                </button>
            </div>
        </form>
    </main>

    <?php include '../includes/footer.php'; ?>

    <?php if ($test['time_limit']): ?>
    <script>
        // Timer
        let timeLeft = <?= $test['time_limit'] ?>;
        const timerDisplay = document.getElementById('timer-display');
        const timerElement = document.getElementById('timer');
        
        const countdown = setInterval(() => {
            timeLeft--;
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            // Warning cuando quedan 2 minutos
            if (timeLeft <= 120) {
                timerElement.classList.add('warning');
            }
            
            // Tiempo agotado
            if (timeLeft <= 0) {
                clearInterval(countdown);
                alert('¡Tiempo agotado! El test se enviará automáticamente.');
                document.getElementById('test-form').submit();
            }
        }, 1000);
        
        // Confirmar salida
        window.addEventListener('beforeunload', (e) => {
            e.preventDefault();
            e.returnValue = '';
        });
    </script>
    <?php endif; ?>
</body>
</html>