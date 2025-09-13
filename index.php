<?php
/**
 * Главный контроллер приложения - точка входа
 * 
 * @package    SimpleBlog
 * @subpackage Core
 * @version    0.9.7
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
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
$start = microtime(1);
session_start([
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax', 
    'use_strict_mode' => true,
    'cookie_lifetime' => 7200,
    'gc_maxlifetime' => 7200 
]);
$regenerateTime = 300;
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
    session_regenerate_id(true);
} elseif (time() - $_SESSION['created'] > $regenerateTime) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
require 'class/ErrorHandler.php';;
ErrorHandler::init($config['debug'] ?? false);
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
    $host = $config['host'];
    $database = $config['database'];
    $db_user = $config['db_user'];
    $db_pass = $config['db_pass'];
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
require 'class/User.php';
require 'class/Contact.php';
require 'class/Comments.php';
require 'class/News.php';
require 'class/Vote.php';
require 'class/ImageUploader.php';
require 'class/Parse.php';
require 'class/Mailer.php';
require 'class/Pagination.php';
require 'class/Core.php';
require 'class/FooterDataProvider.php';
require 'class/Template.php';
// Инициализация объектов
$votes = new Votes($pdo);
$contact = new Contact($pdo);
$news = new News($pdo);
$comments = new Comments($pdo);
$parse = new parse();
$template = new Template();
$imageUploader = new imageUploader($uploadDir, $maxSize);
$user = new User($pdo, $template);
$baseTitle = $config['home_title'];
$pageTitle = $baseTitle;
$core = new Core($pdo, $config, $template);
// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $core->handlePostRequest();
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
			$newsItem = $news->getNewsByIdCached($newsId); // Получаем одну новость
			if (!$newsItem) {
				header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
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
			$totalComments = $comments->countComments($newsId);
			$currentCommentPage = (int)($_GET['comment_page'] ?? 1);

			$pagination = Pagination::calculate(
				$totalComments,
				Pagination::TYPE_COMMENTS,
				$currentCommentPage,
				$config
			);

			$commentsList = $comments->getComments(
				$newsId,
				$pagination['per_page'],
				$pagination['offset']
			);
//var_dump($commentsList);
			$paginationHtml = Pagination::render(
				$pagination, 
				"?id=$newsId", 
				'comment_page'
			);
			$articleRating = $votes->getArticleRating($newsId);
			// Передаем данные в шаблон
			$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
			$pageVars = [
			'pageTitle' => $pageTitle,
			'metaDescription' => $metaDescription,
			'metaKeywords' => $metaKeywords,
			'csrf_token' => $_SESSION['csrf_token'],
			];
			$footerProvider = new FooterDataProvider($news, $user, $template, $config);

			// Получаем данные для футера
			$footerData = $footerProvider->prepareFooterData();

			$template->assignMultiple(array_merge($commonVars, $pageVars, $footerData));
			$template->assign('news.id', $newsId);
			$template->assign('pagination', new class($paginationHtml) {
				private $html;
				public function __construct($html) { $this->html = $html; }
				public function __toString() { return $this->html; }
			});
			//var_dump($commonVars);
		/*$output = $template->render('news.tpl');
        Cache::set($cacheKey, $output, $config['cache_ttl'] ?? 3600);
        echo $output;*/
		$output = $template->render('header.tpl');
		$output .= $template->renderNewsItem($newsItem, 'news.tpl');
		$output .= $template->processComments($commentsList, 'comment.tpl');
		$output .= $template->render('add_comment.tpl');
		//$output .= $template->render('footer.tpl');
		$output .= $template->renderFooter($footerData);
		Cache::set($cacheKey, $output, $config['cache_ttl'] ?? 3600);
        echo $output;
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
			$countNewsByTags = $news->getNewsCountByTag($tag);
			$page = (int)($_GET['page'] ?? 1);
			$pagination = Pagination::calculate(
				$countNewsByTags, 
				Pagination::TYPE_NEWS, 
				$page, 
				$config
			);
			$newsByTags = $news->getNewsByTag($tag, $pagination['per_page'], $pagination['offset']);
			
			foreach ($newsByTags as &$item) {
				if (isset($item['content'])) {
					$item['content'] = $parse->userblocks(
						$item['content'],
						$config,
						$_SESSION['user'] ?? null  
					);
					$item['content'] = $parse->truncateHTML($item['content']);
				}
			}
			unset($item);
			$paginationHtml = Pagination::render($pagination, "?tags=" . urlencode($tag) . "&page=");
			$lastThreeNews = $news->getLastThreeNews();
			
			$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
		$pageVars = [
			'pageTitle' => $pageTitle,
			'metaDescription' => $metaDescription,
			'metaKeywords' => $metaKeywords,
			'votes' => $votes,
			];
			if (empty($newsByTags)) {
				$template->assign('no_news_message', 'Нет новостей');
			}
			$footerProvider = new FooterDataProvider($news, $user, $template, $config);

			$footerData = $footerProvider->prepareFooterData();

			$template->assignMultiple(array_merge($commonVars, $pageVars, $footerData));
			$template->assign('pagination', new class($paginationHtml) {
				private $html;
				public function __construct($html) { $this->html = $html; }
				public function __toString() { return $this->html; }
			});
			$output = $template->render('header.tpl');
			$output .= $template->renderNewsList($newsByTags, 'news_item.tpl');
			$output .= $template->renderFooter($footerData);
			Cache::set($cacheKey, $output, $config['cache_ttl'] ?? 3600);
			echo $output;
		} else {
			// Загрузка главной страницы
			$userPrefix = isset($_SESSION['user_id']) ? 'user_' . $_SESSION['user_id'] : 'guest';
			$cacheKey = $userPrefix . '_homepage_' . ($_SESSION['lang'] ?? 'ru') . '_page_' . (isset($_GET['page']) ? (int)$_GET['page'] : 1);
			if (Cache::has($cacheKey) && ($_SERVER['REQUEST_METHOD'] === 'GET')) {
				echo Cache::get($cacheKey);
				exit;
			}
			$pageTitle = Lang::get('home_page', 'main') . ' | ' . $baseTitle;
			$totalNewsCount = $news->getTotalNewsCount();
			$page = (int)($_GET['page'] ?? 1);

			$pagination = Pagination::calculate(
				$totalNewsCount, 
				Pagination::TYPE_NEWS, 
				$page, 
				$config
			);

			$allNews = $news->getAllNewsCached(
				$pagination['per_page'], 
				$pagination['offset']
			);

			$paginationHtml = Pagination::render($pagination, "/");
			foreach ($allNews as &$item) {
				if (isset($item['content'])) {
					$item['content'] = $parse->userblocks(
						$item['content'],
						$config,
						$_SESSION['user'] ?? null  
					);
					$item['content'] = $parse->truncateHTML($item['content']);
				}
				
			}
			unset($item);
			$lastThreeNews = $news->getLastThreeNews();
			
			$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
		$pageVars = [
			'pageTitle' => $pageTitle,
			'metaDescription' => $config['metaDescription'],
			'metaKeywords' => $config['metaKeywords'],
			'news' => $allNews,
			'votes' => $votes,
			];
			$footerProvider = new FooterDataProvider($news, $user, $template, $config);

			$footerData = $footerProvider->prepareFooterData();

			$template->assignMultiple(array_merge($commonVars, $pageVars, $footerData));
			$template->assign('pagination', new class($paginationHtml) {
				private $html;
				public function __construct($html) { $this->html = $html; }
				public function __toString() { return $this->html; }
			});
			
		$output = $template->render('header.tpl');
		$output .= $template->renderNewsList($allNews, 'news_item.tpl');
		$output .= $template->renderFooter($footerData);
		Cache::set($cacheKey, $output, $config['cache_ttl'] ?? 3600);
        echo $output;
		}
		
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
                $footerProvider = new FooterDataProvider($news, $user, $template, $config);

			$footerData = $footerProvider->prepareFooterData();

			$template->assignMultiple(array_merge($commonVars, $pageVars, $footerData));
                $output = $template->render('header.tpl');
				$output .= $template->render('forgot_password.tpl');
				$output .= $template->renderFooter($footerData);
				echo $output;
                break;
            case 'profile':
				if (!isset($_SESSION['user']['id'])) {
					$_SESSION['flash'] = Lang::get('auth_required', 'core');
					header('Location: /?action=login');
					exit;
				}

				// Обработка POST-запроса на обновление профиля
				/*if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$username = trim($_POST['username'] ?? '');
					$email = trim($_POST['email'] ?? '');
					$currentPassword = trim($_POST['current_password'] ?? '');
					
					// Обработка загрузки аватара
					$avatar = null;
					if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
					    try {
							// Инициализируем загрузчик
							$uploadDir = 'uploads/avatars/';
							$maxSize = 2 * 1024 * 1024; // 2MB
							$imageUploader = new ImageUploader($uploadDir, $maxSize);
							
							// Загружаем изображение. Базовое имя = 'user_' + ID пользователя
							$baseFileName = 'user_' . $_SESSION['user']['id'];
							$newAvatarPath = $imageUploader->upload($_FILES['avatar'], $baseFileName);
							
							// Если загрузка успешна, удаляем старый аватар и сохраняем путь к новому
							ImageUploader::removeOldAvatar($_SESSION['user']['avatar'] ?? null);
							$avatar = $newAvatarPath;
							
						} catch (RuntimeException $e) {
							// Ловим и обрабатываем ошибки загрузки
							$_SESSION['flash'] = Lang::get('avatar_upload_error', 'core') . ': ' . $e->getMessage();
							header('Location: /?action=profile');
							exit;
						}
					}
					
					// Проверяем, изменился ли email
					$emailChanged = ($email !== $_SESSION['user']['email']);
					
					// Если email изменился, проверяем текущий пароль
					if ($emailChanged && empty($currentPassword)) {
						$_SESSION['flash'] = Lang::get('current_password_required', 'core');
						header('Location: /?action=profile');
						exit;
					}
					
					// Обновляем профиль
					if ($user->updateProfile($_SESSION['user']['id'], $username, $email, $avatar, $emailChanged ? $currentPassword : null)) {
						$_SESSION['flash'] = Lang::get('profile_updated', 'core');
						
						// Обновляем данные в сессии
						$_SESSION['user']['username'] = $username;
						$_SESSION['user']['email'] = $email;
						if ($avatar) {
							$_SESSION['user']['avatar'] = $avatar;
						}
						
						// Если email изменился, разлогиниваем пользователя или отмечаем email как неверифицированный
						if ($emailChanged) {
							$_SESSION['user']['email_verified'] = false;
							$_SESSION['flash'] = Lang::get('email_change_success_verify', 'core');
						}
						
						header('Location: /?action=profile');
						exit;
					} else {
						$_SESSION['flash'] = Lang::get('profile_update_error', 'core');
					}
				}*/

				// Обработка привязки соцсетей
				if (isset($_GET['link_social'])) {
					$socialType = $_GET['link_social'];
					$core->handleSocialAuth($socialType);
					$_SESSION['flash'] = Lang::get('social_link_started', 'core') . ': ' . $socialType;
					exit;
				}

				if (isset($_GET['unlink_social'])) {
					if ($user->removeSocialLink($_SESSION['user']['id'], $_GET['unlink_social'])) {
						$_SESSION['flash'] = Lang::get('social_unlinked', 'core');
					} else {
						$_SESSION['flash'] = Lang::get('social_unlink_error', 'core');
					}
					header('Location: /?action=profile');
					exit;
				}

				// Получаем данные для профиля
				$userData = $user->getUserById($_SESSION['user']['id']);
				$userNews = $news->getNewsByAuthor($_SESSION['user']['id'], 5);
				$userComments = $comments->getCommentsByUser($_SESSION['user']['id'], 5);
				$socialLinks = $user->getSocialLinks($_SESSION['user']['id']);
				// Если avatar не установлен, задаем значение по умолчанию
				if (!isset($userData['avatar']) || empty($userData['avatar'])) {
					$userData['avatar'] = 'images/avatar_g.png';
				}

				// Формируем данные для шаблона
				$pageTitle = Lang::get('profile', 'core') . ' | ' . $baseTitle;
				$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
					'pageTitle' => $pageTitle,
					'userData' => $userData,
					'userNews' => $userNews,
					'userComments' => $userComments,
					'socialLinks' => $socialLinks,
					'supportedSocials' => ['telegram', 'github', 'google'] // Поддерживаемые соцсети
				];

				$footerProvider = new FooterDataProvider($news, $user, $template, $config);
				// Получаем данные для футера
				$footerData = $footerProvider->prepareFooterData();
				$template->assignMultiple(array_merge($commonVars, $pageVars, $footerData));
				$output = $template->render('header.tpl');
				$output .= $template->render('profile.tpl');
				$output .= $template->renderFooter($footerData);
				echo $output;
				break;
			case 'oauth_callback':
				$this->handleOAuthCallback();
				exit;
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
                    $footerProvider = new FooterDataProvider($news, $user, $template, $config);

			// Получаем данные для футера
			$footerData = $footerProvider->prepareFooterData();

			$template->assignMultiple(array_merge($commonVars, $pageVars, $footerData));
                    $output = $template->render('header.tpl');
					$output .= $template->render('reset_password.tpl');
					$output .= $template->renderFooter($footerData);
					echo $output;
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
					$pageTitle = Lang::get('search_results', 'core')  . htmlspecialchars($searchQuery) . ' | ' . $baseTitle;
					$metaDescription = $news->generateMetaDescription('', 'search', [
						'query' => $searchQuery
					]);
					
					$metaKeywords = $news->generateMetaKeywords('', 'search', [
						'query' => $searchQuery
					]);
					$totalResults = $news->countSearchResults($searchQuery);
					$page = (int)($_GET['page'] ?? 1);
					$pagination = Pagination::calculate(
						$totalResults, 
						Pagination::TYPE_NEWS, 
						$page, 
						$config
					);
					$searchResults = $news->searchNews($searchQuery, $pagination['per_page'], $pagination['offset']);
					foreach ($searchResults as &$item) {
						if (isset($item['content'])) {
							$item['content'] = $parse->userblocks(
								$item['content'],
								$config,
								$_SESSION['user'] ?? null  // Передаем данные пользователя
							);
							$item['content'] = $parse->truncateHTML($item['content']);
						}
					}
					unset($item);
					$paginationHtml = Pagination::render(
    $pagination, 
    "?action=search&search=" . urlencode($searchQuery)
);
					$lastThreeNews = $news->getLastThreeNews();
					
					$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
					'pageTitle' => $pageTitle,
					'metaDescription' => $metaDescription,
					'metaKeywords' => $metaKeywords,
					];
					$footerProvider = new FooterDataProvider($news, $user, $template, $config);

			// Получаем данные для футера
			$footerData = $footerProvider->prepareFooterData();

			$template->assignMultiple(array_merge($commonVars, $pageVars, $footerData));
					$template->assign('pagination', new class($paginationHtml) {
						private $html;
						public function __construct($html) { $this->html = $html; }
						public function __toString() { return $this->html; }
					});
					$output = $template->render('header.tpl');
					$output .= $template->renderNewsList($searchResults, 'search.tpl');
					$output .= $template->renderFooter($footerData);
					echo $output;
				}
				break;
			case 'login':
				$pageTitle = Lang::get('auth', 'core') . ' | ' . $baseTitle;
				$metaDescription = $news->generateMetaDescription('', 'login');
				$metaKeywords = $news->generateMetaKeywords('', 'login');
				$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
				'pageTitle' => $pageTitle,
				'metaDescription' => $metaDescription,
				'metaKeywords' => $metaKeywords,
				];
				$footerProvider = new FooterDataProvider($news, $user, $template, $config);

			// Получаем данные для футера
			$footerData = $footerProvider->prepareFooterData();

			$template->assignMultiple(array_merge($commonVars, $pageVars, $footerData));
				$output = $template->render('header.tpl');
				$output .= $template->render('login.tpl');
				$output .= $template->renderFooter($footerData);
				echo $output;
                break;
            case 'register':
				$pageTitle = Lang::get('register', 'core') . ' | ' . $baseTitle;
				$metaDescription = $news->generateMetaDescription('', 'register');
				$metaKeywords = $news->generateMetaKeywords('', 'register');
                $commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
				'pageTitle' => $pageTitle,
				'metaDescription' => $metaDescription,
				'metaKeywords' => $metaKeywords,
				];
				$footerProvider = new FooterDataProvider($news, $user, $template, $config);

			// Получаем данные для футера
			$footerData = $footerProvider->prepareFooterData();

			$template->assignMultiple(array_merge($commonVars, $pageVars, $footerData));
				$output = $template->render('header.tpl');
				$output .= $template->render('register.tpl');
				$output .= $template->renderFooter($footerData);
				echo $output;
                break;
            case 'contact':
				$pageTitle = Lang::get('contact', 'core') . ' | ' . $baseTitle;
				$metaDescription = $news->generateMetaDescription('', 'contact');
				$metaKeywords = $news->generateMetaKeywords('', 'contact');
                $commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
				'pageTitle' => $pageTitle,
				'metaDescription' => $metaDescription,
				'metaKeywords' => $metaKeywords,
				];
				$footerProvider = new FooterDataProvider($news, $user, $template, $config);

			// Получаем данные для футера
			$footerData = $footerProvider->prepareFooterData();

			$template->assignMultiple(array_merge($commonVars, $pageVars, $footerData));
				$output = $template->render('header.tpl');
				$output .= $template->render('contact.tpl');
				$output .= $template->renderFooter($footerData);
				echo $output;
                break;
            default:
                // Обработка 404 ошибки
                header($_SERVER["SERVER_PROTOCOL"] . Lang::get('error404', 'core'));
				$pageTitle = Lang::get('error404', 'core') . ' | ' . $baseTitle;
				$metaDescription = $news->generateMetaDescription('', 'error404');
				$metaKeywords = $news->generateMetaKeywords('', 'error404');
				$commonVars = $template->getCommonTemplateVars($config, $news, $_SESSION['user'] ?? null);
				$pageVars = [
				'pageTitle' => $pageTitle,
				'metaDescription' => $metaDescription,
				'metaKeywords' => $metaKeywords,
				];
				$footerProvider = new FooterDataProvider($news, $user, $template, $config);

				// Получаем данные для футера
				$footerData = $footerProvider->prepareFooterData();
				$template->assignMultiple(array_merge($commonVars, $pageVars));
                $output = $template->render('404.tpl');
				echo $output;
                exit;
        }
    }

$finish = microtime(1);
//echo 'generation time: ' . round($finish - $start, 5) . ' сек';