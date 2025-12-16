<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><? echo $pageTitle;?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { 
            width: 250px; 
            background: #333; 
            color: white; 
            padding: 20px 0;
            position: relative;
            transition: transform 0.3s ease;
        }
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
		/* Бургер-меню */
        .burger-menu {
            display: none;
            cursor: pointer;
            padding: 15px;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
        }
        
        .burger-menu span {
            display: block;
            width: 25px;
            height: 3px;
            margin-bottom: 5px;
            background: #000;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle {
            display: none;
        }
        
        /* Мобильные стили */
        @media (max-width: 768px) {
            .admin-container {
                position: relative;
            }
            
            .admin-sidebar {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                z-index: 999;
                transform: translateX(-100%);
            }
            
            .admin-sidebar.active {
                transform: translateX(0);
            }
            
            .admin-content {
                margin-left: 0;
                width: 100%;
            }
            
            .burger-menu {
                display: block;
            }
            
            .sidebar-toggle:checked ~ .admin-sidebar {
                transform: translateX(0);
            }
            
            .sidebar-toggle:checked + .burger-menu span:nth-child(1) {
                transform: rotate(45deg) translate(5px, 5px);
            }
            
            .sidebar-toggle:checked + .burger-menu span:nth-child(2) {
                opacity: 0;
            }
            
            .sidebar-toggle:checked + .burger-menu span:nth-child(3) {
                transform: rotate(-45deg) translate(7px, -6px);
            }
        }
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
        <!-- Чекбокс для управления состоянием меню -->
        <input type="checkbox" id="sidebar-toggle" class="sidebar-toggle">
        
        <!-- Бургер-меню -->
        <label for="sidebar-toggle" class="burger-menu">
            <span></span>
            <span></span>
            <span></span>
        </label>
        
        <div class="admin-sidebar">
            <h2 style="padding: 0 20px;"><?= Lang::get('menu', 'admin') ?></h2>
            <ul class="admin-menu">
                <li <?if ($section == 'system_settings'):?>class="active"<?endif;?>><a href="?section=system_settings"><?= Lang::get('system_settings', 'admin') ?></a></li>
                <li class="nav-item <?if ($section == 'updates'):?>active<?endif?>"><a href="?section=updates"><i class="fas fa-sync-alt"></i> <?= Lang::get('update', 'admin') ?>
        <?if ($updateInfo):?>
            <span class="badge bg-danger"><?= Lang::get('new', 'admin') ?></span>
        <?endif?>
    </a>
</li>
                <li <?if ($section == 'blogs'):?>class="active"<?endif;?>><a href="?section=blogs"><?= Lang::get('blogs', 'admin') ?></a></li>
                <li <?if ($section == 'contacts'):?>class="active"<?endif;?>><a href="?section=contacts"><?= Lang::get('contacts', 'admin') ?></a></li>
                <li <?if ($section == 'users'):?>class="active"<?endif;?>><a href="?section=users"><?= Lang::get('users', 'admin') ?></a></li>
                <li <?if ($section == 'backups'):?>class="active"<?endif;?>><a href="?section=backups"><?= Lang::get('backups', 'admin') ?></a></li>
                <li <?if ($section == 'comments'):?>class="active"<?endif;?>><a href="?section=comments"><?= Lang::get('comments', 'admin') ?></a></li>
                <li <?if ($section == 'tags'):?>class="active"<?endif;?>><a href="?section=tags"><?= Lang::get('tags', 'admin') ?></a></li>
                <li <?if ($section == 'logs'):?>class="active"<?endif;?>><a href="?section=logs"><?= Lang::get('admlogs', 'admin') ?></a></li>
                <li <?if ($section == 'template_settings'):?>class="active"<?endif;?>><a href="?section=template_settings"><?= Lang::get('templates', 'admin') ?></a></li>
                <li><a href="/"><?= Lang::get('go_main', 'admin') ?></a></li>
                <li><a href="?logout"><?= Lang::get('log_out', 'admin') ?></a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1><?= Lang::get('admin_page', 'admin') ?></h1>
                <p><?= Lang::get('welcome', 'admin') ?>, <? echo $user['username'];?>!</p>
            </div>
            
            <?if (isset($admin_message)):?>
                <div class="alert alert-success"><?=$admin_message;?></div>
            <?endif;?>
            <?if (isset($admin_error)):?>
                <div class="alert alert-error"><?=$admin_error;?></div>
            <?endif;?>
            <?php if ($section == 'blogs' && $_GET['action'] == 'edit' && isset($editBlog)): ?>
			
					<div class="admin-edit-form">
						<h2><?= Lang::get('edit_blog', 'admin') ?> #<?=$editBlog['id'];?></h2>
						
						<form method="POST">
							<input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
							<input type="hidden" name="action" value="update_blog">
							<input type="hidden" name="id" value="<?=$editBlog['id'];?>">
							
							<div class="form-group">
								<label><?= Lang::get('blog_title', 'admin') ?>:</label>
								<input type="text" name="title" class="form-control" value="<?=htmlspecialchars($editBlog['title']);?>" required>
							</div>
							
							<div class="form-group">
								<label><?= Lang::get('blog_content', 'admin') ?>:</label>
								  <div class="btn-toolbar mb-2" role="toolbar">
									<div class="btn-group me-2" role="group">
									  <button type="button" class="btn btn-sm btn-secondary" onclick="insertHideTag()">
										<i class="bi bi-eye-slash"></i> <?= Lang::get('insert_hide', 'admin') ?>
									  </button>
									</div>
								  </div>
								<textarea id="content" name="content" class="form-control" required><?=htmlspecialchars($editBlog['content']);?></textarea>
							</div>
<script>
function insertHideTag() {
  const textarea = document.getElementById('content');
  const startPos = textarea.selectionStart;
  const endPos = textarea.selectionEnd;
  const selectedText = textarea.value.substring(startPos, endPos);
  
  // Если текст выделен - оборачиваем его в [hide], если нет - вставляем шаблон
  const insertText = selectedText 
    ? `[hide]${selectedText}[/hide]` 
    : '[hide]Ваш скрытый текст здесь[/hide]';
  
  textarea.value = textarea.value.substring(0, startPos) + 
                   insertText + 
                   textarea.value.substring(endPos);
  
  // Устанавливаем курсор после вставки
  const newCursorPos = startPos + insertText.length;
  textarea.setSelectionRange(newCursorPos, newCursorPos);
  textarea.focus();
}
</script>
							<div class="form-group">
								<label><?= Lang::get('blog_tags', 'admin') ?>:</label>
								<div class="tags-container">
									<?php foreach ($allTags as $tag): ?>
										<div class="tag-checkbox">
											<input type="checkbox" name="tags[]" value="<?=$tag['id'];?>" 
												   id="tag_<?=$tag['id'];?>"
												   <?=in_array($tag['id'], $editBlog['tag_ids']) ? 'checked' : '';?>>
											<label for="tag_<?=$tag['id'];?>"><?=htmlspecialchars($tag['name']);?></label>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
							
							<div class="form-actions">
								<button type="submit" class="btn btn-success"><?= Lang::get('save_changes', 'admin') ?></button>
								<a href="?section=blogs" class="btn btn-secondary"><?= Lang::get('cancel', 'admin') ?></a>
							</div>
						</form>
					</div>
            <? elseif ($section == 'blogs'):?>
                <!-- Раздел управления записями блога -->
                <h2><?= Lang::get('control', 'admin') ?> <?= Lang::get('records', 'admin') ?>. <?= Lang::get('news_count', 'admin') ?>: <?= $newsCount ?></h2> 
                <button onclick="document.getElementById('add-blog-form').style.display='block'" class="btn btn-primary"><?= Lang::get('add_record', 'admin') ?></button>
                
                <div id="add-blog-form" style="display:none; margin: 20px 0; padding: 20px; background: white; border-radius: 5px;">
                    <h3><?= Lang::get('add_record', 'admin') ?></h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
                        <input type="hidden" name="action" value="add_blog">
                        
                        <div class="form-group">
                            <label><?= Lang::get('blog_title', 'admin') ?>:</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label><?= Lang::get('blog_content', 'admin') ?>:</label>
							<div class="btn-toolbar mb-2" role="toolbar">
								<div class="btn-group me-2" role="group">
								  <button type="button" class="btn btn-sm btn-secondary" onclick="insertHideTag()">
									<i class="bi bi-eye-slash"></i> <?= Lang::get('insert_hide', 'admin') ?>
								  </button>
								</div>
							  </div>
                            <textarea id="content" name="content" class="form-control" required></textarea>
                        </div>
