<?php
/**
 * Административная панель управления
 * 
 * @package    SimpleBlog
 * @subpackage Admin
 * @version    1.0.0
 * 
 * @sections
 * - Управление пользователями
 * - Модерация контента
 * - Системные настройки
 * - Резервное копирование
 * - Журнал событий
 * 
 * @permissions
 * - Администратор (уровень 9)
 * - Модератор (уровень 7)
 * - Обычный пользователь (уровень 0)
 */
define('IN_SIMPLECMS', true);
$start = microtime(1);
session_start([
    'cookie_secure' => ($config['force_https'] ?? false) ? true : false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
    'cookie_lifetime' => $config['session_lifetime'] ?? 7200,
    'gc_maxlifetime' => $config['session_lifetime'] ?? 7200
]);

if (isset($config['session_lifetime'])) {
    ini_set('session.gc_maxlifetime', $config['session_lifetime']);
    ini_set('session.cookie_lifetime', $config['session_lifetime']);
}

// Проверка конфигурации и установки
if (!file_exists('config/config.php')) {
    header('Location: install.php');
    die;
}
if (file_exists('install.php')) {
    echo "<font color=red>".Lang::get('delete_install', 'core')."</font>";
    die;
}

$config = require 'config/config.php';

require 'class/ErrorHandler.php';
ErrorHandler::init($config['debug'] ?? false);
require 'class/Lang.php';
Lang::init();

// Подключение к базе данных
$host = $config['host'];
$database = $config['database'];
$db_user = $config['db_user'];
$db_pass = $config['db_pass'];
$pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $db_user, $db_pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$dbPrefix = $config['db_prefix'] ?? '';
$backupDir = $config['backup_dir'];
$maxBackups = $config['max_backups'];
$version = $config['version'];

// Подключаем основные классы
require 'class/Cache.php';
Cache::init($config);
require 'class/Template.php';
require 'class/User.php';
require 'class/Contact.php';
require 'class/News.php';
require 'class/Comments.php';
require 'class/Parse.php';
require 'class/Vote.php';
require_once 'admin/backup_db.php';
require 'class/Updater.php';

// Инициализация объектов
$template = new Template();
$user = new User($pdo, $template);
$contact = new Contact($pdo);
$news = new News($pdo);
$comments = new Comments($pdo);
$parse = new Parse();
$votes = new Votes($pdo);
$updater = new Updater($pdo, $config);

// Инициализация Core с передачей всех зависимостей
require 'class/Core.php';
$core = new Core($pdo, $config, $template);

