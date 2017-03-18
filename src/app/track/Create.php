<?php
namespace app\track;

use app\common\AppBase;
use DB\SQL\Mapper;

class Create extends AppBase
{
    function get($f3)
    {
        $f3->set('title', 'Create - Tracking code');
        echo \Template::instance()->render('track/create.html');
    }

    function post($f3)
    {
        $track = new Mapper($this->db, 'oc_track');
        $track['code'] = $_POST['code'];
        $track['description'] = $_POST['description'];
        $track->save();

        $access = new Mapper($this->db, 'oc_track_access');
        $access['code'] = $_POST['code'];
        $access->save();
        
        $f3->set('code', $track['code']);
        $f3->set('title', 'Create Success');

        echo \Template::instance()->render('track/create_success.html');
    }

    function check()
    {
        $code = $_POST['code'];

        if (empty($code)) {
            $this->error['text'] = 'code required';
        } else {
            $track = new Mapper($this->db, 'oc_track');
            $count = $track->count(['code = ?', $code]);
            if ($count) {
                $this->error['text'] = 'code already existed';
            } else {
                $this->error['code'] = 0;
            }
        }

        echo $this->jsonResponse();
    }
}