</div>

<!-- Introduction menu -->
<div class="w3-col l4">
  <!-- About Card -->
  {if !$isCached}
  <div class="w3-card w3-margin w3-margin-top">
  {if $user}<img src="{$user['avatar'] ?: '/images/avatar_g.png'}" style="width:120px">{/if}
    <div class="w3-container w3-white"><?php flash(); ?>
	{if $user}
	  <p><form class="mt-5" method="post" action="/admin.php?logout=1"></p>
        <p>{Lang::get('hiuser')}, {$user.username}!<button type="submit" class="btn btn-primary">{Lang::get('logoutuser')}</button></form></p>
		{if $user['isadmin'] >= 7}<p><a href="/admin.php">{Lang::get('admpanel')}</a></p>{/if}
			{else}
		{if $auth_error}
            <div class="w3-red">
                <i class="fas fa-exclamation-circle"></i> {$auth_error}
            </div>
		{/if}
        <p><button class="w3-button w3-gray w3-large login-btn">{Lang::get('loginuser')}</button></p>

  <div id="id01" class="w3-modal">
    <div class="w3-modal-content w3-card-4 w3-animate-zoom" style="max-width:600px">
  
      <div class="w3-center"><br>
        <img src="/images/avatar_g.png" alt="Avatar" style="width:30%" class="w3-circle w3-margin-top">
      </div>
      <form method="POST" class="w3-container">
        <div class="w3-section">
		<input type="hidden" name="action" value="login">
		<input type="hidden" name="csrf_token" value="{$csrf_token}">
          <label><b>{Lang::get('username')}</b></label>
          <input class="w3-input w3-border w3-margin-bottom" type="text" placeholder="{Lang::get('username')}" name="username" required>
          <label><b>{Lang::get('password')}</b></label>
          <input class="w3-input w3-border" type="password" placeholder="{Lang::get('password')}" name="password" required>
		  <label><b>{Lang::get('howcapcha')} </b><img src="{$captcha_image_url}" alt="{Lang::get('captcha')}"></label>
			<input class="w3-input w3-border" type="text" name="captcha" required placeholder="{Lang::get('answer')}">
          <button class="w3-button w3-block w3-gray w3-section w3-padding" type="submit">{Lang::get('loginuser')}</button>
          <input class="w3-check w3-margin-top" type="checkbox" checked="checked"> {Lang::get('remember')}
        </div>
      </form>

		<div class="w3-container w3-border-top w3-padding-16 w3-light-grey">
			<button type="button" class="w3-button w3-red close-modal">{Lang::get('cancel')}</button>
			<span class="w3-right w3-padding w3-hide-small">
				<a href="?action=forgot_password">{Lang::get('forgot_password', 'core')}</a> | 
				<a href="?action=register">{Lang::get('register')}</a>
			</span>
		</div>

    </div>
  </div></p>
		
		{/if}
	<p><a href="/">{Lang::get('main')}</a></p>
	<p><a href="?action=contact">{Lang::get('contact')}</a></p>
    </div>
  </div><hr>
  {/if}
  <!-- Search -->
<div class="w3-card w3-margin">
    <div class="w3-container w3-padding">
      <h4>{Lang::get('search')}</h4>
    </div>
    <div class="search-form">
        <form action="/" method="get">
			<input type="hidden" name="action" value="search">
            <input type="text" name="search" placeholder="{Lang::get('findarea')}" 
                   value="{if isset($_GET['search'])} {$_GET.search}{/if}">
            <button type="submit" class="w3-button w3-dark-grey">{Lang::get('find')}</button>
        </form>
    </div>
</div>
<hr>
  
  <!-- Posts -->
  <div class="w3-card w3-margin">
    <div class="w3-container w3-padding">
      <h4>{Lang::get('threenews')}</h4>
    </div>
    <ul class="w3-ul w3-hoverable w3-white">
	{if $lastThreeNews}
{foreach $lastThreeNews as $newsItem}
      <li class="w3-padding">
        <span class="w3-large"><a class="" href="?id={$newsItem.id}">{$newsItem.title}</a></span><br>
        <span>{$newsItem.created_at}</span>
      </li>
	  {/foreach}
    {else}
        <p>{Lang::get('nonews')}</p>
	{/if}

    </ul>
  </div>
  <hr>
  <!-- Labels / tags -->
  <div class="w3-card w3-margin">
        <div class="w3-container w3-padding">
            <h4>{Lang::get('ltags')}</h4>
        </div>
        <div class="w3-container w3-white">
            <p>
			{if $allTags}
				{foreach $allTags as $tag}
                        <span class="w3-tag w3-light-grey w3-small w3-margin-bottom"><a class="w3-button" href="?tags={$tag.name}">{$tag.name}</a></span>
				{/foreach}
			{else}
                    <span class="w3-tag w3-light-grey w3-small w3-margin-bottom">{Lang::get('notags')}</span>
			{/if}
            </p>
        </div>
    </div>

<!-- END Introduction Menu -->
</div>

<!-- END GRID -->
</div><br>

    {!$pagination}<!-- END w3-content -->
</div>

<!-- Footer -->
<footer class="w3-container w3-dark-grey w3-padding-32 w3-margin-top">



  <p align="center">Design by <a href="https://www.w3schools.com/w3css/default.asp" target="_blank">w3.css</a> &copy; {$currentYear} {$serverName}. Generate by {$powered}_{$version}. All rights reserved.</b><br>

  </p>
</footer>

</body></html>