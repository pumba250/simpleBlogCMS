<?php

session_start();
define('IN_SIMPLECMS', true);
// Получаем настройки цветов из конфига или используем значения по умолчанию

$config = require $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';

// Парсим цвета из настроек
function parseColor($colorStr, $default)
{
    $parts = explode(',', $colorStr);
    if (count($parts) === 3) {
        return array_map('intval', $parts);
    }
    return explode(',', $default);
}

// Если это запрос предпросмотра, берем цвета из GET-параметров
if (isset($_GET['preview'])) {
    $bgColor = isset($_GET['bg_color']) ? parseColor($_GET['bg_color'], '10,10,26') : parseColor($config['captcha_bg_color'] ?? '10,10,26', '10,10,26');
    $textColor = isset($_GET['text_color']) ? parseColor($_GET['text_color'], '11,227,255') : parseColor($config['captcha_text_color'] ?? '11,227,255', '11,227,255');
    $accentColor = isset($_GET['accent_color']) ? parseColor($_GET['accent_color'], '188,19,254') : parseColor($config['captcha_accent_color'] ?? '188,19,254', '188,19,254');
    $noiseColor = isset($_GET['noise_color']) ? parseColor($_GET['noise_color'], '50,50,80') : parseColor($config['captcha_noise_color'] ?? '50,50,80', '50,50,80');
} else {
    // Иначе берем из конфига
    $bgColor = parseColor($config['captcha_bg_color'] ?? '10,10,26', '10,10,26');
    $textColor = parseColor($config['captcha_text_color'] ?? '11,227,255', '11,227,255');
    $accentColor = parseColor($config['captcha_accent_color'] ?? '188,19,254', '188,19,254');
    $noiseColor = parseColor($config['captcha_noise_color'] ?? '50,50,80', '50,50,80');
}

// Генерация случайного математического выражения
$num1 = rand(100, 300);
$num2 = rand(10, 50);
$operator = rand(0, 1) ? '+' : '-';
$captchaResult = ($operator == '+') ? $num1 + $num2 : $num1 - $num2;
$_SESSION['captcha_answer'] = $captchaResult;

// Параметры изображения
$width = 140;
$height = 40;
$image = imagecreatetruecolor($width, $height);

// Создание цветов из RGB
$bgColor = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
$textColor = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
$accentColor = imagecolorallocate($image, $accentColor[0], $accentColor[1], $accentColor[2]);
$noiseColor = imagecolorallocate($image, $noiseColor[0], $noiseColor[1], $noiseColor[2]);

// Остальной код остается без изменений...
// Заполнение фона
imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

// Добавление шума (точки)
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noiseColor);
}

// Добавление линий
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $noiseColor);
}

// Генерация текста капчи
$text = "$num1 $operator $num2 = ?";
$fontSize = 15;
$angle = rand(-5, 5); // Небольшой наклон
$x = 10;
$y = 30;

// Тень текста
imagettftext($image, $fontSize, $angle, $x + 1, $y + 1, $accentColor, 'arial.ttf', $text);
// Основной текст
imagettftext($image, $fontSize, $angle, $x, $y, $textColor, 'arial.ttf', $text);

// Эффект сканирующей линии
$lineY = rand(10, $height - 10);
imagefilledrectangle($image, 0, $lineY, $width, $lineY + 1, $accentColor);

// Добавление сетки
for ($i = 0; $i < $width; $i += 15) {
    imageline($image, $i, 0, $i, $height, $noiseColor);
}
for ($i = 0; $i < $height; $i += 15) {
    imageline($image, 0, $i, $width, $i, $noiseColor);
}

// Вывод изображения
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
