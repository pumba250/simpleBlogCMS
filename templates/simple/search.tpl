{include 'header.tpl'}
<div class="w3-card-4 w3-margin w3-white">
	<div class="search-results">
		<h1>{Lang::get('result')}: "{$searchQuery}"</h1>
		
		<?php if ($totalResults > 0): ?>
			<p>{Lang::get('found')}: {$totalResults}</p>
			
			{foreach $news as $item}
				<article class="news-item">
					<h2><a href="?id={$item.id}">{$item.title}</a></h2>
					<div class="news-excerpt">{!$item.excerpt}</div>
					<div class="news-meta">
						<time datetime="{$item.created_at}">{$item.created_at}</time>
					</div>
				</article>
			{/foreach}
			
			{if $totalPages > 1}
				<div class="pagination">
					<?php for ($i = 1; $i <= $totalPages; $i++): ?>
						{if $i == $currentPage}
							<span class="current">{$i}</span>
						<?php else: ?>
							<a href="?action=search&search={$searchQuery}&page={$i}">{$i}</a>
						{/if}
					<?php endfor; ?>
				</div>
			{/if}
		{else}
			<p>{Lang::get('noresult')}</p>
		{/if}
	</div>
</div>
{include 'footer.tpl'}
