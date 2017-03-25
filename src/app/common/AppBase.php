<?php
namespace app\common;

use data\Database;

class AppBase
{
    use Url;

    protected $error = ['code' => -1, 'text' => 'Undefined'];
    protected $db = null;

    function beforeRoute($f3)
    {
        if (!$f3->get('SESSION.AUTH')) {
            if ($f3->get('VERB') == 'GET') {
                setcookie('target', $f3->get('REALM'), 0, '/');
            } else {
                setcookie('target', $this->url(), 0, '/');
            }
            $f3->reroute($this->url('/Login'));
        }

        $this->db = Database::mysql();
    }

    function jsonResponse($data = [])
    {
        if (!$this->error->code && $data) {
            return json_encode(array_merge(['error' => $this->error], $data), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(['error' => $this->error]);
        }
    }

}
