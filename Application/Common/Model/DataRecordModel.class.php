<?php

namespace Common\Model;

class DataRecordModel extends BaseModel
{
    protected $tableName = 'data_record';

    /**
     * 获取商家的总访问次数 不包括PC后台的访问
     * @param int $storeId 商家ID
     * @param array $condition 额外的查询条件
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-28 10:09:11
     * Version: 1.0
     */
    public function getAccessNum($storeId = 0, $condition = [])
    {
        $where = [];
        $where['store_id'] = $storeId;
        $type = 'member_wap,member_web,member_pc,Android,Ios,android,ios';
        $where['terminal_type'] = ['in', $type];
        $where = array_merge($where, $condition);
        $count = $this->where($where)->count();
        if (false === $count) {
            logWrite("查询商家{$storeId}的访问次数出错:" . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '', $count);
    }

    /**
     * 获取今日新增访问量
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-28 10:19:41
     * Version: 1.0
     */
    public function getTodayNewAccessNum($storeId = 0)
    {
        $where = [];
        $where['create_time'] = strtotime(date('Y-m-d'));
        return $this->getAccessNum($storeId, $where);
    }
}