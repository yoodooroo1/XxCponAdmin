<?php

namespace Common\Model;


class RefundGoodsModel extends BaseModel
{
    protected $tableName = 'mb_back_order_goods';


    public function getRefundTotalInfo($orderId)
    {
        $refundGoodData = $this->where(array('order_id' => $orderId))->select();
        $refundTotalData = array();
        $refundTotalData['refund_num'] = 0;
        $refundTotalData['refund_credit'] = 0;
        $refundTotalData['refund_other'] = 0;
        $refundTotalData['line_goods_price'] = 0;
        $refundTotalData['pv'] = 0;
        $refundTotalData['order_id'] = $orderId;
        $refundTotalData['back_goods_price'] = 0;
        foreach ($refundGoodData as $key => $val){
            $refundTotalData['back_goods_price'] = $refundGoodData['back_goods_price'] + $val['back_goods_price'];
            $refundTotalData['refund_num'] = $refundTotalData['refund_num'] + $val['back_num'];
            $refundTotalData['refund_credit'] = $refundTotalData['refund_credit'] + $val['credits_num'] + $val['platform_credits_num'];
            $refundTotalData['refund_other'] = $refundTotalData['refund_other'] + $val['platform_balance']
                + $val['thirdpart_momey'] + $val['balance'] + $val['coupons_exmoney'] + $val['platform_coupons_money'];
            $refundTotalData['line_goods_price'] = $refundTotalData['line_goods_price'] + $val['line_goods_price'];
            $refundTotalData['pv'] = $refundTotalData['pv'] + $val['goods_pv'];
        }
        return getReturn(200, '', $refundTotalData);
    }

    public function getRefundList($orderId)
    {
        $refundGoodData = $this->where(array('order_id' => $orderId))->select();

        foreach ($refundGoodData as $key => $val){
            $refundGoodData[$key]['total_reduce_coupons'] = $val['back_goods_price'] - $val['coupons_exmoney'] - $val['platform_coupons_money'];
            $refundGoodData[$key]['total_other'] = $val['platform_balance']
                + $val['thirdpart_momey'] + $val['balance'] ;
            $refundGoodData[$key]['total_credit'] = $val['credits_num'] + $val['platform_credits_num'];
            if ($val['pay_type'] == 0){
                $refundGoodData[$key]['refund_pay_type'] = "线下付款";
                $refundGoodData[$key]['refund_line_state'] = "退款成功";
            }else if ($val['pay_type'] == 1){
                $refundGoodData[$key]['refund_pay_type'] = "微信";
                if ($val['line_pay_refund_state'] == 1){
                    $refundGoodData[$key]['refund_line_state'] = "退款成功";
                }else{
                    $refundGoodData[$key]['refund_line_state'] = "退款中";
                }
            }else if ($val['pay_type'] == 4){
                $refundGoodData[$key]['refund_pay_type'] = "支付宝";
                if ($val['line_pay_refund_state'] == 1){
                    $refundGoodData[$key]['refund_line_state'] = "退款成功";
                }else{
                    $refundGoodData[$key]['refund_line_state'] = "退款中";
                }
            }
        }
        return getReturn(200, '', $refundGoodData);
    }
}