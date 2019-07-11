<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends AdminController {
    public function index(){
        $this->display('index');
    }

    public function welcome(){
        $this->display('welcome');
    }

    public function test(){
        $headers = array("Content-Type : application/json;charset=UTF-8");
        $data['fmch_id'] = '90002';
        $coupon_content['coupons_type'] = 1;
        $coupon_content['create_time'] = time();
        $coupon_content['end_time'] = time();
        $coupon_content['store_id'] = '11';
        $coupon_content['member_id'] = '1';
        $coupon_content['coupons_discount'] = '0.8';
        $data['coupon_content'] = $coupon_content;
        $res = httpRequest('http://erpds_test.duinin.com/Dock.php?c=index&a=onlineCouponToOffline','POST',$data,$headers);
        var_dump($res);
    }
}