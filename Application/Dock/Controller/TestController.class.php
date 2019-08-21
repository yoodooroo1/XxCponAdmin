<?php
/**
 * Created by PhpStorm.
 * User: Ydr
 * Date: 2019/7/26
 * Time: 10:09
 */

namespace Dock\Controller;


class TestController extends HpBaseController
{
    public function test()
    {
        $req = $this->req;
        $log_str = "[Dock->HpCoupon->onlineCouponToOffline]  " . HP_GETOFFLINECOUPON . " post_data->" . json_encode($req);
        hpLogs($log_str);

        $params = array();
        $params['ftask'] = '501';
        $params['fmch_id'] = '90001';
        $params['fsign'] = 'cf2e3152615709755767f11be5e8bc1d';
        $params['ftimestamp'] = time();
        $params['ftype'] = 0;
        $params['fdate'] =  '2019-01-01'.'~'.date('Y-m-d',time());
//        $params['fuseshopid'] = '1';
//        $params['fshopid'] = '10000100';
        $params['fvip'] = '18031611090287440';
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
        $log_str = "[Dock->HpCoupon->onlineCouponToOffline]  " . HP_GETOFFLINECOUPON . " returndata->" . json_encode($return_data) . "\n" .
            "post_data:" . json_encode($params);
        hpLogs($log_str);
        $return_arr = json_decode($return_data['data'], true);
        var_dump($return_data);
    }

    public function getHpGoods()
    {
        $storeData = D('StoreMemberBind')->getXXStoreMemberBindInfo();
        foreach ($storeData as $value){
            if (empty($value['store_id'])) continue;
            $params = array();
            $params['ftask'] = HP_GETGOODS;
            $params['fmch_id'] = $value['fmch_id'];
            $params['fsign'] = $value['fsign'];
            $params['ftimestamp'] = time();
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
            $log_str = "[Dock->HpGoods->getHpGoods]  ".HP_GETGOODS." returndata->".json_encode($return_data)."\n".
                "post_data:".json_encode($params);
            hpLogs($log_str);
            $return_arr = json_decode($return_data['data'], true);
            $this->hpResChecking($return_arr);
            var_dump($return_arr);
        }
    }

    public function tt(){
        $params['ftask'] = 207;
        $params['fmch_id'] = '90001';
        $params['fsign'] = 'cf2e3152615709755767f11be5e8bc1d';
        $params['ftimestamp'] = time();
        $params['fshopid'] = '10000100';
        $params['fmobile'] = '15056648148';
        $params['fname'] = 'test';
        $params['fcardno'] = '100001125';
//        $params['fvipid'] = '18031611090287440';
//        $params['fpoint'] = 100;
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
        $log_str = "[Dock->HpCoupon->onlineCouponToOffline]  " . HP_GETOFFLINECOUPON . " returndata->" . json_encode($return_data) . "\n" .
            "post_data:" . json_encode($params);
        hpLogs($log_str);
        var_dump($return_data);
    }

    public function ttt(){
        $params['ftask'] = 320;
            $params['fmch_id'] = '910002';
        $params['fsign'] = '87d5f3124d1701caee21251016dd17fb';
        $params['ftimestamp'] = time();
        $params['fprodid'] = '10063801100019';
//        $params['fshopid'] = '10000100';
//        $params['fmobile'] = '15059184619';
//        $params['fname'] = 'test';
//        $params['fvipid'] = '18031611090287440';
//        $params['fpoint'] = 100;
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
//        $log_str = "[Dock->HpCoupon->onlineCouponToOffline]  " . HP_GETOFFLINECOUPON . " returndata->" . json_encode($return_data) . "\n" .
//            "post_data:" . json_encode($params);
//        hpLogs($log_str);
        var_dump($return_data);
    }
    //优惠卷查询接口
    /**
     * type 0 1
     *
    **/
    public function t1(){
            $log_str = "[Dock->HpCoupon->getHpCoupons]  " . HP_GETOFFLINECOUPON . " post_data->" . json_encode($req);
            hpLogs($log_str);
            $params = array();
            $params['ftask'] = HP_GETOFFLINECOUPON;
            $params['fmch_id'] = '90001';
            $params['fsign'] = 'cf2e3152615709755767f11be5e8bc1d';
            $params['ftimestamp'] = time();
            $params['ftype'] = 0;
            $params['fdate'] =  '2019-01-01'.'~'.date('Y-m-d',time());
//            $params['fshopid'] = '10000100';
            $params['fvipid'] = '10251515';
//            var_dump($params);
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
            $log_str = "[Dock->HpCoupon->getHpCoupons]  " . HP_GETOFFLINECOUPON . " returndata->" . json_encode($return_data) . "\n" .
                "post_data:" . json_encode($params);
            hpLogs($log_str);
            $return_arr = json_decode($return_data['data'], true);
            var_dump($return_arr);
        }


    public function newCoupon(){
        $params = array();
        $params['ftask'] = HP_OFFLINE;
        $params['fmch_id'] = '90001';
        $params['fsign'] = 'cf2e3152615709755767f11be5e8bc1d';
        $params['ftimestamp'] = time();
        $params['fstartdate'] = date('Y-m-d',time());
        $params['fenddate'] = date('Y-m-d',time());
        $params['fvipid'] = '1908151827054599311';
        $params['ftype'] = 1;
        $params['famt'] = 0.7;
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
        $log_str = "[Dock->HpCoupon->onlineCouponToOffline]  ".HP_OFFLINE." returndata->".json_encode($return_data)."\n".
            "post_data:".json_encode($params);
        hpLogs($log_str);
        $return_arr = json_decode($return_data['data'],true);
        var_dump($return_arr);

//        $params = array();
//        $params['ftask'] = HP_OFFLINE;
//        $params['fmch_id'] = '90001';
//        $params['fsign'] = 'cf2e3152615709755767f11be5e8bc1d';
//        $params['ftimestamp'] = time();
//        $params['fstartdate'] = date('Y-m-d',time());
//        $params['fenddate'] = date('Y-m-d',time());
//        $params['fvipid'] = '1908151827054599311';
//        $params['ftype'] = 0;
//        $params['famt'] = 0.8;
//        $params['fuseminamt'] = 500;
//        $headers = array("Content-Type : text/html;charset=UTF-8");
//        $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
//        $log_str = "[Dock->HpCoupon->onlineCouponToOffline]  ".HP_OFFLINE." returndata->".json_encode($return_data)."\n".
//            "post_data:".json_encode($params);
//        hpLogs($log_str);
//        $return_arr = json_decode($return_data['data'],true);
//        var_dump($return_arr);
    }

    public function getHpStore()
    {
        $storeData = D('StoreMemberBind')->getXXStoreMemberBindInfo();
        foreach ($storeData as $key => $value){
            $params = array();
            $params['ftask'] = HP_GETSTORE;
            $params['fmch_id'] = $value['fmch_id'];
            $params['fsign'] = $value['fsign'];
            $params['ftimestamp'] = time();
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
            $log_str = "[Dock->HpStore->getHpStore]  ".HP_GETSTORE." returndata->".json_encode($return_data)."\n".
                "post_data:".json_encode($params);
            hpLogs($log_str);
          var_dump($return_data);
        }
    }
}