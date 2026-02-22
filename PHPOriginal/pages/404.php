<?php
$page_title = "Página no encontrada - TestMaster";
require_once __DIR__ . '/../includes/header.php';

// Obtener la página que se intentó visitar
if (isset($_GET['page'])) {
    $page_name = $_GET['page'];
} else {
    $requested_page = $_SERVER['REQUEST_URI'] ?? '';
    $page_name = basename(parse_url($requested_page, PHP_URL_PATH));
}
?>

<style>
    .error-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 50vh;
        text-align: center;
        padding: 2rem;
    }

    .error-code {
        font-size: 8rem;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1;
        margin-bottom: 1rem;
    }

    .error-icon {
        font-size: 4rem;
        margin-bottom: 1.5rem;
    }

    .error-title {
        font-size: 1.8rem;
        color: #2d3748;
        margin-bottom: 0.75rem;
        font-weight: 600;
    }

    .error-message {
        color: #718096;
        font-size: 1.1rem;
        max-width: 400px;
        margin-bottom: 2rem;
    }

    .error-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .btn-secondary {
        background: white;
        color: #667eea;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.95rem;
        border: 2px solid #667eea;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-secondary:hover {
        background: #667eea;
        color: white;
    }

    .suggestions {
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 1px solid #e2e8f0;
    }

    .suggestions h3 {
        color: #4a5568;
        font-size: 1rem;
        margin-bottom: 1rem;
        font-weight: 500;
    }

    .suggestions-list {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .suggestions-list a {
        color: #667eea;
        text-decoration: none;
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
        background: rgba(102, 126, 234, 0.1);
        border-radius: 6px;
        transition: all 0.2s;
    }

    .suggestions-list a:hover {
        background: rgba(102, 126, 234, 0.2);
    }
</style>

<div class="error-container">
    <div class="error-icon"><i class="fa-solid fa-magnifying-glass" style="font-size: 4rem;"></i></div>
    <div class="error-code">404</div>
    <h1 class="error-title">¡Oops! Página no encontrada</h1>
    <p class="error-message">
        Lo sentimos, la página que buscas no existe o aún está en construcción.
    </p>
    <?php if ($page_name && $page_name !== '404.php'): ?>
        <p style="color: #a0aec0; font-size: 0.9rem; margin-bottom: 2rem;">
            Página solicitada: <code style="background: #edf2f7; padding: 0.2rem 0.5rem; border-radius: 4px;"><?= e($page_name) ?></code>
        </p>
    <?php endif; ?>
    
    <div class="error-actions">
        <a href="/testmaster/index.php" class="btn">Ir al Inicio</a>
        <a href="javascript:history.back()" class="btn-secondary">Volver Atrás</a>
    </div>

    <div class="suggestions">
        <h3>¿Quizás buscabas esto?</h3>
        <div class="suggestions-list">
            <a href="/testmaster/pages/explore.php">Explorar Tests</a>
            <?php if (is_logged_in()): ?>
                <a href="/testmaster/pages/perfil.php?id=<?= get_user_id() ?>" class="link">mi panel</a>
                <a href="/testmaster/pages/create-test.php">Crear Test</a>
            <?php else: ?>
                <a href="/testmaster/pages/login.php">Iniciar Sesión</a>
                <a href="/testmaster/pages/register.php">Registrarse</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</html>
