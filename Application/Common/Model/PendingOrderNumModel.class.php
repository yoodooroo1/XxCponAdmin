<?php

namespace Common\Model;
/**
 * Class PendingOrderNumModel.class.php
 * User: hj
 * Date: 2017-10-27 00:27:51
 * Desc: 挂单序号
 * Update: 2017-10-27 00:27:54
 * Version: 1.0
 * @package Common\Model
 */
class PendingOrderNumModel extends BaseModel
{
    protected $tableName = 'mb_pendingorder_nums';

    /**
     * 挂单方案修改后的处理
     * @param int $pendOrderId
     * @param array $pendNum
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2019-03-19 10:52:26
     * Update: 2019-03-19 10:52:26
     * Version: 1.00
     */
    public function afterPendOrderUpdate($pendOrderId = 0, $pendNum = [])
    {
        $newItem = [];
        $addItem = [];
        $results = [];
        foreach ($pendNum as $value) {
            if ($value['id'] > 0) {
                $addItem[] = [
                    'id' => $value['id'],
                    'name' => $value['text'],
                    'store_id' => $this->getStoreId(),
                    'pend_order_id' => $pendOrderId,
                ];
            } else {
                $newItem[] = [
                    'name' => $value['text'],
                    'store_id' => $this->getStoreId(),
                    'pend_order_id' => $pendOrderId,
                ];
            }

        }
        // 先把所有相关的都删除
        $where = [];
        $where['pend_order_id'] = $pendOrderId;
        $results[] = $this->where($where)->delete();

        // 新增
        if (!empty($addItem)) {
            $results[] = $this->addAll($addItem);
        }
        if (!empty($newItem)) {
            $results[] = $this->addAll($newItem);
        }
        if (isTransFail($results)) {
            return getReturn(CODE_ERROR);
        }
        return getReturn(CODE_SUCCESS, 'success');
    }
}