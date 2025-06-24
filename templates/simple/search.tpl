<?php include __DIR__ . '/header.tpl';?>
<div class="w3-card-4 w3-margin w3-white">
	<div class="search-results">
		<h1>Результаты поиска: "<?php echo htmlspecialchars($searchQuery); ?>"</h1>
		
		<?php if ($totalResults > 0): ?>
			<p>Найдено статей: <?php echo $totalResults; ?></p>
			
			<?php foreach ($news as $item): ?>
				<article class="news-item">
					<h2><a href="?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['title']); ?></a></h2>
					<div class="news-excerpt"><?php echo strip_tags($item['excerpt']); ?></div>
					<div class="news-meta">
						<time datetime="<?php echo $item['created_at']; ?>"><?php echo date('d.m.Y', strtotime($item['created_at'])); ?></time>
					</div>
				</article>
			<?php endforeach; ?>
			
			<?php if ($totalPages > 1): ?>
				<div class="pagination">
					<?php for ($i = 1; $i <= $totalPages; $i++): ?>
						<?php if ($i == $currentPage): ?>
							<span class="current"><?php echo $i; ?></span>
						<?php else: ?>
							<a href="?action=search&search=<?php echo urlencode($searchQuery); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
						<?php endif; ?>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		<?php else: ?>
			<p>По вашему запросу ничего не найдено.</p>
		<?php endif; ?>
	</div>
</div>
<?php include __DIR__ . '/footer.tpl'; ?>
