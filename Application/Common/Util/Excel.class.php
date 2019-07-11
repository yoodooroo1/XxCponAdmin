<?php

namespace Common\Util;

class Excel extends Base
{

    const MAX_NUM = 5000;

    private $map; // 映射
    private $data; // 原始数据
    private $header; // 标题数组
    private $exportList; // 导出的数据
    private $fileName = ''; // 文件名称
    private $exportListNum; // 导出数据的数量

    /**
     * Excel constructor.
     * @param array $map
     * @param array $data
     */
    public function __construct($map = [], $data = [])
    {
        if (!empty($map) && !empty($data)) {
            $this->map = $map;
            $this->data = $data;
            $this->buildHeader();
            $this->buildExportList();
        }

    }

    public function setHeader($header)
    {
        $this->header = $header;
    }

    public function setExportList($list)
    {
        $this->exportList = $list;
        $this->exportListNum = count($this->exportList);
    }

    public function setFileName($name)
    {
        $this->fileName = $name;
    }

    public function buildHeader()
    {
        foreach ($this->map as $key => $value) {
            $this->header[] = $key;
        }
    }

    public function buildExportList()
    {
        $list = [];
        foreach ($this->data as $data) {
            $item = [];
            foreach ($this->map as $field) {
                $item[$field] = $data[$field];
            }
            $list[] = $item;
        }
        $this->setExportList($list);
    }

    public function isExceedLimit()
    {
        return $this->exportListNum >= self::MAX_NUM;
    }

    /**
     * 导出
     * User: hjun
     * Date: 2018-05-15 15:37:43
     * Update: 2018-05-15 15:37:43
     * Version: 1.00
     */
    public function export()
    {
        if ($this->isExceedLimit()) {
            $this->exportCsv();
        } else {
            $this->exportXls();
        }
    }

    public function exportXls()
    {
        vendor("PHPExcel.PHPExcel");
        $date = date("Y_m_d", time());
        $this->fileName .= "_{$date}.xls";
        //创建PHPExcel对象，注意，不能少了\
        $objPHPExcel = new \PHPExcel();
        $objProps = $objPHPExcel->getProperties();
        //设置表头
        $key = ord("A");
        $num = ord("A");
        foreach ($this->header as $v) {
            $colum = chr($key);
            if ($key - 65 >= 26) {
                $key = ord("A");
                $colum = chr($num) . chr($key);
                $num++;
            }
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);
            $key += 1;
        }
        $column = 2;
        $objActSheet = $objPHPExcel->getActiveSheet();
        foreach ($this->exportList as $key => $rows) { //行写入
            $span = ord("A");
            $num = ord("A");
            $start_ch = chr($span);
            foreach ($rows as $keyName => $value) {// 列写入
                $j = chr($span);
                if ($span - 65 >= 26) {
                    $span = ord("A");
                    $j = chr($num) . chr($span);
                    $num++;
                }
                $objActSheet->setCellValue($j . $column, $value);
                $objActSheet->getColumnDimension($j)->setWidth(25);
                $span++;
            }
            $end_ch = $j;
            $column++;
        }
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objActSheet->getStyle($start_ch . "1:" . $end_ch . "1")->applyFromArray(array('font' => array('bold' => true)));
        $objPHPExcel->getActiveSheet()->getStyle('C')->getNumberFormat()->setFormatCode("@");
        $fileName = iconv("utf-8", "gb2312", $this->fileName);
        //重命名表
        //$objPHPExcel->getActiveSheet()->setTitle('test');
        //设置活动单指数到第一个表,所以Excel打开这是第一个表
        $objPHPExcel->setActiveSheetIndex(0);
        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"$fileName\"");
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output'); //文件通过浏览器下载
        exit;
    }

    public function exportCsv()
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        $date = date("Y_m_d", time());
        $this->fileName .= "_{$date}.csv";
        ob_end_clean();
        ob_start();
        header("Content-Type: text/csv");
        header("Content-Disposition:filename=" . $this->fileName);
        $fp = fopen('php://output', 'w');
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fp, $this->header);
        $index = 0;
        foreach ($this->exportList as $item) {
            if ($index == 1000) {
                $index = 0;
                ob_flush();
                flush();
            }
            $index++;
            fputcsv($fp, $item);
        }

        ob_flush();
        flush();
        ob_end_clean();
        exit;
    }
}