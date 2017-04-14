<?php

namespace app\meta;

use app\common\AppBase;
use data\Database;

class Sort extends AppBase
{
    function get($f3)
    {
        $f3->set('title', 'Product sort order');
        echo \Template::instance()->render('meta/sort.html');
    }

    function download($f3)
    {
        $db = Database::mysql();
        $results = $db->exec("SELECT product_id, sort_order FROM oc_product WHERE sort_order = 0");

        $excel = new \PHPExcel();

        $excel->getProperties()->setCreator("Auto Generated")
            ->setTitle("Product Sort Order")
            ->setSubject("Product Sort Order")
            ->setDescription("Product Sort Order");

        $excel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'productId')
            ->setCellValue('B1', 'sortOrder');

        $iterator = $excel->getActiveSheet()->getRowIterator(1);

        foreach ($results as $result) {
            $iterator->next();
            $cell = $iterator->current()->getCellIterator();
            $cell->current()->setValue($result['product_id'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
            $cell->next();
            $cell->current()->setValue($result['sort_order'])->setDataType(\PHPExcel_Cell_DataType::TYPE_STRING);
        }

        $excel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $excel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);

        $objWriter = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');

        $filename = 'product_sort_download_' . date('Ymd_') . time() . '.xlsx';

        $objWriter->save($f3->get('ROOT') . '/data/' . $filename);

        echo $f3->get('BASE') . '/data/' . $filename;
    }

    function upload($f3)
    {
        $sqls = ['UPDATE oc_product SET sort_order = 1000'];

        if (isset($_FILES['sort'])) {
            $file = $f3->get('ROOT') . '/data/product_sort_upload_' . date('Ymd_') . time() . '.xlsx';
            file_put_contents($file, file_get_contents($_FILES['sort']['tmp_name']));

            try {
                $excel = \PHPExcel_IOFactory::load($file);

                $sheet = $excel->getSheet(0);

                $iterator = $sheet->getRowIterator(2);

                while ($iterator->valid()) {
                    $row = $iterator->current()->getRowIndex();
                    $productId = $sheet->getCellByColumnAndRow(0, $row);
                    $sortOrder = $sheet->getCellByColumnAndRow(1, $row) ?? 0;
                    if (!empty($sortOrder)) {
                        $sqls[] = 'UPDATE oc_product SET sort_order = ' . $sortOrder . ' WHERE product_id = ' . $productId;
                    }
                    $iterator->next();
                }

                Database::mysql()->exec($sqls);
                echo 'success';
            } catch (\Exception $e) {
                var_dump($e);
                echo 'failure';
            }
        } else {
            echo 'failure';
        }
    }
}
