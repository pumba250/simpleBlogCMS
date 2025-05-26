<?php
class Template {
    protected $variables = [];
    protected $templateDir;
    
    public function __construct() {
        global $templ; // Используем глобальную переменную $templ из config.php
        $this->templateDir = "templates/{$templ}";
        
        // Проверяем существование директории с шаблонами
        if (!is_dir($this->templateDir)) {
            throw new Exception("Директория с шаблонами не найдена: {$this->templateDir}");
        }
    }
    
    // Добавляем переменную в шаблон
    public function assign($key, $value) {
        $this->variables[$key] = $value;
    }
    
    // Рендерим шаблон
    public function render($templateFile) {
        // Проверяем существование файла шаблона
        $templatePath = $this->templateDir . '/' . $templateFile;
        if (!file_exists($templatePath)) {
            throw new Exception("Файл шаблона не найден: {$templateFile}");
        }
        
        // Извлекаем переменные в текущую область видимости
        extract($this->variables);
        
        // Включаем буферизацию вывода
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
    
    // Дополнительный метод для смены директории шаблонов
    public function setTemplateDir($dir) {
        $this->templateDir = $dir;
        return $this;
    }
}