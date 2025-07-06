{include 'header.tpl'}
<?
$hasVotedArticle = isset($_SESSION['user']) ? $votes->hasUserVotedForArticle($news['id'], $_SESSION['user']['id']) : false;
?>
    <div class="w3-card-4 w3-margin w3-white">
        <div class="w3-container">
            <h3><b>{$news.title}</b></h3>
            <h5><span class="w3-opacity">{$newsItem.created_at}</span></h5>
        </div>
        <div class="w3-container">
            <p>{!$news.content}</p> 
            <div class="w3-panel w3-light-grey w3-padding">
                <p>{Lang::get('voteart')}</p>
                <div class="w3-bar">
                    <form method="post" action="?id={$news.id}" class="w3-bar-item">
						<input type="hidden" name="id" value="{$news.id}">
                        <input type="hidden" name="vote_article" value="like">
						<input type="hidden" name="csrf_token" value="{$csrf_token}">
                        <button type="submit" class="w3-button w3-green" <?= $hasVotedArticle ? 'disabled' : '' ?>>
                            <i class="fa fa-thumbs-up"></i> {$articleRating.likes}
                        </button>
                    </form>
                    <form method="post" action="?id={$news.id}" class="w3-bar-item">
						<input type="hidden" name="id" value="{$news.id}">
                        <input type="hidden" name="vote_article" value="dislike">
						<input type="hidden" name="csrf_token" value="{$csrf_token}">
                        <button type="submit" class="w3-button w3-red" <?= $hasVotedArticle ? 'disabled' : '' ?>>
                            <i class="fa fa-thumbs-down"></i> {$articleRating.dislikes}
                        </button>
                    </form>
                </div>
            </div>
            <div class="w3-row">
                <div class="w3-col m8 s12">
                    <p><a href="?"><button class="w3-button w3-padding-large w3-white w3-border"><b>
                        {Lang::get('back')} »
                    </b></button></a></p>
                </div>
            </div>
        </div>
    </div>
    <div class="w3-card-4 w3-margin w3-white">
        <div class="w3-container">
            <h3><b>{Lang::get('comments')}</b></h3>
        </div>
        <div class="w3-container">
            {if empty($commentsList)}
                <p>{Lang::get('nocomments')}.</p>
            {else}
                {foreach $commentsList as $comment}
                    <?$commentRating = $votes->getCommentRating($comment['id']);
                    $hasVoted = isset($_SESSION['user']) ? $votes->hasUserVoted($comment['id'], $_SESSION['user']['id']) : false;
                ?>
                    <div class="w3-panel w3-border w3-light-grey w3-padding" style="margin-bottom: 16px;">
                        <div class="w3-row">
                            <div class="w3-col m10">
                                <strong>{$comment.user_name}:</strong>
                                <p>{$comment.user_text}</p>
                            </div>
                            <div class="w3-col m2">
                                <div class="w3-right">
                                    <form method="post" action="?id={$news.id}" class="w3-bar-item">
                                        <input type="hidden" name="vote_comment" value="{$comment.id}_plus">
										<input type="hidden" name="csrf_token" value="{$csrf_token}">
                                        <button type="submit" class="w3-button w3-small <?= $hasVoted ? 'w3-light-grey' : 'w3-green' ?>" 
                                            <?= $hasVoted ? 'disabled' : '' ?>>
                                            <i class="fa fa-thumbs-up"></i> {$commentRating.plus}
                                        </button>
                                    </form>
                                    <form method="post" action="?id={$news.id}" class="w3-bar-item">
                                        <input type="hidden" name="vote_comment" value="{$comment.id}_minus">
										<input type="hidden" name="csrf_token" value="{$csrf_token}">
                                        <button type="submit" class="w3-button w3-small <?= $hasVoted ? 'w3-light-grey' : 'w3-red' ?>" 
                                            <?= $hasVoted ? 'disabled' : '' ?>>
                                            <i class="fa fa-thumbs-down"></i> {$commentRating.minus}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                {/foreach}
<div class="pagination">
{!$commentsPagination}
</div>
            {/if}
            <hr>
            <form method="post">
				<input type="hidden" name="csrf_token" value="{$csrf_token}">
                {if null !== $user->username}
                    <input class="w3-input w3-border" type="text" name="user_name" required placeholder="{Lang::get('name')}"><br>
                {/if}
                <textarea class="w3-input w3-border" style="height: 80px;" name="user_text" required placeholder="{Lang::get('comment')}"></textarea><br>
                <button class="w3-button w3-padding-large w3-white w3-border" type="submit"><b>{Lang::get('submit')}</b></button>
            </form>
        </div>
    </div>
    <hr>
{include 'footer.tpl'}
