<?php
if (!defined('IN_SIMPLECMS')) {
    die('Прямой доступ запрещен');
}
/**
 * Основной класс для обработки запросов
 * 
 * @package    SimpleBlog
 * @subpackage Core
 * @category   System
 * @version    0.9.8
 * 
 * @method void __construct(PDO $pdo, array $config, Template $template) Инициализирует зависимости
 * @method void handlePostRequest() Обрабатывает все POST-запросы (основной публичный метод)
 * @method void handleCommentPost() Обрабатывает отправку комментария (приватный)
 * @method void handleVotePost() Обрабатывает голосования (приватный)
 * @method void handleRegistration() Обрабатывает регистрацию пользователя (приватный)
 * @method void handleLogin() Обрабатывает авторизацию (приватный)
 * @method void handleContact() Обрабатывает форму обратной связи (приватный)
 * @method void handlePasswordResetRequest() Обрабатывает запрос сброса пароля (приватный)
 * @method void handlePasswordReset() Обрабатывает сброс пароля (приватный)
 * @method void handleProfileUpdate() Обрабатывает обновление профиля (приватный)
 */
class Core
{
    private $pdo;
    private $config;
    private $user;
    private $news;
    private $comments;
    private $votes;
    private $contact;
    private $parse;
    private $template;

    public function __construct($pdo, $config, $template)
    {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->template = $template;
        
        // Инициализация зависимостей
        $this->user = new User($pdo, $template);
        $this->votes = new Votes($pdo);
        $this->contact = new Contact($pdo);
        $this->news = new News($pdo);
        $this->comments = new Comments($pdo);
        $this->parse = new Parse();
    }

    /**
     * Обработка POST-запросов
     */
    public function handlePostRequest()
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die(Lang::get('invalid_csrf', 'core'));
        }

        // Обработка комментариев
        if (isset($_POST['user_text'])) {
            $this->handleCommentPost();
            return;
        }

        // Обработка голосований
        if (isset($_POST['vote_article']) || isset($_POST['vote_comment'])) {
            $this->handleVotePost();
            return;
        }

        // Обработка обновления профиля
        if (isset($_POST['update_profile'])) {
            $this->handleProfileUpdate();
            return;
        }

        // Обработка действий
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'register':
                    $this->handleRegistration();
                    break;
                case 'login':
                    $this->handleLogin();
                    break;
                case 'contact':
                    $this->handleContact();
                    break;
                case 'request_reset':
                    $this->handlePasswordResetRequest();
                    break;
                case 'reset_password':
                    $this->handlePasswordReset();
                    break;
            }
        }
    }

    /**
     * Обработка отправки комментария
     */
    private function handleCommentPost()
    {
        $themeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $userName = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : $_POST['user_name'];
        $userText = $_POST['user_text'];
        
        if ($themeId > 0 && !empty($userName) && !empty($userText)) {
            $this->comments->addComment(0, 0, $themeId, $userName, $userText);
            header("Location: ?id=" . $themeId);
            exit;
        }
    }

    /**
     * Обработка голосования
     */
    private function handleVotePost()
    {
        $newsId = isset($_POST['id']) 
            ? (int)$_POST['id'] 
            : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
        
        if ($newsId === 0) {
            die(Lang::get('not_id', 'core'));
        }
        
        $newsItem = $this->news->getNewsById($newsId);
        if (!$newsItem) {
            die(Lang::get('not_news', 'core'));
        }
        
        if (isset($_POST['vote_article']) && isset($_SESSION['user']['id'])) {
            $voteType = $_POST['vote_article'];
            $this->votes->voteArticle($newsId, $voteType, $_SESSION['user']['id']);
            header("Location: ?id=$newsId");
            exit;
        }
        
        if (isset($_POST['vote_comment']) && isset($_SESSION['user']['id'])) {
            list($commentId, $voteType) = explode('_', $_POST['vote_comment']);
            $this->votes->voteComment($commentId, $voteType, $_SESSION['user']['id']);
            header("Location: ?id=$newsId");
            exit;
        }
    }

    /**
     * Обработка регистрации
     */
    private function handleRegistration()
    {
        $this->user->register($_POST['username'], $_POST['password'], $_POST['email']);
        header("Location: /");
        exit;
    }

    /**
     * Обработка авторизации
     */
    private function handleLogin()
    {
        $captchaValid = isset($_POST['captcha']) && $_POST['captcha'] == $_SESSION['captcha_answer'];
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (!$captchaValid) {
            $_SESSION['auth_error'] = Lang::get('not_answer', 'core');
        } elseif (empty($username) || empty($password)) {
            $_SESSION['auth_error'] = Lang::get('all_field', 'core');
        } else {
            $userData = $this->user->login($username, $password);
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

    /**
     * Обработка формы обратной связи
     */
    private function handleContact()
    {
        if (isset($_POST['captcha']) && $_POST['captcha'] == $_SESSION['captcha_answer']) {
            if ($this->contact->saveMessage($_POST['name'], $_POST['email'], $_POST['message'])) {
                $_SESSION['flash'] = Lang::get('msg_send', 'core');
            } else {
                $_SESSION['flash'] = Lang::get('msg_error', 'core');
            }
        } else {
            $_SESSION['flash'] = Lang::get('not_answer', 'core');
        }
    }

    /**
     * Обработка запроса на сброс пароля
     */
    private function handlePasswordResetRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
            if ($this->user->sendPasswordReset($_POST['email'])) {
                $_SESSION['flash'] = Lang::get('reset_email_sent', 'core');
            } else {
                $_SESSION['flash'] = Lang::get('reset_email_error', 'core');
            }
            header('Location: /');
            exit;
        }
    }

    /**
     * Обработка сброса пароля
     */
    private function handlePasswordReset()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'], $_POST['password'])) {
            if ($this->user->resetPassword($_POST['token'], $_POST['password'])) {
                $_SESSION['flash'] = Lang::get('password_reset_success', 'core');
            } else {
                $_SESSION['flash'] = Lang::get('password_reset_error', 'core');
            }
            header('Location: /');
            exit;
        }
    }

    /**
     * Обработка обновления профиля
     */
    private function handleProfileUpdate()
    {
        if (!isset($_SESSION['user']['id'])) {
            $_SESSION['flash'] = Lang::get('auth_required', 'core');
            header('Location: /?action=login');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currentPassword = trim($_POST['current_password'] ?? '');
        
        // Обработка загрузки аватара
        $avatar = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            try {
                // Используем upload_dir из конфига
                $uploadDir = $this->config['upload_dir'] . 'avatars/';
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
        if ($this->user->updateProfile($_SESSION['user']['id'], $username, $email, $avatar, $emailChanged ? $currentPassword : null)) {
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
            header('Location: /?action=profile');
            exit;
        }
    }
}