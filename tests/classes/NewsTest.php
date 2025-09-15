<?php
use PHPUnit\Framework\TestCase;

class NewsTest extends TestCase
{
    private $news;
    private $pdo;
    
    protected function setUp(): void
    {
        $this->pdo = new MockPDO();
        $this->news = new News($this->pdo);
    }
    
    public function testGetNewsById()
    {
        $result = $this->news->getNewsById(1);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
    }
    
    public function testGetAllNews()
    {
        $result = $this->news->getAllNews(10, 0);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }
    
    public function testGetTotalNewsCount()
    {
        $count = $this->news->getTotalNewsCount();
        $this->assertIsInt($count);
    }
    
    public function testGenerateTags()
    {
        $title = "Test News Title";
        $content = "This is test content with PHP and MySQL keywords";
        
        $tags = $this->news->generateTags($title, $content);
        
        $this->assertIsArray($tags);
        $this->assertContains('test', $tags);
        $this->assertContains('news', $tags);
    }
}