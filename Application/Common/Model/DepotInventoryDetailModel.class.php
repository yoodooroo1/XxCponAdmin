<?php

namespace Common\Model;

/**
 * 盘点单明细
 * Class DepotInventoryDetailModel.class.php
 * @package Common\Model
 * User: hjun
 * Date: 2018-10-29 11:34:14
 * Update: 2018-10-29 11:34:14
 * Version: 1.00
 */
class DepotInventoryDetailModel extends DepotBaseModel
{
    protected $tableName = 'mb_depot_inventory_detail';

    /**
     * 验证单据
     * @param int $orderId
     * @return boolean
     * User: hjun
     * Date: 2019-01-22 20:00:27
     * Update: 2019-01-22 20:00:27
     * Version: 1.00
     */
    public function validateOrderId($orderId = 0)
    {
        return D('DepotInventory')->validateOrderId($orderId);
    }

    /**
     * 自动完成商品名称
     * @param int $goodsId
     * @return string
     * User: hjun
     * Date: 2018-11-06 11:22:59
     * Update: 2018-11-06 11:22:59
     * Version: 1.00
     */
    protected function autoGoodsName($goodsId = 0)
    {
        $info = $this->getGoods($goodsId);
        return $info['goods_name'];
    }

    /**
     * 自动完成规格名称
     * @param int $goodsId
     * @param int $specId
     * @return string
     * User: hjun
     * Date: 2018-11-06 11:23:52
     * Update: 2018-11-06 11:23:52
     * Version: 1.00
     */
    protected function autoSpecName($goodsId = 0, $specId = 0)
    {
        $info = $this->getGoods($goodsId);
        foreach ($info['spec_attr'] as $spec) {
            if ($specId == $spec['primary_id']) {
                return $spec['spec_name'];
            }
        }
        return '';
    }

    /**
     * 自动完成商品条码
     * @param int $goodsId
     * @param int $specId
     * @return string
     * User: hjun
     * Date: 2018-11-06 11:23:52
     * Update: 2018-11-06 11:23:52
     * Version: 1.00
     */
    protected function autoGoodsBarcode($goodsId = 0, $specId = 0)
    {
        $info = $this->getGoods($goodsId);
        foreach ($info['spec_attr'] as $spec) {
            if ($specId == $spec['primary_id']) {
                return $spec['spec_goods_barcode'];
            }
        }
        return '';
    }

    /**
     * 自动完成 盈亏类型
     * @param array $goods
     * @return int
     * User: hjun
     * Date: 2019-01-22 19:37:50
     * Update: 2019-01-22 19:37:50
     * Version: 1.00
     */
    public function autoType($goods = [])
    {
        if ($goods['actual_stock'] < $goods['system_stock']) {
            $result = self::PL_STATUS_LOSS;
        } elseif ($goods['actual_stock'] > $goods['system_stock']) {
            $result = self::PL_STATUS_PROFIT;
        } else {
            $result = self::PL_STATUS_NONE;
        }
        return $result;
    }

    /**
     * 自动完成盘亏数量
     * @param array $goods
     * @return int
     * User: hjun
     * Date: 2019-01-22 19:41:11
     * Update: 2019-01-22 19:41:11
     * Version: 1.00
     */
    public function autoLossNum($goods = [])
    {
        $result = 0;
        if ($goods['actual_stock'] < $goods['system_stock']) {
            $result = $goods['system_stock'] - $goods['actual_stock'];
        }
        return $result;
    }

    /**
     * 自动完成盘亏金额
     * @param array $goods
     * @return double
     * User: hjun
     * Date: 2019-01-22 19:41:11
     * Update: 2019-01-22 19:41:11
     * Version: 1.00
     */
    public function autoLossPrice($goods = [])
    {
        $result = 0;
        if ($goods['actual_stock'] < $goods['system_stock']) {
            $result = ($goods['system_stock'] - $goods['actual_stock']) * $goods['cost_price'];
        }
        return $result;
    }

    /**
     * 自动完成盘盈数量
     * @param array $goods
     * @return int
     * User: hjun
     * Date: 2019-01-22 19:41:11
     * Update: 2019-01-22 19:41:11
     * Version: 1.00
     */
    public function autoProfitNum($goods = [])
    {
        $result = 0;
        if ($goods['actual_stock'] > $goods['system_stock']) {
            $result = $goods['actual_stock'] - $goods['system_stock'];
        }
        return $result;
    }

