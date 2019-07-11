<?php

namespace Common\Model;

class FundTradeDetailModel extends BaseModel
{

    protected $tableName = 'mb_fund_trade_detail';

    /**
     * 订单结算 主店或非主店 还有总后台用
     * @param int $storeId
     * @param int $mainStore
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $map
     * @return array
     */
    public function getStoreFundTradeDetail($storeId = 0, $mainStore = 0, $channelId = 0, $page = 1, $limit = 0, $map = [])
    {
        if (isInSuper()){
            // 支付成功的查出来
            $where = [];
            $where['a.pay_success'] = 1;
        } else {
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
            // 支付成功的查出来
            $where['a.pay_success'] = 1;
            $where['a.is_delete'] = 0;
        }
        $where = array_merge($where, $map);
        $total = $this
            ->alias('a')
            ->join("LEFT JOIN __MEMBER__ b ON b.member_id = a.member_id")
            ->join("LEFT JOIN __STORE__ c ON c.store_id = a.store_id")
            ->where($where)
            ->count();
        $list = $this
            ->field('a.*,b.member_name,c.member_name store_member,c.store_name')
            ->alias('a')
            ->join("LEFT JOIN __MEMBER__ b ON b.member_id = a.member_id")
            ->join("LEFT JOIN __STORE__ c ON c.store_id = a.store_id")
            ->where($where)
            ->order('a.create_time desc')
            ->limit(($page - 1) * $limit, $limit)
            ->select();
        foreach ($list as $key => $value) {
            // 分析状态
            if ($value['account_success'] == 1) {
                $list[$key]['status_name'] = "已结算";
            } else {
                if ($value['refund_success'] == 1) {
                    $list[$key]['status_name'] = "无效";
                } else {
                    if ($value['pay_success'] == 1 && $value['refund_success'] == 0){
                        $list[$key]['status_name'] = "待结算";
                    }elseif ($value['platform_exchange'] + $value['xx_replace_collect'] + $value['platform_replace_collect'] <= 0) {
                        $list[$key]['status_name'] = "不可结算";
                    }
                }
            }
        }
        if (isInSuper()){
            // 总额
            $all['allMoney'] = $this->alias('a')->where($where)->sum('trade_total_price');
            // 总抵用
            $all['allExchange'] = $this->alias('a')->where($where)->sum('store_exchange');
            // 总退款
            $all['allRefund'] = $this->alias('a')->where($where)->sum('trade_refund');
            // 总佣金
            $all['allCommission'] = $this->alias('a')->where($where)->sum('commission');
            // 总结算额
            $all['allAccount'] = $this->alias('a')->where($where)->sum('trade_account');
            // 总线下
            $where['pay_type'] = 0;
            $all['allOffline'] = $this->alias('a')->where($where)->sum('trade_total_price');
            // 总线上
            $where['pay_type'] = ['gt', 0];
            $all['allOnline'] = $this->alias('a')->where($where)->sum('trade_total_price');
        } else {
            // 订单总额 店内优惠 订单退款 店内余额 应收总额 店铺自收 平台优惠 平台余额 结算额 平台代收
            // 订单总额
            $all['trade_total_price'] = $this->alias('a')->where($where)->sum('trade_total_price');
            // 店内优惠
            $all['store_exchange'] = $this->alias('a')->where($where)->sum('store_exchange');
            // 订单退款
            $all['trade_refund'] = $this->alias('a')->where($where)->sum('trade_refund');
            // 店内余额
            $all['store_balance'] = $this->alias('a')->where($where)->sum('store_balance');
            // 应收总额
            $all['trade_receive_price'] = $this->alias('a')->where($where)->sum('trade_receive_price');
            // 店铺自收
            $all['self_collect'] = $this->alias('a')->where($where)->sum('self_collect');
            // 平台优惠
            $all['platform_exchange'] = $this->alias('a')->where($where)->sum('platform_exchange');
            // 平台余额
            $all['platform_balance'] = $this->alias('a')->where($where)->sum('platform_balance');
            // 平台代收
            $all['platform_replace_collect'] = $this->alias('a')->where($where)->sum('platform_replace_collect');
            // 总佣金
            $all['commission'] = $this->alias('a')->where($where)->sum('commission');
            // 总结算额
            $all['trade_account'] = $this->alias('a')->where($where)->sum('trade_account');
            // 系统代收
            $all['xx_replace_collect'] = $this->alias('a')->where($where)->sum('xx_replace_collect');
            // 代收
            $all['replace_collect'] = $all['xx_replace_collect'] + $all['platform_replace_collect'] ;
        }
        $data['total'] = $total;
        $data['list'] = $list;
        $data['all'] = $all;
        return getReturn(200, $this->getDbError(), $data);
    }
    /**
     * 资金汇总
     * user:czx
     * time:2017/11/30 11:14:34
     * @param string $storeMember
     * @param string $storeName
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $map
     * @return array
     */
    public function getMallFundSummary($storeMember = '', $storeName = '', $channelId = 0, $page = 1, $limit = 0, $map = [])
    {

           $where = [];
           $where['b.channel_id'] = $channelId;
           $where['a.pay_success'] = 1;
           $where['a.refund_success'] = 0;
           $where['b.main_store'] = 0;
           if (!empty($storeMember) && !empty($storeName)){
               $where['_string'] = "(b.member_name like '%{$storeMember}%' OR b.store_name like '%{$storeName}%')";
           }else if(empty($storeMember) && !empty($storeName)){
               $where['_string'] = "(b.store_name like '%{$storeName}%')";
           }else if(!empty($storeMember) && empty($storeName)){
               $where['_string'] = "(b.member_name like '%{$storeMember}%')";
           }

           $storeIdData = $this
               ->alias('a')
               ->field('a.store_id')
               ->join('LEFT JOIN __STORE__ b ON a.store_id = b.store_id')
               ->where($where)
               ->group('a.store_id')
               ->limit(($page - 1) * $limit, $limit)
               ->select();
           $storeIdArr = [];
           foreach ($storeIdData as $key => $val) {
               $storeIdArr[] = $val['store_id'];
           }
           $storeIdArr = implode(",", $storeIdArr);

        //获取商家交易明细
        $where = [];
        $where['a.store_id'] = ['in', $storeIdArr];
        $where['a.pay_success'] = 1;
        $where['a.refund_success'] = 0;
        $selectData = $this
                ->field('COUNT(a.store_id) item_count,
                 SUM(a.trade_total_price) trade_total_price,
                 SUM(a.commission) commission, SUM(a.trade_account) trade_account,
                 a.store_id, b.store_name, b.member_name, a.account_success')
                ->alias('a')
                ->join('LEFT JOIN __STORE__ b ON a.store_id = b.store_id')
                ->where($where)
                ->group('a.store_id, a.pay_success, a.refund_success, a.account_success')
                ->select();

        //获取商家资金数量
        $modelFundSum = D('FundStoreSum');
        $where = [];
        $where['store_id'] = ['in', $storeIdArr];
        $fundSumData = $modelFundSum->where($where)->select();

        $list = [];
        foreach ($selectData as $key => $val){
            $list[$val['store_id']]['store_id'] = $val['store_id'];
            $list[$val['store_id']]['store_name'] = $val['store_name'];
            $list[$val['store_id']]['member_name'] = $val['member_name'];
            $list[$val['store_id']]['trade_total_price'] = $list[$val['store_id']]['trade_total_price'] + $val['trade_total_price'];
            $list[$val['store_id']]['commission'] = $list[$val['store_id']]['commission'] + $val['commission'];
            $list[$val['store_id']]['item_count'] = $list[$val['store_id']]['item_count'] + $val['item_count'];
            if ($val['account_success'] == 1){
                $list[$val['store_id']]['already_trade_account'] = empty($val['trade_account']) ? 0 : $val['trade_account'];
                $list[$val['store_id']]['already_item_count'] = empty($val['item_count']) ? 0 : $val['item_count'];
            }else{
                $list[$val['store_id']]['wait_trade_account'] = empty($val['trade_account']) ? 0 : $val['trade_account'];
                $list[$val['store_id']]['wait_item_count'] = empty($val['item_count']) ? 0 : $val['item_count'];

            }

            $list[$val['store_id']]['withdraw_money'] = 0;
            $list[$val['store_id']]['withdraw_out'] = 0;
            $list[$val['store_id']]['withdraw_ongoing'] = 0;

        }
        foreach ($list as $key => $val){
            $list[$key]['already_trade_account'] = empty($list[$key]['already_trade_account']) ? 0 :$list[$key]['already_trade_account'];
            $list[$key]['already_item_count'] = empty($list[$key]['already_item_count']) ? 0 :$list[$key]['already_item_count'];
            $list[$key]['wait_trade_account'] = empty($list[$key]['wait_trade_account']) ? 0 :$list[$key]['wait_trade_account'];
            $list[$key]['wait_item_count'] = empty($list[$key]['wait_item_count']) ? 0 :$list[$key]['wait_item_count'];
        }
        //withdraw_money:可提现金额 withdraw_out:已提现金额 withdraw_ongoing:正在提现金额
        foreach ($fundSumData as $key => $val){
            $list[$val['store_id']]['withdraw_money'] = $val['withdraw_money'];
            $list[$val['store_id']]['withdraw_out'] = $val['withdraw_out'];
            $list[$val['store_id']]['withdraw_ongoing'] = $val['withdraw_ongoing'];
        }

        $data = [];
        //计算总量
        $where = [];
        $where['b.channel_id'] = $channelId;
        $where['a.pay_success'] = 1;
        $where['a.refund_success'] = 0;

        $storeIdData = $this
            ->alias('a')
            ->field('a.store_id')
            ->join('LEFT JOIN __STORE__ b ON a.store_id = b.store_id')
            ->where($where)
            ->group('a.store_id')
            ->select();
        $storeIdArr = [];
        foreach ($storeIdData as $key => $val){
            $storeIdArr[] = $val['store_id'];
        }
        $data['all']['total'] = count($storeIdArr);
        $storeIdArr = implode(',', $storeIdArr);

        $where = [];
        $where['store_id'] = ['in', $storeIdArr];
        $where['pay_success'] = 1;
        $where['refund_success'] = 0;
        $data['all']['trade_total_price'] = $this->where($where)->sum('trade_total_price');

        $options = [];
        $options['trade_success'] = 0;
        $newWhere = array_merge($options, $where);
        $data['all']['wait_trade_account'] = $this->where($newWhere)->sum('trade_account');
        $options = [];
        $options['trade_success'] = 1;
        $newWhere = array_merge($options, $where);
        $data['all']['already_trade_account'] = $this->where($newWhere)->sum('trade_account');

        $where = [];
        $where['store_id'] = ['in', $storeIdArr];
        $sumData = $modelFundSum
            ->field('SUM(withdraw_money) withdraw_money, SUM(withdraw_out) withdraw_out, SUM(withdraw_ongoing) withdraw_ongoing')
            ->where($where)->find();
        $data['all']['withdraw_money'] = $sumData['withdraw_money'];
        $data['all']['withdraw_out'] = $sumData['withdraw_out'];
        $data['all']['withdraw_ongoing'] = $sumData['withdraw_ongoing'];
        $data['list'] = $list;
        return getReturn(200, '', $data);
    }

    public function put_fund_db($storeorder, $order_id, $channel_type)
    {
        $store_totalprice = $storeorder['totalprice'] + $storeorder['platform_balance']
            + $storeorder['platform_coupons_money'] + $storeorder['platform_credits_exmoney']
            + $storeorder['thirdpart_momey'] + $storeorder['coupons_exmoney'] + $storeorder['balance']
            + $storeorder['credits_exmoney'] + $storeorder['mj_price'];

        $fund_data = array();
        $fund_data['store_id'] = $storeorder['storeid'];
        $fund_data['member_id'] = $storeorder['buyer_id'];
        $fund_data['order_id'] = $order_id;
        $fund_data['trade_type'] = 0;
        $fund_data['trade_total_price'] = $store_totalprice;
        $fund_data['mj_price'] = $storeorder['mj_price'];
        //店内优惠

        $store_exchange = $storeorder['coupons_exmoney'] + $storeorder['credits_exmoney'] + $storeorder['mj_price'];
        if ($channel_type != 2 ){
            $store_exchange = $store_exchange + $storeorder['thirdpart_momey'];
        }
        $fund_data['store_exchange'] = $store_exchange;

        //退款
        $trade_refund = 0;
        $fund_data['trade_refund'] = $trade_refund;

        //店内余额
        $store_balance = $storeorder['balance'];
        $fund_data['store_balance'] = $store_balance;

        //应收总额
        $fund_data['trade_receive_price'] = $store_totalprice - $store_exchange -  $store_balance - $trade_refund;

        //平台优惠
        $fund_data['platform_exchange'] =  $storeorder['platform_balance']
            + $storeorder['platform_coupons_money'] + $storeorder['platform_credits_exmoney']
        ;
        //平台余额  1/16 平台抵用分为平台余额 + 平台优惠
        $fund_data['platform_balance'] = 0;
        if ($channel_type == 2 ){
            // $fund_data['platform_exchange']  = $fund_data['platform_exchange']  + $storeorder['thirdpart_momey'];
            //czx 1/16
            $fund_data['platform_balance'] = $fund_data['platform_balance'] + $storeorder['thirdpart_momey'];
        }

        if ($storeorder['order_state'] == 0) {
            //代收
            $xx_replace_collect = 0;
            $platform_replace_collect = 0;


            //自收
            $self_collect = $fund_data['trade_receive_price'] - $fund_data['platform_exchange'] - $fund_data['platform_balance'];
        }else{
            //代收
            $xx_replace_collect = 0;
            $platform_replace_collect = 0;

            //自收
            $self_collect = 0;
        }
        $fund_data['xx_replace_collect'] = $xx_replace_collect;
        $fund_data['platform_replace_collect'] = $platform_replace_collect;
        $fund_data['self_collect'] = $self_collect;

        //佣金
        $commission = $storeorder['order_pv'];
        $fund_data['commission'] = $commission;

        //结算
        if ($channel_type != 2 ){
            $trade_account = $store_totalprice - $store_exchange - $store_balance - $self_collect;

        }else{
            $trade_account = $store_totalprice - $store_exchange - $store_balance - $commission - $self_collect;
        }
        $fund_data['trade_account'] = $trade_account;

        //创建时间
        $fund_data['create_time'] = TIMESTAMP;

        //更新时间
        $fund_data['update_time'] = TIMESTAMP;

        //支付方式
        $fund_data['pay_type'] = $storeorder['pay_type'];

        //是否支付成功
        if ($storeorder['order_state'] == 0 || $storeorder['order_state'] == 1) {
            $pay_success = 1;
        }else{
            $pay_success = 0;
        }
        $fund_data['pay_success'] =  $pay_success;

        //交易成功
        $fund_data['trade_success'] =  0;
        $fund_data['account_success'] =  0;

        $tag = $this->insert($fund_data);
        if ($tag == false){
            $log_data = "订单编号为:".$order_id."。插入mb_fund_trade_detail表失败" .json_encode($fund_data);
            logWrite($log_data);
            return getReturn(-1, $log_data);
        }
        return getReturn(200, "成功");
    }

}