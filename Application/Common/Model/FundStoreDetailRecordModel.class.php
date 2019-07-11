<?php

namespace Common\Model;

/**
 * 店铺的交易明细 收入/提现
 * Class FundStoreDetailRecordModel
 * @package Common\Model
 */
class FundStoreDetailRecordModel extends BaseModel
{
    protected $tableName = 'mb_fund_store_detail_record';

    /**
     * 代收、提现、回冲明细
     * 主店查看的是子店的
     * 主店查看自己的传mainStore = 0
     * @param int $storeId
     * @param int $mainStore
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $map
     * @return array
     */
    public function getStoreDetailRecord($storeId = 0, $mainStore = 0, $channelId = 0, $page = 1, $limit = 0, $map = [])
    {
        if (isInSuper()) {
            return $this->getSuperStoreDetailRecord($storeId, $mainStore, $channelId, $page, $limit, $map);
        }
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
        $where = array_merge($where, $map);
        $field = [
            'a.id record_id,a.store_id,a.change_money,a.change_after_money,a.desc,a.create_time',
            'b.id cash_id,b.incomestate,b.desc cash_describe,b.account_type,b.account_type_name,b.account_card_name,b.account_member_name',
            'c.id income_id,c.order_id,c.trade_id',
            'd.store_name,d.member_name store_member',
            'e.pay_id'
        ];
        $total = $this
            ->alias('a')
            ->where($where)
            ->join('LEFT JOIN __MB_FUND_STORE_CASH_DETAIL__ b ON b.id = a.cash_id')
            ->join('LEFT JOIN __MB_FUND_STORE_INCOME_DETAIL__ c ON c.order_id = a.income_id AND c.store_id = a.store_id')
            ->join('LEFT JOIN __STORE__ d on d.store_id = a.store_id')
            ->join('LEFT JOIN __MB_FUND_TRADE_DETAIL__ e ON e.id = c.trade_id')
            ->count();
        $list = $this
            ->alias('a')
            ->field(implode(',', $field))
            ->join('LEFT JOIN __MB_FUND_STORE_CASH_DETAIL__ b ON b.id = a.cash_id')
            ->join('LEFT JOIN __MB_FUND_STORE_INCOME_DETAIL__ c ON c.order_id = a.income_id AND c.store_id = a.store_id')
            ->join('LEFT JOIN __STORE__ d on d.store_id = a.store_id')
            ->join('LEFT JOIN __MB_FUND_TRADE_DETAIL__ e ON e.id = c.trade_id')
            ->where($where)
            ->order('a.create_time desc')
            ->limit(($page - 1) * $limit, $limit)
            ->select();
        if (false === $list) return $this->getReturn(-1, $this->getDbError());
        foreach ($list as $key => $value) {
            $list[$key]['change_before_money'] = $value['change_after_money'] - $value['change_money'];
            // 判断业务类型等名称
            if ($value['cash_id'] > 0) {
                // 账务类型
                $list[$key]['account_type'] = (double)$value['change_money'] > 0 ? "提现回冲" : "商家提现";
                // 收支类型
                $list[$key]['income_type'] = (double)$value['change_money'] > 0 ? "回冲" : "提现";
                // 业务名称
                $list[$key]['account_type_name'] = (double)$value['change_money'] > 0 ? "账户提现回冲" : "账户提现扣款";
                // 业务类型
                $list[$key]['income_type_name'] = (double)$value['change_money'] > 0 ? "提现回冲" : "商家提现";
                // 金额html
                $list[$key]['change_money_html'] = (double)$value['change_money'] > 0 ?
                    "<span style=\"color:red\">+{$value['change_money']}</span>" :
                    "<span style=\"color:red\">{$value['change_money']}</span>";
            } else {
                $list[$key]['account_type'] = "代收结算";
                $list[$key]['income_type'] = "入账";
                $list[$key]['account_type_name'] = "账户订单收入入账";
                $list[$key]['income_type_name'] = "订单代收结算";
                $list[$key]['change_money_html'] = "<span style=\"color:green\">+{$value['change_money']}</span>";
            }
            $list[$key]['create_time_string'] = date("Y-m-d H:i:s", $value['create_time']);
            $list[$key]['item'] = json_encode($list[$key], JSON_UNESCAPED_UNICODE);
        }
        $data = [];
        $data['list'] = $list;
        $data['total'] = $total;
        return $this->getReturn(200, $this->_sql(), $data);
    }

