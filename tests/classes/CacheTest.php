<?php
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    private $cache;
    
    protected function setUp(): void
    {
        $config = [
            'driver' => 'file',
            'path' => TEST_CACHE_DIR,
            'ttl' => 3600
        ];
        
        Cache::init($config);
        $this->cache = Cache::class;
    }
    
    public function testSetAndGet()
    {
        $key = 'test_key';
        $data = ['test' => 'data'];
        
        $result = $this->cache::set($key, $data, 60);
        $this->assertTrue($result);
        
        $cached = $this->cache::get($key);
        $this->assertEquals($data, $cached);
    }
    
    public function testHas()
    {
        $key = 'test_has';
        $this->cache::set($key, 'value', 60);
        
        $this->assertTrue($this->cache::has($key));
        $this->assertFalse($this->cache::has('non_existent'));
    }
    
    public function testDelete()
    {
        $key = 'test_delete';
        $this->cache::set($key, 'value', 60);
        
        $this->assertTrue($this->cache::delete($key));
        $this->assertFalse($this->cache::has($key));
    }
    
    public function testClear()
    {
        $this->cache::set('key1', 'value1', 60);
        $this->cache::set('key2', 'value2', 60);
        
        $this->cache::clear();
        
        $this->assertFalse($this->cache::has('key1'));
        $this->assertFalse($this->cache::has('key2'));
    }
    
    protected function tearDown(): void
    {
        $this->cache::clear();
    }
}