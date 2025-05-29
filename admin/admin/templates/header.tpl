<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="/admin/templates/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Админ-панель</h1>
            <div class="user-info">
                <?php if ($user): ?>
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Аватар" class="avatar">
                    <span><?= htmlspecialchars($user['username']) ?></span>
                <?php endif; ?>
            </div>
        </header>
        <nav class="admin-nav">
            <ul>
                <li><a href="index.php?view=dashboard"><i class="icon-dashboard"></i> Статистика</a></li>
                <li><a href="index.php?view=manage_users"><i class="icon-users"></i> Пользователи</a></li>
                <li><a href="index.php?view=manage_comment"><i class="icon-comments"></i> Комментарии</a></li>
                <li><a href="index.php?view=manage_feedback"><i class="icon-feedback"></i> Обратная связь</a></li>
                <li><a href="index.php?view=add_news"><i class="icon-add"></i> Добавить запись</a></li>
                <li><a href="index.php?view=template_settings"><i class="icon-templates"></i> Шаблоны</a></li>
                <li><a href="/"><i class="icon-home"></i> На сайт</a></li>
            </ul>
        </nav>
        <main class="admin-content">