<?php
/**
 * Главный контроллер приложения - точка входа
 * 
 * @package    SimpleBlog
 * @subpackage Core
 * @version    0.8.0
 * 
 * @router
 *  GET  /             - Главная страница блога
 *  GET  /?id=N        - Просмотр отдельной записи
 *  GET  /?action=X    - Специальные действия (авторизация/регистрация)
 *  POST /             - Обработка форм
 * 
 * @features
 * - Поддержка русского и английского языков
 * - SEO-оптимизированные маршруты
 * - Защита от CSRF-атак
 * - Система кэширования
 */
define('IN_SIMPLECMS', true);
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://".$_SERVER['SERVER_NAME']."; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
$start = microtime(1);
session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax', 
    'use_strict_mode' => true,
    'cookie_lifetime' => 3600,
    'gc_maxlifetime' => 3600 
]);
require 'class/Lang.php';
Lang::init();

if (isset($_GET['lang'])) {
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
	echo '<p style="color: red; text-align: center;">'.Lang::get('delete_install', 'core').'</p>';
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
    echo "Connection failed";
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
require 'class/Cache.php';
Cache::init($config);
require 'class/Template.php';
require 'class/User.php';
require 'class/Contact.php';
require 'class/Comments.php';
require 'class/News.php';
require 'class/Vote.php';
require 'class/Parse.php';
require 'class/Mailer.php';
// Инициализация объектов
$user = new User($pdo, $template);
$contact = new Contact($pdo);
$news = new News($pdo);
$comments = new Comments($pdo);
$votes = new Votes($pdo);
$parse = new parse();
$template = new Template();
$baseTitle = $config['home_title'];
$pageTitle = $baseTitle;

try {
    // Обработка регистрации
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
			die("Неверный CSRF-токен");
		}
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
				die(Lang::get('not_id', 'core'));
			}
			
			// Проверяем существование новости
			$newsItem = $news->getNewsById($newsId);
			if (!$newsItem) {
				die(Lang::get('not_news', 'core'));
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
			header("Location: /");
			exit;
        }
        // Обработка авторизации
        if (isset($_POST['action']) && $_POST['action'] === 'login') {
			$captchaValid = isset($_POST['captcha']) && $_POST['captcha'] == $_SESSION['captcha_answer'];
			$username = $_POST['username'] ?? '';
			$password = $_POST['password'] ?? '';
			
			if (!$captchaValid) {
				$_SESSION['auth_error'] = Lang::get('not_answer', 'core');
			} elseif (empty($username) || empty($password)) {
				$_SESSION['auth_error'] = Lang::get('all_field', 'core');
			} else {
				$userData = $user->login($username, $password);
				if ($userData) {
					$_SESSION['user'] = $userData;
					session_regenerate_id(true);
					$_SESSION['last_activity'] = time();
					$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
					sleep(1);
					header("Location: /");
					exit;
				} else {
					$_SESSION['auth_error'] = Lang::get('not_login', 'core');
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
                    $errors[] = Lang::get('msg_send', 'core');
                } else {
                    $errors[] = Lang::get('msg_error', 'core');
                }
            } else {
                $errors[] = Lang::get('not_answer', 'core');
            }
        }
    }

// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
    // Обработка GET-запросов
	$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    if (!isset($_GET['action'])) {
        if (isset($_GET['id'])) {
			// Получаем одну новость по id
			$newsId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
				'options' => ['min_range' => 1]
			]);
			if ($newsId === false) {
				throw new InvalidArgumentException("Invalid news ID");
			}
			$userPrefix = isset($_SESSION['user_id']) ? 'user_' . $_SESSION['user_id'] : 'guest';
			$cacheKey = $userPrefix . '_news_page_'. $newsId .'_' . ($_SESSION['lang'] ?? 'ru');
			if ($_SERVER['REQUEST_METHOD'] === 'GET' && Cache::has($cacheKey)) {
				echo Cache::get($cacheKey);
				exit;
			}
			$userPrefix = isset($_SESSION['user_id']) ? 'user_' . $_SESSION['user_id'] : 'guest';
			$cacheKey = $userPrefix . '_news_page_'. $newsId .'_' . ($_SESSION['lang'] ?? 'ru');
			$newsItem = $news->getNewsByIdCached($newsId); // Получаем одну новость
			// Обрабатываем контент
			$newsItem['content'] = $parse->userblocks($newsItem['content'], $config, $_SESSION['user'] ?? null);
			$newsItem['content'] = $parse->truncateHTML($newsItem['content'], 100000);
			$pageTitle = htmlspecialchars($newsItem['title']) . ' | ' . $baseTitle;
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
			$articleRating = $votes->getArticleRating($newsId);

			// Передаем данные в шаблон
			$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
		$pageVars = [
			'pageTitle' => $pageTitle,
			'commentsList' => $commentsList,
			'totalCommentPages' => $totalCommentPages,
			'currentCommentPage' => $currentCommentPage,
			'metaDescription' => $metaDescription,
			'metaKeywords' => $metaKeywords,
			'news' => $newsItem,
			'articleRating' => $articleRating,
			'votes' => $votes,
			];
			$template->assignMultiple(array_merge($commonVars, $pageVars));
			
		} elseif (isset($_GET['tags'])) {
			// Обработка тегов
			$tag = htmlspecialchars($_GET['tags']);
			$userPrefix = isset($_SESSION['user_id']) ? 'user_' . $_SESSION['user_id'] : 'guest';
			$cacheKey = $userPrefix . '_tags_page_' . $tag . '_' . ($_SESSION['lang'] ?? 'ru');
			if ($_SERVER['REQUEST_METHOD'] === 'GET' && Cache::has($cacheKey)) {
				echo Cache::get($cacheKey);
				exit;
			}
			$pageTitle = Lang::get('tag_news', 'core') . $tag . ' | ' . $baseTitle;
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
			$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
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
			$userPrefix = isset($_SESSION['user_id']) ? 'user_' . $_SESSION['user_id'] : 'guest';
			$cacheKey = $userPrefix . '_homepage_' . ($_SESSION['lang'] ?? 'ru') . '_page_' . (isset($_GET['page']) ? (int)$_GET['page'] : 1);
			if (Cache::has($cacheKey) && ($_SERVER['REQUEST_METHOD'] === 'GET')) {
				echo Cache::get($cacheKey);
				exit;
			}
			$pageTitle = Lang::get('home_page', 'main') . ' | ' . $baseTitle;
			$limit = $config['blogs_per_page']; // Количество новостей на странице
			$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
			$offset = ($page - 1) * $limit;
			$allNews = $news->getAllNewsCached($limit, $offset);
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
			$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
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
		$output = $template->render('home.tpl');
        Cache::set($cacheKey, $output, $config['cache_ttl'] ?? 3600);
        echo $output;
	//echo $template->render('home.tpl');
    } else {
        switch ($_GET['action']) {
			case 'verify_email':
                if (isset($_GET['token'])) {
                    if ($user->verifyEmail($_GET['token'])) {
                        $_SESSION['flash'] = Lang::get('email_verified', 'core');
                    } else {
                        $_SESSION['flash'] = Lang::get('invalid_token', 'core');
                    }
                    header('Location: /');
                    exit;
                }
                break;
                
            case 'forgot_password':
                $pageTitle = Lang::get('forgot_password', 'core') . ' | ' . $baseTitle;
                $commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
                $pageVars = [
                    'pageTitle' => $pageTitle,
                ];
                $template->assignMultiple(array_merge($commonVars, $pageVars));
                echo $template->render('forgot_password.tpl');
                break;
                
            case 'request_reset':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
                    if ($user->sendPasswordReset($_POST['email'])) {
                        $_SESSION['flash'] = Lang::get('reset_email_sent', 'core');
                    } else {
                        $_SESSION['flash'] = Lang::get('reset_email_error', 'core');
                    }
                    header('Location: /');
                    exit;
                }
                break;
                
            case 'reset_password':
                if (isset($_GET['token'])) {
                    $pageTitle = Lang::get('reset_password', 'core') . ' | ' . $baseTitle;
                    $commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
                    $pageVars = [
                        'pageTitle' => $pageTitle,
                        'token' => $_GET['token']
                    ];
                    $template->assignMultiple(array_merge($commonVars, $pageVars));
                    echo $template->render('reset_password.tpl');
                    break;
                }
                
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'], $_POST['password'])) {
                    if ($user->resetPassword($_POST['token'], $_POST['password'])) {
                        $_SESSION['flash'] = Lang::get('password_reset_success', 'core');
                    } else {
                        $_SESSION['flash'] = Lang::get('password_reset_error', 'core');
                    }
                    header('Location: /');
                    exit;
                }
                break;
			case 'search':
				if (!empty($searchQuery)) {
				
					// Обработка поиска
					$pageTitle = Lang::get('search_results', 'core') . htmlspecialchars($searchQuery) . ' | ' . $baseTitle;
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
					
					$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
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
				$pageTitle = Lang::get('auth', 'core') . ' | ' . $baseTitle;
				$metaDescription = $news->generateMetaDescription('', 'login');
				$metaKeywords = $news->generateMetaKeywords('', 'login');
				$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
				'pageTitle' => Lang::get('auth', 'core'),
				'metaDescription' => $metaDescription,
				'metaKeywords' => $metaKeywords,
				];
				$template->assignMultiple(array_merge($commonVars, $pageVars));
				echo $template->render('login.tpl');
                break;
            case 'register':
				$pageTitle = Lang::get('register', 'core') . ' | ' . $baseTitle;
				$metaDescription = $news->generateMetaDescription('', 'register');
				$metaKeywords = $news->generateMetaKeywords('', 'register');
                $commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
				'pageTitle' => Lang::get('register', 'core'),
				'metaDescription' => $metaDescription,
				'metaKeywords' => $metaKeywords,
				];
				$template->assignMultiple(array_merge($commonVars, $pageVars));
				echo $template->render('register.tpl');
                break;
            case 'contact':
				$pageTitle = Lang::get('contact', 'core') . ' | ' . $baseTitle;
				$metaDescription = $news->generateMetaDescription('', 'contact');
				$metaKeywords = $news->generateMetaKeywords('', 'contact');
                $commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
				'pageTitle' => Lang::get('contact', 'core'),
				'metaDescription' => $metaDescription,
				'metaKeywords' => $metaKeywords,
				];
				$template->assignMultiple(array_merge($commonVars, $pageVars));
				echo $template->render('contact.tpl');
                break;
            default:
                // Обработка 404 ошибки
                header($_SERVER["SERVER_PROTOCOL"] . Lang::get('error404', 'core'));
				$pageTitle = Lang::get('error404', 'core') . ' | ' . $baseTitle;
				$metaDescription = $news->generateMetaDescription('', 'error404');
				$metaKeywords = $news->generateMetaKeywords('', 'error404');
				$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
				'pageTitle' => Lang::get('error404', 'core'),
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
    error_log(sprintf(
    '[%s] Error in %s:%d - %s',
    get_class($e),
    $e->getFile(),
    $e->getLine(),
    $config['debug'] ? $e->getMessage() : 'Operation failed'
)); // Логирование ошибки
    header($_SERVER["SERVER_PROTOCOL"] . Lang::get('error500', 'core'));
	$pageTitle = Lang::get('error500', 'core') . ' | ' . $baseTitle;
	$metaDescription = $news->generateMetaDescription('', 'error500');
    $metaKeywords = $news->generateMetaKeywords('', 'error500');
	$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
	$pageVars = [
	'pageTitle' => Lang::get('error500', 'core'),
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