<script>
function insertHideTag() {
  const textarea = document.getElementById('content');
  const startPos = textarea.selectionStart;
  const endPos = textarea.selectionEnd;
  const selectedText = textarea.value.substring(startPos, endPos);
  
  // Если текст выделен - оборачиваем его в [hide], если нет - вставляем шаблон
  const insertText = selectedText 
    ? `[hide]${selectedText}[/hide]` 
    : '[hide]Ваш скрытый текст здесь[/hide]';
  
  textarea.value = textarea.value.substring(0, startPos) + 
                   insertText + 
                   textarea.value.substring(endPos);
  
  // Устанавливаем курсор после вставки
  const newCursorPos = startPos + insertText.length;
  textarea.setSelectionRange(newCursorPos, newCursorPos);
  textarea.focus();
}
</script>
                        <div class="form-group">
                            <label><?= Lang::get('blog_tags', 'admin') ?>:</label>
                            <?foreach ($allTags as $tag):?>
                                <div>
                                    <input type="checkbox" name="tags[]" value="<?echo $tag['id'];?>" id="tag_<?echo $tag['id'];?>_new">
                                    <label for="tag_<?echo $tag['id'];?>_new" class="checkbox-label"><?echo $tag['name'];?></label>
                                </div>
                            <?endforeach;?>
                        </div>
                        
                        <button type="submit" class="btn btn-success"><?= Lang::get('save_changes', 'admin') ?></button>
                        <button type="button" onclick="document.getElementById('add-blog-form').style.display='none'" class="btn btn-danger"><?= Lang::get('cancel', 'admin') ?></button>
                    </form>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?= Lang::get('blog_title', 'admin') ?></th>
                            <th><?= Lang::get('created_at', 'admin') ?></th>
							<th><?= Lang::get('rating', 'admin') ?> (+/-)</th>
                            <th><?= Lang::get('blog_tags', 'admin') ?></th>
                            <th><?= Lang::get('actions', 'admin') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?foreach ($blogs as $blog):?>
                        <tr>
                            <td><?= $blog['id'];?></td>
                            <td><?= $blog['title'];?></td>
                            <td><?= $blog['created_at'];?></td>
							<td><?=$blog['likes'];?>/<?=$blog['dislikes'];?></td>
                            <td>
                                <?foreach ($blog['tags'] as $tag):?>
                                    <span style="background: #eee; padding: 3px 6px; border-radius: 3px; margin-right: 5px;"><?= $tag;?></span>
                                <?endforeach;?>
                            </td>
                            <td>
                                <a href="?section=blogs&action=edit&id=<?=$blog['id'];?>" class="btn btn-primary"><?= Lang::get('edit', 'admin') ?></a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
                                    <input type="hidden" name="action" value="delete_blog">
                                    <input type="hidden" name="id" value="<?=$blog['id'];?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('<?= Lang::get('delete_confirm', 'admin') ?>')"><?= Lang::get('delete', 'admin') ?></button>
                                </form>
                            </td>
                        </tr>
                        <?endforeach;?>
                    </tbody>
                </table>
				<?if ($totalPages > 1):?>
					<div class="admin-pagination">
						<?if ($currentPage > 1):?>
							<a class="btn btn-primary" href="?section=blogs&page=<?=$currentPage-1;?>">← <?= Lang::get('prev', 'admin') ?></a>
						<?endif;?>
						
						<?= Lang::get('page', 'admin') ?> <?=$currentPage;?> <?= Lang::get('from', 'admin') ?> <?=$totalPages;?>
						
						<?if ($currentPage < $totalPages):?>
							<a class="btn btn-primary" href="?section=blogs&page=<?=$currentPage+1;?>"><?= Lang::get('next', 'admin') ?> →</a>
						<?endif;?>
					</div>
				<?endif;?>
            <?elseif ($section == 'contacts'):?>
                <!-- Раздел обратной связи -->
                <h2><?= Lang::get('feedback', 'admin') ?></h2>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?= Lang::get('name', 'admin') ?></th>
                            <th><?= Lang::get('email', 'admin') ?></th>
                            <th><?= Lang::get('message', 'admin') ?></th>
                            <th><?= Lang::get('created_at', 'admin') ?></th>
                            <th><?= Lang::get('actions', 'admin') ?></th>
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
                                <button onclick="alert('<?=$contact['message'];?>')" class="btn btn-primary"><?= Lang::get('view', 'admin') ?></button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
                                    <input type="hidden" name="action" value="delete_contact">
                                    <input type="hidden" name="id" value="<?=$contact['id'];?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('<?= Lang::get('delete_confirm', 'admin') ?>')"><?= Lang::get('delete', 'admin') ?></button>
                                </form>
                            </td>
                        </tr>
                        <?endforeach;?>
                    </tbody>
                </table>
            <?elseif ($section == 'users'):?>
                <!-- Раздел пользователей -->
                <h2><?= Lang::get('control', 'admin') ?> <?= Lang::get('user', 'admin') ?></h2>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?= Lang::get('name', 'admin') ?></th>
                            <th><?= Lang::get('email', 'admin') ?></th>
                            <th><?= Lang::get('role', 'admin') ?></th>
                            <th><?= Lang::get('reg_date', 'admin') ?></th>
                            <th><?= Lang::get('actions', 'admin') ?></th>
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
    <i class="fas fa-edit"></i> <?= Lang::get('edit', 'admin') ?>
