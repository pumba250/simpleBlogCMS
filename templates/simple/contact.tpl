{include 'header.tpl'}

<div class="w3-card-4 w3-margin w3-white">
<div id="contact" class="w3-container w3-center w3-padding-32">
<h2 class="w3-wide">{Lang::get('contactus')}</h2>
        {if $flash}
            <div class="w3-panel w3-{$flash.type}">
                {$flash.message}
            </div>
        {/if}
<p class="w3-opacity w3-center"><i>{Lang::get('writefeed')}</i></p>

<form method="POST">
<input type="hidden" name="action" value="contact">
<input type="hidden" name="csrf_token" value="{$csrf_token}">
<input class="w3-input" type="text" placeholder="{Lang::get('name')}" required name="name">
<input class="w3-input" type="text" placeholder="{Lang::get('email')}" required name="email">
<input class="w3-input" type="text" placeholder="{Lang::get('message')}" required name="message">
<p>{Lang::get('howcapcha')} <img src="{$captcha_image_url}" alt="{Lang::get('captcha')}"></p>
    <input type="text" name="captcha" required placeholder="{Lang::get('answer')}">
    <br>
<button class="w3-button w3-black" type="submit">{Lang::get('submit')}</button>
</form>
</div>
</div>
{include 'footer.tpl'}
