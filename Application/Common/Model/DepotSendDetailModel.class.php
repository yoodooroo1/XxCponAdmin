<?php

namespace Common\Model;

/**
 * 收发明细
 * Class DepotSendDetailModel
 * @package Common\Model
 * User: hjun
 * Date: 2018-10-29 11:34:14
 * Update: 2018-10-29 11:34:14
 * Version: 1.00
 */
class DepotSendDetailModel extends DepotBaseModel
{
    protected $tableName = 'mb_depot_sr_details';

    /**
     * 新增明细
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-06 15:59:17
     * Update: 2018-11-06 15:59:17
     * Version: 1.00
     */
    protected function addDetail($data = [])
    {
        // 修改库存和销量
        $type = $data['type'];
        switch ($type) {
            case self::TYPE_SALE_OUT:
                $changeNum = -1 * $data['stock_out'];
                $saleChangeNum = -1 * $changeNum;
                break;
            case self::TYPE_REFUND_IN:
                $changeNum = $data['stock_in'];
                $saleChangeNum = -1 * $changeNum;
                break;
            case self::TYPE_OTHER_IN:
                $changeNum = $data['stock_in'];
                break;
            case self::TYPE_OTHER_OUT:
                $changeNum = -1 * $data['stock_out'];
                break;
            case self::TYPE_INVENTORY_IN:
                $changeNum = $data['stock_in'];
                break;
            case self::TYPE_INVENTORY_OUT:
                $changeNum = -1 * $data['stock_out'];
                break;
            default:
                break;
        }
        $dgModel = D('DepotGoods');
        if (isset($changeNum)) {
            $result = $dgModel->goodsSpecStorageAction($data['depot_id'], $data['goods_id'], $data['spec_id'], $changeNum);
            if (false === $result) {
                return getReturn(CODE_ERROR, $dgModel->getError());
            }
        }
        if (isset($saleChangeNum)) {
            $result = $dgModel->goodsSpecSalesAction($data['depot_id'], $data['goods_id'], $data['spec_id'], $saleChangeNum);
            if (false === $result) {
                return getReturn(CODE_ERROR, $dgModel->getError());
            }
        }
        // 记录明细
        $data['store_id'] = $this->getStoreId();
        $data['stock_result'] = D('DepotGoods')->getGoodsStorage($data['depot_id'], $data['goods_id'], $data['spec_id']);
        $data['create_time'] = time();
        $fields = [
            'store_id', 'depot_id', 'depot_name', 'goods_id', 'goods_name', 'goods_barcode', 'spec_id', 'spec_name',
            'order_id', 'order_time', 'order_sn', 'type', 'object_id', 'object_name',
            'stock_type', 'stock_in', 'stock_out', 'stock_result', 'create_time',
        ];
        $data = $this->getAndValidateDataFromRequest($fields, $data, [], [], self::MODEL_INSERT)['data'];
        $result = $this->add($data);
        if (false === $result) {
            return getReturn(CODE_ERROR);
        }
        return getReturn(CODE_SUCCESS, 'success', $data);
    }

    /**
     * 新增仓库操作的记录
     * @param int $detailId 入库单明细ID
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-06 15:52:57
     * Update: 2018-11-06 15:52:57
     * Version: 1.00
     */
    public function addDepotOrderDetail($detailId = 0)
    {
        $detail = D('DepotOrderDetail')->find($detailId);
        $depotOrder = $this->getOrder($detail['order_id']);
        if (empty($detail) || empty($depotOrder)) {
            return getReturn(CODE_ERROR, "未找到单据明细");
        }
        $detail['object_id'] = $detail['depot_id'];
        $detail['object_name'] = $detail['depot_name'];
        $stockKey = $this->getPropertyByRequestType($depotOrder['type'], 'stock_key');
        $detail['stock_type'] = $this->getPropertyByRequestType($depotOrder['type'], 'stock_action');
        $detail[$stockKey] = $detail['goods_num'];
        $detail['type'] = $depotOrder['type'];
        $result = $this->addDetail($detail);
        if (!isSuccess($result)) {
            return $result;
        }
        return getReturn(CODE_SUCCESS, 'success', $detail);
    }

