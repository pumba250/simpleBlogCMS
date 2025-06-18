<?php

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
        $stmt = $this->pdo->prepare("INSERT INTO `{$dbPrefix}comments` (parent_id, f_parent, created_at, theme_id, user_name, user_text, moderation, plus, minus) VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0)");
        $stmt->execute([$parentId, $fParent, time(), $themeId, $userName, $userText]);
        return $this->pdo->lastInsertId();
    }

    public function editComment($id, $userText) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("UPDATE `{$dbPrefix}comments` SET user_text = ? WHERE id = ?");
        return $stmt->execute([$userText, $id]);
    }

    public function deleteComment($id) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("DELETE FROM `{$dbPrefix}comments` WHERE id = ?");
        return $stmt->execute([$id]);
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
    $stmt = $this->pdo->prepare("SELECT * FROM `{$this->dbPrefix}comments` 
                                WHERE theme_id = ? $moderationCondition 
                                ORDER BY created_at DESC 
                                LIMIT ? OFFSET ?");
    // Явно указываем тип параметров (PDO::PARAM_INT)
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
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$dbPrefix}comments` 
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
        return $this->pdo->query("SELECT COUNT(*) FROM `{$dbPrefix}comments`")
                        ->fetchColumn();
    }

    public function toggleModeration($id) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("UPDATE `{$dbPrefix}comments` SET moderation = NOT moderation WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function voteComment($id, $voteType) {
		global $dbPrefix;
        $column = $voteType == 'plus' ? 'plus' : 'minus';
        $stmt = $this->pdo->prepare("UPDATE `{$dbPrefix}comments` SET $column = $column + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
	public function approveComment($commentId) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("UPDATE `{$dbPrefix}comments` SET moderation = 1 WHERE id = ?");
        $stmt->execute([$commentId]);
    }

    public function rejectComment($commentId) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("DELETE FROM `{$dbPrefix}comments` WHERE id = ?");
        $stmt->execute([$commentId]);
    }

    public function getPendingComments() {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("SELECT * FROM `{$dbPrefix}comments` WHERE moderation = 0");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
	public function countPendingComments() {
		global $dbPrefix;
		return $this->pdo->query("SELECT COUNT(*) FROM `{$dbPrefix}comments` WHERE moderation = 0")->fetchColumn();
	}
public function getCommentStatus($id) {
    global $dbPrefix;
    $stmt = $this->pdo->prepare("SELECT moderation FROM `{$dbPrefix}comments` WHERE id = ?");
    $stmt->execute([$id]);
    return (bool)$stmt->fetchColumn();
}
}

?>