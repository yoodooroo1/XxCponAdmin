<?php

namespace Common\Model;
class GroupBuyingOrderModel extends BaseModel
{
    protected $tableName = 'mb_group_buying_order';

    /**
     * 获取团购订单列表
     * @param int $storeId
     * @param int $memberId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-09 15:26:25
     * Update: 2018-02-09 15:26:25
     * Version: 1.00
     */
    public function getMemberGroupOrderList($storeId = 0, $memberId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $field = [
            'a.*',
            'b.group_status', 'b.close_status', 'b.end_time', 'b.group_num', 'b.base_num', 'b.join_num'
        ];
        $field = implode(',', $field);
        $join = [
            '__MB_GROUP_BUYING__ b ON a.group_id = b.group_id'
        ];
        $where = [];
        $where['a.store_id'] = $storeId;
        $where['a.member_id'] = $memberId;
        $where['a.pay_success'] = 1;
        $where['a.buyer_delete'] = 0;
        $where = array_merge($where, $condition);
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = 'a.create_time DESC';
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => &$value) {
            $value = $this->transformInfo($value, $condition);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 获取团购订单详情
     * @param int $storeId
     * @param int $memberId
     * @param int $orderId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-09 15:16:15
     * Update: 2018-02-09 15:16:15
     * Version: 1.00
     */
    public function getMemberGroupOrderInfo($storeId = 0, $memberId = 0, $orderId = 0)
    {
        $field = [
            'a.*',
            'b.group_status', 'b.close_status', 'b.end_time', 'b.group_num', 'b.base_num', 'b.join_num',
            'b.complete_time', 'b.close_time', 'b.start_time', 'b.end_time'
        ];
        $field = implode(',', $field);
        $join = [
            '__MB_GROUP_BUYING__ b ON a.group_id = b.group_id'
        ];
        $where = [];
        $where['a.store_id'] = $storeId;
        $where['a.pay_success'] = 1;
        $where['a.member_id'] = $memberId;
        $where['a.order_id'] = $orderId;
        $where['a.buyer_delete'] = 0;
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, '订单不存在');
        $condition = [];
        $condition['map_field'] = [
            'pay_type' => ['线下支付', '微信支付', '支付宝支付'],
            'refund_status' => ['退款中', '已退款'],
        ];
        $condition['time_field'] = [
            'create_time' => '',
            'complete_time' => '',
            'end_time' => '',
        ];
        $info = $this->transformInfo($info, $condition);
        $where = [];
        $where['order_sn'] = $info['order_sn'];
        $info['true_order_id'] = M('mb_order')->where($where)->getField('order_id');
        $result['data'] = $info;
        $result['data']['shop_info'] = D('Store')->getStoreInfo($storeId)['data'];
        return $result;
    }

    /**
     * 删除团购订单
     * @param int $storeId
     * @param int $memberId
     * @param int $orderId
     * @return mixed ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-09 15:47:38
     * Update: 2018-02-09 15:47:38
     * Version: 1.00
     */
    public function delGroupOrder($storeId = 0, $memberId = 0, $orderId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['member_id'] = $memberId;
        $where['order_id'] = $orderId;
        $info = $this->where($where)->find();
        if (empty($info)) return getReturn(-1, '订单不存在');
        if ($info['buyer_delete'] == 1) return getReturn(-1, '订单已被删除');
        $result = $this->where($where)->setField('buyer_delete', 1);
        if (false === $result) {
            logWrite("删除订单失败:" . $this->getError() . '-' . $this->getDbError());
            return getReturn();
        }
        return getReturn(200);
    }

    /**
     * 转换数据
     * @param array $info
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-07 19:34:52
     * Update: 2018-02-07 19:34:52
     * Version: 1.00
     */
    public function transformInfo($info = array(), $condition = array())
    {
        $info = parent::transformInfo($info, $condition);
        // 团购状态 1-带成团 2-已成团 3-失败
        if ($info['group_status'] == 1) {
            if ($info['close_status'] == 2 || $info['end_time'] <= NOW_TIME) {
                $info['group_state'] = 3;
                $info['desc'] = '人数不足';
                if ($info['close_status'] == 2) {
                    $info['end_time'] = $info['close_time'];
                }
                // 失效时间
                $info['end_time_string'] = date('Y-m-d H:i:s', $info['end_time']);
                $info['share_desc'] = "团购已经结束咯,下次早点来哦~";
            } else {
                $needNum = $info['group_num'] - $info['base_num'] - $info['join_num'];
                $info['desc'] = "还需{$needNum}件成团";
                $info['group_state'] = 1;
                $info['share_desc'] = "团购火热进行中\n还需要{$needNum}件成团\n截止时间:{$info['end_time_string']}";
            }
        } elseif ($info['group_status'] == 2) {
            $info['group_state'] = 2;
            $joinNum = $info['join_num'] + $info['base_num'];
            $info['desc'] = "已抢{$joinNum}件";
            $info['share_desc'] = "团购火热进行中,已经抢了{$joinNum}件啦";
        }

        // 退款状态
        if (isset($info['refund_status'])){
            if ($info['pay_type'] == 0){
                $info['refund_status'] = 1;
                $info['refund_status_name'] = '已退款';
            }
        }

        return $info;
    }
}