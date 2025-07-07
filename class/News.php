<?php
if (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен'); }
/**
 * Класс для работы с новостями и статьями
 * 
 * @package    SimpleBlog
 * @subpackage Models
 * @category   Content
 * @version    0.8.2
 * 
 * @method array getAllNews(int $limit, int $offset) Получает новости с пагинацией
 * @method array searchNews(string $query, int $limit = 10, int $offset = 0) Ищет новости
 * @method int countSearchResults(string $query) Считает результаты поиска
 * @method array getAllAdm(int $limit = 0, int $offset = 0) Получает все новости (админка)
 * @method int getTotalNewsCount() Получает количество новостей
 * @method array getLastThreeNews() Получает 3 последние новости
 * @method array generateTags(string $title, string $content) Генерирует теги
 * @method bool addBlog(string $title, string $content, array $tags = []) Добавляет статью
 * @method array getNewsWithTags() Получает новости с тегами
 * @method bool updateNews(int $id, string $title, string $content, array $tags) Обновляет новость
 * @method bool updateBlog(int $id, string $title, string $content, array $tags = []) Обновляет блог
 * @method bool deleteNews(int $id) Удаляет новость
 * @method bool deleteBlog(int $id) Удаляет блог
 * @method array GetAllTags() Получает все теги
 * @method bool addTag(string $name) Добавляет тег
 * @method bool deleteTag(int $id) Удаляет тег
 * @method void removeUnusedTags() Удаляет неиспользуемые теги
 * @method void removeTags(int $newsId) Удаляет теги у новости
 * @method array getNewsById(int $id) Получает новость по ID
 * @method array getTagsByNewsId(int $newsId) Получает теги новости
 * @method array getNewsByTag(string $tag) Получает новости по тегу
 * @method string generateMetaDescription(string $content, string $type = 'default', array $additionalData = []) Генерирует meta description
 * @method string generateMetaKeywords(string $content, string $type = 'home', array $additionalData = []) Генерирует meta keywords
 */
class News {
    private $pdo;
	private $dbPrefix;

    public function __construct($pdo) {
		global $config;
        $this->pdo = $pdo;
        if (!isset($config['db_prefix'])) {
            throw new InvalidArgumentException("Database prefix not configured");
        }
        if (!preg_match('/^[a-z][a-z0-9_]*$/i', $config['db_prefix'])) {
            throw new InvalidArgumentException("Invalid database prefix format");
        }
        $this->dbPrefix = $config['db_prefix'];
    }

	public function getAllNews($limit, $offset) {
		$stmt = $this->pdo->prepare("SELECT id, title, LEFT(content, 320) AS excerpt, content, created_at FROM {$this->dbPrefix}blogs ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
		$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
    
	public function searchNews($query, $limit = 10, $offset = 0) {
    try {
        $searchQuery = '%' . $query . '%';
        $stmt = $this->pdo->prepare("
            SELECT id, title, LEFT(content, 320) AS excerpt, created_at 
            FROM {$this->dbPrefix}blogs 
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
		
		try {
			$searchQuery = '%' . $query . '%';
			$stmt = $this->pdo->prepare("
				SELECT COUNT(*) 
				FROM {$this->dbPrefix}blogs 
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
    public function getAllAdm() {
		$stmt = $this->pdo->prepare("
			SELECT b.*, GROUP_CONCAT(t.name) as tag_names, GROUP_CONCAT(t.id) as tag_ids
			FROM `{$this->dbPrefix}blogs` b
			LEFT JOIN `{$this->dbPrefix}blogs_tags` bt ON b.id = bt.blogs_id
			LEFT JOIN `{$this->dbPrefix}tags` t ON bt.tag_id = t.id
			GROUP BY b.id
			ORDER BY b.created_at DESC
		");
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
		$stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->dbPrefix}blogs");
		$stmt->execute();
		return $stmt->fetchColumn();
    }
	public function getLastThreeNews() {
    $stmt = $this->pdo->prepare("SELECT * FROM {$this->dbPrefix}blogs ORDER BY RAND() DESC LIMIT 3");
	$stmt->execute();
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
		try {
			$this->pdo->beginTransaction();
			
			// Добавляем запись
			$stmt = $this->pdo->prepare("INSERT INTO {$this->dbPrefix}blogs (title, content) VALUES (?, ?)");
			$stmt->execute([$title, $content]);
			$blogId = $this->pdo->lastInsertId();
			
			// Get auto-generated tags
			$autoTags = $this->generateTags($title, $content);
			
			// Process both manual tags and auto-generated tags
			$allTags = $tags;
			foreach ($autoTags as $tagName) {
				// Check if tag exists, if not create it
				$stmt = $this->pdo->prepare("SELECT id FROM {$this->dbPrefix}tags WHERE name = ?");
				$stmt->execute([$tagName]);
				$tagId = $stmt->fetchColumn();
				
				if (!$tagId) {
					$stmt = $this->pdo->prepare("INSERT INTO {$this->dbPrefix}tags (name) VALUES (?)");
					$stmt->execute([$tagName]);
					$tagId = $this->pdo->lastInsertId();
				}
				
				$allTags[] = $tagId;
			}
			
			// Добавляем связи с тегами
			if (!empty($allTags)) {
				$allTags = array_unique($allTags); // Remove duplicates
				$stmt = $this->pdo->prepare("INSERT INTO {$this->dbPrefix}blogs_tags (blogs_id, tag_id) VALUES (?, ?)");
				foreach ($allTags as $tagId) {
					$stmt->execute([$blogId, $tagId]);
				}
			}
			
			$this->pdo->commit();
			Cache::clear();
			return true;
		} catch (Exception $e) {
			$this->pdo->rollBack();
			error_log($e->getMessage());
			return false;
		}
	}
	
    /**
     * Обновить запись блога
     */
	public function updateBlog($id, $title, $content, $tags = []) {
		try {
			$this->pdo->beginTransaction();
			
			// Обновление основной информации
			$stmt = $this->pdo->prepare("UPDATE {$this->dbPrefix}blogs SET title = ?, content = ? WHERE id = ?");
			$stmt->execute([$title, $content, $id]);
			
			// Удаляем старые связи с тегами
			$stmt = $this->pdo->prepare("DELETE FROM {$this->dbPrefix}blogs_tags WHERE blogs_id = ?");
			$stmt->execute([$id]);
			
			// Добавляем новые связи с тегами
			if (!empty($tags)) {
				$stmt = $this->pdo->prepare("INSERT INTO {$this->dbPrefix}blogs_tags (blogs_id, tag_id) VALUES (?, ?)");
				foreach ($tags as $tagId) {
					$tagId = (int)$tagId;
					// Проверяем, что тег существует
					$checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->dbPrefix}tags WHERE id = ?");
					$checkStmt->execute([$tagId]);
					if ($checkStmt->fetchColumn()) {
						$stmt->execute([$id, $tagId]);
					}
				}
			}
			
			$this->pdo->commit();
			Cache::clear();
			return true;
		} catch (PDOException $e) {
			$this->pdo->rollBack();
			error_log("Ошибка при обновлении блога: " . $e->getMessage());
			return false;
		}
	}

    /**
     * Удалить запись блога
     */
    public function deleteBlog($id) {
        try {
            $this->pdo->beginTransaction();
            
            // Удаляем связи с тегами
            $stmt = $this->pdo->prepare("DELETE FROM {$this->dbPrefix}blogs_tags WHERE blogs_id = ?");
            $stmt->execute([$id]);
            
            // Удаляем саму запись
            $stmt = $this->pdo->prepare("DELETE FROM {$this->dbPrefix}blogs WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->pdo->commit();
			Cache::clear();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }
	

	public function GetAllTags() {
		$stmt = $this->pdo->prepare("SELECT DISTINCT(`name`) FROM {$this->dbPrefix}tags");
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function addTag($name) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO {$this->dbPrefix}tags (name) VALUES (?)");
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
        try {
            $this->pdo->beginTransaction();
            
            // Удаляем связи с записями
            $stmt = $this->pdo->prepare("DELETE FROM {$this->dbPrefix}blogs_tags WHERE tag_id = ?");
            $stmt->execute([$id]);
            
            // Удаляем сам тег
            $stmt = $this->pdo->prepare("DELETE FROM {$this->dbPrefix}tags WHERE id = ?");
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
		$stmt = $this->pdo->prepare("DELETE FROM `{$this->dbPrefix}tags` WHERE id NOT IN (SELECT tag_id FROM `{$this->dbPrefix}blogs_tags`)");
		$stmt->execute();
	}
	public function removeTags($newsId) {
		$stmt = $this->pdo->prepare("DELETE FROM `{$this->dbPrefix}blogs_tags` WHERE blogs_id = ?");
		$stmt->execute([$newsId]);
	}

	public function getNewsById($id) {
		try {
			$stmt = $this->pdo->prepare("SELECT * FROM {$this->dbPrefix}blogs WHERE id = ? LIMIT 1");
			$stmt->execute([$id]);
			return $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			error_log("Error in getBlogById: " . $e->getMessage());
			return false;
		}
	}

	public function getTagsByNewsId($newsId) {
		$stmt = $this->pdo->prepare("
			SELECT t.name
			FROM {$this->dbPrefix}tags t
			JOIN {$this->dbPrefix}blogs_tags nt ON t.id = nt.tag_id
			WHERE nt.blogs_id = ?
		");
		$stmt->execute([$newsId]);
		$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $tags ?: [];
	}
	public function getNewsByTag($tag) {
		$stmt = $this->pdo->prepare("SELECT id FROM {$this->dbPrefix}tags WHERE name = ?");
		$stmt->execute([$tag]);
		$tagId = $stmt->fetchColumn();
		if (!$tagId) {
			return [];
		}

		$stmt = $this->pdo->prepare("
			SELECT b.id, b.title, LEFT(b.content, 320) AS excerpt, b.content, b.created_at
			FROM {$this->dbPrefix}blogs b
			JOIN {$this->dbPrefix}blogs_tags bt ON b.id = bt.blogs_id
			WHERE bt.tag_id = ?
			ORDER BY b.created_at DESC
		");
		$stmt->execute([$tagId]);

			 $news = $stmt->fetchAll(PDO::FETCH_ASSOC);

			 return $news;
	}

	// Функция для генерации metaDescription
	public function generateMetaDescription($content, $type = 'default', $additionalData = []) {
		$defaultDescription = $config['metaDescription'] ?? '';
		
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
	public function generateMetaKeywords($content, $type = 'default', $additionalData = []) {
		$defaultKeywords = $config['metaKeywords'] ?? '';
		
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
	public function getAllNewsCached($limit, $offset) {
		$cacheKey = 'news_all_' . $limit . '_' . $offset . '_' . ($_SESSION['lang'] ?? 'ru');
		
		if (Cache::has($cacheKey)) {
			return Cache::get($cacheKey);
		}
		
		$result = $this->getAllNews($limit, $offset);
		Cache::set($cacheKey, $result, 1800); // 30 минут кэша
		
		return $result;
	}

	public function getNewsByIdCached($id) {
		$cacheKey = 'news_item_' . $id;
		
		if (Cache::has($cacheKey)) {
			return Cache::get($cacheKey);
		}
		
		$result = $this->getNewsById($id);
		if ($result) {
			Cache::set($cacheKey, $result, 3600); // 1 час кэша для статьи
		}
		
		return $result;
	}
	// Добавляем в класс News
public function getNewsForRendering($limit, $offset): array {
	global $votes;
    $stmt = $this->pdo->prepare("
        SELECT id, title, LEFT(content, 320) AS excerpt, content, created_at 
        FROM {$this->dbPrefix}blogs 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($news as $item) {
        $rating = $votes->getArticleRating($item['id']);
        $tags = $this->getTagsByNewsId($item['id']);

        $result[] = [
            'id' => $item['id'],
            'title' => $item['title'],
            'excerpt' => $item['excerpt'],
            'content' => $item['content'],
            'created_at' => $item['created_at'],
            'likes' => $rating['likes'] ?? 0,
            'dislikes' => $rating['dislikes'] ?? 0,
            'tags' => $tags,
            'read_more' => Lang::get('read_more'),
            'url' => "?id={$item['id']}"
        ];
    }

    return $result;
}

public function getSingleNewsForRendering($id): ?array {
    $stmt = $this->pdo->prepare("
        SELECT id, title, content, created_at 
        FROM {$this->dbPrefix}blogs 
        WHERE id = ? 
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        return null;
    }

    $rating = $this->votes->getArticleRating($id);
    $tags = $this->getTagsByNewsId($id);

    return [
        'id' => $item['id'],
        'title' => $item['title'],
        'content' => $item['content'],
        'created_at' => $item['created_at'],
        'likes' => $rating['likes'] ?? 0,
        'dislikes' => $rating['dislikes'] ?? 0,
        'tags' => $tags,
        'url' => "?id={$item['id']}"
    ];
}
}
