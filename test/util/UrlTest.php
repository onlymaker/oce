<?php
/**
 * Created by IntelliJ IDEA.
 * User: jibo
 * Date: 2017/4/11
 * Time: 17:40
 */

namespace test\util;

use PHPUnit\Framework\TestCase;
use Purl\Url;

class UrlTest extends TestCase
{
    function testBuildQuery()
    {
        $url = new Url('http://oce?name=test#hash');
        $url->query->set('track_code', 'test');
        echo $url, PHP_EOL;
        $this->assertEquals('http://oce/?name=test&track_code=test#hash', $url);
    }
}