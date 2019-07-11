<?php

namespace Common\Model;

class DataAnalyseBagRateModel extends BaseModel
{

    // 定义表名
    protected $tableName = 'mb_data_analyse_bag_rate';

    /**
     *
     * DataAnalyseBagRateModel constructor.
     * @param string $name
     * @param string $tablePrefix
     * @param string $connection
     */
    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        // 正式环境需要读取从数据库
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

    public function getBagRateData($startTime, $endTime, $date_array, $store_id, $timePeriod, $type = 0)
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


            $app_visit_member_num = 0;
            $app_order_member_num = 0;

            $wx_visit_member_num = 0;
            $wx_order_member_num = 0;

            $wap_visit_member_num = 0;
            $wap_order_member_num = 0;

            $pc_visit_member_num = 0;
            $pc_order_member_num = 0;

            foreach ($record_data as $item_key => $item_val) {
                if (($item_val['start_time'] >= strtotime($val)) && ($item_val['start_time'] < (strtotime($val) + $timePeriod))) {
                    if (strtolower($item_val['client_type']) == 'android' || strtolower($item_val['client_type']) == 'ios') {
                        $app_visit_member_num = $item_val['visit_member_num'];
                        $app_order_member_num = $item_val['order_member_num'];
                    } else if ($item_val['client_type'] == 'wap' || empty($item_val['client_type'])) {
                        $wx_visit_member_num = $item_val['visit_member_num'];
                        $wx_order_member_num = $item_val['order_member_num'];
                    } else if ($item_val['client_type'] == 'web') {
                        $wap_visit_member_num = $item_val['visit_member_num'];
                        $wap_order_member_num = $item_val['order_member_num'];
                    } else if ($item_val['client_type'] == 'pc') {
                        $pc_visit_member_num = $item_val['visit_member_num'];
                        $pc_order_member_num = $item_val['order_member_num'];
                    }
                }
            }

            $sum_visit_member_num = $app_visit_member_num + $wx_visit_member_num + $wap_visit_member_num + $pc_visit_member_num;
            $sum_order_member_num = $app_order_member_num + $wx_order_member_num + $wap_order_member_num + $pc_order_member_num;

            if (empty($sum_visit_member_num)) {
                $sum_data_rate = 0;
            } else {
                $sum_data_rate = round($sum_order_member_num / $sum_visit_member_num, 2);
            }

            if (empty($app_visit_member_num)) {
                $app_data_rate = 0;
            } else {
                $app_data_rate = round($app_order_member_num / $app_visit_member_num, 2);
            }

            if (empty($wx_visit_member_num)) {
                $wx_data_rate = 0;
            } else {
                $wx_data_rate = round($wx_order_member_num / $wx_visit_member_num, 2);
            }

            if (empty($wap_visit_member_num)) {
                $wap_data_rate = 0;
            } else {
                $wap_data_rate = round($wap_order_member_num / $wap_visit_member_num, 2);
            }

            if (empty($pc_visit_member_num)) {
                $pc_data_rate = 0;
            } else {
                $pc_data_rate = round($pc_order_member_num / $pc_visit_member_num, 2);
            }
            $sum_data_array[] = $sum_data_rate * 100;
            $app_data_array[] = $app_data_rate * 100;
            $wx_data_array[] = $wx_data_rate * 100;
            $wap_data_array[] = $wap_data_rate * 100;
            $pc_data_array[] = $pc_data_rate * 100;


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