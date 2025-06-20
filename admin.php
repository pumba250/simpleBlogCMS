<?php
$start = microtime(1);
session_start();
/**
 * Возвращает строку с временем, прошедшим с указанной даты
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    // Вместо динамического свойства используем переменную
    $weeks = floor($diff->d / 7);
    $days = $diff->d - $weeks * 7;
    
    $string = [
        'y' => 'год',
        'm' => 'месяц',
        'w' => 'неделю',
        'd' => 'день',
        'h' => 'час',
        'i' => 'минуту',
        's' => 'секунду',
    ];
    
    // Заменяем использование $diff->w и $diff->d
    $values = [
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    ];
    
    foreach ($string as $k => &$v) {
        if ($values[$k]) {
            $v = $values[$k] . ' ' . $v . ($values[$k] > 1 ? (in_array($k, ['y', 'm']) ? 'а' : (in_array($k, ['d', 'h']) ? 'а' : 'ы')) : '');
        } else {
            unset($string[$k]);
        }
    }
    
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' назад' : 'только что';
}
/**
 * Возвращает цвет badge в зависимости от типа действия
 */
function getLogBadgeColor($action) {
    $action = strtolower($action);
    if (strpos($action, 'удал') !== false) return 'danger';
    if (strpos($action, 'добав') !== false || strpos($action, 'созда') !== false) return 'success';
    if (strpos($action, 'опытк') !== false || strpos($action, 'измен') !== false) return 'warning';
    if (strpos($action, 'ошибка') !== false) return 'danger';
    if (strpos($action, 'вход') !== false || strpos($action, 'выход') !== false) return 'info';
    return 'secondary';
}
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
require 'class/Template.php';
require 'class/User.php';
require 'class/Contact.php';
require 'class/News.php';
require 'class/Comments.php';
require_once 'admin/backup_db.php';
// После создания других объектов добавить:
$comments = new Comments($pdo);
$template = new Template();
$user = new User($pdo);
$contact = new Contact($pdo);
$news = new News($pdo);
function getRoleName($roleValue) {
    switch((int)$roleValue) {
        case 9: return 'Администратор';
        case 7: return 'Модератор';
        case 0: return 'Пользователь';
        default: return 'Неизвестная роль';
    }
}
$pageTitle = 'Админ-панель';

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        logAction('Ошибка CSRF', 'Попытка выполнения действия с неверным CSRF-токеном');
        die("Неверный CSRF-токен");
    }
	$currentUserRole = $_SESSION['user']['isadmin'] ?? 0;

