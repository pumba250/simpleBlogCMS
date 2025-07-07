<?php
/**
 * Класс для обновлений системы
 * 
 * @package    SimpleBlog
 * @subpackage Services
 * @category   Maintenance
 * @version    0.8.2
 * 
 * @method bool|array checkForUpdates() Проверяет обновления
 * @method bool performUpdate() Выполняет обновление
 * @method array getUpdateInfo() Получает информацию об обновлении
 */
if (!defined('IN_SIMPLECMS')) {
    die('Прямой доступ запрещен');
}

class Updater {
    private $pdo;
    private $config;
    private $lastCheckFile;
    private $githubRepo;
    
    public function __construct($pdo, $config) {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->lastCheckFile = __DIR__.'/../cache/last_update_check';
        $this->githubRepo = $config['github_repo']; // Ваш репозиторий
    }
    
    /**
     * Проверяет обновления через GitHub Releases
     */
public function checkForUpdates() {
	
    if ($this->config['disable_update_check'] ?? true) {
        return false;
    }
	@unlink($this->lastCheckFile);
    $lastCheck = @filemtime($this->lastCheckFile);
    if ($lastCheck && (time() - $lastCheck) < $this->config['update_check_interval']) {
        return false;
    }

    @touch($this->lastCheckFile);
    $currentVersion = $this->normalizeVersion($this->config['version']);

    try {
        $url = "https://api.github.com/repos/{$this->githubRepo}/releases/latest";
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    "User-Agent: SimpleBlog CMS Updater",
                    "Accept: application/vnd.github.v3+json"
                ],
                'timeout' => 5
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception("Could not connect to GitHub");
        }
        
