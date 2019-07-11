<?php

namespace Common\Model;

class CashOrderModel extends BaseModel
{
    protected $tableName = 'mb_cashorder';


    public function addOrderData($mallstore_order_array, $storeorder, $orderversion_max, $device_id)
    {
        $insertData = array();
        $insertData['order_sn'] = $mallstore_order_array['order_sn'];
        $insertData['storeid'] = $storeorder['storeid'];
        $insertData['buyer_id'] = $mallstore_order_array['buyer_id'];
        $insertData['order_actual_amount'] = $mallstore_order_array['order_actual_amount'];
        $insertData['order_amount'] =  empty($storeorder['total_amount']) ? 0 : $storeorder['total_amount'];
        $insertData['receive_amount'] = empty($storeorder['receive_amount']) ? 0 : $storeorder['receive_amount'];

        $insertData['create_time'] = TIMESTAMP;
        $insertData['order_state'] = 1;
        $insertData['store_name'] = $mallstore_order_array['store_name'];
        $insertData['member_name'] = $mallstore_order_array['member_name'];
        $insertData['member_tel'] = empty($mallstore_order_array['member_tel']) ? '' : $mallstore_order_array['member_tel'];
        $insertData['store_discount_amount'] = empty($mallstore_order_array['store_discount_amount']) ? 0 : $mallstore_order_array['store_discount_amount'];
        $insertData['store_discount'] = empty($mallstore_order_array['store_discount']) ? 0 : $mallstore_order_array['store_discount'];
        $insertData['member_discount_amount'] = empty($mallstore_order_array['member_discount_amount']) ? 0 : $mallstore_order_array['member_discount_amount'];
        $insertData['member_discount'] = empty($mallstore_order_array['member_discount']) ? 0 : $mallstore_order_array['member_discount'];
        $insertData['credits_num'] = empty($storeorder['credits_num']) ? 0 : $storeorder['credits_num'];
        $insertData['credits_exmoney'] = empty($storeorder['credits_exmoney']) ? 0 : $storeorder['credits_exmoney'];
        $insertData['platform_credits_num'] = empty($storeorder['platform_credits_num']) ? 0 : $storeorder['platform_credits_num'];
        $insertData['platform_credits_exmoney'] = empty($storeorder['platform_credits_exmoney']) ? 0 : $storeorder['platform_credits_exmoney'];
        $insertData['thirdpart_momey'] = empty($storeorder['thirdpart_money']) ? 0 : $storeorder['thirdpart_money'];
        $insertData['balance'] = empty($storeorder['balance']) ? 0 : $storeorder['balance'];
        $insertData['platform_balance'] = empty($storeorder['platform_balance']) ? 0 : $storeorder['platform_balance'];
        $insertData['use_coupons'] = empty($storeorder['use_coupons']) ? 0 : $storeorder['use_coupons'];
        $insertData['coupons_id'] = empty($storeorder['coupons_id']) ? 0 :$storeorder['coupons_id'];
        $insertData['coupons_exchange_money'] =  empty($storeorder['coupons_exmoney']) ? 0 : $storeorder['coupons_exmoney'];
        if (!empty($storeorder['gou_info'])){
            $insertData['coupons_content'] = json_encode($storeorder['gou_info'], JSON_UNESCAPED_UNICODE);
        }
        $insertData['use_platform_coupons'] = empty($mallstore_order_array['use_platform_coupons']) ? 0 : $mallstore_order_array['use_platform_coupons'];
        $insertData['platform_coupons_id'] = empty($mallstore_order_array['platform_coupons_id']) ? 0 : $mallstore_order_array['platform_coupons_id'];
        $insertData['platform_coupons_money'] = empty($mallstore_order_array['platform_coupons_money']) ? 0 : $mallstore_order_array['platform_coupons_money'];
        if (empty($mallstore_order_array['platform_coupons_content'])){
            $insertData['platform_coupons_content'] = json_encode($mallstore_order_array['platform_coupons_content'], JSON_UNESCAPED_UNICODE);
        }
        $insertData['store_reduce_money'] = empty($mallstore_order_array['store_reduce_money']) ? 0 :$mallstore_order_array['store_reduce_money'];
        $insertData['cash_charge_account'] = empty($mallstore_order_array['cash_charge_account']) ? 0 :$mallstore_order_array['cash_charge_account'];
        $insertData['bank_charge_account'] = empty($mallstore_order_array['bank_charge_account']) ? 0 : $mallstore_order_array['bank_charge_account'];
        $insertData['ali_charge_account'] = empty($mallstore_order_array['ali_charge_account']) ? 0 : $mallstore_order_array['ali_charge_account'];
        $insertData['wx_charge_account'] = empty($mallstore_order_array['wx_charge_account']) ? 0 :$mallstore_order_array['wx_charge_account'];
        $insertData['free_order'] = empty($mallstore_order_array['free_order']) ? 0 : $mallstore_order_array['free_order'];
        $insertData['free_order_reason'] = empty($mallstore_order_array['free_order_reason']) ? '' : $mallstore_order_array['free_order_reason'];
        $insertData['odd_change'] =  empty($mallstore_order_array['odd_change']) ? 0 : $mallstore_order_array['odd_change'];
        $insertData['order_content'] = json_encode($storeorder['order_content'], JSON_UNESCAPED_UNICODE);
        $insertData['version'] = $orderversion_max;
        $insertData['app_version'] = $mallstore_order_array['app_version'];
        $insertData['device_id'] = empty($device_id) ? '' : $device_id;
        $cashData = M('mb_cashier_bind')->where(array('device_id' => $insertData['device_id'], 'is_delete' => 0, 'store_id' => $storeorder['storeid']))->find();
        if (!empty($cashData)){
            $insertData['device_name'] = $cashData['name'];
            $insertData['cashier_id'] = $cashData['cashier_id'];
        }else{
            $insertData['device_name'] = '';
            $insertData['cashier_id'] = 0;
        }
        $insertData['pay_source'] = $mallstore_order_array['pay_source'];
        if ($mallstore_order_array['pay_source'] == 1 || $mallstore_order_array['pay_source'] == 2){
            $insertData['line_money'] = $insertData['bank_charge_account'] +  $insertData['ali_charge_account'] + $insertData['wx_charge_account'];
        }
        $insertData['order_msg'] = $mallstore_order_array['order_msg'];
        $insertData['pend_num_id'] = empty($mallstore_order_array['pend_num_id']) ? 0 : $mallstore_order_array['pend_num_id'];
        $insertData['pend_name'] = empty($mallstore_order_array['pend_name']) ? '' : $mallstore_order_array['pend_name'];

        $insertData['operationer'] = $mallstore_order_array['operationer'];
        $insertData['print_info'] = json_encode($this->getPrintInfo($insertData), JSON_UNESCAPED_UNICODE);
        $order_id = $this->add($insertData);
        if ($order_id === false) {
            return getReturn(-1, "插入订单失败");
        }
        $insertData['order_id'] = $order_id;
        return getReturn(200, "成功", $insertData);
    }

