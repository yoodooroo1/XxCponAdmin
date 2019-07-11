<?php

namespace Common\Model;

/**
 * 门店商品
 * Class DepotGoodsModel
 * @package Common\Model
 * User: hjun
 * Date: 2018-10-29 11:34:14
 * Update: 2018-10-29 11:34:14
 * Version: 1.00
 */
class DepotGoodsModel extends DepotBaseModel
{
    protected $tableName = 'mb_depot_goods';

    /**
     * 验证商品能否导入
     * 没有导入过则可以导入
     * @param int $depotId
     * @param int $goodsId
     * @return boolean
     * User: hjun
     * Date: 2018-10-31 15:55:49
     * Update: 2018-10-31 15:55:49
     * Version: 1.00
     */
    public function validateGoodsCanAdd($depotId = 0, $goodsId = 0)
    {
        $where = [];
        $where['depot_id'] = $depotId;
        $where['goods_id'] = $goodsId;
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['field'] = 'id';
        $options['where'] = $where;
        $link = $this->selectRow($options);
        return empty($link);
    }

    /**
     * 验证仓库库存是否充足
     * @param array $goods
     * @return boolean
     * User: hjun
     * Date: 2018-11-06 15:04:17
     * Update: 2018-11-06 15:04:17
     * Version: 1.00
     */
    public function validateEnough($goods = [])
    {
        return $goods['is_enough'] == 1;
    }