</button>
                                <?if ($user['id'] != $_SESSION['user_id']):?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="id" value="<?=$user['id'];?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('<?= Lang::get('delete_confirm', 'admin') ?>')"><?= Lang::get('delete', 'admin') ?></button>
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
    
    <h4 style="margin-top:0; margin-bottom:20px;"><?= Lang::get('edit_user', 'admin') ?></h4>
    
    <form method="POST" id="edit-user-form-content">
        <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
        <input type="hidden" name="action" value="edit_user">
        <input type="hidden" name="id" id="edit-user-id">
        
        <div style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px; font-weight:500;"><?= Lang::get('name', 'admin') ?>:</label>
            <input type="text" name="username" id="edit-username" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" required>
        </div>
        
        <div style="margin-bottom:15px;">
            <label style="display:block; margin-bottom:5px; font-weight:500;"><?= Lang::get('email', 'admin') ?>:</label>
            <input type="email" name="email" id="edit-email" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" required>
        </div>
        
        <div style="margin-bottom:20px;">
            <label style="display:block; margin-bottom:8px; font-weight:500;"><?= Lang::get('role', 'admin') ?>:</label>
            <div style="display:flex; gap:10px;">
                <label style="flex:1; text-align:center;">
                    <input type="radio" name="isadmin" value="0" style="margin-right:5px;"> <?= Lang::get('ruser', 'admin') ?>
                </label>
                <label style="flex:1; text-align:center;">
                    <input type="radio" name="isadmin" value="7" style="margin-right:5px;"> <?= Lang::get('moder', 'admin') ?>
                </label>
                <label style="flex:1; text-align:center;">
                    <input type="radio" name="isadmin" value="9" style="margin-right:5px;"> <?= Lang::get('admin', 'admin') ?>
                </label>
            </div>
        </div>
        
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="closeEditForm()" style="padding:8px 15px; background:#dc3545; color:white; border:none; border-radius:4px; cursor:pointer;"><?= Lang::get('cancel', 'admin') ?></button>
            <button type="submit" style="padding:8px 15px; background:#28a745; color:white; border:none; border-radius:4px; cursor:pointer;"><?= Lang::get('save_changes', 'admin') ?></button>
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
                <h2><?= Lang::get('control', 'admin') ?> <?= Lang::get('template', 'admin') ?></h2>
				
				<div class="current-template">
					<h3><?= Lang::get('current_template', 'admin') ?>: <?= htmlspecialchars($currentTemplate) ?></h3>
				</div>

				<div class="templates-grid">
					<?php foreach ($templates as $templateName): ?>
					<div class="template-card <?= $templateName === $currentTemplate ? 'active' : '' ?>">
						<h4><?= htmlspecialchars(ucfirst($templateName)) ?></h4>
						<?php if ($templateName === $currentTemplate): ?>
							<span class="badge"><?= Lang::get('active_template', 'admin') ?></span>
						<?php else: ?>
							<form method="post">
								<input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
								<input type="hidden" name="action" value="change_template">
								<input type="hidden" name="template" value="<?= htmlspecialchars($templateName) ?>">
								<button type="submit" class="btn"><?= Lang::get('activate_template', 'admin') ?></button>
							</form>
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>

            <?elseif ($section == 'tags'):?>
                <!-- Раздел тегов -->
                <h2><?= Lang::get('control', 'admin') ?> <?= Lang::get('tag', 'admin') ?></h2>
                
                <div style="margin-bottom: 20px;">
                    <button onclick="document.getElementById('add-tag-form').style.display='block'" class="btn btn-primary"><?= Lang::get('add_tag', 'admin') ?></button>
                    
                    <div id="add-tag-form" style="display:none; margin-top: 20px; padding: 20px; background: white; border-radius: 5px;">
                        <h3><?= Lang::get('add_tag', 'admin') ?></h3>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
                            <input type="hidden" name="action" value="add_tag">
                            
                            <div class="form-group">
                                <label><?= Lang::get('name_tag', 'admin') ?>:</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-success"><?= Lang::get('save_changes', 'admin') ?></button>
                            <button type="button" onclick="document.getElementById('add-tag-form').style.display='none'" class="btn btn-danger"><?= Lang::get('cancel', 'admin') ?></button>
                        </form>
                    </div>
                </div>
                
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?= Lang::get('name_tag', 'admin') ?></th>
                            <th><?= Lang::get('actions', 'admin') ?></th>
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
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('<?= Lang::get('delete_confirm', 'admin') ?>')"><?= Lang::get('delete', 'admin') ?></button>
                                </form>
                            </td>
                        </tr>
                        <?endforeach;?>
                    </tbody>
                </table>
            <?elseif ($section == 'comments'):?>
				<!-- Раздел комментариев -->
				<h2><?= Lang::get('control', 'admin') ?> <?= Lang::get('comm', 'admin') ?> <span class="badge"><?=$pendingCount;?> <?= Lang::get('on_moderate', 'admin') ?></span></h2>
				
				<table class="admin-table">
					<thead>
						<tr>
							<th>ID</th>
							<th><?= Lang::get('blog_title', 'admin') ?></th>
							<th><?= Lang::get('ruser', 'admin') ?></th>
							<th><?= Lang::get('blog_content', 'admin') ?></th>
							<th><?= Lang::get('created_at', 'admin') ?></th>
							<th><?= Lang::get('rating', 'admin') ?> (+/-)</th>
							<th><?= Lang::get('status', 'admin') ?></th>
							<th><?= Lang::get('actions', 'admin') ?></th>
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
									<span class="badge" style="background: #28a745;"><?= Lang::get('approved', 'admin') ?></span>
								<?else:?>
									<span class="badge" style="background: #ffc107;"><?= Lang::get('on_moderate', 'admin') ?></span>
								<?endif;?>
							</td>
							<td>
								<button onclick="showCommentEditForm(<?=$comment['id'];?>, '<?=htmlspecialchars($comment['user_text'], ENT_QUOTES);?>', <?=$comment['moderation'];?>)" class="btn btn-primary"><?= Lang::get('edit', 'admin') ?></button>
								<form method="POST" style="display:inline;">
									<input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
									<input type="hidden" name="action" value="toggle_comment">
									<input type="hidden" name="id" value="<?=$comment['id'];?>">
									<button type="submit" class="btn <?=$comment['moderation'] ? 'btn-warning' : 'btn-success';?>">
										<?=$comment['moderation'] ? Lang::get('hide', 'admin') : Lang::get('approve', 'admin');?>
									</button>
								</form>
								<form method="POST" style="display:inline;">
									<input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
									<input type="hidden" name="action" value="delete_comment">
									<input type="hidden" name="id" value="<?=$comment['id'];?>">
									<button type="submit" class="btn btn-danger" onclick="return confirm('<?= Lang::get('delete_confirm', 'admin') ?>')"><?= Lang::get('delete', 'admin') ?></button>
								</form>
							</td>
						</tr>
						<?endforeach;?>
					</tbody>
				</table>
<?if ($totalPages > 1):?>
    <div class="admin-pagination">
        <?if ($currentPage > 1):?>
            <a href="?section=comments&page=<?=$currentPage-1;?>">← <?= Lang::get('prev', 'admin') ?></a>
        <?endif;?>
        
        <?= Lang::get('page', 'admin') ?> <?=$currentPage;?> <?= Lang::get('from', 'admin') ?> <?=$totalPages;?>
        
        <?if ($currentPage < $totalPages):?>
            <a href="?section=comments&page=<?=$currentPage+1;?>"><?= Lang::get('next', 'admin') ?> →</a>
        <?endif;?>
    </div>