        $release = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($release['tag_name'])) {
            throw new Exception("Invalid GitHub response format");
        }
        
        $latestVersion = $this->normalizeVersion($release['tag_name']);
        
        if (version_compare($currentVersion, $latestVersion, '<')) {
            $updateInfo = [
                'current_version' => $this->config['version'],
                'new_version' => $release['tag_name'],
                'release_date' => date('d.m.Y', strtotime($release['published_at'])),
                'changelog' => $this->parseReleaseNotes($release['body']),
                'download_url' => $release['zipball_url'],
                'release_url' => $release['html_url'],
                'is_important' => $this->isImportantUpdate($release['body'])
            ];
            return $updateInfo;
        }
    } catch (Exception $e) {
        error_log("GitHub check failed: ".$e->getMessage());
    }
    
    return false;
}

    /**
     * Нормализация версий для правильного сравнения
     */
    private function normalizeVersion($version) {
		// Remove leading 'v' or 'V' if present
		$version = ltrim($version, 'vV');
		
		// Split into parts and take first 3 segments
		$parts = explode('.', $version);
		$parts = array_slice($parts, 0, 3);
		
		// Ensure we have exactly 3 parts (pad with 0 if needed)
		while (count($parts) < 3) {
			$parts[] = '0';
		}
		
		// Join back together
		return implode('.', $parts);
	}
    private function parseReleaseNotes($body) {
        // Удаляем инструкции по обновлению
        $notes = preg_replace('/To update v\d+\.\d+\.\d+ to v\d+\.\d+\.\d+:.*$/s', '', $body);
        
        // Заменяем маркированные списки на HTML
        $notes = preg_replace('/^\*\s*(.+)$/m', '• $1', $notes);
        
        // Удаляем лишние пустые строки
        $notes = preg_replace("/(\r?\n){2,}/", "\n\n", $notes);
        
        return trim($notes);
    }

    /**
     * Проверка, является ли обновление важным (безопасность/критические исправления)
     */
    private function isImportantUpdate($body) {
        return (strpos($body, 'Security') !== false) || 
               (strpos($body, 'безопасност') !== false) ||
               (strpos($body, 'критич') !== false);
    }
    
    /**
     * Получает SHA256 хеш ассета
     */
    private function getAssetHash($assetUrl) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $assetUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'SimpleBlog CMS Updater',
            CURLOPT_HTTPHEADER => [
                'Accept: application/octet-stream'
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $content = curl_exec($ch);
        
        if (curl_errno($ch)) {
            curl_close($ch);
            return null;
        }
        
        curl_close($ch);
        return hash('sha256', $content);
    }
    
    /**
     * Очищает номер версии
     */
    private function cleanVersion($version) {
        return ltrim($version, 'vV');
    }
    
    /**
	 * Выполняет обновление
	 */
	public function performUpdate() {
		try {
			// Явный вывод ошибок
			echo "Начало процесса обновления...<br>";
			
			// Получаем информацию об обновлении
			$updateInfo = $this->checkForUpdates();
			echo "Информация об обновлении: " . print_r($updateInfo, true) . "<br>";
			
			if (!$updateInfo || !isset($updateInfo['download_url'])) {
				throw new Exception("Нет корректной информации об обновлении");
			}

			// Создаем резервную копию
			echo "Создание резервной копии...<br>";
			$backupFile = $this->createBackup();
			echo "Резервная копия создана: $backupFile<br>";

			// Загружаем обновление
			echo "Загрузка обновления...<br>";
			$tempFile = $this->downloadUpdate($updateInfo['download_url']);
			echo "Обновление загружено во временный файл: $tempFile<br>";

			// Проверка хеша
			if (!empty($updateInfo['sha256'])) {
				echo "Проверка хеша...<br>";
				$fileHash = hash_file('sha256', $tempFile);
				if ($fileHash !== $updateInfo['sha256']) {
					throw new Exception("Хеш не совпадает! Ожидалось: {$updateInfo['sha256']}, получено: $fileHash");
				}
				echo "Хеш верный<br>";
			}

			// Применяем обновление
			echo "Применение обновления...<br>";
			$this->applyUpdate($tempFile);
			echo "Обновление применено<br>";

			// Обновляем версию в конфиге
			echo "Обновление конфигурации...<br>";
			$this->updateConfigVersion($updateInfo['new_version']);
			echo "Версия в конфиге обновлена<br>";

			// Очистка
			if (file_exists($tempFile)) {
				unlink($tempFile);
			}

			echo "Обновление успешно завершено!<br>";
			return true;

		} catch (Exception $e) {
			// Логируем ошибку
			error_log("Ошибка обновления: " . $e->getMessage());
			echo "<strong>Ошибка:</strong> " . $e->getMessage() . "<br>";
			
			// Восстановление из резервной копии
			if (!empty($backupFile) && file_exists($backupFile)) {
				echo "Попытка восстановления из резервной копии...<br>";
				$this->restoreBackup($backupFile);
			}
			
			return false;
		}
	}
    
    /**
	 * Загружает обновление во временный файл и возвращает путь к нему
	 */
	private function downloadUpdate($url) {
		$tempFile = tempnam(sys_get_temp_dir(), 'sb_update_');
		if (!$tempFile) {
			throw new Exception("Не удалось создать временный файл");
		}

		$ch = curl_init();
		$fp = fopen($tempFile, 'w+');
		
		$headers = [
			'Accept: application/vnd.github.v3.raw',
			'User-Agent: SimpleBlog-CMS-Updater'
		];
		
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_FILE => $fp,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_TIMEOUT => 300,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_FAILONERROR => true
		]);
		
		if (!curl_exec($ch)) {
			$error = curl_error($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			fclose($fp);
			unlink($tempFile);
			
			throw new Exception("Ошибка загрузки (HTTP $httpCode): $error");
		}
		
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		fclose($fp);
		
		if ($httpCode !== 200) {
			unlink($tempFile);
			throw new Exception("Сервер вернул код $httpCode");
		}
		
		// Проверяем, что файл не пустой
		if (filesize($tempFile) === 0) {
			unlink($tempFile);
			throw new Exception("Загружен пустой файл");
		}
		
		return $tempFile;
	}
    
    /**
	 * Применяет обновление из временного файла
	 */
	private function applyUpdate($tempFile) {
		$tempDir = sys_get_temp_dir().'/sb_update_'.bin2hex(random_bytes(8));
		if (!mkdir($tempDir, 0700)) {
			throw new Exception("Не удалось создать временную директорию");
		}

		$zip = new ZipArchive;
		if ($zip->open($tempFile) !== true) {
			$this->removeDirectory($tempDir);
			throw new Exception("Не удалось открыть архив обновления");
		}
		
		if (!$zip->extractTo($tempDir)) {
			$zip->close();
			$this->removeDirectory($tempDir);
			throw new Exception("Ошибка распаковки архива");
		}
		$zip->close();
		
		// GitHub архивы содержат папку repo-name-commitHash/
		$extractedDirs = array_diff(scandir($tempDir), ['.', '..']);
		$sourceDir = $tempDir.'/'.reset($extractedDirs);
		
		if (!is_dir($sourceDir)) {
			$this->removeDirectory($tempDir);
			throw new Exception("Неверная структура архива обновления");
		}
		
		// Копируем файлы из архива в корень системы
		$this->copyDirectory($sourceDir, dirname(__DIR__));
		
		// Выполняем SQL-скрипты, если они есть
		if (file_exists($sourceDir.'/update.sql')) {
			$this->executeUpdateSql($sourceDir.'/update.sql');
		}
		
		$this->removeDirectory($tempDir);
	}
    
    /**
     * Получает информацию о доступном обновлении
     */
    public function getUpdateInfo() {
        return $_SESSION['available_update'] ?? null;
    }
    /**
 * Обновляет версию в конфигурационном файле
 * 
 * @param string $newVersion Новая версия (например "1.0.0")
 * @return bool
 * @throws Exception
 */
