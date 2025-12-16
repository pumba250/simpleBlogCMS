<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$pageTitle}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{$metaDescription}">
    <meta name="keywords" content="{$metaKeywords}">
    <link rel="stylesheet" href="/templates/{$templ}/css/font-awesome.min.css">
    <link rel="stylesheet" href="/templates/{$templ}/css/all.min.css">
    <link rel="stylesheet" href="/templates/{$templ}/css/w3.css">
    <link rel="stylesheet" href="/templates/{$templ}/css/modal.css">
    <script src="/templates/{$templ}/js/modal.js" defer></script>
    <style>
        body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
        .language-switcher { position: absolute; top: 20px; right: 20px; z-index: 1000; }
        .language-switcher a { display: inline-block; width: 22px; height: 22px; margin-left: 5px; text-indent: -9999px; overflow: hidden; background-size: cover; border: 1px solid #ddd; opacity: 0.4; transition: opacity 0.3s; }
        .language-switcher a:hover { opacity: 1; }
        .language-switcher a[href*="en"] { background-image: url('/images/flags/en.png'); }
        .language-switcher a[href*="ru"] { background-image: url('/images/flags/ru.png'); }
        .language-switcher a.active { opacity: 1; border-color: #616161;}
    </style>
</head>
<body class="w3-light-grey">

<div class="w3-content" style="max-width:1400px">
	<div class="language-switcher">
        <a href="?lang=en" title="English" class="{if $currentLang == 'en'}active{/if}">EN</a>
        <a href="?lang=ru" title="Русский" class="{if $currentLang == 'ru'}active{/if}">RU</a>
    </div>
<header class="w3-container w3-center w3-padding-32"> 
    <h1><b>MY BLOG</b></h1>
  <p>{l_welcome} of <span class="w3-tag">not young admin</span></p>
</header>
<div class="w3-row">
<div class="w3-col l8 s12">