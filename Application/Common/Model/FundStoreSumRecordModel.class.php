<?php

namespace Common\Model;

class FundStoreSumRecordModel extends BaseModel
{
    protected $tableName = 'mb_fund_store_detail';

    public function getStoreFundSumRecord($storeId = 0, $option = array(), $limit = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['is_delete'] = 0;
        $where = array_merge($where, $option);
        $result = $this->_select($where, $limit, true, 'create_time desc');
        foreach ($result['list'] as $key => $value) {
            if ($value['operate_type'] == 0) {
                $result['list'][$key]['operate_type'] = '收入';
                $result['list'][$key]['type_name'] = '代收结算';
                $result['list'][$key]['money_before'] = number_format($value['withdraw_money'] - $value['money'], 2);
            } elseif ($value['operate_type'] == 1) {
                $result['list'][$key]['operate_type'] = '提现';
                $result['list'][$key]['type_name'] = '商家提现';
                $result['list'][$key]['money_before'] = number_format($value['withdraw_money'] + $value['money'], 2);
            }
            switch ((int)$value['incomestate']) {
                case 0:
                    $result['list'][$key]['incomestate'] = '提现失败';
                    break;
                case 1:
                    $result['list'][$key]['incomestate'] = '提现中';
                    break;
                case 2:
                    $result['list'][$key]['incomestate'] = '已提现';
                    break;
                case 3:
                    $result['list'][$key]['incomestate'] = '自动提现';
                    break;
                default:
                    break;
            }

            $result['list'][$key]['create_time'] = date("Y-m-d H:i:s", $value['create_time']);
            $result['list'][$key]['item'] = json_encode($result['list'][$key], JSON_UNESCAPED_UNICODE);
        }
        return $result;
    }
}