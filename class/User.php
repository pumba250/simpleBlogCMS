<?php
/**
 * User management class - handles authentication, registration, permissions and profile operations
 * 
 * @package    SimpleBlog
 * @subpackage Core
 * @version    0.7.0
 * @author     pumba250
 * @license    MIT License
 * @copyright  2023 SimpleBlog
 * 
 * @method bool hasPermission(int $requiredRole, int $currentRole) Check user access level
 * @method bool register(string $username, string $password, string $email) Process new user registration
 * @method bool verifyEmail(string $token) Confirm email address validity
 * @method bool sendPasswordReset(string $email) Initiate password recovery
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
            error_log($e->getMessage());
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

    private function sendVerificationEmail($username, $email, $token) {
        global $config;
        Mailer::init($config);
        
        $verificationUrl = "http://{$_SERVER['HTTP_HOST']}/?action=verify_email&token=$token";
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
        
        $resetUrl = "http://{$_SERVER['HTTP_HOST']}/?action=reset_password&token=$token";
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

	public function login($username, $password) {
		global $dbPrefix;
		
		// Rate limiting check
		if ($_SESSION['login_attempts'] >= 5 && time() - $_SESSION['last_attempt'] < 3600) {
			flash(Lang::get('too_many_attempts', 'core'));
			return false;
		}
		if ($_SESSION['login_attempts'] >= 3) {
			sleep(min(10, $_SESSION['login_attempts'])); // Экспоненциальная задержка
		}

		$stmt = $this->pdo->prepare("SELECT * FROM {$dbPrefix}users WHERE username = ?");
		$stmt->execute([$username]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($user && password_verify($password, $user['password'])) {
			// Reset attempts on successful login
			$_SESSION['login_attempts'] = 0;
			$_SESSION['user_id'] = $user['id'];
			$_SESSION['username'] = $user['username'];
			$_SESSION['isadmin'] = $user['isadmin'];
			return $user;
		}
		
		// Increment attempts on failure
		$_SESSION['login_attempts']++;
		$_SESSION['last_attempt'] = time();
		return false;
	}
	/*public function generateCsrfToken() {
		if (empty($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		return $_SESSION['csrf_token'];
	}

	public function validateCsrfToken($token) {
		return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
	}*/
	public function logout() {
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		$_SESSION = [];
		
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
