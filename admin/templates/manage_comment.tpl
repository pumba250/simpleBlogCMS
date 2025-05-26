<!DOCTYPE html>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><? echo $pageTitle; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link id="theme-style" rel="stylesheet" href="/css/w3.css">
<link id="theme-style" rel="stylesheet" href="/css/css">
<style>
body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
</style>
</head>
<body class="w3-light-grey">
<!-- w3-content defines a container for fixed size centered content, 
and is wrapped around the whole page content, except for the footer in this example -->
<div class="w3-content" style="max-width:1400px">
<!-- Header -->
<header class="w3-container w3-center w3-padding-32"> 
  <h1>Панель управления</h1>
  <h2>Управление комментариями</h2>
</header>
<!-- Grid -->
<div class="w3-row">
<!-- Blog entries -->
<div class="w3-col l8 s12">
        <div class="w3-card-4 w3-margin w3-white">
        <div class="w3-container">
            <h5><span class="w3-opacity"></span></h5>
        </div>
        <div class="w3-container">
            <p>
        <?php

$config = require '../config/config.php';

try {
	// Получаем необходимые параметры из конфигурационного файла
    $host = $config['host'];
    $database = $config['database'];
    $db_user = $config['db_user'];
    $db_pass = $config['db_pass'];
	// Устанавливаем соединение с базой данных
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
require '../class/Comments.php';

$comments = new Comments($pdo);

$pendingComments = $comments->getPendingComments();
if (empty($pendingComments)) {
    // Если массив пустой, отображаем сообщение
    echo '<p>Нет комментариев на модерацию.</p>';
} else {
		echo "<table>
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Сообщение</th>
            <th>Действия</th>
        </tr>";
        // Получить список обратной связи
        foreach ($pendingComments as $comment) {
			/*echo '<div>';
			echo '<strong>' . htmlspecialchars($comment['user_name']) . ':</strong>';
			echo '<p>' . htmlspecialchars($comment['user_text']) . '</p>';
			echo '<form method="post">';
			echo '<input type="hidden" name="comment_id" value="' . $comment['id'] . '">';
			echo '<button type="submit" name="approve">Одобрить</button>';
			echo '<button type="submit" name="reject">Удалить</button>';
			echo '</form>';
			echo '</div>';*/
			
			echo "<tr>
                    <td>{$comment['id']}</td>
                    <td>{$comment['user_name']}</td>
                    <td>{$comment['user_text']}</td>
                    <td>
                        <form method='POST'>
                            <input type='hidden' name='comment_id' value='{$comment['id']}'>
							<button type='submit' name='approve'>Одобрить</button>
							<button type='submit' name='reject'>Удалить</button>
                        </form>
                    </td>
                  </tr>";
		}
}
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentId = $_POST['comment_id'];
    if (isset($_POST['approve'])) {
        $comments->approveComment($commentId);
    } elseif (isset($_POST['reject'])) {
        $comments->rejectComment($commentId);
    }
    // Перенаправление обратно на страницу с комментариями
    header('Location: index.php?view=manage_comment');
    exit;
}
        ?>
    </table></p> <!-- Полное содержание -->
            <div class="w3-row">
                <div class="w3-col m8 s12">
                    <p></p>
                </div>
            </div>
        </div>
    </div>
    <hr>
</div>
<!-- Introduction menu -->
<div class="w3-col l4">
  <!-- About Card -->
  <div class="w3-card w3-margin w3-margin-top">
  <?php if ($user): ?><img src="https://yunisov.tech<?= htmlspecialchars($user['avatar']);?>" style="width:120px"><?php endif; ?>
    <div class="w3-container w3-white">
            <h4>Навигация</h4>
        <div class="w3-container w3-white">
			<p><a href="/">BLOG</a></p>
			<p><a href="index.php?view=manage_users">Управление пользователями</a></p>
			<p><a href="index.php?view=manage_comment">Управление Коментариями</a></p>
			<p><a href="index.php?view=template_settings">Управление Шаблонами</a></p>
			<p><a href="index.php?view=manage_feedback">Обратная связь</a></p>
			<p><a href="index.php?view=add_news">Добавить запись в блог</a></p>
        </div>
    </div>
  </div><hr>
</div>
</div><br>
</div>
<!-- Footer -->
<footer class="w3-container w3-dark-grey w3-padding-32 w3-margin-top w3-center">
  <p>Powered by <a href="https://www.w3schools.com/w3css/default.asp" target="_blank">w3.css</a> Copyright &copy; <?php echo date("Y");?> by <b><?php echo $_SERVER['SERVER_NAME'];?></b></p>
</footer>
</body></html>