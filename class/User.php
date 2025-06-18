<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function getAllUsers() {
	global $dbPrefix;
        $stmt = $this->pdo->query("SELECT id, username, email, isadmin, created_at FROM {$dbPrefix}users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
	global $dbPrefix;
    $query = $this->pdo->query("SELECT COUNT(*) FROM {$dbPrefix}users");
    $userCount = $query->fetchColumn();

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $isAdmin = ($userCount == 0) ? 9 : 0;

    $stmtCheck = $this->pdo->prepare("SELECT COUNT(*) FROM {$dbPrefix}users WHERE username = ? OR email = ?");
    $stmtCheck->execute([$username, $email]);
    $exists = $stmtCheck->fetchColumn();

    if ($exists) {
        flash('Имя пользователя или email уже заняты'); // Логика обработки ошибки: имя пользователя или email уже заняты
        return false; // Можно выбросить исключение или вернуть ошибку
    }

    $stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}users (username, password, email, isadmin) VALUES (?, ?, ?, ?)");
	flash('Регистрация успешна, авторизуйтесь!');
    return $stmt->execute([$username, $hash, $email, $isAdmin]);
}

	public function login($username, $password) {
	global $dbPrefix;
    $stmt = $this->pdo->prepare("SELECT * FROM {$dbPrefix}users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
		$_SESSION['isadmin'] = $user['isadmin'];
        return $user;
    }
    return false;
}
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