private function updateConfigVersion($newVersion) {
    // Проверка формата версии
    if (!preg_match('/^v?\d+\.\d+(\.\d+)?$/', $newVersion)) {
        throw new Exception("Некорректный формат версии: ".$newVersion);
    }
    
    $configFile = __DIR__.'/../config/config.php';
    
    // Проверяем существование файла
    if (!file_exists($configFile)) {
        throw new Exception("Конфигурационный файл не найден");
    }
    
    // Проверяем права на запись
    if (!is_writable($configFile)) {
        throw new Exception("Нет прав на запись в конфигурационный файл");
    }
    
    // Читаем текущий конфиг
    $config = require $configFile;
    
    // Создаем резервную копию
    $backupContent = file_get_contents($configFile);
    if ($backupContent === false) {
        throw new Exception("Не удалось прочитать конфигурационный файл");
    }
    
    // Обновляем версию в массиве
    $config['version'] = $newVersion;
    
    // Готовим новое содержимое
    $newContent = "<?php\n";
    $newContent .= "if (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен'); }\n\n";
    $newContent .= "return [\n";
    
    // Вручную формируем массив для надежности
    foreach ($config as $key => $value) {
        $newContent .= "    '".addslashes($key)."' => ";
        
        if (is_null($value)) {
            $newContent .= "null";
        } elseif (is_bool($value)) {
            $newContent .= $value ? 'true' : 'false';
        } elseif (is_int($value) || is_float($value)) {
            $newContent .= $value;
        } else {
            $newContent .= "'".addslashes($value)."'";
        }
        
        $newContent .= ",\n";
    }
    
    $newContent .= "];\n";
    
    // Создаем временный файл
    $tempFile = tempnam(sys_get_temp_dir(), 'config_');
    if (file_put_contents($tempFile, $newContent) === false) {
        throw new Exception("Не удалось записать временный конфигурационный файл");
    }
    
    // Проверяем, что файл валиден
    try {
        $testConfig = require $tempFile;
        if (($testConfig['version'] ?? null) !== $newVersion) {
            throw new Exception("Проверка версии не удалась");
        }
    } catch (Exception $e) {
        unlink($tempFile);
        throw new Exception("Сгенерированный конфиг содержит ошибки: ".$e->getMessage());
    }
    
    // Делаем бэкап оригинального файла
    $backupFile = $configFile.'.bak';
    if (!copy($configFile, $backupFile)) {
        unlink($tempFile);
        throw new Exception("Не удалось создать резервную копию конфига");
    }
    
    // Пытаемся обновить основной конфиг
    if (!rename($tempFile, $configFile)) {
        // Пытаемся восстановить из бэкапа
        if (file_exists($backupFile)) {
            copy($backupFile, $configFile);
        }
        throw new Exception("Не удалось заменить конфигурационный файл");
    }
    
    // Финальная проверка
    $finalConfig = require $configFile;
    if (($finalConfig['version'] ?? null) !== $newVersion) {
        throw new Exception("Финальная проверка версии не пройдена");
    }
    
    return true;
}
    /**
     * Создает резервную копию перед обновлением
     */
    private function createBackup() {
        require_once __DIR__.'/../admin/backup_db.php';
        
        $backupDir = $this->config['backup_dir'] ?? 'admin/backups/';
        $backupFile = $backupDir.'pre_update_'.date('Y-m-d_His').'.sql';
        
        // Создаем резервную копию БД
        dbBackup(__DIR__.'/../'.$backupFile, true);
        
        return $backupFile;
    }
    
    /**
     * Рекурсивное копирование директории
     */
    private function copyDirectory($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..' && $file != 'install.php') {
                if (is_dir($src.'/'.$file)) {
                    $this->copyDirectory($src.'/'.$file, $dst.'/'.$file);
                } else {
                    copy($src.'/'.$file, $dst.'/'.$file);
                }
            }
        }
        
        closedir($dir);
    }
    
    /**
     * Рекурсивное удаление директории
     */
    private function removeDirectory($dir) {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
    
    /**
     * Выполняет SQL-скрипт обновления
     */
    private function executeUpdateSql($file) {
        $sql = file_get_contents($file);
        
        if (!empty($this->config['db_prefix'])) {
            $sql = str_replace('{PREFIX_}', $this->config['db_prefix'], $sql);
        }
        
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($queries as $query) {
            if (!empty($query)) {
                $this->pdo->exec($query);
            }
        }
    }
	/**
	 * Восстанавливает систему из резервной копии
	 * 
	 * @param string $backupFile Путь к файлу резервной копии
	 * @return bool
	 * @throws Exception
	 */
	private function restoreBackup($backupFile) {
		if (!file_exists($backupFile)) {
			throw new Exception("Файл резервной копии не найден");
		}

		try {
			// Проверяем расширение файла
			if (!preg_match('/\.sql$/i', $backupFile)) {
				throw new Exception("Недопустимый формат файла резервной копии");
			}

			// Читаем SQL файл
			$sql = file_get_contents($backupFile);
			if ($sql === false) {
				throw new Exception("Не удалось прочитать файл резервной копии");
			}

			// Удаляем комментарии и пустые строки
			$sql = preg_replace('/\-\-.*$/m', '', $sql);
			$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
			$sql = preg_replace('/^\s*$/m', '', $sql);

			// Разбиваем на отдельные запросы
			$queries = array_filter(array_map('trim', explode(';', $sql)));

			// Выполняем каждый запрос в транзакции
			$this->pdo->beginTransaction();
			
			foreach ($queries as $query) {
				if (!empty($query)) {
					$this->pdo->exec($query);
				}
			}
			
			$this->pdo->commit();
			
			return true;
		} catch (Exception $e) {
			if ($this->pdo->inTransaction()) {
				$this->pdo->rollBack();
			}
			throw new Exception("Ошибка восстановления из резервной копии: " . $e->getMessage());
		}
	}
}