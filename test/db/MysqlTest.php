<?php
namespace test\db;

use data\Database;
use PHPUnit\Framework\TestCase;

class MysqlTest extends TestCase
{
    public function testPdo()
    {
        $pdo = Database::mysql();
        $this->assertNotEmpty($pdo);
        return $pdo;
    }

    /**
     * @depends testPdo
     */
    public function testConnection($pdo)
    {
        $this->assertNotEmpty($pdo->query('SELECT 1'));
    }
}
