<?php
if (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен'); }

/**
 * Класс шаблонизатора с поддержкой синтаксиса {}
 * 
 * @package    SimpleBlog
 * @subpackage Core
 * @category   Views
 * @version    0.8.1
 * 
 * @method void assign(string $key, mixed $value) Назначает переменную
 * @method void assignMultiple(array $vars) Назначает несколько переменных
 * @method string render(string $templateFile, array $additionalVars = []) Рендерит шаблон
 * @method array getCommonTemplateVars(array $config, News $news, ?User $user) Получает общие переменные
 * @method bool changeTemplate(string $newTemplate, array $availableTemplates, array &$config, string $configPath) Меняет шаблон
 * @method array getAvailableTemplates(string $templatesDir) Получает доступные шаблоны
 * @method string formatSize(int $bytes) Форматирует размер
 */
class Template {
    protected $variables = [];
    protected $templateDir;
    //protected $config;
    
    public function __construct() {
        global $templ;
        $this->config = $config;
        $this->templateDir = "templates/{$templ}";
        
        if (!is_dir($this->templateDir)) {
            throw new Exception("Директория с шаблонами не найдена: {$this->templateDir}");
        }
    }

    public function assignMultiple(array $vars): void {
        foreach ($vars as $key => $value) {
            $this->assign($key, $value);
        }
    }

    public function assign($key, $value) {
        if (is_string($value)) {
            $this->variables[$key] = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
        } else {
            $this->variables[$key] = $value;
        }
    }

	public function getCommonTemplateVars($config, $news, $user = null) {
		$currentCommentPage = $_GET['comment_page'] ?? 1;
		$commentsPerPage = 10;//$config['comments_per_page']; // Количество комментариев на странице
		$authError = $_SESSION['auth_error'] ?? null;
			if (isset($_SESSION['auth_error'])) {
				unset($_SESSION['auth_error']); // Очищаем сразу после получения
			}
			$flash = $_SESSION['flash'] ?? null;
			if (isset($_SESSION['flash'])) {
				unset($_SESSION['flash']);
			}

		return [
			'powered' => $config['powered'],
			'version' => $config['version'],
			'dbPrefix' => $dbPrefix,
			'csrf_token' => $_SESSION['csrf_token'],
			'templ' => $config['templ'],
			'captcha_image_url' => '/class/captcha.php',
			'allTags' => $news->GetAllTags(),
			'lastThreeNews' => $news->getLastThreeNews(),
			'user' => $user ?? null,
			'auth_error' => $authError,
			'flash' => $flash,
			'comments' => $comments ?? null,
			'currentYear' => date("Y"),
			'serverName' => htmlspecialchars($_SERVER['SERVER_NAME']),
		];
	}
/*public function renderNewsList(array $newsItems, string $template): string {
    $html = '';
    foreach ($newsItems as $item) {
        $html .= $this->renderNewsItem($item, $template);
    }
    return $html;
}*/

/*public function renderNewsItem(array $item, string $template): string {
    // Обрабатываем шаблон для одной новости
    $patterns = [];
    $replacements = [];
    
    foreach ($item as $key => $value) {
        $patterns[] = '/\{' . $key . '\}/';
        if (is_array($value)) {
            $replacements[] = implode(', ', $value);
        } else {
            $replacements[] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }
    
    // Специальная обработка для {!content} (без экранирования)
    if (isset($item['content'])) {
        $patterns[] = '/\{!content\}/';
        $replacements[] = $item['content'];
    }
    
    return preg_replace($patterns, $replacements, $template);
}*/
    /**
     * Рендерит шаблон с передачей переменных
     * 
     * @param string $templateFile Имя файла шаблона (относительно templateDir)
     * @param array $additionalVars Дополнительные переменные для этого рендеринга
     * @return string
     * @throws Exception Если файл шаблона не найден или ошибка рендеринга
     */
    public function render(string $templateFile, array $additionalVars = []): string {
        $templatePath = $this->templateDir . '/' . ltrim($templateFile, '/');
        
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template file not found: {$templatePath}");
        }
        
        $vars = array_merge($this->variables, $additionalVars);
        $templateContent = file_get_contents($templatePath);
        $compiledContent = $this->compileTemplate($templateContent);
        
        $tempFile = tempnam(sys_get_temp_dir(), 'tpl_');
        file_put_contents($tempFile, $compiledContent);
        
        // Сохраняем текущий templateDir для использования в include
        $originalTemplateDir = $this->templateDir;
        
        extract($vars, EXTR_SKIP);
        ob_start();
        try {
            include $tempFile;
            unlink($tempFile);
        } catch (\Throwable $e) {
            ob_end_clean();
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            error_log("Template error: " . $e->getMessage());
            if ($this->config['debug'] ?? false) {
                throw $e;
            }
            return '<div class="error">Page rendering error</div>';
        } finally {
            // Восстанавливаем оригинальный templateDir
            $this->templateDir = $originalTemplateDir;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Компилирует шаблон с синтаксисом {} в PHP-код
     */
    protected function compileTemplate(string $content): string {
        $replacements = [
            // Условия
            '/\{if\s+(.+?)\}/' => '<?php if ($1): ?>',
            '/\{elseif\s+(.+?)\}/' => '<?php elseif ($1): ?>',
            '/\{else\}/' => '<?php else: ?>',
            '/\{\/if\}/' => '<?php endif; ?>',
            // Статические методы (Lang::get)
			'/\{([A-Za-z_][A-Za-z0-9_]*)::([a-zA-Z0-9_]+)\(([^)]*)\)\}/' => '<?php echo $1::$2($3); ?>',
            // Циклы
            '/\{foreach\s+\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+as\s+\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/' => '<?php foreach ($$1 as $$2): ?>',
           '/\{for \$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*=\s*([^\s]+)\s+to\s+([^\}]+)\s*\}/' => '<?php for ($1 = $2; $1 <= $3; $1++): ?>',
        '/\{\/for\}/' => '<?php endfor; ?>', '/\{foreach\s+\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s+as\s+\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*=>\s*\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/' => '<?php foreach ($$1 as $$2 => $$3): ?>',
            '/\{\/foreach\}/' => '<?php endforeach; ?>',
            // Обработка $_SESSION, $_GET, $_POST и других суперглобальных массивов
        '/\{\$_([A-Za-z_]+)\[\'([^\']+)\'\]\}/' => '<?php echo htmlspecialchars($_$1[\'$2\'] ?? \'\', ENT_QUOTES, \'UTF-8\'); ?>',
            // Включения (исправлено)
            '/\{include\s+[\'"](.+?)[\'"]\}/' => '<?php echo $this->render(\'$1\'); ?>',
            '/\{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\[\'([^\']+)\'\]\s*\?:\s*([^\}]+)\}/' => '<?php echo htmlspecialchars(($$1[\'$2\'] ?? $3), ENT_QUOTES, \'UTF-8\'); ?>',
        '/\{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\.([a-zA-Z0-9_\x7f-\xff]+)\s*\?:\s*([^\}]+)\}/' => '<?php echo htmlspecialchars((is_array($$1) ? ($$1[\'$2\'] ?? $3) : ($$1->$2 ?? $3)), ENT_QUOTES, \'UTF-8\'); ?>',
        // Общее правило для элементов массивов
        '/\{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\[\'([^\']+)\'\]\}/' => '<?php echo htmlspecialchars($$1[\'$2\'] ?? \'\', ENT_QUOTES, \'UTF-8\'); ?>',
            // Переменные
            '/\{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/' => '<?php echo htmlspecialchars($$1, ENT_QUOTES, \'UTF-8\'); ?>',
           '/\{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\.([a-zA-Z0-9_\x7f-\xff]+)\}/' => '<?php echo htmlspecialchars((is_array($$1) ? $$1[\'$2\'] : $$1->$2), ENT_QUOTES, \'UTF-8\'); ?>',
            '/\{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)->([a-zA-Z0-9_\x7f-\xff]+)\}/' => '<?php echo htmlspecialchars($$1->$2, ENT_QUOTES, \'UTF-8\'); ?>',
			// Для арифметических операций с элементами массива
        '/\{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\[\'([a-zA-Z0-9_\x7f-\xff]+)\'\]\s*([+\-*\/])\s*([0-9]+)\}/' => '<?php echo htmlspecialchars($$1[\'$2\'] $3 $4, ENT_QUOTES, \'UTF-8\'); ?>',
		'/\{\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*([+\-*\/])\s*([0-9]+)\}/' => '<?php echo htmlspecialchars($$1 $2 $3, ENT_QUOTES, \'UTF-8\'); ?>',
            // Форматирование дат
        '/\{([^\|]+)\|date_format\}/' => '<?php echo date("d.m.Y", strtotime($1)); ?>',
        // Проверка на пустоту (empty)
        '/\{empty\s+(.+?)\}/' => '<?php if(empty($1)): ?>',
        
        // Модификаторы для переменных
        '/\{([^\|]+)\|upper\}/' => '<?php echo strtoupper($1); ?>',
        '/\{([^\|]+)\|lower\}/' => '<?php echo strtolower($1); ?>',
        
        // Более сложные условия в {if}
        '/\{if\s+([^}]+)\}/' => '<?php if ($1): ?>',
        
        // Обработка тернарных операторов
        '/\{\$([a-zA-Z0-9_]+)\s*\?\s*([^:]+)\s*:\s*([^}]+)\}/' => '<?php echo $$1 ? $2 : $3; ?>',
            // Вывод без экранирования
            '/\{!\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/' => '<?php echo $$1; ?>',
			'/\{!\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\.([a-zA-Z0-9_\x7f-\xff]+)\}/' => '<?php echo $$1[\'$2\']; ?>',
            
            // Комментарии
            '/\{\*.+?\*\}/s' => '',
        ];
        
        return preg_replace(array_keys($replacements), array_values($replacements), $content);
    }

    public function setTemplateDir($dir) {
        $this->templateDir = $dir;
        return $this;
    }

    public function getAvailableTemplates($templatesDir) {
        $availableTemplates = [];
        if (is_dir($templatesDir)) {
            $items = scandir($templatesDir);
            foreach ($items as $item) {
                $fullPath = $templatesDir.'/'.$item;
                if ($item != '.' && $item != '..' && is_dir($fullPath)) {
                    if (file_exists($fullPath.'/footer.tpl') && 
                        file_exists($fullPath.'/header.tpl') && 
                        file_exists($fullPath.'/home.tpl')) {
                        $availableTemplates[] = $item;
                    }
                }
            }
        }
        return $availableTemplates;
    }

    public function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function changeTemplate($newTemplate, $availableTemplates, &$config, $configPath) {
        if (in_array($newTemplate, $availableTemplates)) {
            $config['templ'] = $newTemplate;
            $configContent = "<?php\nif (!defined('IN_SIMPLECMS')) { die('Прямой доступ запрещен'); }\nreturn " . var_export($config, true) . ";\n";
            
            if (file_put_contents($configPath, $configContent, LOCK_EX)) {
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($configPath);
                }
                return true;
            }
        }
        return false;
    }
	
	// Добавляем в класс Template
public function renderTemplateString(string $template, array $data): string {
    // Обрабатываем основные плейсхолдеры
    foreach ($data as $key => $value) {
        // Экранированные плейсхолдеры {key}
        $template = str_replace(
            '{'.$key.'}', 
            is_array($value) ? implode(', ', $value) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
            $template
        );
        
        // Неэкранированные плейсхолдеры {!key}
        $template = str_replace(
            '{!'.$key.'}', 
            is_array($value) ? implode(', ', $value) : $value,
            $template
        );
    }

    // Обрабатываем вложенные свойства объектов {user.name}
    $template = preg_replace_callback(
        '/\{([a-zA-Z_][a-zA-Z0-9_]*)\.([a-zA-Z_][a-zA-Z0-9_]*)\}/',
        function($matches) use ($data) {
            $var = $data[$matches[1]] ?? null;
            if (is_array($var)) {
                return htmlspecialchars($var[$matches[2]] ?? '', ENT_QUOTES, 'UTF-8');
            } elseif (is_object($var)) {
                return htmlspecialchars($var->{$matches[2]} ?? '', ENT_QUOTES, 'UTF-8');
            }
            return '';
        },
        $template
    );

    return $template;
}

public function renderNewsList(array $newsItems, string $templateFile): string {
    $template = file_get_contents($this->templateDir . '/' . $templateFile);
    $result = '';

    foreach ($newsItems as $item) {
        $result .= $this->renderTemplateString($template, $item);
    }

    return $result;
}

public function renderNewsItem(array $newsItem, string $templateFile): string {
    $template = file_get_contents($this->templateDir . '/' . $templateFile);
    return $this->renderTemplateString($template, $newsItem);
}
}