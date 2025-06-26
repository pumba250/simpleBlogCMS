<?php
$start = microtime(1);
session_start();
require 'class/Lang.php';
Lang::init();

if (isset($_GET['lang'])) {
	/*
	$allowed_langs = ['ru', 'en', 'es', 'de'];
	$lang = in_array($_GET['lang'], $allowed_langs) ? $_GET['lang'] : 'ru';
	*/
	$lang = in_array($_GET['lang'], ['ru', 'en']) ? $_GET['lang'] : 'ru';
    Lang::setLanguage($lang);
	$_SESSION['lang'] = $lang;
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
if (!empty($_SESSION['lang'])) {
    Lang::setLanguage($_SESSION['lang']);
}
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
require 'class/Vote.php';
require 'class/Parse.php';
$template = new Template();
$user = new User($pdo);
$contact = new Contact($pdo);
$news = new News($pdo);
$comments = new Comments($pdo);
$votes = new Votes($pdo);
$parse = new parse();
$pageTitle = 'simpleBlog';

try {
    // Обработка регистрации
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			if (isset($_POST['user_text'])) {
				$themeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
				$userName = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : $_POST['user_name'];
				$userText = $_POST['user_text'];
				
				if ($themeId > 0 && !empty($userName) && !empty($userText)) {
					$comments->addComment(0, 0, $themeId, $userName, $userText);
					header("Location: ?id=" . $themeId);
					exit;
				}
			}
		if (isset($_POST['vote_article']) || isset($_POST['vote_comment'])) {
			$newsId = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
			
			if ($newsId === 0) {
				die("Ошибка: не указан ID новости");
			}
			
			// Проверяем существование новости
			$newsItem = $news->getNewsById($newsId);
			if (!$newsItem) {
				die("Новость не найдена");
			}
			
			// Обработка голосования за статью
			if (isset($_POST['vote_article']) && isset($_SESSION['user']['id'])) {
				$voteType = $_POST['vote_article'];
				$votes->voteArticle($newsId, $voteType, $_SESSION['user']['id']);
				header("Location: ?id=$newsId");
				exit;
			}
			
			// Обработка голосования за комментарий
			if (isset($_POST['vote_comment']) && isset($_SESSION['user']['id'])) {
				list($commentId, $voteType) = explode('_', $_POST['vote_comment']);
				$votes->voteComment($commentId, $voteType, $_SESSION['user']['id']);
				header("Location: ?id=$newsId");
				exit;
			}
		}
        if ($_POST['action'] === 'register') {
            $user->register($_POST['username'], $_POST['password'], $_POST['email']);
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
					sleep(1);
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
function getCommonTemplateVars($config, $news, $user = null) {
    return [
        'powered' => $config['powered'],
        'version' => $config['version'],
		'dbPrefix' => $dbPrefix,
        'templ' => $config['templ'],
        'captcha_image_url' => '/class/captcha.php',
        'allTags' => $news->GetAllTags(),
        'lastThreeNews' => $news->getLastThreeNews(),
        'user' => $user ?? null,
        'comments' => $comments
    ];
}

    // Обработка GET-запросов
	$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    if (!isset($_GET['action'])) {
        if (isset($_GET['id'])) {
			// Получаем одну новость по id
			$newsId = (int)$_GET['id'];
			$newsItem = $news->getNewsById($newsId); // Получаем одну новость
			// Обрабатываем контент
			$newsItem['content'] = $parse->userblocks($newsItem['content'], $config, $_SESSION['user'] ?? null);
			$newsItem['content'] = $parse->truncateHTML($newsItem['content'], 100000);
			$pageTitle = htmlspecialchars($newsItem['title']) . ' | IT Блог';
			$metaDescription = $news->generateMetaDescription($newsItem['content'], 'article', [
				'title' => $newsItem['title']
			]);
			
			$metaKeywords = $news->generateMetaKeywords($newsItem['content'], 'article', [
				'title' => $newsItem['title']
			]);
			$lastThreeNews = $news->getLastThreeNews();
			// Количество комментариев на страницу
			$commentsPerPage = 10;
			$currentCommentPage = isset($_GET['comment_page']) ? max(1, (int)$_GET['comment_page']) : 1;
			$commentsOffset = ($currentCommentPage - 1) * $commentsPerPage;

			// Получаем комментарии с пагинацией
			$commentsList = $comments->getComments($newsId, $commentsPerPage, $commentsOffset);
			$totalComments = $comments->countComments($newsId);
			$totalCommentPages = ceil($totalComments / $commentsPerPage);

			// Передаем данные в шаблон
			$commonVars = getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
		$pageVars = [
			'pageTitle' => $pageTitle,
			'commentsList' => $commentsList,
			'totalCommentPages' => $totalCommentPages,
			'currentCommentPage' => $currentCommentPage,
			'metaDescription' => $metaDescription,
			'metaKeywords' => $metaKeywords,
			'newsItem' => $newsItem,
			'votes' => $votes,
			];
			$template->assignMultiple(array_merge($commonVars, $pageVars));
			
		} elseif (isset($_GET['tags'])) {
			// Обработка тегов
			$tag = htmlspecialchars($_GET['tags']);
			$pageTitle = "Новости по тегу: " . $tag;
			$metaDescription = $news->generateMetaDescription('', 'tag', [
				'tag' => $tag
			]);
			
			$metaKeywords = $news->generateMetaKeywords('', 'tag', [
				'tag' => $tag
			]);
			$newsByTags = $news->getNewsByTag($tag);
			foreach ($newsByTags as &$item) {
				if (isset($item['excerpt'])) {
					$item['excerpt'] = $parse->userblocks(
						$item['excerpt'],
						$config,
						$_SESSION['user'] ?? null  // Передаем данные пользователя
					);
					$item['excerpt'] = $parse->truncateHTML($item['excerpt']);
				}
			}
			unset($item);
			$lastThreeNews = $news->getLastThreeNews();
			// Передача данных в шаблон
			$commonVars = getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
		$pageVars = [
			'pageTitle' => $pageTitle,
			'metaDescription' => $metaDescription,
			'metaKeywords' => $metaKeywords,
			'news' => $newsByTags,
			'votes' => $votes,
			];
			$template->assignMultiple(array_merge($commonVars, $pageVars));
			
		} else {
			// Загрузка главной страницы
			$pageTitle = 'Главная страница'; // Заголовок для главной страницы
			$limit = 6; // Количество новостей на странице
			$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
			$offset = ($page - 1) * $limit;
			$allNews = $news->getAllNews($limit, $offset);
			foreach ($allNews as &$item) {
				if (isset($item['excerpt'])) {
					$item['excerpt'] = $parse->userblocks(
						$item['excerpt'],
						$config,
						$_SESSION['user'] ?? null  // Передаем данные пользователя
					);
					$item['excerpt'] = $parse->truncateHTML($item['excerpt']);
				}
			}
			unset($item);
			$totalNewsCount = $news->getTotalNewsCount();
			$totalPages = ceil($totalNewsCount / $limit);
			$lastThreeNews = $news->getLastThreeNews();
			
			// Передача данных в шаблон
			$commonVars = getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
		$pageVars = [
			'pageTitle' => $pageTitle,
			'metaDescription' => $config['metaDescription'],
			'metaKeywords' => $config['metaKeywords'],
			'news' => $allNews,
			'totalPages' => $totalPages,
			'currentPage' => $page,
			'votes' => $votes,
			];
			$template->assignMultiple(array_merge($commonVars, $pageVars));
		}
    echo $template->render('home.tpl');
    } else {
        switch ($_GET['action']) {
			case 'search':
				if (!empty($searchQuery)) {
				
					// Обработка поиска
					$pageTitle = 'Результаты поиска: ' . htmlspecialchars($searchQuery);
					$metaDescription = $news->generateMetaDescription('', 'search', [
						'query' => $searchQuery
					]);
					
					$metaKeywords = $news->generateMetaKeywords('', 'search', [
						'query' => $searchQuery
					]);
					$limit = 6; // Количество результатов на странице
					$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
					$offset = ($page - 1) * $limit;
					
					$searchResults = $news->searchNews($searchQuery, $limit, $offset);
					$totalResults = $news->countSearchResults($searchQuery);
					$totalPages = ceil($totalResults / $limit);
					
					$lastThreeNews = $news->getLastThreeNews();
					
					$commonVars = getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
					'pageTitle' => $pageTitle,
					'searchQuery' => htmlspecialchars($searchQuery),
					'news' => $searchResults,
					'totalPages' => $totalPages,
					'currentPage' => $page,
					'totalResults' => $totalResults,
					'metaDescription' => $metaDescription,
					'metaKeywords' => $metaKeywords,
					];
					$template->assignMultiple(array_merge($commonVars, $pageVars));
					echo $template->render('search.tpl');
					
				}
				break;
			case 'login':
				$metaDescription = $news->generateMetaDescription('', 'login');
				$metaKeywords = $news->generateMetaKeywords('', 'login');
				$commonVars = getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
				'pageTitle' => 'Авторизация simpleBlog',
				'metaDescription' => $metaDescription,
				'metaKeywords' => $metaKeywords,
				];
				$template->assignMultiple(array_merge($commonVars, $pageVars));
				echo $template->render('login.tpl');
                break;
            case 'register':
				$metaDescription = $news->generateMetaDescription('', 'register');
				$metaKeywords = $news->generateMetaKeywords('', 'register');
                $commonVars = getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
				'pageTitle' => 'Регистрация simpleBlog',
				'metaDescription' => $metaDescription,
				'metaKeywords' => $metaKeywords,
				];
				$template->assignMultiple(array_merge($commonVars, $pageVars));
				echo $template->render('register.tpl');
                break;
            case 'contact':
				$metaDescription = $news->generateMetaDescription('', 'contact');
				$metaKeywords = $news->generateMetaKeywords('', 'contact');
                $commonVars = getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
				'pageTitle' => 'Форма обратной связи simpleBlog',
				'metaDescription' => $metaDescription,
				'metaKeywords' => $metaKeywords,
				];
				$template->assignMultiple(array_merge($commonVars, $pageVars));
				echo $template->render('contact.tpl');
                break;
            default:
                // Обработка 404 ошибки
                header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
				$metaDescription = $news->generateMetaDescription('', 'error404');
				$metaKeywords = $news->generateMetaKeywords('', 'error404');
				$commonVars = getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
				'pageTitle' => '404 Страница не найдена',
				'metaDescription' => $metaDescription,
				'metaKeywords' => $metaKeywords,
				];
				$template->assignMultiple(array_merge($commonVars, $pageVars));
                echo $template->render('404.tpl');
                exit;
        }
    }
} catch (Exception $e) {
    // Обработка ошибки 500
    error_log($e->getMessage()); // Логирование ошибки
    header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
	$metaDescription = $news->generateMetaDescription('', 'error500');
    $metaKeywords = $news->generateMetaKeywords('', 'error500');
	$commonVars = getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
	$pageVars = [
	'pageTitle' => '500 Внутренняя ошибка сервера',
	'metaDescription' => $metaDescription,
	'metaKeywords' => $metaKeywords,
	];
	$template->assignMultiple(array_merge($commonVars, $pageVars));
    echo $template->render('500.tpl');
    exit;
}
$finish = microtime(1);
//echo 'generation time: ' . round($finish - $start, 5) . ' сек';
//echo $searchQuery;