    public function getPrintInfo($insertData)
    {
        $print_info = array();
        $storeData = D("Store")->getStoreInfo2($insertData['storeid'])['data'];
        if (empty($storeData)) {
            return $print_info;
        }
        $channelData = D('Channel')->getChannelInfo($storeData['channel_id'])['data'];
        if (empty($channelData)) {
            return $print_info;
        }
        if ($channelData['store_type'] == 2) {
            $mainStoreData = M("store")->field('store_id')->where(array('channel_id' => $channelData['channel_id'], 'main_store' => 1))->find();
            $mainStoreMemberInfo = M("mb_storemember")
                ->where(array('store_id' => $mainStoreData['store_id'], 'member_id' => $insertData['buyer_id']))
                ->find();
            if (empty($mainStoreMemberInfo)) {
                return $print_info;
            }
        }

        $storeMemberInfo = M("mb_storemember")
            ->where(array('store_id' => $insertData['storeid'], 'member_id' => $insertData['buyer_id']))
            ->find();
        $memberInfo = M("member")->field('member_name,bindtel,member_nickname')->where(array('member_id' => $insertData['buyer_id']))->find();

        $rs_balance = $storeMemberInfo['balance'] - $insertData['balance'];
        $rs_credits_num = $storeMemberInfo['sum_score'] - $insertData['credits_num'];
        $rs_p_balance = $mainStoreMemberInfo['balance'] - $insertData['platform_balance'];
        $rs_p_credits_num = $mainStoreMemberInfo['sum_score'] - $insertData['platform_credits_num'];
        $print_info['member_name'] = $memberInfo['member_name'];
        $print_info['bindtel'] = empty($memberInfo['bindtel']) ? "":$memberInfo['bindtel'];
        $print_info['member_nickname'] = $memberInfo['member_nickname'];
        $print_info['rs_balance'] = $rs_balance;
        $print_info['rs_credits_num'] = $rs_credits_num;
        $print_info['rs_p_balance'] = $rs_p_balance;
        $print_info['rs_p_credits_num'] = $rs_p_credits_num;
        return $print_info;
    }

