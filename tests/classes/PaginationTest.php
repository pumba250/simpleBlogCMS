<?php
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    public function testCalculatePagination()
    {
        $config = [
            'per_page' => 10,
            'num_links' => 5
        ];
        
        $pagination = Pagination::calculate(100, 'news', 1, $config);
        
        $this->assertIsArray($pagination);
        $this->assertArrayHasKey('total_pages', $pagination);
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertEquals(10, $pagination['total_pages']);
    }
    
    public function testRenderPagination()
    {
        $paginationData = [
            'total_pages' => 5,
            'current_page' => 1,
            'has_prev' => false,
            'has_next' => true
        ];
        
        $html = Pagination::render($paginationData, '/news', 'page');
        
        $this->assertStringContainsString('page=2', $html);
        $this->assertStringContainsString('pagination', $html);
    }
}