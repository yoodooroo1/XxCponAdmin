<?php

namespace Common\Model;

class FundStoreIncomeDetailModel extends BaseModel
{
    protected $tableName = 'mb_fund_store_income_detail';

    /**
     * 店铺 收入记录
     * @param int $storeId
     * @param int $mainStore
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $map
     * @return array
     */
    public function getStoreIncomeDetail($storeId = 0, $mainStore = 0, $channelId = 0, $page = 1, $limit = 0, $map = [])
    {
        // 如果是商城 则获取所有子店
        if ($mainStore == 1) {
            $modelStore = D('Store');
            $where = [];
            $where['channel_id'] = $channelId;
            $storeIdArr = $modelStore->where($where)->getField('store_id', true);
            $storeIdArr = implode(',', $storeIdArr);
            $where = [];
            $where['a.store_id'] = ['in', $storeIdArr];
        } else {
            $where = [];
            $where['a.store_id'] = $storeId;
        }
        $where['a.is_delete'] = 0;
        $where = array_merge($where, $map);
        $total = $this
            ->alias('a')
            ->join('LEFT JOIN __MB_FUND_TRADE_DETAIL__ b ON b.id = a.trade_id')
            ->join('LEFT JOIN __MB_FUND_STORE_DETAIL_RECORD__ c ON c.store_id = a.store_id AND c.income_id = a.order_id')
            ->where($where)
            ->count();
        $list = $this
            ->field('a.*,b.pay_id,c.desc')
            ->alias('a')
            ->join('LEFT JOIN __MB_FUND_TRADE_DETAIL__ b ON b.id = a.trade_id')
            ->join('LEFT JOIN __MB_FUND_STORE_DETAIL_RECORD__ c ON c.store_id = a.store_id AND c.income_id = a.order_id')
            ->where($where)
            ->order('a.create_time desc')
            ->limit(($page - 1) * $limit, $limit)
            ->select();
        foreach ($list as $key => $value) {
            $list[$key]['change_before_money'] = $value['withdraw_money'] - $value['money'];
            $list[$key]['item'] = json_encode($list[$key], JSON_UNESCAPED_UNICODE);
        }
        $data = [];
        $data['list'] = $list;
        $data['total'] = $total;
        return $this->getReturn(200, $this->_sql(), $data);
    }

}