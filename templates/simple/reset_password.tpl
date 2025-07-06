{include 'header.tpl'}

<div class="w3-card-4 w3-margin w3-white">
    <div class="w3-container w3-padding-32">
        <h2>{Lang::get('reset_password', 'core')}</h2>
        
        {if $flash}
            <div class="w3-panel w3-{$flash.type}">
                {$flash.message}
            </div>
        {/if}
        
        <form method="POST" action="?action=reset_password">
            <input type="hidden" name="token" value="{$token}">
            
            <div class="w3-section">
                <label><b>{Lang::get('new_password', 'core')}</b></label>
                <input class="w3-input w3-border" type="password" name="password" required>
            </div>
            
            <div class="w3-section">
                <label><b>{Lang::get('confirm_password', 'core')}</b></label>
                <input class="w3-input w3-border" type="password" name="password_confirm" required>
            </div>
            
            <button class="w3-button w3-black" type="submit">
                {Lang::get('reset_password_btn', 'core')}
            </button>
        </form>
    </div>
</div>

{include 'footer.tpl'}