<?php

namespace app\meta;

use app\common\AppBase;

class Product extends AppBase
{
    function get($f3)
    {
        $f3->set('title', 'Product meta information');
        echo \Template::instance()->render('meta/product.html');
    }

    function meta($f3)
    {
        $data = [];
        switch ($_GET['name']) {
            case 'attribute':
                $data = $this->db->exec('SELECT attribute_id AS id, name FROM oc_attribute_description ORDER BY id');
                break;
            case 'category':
                $data = $this->db->exec('SELECT category_id AS id, name FROM oc_category_description ORDER BY id');
                break;
            case 'filter':
                $data = $this->db->exec('SELECT filter_id AS id, name FROM oc_filter_description ORDER BY id');
                break;
            case 'option':
                $data = $this->db->exec('SELECT o.option_id AS id, d.name, o.type FROM oc_option o, oc_option_description d WHERE o.option_id=d.option_id ORDER BY id');
                break;
            case 'option value':
                $data = $this->db->exec('SELECT option_id AS id, option_value_id as vid, name FROM oc_option_value_description ORDER BY id, vid');
                break;
        }
        $f3->log($this->db->log());
        $this->error['code'] = 0;
        echo $this->jsonResponse($data);
    }
}