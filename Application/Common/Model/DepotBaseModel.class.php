<?php

namespace Common\Model;

/**
 * 仓库基类
 * Class DepotBaseModel
 * @package Common\Model
 * User: hjun
 * Date: 2018-10-29 11:34:14
 * Update: 2018-10-29 11:34:14
 * Version: 1.00
 */
class DepotBaseModel extends BaseModel
{
    const TYPE_OTHER_IN = 101; // 其他入库
    const TYPE_REFUND_IN = 102; // 退货入库
    const TYPE_INVENTORY_IN = 103; // 盘盈入库
    const TYPE_SALE_OUT = 201; // 销售出库
    const TYPE_OTHER_OUT = 202; // 其他出库
    const TYPE_INVENTORY_OUT = 203; // 盘亏出库
    const TYPE_INVENTORY_ORDER = 'inventory_order'; // 盘点单
    const STATUS_ON_SALE = 1; // 销售中
    const STATUS_OFF_SHELF = 0; // 已下架
    const ACTION_DELETE = self::MODEL_DELETE; // 删除
    const ACTION_SHELF = 5; // 上架
    const ACTION_OFF_SHELF = 6; // 下架
    const ACTION_AUDIT = 1001;
    const STOCK_OUT = 1;
    const STOCK_IN = 0;
    const PICKUP = 0;
    const DEPOT = 1;

    const IS_PL_YES = 1; // 有盈亏
    const IS_PL_NO = 0; // 无盈亏
    const PL_STATUS_NONE = 0; // 无盈亏
    const PL_STATUS_PROFIT = 1; // 盈
    const PL_STATUS_LOSS = 2; // 亏

    /**
     * 根据请求的类型 获取相应的属性值
     * @param string $orderType
     * @param string $property
     * @return mixed
     * User: hjun
     * Date: 2019-01-16 17:47:12
     * Update: 2019-01-16 17:47:12
     * Version: 1.00
     */
    public function getPropertyByRequestType($orderType = '', $property = '')
    {
        if (empty($property)) {
            $property = 'order_type';
        }
        switch ($orderType) {
            case self::TYPE_OTHER_OUT:
                $result = [
                    'order_type' => self::TYPE_OTHER_OUT,
                    'order_sn_prefix' => 'QTCK',
                    'add_log' => '商品出库',
                    'update_log' => '修改出库单',
                    'audit_log' => '审核出库单',
                    'batch_audit_log' => '批量审核出库单',
                    'batch_delete_log' => '批量删除出库单',
                    'excel_name' => '出库记录',
                    'stock_key' => 'stock_out',
                    'stock_action' => self::STOCK_OUT,
                ];
                break;
            case self::TYPE_INVENTORY_ORDER:
                $result = [
                    'order_type' => self::TYPE_INVENTORY_ORDER,
                    'order_sn_prefix' => 'PD',
                    'add_log' => '商品盘点',
                    'update_log' => '修改盘点单',
                    'audit_log' => '审核盘点单',
                    'batch_audit_log' => '批量审核盘点单',
                    'batch_delete_log' => '批量删除盘点单',
                    'excel_name' => '盘点记录',
                    'stock_key' => 'stock_in',
                    'stock_action' => self::STOCK_IN,
                ];
                break;
            case self::TYPE_OTHER_IN:
            default:
                $result = [
                    'order_type' => self::TYPE_OTHER_IN,
                    'order_sn_prefix' => 'QTRK',
                    'add_log' => '商品入库',
                    'update_log' => '修改入库单',
                    'audit_log' => '审核入库单',
                    'batch_audit_log' => '批量审核入库单',
                    'batch_delete_log' => '批量删除入库单',
                    'excel_name' => '入库记录',
                    'stock_key' => 'stock_in',
                    'stock_action' => self::STOCK_IN,
                ];
                break;
        }
        return $result[$property];
    }

