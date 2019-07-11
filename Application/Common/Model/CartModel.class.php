<?php

namespace Common\Model;


/**
 * Class CartService
 * 购物车 模型
 * @package Common\Service
 * User: hjun
 * Date: 2018-01-25 12:06:07
 */
class CartModel extends BaseModel
{
    protected $tableName = 'mb_shop_cart';

    /**
     * 获取购物车数据
     * @param int $storeId
     * @param int $memberId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-25 14:38:13
     * Update: 2018-01-25 14:38:13
     * Version: 1.00
     */
    public function getItem($storeId = 0, $memberId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['member_id'] = $memberId;
        $options = [];
        $options['where'] = $where;
        $result = $this->queryRow($options);
        $info = $result['data'];
        return empty($info) ? [] : unserialize($info['serialized_data']);
    }

    /**
     * 获取购物车数据
     * @param int $storeId
     * @param int $memberId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-30 17:06:28
     * Update: 2018-03-30 17:06:28
     * Version: 1.00
     */
    public function getCart($storeId = 0, $memberId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['member_id'] = $memberId;
        $options = [];
        $options['where'] = $where;
        $result = $this->queryRow($options);
        $info = $result['data'];
        return empty($info) ? [] : $info;
    }
}