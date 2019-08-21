<?php
/**
 * Created by PhpStorm.
 * User: Ydr
 * Date: 2019/8/13
 * Time: 17:01
 */
namespace Dock\Model;
class CouponsMatchModel extends BaseModel
{
    public function getStoreCouponMatchInfo($store_id,$coupons_id,$third_coupons_id){
        $where = array();
        $where['store_id'] = $store_id;
        $where['xx_coupons_id'] = $coupons_id;
        $where['third_coupons_id'] = $third_coupons_id;
        $matchInfo = $this->where($where)->find();
        if(!empty($matchInfo)){
            return $matchInfo;
        }
    }
}