<?php

namespace Common\Model;

class DataAnalyseOrderModel extends BaseModel
{

    // 定义表名
    protected $tableName = 'mb_data_analyse_order';

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        if (isFormal()) {
            $this->connection = array(
                'db_type' => 'mysql',
                'db_user' => 'xunxin_remote',
                'db_pwd' => 'HfyHI6Xs1n6U',
                'db_host' => '123.207.100.137',
                'db_port' => '3306',
                'db_name' => 'xunxin_db',
                'db_charset' => 'utf8',
                'db_params' => array(), // 非必须
            );
        }
        parent::__construct($name, $tablePrefix, $connection);
    }

    public function getOrderSalesData($startTime, $endTime, $date_array, $store_id, $timePeriod, $type = 0)
    {
        $condition = array();
        $condition['start_time'] = [['egt', $startTime], ['elt', $endTime]];
        if ($type == 1) {
            $condition['channel_id'] = $store_id;
        } else {
            $condition['store_id'] = $store_id;
        }
        $record_data = $this
            ->where($condition)
            ->select();
        $sum_data_array = [];
        $app_data_array = [];
        $wx_data_array = [];
        $wap_data_array = [];
        $pc_data_array = [];

        foreach ($date_array as $key => $val) {

            $app_order_sales = 0;
            $wx_order_sales = 0;
            $wap_order_sales = 0;
            $pc_order_sales = 0;

            foreach ($record_data as $item_key => $item_val) {

                if (($item_val['start_time'] >= strtotime($val)) && ($item_val['start_time'] < (strtotime($val) + $timePeriod))) {
                    if (strtolower($item_val['client_type']) == 'android' || strtolower($item_val['client_type']) == 'ios') {
                        $app_order_sales += $item_val['sales_price'];

                    } else if (($item_val['client_type'] == 'wap') || empty($item_val['client_type'])) {
                        $wx_order_sales += $item_val['sales_price'];

                    } else if ($item_val['client_type'] == 'web') {
                        $wap_order_sales += $item_val['sales_price'];

                    } else if ($item_val['client_type'] == 'pc') {
                        $pc_order_sales += $item_val['sales_price'];

                    }

                }
            }

            $sum_order_sales = $app_order_sales + $wx_order_sales + $wap_order_sales + $pc_order_sales;

            $sum_data_array[] = $sum_order_sales;
            $app_data_array[] = $app_order_sales;
            $wx_data_array[] = $wx_order_sales;
            $wap_data_array[] = $wap_order_sales;
            $pc_data_array[] = $pc_order_sales;
        }
        $returnData = array();
        $returnData['sum_data_array'] = $sum_data_array;
        $returnData['app_data_array'] = $app_data_array;
        $returnData['wx_data_array'] = $wx_data_array;
        $returnData['wap_data_array'] = $wap_data_array;
        $returnData['pc_data_array'] = $pc_data_array;


        return getReturn(200, "成功", $returnData);
    }


    public function getOrderNumData($startTime, $endTime, $date_array, $store_id, $timePeriod, $type = 0)
    {
        $condition = array();
        $condition['start_time'] = [['egt', $startTime], ['elt', $endTime]];
        if ($type == 1) {
            $condition['channel_id'] = $store_id;
        } else {
            $condition['store_id'] = $store_id;
        }
        $record_data = $this
            ->where($condition)
            ->select();
        $sum_data_array = [];
        $app_data_array = [];
        $wx_data_array = [];
        $wap_data_array = [];
        $pc_data_array = [];

        foreach ($date_array as $key => $val) {

            $app_order_num = 0;
            $wx_order_num = 0;
            $wap_order_num = 0;
            $pc_order_num = 0;

            foreach ($record_data as $item_key => $item_val) {
                if (($item_val['start_time'] >= strtotime($val)) && ($item_val['start_time'] < (strtotime($val) + $timePeriod))) {
                    if (strtolower($item_val['client_type']) == 'android' || strtolower($item_val['client_type']) == 'ios') {
                        $app_order_num += $item_val['order_num'];
                    } else if (($item_val['client_type'] == 'wap') || empty($item_val['client_type'])) {
                        $wx_order_num += $item_val['order_num'];
                    } else if ($item_val['client_type'] == 'web') {
                        $wap_order_num += $item_val['order_num'];
                    } else if ($item_val['client_type'] == 'pc') {
                        $pc_order_num += $item_val['order_num'];
                    }
                }
            }

            $sum_order_num = $app_order_num + $wx_order_num + $wap_order_num + $pc_order_num;

            $sum_data_array[] = $sum_order_num;
            $app_data_array[] = $app_order_num;
            $wx_data_array[] = $wx_order_num;
            $wap_data_array[] = $wap_order_num;
            $pc_data_array[] = $pc_order_num;
        }
        $returnData = array();
        $returnData['sum_data_array'] = $sum_data_array;
        $returnData['app_data_array'] = $app_data_array;
        $returnData['wx_data_array'] = $wx_data_array;
        $returnData['wap_data_array'] = $wap_data_array;
        $returnData['pc_data_array'] = $pc_data_array;
        return getReturn(200, "成功", $returnData);
    }


    public function getOrderUnitPriceData($startTime, $endTime, $date_array, $store_id, $timePeriod, $type = 0)
    {
        $condition = array();
        $condition['start_time'] = [['egt', $startTime], ['elt', $endTime]];
        if ($type == 1) {
            $condition['channel_id'] = $store_id;
        } else {
            $condition['store_id'] = $store_id;
        }
        $record_data = $this
            ->where($condition)
            ->select();
        $sum_data_array = [];
        $app_data_array = [];
        $wx_data_array = [];
        $wap_data_array = [];
        $pc_data_array = [];

        foreach ($date_array as $key => $val) {


            $app_order_sales = 0;
            $wx_order_sales = 0;
            $wap_order_sales = 0;
            $pc_order_sales = 0;

            $app_order_num = 0;
            $wx_order_num = 0;
            $wap_order_num = 0;
            $pc_order_num = 0;

            foreach ($record_data as $item_key => $item_val) {
                if (($item_val['start_time'] >= strtotime($val)) && ($item_val['start_time'] < (strtotime($val) + $timePeriod))) {
                    if (strtolower($item_val['client_type']) == 'android' || strtolower($item_val['client_type']) == 'ios') {
                        $app_order_sales += $item_val['sales_price'];
                        $app_order_num += $item_val['order_num'];
                    } else if ($item_val['client_type'] == 'wap') {
                        $wx_order_sales += $item_val['sales_price'];
                        $wx_order_num += $item_val['order_num'];
                    } else if (($item_val['client_type'] == 'web') || empty($item_val['client_type'])) {
                        $wap_order_sales += $item_val['sales_price'];
                        $wap_order_num += $item_val['order_num'];
                    } else if ($item_val['client_type'] == 'pc') {
                        $pc_order_sales += $item_val['sales_price'];
                        $pc_order_num += $item_val['order_num'];
                    }
                }
            }

            $sum_order_num = $app_order_num + $wx_order_num + $wap_order_num + $pc_order_num;
            $sum_order_sales = $app_order_sales + $wx_order_sales + $wap_order_sales + $pc_order_sales;
            if (empty($sum_order_num)) {
                $sum_order_unitprice = 0;
            } else {
                $sum_order_unitprice = round($sum_order_sales / $sum_order_num, 2);
            }

            if (empty($app_order_num)) {
                $app_order_unitprice = 0;
            } else {
                $app_order_unitprice = round($app_order_sales / $app_order_num, 2);
            }

            if (empty($wx_order_num)) {
                $wx_order_unitprice = 0;
            } else {
                $wx_order_unitprice = round($wx_order_sales / $wx_order_num, 2);
            }

            if (empty($wap_order_num)) {
                $wap_order_unitprice = 0;
            } else {
                $wap_order_unitprice = round($wap_order_sales / $wap_order_num, 2);
            }

            if (empty($pc_order_num)) {
                $pc_order_unitprice = 0;
            } else {
                $pc_order_unitprice = round($pc_order_sales / $pc_order_num, 2);
            }


            $sum_data_array[] = $sum_order_unitprice;
            $app_data_array[] = $app_order_unitprice;
            $wx_data_array[] = $wx_order_unitprice;
            $wap_data_array[] = $wap_order_unitprice;
            $pc_data_array[] = $pc_order_unitprice;
        }
        $returnData = array();
        $returnData['sum_data_array'] = $sum_data_array;
        $returnData['app_data_array'] = $app_data_array;
        $returnData['wx_data_array'] = $wx_data_array;
        $returnData['wap_data_array'] = $wap_data_array;
        $returnData['pc_data_array'] = $pc_data_array;
        return getReturn(200, "成功", $returnData);
    }

}