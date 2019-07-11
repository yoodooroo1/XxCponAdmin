<?php

namespace Common\Model;
/**
 * Class StoreVipModel
 *
 * @package Common\Model
 * User: hjun
 * Date: 2018-01-17 15:06:41
 */
class StoreVipModel extends BaseModel
{
    protected $tableName = 'mb_storevip';

    public function getStoreVipList($storeId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $field = true;
        $options = [];
        $options['where'] = $where;
        $options['field'] = $field;
        $result = $this->queryList($options);
        return $result['data']['list'];
    }

    /**
     * 获取商家VIP等级信息
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     *  data=> [
     *      ['store_id'=>564,'vip_level'=>1, 'vip_price'=>500, 'discount'=>9.8]
     *  ]
     * User: hj
     * Date: 2017-09-20 10:46:54
     * Version: 1.0
     */
    public function getStoreVip($storeId = 0)
    {
        // 查询商家会员等级开关
        $where = [];
        $where['store_id'] = $storeId;
        $memberVip = M('Store')->where($where)->getField('member_vip');
        if ($memberVip == 1){
            $info = $this->where($where)->order('vip_level')->select();
            if (false === $info) {
                logWrite("查询商家{$storeId}VIP信息出错:" . $this->getDbError());
                return getReturn();
            }
            $maxValue = 0;
            $maxLevel = 0;
            if (!empty($info)) {
                foreach ($info as $key => $value) {
                    if ($value['vip_price'] > $maxValue) $maxValue = $value['vip_price'];
                    if ($value['vip_level'] > $maxLevel) $maxLevel = $value['vip_level'];
                    $info[$key]['vip_level_name'] = "VIP{$value['vip_level']}";
                }
            }
            $data = [];
            $data['max'] = $maxValue;
            $data['max_level'] = $maxLevel;
            $data['list'] = $info;
        } else {
            $data = [];
        }
        return getReturn(200, '', $data);
    }
}