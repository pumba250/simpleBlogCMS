{include 'header.tpl'}
    {if $news} 
        {foreach $news as $newsItem}
		<? $articleRating = $votes->getArticleRating($newsItem['id']);//Если нужно отображать количество голосов за статью ?>
        <div class="w3-card-4 w3-margin w3-white">
            <div class="w3-container w3-padding">
                <h3><b>{$newsItem.title}</b></h3>
                <h5><span class="w3-opacity">{$newsItem.created_at}</span></h5>
            </div>
            <div class="w3-container">
                <p>{!$newsItem.excerpt}...</p>
                <div class="w3-bar">
                    <span class="w3-bar-item w3-small">
                        <i class="fa fa-thumbs-up"></i> {$articleRating.likes}
                    </span>
                    <span class="w3-bar-item w3-small">
                        <i class="fa fa-thumbs-down"></i> {$articleRating.dislikes}
                    </span>
                </div>
                <div class="w3-row">
                    <div class="w3-col m8 s12">
                        <p><a href="?id={$newsItem.id}"><button class="w3-button w3-padding-large w3-white w3-border"><b>
						{Lang::get('read_more')} »
                        </b></button></a></p>
                    </div>
                </div>
            </div>
        </div>
        <hr>
		{/foreach}
    {else}
        <p>Нет новостей</p>
	{/if} 
{include 'footer.tpl'}