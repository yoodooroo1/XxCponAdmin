<?php

namespace Common\Model;

/**
 * 入库单
 * Class DepotOrderModel
 * @package Common\Model
 * User: hjun
 * Date: 2018-10-29 11:34:14
 * Update: 2018-10-29 11:34:14
 * Version: 1.00
 */
class DepotOrderModel extends DepotBaseModel
{
    protected $tableName = 'mb_depot_order';

    /**
     * 获取库单数据
     * @param int $orderId
     * @return array
     * User: hjun
     * Date: 2018-11-05 16:10:15
     * Update: 2018-11-05 16:10:15
     * Version: 1.00
     */
    public function getDepotOrder($orderId = 0)
    {
        $where = [];
        $where['order_id'] = $orderId;
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['where'] = $where;
        $info = $this->selectRow($options);
        return $info;
    }

    /**
     * 获取库单详细数据
     * @param int $orderId
     * @return array
     * User: hjun
     * Date: 2018-11-06 16:36:38
     * Update: 2018-11-06 16:36:38
     * Version: 1.00
     */
    public function getDepotOrderDetailData($orderId = 0)
    {
        $order = $this->getDepotOrder($orderId);
        if (!empty($order)) {
            $order['order_time'] = date('Y-m-d', $order['order_time']);
            $order['create_time_string'] = $this->autoTimeString($order['create_time']);
            $order['update_time_string'] = $this->autoTimeString($order['update_time']);
        }
        $order['details'] = D('DepotOrderDetail')->getOrderDetails($orderId);
        return $order;
    }

    /**
     * 获取仓库操作的数据
     * @param array $request
     * @param int $action
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-05 14:50:44
     * Update: 2018-11-05 14:50:44
     * Version: 1.00
     */
    public function getDepotOrderActionData($request = [], $action = self::MODEL_INSERT)
    {
        $dpModel = D('DepotGoods');
        if ($action === self::MODEL_INSERT) {
            if (!$dpModel->validateDepot($request['pickup_id'])) {
                return getReturn(CODE_ERROR, "选择的门店已失效");
            }
            $goodsData = $dpModel->getPickupGoodsSpecData($request['pickup_id'], 1, 0);
            $order = $this->getDefaultOrderData($request, $goodsData['list']);
        } else {
            // 库单数据
            $order = $this->getDepotOrderDetailData($request['order_id']);
        }
        $data = [];
        $data['order'] = $order;
        return getReturn(CODE_SUCCESS, 'success', $data);
    }

    /**
     * 审核操作需要进行的动作
     * @param array $orderDetails
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-07 22:47:38
     * Update: 2018-11-07 22:47:38
     * Version: 1.00
     */
    public function auditAction($orderDetails = [])
    {
        $srModel = D('DepotSendDetail');
        $goodsIds = [];
        $type = self::TYPE_OTHER_OUT;
        foreach ($orderDetails as $goods) {
            $result = $srModel->addDepotOrderDetail($goods['id']);
            if (!isSuccess($result)) {
                return $result;
            }
            $type = $result['data']['type'];
            $goodsIds[] = $goods['goods_id'];
        }
        // 审核入库之后 需要计算平均成本价
        if ($type == self::TYPE_OTHER_IN) {
            // 1. 查出本次入库的商品所有的入库记录 计算出每个商品的平均价格
            $model = D('DepotOrderDetail');
            $field = [
                'a.goods_id', 'a.depot_id', 'a.spec_id', 'AVG(a.goods_price) avg_goods_price'
            ];
            $field = implode(',', $field);
            $join = [
                '__MB_DEPOT_ORDER__ b ON a.order_id = b.order_id'
            ];
            $where = [];
            $where['a.goods_id'] = getInSearchWhereByArr($goodsIds);
            $where['b.status'] = 1;
            $where['b.type'] = self::TYPE_OTHER_IN;
            $group = 'a.goods_id,a.spec_id';
            $options = [];
            $options['alias'] = 'a';
            $options['field'] = $field;
            $options['join'] = $join;
            $options['where'] = $where;
            $options['group'] = $group;
            $records = $model->selectList($options);
            // 2. 更新每个商品的价格
            $model = D('DepotGoods');
            foreach ($records as $goods) {
                $where = [];
                $where['goods_id'] = $goods['goods_id'];
                $where['spec_id'] = $goods['spec_id'];
                $where['depot_id'] = $goods['depot_id'];
                $result = $model->where($where)->setField('goods_price', $goods['avg_goods_price']);
                if (false === $result) {
                    return getReturn(CODE_ERROR);
                }
            }
        }



        return getReturn(CODE_SUCCESS);
    }

