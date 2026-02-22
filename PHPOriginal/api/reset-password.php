<?php
/**
 * Reset Password - Herramienta para resetear contraseñas de usuarios
 * Uso rápido y básico
 */

require_once '../config/database.php';

header('Content-Type: application/json');

// Solo permite POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Solo se permite POST']);
    exit;
}

$email = $_POST['email'] ?? null;
$new_password = $_POST['password'] ?? null;

// Validar que tenemos los datos
if (!$email || !$new_password) {
    echo json_encode(['error' => 'Email y contraseña son requeridos']);
    exit;
}

// Validar que la contraseña tenga mínimo 6 caracteres
if (strlen($new_password) < 6) {
    echo json_encode(['error' => 'La contraseña debe tener mínimo 6 caracteres']);
    exit;
}

try {
    // Hashear la contraseña
    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    
    // Actualizar la contraseña en la base de datos
    $sql = "UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':password_hash' => $password_hash,
        ':email' => $email
    ]);
    
    // Verificar si la actualización fue exitosa
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente',
            'email' => $email
        ]);
    } else {
        echo json_encode(['error' => 'Usuario no encontrado']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