    /**
     * 代收、提现、回冲明细
     * 主店查看的是子店的
     * 主店查看自己的传mainStore = 0
     * @param int $storeId
     * @param int $mainStore
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $map
     * @return array
     */
    protected function getSuperStoreDetailRecord($storeId = 0, $mainStore = 0, $channelId = 0, $page = 1, $limit = 0, $map = [])
    {
        $where = [];
        $where = array_merge($where, $map);
        $field = [
            'a.id record_id,a.store_id,a.change_money,a.change_after_money,a.desc,a.create_time, a.income_id, a.cash_id',
            //'b.id cash_id,b.incomestate,b.desc cash_describe,b.account_type,b.account_type_name,b.account_card_name,b.account_member_name',
            // 'c.id income_id,c.order_id,c.trade_id',
            'd.store_name,d.member_name store_member'
            // 'e.pay_id'
        ];
        $total = $this
            ->alias('a')
            ->where($where)
            //->join('LEFT JOIN __MB_FUND_STORE_CASH_DETAIL__ b ON b.id = a.cash_id')
            //->join('LEFT JOIN __MB_FUND_STORE_INCOME_DETAIL__ c ON c.order_id = a.income_id AND c.store_id = a.store_id')
            ->join('LEFT JOIN __STORE__ d on d.store_id = a.store_id')
            //->join('LEFT JOIN __MB_FUND_TRADE_DETAIL__ e ON e.id = c.trade_id')
            ->count();
        $list = $this
            ->alias('a')
            ->field(implode(',', $field))
            // ->join('LEFT JOIN __MB_FUND_STORE_CASH_DETAIL__ b ON b.id = a.cash_id')
            //->join('LEFT JOIN __MB_FUND_STORE_INCOME_DETAIL__ c ON c.order_id = a.income_id AND c.store_id = a.store_id')
            ->join('LEFT JOIN __STORE__ d on d.store_id = a.store_id')
            //->join('LEFT JOIN __MB_FUND_TRADE_DETAIL__ e ON e.id = c.trade_id')
            ->where($where)
            ->order('a.create_time desc')
            ->limit(($page - 1) * $limit, $limit)
            ->select();
        if (false === $list) return $this->getReturn(-1, $this->getDbError());
        foreach ($list as $key => $value) {
            $list[$key]['change_before_money'] = $value['change_after_money'] - $value['change_money'];
            // 判断业务类型等名称
            if ($value['cash_id'] > 0) {
                $list[$key]['order_id'] = $value['cash_id'];
                // 账务类型
                $list[$key]['account_type'] = (double)$value['change_money'] > 0 ? "提现回冲" : "商家提现";
                // 收支类型
                $list[$key]['income_type'] = (double)$value['change_money'] > 0 ? "回冲" : "提现";
                // 业务名称
                $list[$key]['account_type_name'] = (double)$value['change_money'] > 0 ? "账户提现回冲" : "账户提现扣款";
                // 业务类型
                $list[$key]['income_type_name'] = (double)$value['change_money'] > 0 ? "提现回冲" : "商家提现";
                // 金额html
                $list[$key]['change_money_html'] = (double)$value['change_money'] > 0 ?
                    "<span style=\"color:red\">+{$value['change_money']}</span>" :
                    "<span style=\"color:red\">{$value['change_money']}</span>";
            } else {
                $list[$key]['order_id'] = $value['income_id'];
                $list[$key]['account_type'] = "代收结算";
                $list[$key]['income_type'] = "入账";
                $list[$key]['account_type_name'] = "账户订单收入入账";
                $list[$key]['income_type_name'] = "订单代收结算";
                $list[$key]['change_money_html'] = "<span style=\"color:green\">+{$value['change_money']}</span>";
            }
            $list[$key]['create_time_string'] = date("Y-m-d H:i:s", $value['create_time']);
            $list[$key]['item'] = json_encode($list[$key], JSON_UNESCAPED_UNICODE);
        }
        $data = [];
        $data['list'] = $list;
        $data['total'] = $total;
        return $this->getReturn(200, $this->_sql(), $data);
    }

    public function getStoreDetailRecordItem($record_id = 0)
    {
        $where = [];
        $where['a.id'] = $record_id;
        $field = [
            'a.id record_id,a.store_id,a.change_money,a.change_after_money,a.desc,a.create_time',
            'b.id cash_id,b.incomestate,b.desc cash_describe,b.account_type,b.account_type_name,b.account_card_name,b.account_member_name',
            'c.id income_id,c.order_id,c.trade_id',
            'd.store_name,d.member_name store_member',
            'e.pay_id'
        ];

        $data = $this
            ->alias('a')
            ->field(implode(',', $field))
            ->join('LEFT JOIN __MB_FUND_STORE_CASH_DETAIL__ b ON b.id = a.cash_id')
            ->join('LEFT JOIN __MB_FUND_STORE_INCOME_DETAIL__ c ON c.order_id = a.income_id AND c.store_id = a.store_id')
            ->join('LEFT JOIN __STORE__ d on d.store_id = a.store_id')
            ->join('LEFT JOIN __MB_FUND_TRADE_DETAIL__ e ON e.id = c.trade_id')
            ->where($where)
            ->find();
        if (false === $data) return $this->getReturn(-1, $this->getDbError());
        $data['change_before_money'] = $data['change_after_money'] - $data['change_money'];
        // 判断业务类型等名称
        if ($data['cash_id'] > 0) {
            $data['order_id'] = $data['cash_id'];
            // 账务类型
            $data['account_type'] = (double)$data['change_money'] > 0 ? "提现回冲" : "商家提现";
            // 收支类型
            $data['income_type'] = (double)$data['change_money'] > 0 ? "回冲" : "提现";
            // 业务名称
            $data['account_type_name'] = (double)$data['change_money'] > 0 ? "账户提现回冲" : "账户提现扣款";
            // 业务类型
            $data['income_type_name'] = (double)$data['change_money'] > 0 ? "提现回冲" : "商家提现";
            // 金额html
            $data['change_money_html'] = (double)$data['change_money'] > 0 ?
                "<span style=\"color:red\">+{$data['change_money']}</span>" :
                "<span style=\"color:red\">{$data['change_money']}</span>";
        } else {
            $data['account_type'] = "代收结算";
            $data['income_type'] = "入账";
            $data['account_type_name'] = "账户订单收入入账";
            $data['income_type_name'] = "订单代收结算";
            $data['change_money_html'] = "<span style=\"color:green\">+{$data['change_money']}</span>";
        }
        $data['create_time_string'] = date("Y-m-d H:i:s", $data['create_time']);
        //$data['item'] = json_encode($data, JSON_UNESCAPED_UNICODE);

        return $this->getReturn(200, $this->_sql(), $data);
    }
}