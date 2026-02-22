<?php
/**
 * Configuración de conexión a la base de datos
 * TestMaster - Database Connection
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'testmaster');
define('DB_USER', 'root');
define('DB_PASS', ''); // En XAMPP por defecto está vacío
define('DB_CHARSET', 'utf8mb4');

// Intentar conectar a la base de datos
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // En producción, no mostrar detalles del error
    die("Error de conexión a la base de datos. Por favor, contacta al administrador.");
    // Para desarrollo, puedes descomentar la siguiente línea:
    // die("Error de conexión: " . $e->getMessage());
}

/**
 * Función auxiliar para ejecutar queries preparadas
 */
function query($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Función para obtener un solo registro
 */
function fetch_one($sql, $params = []) {
    return query($sql, $params)->fetch();
}

/**
 * Función para obtener múltiples registros
 */
function fetch_all($sql, $params = []) {
    return query($sql, $params)->fetchAll();
}

/**
 * Función para escapar HTML (prevenir XSS)
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Función para redireccionar
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Verificar si el usuario está logueado
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Obtener el ID del usuario logueado
 */
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Requerir login (redirige a login si no está logueado)
 */
function require_login() {
    if (!is_logged_in()) {
        redirect('pages/login.php');
    }
}