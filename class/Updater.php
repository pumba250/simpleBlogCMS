<?php
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
	@unlink($this->lastCheckFile);
    if ($this->config['disable_update_check'] ?? true) {
        return false;
    }

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
     * Получает последний релиз с GitHub
     */
    private function getLatestGitHubRelease() {
    $url = "https://api.github.com/repos/{$this->githubRepo}/releases/latest";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'SimpleBlog CMS Updater',
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.github.v3+json'
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true
    ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception("GitHub API error: ".curl_error($ch));
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new Exception("GitHub API returned $httpCode");
        }
        
        curl_close($ch);
        
        return json_decode($response, true);
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
        $updateInfo = $this->getUpdateInfo();
        if (!$updateInfo) {
            throw new Exception("Нет информации об обновлении");
        }
        
        // Создаем резервную копию
        $backupFile = $this->createBackup();
        
        try {
            // Загружаем обновление
            $package = $this->downloadUpdate($updateInfo['download_url']);
            
            // Проверяем хеш
            if ($updateInfo['sha256'] && hash('sha256', $package) !== $updateInfo['sha256']) {
                throw new Exception("Хеш пакета не совпадает");
            }
            
            // Применяем обновление
            $this->applyUpdate($package);
            
            // Обновляем версию в конфиге
            $this->updateConfigVersion($updateInfo['new_version']);
            
            return true;
        } catch (Exception $e) {
            // Восстанавливаем из резервной копии при ошибке
            if (file_exists($backupFile)) {
                $this->restoreBackup($backupFile);
            }
            throw $e;
        }
    }
    
    /**
     * Загружает обновление
     */
    private function downloadUpdate($url) {
    $tempFile = tempnam(sys_get_temp_dir(), 'sb_update_');
    
    // Для GitHub добавляем заголовок Accept
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'SimpleBlog CMS Updater',
        CURLOPT_HTTPHEADER => [
            'Accept: application/octet-stream'
        ],
        CURLOPT_TIMEOUT => 300,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FILE => fopen($tempFile, 'w+')
    ]);
        
        if (!curl_exec($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            unlink($tempFile);
            throw new Exception("Ошибка загрузки: $error");
        }
        
        curl_close($ch);
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        return $content;
    }
    
    /**
     * Применяет обновление из ZIP-архива
     */
    private function applyUpdate($zipContent) {
        $tempDir = sys_get_temp_dir().'/sb_update_'.md5(time());
        mkdir($tempDir, 0755);
        
        $zipFile = $tempDir.'/update.zip';
        file_put_contents($zipFile, $zipContent);
        
        $zip = new ZipArchive;
        if ($zip->open($zipFile) !== true) {
            throw new Exception("Не удалось открыть архив обновления");
        }
        
        if (!$zip->extractTo($tempDir)) {
            $zip->close();
            throw new Exception("Ошибка распаковки архива");
        }
        $zip->close();
        unlink($zipFile);
        
        // Копируем файлы
        $this->copyDirectory($tempDir.'/files', dirname(__DIR__));
        
        // Выполняем SQL-скрипты
        if (file_exists($tempDir.'/update.sql')) {
            $this->executeUpdateSql($tempDir.'/update.sql');
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
     * Создает резервную копию перед обновлением
     */
    private function createBackup() {
        require_once __DIR__.'/../admin/backup_db.php';
        
        $backupDir = $this->config['backup_dir'] ?? 'admin/backups/';
        $backupFile = $backupDir.'pre_update_'.date('Y-m-d_His').'.zip';
        
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
            if ($file != '.' && $file != '..') {
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
}