<?php

namespace Common\PHPUnit;

class PendingOrderTest extends BaseTest
{
    public function testUpdate()
    {
        // 挂单序号用关联表存储
        $model = D('PendingOrder');
        $where = [];
        $where['is_delete'] = 0;
        $list = $model->where($where)->select();
        $items = [];
        foreach ($list as $value) {
            $nums = jsonDecodeToArr($value['pend_num_text']);
            if (!empty($nums)) {
                foreach ($nums as $name) {
                    $items[] = [
                        'name' => $name,
                        'store_id' => $value['store_id'],
                        'pend_order_id' => $value['id'],
                    ];
                }
            }
        }
        D('PendingOrderNum')->addAll($items);
    }
}