<?php
if (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен'); }
/**
 * Класс для работы с комментариями
 * 
 * @package    SimpleBlog
 * @subpackage Models
 * @category   Content
 * @version    0.9.1
 * 
 * @method int addComment(int $parentId, int $fParent, int $themeId, string $userName, string $userText) Добавляет комментарий
 * @method bool editComment(int $id, string $userText) Редактирует комментарий
 * @method bool deleteComment(int $id) Удаляет комментарий
 * @method array getComments(int $themeId, int $limit = 10, int $offset = 0, bool $moderatedOnly = true) Получает комментарии
 * @method array AllComments(int $limit = 10, int $offset = 0) Получает все комментарии (админка)
 * @method int countComments(int $themeId, bool $moderatedOnly = true) Считает комментарии
 * @method int countAllComments() Считает все комментарии
 * @method bool toggleModeration(int $id) Переключает модерацию
 * @method bool voteComment(int $id, string $voteType) Голосование за комментарий
 * @method bool approveComment(int $commentId) Одобряет комментарий
 * @method bool rejectComment(int $commentId) Отклоняет комментарий
 * @method array getPendingComments() Получает комментарии на модерации
 * @method int countPendingComments() Считает комментарии на модерации
 * @method bool getCommentStatus(int $id) Получает статус комментария
 */
class Comments {
    private $pdo;
	private $dbPrefix;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        global $dbPrefix;
        $this->dbPrefix = $dbPrefix;
    }

    public function addComment($parentId, $fParent, $themeId, $userName, $userText) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->dbPrefix}comments` (parent_id, f_parent, created_at, theme_id, user_name, user_text, moderation, plus, minus) VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0)");
        $stmt->execute([$parentId, $fParent, time(), $themeId, $userName, $userText]);
        return $this->pdo->lastInsertId();
    }

    public function editComment($id, $userText) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("UPDATE `{$this->dbPrefix}comments` SET user_text = ? WHERE id = ?");
        return $stmt->execute([$userText, $id]);
    }

    public function deleteComment($id) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->dbPrefix}comments` WHERE id = ?");
        return $stmt->execute([$id]);
    }

public function getCommentsPaginationData($themeId, $currentPage = 1, $moderatedOnly = true) {
    $total = $this->countComments($themeId, $moderatedOnly);
    global $config;
    return Pagination::calculate($total, 'comments_per_page', $currentPage, $config);
}

    /**
     * Получает комментарии для конкретной темы с пагинацией
     * @param int $themeId ID темы
     * @param int $limit Количество комментариев на странице
     * @param int $offset Смещение
     * @param bool $moderatedOnly Только модерированные комментарии (по умолчанию true)
     * @return array
     */
public function getComments($themeId, $limit = 10, $offset = 0, $moderatedOnly = true) {
    $moderationCondition = $moderatedOnly ? 'AND moderation = 1' : '';
    
    $stmt = $this->pdo->prepare("
        SELECT c.*, u.avatar, u.isadmin 
        FROM `{$this->dbPrefix}comments` c
        LEFT JOIN `{$this->dbPrefix}users` u ON c.user_name = u.username
        WHERE theme_id = ? $moderationCondition 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $stmt->bindValue(1, $themeId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function AllComments($limit = 10, $offset = 0) {
    $stmt = $this->pdo->prepare("SELECT c.*, b.title as post_title, u.username 
                                FROM `{$this->dbPrefix}comments` c 
                                LEFT JOIN `{$this->dbPrefix}blogs` b ON c.theme_id = b.id 
                                LEFT JOIN `{$this->dbPrefix}users` u ON c.user_name = u.username
                                ORDER BY c.created_at DESC
                                LIMIT ? OFFSET ?");
    // Явно указываем тип параметров
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    /**
     * Считает общее количество комментариев для темы
     * @param int $themeId ID темы
     * @param bool $moderatedOnly Только модерированные
     * @return int
     */
    public function countComments($themeId, $moderatedOnly = true) {
		global $dbPrefix;
        $moderationCondition = $moderatedOnly ? 'AND moderation = 1' : '';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->dbPrefix}comments` 
                                    WHERE theme_id = ? $moderationCondition");
        $stmt->execute([$themeId]);
        return $stmt->fetchColumn();
    }

    /**
     * Считает общее количество всех комментариев (для админки)
     * @return int
     */
    public function countAllComments() {
		global $dbPrefix;
        return $this->pdo->query("SELECT COUNT(*) FROM `{$this->dbPrefix}comments`")
                        ->fetchColumn();
    }

    public function toggleModeration($id) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("UPDATE `{$this->dbPrefix}comments` SET moderation = NOT moderation WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function voteComment($id, $voteType) {
		global $dbPrefix;
        $column = $voteType == 'plus' ? 'plus' : 'minus';
        $stmt = $this->pdo->prepare("UPDATE `{$this->dbPrefix}comments` SET $column = $column + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
	public function approveComment($commentId) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("UPDATE `{$this->dbPrefix}comments` SET moderation = 1 WHERE id = ?");
        $stmt->execute([$commentId]);
    }

    public function rejectComment($commentId) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->dbPrefix}comments` WHERE id = ?");
        $stmt->execute([$commentId]);
    }

    public function getPendingComments() {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->dbPrefix}comments` WHERE moderation = 0");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
	public function countPendingComments() {
		global $dbPrefix;
		return $this->pdo->query("SELECT COUNT(*) FROM `{$this->dbPrefix}comments` WHERE moderation = 0")->fetchColumn();
	}
public function getCommentStatus($id) {
    global $dbPrefix;
    $stmt = $this->pdo->prepare("SELECT moderation FROM `{$this->dbPrefix}comments` WHERE id = ?");
    $stmt->execute([$id]);
    return (bool)$stmt->fetchColumn();
}
}

?>