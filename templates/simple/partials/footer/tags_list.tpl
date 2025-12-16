<div class="w3-container w3-white">
	<p>
		{foreach $allTags as $tag}
        <span class="w3-tag w3-light-grey w3-small w3-margin-bottom">
            <a href="?tags={$tag['name']}" class="w3-button">
                {$tag['name']}
            </a>
        </span>
        {/foreach}
	</p>
</div>