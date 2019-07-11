<?php

namespace Common\Model;
/**
 * Class PickUpModel
 * User: hj
 * Date: 2017-10-30 16:33:20
 * Desc: 自提点模型
 * Update: 2017-10-30 16:33:21
 * Version: 1.0
 * @package Common\Model
 */
class PickUpModel extends DepotModel
{
    /**
     * 获取门店数据
     * @param int $pickupId
     * @return array
     * User: hjun
     * Date: 2018-10-31 15:54:01
     * Update: 2018-10-31 15:54:01
     * Version: 1.00
     */
    public function getPickup($pickupId = 0)
    {
        return $this->getDepot($pickupId);
    }

    /**
     * 获取门店列表
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-10-31 15:54:09
     * Update: 2018-10-31 15:54:09
     * Version: 1.00
     */
    public function getPickUpList($storeId = 0)
    {
        $where = [];
        $where['type'] = self::PICKUP;
        return $this->getDepotList($storeId, 1, 0, $where, 'id,store_name');
    }

    /**
     * 获取select option列表
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-12 09:41:13
     * Update: 2018-11-12 09:41:13
     * Version: 1.00
     */
    public function getSelectList($storeId = 0)
    {
        $where = [];
        $where['type'] = self::PICKUP;
        $field = [
            'id pickup_id', 'store_name pickup_name'
        ];
        return $this->getDepotList($storeId, 1, 0, $where, $field)['data']['list'];
    }

    /**
     * 获取购物车可选门店列表
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-12 09:49:23
     * Update: 2018-11-12 09:49:23
     * Version: 1.00
     */
    public function getCartPickupList($condition = [])
    {
        $where = [];
        $where['type'] = self::PICKUP;
        $where['is_pick'] = 1;
        $where['isdelete'] = NOT_DELETE;
        $where = array_merge($where, $condition);
        $address = "CONCAT(IFNULL(province, ''), IFNULL(city, ''), IFNULL(area, ''), IFNULL(address, '')) pick_address";
        $field = [
            'id', 'id pick_id', 'store_id', 'store_name pick_name', $address, 'link_tel', '0 checked',
            'longitude', 'latitude', 'staff'
        ];
        $field = implode(',', $field);
        $pickList = D('PickUp')->field($field)->where($where)->order('addtime DESC')->select();
        return $pickList;
    }
}