<?php
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
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) {
            throw new Exception("Недопустимый префикс таблиц");
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Неверный CSRF-токен");
    }

    $dbHost = validateInput($_POST['db_host']);
    $dbName = validateInput($_POST['db_name']);
    $dbUser = validateInput($_POST['db_user']);
    $dbPass = $_POST['db_pass']; 
    $dbPrefix = isset($_POST['db_prefix']) ? validateInput($_POST['db_prefix']) : '';

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

        try {
            $pdo->exec("SELECT 1");
        } catch (PDOException $e) {
            die("Ошибка тестового запроса к БД: " . $e->getMessage());
        }

        executeSqlFile('sql/schema.sql', $pdo, $dbPrefix);
        
        if (!file_exists('config')) {
            mkdir('config', 0755, true);
        }
        
        $configContent = "<?php\n\nreturn [\n  'host' => '$dbHost',\n  'database' => '$dbName',\n  'db_user' => '$dbUser',\n  'db_pass' => '$dbPass',\n  'templ' => 'simple',\n  'db_prefix' => '$dbPrefix',\n  'powered' => 'simpleBlog',\n  'version' => 'v0.3',\n  ];";
        
        if (file_put_contents('config/config.php', $configContent) === false) {
            throw new Exception("Не удалось записать конфигурационный файл");
        }

        echo "<p>Установка завершена успешно!<br>Не забудьте удалить install.php и папку sql!</p>";
        echo '<meta http-equiv="refresh" content="3;URL=?action=register">';
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
</head>
<body>
    <h1>Установка simpleBlog</h1>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <label for="db_host">Хост БД:</label>
        <input type="text" name="db_host" required pattern="[a-zA-Z0-9\.\-]+">
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
        <label for="db_prefix">Префикс таблиц (необязательно):</label>
        <input type="text" name="db_prefix" placeholder="Например: myprefix_" pattern="[a-zA-Z0-9_]+">
        <br>
        <button type="submit">Установить</button>
    </form>
</body>
</html>
