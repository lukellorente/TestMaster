<?php
session_start();
require_once '../config/database.php';

// Requiere login
require_login();

// Obtener categorías
$categories = fetch_all("SELECT * FROM categories ORDER BY name");

$page_title = 'Crear Test';
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
        .section {
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .question-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .question-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
        }
        
        .btn-remove {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .btn-remove:hover {
            background: #dc2626;
        }
        
        .option-item {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .option-item input[type="text"] {
            flex: 1;
        }
        
        .btn-add-option {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .btn-add-option:hover {
            background: #059669;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .help-text {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 0.5rem;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
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
                <a href="perfil.php?id=<?= get_user_id() ?>" class="link">mi panel</a>
                <a href="../index.php" class="link">cancelar</a>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main style="max-width: 1000px; margin: 0 auto; padding: 2rem 1.5rem;">
        <h1 style="font-size: 2rem; margin-bottom: 2rem;">Crear Nuevo Test</h1>

        <form id="test-form" method="POST" action="save-test.php">
            
            <!-- INFORMACIÓN BÁSICA -->
            <div class="section">
                <h2 class="section-title"><i class="fa-solid fa-file-lines"></i> Información Básica</h2>
                
                <div class="form-group">
                    <label class="form-label" for="title">Título del Test *</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-input" 
                        placeholder="Ej: Test de Química Básica"
                        required
                        maxlength="255">
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Descripción</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-input" 
                        placeholder="Describe brevemente de qué trata el test..."
                        rows="4"></textarea>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="category">Categoría</label>
                        <select id="category" name="category_id" class="form-input">
                            <option value="">Sin categoría</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="difficulty">Dificultad</label>
                        <select id="difficulty" name="difficulty" class="form-input">
                            <option value="easy"><i class="fa-solid fa-face-smile"></i> Fácil</option>
                            <option value="medium" selected><i class="fa-solid fa-face-meh"></i> Medio</option>
                            <option value="hard"><i class="fa-solid fa-face-frown"></i> Difícil</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- CONFIGURACIÓN -->
            <div class="section">
                <h2 class="section-title"><i class="fa-solid fa-gear"></i> Configuración del Test</h2>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label" for="time_limit">Tiempo Límite (minutos)</label>
                        <input 
                            type="number" 
                            id="time_limit" 
                            name="time_limit" 
                            class="form-input" 
                            placeholder="0 = sin límite"
                            min="0"
                            value="0">
                        <p class="help-text">0 = sin límite de tiempo</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="max_attempts">Máximo de Intentos</label>
                        <input 
                            type="number" 
                            id="max_attempts" 
                            name="max_attempts" 
                            class="form-input" 
                            placeholder="-1 = ilimitado"
                            value="-1">
                        <p class="help-text">-1 = intentos ilimitados</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pass_score">Nota Mínima para Aprobar (%)</label>
                        <input 
                            type="number" 
                            id="pass_score" 
                            name="pass_score" 
                            class="form-input" 
                            min="0"
                            max="100"
                            value="70">
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_public" value="1" checked>
                            <span>Hacer público (otros pueden verlo)</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="shuffle_questions" value="1">
                            <span>Orden aleatorio de preguntas</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="shuffle_answers" value="1">
                            <span>Orden aleatorio de respuestas</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_correct_answers" value="1" checked>
                            <span>Mostrar respuestas correctas</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_explanations" value="1" checked>
                            <span>Mostrar explicaciones</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- PREGUNTAS -->
            <div class="section">
                <h2 class="section-title"><i class="fa-solid fa-question"></i> Preguntas</h2>
                
                <div id="questions-container">
                    <!-- Las preguntas se añadirán aquí con JavaScript -->
                </div>

                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button type="button" class="btn" onclick="addQuestion('multiple_choice')">
                        + Opción Múltiple
                    </button>
                    <button type="button" class="btn" onclick="addQuestion('true_false')" 
                            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        + Verdadero/Falso
                    </button>
                    <button type="button" class="btn" onclick="addQuestion('short_answer')"
                            style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        + Respuesta Corta
                    </button>
                </div>

                <p class="help-text" style="margin-top: 1rem;">
                    Añade al menos una pregunta para crear el test
                </p>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
                <button type="submit" name="status" value="draft" class="btn" 
                        style="padding: 1rem 2rem; background: #718096;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar como Borrador
                </button>
                <button type="submit" name="status" value="published" class="btn" 
                        style="padding: 1rem 2rem;">
                    <i class="fa-solid fa-rocket"></i> Publicar Test
                </button>
            </div>
        </form>
    </main>

    <?php include '../includes/footer.php'; ?>
    <script>
        let questionCount = 0;

        function addQuestion(type) {
            questionCount++;
            const container = document.getElementById('questions-container');
            
            let questionHTML = `
                <div class="question-card" id="question-${questionCount}">
                    <div class="question-header">
                        <span class="question-number">Pregunta ${questionCount}</span>
                        <button type="button" class="btn-remove" onclick="removeQuestion(${questionCount})">
                            ✕ Eliminar
                        </button>
                    </div>

                    <input type="hidden" name="questions[${questionCount}][type]" value="${type}">

                    <div class="form-group">
                        <label class="form-label">Pregunta *</label>
                        <textarea 
                            name="questions[${questionCount}][content]" 
                            class="form-input" 
                            placeholder="Escribe aquí la pregunta..."
                            rows="3"
                            required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Explicación (opcional)</label>
                        <textarea 
                            name="questions[${questionCount}][explanation]" 
                            class="form-input" 
                            placeholder="Explica por qué es correcta la respuesta..."
                            rows="2"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Puntos</label>
                        <input 
                            type="number" 
                            name="questions[${questionCount}][points]" 
                            class="form-input" 
                            value="1"
                            min="1"
                            style="width: 100px;">
                    </div>
            `;

            if (type === 'multiple_choice') {
                questionHTML += `
                    <div class="form-group">
                        <label class="form-label">Opciones de Respuesta</label>
                        <div id="options-${questionCount}">
                            ${generateOption(questionCount, 1, true)}
                            ${generateOption(questionCount, 2, false)}
                            ${generateOption(questionCount, 3, false)}
                            ${generateOption(questionCount, 4, false)}
                        </div>
                        <button type="button" class="btn-add-option" onclick="addOption(${questionCount})">
                            + Añadir Opción
                        </button>
                        <p class="help-text">Marca la respuesta correcta</p>
                    </div>
                `;
            } else if (type === 'true_false') {
                questionHTML += `
                    <div class="form-group">
                        <label class="form-label">Respuesta Correcta</label>
                        <div style="display: flex; gap: 1rem;">
                            <label class="checkbox-label">
                                <input type="radio" name="questions[${questionCount}][correct]" value="true" checked>
                                <span><i class="fa-solid fa-check"></i> Verdadero</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="radio" name="questions[${questionCount}][correct]" value="false">
                                <span><i class="fa-solid fa-xmark"></i> Falso</span>
                            </label>
                        </div>
                    </div>
                `;
            } else if (type === 'short_answer') {
                questionHTML += `
                    <div class="form-group">
                        <label class="form-label">Respuesta Correcta *</label>
                        <input 
                            type="text" 
                            name="questions[${questionCount}][correct_answer]" 
                            class="form-input" 
                            placeholder="Escribe la respuesta correcta"
                            required>
                        <p class="help-text">La respuesta no distingue mayúsculas/minúsculas</p>
                    </div>
                `;
            }

            questionHTML += `</div>`;
            
            container.insertAdjacentHTML('beforeend', questionHTML);
            
            // Scroll to new question
            document.getElementById(`question-${questionCount}`).scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }

        function generateOption(questionNum, optionNum, isCorrect = false) {
            return `
                <div class="option-item" id="option-${questionNum}-${optionNum}">
                    <input type="radio" 
                           name="questions[${questionNum}][correct_option]" 
                           value="${optionNum}"
                           ${isCorrect ? 'checked' : ''}
                           required>
                    <input type="text" 
                           name="questions[${questionNum}][options][]" 
                           class="form-input" 
                           placeholder="Opción ${optionNum}"
                           required>
                    ${optionNum > 2 ? `
                        <button type="button" class="btn-remove" style="padding: 0.5rem;" 
                                onclick="removeOption(${questionNum}, ${optionNum})">
                            ✕
                        </button>
                    ` : ''}
                </div>
            `;
        }

        function addOption(questionNum) {
            const container = document.getElementById(`options-${questionNum}`);
            const optionCount = container.querySelectorAll('.option-item').length + 1;
            container.insertAdjacentHTML('beforeend', generateOption(questionNum, optionCount, false));
        }

        function removeOption(questionNum, optionNum) {
            const option = document.getElementById(`option-${questionNum}-${optionNum}`);
            if (option) option.remove();
        }

        function removeQuestion(questionNum) {
            if (confirm('¿Eliminar esta pregunta?')) {
                const question = document.getElementById(`question-${questionNum}`);
                if (question) question.remove();
                updateQuestionNumbers();
            }
        }

        function updateQuestionNumbers() {
            const questions = document.querySelectorAll('.question-card');
            questions.forEach((q, index) => {
                const numberSpan = q.querySelector('.question-number');
                if (numberSpan) {
                    numberSpan.textContent = `Pregunta ${index + 1}`;
                }
            });
        }

        // Validación del formulario
        document.getElementById('test-form').addEventListener('submit', function(e) {
            const questions = document.querySelectorAll('.question-card');
            if (questions.length === 0) {
                e.preventDefault();
                alert('Debes añadir al menos una pregunta al test');
                return false;
            }
        });

        // Añadir primera pregunta automáticamente
        window.addEventListener('load', function() {
            addQuestion('multiple_choice');
        });
    </script>
</body>
</html>