    /**
     * 根据请求 获取单据类型
     * @param string $orderType
     * @return int
     * User: hjun
     * Date: 2019-01-16 17:37:01
     * Update: 2019-01-16 17:37:01
     * Version: 1.00
     */
    public function getOrderTypeByRequestType($orderType = '')
    {
        return $this->getPropertyByRequestType($orderType, 'order_type');
    }

    /**
     * 获取默认的数据
     * @param array $request
     * @param array $goodsList
     * @return array
     * User: hjun
     * Date: 2018-11-07 16:15:22
     * Update: 2018-11-07 16:15:22
     * Version: 1.00
     */
    public function getDefaultOrderData($request = [], $goodsList = [])
    {
        $order = [
            'depot_id' => $request['pickup_id'], 'order_time' => date('Y-m-d'), 'order_sn' => $this->autoOrderSn($request['order_type']),
            'type' => $this->getOrderTypeByRequestType($request['order_type']), 'details' => [], 'remark' => '',
            'originator_id' => $request['originator_id'], 'originator_name' => $request['originator_name']
        ];
        if (!empty($request['select_ids'])) {
            $ids = explode('-', $request['select_ids']);
            foreach ($ids as $id) {
                foreach ($goodsList as $spec) {
                    if ($spec['id'] == $id) {
                        $order['details'][] = [
                            'goods_id' => $spec['goods_id'],
                            'goods_name' => $spec['goods_name'],
                            'spec_id' => $spec['spec_id'],
                            'spec_name' => $spec['spec_name'],
                            'goods_barcode' => $spec['goods_barcode'],
                            'depot_id' => $request['pickup_id'],
                            'depot_name' => '',
                            'goods_num' => '1',
                            'goods_price' => round($spec['cost_price'], 2),
                            'total_price' => round($spec['cost_price'], 2),
                            'system_stock' => $spec['system_stock'], // 系统库存
                            'actual_stock' => $spec['system_stock'], // 实际库存
                            'cost_price' => round($spec['cost_price'], 2), // 成本价格
                            'remark' => ''
                        ];
                    }
                }
            }
        } elseif (!empty($request['copyid'])) {
            // 复制操作
            switch ($order['type']) {
                case self::TYPE_OTHER_IN:
                case self::TYPE_OTHER_OUT:
                    $details = D('DepotOrderDetail')->getOrderDetails($request['copyid']);
                    break;
                case self::TYPE_INVENTORY_ORDER:
                    $details = D('DepotInventoryDetail')->getOrderDetails($request['copyid']);
                    break;
            }
            if (!empty($details)) {
                foreach ($details as $goods) {
                    foreach ($goodsList as $spec) {
                        if ($spec['goods_id'] == $goods['goods_id'] && $spec['spec_id'] == $goods['spec_id']) {
                            $order['details'][] = [
                                'goods_id' => $spec['goods_id'],
                                'goods_name' => $spec['goods_name'],
                                'spec_id' => $spec['spec_id'],
                                'spec_name' => $spec['spec_name'],
                                'goods_barcode' => $spec['goods_barcode'],
                                'depot_id' => $request['pickup_id'],
                                'depot_name' => '',
                                'goods_num' => $goods['goods_num'],
                                'goods_price' => $goods['goods_price'],
                                'total_price' => $goods['total_price'],
                                'system_stock' => $spec['system_stock'], // 系统库存
                                'actual_stock' => $goods['system_stock'], // 实际库存
                                'cost_price' => round($spec['cost_price'], 2), // 成本价格
                                'remark' => $goods['remark'],
                            ];
                        }
                    }
                }
            }
        }
        return $order;
    }

    /**
     * 验证单据日期
     * @param string $orderTime
     * @return boolean
     * User: hjun
     * Date: 2018-11-01 10:16:26
     * Update: 2018-11-01 10:16:26
     * Version: 1.00
     */
    public function validateOrderTime($orderTime = '')
    {
        $time = strtotime($orderTime);
        return $time !== false;
    }

    /**
     * 验证明细
     * @param array $details
     * @return boolean
     * User: hjun
     * Date: 2018-11-05 15:43:52
     * Update: 2018-11-05 15:43:52
     * Version: 1.00
     */
    public function validateDetails($details = [])
    {
        return !empty($details);
    }