    /**
     * 新增盘点操作的记录
     * @param int $detailId 盘点单明细ID
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-06 15:52:57
     * Update: 2018-11-06 15:52:57
     * Version: 1.00
     */
    public function addDepotInventoryDetail($detailId = 0)
    {
        $detail = D('DepotInventoryDetail')->find($detailId);
        $depotOrder = D('DepotInventory')->getDepotInventory($detail['order_id']);
        if (empty($detail) || empty($depotOrder)) {
            return getReturn(CODE_ERROR, "未找到单据明细");
        }
        // 如果不亏不盈 则不需要记录
        if ($detail['type'] == self::PL_STATUS_NONE) {
            return getReturn(CODE_SUCCESS, 'success');
        }

        $detail['object_id'] = $detail['depot_id'];
        $detail['object_name'] = $detail['depot_name'];
        switch ($detail['type']) {
            case self::PL_STATUS_PROFIT:
                $stockType = self::STOCK_IN;
                $type = self::TYPE_INVENTORY_IN;
                $stockKey = 'stock_in';
                $num = $detail['profit_num'];
                break;
            case self::PL_STATUS_LOSS:
                $stockType = self::STOCK_OUT;
                $type = self::TYPE_INVENTORY_OUT;
                $stockKey = 'stock_out';
                $num = $detail['loss_num'];
                break;
            default:
                return getReturn(CODE_SUCCESS, 'success');
                break;
        }
        $detail['stock_type'] = $stockType;
        $detail[$stockKey] = $num;
        $detail['type'] = $type;
        $result = $this->addDetail($detail);
        if (!isSuccess($result)) {
            return $result;
        }
        return getReturn(CODE_SUCCESS, 'success', $detail);
    }

    /**
     * 新增销售出库明细
     * @param int $orderId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-06 15:53:50
     * Update: 2018-11-06 15:53:50
     * Version: 1.00
     */
    public function addSaleOutDetail($orderId = 0, $startTrans = false)
    {
        $order = D('Order')->find($orderId);
        if (empty($order)) {
            return getReturn(CODE_ERROR, "未找到订单数据");
        }
        // 如果不是自提 则不需要记录
        if ($order['pickup_id'] <= 0) {
            return getReturn(CODE_SUCCESS);
        }
        $startTrans && $this->startTrans();
        $orderContent = jsonDecodeToArr($order['order_content']);
        foreach ($orderContent as $goods) {
            $goods['depot_id'] = $order['pickup_id'];
            $goods['depot_name'] = $order['pickup_store_name'];
            $goods['spec_id'] = $goods['primary_id'];
            $goods['order_id'] = $order['order_id'];
            $goods['order_sn'] = $order['order_sn'];
            $goods['order_time'] = $order['create_time'];
            $goods['stock_type'] = self::STOCK_OUT;
            $goods['stock_out'] = $goods['gou_num'];
            $goods['object_id'] = $goods['buyer_id'];
            $goods['object_name'] = $goods['order_membername'];
            $goods['type'] = self::TYPE_SALE_OUT;
            $result = $this->addDetail($goods);
            if (!isSuccess($result)) {
                $startTrans && $this->rollback();
                return $result;
            }
        }
        $startTrans && $this->commit();
        return getReturn(CODE_SUCCESS, 'success');
    }

