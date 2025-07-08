<?php
/**
 * Административная панель управления
 * 
 * @package    SimpleBlog
 * @subpackage Admin
 * @version    0.9.0
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
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true
]);
require 'class/Lang.php';
Lang::init();

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
$backupDir = $config['backup_dir'];
$maxBackups = $config['max_backups'];
$version = $config['version'];
// Функция для логирования действий
function logAction($action, $details = null) {
    global $pdo, $dbPrefix;
    if (!$pdo) {
        error_log("Не удалось записать лог: соединение с БД не установлено");
        return false;
    }
    $user_id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
    $username = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : 'Система';
    try {
        $stmt = $pdo->prepare("INSERT INTO {$dbPrefix}admin_logs (user_id, username, action, details, ip_address) 
                              VALUES (:user_id, :username, :action, :details, :ip)");
        return $stmt->execute([
            ':user_id' => $user_id,
            ':username' => $username,
            ':action' => $action,
            ':details' => $details,
            ':ip' => $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        error_log("Ошибка записи лога: " . $e->getMessage());
        return false;
    }
}
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
    // Получаем IP адрес
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    // Формируем сообщение с деталями попытки доступа
    $details = "Попытка доступа к админ-панели";
    // Проверяем, есть ли соединение с БД
    if (isset($pdo)) {
        try {
            // Записываем в лог
            $stmt = $pdo->prepare("INSERT INTO {$dbPrefix}admin_logs 
                                 (action, details, ip_address) 
                                 VALUES (:action, :details, :ip)");
            $stmt->execute([
                ':action' => 'Неавторизованный доступ',
                ':details' => $details,
                ':ip' => $ip
            ]);
        } catch (PDOException $e) {
            error_log("Ошибка записи лога: " . $e->getMessage());
        }
    }
    header('Location: /');
    exit;
}
$currentUserRole = $_SESSION['user']['isadmin'] ?? 0;
require 'class/Cache.php';
Cache::init($config);
require 'class/Template.php';
require 'class/User.php';
require 'class/Contact.php';
require 'class/News.php';
require 'class/Comments.php';
require 'class/Parse.php';
require_once 'admin/backup_db.php';
require 'class/Updater.php';
$updater = new Updater($pdo, $config);

// После создания других объектов добавить:
$comments = new Comments($pdo);
$template = new Template();
$user = new User($pdo);
$contact = new Contact($pdo);
$news = new News($pdo);
$parse = new parse();

$pageTitle = Lang::get('admin_page', 'admin');
function getRoleName($roleValue) {
		switch((int)$roleValue) {
			case 9: return Lang::get('admin', 'admin');
			case 7: return Lang::get('moder', 'admin');
			case 0: return Lang::get('ruser', 'admin');
			default: return Lang::get('unknownrole', 'admin');
		}
	}

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        logAction('Ошибка CSRF', 'Попытка выполнения действия с неверным CSRF-токеном');
        die("Неверный CSRF-токен");
    }
    // Обработка действий
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
			case 'edit_comment':
			if (!$user->hasPermission(7, $currentUserRole)) {
				$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				logAction('Попытка редактирования комментария', "Редактирование комментария запрещено");
			} else {
                $id = (int)$_POST['id'];
                $userText = htmlspecialchars(trim($_POST['user_text']));
                $moderation = isset($_POST['moderation']) ? 1 : 0;
                
                if ($comments->editComment($id, $userText)) {
                    // Обновляем статус модерации отдельно
                    if ($moderation) {
                        $comments->approveComment($id);
                        logAction('Редактирование комментария', "Комментарий ID $id отредактирован и одобрен");
                    } else {
                        $stmt = $pdo->prepare("UPDATE `{$dbPrefix}comments` SET moderation = 0 WHERE id = ?");
                        $stmt->execute([$id]);
                        logAction('Редактирование комментария', "Комментарий ID $id отредактирован и снят с публикации");
                    }
                    $_SESSION['admin_message'] = Lang::get('edit_comm', 'core');
                } else {
                    logAction('Ошибка редактирования комментария', "Не удалось отредактировать комментарий ID $id");
                    $_SESSION['admin_error'] = Lang::get('edit_comm', 'core');
                }
			}
                break;
                
            case 'delete_comment':
			if (!$user->hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				logAction('Попытка удаления комментария', "Удаление комментария запрещено");
			} else {
                $id = (int)$_POST['id'];
                if ($comments->deleteComment($id)) {
                    logAction('Удаление комментария', "Комментарий ID $id удален");
                    $_SESSION['admin_message'] = Lang::get('comm_del', 'core');
                } else {
                    logAction('Ошибка удаления комментария', "Не удалось удалить комментарий ID $id");
                    $_SESSION['admin_error'] = Lang::get('comm_del_errno', 'core');
                }
			}
                break;
                
            case 'toggle_comment':
			if (!$user->hasPermission(7, $currentUserRole)) {
				$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				logAction('Попытка изменения статуса комментария', "Изменение статуса запрещено");
			} else {
                $id = (int)$_POST['id'];
                if ($comments->toggleModeration($id)) {
                    $status = $comments->getCommentStatus($id);
                    logAction('Изменение статуса комментария', "Комментарий ID $id: " . ($status ? 'Одобрен' : 'На модерации'));
                    $_SESSION['admin_message'] = Lang::get('comm_stat', 'core') . ($status ? Lang::get('comm_stat_app', 'core') : Lang::get('comm_stat_mod', 'core'));
                } else {
                    logAction('Ошибка изменения статуса комментария', "Не удалось изменить статус комментария ID $id");
                    $_SESSION['admin_error'] = Lang::get('comm_stat_err', 'core');
                }
			}
                break;
			case 'edit_settings':
				// Обработка сохранения настроек
					if (!$user->hasPermission(9, $currentUserRole)) {
						$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
						logAction('Попытка изменения системных настроек', "Изменение запрещено");
					} else {
						// Обновляем конфигурацию
						$config['home_title'] = trim($_POST['home_title']);
						$config['cache_enabled'] = trim($_POST['cache_enabled']);
						$config['cache_driver'] = trim($_POST['cache_driver']);
						$config['cache_ttl'] = trim($_POST['cache_ttl']);
						$config['cache_key_salt'] = trim($_POST['cache_key_salt']);
						$config['redis_host'] = trim($_POST['redis_host']);
						$config['redis_port'] = trim($_POST['redis_port']);
						$config['memcached_host'] = trim($_POST['memcached_host']);
						$config['memcached_port'] = trim($_POST['memcached_port']);
						$config['blogs_per_page'] = trim($_POST['blogs_per_page']);
						$config['comments_per_page'] = trim($_POST['comments_per_page']);
						$config['mail_from'] = trim($_POST['mail_from']);
						$config['mail_from_name'] = trim($_POST['mail_from_name']);
						$config['metaKeywords'] = trim($_POST['metaKeywords']);
						$config['metaDescription'] = trim($_POST['metaDescription']);
						$config['blocks_for_reg'] = isset($_POST['blocks_for_reg']) ? true : false;
						
						// Сохраняем обновленный конфиг
						file_put_contents(__DIR__ . '/config/config.php', "<?php\nif (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен');}\nreturn " . var_export($config, true) . ";\n");
						
						$_SESSION['admin_message'] = Lang::get('save_setting', 'core');
						logAction('Изменение системных настроек', 'Обновлены основные настройки системы');
						
						header("Location: ?section=system_settings");
						exit;
					}
				break;
                
            case 'update_blog':
				if (!$user->hasPermission(7, $currentUserRole)) {
					$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
					logAction('Попытка редактирования записи блога', "Редактирование запрещено");
				} else {
					$id = (int)$_POST['id'];
					$title = htmlspecialchars(trim($_POST['title']));
					$content = nl2br(trim($_POST['content']));
					$tags = isset($_POST['tags']) ? $_POST['tags'] : [];

					if ($news->updateBlog($id, $title, $content, $tags)) {
						logAction('Редактирование записи блога', "Запись ID $id отредактирована. Новый заголовок: $title");
						$_SESSION['admin_message'] = Lang::get('blog_upd', 'core');
						header("Location: ?section=blogs");
						exit;
					} else {
						logAction('Ошибка редактирования записи блога', "Не удалось отредактировать запись ID $id");
						$_SESSION['admin_error'] = Lang::get('blog_err', 'core');
						header("Location: ?section=blogs&action=edit&id=$id");
						exit;
					}
				}
				break;
                
            case 'delete_blog':
			if (!$user->hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				logAction('Попытка удаления записи блога', "Удаление запрещено");
			} else {
                $id = (int)$_POST['id'];
                if ($news->deleteBlog($id)) {
                    logAction('Удаление записи блога', "Запись ID $id удалена");
                    $_SESSION['admin_message'] = Lang::get('blog_del', 'core');
                } else {
                    logAction('Ошибка удаления записи блога', "Не удалось удалить запись ID $id");
                    $_SESSION['admin_error'] = Lang::get('blog_del_err', 'core');
                }
			}
                break;
                
            case 'add_blog':
			if (!$user->hasPermission(7, $currentUserRole)) {
				$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				logAction('Попытка добавления записи блога', "Добавление запрещено");
			} else {
                $title = htmlspecialchars(trim($_POST['title']));
                $content = nl2br(trim($_POST['content']));
                
                if ($news->addBlog($title, $content, $tags)) {
                    logAction('Добавление записи блога', "Добавлена новая запись: $title");
                    $_SESSION['admin_message'] = Lang::get('blog_add', 'core');
                } else {
                    logAction('Ошибка добавления записи блога', "Не удалось добавить запись: $title");
                    $_SESSION['admin_error'] = Lang::get('blog_add_err', 'core');
                }
			}
                break;
                
            case 'delete_contact':
			if (!$user->hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				logAction('Попытка удаления сообщения', "Удаление запрещено");
			} else {
                $id = (int)$_POST['id'];
                if ($contact->deleteMessage($id)) {
                    logAction('Удаление сообщения', "Сообщение ID $id удалено");
                    $_SESSION['admin_message'] = Lang::get('cont_del', 'core');
                } else {
                    logAction('Ошибка удаления сообщения', "Не удалось удалить сообщение ID $id");
                    $_SESSION['admin_error'] = Lang::get('cont_err', 'core');
                }
			}
                break;
                
            case 'edit_user':
			if (!$user->hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				logAction('Попытка редактирования пользователя', "Редактирование запрещено");
			} else {
                $id = (int)$_POST['id'];
                $username = htmlspecialchars(trim($_POST['username']));
                $email = htmlspecialchars(trim($_POST['email']));
                $isadmin = (int)$_POST['isadmin']; // Получаем 0, 7 или 9
                
                if ($user->updateUser($id, $username, $email, $isadmin)) {
					$roleName = getRoleName($isadmin);
                    logAction('Редактирование пользователя', "ID: $id, Новые данные: $username, $email, Роль: $roleName");
                    $_SESSION['admin_message'] = Lang::get('user_upd', 'core');
                } else {
                    logAction('Ошибка редактирования пользователя', "Не удалось обновить пользователя ID $id");
                    $_SESSION['admin_error'] = Lang::get('user_upd_err', 'core');
                }
			}
                break;
                
            case 'delete_user':
			if (!$user->hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				logAction('Попытка удаления пользователя', "Удаление запрещено");
			} else {
                $id = (int)$_POST['id'];
                if ($user->deleteUser($id)) {
                    logAction('Удаление пользователя', "Пользователь ID $id удален");
                    $_SESSION['admin_message'] = Lang::get('user_del', 'core');
                } else {
                    logAction('Ошибка удаления пользователя', "Не удалось удалить пользователя ID $id");
                    $_SESSION['admin_error'] = Lang::get('user_del_err', 'core');
                }
			}		
                break;
                
            case 'add_tag':
			if (!$user->hasPermission(7, $currentUserRole)) {
				$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				logAction('Попытка добавления тега', "Добавление запрещено");
			} else {
                $name = htmlspecialchars(trim($_POST['name']));
                if ($news->addTag($name)) {
                    logAction('Добавление тега', "Добавлен новый тег: $name");
                    $_SESSION['admin_message'] = Lang::get('tag_add', 'core');
                } else {
                    logAction('Ошибка добавления тега', "Не удалось добавить тег: $name");
                    $_SESSION['admin_error'] = Lang::get('tag_err', 'core');
                }
			}
                break;
                
            case 'delete_tag':
			if (!$user->hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				logAction('Попытка удаления тега', "Удаление запрещено");
			} else {
                $id = (int)$_POST['id'];
                if ($news->deleteTag($id)) {
                    logAction('Удаление тега', "Тег ID $id удален");
                    $_SESSION['admin_message'] = Lang::get('tag_del', 'core');
                } else {
                    logAction('Ошибка удаления тега', "Не удалось удалить тег ID $id");
                    $_SESSION['admin_error'] = Lang::get('tag_del_err', 'core');
                }
			}
                break;
                
            case 'change_template':
			if (!$user->hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				logAction('Попытка смены шаблона', "Смена шаблона запрещена");
			} else {
                $templatesDir = 'templates';
                $configPath = 'config/config.php';
                $availableTemplates = $template->getAvailableTemplates($templatesDir);
                $newTemplate = $_POST['template'] ?? '';
                
                if ($template->changeTemplate($newTemplate, $availableTemplates, $config, $configPath)) {
                    $_SESSION['admin_message'] = Lang::get('templ_active', 'core');
                } else {
                    $_SESSION['admin_error'] = Lang::get('templ_err', 'core');
                }
			}
                header("Location: ?section=template_settings");
				exit();
                break;
			
			case 'delete_log':
				if (!$user->hasPermission(9, $currentUserRole)) {
					$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
					logAction('Попытка удаления лога', "Удаление лога запрещено");
				} else {
					$id = (int)$_POST['id'];
					try {
						$stmt = $pdo->prepare("DELETE FROM `{$dbPrefix}admin_logs` WHERE id = ?");
						if ($stmt->execute([$id])) {
							$_SESSION['admin_message'] = Lang::get('log_del', 'core');
						} else {
							$_SESSION['admin_error'] = Lang::get('log_del_err', 'core');
						}
					} catch (PDOException $e) {
						$_SESSION['admin_error'] = Lang::get('log_err', 'core');
					}
				}
				break;
			case 'create_backup':
				if (!$user->hasPermission(9, $currentUserRole)) {
					$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				} else {
					$backup = 'backup_'.date('Y-m-d_H-i-s').'.sql';
					$backupFile = dbBackup(__DIR__.'/'. $backupDir . $backup, false);
					if ($backupFile) {
						$_SESSION['admin_message'] = Lang::get('backup', 'core') . basename($backup);
						logAction('Создание резервной копии', 'Файл: ' . basename($backup));
					} else {
						$_SESSION['admin_error'] = Lang::get('backup_err', 'core');
						
					}
				}
				header("Location: ?section=backups");
				break;

			case 'restore_backup':
				if (!$user->hasPermission(9, $currentUserRole)) {
					$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				} else {
					$file = __DIR__ . '/'. $backupDir . basename($_POST['file']);
					if (!preg_match('/\.sql$/', $file)) {
						die("Только .sql файлы разрешены");
					}
					if (file_exists($file)) {
						try {
							// Читаем SQL файл
							$sql = file_get_contents($file);
							
							// Удаляем комментарии и пустые строки
							$sql = preg_replace('/\-\-.*$/m', '', $sql);
							$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
							$sql = preg_replace('/^\s*$/m', '', $sql);
							if (filesize($file) > 10 * 1024 * 1024) { // 10MB
								$_SESSION['admin_error'] = 'Backup file is too large';
								break;
							}
							// Разбиваем на отдельные запросы
							$queries = array_filter(array_map('trim', explode(';', $sql)));
							
							// Выполняем каждый запрос в транзакции
							$pdo->beginTransaction();
							
							foreach ($queries as $query) {
								$stmt = $pdo->prepare($query);
								$stmt->execute();
							}
							
							$pdo->commit();
							
							$_SESSION['admin_message'] = Lang::get('backup_rest', 'core');
							logAction('Восстановление БД', 'Из файла: ' . basename($file));
						} catch (PDOException $e) {
							$pdo->rollBack();
							$_SESSION['admin_error'] = Lang::get('backup_rest_err', 'core') . ': ' . $e->getMessage();
							error_log("Backup restore error: " . $e->getMessage());
						}
					} else {
						$_SESSION['admin_error'] = Lang::get('not_file', 'core');
					}
				}
				break;

			case 'delete_backup':
				if (!$user->hasPermission(9, $currentUserRole)) {
					$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				} else {
					$file = __DIR__ . '/'. $backupDir . basename($_POST['file']);
					
					// Добавляем проверку пути
					$realPath = realpath($file);
					$allowedPath = realpath(__DIR__ . '/' . $backupDir);
					
					if ($realPath === false || strpos($realPath, $allowedPath) !== 0) {
						$_SESSION['admin_error'] = Lang::get('invalid_file_path', 'core');
					} elseif (file_exists($realPath)) {
						if (unlink($realPath)) {
							$_SESSION['admin_message'] = Lang::get('backup_del', 'core');
							logAction('Удаление резервной копии', 'Файл: ' . basename($realPath));
						} else {
							$_SESSION['admin_error'] = Lang::get('backup_del_failed', 'core');
						}
					} else {
						$_SESSION['admin_error'] = Lang::get('not_file', 'core');
					}
				}
				break;

			case 'update_backup_settings':
				if (!$user->hasPermission(9, $currentUserRole)) {
					$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
				} else {
					$config['max_backups'] = (int)$_POST['max_backups'];
					$config['backup_schedule'] = $_POST['backup_schedule'];
					
					// Сохраняем обновленный конфиг
					file_put_contents(__DIR__ . '/config/config.php', "<?php\nif (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен');}\nreturn " . var_export($config, true) . ";\n");
					
					$_SESSION['admin_message'] = Lang::get('backup_upd', 'core');
					logAction('Изменение настроек бэкапа', 'Макс. копий: ' . $config['max_backups'] . ', Расписание: ' . $config['backup_schedule']);
				}
				break;
			case 'install_update':
				if (!$user->hasPermission(9, $currentUserRole)) {
					$_SESSION['admin_error'] = Lang::get('not_perm', 'core');
					logAction('Попытка установки обновления', "Недостаточно прав");
				} else {
					try {
						// Сохраняем информацию об обновлении в сессии
						$_SESSION['available_update'] = $updateInfo;
						
						if ($updater->performUpdate()) {
							$_SESSION['admin_message'] = 'Обновление успешно установлено!';
							logAction('Установка обновления', "Установлена версия: " . $updateInfo['new_version']);
							
							
							// Перенаправляем с задержкой
							echo '<meta http-equiv="refresh" content="3;url=?section=updates">';
							exit;
						} else {
							throw new Exception("Не удалось выполнить обновление");
						}
					} catch (Exception $e) {
						$_SESSION['admin_error'] = 'Ошибка обновления: '.$e->getMessage();
						logAction('Ошибка обновления', $e->getMessage());
					}
				}
				break;
		}
	}
}
// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Определение раздела админки
$section = isset($_GET['section']) ? $_GET['section'] : 'server_info';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$updateInfo = $updater->checkForUpdates();
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
			$newsCount = $news->getTotalNewsCount();
			$blogs = $news->getAllAdm();
			$allTags = $pdo->query("SELECT * FROM `{$dbPrefix}tags` ORDER by `name`")->fetchAll(PDO::FETCH_ASSOC);
			$template->assign('newsCount', $newsCount);
			$template->assign('blogs', $blogs);
			$template->assign('allTags', $allTags);
			$template->assign('updateInfo', $updateInfo);
			break;
			
        case 'system_settings':
			$template->assign('currentSettings', $config);
			$template->assign('updateInfo', $updateInfo);
			break;

		case 'updates':
		
			$updateInfo = $updater->checkForUpdates();
			//var_dump($updateInfo);
			$template->assign('updateInfo', $updateInfo);
			$template->assign('currentVersion', $config['version']);
			break;
			
        case 'comments':
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
			$template->assign('updateInfo', $updateInfo);
            break;
            
        case 'contacts':
            $contacts = $contact->getAllMessages();
            $template->assign('contacts', $contacts);
			$template->assign('updateInfo', $updateInfo);
            break;
            
        case 'users':
            $users = $user->getAllUsers();
            $template->assign('users', $users);
			$template->assign('roleName', $roleName);
			$template->assign('updateInfo', $updateInfo);
            break;
            
        case 'tags':
            $tags = $pdo->query("SELECT * FROM `{$dbPrefix}tags` ORDER by `name`")->fetchAll(PDO::FETCH_ASSOC);
            $template->assign('tags', $tags);
			$template->assign('updateInfo', $updateInfo);
            break;
            
        case 'template_settings':
            $templatesDir = 'templates';
            $configPath = 'config/config.php';
            $availableTemplates = $template->getAvailableTemplates($templatesDir);
            $currentTemplate = $config['templ'] ?? 'simple';
            
            if (!in_array($currentTemplate, $availableTemplates)) {
                $currentTemplate = 'simple';
                $config['templ'] = 'simple';
                file_put_contents($configPath, "<?php\nif (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен');}\nreturn ".var_export($config, true).";\n");
            }
            
            $template->assign('templates', $availableTemplates);
            $template->assign('currentTemplate', $currentTemplate);
			$template->assign('updateInfo', $updateInfo);
            break;
            
        case 'logs':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $logs = $pdo->query("SELECT * FROM `{$dbPrefix}admin_logs` ORDER BY created_at DESC LIMIT $limit OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);
            $totalLogs = $pdo->query("SELECT COUNT(*) FROM `{$dbPrefix}admin_logs`")->fetchColumn();
            
            $template->assign('logs', $logs);
            $template->assign('totalLogs', $totalLogs);
            $template->assign('currentPage', $page);
            $template->assign('perPage', $limit);
			$template->assign('parse', $parse);
			$template->assign('updateInfo', $updateInfo);
            break;
			
		case 'server_info':
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
				'PDO MySQL' => extension_loaded('pdo_mysql') ? '✓' : '✗',
				'Gzip' => extension_loaded('gzip') ? '✓' : '✗',
				'GD' => extension_loaded('gd') ? '✓' : '✗',
				'Zlib' => extension_loaded('zlib') ? '✓' : '✗',
				'MBString' => extension_loaded('mbstring') ? '✓' : '✗',
			];
			
			$template->assign('serverInfo', $serverInfo);
			$template->assign('filePermissions', $filePermissions);
			$template->assign('phpModules', $phpModules);
			$template->assign('updateInfo', $updateInfo);
			break;
	
		case 'backups':
			if (isset($_GET['action']) && $_GET['action'] === 'download_backup') {
				try {
					if (!$user->hasPermission(9, $currentUserRole)) {
						throw new Exception(Lang::get('not_perm', 'core'));
					}

					if (empty($_GET['file'])) {
						throw new Exception("Filename not specified");
					}

					// Безопасное получение имени файла
					$filename = basename($_GET['file']);
					$backupDir = rtrim($config['backup_dir'], '/') . '/';
					$filepath = realpath(__DIR__ . '/' . $backupDir . $filename);
					// Валидация пути
					if (!$filepath) {
						throw new Exception("File not found");
					}

					if (!is_readable($filepath)) {
						throw new Exception("File not readable");
					}

					// Проверка что файл находится в разрешенной директории
					$allowedPath = realpath(__DIR__ . '/' . $backupDir);
					if (strpos($filepath, $allowedPath) !== 0) {
						throw new Exception("Invalid file location");
					}

					// Отключить буферизацию
					if (ob_get_level()) ob_end_clean();

					// Отправка файла
					header('Content-Description: File Transfer');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="' . $filename . '"');
					header('Content-Length: ' . filesize($filepath));
					header('Expires: 0');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					
					// Чтение и отправка файла
					$chunkSize = 1024 * 1024; // 1MB за раз
					$handle = fopen($filepath, 'rb');
					if (!$handle) throw new Exception("Cannot open file");
					
					while (!feof($handle)) {
						echo fread($handle, $chunkSize);
						flush();
					}
					
					fclose($handle);
					exit;
					
				} catch (Exception $e) {
					error_log("Backup download failed: " . $e->getMessage());
					$_SESSION['admin_error'] = "Ошибка загрузки: " . $e->getMessage();
					header("Location: ?section=backups");
					exit;
				}
            }
			$backups = [];
			
			if (file_exists($backupDir)) {
				$backups = glob($backupDir . 'backup_*.sql');
				// Сортируем по дате (новые сверху)
				usort($backups, function($a, $b) {
					return filemtime($b) - filemtime($a);
				});
			}
			
			$template->assign('backups', $backups);
			$template->assign('max_backups', $config['max_backups'] ?? 5);
			$template->assign('backup_schedule', $config['backup_schedule'] ?? 'disabled');
			$template->assign('updateInfo', $updateInfo);
			break;
    }
    
    echo $template->render('admin/index.tpl');
    
} catch (Exception $e) {
    logAction('Ошибка системы', "Ошибка в админ-панели: " . $e->getMessage());
    error_log($e->getMessage());
    header($_SERVER["SERVER_PROTOCOL"] . Lang::get('error500', 'core'));
    $template->assign('pageTitle', Lang::get('error500', 'core'));
    echo $template->render('admin/500.tpl');
    exit;
}

$finish = microtime(1);
echo 'generation time: ' . round($finish - $start, 5) . ' сек';