// Обработка POST-запросов через Core
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $core->handlePostRequest();
    
    // После обработки POST редиректим для предотвращения повторной отправки
    if (!headers_sent()) {
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Функция для логирования действий
function logAction($action, $details = null) {
    global $pdo, $dbPrefix;
    if (!$pdo) {
        error_log("Не удалось записать лог: соединение с БД не установлено");
        return false;
    }
    $user_id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
    $username = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : 'Система';
    
    $stmt = $pdo->prepare("INSERT INTO {$dbPrefix}admin_logs (user_id, username, action, details, ip_address) 
                          VALUES (:user_id, :username, :action, :details, :ip)");
    return $stmt->execute([
        ':user_id' => $user_id,
        ':username' => $username,
        ':action' => $action,
        ':details' => $details,
        ':ip' => $_SERVER['REMOTE_ADDR']
    ]);
}

// Выход из системы
if (isset($_GET['logout'])) {
    if (isset($pdo)) {
        logAction('Выход из системы', 'Пользователь вышел из админ-панели');
    }
    $_SESSION = array();
    session_destroy();
    header("Location: /");
    exit();
}

// Проверка авторизации и прав администратора
if (!isset($_SESSION['user']) || !$_SESSION['user']['isadmin']) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $details = "Попытка доступа к админ-панели";
    
    if (isset($pdo)) {
        $stmt = $pdo->prepare("INSERT INTO {$dbPrefix}admin_logs 
                             (action, details, ip_address) 
                             VALUES (:action, :details, :ip)");
        $stmt->execute([
            ':action' => 'Неавторизованный доступ',
            ':details' => $details,
            ':ip' => $ip
        ]);
    }
    header('Location: /');
    exit;
}

$currentUserRole = $_SESSION['user']['isadmin'] ?? 0;

// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Определение раздела админки
$section = isset($_GET['section']) ? $_GET['section'] : 'server_info';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$updateInfo = $updater->checkForUpdates();

$pageTitle = Lang::get('admin_page', 'admin');

function getRoleName($roleValue) {
    switch((int)$roleValue) {
        case 9: return Lang::get('admin', 'admin');
        case 7: return Lang::get('moder', 'admin');
        case 0: return Lang::get('ruser', 'admin');
        default: return Lang::get('unknownrole', 'admin');
    }
}

// Подготовка данных для шаблона
$template->assign('pageTitle', $pageTitle);
$template->assign('user', $_SESSION['user']);
$template->assign('section', $section);
$template->assign('csrf_token', $_SESSION['csrf_token']);
$template->assign('updateInfo', $updateInfo);

// Сообщения об ошибках/успехе
if (isset($_SESSION['admin_message'])) {
    $template->assign('admin_message', $_SESSION['admin_message']);
    unset($_SESSION['admin_message']);
}
if (isset($_SESSION['admin_error'])) {
    $template->assign('admin_error', $_SESSION['admin_error']);
    unset($_SESSION['admin_error']);
}

// Загрузка данных в зависимости от раздела
switch ($section) {
    case 'blogs':
        if (!$user->hasPermission(7, $currentUserRole)) {
            throw new Exception(Lang::get('not_perm', 'core'));
        }
        
        if ($action === 'edit' && $id > 0) {
            // Загрузка данных редактируемого блога
            $editBlog = $news->getNewsById($id);
            if (!$editBlog) {
                $_SESSION['admin_error'] = Lang::get('not_blog', 'core');
                header("Location: ?section=blogs");
                exit;
            }
            // Загрузка тегов для этой записи
            $editBlog['tag_ids'] = $news->getTagsByNewsId($id);
            $template->assign('editBlog', $editBlog);
        }
        
        $perPage = 15;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $perPage;
        $newsCount = $news->getTotalNewsCount();
        $totalPages = ceil($newsCount / $perPage);
        $blogs = $news->getAllAdm($perPage, $offset);
        $allTags = $pdo->query("SELECT * FROM `{$dbPrefix}tags` ORDER by `name`")->fetchAll(PDO::FETCH_ASSOC);
        
        $template->assign('newsCount', $newsCount);
        $template->assign('totalPages', $totalPages);
        $template->assign('currentPage', $page);
        $template->assign('blogs', $blogs);
        $template->assign('allTags', $allTags);
        break;
    
    case 'system_settings':
        if (!$user->hasPermission(9, $currentUserRole)) {
            throw new Exception(Lang::get('not_perm', 'core'));
        }
        
        $template->assign('currentSettings', $config);
        break;

    case 'updates':
        if (!$user->hasPermission(9, $currentUserRole)) {
            throw new Exception(Lang::get('not_perm', 'core'));
        }
        $template->assign('currentVersion', $config['version']);
        break;
        
    case 'comments':
        if (!$user->hasPermission(7, $currentUserRole)) {
            throw new Exception(Lang::get('not_perm', 'core'));
        }
        
        $perPage = 15;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $perPage;
        
        $allComments = $comments->AllComments($perPage, $offset);
        $pendingCount = $comments->countPendingComments();
        $totalComments = $comments->countAllComments();
        $totalPages = ceil($totalComments / $perPage);
        
        $template->assign('comments', $allComments);
        $template->assign('totalPages', $totalPages);
        $template->assign('currentPage', $page);
        $template->assign('pendingCount', $pendingCount);
        break;
        
    case 'contacts':
        if (!$user->hasPermission(7, $currentUserRole)) {
            throw new Exception(Lang::get('not_perm', 'core'));
        }
        
        $contacts = $contact->getAllMessages();
        $template->assign('contacts', $contacts);
        break;
        
    case 'users':
        if (!$user->hasPermission(9, $currentUserRole)) {
            throw new Exception(Lang::get('not_perm', 'core'));
        }
        
        $users = $user->getAllUsers();
        $template->assign('users', $users);
        break;
        
    case 'tags':
        if (!$user->hasPermission(7, $currentUserRole)) {
            throw new Exception(Lang::get('not_perm', 'core'));
        }
        
        $tags = $pdo->query("SELECT * FROM `{$dbPrefix}tags` ORDER by `name`")->fetchAll(PDO::FETCH_ASSOC);
        $template->assign('tags', $tags);
        break;
        
    case 'template_settings':
        if (!$user->hasPermission(9, $currentUserRole)) {
            throw new Exception(Lang::get('not_perm', 'core'));
        }
        
        $templatesDir = 'templates';
        $availableTemplates = $template->getAvailableTemplates($templatesDir);
        $currentTemplate = $config['templ'] ?? 'simple';
        
        if (!in_array($currentTemplate, $availableTemplates)) {
            $currentTemplate = 'simple';
            $config['templ'] = 'simple';
            file_put_contents('config/config.php', "<?php\nif (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен');}\nreturn ".var_export($config, true).";\n");
        }
        
        $template->assign('templates', $availableTemplates);
        $template->assign('currentTemplate', $currentTemplate);
        break;
        
    case 'logs':
        if (!$user->hasPermission(9, $currentUserRole)) {
            throw new Exception(Lang::get('not_perm', 'core'));
        }
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $stmt = $pdo->prepare("SELECT * FROM `{$dbPrefix}admin_logs` ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalLogs = $pdo->query("SELECT COUNT(*) FROM `{$dbPrefix}admin_logs`")->fetchColumn();
        
        $template->assign('logs', $logs);
        $template->assign('totalLogs', $totalLogs);
        $template->assign('currentPage', $page);
        $template->assign('perPage', $limit);
        $template->assign('parse', $parse);
        break;
        
    case 'server_info':
        if (!$user->hasPermission(9, $currentUserRole)) {
            throw new Exception(Lang::get('not_perm', 'core'));
        }
        
        // Информация о сервере
        $serverInfo = [
            'Web-сервер' => $_SERVER['SERVER_SOFTWARE'] ?? 'Неизвестно',
            'PHP версия' => phpversion(),
            'MySQL версия' => $pdo->query("SELECT version()")->fetchColumn(),
            'ОС сервера' => PHP_OS,
            'Лимит памяти PHP' => ini_get('memory_limit'),
            'Максимальное время выполнения' => ini_get('max_execution_time') . ' сек',
        ];
        
        // Проверка прав доступа к файлам
        $filePermissions = [
            'config/config.php' => file_exists('config/config.php') ? substr(sprintf('%o', fileperms('config/config.php')), -4) : 'Не найден',
            'install.php' => file_exists('install.php') ? substr(sprintf('%o', fileperms('install.php')), -4) : 'Не найден',
            'admin.php' => substr(sprintf('%o', fileperms('admin.php')), -4),
        ];
        
        // Проверка важных PHP модулей
        $phpModules = [
            'PDO' => extension_loaded('pdo') ? '✓' : '✗',
            'Bzip2' => extension_loaded('bz2') ? '✓' : '✗',
            'Gzip' => extension_loaded('gzip') ? '✓' : '✗',
            'GD' => extension_loaded('gd') ? '✓' : '✗',
            'Zlib' => extension_loaded('zlib') ? '✓' : '✗',
            'MBString' => extension_loaded('mbstring') ? '✓' : '✗',
        ];
        
        $template->assign('serverInfo', $serverInfo);
        $template->assign('filePermissions', $filePermissions);
        $template->assign('phpModules', $phpModules);
        break;

    case 'backups':
        if (!$user->hasPermission(9, $currentUserRole)) {
            throw new Exception(Lang::get('not_perm', 'core'));
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'download_backup') {
            if (empty($_GET['file'])) {
                throw new Exception("Filename not specified");
            }

            $filename = basename($_GET['file']);
            $backupDir = rtrim($config['backup_dir'], '/') . '/';
            $filepath = realpath(__DIR__ . '/' . $backupDir . $filename);
            
            if (!$filepath) {
                throw new Exception("File not found");
            }

            if (!is_readable($filepath)) {
                throw new Exception("File not readable");
            }

            $allowedPath = realpath(__DIR__ . '/' . $backupDir);
            if (strpos($filepath, $allowedPath) !== 0) {
                throw new Exception("Invalid file location");
            }

            if (ob_get_level()) ob_end_clean();

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            
            $chunkSize = 1024 * 1024;
            $handle = fopen($filepath, 'rb');
            if (!$handle) throw new Exception("Cannot open file");
            
            while (!feof($handle)) {
                echo fread($handle, $chunkSize);
                flush();
            }
            
            fclose($handle);
            exit;
        }
        
        $backups = [];
        if (file_exists($backupDir)) {
            $backups = glob($backupDir . 'backup_*.sql');
            usort($backups, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
        }
        
        $template->assign('backups', $backups);
        $template->assign('max_backups', $config['max_backups'] ?? 5);
        $template->assign('backup_schedule', $config['backup_schedule'] ?? 'disabled');
        break;
}

// Отображение шаблона
echo $template->render('admin/index.tpl');

$finish = microtime(1);
echo 'generation time: ' . round($finish - $start, 5) . ' сек';
