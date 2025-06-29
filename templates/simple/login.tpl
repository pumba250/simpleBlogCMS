<?php include __DIR__ . '/header.tpl'; ?>

<div class="w3-card-4 w3-margin w3-white">
<div id="contact" class="w3-container w3-center w3-padding-32">
	<h2 class="w3-wide"><i class="fas fa-sign-in-alt"></i> <?= Lang::get('login') ?></h2>
        <?php if (isset($_SESSION['auth_error'])): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['auth_error'] ?>
            </div>
        <?php endif; ?>
            <form method="POST" class="auth-form">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> <?= Lang::get('username') ?></label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> <?= Lang::get('password') ?></label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-shield-alt"></i> <?= Lang::get('captcha') ?></label>
                    <div class="captcha-container">
                        <img src="<?= $captcha_image_url ?>" alt="<?= Lang::get('captcha') ?>">
                        <input type="text" name="captcha" required placeholder="<?= Lang::get('answer') ?>">
                    </div>
                </div>
                
                <div class="form-footer">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember" checked>
                        <span class="checkmark"></span>
                        <?= Lang::get('remember') ?>
                    </label>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> <?= Lang::get('loginuser') ?>
                    </button>
                </div>
            </form>
        
        <div class="auth-links">
            <a href="?action=forgot_password"><?= Lang::get('forgot_password', 'core') ?></a> | <?= Lang::get('nologin') ?> <a href="/?action=register"><?= Lang::get('doreg') ?></a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.tpl'; ?>