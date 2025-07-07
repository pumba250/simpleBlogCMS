<?php
if (!preg_match("/index.php/i", $_SERVER['PHP_SELF']) && !preg_match("/admin.php/i", $_SERVER['PHP_SELF'])) { 
    die("Access denied"); 
}
return array (
  'host' => 'localhost',
  'database' => 'u2339566_default',
  'db_user' => 'u2339566_default',
  'db_pass' => '7zE7CZq6tZn4RDAj',
  'templ' => 'simple',
  'db_prefix' => 'release_',
  'csrf_token_name' => 'csrf_token',
  'max_backups' => 5,
  'backup_schedule' => 'disabled',
  'backup_dir' => 'admin/backups/',
  'blocks_for_reg' => true,
  'home_title' => 'Ваш сайт',
  'metaKeywords' => 'Здесь ключевые слова, через запятую (,)',
  'metaDescription' => 'Здесь описание вашего сайта',
  'blogs_per_page' => '6',
  'comments_per_page' => '10',
  'powered' => 'simpleBlog',
  'version' => 'release',
  'mail_from' => 'no-reply@yourdomain.com',
  'mail_from_name' => 'SimpleBlog Notifications',
);
