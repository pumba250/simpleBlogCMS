<?php
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    private $template;
    
    protected function setUp(): void
    {
        $this->template = new Template();
    }
    
    public function testAssignAndRender()
    {
        $this->template->assign('testVar', 'testValue');
        
        $templateContent = '<div>{$testVar}</div>';
        $result = $this->template->renderTemplateString($templateContent, []);
        
        $this->assertEquals('<div>testValue</div>', $result);
    }
    
    public function testAssignMultiple()
    {
        $vars = [
            'var1' => 'value1',
            'var2' => 'value2'
        ];
        
        $this->template->assignMultiple($vars);
        
        $templateContent = '<div>{$var1} - {$var2}</div>';
        $result = $this->template->renderTemplateString($templateContent, []);
        
        $this->assertEquals('<div>value1 - value2</div>', $result);
    }
    
    public function testFormatSize()
    {
        $this->assertEquals('1 KB', $this->template->formatSize(1024));
        $this->assertEquals('1 MB', $this->template->formatSize(1048576));
    }
}