<?php
namespace app\track;

use app\common\AppBase;
use DB\SQL\Mapper;

class Index extends AppBase
{
    private $page = [
        'no'    => 1,
        'size'  => 25,
        'total' => 1,
    ];

    function get($f3)
    {
        $track = new Mapper($this->db, 'oc_track');
        $tracks = $track->find([], $this->pageOptions());

        $access = new Mapper($this->db, 'oc_track_access');
        $register = new Mapper($this->db, 'oc_track_register');
        $payment = new Mapper($this->db, 'oc_track_payment');
        $statistics = [];

        foreach ($tracks as $t) {
            $filter = "code = '${t['code']}'";
            $a = $access->findone($filter);
            $statistics[$t['id']] = [];
            $statistics[$t['id']]['access'] = $a ? $a['count'] : 0;
            $statistics[$t['id']]['register'] = $register->count($filter);
            $statistics[$t['id']]['payment'] = $payment->count($filter);
        }

        $f3->set('tracks', $tracks);
        $f3->set('statistics', $statistics);
        $f3->set('page', $this->page);
        $f3->set('title', 'Tracking code');

        echo \Template::instance()->render('track/index.html');
    }

    function pageOptions()
    {
        //page no
        if ($_GET['page'] && $_GET['page'] > 1) {
            $this->page['no'] = $_GET['page'];
        }

        //page total
        $track = new Mapper($this->db, 'oc_track');
        $total = ceil($track->count() / $this->page['size']);
        $this->page['total'] = $total ? $total : 1;

        //offset
        $offset = ($this->page['no'] - 1) * $this->page['size'];

        return [
            'order'  => 'id desc',
            'limit'  => $this->page['size'],
            'offset' => $offset,
        ];
    }
}