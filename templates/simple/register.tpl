<?php include __DIR__ . '/header.tpl';

?><div class="w3-card-4 w3-margin w3-white">
<div id="contact" class="w3-container w3-center w3-padding-32">
<?php if (!$user): ?><h2 class="w3-wide"><?= Lang::get('register') ?></h2>
<p class="w3-opacity w3-center"><? flash(); ?></p>
<form method="POST">
    <input type="hidden" name="action" value="register">
    
    <label for="username"><?= Lang::get('name') ?>:</label>
    <input type="text" name="username" id="username" placeholder="<?= Lang::get('name') ?>" required><br>
    
    <label for="password"><?= Lang::get('password') ?>:</label>
    <input type="password" name="password" id="password" placeholder="<?= Lang::get('password') ?>" required><br>
    
    <label for="email"><?= Lang::get('email') ?>:</label>
    <input type="email" name="email" id="email" placeholder="<?= Lang::get('email') ?>" required><br>
    
    <label for="question"><?= Lang::get('howcapcha') ?> <img src="<?php echo $captcha_image_url; ?>" alt="<?= Lang::get('captcha') ?>"></label>
    <input type="text" name="captcha" required placeholder="<?= Lang::get('answer') ?>"><br>
    
    <button type="submit"><?= Lang::get('doregister') ?></button>
</form><?php else: ?><p class="w3-opacity w3-center"><a href="?"><?= Lang::get('main') ?></a></p><?php endif; ?></div>
</div>
<?php include __DIR__ . '/footer.tpl'; ?>
