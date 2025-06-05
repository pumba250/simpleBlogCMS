<?php
$start = microtime(1);
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Проверка конфигурации и установки
if (!file_exists('config/config.php')) {
    header('Location: install.php');
    die;
}

if (file_exists('install.php')) {
    echo "<font color=red>Удалите файл install.php и директорию sql</font>";
    die;
}

$config = require 'config/config.php';
try {
    $host = $config['host'];
    $database = $config['database'];
    $db_user = $config['db_user'];
    $db_pass = $config['db_pass'];
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

$dbPrefix = $config['db_prefix'] ?? '';
require 'class/Template.php';
require 'class/User.php';
require 'class/Contact.php';
require 'class/News.php';
require 'class/Comments.php';

// После создания других объектов добавить:
$comments = new Comments($pdo);
$template = new Template();
$user = new User($pdo);
$contact = new Contact($pdo);
$news = new News($pdo);

// Проверка авторизации и прав администратора
if (!isset($_SESSION['user']) || !$_SESSION['user']['isadmin']) {
    header('Location: /');
    exit;
}

$pageTitle = 'Админ-панель';
if (isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    header("Location: /");
    exit();
}
// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Неверный CSRF-токен");
    }
    // Обработка действий
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
			case 'edit_comment':
				$id = (int)$_POST['id'];
				$userText = htmlspecialchars(trim($_POST['user_text']));
				$moderation = isset($_POST['moderation']) ? 1 : 0;
				
				if ($comments->editComment($id, $userText)) {
					// Обновляем статус модерации отдельно
					if ($moderation) {
						$comments->approveComment($id);
					} else {
						$stmt = $pdo->prepare("UPDATE `{$dbPrefix}comments` SET moderation = 0 WHERE id = ?");
						$stmt->execute([$id]);
					}
					$_SESSION['admin_message'] = 'Комментарий успешно обновлен';
				} else {
					$_SESSION['admin_error'] = 'Ошибка при обновлении комментария';
				}
				break;
				
			case 'delete_comment':
				$id = (int)$_POST['id'];
				if ($comments->deleteComment($id)) {
					$_SESSION['admin_message'] = 'Комментарий успешно удален';
				} else {
					$_SESSION['admin_error'] = 'Ошибка при удалении комментария';
				}
				break;
				
			case 'toggle_comment':
				$id = (int)$_POST['id'];
				if ($comments->toggleModeration($id)) {
					$status = $comments->getCommentStatus($id);
					$_SESSION['admin_message'] = 'Статус комментария изменен: ' . ($status ? 'Одобрен' : 'На модерации');
				} else {
					$_SESSION['admin_error'] = 'Ошибка при изменении статуса комментария';
				}
				break;
            case 'edit_blog':
                $id = (int)$_POST['id'];
                $title = htmlspecialchars(trim($_POST['title']));
                $content = htmlspecialchars(trim($_POST['content']));
                $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
                
                if ($news->updateBlog($id, $title, $content, $tags)) {
                    $_SESSION['admin_message'] = 'Запись успешно обновлена';
                } else {
                    $_SESSION['admin_error'] = 'Ошибка при обновлении записи';
                }
                break;
                
            case 'delete_blog':
                $id = (int)$_POST['id'];
                if ($news->deleteBlog($id)) {
                    $_SESSION['admin_message'] = 'Запись успешно удалена';
                } else {
                    $_SESSION['admin_error'] = 'Ошибка при удалении записи';
                }
                break;
                
            case 'add_blog':
                $title = htmlspecialchars(trim($_POST['title']));
                $content = htmlspecialchars(trim($_POST['content']));
                $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
                
                if ($news->addBlog($title, $content, $tags)) {
                    $_SESSION['admin_message'] = 'Запись успешно добавлена';
                } else {
                    $_SESSION['admin_error'] = 'Ошибка при добавлении записи';
                }
                break;
                
            case 'delete_contact':
                $id = (int)$_POST['id'];
                if ($contact->deleteMessage($id)) {
                    $_SESSION['admin_message'] = 'Сообщение успешно удалено';
                } else {
                    $_SESSION['admin_error'] = 'Ошибка при удалении сообщения';
                }
                break;
                
            case 'edit_user':
                $id = (int)$_POST['id'];
                $username = htmlspecialchars(trim($_POST['username']));
                $email = htmlspecialchars(trim($_POST['email']));
                $isadmin = isset($_POST['isadmin']) ? 1 : 0;
                
                if ($user->updateUser($id, $username, $email, $isadmin)) {
                    $_SESSION['admin_message'] = 'Пользователь успешно обновлен';
                } else {
                    $_SESSION['admin_error'] = 'Ошибка при обновлении пользователя';
                }
                break;
                
            case 'delete_user':
                $id = (int)$_POST['id'];
                if ($user->deleteUser($id)) {
                    $_SESSION['admin_message'] = 'Пользователь успешно удален';
                } else {
                    $_SESSION['admin_error'] = 'Ошибка при удалении пользователя';
                }
                break;
                
            case 'add_tag':
                $name = htmlspecialchars(trim($_POST['name']));
                if ($news->addTag($name)) {
                    $_SESSION['admin_message'] = 'Тег успешно добавлен';
                } else {
                    $_SESSION['admin_error'] = 'Ошибка при добавлении тега';
                }
                break;
                
            case 'delete_tag':
                $id = (int)$_POST['id'];
                if ($news->deleteTag($id)) {
                    $_SESSION['admin_message'] = 'Тег успешно удален';
                } else {
                    $_SESSION['admin_error'] = 'Ошибка при удалении тега';
                }
                break;
			case 'change_template':
				$templatesDir = 'templates';
				$configPath = 'config/config.php';
				$availableTemplates = $template->getAvailableTemplates($templatesDir);
				$newTemplate = $_POST['template'] ?? '';
				
				if ($template->changeTemplate($newTemplate, $availableTemplates, $config, $configPath)) {
					$_SESSION['admin_message'] = "Шаблон <b>{$newTemplate}</b> успешно активирован!";
				} else {
					$_SESSION['admin_error'] = "Ошибка при смене шаблона";
				}
				header("Location: ?section=template_settings");
			break;
        }
        
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Определение раздела админки
$section = isset($_GET['section']) ? $_GET['section'] : 'blogs';

