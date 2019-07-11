<?php

namespace Common\Model;


class MergeOrderModel extends BaseModel
{
    protected $tableName = 'mb_mergeorder';


    public function addMergeOrder($mallstore_order_array)
    {
        $mergeorder_max = $this->max('version');
        $mergeorder_data = array();
        if ($mallstore_order_array['pay_type'] == 0 || $mallstore_order_array['pay_type'] == 3) {
            $mergeorder_data['order_state'] = 0;
        } else {
            $mergeorder_data['order_state'] = 6;
        }
        if ($mallstore_order_array['pay_type'] == 0) {
            $mallstore_order_array['pay_name'] = "线下付款";
        } else if ($mallstore_order_array['pay_type'] == 1) {
            $mallstore_order_array['pay_name'] = "微信支付";
        } else if($mallstore_order_array['pay_type'] == 3) {
            $mallstore_order_array['pay_name'] = '余额支付';
        }else if ($mallstore_order_array['pay_type'] == 4) {
            $mallstore_order_array['pay_name'] = '支付宝支付';
        }
        $mergeorder_data['create_time'] = TIMESTAMP;
        $mergeorder_data['buyer_id'] = $mallstore_order_array['buyer_id'];
        $mergeorder_data['totalprice'] = $mallstore_order_array['total_price'];
        $mergeorder_data['order_content'] = json_encode($mallstore_order_array['mergeorder_content'], JSON_UNESCAPED_UNICODE);
        $mergeorder_data['version'] = $mergeorder_max + 1;
        $mergeorder_data['channel_id'] = $mallstore_order_array['channel_id'];
        $mergeorder_data['pay_type'] = $mallstore_order_array['pay_type'];
        $mergeorder_data['pay_name'] = $mallstore_order_array['pay_name'];
        $mergeorder_data['balance'] = $mallstore_order_array['balance'];
        $mergeorder_data['platform_balance'] = $mallstore_order_array['platform_balance'];
        $mergeorder_data['merge_store_id'] = $mallstore_order_array['merge_store_id'];
        $mergeorder_data['use_platform_coupons'] = $mallstore_order_array['use_platform_coupons'];
        $mergeorder_data['platform_coupons_id'] = $mallstore_order_array['platform_coupons_id'];
        $mergeorder_data['platform_coupons_info'] = $mallstore_order_array['platform_coupons_info'];
        $mergeorder_data['platform_coupons_money'] = $mallstore_order_array['platform_coupons_money'];
        $mergeorder_data['platform_credits_num'] = $mallstore_order_array['platform_credits_num'];
        $mergeorder_data['platform_credits_exmoney'] = $mallstore_order_array['platform_credits_exmoney'];
        $mergeorder_id = $this->insert($mergeorder_data, true);
        if ($mergeorder_id === false) {
           return getReturn(-1, "添加合并订单数据失败");
        } else {
            $morder_id = "mg_" . $mergeorder_id;
            $tag = Model("mb_mergeorder")->where(array('id' => $mergeorder_id))->update(array('morder_id' => $morder_id));
            if ($tag === false) {
                return getReturn(-2, "更新合并订单编号失败");
            }
            $mergeorder_data['morder_id'] = $morder_id;
            $mergeorder_data['mergeorder_id'] = $mergeorder_id;
        }
        return getReturn(200, "成功", $mergeorder_data);
    }



}