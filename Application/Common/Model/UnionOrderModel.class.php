<?php

namespace Common\Model;
class UnionOrderModel extends BaseModel
{
    protected $tableName = 'mb_unionorder';

    public function unionorder($order_id, $agent_store_id, $storeid_data)
    {

        $order_data = Model('mb_order')->where(array('order_id' => $order_id))->find();
        $agent_store = Model('store')->where(array('store_id' => $agent_store_id))->find();

        if (!empty($order_data) && !empty($agent_store)) {
            $goods_data = json_decode($order_data['order_content'], true);

            for ($i = 0; $i < count($storeid_data); $i++) {
                $uniongoods = array();
                $union_totalmoney = 0;
                $union_maxversion = $this->max('version');
                for ($j = 0; $j < count($goods_data); $j++) {
                    if ($storeid_data[$i] == $goods_data[$j]['store_id']) {
                        $uniongoods[] = $goods_data[$j];
                        $union_totalmoney = $union_totalmoney + doubleval($goods_data[$j]['supplier_price']) * $goods_data[$j]['gou_num'];
                    }
                }

                if (count($uniongoods) > 0) {
                    $unionorder_data = array();
                    $unionorder_data['order_id'] = $order_data['order_id'];
                    $unionorder_data['order_state'] = $order_data['order_state'];
                    $unionorder_data['create_time'] = $order_data['create_time'];
                    $unionorder_data['buyer_id'] = $order_data['buyer_id'];
                    $unionorder_data['totalprice'] = $union_totalmoney;
                    $unionorder_data['version'] = $union_maxversion + 1;
                    $unionorder_data['storeid'] = $storeid_data[$i];
                    $unionorder_data['agent_storeid'] = $agent_store['store_id'];
                    $unionorder_data['agent_storename'] = $agent_store['store_name'];
                    $unionorder_data['agent_owner'] = $agent_store['member_name'];
                    $unionorder_data['agent_storetel'] = $agent_store['store_tel'];
                    $unionorder_data['order_content'] = json_encode($uniongoods, JSON_UNESCAPED_UNICODE);
                    $returnData = $this->add($unionorder_data);
                    if ($returnData == false){
                        return getReturn(-1, "插入联盟订单失败");
                    }
                }
            }
        }
        return getReturn(200, "成功");
    }


}