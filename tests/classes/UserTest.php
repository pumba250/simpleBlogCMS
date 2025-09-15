<?php
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private $user;
    private $pdo;
    
    protected function setUp(): void
    {
        $this->pdo = new MockPDO();
        $this->user = new User($this->pdo);
    }
    
    public function testHasPermission()
    {
        $this->assertTrue($this->user->hasPermission(1, 1));
        $this->assertFalse($this->user->hasPermission(2, 1));
    }
    
    public function testLogin()
    {
        $result = $this->user->login('testuser', 'password');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }
    
    public function testGetUserById()
    {
        $user = $this->user->getUserById(1);
        $this->assertIsArray($user);
        $this->assertArrayHasKey('username', $user);
    }
}