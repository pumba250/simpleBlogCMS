<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=test_db', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Запуск схемы
$schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
$pdo->exec($schema);

// Тестовые данные
$pdo->exec("INSERT INTO users (username, password, email, isadmin) VALUES 
    ('testuser', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'test@example.com', 1)");

echo "Test database setup complete!\n";