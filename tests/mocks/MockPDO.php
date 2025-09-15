<?php
class MockPDO extends PDO
{
    public function __construct()
    {
        // Пустой конструктор для избежания реального подключения
    }
    
    public function prepare($statement, $options = [])
    {
        return new MockStatement();
    }
}