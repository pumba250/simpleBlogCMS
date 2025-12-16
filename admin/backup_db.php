<?php
if (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен'); }
/**
 * Создает резервную копию базы данных
 */
function dbBackup($fname, $gzmode, $tlist = '') {
    global $pdo, $dbPrefix, $backupDir, $maxBackups, $version;
    cleanOldBackups($backupDir, $maxBackups);

    // Проверка поддержки gzip
    if ($gzmode && !function_exists('gzopen')) {
        $gzmode = false;
    }

    // Открытие файла
    if ($gzmode) {
        $fh = gzopen($fname, "w");
    } else {
        $fh = fopen($fname, "w");
    }

    if ($fh === false) {
        return false;
    }

    // Получение списка таблиц с информацией о зависимостях
    if (!is_array($tlist)) {
        $tlist = [];
        $stmt = $pdo->query("SHOW TABLES LIKE '{$dbPrefix}%'");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tlist[] = $row[0];
        }
    }

    // Запись заголовка с командами отключения внешних ключей
    $out  = "# ".str_repeat('=', 60)."\n";
    $out .= "# Backup file for simpleBlog\n";
    $out .= "# ".str_repeat('=', 60)."\n";
    $out .= "# DATE: ".gmdate("d-m-Y H:i:s", time())." GMT\n";
    $out .= "# VERSION: ".($version ?? 'unknown')."\n#\n";
    $out .= "# List of tables for backup: ".implode(", ", $tlist)."\n#\n";
    $out .= "\n# Отключение проверки внешних ключей\n";
    $out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $out .= "SET NAMES utf8;\n";
    $out .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";

    if ($gzmode) {
        gzwrite($fh, $out);
    } else {
        fwrite($fh, $out);
    }

    // Получаем информацию о зависимостях таблиц
    $tableDependencies = [];
    foreach ($tlist as $tname) {
        // Получаем список внешних ключей для каждой таблицы
        $stmt = $pdo->query("SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND REFERENCED_TABLE_NAME IS NOT NULL
            AND TABLE_NAME = '{$tname}'");
        
        $dependencies = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dependencies[] = $row['REFERENCED_TABLE_NAME'];
        }
        $tableDependencies[$tname] = $dependencies;
    }

    // Сортируем таблицы так, чтобы таблицы без зависимостей шли первыми
    $sortedTables = [];
    $processed = [];
    
    function sortTables($table, $dependencies, &$sortedTables, &$processed) {
        if (in_array($table, $processed)) {
            return;
        }
        
        $processed[] = $table;
        
        // Сначала обрабатываем зависимости
        foreach ($dependencies[$table] as $dependency) {
            if (isset($dependencies[$dependency])) {
                sortTables($dependency, $dependencies, $sortedTables, $processed);
            }
        }
        
        $sortedTables[] = $table;
    }
    
    foreach ($tableDependencies as $table => $deps) {
        sortTables($table, $tableDependencies, $sortedTables, $processed);
    }
    
    // Удаляем дубликаты, сохраняя порядок
    $sortedTables = array_values(array_unique($sortedTables));

    // Обработка каждой таблицы в правильном порядке
    foreach ($sortedTables as $tname) {
        try {
            // Получение структуры таблицы
            $stmt = $pdo->query("SHOW CREATE TABLE `{$tname}`");
            $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($createTable) {
                $out  = "\n#\n# Table `{$tname}`\n#\n";
                $out .= "DROP TABLE IF EXISTS `{$tname}`;\n";
                $out .= $createTable['Create Table'].";\n";

                if ($gzmode) {
                    gzwrite($fh, $out);
                } else {
                    fwrite($fh, $out);
                }

                // Получение данных таблицы
                $stmt = $pdo->query("SELECT * FROM `{$tname}`");
                $rowCount = 0;
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $values = array_map(function($value) use ($pdo) {
                        if ($value === null) return 'NULL';
                        return $pdo->quote($value);
                    }, $row);
                    
                    $out = "INSERT INTO `{$tname}` (`".implode('`, `', array_keys($row))."`) VALUES (".implode(', ', $values).");\n";
                    
                    if ($gzmode) {
                        gzwrite($fh, $out);
                    } else {
                        fwrite($fh, $out);
                    }
                    
                    $rowCount++;
                }

                $out = "# Total records: {$rowCount}\n";
                if ($gzmode) {
                    gzwrite($fh, $out);
                } else {
                    fwrite($fh, $out);
                }
            }
        } catch (PDOException $e) {
            $out = "#% Error processing table `{$tname}`: ".$e->getMessage()."\n";
            if ($gzmode) {
                gzwrite($fh, $out);
            } else {
                fwrite($fh, $out);
            }
        }
    }

    // В конце файла включить проверку внешних ключей
    $out = "\n# Включение проверки внешних ключей\n";
    $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    if ($gzmode) {
        gzwrite($fh, $out);
    } else {
        fwrite($fh, $out);
    }

    // Закрытие файла
    if ($gzmode) {
        gzclose($fh);
    } else {
        fclose($fh);
    }

    return true;
}

/**
 * Удаляет старые резервные копии, оставляя только указанное количество
 */
function cleanOldBackups($backupDir, $maxBackups) {
    $backups = glob($backupDir . 'backup_*.sql');
    
    if (count($backups) > $maxBackups) {
        // Сортируем по дате (старые в начале)
        usort($backups, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Удаляем лишние файлы
        $toDelete = count($backups) - $maxBackups;
        for ($i = 0; $i < $toDelete; $i++) {
            unlink($backups[$i]);
        }
    }
}

/**
 * Проверяет, нужно ли создавать резервную копию по расписанию
 */
function checkScheduledBackup($pdo, $config) {
	global $pdo, $dbPrefix, $backupDir, $maxBackups, $version;
    $lastBackupFile = getLastBackupFile($config['backup_dir']);
    $lastBackupTime = $lastBackupFile ? filemtime($lastBackupFile) : 0;
    
    $now = time();
    $needBackup = false;
    
    switch ($config['backup_schedule']) {
        case 'daily':
            $needBackup = ($now - $lastBackupTime) > 86400; // 24 часа
            break;
        case 'weekly':
            $needBackup = ($now - $lastBackupTime) > 604800; // 7 дней
            break;
        case 'monthly':
            $needBackup = ($now - $lastBackupTime) > 2592000; // 30 дней
            break;
    }
	
    if ($needBackup) {
        dbBackup(__DIR__.'/../'. $backupDir . 'backup_auto_'.date('Y-m-d_H-i-s').'.sql', false);
    }
}

/**
 * Возвращает последний созданный файл резервной копии
 */
function getLastBackupFile($backupDir) {
    $backups = glob($backupDir . 'backup_*.sql');
    if (empty($backups)) return null;
    
    // Сортируем по дате (новые в начале)
    usort($backups, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    return $backups[0];
}