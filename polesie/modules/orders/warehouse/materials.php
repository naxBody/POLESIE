<?php
/**
 * Справочник материалов - полный каталог с фильтрацией
 * ОАО "Полесьеэлектромаш"
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/auth.php';
session_start();

if (!isLoggedIn()) {
    redirect('../../../login.php');
}

$user = getCurrentUser();
$pageTitle = 'Материалы';

// Загрузка данных из JSON
$jsonPath = BASE_PATH . '/../../list_materials.json';
$materialsData = [];
$categories = [];
$allMaterials = [];

if (file_exists($jsonPath)) {
    $jsonData = file_get_contents($jsonPath);
    $materialsData = json_decode($jsonData, true);
    $categories = $materialsData['categories'] ?? [];
    
    // Сбор всех материалов в плоский список
    foreach ($categories as $category) {
        if (isset($category['subcategories'])) {
            foreach ($category['subcategories'] as $subcategory) {
                if (isset($subcategory['materials'])) {
                    foreach ($subcategory['materials'] as $material) {
                        $material['parent_category'] = $category;
                        $material['subcategory'] = $subcategory;
                        $allMaterials[] = $material;
                    }
                }
            }
        }
    }
}

// Получение уникальных значений для фильтров
$materialGrades = [];
$standards = [];
$productForms = [];
$units = [];
$criticalLevels = ['Все', 'Обычные', 'Ответственные'];

foreach ($allMaterials as $mat) {
    $specs = $mat['specifications'] ?? [];
    
    if (!empty($specs['material_grade']) && !in_array($specs['material_grade'], $materialGrades)) {
        $materialGrades[] = $specs['material_grade'];
    }
    if (!empty($specs['standard_doc']) && !in_array($specs['standard_doc'], $standards)) {
        $standards[] = $specs['standard_doc'];
    }
    if (!empty($specs['product_form']) && !in_array($specs['product_form'], $productForms)) {
        $productForms[] = $specs['product_form'];
    }
    if (!empty($mat['base_unit']) && !in_array($mat['base_unit'], $units)) {
        $units[] = $mat['base_unit'];
    }
}

sort($materialGrades);
sort($standards);
sort($productForms);
sort($units);

// Применение фильтров
$filteredMaterials = $allMaterials;

// Фильтр по категории
$filterCategory = $_GET['category'] ?? '';
if ($filterCategory !== '') {
    $filteredMaterials = array_filter($filteredMaterials, function($m) use ($filterCategory) {
        return $m['category_id'] == $filterCategory;
    });
}

// Фильтр по поиску
$filterSearch = $_GET['search'] ?? '';
if ($filterSearch !== '') {
    $filteredMaterials = array_filter($filteredMaterials, function($m) use ($filterSearch) {
        return stripos($m['name_full'], $filterSearch) !== false || 
               stripos($m['name_short'], $filterSearch) !== false ||
               stripos($m['code_internal'], $filterSearch) !== false;
    });
}

// Фильтр по марке материала
$filterGrade = $_GET['grade'] ?? '';
if ($filterGrade !== '') {
    $filteredMaterials = array_filter($filteredMaterials, function($m) use ($filterGrade) {
        return isset($m['specifications']['material_grade']) && 
               $m['specifications']['material_grade'] === $filterGrade;
    });
}

// Фильтр по стандарту
$filterStandard = $_GET['standard'] ?? '';
if ($filterStandard !== '') {
    $filteredMaterials = array_filter($filteredMaterials, function($m) use ($filterStandard) {
        return isset($m['specifications']['standard_doc']) && 
               $m['specifications']['standard_doc'] === $filterStandard;
    });
}

// Фильтр по форме изделия
$filterForm = $_GET['form'] ?? '';
if ($filterForm !== '') {
    $filteredMaterials = array_filter($filteredMaterials, function($m) use ($filterForm) {
        return isset($m['specifications']['product_form']) && 
               $m['specifications']['product_form'] === $filterForm;
    });
}

// Фильтр по ответственности
$filterCritical = $_GET['critical'] ?? '';
if ($filterCritical !== '') {
    $filteredMaterials = array_filter($filteredMaterials, function($m) use ($filterCritical) {
        if ($filterCritical === 'critical') {
            return !empty($m['is_critical']);
        } elseif ($filterCritical === 'non_critical') {
            return empty($m['is_critical']);
        }
        return true;
    });
}

// Фильтр по требованию сертификата
$filterCert = $_GET['cert'] ?? '';
if ($filterCert !== '') {
    $filteredMaterials = array_filter($filteredMaterials, function($m) use ($filterCert) {
        if ($filterCert === 'required') {
            return !empty($m['requires_cert']);
        } elseif ($filterCert === 'not_required') {
            return empty($m['requires_cert']);
        }
        return true;
    });
}

// Сортировка
$sortBy = $_GET['sort'] ?? 'name';
$sortOrder = $_GET['order'] ?? 'asc';

usort($filteredMaterials, function($a, $b) use ($sortBy, $sortOrder) {
    $result = 0;
    switch ($sortBy) {
        case 'name':
            $result = strcmp($a['name_full'], $b['name_full']);
            break;
        case 'code':
            $result = strcmp($a['code_internal'], $b['code_internal']);
            break;
        case 'category':
            $result = strcmp($a['parent_category']['name_ru'] ?? '', $b['parent_category']['name_ru'] ?? '');
            break;
        case 'grade':
            $gradeA = $a['specifications']['material_grade'] ?? '';
            $gradeB = $b['specifications']['material_grade'] ?? '';
            $result = strcmp($gradeA, $gradeB);
            break;
    }
    return $sortOrder === 'desc' ? -$result : $result;
});

// Переменные для topbar
$notificationCount = 0;

include '../../../includes/sidebar.php';
include '../../../includes/topbar.php';
?>

<style>
.materials-page {
    padding: 24px;
}

.filters-panel {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: var(--shadow);
}

.filters-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.filters-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-label {
    font-size: 12px;
    font-weight: 500;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.filter-select,
.filter-input {
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 14px;
    background: var(--bg-primary);
    color: var(--text-primary);
    cursor: pointer;
    transition: border-color var(--transition-fast);
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.filter-actions {
    display: flex;
    gap: 12px;
    margin-top: 16px;
    align-items: center;
}

.active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border-color);
}

.filter-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary-color);
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
}

.filter-chip-remove {
    cursor: pointer;
    opacity: 0.7;
    transition: opacity var(--transition-fast);
}

.filter-chip-remove:hover {
    opacity: 1;
}

.stats-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 16px 20px;
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
}

.stats-count {
    font-size: 14px;
    color: var(--text-secondary);
}

.stats-count strong {
    color: var(--text-primary);
    font-size: 16px;
}

.view-controls {
    display: flex;
    gap: 8px;
}

.view-btn {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all var(--transition-fast);
    font-size: 14px;
}

.view-btn.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.materials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.material-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 20px;
    box-shadow: var(--shadow);
    transition: transform var(--transition-fast), box-shadow var(--transition-fast);
    cursor: pointer;
    border: 1px solid transparent;
}

.material-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary-color);
}

.material-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 12px;
}

.material-category {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    margin-bottom: 4px;
}

.material-name {
    font-size: 15px;
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1.4;
}

.material-code {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: var(--text-secondary);
    background: var(--gray-100);
    padding: 4px 8px;
    border-radius: 4px;
    margin-top: 8px;
    display: inline-block;
}

.material-specs {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border-color);
}

.spec-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-bottom: 8px;
}

.spec-label {
    color: var(--text-secondary);
}

.spec-value {
    font-weight: 500;
    color: var(--text-primary);
}

.material-badges {
    display: flex;
    gap: 6px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.badge-critical {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.badge-cert {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning-color);
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.materials-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow);
}

.materials-table thead th {
    padding: 14px 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
    background: var(--gray-50);
    border-bottom: 2px solid var(--border-color);
    cursor: pointer;
    transition: background var(--transition-fast);
}

.materials-table thead th:hover {
    background: var(--gray-100);
}

.materials-table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
    font-size: 14px;
}

.materials-table tbody tr:hover {
    background: var(--gray-50);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.empty-state h3 {
    font-size: 18px;
    color: var(--text-primary);
    margin-bottom: 8px;
}

.empty-state p {
    color: var(--text-secondary);
}

/* Modal styles */
.modal-material {
    max-width: 800px;
}

