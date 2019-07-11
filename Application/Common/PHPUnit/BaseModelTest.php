<?php

namespace Common\PHPUnit;

class BaseModelTest extends BaseTest
{
    public function testBase()
    {
        $store = M('store')->find(564);
        $this->assertEquals(564, $store['store_id']);
    }

    public function testDecode()
    {
        $json = file_get_contents(DATA_PATH . '/goods_info.txt');
        $json = filterToUTF8($json);
        $data = json_decode($json, true);
        if (empty($data)) {
            $code = json_last_error();
            $error = json_last_error_msg();
        }
    }

    public function testExcelColumn()
    {
        $column = getExcelColumn(0);
        $this->assertEquals($column, 'A');
        $column = getExcelColumn(5);
        $this->assertEquals($column, 'F');
        $column = getExcelColumn(25);
        $this->assertEquals($column, 'Z');
        $column = getExcelColumn(26);
        $this->assertEquals($column, 'AA');
        $column = getExcelColumn(27);
        $this->assertEquals($column, 'AB');
        $column = getExcelColumn(25 + 26 + 1);
        $this->assertEquals($column, 'BA');
        $column = getExcelColumn(25 + 26 * 26);
        $this->assertEquals($column, 'ZZ');
        $column = getExcelColumn(25 + 26 * 26 + 1);
        $this->assertEquals($column, 'AAA');
        $column = getExcelColumn(676);
    }

    public function testSort()
    {
        //
        $arr = [
            '0' => ['sort' => 0],
            '1' => ['sort' => '1'],
            '2' => ['sort' => '2'],
            '3' => ['sort' => '3'],
            '4' => ['sort' => '4'],
            '5' => ['sort' => '5'],
            '6' => ['sort' => '6'],
            '7' => ['sort' => '7'],
            '8' => ['sort' => '8'],
            '10' => ['sort' => '1'],
        ];
        $arr = [
            ['sort' => '0'],
            ['sort' => '1'],
            ['sort' => '0'],
        ];
        $result = array_sort($arr, 'sort', 'asc');
    }

    public function testCzx()
    {
        sig("777777", "");
    }

    public function testCollectImage()
    {
        $model = D('Goods')->collectGoodsImg(16963);
    }

}