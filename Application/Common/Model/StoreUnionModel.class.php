<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2017/4/26
 * Time: 15:38
 */

namespace Common\Model;
class StoreUnionModel extends BaseModel
{

    protected $tableName = 'mb_unionstore';

    public function getUnionStoreCount($storeId = 0)
    {
        $storeId = (int)$storeId;
        if ($storeId <= 0) return getReturn(-1, '参数无效');
        $where = [];
        $where['mainid'] = $storeId;
        $where['isdelete'] = 0;
        $where['state'] = 1;
        $count = $this->where($where)->count();
        if (false === $count) return getReturn();
        return getReturn(200, '', $count);
    }

    public function getUnionStoreList($storeId = 0)
    {
        $storeId = (int)$storeId;
        if ($storeId <= 0) return getReturn(-1, '参数无效');
        $where = [];
        $where['a.mainid'] = $storeId;
        $where['a.isdelete'] = 0;
        $where['a.state'] = 1;
        $list = $this
            ->alias('a')
            ->field('a.storeid store_id,b.store_name,b.member_name')
            ->where($where)
            ->join('__STORE__ b ON b.store_id = a.storeid')
            ->order('a.createtime DESC')
            ->select();
        if (false === $list) return getReturn();
        return getReturn(200, '', $list);
    }
}