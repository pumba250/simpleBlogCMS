<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Автозагрузка классов
spl_autoload_register(function ($class) {
    $classFile = __DIR__ . '/../class/' . $class . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Тестовые константы
define('TEST_TEMP_DIR', __DIR__ . '/temp/');
define('TEST_CACHE_DIR', TEST_TEMP_DIR . 'cache/');

// Создание тестовых директорий
if (!file_exists(TEST_TEMP_DIR)) {
    mkdir(TEST_TEMP_DIR, 0777, true);
}
if (!file_exists(TEST_CACHE_DIR)) {
    mkdir(TEST_CACHE_DIR, 0777, true);
}

// Функция для очистки тестовых директорий
function cleanTestDirs() {
    $dirs = [TEST_CACHE_DIR, TEST_TEMP_DIR];
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}

// Очистка перед запуском
cleanTestDirs();