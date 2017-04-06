<?php
namespace app\api;

use data\Database;
use helper\Bridge;

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
SELECT o.order_id, p.order_product_id, p.model, p.price, p.quantity, p.total AS pt, (SELECT ot.value FROM oc_order_total ot WHERE ot.order_id = o.order_id AND ot.code = 'shipping') AS shipping, o.total, o.currency_code AS currency, date_added, shipping_firstname AS firstname, shipping_lastname AS lastname, shipping_address_1 AS address1, shipping_address_2 AS address2, shipping_country AS country, shipping_zone AS zone, shipping_city AS city, shipping_postcode AS postcode, telephone, email, comment FROM oc_order o LEFT JOIN oc_order_product p ON o.order_id = p.order_id WHERE o.order_id in (${orders});
SQL;
            $results = $db->exec($sql);

            $objPHPExcel = new \PHPExcel();

            $objPHPExcel->getProperties()->setCreator("Auto Generated")
                ->setTitle("Order Info")
                ->setSubject("Order Info")
                ->setDescription("Order Info");

            $objPHPExcel->setActiveSheetIndex(0)
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
                ->setCellValue('K1', 'Address 1')
                ->setCellValue('L1', 'Address 2')
                ->setCellValue('M1', 'Country')
                ->setCellValue('N1', 'Region/State')
                ->setCellValue('O1', 'City')
                ->setCellValue('P1', 'Postcode')
                ->setCellValue('Q1', 'Telephone')
                ->setCellValue('R1', 'Email')
                ->setCellValue('S1', 'Comment')
                ->setCellValue('T1', 'Option');

            $iterator = $objPHPExcel->getActiveSheet()->getRowIterator(1);

            foreach ($results as $result) {
                $options = $db->exec("SELECT * FROM oc_order_option WHERE order_product_id = ${result['order_product_id']}");

                $optionNames = json_encode(array_column($options, 'name'));

                $optionValueIds = implode(',', array_column($options, 'product_option_value_id'));

                if ($optionValueIds && $db->exec("SHOW COLUMNS FROM oc_product_option_value WHERE field='sub_sku'")) {
                    $query = $db->exec("SELECT sub_sku FROM oc_product_option_value WHERE product_option_value_id in (${optionValueIds}) AND sub_sku != '' AND sub_sku is not null");
                    if ($query) $model = $query[0]['sub_sku'];
                }

                $iterator->next();
                $cell = $iterator->current()->getCellIterator();
                $cell->current()->setValue($result['order_id'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue(isset($model) ? $model : $result['model'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['price'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['quantity'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['pt'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['shipping'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['total'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['currency'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['date_added'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['firstname'] . ' ' . $result['lastname'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['address1'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['address2'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['country'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['zone'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['city'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['postcode'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['telephone'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($result['comment'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
                $cell->next();
                $cell->current()->setValue($optionNames)->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
            }

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setAutoSize(true);

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            $filename = 'order_' . date('Ymd_') . time() . '.xlsx';

            $objWriter->save($f3->get('ROOT') . '/data/' . $filename);

            $f3->log($db->log());

            echo $filename;
        } else {
            echo 'invalid';
        }
    }
}