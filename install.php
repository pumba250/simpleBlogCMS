<?php
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    die('Требуется PHP версии 7.0 или выше.');
}

function executeSqlFile($filename, $pdo, $prefix = '') {
    $sql = file_get_contents($filename);
    
    if (!empty($prefix)) {
        $sql = str_replace('{PREFIX_}', $prefix, $sql);
    }
    $pdo->exec($sql);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dbHost = $_POST['db_host'];
    $dbName = $_POST['db_name'];
    $dbUser = $_POST['db_user'];
    $dbPass = $_POST['db_pass'];
    $dbPrefix = isset($_POST['db_prefix']) ? trim($_POST['db_prefix']) : '';

    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        executeSqlFile('sql/schema.sql', $pdo, $dbPrefix);
        
        if (!file_exists('config')) {
            mkdir('config', 0755, true);  // Создаём директорию с правами 0755
        }
        
        file_put_contents('config/config.php', "<?php\n\nreturn [\n  'host' => '$dbHost',\n  'database' => '$dbName',\n  'db_user' => '$dbUser',\n  'db_pass' => '$dbPass',\n  'templ' => 'simple',\n  'db_prefix' => '$dbPrefix',\n  'powered' => 'simpleBlog',\n  'version' => 'v0.2.4',\n  ];");

        echo "<p>Установка завершена успешно!<br>Не забудьте удалить install.php и папку sql!</p>";
		echo '<meta http-equiv="refresh" content="3;URL=?action=register">';
    } catch (PDOException $e) {
        die("Ошибка подключения: " . $e->getMessage());
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
        <label for="db_host">Хост БД:</label>
        <input type="text" name="db_host" required>
        <br>
        <label for="db_name">Имя БД:</label>
        <input type="text" name="db_name" required>
        <br>
        <label for="db_user">Пользователь БД:</label>
        <input type="text" name="db_user" required>
        <br>
        <label for="db_pass">Пароль БД:</label>
        <input type="password" name="db_pass" required>
        <br>
        <label for="db_prefix">Префикс таблиц (необязательно):</label>
        <input type="text" name="db_prefix" placeholder="Например: myprefix_">
        <br>
        <button type="submit">Установить</button>
    </form>
</body>
</html>
