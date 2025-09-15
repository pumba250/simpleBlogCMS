<?php

if (!defined('IN_SIMPLECMS')) {
    die('Прямой доступ запрещен');
}

/**
 * Класс для работы с кэшированием данных
 *
 * @package    SimpleBlog
 * @subpackage Core
 * @category   Performance
 * @version    0.9.8
 *
 * @method static bool   has(string $key)                  Проверяет наличие данных в кэше
 * @method static mixed  get(string $key)                  Получает данные из кэша
 * @method static bool   set(string $key, mixed $data, int $ttl = 3600) Сохраняет данные в кэш
 * @method static bool   delete(string $key)               Удаляет данные из кэша
 * @method static void   clear()                           Очищает весь кэш
 * @method static string getCacheKey(string $identifier)   Генерирует ключ кэша
 * @method static void   init(array $config)               Инициализирует кэш с указанной конфигурацией
 */

class Cache
{
    private static $driver;
    private static $config;
    private static $enabled = true;
    private static $cacheDir = __DIR__ . '/../cache/';

    public static function init(array $config): void
    {
        self::$config = $config;
        self::$enabled = $config['cache_enabled'] ?? true;

        if (!self::$enabled) {
            return;
        }

        $driver = strtolower($config['cache_driver'] ?? 'file');

        try {
            switch ($driver) {
                case 'redis':
                    if (extension_loaded('redis')) {
                        $redis = new Redis();
                        $redis->connect(
                            $config['redis_host'] ?? '127.0.0.1',
                            $config['redis_port'] ?? 6379
                        );
                        if (!empty($config['redis_password'])) {
                            $redis->auth($config['redis_password']);
                        }
                        self::$driver = $redis;
                    }
                    break;

                case 'memcached':
                    if (extension_loaded('memcached')) {
                        $memcached = new Memcached();
                        $memcached->addServer(
                            $config['memcached_host'] ?? '127.0.0.1',
                            $config['memcached_port'] ?? 11211
                        );
                        self::$driver = $memcached;
                    }
                    break;

                case 'apcu':
                    if (extension_loaded('apcu') && ini_get('apc.enabled')) {
                        self::$driver = 'apcu';
                    }
                    break;

                case 'file':
                default:
                    self::ensureCacheDir();
                    self::$driver = 'file';
            }
        } catch (Exception $e) {
            error_log("Cache initialization failed: " . $e->getMessage());
            self::$enabled = false;
        }
    }

    private static function ensureCacheDir(): void
    {
        if (!is_dir(self::$cacheDir)) {
            if (!mkdir(self::$cacheDir, 0700, true)) {
                throw new RuntimeException('Failed to create cache directory');
            }
            // Add protection file
            file_put_contents(self::$cacheDir . '.htaccess', "Deny from all\n");
        }
    }

