<?php
session_start();

// Генерация случайного математического выражения
$num1 = rand(100, 300);
$num2 = rand(10, 50);
$operator = rand(0, 1) ? '+' : '-';
$captchaResult = ($operator == '+') ? $num1 + $num2 : $num1 - $num2;
$_SESSION['captcha_answer'] = $captchaResult;

// Параметры изображения
$width = 180;
$height = 60;
$image = imagecreatetruecolor($width, $height);

// Цветовая палитра (футуристичные цвета)
$bgColor = imagecolorallocate($image, 10, 10, 26);    // Темно-синий фон
$textColor = imagecolorallocate($image, 11, 227, 255); // Неоново-синий текст
$accentColor = imagecolorallocate($image, 188, 19, 254); // Неоново-фиолетовый
$noiseColor = imagecolorallocate($image, 50, 50, 80);   // Шум

// Заполнение фона
imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

// Добавление шума (точки)
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noiseColor);
}

// Добавление линий
for ($i = 0; $i < 5; $i++) {
    imageline($image, 
        rand(0, $width), rand(0, $height),
        rand(0, $width), rand(0, $height),
        $noiseColor);
}

// Генерация текста капчи
$text = "$num1 $operator $num2 = ?";
$fontSize = 20;
$angle = rand(-5, 5); // Небольшой наклон
$x = 15;
$y = 40;

// Тень текста
imagettftext($image, $fontSize, $angle, $x+1, $y+1, $accentColor, 'arial.ttf', $text);
// Основной текст
imagettftext($image, $fontSize, $angle, $x, $y, $textColor, 'arial.ttf', $text);

// Эффект сканирующей линии
$lineY = rand(10, $height-10);
imagefilledrectangle($image, 0, $lineY, $width, $lineY+1, $accentColor);

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
?>