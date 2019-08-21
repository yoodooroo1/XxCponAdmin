<?php
/**
 * Created by PhpStorm.
 * User: Ydr
 * Date: 2019/8/15
 * Time: 12:12
 */

namespace Dock\Model;


use Think\Model;

class StoreMemberBindModel extends BaseModel
{
    //获取讯信绑定信息
    public function getXXStoreMemberBindInfo(){
        $member_info = $this->where(array('state'=>1))->select();
        return $member_info;
    }


    public function getStoreInfoById($store_id){
        $info = $this->where(array('store_id'=>$store_id))->find();
        return $info;
    }

    public function getMemberInfoById($third_member_id){
        $info = M('member_bind')->where(array('third_member_id'=>$third_member_id))->find();
        return $info;
    }
}