    private static function validateCacheKey(string $key): void
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
            throw new InvalidArgumentException('Invalid cache key format');
        }
    }

    private static function getCacheFilePath(string $key): string
    {
        self::validateCacheKey($key);
        $file = self::$cacheDir . $key . '.cache';

        // Protection against directory traversal
        $realPath = realpath(dirname($file)) . DIRECTORY_SEPARATOR . basename($file);
        if (strpos($realPath, realpath(self::$cacheDir)) !== 0) {
            throw new RuntimeException('Invalid cache path detected');
        }

        return $file;
    }

    public static function has(string $key): bool
    {
        if (!self::$enabled) {
            return false;
        }

        $key = self::getCacheKey($key);

        switch (self::$driver) {
            case 'redis':
                return self::$driver->exists($key);

            case 'memcached':
                self::$driver->get($key);
                return self::$driver->getResultCode() === Memcached::RES_SUCCESS;

            case 'apcu':
                return apcu_exists($key);

            case 'file':
                try {
                    $file = self::getCacheFilePath($key);
                    if (!file_exists($file)) {
                        return false;
                    }

                    $content = file_get_contents($file);
                    if ($content === false) {
                        return false;
                    }

                    $data = unserialize($content);
                    return isset($data['expire']) && $data['expire'] > time();
                } catch (Exception $e) {
                    error_log("Cache has() error: " . $e->getMessage());
                    return false;
                }
        }

        return false;
    }

    public static function get(string $key)
    {
        if (!self::$enabled) {
            return null;
        }

        $key = self::getCacheKey($key);

        switch (self::$driver) {
            case 'redis':
                $data = self::$driver->get($key);
                return $data ? unserialize($data) : null;

            case 'memcached':
                $data = self::$driver->get($key);
                return $data ?: null;

            case 'apcu':
                return apcu_fetch($key);

            case 'file':
                try {
                    $file = self::getCacheFilePath($key);
                    if (!file_exists($file)) {
                        return null;
                    }

                    $content = file_get_contents($file);
                    if ($content === false) {
                        return null;
                    }

                    $data = unserialize($content);
                    if (!is_array($data)) {
                        unlink($file);
                        return null;
                    }

                    if (isset($data['user_id']) && $data['user_id'] != ($_SESSION['user_id'] ?? null)) {
                        self::delete($key);
                        return null;
                    }

                    if (isset($data['expire']) && $data['expire'] < time()) {
                        unlink($file);
                        return null;
                    }

                    return $data['content'] ?? null;
                } catch (Exception $e) {
                    error_log("Cache get() error: " . $e->getMessage());
                    return null;
                }
        }

        return null;
    }

    public static function set(string $key, $data, int $ttl = 3600): bool
    {
        if (!self::$enabled) {
            return false;
        }

        $key = self::getCacheKey($key);

        switch (self::$driver) {
            case 'redis':
                return self::$driver->set($key, serialize($data), $ttl);

            case 'memcached':
                return self::$driver->set($key, $data, $ttl);

            case 'apcu':
                return apcu_store($key, $data, $ttl);

            case 'file':
                try {
                    $file = self::getCacheFilePath($key);
                    $content = [
                        'content' => $data,
                        'expire' => time() + $ttl,
                        'created_at' => time()
                    ];

                    // Atomic write via temp file
                    $tempFile = tempnam(self::$cacheDir, 'tmp_');
                    if ($tempFile === false) {
                        return false;
                    }

                    if (file_put_contents($tempFile, serialize($content), LOCK_EX) === false) {
                        unlink($tempFile);
                        return false;
                    }

                    if (!rename($tempFile, $file)) {
                        unlink($tempFile);
                        return false;
                    }

                    chmod($file, 0600);
                    return true;
                } catch (Exception $e) {
                    error_log("Cache set() error: " . $e->getMessage());
                    return false;
                }
        }

        return false;
    }

    public static function delete(string $key): bool
    {
        if (!self::$enabled) {
            return false;
        }

        $key = self::getCacheKey($key);

        switch (self::$driver) {
            case 'redis':
                return self::$driver->del($key) > 0;

            case 'memcached':
                return self::$driver->delete($key);

            case 'apcu':
                return apcu_delete($key);

            case 'file':
                try {
                    $file = self::getCacheFilePath($key);
                    if (file_exists($file)) {
                        return unlink($file);
                    }
                    return true;
                } catch (Exception $e) {
                    error_log("Cache delete() error: " . $e->getMessage());
                    return false;
                }
        }

        return false;
    }

    public static function clear(): void
    {
        if (!self::$enabled) {
            return;
        }

        switch (self::$driver) {
            case 'redis':
                self::$driver->flushDB();
                break;

            case 'memcached':
                self::$driver->flush();
                break;

            case 'apcu':
                apcu_clear_cache();
                break;

            case 'file':
                try {
                    $files = glob(self::$cacheDir . '*.cache');
                    if ($files === false) {
                        return;
                    }

                    foreach ($files as $file) {
                        if (is_file($file) && strpos($file, '..') === false) {
                            unlink($file);
                        }
                    }
                } catch (Exception $e) {
                    error_log("Cache clear() error: " . $e->getMessage());
                }
        }
    }

    public static function getCacheKey(string $identifier): string
    {
        return md5($identifier . (self::$config['cache_key_salt'] ?? 'simpleblog'));
    }
}
