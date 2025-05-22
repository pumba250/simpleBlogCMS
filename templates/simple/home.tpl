<?php include __DIR__ . '/header.tpl'; 
/* Blog entry */
// Проверяем, есть ли параметр id в GET-запросе
if (isset($_GET['id'])) {
    // Получаем одну новость по id
    $newsId = (int)$_GET['id'];
    $newsItem = $news->getNewsById($newsId); // Получаем одну новость
	$commentsList = $comments->getComments($newsId);

    if ($newsItem): // Если новость найдена

$randomImage = $themeimg->getRandomImage($newsItem['title']);
?>
    <div class="w3-card-4 w3-margin w3-white">
        
        <div class="w3-container">
            <h3><img src="<?= htmlspecialchars($randomImage) ?>" alt="image" style="width:13%;"><b><?= htmlspecialchars($newsItem['title']) ?></b>
                    <?php if (isset($user) && $user['isadmin'] == 9): ?>
                        / <a href="/admin/edit_news.php?id=<?= $newsItem['id'] ?>">edit</a>
                        or <a href="/admin/delete_news.php?id=<?= $newsItem['id'] ?>">delete</a>
                    <?php endif; ?>
                    </h3>
            <h5><span class="w3-opacity"><?= htmlspecialchars($newsItem['created_at']) ?></span></h5>
        </div>
        <div class="w3-container">
            <p><?= ($newsItem['content']) ?></p> <!-- Полное содержание -->
            <div class="w3-row">
                <div class="w3-col m8 s12">
                    <p><a href="?"><button class="w3-button w3-padding-large w3-white w3-border"><b>
                        Вернуться »
                    </b></button></a></p>
                </div>
            </div>
        </div>
    </div>
	<div class="w3-card-4 w3-margin w3-white">
        <div class="w3-container">
            <h3><b>Comments:</b></h3>
            <h5><span class="w3-opacity"></span></h5>
        </div>
        <div class="w3-container">
            <p>
			<?
			if (empty($commentsList)) {
				// Если массив пустой, отображаем сообщение
				echo '<p>Нет комментариев.</p>';
			} else {
						foreach ($commentsList as $comment) {
						   echo '<div>';
						   echo '<strong>' . htmlspecialchars($comment['user_name']) . ':</strong>';
						   echo '<p>' . htmlspecialchars($comment['user_text']) . '</p>';
						   echo '</div>';
					   }
			}
			?><hr>
			<?
			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			   $userName = isset($user['username']) ? $user['username'] : trim($_POST['user_name']);
			   $userText = trim($_POST['user_text']);
			   // Проверка на некорректный ввод
        if (!empty($userName) && !empty($userText)) {
            $comments->addComment(0, 0, $newsId, $userName, $userText);
            echo '<p>Ваш комментарий был добавлен.</p>';
        } else {
            echo '<p>Имя и комментарий не могут быть пустыми.</p>';
        }
		   }
		   //var_dump($user);
		   ?>

		   <form method="post">
			   <?php if (!isset($user['username'])): ?>
				<input type="text" name="user_name" required placeholder="Ваше имя"><br>
			<?php endif; ?>
			   <textarea style="width: 300px; height: 80px;" name="user_text" required placeholder="Ваш комментарий"></textarea><br>
			   <button class="w3-button w3-padding-large w3-white w3-border" type="submit"><b>Отправить</b></button>
		   </form>
			</p> <!-- Полное содержание -->
            <div class="w3-row">
                <div class="w3-col m8 s12">
                    <p></p>
                </div>
            </div>
        </div>
    </div>
    <hr>
<?php
    else:
        // Если новость не найдена
		header('HTTP/1.1 404 Not Found');
		echo '<div class="w3-card-4 w3-margin w3-white"><div class="w3-container"></div><div class="w3-container">';
        echo "<p>Новость не найдена.</p></div></div>";
    endif;
// Загружаем краткие новости, если id не указан
} else {
	//var_dump($news);
    if ($news): // Проверяем, есть ли новости
        foreach ($news as $newsItem): // Перебираем все короткие новости

$randomImage = $themeimg->getRandomImage($newsItem['title']);
?>
        <div class="w3-card-4 w3-margin w3-white">
            
            <div class="w3-container w3-padding">
                <h3><img src="<?= htmlspecialchars($randomImage) ?>" alt="image" style="width:13%"><b><?= htmlspecialchars($newsItem['title']) ?></b>
                    <?php if (isset($user) && $user['isadmin'] == 9): ?>
                        / <a href="/admin/edit_news.php?id=<?= $newsItem['id'] ?>">edit</a>
                        or <a href="/admin/delete_news.php?id=<?= $newsItem['id'] ?>">delete</a>
                    <?php endif; ?>
                    </h3>
                <h5><span class="w3-opacity"><?= htmlspecialchars($newsItem['created_at']) ?></span></h5>
            </div>
            <div class="w3-container">
                <p><?= ($newsItem['excerpt']) ?>...</p> <!-- Краткое содержание -->
                <div class="w3-row">
                    <div class="w3-col m8 s12">
                        <p><a href="?id=<?= $newsItem['id'] ?>"><button class="w3-button w3-padding-large w3-white w3-border"><b>
                            Читать дальше »
                        </b></button></a></p>
                    </div>
                </div>
            </div>
        </div>
        <hr>
<?php
        endforeach; 
    else:
        echo "<p>Нет новостей</p>";
    endif; 
}
/* END BLOG ENTRIES */
include __DIR__ . '/footer.tpl'; ?>
