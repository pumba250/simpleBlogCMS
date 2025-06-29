<?php
/**
 * Multilingual support system - handles language detection and translations
 *
 * @package    SimpleBlog
 * @subpackage Core
 * @category   Internationalization
 * @static
 * 
 * @property-read string $language       Current language code (e.g. 'en')
 * @property-read array  $translations   Loaded translation strings
 * 
 * @method static void   init()              Initialize language system
 * @method static void   setLanguage(string $lang) Change active language
 * @method static string get(string $key, string $section = 'main') Get translation
 * 
 * @file-structure
 *  /lang/
 *    /en/
 *      - main.php
 *      - admin.php
 *    /ru/
 *      - main.php
 *      - admin.php
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