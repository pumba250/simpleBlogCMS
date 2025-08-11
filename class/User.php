<?php
if (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен'); }
/**
 * Класс для работы с пользователями и аутентификацией
 * 
 * @package    SimpleBlog
 * @subpackage Core
 * @category   Authentication
 * @version    0.9.4
 * 
 * @method void   __construct(PDO $pdo) Инициализирует систему пользователей
 * @method array  getAllUsers() Получает список всех пользователей
 * @method bool   hasPermission(int $requiredRole, int $currentRole) Проверяет права доступа
 * @method bool   updateUser(int $id, string $username, string $email, int $isadmin) Обновляет данные пользователя
 * @method bool   deleteUser(int $id) Удаляет пользователя
 * @method bool   register(string $username, string $password, string $email) Регистрирует нового пользователя
 * @method bool   getUserById(int $id) Получает данные пользователя по ID
 * @method bool   verifyEmail(string $token) Подтверждает email пользователя
 * @method bool   sendPasswordReset(string $email) Отправляет ссылку для сброса пароля
 * @method bool   resetPassword(string $token, string $newPassword) Сбрасывает пароль пользователя
 * @method array|bool login(string $username, string $password) Выполняет вход пользователя
 * @method void   logout() Выполняет выход пользователя
 * @method void   startSession() Инициализирует сессию (приватный)
 * @method bool   sendVerificationEmail(string $username, string $email, string $token) Отправляет email подтверждения (приватный)
 * @method bool   sendResetEmail(string $username, string $email, string $token) Отправляет email сброса пароля (приватный)
 * 
 * Вспомогательные функции для работы с пользователями
 * 
 * @method void   flash(?string $message = null) Устанавливает/выводит flash-сообщение
 * @method string formatDate(string $dateString) Форматирует дату
 * @method string getRandomColor() Генерирует случайный цвет
 * @method void   setFlash(string $type, string $message) Устанавливает типизированное flash-сообщение
 * @method string e(string $string) Экранирует HTML-спецсимволы (аналог htmlspecialchars)
 */
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
		$this->startSession();
}

