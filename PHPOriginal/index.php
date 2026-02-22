<?php
$page_title = 'TestMaster - Inicio';
require_once 'includes/header.php';

// Obtener últimos tests públicos
$recent_tests = fetch_all("
    SELECT t.*, u.name as author_name,
           (SELECT COUNT(*) FROM questions WHERE test_id = t.id) as num_questions,
           (SELECT COUNT(*) FROM test_attempts WHERE test_id = t.id AND is_completed = 1) as attempts_count
    FROM tests t
    JOIN users u ON t.user_id = u.id
    WHERE t.is_public = 1 AND t.status = 'published'
    ORDER BY t.created_at DESC
    LIMIT 20
");

// Obtener estadísticas generales
$stats = fetch_one("
    SELECT 
        (SELECT COUNT(*) FROM tests WHERE is_public = 1 AND status = 'published') as total_tests,
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM test_attempts WHERE is_completed = 1) as total_attempts
");
?>

        <div class="page-header">
            <div>
                <h1>Tests recientes</h1>
                <p class="subtitle"><span class="count"><?= number_format($stats['total_tests']) ?></span> tests disponibles</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <select>
                <option>Todas las categorías</option>
                <option>Ciencia</option>
                <option>Informática</option>
                <option>Geografía</option>
                <option>Historia</option>
                <option>Idiomas</option>
                <option>Matemáticas</option>
                <option>Arte</option>
                <option>Oposiciones</option>
                <option>Universidad</option>
                <option>Test de Conducir</option>
            </select>

            <select>
                <option>Todos los tiempos</option>
                <option>Hoy</option>
                <option>Esta semana</option>
                <option>Este mes</option>
            </select>

            <select>
                <option>Más recientes</option>
                <option>Más vistos</option>
                <option>Mejor valorados</option>
            </select>
        </div>

        <!-- Lista de tests -->
        <div class="tests-table-container">
            <?php if (empty($recent_tests)): ?>
                <div style="text-align: center; padding: 3rem; color: #666; background: white; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <p>No hay tests disponibles aún.</p>
                    <?php if (is_logged_in()): ?>
                        <a href="pages/create-test.php">
                            <button class="btn" style="margin-top: 1rem;">Crear el Primer Test</button>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="tests-table">
                    <thead>
                        <tr>
                            <th class="th-title">Últimos tests</th>
                            <th class="th-date"><i class="fa-solid fa-clock"></i> Fecha</th>
                            <th class="th-questions"><i class="fa-solid fa-file-lines"></i> Preg.</th>
                            <th class="th-views"><i class="fa-solid fa-eye"></i> Visto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_tests as $test): ?>
                            <tr class="test-row">
                                <td class="td-title">
                                    <a href="pages/view-test.php?id=<?= $test['id'] ?>" class="test-link">
                                        <?= e($test['title']) ?>
                                        <?php if ($test['attempts_count'] > 10): ?>
                                            <span class="badge">popular</span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td class="td-date">
                                    <?php
                                    $date = new DateTime($test['created_at']);
                                    echo $date->format('m/d/Y h:i:s A');
                                    ?>
                                </td>
                                <td class="td-questions"><?= $test['num_questions'] ?> preg</td>
                                <td class="td-views"><?= $test['attempts_count'] ?> visto<?= $test['attempts_count'] != 1 ? 's' : '' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

<?php require_once 'includes/footer.php'; ?>