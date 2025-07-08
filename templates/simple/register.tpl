<div class="w3-card-4 w3-margin w3-white">
<div id="contact" class="w3-container w3-center w3-padding-32">
<?php if (!$user): ?><h2 class="w3-wide"><i class="fas fa-user-plus"></i> {l_register}</h2>
<p class="w3-opacity w3-center"><? flash(); ?></p>
<form method="POST">
    <input type="hidden" name="action" value="register">
	<input type="hidden" name="csrf_token" value="{$csrf_token}">
    
    <label for="username"><i class="fas fa-user"></i> {l_name}:</label>
    <input type="text" name="username" id="username" placeholder="{l_name}" required><br>
    
    <label for="password"><i class="fas fa-lock"></i> {l_password}:</label>
    <input type="password" name="password" id="password" placeholder="{l_password}" required><br>
    
    <label for="email"><i class="fas fa-envelope"></i> {l_email}:</label>
    <input type="email" name="email" id="email" placeholder="{l_email}" required><br>
    
    <label for="question"><i class="fas fa-shield-alt"></i>{l_howcapcha} <img src="<?php echo $captcha_image_url; ?>" alt="{l_captcha}"></label>
    <input type="text" name="captcha" required placeholder="{l_answer}"><br>
    
    <button type="submit"><i class="fas fa-sign-in-alt"></i> {l_doregister}</button>
</form>
<div class="auth-links">
                Уже есть аккаунт? <a href="?action=login">Войти</a>
            </div>
<?php else: ?><p class="w3-opacity w3-center"><a href="?">{l_main}</a></p><?php endif; ?></div>
</div>