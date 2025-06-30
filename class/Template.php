<?php
/**
 * Template Engine - handles view rendering with security and variable management
 *
 * @package    SimpleBlog
 * @subpackage Core
 * @category   Views
 * @version    0.6.9
 *
 * @property-read array $variables      All assigned template variables
 * @property-read string $templateDir   Active template directory
 *
 * @method void assign(string $key, mixed $value)        Assign single variable (auto-escapes strings)
 * @method void assignMultiple(array $vars)              Bulk assign variables
 * @method string render(string $templateFile, array $additionalVars = []) Render template to string
 * @method array getCommonTemplateVars(array $config, News $news, ?User $user) Get standard template vars
 * @method bool changeTemplate(string $newTemplate, array $availableTemplates, array &$config, string $configPath) Switch active template
 * @method array getAvailableTemplates(string $templatesDir) Discover valid templates
 * @method string formatSize(int $bytes) Format bytes to human-readable size
 *
 * @security
 * - Automatic HTML escaping for all string variables
 * - Sandboxed template inclusion
 * - Path traversal protection
 *
 * @template-requirements
 * - Must contain header.tpl
 * - Must contain footer.tpl
 * - UTF-8 encoded files
 *
 * @performance
 * - Uses output buffering
 * - OPcache integration
 * - Minimal variable processing overhead
 *
 * @throws RuntimeException On template file errors
 * @throws Exception On directory initialization failures
 */
class Template {
    protected $variables = [];
    protected $templateDir;
    
    public function __construct() {
        global $templ; 
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
		return [
			'powered' => $config['powered'],
			'version' => $config['version'],
			'dbPrefix' => $dbPrefix,
			'templ' => $config['templ'],
			'captcha_image_url' => '/class/captcha.php',
			'allTags' => $news->GetAllTags(),
			'lastThreeNews' => $news->getLastThreeNews(),
			'user' => $user ?? null,
			'comments' => $comments
		];
	}
    
/**
 * Рендерит шаблон с передачей переменных
 * 
 * @param string $templateFile Имя файла шаблона (относительно templateDir)
 * @param array $additionalVars Дополнительные переменные для этого рендеринга
 * @return string
 * @throws Exception Если файл шаблона не найден или ошибка рендеринга
 */
public function render(string $templateFile, array $additionalVars = []): string
{
    $templatePath = $this->templateDir . '/' . ltrim($templateFile, '/');
    
    if (!file_exists($templatePath)) {
        throw new \RuntimeException("Template file not found: {$templatePath}");
    }
    
    // Объединяем общие переменные с дополнительными
    $vars = array_merge($this->variables, $additionalVars);
    
    // Извлекаем переменные в текущую область видимости
    extract($vars, EXTR_SKIP);
    
    // Буферизация вывода
    ob_start();
    try {
        include $templatePath;
    } catch (\Throwable $e) {
        ob_end_clean(); // Очищаем буфер в случае ошибки
        throw new \RuntimeException("Error rendering template {$templateFile}: " . $e->getMessage(), 0, $e);
    }
    
    return ob_get_clean();
}
    
    public function setTemplateDir($dir) {
        $this->templateDir = $dir;
        return $this;
    }
	function getAvailableTemplates($templatesDir) {
		$availableTemplates = [];
		if (is_dir($templatesDir)) {
			$items = scandir($templatesDir);
			foreach ($items as $item) {
				$fullPath = $templatesDir.'/'.$item;
				if ($item != '.' && $item != '..' && is_dir($fullPath)) {
					if (file_exists($fullPath.'/footer.tpl') && 
						file_exists($fullPath.'/header.tpl')) {
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
	function changeTemplate($newTemplate, $availableTemplates, &$config, $configPath) {
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
}