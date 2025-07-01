<?php
class News {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

	public function getAllNews($limit, $offset) {
		global $dbPrefix;
		$stmt = $this->pdo->prepare("SELECT id, title, LEFT(content, 320) AS excerpt, content, created_at FROM {$dbPrefix}blogs ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
		$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
    
	public function searchNews($query, $limit = 10, $offset = 0) {
    global $dbPrefix;
    
    try {
        $searchQuery = '%' . $query . '%';
        $stmt = $this->pdo->prepare("
            SELECT id, title, LEFT(content, 320) AS excerpt, created_at 
            FROM {$dbPrefix}blogs 
            WHERE title LIKE :query OR content LIKE :query
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindParam(':query', $searchQuery, PDO::PARAM_STR);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        return [];
    }
	}

	public function countSearchResults($query) {
		global $dbPrefix;
		
		try {
			$searchQuery = '%' . $query . '%';
			$stmt = $this->pdo->prepare("
				SELECT COUNT(*) 
				FROM {$dbPrefix}blogs 
				WHERE title LIKE :query OR content LIKE :query
			");
			
			$stmt->bindParam(':query', $searchQuery, PDO::PARAM_STR);
			$stmt->execute();
			
			return $stmt->fetchColumn();
		} catch (PDOException $e) {
			error_log("Search count error: " . $e->getMessage());
			return 0;
		}
	}
    public function getAllAdm($limit = 0, $offset = 0) {
		global $dbPrefix;
		$limitClause = $limit > 0 ? "LIMIT $limit OFFSET $offset" : "";
		
		$stmt = $this->pdo->prepare("
			SELECT b.*, GROUP_CONCAT(t.name) as tag_names, GROUP_CONCAT(t.id) as tag_ids
			FROM `{$dbPrefix}blogs` b
			LEFT JOIN `{$dbPrefix}blogs_tags` bt ON b.id = bt.blogs_id
			LEFT JOIN `{$dbPrefix}tags` t ON bt.tag_id = t.id
			GROUP BY b.id
			ORDER BY b.created_at DESC
			LIMIT :limit OFFSET :offset
		");
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();
		
		$blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// Преобразуем теги в массивы
		foreach ($blogs as &$blog) {
			$blog['tags'] = $blog['tag_names'] ? array_map('htmlspecialchars', explode(',', $blog['tag_names'])) : [];
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
    $stmt = $this->pdo->query("SELECT * FROM {$dbPrefix}blogs ORDER BY RAND() DESC LIMIT 3");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function generateTags($title, $content) {
		$tags = [];
		
		$stopWords = ['и', 'в', 'на', 'с', 'по', 'к', 'из', 'для', 'это', 'что', 'как']; 

		$title = strip_tags($title);
		$content = strip_tags($content);
		
		$title = preg_replace('/[^\w\s]/u', '', $title);  // Added 'u' modifier for UTF-8 support
		$content = preg_replace('/[^\w\s]/u', '', $content);
		
		$words = preg_split('/\s+/', $title . ' ' . $content);
		
		foreach ($words as $word) {
			$word = mb_strtolower(trim($word), 'UTF-8');  // Using mb_strtolower for proper UTF-8 support
			
			if (mb_strlen($word, 'UTF-8') > 5 && !in_array($word, $tags) && !in_array($word, $stopWords)) {
				$tags[] = $word;
			}
		}

		return array_slice($tags, 0, 4);
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
			
			// Get auto-generated tags
			$autoTags = $this->generateTags($title, $content);
			
			// Process both manual tags and auto-generated tags
			$allTags = $tags;
			foreach ($autoTags as $tagName) {
				// Check if tag exists, if not create it
				$stmt = $this->pdo->prepare("SELECT id FROM {$dbPrefix}tags WHERE name = ?");
				$stmt->execute([$tagName]);
				$tagId = $stmt->fetchColumn();
				
				if (!$tagId) {
					$stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}tags (name) VALUES (?)");
					$stmt->execute([$tagName]);
					$tagId = $this->pdo->lastInsertId();
				}
				
				$allTags[] = $tagId;
			}
			
			// Добавляем связи с тегами
			if (!empty($allTags)) {
				$allTags = array_unique($allTags); // Remove duplicates
				$stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}blogs_tags (blogs_id, tag_id) VALUES (?, ?)");
				foreach ($allTags as $tagId) {
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
	public function getNewsWithTags() {
		global $dbPrefix;
		$stmt = $this->pdo->prepare("
			SELECT b.id, b.title, LEFT(b.content, 320) AS excerpt, b.content, b.created_at
			FROM {$dbPrefix}blogs b
			JOIN {$dbPrefix}blogs_tags bt ON b.id = bt.blogs_id
			WHERE bt.tag_id = ?
		");
		$stmt->execute([$tagId]);
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// Escape the tags data
		foreach ($result as &$row) {
			if ($row['tags']) {
				$row['tags'] = htmlspecialchars($row['tags']);
			}
		}
		
		return $result;
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
			
			// Обновление основной информации
			$stmt = $this->pdo->prepare("UPDATE {$dbPrefix}blogs SET title = ?, content = ? WHERE id = ?");
			$stmt->execute([$title, $content, $id]);
			
			// Удаляем старые связи с тегами
			$stmt = $this->pdo->prepare("DELETE FROM {$dbPrefix}blogs_tags WHERE blogs_id = ?");
			$stmt->execute([$id]);
			
			// Добавляем новые связи с тегами
			if (!empty($tags)) {
				$stmt = $this->pdo->prepare("INSERT INTO {$dbPrefix}blogs_tags (blogs_id, tag_id) VALUES (?, ?)");
				foreach ($tags as $tagId) {
					$stmt->execute([$id, (int)$tagId]);
				}
			}
			
			$this->pdo->commit();
			return true;
		} catch (PDOException $e) {
			$this->pdo->rollBack();
			error_log("Ошибка при обновлении блога: " . $e->getMessage());
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
		try {
			$stmt = $this->pdo->prepare("SELECT * FROM {$dbPrefix}blogs WHERE id = ? LIMIT 1");
			$stmt->execute([$id]);
			return $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			error_log("Error in getBlogById: " . $e->getMessage());
			return false;
		}
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
		$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $tags ?: [];
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
			SELECT b.id, b.title, LEFT(b.content, 320) AS excerpt, b.content, b.created_at
			FROM {$dbPrefix}blogs b
			JOIN {$dbPrefix}blogs_tags bt ON b.id = bt.blogs_id
			WHERE bt.tag_id = ?
			ORDER BY b.created_at DESC
		");
		$stmt->execute([$tagId]);

			 $news = $stmt->fetchAll(PDO::FETCH_ASSOC);

			 return $news;
	}

	// Функция для генерации metaDescription
	public function generateMetaDescription($content, $type = 'default', $additionalData = []) {
		global $config;
		$defaultDescription = $config['metaDescription'];
		
		switch ($type) {
			case 'article':
				// Для статьи берем первые 160 символов контента
				$cleanContent = strip_tags($content);
				return mb_substr($cleanContent, 0, 160) . '...';
				
			case 'tag':
				return 'Все публикации по теме: ' . htmlspecialchars($additionalData['tag']);
				
			case 'search':
				return 'Результаты поиска по запросу: ' . htmlspecialchars($additionalData['query']);
				
			case 'login':
				return 'Форма авторизации '.$config['metaDescription'];
				
			case 'register':
				return 'Форма регистрации нового пользователя '.$config['metaDescription'];
				
			case 'contact':
				return 'Контактная форма для связи с администрацией '.$config['metaDescription'];
				
			case 'error404':
				return 'Страница не найдена';
				
			case 'error500':
				return 'Внутренняя ошибка сервера';
				
			default:
				return $defaultDescription;
		}
	}

	// Функция для генерации metaKeywords
	public function generateMetaKeywords($content, $type = 'home', $additionalData = []) {
		global $config;
		$defaultKeywords = $config['metaKeywords'];
		
		switch ($type) {
			case 'article':
				// Для статьи берем слова из заголовка и первые 20 слов контента
				$titleWords = explode(' ', $additionalData['title']);
				$cleanContent = strip_tags($content);
				$contentWords = explode(' ', $cleanContent);
				$allWords = array_merge($titleWords, array_slice($contentWords, 0, 20));
				
				// Удаляем слишком короткие слова и дубликаты
				$filteredWords = array_filter($allWords, function($word) {
					return mb_strlen($word) > 3;
				});
				
				$uniqueWords = array_unique($filteredWords);
				return implode(', ', array_slice($uniqueWords, 0, 15));
				
			case 'tag':
				return htmlspecialchars($additionalData['tag']) . ', '.$config['metaKeywords'];
				
			case 'search':
				return 'поиск, ' . htmlspecialchars($additionalData['query']);
				
			case 'login':
				return 'авторизация, вход, '.$config['metaKeywords'];
				
			case 'register':
				return 'регистрация, создать аккаунт, '.$config['metaKeywords'];
				
			case 'contact':
				return 'контакты, обратная связь, '.$config['metaKeywords'];
				
			case 'error404':
				return '404, страница не найдена';
				
			case 'error500':
				return '500, ошибка сервера';
				
			default:
				return $defaultKeywords;
		}
	}

}
