<?php include __DIR__ . '/header.tpl'; 
session_start();

?><div class="w3-card-4 w3-margin w3-dark-grey">
<div id="contact" class="w3-container w3-center w3-padding-32">
<?php if (!$user): ?><h2 class="w3-wide">Регистрация</h2>
<p class="w3-opacity w3-center"><? flash(); ?></p>
<form method="POST">
    <input type="hidden" name="action" value="register">
    
    <label for="username">Логин:</label>
    <input class="w3-input w3-border" type="text" name="username" id="username" placeholder="Логин" required><br>
    
    <label for="password">Пароль:</label>
    <input class="w3-input w3-border" type="password" name="password" id="password" placeholder="Пароль" required><br>
    
    <label for="email">Email:</label>
    <input class="w3-input w3-border" type="email" name="email" id="email" placeholder="Email" required><br>
    
    <label for="question">Сколько будет <img src="<?php echo $captcha_image_url; ?>" alt="Капча"></label>
    <input class="w3-input w3-border" type="text" name="captcha" required placeholder="Введите ответ"><br>
    
    <button class="w3-button w3-teal" type="submit">Зарегистрироваться</button>
</form><?php else: ?><p class="w3-opacity w3-center"><a href="?" class="w3-text-teal">На главную</a></p><?php endif; ?></div>
</div>
<?php include __DIR__ . '/footer.tpl'; ?>