// Функция проверки прав
function hasPermission($requiredRole, $currentRole) {
    return $currentRole >= $requiredRole;
}
    // Обработка действий
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'edit_comment':
			if (!hasPermission(7, $currentUserRole)) {
				$_SESSION['admin_error'] = ("Недостаточно прав");
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
                    $_SESSION['admin_message'] = 'Комментарий успешно обновлен';
                } else {
                    logAction('Ошибка редактирования комментария', "Не удалось отредактировать комментарий ID $id");
                    $_SESSION['admin_error'] = 'Ошибка при обновлении комментария';
                }
			}
                break;
                
            case 'delete_comment':
			if (!hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = ("Недостаточно прав");
				logAction('Попытка удаления комментария', "Удаление комментария запрещено");
			} else {
                $id = (int)$_POST['id'];
                if ($comments->deleteComment($id)) {
                    logAction('Удаление комментария', "Комментарий ID $id удален");
                    $_SESSION['admin_message'] = 'Комментарий успешно удален';
                } else {
                    logAction('Ошибка удаления комментария', "Не удалось удалить комментарий ID $id");
                    $_SESSION['admin_error'] = 'Ошибка при удалении комментария';
                }
			}
                break;
                
            case 'toggle_comment':
			if (!hasPermission(7, $currentUserRole)) {
				$_SESSION['admin_error'] = ("Недостаточно прав");
				logAction('Попытка изменения статуса комментария', "Изменение статуса запрещено");
			} else {
                $id = (int)$_POST['id'];
                if ($comments->toggleModeration($id)) {
                    $status = $comments->getCommentStatus($id);
                    logAction('Изменение статуса комментария', "Комментарий ID $id: " . ($status ? 'Одобрен' : 'На модерации'));
                    $_SESSION['admin_message'] = 'Статус комментария изменен: ' . ($status ? 'Одобрен' : 'На модерации');
                } else {
                    logAction('Ошибка изменения статуса комментария', "Не удалось изменить статус комментария ID $id");
                    $_SESSION['admin_error'] = 'Ошибка при изменении статуса комментария';
                }
			}
                break;
                
            case 'update_blog':
				if (!hasPermission(7, $currentUserRole)) {
					$_SESSION['admin_error'] = "Недостаточно прав";
					logAction('Попытка редактирования записи блога', "Редактирование запрещено");
				} else {
					$id = (int)$_POST['id'];
					$title = htmlspecialchars(trim($_POST['title']));
					$content = nl2br(trim($_POST['content']));
					$tags = isset($_POST['tags']) ? $_POST['tags'] : [];
					
					if ($news->updateBlog($id, $title, $content, $tags)) {
						logAction('Редактирование записи блога', "Запись ID $id отредактирована. Новый заголовок: $title");
						$_SESSION['admin_message'] = 'Запись успешно обновлена';
						header("Location: ?section=blogs");
						exit;
					} else {
						logAction('Ошибка редактирования записи блога', "Не удалось отредактировать запись ID $id");
						$_SESSION['admin_error'] = 'Ошибка при обновлении записи';
						header("Location: ?section=blogs&action=edit&id=$id");
						exit;
					}
				}
				break;
                
            case 'delete_blog':
			if (!hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = ("Недостаточно прав");
				logAction('Попытка удаления записи блога', "Удаление запрещено");
			} else {
                $id = (int)$_POST['id'];
                if ($news->deleteBlog($id)) {
                    logAction('Удаление записи блога', "Запись ID $id удалена");
                    $_SESSION['admin_message'] = 'Запись успешно удалена';
                } else {
                    logAction('Ошибка удаления записи блога', "Не удалось удалить запись ID $id");
                    $_SESSION['admin_error'] = 'Ошибка при удалении записи';
                }
			}
                break;
                
            case 'add_blog':
			if (!hasPermission(7, $currentUserRole)) {
				$_SESSION['admin_error'] = ("Недостаточно прав");
				logAction('Попытка добавления записи блога', "Добавление запрещено");
			} else {
                $title = htmlspecialchars(trim($_POST['title']));
                $content = htmlspecialchars(trim($_POST['content']));
				//$content = nl2br(trim($_POST['content']));
                //$tags = isset($_POST['tags']) ? $_POST['tags'] : [];
                
                if ($news->addBlog($title, $content, $tags)) {
                    logAction('Добавление записи блога', "Добавлена новая запись: $title");
                    $_SESSION['admin_message'] = 'Запись успешно добавлена';
                } else {
                    logAction('Ошибка добавления записи блога', "Не удалось добавить запись: $title");
                    $_SESSION['admin_error'] = 'Ошибка при добавлении записи';
                }
			}
                break;
                
            case 'delete_contact':
			if (!hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = ("Недостаточно прав");
				logAction('Попытка удаления сообщения', "Удаление запрещено");
			} else {
                $id = (int)$_POST['id'];
                if ($contact->deleteMessage($id)) {
                    logAction('Удаление сообщения', "Сообщение ID $id удалено");
                    $_SESSION['admin_message'] = 'Сообщение успешно удалено';
                } else {
                    logAction('Ошибка удаления сообщения', "Не удалось удалить сообщение ID $id");
                    $_SESSION['admin_error'] = 'Ошибка при удалении сообщения';
                }
			}
                break;
                
            case 'edit_user':
			if (!hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = ("Недостаточно прав");
				logAction('Попытка редактирования пользователя', "Редактирование запрещено");
			} else {
                $id = (int)$_POST['id'];
                $username = htmlspecialchars(trim($_POST['username']));
                $email = htmlspecialchars(trim($_POST['email']));
                $isadmin = (int)$_POST['isadmin']; // Получаем 0, 7 или 9
                
                if ($user->updateUser($id, $username, $email, $isadmin)) {
					$roleName = getRoleName($isadmin);
                    logAction('Редактирование пользователя', "ID: $id, Новые данные: $username, $email, Роль: $roleName");
                    $_SESSION['admin_message'] = 'Пользователь успешно обновлен';
                } else {
                    logAction('Ошибка редактирования пользователя', "Не удалось обновить пользователя ID $id");
                    $_SESSION['admin_error'] = 'Ошибка при обновлении пользователя';
                }
			}
                break;
                
            case 'delete_user':
			if (!hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = ("Недостаточно прав");
				logAction('Попытка удаления пользователя', "Удаление запрещено");
			} else {
                $id = (int)$_POST['id'];
                if ($user->deleteUser($id)) {
                    logAction('Удаление пользователя', "Пользователь ID $id удален");
                    $_SESSION['admin_message'] = 'Пользователь успешно удален';
                } else {
                    logAction('Ошибка удаления пользователя', "Не удалось удалить пользователя ID $id");
                    $_SESSION['admin_error'] = 'Ошибка при удалении пользователя';
                }
			}		
                break;
                
            case 'add_tag':
			if (!hasPermission(7, $currentUserRole)) {
				$_SESSION['admin_error'] = ("Недостаточно прав");
				logAction('Попытка добавления тега', "Добавление запрещено");
			} else {
                $name = htmlspecialchars(trim($_POST['name']));
                if ($news->addTag($name)) {
                    logAction('Добавление тега', "Добавлен новый тег: $name");
                    $_SESSION['admin_message'] = 'Тег успешно добавлен';
                } else {
                    logAction('Ошибка добавления тега', "Не удалось добавить тег: $name");
                    $_SESSION['admin_error'] = 'Ошибка при добавлении тега';
                }
			}
                break;
                
            case 'delete_tag':
			if (!hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = ("Недостаточно прав");
				logAction('Попытка удаления тега', "Удаление запрещено");
			} else {
                $id = (int)$_POST['id'];
                if ($news->deleteTag($id)) {
                    logAction('Удаление тега', "Тег ID $id удален");
                    $_SESSION['admin_message'] = 'Тег успешно удален';
                } else {
                    logAction('Ошибка удаления тега', "Не удалось удалить тег ID $id");
                    $_SESSION['admin_error'] = 'Ошибка при удалении тега';
                }
			}
                break;
                
            case 'change_template':
			if (!hasPermission(9, $currentUserRole)) {
				$_SESSION['admin_error'] = ("Недостаточно прав");
				logAction('Попытка смены шаблона', "Смена шаблона запрещена");
			} else {
                $templatesDir = 'templates';
                $configPath = 'config/config.php';
                $availableTemplates = $template->getAvailableTemplates($templatesDir);
                $newTemplate = $_POST['template'] ?? '';
                
                if ($template->changeTemplate($newTemplate, $availableTemplates, $config, $configPath)) {
                    $_SESSION['admin_message'] = "Шаблон <b>{$newTemplate}</b> успешно активирован!";
                } else {
                    $_SESSION['admin_error'] = "Ошибка при смене шаблона";
                }
			}
                header("Location: ?section=template_settings");
				exit();
                break;
			
			case 'delete_log':
				if (!hasPermission(9, $currentUserRole)) {
					$_SESSION['admin_error'] = "Недостаточно прав";
					logAction('Попытка удаления лога', "Удаление лога запрещено");
				} else {
					$id = (int)$_POST['id'];
					try {
						$stmt = $pdo->prepare("DELETE FROM `{$dbPrefix}admin_logs` WHERE id = ?");
						if ($stmt->execute([$id])) {
							$_SESSION['admin_message'] = 'Запись лога успешно удалена';
						} else {
							$_SESSION['admin_error'] = 'Ошибка при удалении записи лога';
						}
					} catch (PDOException $e) {
						$_SESSION['admin_error'] = 'Ошибка базы данных при удалении лога';
					}
				}
				break;
			case 'create_backup':
				if (!hasPermission(9, $currentUserRole)) {
					$_SESSION['admin_error'] = "Недостаточно прав";
				} else {
					$backup = 'backup_'.date('Y-m-d_H-i-s').'.sql';
					$backupFile = dbBackup(__DIR__.'/'. $backupDir . $backup, false);
					if ($backupFile) {
						$_SESSION['admin_message'] = "Резервная копия успешно создана: " . basename($backup);
						logAction('Создание резервной копии', 'Файл: ' . basename($backup));
					} else {
						$_SESSION['admin_error'] = "Ошибка при создании резервной копии";
						
					}
				}
				header("Location: ?section=backups");
				break;
				
			case 'download_backup':
				if (!hasPermission(9, $currentUserRole)) {
					$_SESSION['admin_error'] = "Недостаточно прав";
				} else {
					$file = __DIR__ . '/'. $backupDir . basename($_GET['file']);
					if (file_exists($file)) {
						header('Content-Type: application/octet-stream');
						header('Content-Disposition: attachment; filename="' . basename($file) . '"');
						readfile($file);
						exit;
					}
				}
				break;

			case 'restore_backup':
				if (!hasPermission(9, $currentUserRole)) {
					$_SESSION['admin_error'] = "Недостаточно прав";
				} else {
					$file = __DIR__ . '/'. $backupDir . basename($_POST['file']);
					if (file_exists($file)) {
						$command = "mysql --user={$config['db_user']} --password={$config['db_pass']} --host={$config['host']} {$config['database']} < {$file}";
						system($command, $output);
						
						if ($output === 0) {
							$_SESSION['admin_message'] = 'База данных успешно восстановлена';
							logAction('Восстановление БД', 'Из файла: ' . basename($file));
						} else {
							$_SESSION['admin_error'] = 'Ошибка при восстановлении';
						}
					}
				}
				break;

			case 'delete_backup':
				if (!hasPermission(9, $currentUserRole)) {
					$_SESSION['admin_error'] = "Недостаточно прав";
				} else {
					$file = __DIR__ . '/'. $backupDir . basename($_POST['file']);
					if (file_exists($file)) {
						unlink($file);
						$_SESSION['admin_message'] = 'Резервная копия удалена';
						logAction('Удаление резервной копии', 'Файл: ' . basename($file));
					} else {
						$_SESSION['admin_error'] = 'Файл не найден';
					}
				}
				break;

			case 'update_backup_settings':
				if (!hasPermission(9, $currentUserRole)) {
					$_SESSION['admin_error'] = "Недостаточно прав";
				} else {
					$config['max_backups'] = (int)$_POST['max_backups'];
					$config['backup_schedule'] = $_POST['backup_schedule'];
					
					// Сохраняем обновленный конфиг
					file_put_contents(__DIR__ . '/config/config.php', "<?php\nreturn " . var_export($config, true) . ";\n");
					
					$_SESSION['admin_message'] = 'Настройки резервного копирования обновлены';
					logAction('Изменение настроек бэкапа', 'Макс. копий: ' . $config['max_backups'] . ', Расписание: ' . $config['backup_schedule']);
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
					$_SESSION['admin_error'] = 'Запись блога не найдена';
					header("Location: ?section=blogs");
					exit;
				}
				
				// Загрузка тегов для этой записи
				$editBlog['tag_ids'] = $news->getTagsByNewsId($id);
				/*$stmt = $pdo->prepare("SELECT tag_id FROM {$dbPrefix}blogs_tags WHERE blogs_id = ?");
				$stmt->execute([$id]);
				$editBlog['tag_ids'] = $stmt->fetchAll(PDO::FETCH_COLUMN);*/
				
				$template->assign('editBlog', $editBlog);
			}
			
			$blogs = $news->getAllAdm(0, 0);
			$allTags = $pdo->query("SELECT * FROM `{$dbPrefix}tags` ORDER by `name`")->fetchAll(PDO::FETCH_ASSOC);
			
			$template->assign('blogs', $blogs);
			$template->assign('allTags', $allTags);
			
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
            
            if (!in_array($currentTemplate, $availableTemplates)) {
                $currentTemplate = 'simple';
                $config['templ'] = 'simple';
                file_put_contents($configPath, "<?php\nreturn ".var_export($config, true).";\n");
            }
            
            $template->assign('templates', $availableTemplates);
            $template->assign('currentTemplate', $currentTemplate);
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
            break;
			
		case 'server_info':
			// Информация о сервере
			$serverInfo = [
				'Веб-сервер' => $_SERVER['SERVER_SOFTWARE'] ?? 'Неизвестно',
				'PHP версия' => phpversion(),
				'MySQL версия' => $pdo->query("SELECT version()")->fetchColumn(),
				'ОС сервера' => php_uname(),
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
			break;
	
		case 'backups':
			//$backupDir = __DIR__ . '/admin/backups/';
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
			break;
    }
    
    echo $template->render('admin/index.tpl');
    
} catch (Exception $e) {
    logAction('Ошибка системы', "Ошибка в админ-панели: " . $e->getMessage());
    error_log($e->getMessage());
    header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
    $template->assign('pageTitle', '500 - Внутренняя ошибка сервера');
    echo $template->render('admin/500.tpl');
    exit;
}

$finish = microtime(1);
echo 'generation time: ' . round($finish - $start, 5) . ' сек';