<?php

namespace Common\Model;


/**
 * 店铺的提现金额信息 表
 * Class FundStoreSumModel
 * @package Common\Model
 */
class FundStoreSumModel extends BaseModel
{
    protected $tableName = 'mb_fund_store_sum';

    /**
     * 可提现、提现中、已提现、待结算预估(待结算订单的结算额相加)
     * @param int $storeId
     * @param int $mainStore
     * @param int $channelId
     * @return array
     */
    public function getStoreFundSum($storeId = 0, $mainStore = 0, $channelId = 0)
    {
        if (isInSuper()){
            $where = [];
            $where['owe_store_id'] = 0;
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
                $where['store_id'] = ['in', $storeIdArr];
            } else {
                $where = [];
                $where['store_id'] = $storeId;
            }
        }

        // 可提现金额 已提现 提现中
        $result = $this
            ->field('SUM(withdraw_money) withdraw_money, SUM(withdraw_out) withdraw_out, SUM(withdraw_ongoing) withdraw_ongoing')
            ->where($where)
            ->find();
        // 待结算预估
        $where['account_success'] = 0;
        $where['pay_success'] = 1;
        $where['refund_success'] = 0;
        $where['is_delete'] = 0;
        $waitMoney = $this
            ->table('__MB_FUND_TRADE_DETAIL__')
            ->field('SUM(trade_account) trade_account')
            ->where($where)
            ->find();
        $result['trade_account'] = $waitMoney['trade_account'];
        return $this->getReturn(200, $this->getDbError(), $result);
    }
}