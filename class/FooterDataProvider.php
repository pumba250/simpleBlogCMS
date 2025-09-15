<?php

if (!defined('IN_SIMPLECMS')) {
    die('Прямой доступ запрещен');
}
/**
 * Класс для подготовки данных футера
 *
 * @package    SimpleBlog
 * @subpackage Core
 * @category   Views
 * @version    0.9.8
 *
 * @method void __construct(News $news, User $user, Template $template, array $config) Инициализирует зависимости
 * @method array prepareFooterData()                                                 Подготавливает все данные для футера
 * @method string prepareUserSection()                                               Подготавливает секцию пользователя
 * @method string prepareSearchForm()                                                Подготавливает форму поиска
 * @method string prepareRecentNewsList()                                            Подготавливает список последних новостей
 * @method string prepareTagsList()                                                  Подготавливает список тегов
 * @method string prepareFooterContent()                                             Подготавливает контент футера
 */
class FooterDataProvider
{
    private $news;
    private $user;
    private $template;
    private $config;

    public function __construct(News $news, User $user, Template $template, array $config)
    {
        $this->news = $news;
        $this->user = $user;
        $this->template = $template;
        $this->config = $config;
    }

    public function prepareFooterData()
    {
        return [
            'userSection' => $this->prepareUserSection(),
            'searchForm' => $this->prepareSearchForm(),
            'recentNewsList' => $this->prepareRecentNewsList(),
            'tagsList' => $this->prepareTagsList(),
            'footerContent' => $this->prepareFooterContent(),
            'searchTitle' => Lang::get('search', 'main'),
            'recentNewsTitle' => Lang::get('recent_news', 'main'),
            'tagsTitle' => Lang::get('tags', 'main')
        ];
    }

    private function prepareUserSection()
    {
        $currentUser = $_SESSION['user'] ?? null;
        $data = [
            'auth_error' => $_SESSION['auth_error'] ?? null,
            'csrf_token' => $_SESSION['csrf_token'],
            'captcha_image_url' => '/class/captcha.php'
        ];

        if ($currentUser) {
            return $this->template->render('partials/footer/user_logged_in.tpl', array_merge($data, [
                'userData' => $currentUser,
            ]));
        } else {
            return $this->template->render('partials/footer/user_not_logged_in.tpl', $data);
        }
    }

    private function prepareSearchForm()
    {
        return $this->template->render('partials/footer/search_form.tpl', [
            'searchQuery' => $_GET['search'] ?? ''
        ]);
    }

    private function prepareRecentNewsList()
	{
        $lastThreeNews = $this->news->getLastThreeNews();
        //var_dump('Recent News Data: ' . print_r($lastThreeNews, true));
        if (empty($lastThreeNews)) {
            return 'Нет новостей'; // или можно вернуть сообщение "Нет новостей"
        }
        return $this->template->render('partials/footer/recent_news_list.tpl', [
            'newsItems' => $lastThreeNews
        ]);
    }

    private function prepareTagsList()
    {
        $allTags = $this->news->getAllTags();
        if (empty($allTags)) {
            return 'Нет тегов'; // или можно вернуть сообщение "Нет тегов"
        }
        return $this->template->render('partials/footer/tags_list.tpl', [
            'allTags' => $allTags
        ]);
    }

    private function prepareFooterContent()
    {
        return $this->template->render('partials/footer/footer_content.tpl', [
            'currentYear' => date("Y"),
            'serverName' => htmlspecialchars($_SERVER['SERVER_NAME']),
            'powered' => $this->config['powered'],
            'version' => $this->config['version']
        ]);
    }
}
