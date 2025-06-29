<?php include __DIR__ . '/header.tpl';

?><div class="w3-card-4 w3-margin w3-white">
<div id="contact" class="w3-container w3-center w3-padding-32">
<?php if (!$user): ?><h2 class="w3-wide"><i class="fas fa-user-plus"></i> <?= Lang::get('register') ?></h2>
<p class="w3-opacity w3-center"><? flash(); ?></p>
<form method="POST">
    <input type="hidden" name="action" value="register">
    
    <label for="username"><i class="fas fa-user"></i> <?= Lang::get('name') ?>:</label>
    <input type="text" name="username" id="username" placeholder="<?= Lang::get('name') ?>" required><br>
    
    <label for="password"><i class="fas fa-lock"></i> <?= Lang::get('password') ?>:</label>
    <input type="password" name="password" id="password" placeholder="<?= Lang::get('password') ?>" required><br>
    
    <label for="email"><i class="fas fa-envelope"></i> <?= Lang::get('email') ?>:</label>
    <input type="email" name="email" id="email" placeholder="<?= Lang::get('email') ?>" required><br>
    
    <label for="question"><i class="fas fa-shield-alt"></i><?= Lang::get('howcapcha') ?> <img src="<?php echo $captcha_image_url; ?>" alt="<?= Lang::get('captcha') ?>"></label>
    <input type="text" name="captcha" required placeholder="<?= Lang::get('answer') ?>"><br>
    
    <button type="submit"><i class="fas fa-sign-in-alt"></i> <?= Lang::get('doregister') ?></button>
</form>
<div class="auth-links">
                Уже есть аккаунт? <a href="?action=login">Войти</a>
            </div>
<?php else: ?><p class="w3-opacity w3-center"><a href="?"><?= Lang::get('main') ?></a></p><?php endif; ?></div>
</div>
<?php include __DIR__ . '/footer.tpl'; ?>
