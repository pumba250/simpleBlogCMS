<?php
session_start();
ob_start(); // Включаем буферизацию вывода
/*error_reporting(E_ALL);
ini_set('display_errors', 1);*/

$config = require '../config/config.php';
try {
    $host = $config['host'];
    $database = $config['database'];
    $db_user = $config['db_user'];
    $db_pass = $config['db_pass'];
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
$dbPrefix = $config['db_prefix'] ?? '';
require '../class/User.php';
$user = new User($pdo);

// Получение пользователя
$userData = [];
if (!empty($_SESSION['username'])) {
    $stmt = $pdo->prepare('SELECT * FROM '.$dbPrefix.'users WHERE username = :username');
    $stmt->execute(['username' => $_SESSION['username']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Обработка авторизации
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $userData = $user->login($_POST['username'], $_POST['password']);
    if ($userData) {
        $_SESSION['user'] = $userData;
    }
}

// Статистика
$totalUsers = $pdo->query("SELECT COUNT(*) FROM {$dbPrefix}users")->fetchColumn();
$totalNews = $pdo->query("SELECT COUNT(*) FROM {$dbPrefix}blogs")->fetchColumn();
$totalFeedback = $pdo->query("SELECT COUNT(*) FROM {$dbPrefix}blogs_contacts")->fetchColumn();
$totalComm = $pdo->query("SELECT COUNT(*) FROM {$dbPrefix}comments")->fetchColumn();
$totalCommod = $pdo->query("SELECT COUNT(*) FROM `{$dbPrefix}comments` where moderation = 0")->fetchColumn();

// Проверка прав администратора
if (isset($userData['isadmin']) && $userData['isadmin'] == 9) {
    $view = $_GET['view'] ?? 'dashboard';
    $templateVariables = [
        'pageTitle' => '',
        'totalUsers' => $totalUsers,
        'totalNews' => $totalNews,
        'totalFeedback' => $totalFeedback,
        'totalComm' => $totalComm,
        'totalCommod' => $totalCommod,
        'message' => $_SESSION['message'] ?? ''
    ];

    // Обработка смены шаблона
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_template') {
        $templatesDir = '../templates';
        $configPath = '../config/config.php';
        $availableTemplates = array_filter(scandir($templatesDir), function($item) use ($templatesDir) {
            return $item != '.' && $item != '..' && is_dir($templatesDir.'/'.$item);
        });

        $newTemplate = $_POST['template'] ?? '';
        if (in_array($newTemplate, $availableTemplates)) {
            $config['templ'] = $newTemplate;
            $configContent = "<?php\nreturn " . var_export($config, true) . ";\n";
            
            if (file_put_contents($configPath, $configContent, LOCK_EX)) {
                $_SESSION['message'] = "Шаблон '{$newTemplate}' успешно активирован!";
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($configPath);
                }
            } else {
                $_SESSION['message'] = "Ошибка записи в config.php";
            }
        } else {
            $_SESSION['message'] = "Неверный шаблон";
        }
        header("Location: ?view=template_settings");
        exit;
    }

    // Выбор шаблона админки
    switch ($view) {
        case 'manage_users':
			$templateVariables['pageTitle'] = 'Управление пользователями';
			
			// Получаем список пользователей
			$stmt = $pdo->query("SELECT * FROM {$dbPrefix}users");
			if ($stmt === false) {
				die("Ошибка выполнения запроса: " . print_r($pdo->errorInfo(), true));
			}
			
			$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$templateVariables['users'] = $users; // Убедитесь, что эта строка есть
			
			$template = 'manage_users.tpl';
			break;
        case 'manage_feedback':
            $template = 'manage_feedback.tpl';
            $templateVariables['pageTitle'] = 'Обратная связь';
            break;
        case 'manage_comment':
            $template = 'manage_comment.tpl';
            $templateVariables['pageTitle'] = 'Управление Коментариями';
            break;
        case 'add_news':
            $template = 'add_news.tpl';
            $templateVariables['pageTitle'] = 'Добавить пост';
            break;
        case 'template_settings':
			$template = 'template_settings.tpl';
			$pageTitle = 'Управление шаблонами';
			
			// Абсолютные пути
			$templatesDir = '../templates';
			$configPath = '../config/config.php';
			
			// Получаем список реально существующих шаблонов
			$availableTemplates = [];
			if (is_dir($templatesDir)) {
				$items = scandir($templatesDir);
				foreach ($items as $item) {
					$fullPath = $templatesDir.'/'.$item;
					if ($item != '.' && $item != '..' && is_dir($fullPath)) {
						// Проверяем обязательные файлы шаблона
						if (file_exists($fullPath.'/footer.tpl') && 
							file_exists($fullPath.'/header.tpl')) {
							$availableTemplates[] = $item;
						}
					}
				}
			}
			
			// Текущий шаблон из конфига
			$currentTemplate = $config['templ'] ?? 'simple';
			
			// Если текущего шаблона нет в доступных, сбрасываем на default
			if (!in_array($currentTemplate, $availableTemplates)) {
				$currentTemplate = 'simple';
				$config['templ'] = 'simple';
				file_put_contents($configPath, "<?php\nreturn ".var_export($config, true).";\n");
			}
			
			// Обработка смены шаблона
			if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
				isset($_POST['action']) && 
				$_POST['action'] === 'change_template' &&
				isset($_POST['template'])) {
				
				$newTemplate = $_POST['template'];
				
				// Дополнительная проверка
				if (in_array($newTemplate, $availableTemplates)) {
					$config['templ'] = $newTemplate;
					$configContent = "<?php\nreturn ".var_export($config, true).";\n";
					
					if (file_put_contents($configPath, $configContent)) {
						$_SESSION['message'] = "Шаблон '{$newTemplate}' успешно активирован!";
						
						// Очистка кэша
						if (function_exists('opcache_invalidate')) {
							opcache_invalidate($configPath);
						}
					} else {
						$_SESSION['message'] = "Ошибка записи в config.php";
					}
				} else {
					$_SESSION['message'] = "Шаблон '{$newTemplate}' не существует или неполный!";
				}
				
				header("Location: ?view=template_settings");
				exit;
			}
			
			// Подготовка данных для шаблона
			$templateVariables = [
				'pageTitle' => $pageTitle,
				'templates' => $availableTemplates,
				'currentTemplate' => $currentTemplate,
				'message' => $_SESSION['message'] ?? ''
			];
			unset($_SESSION['message']);
			break;
        default:
            $template = 'dashboard.tpl';
            $templateVariables['pageTitle'] = 'Статистика';
            break;
    }

    echo renderTemplate($template, $templateVariables);
    unset($_SESSION['message']);
} else {
    // Форма входа
    $template = 'login_form.tpl';
    $templateVariables = [
        'pageTitle' => 'Вход в административную панель',
        'message' => $_SESSION['message'] ?? ''
    ];
    echo renderTemplate($template, $templateVariables);
    unset($_SESSION['message']);
}

/*function renderTemplate($template, $variables = []) {
    extract($variables);
    ob_start();
    include "templates/$template";
    return ob_get_clean();
}*/
function renderTemplate($template, $variables = []) {
    extract($variables);
    ob_start();
    // Подключаем header
    require_once "templates/header.tpl";
    
    // Подключаем основной контент
    $templatePath = "templates/{$template}";
    if (file_exists($templatePath)) {
        require_once $templatePath;
    } else {
        die("Шаблон {$template} не найден");
    }
    
    // Подключаем footer
    require_once "templates/footer.tpl";
	return ob_get_clean();
}
