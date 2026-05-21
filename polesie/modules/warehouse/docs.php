<?php
/**
 * Справочник документов и расшифровка аббревиатур материалов
 * ОАО "Полесьеэлектромаш"
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
session_start();

if (!isLoggedIn()) {
    redirect('../../login.php');
}

$user = getCurrentUser();
$pdo = getDbConnection();
$pageTitle = 'Документы и справочники';

// Загрузка данных из JSON
$docsPath = BASE_PATH . '/../list_materials_docs.json';
$docsData = [];

if (file_exists($docsPath)) {
    $jsonData = file_get_contents($docsPath);
    $docsData = json_decode($jsonData, true);
}

$abbreviations = $docsData['abbreviation_decodings'] ?? [];
$gostStandards = $docsData['gost_standards'] ?? [];
$codeStructures = $docsData['material_codes_structure'] ?? [];

// Получение количества уведомлений
$notifications = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? AND is_read = FALSE 
    ORDER BY created_at DESC 
    LIMIT 10
");
$notifications->execute([$user['id']]);
$notificationList = $notifications->fetchAll();
$notificationCount = count($notificationList);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
    .docs-page {
        padding: 24px;
    }
    
    .docs-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 0;
    }
    
    .doc-tab {
        padding: 12px 24px;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: var(--text-secondary);
        transition: all var(--transition-fast);
    }
    
    .doc-tab:hover {
        color: var(--primary-color);
    }
    
    .doc-tab.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }
    
    .doc-section {
        display: none;
    }
    
    .doc-section.active {
        display: block;
    }
    
    .standards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
        gap: 16px;
    }
    
    .standard-card {
        background: var(--bg-primary);
        border-radius: var(--border-radius-lg);
        padding: 20px;
        box-shadow: var(--shadow);
        border-left: 4px solid var(--primary-color);
    }
        .standard-card-link:hover .standard-card {
                transform: translateY(-2px);
                box-shadow: var(--shadow-md);
            }
    
    .standard-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    
    .standard-number {
        font-size: 16px;
        font-weight: 700;
        color: var(--primary-color);
        font-family: 'Courier New', monospace;
    }
    
    .standard-status {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-active {
        background: rgba(34, 197, 94, 0.1);
        color: #22c55e;
    }
    
    .standard-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 8px;
        line-height: 1.4;
    }
    
    .standard-category {
        font-size: 12px;
        color: var(--text-secondary);
    }
    
    .abbreviations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
    }
    
    .abbreviation-card {
        background: var(--bg-primary);
        border-radius: var(--border-radius-lg);
        padding: 16px;
        box-shadow: var(--shadow);
    }
    
    .abbr-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    
    .abbr-code {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary-color);
        font-family: 'Courier New', monospace;
        background: rgba(37, 99, 235, 0.1);
        padding: 8px 12px;
        border-radius: 8px;
    }
    
    .abbr-full-name {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-primary);
    }
    
    .abbr-description {
        font-size: 13px;
        color: var(--text-secondary);
        line-height: 1.5;
        margin-bottom: 8px;
    }
    
    .abbr-category {
        font-size: 11px;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .code-structure-section {
        margin-bottom: 24px;
    }
    
    .code-structure-card {
        background: var(--bg-primary);
        border-radius: var(--border-radius-lg);
        padding: 20px;
        box-shadow: var(--shadow);
        margin-bottom: 16px;
    }
    
    .code-pattern {
        font-family: 'Courier New', monospace;
        font-size: 14px;
        background: var(--gray-100);
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 16px;
        color: var(--primary-color);
    }
    
    .code-example {
        font-family: 'Courier New', monospace;
        font-size: 16px;
        font-weight: 600;
        color: var(--text-primary);
        background: rgba(37, 99, 235, 0.1);
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 16px;
    }
    
    .explanation-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .explanation-table td {
        padding: 8px 12px;
        border-bottom: 1px solid var(--border-color);
        font-size: 13px;
    }
    
    .explanation-table td:first-child {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        color: var(--primary-color);
        width: 150px;
    }
    
    .search-box {
        margin-bottom: 24px;
    }
    
    .search-input {
        width: 100%;
        max-width: 400px;
        padding: 12px 16px;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-lg);
        font-size: 14px;
        background: var(--bg-primary);
        color: var(--text-primary);
    }
    
    .search-input:focus {
        outline: none;
        border-color: var(--primary-color);
    }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../../includes/topbar.php'; ?>
            
            <div class="content-area">
                <div class="docs-page">
                    <h1 style="margin-bottom: 24px;">📚 Документы и справочники материалов</h1>
                    
                    <!-- Вкладки -->
                    <div class="docs-tabs">
                        <button class="doc-tab active" onclick="switchTab('gost')">📋 ГОСТы и стандарты</button>
                        <button class="doc-tab" onclick="switchTab('abbreviations')">🔤 Расшифровка аббревиатур</button>
                        <button class="doc-tab" onclick="switchTab('structures')">📝 Структура кодов материалов</button>
                    </div>
                    
                    <!-- Секция: ГОСТы -->
                    <div id="gost-section" class="doc-section active">
                        <div class="search-box">
                            <input type="text" class="search-input" placeholder="🔍 Поиск ГОСТа..." onkeyup="filterStandards(this.value)">
                        </div>
                        
                        <div class="standards-grid" id="standardsGrid">
                            <?php foreach ($gostStandards as $gost): ?>
                            <?php 
                                // Генерируем ссылку на ГОСТ
                                $gostNumber = preg_replace('/ГОСТ\s*([0-9.]+).*/i', '$1', $gost['gost_number']);
                                $gostNumber = str_replace('.', '-', $gostNumber);
                                $gostLink = 'https://docs.cntd.ru/document/' . $gostNumber;
                            ?>
                            <a href="<?= $gostLink ?>" target="_blank" class="standard-card-link" style="text-decoration: none; color: inherit;">
                                <div class="standard-card" data-gost="<?= e(strtolower($gost['gost_number'])) ?>" data-title="<?= e(strtolower($gost['title'])) ?>">
                                    <div class="standard-header">
                                        <span class="standard-number"><?= e($gost['gost_number']) ?></span>
                                        <span class="standard-status status-active\"><?= e($gost['status']) ?></span>
                                    </div>
                                    <div class="standard-title"><?= e($gost['title']) ?></div>
                                    <div class="standard-category">📁 <?= e($gost['category']) ?></div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Секция: Аббревиатуры -->
                    <div id="abbreviations-section" class="doc-section">
                        <div class="search-box">
                            <input type="text" class="search-input" placeholder="🔍 Поиск аббревиатуры..." onkeyup="filterAbbreviations(this.value)">
                        </div>
                        
                        <div class="abbreviations-grid" id="abbreviationsGrid">
                            <?php foreach ($abbreviations as $code => $info): ?>
                            <div class="abbreviation-card" data-code="<?= e(strtolower($code)) ?>" data-name="<?= e(strtolower($info['full_name'])) ?>">
                                <div class="abbr-header">
                                    <span class="abbr-code"><?= e($code) ?></span>
                                    <span class="abbr-full-name"><?= e($info['full_name']) ?></span>
                                </div>
                                <div class="abbr-description"><?= e($info['description']) ?></div>
                                <div class="abbr-category">📁 <?= e($info['category']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Секция: Структура кодов -->
                    <div id="structures-section" class="doc-section">
                        <?php foreach ($codeStructures as $type => $structure): ?>
                        <div class="code-structure-section">
                            <h3 style="margin-bottom: 16px;"><?= e(ucfirst(str_replace('_', ' ', $type))) ?></h3>
                            <div class="code-structure-card">
                                <div class="code-pattern">
                                    📐 Шаблон: <?= e($structure['pattern']) ?>
                                </div>
                                <div class="code-example">
                                    💡 Пример: <?= e($structure['example']) ?>
                                </div>
                                <table class="explanation-table">
                                    <?php foreach ($structure['explanation'] as $part => $desc): ?>
                                    <tr>
                                        <td><?= e($part) ?></td>
                                        <td><?= e($desc) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function switchTab(tabName) {
        // Убираем активный класс со всех вкладок
        document.querySelectorAll('.doc-tab').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.doc-section').forEach(section => section.classList.remove('active'));
        
        // Добавляем активный класс выбранной вкладке
        event.target.classList.add('active');
        document.getElementById(tabName + '-section').classList.add('active');
    }
    
    function filterStandards(query) {
        const cards = document.querySelectorAll('#standardsGrid .standard-card');
        cards.forEach(card => {
            const gost = card.dataset.gost;
            const title = card.dataset.title;
            if (gost.includes(query.toLowerCase()) || title.includes(query.toLowerCase())) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    function filterAbbreviations(query) {
        const cards = document.querySelectorAll('#abbreviationsGrid .abbreviation-card');
        cards.forEach(card => {
            const code = card.dataset.code;
            const name = card.dataset.name;
            if (code.includes(query.toLowerCase()) || name.includes(query.toLowerCase())) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    </script>
</body>
</html>
