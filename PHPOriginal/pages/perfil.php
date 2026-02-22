<?php
session_start();
require_once '../config/database.php';

// Obtener usuario del parámetro
$username = $_GET['user'] ?? '';
$user_id = $_GET['id'] ?? 0;

// Buscar usuario
if ($username) {
    $profile_user = fetch_one("SELECT * FROM users WHERE name = ?", [$username]);
} elseif ($user_id) {
    $profile_user = fetch_one("SELECT * FROM users WHERE id = ?", [$user_id]);
} else {
    die("Usuario no encontrado");
}

if (!$profile_user) {
    die("Usuario no encontrado");
}

// Obtener estadísticas
$stats = fetch_one("SELECT * FROM user_stats WHERE user_id = ?", [$profile_user['id']]);
if (!$stats) {
    $stats = [
        'tests_created' => 0,
        'tests_completed' => 0,
        'tests_passed' => 0,
        'average_score' => 0,
        'perfect_scores' => 0,
        'current_streak' => 0
    ];
}

// Tests creados
$created_tests = fetch_all("
    SELECT t.*, 
           (SELECT COUNT(*) FROM questions WHERE test_id = t.id) as num_questions,
           (SELECT COUNT(*) FROM test_attempts WHERE test_id = t.id AND is_completed = 1) as attempts_count,
           (SELECT AVG(score) FROM test_attempts WHERE test_id = t.id AND is_completed = 1) as avg_score
    FROM tests t
    WHERE t.user_id = ? AND t.is_public = 1 AND t.status = 'published'
    ORDER BY t.created_at DESC
", [$profile_user['id']]);

// Tests realizados (solo los que tienen el test público)
$completed_tests = fetch_all("
    SELECT a.*, t.title, t.id as test_id, u.name as author_name
    FROM test_attempts a
    JOIN tests t ON a.test_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE a.user_id = ? AND a.is_completed = 1 AND t.is_public = 1
    ORDER BY a.completed_at DESC
    LIMIT 20
", [$profile_user['id']]);

// Actividad reciente
$recent_activity = fetch_all("
    SELECT 'test' as type, created_at as date, title, id
    FROM tests
    WHERE user_id = ? AND is_public = 1 AND status = 'published'
    
    UNION ALL
    
    SELECT 'attempt' as type, a.completed_at as date, t.title, a.id
    FROM test_attempts a
    JOIN tests t ON a.test_id = t.id
    WHERE a.user_id = ? AND a.is_completed = 1 AND t.is_public = 1
    
    ORDER BY date DESC
    LIMIT 30
", [$profile_user['id'], $profile_user['id']]);

$page_title = 'Perfil de ' . $profile_user['name'];
$is_own_profile = is_logged_in() && get_user_id() == $profile_user['id'];
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
        .profile-header {
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }
        
        .profile-info {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .profile-details {
            flex: 1;
        }
        
        .profile-name {
            font-size: 2rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .profile-meta {
            color: #718096;
            font-size: 0.9rem;
        }
        
        .tabs {
            display: flex;
            gap: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }
        
        .tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            border-right: 1px solid #e2e8f0;
            transition: all 0.2s;
            font-weight: 500;
            color: #718096;
        }
        
        .tab:last-child {
            border-right: none;
        }
        
        .tab:hover {
            background: #f8f9fa;
        }
        
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .stat-box {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #718096;
        }
        
        @media (max-width: 768px) {
            .profile-info {
                flex-direction: column;
                text-align: center;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
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
                <?php if ($is_own_profile): ?>
                    <a href="/testmaster/pages/perfil.php?id=<?= get_user_id() ?>" class="link">mi panel</a>
                <?php endif; ?>
                <a href="../index.php" class="link">volver</a>
            </div>
        </div>
    </header>

    <!-- Main -->
    <main style="max-width: 1000px; margin: 0 auto; padding: 2rem 1.5rem;">
        
        <!-- Perfil Header -->
        <div class="profile-header">
            <div class="profile-info">
                <div class="profile-avatar">
                    <?= strtoupper(substr($profile_user['name'], 0, 1)) ?>
                </div>
                <div class="profile-details">
                    <h1 class="profile-name"><?= htmlspecialchars($profile_user['name']) ?></h1>
                    <div class="profile-meta">
                        Miembro desde <?= date('d/m/Y', strtotime($profile_user['created_at'])) ?>
                        <?php if ($profile_user['last_login']): ?>
                            • Última actividad: <?= date('d/m/Y', strtotime($profile_user['last_login'])) ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($profile_user['bio']): ?>
                        <p style="margin-top: 1rem; color: #4a5568;">
                            <?= nl2br(htmlspecialchars($profile_user['bio'])) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php if ($is_own_profile): ?>
                    <a href="edit-perfil.php" class="btn">Editar Perfil</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="showTab('tests')">
                <i class="fa-solid fa-file-lines"></i> Tests (<?= count($created_tests) ?>)
            </div>
            <div class="tab" onclick="showTab('activity')">
                <i class="fa-solid fa-bolt"></i> Actividad (<?= count($recent_activity) ?>)
            </div>
            <div class="tab" onclick="showTab('completed')">
                <i class="fa-solid fa-circle-check"></i> Realizados (<?= count($completed_tests) ?>)
            </div>
            <div class="tab" onclick="showTab('stats')">
                <i class="fa-solid fa-chart-bar"></i> Estadísticas
            </div>
        </div>

        <!-- Tab Content: Tests Creados -->
        <div id="tab-tests" class="tab-content active">
            <?php if (empty($created_tests)): ?>
                <div class="empty-state" style="background: white; padding: 3rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div class="empty-state-icon"><i class="fa-solid fa-file-lines" style="font-size: 3rem; color: #a0aec0;"></i></div>
                    <p><?= htmlspecialchars($profile_user['name']) ?> aún no ha creado tests públicos</p>
                </div>
            <?php else: ?>
                <div style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <?php foreach ($created_tests as $test): ?>
                        <div class="test-item" onclick="window.location.href='view-test.php?id=<?= $test['id'] ?>'">
                            <a href="view-test.php?id=<?= $test['id'] ?>" class="test-title">
                                <?= htmlspecialchars($test['title']) ?>
                            </a>
                            <span class="test-meta time">
                                <?= date('d/m/Y', strtotime($test['created_at'])) ?>
                            </span>
                            <span class="test-meta questions"><?= $test['num_questions'] ?> preg</span>
                            <span class="test-meta views"><?= $test['attempts_count'] ?> intento<?= $test['attempts_count'] != 1 ? 's' : '' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab Content: Actividad -->
        <div id="tab-activity" class="tab-content">
            <?php if (empty($recent_activity)): ?>
                <div class="empty-state" style="background: white; padding: 3rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div class="empty-state-icon"><i class="fa-solid fa-bolt" style="font-size: 3rem; color: #a0aec0;"></i></div>
                    <p>Sin actividad reciente</p>
                </div>
            <?php else: ?>
                <div style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <?php foreach ($recent_activity as $activity): ?>
                        <div style="padding: 1rem 0; border-bottom: 1px solid #f7fafc; display: flex; gap: 1rem; align-items: center;">
                            <div style="font-size: 1.5rem;">
                                <?= $activity['type'] === 'test' ? '<i class="fa-solid fa-file-lines"></i>' : '<i class="fa-solid fa-circle-check"></i>' ?>
                            </div>
                            <div style="flex: 1;">
                                <div style="color: #1a202c; margin-bottom: 0.25rem;">
                                    <?php if ($activity['type'] === 'test'): ?>
                                        Creó el test <strong><?= htmlspecialchars($activity['title']) ?></strong>
                                    <?php else: ?>
                                        Completó <strong><?= htmlspecialchars($activity['title']) ?></strong>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.85rem; color: #a0aec0;">
                                    <?= date('d/m/Y H:i', strtotime($activity['date'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab Content: Tests Completados -->
        <div id="tab-completed" class="tab-content">
            <?php if (empty($completed_tests)): ?>
                <div class="empty-state" style="background: white; padding: 3rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div class="empty-state-icon"><i class="fa-solid fa-circle-check" style="font-size: 3rem; color: #a0aec0;"></i></div>
                    <p><?= htmlspecialchars($profile_user['name']) ?> aún no ha completado tests</p>
                </div>
            <?php else: ?>
                <div style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <?php foreach ($completed_tests as $attempt): ?>
                        <div style="padding: 1rem; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #1a202c; margin-bottom: 0.25rem;">
                                    <?= htmlspecialchars($attempt['title']) ?>
                                </div>
                                <div style="font-size: 0.85rem; color: #718096;">
                                    Por <?= htmlspecialchars($attempt['author_name']) ?> • 
                                    <?= date('d/m/Y', strtotime($attempt['completed_at'])) ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.5rem; font-weight: 600; color: <?= $attempt['passed'] ? '#10b981' : '#ef4444' ?>;">
                                    <?= number_format($attempt['score'], 1) ?>%
                                </div>
                                <div style="font-size: 0.75rem; color: #718096;">
                                    <?= $attempt['passed'] ? 'Aprobado' : 'Reprobado' ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab Content: Estadísticas -->
        <div id="tab-stats" class="tab-content">
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['tests_created'] ?></div>
                    <div class="stat-label">Tests Creados</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['tests_completed'] ?></div>
                    <div class="stat-label">Tests Completados</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['tests_passed'] ?></div>
                    <div class="stat-label">Aprobados</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= number_format($stats['average_score'], 1) ?>%</div>
                    <div class="stat-label">Promedio</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['perfect_scores'] ?></div>
                    <div class="stat-label">Scores Perfectos</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['current_streak'] ?></div>
                    <div class="stat-label">Racha Actual</div>
                </div>
            </div>

            <!-- Gráfico simple de progreso -->
            <?php if ($stats['tests_completed'] > 0): ?>
                <div style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; color: #1a202c;">Rendimiento General</h3>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="color: #718096;">Tasa de Aprobación</span>
                            <span style="font-weight: 600; color: #667eea;">
                                <?= $stats['tests_completed'] > 0 ? number_format(($stats['tests_passed'] / $stats['tests_completed']) * 100, 1) : 0 ?>%
                            </span>
                        </div>
                        <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; width: <?= $stats['tests_completed'] > 0 ? ($stats['tests_passed'] / $stats['tests_completed']) * 100 : 0 ?>%;"></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        function showTab(tabName) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Desactivar todos los tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activar el tab y contenido seleccionado
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>

    </script>
</body>
</html>