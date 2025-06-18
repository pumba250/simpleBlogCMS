<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><? echo $pageTitle;?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #333; color: white; padding: 20px 0; }
        .admin-content { flex: 1; padding: 20px; }
        .admin-menu { list-style: none; padding: 0; margin: 0; }
        .admin-menu li { padding: 10px 20px; }
        .admin-menu li a { color: white; text-decoration: none; display: block; }
        .admin-menu li a:hover { background: #444; }
        .admin-menu li.active { background: #555; }
        .admin-header { background: #444; color: white; padding: 15px; margin-bottom: 20px; }
        .admin-table { width: 100%; border-collapse: collapse; background: white; }
        .admin-table th, .admin-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        .admin-table th { background: #444; color: white; }
        .admin-table tr:hover { background: #f9f9f9; }
        .btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        textarea.form-control { min-height: 150px; }
        .checkbox-label { display: inline-block; margin-left: 5px; }
		.templates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; }
		.template-card { background-color: white; padding: 1.5rem; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; border: 2px solid transparent; }
		.template-card.active { border-color: var(--success-color); }
		.template-card h4 { margin-top: 0; }
		/* Основные стили для горизонтальной пагинации */
.pagination {
    display: flex;
    flex-wrap: nowrap;
    padding-left: 0;
    list-style: none;
    margin: 0;
}

.pagination.pagination-sm .page-item .page-link {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
}

.pagination .page-item {
    margin: 0 2px; /* Небольшой отступ между элементами */
}

.pagination .page-item .page-link {
    position: relative;
    display: block;
    color: #0d6efd;
    text-decoration: none;
    background-color: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
}

.pagination .page-item.active .page-link {
    z-index: 3;
    color: #fff;
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.pagination .page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    background-color: #fff;
    border-color: #dee2e6;
}

/* Для float-right */
.float-right {
    float: right !important;
}

/* Очистка float */
.m-0 {
    margin: 0 !important;
}
		/* Base badge styles */
.badge {
    display: inline-block;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    color: #000;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Color variants */
.badge.bg-primary {
    background-color: #0d6efd;
}

.badge.bg-secondary {
    background-color: #6c757d;
}

.badge.bg-success {
    background-color: #00bd49;
}

.badge.bg-danger {
    background-color: #dc3545;
}

.badge.bg-warning {
    background-color: #ff5858;
    color: #212529; /* Dark text for better contrast */
}

.badge.bg-info {
    background-color: #0dcaf0;
    color: #212529; /* Dark text for better contrast */
}

/* Optional: Add some animations */
.badge {
    transition: all 0.2s ease-in-out;
}

.badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* For the checkmark and cross icons */
.badge .icon {
    margin-right: 3px;
    font-size: 0.9em;
}

/* Small version */
.badge-sm {
    padding: 0.2em 0.4em;
    font-size: 0.65em;
}

/* Large version */
.badge-lg {
    padding: 0.5em 0.8em;
    font-size: 0.9em;
}
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2 style="padding: 0 20px;">Меню</h2>
            <ul class="admin-menu">
                <li <?if ($section == 'blogs'):?>class="active"<?endif;?>><a href="?section=blogs">Записи блога</a></li>
                <li <?if ($section == 'contacts'):?>class="active"<?endif;?>><a href="?section=contacts">Обратная связь</a></li>
                <li <?if ($section == 'users'):?>class="active"<?endif;?>><a href="?section=users">Пользователи</a></li>
				<li <?if ($section == 'comments'):?>class="active"<?endif;?>><a href="?section=comments">Комментарии</a></li>
                <li <?if ($section == 'tags'):?>class="active"<?endif;?>><a href="?section=tags">Теги</a></li>
				<li <?if ($section == 'logs'):?>class="active"<?endif;?>><a href="?section=logs">Логи действий</a></li>
                <li <?if ($section == 'template_settings'):?>class="active"<?endif;?>><a href="?section=template_settings">Шаблоны</a></li>
                <li><a href="/">На сайт</a></li>
                <li><a href="?logout">Выйти</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1><? echo $pageTitle;?></h1>
                <p>Добро пожаловать, <? echo $user['username'];?>!</p>
            </div>
            
            <?if (isset($admin_message)):?>
                <div class="alert alert-success"><?=$admin_message;?></div>
            <?endif;?>
            <?if (isset($admin_error)):?>
                <div class="alert alert-error"><?=$admin_error;?></div>
            <?endif;?>
            
            <?if ($section == 'blogs'):?>
                <!-- Раздел управления записями блога -->
                <h2>Управление записями</h2>
                <button onclick="document.getElementById('add-blog-form').style.display='block'" class="btn btn-primary">Добавить запись</button>
                
                <div id="add-blog-form" style="display:none; margin: 20px 0; padding: 20px; background: white; border-radius: 5px;">
                    <h3>Добавить новую запись</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
                        <input type="hidden" name="action" value="add_blog">
                        
                        <div class="form-group">
                            <label>Заголовок:</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Содержание:</label>
                            <textarea name="content" class="form-control" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Теги:</label>
                            <?foreach ($allTags as $tag):?>
                                <div>
                                    <input type="checkbox" name="tags[]" value="<?echo $tag['id'];?>" id="tag_<?echo $tag['id'];?>_new">
                                    <label for="tag_<?echo $tag['id'];?>_new" class="checkbox-label"><?echo $tag['name'];?></label>
                                </div>
                            <?endforeach;?>
                        </div>
                        
                        <button type="submit" class="btn btn-success">Сохранить</button>
                        <button type="button" onclick="document.getElementById('add-blog-form').style.display='none'" class="btn btn-danger">Отмена</button>
                    </form>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Заголовок</th>
                            <th>Дата создания</th>
                            <th>Теги</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?foreach ($blogs as $blog):?>
                        <tr>
                            <td><?= $blog['id'];?></td>
                            <td><?= $blog['title'];?></td>
                            <td><?= $blog['created_at'];?></td>
                            <td>
                                <?foreach ($blog['tags'] as $tag):?>
                                    <span style="background: #eee; padding: 3px 6px; border-radius: 3px; margin-right: 5px;"><?= $tag;?></span>
                                <?endforeach;?>
                            </td>
                            <td>
                                <button onclick="showEditForm(<?=$blog['id'];?>, '<?=$blog['title'];?>', '<?=$blog['content'];?>', [<?foreach ($blog['tag_ids'] as $tag_id):echo $tag_id;endforeach;?>])" class="btn btn-primary">Редактировать</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
                                    <input type="hidden" name="action" value="delete_blog">
                                    <input type="hidden" name="id" value="<?=$blog['id'];?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Вы уверены?')">Удалить</button>
                                </form>
                            </td>
                        </tr>
                        <?endforeach;?>
                    </tbody>
                </table>
                
                <!-- Форма редактирования (скрыта по умолчанию) -->
                <div id="edit-blog-form" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 5px; box-shadow: 0 0 20px rgba(0,0,0,0.2); z-index: 1000; width: 80%; max-width: 800px;">
                    <h3>Редактировать запись</h3>
                    <form method="POST" id="edit-form">
                        <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
                        <input type="hidden" name="action" value="edit_blog">
                        <input type="hidden" name="id" id="edit-id">
                        
                        <div class="form-group">
                            <label>Заголовок:</label>
                            <input type="text" name="title" id="edit-title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Содержание:</label>
                            <textarea name="content" id="edit-content" class="form-control" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Теги:</label>
                            <div id="edit-tags-container">
                                <?foreach ($allTags as $tag):?>
                                    <div>
                                        <input type="checkbox" name="tags[]" value="<?=$tag['id'];?>" id="tag_<?=$tag['id'];?>">
                                        <label for="tag_<?=$tag['id'];?>" class="checkbox-label"><?=$tag['name'];?></label>
                                    </div>
                                <?endforeach;?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">Сохранить</button>
                        <button type="button" onclick="document.getElementById('edit-blog-form').style.display='none'" class="btn btn-danger">Отмена</button>
                    </form>
                </div>
                
                <script>
                    function showEditForm(id, title, content, tagIds) {
                        document.getElementById('edit-id').value = id;
                        document.getElementById('edit-title').value = title;
                        document.getElementById('edit-content').value = content;
                        
                        // Сбросить все чекбоксы
                        let checkboxes = document.querySelectorAll('#edit-tags-container input[type="checkbox"]');
                        checkboxes.forEach(cb => cb.checked = false);
                        
                        // Отметить выбранные теги
                        tagIds.forEach(tagId => {
                            let cb = document.querySelector(`#edit-tags-container input[value="${tagId}"]`);
                            if (cb) cb.checked = true;
                        });
                        
                        document.getElementById('edit-blog-form').style.display = 'block';
                    }
                </script>
                
            <?elseif ($section == 'contacts'):?>
                <!-- Раздел обратной связи -->
                <h2>Сообщения от пользователей</h2>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>Сообщение</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?foreach ($contacts as $contact):?>
                        <tr>
                            <td><?=$contact['id'];?></td>
                            <td><?=$contact['name'];?></td>
                            <td><?=$contact['email'];?></td>
                            <td><?=$contact['message'];?></td>
                            <td><?=$contact['created_at'];?></td>
                            <td>
                                <button onclick="alert('<?=$contact['message'];?>')" class="btn btn-primary">Просмотр</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
                                    <input type="hidden" name="action" value="delete_contact">
                                    <input type="hidden" name="id" value="<?=$contact['id'];?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Вы уверены?')">Удалить</button>
                                </form>
                            </td>
                        </tr>
                        <?endforeach;?>
                    </tbody>
                </table>
                
            <?elseif ($section == 'users'):?>
                <!-- Раздел пользователей -->
                <h2>Управление пользователями</h2>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Логин</th>
                            <th>Email</th>
                            <th>Роль</th>
                            <th>Дата регистрации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?foreach ($users as $user):?>
						<? $roleName = getRoleName($user['isadmin']);?>
                        <tr>
                            <td><?=$user['id'];?></td>
                            <td><?=$user['username'];?></td>
                            <td><?=$user['email'];?></td>
                            <td><?=$roleName;?></td>
                            <td><?=$user['created_at'];?></td>
                            <td>
                                <!-- Кнопка редактирования в таблице пользователей -->
<button onclick="showUserEditForm(<?=$user['id'];?>, '<?=htmlspecialchars($user['username'], ENT_QUOTES);?>', '<?=htmlspecialchars($user['email'], ENT_QUOTES);?>', <?=(int)$user['isadmin'];?>)" class="btn btn-primary btn-sm">
    <i class="fas fa-edit"></i> Редактировать
</button>
                                <?if ($user['id'] != $_SESSION['user_id']):?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="id" value="<?=$user['id'];?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Вы уверены?')">Удалить</button>
                                    </form>
                                <?endif;?>
                            </td>
                        </tr>
                        <?endforeach;?>
                    </tbody>
                </table>
                
                <!-- Форма редактирования -->
<div id="edit-user-form" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; padding:25px; border-radius:8px; box-shadow:0 0 15px rgba(0,0,0,0.2); z-index:1050; width:450px; max-width:95%;">
    <button onclick="closeEditForm()" style="position:absolute; top:10px; right:10px; background:none; border:none; font-size:1.2rem; cursor:pointer;">&times;</button>
    
    <h4 style="margin-top:0; margin-bottom:20px;">Редактировать пользователя</h4>
    
    <form method="POST" id="edit-user-form-content">
        <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
        <input type="hidden" name="action" value="edit_user">
        <input type="hidden" name="id" id="edit-user-id">
        
        <div style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px; font-weight:500;">Логин:</label>
            <input type="text" name="username" id="edit-username" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" required>
        </div>
        
        <div style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px; font-weight:500;">Email:</label>
            <input type="email" name="email" id="edit-email" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" required>
        </div>
        
        <div style="margin-bottom:20px;">
            <label style="display:block; margin-bottom:8px; font-weight:500;">Роль:</label>
            <div style="display:flex; gap:10px;">
                <label style="flex:1; text-align:center;">
                    <input type="radio" name="isadmin" value="0" style="margin-right:5px;"> Пользователь
                </label>
                <label style="flex:1; text-align:center;">
                    <input type="radio" name="isadmin" value="7" style="margin-right:5px;"> Модератор
                </label>
                <label style="flex:1; text-align:center;">
                    <input type="radio" name="isadmin" value="9" style="margin-right:5px;"> Админ
                </label>
            </div>
        </div>
        
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="closeEditForm()" style="padding:8px 15px; background:#dc3545; color:white; border:none; border-radius:4px; cursor:pointer;">Отмена</button>
            <button type="submit" style="padding:8px 15px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer;">Сохранить</button>
        </div>
    </form>
</div>

<script>
// Функция открытия формы редактирования
function showUserEditForm(id, username, email, isadmin) {
    console.log('Opening edit form for user:', {id, username, email, isadmin});
    
    // Заполняем форму данными
    document.getElementById('edit-user-id').value = id;
    document.getElementById('edit-username').value = username;
    document.getElementById('edit-email').value = email;
    
    // Устанавливаем правильную роль
    const roleRadios = document.getElementsByName('isadmin');
    for (let radio of roleRadios) {
        radio.checked = (radio.value == isadmin);
    }
    
    // Показываем форму
    document.getElementById('edit-user-form').style.display = 'block';
    
    // Добавляем затемнение фона
    const overlay = document.createElement('div');
    overlay.id = 'modal-overlay';
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.background = 'rgba(0,0,0,0.5)';
    overlay.style.zIndex = '1040';
    overlay.onclick = closeEditForm;
    document.body.appendChild(overlay);
}

// Функция закрытия формы
function closeEditForm() {
    document.getElementById('edit-user-form').style.display = 'none';
    const overlay = document.getElementById('modal-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// Закрытие по ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditForm();
    }
});

// Для тестирования - можно раскомментировать
// showUserEditForm(1, 'testuser', 'test@example.com', 7);
</script>
            <?elseif ($section == 'template_settings'):?>
                <!-- Раздел тегов -->
                <h2>Управление Шаблонами</h2>
				
				<div class="current-template">
					<h3>Текущий шаблон: <?= htmlspecialchars($currentTemplate) ?></h3>
				</div>

				<div class="templates-grid">
					<?php foreach ($templates as $templateName): ?>
					<div class="template-card <?= $templateName === $currentTemplate ? 'active' : '' ?>">
						<h4><?= htmlspecialchars(ucfirst($templateName)) ?></h4>
						<?php if ($templateName === $currentTemplate): ?>
							<span class="badge">Активен</span>
						<?php else: ?>
							<form method="post">
								<input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
								<input type="hidden" name="action" value="change_template">
								<input type="hidden" name="template" value="<?= htmlspecialchars($templateName) ?>">
								<button type="submit" class="btn">Активировать</button>
							</form>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>

            <?elseif ($section == 'tags'):?>
                <!-- Раздел тегов -->
                <h2>Управление тегами</h2>
                
                <div style="margin-bottom: 20px;">
                    <button onclick="document.getElementById('add-tag-form').style.display='block'" class="btn btn-primary">Добавить тег</button>
                    
                    <div id="add-tag-form" style="display:none; margin-top: 20px; padding: 20px; background: white; border-radius: 5px;">
                        <h3>Добавить новый тег</h3>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
                            <input type="hidden" name="action" value="add_tag">
                            
                            <div class="form-group">
                                <label>Название тега:</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-success">Сохранить</button>
                            <button type="button" onclick="document.getElementById('add-tag-form').style.display='none'" class="btn btn-danger">Отмена</button>
                        </form>
                    </div>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?foreach ($tags as $tag):?>
                        <tr>
                            <td><?=$tag['id'];?></td>
                            <td><?=$tag['name'];?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
                                    <input type="hidden" name="action" value="delete_tag">
                                    <input type="hidden" name="id" value="<?=$tag['id'];?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Вы уверены? Удаление тега также удалит все его связи с записями.')">Удалить</button>
                                </form>
                            </td>
                        </tr>
                        <?endforeach;?>
                    </tbody>
                </table>
            <?elseif ($section == 'comments'):?>
				<!-- Раздел комментариев -->
				<h2>Управление комментариями <span class="badge"><?=$pendingCount;?> на модерации</span></h2>
				
				<table class="admin-table">
					<thead>
						<tr>
							<th>ID</th>
							<th>Пост</th>
							<th>Пользователь</th>
							<th>Текст</th>
							<th>Дата</th>
							<th>Рейтинг (+/-)</th>
							<th>Статус</th>
							<th>Действия</th>
						</tr>
					</thead>
					<tbody>
						<?foreach ($comments as $comment):?>
						<tr>
							<td><?=$comment['id'];?></td>
							<td><?=$comment['post_title'] ?? 'Без привязки';?></td>
							<td><?=$comment['user_name'];?></td>
							<td><?=mb_substr($comment['user_text'], 0, 50, 'UTF-8');?>...</td>
							<td><?=date('d.m.Y H:i', $comment['created_at']);?></td>
							<td><?=$comment['plus'];?>/<?=$comment['minus'];?></td>
							<td>
								<?if ($comment['moderation']):?>
									<span class="badge" style="background: #28a745;">Одобрен</span>
								<?else:?>
									<span class="badge" style="background: #ffc107;">На модерации</span>
								<?endif;?>
							</td>
							<td>
								<button onclick="showCommentEditForm(<?=$comment['id'];?>, '<?=htmlspecialchars($comment['user_text'], ENT_QUOTES);?>', <?=$comment['moderation'];?>)" class="btn btn-primary">Ред.</button>
								<form method="POST" style="display:inline;">
									<input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
									<input type="hidden" name="action" value="toggle_comment">
									<input type="hidden" name="id" value="<?=$comment['id'];?>">
									<button type="submit" class="btn <?=$comment['moderation'] ? 'btn-warning' : 'btn-success';?>">
										<?=$comment['moderation'] ? 'Скрыть' : 'Одобрить';?>
									</button>
								</form>
								<form method="POST" style="display:inline;">
									<input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
									<input type="hidden" name="action" value="delete_comment">
									<input type="hidden" name="id" value="<?=$comment['id'];?>">
									<button type="submit" class="btn btn-danger" onclick="return confirm('Удалить этот комментарий?')">Удалить</button>
								</form>
							</td>
						</tr>
						<?endforeach;?>
					</tbody>
				</table>
<?if ($totalPages > 1):?>
    <div class="admin-pagination">
        <?if ($currentPage > 1):?>
            <a href="?section=comments&page=<?=$currentPage-1;?>">← Предыдущая</a>
        <?endif;?>
        
        Страница <?=$currentPage;?> из <?=$totalPages;?>
        
        <?if ($currentPage < $totalPages):?>
            <a href="?section=comments&page=<?=$currentPage+1;?>">Следующая →</a>
        <?endif;?>
    </div>
<?endif;?>
				<!-- Форма редактирования комментария -->
				<div id="edit-comment-form" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 5px; box-shadow: 0 0 20px rgba(0,0,0,0.2); z-index: 1000; width: 80%; max-width: 800px;">
					<h3>Редактировать комментарий</h3>
					<form method="POST" id="edit-comment-form-content">
						<input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
						<input type="hidden" name="action" value="edit_comment">
						<input type="hidden" name="id" id="edit-comment-id">
						
						<div class="form-group">
							<label>Текст комментария:</label>
							<textarea name="user_text" id="edit-comment-text" class="form-control" rows="8" required></textarea>
						</div>
						
						<div class="form-group">
							<input type="checkbox" name="moderation" id="edit-comment-moderation">
							<label for="edit-comment-moderation" class="checkbox-label">Одобрен</label>
						</div>
						
						<button type="submit" class="btn btn-success">Сохранить</button>
						<button type="button" onclick="document.getElementById('edit-comment-form').style.display='none'" class="btn btn-danger">Отмена</button>
					</form>
				</div>

				<script>
					function showCommentEditForm(id, text, moderation) {
						document.getElementById('edit-comment-id').value = id;
						document.getElementById('edit-comment-text').value = text;
						document.getElementById('edit-comment-moderation').checked = moderation == 1;
						
						document.getElementById('edit-comment-form').style.display = 'block';
					}
				</script>
			<?php elseif ($section == 'logs'): ?>
				<h2>Логи административных действий</h2><span class="badge bg-primary">Всего записей: <?= $totalLogs ?></span>
					<table class="admin-table">
                    <thead>
                        <tr>
                            <th></th>
							<th>Пользователь</th>
                            <th>Действия</th>
                            <th>Доп.инфо</th>
							<th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
									<?php foreach ($logs as $log): ?>
									<tr>
										<td style="width: 60px; padding: 10px; text-align: center;">
											<?php if (!empty($log['user_id'])): ?>
												<img src="<?= !empty($log['avatar']) ? htmlspecialchars($log['avatar']) : '/images/avatar_g.png' ?>" 
													 class="img-circle elevation-2" 
													 style="width: 40px; height: 40px; object-fit: cover;" 
													 alt="User Avatar">
											<?php else: ?>
												<i class="fas fa-cog fa-2x text-muted"></i>
											<?php endif; ?>
										</td>
										<td style="vertical-align: middle;">
											
													<strong>
														<?php if (!empty($log['user_id'])): ?>
															<?= htmlspecialchars($log['username'] ?? 'Система') ?>
														<?php else: ?>
															Система
														<?php endif; ?>
													</strong>
													<small class="text-muted" title="<?= date('d.m.Y H:i:s', strtotime($log['created_at'])) ?>">
														<?= time_elapsed_string($log['created_at']) ?>
													</small>
										</td>
										<td>
												<div class="text-break">
													<span class="badge bg-<?= getLogBadgeColor($log['action']) ?>">
														<?= htmlspecialchars($log['action']) ?>
													</span>
													<?php if (!empty($log['details'])): ?>
														- <?= htmlspecialchars($log['details']) ?>
													<?php endif; ?>
												</div>
										</td>
										<td>
												<?php if (!empty($log['ip_address'])): ?>
													<small class="text-muted">
														IP: <?= htmlspecialchars($log['ip_address']) ?>
													</small>
												<?php endif; ?>
											
										</td>
										<td>
											<form method="post" style="display:inline;">
												<input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
												<input type="hidden" name="action" value="delete_log">
												<input type="hidden" name="id" value="<?=$log['id']?>">
												<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить эту запись лога?')">
													<i class="bi bi-trash"></i> Удалить
												</button>
											</form>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
					<div class="card-footer clearfix">
						<?php if ($totalLogs > $perPage): ?>
							<ul class="pagination pagination-sm m-0 float-right">
								<?php if ($currentPage > 1): ?>
									<li class="page-item">
										<a class="page-link" href="?section=logs&page=<?= $currentPage - 1 ?>">&laquo;</a>
									</li>
								<?php endif; ?>
								
								<?php 
								$totalPages = ceil($totalLogs / $perPage);
								$startPage = max(1, $currentPage - 2);
								$endPage = min($totalPages, $currentPage + 2);
								
								if ($startPage > 1) {
									echo '<li class="page-item"><a class="page-link" href="?section=logs&page=1">1</a></li>';
									if ($startPage > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
								}
								
								for ($i = $startPage; $i <= $endPage; $i++): ?>
									<li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
										<a class="page-link" href="?section=logs&page=<?= $i ?>"><?= $i ?></a>
									</li>
								<?php endfor; 
								
								if ($endPage < $totalPages) {
									if ($endPage < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
									echo '<li class="page-item"><a class="page-link" href="?section=logs&page='.$totalPages.'">'.$totalPages.'</a></li>';
								}
								?>
								
								<?php if ($currentPage < $totalPages): ?>
									<li class="page-item">
										<a class="page-link" href="?section=logs&page=<?= $currentPage + 1 ?>">&raquo;</a>
									</li>
								<?php endif; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
				<?php elseif ($section == 'server_info'): ?>
					<div class="card">
						<div class="card-header">
							<h3 class="card-title">Информация о сервере</h3>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-6">
									<div class="card">
										<div class="card-header bg-primary">
											<h4 class="card-title">Основная информация</h4>
										</div>
										<div class="card-body p-0">
											<table class="admin-table">
												<tbody>
													<?php foreach ($serverInfo as $key => $value): ?>
													<tr>
														<td width="40%"><strong><?= $key ?></strong></td>
														<td><?= htmlspecialchars($value) ?></td>
													</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									</div>
								</div>
								
								<div class="col-md-6">
									<div class="card">
										<div class="card-header bg-info">
											<h4 class="card-title">Права доступа к файлам</h4>
										</div>
										<div class="card-body p-0">
											<table class="admin-table">
												<tbody>
													<?php foreach ($filePermissions as $file => $perms): ?>
													<tr>
														<td width="40%"><strong><?= $file ?></strong></td>
														<td>
														<?php if ($file == 'install.php' && $perms == 'Не найден'): ?>
															<span class="badge bg-success" title="Файл удален, что рекомендуется для безопасности">
																<i class="fas fa-check"></i> Удален
															</span>
														<?php else: ?>
															<span class="badge bg-<?= ($perms == '0644' || $perms == '0444') ? 'success' : 'warning' ?>">
																<?= $perms ?>
															</span>
															<?php if (($file == 'config/config.php') && $perms != '0644' && $perms != '0444'): ?>
															<small class="text-danger">(Рекомендуется 0644)</small>
															<?php endif; ?>
															<?php endif; ?>
															
														</td>
													</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									</div>
									
									<div class="card mt-4">
										<div class="card-header bg-secondary">
											<h4 class="card-title">PHP модули</h4>
										</div>
										<div class="card-body p-0">
											<table class="admin-table">
												<tbody>
													<?php foreach ($phpModules as $module => $status): ?>
													<tr>
														<td width="40%"><strong><?= $module ?></strong></td>
														<td>
															<span class="badge bg-<?= $status == '✓' ? 'success' : 'danger' ?>">
																<?= $status ?>
															</span>
														</td>
													</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>
        </div>
    </div>
</body>
</html>