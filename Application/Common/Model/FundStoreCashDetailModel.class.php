<?php

namespace Common\Model;

/**
 * 店铺提现明细
 * Class FundStoreCashDetailModel
 * @package Common\Model
 */
class FundStoreCashDetailModel extends BaseModel
{
    protected $tableName = 'mb_fund_store_cash_detail';

    /**
     * 提现记录、提现审核
     * @param int $storeId
     * @param int $mainStore
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $map
     * @return array
     */
    public function getStoreCashDetail($storeId = 0, $mainStore = 0, $channelId = 0, $page = 1, $limit = 0, $map = [])
    {
        if (isInSuper()){
            $where = [];
            $where['a.owe_store_id'] = 0;
        } else {
            // 如果是商城 则获取所有子店
            if ($mainStore == 1) {
                $modelStore = D('Store');
                $where = [];
                $where['channel_id'] = $channelId;
                // 不包括自己
                $where['store_id'] = ["neq", $storeId];
                $storeIdArr = $modelStore->where($where)->getField('store_id', true);
                $storeIdArr = implode(',', $storeIdArr);
                $where = [];
                $where['a.store_id'] = ['in', $storeIdArr];
            } else {
                $where = [];
                $where['a.store_id'] = $storeId;
            }
        }

        $where['a.is_delete'] = 0;
        $where = array_merge($where, $map);
        $total = $this
            ->alias('a')
            ->field('a.*,b.store_name,b.member_name store_member')
            ->join('LEFT JOIN __STORE__ b ON b.store_id = a.store_id')
            ->where($where)
            ->count();
        $list = $this
            ->alias('a')
            ->field('a.*,b.store_name,b.member_name store_member')
            ->join('LEFT JOIN __STORE__ b ON b.store_id = a.store_id')
            ->where($where)
            ->order('create_time desc')
            ->limit(($page - 1) * $limit, $limit)
            ->select();
        foreach ($list as $key => $value) {
            // 因为是负数，用-的就可以
            $list[$key]['change_before_money'] = $value['withdraw_money'] - $value['money'];
            $list[$key]['item'] = json_encode($list[$key], JSON_UNESCAPED_UNICODE);
        }
        $data = [];
        $data['list'] = $list;
        $data['total'] = $total;
        return $this->getReturn(200, $this->_sql(), $data);
    }
}