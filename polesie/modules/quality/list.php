<?php
/**
 * Контроль качества (ОТК)
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
session_start();

if (!isLoggedIn()) {
    redirect('../../login.php');
}

$user = getCurrentUser();
$pdo = getDbConnection();

$pageTitle = 'Контроль качества';

$status = $_GET['status'] ?? '';

$sql = "SELECT qi.*, pt.quantity as task_quantity, p.name as product_name, 
               u.full_name as inspector_name,
               CASE WHEN qi.id IS NOT NULL THEN TRUE ELSE FALSE END as is_inspected
        FROM production_tasks pt 
        JOIN products p ON pt.product_id = p.id 
        LEFT JOIN quality_inspections qi ON pt.id = qi.task_id
        LEFT JOIN users u ON qi.inspector_id = u.id
        WHERE pt.status IN ('completed', 'in_progress')";
$params = [];

if ($status === 'pending') {
    $sql .= " AND qi.id IS NULL";
} elseif ($status === 'passed') {
    $sql .= " AND qi.result = 'passed'";
} elseif ($status === 'failed') {
    $sql .= " AND qi.result = 'failed'";
}

$sql .= " ORDER BY pt.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inspections = $stmt->fetchAll();

include '../../includes/sidebar.php';
include '../../includes/topbar.php';
?>

<div class="content">
    <div class="page-header">
        <div class="page-header-title">
            <h2>✅ ОТК - Контроль качества</h2>
            <p>Проверка качества продукции</p>
        </div>
        <div class="page-header-actions">
            <?php if (hasPermission('quality.create')): ?>
                <a href="create.php" class="btn btn-primary">+ Проверка</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <select name="status" style="width: 200px; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--border-radius);">
                        <option value="">Все</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Ожидают проверки</option>
                        <option value="passed" <?= $status === 'passed' ? 'selected' : '' ?>>Пройдено</option>
                        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Не пройдено</option>
                    </select>
                    <button type="submit" class="btn btn-secondary">Фильтр</button>
                    <a href="list.php" class="btn btn-outline">Сброс</a>
                </div>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Задание</th>
                        <th>Продукция</th>
                        <th>Кол-во</th>
                        <th>Статус задания</th>
                        <th>Результат ОТК</th>
                        <th>Инспектор</th>
                        <th>Дата проверки</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inspections as $i): ?>
                    <tr>
                        <td><strong>#<?= $i['task_id'] ?></strong></td>
                        <td><?= e($i['product_name']) ?></td>
                        <td><?= $i['task_quantity'] ?></td>
                        <td>
                            <?php if ($i['task_status'] === 'completed'): ?>
                                <span class="badge badge-success">Завершено</span>
                            <?php else: ?>
                                <span class="badge badge-info">В работе</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$i['is_inspected']): ?>
                                <span class="badge badge-warning">Ожидает</span>
                            <?php elseif ($i['result'] === 'passed'): ?>
                                <span class="badge badge-success">✓ Пройдено</span>
                            <?php else: ?>
                                <span class="badge badge-danger">✗ Не пройдено</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($i['inspector_name'] ?? '—') ?></td>
                        <td><?= $i['inspection_date'] ? date('d.m.Y H:i', strtotime($i['inspection_date'])) : '—' ?></td>
                        <td>
                            <?php if (!$i['is_inspected'] && hasPermission('quality.create')): ?>
                                <a href="create.php?task=<?= $i['task_id'] ?>" class="btn-icon" title="Проверить">✅</a>
                            <?php elseif ($i['is_inspected'] && hasPermission('quality.view')): ?>
                                <a href="view.php?id=<?= $i['id'] ?>" class="btn-icon" title="Просмотр">👁️</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($inspections)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">✅</div>
                    <h3>Нет заданий для проверки</h3>
                    <p>Задания появятся после начала производства</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
