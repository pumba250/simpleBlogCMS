<div class="w3-card-4 w3-margin w3-white">
<div id="contact" class="w3-container w3-center w3-padding-32">
	<h2 class="w3-wide"><i class="fas fa-sign-in-alt"></i> {l_login}</h2>
        {if $auth_error}
            <div class="alert error">
                <i class="fas fa-exclamation-circle"></i> {$auth_error}
            </div>
        {/if}
		{if $flash}
			<div class="flash flash-{$flash.type}">{$flash.message}</div>
		{/if}
            <form method="POST" class="auth-form">
                <input type="hidden" name="action" value="login">
				<input type="hidden" name="csrf_token" value="{$csrf_token}">
                
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> {l_username}</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> {l_password}</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-shield-alt"></i> {l_captcha}</label>
                    <div class="captcha-container">
                        <img src="{$captcha_image_url}" alt="{l_captcha}">
                        <input type="text" name="captcha" required placeholder="{l_answer}">
                    </div>
                </div>
                
                <div class="form-footer">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember" checked>
                        <span class="checkmark"></span>
                        {l_remember}
                    </label>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> {l_loginuser}
                    </button>
                </div>
            </form>
        
        <div class="auth-links">
            <a href="?action=forgot_password">{l_forgot_password:core}</a> | {l_nologin} <a href="/?action=register">{l_doreg}</a>
        </div>
    </div>
</div>