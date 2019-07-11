<?php

namespace Common\Model;
class CashOrderGoodsModel extends BaseModel
{
    protected $tableName = 'mb_cash_order_goods';


    public function put_order_goods_db($goods_array, $order_id, $store_id)
    {

        for ($f = 0; $f < count($goods_array); $f++) {
            $goods_bean = $goods_array[$f];

            $goods_data = array();
            $goods_data['order_id'] = $order_id;
            $goods_data['store_id'] = $store_id;
            $goods_data['goods_id'] = $goods_bean['goods_id'];
            $goods_data['goods_name'] = $goods_bean['goods_name'];
            $goods_data['goods_price'] = $goods_bean['goods_price'];
            $goods_data['goods_original_price'] = 0;
            $goods_data['specid'] = $goods_bean['specid'];
            $goods_data['spec_name'] = $goods_bean['spec_name'];
            $goods_data['gou_num'] = $goods_bean['gou_num'];
            $goods_data['goods_barcode'] = $goods_bean['goods_barcode'];
            $goods_data['goods_figure'] = "";
            $goods_data['goods_desc'] = $goods_bean['goods_desc'];
            $goods_data['gc_id'] = $goods_bean['gc_id'];
            $goods_data['goods_pv'] = $goods_bean['goods_pv'];
            $goods_data['platform_credits_exmoney'] = empty($goods_bean['platform_credits_exmoney']) ? 0:$goods_bean['platform_credits_exmoney'];
            $goods_data['platform_credits_num'] = empty($goods_bean['platform_credits_num']) ? 0:$goods_bean['platform_credits_num'];
            $goods_data['platform_balance'] = empty($goods_bean['platform_balance']) ? 0:$goods_bean['platform_balance'];
            $goods_data['platform_coupons_money'] = empty($goods_bean['platform_coupons_money']) ? 0:$goods_bean['platform_coupons_money'];
            $goods_data['thirdpart_momey'] = empty($goods_bean['thirdpart_momey']) ? 0:$goods_bean['thirdpart_momey'];
            $goods_data['credits_exmoney'] = empty($goods_bean['credits_exmoney']) ? 0:$goods_bean['credits_exmoney'];
            $goods_data['credits_num'] = empty($goods_bean['credits_num']) ? 0:$goods_bean['credits_num'];
            $goods_data['balance'] = empty($goods_bean['balance']) ? 0:$goods_bean['balance'];
            $goods_data['coupons_exmoney'] = empty($goods_bean['coupons_exmoney']) ? 0:$goods_bean['coupons_exmoney'];
            $goods_data['refund'] = 0;
            $goods_data['mj_id'] = empty($goods_bean['mj_id']) ? 0:$goods_bean['mj_id'];
            $goods_data['mj_price'] = empty($goods_bean['mj_bean_price']) ? 0:$goods_bean['mj_bean_price'];
            $goods_data['store_discount_amount'] = empty($goods_bean['store_discount_amount']) ? 0 : $goods_bean['store_discount_amount'];
            $goods_data['store_reduce_money'] = empty($goods_bean['store_reduce_money']) ? 0: $goods_bean['store_reduce_money'];
            $log_data = "订单为:" . $order_id."。商品编号为:".$goods_bean['goods_id']."插入mb_order_goods失败!".json_encode($goods_data);
            refundGoodsLogs($log_data);
            $tag = $this->add($goods_data);
            if ($tag == false){
                return getReturn(-1, "插入订单商品表失败");
            }

        }
        return getReturn(200, "成功");
    }


}