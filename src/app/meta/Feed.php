<?php

namespace app\meta;

use app\common\AppBase;

class Feed extends AppBase
{
    const PREFIX = 'https://onlymaker.com/';
    const HEAD = [
        'id',
        'model',
        'title',
        'description',
        'price',
        'link',
        'image',
        'category',
        'color',
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
                self::PREFIX . 'image/' . $product['image'],
                implode(';', $this->getProductCategories($product['id'])),
                implode(';', $this->getProductColors($product['id'])),
                implode(';', $this->getProductSizes($product['id']))
            ];

            $options = $this->getMultiOptionImages($product['id']);

            if ($options) {
                $head = array_flip(self::HEAD);
                foreach ($options as $option) {
                    $sub = $data;
                    $sub[$head['id']] .= '-' . $option['sku'];
                    $sub[$head['link']] .= '&poip_ov=' . $option['povId'];
                    $sub[$head['model']] = $option['sku'];
                    $sub[$head['color']] = $option['color'];
                    $sub[$head['image']] = self::PREFIX . 'image/' . $option['image'];
                    fputcsv($fp, $sub);
                }
            } else {
                fputcsv($fp, $data);
            }

            ob_flush();
            flush();
        }

        fclose($fp);

        \Base::instance()->log($this->db->log());
    }

    function getProductCategories($id)
    {
        $categories = $this->db->exec("SELECT c.name as category FROM oc_category_description c, oc_product_to_category p WHERE p.product_id = $id AND p.category_id = c.category_id");
        return array_column($categories, 'category');
    }

    function getProductColors($id)
    {
        $color = $this->db->exec("SELECT v.name as color FROM oc_product_option_value p, oc_option_description d, oc_option_value_description v WHERE p.product_id = $id AND d.name = 'color' AND p.option_id = d.option_id AND p.option_value_id = v.option_value_id");
        return array_column($color, 'color');
    }

    function getProductSizes($id)
    {
        $sizes = $this->db->exec("SELECT v.name as size FROM oc_product_option_value p, oc_option_description d, oc_option_value_description v WHERE p.product_id = $id AND d.name LIKE 'size%' AND p.option_id = d.option_id AND p.option_value_id = v.option_value_id  ORDER by 1");
        return array_column($sizes, 'size');
    }

    function getMultiOptionImages($id)
    {
        $results = [];

        $data = $this->db->exec("SELECT DISTINCT(product_option_value_id) FROM oc_poip_option_image WHERE product_id = $id");

        if (count($data) > 1) {
            foreach ($data as $item) {
                $povId = $item['product_option_value_id'];
                list($info) = $this->db->exec("SELECT name as color, sub_sku as sku, image FROM oc_option_value_description v, oc_product_option_value p, oc_poip_option_image m WHERE v.option_value_id = p.option_value_id AND p.product_option_value_id = $povId AND p.product_option_value_id = m.product_option_value_id ORDER BY sort_order LIMIT 1");
                $results[] = [
                    'povId' => $povId,
                    'color' => $info['color'],
                    'sku' => $info['sku'],
                    'image' => $info['image']
                ];
            }
        }

        return $results;
    }
}
