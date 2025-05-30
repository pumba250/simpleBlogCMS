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
}