<?endif;?>
				<!-- Форма редактирования комментария -->
				<div id="edit-comment-form" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 5px; box-shadow: 0 0 20px rgba(0,0,0,0.2); z-index: 1000; width: 80%; max-width: 800px;">
					<h3><?= Lang::get('edit_comm', 'admin') ?></h3>
					<form method="POST" id="edit-comment-form-content">
						<input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
						<input type="hidden" name="action" value="edit_comment">
						<input type="hidden" name="id" id="edit-comment-id">
						
						<div class="form-group">
							<label><?= Lang::get('text_comm', 'admin') ?>:</label>
							<textarea name="user_text" id="edit-comment-text" class="form-control" rows="8" required></textarea>
						</div>
						
						<div class="form-group">
							<input type="checkbox" name="moderation" id="edit-comment-moderation">
							<label for="edit-comment-moderation" class="checkbox-label"><?= Lang::get('approved', 'admin') ?></label>
						</div>
						
						<button type="submit" class="btn btn-success"><?= Lang::get('save_changes', 'admin') ?></button>
						<button type="button" onclick="document.getElementById('edit-comment-form').style.display='none'" class="btn btn-danger"><?= Lang::get('cancel', 'admin') ?></button>
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
				<h2><?= Lang::get('admlogs', 'admin') ?></h2><span class="badge bg-primary"><?= Lang::get('total_records', 'admin') ?>: <?= $totalLogs ?></span>
					<table class="admin-table">
                    <thead>
                        <tr>
                            <th></th>
							<th><?= Lang::get('ruser', 'admin') ?></th>
                            <th><?= Lang::get('actions', 'admin') ?></th>
                            <th><?= Lang::get('info', 'admin') ?></th>
							<th><?= Lang::get('actions', 'admin') ?></th>
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
															<?= htmlspecialchars($log['username'] ?? Lang::get('system', 'admin')) ?>
														<?php else: ?>
															<?= Lang::get('system', 'admin') ?>
														<?php endif; ?>
													</strong>
													<small class="text-muted" title="<?= date('d.m.Y H:i:s', strtotime($log['created_at'])) ?>">
														<?= $parse->time_elapsed_string($log['created_at']) ?>
													</small>
										</td>
										<td>
												<div class="text-break">
													<span class="badge bg-<?= $parse->getLogBadgeColor($log['action']) ?>">
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
												<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('<?= Lang::get('delete_confirm', 'admin') ?>')">
													<i class="bi bi-trash"></i> <?= Lang::get('delete', 'admin') ?>
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
				<?php elseif ($section == 'server_info'): ?>
					<div class="card">
						<div class="card-header">
							<h3 class="card-title"><?= Lang::get('server_info', 'admin') ?></h3>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-6">
									<div class="card">
										<div class="card-header bg-primary">
											<h4 class="card-title"><?= Lang::get('server_info', 'admin') ?></h4>
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
											<h4 class="card-title"><?= Lang::get('file_perm', 'admin') ?></h4>
										</div>
										<div class="card-body p-0">
											<table class="admin-table">
												<tbody>
													<?php foreach ($filePermissions as $file => $perms): ?>
													<tr>
														<td width="40%"><strong><?= $file ?></strong></td>
														<td>
														<?php if ($file == 'install.php' && $perms == 'Не найден'): ?>
															<span class="badge bg-success" title="<?= Lang::get('file_delete', 'admin') ?>">
																<i class="fas fa-check"></i> <?= Lang::get('fdelete', 'admin') ?>
															</span>
														<?php else: ?>
															<span class="badge bg-<?= ($perms == '0644' || $perms == '0444' || $perms == '0600') ? 'success' : 'warning' ?>">
																<?= $perms ?>
															</span>
															<?php if (($file == 'config/config.php') && $perms != '0600' && $perms != '0444'): ?>
															<small class="text-danger">(<?= Lang::get('perm', 'admin') ?> 0600)</small>
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
											<h4 class="card-title">PHP <?= Lang::get('modules', 'admin') ?></h4>
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
				<?php elseif ($section == 'backups'): ?>
					<div class="card mb-4">
						<div class="card-header">
							<h5><?= Lang::get('control', 'admin') ?> <?= Lang::get('backup', 'admin') ?></h5>
						</div>
						<div class="card-body">
							<form method="post">
								<input type="hidden" name="csrf_token" value="<?=$csrf_token?>">
								<button type="submit" name="action" value="create_backup" class="btn btn-primary">
									<?= Lang::get('create_backup', 'admin') ?>
								</button>
							</form>
							
							<h6 class="mt-4"><?= Lang::get('setting_backup', 'admin') ?></h6>
							<form method="post">
								<input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
								<div class="form-group">
									<label><?= Lang::get('max_backups', 'admin') ?></label>
									<input type="number" name="max_backups" value="<?=$max_backups;?>" min="1" class="form-control">
								</div>
								<div class="form-group">
									<label><?= Lang::get('backup_schedule', 'admin') ?></label>
									<select name="backup_schedule" class="form-control">
										<option value="disabled" <?if ($backup_schedule == 'disabled'):?>selected<?endif; ?>><?= Lang::get('disable', 'admin') ?></option>
										<option value="daily" <?if ($backup_schedule == 'daily'):?>selected<?endif; ?>><?= Lang::get('daily', 'admin') ?></option>
										<option value="weekly" <?if ($backup_schedule == 'weekly'):?>selected<?endif; ?>><?= Lang::get('weekly', 'admin') ?></option>
										<option value="monthly" <?if ($backup_schedule == 'monthly'):?>selected<?endif; ?>><?= Lang::get('monthly', 'admin') ?></option>
									</select>
								</div>
								<button type="submit" name="action" value="update_backup_settings" class="btn btn-primary">
									<?= Lang::get('save_changes', 'admin') ?>
								</button>
							</form>
							
							<h6 class="mt-4"><?= Lang::get('list_backups', 'admin') ?></h6>
							<table class="table">
								<thead>
									<tr>
										<th><?= Lang::get('file_name', 'admin') ?></th>
										<th><?= Lang::get('created_at', 'admin') ?></th>
										<th><?= Lang::get('filesize', 'admin') ?></th>
										<th><?= Lang::get('actions', 'admin') ?></th>
									</tr>
								</thead>
								<tbody>
									<?foreach ($backups as $backup):?>
										<tr>
											<td><?=basename($backup);?></td>
											<td><?=date('Y-m-d H:i:s', filemtime($backup));?></td>
											<td><?=round(filesize($backup) / 1024, 2);?> KB</td>
											<td>
												<a href="?section=backups&action=download_backup&file=<?=basename($backup);?>" class="btn btn-sm btn-success"><?= Lang::get('download_file', 'admin') ?></a>
												<form method="post" style="display:inline">
													<input type="hidden" name="csrf_token" value="<?=$csrf_token;?>">
													<input type="hidden" name="file" value="<?=basename($backup);?>">
													<button type="submit" name="action" value="restore_backup" class="btn btn-sm btn-warning"><?= Lang::get('restore_backup', 'admin') ?></button>
													<button type="submit" name="action" value="delete_backup" class="btn btn-sm btn-danger"><?= Lang::get('delete', 'admin') ?></button>
												</form>
											</td>
										</tr>
									<?endforeach;?>
								</tbody>
							</table>
						</div>
					</div>
				<?php elseif ($section == 'system_settings'): ?>
					<style>
						/* Стили для вкладок настроек */
						.settings-tabs {
							display: flex;
							flex-direction: row;
						}

						.settings-tabs .tab-btn {
							background: #f8f9fa;
							border: 1px solid #dee2e6;
							border-radius: 0.25rem;
							padding: 12px 16px;
							margin-bottom: 8px;
							text-align: left;
							color: #495057;
							cursor: pointer;
							transition: all 0.2s ease;
							font-weight: 500;
							display: flex;
							align-items: center;
						}

						.settings-tabs .tab-btn:hover {
							background: #e9ecef;
							border-color: #adb5bd;
						}

						.settings-tabs .tab-btn.active {
							background: #555555b0;
							border-color: #dee2e6;
							color: white;
						}

						.settings-tabs .tab-btn i {
							margin-right: 10px;
							width: 20px;
							text-align: center;
						}

						.tab-pane {
							display: none;
							animation: fadeIn 0.3s ease;
						}

						.tab-pane.active {
							display: block;
						}

						@keyframes fadeIn {
							from { opacity: 0; transform: translateY(10px); }
							to { opacity: 1; transform: translateY(0); }
						}

						/* Стили для карточек настроек */
						.settings-card {
							border: 1px solid #dee2e6;
							border-radius: 0.5rem;
							overflow: hidden;
							margin-bottom: 1.5rem;
						}

						.settings-card-header {
							background: #f8f9fa;
							padding: 1rem 1.5rem;
							border-bottom: 1px solid #dee2e6;
						}

						.settings-card-body {
							padding: 1.5rem;
						}

						/* Стили для цветовых индикаторов */
						.color-indicator {
							width: 24px;
							height: 24px;
							border: 1px solid #dee2e6;
							border-radius: 3px;
							margin-left: 10px;
						}

						/* Стили для переключателей */
						.form-switch .form-check-input {
							width: 3em;
							height: 1.5em;
						}

						.form-switch .form-check-input:checked {
							background-color: #0d6efd;
							border-color: #0d6efd;
						}

						/* Стили для предупреждений */
						.alert-warning {
							background-color: #fff3cd;
							border-color: #ffeaa7;
							color: #721c24;
						}
					</style>

					<div class="card">
						<div class="card-header bg-primary text-white">
							<h3><i class="fas fa-cog"></i> <?= Lang::get('system_settings', 'admin') ?></h3>
							<p class="mb-0"><?= Lang::get('system_settings_desc', 'admin') ?></p>
						</div>
						<div class="card-body">
							<form method="POST" id="systemSettingsForm">
								<input type="hidden" name="csrf_token" value="<?=$csrf_token?>">
								<input type="hidden" name="action" value="edit_settings">
								
								<!-- Навигация по настройкам -->
								<div class="row mb-4">
									<div class="col-md-3">
										<div class="settings-tabs">
											<button class="tab-btn active" type="button" onclick="showTab('general')">
												<i class="fas fa-home"></i> <?= Lang::get('general_settings', 'admin') ?>
											</button>
											<button class="tab-btn" type="button" onclick="showTab('seo')">
												<i class="fas fa-search"></i> <?= Lang::get('seo_settings', 'admin') ?>
											</button>
											<button class="tab-btn" type="button" onclick="showTab('email')">
												<i class="fas fa-envelope"></i> <?= Lang::get('email_settings', 'admin') ?>
											</button>
											<button class="tab-btn" type="button" onclick="showTab('performance')">
												<i class="fas fa-tachometer-alt"></i> <?= Lang::get('performance_settings', 'admin') ?>
											</button>
											<button class="tab-btn" type="button" onclick="showTab('security')">
												<i class="fas fa-shield-alt"></i> <?= Lang::get('security_settings', 'admin') ?>
											</button>
											<button class="tab-btn" type="button" onclick="showTab('captcha')">
												<i class="fas fa-robot"></i> <?= Lang::get('captcha_settings', 'admin') ?>
											</button>
										</div>
									</div>
									
									<div class="col-md-9">
										<div class="tab-content">
											<!-- Общие настройки -->
											<div class="tab-pane active" id="generalContent">
												<div class="settings-card">
													<div class="settings-card-header">
														<h4 class="mb-0"><i class="fas fa-home"></i> <?= Lang::get('general_settings', 'admin') ?></h4>
													</div>
													<div class="settings-card-body">
														<div class="form-group mb-3">
															<label class="form-label"><?= Lang::get('home_title', 'admin') ?>:</label>
															<input type="text" name="home_title" class="form-control" 
																   value="<?=htmlspecialchars($currentSettings['home_title'] ?? '')?>" 
																   placeholder="<?= Lang::get('home_title_placeholder', 'admin') ?>">
															<small class="form-text text-muted"><?= Lang::get('home_title_desc', 'admin') ?></small>
														</div>
														
														<div class="row">
															<div class="col-md-6">
																<div class="form-group mb-3">
																	<label class="form-label"><?= Lang::get('blogs_per_page', 'admin') ?>:</label>
																	<input type="number" name="blogs_per_page" class="form-control" 
																		   value="<?=htmlspecialchars($currentSettings['blogs_per_page'] ?? 10)?>" 
																		   min="5" max="100">
																	<small class="form-text text-muted"><?= Lang::get('blogs_per_page_desc', 'admin') ?></small>
																</div>
															</div>
															<div class="col-md-6">
																<div class="form-group mb-3">
																	<label class="form-label"><?= Lang::get('comments_per_page', 'admin') ?>:</label>
																	<input type="number" name="comments_per_page" class="form-control" 
																		   value="<?=htmlspecialchars($currentSettings['comments_per_page'] ?? 20)?>" 
																		   min="5" max="100">
																	<small class="form-text text-muted"><?= Lang::get('comments_per_page_desc', 'admin') ?></small>
																</div>
															</div>
														</div>
														
														<div class="form-check form-switch mb-3">
															<input type="checkbox" name="blocks_for_reg" id="blocks_for_reg" 
																   class="form-check-input" <?=($currentSettings['blocks_for_reg'] ?? false) ? 'checked' : ''?>>
															<label for="blocks_for_reg" class="form-check-label">
																<?= Lang::get('blocks_for_reg', 'admin') ?>
															</label>
															<small class="form-text text-muted d-block"><?= Lang::get('blocks_for_reg_desc', 'admin') ?></small>
														</div>
														
														<div class="form-group mb-0">
															<label class="form-label"><?= Lang::get('current_version', 'admin') ?>:</label>
															<div class="alert alert-info p-2 mb-0">
																<strong><?=htmlspecialchars($currentSettings['powered'] ?? '')?>_<?=htmlspecialchars($currentSettings['version'] ?? '')?></strong>
															</div>
														</div>
													</div>
												</div>
											</div>
											
											<!-- SEO настройки -->
											<div class="tab-pane" id="seoContent">
												<div class="settings-card">
													<div class="settings-card-header">
														<h4 class="mb-0"><i class="fas fa-search"></i> <?= Lang::get('seo_settings', 'admin') ?></h4>
													</div>
													<div class="settings-card-body">
														<div class="form-group mb-3">
															<label class="form-label"><?= Lang::get('meta_keywords', 'admin') ?>:</label>
															<input type="text" name="metaKeywords" class="form-control" 
																   value="<?=htmlspecialchars($currentSettings['metaKeywords'] ?? '')?>" 
																   placeholder="<?= Lang::get('meta_keywords_placeholder', 'admin') ?>">
															<small class="form-text text-muted"><?= Lang::get('meta_keywords_desc', 'admin') ?></small>
														</div>
														
														<div class="form-group mb-0">
															<label class="form-label"><?= Lang::get('meta_description', 'admin') ?>:</label>
															<textarea name="metaDescription" class="form-control" rows="4"
																	  placeholder="<?= Lang::get('meta_description_placeholder', 'admin') ?>"><?=htmlspecialchars($currentSettings['metaDescription'] ?? '')?></textarea>
															<small class="form-text text-muted"><?= Lang::get('meta_description_desc', 'admin') ?></small>
														</div>
													</div>
												</div>
											</div>
											
											<!-- Email настройки -->
											<div class="tab-pane" id="emailContent">
												<div class="settings-card">
													<div class="settings-card-header">
														<h4 class="mb-0"><i class="fas fa-envelope"></i> <?= Lang::get('email_settings', 'admin') ?></h4>
													</div>
													<div class="settings-card-body">
														<div class="form-group mb-3">
															<label class="form-label"><?= Lang::get('mail_from', 'admin') ?>:</label>
															<input type="email" name="mail_from" class="form-control" 
																   value="<?=htmlspecialchars($currentSettings['mail_from'] ?? '')?>" 
																   placeholder="<?= Lang::get('mail_from_placeholder', 'admin') ?>">
															<small class="form-text text-muted"><?= Lang::get('mail_from_desc', 'admin') ?></small>
														</div>
														
														<div class="form-group mb-0">
															<label class="form-label"><?= Lang::get('mail_from_name', 'admin') ?>:</label>
															<input type="text" name="mail_from_name" class="form-control" 
																   value="<?=htmlspecialchars($currentSettings['mail_from_name'] ?? '')?>" 
																   placeholder="<?= Lang::get('mail_from_name_placeholder', 'admin') ?>">
															<small class="form-text text-muted"><?= Lang::get('mail_from_name_desc', 'admin') ?></small>
														</div>
													</div>
												</div>
											</div>
											
											<!-- Настройки производительности -->
											<div class="tab-pane" id="performanceContent">
												<div class="settings-card">
													<div class="settings-card-header">
														<h4 class="mb-0"><i class="fas fa-tachometer-alt"></i> <?= Lang::get('performance_settings', 'admin') ?></h4>
													</div>
													<div class="settings-card-body">
														<div class="form-check form-switch mb-3">
															<input type="checkbox" name="cache_enabled" id="cache_enabled" 
																   class="form-check-input" <?=($currentSettings['cache_enabled'] ?? false) ? 'checked' : ''?>>
															<label for="cache_enabled" class="form-check-label">
																<?= Lang::get('cache_enabled', 'admin') ?>
															</label>
															<small class="form-text text-muted d-block"><?= Lang::get('cache_enabled_desc', 'admin') ?></small>
														</div>
														
														<div class="form-group mb-3">
															<label class="form-label"><?= Lang::get('cache_driver', 'admin') ?>:</label>
															<select name="cache_driver" id="cache_driver_select" class="form-control">
																<option value="file" <?=($currentSettings['cache_driver'] ?? 'file') == 'file' ? 'selected' : ''?>>
																	<?= Lang::get('cache_driver_file', 'admin') ?>
																</option>
																<option value="redis" <?=($currentSettings['cache_driver'] ?? '') == 'redis' ? 'selected' : ''?>>
																	<?= Lang::get('cache_driver_redis', 'admin') ?>
																</option>
																<option value="memcached" <?=($currentSettings['cache_driver'] ?? '') == 'memcached' ? 'selected' : ''?>>
																	<?= Lang::get('cache_driver_memcached', 'admin') ?>
																</option>
																<option value="apcu" <?=($currentSettings['cache_driver'] ?? '') == 'apcu' ? 'selected' : ''?>>
																	<?= Lang::get('cache_driver_apcu', 'admin') ?>
																</option>
															</select>
															<small class="form-text text-muted"><?= Lang::get('cache_driver_desc', 'admin') ?></small>
														</div>
														
														<div class="form-group mb-3">
															<label class="form-label"><?= Lang::get('cache_ttl', 'admin') ?>:</label>
															<input type="number" name="cache_ttl" class="form-control" 
																   value="<?=htmlspecialchars($currentSettings['cache_ttl'] ?? 3600)?>" 
																   min="60" max="86400">
															<small class="form-text text-muted"><?= Lang::get('cache_ttl_desc', 'admin') ?></small>
														</div>
														
														<div class="form-group mb-3">
															<label class="form-label"><?= Lang::get('cache_key_salt', 'admin') ?>:</label>
															<input type="text" name="cache_key_salt" class="form-control" 
																   value="<?=htmlspecialchars($currentSettings['cache_key_salt'] ?? 'your_unique_salt_here')?>">
															<small class="form-text text-muted"><?= Lang::get('cache_key_salt_desc', 'admin') ?></small>
														</div>
														
														<div id="redis_settings" class="driver-settings" style="display: <?=($currentSettings['cache_driver'] ?? '') == 'redis' ? 'block' : 'none' ?>;">
															<h5 class="mt-3 border-bottom pb-2"><?= Lang::get('redis_settings', 'admin') ?></h5>
															<div class="row">
																<div class="col-md-6">
																	<div class="form-group mb-3">
																		<label class="form-label"><?= Lang::get('redis_host', 'admin') ?>:</label>
																		<input type="text" name="redis_host" class="form-control" 
																			   value="<?=htmlspecialchars($currentSettings['redis_host'] ?? '127.0.0.1')?>">
																		<small class="form-text text-muted"><?= Lang::get('redis_host_desc', 'admin') ?></small>
																	</div>
																</div>
																<div class="col-md-6">
																	<div class="form-group mb-3">
																		<label class="form-label"><?= Lang::get('redis_port', 'admin') ?>:</label>
																		<input type="number" name="redis_port" class="form-control" 
																			   value="<?=htmlspecialchars($currentSettings['redis_port'] ?? 6379)?>">
																		<small class="form-text text-muted"><?= Lang::get('redis_port_desc', 'admin') ?></small>
																	</div>
																</div>
															</div>
															<div class="form-group mb-0">
																<label class="form-label"><?= Lang::get('redis_password', 'admin') ?>:</label>
																<input type="password" name="redis_password" class="form-control" 
																	   value="<?=htmlspecialchars($currentSettings['redis_password'] ?? '')?>">
																<small class="form-text text-muted"><?= Lang::get('redis_password_desc', 'admin') ?></small>
															</div>
														</div>
														
														<div id="memcached_settings" class="driver-settings" style="display: <?=($currentSettings['cache_driver'] ?? '') == 'memcached' ? 'block' : 'none' ?>;">
															<h5 class="mt-3 border-bottom pb-2"><?= Lang::get('memcached_settings', 'admin') ?></h5>
															<div class="row">
																<div class="col-md-6">
																	<div class="form-group mb-3">
																		<label class="form-label"><?= Lang::get('memcached_host', 'admin') ?>:</label>
																		<input type="text" name="memcached_host" class="form-control" 
																			   value="<?=htmlspecialchars($currentSettings['memcached_host'] ?? '127.0.0.1')?>">
																		<small class="form-text text-muted"><?= Lang::get('memcached_host_desc', 'admin') ?></small>
																	</div>
																</div>
																<div class="col-md-6">
																	<div class="form-group mb-3">
																		<label class="form-label"><?= Lang::get('memcached_port', 'admin') ?>:</label>
																		<input type="number" name="memcached_port" class="form-control" 
																			   value="<?=htmlspecialchars($currentSettings['memcached_port'] ?? 11211)?>">
																		<small class="form-text text-muted"><?= Lang::get('memcached_port_desc', 'admin') ?></small>
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
											
											<!-- Настройки безопасности -->
											<div class="tab-pane" id="securityContent">
												<div class="settings-card">
													<div class="settings-card-header">
														<h4 class="mb-0"><i class="fas fa-shield-alt"></i> <?= Lang::get('security_settings', 'admin') ?></h4>
													</div>
													<div class="settings-card-body">
														<div class="alert alert-warning">
															<i class="fas fa-exclamation-triangle"></i>
															<strong><?= Lang::get('security_warning', 'admin') ?>:</strong>
															<?= Lang::get('security_warning_desc', 'admin') ?>
														</div>
														
														<div class="form-group mb-3">
															<label class="form-label"><?= Lang::get('session_lifetime', 'admin') ?>:</label>
															<input type="number" name="session_lifetime" class="form-control" 
																   value="<?=htmlspecialchars($currentSettings['session_lifetime'] ?? 7200)?>" 
																   min="300" max="86400">
															<small class="form-text text-muted"><?= Lang::get('session_lifetime_desc', 'admin') ?></small>
														</div>
														
														<div class="form-check form-switch mb-3">
															<input type="checkbox" name="force_https" id="force_https" 
																   class="form-check-input" <?=($currentSettings['force_https'] ?? false) ? 'checked' : ''?>>
															<label for="force_https" class="form-check-label">
																<?= Lang::get('force_https', 'admin') ?>
															</label>
															<small class="form-text text-muted d-block"><?= Lang::get('force_https_desc', 'admin') ?></small>
														</div>
														
														<!--<div class="form-check form-switch mb-3">
															<input type="checkbox" name="disable_update_check" id="disable_update_check" 
																   class="form-check-input" <?=($currentSettings['disable_update_check'] ?? false) ? 'checked' : ''?>>
															<label for="disable_update_check" class="form-check-label">
																<?= Lang::get('disable_update_check', 'admin') ?>
															</label>
															<small class="form-text text-muted d-block"><?= Lang::get('disable_update_check_desc', 'admin') ?></small>
														</div>
														
														<div class="form-group mb-0">
															<label class="form-label"><?= Lang::get('update_check_interval', 'admin') ?>:</label>
															<input type="number" name="update_check_interval" class="form-control" 
																   value="<?=htmlspecialchars($currentSettings['update_check_interval'] ?? 24)?>" 
																   min="1" max="168">
															<small class="form-text text-muted"><?= Lang::get('update_check_interval_desc', 'admin') ?></small>
														</div> -->
													</div>
												</div>
											</div>
											
											<!-- Настройки капчи -->
											<div class="tab-pane" id="captchaContent">
												<div class="settings-card">
													<div class="settings-card-header">
														<h4 class="mb-0"><i class="fas fa-robot"></i> <?= Lang::get('captcha_settings', 'admin') ?></h4>
													</div>
													<div class="settings-card-body">
														<div class="row">
															<div class="col-md-6">
																<div class="form-group mb-3">
																	<label class="form-label"><?= Lang::get('captcha_bg_color', 'admin') ?>:</label>
																	<div class="input-group">
																		<input type="text" name="bg_color" class="form-control" 
																			   value="<?= htmlspecialchars($currentSettings['captcha_bg_color'] ?? '10,10,26') ?>">
																		<span class="input-group-text">
																			<div class="color-indicator" style="background-color: rgb(<?= $currentSettings['captcha_bg_color'] ?? '10,10,26' ?>);"></div>
																		</span>
																	</div>
																	<small class="form-text text-muted"><?= Lang::get('captcha_bg_color_desc', 'admin') ?></small>
																</div>
																
																<div class="form-group mb-3">
																	<label class="form-label"><?= Lang::get('captcha_text_color', 'admin') ?>:</label>
																	<div class="input-group">
																		<input type="text" name="text_color" class="form-control" 
																			   value="<?= htmlspecialchars($currentSettings['captcha_text_color'] ?? '11,227,255') ?>">
																		<span class="input-group-text">
																			<div class="color-indicator" style="background-color: rgb(<?= $currentSettings['captcha_text_color'] ?? '11,227,255' ?>);"></div>
																		</span>
																	</div>
																	<small class="form-text text-muted"><?= Lang::get('captcha_text_color_desc', 'admin') ?></small>
																</div>
															</div>
															<div class="col-md-6">
																<div class="form-group mb-3">
																	<label class="form-label"><?= Lang::get('captcha_accent_color', 'admin') ?>:</label>
																	<div class="input-group">
																		<input type="text" name="accent_color" class="form-control" 
																			   value="<?= htmlspecialchars($currentSettings['captcha_accent_color'] ?? '188,19,254') ?>">
																		<span class="input-group-text">
																			<div class="color-indicator" style="background-color: rgb(<?= $currentSettings['captcha_accent_color'] ?? '188,19,254' ?>);"></div>
																		</span>
																	</div>
																	<small class="form-text text-muted"><?= Lang::get('captcha_accent_color_desc', 'admin') ?></small>
																</div>
																
																<div class="form-group mb-3">
																	<label class="form-label"><?= Lang::get('captcha_noise_color', 'admin') ?>:</label>
																	<div class="input-group">
																		<input type="text" name="noise_color" class="form-control" 
																			   value="<?= htmlspecialchars($currentSettings['captcha_noise_color'] ?? '50,50,80') ?>">
																		<span class="input-group-text">
																			<div class="color-indicator" style="background-color: rgb(<?= $currentSettings['captcha_noise_color'] ?? '50,50,80' ?>);"></div>
																		</span>
																	</div>
																	<small class="form-text text-muted"><?= Lang::get('captcha_noise_color_desc', 'admin') ?></small>
																</div>
															</div>
														</div>
														
														<div class="text-center mt-4">
															<button type="button" class="btn btn-primary" onclick="updateCaptchaPreview()">
																<i class="fas fa-sync-alt"></i> <?= Lang::get('update_preview', 'admin') ?>
															</button>
														</div>
														
														<div class="mt-4">
															<h5><?= Lang::get('captcha_preview', 'admin') ?>:</h5>
															<div class="card">
																<div class="card-body text-center">
																	<img id="captcha-preview" src="/class/captcha.php?preview=1&bg_color=<?= urlencode($currentSettings['captcha_bg_color'] ?? '10,10,26') ?>&text_color=<?= urlencode($currentSettings['captcha_text_color'] ?? '11,227,255') ?>" 
																		 class="img-fluid" style="max-width: 300px;">
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								
								<!-- Кнопки сохранения -->
								<div class="card mt-4">
									<div class="card-body text-center">
										<button type="submit" class="btn btn-primary btn-lg px-5">
											<i class="fas fa-save"></i> <?= Lang::get('save_changes', 'admin') ?>
										</button>
										<button type="button" class="btn btn-secondary btn-lg px-5" onclick="resetForm()">
											<i class="fas fa-undo"></i> <?= Lang::get('reset_changes', 'admin') ?>
										</button>
									</div>
								</div>
							</form>
						</div>
					</div>

					<script>
					// Функция переключения вкладок
					function showTab(tabName) {
						// Скрываем все вкладки
						document.querySelectorAll('.tab-pane').forEach(pane => {
							pane.classList.remove('active');
						});
						
						// Убираем активный класс со всех кнопок
						document.querySelectorAll('.tab-btn').forEach(btn => {
							btn.classList.remove('active');
						});
						
						// Показываем выбранную вкладку
						const contentId = tabName + 'Content';
						const contentElement = document.getElementById(contentId);
						if (contentElement) {
							contentElement.classList.add('active');
						}
						
						// Делаем кнопку активной
						const activeButton = document.querySelector(`.tab-btn[onclick="showTab('${tabName}')"]`);
						if (activeButton) {
							activeButton.classList.add('active');
						}
						
						// Обновляем цветовые индикаторы для капчи
						if (tabName === 'captcha') {
							updateColorIndicators();
						}
					}

					// Функция обновления цветовых индикаторов
					function updateColorIndicators() {
						document.querySelectorAll('input[name="bg_color"], input[name="text_color"], input[name="accent_color"], input[name="noise_color"]').forEach(input => {
							const colorValue = input.value;
							const indicator = input.closest('.input-group')?.querySelector('.color-indicator');
							if (indicator && colorValue) {
								indicator.style.backgroundColor = `rgb(${colorValue})`;
							}
						});
					}

					// Инициализация настроек кэша при загрузке
					document.addEventListener('DOMContentLoaded', function() {
						const cacheDriverSelect = document.getElementById('cache_driver_select');
						if (cacheDriverSelect) {
							function updateDriverSettings() {
								const selectedDriver = cacheDriverSelect.value;
								
								// Скрываем все настройки драйверов
								document.querySelectorAll('.driver-settings').forEach(setting => {
									setting.style.display = 'none';
								});
								
								if (selectedDriver === 'redis') {
									const redisSettings = document.getElementById('redis_settings');
									if (redisSettings) redisSettings.style.display = 'block';
								} else if (selectedDriver === 'memcached') {
									const memcachedSettings = document.getElementById('memcached_settings');
									if (memcachedSettings) memcachedSettings.style.display = 'block';
								}
							}
							
							updateDriverSettings();
							cacheDriverSelect.addEventListener('change', updateDriverSettings);
						}
						
						// Обновление цветовых индикаторов при изменении значений
						document.querySelectorAll('input[name="bg_color"], input[name="text_color"], input[name="accent_color"], input[name="noise_color"]').forEach(input => {
							input.addEventListener('input', updateColorIndicators);
						});
					});

					// Функция обновления предпросмотра капчи
					function updateCaptchaPreview() {
						const bgColor = document.querySelector('input[name="bg_color"]')?.value || '10,10,26';
						const textColor = document.querySelector('input[name="text_color"]')?.value || '11,227,255';
						const accentColor = document.querySelector('input[name="accent_color"]')?.value || '188,19,254';
						const noiseColor = document.querySelector('input[name="noise_color"]')?.value || '50,50,80';
						
						const params = new URLSearchParams();
						params.append('bg_color', bgColor);
						params.append('text_color', textColor);
						params.append('accent_color', accentColor);
						params.append('noise_color', noiseColor);
						params.append('preview', '1');
						params.append('t', new Date().getTime());
						
						const previewImg = document.getElementById('captcha-preview');
						if (previewImg) {
							previewImg.src = '/class/captcha.php?' + params.toString();
						}
					}

					// Функция сброса формы
					function resetForm() {
						if (confirm('<?= Lang::get('reset_confirm', 'admin') ?>')) {
							document.getElementById('systemSettingsForm').reset();
							updateColorIndicators();
						}
					}
					</script>

				<?php elseif ($section == 'updates'): ?>
					<div class="admin-content">
						<h2><?= Lang::get('updates', 'admin') ?></h2>
						
						<?php if (!empty($admin_message)): ?>
							<div class="alert alert-success"><?php echo htmlspecialchars($admin_message); ?></div>
						<?php endif; ?>
						
						<?php if (!empty($admin_error)): ?>
							<div class="alert alert-danger"><?php echo htmlspecialchars($admin_error); ?></div>
						<?php endif; ?>
						
						<div class="card mb-4">
							<div class="card-header">
								<h3><?= Lang::get('version_info', 'admin') ?></h3>
							</div>
							<div class="card-body">
								<p><strong><?= Lang::get('current_ver', 'admin') ?></strong> <?php echo htmlspecialchars($currentVersion); ?></p>
								
								<?php if (!empty($updateInfo)): ?>
									<div class="alert <?php echo $updateInfo['is_important'] ? 'alert-danger' : 'alert-info'; ?>">
										<h4><?= Lang::get('update_avail', 'admin') ?><?php echo htmlspecialchars($updateInfo['new_version']); ?>!</h4>
										<p><strong><?= Lang::get('date_release', 'admin') ?></strong> <?php echo htmlspecialchars($updateInfo['release_date']); ?></p>
										<p><strong><?= Lang::get('important', 'admin') ?></strong> 
											<?php if ($updateInfo['is_important']): ?>
												<span class="badge bg-danger"><?= Lang::get('important_update', 'admin') ?></span>
											<?php else: ?>
												<span class="badge bg-warning"><?= Lang::get('recomend_update', 'admin') ?></span>
											<?php endif; ?>
										</p>
										
										<h5><?= Lang::get('changelog', 'admin') ?></h5>
										<div class="changelog"><code><?php echo nl2br(htmlspecialchars($updateInfo['changelog'])); ?></code></div>
										<br />
										<div class="mt-3">
											<a href="<?php echo htmlspecialchars($updateInfo['release_url']); ?>" 
											   target="_blank" 
											   class="btn btn-success">
												<?= Lang::get('link_git', 'admin') ?>
											</a>
											<a href="<?php echo htmlspecialchars($updateInfo['download_url']); ?>" 
											   target="_blank" 
											   class="btn btn-primary"> <?= Lang::get('download', 'admin') ?>
											</a>
											<?php if (!empty($updateInfo)): ?>
											<form method="POST" style="display: inline-block;">
												<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
												<input type="hidden" name="action" value="install_update">
												<button type="submit" class="btn btn-primary ml-2">
													Установить обновление
												</button>
											</form>
											<?php endif; ?>
										</div>
									</div>
								<?php else: ?>
									<div class="alert alert-success">
										<?= Lang::get('latest', 'admin') ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>				<?php endif; ?>
        </div>
    </div>
	<script>
        // Закрытие меню при клике на пункт на мобильных устройствах
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.admin-menu a');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        sidebarToggle.checked = false;
                    }
                });
            });
        });
    </script>
</body>
</html>