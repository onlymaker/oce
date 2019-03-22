<?php
/**
 * This script is trying to migrate the product from previous store to current
 * Output is /tmp/migrate.sql
 */

ini_set("memory_limit", "1024M");

define('ROOT', dirname(dirname(__DIR__)));
define('OUTPUT', '/tmp/migrate.sql');

require_once ROOT . '/vendor/autoload.php';

$f3 = Base::instance();
$f3->config(ROOT . '/src/cfg/system.ini');
$f3->config(ROOT . '/src/cfg/local.ini');

$db = new \DB\SQL(
    $f3->get('MYSQL_DSN'),
    $f3->get('MYSQL_USER'),
    $f3->get('MYSQL_PASSWORD')
);

list($query) = $db->exec('SELECT max(product_option_id) as max FROM oc_product_option');
$nextProductOptionId = (int)$query['max'] + 1;
list($query) = $db->exec('SELECT max(product_option_value_id) as max FROM oc_product_option_value');
$nextProductOptionValueId = (int)$query['max'] + 1;

if (file_exists(OUTPUT)) {
    unlink(OUTPUT);
}

$row = \PhpOffice\PhpSpreadsheet\IOFactory::load($argv[1])->getSheet(0)->getRowIterator(2);

while ($row->valid()) {
    file_put_contents(OUTPUT, toSql($row->current()->getCellIterator(), $nextProductOptionId, $nextProductOptionValueId), FILE_APPEND);
    $row->next();
}

function toSql($iterator, &$nextProductOptionId, &$nextProductOptionValueId)
{
    $field = [
        'id' => 'A',
        'model' => 'E',
        'name' => 'M',
        'description' => 'P',
        'price' => 'R',
        'mainImage' => 'Y',
        'sort' => 'AH',
        'images' => 'AQ',
        'metaTitle' => 'AY',
        'category' => 'BD',
        'filter' => 'BE',
        'option' => 'BF',
    ];

    $id = (int)trim($iterator->seek($field['id'])->current());
    $model = (string)trim($iterator->seek($field['model'])->current());
    $name = htmlentities(trim($iterator->seek($field['name'])->current()), ENT_QUOTES);
    $description = htmlentities(trim($iterator->seek($field['description'])->current()), ENT_QUOTES);
    $price = (float)trim($iterator->seek($field['price'])->current());
    $mainImage = htmlentities(trim($iterator->seek($field['mainImage'])->current()), ENT_QUOTES);
    $sort = (int)trim($iterator->seek($field['sort'])->current());
    $images = htmlentities(trim($iterator->seek($field['images'])->current()), ENT_QUOTES);
    $metaTitle = htmlentities(trim($iterator->seek($field['metaTitle'])->current()), ENT_QUOTES);
    $categories = (string)trim($iterator->seek($field['category'])->current());
    $filters = (string)trim($iterator->seek($field['filter'])->current());
    $options = (string)trim($iterator->seek($field['option'])->current());
    $points = ceil($price / 100);

    $sql = <<<SQL
#### $id #####
DELETE FROM oc_product WHERE product_id=$id;
INSERT INTO oc_product SET product_id=$id, model='$model', sku='', upc='', ean='', jan='', isbn='', mpn='',
manufacturer_id=11, shipping=1, price=$price, points=$points, quantity=1000, minimum=1, stock_status_id=6,
image='$mainImage', weight=1, weight_class_id=1, length=0, width=0, height=0, length_class_id=1, tax_class_id=0,
location='', status=1, subtract=1, sort_order=$sort, date_available=now(), date_added=now(), date_modified=now();
DELETE FROM oc_product_to_store WHERE product_id=$id;
INSERT INTO oc_product_to_store SET product_id=$id, store_id=0;\n
SQL;

    $sql .= "DELETE FROM oc_product_description WHERE product_id=$id;\n";
    $sql .= "INSERT INTO oc_product_description SET product_id=$id, language_id=1, name='$name', description='$description', tag='', meta_title='$metaTitle', meta_description='', meta_keyword='';\n";

    if ($images) {
        $sql .= "DELETE FROM oc_product_image WHERE product_id=$id;\n";
        foreach (explode(';', $images) as $image) {
            if ($image) {
                $sql .= "INSERT INTO oc_product_image SET product_id=$id, image='$image';\n";
            }
        }
    }

    if ($categories) {
        $sql .= "DELETE FROM oc_product_to_category WHERE product_id=$id;\n";
        foreach (explode(',', $categories) as $category) {
            if ($category) {
                $sql .= "INSERT INTO oc_product_to_category SET product_id=$id, category_id=$category;\n";
            }
        }
    }

    if ($filters) {
        $sql .= "DELETE FROM oc_product_filter WHERE product_id=$id;\n";
        foreach (explode(',', $filters) as $filter) {
            if ($filter) {
                $sql .= "INSERT INTO oc_product_filter SET product_id=$id, filter_id=$filter;\n";
            }
        }
    }

    if ($options) {
        $sql .= "DELETE FROM oc_product_option WHERE product_id=$id;\n";
        $sql .= "DELETE FROM oc_product_option_value WHERE product_id=$id;\n";

        $option = [];

        foreach (explode(';', $options) as $value) {
            if (strpos($value, ',') !== false) {
                $values = explode(',', $value);
                if ($option[$values[0]]) {
                    $option[$values[0]][] = $values[1];
                } else {
                    $option[$values[0]] = [$values[1]];
                }
            }
        }

        foreach ($option as $oid => $vids) {
            $sql .= "INSERT INTO oc_product_option SET product_option_id=$nextProductOptionId, product_id=$id, option_id=$oid, value='', required=1;\n";
            foreach ($vids as $vid) {
                $sql .= "INSERT INTO oc_product_option_value SET product_option_value_id=$nextProductOptionValueId, product_option_id=$nextProductOptionId, product_id=$id, option_id=$oid, option_value_id=$vid, quantity=1000, subtract=1, price=0, price_prefix='+', points=0, points_prefix='+', weight=0, weight_prefix='+';\n";
                $nextProductOptionValueId ++;
            }
            $nextProductOptionId ++;
        }
    }

    return $sql . PHP_EOL;
}
