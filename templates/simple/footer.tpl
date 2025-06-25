<?php
?>
</div>

<!-- Introduction menu -->
<div class="w3-col l4">
  <!-- About Card -->
  <div class="w3-card w3-margin w3-margin-top">
  <?php if ($user): ?><img src="<?= !empty($user['avatar']) ? htmlspecialchars($user['avatar'], ENT_QUOTES) : '/images/avatar_g.png'; ?>" style="width:120px"><?php endif; ?>
    <div class="w3-container w3-white"><?php flash(); ?>
      <?php if ($user): ?>
	  <p><form class="mt-5" method="post" action="/admin.php?logout=1"></p>
        <p><?= Lang::get('hiuser') ?>, <?= htmlspecialchars($user['username']) ?>!<button type="submit" class="btn btn-primary"><?= Lang::get('logoutuser') ?></button></form></p>
		<?php if ($user['isadmin']>='7'): ?><p><a href="/admin.php"><?= Lang::get('admpanel') ?></a></p><?php endif; ?>
    <?php else: ?>
		<?php if (isset($_SESSION['auth_error'])): ?>
            <div class="w3-red">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['auth_error'] ?>
            </div>
        <?php endif; ?>
        <p><button onclick="document.getElementById('id01').style.display='block'" class="w3-button w3-gray w3-large"><?= Lang::get('loginuser') ?></button></p>

  <div id="id01" class="w3-modal">
    <div class="w3-modal-content w3-card-4 w3-animate-zoom" style="max-width:600px">
  
      <div class="w3-center"><br>
        <span onclick="document.getElementById('id01').style.display='none'" class="w3-button w3-xlarge w3-transparent w3-display-topright" title="Close Modal">×</span>
        <img src="/images/avatar_g.png" alt="Avatar" style="width:30%" class="w3-circle w3-margin-top">
      </div>
      <form method="POST" class="w3-container">
        <div class="w3-section">
		<input type="hidden" name="action" value="login">
          <label><b><?= Lang::get('username') ?></b></label>
          <input class="w3-input w3-border w3-margin-bottom" type="text" placeholder="Enter Username" name="username" required>
          <label><b><?= Lang::get('password') ?></b></label>
          <input class="w3-input w3-border" type="password" placeholder="Enter Password" name="password" required>
		  <label><b><?= Lang::get('howcapcha') ?> </b><img src="<?php echo $captcha_image_url; ?>" alt="<?= Lang::get('captcha') ?>"></label>
			<input class="w3-input w3-border" type="text" name="captcha" required placeholder="<?= Lang::get('answer') ?>">
          <button class="w3-button w3-block w3-gray w3-section w3-padding" type="submit"><?= Lang::get('loginuser') ?></button>
          <input class="w3-check w3-margin-top" type="checkbox" checked="checked"> <?= Lang::get('remember') ?>
        </div>
      </form>

      <div class="w3-container w3-border-top w3-padding-16 w3-light-grey">
        <button onclick="document.getElementById('id01').style.display='none'" type="button" class="w3-button w3-red"><?= Lang::get('cancel') ?></button>
        <span class="w3-right w3-padding w3-hide-small"><a href="?action=register"><?= Lang::get('register') ?></a></span>
      </div>

    </div>
  </div></p>
		
    <?php endif; ?>
	<p><a href="/"><?= Lang::get('main') ?></a></p>
	<p><a href="?action=contact"><?= Lang::get('contact') ?></a></p>
    </div>
  </div><hr>
  
  <!-- Search -->
<div class="w3-card w3-margin">
    <div class="w3-container w3-padding">
      <h4><?= Lang::get('search') ?></h4>
    </div>
    <div class="search-form">
        <form action="/" method="get">
			<input type="hidden" name="action" value="search">
            <input type="text" name="search" placeholder="<?= Lang::get('findarea') ?>" 
                   value="<?php if (isset($_GET['search'])) echo htmlspecialchars($_GET['search']); ?>">
            <button type="submit" class="w3-button w3-block w3-dark-grey"><?= Lang::get('find') ?></button>
        </form>
    </div>
</div>
<hr>
  
  <!-- Posts -->
  <div class="w3-card w3-margin">
    <div class="w3-container w3-padding">
      <h4><?= Lang::get('threenews') ?></h4>
    </div>
    <ul class="w3-ul w3-hoverable w3-white">
<?php if ($lastThreeNews): ?>
    <?php foreach ($lastThreeNews as $newsItem): ?>
      <li class="w3-padding">
        <span class="w3-large"><a class="" href="?id=<?= htmlspecialchars($newsItem['id']) ?>"><?= htmlspecialchars($newsItem['title']) ?></a></span><br>
        <span><?= $newsItem['created_at'] ?></span>
      </li>
	<?php endforeach; ?>
    <?php else: ?>
        <p><?= Lang::get('nonews') ?></p>
    <?php endif; ?>

    </ul>
  </div>
  <hr>
  <!-- Labels / tags -->
  <div class="w3-card w3-margin">
        <div class="w3-container w3-padding">
            <h4><?= Lang::get('ltags') ?></h4>
        </div>
        <div class="w3-container w3-white">
            <p>
                <?php if ($allTags): ?>
                    <?php foreach ($allTags as $tag): ?>
                        <span class="w3-tag w3-light-grey w3-small w3-margin-bottom"><a class="w3-button" href="?tags=<?= htmlspecialchars($tag['name']) ?>"><?= htmlspecialchars($tag['name']) ?></a></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="w3-tag w3-light-grey w3-small w3-margin-bottom"><?= Lang::get('notags') ?></span>
                <?php endif; ?>
            </p>
        </div>
    </div>

<!-- END Introduction Menu -->
</div>

<!-- END GRID -->
</div><br>

<!-- END w3-content -->
</div>

<!-- Footer -->
<footer class="w3-container w3-dark-grey w3-padding-32 w3-margin-top">
	<?if (isset($_GET['id']) || isset($_GET['action'])) {} else {?>
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?page=<?= $currentPage - 1 ?>" class="w3-button w3-black w3-padding-large w3-margin-bottom"><?= Lang::get('prev') ?></a>
            <?php else: ?>
                <button class="w3-button w3-black w3-disabled w3-padding-large w3-margin-bottom"><?= Lang::get('prev') ?></button>
            <?php endif; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?= $currentPage + 1 ?>" class="w3-button w3-black w3-padding-large w3-margin-bottom"><?= Lang::get('next') ?> &raquo;</a>
            <?php else: ?>
                <button class="w3-button w3-black w3-disabled w3-padding-large w3-margin-bottom"><?= Lang::get('next') ?> &raquo;</button>
	<?php endif; }?>
        </div>
  <p align="center">Design by <a href="https://www.w3schools.com/w3css/default.asp" target="_blank">w3.css</a> &copy; <?= date("Y") ?> <?= htmlspecialchars($_SERVER['SERVER_NAME']) ?>. Powered by <?= $powered ?>_<?= $version ?>. All rights reserved.</b><br>

  </p>
</footer>

</body></html>