<?php
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
    
    public function assign($key, $value) {
        $this->variables[$key] = $value;
    }
    
    public function render($templateFile) {
        $templatePath = $this->templateDir . '/' . $templateFile;
        if (!file_exists($templatePath)) {
            throw new Exception("Файл шаблона не найден: {$templateFile}");
        }
        
        extract($this->variables);
        
        ob_start();
        include $templatePath;
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
			$configContent = "<?php\nreturn " . var_export($config, true) . ";\n";
			
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