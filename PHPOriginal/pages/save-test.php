<?php
session_start();
require_once '../config/database.php';

// Requiere login
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create-test.php');
    exit();
}

// Validar que haya al menos una pregunta
if (empty($_POST['questions']) || !is_array($_POST['questions'])) {
    $_SESSION['error'] = 'Debes añadir al menos una pregunta';
    header('Location: create-test.php');
    exit();
}

try {
    // Obtener datos del formulario
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $difficulty = $_POST['difficulty'] ?? 'medium';
    $time_limit = (int)($_POST['time_limit'] ?? 0);
    $max_attempts = (int)($_POST['max_attempts'] ?? -1);
    $pass_score = (int)($_POST['pass_score'] ?? 70);
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $shuffle_questions = isset($_POST['shuffle_questions']) ? 1 : 0;
    $shuffle_answers = isset($_POST['shuffle_answers']) ? 1 : 0;
    $show_correct_answers = isset($_POST['show_correct_answers']) ? 1 : 0;
    $show_explanations = isset($_POST['show_explanations']) ? 1 : 0;
    $status = $_POST['status'] ?? 'draft';
    
    // Validaciones básicas
    if (empty($title)) {
        throw new Exception('El título es obligatorio');
    }
    
    // Convertir tiempo de minutos a segundos
    $time_limit_seconds = $time_limit > 0 ? $time_limit * 60 : null;
    
    // Insertar el test
    query("
        INSERT INTO tests (
            user_id, title, description, is_public, time_limit, max_attempts,
            pass_score, shuffle_questions, shuffle_answers, show_correct_answers,
            show_explanations, difficulty, status, created_at, updated_at,
            published_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
    ", [
        get_user_id(),
        $title,
        $description,
        $is_public,
        $time_limit_seconds,
        $max_attempts,
        $pass_score,
        $shuffle_questions,
        $shuffle_answers,
        $show_correct_answers,
        $show_explanations,
        $difficulty,
        $status,
        $status === 'published' ? date('Y-m-d H:i:s') : null
    ]);
    
    $test_id = $GLOBALS['pdo']->lastInsertId();
    
    // Insertar categoría si se seleccionó
    if ($category_id) {
        query("INSERT INTO test_categories (test_id, category_id) VALUES (?, ?)", 
              [$test_id, $category_id]);
        
        // Actualizar contador de tests en la categoría
        query("UPDATE categories SET tests_count = tests_count + 1 WHERE id = ?", 
              [$category_id]);
    }
    
    // Insertar preguntas
    $order_index = 1;
    foreach ($_POST['questions'] as $question_data) {
        $question_type = $question_data['type'];
        $question_content = trim($question_data['content'] ?? '');
        $question_explanation = trim($question_data['explanation'] ?? '');
        $question_points = (int)($question_data['points'] ?? 1);
        
        if (empty($question_content)) {
            continue; // Saltar preguntas vacías
        }
        
        // Insertar pregunta
        query("
            INSERT INTO questions (
                test_id, type, content, explanation, points, order_index, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [
            $test_id,
            $question_type,
            $question_content,
            $question_explanation,
            $question_points,
            $order_index
        ]);
        
        $question_id = $GLOBALS['pdo']->lastInsertId();
        
        // Insertar opciones según el tipo de pregunta
        if ($question_type === 'multiple_choice') {
            $options = $question_data['options'] ?? [];
            $correct_option = (int)($question_data['correct_option'] ?? 1);
            
            foreach ($options as $option_index => $option_content) {
                $option_content = trim($option_content);
                if (empty($option_content)) continue;
                
                $is_correct = (($option_index + 1) === $correct_option) ? 1 : 0;
                
                query("
                    INSERT INTO question_options (
                        question_id, content, is_correct, order_index
                    ) VALUES (?, ?, ?, ?)
                ", [
                    $question_id,
                    $option_content,
                    $is_correct,
                    $option_index + 1
                ]);
            }
            
        } elseif ($question_type === 'true_false') {
            $correct_answer = $question_data['correct'] ?? 'true';
            
            // Insertar opción "Verdadero"
            query("
                INSERT INTO question_options (
                    question_id, content, is_correct, order_index
                ) VALUES (?, ?, ?, ?)
            ", [
                $question_id,
                'Verdadero',
                $correct_answer === 'true' ? 1 : 0,
                1
            ]);
            
            // Insertar opción "Falso"
            query("
                INSERT INTO question_options (
                    question_id, content, is_correct, order_index
                ) VALUES (?, ?, ?, ?)
            ", [
                $question_id,
                'Falso',
                $correct_answer === 'false' ? 1 : 0,
                2
            ]);
            
        } elseif ($question_type === 'short_answer') {
            $correct_answer = trim($question_data['correct_answer'] ?? '');
            
            if (!empty($correct_answer)) {
                query("
                    INSERT INTO question_options (
                        question_id, content, is_correct, order_index
                    ) VALUES (?, ?, ?, ?)
                ", [
                    $question_id,
                    $correct_answer,
                    1,
                    1
                ]);
            }
        }
        
        $order_index++;
    }
    
    // Actualizar estadísticas del usuario
    query("
        UPDATE user_stats 
        SET tests_created = tests_created + 1 
        WHERE user_id = ?
    ", [get_user_id()]);
    
    // Si no existe registro de stats, crearlo
    $stats_exists = fetch_one("SELECT id FROM user_stats WHERE user_id = ?", [get_user_id()]);
    if (!$stats_exists) {
        query("INSERT INTO user_stats (user_id, tests_created) VALUES (?, 1)", [get_user_id()]);
    }
    
    // Crear registro de estadísticas del test
    query("INSERT INTO test_stats (test_id) VALUES (?)", [$test_id]);
    
    // Redirigir al test creado
    $_SESSION['success'] = $status === 'published' 
        ? '¡Test publicado exitosamente!' 
        : 'Test guardado como borrador';
    
    header("Location: view-test.php?id=$test_id");
    exit();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error al crear el test: ' . $e->getMessage();
    header('Location: create-test.php');
    exit();
}