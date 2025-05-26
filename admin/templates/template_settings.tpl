<!DOCTYPE html>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><? echo $pageTitle; ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link id="theme-style" rel="stylesheet" href="/css/w3.css">
<link id="theme-style" rel="stylesheet" href="/css/css">
<style>
body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
</style>
</head>
<body class="w3-light-grey">
<div class="w3-content" style="max-width:1400px">
<!-- Header -->
<header class="w3-container w3-center w3-padding-32"> 
  <h1>Панель управления</h1>
  <h2>Управление шаблонами блога</h2>
</header>
<!-- Grid -->
<div class="w3-row">
<!-- Blog entries -->
<div class="w3-col l8 s12">
        <div class="w3-card-4 w3-margin w3-white">
        <div class="w3-container">
            <h5><span class="w3-opacity"></span></h5>
        </div>
        <div class="w3-container">
            <p>
        <div class="container">
    <h1><?= $pageTitle ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= strpos($message, 'Ошибка') !== false ? 'danger' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <div class="card mt-4">
        <div class="card-header">
            <h3>Текущий шаблон: <?= htmlspecialchars($currentTemplate) ?></h3>
        </div>
        <div class="card-body">
            <?php if (!empty($templates)): ?>
                <div class="row">
                    <?php foreach ($templates as $templateName): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card <?= $templateName === $currentTemplate ? 'border-success' : '' ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars(ucfirst($templateName)) ?></h5>
                                    
                                    <?php if ($templateName === $currentTemplate): ?>
                                        <span class="badge bg-success">Активен</span>
                                    <?php else: ?>
                                        <form method="post" class="mt-2">
                                            <input type="hidden" name="action" value="change_template">
                                            <input type="hidden" name="template" value="<?= htmlspecialchars($templateName) ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                Активировать
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    Нет доступных шаблонов в папке templates/
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
    </p> <!-- Полное содержание -->
            <div class="w3-row">
                <div class="w3-col m8 s12">
                    <p></p>
                </div>
            </div>
        </div>
    </div>
    <hr>
</div>
<!-- Introduction menu -->
<div class="w3-col l4">
  <!-- About Card -->
  <div class="w3-card w3-margin w3-margin-top">
  <?php if ($user): ?><img src="https://yunisov.tech<?= htmlspecialchars($user['avatar']);?>" style="width:120px"><?php endif; ?>
    <div class="w3-container w3-white">
            <h4>Навигация</h4>
        <div class="w3-container w3-white">
			<p><a href="/">BLOG</a></p>
			<p><a href="index.php?view=manage_users">Управление пользователями</a></p>
			<p><a href="index.php?view=manage_comment">Управление Коментариями</a></p>
			<p><a href="index.php?view=template_settings">Управление Шаблонами</a></p>
			<p><a href="index.php?view=manage_feedback">Обратная связь</a></p>
			<p><a href="index.php?view=add_news">Добавить запись в блог</a></p>
        </div>
    </div>
  </div><hr>
</div>
</div><br>
</div>
<!-- Footer -->
<footer class="w3-container w3-dark-grey w3-padding-32 w3-margin-top w3-center">
  <p>Powered by <a href="https://www.w3schools.com/w3css/default.asp" target="_blank">w3.css</a> Copyright &copy; <?php echo date("Y");?> by <b><?php echo $_SERVER['SERVER_NAME'];?></b></p>
</footer>
</body></html>
<?
