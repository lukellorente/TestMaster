<?php
session_start();
require_once '../config/database.php';

// Requiere login
if (!is_logged_in()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

$test_id = $_POST['test_id'] ?? 0;
$started_at = $_POST['started_at'] ?? time();
$answers = $_POST['answers'] ?? [];

// Obtener test
$test = fetch_one("SELECT * FROM tests WHERE id = ?", [$test_id]);
if (!$test) {
    die("Test no encontrado");
}

// Obtener todas las preguntas
$questions = fetch_all("SELECT * FROM questions WHERE test_id = ?", [$test_id]);

// Calcular tiempo empleado
$time_spent = time() - $started_at;

// Inicializar contadores
$correct_answers = 0;
$incorrect_answers = 0;
$unanswered = 0;
$points_earned = 0;
$total_points = 0;

// Array para guardar cada respuesta
$responses = [];

foreach ($questions as $question) {
    $total_points += $question['points'];
    $question_id = $question['id'];
    $given_answer = $answers[$question_id] ?? null;
    $is_correct = false;
    
    if (!$given_answer) {
        $unanswered++;
        $responses[] = [
            'question_id' => $question_id,
            'given_answer' => '',
            'is_correct' => false,
            'points_earned' => 0
        ];
        continue;
    }
    
    // Verificar respuesta según tipo de pregunta
    if ($question['type'] === 'multiple_choice') {
        // Verificar si la opción seleccionada es correcta
        $option = fetch_one("
            SELECT is_correct 
            FROM question_options 
            WHERE id = ? AND question_id = ?
        ", [$given_answer, $question_id]);
        
        $is_correct = $option && $option['is_correct'];
        
    } elseif ($question['type'] === 'true_false') {
        // Para true/false, verificamos si existe una opción correcta que coincida
        $correct_option = fetch_one("
            SELECT content 
            FROM question_options 
            WHERE question_id = ? AND is_correct = 1
        ", [$question_id]);
        
        if ($correct_option) {
            $is_correct = (strtolower($given_answer) === 'true' && $correct_option['content'] === 'Verdadero') ||
                         (strtolower($given_answer) === 'false' && $correct_option['content'] === 'Falso');
        } else {
            // Si no hay opciones guardadas, asumimos que "true" es la respuesta correcta
            $is_correct = (strtolower($given_answer) === 'true');
        }
        
    } elseif ($question['type'] === 'short_answer') {
        // Para respuesta corta, comparamos con las opciones correctas
        $correct_options = fetch_all("
            SELECT content 
            FROM question_options 
            WHERE question_id = ? AND is_correct = 1
        ", [$question_id]);
        
        foreach ($correct_options as $option) {
            if (strcasecmp(trim($given_answer), trim($option['content'])) === 0) {
                $is_correct = true;
                break;
            }
        }
    }
    
    // Actualizar contadores
    if ($is_correct) {
        $correct_answers++;
        $points_earned += $question['points'];
    } else {
        $incorrect_answers++;
    }
    
    // Guardar respuesta para insertar después
    $responses[] = [
        'question_id' => $question_id,
        'given_answer' => is_array($given_answer) ? json_encode($given_answer) : $given_answer,
        'is_correct' => $is_correct,
        'points_earned' => $is_correct ? $question['points'] : 0
    ];
}

// Calcular puntuación (porcentaje)
$score = $total_points > 0 ? ($points_earned / $total_points) * 100 : 0;
$passed = $score >= $test['pass_score'];

try {
    // Insertar intento
    query("
        INSERT INTO test_attempts (
            user_id, test_id, score, points_earned, total_points,
            correct_answers, incorrect_answers, unanswered,
            time_spent, is_completed, passed, started_at, completed_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, FROM_UNIXTIME(?), NOW())
    ", [
        get_user_id(), $test_id, $score, $points_earned, $total_points,
        $correct_answers, $incorrect_answers, $unanswered,
        $time_spent, $passed, $started_at
    ]);
    
    $attempt_id = $GLOBALS['pdo']->lastInsertId();
    
    // Insertar cada respuesta
    foreach ($responses as $response) {
        query("
            INSERT INTO question_responses (
                attempt_id, question_id, given_answer, is_correct, points_earned
            ) VALUES (?, ?, ?, ?, ?)
        ", [
            $attempt_id,
            $response['question_id'],
            $response['given_answer'],
            $response['is_correct'],
            $response['points_earned']
        ]);
    }
    
    // Actualizar estadísticas del usuario
    query("
        UPDATE user_stats SET
            tests_completed = tests_completed + 1,
            tests_passed = tests_passed + ?,
            average_score = (
                SELECT AVG(score) 
                FROM test_attempts 
                WHERE user_id = ? AND is_completed = 1
            )
        WHERE user_id = ?
    ", [$passed ? 1 : 0, get_user_id(), get_user_id()]);
    
    // Actualizar estadísticas del test
    query("
        UPDATE test_stats SET
            attempts_count = attempts_count + 1,
            completions_count = completions_count + 1,
            average_score = (
                SELECT AVG(score) 
                FROM test_attempts 
                WHERE test_id = ? AND is_completed = 1
            )
        WHERE test_id = ?
    ", [$test_id, $test_id]);
    
    // Si no existe registro de stats, crearlo
    $stats_exists = fetch_one("SELECT id FROM test_stats WHERE test_id = ?", [$test_id]);
    if (!$stats_exists) {
        query("INSERT INTO test_stats (test_id, attempts_count, completions_count, average_score) VALUES (?, 1, 1, ?)", [$test_id, $score]);
    }
    
    // Redirigir a resultados
    header("Location: results.php?attempt_id=$attempt_id");
    exit();
    
} catch (Exception $e) {
    die("Error al guardar el intento: " . $e->getMessage());
}