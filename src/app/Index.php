<?php
namespace app;

use app\common\AppBase;

class Index extends AppBase
{
    function get($f3)
    {
        $f3->set('title', 'Nav');
        echo \Template::instance()->render('index.html');
    }
}
