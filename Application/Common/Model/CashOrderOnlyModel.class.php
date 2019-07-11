<?php

namespace Common\Model;


class CashOrderOnlyModel extends BaseModel
{
    protected $tableName = 'mb_cashorder_only';


    public function addOrderOnly($order_sn)
    {
        $insertData = array();
        $insertData['order_sn'] = $order_sn;
        $data = $this->where($insertData)->find();
        if (empty($data)){
            $returnData = $this->add($insertData);
        }else{
            return getReturn(-1, "插入不成功");
        }
        if ($returnData === false) return getReturn(-1, "插入不成功");
        return getReturn(200, "成功");
    }

    public function getOrderOnly($order_sn){
        $where = array();
        $where['order_sn'] = $order_sn;
        $where = $this->where($where)->find();
        return getReturn(200, "成功", $where);
    }

}