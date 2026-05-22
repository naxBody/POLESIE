<?php
/**
 * Список продукции
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
session_start();

if (!isLoggedIn()) {
    redirect(pageUrl('login.php'));
}

$user = getCurrentUser();
$pdo = getDbConnection();

$pageTitle = 'Продукция';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN product_categories c ON p.category_id = c.id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.article LIKE ?)";
    $params = ["%$search%", "%$search%"];
}

if ($category) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
}

$sql .= " ORDER BY p.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Получение категорий
$catStmt = $pdo->query("SELECT * FROM product_categories ORDER BY name");
$categories = $catStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
</head>
<body>
    <div class="app-container">
        <!-- Боковая панель -->
        <?php include BASE_PATH . '/includes/sidebar.php'; ?>
        
        <!-- Основной контент -->
        <div class="main-content">
            <!-- Верхняя панель -->
            <?php include BASE_PATH . '/includes/topbar.php'; ?>
            
            <!-- Контентная область -->
            <div class="content-area">
                <div class="content">
                    <div class="page-header">
                        <div class="page-header-title">
                            <h2>📦 Продукция</h2>
                            <p>Каталог выпускаемой продукции</p>
                        </div>
                        <div class="page-header-actions">
                            <?php if (hasPermission('products.create')): ?>
                                <a href="create.php" class="btn btn-primary">+ Добавить</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
            <form method="GET" class="filter-form">
                <div class="filter-row">
                    <input type="text" name="search" placeholder="Поиск по названию, артикулу..." value="<?= e($search) ?>" 
                           style="flex: 1; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--border-radius);">
                    <select name="category" style="width: 200px; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: var(--border-radius);">
                        <option value="">Все категории</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-secondary">Найти</button>
                    <a href="list.php" class="btn btn-outline">Сброс</a>
                </div>
            </form>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Артикул</th>
                        <th>Наименование</th>
                        <th>Категория</th>
                        <th>Ед. изм.</th>
                        <th>Цена (BYN)</th>
                        <th>Описание</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><code><?= e($p['article']) ?></code></td>
                        <td><strong><?= e($p['name']) ?></strong></td>
                        <td><?= e($p['category_name'] ?? '—') ?></td>
                        <td><?= e($p['unit']) ?></td>
                        <td><?= number_format($p['price'], 2, ',', ' ') ?></td>
                        <td><?= e(mb_substr($p['description'] ?? '', 0, 50)) ?><?= mb_strlen($p['description'] ?? '') > 50 ? '...' : '' ?></td>
                        <td>
                            <?php if (hasPermission('products.edit')): ?>
                                <a href="edit.php?id=<?= $p['id'] ?>" class="btn-icon" title="Редактировать">✏️</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <h3>Продукция не найдена</h3>
                    <p>Добавьте первую позицию продукции</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

    <script src="<?= asset('assets/js/main.js') ?>"></script>
</body>
</html>
