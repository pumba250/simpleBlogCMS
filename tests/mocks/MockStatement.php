<?php
class MockStatement
{
    public $boundValues = [];
    public $executeResult = true;
    
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR)
    {
        $this->boundValues[$parameter] = $value;
        return true;
    }
    
    public function execute()
    {
        return $this->executeResult;
    }
    
    public function fetch($fetch_style = PDO::FETCH_ASSOC)
    {
        return ['id' => 1, 'title' => 'Test News'];
    }
    
    public function fetchAll($fetch_style = PDO::FETCH_ASSOC)
    {
        return [
            ['id' => 1, 'title' => 'Test News 1'],
            ['id' => 2, 'title' => 'Test News 2']
        ];
    }
    
    public function rowCount()
    {
        return 1;
    }
}