    public function addServerOrderData($orderData, $storeInfo, $orderversion_max)
    {
        $insertData = array();
        $insertData['order_sn'] = $orderData['order_sn'];
        $insertData['storeid'] = $orderData['storeId'];
        $insertData['order_actual_amount'] = $orderData['order_actual_amount'];
        $insertData['order_amount'] = $orderData['order_amount'];
        $insertData['receive_amount'] = $orderData['receive_amount'];
        $insertData['create_time'] = time();
        $insertData['order_state'] = 1;
        $insertData['store_name'] = $storeInfo['store_name'];
        $insertData['member_name'] = '';
        $insertData['member_tel'] = '';
        $insertData['store_discount_amount'] = $orderData['store_discount_amount'];
        $insertData['store_discount'] = $orderData['store_discount'];
        $insertData['member_discount_amount'] = 0;
        $insertData['member_discount'] = 0;
        $insertData['credits_num'] = 0;
        $insertData['credits_exmoney'] = 0;
        $insertData['platform_credits_num'] = 0;
        $insertData['platform_credits_exmoney'] = 0;
        $insertData['thirdpart_momey'] = 0;
        $insertData['balance'] = 0;
        $insertData['platform_balance'] = 0;
        $insertData['use_coupons'] = 0;
        $insertData['coupons_id'] = 0;
        $insertData['coupons_exchange_money'] = 0;
        $insertData['coupons_content'] = '';
        $insertData['use_platform_coupons'] = 0;
        $insertData['platform_coupons_id'] = 0;
        $insertData['platform_coupons_money'] = 0;
        $insertData['platform_coupons_content'] = '';
        $insertData['store_reduce_money'] = $orderData['store_reduce_money'];
        $insertData['cash_charge_account'] = $orderData['cash_charge_account'];
        $insertData['bank_charge_account'] = $orderData['bank_charge_account'];
        $insertData['ali_charge_account'] = $orderData['ali_charge_account'];
        $insertData['wx_charge_account'] = $orderData['wx_charge_account'];
        $insertData['free_order'] = $orderData['free_order'];
        $insertData['free_order_reason'] = $orderData['free_order_reason'];
        $insertData['odd_change'] = $orderData['odd_change'];
        $insertData['order_content'] = json_encode($orderData['order_content'], JSON_UNESCAPED_UNICODE);
        $insertData['version'] = $orderversion_max;
        if (empty($orderData['app_version'])) $orderData['app_version'] = 0;
        $insertData['app_version'] = $orderData['app_version'];
        $insertData['is_sync'] = 1;
        $insertData['device_id'] = empty($orderData['device_id']) ? '' : $orderData['device_id'];
        $cashData = M('mb_cashier_bind')->where(array('device_id' => $insertData['device_id'], 'is_delete' => 0, 'store_id' => $orderData['storeId']))->find();
        if (!empty($cashData)){
            $insertData['device_name'] = $cashData['name'];
            $insertData['cashier_id'] = $cashData['cashier_id'];
        }else{
            $insertData['device_name'] = '';
            $insertData['cashier_id'] = 0;
        }
        $insertData['pay_source'] = $orderData['pay_source'];
        if ($orderData['pay_source'] == 1 || $orderData['pay_source'] == 2){
            $insertData['line_money'] = $insertData['bank_charge_account'] +  $insertData['ali_charge_account'] + $insertData['wx_charge_account'];
        }
        $insertData['order_msg'] = $orderData['order_msg'];
        $insertData['pend_num_id'] = empty($orderData['pend_num_id']) ? 0 : $orderData['pend_num_id'];
        $insertData['pend_name'] = empty($orderData['pend_name']) ? '' : $orderData['pend_name'];
        $insertData['operationer'] = $orderData['operationer'];
        $order_id = $this->add($insertData);
        if ($order_id === false) {
            return getReturn(-1, "插入订单失败");
        }
        $insertData['order_id'] = $order_id;
        return getReturn(200, "成功", $insertData);
    }
}