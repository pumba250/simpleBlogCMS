{include "header.tpl"}

<div class="w3-container w3-card w3-white w3-margin-bottom">
    <h2 class="w3-text-grey w3-padding-16">
        <i class="fa fa-user fa-fw w3-margin-right w3-xxlarge w3-text-teal"></i>
        {l_profile:core}
    </h2>

    <!-- Основная информация -->
    <div class="w3-container w3-padding-16">
        <div class="w3-row-padding">
            <div class="w3-col m3">
                <div class="w3-card w3-round w3-white">
                    <div class="w3-container" style="text-align: center;">
                        <img src="{$userData.avatar|default:'/images/default-avatar.png'}" 
                             class="w3-circle" style="width: 150px; height: 150px;" alt="Avatar">
                        <h4>{$userData.username}</h4>
                        <p class="w3-text-gray">
                            {l_member_since:core} {$userData.created_at|date_format}
                        </p>
                    </div>
                </div>
            </div>

            <div class="w3-col m9">
                <!-- Статистика -->
                <div class="w3-row-padding">
                    <div class="w3-third w3-center">
                        <div class="w3-card w3-padding w3-round-large w3-light-grey">
                            <h4>{count($userNews)}</h4>
                            <p>{l_articles:core}</p>
                        </div>
                    </div>
                    <div class="w3-third w3-center">
                        <div class="w3-card w3-padding w3-round-large w3-light-grey">
                            <h4>{count($userComments)}</h4>
                            <p>{l_comments:core}</p>
                        </div>
                    </div>
                    <div class="w3-third w3-center">
                        <div class="w3-card w3-padding w3-round-large w3-light-grey">
                            <h4>{$userData.isadmin ? l_admin:core : l_user:core}</h4>
                            <p>{l_role:core}</p>
                        </div>
                    </div>
                </div>

                <!-- Последние статьи -->
                <div class="w3-margin-top">
                    <h4><b>{l_last_articles:core}</b></h4>
                    {if empty($userNews)}
                        <p>{l_no_articles_yet:core}</p>
                    {else}
                        <ul class="w3-ul w3-card">
                            {foreach $userNews as $article}
                                <li>
                                    <a href="?id={$article.id}">{$article.title}</a>
                                    <span class="w3-opacity w3-small">{$article.created_at|date_format}</span>
                                </li>
                            {/foreach}
                        </ul>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>

{include "footer.tpl"}