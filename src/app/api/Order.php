<?php
namespace app\api;

use data\Database;
use helper\Bridge;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class Order
{
    function download($f3)
    {
        $token = $f3->get('GET.token');

        $bridge = new Bridge();
        if ($bridge->isTokenValid($token)) {
            $orders = $f3->get('GET.orders');

            $db = Database::mysql();
            $sql = <<<SQL
SELECT o.order_id, p.product_id, p.order_product_id, p.model, p.price, p.quantity, p.total AS pt, (SELECT ot.value FROM oc_order_total ot WHERE ot.order_id = o.order_id AND ot.code = 'shipping') AS shipping, o.total, o.currency_code AS currency, o.currency_value, date_added, shipping_firstname AS firstname, shipping_lastname AS lastname, shipping_company as company, shipping_address_1 AS address1, shipping_address_2 AS address2, shipping_country AS country, shipping_zone AS zone, shipping_city AS city, shipping_postcode AS postcode, telephone, email, comment FROM oc_order o LEFT JOIN oc_order_product p ON o.order_id = p.order_id WHERE o.order_id in (${orders});
SQL;
            $results = $db->exec($sql);

            $spreadsheet = new Spreadsheet();

            $spreadsheet->getProperties()->setCreator("Auto Generated")
                ->setTitle("Order Info")
                ->setSubject("Order Info")
                ->setDescription("Order Info");

            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A1', 'OrderId')
                ->setCellValue('B1', 'Model')
                ->setCellValue('C1', 'Product Price')
                ->setCellValue('D1', 'Quantity')
                ->setCellValue('E1', 'Product Total')
                ->setCellValue('F1', 'Shipping Price')
                ->setCellValue('G1', 'Order Total')
                ->setCellValue('H1', 'Currency')
                ->setCellValue('I1', 'Date Added')
                ->setCellValue('J1', 'Receiver')
                ->setCellValue('K1', 'Company')
                ->setCellValue('L1', 'Address 1')
                ->setCellValue('M1', 'Address 2')
                ->setCellValue('N1', 'Country')
                ->setCellValue('O1', 'Region/State')
                ->setCellValue('P1', 'City')
                ->setCellValue('Q1', 'Postcode')
                ->setCellValue('R1', 'Telephone')
                ->setCellValue('S1', 'Email')
                ->setCellValue('T1', 'Comment')
                ->setCellValue('U1', 'Option')
                ->setCellValue('V1', 'Image');

            $iterator = $spreadsheet->getActiveSheet()->getRowIterator(1);

            foreach ($results as $result) {
                $options = $db->exec("SELECT product_option_value_id, name, value FROM oc_order_option WHERE order_product_id = ${result['order_product_id']}");

                $optionValueIds = implode(',', array_column($options, 'product_option_value_id'));

                if ($optionValueIds && $db->exec("SHOW COLUMNS FROM oc_product_option_value WHERE field='sub_sku'")) {
                    $query = $db->exec("SELECT product_id, product_option_id, product_option_value_id, sub_sku FROM oc_product_option_value WHERE product_option_value_id in (${optionValueIds}) AND sub_sku != '' AND sub_sku is not null");
                    if ($query) {
                        $model = $query[0]['sub_sku'];
                        $poip = [
                            'product_id' => $query[0]['product_id'],
                            'product_option_id' => $query[0]['product_option_id'],
                            'product_option_value_id' => $query[0]['product_option_value_id'],
                        ];
                        $optionImage = $db->exec("SELECT image FROM oc_poip_option_image WHERE product_id=${poip['product_id']} AND product_option_id=${poip['product_option_id']} AND product_option_value_id=${poip['product_option_value_id']} ORDER BY sort_order LIMIT 1");
                        if ($optionImage) {
                            $image = $f3->get('ROOT') . '/image/' . $optionImage[0]['image'];
                        }
                    }
                }

                if (!isset($image)) {
                    list($product) = $db->exec("SELECT image FROM oc_product WHERE product_id=${result['product_id']}");
                    $image = $product['image'];
                }

                if (file_exists($image)) {
                    $info = pathinfo($image);
                    $thumb = '/tmp/' . str_replace('.' . $info['extension'], '_thumb.' . $info['extension'], $info['basename']);
                    if (!file_exists($thumb)) {
                        try{
                            $img = new \Gmagick($image);
                            $img->stripimage();
                            $img->setCompressionQuality(75);
                            $img->thumbnailimage(100, 100, true);
                            $img->writeimage($thumb);
                            $img->destroy();
                            unset($img);
                        } catch(\Exception $e) {
                            $f3->set('UI', $info['dirname']);
                            $img = new \Image('/' . $info['basename']);
                            $img->resize(100, 100, false);
                            $img->dump('jpeg', $thumb);
                            $img->__destruct();
                            unset($img);
                        }
                    }
                }

                foreach ($options as &$option) {
                    unset($option['product_option_value_id']);
                }

                $iterator->next();
                $cell = $iterator->current()->getCellIterator();
                $cell->current()->setValue($result['order_id'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue(isset($model) ? $model : $result['model'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['price'] * $result['currency_value'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['quantity'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['pt'] * $result['currency_value'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['shipping'] * $result['currency_value'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['total'] * $result['currency_value'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['currency'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['date_added'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['firstname'] . ' ' . $result['lastname'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['company'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['address1'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['address2'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['country'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['zone'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['city'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['postcode'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['telephone'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['email'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['comment'])->setDataType(DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue(json_encode($options))->setDataType(DataType::TYPE_STRING);
                if (isset($thumb) && file_exists($thumb)) {
                    $cell->next();
                    $objDrawing = new Drawing();
                    $objDrawing->setPath($thumb);
                    $objDrawing->setHeight(100);
                    $objDrawing->setCoordinates($cell->current()->getCoordinate());
                    $objDrawing->getShadow()->setVisible(true);
                    $objDrawing->setWorksheet($spreadsheet->getActiveSheet());
                    $spreadsheet->getActiveSheet()->getRowDimension($iterator->current()->getRowIndex())->setRowHeight(100);
                }
            }

            $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('O')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('P')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('R')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('S')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('T')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('U')->setAutoSize(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('V')->setWidth(25);

            $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');

            $filename = 'order_' . date('Ymd_') . time() . '.xlsx';

            $objWriter->save($f3->get('ROOT') . '/data/' . $filename);

            $f3->log($db->log());

            echo $filename;
        } else {
            echo 'invalid';
        }
    }
}
