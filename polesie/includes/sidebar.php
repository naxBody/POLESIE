<!-- Боковая панель навигации -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">⚡</div>
            <span>Полесьеэлектромаш</span>
        </div>
    </div>
    
    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <div class="sidebar-user-avatar">
                <?= mb_substr($user['full_name'], 0, 1) ?>
            </div>
            <div class="sidebar-user-details">
                <div class="sidebar-user-name"><?= e($user['full_name']) ?></div>
                <div class="sidebar-user-role"><?= e($user['role_name']) ?></div>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Основное -->
        <div class="sidebar-nav-section">
            <div class="sidebar-nav-title">Основное</div>
            <a href="../index.php" class="sidebar-nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <span class="sidebar-nav-icon">📊</span>
                <span>Панель управления</span>
            </a>
            <a href="#" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">🔔</span>
                <span>Уведомления</span>
                <?php if ($notificationCount > 0): ?>
                <span style="margin-left: auto; background: var(--danger-color); padding: 2px 8px; border-radius: 10px; font-size: 11px;"><?= $notificationCount ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <!-- Заказы -->
        <div class="sidebar-nav-section">
            <div class="sidebar-nav-title">Заказы</div>
            <a href="orders/list.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">📦</span>
                <span>Все заказы</span>
            </a>
            <a href="orders/create.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">➕</span>
                <span>Новый заказ</span>
            </a>
            <a href="contractors/list.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">🏢</span>
                <span>Контрагенты</span>
            </a>
        </div>
        
        <!-- Производство -->
        <div class="sidebar-nav-section">
            <div class="sidebar-nav-title">Производство</div>
            <a href="production/list.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">⚙️</span>
                <span>Производственные задания</span>
            </a>
            <a href="production/stages.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">📋</span>
                <span>Этапы производства</span>
            </a>
            <a href="products/list.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">🔧</span>
                <span>Продукция</span>
            </a>
        </div>
        
        <!-- Контроль качества -->
        <div class="sidebar-nav-section">
            <div class="sidebar-nav-title">Контроль качества</div>
            <a href="quality/checks.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">✅</span>
                <span>Проверки</span>
            </a>
            <a href="quality/reports.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">📈</span>
                <span>Отчеты по качеству</span>
            </a>
        </div>
        
        <!-- Склад -->
        <div class="sidebar-nav-section">
            <div class="sidebar-nav-title">Склад</div>
            <a href="../warehouse/materials.php" class="sidebar-nav-item <?= strpos($_SERVER['PHP_SELF'], 'warehouse/materials') !== false ? 'active' : '' ?>">
                <span class="sidebar-nav-icon">📦</span>
                <span>Материалы</span>
            </a>
            <a href="warehouse/list.php" class="sidebar-nav-item <?= strpos($_SERVER['PHP_SELF'], 'warehouse/list') !== false ? 'active' : '' ?>">
                <span class="sidebar-nav-icon">🏭</span>
                <span>Остатки на складе</span>
            </a>
            <a href="warehouse/transactions.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">📝</span>
                <span>Движение</span>
            </a>
        </div>
        
        <!-- Сотрудники -->
        <div class="sidebar-nav-section">
            <div class="sidebar-nav-title">Сотрудники</div>
            <a href="employees/list.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">👥</span>
                <span>Все сотрудники</span>
            </a>
            <a href="employees/departments.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">🏛️</span>
                <span>Структура</span>
            </a>
        </div>
        
        <!-- Отчеты -->
        <div class="sidebar-nav-section">
            <div class="sidebar-nav-title">Отчеты</div>
            <a href="reports/production.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">📊</span>
                <span>Производство</span>
            </a>
            <a href="reports/sales.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">💰</span>
                <span>Продажи</span>
            </a>
            <a href="reports/analytics.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">📈</span>
                <span>Аналитика</span>
            </a>
        </div>
        
        <!-- Настройки -->
        <?php if ($user['role_code'] === 'admin'): ?>
        <div class="sidebar-nav-section">
            <div class="sidebar-nav-title">Администрирование</div>
            <a href="settings/users.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">👤</span>
                <span>Пользователи</span>
            </a>
            <a href="settings/system.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">⚙️</span>
                <span>Настройки системы</span>
            </a>
            <a href="settings/logs.php" class="sidebar-nav-item">
                <span class="sidebar-nav-icon">📜</span>
                <span>Журнал событий</span>
            </a>
        </div>
        <?php endif; ?>
    </nav>
</div>
