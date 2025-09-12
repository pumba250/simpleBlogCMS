<div class="w3-col l4">
    <div class="w3-card w3-margin w3-margin-top">
        <div class="w3-container w3-white">
            {!$userSection}
			<p><a href="/">{l_main}</a></p>
			<p><a href="?action=contact">{l_contact}</a></p>
			<p><a href="https://github.com/pumba250/simpleBlog/releases" target=_new>{l_us_git:core}</a></p>
        </div>
    </div>
    
    <div class="w3-card w3-margin">
        <div class="w3-container w3-padding">
            <h4>{l_search}</h4>
            {$searchForm}
        </div>
    </div>
    
    <div class="w3-card w3-margin">
        <div class="w3-container w3-padding">
            <h4>{l_threenews}</h4>
        </div>
		{!$lastThreeNewsHtml}
    </div>
    
    <div class="w3-card w3-margin">
        <div class="w3-container w3-padding">
            <h4>{l_ltags}</h4>
        </div>
		{!$allTags}
    </div>
</div>