    /**
     * 生成order_sn
     * @param string $orderType
     * @return mixed
     * User: hjun
     * Date: 2018-10-31 17:30:23
     * Update: 2018-10-31 17:30:23
     * Version: 1.00
     */
    public function autoOrderSn($orderType = '')
    {
        $prefix = $this->getPropertyByRequestType($orderType, 'order_sn_prefix');
        $date = date('Ymd', NOW_TIME);
        $rand = \Org\Util\String::randString(7, 1);
        return "{$prefix}{$date}{$rand}";
    }

    /**
     * 生成单据日期
     * @param string $time
     * @return string
     * User: hjun
     * Date: 2018-10-31 17:34:57
     * Update: 2018-10-31 17:34:57
     * Version: 1.00
     */
    public function autoOrderTime($time = '')
    {
        return strtotime($time);
    }

    /**
     * 生成数量总数
     * @param array $detailsList
     * @return int
     * User: hjun
     * Date: 2018-10-31 17:48:41
     * Update: 2018-10-31 17:48:41
     * Version: 1.00
     */
    public function autoTotalNum($detailsList = [])
    {
        $num = 0;
        foreach ($detailsList as $goods) {
            $num += $goods['goods_num'];
        }
        return $num;
    }

    /**
     * 生成总金额
     * @param array $detailsList
     * @return double
     * User: hjun
     * Date: 2018-10-31 17:49:20
     * Update: 2018-10-31 17:49:20
     * Version: 1.00
     */
    public function autoTotalPrice($detailsList = [])
    {
        $price = 0;
        foreach ($detailsList as $goods) {
            $price += $goods['goods_num'] * $goods['goods_price'];
        }
        return round($price, 2);
    }

    /**
     * 获取商品数据
     * @param int $goodsId
     * @return array
     * User: hjun
     * Date: 2018-11-01 16:02:29
     * Update: 2018-11-01 16:02:29
     * Version: 1.00
     */
    public function getGoods($goodsId = 0)
    {
        $info = $this->getLastQueryData("goods_{$goodsId}");
        if (empty($info)) {
            $info = D('GoodsExtra')->getGoods($goodsId);
            $this->setLastQueryData("goods_{$goodsId}", $info);
        }
        return $info;
    }

    /**
     * 获取仓库数据
     * @param int $depotId
     * @return array
     * User: hjun
     * Date: 2018-11-05 15:59:32
     * Update: 2018-11-05 15:59:32
     * Version: 1.00
     */
    public function getDepot($depotId = 0)
    {
        $info = $this->getLastQueryData("depot_{$depotId}");
        if (empty($info)) {
            $info = D('Depot')->getDepot($depotId);
            $this->setLastQueryData("depot_{$depotId}");
        }
        return $info;
    }

    /**
     * 获取库单数据
     * @param int $orderId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-07 09:27:59
     * Update: 2018-11-07 09:27:59
     * Version: 1.00
     */
    public function getOrder($orderId = 0)
    {
        $info = $this->getLastQueryData("depot_order_{$orderId}");
        if (empty($info)) {
            $info = D('DepotOrder')->getDepotOrder($orderId);
            $this->setLastQueryData("depot_order_{$orderId}");
        }
        return $info;
    }

    /**
     * 验证仓库
     * @param int $depotId
     * @return boolean
     * User: hjun
     * Date: 2018-10-31 15:49:51
     * Update: 2018-10-31 15:49:51
     * Version: 1.00
     */
    public function validateDepot($depotId = 0)
    {
        $pickup = $this->getDepot($depotId);
        if (empty($pickup)) {
            $this->setValidateError("选择的仓库已失效");
            return false;
        }
        return true;
    }

    /**
     * 验证批量操作的ID数组
     * @param array $ids
     * @return boolean
     * User: hjun
     * Date: 2018-11-05 14:24:11
     * Update: 2018-11-05 14:24:11
     * Version: 1.00
     */
    protected function validateBatchIds($ids = [])
    {
        if (empty($ids)) {
            return false;
        }
        if (!is_array($ids)) {
            return false;
        }
        return true;
    }

