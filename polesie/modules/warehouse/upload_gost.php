<?php
/**
 * Обработчик загрузки ГОСТов
 * ОАО "Полесьеэлектромаш"
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
session_start();

header('Content-Type: application/json');

// Проверка авторизации
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

$user = getCurrentUser();

// Проверка прав доступа (только администраторы или инженеры)
if ($user['role_code'] !== 'admin' && $user['role_code'] !== 'engineer') {
    echo json_encode(['success' => false, 'message' => 'Недостаточно прав для загрузки файлов']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

// Проверка наличия файла
if (!isset($_FILES['gost_file']) || $_FILES['gost_file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Файл слишком большой (превышен лимит php.ini)',
        UPLOAD_ERR_FORM_SIZE => 'Файл слишком большой (превышен лимит формы)',
        UPLOAD_ERR_PARTIAL => 'Файл загружен частично',
        UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
        UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная директория',
        UPLOAD_ERR_CANT_WRITE => 'Ошибка записи файла на диск',
        UPLOAD_ERR_EXTENSION => 'Загрузка прервана расширением PHP'
    ];
    $errorCode = $_FILES['gost_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    echo json_encode([
        'success' => false, 
        'message' => $errorMessages[$errorCode] ?? 'Ошибка загрузки файла'
    ]);
    exit;
}

$file = $_FILES['gost_file'];
$allowedTypes = ['application/pdf', 'application/x-pdf'];
$maxSize = 50 * 1024 * 1024; // 50 MB

// Проверка типа файла
$finfo = new finfo(FILEINFO_MIME_TYPE);
$fileType = $finfo->file($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Разрешены только PDF файлы']);
    exit;
}

// Проверка размера
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Файл слишком большой (максимум 50 MB)']);
    exit;
}

// Получение данных из формы
$gostNumber = trim($_POST['gost_number'] ?? '');
$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$status = trim($_POST['status'] ?? 'Действующий');

// Валидация данных
if (empty($gostNumber)) {
    echo json_encode(['success' => false, 'message' => 'Укажите номер ГОСТа']);
    exit;
}

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Укажите название стандарта']);
    exit;
}

if (empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Укажите категорию']);
    exit;
}

// Извлечение номера ГОСТа для имени файла
$gostNumberClean = preg_replace('/ГОСТ\s*([0-9.]+(?:-[0-9]+)?).*/i', '$1', $gostNumber);
$fileName = 'gost_' . str_replace('.', '-', $gostNumberClean) . '.pdf';
$uploadPath = ASSETS_PATH . '/gosts/' . $fileName;

// Сохранение файла
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Ошибка сохранения файла']);
    exit;
}

// Загрузка существующих данных
$docsPath = BASE_PATH . '/../list_materials_docs.json';
$docsData = [];

if (file_exists($docsPath)) {
    $jsonData = file_get_contents($docsPath);
    $docsData = json_decode($jsonData, true);
}

// Инициализация структуры если не существует
if (!isset($docsData['gost_standards'])) {
    $docsData['gost_standards'] = [];
}

// Добавление нового ГОСТа
$newGost = [
    'gost_number' => $gostNumber,
    'title' => $title,
    'category' => $category,
    'status' => $status,
    'file_name' => $fileName,
    'uploaded_at' => date('Y-m-d H:i:s'),
    'uploaded_by' => $user['id']
];

// Проверяем, есть ли уже такой ГОСТ и обновляем его
$found = false;
foreach ($docsData['gost_standards'] as &$existingGost) {
    if ($existingGost['gost_number'] === $gostNumber) {
        $existingGost = $newGost;
        $found = true;
        break;
    }
}

if (!$found) {
    $docsData['gost_standards'][] = $newGost;
}

// Сохранение обновленных данных
if (file_put_contents($docsPath, json_encode($docsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo json_encode([
        'success' => true, 
        'message' => 'ГОСТ успешно загружен',
        'file_name' => $fileName,
        'gost_number' => $gostNumber
    ]);
} else {
    // Удаляем файл если не удалось сохранить JSON
    unlink($uploadPath);
    echo json_encode(['success' => false, 'message' => 'Ошибка сохранения данных']);
}
