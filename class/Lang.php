<?php
if (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен'); }
/**
 * Класс для работы с языковыми файлами
 * 
 * @package    SimpleBlog
 * @subpackage Core
 * @category   Internationalization
 * @version    0.9.0
 * 
 * @method static void init() Инициализирует языковую систему
 * @method static void setLanguage(string $lang) Устанавливает язык
 * @method static string get(string $key, string $section = 'main') Получает перевод
 */
class Lang {
    protected static $translations = [];
    protected static $language = 'ru'; // Язык по умолчанию

    public static function init() {
        // Определяем язык из сессии или браузера
        if (isset($_SESSION['lang'])) {
            self::$language = $_SESSION['lang'];
        } else {
            self::$language = self::detectLanguage();
        }
    }

    public static function setLanguage($lang) {
        if (file_exists(__DIR__ . '/../lang/' . $lang)) {
            $_SESSION['lang'] = $lang;
            self::$language = $lang;
        }
    }

    public static function get(string $key, string $section = 'main') {
        // Проверяем, загружен ли уже этот раздел
        if (!isset(self::$translations[$section])) {
            $file = __DIR__ . '/../lang/' . self::$language . '/' . $section . '.php';
            
            if (!file_exists($file)) {
                error_log("Language file not found: {$file}");
                return $key; // Возвращаем ключ, если файл не найден
            }
            
            self::$translations[$section] = require $file;
            
            if (!is_array(self::$translations[$section])) {
                error_log("Invalid language file format: {$file}");
                self::$translations[$section] = [];
            }
        }
        
        return self::$translations[$section][$key] ?? $key;
    }

    private static function detectLanguage() {
        $supported = ['en', 'ru'];
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);
        return in_array($browserLang, $supported) ? $browserLang : 'en';
    }
}