    /**
     * 验证商品能否自提（微信端前端展示）
     * @param int $goodsId
     * @return boolean
     * User: hjun
     * Date: 2018-11-09 14:20:33
     * Update: 2018-11-09 14:20:33
     * Version: 1.00
     */
    public function validateGoodsCanPickup($goodsId = 0)
    {
        $field = [
            'a.id'
        ];
        $field = implode(',', $field);
        $where = [];
        $where['a.goods_id'] = $goodsId;
        $where['a.storage'] = ['gt', 0];
        $where['b.type'] = self::PICKUP;
        $where['b.is_pick'] = 1;
        $where['b.isdelete'] = NOT_DELETE;
        $join = [
            '__MB_PICKUP_LIST__ b ON a.depot_id = b.id'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['where'] = $where;
        $options['join'] = $join;
        $info = $this->selectRow($options);
        return !empty($info);
    }

    /**
     * 将门店的商品列表根据商品ID进行分组
     * @param array $goodsList
     * @return array
     * User: hjun
     * Date: 2018-10-29 15:18:22
     * Update: 2018-10-29 15:18:22
     * Version: 1.00
     */
    protected function groupPickupGoods($goodsList = [])
    {
        $groupGoodsList = [];
        foreach ($goodsList as $key => $goods) {
            if (!isset($groupGoodsList[$goods['goods_id']])) {
                $groupGoodsList[$goods['goods_id']] = [
                    'goods_id' => $goods['goods_id'],
                    'goods_name' => $goods['goods_name']
                ];
                $groupGoodsList[$goods['goods_id']]['specs'] = [];
            }
            $specs = jsonDecodeToArr($goods['spec_attr']);
            foreach ($specs as $spec) {
                if ($spec['primary_id'] == $goods['spec_id']) {
                    $goods['goods_barcode'] = $spec['spec_goods_barcode'];
                    $goods['spec_name'] = $spec['spec_name'];
                    $goods['spec_price'] = D('GoodsExtra')->getGoodsSpecPrice($goods, $goods['spec_id']);
                    $goods['status_name'] = $this->autoGoodsStatusName($goods['status']);
                    break;
                }
            }
            $groupGoodsList[$goods['goods_id']]['specs'][] = $goods;
        }
        return array_values($groupGoodsList);
    }

    /**
     * 获取商品库存
     * @param int $depotId
     * @param int $goodsId
     * @param int $specId
     * @return int
     * User: hjun
     * Date: 2018-11-06 15:26:38
     * Update: 2018-11-06 15:26:38
     * Version: 1.00
     */
    public function getGoodsStorage($depotId = 0, $goodsId = 0, $specId = 0)
    {
        $where = [];
        $where['depot_id'] = $depotId;
        $where['goods_id'] = $goodsId;
        $where['spec_id'] = $specId;
        return $this->where($where)->getField('storage');
    }

    /**
     * 获取搜索条件
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-10-29 18:11:40
     * Update: 2018-10-29 18:11:40
     * Version: 1.00
     */
    public function getSearchWhere($request = [])
    {
        $where = [];
        if (!empty($request['goods_id'])) {
            $where['a.goods_id'] = $request['goods_id'];
        }
        if (!empty($request['goods_name'])) {
            $where['b.goods_name'] = ['like', "%{$request['goods_name']}%"];
        }
        if (!empty($request['status'])) {
            $where['a.status'] = $request['status'] - 1;
        }
        // 库存范围搜索
        $result = getRangeWhere($request, 'min_stock', 'max_stock', '库存范围不正确');
        if (!isSuccess($result)) {
            return $result;
        }
        if ($request['max_stock'] === '0') {
            $where['a.storage'] = 0;
        } else {
            if (!empty($result['data'])) {
                $where['a.storage'] = [['neq', -1], $result['data'], 'and'];
            }
        }
        // 销量范围搜索
        $result = getRangeWhere($request, 'min_sale', 'max_sale', '销量范围不正确');
        if (!isSuccess($result)) {
            return $result;
        }
        if ($request['max_sale'] === '0') {
            $where['a.sale_num'] = 0;
        } else {
            if (!empty($result['data'])) {
                $where['a.sale_num'] = $result['data'];
            }
        }
        return getReturn(CODE_SUCCESS, 'success', $where);
    }

    /**
     * 获取门店的商品列表
     * @param int $pickupId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array
     * User: hjun
     * Date: 2018-10-29 14:56:05
     * Update: 2018-10-29 14:56:05
     * Version: 1.00
     */
    public function getPickupGoodsData($pickupId = 0, $page = 1, $limit = 20, $condition = [])
    {
        $field = [
            'a.*', 'a.goods_price cost_price', 'a.storage system_stock',
            'b.goods_name', 'b.spec_attr', 'b.is_qinggou', 'b.is_promote',
        ];
        $field = implode(',', $field);
        $where = [];
        $where['a.depot_id'] = $pickupId;
        $where['a.is_delete'] = NOT_DELETE;
        $where['b.spec_type'] = 2;
        $where = array_merge($where, $condition);
        $join = [
            '__GOODS_EXTRA__ b ON a.goods_id = b.goods_id'
        ];
        $order = 'b.sort DESC,b.top DESC';
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['join'] = $join;
        $options['order'] = $order;
        $data = $this->queryList($options)['data'];
        $data['list'] = $this->groupPickupGoods($data['list']);
        return $data;
    }

    /**
     * 一个规格一条数据
     * @param int $pickupId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array
     * User: hjun
     * Date: 2018-11-07 20:24:49
     * Update: 2018-11-07 20:24:49
     * Version: 1.00
     */
    public function getPickupGoodsSpecData($pickupId = 0, $page = 1, $limit = 20, $condition = [])
    {
        $data = $this->getPickupGoodsData($pickupId, $page, $limit, $condition);
        $newList = [];
        foreach ($data['list'] as $goods) {
            foreach ($goods['specs'] as $spec) {
                $newList[] = $spec;
            }
        }
        $data['list'] = $newList;
        return $data;
    }

    /**
     * 获取已经被加入到门店的商品规格
     * @param int $pickupId
     * @return array
     * User: hjun
     * Date: 2018-10-30 15:34:59
     * Update: 2018-10-30 15:34:59
     * Version: 1.00
     */
    public function getCantSelectGoodsSpec($pickupId = 0)
    {
        $where = [];
        $where['depot_id'] = $pickupId;
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['field'] = 'goods_id,spec_id';
        $options['where'] = $where;
        $list = $this->selectList($options);
        return $list;
    }

    /**
     * 导入门店商品
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-10-31 15:47:40
     * Update: 2018-10-31 15:47:40
     * Version: 1.00
     */
    public function addPickupGoods($request = [])
    {
        if (!$this->validateBatchIds($request['goods_id'])) {
            return getReturn(CODE_ERROR, '请选择需要导入的商品');
        }
        if (!$this->validateDepot($request['pickup_id'])) {
            return getReturn(CODE_ERROR, '导入的门店已失效');
        }
        $pickup = $this->getDepot($request['pickup_id']);
        $data = [];
        foreach ($request['goods_id'] as $goodsId) {
            if (!$this->validateGoods($goodsId)) {
                return getReturn(CODE_ERROR, $this->getError());
            }
            $goods = $this->getGoods($goodsId);
            if (!$this->validateGoodsCanAdd($request['pickup_id'], $goodsId)) {
                return getReturn(CODE_ERROR, "已有商品[{$goods['goods_name']}]，无需再添加！");
            }

            $item = [];
            $item['goods_id'] = $goodsId;
            $item['depot_id'] = $pickup['id'];
            $item['store_id'] = $pickup['store_id'];
            $item['create_time'] = NOW_TIME;
            foreach ($goods['spec_attr'] as $spec) {
                $item['spec_id'] = $spec['primary_id'];
                $item['goods_price'] = $spec['spec_price'] - $spec['spec_goods_pv']; // 成本价
                $data[] = $item;
            }
        }
        if (empty($data)) {
            return getReturn(CODE_ERROR, "没有导入的商品");
        }
        if (!empty($data)) {
            $result = $this->addAll($data);
            if (false === $result) {
                return getReturn(CODE_ERROR);
            }
        }
        return getReturn(CODE_SUCCESS, '导入成功');
    }

    /**
     * 批量操作
     * 上架、下架、删除
     * @param array $request
     * @param int $action
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-05 14:36:14
     * Update: 2018-11-05 14:36:14
     * Version: 1.00
     */
    public function batchGoodsAction($request = [], $action = self::MODEL_DELETE)
    {
        if (!$this->validateBatchIds($request['ids'])) {
            $error = [
                self::ACTION_DELETE => "请选择需要删除的商品",
                self::ACTION_SHELF => "请选择需要上架的商品",
                self::ACTION_OFF_SHELF => "请选择需要下架的商品",
            ];
            return getReturn(CODE_ERROR, $error[$action]);
        }

        if (!$this->validateDepot($request['pickup_id'])) {
            return getReturn(CODE_ERROR, "门店不存在");
        }

        $where = [];
        $where['depot_id'] = $request['pickup_id'];
        $where['id'] = getInSearchWhereByArr($request['ids']);
        $actionData = [
            self::ACTION_DELETE => ['is_delete' => 1],
            self::ACTION_SHELF => ['status' => 1],
            self::ACTION_OFF_SHELF => ['status' => 0],
        ];
        $data = $actionData[$action];
        $result = $this->where($where)->save($data);
        if (false === $result) {
            return getReturn(CODE_ERROR);
        }
        $msg = [
            self::ACTION_DELETE => "删除成功",
            self::ACTION_SHELF => "上架成功",
            self::ACTION_OFF_SHELF => "下架成功",
        ];
        return getReturn(CODE_SUCCESS, $msg[$action]);
    }

    /**
     * 删除商品
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-05 14:38:38
     * Update: 2018-11-05 14:38:38
     * Version: 1.00
     */
    public function batchDeleteGoods($request = [])
    {
        return $this->batchGoodsAction($request, self::ACTION_DELETE);
    }

    /**
     * 上架商品
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-05 14:38:44
     * Update: 2018-11-05 14:38:44
     * Version: 1.00
     */
    public function batchShelfGoods($request = [])
    {
        return $this->batchGoodsAction($request, self::ACTION_SHELF);
    }

    /**
     * 下架商品
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-05 14:38:53
     * Update: 2018-11-05 14:38:53
     * Version: 1.00
     */
    public function batchOffShelAction($request = [])
    {
        return $this->batchGoodsAction($request, self::ACTION_OFF_SHELF);
    }

    /**
     * 商品规格库存增减
     * @param int $depotId
     * @param int $goodsId
     * @param int $specId
     * @param int $changeNum
     * @return boolean
     * User: hjun
     * Date: 2018-11-06 14:53:10
     * Update: 2018-11-06 14:53:10
     * Version: 1.00
     */
    public function goodsSpecStorageAction($depotId = 0, $goodsId = 0, $specId = 0, $changeNum = 0)
    {
        $where = [];
        $where['goods_id'] = $goodsId;
        $where['spec_id'] = $specId;
        $where['depot_id'] = $depotId;
        $goods = $this->where($where)->find();
        if (empty($goods)) {
            $this->setValidateError("变更库存的商品不存在");
            return false;
        }
        // 如果没有变更 或者 库存充足 则不需要进行扣减
        if ($changeNum == 0 || $this->validateEnough($goods)) {
            return true;
        }
        if ($changeNum > 0) {
            $result = $this->where($where)->setInc('storage', abs($changeNum));
        } else {
            $result = $this->where($where)->setDec('storage', abs($changeNum));
        }
        return $result !== false;
    }

    /**
     * 商品规格销量增减
     * @param int $depotId
     * @param int $goodsId
     * @param int $specId
     * @param int $changeNum
     * @return boolean
     * User: hjun
     * Date: 2018-11-06 14:53:10
     * Update: 2018-11-06 14:53:10
     * Version: 1.00
     */
    public function goodsSpecSalesAction($depotId = 0, $goodsId = 0, $specId = 0, $changeNum = 0)
    {
        $where = [];
        $where['goods_id'] = $goodsId;
        $where['spec_id'] = $specId;
        $where['depot_id'] = $depotId;
        $goods = $this->where($where)->find();
        if (empty($goods)) {
            $this->setValidateError("变更销量的商品不存在");
            return false;
        }
        // 如果没有变更 或者 库存充足 则不需要进行扣减
        if ($changeNum == 0) {
            return true;
        }
        if ($changeNum > 0) {
            $result = $this->where($where)->setInc('sale_num', abs($changeNum));
        } else {
            $result = $this->where($where)->setDec('sale_num', abs($changeNum));
        }
        return $result !== false;
    }

    /**
     * 更新价格
     * @param int $depotId
     * @param int $goodsId
     * @param int $specId
     * @param int $price
     * @return mixed
     * User: hjun
     * Date: 2018-11-07 22:40:25
     * Update: 2018-11-07 22:40:25
     * Version: 1.00
     */
    public function updateGoodsSpecPrice($depotId = 0, $goodsId = 0, $specId = 0, $price = 0)
    {
        $where = [];
        $where['depot_id'] = $depotId;
        $where['spec_id'] = $specId;
        $where['goods_id'] = $goodsId;
        $data = [];
        $data['goods_price'] = $price;
        return $this->where($where)->save($data);
    }

    /**
     * 根据商品列表获取自提列表
     * @param int $cartStoreId
     * @param array $goodsList
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-09 14:44:12
     * Update: 2018-11-09 14:44:12
     * Version: 1.00
     */
    public function getPickupListByGoodsList($cartStoreId = 0, $goodsList = [])
    {
        // 如果门店销售类型是0 则返回所有店铺可选的自提点
        $storeInfo = D('Store')->getStoreInfo($cartStoreId)['data'];
        if (pickupIsMall($storeInfo)) {
            $storeIds = [];
            foreach ($goodsList as $goods) {
                if (!in_array($goods['store_id'], $storeIds)) {
                    // 如果该店铺有开启自提才去获取
                    if (pickupIsOpen($goods['store_id'])) {
                        $storeIds[] = $goods['store_id'];
                    }
                }
            }
            $mainStoreId = $storeInfo['main_store_id'];
            if (!in_array($mainStoreId, $storeIds)) {
                $storeIds[] = $mainStoreId;
            }
            $where = [];
            $where['store_id'] = getInSearchWhereByArr($storeIds);
            $pickList = D('PickUp')->getCartPickupList($where);
            return $pickList;
        }
        $goodsId = [];
        $specId = [];
        foreach ($goodsList as $goods) {
            if (!in_array($goods['goods_id'], $goodsId)) {
                $goodsId[] = $goods['goods_id'];
            }
            if (!in_array($goods['primary_id'], $specId)) {
                $specId[] = $goods['primary_id'];
            }
        }
        $where = [];
        $where['goods_id'] = getInSearchWhereByArr($goodsId);
        $where['spec_id'] = getInSearchWhereByArr($specId);
        $where['storage'] = ['gt', 0];
        $where['is_delete'] = NOT_DELETE;
        $list = $this->where($where)->select();
        if (empty($list)) return [];

        // 分组每个自提点允许的商品列表
        $link = [];
        $pickIdArr = [];
        foreach ($list as $pick) {
            $link[$pick['depot_id']][] = $pick;
            $pickIdArr[] = $pick['depot_id'];
        }
        $where = [];
        $where['id'] = getInSearchWhereByArr($pickIdArr);
        $pickList = D('PickUp')->getCartPickupList($where);
        if (empty($pickList)) return [];
        $req = getRequest();
        $lng = $req['lng'];
        $lat = $req['lat'];
        foreach ($pickList as $key => $pick) {
            $pick['limit_goods_list'] = empty($link[$pick['pick_id']]) ? [] : $link[$pick['pick_id']];
            // 计算距离
            $pick['distance'] = getPickupDistance($pick['latitude'], $pick['longitude'], $lat, $lng);
            $pickList[$key] = $pick;
        }
        return $pickList;
    }
}