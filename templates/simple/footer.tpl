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
        <p>{l_hiuser}, {$user.username}!<button type="submit" class="btn btn-primary">{l_logoutuser}</button></form></p>
		{if $user['isadmin'] >= 7}<p><a href="/admin.php">{l_admpanel}</a></p>{/if}
			{else}
		{if $auth_error}
            <div class="w3-red">
                <i class="fas fa-exclamation-circle"></i> {$auth_error}
            </div>
		{/if}
        <p><button class="w3-button w3-gray w3-large login-btn">{l_loginuser}</button></p>

  <div id="id01" class="w3-modal">
    <div class="w3-modal-content w3-card-4 w3-animate-zoom" style="max-width:600px">
  
      <div class="w3-center"><br>
        <img src="/images/avatar_g.png" alt="Avatar" style="width:30%" class="w3-circle w3-margin-top">
      </div>
      <form method="POST" class="w3-container">
        <div class="w3-section">
		<input type="hidden" name="action" value="login">
		<input type="hidden" name="csrf_token" value="{$csrf_token}">
          <label><b>{l_username}</b></label>
          <input class="w3-input w3-border w3-margin-bottom" type="text" placeholder="{l_username}" name="username" required>
          <label><b>{l_password}</b></label>
          <input class="w3-input w3-border" type="password" placeholder="{l_password}" name="password" required>
		  <label><b>{l_howcapcha} </b><img src="{$captcha_image_url}" alt="{l_captcha}"></label>
			<input class="w3-input w3-border" type="text" name="captcha" required placeholder="{l_answer}">
          <button class="w3-button w3-block w3-gray w3-section w3-padding" type="submit">{l_loginuser}</button>
          <input class="w3-check w3-margin-top" type="checkbox" checked="checked"> {l_remember}
        </div>
      </form>

		<div class="w3-container w3-border-top w3-padding-16 w3-light-grey">
			<button type="button" class="w3-button w3-red close-modal">{l_cancel}</button>
			<span class="w3-right w3-padding w3-hide-small">
				<a href="?action=forgot_password">{l_forgot_password:core}</a> | 
				<a href="?action=register">{l_register}</a>
			</span>
		</div>

    </div>
  </div></p>
		
		{/if}
	<p><a href="/">{l_main}</a></p>
	<p><a href="?action=contact">{l_contact}</a></p>
    </div>
  </div><hr>
  {/if}
  <!-- Search -->
<div class="w3-card w3-margin">
    <div class="w3-container w3-padding">
      <h4>{l_search}</h4>
    </div>
    <div class="search-form">
        <form action="/" method="get">
			<input type="hidden" name="action" value="search">
            <input type="text" name="search" placeholder="{l_findarea}" 
                   value="{if isset($_GET['search'])} {$_GET.search}{/if}">
            <button type="submit" class="w3-button w3-dark-grey">{l_find}</button>
        </form>
    </div>
</div>
<hr>
  
  <!-- Posts -->
  <div class="w3-card w3-margin">
    <div class="w3-container w3-padding">
      <h4>{l_threenews}</h4>
    </div>
    <ul class="w3-ul w3-hoverable w3-white">
	{!$lastThreeNewsHtml}

    </ul>
  </div>
  <hr>
  <!-- Labels / tags -->
  <div class="w3-card w3-margin">
        <div class="w3-container w3-padding">
            <h4>{l_ltags}</h4>
        </div>
        <div class="w3-container w3-white">
            <p>
			{!$allTags}
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
  <p align="center">Design by <a href="https://www.w3schools.com/w3css/default.asp" target="_blank">w3.css</a> &copy; {$currentYear} {$serverName}. Powered by {$powered}_{$version}. All rights reserved.</b><br>
  </p>
</footer>
</body></html>