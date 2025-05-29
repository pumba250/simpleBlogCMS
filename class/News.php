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
		// Простой алгоритм генерации тегов (можно усовершенствовать)
		$tags = [];
		
		// Массив стоп-слов
		$stopWords = ['и', 'в', 'на', 'с', 'по', 'к', 'из', 'для', 'это', 'что', 'как']; // Добавьте дополнительные

		// Удаляем HTML-теги и приводим к нижнему регистру
		$title = strip_tags($title);
		$content = strip_tags($content);
		
		// Удаляем знаки препинания 
		$title = preg_replace('/[^\w\s]/', '', $title);
		$content = preg_replace('/[^\w\s]/', '', $content);
		
		// Собираем слова из заголовка и контента
		$words = preg_split('/\s+/', $title . ' ' . $content);
		
		foreach ($words as $word) {
			$word = strtolower(trim($word));
			
			// Пропускаем короткие слова и стоп-слова
			if (strlen($word) > 5 && !in_array($word, $tags) && !in_array($word, $stopWords)) {
				$tags[] = $word;
			}
		}

		// Ограничиваем количество тегов (например, до 10)
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

    // Удаление старых тегов
    $this->removeTags($id);

    // Сохранение новых тегов
    foreach ($tags as $tag) {
        $stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}tags (name) VALUES (?) ON DUPLICATE KEY UPDATE id=id");
        $stmt->execute([$tag]);
        $tagId = $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}blogs_tags (blogs_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$id, $tagId]);
    }
}
public function deleteNews($id) {
	global $dbPrefix;
    try {
        // Удаляем теги, связанные с новостью
        $this->removeTags($id); 
        
        // Удаляем саму новость
        $stmt = $this->pdo->prepare("DELETE FROM {$dbPrefix}blogs WHERE id = ?");
        $stmt->execute([$id]);

        // После удаления новости, удаляем неиспользуемые теги
        $this->removeUnusedTags();
    } catch (PDOException $e) {
        echo 'Ошибка при удалении новости: ' . $e->getMessage();
    }
}
public function removeUnusedTags() {
	global $dbPrefix;
    // Удаляем теги, которые больше не используются
    $stmt = $this->pdo->prepare("DELETE FROM {$dbPrefix}tags WHERE id NOT IN (SELECT tag_id FROM blogs_tags)");
    $stmt->execute();
}
public function removeTags($newsId) {
	global $dbPrefix;
    $stmt = $this->pdo->prepare("DELETE FROM {$dbPrefix}blogs_tags WHERE blogs_id = ?");
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
    // Сначала получаем ID тега по имени
    $stmt = $this->pdo->prepare("SELECT id FROM {$dbPrefix}tags WHERE name = ?");
    $stmt->execute([$tag]);
    $tagId = $stmt->fetchColumn();
    // Если тег не найден, возвращаем пустой массив
    if (!$tagId) {
        return [];
    }

    // Теперь получаем все новости, связанные с этим тегом
    $stmt = $this->pdo->prepare("
        SELECT b.*
        FROM {$dbPrefix}blogs b
        JOIN {$dbPrefix}blogs_tags bt ON b.id = bt.blogs_id
        WHERE bt.tag_id = ?
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
    
    // Удаляем спецсимволы, оставляем только слова
    $words = preg_split('/\s+/', preg_replace('/[^a-zA-Zа-яА-Я0-9\s]/u', '', $text));
    
    // Удаляем стоп-слова
    $stopWords = ['и', 'в', 'на', 'с', 'по', 'для', 'не', 'что', 'это', 'как'];
    $words = array_diff($words, $stopWords);
    
    // Подсчитываем частоту слов
    $wordCounts = array_count_values($words);
    arsort($wordCounts);
    
    // Берем самые частые слова
    $keywords = array_slice(array_keys($wordCounts), 0, $maxKeywords);
    
    return implode(', ', $keywords);
}

}
