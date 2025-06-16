<?php

class Comments {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
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

    public function getComments($themeId) {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("SELECT * FROM `{$dbPrefix}comments` WHERE theme_id = ? AND moderation = 1 ORDER BY created_at DESC");
        $stmt->execute([$themeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
	
	public function AllComments() {
		global $dbPrefix;
        $stmt = $this->pdo->prepare("SELECT c.*, b.title as post_title, u.username 
            FROM `{$dbPrefix}comments` c 
            LEFT JOIN `{$dbPrefix}blogs` b ON c.theme_id = b.id 
            LEFT JOIN `{$dbPrefix}users` u ON c.user_name = u.username
            ORDER BY c.created_at DESC");
        $stmt->execute();
        //return var_dump($stmt); //
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
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