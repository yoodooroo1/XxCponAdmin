<?php

namespace Common\Model;

/**
 * 盘点单
 * Class DepotInventoryModel.class.php
 * @package Common\Model
 * User: hjun
 * Date: 2018-10-29 11:34:14
 * Update: 2018-10-29 11:34:14
 * Version: 1.00
 */
class DepotInventoryModel extends DepotBaseModel
{
    protected $tableName = 'mb_depot_inventory';

    /**
     * 获取盘点单数据
     * @param int $orderId
     * @return array
     * User: hjun
     * Date: 2018-11-05 16:10:15
     * Update: 2018-11-05 16:10:15
     * Version: 1.00
     */
    public function getDepotInventory($orderId = 0)
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
     * 获取盘点单详细数据
     * @param int $orderId
     * @return array
     * User: hjun
     * Date: 2018-11-06 16:36:38
     * Update: 2018-11-06 16:36:38
     * Version: 1.00
     */
    public function getDepotInventoryDetailData($orderId = 0)
    {
        $order = $this->getDepotInventory($orderId);
        if (!empty($order)) {
            $order['order_time'] = date('Y-m-d', $order['order_time']);
            $order['create_time_string'] = $this->autoTimeString($order['create_time']);
            $order['update_time_string'] = $this->autoTimeString($order['update_time']);
        }
        $order['details'] = D('DepotInventoryDetail')->getOrderDetails($orderId);
        return $order;
    }

    /**
     * 获取盘点操作的数据
     * @param array $request
     * @param int $action
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-05 14:50:44
     * Update: 2018-11-05 14:50:44
     * Version: 1.00
     */
    public function getDepotInventoryActionData($request = [], $action = self::MODEL_INSERT)
    {
        $request['order_type'] = self::TYPE_INVENTORY_ORDER;
        $dpModel = D('DepotGoods');
        if ($action === self::MODEL_INSERT) {
            if (!$dpModel->validateDepot($request['pickup_id'])) {
                return getReturn(CODE_ERROR, "选择的门店已失效");
            }
            $goodsData = $dpModel->getPickupGoodsSpecData($request['pickup_id'], 1, 0);
            $order = $this->getDefaultOrderData($request, $goodsData['list']);
        } else {
            // 盘点单数据
            $order = $this->getDepotInventoryDetailData($request['order_id']);
        }
        $data = [];
        $data['order'] = $order;
        return getReturn(CODE_SUCCESS, 'success', $data);
    }

    /**
     * 获取盘点单数据
     * @param int $orderId
     * @return array
     * User: hjun
     * Date: 2019-01-22 12:03:45
     * Update: 2019-01-22 12:03:45
     * Version: 1.00
     */
    public function getInventoryOrderFromCache($orderId = 0)
    {
        static $info;
        if (isset($info)) {
            return $info;
        }
        $where = [];
        $where['order_id'] = $orderId;
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['where'] = $where;
        $info = $this->selectRow($options);
        return $info;
    }

    /**
     * 验证盘点单据
     * @param int $orderId
     * @return boolean
     * User: hjun
     * Date: 2019-01-22 12:04:35
     * Update: 2019-01-22 12:04:35
     * Version: 1.00
     */
    public function validateOrderId($orderId = 0)
    {
        $info = $this->getInventoryOrderFromCache($orderId);
        if (empty($info)) {
            $this->setValidateError('当前单据已失效');
            return false;
        }
        return true;
    }

    /**
     * 验证单据能否审核
     * @param int $orderId
     * @return boolean
     * User: hjun
     * Date: 2019-01-22 12:05:25
     * Update: 2019-01-22 12:05:25
     * Version: 1.00
     */
    public function validateCanAudit($orderId = 0)
    {
        $info = $this->getInventoryOrderFromCache($orderId);
        if ($info['status'] == 1) {
            $this->setValidateError('当前单据已审核');
            return false;
        }
        return true;
    }

    /**
     * 自动计算盘盈总数
     * @param array $details
     * @return int
     * User: hjun
     * Date: 2019-01-22 14:21:13
     * Update: 2019-01-22 14:21:13
     * Version: 1.00
     */
    public function autoProfitNum($details = [])
    {
        $result = 0;
        foreach ($details as $goods) {
            if ($goods['actual_stock'] > $goods['system_stock']) {
                $result += $goods['actual_stock'] - $goods['system_stock'];
            }
        }
        return $result;
    }

