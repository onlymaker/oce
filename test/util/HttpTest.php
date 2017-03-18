<?php
namespace test\util;

use Httpful\Mime;
use Httpful\Request;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    public function testLogin()
    {
        $login = Request::post('http://localhost/Login', http_build_query([
            'username' => bin2hex(random_bytes(3)),
            'password' => bin2hex(random_bytes(3)),
        ]))
            ->expects(Mime::JSON)
            ->sendsType(Mime::FORM)
            ->send();
        $this->assertNotEquals(0, $login->body->error->code);
    }
}