try {
    // Подготовка данных для шаблона
    $template->assign('pageTitle', $pageTitle);
    $template->assign('user', $_SESSION['user']);
    $template->assign('section', $section);
    $template->assign('csrf_token', $_SESSION['csrf_token']);
    
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
            $blogs = $news->getAllAdm(0, 0); // Получаем все блоги без пагинации
            $allTags = $pdo->query("SELECT * FROM `{$dbPrefix}tags` ORDER by `name`")->fetchAll(PDO::FETCH_ASSOC);
            
            $template->assign('blogs', $blogs);
            $template->assign('allTags', $allTags);
            break;
            
        case 'comments':
			$allComments = $comments->AllComments();
			$pendingCount = $comments->countPendingComments();
			$template->assign('comments', $allComments);
			$template->assign('pendingCount', $pendingCount);
			break;
		case 'contacts':
            $contacts = $contact->getAllMessages();
            $template->assign('contacts', $contacts);
            break;
            
        case 'users':
            $users = $user->getAllUsers();
            $template->assign('users', $users);
            break;
            
        case 'tags':
            $tags = $pdo->query("SELECT * FROM `{$dbPrefix}tags` ORDER by `name`")->fetchAll(PDO::FETCH_ASSOC);
            $template->assign('tags', $tags);
            break;
		
		case 'template_settings':
			$templatesDir = 'templates';
			$configPath = 'config/config.php';
			$availableTemplates = $template->getAvailableTemplates($templatesDir);
			$currentTemplate = $config['templ'] ?? 'simple';
			
			// Если текущего шаблона нет в доступных, сбрасываем на default
			if (!in_array($currentTemplate, $availableTemplates)) {
				$currentTemplate = 'simple';
				$config['templ'] = 'simple';
				file_put_contents($configPath, "<?php\nreturn ".var_export($config, true).";\n");
			}
			
			$template->assign('templates', $availableTemplates);
			$template->assign('currentTemplate', $currentTemplate);
		break;
    }
    
    echo $template->render('admin/index.tpl');
    
} catch (Exception $e) {
    error_log($e->getMessage());
    header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
    $template->assign('pageTitle', '500 - Внутренняя ошибка сервера');
    echo $template->render('admin/500.tpl');
    exit;
}

$finish = microtime(1);
echo '<!-- generation time: ' . round($finish - $start, 5) . ' сек -->';