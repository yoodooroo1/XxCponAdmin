<?php

namespace Common\Model;

class WalletRecordModel extends BaseModel
{
    protected $tableName = 'mb_walletrecord';

    /**
     * 查询商家的提现审核数量
     * @param int $storeId
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-28 10:53:28
     * Version: 1.0
     */
    public function getWaitWalletNum($storeId = 0, $condition = [])
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['type'] = 3;
        $where['wallet_state'] = 0;
        $where = array_merge($where, $condition);
        $count = $this->where($where)->count();
        if (false === $count) {
            logWrite("查询商家{$storeId}的会员提现审核数量出错:" . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '', $count);
    }
}