    /**
     * 单据操作
     * @param array $request
     * @param int $action
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-10-31 17:02:06
     * Update: 2018-10-31 17:02:06不
     * Version: 1.00
     */
    public function depotOrderAction($request = [], $action = self::MODEL_INSERT)
    {
        $isAudit = $action === self::ACTION_AUDIT;
        if ($action === self::MODEL_INSERT) {
            $fields = [
                'store_id', 'depot_id', 'depot_name', 'order_sn', 'order_time', 'remark',
                'originator_id', 'originator_name', 'type', 'stock_type',
                'total_num', 'total_price', 'create_time', 'update_time'
            ];
        } elseif ($isAudit) {
            $fields = [
                'order_time', 'remark', 'total_num', 'total_price',
                'status', 'audit_id', 'audit_name', 'audit_time', 'update_time'
            ];
        } elseif ($action === self::MODEL_UPDATE) {
            $fields = [
                'order_time', 'remark',
                'total_num', 'total_price', 'update_time'
            ];
        }
        $validate = [];
        $validate[] = ['depot_id', 'validateDepot', '选择的仓库已失效', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
        $validate[] = ['order_id', 'validateOrderId', '单据已失效', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE];
        if ($isAudit) {
            $validate[] = ['order_id', 'validateCanAudit', '单据已经审核', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE];
        }
        $validate[] = ['order_time', 'validateOrderTime', '请选择单据日期', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
        $validate[] = ['details', 'validateDetails', '请选择商品', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
        $auto = [];
        $auto[] = ['store_id', $this->getStoreId(), self::MODEL_INSERT, 'string'];
        $auto[] = ['depot_name', $this->autoDepotName($request['depot_id']), self::MODEL_INSERT, 'string'];
        $auto[] = ['order_sn', empty($request['order_sn']) ? $this->autoOrderSn($request['order_type']) : $request['order_sn'], self::MODEL_INSERT, 'string'];
        $auto[] = ['order_time', $this->autoOrderTime($request['order_time']), self::MODEL_BOTH, 'string'];
        $auto[] = ['stock_type', $this->getPropertyByRequestType($request['order_type'], 'stock_action'), self::MODEL_INSERT, 'string'];
        $auto[] = ['type', $this->getPropertyByRequestType($request['order_type'], 'order_type'), self::MODEL_INSERT, 'string'];
        $auto[] = ['originator_id', $request['member_id'], self::MODEL_INSERT, 'string'];
        $auto[] = ['originator_name', $request['member_name'], self::MODEL_INSERT, 'string'];
        $auto[] = ['total_num', $this->autoTotalNum($request['details']), self::MODEL_BOTH, 'string'];
        $auto[] = ['total_price', $this->autoTotalPrice($request['details']), self::MODEL_BOTH, 'string'];
        $auto[] = ['create_time', NOW_TIME, self::MODEL_INSERT, 'string'];
        $auto[] = ['update_time', NOW_TIME, self::MODEL_BOTH, 'string'];
        if ($isAudit) {
            $auto[] = ['status', 1, self::MODEL_UPDATE, 'string'];
            $auto[] = ['audit_id', $request['member_id'], self::MODEL_UPDATE, 'string'];
            $auto[] = ['audit_name', $request['member_name'], self::MODEL_UPDATE, 'string'];
            $auto[] = ['audit_time', NOW_TIME, self::MODEL_UPDATE, 'string'];
        }
        if ($isAudit) $action = self::MODEL_UPDATE;
        $result = $this->getAndValidateDataFromRequest($fields, $request, $validate, $auto, $action);
        if (!isSuccess($result)) {
            return $result;
        }
        $data = $result['data'];

        $this->startTrans();

        // 保存入库单
        if ($action === self::MODEL_INSERT) {
            $data['order_id'] = $this->add($data);
            $result = $data['order_id'];
        } else {
            $where = [];
            $where['order_id'] = $request['order_id'];
            $result = $this->where($where)->save($data);
            $order = $this->getOrder($request['order_id']);
            $data = array_merge($order, $data);
        }
        if (false === $result) {
            $this->rollback();
            return getReturn(CODE_ERROR);
        }

        // 保存入库单明细
        $data['details'] = $request['details'];
        $result = D('DepotOrderDetail')->saveDepotOrderDetail($data, $action);
        if (!isSuccess($result)) {
            $this->rollback();
            return $result;
        }
        $orderDetails = $result['data'];

        // 如果是审核 处理商品库存变动
        if ($isAudit) {
            $result = $this->auditAction($orderDetails);
            if (!isSuccess($result)) {
                $this->rollback();
                return $result;
            }

        }
        $this->commit();
        return getReturn(CODE_SUCCESS, '保存成功', $data);
    }

    /**
     * 新增库单
     * @param array $request
     * @param int $originatorId
     * @param string $originatorName
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-05 16:06:20
     * Update: 2018-11-05 16:06:20
     * Version: 1.00
     */
    public function addDepotOrder($request = [], $originatorId = 0, $originatorName = '')
    {
        $request['member_id'] = $originatorId;
        $request['member_name'] = $originatorName;
        return $this->depotOrderAction($request, self::MODEL_INSERT);
    }

    /**
     * 修改库单
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-05 16:06:53
     * Update: 2018-11-05 16:06:53
     * Version: 1.00
     */
    public function updateDepotOrder($request = [])
    {
        return $this->depotOrderAction($request, self::MODEL_UPDATE);
    }

    /**
     * 审核库单
     * @param array $request
     * @param int $auditId
     * @param string $auditName
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-06 09:32:36
     * Update: 2018-11-06 09:32:36
     * Version: 1.00
     */
    public function auditDepotOrder($request = [], $auditId = 0, $auditName = '')
    {
        $request['status'] = 1;
        $request['member_id'] = $auditId;
        $request['member_name'] = $auditName;
        return $this->depotOrderAction($request, self::ACTION_AUDIT);
    }

    /**
     * 获取搜索条件
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-06 21:16:37
     * Update: 2018-11-06 21:16:37
     * Version: 1.00
     */
    public function getSearchWhere($request = [])
    {
        $where = [];
        // 业务类型
        if (empty($request['stock_type'])) {
            $where['a.stock_type'] = self::STOCK_IN;
        } elseif ($request['stock_type'] == 'all') {

        } else {
            $where['a.stock_type'] = $request['stock_type'];
        }

        if (!empty($request['order_sn'])) {
            $where['a.order_sn'] = $request['order_sn'];
        }
        if (!empty($request['originator_name'])) {
            $where['a.originator_name'] = ['like', "%{$request['originator_name']}%"];
        }
        if (!empty($request['remark'])) {
            $where['a.remark'] = ['like', "%{$request['remark']}%"];
        }
        if (!empty($request['status'])) {
            $where['a.status'] = $request['status'] - 1;
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
     * 获取仓库库记录数据
     * @param int $depotId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array
     * User: hjun
     * Date: 2018-11-06 21:26:07
     * Update: 2018-11-06 21:26:07
     * Version: 1.00
     */
    public function getDeportOrderListData($depotId = 0, $page = 1, $limit = 20, $condition = [])
    {
        $where = [];
        $where['a.depot_id'] = $depotId;
        $where['a.is_delete'] = NOT_DELETE;
        $where = array_merge($where, $condition);
        $order = 'order_id DESC';
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
        return $data;
    }

    /**
     * 批量操作
     * @param array $request
     * @param int $action
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-07 11:05:19
     * Update: 2018-11-07 11:05:19
     * Version: 1.00
     */
    public function batchAction($request = [], $action = self::ACTION_AUDIT)
    {
        if (!$this->validateDepot($request['pickup_id'])) {
            return getReturn(CODE_NOT_FOUND, "当前门店已失效");
        }
        if (!$this->validateBatchIds($request['order_ids'])) {
            return getReturn(CODE_ERROR, "请选择商品");
        }
        $this->startTrans();
        foreach ($request['order_ids'] as $key => $orderId) {
            $index = $key + 1;
            if (!$this->validateOrderId($orderId)) {
                return getReturn(CODE_NOT_FOUND, "选择的的第{$index}个单据已失效,请重新选择");
            }
            $order = $this->getOrder($orderId);
            if ($order['depot_id'] != $request['pickup_id']) {
                return getReturn(CODE_NOT_FOUND, "选择的的第{$index}个单据已失效,请重新选择,错误码:404");
            }
            if ($order['status'] == 1) {
                return getReturn(CODE_NOT_FOUND, "选择的的第{$index}个单据已审核,不可操作,请重新选择");
            }
            $where = [];
            $where['order_id'] = $orderId;
            $data = [];
            $data['update_time'] = time();
            if ($action === self::ACTION_DELETE) {
                $data['is_delete'] = DELETED;
            } elseif ($action === self::ACTION_AUDIT) {
                $data['status'] = 1;
                $data['audit_id'] = $request['audit_id'];
                $data['audit_name'] = $request['audit_name'];
                $data['audit_time'] = time();
            }
            $result = $this->where($where)->save($data);

            if (false === $result) {
                $this->rollback();
                return getReturn(CODE_ERROR);
            }

            if ($action === self::ACTION_AUDIT) {
                $orderDetails = D('DepotOrderDetail')->getOrderDetails($orderId);
                $result = $this->auditAction($orderDetails);
                if (!isSuccess($result)) {
                    $this->rollback();
                    return $result;
                }
            }
        }
        $this->commit();
        $msg = [
            self::ACTION_AUDIT => '审核成功',
            self::ACTION_DELETE => '删除成功',
        ];
        return getReturn(CODE_SUCCESS, $msg[$action]);
    }

    /**
     * 批量审核
     * @param array $request
     * @param int $auditId
     * @param string $auditName
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-07 10:51:40
     * Update: 2018-11-07 10:51:40
     * Version: 1.00
     */
    public function batchAudit($request = [], $auditId = 0, $auditName = '')
    {
        $request['audit_id'] = $auditId;
        $request['audit_name'] = $auditName;
        return $this->batchAction($request, self::ACTION_AUDIT);
    }

    /**
     * 批量删除
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-07 10:51:47
     * Update: 2018-11-07 10:51:47
     * Version: 1.00
     */
    public function batchDelete($request = [])
    {
        return $this->batchAction($request, self::ACTION_DELETE);
    }
}