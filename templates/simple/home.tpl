<?php include __DIR__ . '/header.tpl'; 
if (isset($_GET['id'])) {
    $newsId = (int)$_GET['id'];
    $newsItem = $news->getNewsById($newsId); 
    $articleRating = $votes->getArticleRating($newsId);

    if ($newsItem):
?>
    <div class="w3-card-4 w3-margin w3-white">
        <div class="w3-container">
            <h3><b><?= htmlspecialchars($newsItem['title']) ?></b></h3>
            <h5><span class="w3-opacity"><?= htmlspecialchars($newsItem['created_at']) ?></span></h5>
        </div>
        <div class="w3-container">
            <p><?= ($newsItem['content']) ?></p> 
            
            <div class="w3-panel w3-light-grey w3-padding">
                <p><?= Lang::get('voteart') ?>:</p>
                <div class="w3-bar">
                    <form method="post" action="?id=<?= $newsItem['id'] ?>" class="w3-bar-item">
						<input type="hidden" name="id" value="<?= $newsId ?>">
                        <input type="hidden" name="vote_article" value="like">
                        <button type="submit" class="w3-button w3-green">
                            <i class="fa fa-thumbs-up"></i> <?= $articleRating['likes'] ?? 0 ?>
                        </button>
                    </form>
                    <form method="post" action="?id=<?= $newsItem['id'] ?>" class="w3-bar-item">
						<input type="hidden" name="id" value="<?= $newsId ?>">
                        <input type="hidden" name="vote_article" value="dislike">
                        <button type="submit" class="w3-button w3-red">
                            <i class="fa fa-thumbs-down"></i> <?= $articleRating['dislikes'] ?? 0 ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="w3-row">
                <div class="w3-col m8 s12">
                    <p><a href="?"><button class="w3-button w3-padding-large w3-white w3-border"><b>
                        <?= Lang::get('back') ?> »
                    </b></button></a></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="w3-card-4 w3-margin w3-white">
        <div class="w3-container">
            <h3><b><?= Lang::get('comments') ?>:</b></h3>
        </div>
        <div class="w3-container">
            <?php if (empty($commentsList)): ?>
                <p><?= Lang::get('nocomments') ?>.</p>
            <?php else: ?>
                <?php foreach ($commentsList as $comment): 
                    $commentRating = $votes->getCommentRating($comment['id']);
                    $hasVoted = isset($_SESSION['user']) ? $votes->hasUserVoted($comment['id'], $_SESSION['user']['id']) : false;
                ?>
                    <div class="w3-panel w3-border w3-light-grey w3-padding" style="margin-bottom: 16px;">
                        <div class="w3-row">
                            <div class="w3-col m10">
                                <strong><?= htmlspecialchars($comment['user_name']) ?>:</strong>
                                <p><?= htmlspecialchars($comment['user_text']) ?></p>
                            </div>
                            <div class="w3-col m2">
                                <div class="w3-right">
                                    <form method="post" action="?id=<?= $newsId ?>" class="w3-bar-item">
                                        <input type="hidden" name="vote_comment" value="<?= $comment['id'] ?>_plus">
                                        <button type="submit" class="w3-button w3-small <?= $hasVoted ? 'w3-light-grey' : 'w3-green' ?>" 
                                            <?= $hasVoted ? 'disabled' : '' ?>>
                                            <i class="fa fa-thumbs-up"></i> <?= $commentRating['plus'] ?? 0 ?>
                                        </button>
                                    </form>
                                    <form method="post" action="?id=<?= $newsId ?>" class="w3-bar-item">
                                        <input type="hidden" name="vote_comment" value="<?= $comment['id'] ?>_minus">
                                        <button type="submit" class="w3-button w3-small <?= $hasVoted ? 'w3-light-grey' : 'w3-red' ?>" 
                                            <?= $hasVoted ? 'disabled' : '' ?>>
                                            <i class="fa fa-thumbs-down"></i> <?= $commentRating['minus'] ?? 0 ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if ($totalCommentPages > 1): ?>
                    <div class="w3-center w3-padding">
                        <div class="w3-bar">
                            <?php if ($currentCommentPage > 1): ?>
                                <a href="?id=<?= $newsId ?>&comment_page=<?= $currentCommentPage-1 ?>" class="w3-button">&laquo;</a>
                            <?php endif; ?>
                            
                            <?php for ($page = 1; $page <= $totalCommentPages; $page++): ?>
                                <a href="?id=<?= $newsId ?>&comment_page=<?= $page ?>" class="w3-button <?= ($page == $currentCommentPage) ? 'w3-green' : '' ?>"><?= $page ?></a>
                            <?php endfor; ?>
                            
                            <?php if ($currentCommentPage < $totalCommentPages): ?>
                                <a href="?id=<?= $newsId ?>&comment_page=<?= $currentCommentPage+1 ?>" class="w3-button">&raquo;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <hr>
            
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_text'])): 
                $userName = isset($user['username']) ? $user['username'] : trim($_POST['user_name']);
                $userText = trim($_POST['user_text']);
                if (!empty($userName) && !empty($userText)) {
                    $comments->addComment(0, 0, $newsId, $userName, $userText);
                    echo '<p class="w3-panel w3-green">'. Lang::get('commadded').'</p>';
                } else {
                    echo '<p class="w3-panel w3-red">'. Lang::get('commerror').'</p>';
                }
            endif; ?>
            
            <form method="post">
                <?php if (!isset($user['username'])): ?>
                    <input class="w3-input w3-border" type="text" name="user_name" required placeholder="<?= Lang::get('name') ?>"><br>
                <?php endif; ?>
                <textarea class="w3-input w3-border" style="height: 80px;" name="user_text" required placeholder="<?= Lang::get('comment') ?>"></textarea><br>
                <button class="w3-button w3-padding-large w3-white w3-border" type="submit"><b><?= Lang::get('submit') ?></b></button>
            </form>
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
} else {
    // Загружаем краткие новости, если id не указан
    if ($news): // Проверяем, есть ли новости
        foreach ($news as $newsItem): // Перебираем все короткие новости
            $articleRating = $votes->getArticleRating($newsItem['id']);
?>
        <div class="w3-card-4 w3-margin w3-white">
            <div class="w3-container w3-padding">
                <h3><b><?= htmlspecialchars($newsItem['title']) ?></b></h3>
                <h5><span class="w3-opacity"><?= htmlspecialchars($newsItem['created_at']) ?></span></h5>
            </div>
            <div class="w3-container">
                <p><?= ($newsItem['excerpt'] ?? '') ?>...</p> <!-- Краткое содержание -->
                
                <!-- Мини-блок голосования для списка статей -->
                <div class="w3-bar">
                    <span class="w3-bar-item w3-small">
                        <i class="fa fa-thumbs-up"></i> <?= $articleRating['likes'] ?? 0 ?>
                    </span>
                    <span class="w3-bar-item w3-small">
                        <i class="fa fa-thumbs-down"></i> <?= $articleRating['dislikes'] ?? 0 ?>
                    </span>
                </div>
                
                <div class="w3-row">
                    <div class="w3-col m8 s12">
                        <p><a href="?id=<?= $newsItem['id'] ?>"><button class="w3-button w3-padding-large w3-white w3-border"><b>
                            <?= Lang::get('read_more') ?> »
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