private function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        // Initialize rate limiting if not set
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt'] = 0;
        }
    }
}
    public function getAllUsers() {
	global $dbPrefix;
        $stmt = $this->pdo->prepare("SELECT id, username, email, isadmin, created_at FROM {$dbPrefix}users ORDER BY created_at DESC");
        $stmt->execute();
		return $stmt->fetchAll();
    }
    // Функция проверки прав
	public function hasPermission($requiredRole, $currentRole) {
		return $currentRole >= $requiredRole;
	}
    /**
     * Обновить пользователя
     */
    public function updateUser($id, $username, $email, $isadmin) {
	global $dbPrefix;
        try {
            $stmt = $this->pdo->prepare("UPDATE {$dbPrefix}users SET username = ?, email = ?, isadmin = ? WHERE id = ?");
            return $stmt->execute([$username, $email, $isadmin, $id]);
        } catch (Exception $e) {
            error_log(sprintf(
    '[%s] Error in %s:%d - %s',
    get_class($e),
    $e->getFile(),
    $e->getLine(),
    $config['debug'] ? $e->getMessage() : 'Operation failed'
));
            return false;
        }
    }
    
    /**
     * Удалить пользователя
     */
    public function deleteUser($id) {
	global $dbPrefix;
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$dbPrefix}users WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    public function register($username, $password, $email) {
        global $dbPrefix, $config;
        $query = $this->pdo->query("SELECT COUNT(*) FROM {$dbPrefix}users");
        $userCount = $query->fetchColumn();

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $isAdmin = ($userCount == 0) ? 9 : 0;
        $verificationToken = bin2hex(random_bytes(32));

        $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) FROM {$dbPrefix}users WHERE username = ? OR email = ?");
        $stmtCheck->execute([$username, $email]);
        $exists = $stmtCheck->fetchColumn();

        if ($exists) {
            flash(Lang::get('user_exists', 'core'));
            return false;
        }
		if (strlen($password) < 6 || !preg_match('/[A-Z]/', $password) || 
			!preg_match('/[0-9]/', $password)) {
			throw new Exception("Password does not meet complexity requirements");
		}

        $stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}users (username, password, email, isadmin, verification_token) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$username, $hash, $email, $isAdmin, $verificationToken])) {
            $this->sendVerificationEmail($username, $email, $verificationToken);
            flash(Lang::get('reg_success_verify', 'core'));
            return true;
        }
        
        return false;
    }
	public function getUserById($userId) {
		global $dbPrefix;
		$stmt = $this->pdo->prepare("
			SELECT id, username, email, isadmin, created_at, avatar 
			FROM {$dbPrefix}users 
			WHERE id = ? 
			LIMIT 1
		");
		$stmt->execute([$userId]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
    private function sendVerificationEmail($username, $email, $token) {
        global $config;
        Mailer::init($config);
        
        $verificationUrl = "https://{$_SERVER['HTTP_HOST']}/?action=verify_email&token=$token";
        $subject = Lang::get('verify_email_subject', 'core');
        
        $message = '
            <p>'.Lang::get('hiuser', 'main').' <strong>'.htmlspecialchars($username).'</strong>,</p>
            
            <p>'.Lang::get('thank', 'core').' '.htmlspecialchars($config['home_title'] ?? 'our site').'. 
            '.Lang::get('verify_email_body', 'core').'</p>
            
            <p style="text-align: center; margin: 25px 0;">
                <a href="'.htmlspecialchars($verificationUrl).'" class="button">
                    '.Lang::get('verify', 'core').'
                </a>
            </p>
            
            <p>'.Lang::get('reset_email_body2', 'core').'<br>
            <code>'.htmlspecialchars($verificationUrl).'</code></p>
            
            <p>'.Lang::get('verify_email_body2', 'core').'</p>
            
            <p>'.Lang::get('reset_email_body4', 'core').',<br>'.htmlspecialchars($config['home_title'] ?? 'simpleBlog').'</p>
        ';

        return Mailer::send($email, $subject, $message);
    }

    public function verifyEmail($token) {
        global $dbPrefix;
        
        // Check if token exists
        $stmt = $this->pdo->prepare("SELECT id FROM {$dbPrefix}users WHERE verification_token = ? AND email_verified = 0");
        $stmt->execute([$token]);
        $userId = $stmt->fetchColumn();

        if ($userId) {
            // Mark email as verified
            $stmt = $this->pdo->prepare("UPDATE {$dbPrefix}users SET email_verified = 1, verification_token = NULL WHERE id = ?");
            return $stmt->execute([$userId]);
        }
        
        return false;
    }

    public function sendPasswordReset($email) {
        global $dbPrefix, $config;
        
        $stmt = $this->pdo->prepare("SELECT id, username FROM {$dbPrefix}users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $resetToken = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
            
            $stmt = $this->pdo->prepare("UPDATE {$dbPrefix}users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            if ($stmt->execute([$resetToken, $expires, $user['id']])) {
                return $this->sendResetEmail($user['username'], $email, $resetToken);
            }
        }
        
        return false;
    }

    private function sendResetEmail($username, $email, $token) {
        global $config;
        Mailer::init($config);
        
        $resetUrl = "https://{$_SERVER['HTTP_HOST']}/?action=reset_password&token=$token";
        $subject = Lang::get('reset_email_subject', 'core');
        
        $message = '
            <p>'.Lang::get('hiuser', 'main').' <strong>'.htmlspecialchars($username).'</strong>,</p>
            
            <p>'.Lang::get('reset_email_body', 'core').'</p>
            
            <p style="text-align: center; margin: 25px 0;">
                <a href="'.htmlspecialchars($resetUrl).'" class="button">
                    '.Lang::get('reset_password', 'core').'
                </a>
            </p>
            
            <p>'.Lang::get('reset_email_body2', 'core').'<br>
            <code>'.htmlspecialchars($resetUrl).'</code></p>
            
            <p>'.Lang::get('reset_email_body3', 'core').'</p>
            
            <p>'.Lang::get('reset_email_body4', 'core').',<br>'.htmlspecialchars($config['home_title'] ?? 'simpleBlog').'</p>
        ';

        return Mailer::send($email, $subject, $message);
    }

    public function resetPassword($token, $newPassword) {
        global $dbPrefix;
        
        // Check if token is valid and not expired
        $stmt = $this->pdo->prepare("SELECT id FROM {$dbPrefix}users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $userId = $stmt->fetchColumn();

        if ($userId) {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("UPDATE {$dbPrefix}users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            return $stmt->execute([$hash, $userId]);
        }
        
        return false;
    }
	/**
     * Обновляет данные профиля пользователя
     */
    public function updateProfile($userId, $username, $email, $avatar = null) {
        global $dbPrefix;
        
        try {
            if ($avatar) {
                $stmt = $this->pdo->prepare("UPDATE {$dbPrefix}users SET username = ?, email = ?, avatar = ? WHERE id = ?");
                return $stmt->execute([$username, $email, $avatar, $userId]);
            } else {
                $stmt = $this->pdo->prepare("UPDATE {$dbPrefix}users SET username = ?, email = ? WHERE id = ?");
                return $stmt->execute([$username, $email, $userId]);
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Добавляет/обновляет привязку социальной сети
     */
    public function setSocialLink($userId, $socialType, $socialId, $username = null) {
        global $dbPrefix;
        
        try {
            // Проверяем существующую привязку
            $stmt = $this->pdo->prepare("SELECT id FROM {$dbPrefix}user_social WHERE user_id = ? AND social_type = ?");
            $stmt->execute([$userId, $socialType]);
            
            if ($stmt->fetch()) {
                // Обновляем существующую
                $stmt = $this->pdo->prepare("UPDATE {$dbPrefix}user_social SET social_id = ?, social_username = ? WHERE user_id = ? AND social_type = ?");
                return $stmt->execute([$socialId, $username, $userId, $socialType]);
            } else {
                // Добавляем новую
                $stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}user_social (user_id, social_type, social_id, social_username) VALUES (?, ?, ?, ?)");
                return $stmt->execute([$userId, $socialType, $socialId, $username]);
            }
        } catch (Exception $e) {
            error_log("Social link error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получает привязанные социальные сети пользователя
     */
    public function getSocialLinks($userId) {
        global $dbPrefix;
        
        $stmt = $this->pdo->prepare("SELECT social_type, social_id, social_username FROM {$dbPrefix}user_social WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Удаляет привязку социальной сети
     */
    public function removeSocialLink($userId, $socialType) {
        global $dbPrefix;
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$dbPrefix}user_social WHERE user_id = ? AND social_type = ?");
            return $stmt->execute([$userId, $socialType]);
        } catch (Exception $e) {
            error_log("Remove social link error: " . $e->getMessage());
            return false;
        }
    }

	public function login($username, $password) {
		global $dbPrefix;
		
		// Rate limiting check
		$security = [
			'max_attempts' => 5,         // Макс. попыток до полной блокировки
			'block_time' => 3600,        // Время блокировки (1 час)
			'initial_delay' => 10,       // Задержка после 1-й попытки (сек)
			'progressive_delay' => 30    // Задержка после 3+ попыток (сек)
		];

		// Проверка блокировки
		if ($_SESSION['login_attempts'] >= $security['max_attempts'] && 
			time() - $_SESSION['last_attempt'] < $security['block_time']) {
			flash(Lang::get('too_many_attempts', 'core') . ' Попробуйте через ' . 
				  ceil(($security['block_time'] - (time() - $_SESSION['last_attempt'])) / 60) . ' минут.');
			return false;
		}

		// Прогрессивная задержка
		if ($_SESSION['login_attempts'] >= 1) {
			$delay = ($_SESSION['login_attempts'] >= 3) ? $security['progressive_delay'] : $security['initial_delay'];
			sleep($delay);
		}

		$stmt = $this->pdo->prepare("SELECT * FROM {$dbPrefix}users WHERE username = ?");
		$stmt->execute([$username]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($user && password_verify($password, $user['password'])) {
			session_regenerate_id(true);
			$_SESSION = [];
			$_SESSION['user_id'] = $user['id'];
			$_SESSION['username'] = $user['username'];
			$_SESSION['isadmin'] = $user['isadmin'];
			$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
			$_SESSION['created_at'] = time();
			// Сбрасываем счетчик попыток
			$_SESSION['login_attempts'] = 0;
			Cache::clear();
			return $user;
		}
		
		// Увеличиваем счетчик попыток
		$_SESSION['login_attempts']++;
		$_SESSION['last_attempt'] = time();
		return false;
	}

	public function logout() {
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		$_SESSION = [];
		Cache::clear();
		session_destroy();
	}
}
function flash(?string $message = null){
    if ($message) {
        $_SESSION['flash'] = $message;
    } else {
        if (!empty($_SESSION['flash'])) { ?>
          <div style="color: red;">
              <?= htmlspecialchars($_SESSION['flash'] ?? '') ?>
          </div>
        <?php }
        unset($_SESSION['flash']);
    }
}

/**
 * Format date to readable format
 */
function formatDate($dateString) {
    $date = new DateTime($dateString);
    return $date->format('d.m.Y H:i');
}

/**
 * Generate random color for default avatars
 */
function getRandomColor() {
    $colors = ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c'];
    return $colors[array_rand($colors)];
}


/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * HTML special chars shortcut
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
