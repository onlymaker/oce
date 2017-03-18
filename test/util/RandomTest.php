<?php
namespace test\util;

use Base as F3;
use PHPUnit\Framework\TestCase;

class RandomTest extends TestCase
{
    function testInt()
    {
        $int = random_int(1, 10);
        $this->assertTrue(is_int($int));
        F3::instance()->log('random number: {i}', ['i' => $int]);
        return $int;
    }

    /**
     * @depends testInt
     */
    function testString($length)
    {
        $bytes = bin2hex(random_bytes($length));
        $this->assertTrue(is_string($bytes));
        $this->assertEquals($length * 2, strlen($bytes));
        F3::instance()->log('random string: {s}', ['s' => $bytes]);
    }
}
