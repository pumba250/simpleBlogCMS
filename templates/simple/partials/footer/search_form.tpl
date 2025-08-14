<form action="/" method="get">
    <input type="hidden" name="action" value="search">
    <input type="text" name="search" placeholder="{l_findarea}" value="{if isset($_GET['search'])} {$_GET.search}{/if}">
    <button type="submit" class="w3-button w3-gray">{l_find}</button>
</form>