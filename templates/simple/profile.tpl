{include "header.tpl"}

<div class="w3-card-4 w3-margin w3-white">
    <h2 class="w3-text-grey w3-padding-16">
        <i class="fa fa-user fa-fw w3-margin-right w3-xxlarge"></i>
        {l_profile:core}
    </h2>

    {if $flash}
        <div class="w3-panel w3-{if $flash_type}{$flash_type}{else}green{/if} w3-padding">
            {$flash}
        </div>
    {/if}

    <!-- Форма редактирования профиля -->
    <div class="w3-container w3-padding-16">
        <form method="post" enctype="multipart/form-data" class="w3-container">
            <input type="hidden" name="csrf_token" value="{$csrf_token}">
            
            <div class="w3-row-padding">
                <div class="w3-col m3">
                    <div class="w3-card w3-round w3-white w3-center" style="padding: 15px;">
                        <img src="{$userData.avatar}" 
                             class="w3-circle" style="width:120px;height:120px;" alt="Avatar">
                        <h4>{$userData.username}</h4>
                        <p class="w3-text-gray">
                            {l_member_since:core} {$userData.created_at}
                        </p>
                        
                        <div class="w3-margin-top">
                            <label class="w3-button w3-dark-grey w3-small">
                                {l_change_avatar:core}
                                <input type="file" name="avatar" accept="image/*" style="display: none;" 
                                       onchange="document.getElementById('avatar-preview').src = window.URL.createObjectURL(this.files[0])">
                            </label>
                        </div>
                    </div>
                    
                    <!-- Социальные сети -->
                    <div class="w3-card w3-round w3-white w3-margin-top" style="padding: 15px;">
                        <h4>{l_social_networks:core}</h4>
                        <div class="w3-padding">
                            {foreach $supportedSocials as $social}
                                {*assign var="linked" value=false*}
                                {foreach $socialLinks as $link}
                                    {if $link.social_type == $social}
                                        {*assign var="linked" value=true*}
                                        <div class="w3-padding-small w3-light-grey w3-margin-bottom w3-round">
                                            <i class="fa fa-{$social} w3-margin-right"></i>
                                            {$link.social_username ?: $link.social_id}
                                            <a href="?action=profile&unlink_social={$social}" 
                                               class="w3-right w3-button w3-tiny w3-red">
                                                {l_unlink:core}
                                            </a>
                                        </div>
                                    {/if}
                                {/foreach}
                                
                                {if !$linked}
                                    <div class="w3-padding-small w3-light-grey w3-margin-bottom w3-round">
                                        <i class="fa fa-{$social} w3-margin-right"></i>
                                        <a href="?action=link_social&type={$social}" 
                                           class="w3-button w3-tiny w3-dark-grey">
                                            {l_link_account:core}
                                        </a>
                                    </div>
                                {/if}
                            {/foreach}
                        </div>
                    </div>
                </div>
                
                <div class="w3-col m9">
                    <!-- Основные данные -->
                    <div class="w3-card w3-padding w3-round-large w3-white">
                        <h4>{l_personal_info:core}</h4>
                        
                        <div class="w3-row-padding w3-margin-bottom">
                            <div class="w3-half">
                                <label>{l_username:core}</label>
                                <input class="w3-input w3-border" type="text" name="username" 
                                       value="{$userData.username}" required>
                            </div>
                            <div class="w3-half">
                                <label>{l_email:core}</label>
                                <input class="w3-input w3-border" type="email" name="email" 
                                       value="{$userData.email}" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="w3-button w3-dark-grey">
                            {l_save_changes:core}
                        </button>
                    </div>
                    
                    <!-- Статистика -->
                    <div class="w3-row-padding w3-margin-top">
                        <div class="w3-half w3-margin-bottom">
                            <div class="w3-card w3-padding w3-round-large w3-white w3-center">
                                <h3>{$userNewsCount}</h3>
                                <p>{l_articles:core}</p>
                            </div>
                        </div>
                        <div class="w3-half w3-margin-bottom">
                            <div class="w3-card w3-padding w3-round-large w3-white w3-center">
                                <h3>{$userCommentsCount}</h3>
                                <p>{l_comments:core}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Последние статьи -->
                    <div class="w3-card w3-padding w3-round-large w3-white w3-margin-top">
                        <h4>{l_last_articles:core}</h4>
                        {if $userNews}
                            <ul class="w3-ul">
                                {foreach $userNews as $article}
                                    <li>
                                        <a href="?id={$article.id}">{$article.title}</a>
                                        <span class="w3-opacity w3-small">{$article.created_at}</span>
                                    </li>
                                {/foreach}
                            </ul>
                        {else}
                            <p>{l_no_articles_yet:core}</p>
                        {/if}
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

{include "footer.tpl"}