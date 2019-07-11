<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2017/4/26
 * Time: 15:35
 */

namespace Common\Model;
/**
 * 日结
 * Class DailyOrderRecordModel
 * @package Common\Model
 * User: czx
 * Date: 2019-6-26 11:04:06
 * Update: 2019-6-26 11:04:06
 * Version: 1.00
 */
class DailyOrderRecordModel extends BaseModel
{
    protected $tableName = 'mb_daily_order_record';

    public function saveCashDailyOrderRecord($recordData)
    {
        foreach ($recordData as $store_id => $value) {
            foreach ($value as $create_time_str => $data_cash) {
                if (empty($create_time_str)) continue;
                $order_time = strtotime($create_time_str);
                foreach ($data_cash as $cashier_id => $data_content) {
                    $where = array();
                    $where['store_id'] = $store_id;
                    $where['order_time'] = $order_time;
                    $where['come_type'] = 0;
                    $existData = $this->where($where)->find();
                    if (!empty($existData)) {
                        continue;
                    }
                    $insertData = array();
                    $insertData['order_num'] = emptyToZero($data_content['order_num']);
                    if (empty($data_content['order_id_content'])) {
                        $insertData['order_id_content'] = '';
                    } else {
                        $insertData['order_id_content'] = json_encode($data_content['order_id_content']);
                    }
                    $insertData['create_time'] = time();
                    $insertData['come_type'] = 0;
                    if (empty($data_content['refund_order_id_content'])) {
                        $insertData['refund_order_id_content'] = '';
                    } else {
                        $insertData['refund_order_id_content'] = json_encode($data_content['refund_order_id_content']);
                    }
                    $insertData['history_refund_amount_online'] = emptyToZero($data_content['history_refund_amount_online']);
                    $insertData['history_refund_amount'] = emptyToZero($data_content['history_refund_amount']);
                    $insertData['history_refund_num'] = emptyToZero($data_content['history_refund_num']);
                    $insertData['today_refund_amount_online'] = emptyToZero($data_content['today_refund_amount_online']);
                    $insertData['today_refund_amount'] = emptyToZero($data_content['today_refund_amount']);
                    $insertData['today_refund_num'] = emptyToZero($data_content['today_refund_num']);
                    $insertData['odd_change'] = emptyToZero($data_content['odd_change']);
                    $insertData['balance_money'] = emptyToZero($data_content['balance_money']);
                    $insertData['credits_money'] = emptyToZero($data_content['credits_money']);
                    $insertData['wx_charge_account_online'] = emptyToZero($data_content['wx_charge_account_online']);
                    $insertData['ali_charge_account_online'] = emptyToZero($data_content['ali_charge_account_online']);
                    $insertData['bank_charge_account_online'] = emptyToZero($data_content['bank_charge_account_online']);
                    $insertData['wx_charge_account'] = emptyToZero($data_content['wx_charge_account']);
                    $insertData['ali_charge_account'] = emptyToZero($data_content['ali_charge_account']);
                    $insertData['bank_charge_account'] = emptyToZero($data_content['bank_charge_account']);
                    $insertData['cash_charge_account'] = emptyToZero($data_content['cash_charge_account']);
                    $insertData['store_reduce_amount'] = emptyToZero($data_content['store_reduce_amount']);
                    $insertData['coupons_amount'] = emptyToZero($data_content['coupons_amount']);
                    $insertData['order_amount'] = emptyToZero($data_content['order_amount']);
                    $insertData['free_order_amount'] = emptyToZero($data_content['free_order_amount']);
                    $insertData['store_id'] = $store_id;
                    $insertData['thirdpart_momey'] = emptyToZero($data_content['thirdpart_momey']);
                    $insertData['order_time'] = $order_time;
                    $insertData['cashier_id'] = $cashier_id;
                    $resultData = $this->add($insertData);
                    if ($resultData === false) {
                        return false;
                    }
                }

            }
        }
        return true;
    }

    public function saveShopDailyOrderRecord($recordData)
    {
        foreach ($recordData as $store_id => $value) {
            foreach ($value as $create_time_str => $come_data) {
                foreach ($come_data as $come_type => $data_content) {
                    if (empty($create_time_str)) continue;
                    $order_time = strtotime($create_time_str);
                    $where = array();
                    $where['store_id'] = $store_id;
                    $where['order_time'] = $order_time;
                    $where['come_type'] = $come_type;
                    $existData = $this->where($where)->find();
                    if (!empty($existData)) {
                        continue;
                    }
                    $insertData = array();
                    $insertData['order_num'] = emptyToZero($data_content['order_num']);
                    if (empty($data_content['order_id_content'])) {
                        $insertData['order_id_content'] = '';
                    } else {
                        $insertData['order_id_content'] = json_encode($data_content['order_id_content']);
                    }
                    $insertData['create_time'] = time();
                    $insertData['come_type'] = $come_type;
                    if (empty($data_content['refund_order_id_content'])) {
                        $insertData['refund_order_id_content'] = '';
                    } else {
                        $insertData['refund_order_id_content'] = json_encode($data_content['refund_order_id_content']);
                    }
                    $insertData['history_refund_amount_online'] = emptyToZero($data_content['history_refund_amount_online']);
                    $insertData['history_refund_amount'] = emptyToZero($data_content['history_refund_amount']);
                    $insertData['history_refund_num'] = emptyToZero($data_content['history_refund_num']);
                    $insertData['today_refund_amount_online'] = emptyToZero($data_content['today_refund_amount_online']);
                    $insertData['today_refund_amount'] = emptyToZero($data_content['today_refund_amount']);
                    $insertData['today_refund_num'] = emptyToZero($data_content['today_refund_num']);
                    $insertData['odd_change'] = emptyToZero($data_content['odd_change']);
                    $insertData['balance_money'] = emptyToZero($data_content['balance_money']);
                    $insertData['credits_money'] = emptyToZero($data_content['credits_money']);
                    $insertData['wx_charge_account_online'] = emptyToZero($data_content['wx_charge_account_online']);
                    $insertData['ali_charge_account_online'] = emptyToZero($data_content['ali_charge_account_online']);
                    $insertData['bank_charge_account_online'] = emptyToZero($data_content['bank_charge_account_online']);
                    $insertData['wx_charge_account'] = emptyToZero($data_content['wx_charge_account']);
                    $insertData['ali_charge_account'] = emptyToZero($data_content['ali_charge_account']);
                    $insertData['bank_charge_account'] = emptyToZero($data_content['bank_charge_account']);
                    $insertData['cash_charge_account'] = emptyToZero($data_content['cash_charge_account']);
                    $insertData['store_reduce_amount'] = emptyToZero($data_content['store_reduce_amount']);
                    $insertData['coupons_amount'] = emptyToZero($data_content['coupons_amount']);
                    $insertData['order_amount'] = emptyToZero($data_content['order_amount']);
                    $insertData['free_order_amount'] = emptyToZero($data_content['free_order_amount']);
                    $insertData['store_id'] = $store_id;
                    $insertData['thirdpart_momey'] = emptyToZero($data_content['thirdpart_momey']);
                    $insertData['order_time'] = $order_time;
                    $resultData = $this->add($insertData);
                    if ($resultData === false) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
}   