    /**
     * 验证商品
     * @param int $goodsId
     * @return boolean
     * User: hjun
     * Date: 2018-10-31 16:06:50
     * Update: 2018-10-31 16:06:50
     * Version: 1.00
     */
    public function validateGoods($goodsId = 0)
    {
        $model = D('GoodsExtra');
        $goods = $this->getGoods($goodsId);
        if (empty($goods)) {
            $this->setValidateError("商品ID【{$goodsId}】已失效");
            return false;
        }
        if ($goods['spec_type'] != $model::SPEC_TYPE_GROUP) {
            $this->setValidateError("商品【{$goods['goods_name']}】不是多规格商品");
            return false;
        }
        return true;
    }

    /**
     * 验证规格
     * @param int $specId
     * @param int $goodsId
     * @return boolean
     * User: hjun
     * Date: 2018-11-06 11:13:20
     * Update: 2018-11-06 11:13:20
     * Version: 1.00
     */
    public function validateGoodsSpec($specId = 0, $goodsId = 0)
    {
        $info = $this->getGoods($goodsId);
        foreach ($info['spec_attr'] as $spec) {
            if ($specId == $spec['primary_id']) {
                return true;
            }
        }
        $this->setValidateError("商品选择的规格已失效");
        return false;
    }

    /**
     * 验证库单
     * @param int $orderId
     * @return boolean
     * User: hjun
     * Date: 2018-11-05 16:11:23
     * Update: 2018-11-05 16:11:23
     * Version: 1.00
     */
    public function validateOrderId($orderId = 0)
    {
        $info = $this->getOrder($orderId);
        return !empty($info);
    }

    /**
     * 验证是否可以审核
     * @param int $orderId
     * @return boolean
     * User: hjun
     * Date: 2018-11-07 09:29:10
     * Update: 2018-11-07 09:29:10
     * Version: 1.00
     */
    public function validateCanAudit($orderId = 0)
    {
        $info = $this->getOrder($orderId);
        return $info['status'] == 0;
    }

    /**
     * 自动获取类型名称
     * @param int $type
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-06 20:34:30
     * Update: 2018-11-06 20:34:30
     * Version: 1.00
     */
    public function autoTypeName($type = 0)
    {
        $name = [
            self::TYPE_OTHER_IN => '其他入库',
            self::TYPE_REFUND_IN => '退货入库',
            self::TYPE_SALE_OUT => '销售出库',
            self::TYPE_OTHER_OUT => '其他出库',
            self::TYPE_INVENTORY_IN => '盘盈入库',
            self::TYPE_INVENTORY_OUT => '盘亏出库',
        ];
        return $name[$type];
    }

    /**
     * 自动完成商品状态名称
     * @param int $status
     * @return string
     * User: hjun
     * Date: 2018-11-06 23:37:40
     * Update: 2018-11-06 23:37:40
     * Version: 1.00
     */
    public function autoGoodsStatusName($status = self::STATUS_ON_SALE)
    {
        $name = [
            self::STATUS_ON_SALE => '销售中',
            self::STATUS_OFF_SHELF => '已下架',
        ];
        return $name[$status];
    }

    /**
     * 自动转换时间
     * @param int $time
     * @return string
     * User: hjun
     * Date: 2018-11-06 20:30:39
     * Update: 2018-11-06 20:30:39
     * Version: 1.00
     */
    public function autoTimeString($time = 0)
    {
        return date('Y-m-d H:i:s', $time);
    }

    /**
     * 自动完成仓库名称
     * @param int $depotId
     * @return string
     * User: hjun
     * Date: 2018-11-06 11:24:28
     * Update: 2018-11-06 11:24:28
     * Version: 1.00
     */
    public function autoDepotName($depotId = 0)
    {
        $info = $this->getDepot($depotId);
        return $info['store_name'];
    }
}