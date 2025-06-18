<?php
class News {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

public function getAllNews($limit, $offset) {
	global $dbPrefix;
    $stmt = $this->pdo->prepare("SELECT id, title, LEFT(content, 300) AS excerpt, content, created_at FROM {$dbPrefix}blogs ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    /**
     * Получить все записи блога (для админки)
     */
    public function getAllAdm($limit = 0, $offset = 0) {
		global $dbPrefix;
        $limitClause = $limit > 0 ? "LIMIT $limit OFFSET $offset" : "";
        
        $stmt = $this->pdo->query("
            SELECT b.*, GROUP_CONCAT(t.name) as tag_names, GROUP_CONCAT(t.id) as tag_ids
            FROM `{$dbPrefix}blogs` b
            LEFT JOIN `{$dbPrefix}blogs_tags` bt ON b.id = bt.blogs_id
            LEFT JOIN `{$dbPrefix}tags` t ON bt.tag_id = t.id
            GROUP BY b.id
            ORDER BY b.created_at DESC
            $limitClause
        ");
        
        $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Преобразуем теги в массивы
        foreach ($blogs as &$blog) {
            $blog['tags'] = $blog['tag_names'] ? explode(',', $blog['tag_names']) : [];
            $blog['tag_ids'] = $blog['tag_ids'] ? array_map('intval', explode(',', $blog['tag_ids'])) : [];
        }
        
        return $blogs;
    }

    public function getTotalNewsCount() {
	global $dbPrefix;
        return $this->pdo->query("SELECT COUNT(*) FROM {$dbPrefix}blogs")->fetchColumn();
    }
	public function getLastThreeNews() {
	global $dbPrefix;
    $stmt = $this->pdo->query("SELECT * FROM {$dbPrefix}blogs ORDER BY created_at DESC LIMIT 3");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function generateTags($title, $content) {
		$tags = [];
		
		$stopWords = ['и', 'в', 'на', 'с', 'по', 'к', 'из', 'для', 'это', 'что', 'как']; 

		$title = strip_tags($title);
		$content = strip_tags($content);
		
		$title = preg_replace('/[^\w\s]/', '', $title);
		$content = preg_replace('/[^\w\s]/', '', $content);
		
		$words = preg_split('/\s+/', $title . ' ' . $content);
		
		foreach ($words as $word) {
			$word = strtolower(trim($word));
			
			if (strlen($word) > 5 && !in_array($word, $tags) && !in_array($word, $stopWords)) {
				$tags[] = $word;
			}
		}

		return array_slice($tags, 0, 10);
	}
	public function getNewsWithTags() {
	global $dbPrefix;
    $stmt = $this->pdo->query("
        SELECT n.*, GROUP_CONCAT(t.name SEPARATOR ', ') as tags
        FROM {$dbPrefix}blogs n
        LEFT JOIN {$dbPrefix}blogs_tags nt ON n.id = nt.blogs_id
        LEFT JOIN {$dbPrefix}tags t ON nt.tag_id = t.id
        GROUP BY n.id
        ORDER BY n.created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function updateNews($id, $title, $content, $tags) {
	global $dbPrefix;
    $stmt = $this->pdo->prepare("UPDATE {$dbPrefix}blogs SET title = ?, content = ? WHERE id = ?");
    $stmt->execute([$title, $content, $id]);

    $this->removeTags($id);

    foreach ($tags as $tag) {
        $stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}tags (name) VALUES (?) ON DUPLICATE KEY UPDATE id=id");
        $stmt->execute([$tag]);
        $tagId = $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}blogs_tags (blogs_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$id, $tagId]);
    }
}
    /**
     * Обновить запись блога
     */
    public function updateBlog($id, $title, $content, $tags = []) {
		global $dbPrefix;
        try {
            $this->pdo->beginTransaction();
            
            // Обновляем саму запись
            $stmt = $this->pdo->prepare("UPDATE {$dbPrefix}blogs SET title = ?, content = ? WHERE id = ?");
            $stmt->execute([$title, $content, $id]);
            
            // Удаляем все текущие связи с тегами
            $stmt = $this->pdo->prepare("DELETE FROM {$dbPrefix}blogs_tags WHERE blogs_id = ?");
            $stmt->execute([$id]);
            
            // Добавляем новые связи с тегами
            if (!empty($tags)) {
                $stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}blogs_tags (blogs_id, tag_id) VALUES (?, ?)");
                foreach ($tags as $tagId) {
                    $stmt->execute([$id, $tagId]);
                }
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }
public function deleteNews($id) {
	global $dbPrefix;
    try {
        $this->removeTags($id); 
        
        $stmt = $this->pdo->prepare("DELETE FROM `{$dbPrefix}blogs` WHERE id = ?");
        $stmt->execute([$id]);

        $this->removeUnusedTags();
    } catch (PDOException $e) {
        echo 'Ошибка при удалении новости: ' . $e->getMessage();
    }
}
    /**
     * Удалить запись блога
     */
    public function deleteBlog($id) {
		global $dbPrefix;
        try {
            $this->pdo->beginTransaction();
            
            // Удаляем связи с тегами
            $stmt = $this->pdo->prepare("DELETE FROM {$dbPrefix}blogs_tags WHERE blogs_id = ?");
            $stmt->execute([$id]);
            
            // Удаляем саму запись
            $stmt = $this->pdo->prepare("DELETE FROM {$dbPrefix}blogs WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }
	
    /**
     * Добавить новую запись блога
     */
    public function addBlog($title, $content, $tags = []) {
		global $dbPrefix;
        try {
            $this->pdo->beginTransaction();
            
            // Добавляем запись
            $stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}blogs (title, content) VALUES (?, ?)");
            $stmt->execute([$title, $content]);
            $blogId = $this->pdo->lastInsertId();
            
            // Добавляем связи с тегами
            if (!empty($tags)) {
                $stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}blogs_tags (blogs_id, tag_id) VALUES (?, ?)");
                foreach ($tags as $tagId) {
                    $stmt->execute([$blogId, $tagId]);
                }
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }
public function GetAllTags() {
	global $dbPrefix;
	return $this->pdo->query("SELECT DISTINCT(`name`) FROM {$dbPrefix}tags")->fetchAll(PDO::FETCH_ASSOC);
}
public function addTag($name) {
		global $dbPrefix;
        try {
            $stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}tags (name) VALUES (?)");
            return $stmt->execute([$name]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }
    
    /**
     * Удалить тег
     */
    public function deleteTag($id) {
		global $dbPrefix;
        try {
            $this->pdo->beginTransaction();
            
            // Удаляем связи с записями
            $stmt = $this->pdo->prepare("DELETE FROM {$dbPrefix}blogs_tags WHERE tag_id = ?");
            $stmt->execute([$id]);
            
            // Удаляем сам тег
            $stmt = $this->pdo->prepare("DELETE FROM {$dbPrefix}tags WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }
public function removeUnusedTags() {
	global $dbPrefix;
    $stmt = $this->pdo->prepare("DELETE FROM `{$dbPrefix}tags` WHERE id NOT IN (SELECT tag_id FROM `{$dbPrefix}blogs_tags`)");
    $stmt->execute();
}
public function removeTags($newsId) {
	global $dbPrefix;
    $stmt = $this->pdo->prepare("DELETE FROM `{$dbPrefix}blogs_tags` WHERE blogs_id = ?");
    $stmt->execute([$newsId]);
}

public function getNewsById($id) {
	global $dbPrefix;
    $stmt = $this->pdo->prepare("SELECT * FROM `{$dbPrefix}blogs` WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function getTagsByNewsId($newsId) {
	global $dbPrefix;
    $stmt = $this->pdo->prepare("
        SELECT t.name
        FROM {$dbPrefix}tags t
        JOIN {$dbPrefix}blogs_tags nt ON t.id = nt.tag_id
        WHERE nt.blogs_id = ?
    ");
    $stmt->execute([$newsId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getNewsByTag($tag) {
	global $dbPrefix;
    $stmt = $this->pdo->prepare("SELECT id FROM {$dbPrefix}tags WHERE name = ?");
    $stmt->execute([$tag]);
    $tagId = $stmt->fetchColumn();
    if (!$tagId) {
        return [];
    }

    $stmt = $this->pdo->prepare("
        SELECT b.id, b.title, LEFT(b.content, 300) AS excerpt, b.content, b.created_at
        FROM {$dbPrefix}blogs b
        JOIN {$dbPrefix}blogs_tags bt ON b.id = bt.blogs_id
        WHERE bt.tag_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$tagId]);

         $news = $stmt->fetchAll(PDO::FETCH_ASSOC);

         return $news;
}

public function generateMetaDescription($content, $length = 160) {
    $content = strip_tags($content);
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);
    
    if (mb_strlen($content) > $length) {
        $content = mb_substr($content, 0, $length - 3) . '...';
    }
    
    return $content;
}

public function generateMetaKeywords($title, $content, $maxKeywords = 15) {
    $text = $title . ' ' . $content;
    $text = strip_tags($text);
    $text = mb_strtolower($text);
    
    $words = preg_split('/\s+/', preg_replace('/[^a-zA-Zа-яА-Я0-9\s]/u', '', $text));
    
    $stopWords = ['и', 'в', 'на', 'с', 'по', 'для', 'не', 'что', 'это', 'как'];
    $words = array_diff($words, $stopWords);
    
    $wordCounts = array_count_values($words);
    arsort($wordCounts);
    
    $keywords = array_slice(array_keys($wordCounts), 0, $maxKeywords);
    
    return implode(', ', $keywords);
}

}
