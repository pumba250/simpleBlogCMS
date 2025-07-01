<?
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
?>
<!DOCTYPE html>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><? echo $pageTitle; ?></title>

<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'IT блог о настройке сетевого оборудования') ?>">
<meta name="keywords" content="<?= htmlspecialchars($metaKeywords ?? 'IT, блог, сети, mikrotik') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link id="theme-style" rel="stylesheet" href="/css/w3.css">
<link id="theme-style" rel="stylesheet" href="/css/css">
<style>
body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
.language-switcher { position: absolute; top: 20px; right: 20px; z-index: 1000; }
.language-switcher a { display: inline-block; width: 22px; height: 22px; margin-left: 5px; text-indent: -9999px; overflow: hidden; background-size: cover; border: 1px solid #ddd; opacity: 0.5; transition: opacity 0.3s; }
.language-switcher a:hover { opacity: 1; }
.language-switcher a[href*="en"] { background-image: url('/images/flags/en.png'); }
.language-switcher a[href*="ru"] { background-image: url('/images/flags/ru.png'); }
.language-switcher a.active { opacity: 1; border-color: #000;}
</style>
</head>
<body class="w3-light-grey">

<div class="w3-content" style="max-width:1400px">
<div class="language-switcher">
    <a href="?lang=en" title="English" <?= ($_SESSION['lang'] ?? 'en') == 'en' ? 'class="active"' : '' ?>>English</a>
    <a href="?lang=ru" title="Русский" <?= ($_SESSION['lang'] ?? 'en') == 'ru' ? 'class="active"' : '' ?>>Русский</a>
</div>
<!-- Header -->
<header class="w3-container w3-center w3-padding-32"> 
  <h1><b>MY BLOG</b></h1>
  <p><?= Lang::get('welcome') ?> of <span class="w3-tag">not young admin</span></p>
</header>
<!-- Grid -->
<div class="w3-row">

<!-- Blog entries -->
<div class="w3-col l8 s12">