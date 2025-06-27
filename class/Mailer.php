<?php
class Mailer {
    private static $config;
    
    public static function init($config) {
        self::$config = $config;
    }
    
    public static function send($to, $subject, $body, $isHtml = true) {
        $from = self::$config['mail_from'] ?? 'noreply@'.str_replace("www.", "", $_SERVER['SERVER_NAME']);
        
        $headers = [
            'From' => $from,
            'Reply-To' => $from,
            'Return-Path' => $from,
            'MIME-Version' => '1.0',
            'X-Mailer' => 'simpleBlog/'.(self::$config['version'] ?? '1.0')
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
    
    private static function wrapHtml($subject, $content) {
        return '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>'.htmlspecialchars($subject).'</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 15px; text-align: center; }
                .content { padding: 20px; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; 
                          font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>'.htmlspecialchars(self::$config['home_title'] ?? 'SimpleBlog').'</h2>
                </div>
                <div class="content">'.$content.'</div>
                <div class="footer">
                    <p>'.(self::$config['powered'] ?? 'Powered by SimpleBlog').'</p>
                </div>
            </div>
        </body>
        </html>';
    }
}