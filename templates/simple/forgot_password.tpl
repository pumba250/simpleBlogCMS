{include 'header.tpl'}

<div class="w3-card-4 w3-margin w3-white">
    <div class="w3-container w3-padding-32">
        <h2>{Lang::get('forgot_password', 'core')}</h2>
        
        {if $flash}
            <div class="w3-panel w3-{$flash.type}">
                {$flash.message}
            </div>
        {/if}
        
        <form method="POST" action="?action=request_reset">
            <div class="w3-section">
                <label><b>{Lang::get('email', 'core')}</b></label>
                <input class="w3-input w3-border" type="email" name="email" required>
            </div>
            
            <button class="w3-button w3-black" type="submit">
                {Lang::get('send_reset_link', 'core')}
            </button>
        </form>
        
        <div class="w3-margin-top">
            <a href="?action=login">{Lang::get('back_to_login', 'core')}</a>
        </div>
    </div>
</div>

{include 'footer.tpl'}