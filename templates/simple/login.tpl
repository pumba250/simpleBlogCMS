<?php include __DIR__ . '/header.tpl'; ?>

<div class="card auth-card">
    <div class="card-header">
        <h2><i class="fas fa-sign-in-alt"></i> Вход в систему</h2>
    </div>
    
    <div class="card-body">
        <?php if (isset($_SESSION['auth_error'])): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['auth_error'] ?>
            </div>
        <?php endif; ?>
        
        
            <form method="POST" class="auth-form">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Имя пользователя</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-shield-alt"></i> Капча</label>
                    <div class="captcha-container">
                        <img src="<?= $captcha_image_url ?>" alt="Капча">
                        <input type="text" name="captcha" required placeholder="Введите ответ">
                    </div>
                </div>
                
                <div class="form-footer">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember" checked>
                        <span class="checkmark"></span>
                        Запомнить меня
                    </label>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </button>
                </div>
            </form>
        
        <div class="auth-links">
            Нет аккаунта? <a href="/?action=register">Зарегистрируйтесь</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.tpl'; ?>