<?php
if (!defined('IN_SIMPLECMS')) {
    die('Прямой доступ запрещен');
}

/**
 * Класс для отправки электронной почты
 * 
 * @package    SimpleBlog
 * @subpackage Services
 * @category   Communication
 * @version    0.9.8
 *
 * @method static void   init(array $config)                              Инициализирует конфигурацию почтовой системы
 * @method static bool   send(string $to, string $subject, string $body, bool $isHtml = true) Отправляет email
 * @method static string wrapHtml(string $subject, string $content)       Форматирует контент в HTML-шаблон
 */
class Mailer
{
    /**
     * Конфигурация почтовой системы
     * 
     * @var array
     */
    private static $config;
    
    /**
     * Инициализирует конфигурацию почтовой системы
     *
     * @param array $config
     * @return void
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }
    
    /**
     * Отправляет email
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param bool $isHtml
     * @return bool
     */
    public static function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $from = self::$config['mail_from'] 
            ?? 'noreply@' . str_replace("www.", "", $_SERVER['SERVER_NAME']);
        
        $headers = [
            'From' => $from,
            'Reply-To' => $from,
            'Return-Path' => $from,
            'MIME-Version' => '1.0',
            'X-Mailer' => 'simpleBlog/' . (self::$config['version'] ?? '1.0')
        ];

        if ($isHtml) {
            $headers['Content-Type'] = 'text/html; charset=UTF-8';
            $fullBody = self::wrapHtml($subject, $body);
        } else {
            $headers['Content-Type'] = 'text/plain; charset=UTF-8';
            $fullBody = $body;
        }

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "$key: $value\r\n";
        }

        return mail($to, $subject, $fullBody, $headerString);
    }
    
    /**
     * Форматирует контент в HTML-шаблон
     *
     * @param string $subject
     * @param string $content
     * @return string
     */
    private static function wrapHtml(string $subject, string $content): string
    {
        $homeTitle = htmlspecialchars(self::$config['home_title'] ?? 'SimpleBlog');
        $poweredBy = self::$config['powered'] ?? 'Powered by SimpleBlog';
        
        return '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . htmlspecialchars($subject) . '</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    padding: 20px; 
                }
                .header { 
                    background-color: #f8f9fa; 
                    padding: 15px; 
                    text-align: center; 
                }
                .content { 
                    padding: 20px; 
                }
                .footer { 
                    margin-top: 20px; 
                    padding-top: 20px; 
                    border-top: 1px solid #eee; 
                    font-size: 12px; 
                    color: #777; 
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>' . $homeTitle . '</h2>
                </div>
                <div class="content">' . $content . '</div>
                <div class="footer">
                    <p>' . $poweredBy . '</p>
                </div>
            </div>
        </body>
        </html>';
    }
}