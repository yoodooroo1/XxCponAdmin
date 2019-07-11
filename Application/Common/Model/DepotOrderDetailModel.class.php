<?php

namespace Common\Model;

/**
 * 入库单明细
 * Class DepotOrderDetailModel
 * @package Common\Model
 * User: hjun
 * Date: 2018-10-29 11:34:14
 * Update: 2018-10-29 11:34:14
 * Version: 1.00
 */
class DepotOrderDetailModel extends DepotBaseModel
{
    protected $tableName = 'mb_depot_order_detail';

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
     * 自动完成总价
     * @param int $goodsNum
     * @param int $goodsPrice
     * @return double
     * User: hjun
     * Date: 2018-11-06 11:25:51
     * Update: 2018-11-06 11:25:51
     * Version: 1.00
     */
    public function autoTotalPrice($goodsNum = 0, $goodsPrice = 0)
    {
        return round($goodsNum * $goodsPrice, 2);
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
    public function saveDepotOrderDetail($request = [], $action = self::MODEL_INSERT, $startTrans = false)
    {
        $fields = [
            'store_id', 'order_id', 'order_sn', 'order_time', 'goods_id', 'goods_name', 'spec_id', 'spec_name',
            'depot_id', 'depot_name', 'goods_num', 'goods_price', 'total_price', 'remark', 'goods_barcode',
        ];
        $data = [];
        $depotOrder = $this->getOrder($request['order_id']);
        foreach ($request['details'] as $key => $goods) {
            $index = $key + 1;
            $goods['store_id'] = $this->getStoreId();
            $goods['order_id'] = $depotOrder['order_id'];
            $goods['order_sn'] = $depotOrder['order_sn'];
            $goods['order_time'] = $depotOrder['order_time'];
            $goods['depot_id'] = $depotOrder['depot_id'];
            $goods['depot_name'] = $depotOrder['depot_name'];
            $validate = [];
            $validate[] = ['order_id', 'validateOrderId', '单据不存在', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
            $validate[] = ['goods_id', 'validateGoods', '选择的商品已失效', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
            $validate[] = ['spec_id', 'validateGoodsSpec', '商品选择的规格已失效', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH, [$goods['goods_id']]];
            $validate[] = ['goods_num', 'integer', '输入的数量格式不正确', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH];
            $validate[] = ['goods_price', 'double', '输入的单价格式不正确', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH];
            $auto = [];
            $auto[] = ['goods_name', $this->autoGoodsName($goods['goods_id']), self::MODEL_BOTH, 'string'];
            $auto[] = ['spec_name', $this->autoSpecName($goods['goods_id'], $goods['spec_id']), self::MODEL_BOTH, 'string'];
            $auto[] = ['goods_barcode', $this->autoGoodsBarcode($goods['goods_id'], $goods['spec_id']), self::MODEL_BOTH, 'string'];
            $auto[] = ['total_price', $this->autoTotalPrice($goods['goods_num'], $goods['goods_price']), self::MODEL_BOTH, 'string'];
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