    /**
     * 根据order_content中的某个商品
     * 记录收发明细
     * @param int $orderId
     * @param array $goods
     * @param int $stockType
     * @param int $type
     * @param bool $startTrans
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-12 15:47:49
     * Update: 2018-11-12 15:47:49
     * Version: 1.00
     */
    public function addDetailByOrderGoods($orderId = 0, $goods = [], $stockType = self::STOCK_OUT, $type = self::TYPE_SALE_OUT, $startTrans = false)
    {
        $field = [
            'order_id', 'order_sn', 'buyer_id', 'order_membername',
            'pickup_id', 'pickup_store_name', 'create_time'
        ];
        $field = implode(',', $field);
        $order = D('Order')->field($field)->find($orderId);
        if (empty($order)) {
            return getReturn(CODE_ERROR, "未找到出售订单的数据");
        }
        // 如果不是自提 则不需要记录
        if ($order['pickup_id'] <= 0) {
            return getReturn(CODE_SUCCESS);
        }
        $startTrans && $this->startTrans();
        $goods['depot_id'] = $order['pickup_id'];
        $goods['depot_name'] = $order['pickup_store_name'];
        $goods['spec_id'] = $goods['primary_id'];
        $goods['order_id'] = $order['order_id'];
        $goods['order_sn'] = $order['order_sn'];
        $goods['order_time'] = $order['create_time'];
        $goods['stock_type'] = $stockType;
        if ($stockType === self::STOCK_IN) {
            $goods['stock_in'] = $goods['gou_num'];
        } elseif ($stockType === self::STOCK_OUT) {
            $goods['stock_out'] = $goods['gou_num'];
        }
        $goods['object_id'] = $order['buyer_id'];
        $goods['object_name'] = $order['order_membername'];
        $goods['type'] = $type;
        $result = $this->addDetail($goods);
        if (!isSuccess($result)) {
            $startTrans && $this->rollback();
            return $result;
        }
        $startTrans && $this->commit();
        return getReturn(CODE_SUCCESS, 'success');
    }

    /**
     * 获取搜索条件
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-06 20:28:01
     * Update: 2018-11-06 20:28:01
     * Version: 1.00
     */
    public function getSearchWhere($request = [])
    {
        $where = [];
        if (!empty($request['order_sn'])) {
            $where['a.order_sn'] = $request['order_sn'];
        }
        if (!empty($request['goods_name'])) {
            $where['a.goods_name'] = ['like', "%{$request['goods_name']}%"];
        }
        if (!empty($request['object_name'])) {
            $where['a.object_name'] = ['like', "%{$request['object_name']}%"];
        }
        if (!empty($request['type'])) {
            $where['a.type'] = $request['type'];
        }
        if (!empty($request['goods_id'])) {
            $where['a.goods_id'] = $request['goods_id'];
        }
        if (!empty($request['spec_id'])) {
            $where['a.spec_id'] = $request['spec_id'];
        }
        $result = getRangeWhere($request, 'minTime', 'maxTime');
        if (!isSuccess($result)) {
            return $result;
        }
        $timeWhere = $result['data'];
        if (!empty($timeWhere)) {
            $where['a.order_time'] = $timeWhere;
        }
        return getReturn(CODE_SUCCESS, 'success', $where);
    }

    /**
     * 获取收发明细里列表数据
     * @param int $depotId
     * @param int $page
     * @param int $limit
     * @param $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-06 20:31:22
     * Update: 2018-11-06 20:31:22
     * Version: 1.00
     */
    public function getSendDetailData($depotId = 0, $page = 1, $limit = 20, $condition)
    {
        $where = [];
        $where['a.depot_id'] = $depotId;
        $where['a.is_delete'] = NOT_DELETE;
        $where = array_merge($where, $condition);
        $order = 'id DESC';
        $options = [];
        $options['alias'] = 'a';
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = $order;
        $data = $this->queryList($options)['data'];
        foreach ($data['list'] as $key => $value) {
            $value['order_time_string'] = date('Y-m-d', $value['order_time']);
            $value['type_name'] = $this->autoTypeName($value['type']);
            $data['list'][$key] = $value;
        }
        if (!empty($data['list'])) {
            $field = [
                'SUM(a.stock_in) stock_in', 'SUM(a.stock_out) stock_out', 'SUM(a.stock_result) stock_result'
            ];
            $field = implode(',', $field);
            $options['field'] = $field;
            $meta = $this->selectRow($options);
        } else {
            $meta = [
                'stock_in' => 0,
                'stock_out' => 0,
                'stock_result' => 0,
            ];
        }
        $data['meta'] = $meta;
        return $data;
    }

    /**
     * 判断商品是否有收发记录
     * @param int $goodsId
     * @return boolean
     * User: hjun
     * Date: 2018-11-19 14:30:50
     * Update: 2018-11-19 14:30:50
     * Version: 1.00
     */
    public function isGoodsHasSRData($goodsId = 0)
    {
        return false;
        $where = [];
        $where['goods_id'] = $goodsId;
        $bool = $this->field('id')->where($where)->find();
        return !empty($bool);
    }
}