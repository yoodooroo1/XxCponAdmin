<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/25
 * Time: 11:14
 */

namespace Dock\Controller;


use Think\Controller;

class HpCouponController extends HpBaseController
{
    public function index()
    {
        die("dock");
    }

    public function getHpCoupons(){
        $storeData = $this->getMemberInfo();
        foreach ($storeData as $value){
        }
    }

    public function transformHpCouponsByXx()
    {
        $req= $this->req;
        $log_str = "[Dock->HpCoupon->onlineCouponToOffline]  ".HP_OFFLINE." post_data->".json_encode($req);
        hpLogs($log_str);
        $cp = $req['coupon_content'];
        $params = array();
        $params['ftask'] = HP_OFFLINE;
        $params['fmch_id'] = $req['fmch_id'];;
        $params['fsign'] = $req['fsign'];
        $params['ftimestamp'] = time();
        $params['ftype'] = $cp['coupons_type'];
        $params['famt'] = '';
        $params['fstartdate'] = date('Y-m-d',$cp['create_time']);
        $params['fenddate'] = date('Y-m-d',$cp['end_time']);
        $params['fuseshopid'] = $cp['store_id'];
        $params['fvipid'] = $cp['member_id'];
        $params['fuseminamt'] = '';
        if($params['ftype'] == 0){
            $params['famt'] = $cp['coupons_money'];
            $params['fuseminamt'] = $cp['limit_money'];
        }else if($params['ftype'] == 1){
            $params['famt'] = $cp['coupons_discount'];
            unset($params['fuseminamt']);
        }
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($this->base_url, "POST", json_encode($params), $headers);
        $log_str = "[Dock->HpCoupon->onlineCouponToOffline]  ".HP_OFFLINE." returndata->".json_encode($return_data)."\n".
            "post_data:".json_encode($params);
        hpLogs($log_str);
        $return_arr = json_decode($return_data['data'],true);
        if($return_arr['result']['code'] == 0){
            $post_data = array();
            $post_data['hp_mark'] = time();
            $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
            $post_data['fmch_id'] = $params['fmch_id'];
            $info['nid'] = $return_arr['records'][0]['nid'];
            $info['coupons_type'] = $params['ftype'];
            $info['count'] = $params['famt'];
            $info['create_time'] = $cp['create_time'];
            $info['end_time'] = $cp['end_time'];
            $info['store_id'] = $params['fuseshopid'];
            $info['member_id'] = $params['fvipid'];
            $info['limit_money'] = '';
            if($info['coupons_type'] == 0){
                $info['limit_money'] = $params['fuseminamt'];
            }else if($info['coupons_type'] == 1){
                unset($info['limit_money']);
            }
             $post_data['info'] = $info;
             $xx_url = $this->getXxUrl("Hp", "toOfflineCoupon");
             $return_data_two = httpRequest($xx_url, "post", $post_data);
            $log_str = "[线上转线下成功]: nid:".$info['nid']. "  store_id:".$info['store_id']."  member_id:".$info['member_id']."\n";
            hpLogs($log_str);
            $this->response($info,'JSON','200');
        }else{
            $log_str = "[线上转线下失败]:"."请求数据:".json_encode($params)."\n".
                " result:".json_encode($return_data)."\n";
            hpLogs($log_str);
            $this->response('NULL','JSON','100');
        }
    }

    public function transformXxCouponsByHp()
    {
        $req = $this->req;
        $log_str = "[Dock->HpCoupon->offlineCouponToOnline]  ".HP_ONLINE." post_data->".json_encode($req);
        hpLogs($log_str);
        $cp = $req['coupon_content'];
        $params = array();
        $params['ftask'] = HP_ONLINE;
        $params['fmch_id'] = $req['fmch_id'];
        $params['fsign'] = $req['fsign'];
        $params['ftimestamp'] = time();
        $params['fcouponid'] = $cp['nid'];
        $params['ftype'] = $cp['coupons_type'];
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($this->base_url, "POST", json_encode($params), $headers);
        $log_str = "[Dock->HpCoupon->offlineCouponToOnline]  ".HP_ONLINE." returndata->".json_encode($return_data)."\n".
            "post_data:".json_encode($params);
        hpLogs($log_str);
        $return_arr = json_decode($return_data['data'],true);
        if($return_arr['result']['code'] == 0){
             $post_data = array();
             $post_data['hp_mark'] = time();
             $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
             $post_data['fmch_id'] = $params['fmch_id'];
             $info['nid'] = $params['fcouponid'];
             $info['coupons_type'] = $params['ftype'];
             $post_data['info'] = $info;
             $xx_url = $this->getXxUrl("Hp", "toOnlineCoupon");
             $return_data_two = httpRequest($xx_url, "post", $post_data);
            $log_str = "[线下转线上成功]: nid:".$info['nid']. "  coupons_type:".$info['coupons_type']."\n";
            hpLogs($log_str);
            $this->response($info,'JSON','200');
        }else{
            $log_str = "[线上转线下失败]:"."请求数据:".json_encode($params)."\n".
                " result:".json_encode($return_data)."\n";
            hpLogs($log_str);
            $this->response('NULL','JSON','100');
        }
    }

}