    /**
     * 自动计算盘盈金额
     * @param array $details
     * @return double
     * User: hjun
     * Date: 2019-01-22 14:17:04
     * Update: 2019-01-22 14:17:04
     * Version: 1.00
     */
    public function autoProfitPrice($details = [])
    {
        $result = 0;
        foreach ($details as $goods) {
            if ($goods['actual_stock'] > $goods['system_stock']) {
                $result += ($goods['actual_stock'] - $goods['system_stock']) * $goods['cost_price'];
            }
        }
        return round($result, 2);
    }

    /**
     * 自动计算盘亏总数
     * @param array $details
     * @return int
     * User: hjun
     * Date: 2019-01-22 14:21:33
     * Update: 2019-01-22 14:21:33
     * Version: 1.00
     */
    public function autoLossNum($details = [])
    {
        $result = 0;
        foreach ($details as $goods) {
            if ($goods['actual_stock'] < $goods['system_stock']) {
                $result += $goods['system_stock'] - $goods['actual_stock'];
            }
        }
        return $result;
    }

    /**
     * 自动计算盘亏金额
     * @param array $details
     * @return double
     * User: hjun
     * Date: 2019-01-22 14:16:42
     * Update: 2019-01-22 14:16:42
     * Version: 1.00
     */
    public function autoLossPrice($details = [])
    {
        $result = 0;
        foreach ($details as $goods) {
            if ($goods['actual_stock'] < $goods['system_stock']) {
                $result += ($goods['system_stock'] - $goods['actual_stock']) * $goods['cost_price'];
            }
        }
        return round($result, 2);
    }

    /**
     * 自动完成商品名称
     * @param array $details
     * @return string
     * User: hjun
     * Date: 2019-01-22 20:07:24
     * Update: 2019-01-22 20:07:24
     * Version: 1.00
     */
    public function autoGoodsName($details = [])
    {
        return $details[0]['goods_name'];
    }

    /**
     * 自动完成是否盈亏
     * @param array $details
     * @return int
     * User: hjun
     * Date: 2019-01-22 16:37:25
     * Update: 2019-01-22 16:37:25
     * Version: 1.00
     */
    public function autoIsPl($details = [])
    {
        $result = self::IS_PL_NO;
        foreach ($details as $goods) {
            if ($goods['actual_stock'] > $goods['system_stock']) {
                $result = self::IS_PL_YES;
                break;
            } elseif ($goods['actual_stock'] < $goods['system_stock']) {
                $result = self::IS_PL_YES;
                break;
            }
        }
        return $result;
    }

