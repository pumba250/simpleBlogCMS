<?php
$start = microtime(1);
session_start();
if (!ob_start("ob_gzhandler")) {
    ob_start();
}
if (!file_exists('config/config.php')) {
	header('Location: install.php');
	die;
}
if (file_exists('install.php')) {
	echo '<p style="color: red; text-align: center;">Удалите файл install.php и директорию sql</p>';
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
$templ = $config['templ'];
$dbPrefix = $config['db_prefix'];
$backupDir = $config['backup_dir'];
$maxBackups = $config['max_backups'];
$version = $config['version'];
// Проверка необходимости создания резервной копии по расписанию
if (isset($config['backup_schedule']) && $config['backup_schedule'] !== 'disabled') {
    require_once __DIR__ . '/admin/backup_db.php';
    checkScheduledBackup($pdo, $config);
}

require 'class/Template.php';
require 'class/User.php';
require 'class/Contact.php';
require 'class/Comments.php';
require 'class/News.php';
$template = new Template();
$user = new User($pdo);
$contact = new Contact($pdo);
$news = new News($pdo);
$comments = new Comments($pdo);
$pageTitle = 'simpleBlog';
try {
    // Обработка регистрации
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($_POST['action'] === 'register') {
            $user->register($_POST['username'], $_POST['password'], $_POST['email']);
            $pageTitle = 'Регистрация';
			$metaDescription = 'Форма регистрации нового пользователя в IT блоге';
			$metaKeywords = 'регистрация, создать аккаунт, IT блог';
        }
        // Обработка авторизации
        if (isset($_POST['action']) && $_POST['action'] === 'login') {
			$captchaValid = isset($_POST['captcha']) && $_POST['captcha'] == $_SESSION['captcha_answer'];
			$username = $_POST['username'] ?? '';
			$password = $_POST['password'] ?? '';
			
			if (!$captchaValid) {
				$_SESSION['auth_error'] = "Неправильный ответ на капчу. Попробуйте еще раз.";
			} elseif (empty($username) || empty($password)) {
				$_SESSION['auth_error'] = "Заполните все поля";
			} else {
				$userData = $user->login($username, $password);
				if ($userData) {
					$_SESSION['user'] = $userData;
					sleep(2);
					header("Location: /");
					exit;
				} else {
					$_SESSION['auth_error'] = "Неверный логин или пароль";
				}
			}
			
			$_SESSION['auth_form_data'] = [
				'username' => $username
			];
			
			header("Location: " . $_SERVER['REQUEST_URI']);
			exit;
		}
        // Обработка обратной связи
        if ($_POST['action'] === 'contact') {
			$pageTitle = 'Форма обратной связи';
			$metaDescription = 'Контактная форма для связи с администрацией IT блога';
			$metaKeywords = 'контакты, обратная связь, IT блог';
            if (isset($_POST['captcha']) && $_POST['captcha'] == $_SESSION['captcha_answer']) {
                if ($contact->saveMessage($_POST['name'], $_POST['email'], $_POST['message'])) {
                    $errors[] = "Сообщение успешно отправлено!";
                } else {
                    $errors[] = "Произошла ошибка при отправке сообщения.";
                }
            } else {
                $errors[] = "Неправильный ответ на капчу. Попробуйте еще раз.";
            }
        }
    }
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_text'])) {
		$themeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		$userName = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : $_POST['user_name'];
		$userText = $_POST['user_text'];
		
		if ($themeId > 0 && !empty($userName) && !empty($userText)) {
			$comments->addComment(0, 0, $themeId, $userName, $userText);
			header("Location: ?id=" . $themeId);
			exit;
		}
	}
    // Обработка GET-запросов
    if (!isset($_GET['action'])) {
        if (isset($_GET['id'])) {
    // Получаем одну новость по id
    $newsId = (int)$_GET['id'];
    $newsItem = $news->getNewsById($newsId); // Получаем одну новость
	$pageTitle = htmlspecialchars($newsItem['title']) . ' | IT Блог';
    $metaDescription = $news->generateMetaDescription($newsItem['content']);
    $metaKeywords = $news->generateMetaKeywords($newsItem['title'], $newsItem['content']);
    $lastThreeNews = $news->getLastThreeNews();
	// Установите количество комментариев на страницу
	$commentsPerPage = 10;
	$currentCommentPage = isset($_GET['comment_page']) ? max(1, (int)$_GET['comment_page']) : 1;
	$commentsOffset = ($currentCommentPage - 1) * $commentsPerPage;

	// Получаем комментарии с пагинацией
	$commentsList = $comments->getComments($newsId, $commentsPerPage, $commentsOffset);
	$totalComments = $comments->countComments($newsId);
	$totalCommentPages = ceil($totalComments / $commentsPerPage);

	// Передаем данные в шаблон
	$template->assign('commentsList', $commentsList);
	$template->assign('totalCommentPages', $totalCommentPages);
	$template->assign('currentCommentPage', $currentCommentPage);
	// Передача данных тегов и заголовка в шаблон 
	$template->assign('dbPrefix', $dbPrefix);
	$template->assign('powered', $config['powered']);
	$template->assign('version', $config['version']);
	$template->assign('captcha_image_url', '/class/captcha.php'); // путь к скрипту капчи
    $template->assign('allTags', $news->GetAllTags());
	$template->assign('metaDescription', $metaDescription);
	$template->assign('metaKeywords', $metaKeywords);
    $template->assign('lastThreeNews', $lastThreeNews);
    $template->assign('user', $_SESSION['user'] ?? null);
	$template->assign('news', $news);
	//$template->assign('comments', $comments);
	$template->assign('templ', $templ);
    $template->assign('pageTitle', $pageTitle); 
} elseif (isset($_GET['tags'])) {
        // Обработка тегов
        $tag = htmlspecialchars($_GET['tags']);
		$pageTitle = "Новости по тегу: " . $tag;
		$metaDescription = "Все публикации по теме: " . $tag;
		$metaKeywords = $tag . ', IT, блог, сети';
        $newsByTags = $news->getNewsByTag($tag);
        $lastThreeNews = $news->getLastThreeNews();
        // Передача данных в шаблон
		$template->assign('captcha_image_url', '/class/captcha.php'); // путь к скрипту капчи
		$template->assign('powered', $config['powered']);
		$template->assign('version', $config['version']);
        $template->assign('allTags', $news->GetAllTags());
		$template->assign('metaDescription', $metaDescription);
		$template->assign('metaKeywords', $metaKeywords);
        $template->assign('lastThreeNews', $lastThreeNews);
        $template->assign('user', $_SESSION['user'] ?? null);
        $template->assign('news', $newsByTags);
	$template->assign('comments', $comments);
	$template->assign('templ', $templ);
        $template->assign('pageTitle', $pageTitle);
    } else {
    // Загрузка главной страницы
    $pageTitle = 'Главная страница'; // Заголовок для главной страницы
	$metaDescription = 'IT блог о настройке сетевого оборудования, MikroTik, RouterBoard и сетевой безопасности';
	$metaKeywords = 'IT, блог, сети, mikrotik, routerboard, безопасность';
    $limit = 6; // Количество новостей на странице
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    $allNews = $news->getAllNews($limit, $offset);
    $totalNewsCount = $news->getTotalNewsCount();
    $totalPages = ceil($totalNewsCount / $limit);
    $lastThreeNews = $news->getLastThreeNews();
    // Передача данных тегов и заголовка в шаблон
	$template->assign('captcha_image_url', '/class/captcha.php'); // путь к скрипту капчи
	$template->assign('powered', $config['powered']);
	$template->assign('version', $config['version']);
    $template->assign('allTags', $news->GetAllTags());
	$template->assign('metaDescription', $metaDescription);
	$template->assign('metaKeywords', $metaKeywords);
    $template->assign('lastThreeNews', $lastThreeNews);
    $template->assign('user', $_SESSION['user'] ?? null);
    $template->assign('news', $allNews);
	$template->assign('comments', $comments);
	$template->assign('templ', $templ);
    $template->assign('totalPages', $totalPages);
    $template->assign('currentPage', $page);
    $template->assign('pageTitle', $pageTitle);
}
    echo $template->render('home.tpl');
    } else {
        switch ($_GET['action']) {
			case 'login':
            // Загрузка страницы регистрации
                $template->assign('lastThreeNews', $news->getLastThreeNews());
				$template->assign('powered', $config['powered']);
				$template->assign('version', $config['version']);
				$template->assign('templ', $templ);
				$template->assign('captcha_image_url', '/class/captcha.php'); // путь к скрипту капчи
				$template->assign('metaDescription', $metaDescription);
				$template->assign('metaKeywords', $metaKeywords);
                $template->assign('allTags', $news->GetAllTags());
				$template->assign('user', $_SESSION['user'] ?? null);
                $template->assign('pageTitle', 'Авторизация simpleBlog');
				    echo $template->render('login.tpl');
                break;
            case 'register':
                // Загрузка страницы регистрации
                $template->assign('lastThreeNews', $news->getLastThreeNews());
				$template->assign('powered', $config['powered']);
				$template->assign('version', $config['version']);
				$template->assign('templ', $templ);
				$template->assign('captcha_image_url', '/class/captcha.php'); // путь к скрипту капчи
				$template->assign('metaDescription', 'Регистрация simpleBlog');
				$template->assign('metaKeywords', '');
                $template->assign('allTags', $news->GetAllTags());
				$template->assign('user', $_SESSION['user'] ?? null);
                $template->assign('pageTitle', 'Регистрация simpleBlog');
				    echo $template->render('register.tpl');
                break;
            case 'contact':
                // Загрузка формы обратной связи
                $template->assign('lastThreeNews', $news->getLastThreeNews());
				$template->assign('powered', $config['powered']);
				$template->assign('version', $config['version']);
				$template->assign('templ', $templ);
				$template->assign('metaDescription', 'Форма обратной связи simpleBlog');
				$template->assign('metaKeywords', 'контакты, simpleBlog');
				$template->assign('captcha_image_url', '/class/captcha.php'); // путь к скрипту капчи
                $template->assign('allTags', $news->GetAllTags());
				$template->assign('user', $_SESSION['user'] ?? null);
                $template->assign('pageTitle', 'Форма обратной связи simpleBlog');
				    echo $template->render('contact.tpl');
                break;
            default:
                // Обработка 404 ошибки
                header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
				$template->assign('powered', $config['powered']);
				$template->assign('version', $config['version']);
				$template->assign('templ', $templ);
				$template->assign('metaDescription', 'Страница не найдена');
				$template->assign('metaKeywords', '404, страница не найдена');
                $template->assign('pageTitle', '404 - Страница не найдена simpleBlog');
				$template->assign('lastThreeNews', $news->getLastThreeNews());
				$template->assign('allTags', $news->GetAllTags());
                echo $template->render('404.tpl');
                exit;
        }
    }
} catch (Exception $e) {
    // Обработка ошибки 500
    error_log($e->getMessage()); // Логирование ошибки
    header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
	$template->assign('powered', $config['powered']);
	$template->assign('version', $config['version']);
	$template->assign('templ', $templ);
	$template->assign('metaDescription', 'Внутренняя ошибка сервера');
	$template->assign('metaKeywords', '500, ошибка сервера');
    $template->assign('pageTitle', '500 - Внутренняя ошибка сервера simpleBlog');
    echo $template->render('500.tpl');
    exit;
}
$finish = microtime(1);
//echo 'generation time: ' . round($finish - $start, 5) . ' сек';
