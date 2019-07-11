<?php

namespace Common\Model;


class PickUpOrderModel extends BaseModel
{
    protected $tableName = 'mb_pickup_order';

    /**
     * 添加自提记录
     * @param int $pickup_id 自提编号编号
     * @param int $order_id 订单编号
     * @param int $buy_id 会员编号
     * @return
     */
    public function addPickUpOrder($pickup_id, $order_id, $buy_id, $order_membername,
                                   $all_goods_count, $store_id, $expect_pick_time)
    {
        $insert_data = [];
        $insert_data['store_id'] = $store_id;
        $insert_data['pick_id'] = $pickup_id;
        $insert_data['order_sn'] = $order_id;
        $insert_data['buyer_id'] = $buy_id;
        $insert_data['member_name'] = $order_membername;
        $insert_data['other_name'] = $order_membername;
        $insert_data['goods_num'] = $all_goods_count;
        $insert_data['expect_pick_time'] = date('Y-m-d H:i:s',$expect_pick_time);
        $returnData = $this->add($insert_data);
        if ($returnData == false){
            return getReturn(-1, "插入自提信息失败");
        }
        return getReturn(200, "成功");
    }


}