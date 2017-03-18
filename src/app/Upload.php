<?php
namespace app;

use app\common\Url;

class Upload extends \Web
{
    use Url;

    function post($f3)
    {
        $data = [];

        foreach (parent::receive(null, true, false) as $key => $value) {
            if ($value) {
                $name = basename($key);
                $data[] = [
                    $name,
                    $this->url(substr($f3->get('UPLOADS'), strlen($f3->get('ROOT'))) . $name),
                ];
            }
        }

        echo json_encode(['files' => $data], JSON_UNESCAPED_UNICODE);
    }

    function get()
    {
        echo __CLASS__;
    }

    function beforeRoute($f3)
    {
        if ($f3->get('AJAX')) {
            header('Access-Control-Allow-Origin:*');
        }
    }
}
