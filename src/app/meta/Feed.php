<?php

namespace app\meta;

use app\common\AppBase;

class Feed extends AppBase
{
    const PREFIX = 'http://onlymaker.com/';
    const HEAD = [
        'id',
        'model',
        'title',
        'description',
        'price',
        'link',
        'image',
        'category',
        'size'
    ];
    private $csv;

    function get($f3)
    {
        @ini_set('max_execution_time', 1800);
        @ini_set('memory_limit', '512M');

        header('Content-Type: octet-stream');
        header('Content-Disposition: attachment; filename="feed.csv"');

        if (!is_dir($f3->get('UPLOADS'))) {
            mkdir($f3->get('UPLOADS'), 0777, true);
        }

        $this->csv = $f3->get('UPLOADS') . 'feed_' . date('YmdHis') . '.csv';

        $this->feed();

        echo file_get_contents($this->csv);
    }

    function feed()
    {
        $fp = fopen($this->csv, 'a');

        fputcsv($fp, self::HEAD);

        $products = $this->db->exec('SELECT p.product_id as id, p.model, d.name as title, d.description, p.price, p.image FROM oc_product p, oc_product_description d WHERE p.status = 1 AND p.product_id = d.product_id');

        \Base::instance()->log('total products: ' . count($products));

        foreach ($products as $product) {
            $data = [
                $product['id'],
                $product['model'],
                $product['title'],
                strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8')),
                $product['price'],
                self::PREFIX . 'index.php?route=product/product&product_id=' . $product['id'],
                self::PREFIX . $product['image']
            ];

            $category = $this->db->exec('SELECT c.name FROM oc_category_description c, oc_product_to_category p WHERE p.product_id = ? AND p.category_id = c.category_id', $product['id']);
            $data[] = implode(';', array_column($category, 'name'));

            $size = $this->db->exec(<<<SQL
SELECT n.name FROM oc_product_option_value v, oc_option_description o, oc_option_value_description n WHERE v.product_id = ${product['id']} AND v.option_id = o.option_id AND o.name = 'size for shoes' AND v.option_value_id = n.option_value_id  ORDER by n.name
SQL
            );
            $data[] = implode(';', array_column($size, 'name'));;

            fputcsv($fp, $data);
            ob_flush();
            flush();
        }

        fclose($fp);

        \Base::instance()->log($this->db->log());
    }
}
