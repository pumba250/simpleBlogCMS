<?php
use PHPUnit\Framework\TestCase;

class CommentsTest extends TestCase
{
    private $comments;
    private $pdo;
    
    protected function setUp(): void
    {
        $this->pdo = new MockPDO();
        $this->comments = new Comments($this->pdo);
    }
    
    public function testAddComment()
    {
        $result = $this->comments->addComment(0, 0, 1, 'testuser', 'Test comment');
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }
    
    public function testGetComments()
    {
        $comments = $this->comments->getComments(1, 10, 0, true);
        $this->assertIsArray($comments);
    }
    
    public function testCountComments()
    {
        $count = $this->comments->countComments(1);
        $this->assertIsInt($count);
    }
    
    public function testToggleModeration()
    {
        $result = $this->comments->toggleModeration(1);
        $this->assertTrue($result);
    }
}