    /**
     * 自动完成盈亏状态
     * @param array $details
     * @return int
     * User: hjun
     * Date: 2019-01-22 16:37:40
     * Update: 2019-01-22 16:37:40
     * Version: 1.00
     */
    public function autoPlStatus($details = [])
    {
        $lossPrice = $this->autoLossPrice($details);
        $profitPrice = $this->autoProfitPrice($details);
        if ($lossPrice > $profitPrice) {
            $result = self::PL_STATUS_LOSS;
        } elseif ($lossPrice < $profitPrice) {
            $result = self::PL_STATUS_PROFIT;
        } else {
            $result = self::PL_STATUS_NONE;
        }
        return $result;
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
        foreach ($orderDetails as $goods) {
            $result = $srModel->addDepotInventoryDetail($goods['id']);
            if (!isSuccess($result)) {
                return $result;
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
        $validate = [];
        $auto = [];
        switch ($action) {
            case self::MODEL_INSERT:
                $fields = [
                    'store_id', 'depot_id', 'depot_name', 'goods_name', 'order_sn', 'order_time',
                    'remark', 'loss_num', 'loss_price', 'profit_num', 'profit_price', 'is_pl', 'pl_status',
                    'originator_id', 'originator_name', 'create_time', 'update_time',
                    'status', 'audit_id', 'audit_name', 'audit_time',
                ];
                $validate[] = ['depot_id', 'validateDepot', '选择的门店已失效', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
                $validate[] = ['order_time', 'validateOrderTime', '请选择单据日期', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
                $validate[] = ['details', 'validateDetails', '请选择商品', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
                $auto[] = ['store_id', $this->getStoreId(), self::MODEL_INSERT, 'string'];
                $auto[] = ['depot_name', $this->autoDepotName($request['depot_id']), self::MODEL_INSERT, 'string'];
                $auto[] = ['order_sn', empty($request['order_sn']) ? $this->autoOrderSn($request['order_type']) : $request['order_sn'], self::MODEL_INSERT, 'string'];
                $auto[] = ['order_time', $this->autoOrderTime($request['order_time']), self::MODEL_BOTH, 'string'];
                $auto[] = ['originator_id', $request['member_id'], self::MODEL_INSERT, 'string'];
                $auto[] = ['originator_name', $request['member_name'], self::MODEL_INSERT, 'string'];
                $auto[] = ['goods_name', $this->autoGoodsName($request['details']), self::MODEL_INSERT, 'string'];
                $auto[] = ['is_pl', $this->autoIsPl($request['details']), self::MODEL_INSERT, 'string'];
                $auto[] = ['pl_status', $this->autoPlStatus($request['details']), self::MODEL_INSERT, 'string'];
                $auto[] = ['loss_num', $this->autoLossNum($request['details']), self::MODEL_BOTH, 'string'];
                $auto[] = ['profit_num', $this->autoProfitNum($request['details']), self::MODEL_BOTH, 'string'];
                $auto[] = ['loss_price', $this->autoLossPrice($request['details']), self::MODEL_BOTH, 'string'];
                $auto[] = ['profit_price', $this->autoProfitPrice($request['details']), self::MODEL_BOTH, 'string'];
                $auto[] = ['create_time', NOW_TIME, self::MODEL_INSERT, 'string'];
                $auto[] = ['update_time', NOW_TIME, self::MODEL_INSERT, 'string'];
                $auto[] = ['status', 1, self::MODEL_INSERT, 'string'];
                $auto[] = ['audit_id', $request['member_id'], self::MODEL_INSERT, 'string'];
                $auto[] = ['audit_name', $request['member_name'], self::MODEL_INSERT, 'string'];
                $auto[] = ['audit_time', NOW_TIME, self::MODEL_INSERT, 'string'];
                break;
            case self::MODEL_UPDATE:
                $fields = [
                    'goods_name', 'order_time', 'remark', 'loss_num', 'profit_num',
                    'loss_price', 'profit_price', 'update_time', 'is_pl', 'pl_status',
                ];
                $validate[] = ['depot_id', 'validateDepot', '选择的门店已失效', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
                $validate[] = ['order_id', 'validateOrderId', '单据已失效', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE];
                $validate[] = ['order_time', 'validateOrderTime', '请选择单据日期', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
                $validate[] = ['details', 'validateDetails', '请选择商品', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
                $auto[] = ['order_time', $this->autoOrderTime($request['order_time']), self::MODEL_BOTH, 'string'];
                $auto[] = ['is_pl', $this->autoIsPl($request['details']), self::MODEL_INSERT, 'string'];
                $auto[] = ['pl_status', $this->autoPlStatus($request['details']), self::MODEL_INSERT, 'string'];
                $auto[] = ['loss_num', $this->autoLossNum($request['details']), self::MODEL_BOTH, 'string'];
                $auto[] = ['profit_num', $this->autoProfitNum($request['details']), self::MODEL_BOTH, 'string'];
                $auto[] = ['loss_price', $this->autoLossPrice($request['details']), self::MODEL_BOTH, 'string'];
                $auto[] = ['profit_price', $this->autoProfitPrice($request['details']), self::MODEL_BOTH, 'string'];
                $auto[] = ['update_time', NOW_TIME, self::MODEL_BOTH, 'string'];
                break;
            case self::ACTION_AUDIT:
                $fields = [
                    'goods_name', 'order_time', 'remark', 'loss_price', 'profit_price',
                    'loss_num', 'profit_num', 'status', 'is_pl', 'pl_status',
                    'audit_id', 'audit_name', 'audit_time', 'update_time',
                ];
                $validate[] = ['depot_id', 'validateDepot', '选择的门店已失效', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
                $validate[] = ['order_id', 'validateOrderId', '单据已失效', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE];
                $validate[] = ['order_id', 'validateCanAudit', '单据已经审核', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE];
                $validate[] = ['order_time', 'validateOrderTime', '请选择单据日期', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
                $validate[] = ['details', 'validateDetails', '请选择商品', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
                $auto[] = ['order_time', $this->autoOrderTime($request['order_time']), self::MODEL_BOTH, 'string'];
                $auto[] = ['is_pl', $this->autoIsPl($request['details']), self::MODEL_INSERT, 'string'];
                $auto[] = ['pl_status', $this->autoPlStatus($request['details']), self::MODEL_INSERT, 'string'];
                $auto[] = ['loss_num', $this->autoLossNum($request['details']), self::MODEL_BOTH, 'string'];
                $auto[] = ['profit_num', $this->autoProfitNum($request['details']), self::MODEL_BOTH, 'string'];
                $auto[] = ['loss_price', $this->autoLossPrice($request['details']), self::MODEL_BOTH, 'string'];
                $auto[] = ['profit_price', $this->autoProfitPrice($request['details']), self::MODEL_BOTH, 'string'];
                $auto[] = ['update_time', NOW_TIME, self::MODEL_BOTH, 'string'];
                $auto[] = ['status', 1, self::MODEL_UPDATE, 'string'];
                $auto[] = ['audit_id', $request['member_id'], self::MODEL_UPDATE, 'string'];
                $auto[] = ['audit_name', $request['member_name'], self::MODEL_UPDATE, 'string'];
                $auto[] = ['audit_time', NOW_TIME, self::MODEL_UPDATE, 'string'];
                break;
            default:
                return getReturn(CODE_ERROR, 'error action');
                break;
        }
        $isAudit = $action == self::ACTION_AUDIT;
        $type = $action;
        if ($isAudit) {
            $type = self::MODEL_UPDATE;
        }
        $result = $this->getAndValidateDataFromRequest($fields, $request, $validate, $auto, $type);
        if (!isSuccess($result)) {
            return $result;
        }
        $data = $result['data'];

        $this->startTrans();

        // 保存入盘点单
        if ($type === self::MODEL_INSERT) {
            $data['order_id'] = $this->add($data);
            $result = $data['order_id'];
        } else {
            $where = [];
            $where['order_id'] = $request['order_id'];
            $result = $this->where($where)->save($data);
            $order = $this->getInventoryOrderFromCache($request['order_id']);
            $data = array_merge($order, $data);
        }
        if (false === $result) {
            $this->rollback();
            return getReturn(CODE_ERROR);
        }

        // 保存盘点单明细
        $data['details'] = $request['details'];
        $result = D('DepotInventoryDetail')->saveDepotInventoryDetail($data, $type);
        if (!isSuccess($result)) {
            $this->rollback();
            return $result;
        }
        $orderDetails = $result['data'];

        // 盘点无审核 直接处理商品库存变动
        $result = $this->auditAction($orderDetails);
        if (!isSuccess($result)) {
            $this->rollback();
            return $result;
        }

        $this->commit();
        return getReturn(CODE_SUCCESS, '保存成功', $data);
    }

    /**
     * 新增盘点单
     * @param array $request
     * @param int $originatorId
     * @param string $originatorName
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-05 16:06:20
     * Update: 2018-11-05 16:06:20
     * Version: 1.00
     */
    public function addDepotInventory($request = [], $originatorId = 0, $originatorName = '')
    {
        $request['member_id'] = $originatorId;
        $request['member_name'] = $originatorName;
        return $this->depotOrderAction($request, self::MODEL_INSERT);
    }

    /**
     * 修改盘点单
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-05 16:06:53
     * Update: 2018-11-05 16:06:53
     * Version: 1.00
     */
    public function updateDepotInventory($request = [])
    {
        return $this->depotOrderAction($request, self::MODEL_UPDATE);
    }

    /**
     * 审核盘点单
     * @param array $request
     * @param int $auditId
     * @param string $auditName
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-06 09:32:36
     * Update: 2018-11-06 09:32:36
     * Version: 1.00
     */
    public function auditDepotInventory($request = [], $auditId = 0, $auditName = '')
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
        if (!empty($request['order_sn'])) {
            $where['a.order_sn'] = $request['order_sn'];
        }
        if (!empty($request['originator_name'])) {
            $where['a.originator_name'] = ['like', "%{$request['originator_name']}%"];
        }
        if (!empty($request['remark'])) {
            $where['a.remark'] = ['like', "%{$request['remark']}%"];
        }
        if (!empty($request['is_pl'])) {
            $where['a.is_pl'] = $request['is_pl'] - 1;
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
     * 获取盘点库记录数据
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
    public function getDepotInventoryListData($depotId = 0, $page = 1, $limit = 20, $condition = [])
    {
        $where = [];
        $where['a.depot_id'] = $depotId;
        $where['a.is_delete'] = NOT_DELETE;
        $where = array_merge($where, $condition);
        $order = 'a.order_id DESC';
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
                $orderDetails = D('DepotInventoryDetail')->getOrderDetails($orderId);
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