.modal-material-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.modal-section {
    margin-bottom: 20px;
}

.modal-section-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--primary-color);
}

.specs-list {
    display: grid;
    gap: 10px;
}

.spec-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 12px;
    background: var(--gray-50);
    border-radius: var(--border-radius);
    font-size: 13px;
}

.spec-item-label {
    color: var(--text-secondary);
}

.spec-item-value {
    font-weight: 500;
    color: var(--text-primary);
}

@media (max-width: 768px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .materials-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-material-body {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="materials-page">
    <!-- Панель фильтров -->
    <div class="filters-panel">
        <div class="filters-header">
            <div class="filters-title">
                <span>🔍</span>
                Фильтры материалов
            </div>
        </div>
        
        <form method="GET" id="filtersForm">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Поиск</label>
                    <input type="text" 
                           name="search" 
                           class="filter-input" 
                           placeholder="Название, код..."
                           value="<?= e($filterSearch) ?>">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Категория</label>
                    <select name="category" class="filter-select">
                        <option value="">Все категории</option>
                        <?php foreach ($categories as $cat): ?>
                            <?php if (isset($cat['subcategories'])): ?>
                                <?php foreach ($cat['subcategories'] as $subcat): ?>
                                    <option value="<?= $subcat['id'] ?>" 
                                            <?= $filterCategory == $subcat['id'] ? 'selected' : '' ?>>
                                        <?= e($cat['name_ru']) ?> → <?= e($subcat['name_ru']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Марка материала</label>
                    <select name="grade" class="filter-select">
                        <option value="">Все марки</option>
                        <?php foreach ($materialGrades as $grade): ?>
                            <option value="<?= e($grade) ?>" <?= $filterGrade === $grade ? 'selected' : '' ?>>
                                <?= e($grade) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Стандарт</label>
                    <select name="standard" class="filter-select">
                        <option value="">Все стандарты</option>
                        <?php foreach ($standards as $std): ?>
                            <option value="<?= e($std) ?>" <?= $filterStandard === $std ? 'selected' : '' ?>>
                                <?= e($std) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Форма изделия</label>
                    <select name="form" class="filter-select">
                        <option value="">Все формы</option>
                        <?php foreach ($productForms as $form): ?>
                            <option value="<?= e($form) ?>" <?= $filterForm === $form ? 'selected' : '' ?>>
                                <?= e($form) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Ответственность</label>
                    <select name="critical" class="filter-select">
                        <option value="">Все</option>
                        <option value="critical" <?= $filterCritical === 'critical' ? 'selected' : '' ?>>Ответственные</option>
                        <option value="non_critical" <?= $filterCritical === 'non_critical' ? 'selected' : '' ?>>Обычные</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Сертификат</label>
                    <select name="cert" class="filter-select">
                        <option value="">Все</option>
                        <option value="required" <?= $filterCert === 'required' ? 'selected' : '' ?>>Требуется</option>
                        <option value="not_required" <?= $filterCert === 'not_required' ? 'selected' : '' ?>>Не требуется</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Сортировка</label>
                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>По названию</option>
                        <option value="code" <?= $sortBy === 'code' ? 'selected' : '' ?>>По коду</option>
                        <option value="category" <?= $sortBy === 'category' ? 'selected' : '' ?>>По категории</option>
                        <option value="grade" <?= $sortBy === 'grade' ? 'selected' : '' ?>>По марке</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Применить фильтры</button>
                <a href="materials.php" class="btn btn-outline">Сбросить</a>
                
                <?php if ($filterSearch || $filterCategory || $filterGrade || $filterStandard || $filterForm || $filterCritical || $filterCert): ?>
                    <div class="active-filters">
                        <span style="font-size: 13px; color: var(--text-secondary); align-self: center;">Активные фильтры:</span>
                        <?php if ($filterSearch): ?>
                            <span class="filter-chip">
                                Поиск: <?= e($filterSearch) ?>
                                <a href="?<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'search', ARRAY_FILTER_USE_KEY)) ?>" class="filter-chip-remove">✕</a>
                            </span>
                        <?php endif; ?>
                        <?php if ($filterCategory): ?>
                            <?php 
                            $catName = '';
                            foreach ($categories as $cat) {
                                if (isset($cat['subcategories'])) {
                                    foreach ($cat['subcategories'] as $subcat) {
                                        if ($subcat['id'] == $filterCategory) {
                                            $catName = $cat['name_ru'] . ' → ' . $subcat['name_ru'];
                                        }
                                    }
                                }
                            }
                            ?>
                            <span class="filter-chip">
                                Категория: <?= e($catName) ?>
                                <a href="?<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'category', ARRAY_FILTER_USE_KEY)) ?>" class="filter-chip-remove">✕</a>
                            </span>
                        <?php endif; ?>
                        <?php if ($filterGrade): ?>
                            <span class="filter-chip">
                                Марка: <?= e($filterGrade) ?>
                                <a href="?<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'grade', ARRAY_FILTER_USE_KEY)) ?>" class="filter-chip-remove">✕</a>
                            </span>
                        <?php endif; ?>
                        <?php if ($filterStandard): ?>
                            <span class="filter-chip">
                                Стандарт: <?= e($filterStandard) ?>
                                <a href="?<?= http_build_query(array_filter($_GET, fn($k) => $k !== 'standard', ARRAY_FILTER_USE_KEY)) ?>" class="filter-chip-remove">✕</a>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Статистика и управление видом -->
    <div class="stats-bar">
        <div class="stats-count">
            Найдено материалов: <strong><?= count($filteredMaterials) ?></strong> из <strong><?= count($allMaterials) ?></strong>
        </div>
        <div class="view-controls">
            <button class="view-btn active" onclick="setView('grid')" title="Карточки">▦</button>
            <button class="view-btn" onclick="setView('table')" title="Таблица">☰</button>
        </div>
    </div>
    
    <!-- Список материалов -->
    <?php if (empty($filteredMaterials)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <h3>Материалы не найдены</h3>
            <p>Попробуйте изменить параметры фильтров</p>
        </div>
    <?php else: ?>
        <!-- Вид: Карточки -->
        <div class="materials-grid" id="materialsGrid">
            <?php foreach ($filteredMaterials as $material): ?>
                <div class="material-card" onclick="openMaterialModal(<?= htmlspecialchars(json_encode($material), ENT_QUOTES, 'UTF-8') ?>)">
                    <div class="material-card-header">
                        <div>
                            <div class="material-category">
                                <?= e($material['parent_category']['name_ru'] ?? '') ?> → 
                                <?= e($material['subcategory']['name_ru'] ?? '') ?>
                            </div>
                            <div class="material-name"><?= e($material['name_full']) ?></div>
                        </div>
                    </div>
                    
                    <div class="material-code"><?= e($material['code_internal']) ?></div>
                    
                    <div class="material-specs">
                        <?php 
                        $specs = $material['specifications'] ?? [];
                        $displaySpecs = array_slice($specs, 0, 3, true);
                        foreach ($displaySpecs as $key => $value): 
                            if (is_array($value)) {
                                $value = implode(', ', array_slice($value, 0, 3)) . (count($value) > 3 ? '...' : '');
                            }
                        ?>
                            <div class="spec-row">
                                <span class="spec-label"><?= e(ucfirst(str_replace('_', ' ', $key))) ?></span>
                                <span class="spec-value"><?= is_array($value) ? e(implode(', ', array_slice($value, 0, 3))) : e($value) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="material-badges">
                        <?php if (!empty($material['is_critical'])): ?>
                            <span class="badge-critical">⚠ Ответственный</span>
                        <?php endif; ?>
                        <?php if (!empty($material['requires_cert'])): ?>
                            <span class="badge-cert">📄 Сертификат</span>
                        <?php endif; ?>
                        <span style="font-size: 11px; color: var(--text-muted);">📦 <?= e($material['base_unit']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Вид: Таблица -->
        <table class="materials-table" id="materialsTable" style="display: none;">
            <thead>
                <tr>
                    <th>Код</th>
                    <th>Наименование</th>
                    <th>Категория</th>
                    <th>Марка</th>
                    <th>Стандарт</th>
                    <th>Ед.</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filteredMaterials as $material): ?>
                    <tr onclick="openMaterialModal(<?= htmlspecialchars(json_encode($material), ENT_QUOTES, 'UTF-8') ?>)" style="cursor: pointer;">
                        <td><code><?= e($material['code_internal']) ?></code></td>
                        <td>
                            <strong><?= e($material['name_full']) ?></strong><br>
                            <small style="color: var(--text-muted);"><?= e($material['name_short']) ?></small>
                        </td>
                        <td>
                            <small><?= e($material['parent_category']['name_ru'] ?? '') ?></small><br>
                            <strong><?= e($material['subcategory']['name_ru'] ?? '') ?></strong>
                        </td>
                        <td><?= e($material['specifications']['material_grade'] ?? '—') ?></td>
                        <td><small><?= e($material['specifications']['standard_doc'] ?? '—') ?></small></td>
                        <td><?= e($material['base_unit']) ?></td>
                        <td>
                            <?php if (!empty($material['is_critical'])): ?>
                                <span class="badge badge-danger">Ответств.</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Обычный</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Модальное окно материала -->
<div class="modal-overlay" id="materialModal" onclick="closeMaterialModal(event)">
    <div class="modal modal-material">
        <div class="modal-header">
            <h3 class="modal-title" id="modalMaterialName"></h3>
            <button class="modal-close" onclick="closeMaterialModal()">✕</button>
        </div>
        <div class="modal-body modal-material-body" id="modalMaterialBody">
            <!-- Контент заполняется через JS -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeMaterialModal()">Закрыть</button>
            <button class="btn btn-primary" onclick="printMaterial()">🖨 Печать</button>
        </div>
    </div>
</div>

<script>
let currentMaterial = null;

function setView(view) {
    const grid = document.getElementById('materialsGrid');
    const table = document.getElementById('materialsTable');
    const btns = document.querySelectorAll('.view-btn');
    
    if (view === 'grid') {
        grid.style.display = 'grid';
        table.style.display = 'none';
        btns[0].classList.add('active');
        btns[1].classList.remove('active');
    } else {
        grid.style.display = 'none';
        table.style.display = 'table';
        btns[0].classList.remove('active');
        btns[1].classList.add('active');
    }
    
    localStorage.setItem('materialsView', view);
}

// Восстановление вида из localStorage
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('materialsView') || 'grid';
    setView(savedView);
});

function openMaterialModal(material) {
    currentMaterial = material;
    
    const modal = document.getElementById('materialModal');
    const nameEl = document.getElementById('modalMaterialName');
    const bodyEl = document.getElementById('modalMaterialBody');
    
    nameEl.textContent = material.name_full;
    
    const specs = material.specifications || {};
    let specsHtml = '';
    for (const [key, value] of Object.entries(specs)) {
        let displayValue = value;
        if (Array.isArray(value)) {
            displayValue = value.join(', ');
        } else if (typeof value === 'object') {
            displayValue = JSON.stringify(value, null, 2);
        }
        
        const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        specsHtml += `
            <div class="spec-item">
                <span class="spec-item-label">${label}</span>
                <span class="spec-item-value">${escapeHtml(displayValue)}</span>
            </div>
        `;
    }
    
    bodyEl.innerHTML = `
        <div class="modal-section">
            <div class="modal-section-title">📋 Общая информация</div>
            <div class="specs-list">
                <div class="spec-item">
                    <span class="spec-item-label">Внутренний код</span>
                    <span class="spec-item-value"><code>${escapeHtml(material.code_internal)}</code></span>
                </div>
                <div class="spec-item">
                    <span class="spec-item-label">Краткое название</span>
                    <span class="spec-item-value">${escapeHtml(material.name_short)}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-item-label">Категория</span>
                    <span class="spec-item-value">${escapeHtml(material.parent_category?.name_ru || '')} → ${escapeHtml(material.subcategory?.name_ru || '')}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-item-label">Единица измерения</span>
                    <span class="spec-item-value">${escapeHtml(material.base_unit)}</span>
                </div>
                ${material.alt_unit ? `
                <div class="spec-item">
                    <span class="spec-item-label">Альт. единица</span>
                    <span class="spec-item-value">${escapeHtml(material.alt_unit)} (коэф. ${material.conversion_factor})</span>
                </div>
                ` : ''}
            </div>
        </div>
        
        <div class="modal-section">
            <div class="modal-section-title">⚙️ Характеристики</div>
            <div class="specs-list">
                ${specsHtml}
            </div>
        </div>
        
        <div class="modal-section">
            <div class="modal-section-title">📌 Дополнительная информация</div>
            <div class="specs-list">
                <div class="spec-item">
                    <span class="spec-item-label">Ответственный материал</span>
                    <span class="spec-item-value">${material.is_critical ? '✅ Да' : '❌ Нет'}</span>
                </div>
                <div class="spec-item">
                    <span class="spec-item-label">Требуется сертификат</span>
                    <span class="spec-item-value">${material.requires_cert ? '✅ Да' : '❌ Нет'}</span>
                </div>
                <div class="spec-item" style="grid-column: 1 / -1;">
                    <span class="spec-item-label">Условия хранения</span>
                    <span class="spec-item-value">${escapeHtml(material.storage_condition || '—')}</span>
                </div>
            </div>
        </div>
    `;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeMaterialModal(event) {
    if (event && event.target !== event.currentTarget) return;
    
    const modal = document.getElementById('materialModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    currentMaterial = null;
}

function printMaterial() {
    if (!currentMaterial) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${currentMaterial.name_full}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 40px; }
                h1 { font-size: 20px; margin-bottom: 20px; }
                .section { margin-bottom: 20px; }
                .section-title { font-weight: bold; border-bottom: 2px solid #2563eb; padding-bottom: 8px; margin-bottom: 12px; }
                .spec-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                .spec-label { color: #666; }
                .spec-value { font-weight: 500; }
                code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
            </style>
        </head>
        <body>
            <h1>${currentMaterial.name_full}</h1>
            <p><strong>Код:</strong> <code>${currentMaterial.code_internal}</code></p>
            <p><strong>Краткое название:</strong> ${currentMaterial.name_short}</p>
            
            <div class="section">
                <div class="section-title">Характеристики</div>
                ${Object.entries(currentMaterial.specifications || {}).map(([key, value]) => `
                    <div class="spec-row">
                        <span class="spec-label">${key.replace(/_/g, ' ')}</span>
                        <span class="spec-value">${Array.isArray(value) ? value.join(', ') : value}</span>
                    </div>
                `).join('')}
            </div>
            
            <div class="section">
                <div class="section-title">Дополнительно</div>
                <div class="spec-row">
                    <span class="spec-label">Ответственный</span>
                    <span class="spec-value">${currentMaterial.is_critical ? 'Да' : 'Нет'}</span>
                </div>
                <div class="spec-row">
                    <span class="spec-label">Сертификат</span>
                    <span class="spec-value">${currentMaterial.requires_cert ? 'Требуется' : 'Не требуется'}</span>
                </div>
                <div class="spec-row">
                    <span class="spec-label">Условия хранения</span>
                    <span class="spec-value">${currentMaterial.storage_condition || '—'}</span>
                </div>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Закрытие модального окна по ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMaterialModal();
    }
});
</script>

<script src="../../assets/js/main.js"></script>
</body>
</html>
