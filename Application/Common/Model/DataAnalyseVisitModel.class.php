<?php

namespace Common\Model;

class DataAnalyseVisitModel extends BaseModel
{

    // 定义表名
    protected $tableName = 'mb_data_analyse_visit';

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

    public function getVisitData($startTime, $endTime, $date_array, $store_id, $timePeriod, $type = 0)
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

            $app_visit_num = 0;
            $wx_visit_num = 0;
            $wap_visit_num = 0;
            $pc_visit_num = 0;

            foreach ($record_data as $item_key => $item_val) {
                if (($item_val['start_time'] >= strtotime($val)) && ($item_val['start_time'] < (strtotime($val) + $timePeriod))) {
                    if (strtolower($item_val['terminal_type']) == 'android' || strtolower($item_val['terminal_type']) == 'ios') {
                        $app_visit_num += $item_val['visit_num'];
                    } else if (($item_val['terminal_type'] == 'member_wap') || empty($item_val['terminal_type'])) {
                        $wx_visit_num += $item_val['visit_num'];
                    } else if ($item_val['terminal_type'] == 'member_web') {
                        $wap_visit_num += $item_val['visit_num'];
                    } else if ($item_val['terminal_type'] == 'member_pc') {
                        $pc_visit_num += $item_val['visit_num'];
                    }
                }
            }

            $sum_visit_num = $app_visit_num + $wx_visit_num + $wap_visit_num + $pc_visit_num;


            $sum_data_array[] = $sum_visit_num;
            $app_data_array[] = $app_visit_num;
            $wx_data_array[] = $wx_visit_num;
            $wap_data_array[] = $wap_visit_num;
            $pc_data_array[] = $pc_visit_num;


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