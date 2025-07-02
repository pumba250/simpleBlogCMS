<?php
if (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен'); }
/**
 * Класс для работы с голосованием
 * 
 * @package    SimpleBlog
 * @subpackage Models
 * @category   Interaction
 * @version    0.8.0
 * 
 * @method bool voteComment(int $id, string $voteType, int $userId) Голосует за комментарий
 * @method bool hasUserVoted(int $commentId, int $userId) Проверяет голос пользователя
 * @method bool voteArticle(int $articleId, string $voteType, int $userId) Голосует за статью
 * @method bool hasUserVotedForArticle(int $articleId, int $userId) Проверяет голос за статью
 * @method array getArticleRating(int $articleId) Получает рейтинг статьи
 * @method array getCommentRating(int $commentId) Получает рейтинг комментария
 */
class Votes {
    private $pdo;
    private $dbPrefix;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        global $dbPrefix;
        $this->dbPrefix = $dbPrefix;
    }

    /**
     * Оценить комментарий
     * @param int $id ID комментария
     * @param string $voteType Тип оценки ('plus' или 'minus')
     * @param int $userId ID пользователя (для предотвращения повторных голосов)
     * @return bool Успешность операции
     */
    public function voteComment($id, $voteType, $userId) {
        // Проверяем, голосовал ли уже пользователь
        if ($this->hasUserVoted($id, $userId)) {
            return false;
        }
		if (!in_array($voteType, ['plus', 'minus'])) {
			throw new InvalidArgumentException("Invalid vote type");
		}
        $column = $voteType == 'plus' ? 'plus' : 'minus';
        
        try {
            $this->pdo->beginTransaction();
            
            // Обновляем счетчик голосов
            $stmt = $this->pdo->prepare("UPDATE `{$this->dbPrefix}comments` 
                                        SET $column = $column + 1 
                                        WHERE id = ?");
            $stmt->execute([$id]);
            
            // Записываем факт голосования
            $stmt = $this->pdo->prepare("INSERT INTO `{$this->dbPrefix}comment_votes` 
                                        (comment_id, user_id, vote_type) 
                                        VALUES (?, ?, ?)");
            $stmt->execute([$id, $userId, $voteType]);
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Vote error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Проверяет, голосовал ли пользователь за комментарий
     * @param int $commentId ID комментария
     * @param int $userId ID пользователя
     * @return bool
     */
    public function hasUserVoted($commentId, $userId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) 
                                    FROM `{$this->dbPrefix}comment_votes` 
                                    WHERE comment_id = ? AND user_id = ?");
        $stmt->execute([$commentId, $userId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Оценить статью
     * @param int $articleId ID статьи
     * @param string $voteType Тип оценки ('like' или 'dislike')
     * @param int $userId ID пользователя
     * @return bool Успешность операции
     */
    public function voteArticle($articleId, $voteType, $userId) {
        // Проверяем, голосовал ли уже пользователь
        if ($this->hasUserVotedForArticle($articleId, $userId)) {
            return false;
        }

        $column = $voteType == 'like' ? 'likes' : 'dislikes';
        
        try {
            $this->pdo->beginTransaction();
            
            // Обновляем счетчик голосов статьи
            $stmt = $this->pdo->prepare("UPDATE `{$this->dbPrefix}blogs` 
                                        SET $column = $column + 1 
                                        WHERE id = ?");
            $stmt->execute([$articleId]);
            
            // Записываем факт голосования
            $stmt = $this->pdo->prepare("INSERT INTO `{$this->dbPrefix}article_votes` 
                                        (article_id, user_id, vote_type) 
                                        VALUES (?, ?, ?)");
            $stmt->execute([$articleId, $userId, $voteType]);
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Article vote error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Проверяет, голосовал ли пользователь за статью
     * @param int $articleId ID статьи
     * @param int $userId ID пользователя
     * @return bool
     */
    public function hasUserVotedForArticle($articleId, $userId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) 
                                    FROM `{$this->dbPrefix}article_votes` 
                                    WHERE article_id = ? AND user_id = ?");
        $stmt->execute([$articleId, $userId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Получает рейтинг статьи
     * @param int $articleId ID статьи
     * @return array ['likes' => количество лайков, 'dislikes' => количество дизлайков]
     */
    public function getArticleRating($articleId) {
        $stmt = $this->pdo->prepare("SELECT likes, dislikes 
                                    FROM `{$this->dbPrefix}blogs` 
                                    WHERE id = ?");
        $stmt->execute([$articleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Получает рейтинг комментария
     * @param int $commentId ID комментария
     * @return array ['plus' => количество плюсов, 'minus' => количество минусов]
     */
    public function getCommentRating($commentId) {
        $stmt = $this->pdo->prepare("SELECT plus, minus 
                                    FROM `{$this->dbPrefix}comments` 
                                    WHERE id = ?");
        $stmt->execute([$commentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}