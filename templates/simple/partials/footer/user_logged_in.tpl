<img class="w3-margin-top w3-circle" src="{$userData.avatar}" style="width:60px">
<p>
    <form method="post" action="/admin.php?logout=1">
        {l_hiuser}, {$userData.username}!
        <button type="submit" class="w3-button w3-gray">{l_logoutuser}</button>
    </form>
</p>
{if $userData['isadmin'] >= 7}
    <p><a href="/admin.php">{l_admpanel}</a></p>
{/if}
<p><a href="?action=profile">{l_profile:core}</a></p>