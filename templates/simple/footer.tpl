{!$pagination}</div>
<div class="w3-col l4">
    <div class="w3-card w3-margin w3-margin-top">
        <div class="w3-container w3-white">
		{if $auth_error}
			<div class="w3-red">
				<i class="fas fa-exclamation-circle"></i> {$auth_error}
			</div>
		{/if}
		{if $flash}
			<div class="flash flash-{$flash.type}">{$flash.message}</div>
		{/if}
            {!$userSection}
			<p><a href="/">{l_main}</a></p>
			<p><a href="?action=contact">{l_contact}</a></p>
			<p><a href="https://github.com/pumba250/simpleBlog/releases" target=_new>{l_us_git:core}</a></p>
        </div>
    </div>
    
    <div class="w3-card w3-margin">
        <div class="w3-container w3-padding">
            <h4>{l_search}</h4>
            {!$searchForm}
        </div>
    </div>
    
    <div class="w3-card w3-margin">
        <div class="w3-container w3-padding">
            <h4>{l_threenews}</h4>
        </div>
		{!$recentNewsList}
    </div>
    
    <div class="w3-card w3-margin">
        <div class="w3-container w3-padding">
            <h4>{l_ltags}</h4>
        </div>
		{!$tagsList}
    </div>
</div>
</div>

<footer class="w3-container w3-dark-grey w3-padding-32 w3-margin-top">
    <p align="center">{!$footerContent}</p>
</footer>
</body>
</html>