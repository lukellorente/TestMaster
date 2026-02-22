<?php
session_start();
$page_title = 'Registrarse - TestMaster';

// Si ya está logueado, redirigir al index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Procesar el formulario de registro
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../config/database.php';
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validaciones
    if (empty($name) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Por favor, completa todos los campos.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } else {
        // Verificar si el email ya existe
        $existing_user = fetch_one("SELECT id FROM users WHERE email = ?", [$email]);
        
        if ($existing_user) {
            $error = 'Este email ya está registrado.';
        } else {
            // Registrar usuario
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                query("INSERT INTO users (name, email, password_hash, email_verified_at, created_at) 
                       VALUES (?, ?, ?, NOW(), NOW())", 
                      [$name, $email, $password_hash]);
                
                $user_id = $GLOBALS['pdo']->lastInsertId();
                
                // Crear estadísticas iniciales
                query("INSERT INTO user_stats (user_id) VALUES (?)", [$user_id]);
                query("INSERT INTO user_points (user_id) VALUES (?)", [$user_id]);
                
                $success = 'Cuenta creada exitosamente. Redirigiendo...';
                
                // Login automático
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = 'user';
                
                // Redirigir después de 2 segundos
                header('Refresh: 2; URL=dashboard.php');
                
            } catch (Exception $e) {
                $error = 'Error al crear la cuenta. Por favor, intenta de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
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
                <a href="../index.php" class="link">volver al inicio</a>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main>
        <div style="max-width: 500px; margin: 2rem auto;">
            <div style="background: white; padding: 2.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                
                <h1 style="text-align: center; margin-bottom: 0.5rem; color: #1a202c;">Crear Cuenta</h1>
                <p style="text-align: center; color: #a0aec0; margin-bottom: 2rem;">Únete a TestMaster gratis</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="name">Nombre Completo</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input" 
                            placeholder="Juan Pérez"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            required
                            autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Correo Electrónico</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="tu@email.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Contraseña</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Mínimo 6 caracteres"
                            required>
                        <small style="color: #718096; font-size: 0.85rem;">Mínimo 6 caracteres</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password_confirm">Confirmar Contraseña</label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            class="form-input" 
                            placeholder="Repite tu contraseña"
                            required>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%; padding: 0.75rem; margin-bottom: 1rem;">
                        Crear Cuenta
                    </button>
                </form>
                
                <div style="text-align: center; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                    <p style="color: #718096; font-size: 0.9rem;">
                        ¿Ya tienes cuenta? 
                        <a href="login.php" style="color: #667eea; font-weight: 600; text-decoration: none;">
                            Inicia sesión aquí
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>