<?php
/**
 * Список производственных заданий
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
session_start();

if (!isLoggedIn()) {
    redirect(pageUrl('login.php'));
}

$user = getCurrentUser();
$pdo = getDbConnection();

$pageTitle = 'Производство';

$status = $_GET['status'] ?? '';
$orderId = $_GET['order'] ?? '';

$sql = "SELECT pt.*, o.order_number, p.name as product_name, u.full_name as responsible_name 
        FROM production_tasks pt 
        JOIN orders o ON pt.order_id = o.id 
        JOIN products p ON pt.product_id = p.id 
        LEFT JOIN users u ON pt.responsible_id = u.id 
        WHERE 1=1";
$params = [];

if ($status) {
    $sql .= " AND pt.status = ?";
    $params[] = $status;
}

if ($orderId) {
    $sql .= " AND pt.order_id = ?";
    $params[] = $orderId;
}

$sql .= " ORDER BY pt.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

require_once BASE_PATH . '/includes/sidebar.php';
require_once BASE_PATH . '/includes/topbar.php';
?>

<div class="content">
    <div class="page-header">
        <div class="page-header-title">
            <h2>🏭 Производство</h2>
            <p>Управление производственными заданиями</p>
        </div>
        <div class="page-header-actions">
            <?php if (hasPermission('production.create')): ?>
                <a href="create.php" class="btn btn-primary">+ Задание</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <select name="status" style="width: 200px; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--border-radius);">
                        <option value="">Все статусы</option>
                        <option value="planned" <?= $status === 'planned' ? 'selected' : '' ?>>Планируется</option>
                        <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>В работе</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Завершено</option>
                        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Отменено</option>
                    </select>
                    <button type="submit" class="btn btn-secondary">Фильтр</button>
                    <a href="list.php" class="btn btn-outline">Сброс</a>
                </div>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Продукция</th>
                        <th>Заказ</th>
                        <th>Кол-во</th>
                        <th>План. дата</th>
                        <th>Статус</th>
                        <th>Ответственный</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $t): ?>
                    <tr>
                        <td><strong>#<?= $t['id'] ?></strong></td>
                        <td><?= e($t['product_name']) ?></td>
                        <td><?= e($t['order_number']) ?></td>
                        <td><?= $t['quantity'] ?></td>
                        <td><?= date('d.m.Y', strtotime($t['planned_date'])) ?></td>
                        <td>
                            <?php if ($t['status'] === 'planned'): ?>
                                <span class="badge badge-warning">Планируется</span>
                            <?php elseif ($t['status'] === 'in_progress'): ?>
                                <span class="badge badge-info">В работе</span>
                            <?php elseif ($t['status'] === 'completed'): ?>
                                <span class="badge badge-success">Завершено</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Отменено</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($t['responsible_name'] ?? '—') ?></td>
                        <td>
                            <a href="view.php?id=<?= $t['id'] ?>" class="btn-icon" title="Просмотр">👁️</a>
                            <?php if (hasPermission('production.edit')): ?>
                                <a href="edit.php?id=<?= $t['id'] ?>" class="btn-icon" title="Редактировать">✏️</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🏭</div>
                    <h3>Заданий нет</h3>
                    <p>Создайте первое производственное задание</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

    <script src="<?= asset('assets/js/main.js') ?>"></script>
</body>
</html>
