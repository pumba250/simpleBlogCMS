<?php
/**
 * Installation wizard for SimpleBlog CMS
 * 
 * @package    SimpleBlog
 * @subpackage Installer
 * @version    0.6.8
 * @author     pumba250 
 * 
 * @todo       Add database version checking
 * @todo       Implement upgrade path for existing installations
 * 
 * This script handles:
 * - Database connection testing
 * - Schema initialization
 * - Configuration file generation
 * - Post-install cleanup
 */
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    die('Требуется PHP версии 7.0 или выше.');
}

session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validateInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function executeSqlFile($filename, $pdo, $prefix = '') {
    if (!file_exists($filename)) {
        throw new Exception("SQL файл не найден");
    }
    
    $sql = file_get_contents($filename);
    
    if (!empty($prefix)) {
        $prefix = rtrim($prefix, '_');
        $prefix .= '_';
        
        if (!preg_match('/^[a-zA-Z0-9]+$/', $prefix)) {
            throw new Exception("Префикс таблиц должен содержать только буквы и цифры");
        }
        
        $sql = str_replace('{PREFIX_}', $prefix, $sql);
    }
    
    $queries = explode(';', $sql);
    foreach ($queries as $query) {
        if (trim($query)) {
            $pdo->exec($query);
        }
    }
}

function testDatabaseConnection($host, $name, $user, $pass) {
    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$name'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}
function deleteInstallationFiles() {
    // Удаляем текущий файл (install.php)
    if (file_exists(__FILE__)) {
        unlink(__FILE__);
    }
    
    // Удаляем папку sql (если она существует)
    $sqlDir = dirname(__FILE__) . '/sql';
    if (file_exists($sqlDir) && is_dir($sqlDir)) {
        array_map('unlink', glob("$sqlDir/*.*"));
        rmdir($sqlDir);
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Неверный CSRF-токен");
    }

    $dbHost = validateInput($_POST['db_host']);
    $dbName = validateInput($_POST['db_name']);
    $dbUser = validateInput($_POST['db_user']);
    $dbPass = $_POST['db_pass']; 
    $dbPrefix = isset($_POST['db_prefix']) ? validateInput($_POST['db_prefix']) : '';
	$dbPrefix = rtrim($dbPrefix, '_');
	if (!empty($dbPrefix)) {
		$dbPrefix .= '_';
	}

    if (file_exists('config/config.php')) {
        die("Конфигурационный файл уже существует. Удалите его для повторной установки.");
    }

    if (!testDatabaseConnection($dbHost, $dbName, $dbUser, $dbPass)) {
        die("Не удалось подключиться к БД или БД не существует");
    }

    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);

        executeSqlFile('sql/schema.sql', $pdo, $dbPrefix);
        
        if (!file_exists('config')) {
            mkdir('config', 0755, true);
        }
        
        $configContent = "<?php\n\nreturn [\n  'host' => '$dbHost',\n  'database' => '$dbName',\n  'db_user' => '$dbUser',\n  'db_pass' => '$dbPass',\n 'home_title' => 'Заголовок вашего сайта',\n  'templ' => 'simple',\n  'db_prefix' => '$dbPrefix',\n 'csrf_token_name' => 'csrf_token',\n  'max_backups' => 5,\n  'backup_schedule' => 'disabled',\n  'backup_dir' => 'admin/backups/',\n 'blocks_for_reg' => true,\n  'metaKeywords' => 'Здесь ключевые слова, через запятую (,)',\n  'metaDescription' => 'Здесь описание вашего сайта',\n 'mail_from' => 'no-reply@yourdomain.com',\n 'mail_from_name' => 'SimpleBlog Notifications',\n 'powered' => 'simpleBlog',\n  'version' => 'v0.6.8',\n  ];";
        
        if (file_put_contents('config/config.php', $configContent) === false) {
            throw new Exception("Не удалось записать конфигурационный файл");
        }

        echo "<p>Установка завершена успешно!</p>";
        
        if ($deleteFiles) {
            echo "<p>Удаление установочных файлов...</p>";
            deleteInstallationFiles();
            echo "<p>Установочные файлы удалены.</p>";
        } else {
            echo "<p>Не забудьте удалить install.php и папку sql вручную!</p>";
        }
        echo '<meta http-equiv="refresh" content="3;URL=/?action=register">';
		die;
    } catch (PDOException $e) {
        die("Ошибка подключения: " . $e->getMessage());
    } catch (Exception $e) {
        die("Ошибка: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Установка simpleBlog</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        label { display: inline-block; width: 180px; margin-bottom: 10px; }
        input[type="text"], input[type="password"] { width: 300px; padding: 5px; }
        .checkbox-label { width: auto; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>Установка simpleBlog</h1>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <label for="db_host">Хост БД:</label>
        <input type="text" name="db_host" required pattern="[a-zA-Z0-9\.\-]+" value="localhost">
        <br>
        <label for="db_name">Имя БД:</label>
        <input type="text" name="db_name" required pattern="[a-zA-Z0-9_\-]+">
        <br>
        <label for="db_user">Пользователь БД:</label>
        <input type="text" name="db_user" required pattern="[a-zA-Z0-9_\-]+">
        <br>
        <label for="db_pass">Пароль БД:</label>
        <input type="password" name="db_pass" required>
        <br>
        <label for="db_prefix">Префикс таблиц (желательно):</label>
        <input type="text" name="db_prefix" placeholder="Например: myprefix" pattern="[a-zA-Z0-9_]+" title="Префикс должен содержать только буквы и цифры">
        <br>
        <label>
            <input type="checkbox" name="delete_files" value="1" checked>
            <span class="checkbox-label">Удалить установочные файлы после успешной установки?</span>
        </label>
        <br><br>
        <button type="submit">Установить</button>
    </form>
</body>
</html>
