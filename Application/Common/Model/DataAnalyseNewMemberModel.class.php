<?php

namespace Common\Model;

class DataAnalyseNewMemberModel extends BaseModel
{

    // 定义表名
    protected $tableName = 'mb_data_analyse_newmember';

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        // 正式环境需要切换到从数据库读取数据
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

    public function getNewMemberData($startTime, $endTime, $date_array, $store_id, $timePeriod)
    {
        $condition = array();
        $condition['start_time'] = [['egt', $startTime], ['elt', $endTime]];
        if (!isInSuper()) {
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

            $sum_data_new_member = 0;


            foreach ($record_data as $item_key => $item_val) {
                if (($item_val['start_time'] >= strtotime($val)) && ($item_val['start_time'] < (strtotime($val) + $timePeriod))) {
                    $sum_data_new_member += $item_val['new_member_num'];
                }
            }

            $sum_data_array[] = $sum_data_new_member;
            $app_data_array[] = 0;
            $wx_data_array[] = $sum_data_new_member;
            $wap_data_array[] = 0;
            $pc_data_array[] = 0;


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