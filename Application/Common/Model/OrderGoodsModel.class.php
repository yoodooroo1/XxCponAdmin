<?php

namespace Common\Model;
class OrderGoodsModel extends BaseModel
{
    protected $tableName = 'mb_order_goods';


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
            $goods_data['goods_original_price'] = $goods_bean['original_price'];
            $goods_data['specid'] = $goods_bean['specid'];
            $goods_data['spec_name'] = $goods_bean['spec_name'];
            $goods_data['gou_num'] = $goods_bean['gou_num'];
            $goods_data['goods_barcode'] = $goods_bean['goods_barcode'];
            $goods_data['goods_figure'] = $goods_bean['goods_figure'];
            $goods_data['goods_desc'] = $goods_bean['goods_desc'];
            $goods_data['gc_id'] = $goods_bean['gc_id'];
            $goods_data['goods_pv'] = $goods_bean['goods_pv'];
            $goods_data['platform_credits_exmoney'] = $goods_bean['platform_credits_exmoney'];
            $goods_data['platform_balance'] = $goods_bean['platform_balance'];
            $goods_data['platform_coupons_money'] = $goods_bean['platform_coupons_money'];
            $goods_data['thirdpart_momey'] = $goods_bean['thirdpart_momey'];
            $goods_data['credits_exmoney'] = $goods_bean['credits_exmoney'];
            $goods_data['credits_num'] = $goods_bean['credits_num'];
            $goods_data['balance'] = $goods_bean['balance'];
            $goods_data['coupons_exmoney'] = $goods_bean['coupons_exmoney'];
            $goods_data['refund'] = 0;
            $goods_data['mj_id'] = $goods_bean['mj_id'];
            $goods_data['mj_price'] = $goods_bean['mj_bean_price'];
            $tag = $this->add($goods_data);
            if ($tag == false){
//                $log_data = "订单为:" . $order_id."。商品编号为:".$goods_bean['goods_id']."插入mb_order_goods失败!".json_encode($goods_data);
//                Log::record($log_data, Log::ERR);
                return getReturn(-1, "插入订单商品表失败");
            }
        }
        return getReturn(200, "成功");
    }


}