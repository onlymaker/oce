<?php
namespace app;

use app\common\AppBase;

class Index extends AppBase
{
    function get($f3)
    {
        $f3->reroute('track/Index');
    }
}
