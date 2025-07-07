<?php
/**
 * Основной класс для обработки запросов.
 * 
 * Этот класс отвечает за обработку POST-запросов, включая регистрацию, авторизацию, 
 * отправку комментариев, голосования, обратную связь и сброс пароля. 
 * Также обеспечивает проверку CSRF-токена и валидацию данных.
 * 
 * @version 0.8.2
 * @author pumba250
 */
class Core {
    private $pdo;
    private $config;
    private $user;
    private $news;
    private $comments;
    private $votes;
    private $contact;
    private $parse;
    private $template;

    public function __construct($pdo, $config, $template) {
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
    public function handlePostRequest() {
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
    private function handleCommentPost() {
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
    private function handleVotePost() {
        $newsId = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
        
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
    private function handleRegistration() {
        $this->user->register($_POST['username'], $_POST['password'], $_POST['email']);
        header("Location: /");
        exit;
    }

    /**
     * Обработка авторизации
     */
    private function handleLogin() {
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
    private function handleContact() {
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
    private function handlePasswordResetRequest() {
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
    private function handlePasswordReset() {
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
}