    /**
     * 自动完成盘盈金额
     * @param array $goods
     * @return double
     * User: hjun
     * Date: 2019-01-22 19:41:11
     * Update: 2019-01-22 19:41:11
     * Version: 1.00
     */
    public function autoProfitPrice($goods = [])
    {
        $result = 0;
        if ($goods['actual_stock'] > $goods['system_stock']) {
            $result = ($goods['actual_stock'] - $goods['system_stock']) * $goods['cost_price'];
        }
        return $result;
    }

    /**
     * 获取库单明细
     * @param int $orderId
     * @return array
     * User: hjun
     * Date: 2018-11-06 16:36:00
     * Update: 2018-11-06 16:36:00
     * Version: 1.00
     */
    public function getOrderDetails($orderId = 0)
    {
        $where = [];
        $where['order_id'] = $orderId;
        return $this->where($where)->select();
    }

    /**
     * 保存库单明细
     * @param array $request
     * @param int $action
     * @param boolean $startTrans 是否使用事务
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-01 10:12:40
     * Update: 2018-11-01 10:12:40
     * Version: 1.00
     */
    public function saveDepotInventoryDetail($request = [], $action = self::MODEL_INSERT, $startTrans = false)
    {
        $fields = [
            'store_id', 'order_id', 'order_sn', 'order_time', 'goods_id', 'goods_name', 'spec_id', 'spec_name',
            'depot_id', 'depot_name', 'goods_price', 'remark', 'goods_barcode', 'system_stock', 'actual_stock',
            'type', 'loss_num', 'loss_price', 'profit_num', 'profit_price',
        ];
        $data = [];
        $depotOrder = D('DepotInventory')->getDepotInventory($request['order_id']);
        foreach ($request['details'] as $key => $goods) {
            $index = $key + 1;
            $goods['store_id'] = $this->getStoreId();
            $goods['order_id'] = $depotOrder['order_id'];
            $goods['order_sn'] = $depotOrder['order_sn'];
            $goods['order_time'] = $depotOrder['order_time'];
            $goods['depot_id'] = $depotOrder['depot_id'];
            $goods['depot_name'] = $depotOrder['depot_name'];
            $goods['goods_price'] = $goods['cost_price'];
            $validate = [];
            $validate[] = ['order_id', 'validateOrderId', '单据不存在', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
            $validate[] = ['goods_id', 'validateGoods', '选择的商品已失效', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
            $validate[] = ['spec_id', 'validateGoodsSpec', '商品选择的规格已失效', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH, [$goods['goods_id']]];
            $auto = [];
            $auto[] = ['goods_name', $this->autoGoodsName($goods['goods_id']), self::MODEL_BOTH, 'string'];
            $auto[] = ['spec_name', $this->autoSpecName($goods['goods_id'], $goods['spec_id']), self::MODEL_BOTH, 'string'];
            $auto[] = ['goods_barcode', $this->autoGoodsBarcode($goods['goods_id'], $goods['spec_id']), self::MODEL_BOTH, 'string'];
            $auto[] = ['type', $this->autoType($goods), self::MODEL_BOTH, 'string'];
            $auto[] = ['loss_num', $this->autoLossNum($goods), self::MODEL_BOTH, 'string'];
            $auto[] = ['loss_price', $this->autoLossPrice($goods), self::MODEL_BOTH, 'string'];
            $auto[] = ['profit_num', $this->autoProfitNum($goods), self::MODEL_BOTH, 'string'];
            $auto[] = ['profit_price', $this->autoProfitPrice($goods), self::MODEL_BOTH, 'string'];
            $result = $this->getAndValidateDataFromRequest($fields, $goods, $validate, $auto, $action);
            if (!isSuccess($result)) {
                $result['msg'] = "第{$index}个{$result['msg']}";
                return $result;
            }
            $data[] = $result['data'];
        }
        $startTrans && $this->startTrans();
        // 如果是更细 需要先把之前的都删除了
        if ($action === self::MODEL_UPDATE) {
            $where = [];
            $where['order_id'] = $request['order_id'];
            $results[] = $this->where($where)->delete();
        }
        $firstId = $this->addAll($data);
        $results[] = $firstId;
        if (isTransFail($results)) {
            $startTrans && $this->rollback();
            return getReturn(CODE_ERROR);
        }
        // 记录主键
        foreach ($data as $key => $value) {
            $value['id'] = $firstId++;
            $data[$key] = $value;
        }
        $startTrans && $this->commit();
        return getReturn(CODE_SUCCESS, '保存成功', $data);
    }
}