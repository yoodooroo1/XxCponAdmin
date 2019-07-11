<?php

namespace Common\Model;
class GroupBuyingLimitModel extends BaseModel
{
    protected $tableName = 'mb_group_buying_limit';

    /**
     * 限购检查
     * @param array $groupInfo
     * @param int $memberId
     * @param int $buyNum
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-01 14:59:21
     * Update: 2018-11-01 14:59:21
     * Version: 1.00
     */
    public function validateBuyNum($groupInfo = [], $memberId = 0, $buyNum = 0)
    {
        if ($groupInfo['is_limit'] == 1) {
            $limitNum = $groupInfo['limit_buy_num'];
            $where = [];
            $where['group_id'] = $groupInfo['group_id'];
            $where['member_id'] = $memberId;
            $where['goods_id'] = $groupInfo['goods_id'];
            $limit = $this->where($where)->find();
            $historyNum = $limit['buy_num'] > 0 ? $limit['buy_num'] : 0;
            $canBuyNum = $limitNum - $historyNum;
            if ($canBuyNum <= 0) {
                return getReturn(CODE_ERROR, "该商品限购{$limitNum}件,您已经购买过");
            }
            if ($buyNum > $canBuyNum) {
                return getReturn(CODE_ERROR, "该商品限购{$limitNum}件,您只能再购买{$canBuyNum}件");
            }
        }
        return getReturn(CODE_SUCCESS);
    }

    /**
     * 保存购买数量
     * @param int $groupId
     * @param int $memberId
     * @param int $goodsId
     * @param int $buyNum
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-01 15:33:07
     * Update: 2018-11-01 15:33:07
     * Version: 1.00
     */
    public function addBuyNum($groupId = 0, $memberId = 0, $goodsId = 0, $buyNum = 0)
    {
        if ($buyNum > 0) {
            $where = [];
            $where['group_id'] = $groupId;
            $where['member_id'] = $memberId;
            $where['goods_id'] = $goodsId;
            $limit = $this->where($where)->find();
            if (empty($limit)) {
                $data = [];
                $data['group_id'] = $groupId;
                $data['member_id'] = $memberId;
                $data['goods_id'] = $goodsId;
                $data['buy_num'] = $buyNum;
                $result = $this->add($data);
            } else {
                $where = [];
                $where['id'] = $limit['id'];
                $result = $this->where($where)->setInc('buy_num', $buyNum);
            }
            if (false === $result) {
                return getReturn(CODE_ERROR);
            }
        }
        return getReturn(CODE_SUCCESS, 'success');
    }
}