<?php
if (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен'); }
/**
 * Класс для работы с языковыми файлами
 * 
 * @package    SimpleBlog
 * @subpackage Core
 * @category   Internationalization
 * @version    0.8.1
 * 
 * @method static void init() Инициализирует языковую систему
 * @method static void setLanguage(string $lang) Устанавливает язык
 * @method static string get(string $key, string $section = 'main') Получает перевод
 */
class Lang {
    private static $language = 'en';
    private static $translations = [];

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

    public static function get($key, $section = 'main') {
        if (empty(self::$translations[$section])) {
            $file = __DIR__ . '/../lang/' . self::$language . '/' . $section . '.php';
            if (file_exists($file)) {
                self::$translations[$section] = require $file;
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