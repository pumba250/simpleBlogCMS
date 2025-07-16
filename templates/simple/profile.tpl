{include "header.tpl"}

<div class="w3-container w3-card w3-white w3-margin-bottom">
    <h2 class="w3-text-grey w3-padding-16">
        <i class="fa fa-user fa-fw w3-margin-right w3-xxlarge "></i>
        {l_profile:core}
    </h2>

    <!-- Основная информация -->
    <div class="w3-container w3-padding-16">
        <div class="w3-row-padding">
            <div class="w3-col m3">
                <div class="w3-card w3-round w3-white">
                    <div class="w3-container" style="text-align: center;">
                        
                        <h4>{$userData.username}</h4>
                        <p class="w3-text-gray">
                            {l_member_since:core} {$userData.created_at}
                        </p>
                    </div>
                </div>
            </div>
			<div class="w3-col m9">
                <!-- Статистика -->
                <div class="w3-row-padding">
                    <div class="w3-third w3-center">
                        <div class="w3-card w3-padding w3-round-large w3-white">
                            <h4>{$userNewsCount}</h4>
                            <p>{l_articles:core}</p>
                        </div>
                    </div>
                    <div class="w3-third w3-center">
                        <div class="w3-card w3-padding w3-round-large w3-white">
                            <h4>{$userCommentsCount}</h4>
                            <p>{l_comments:core}</p>
                        </div>
                    </div>
                    
                </div>

                <!-- Последние статьи -->
                <div class="w3-margin-top">
                    <h4><b>{l_last_articles:core}</b></h4>
                        <ul class="w3-ul w3-card">
                            {foreach $userNews as $article}
                                <li>
                                    <a href="?id={$article.id}">{$article.title}</a>
                                    <span class="w3-opacity w3-small">{$article.created_at}</span>
                                </li>
                            {/foreach}
                        </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{include "footer.tpl"}