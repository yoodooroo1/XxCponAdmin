<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/9
 * Time: 17:13
 */

namespace Admin\Controller;


class TestController extends BaseController
{

    public function test()
    {
        $params['sign'] = '7c0ddd4570fcc3ef3ea61d5f9b98a067';
        $params['fmch_id'] = '90001' ;
        $params['fsign'] = 'cf2e3152615709755767f11be5e8bc1d' ;
        $params['store_id'] = '27' ;
        $params['tel'] = '15056648145' ;
        $params['member_card'] = '100001126' ;
        $params['openid'] = '' ;
        $params['member_name'] = 'test' ;
        $headers = array("Content-Type :application/json;charset=UTF-8");
        $return_data = httpRequest('http://123.207.98.106/Dock.php?c=HpMember&a=bindHpMember', "POST",json_encode($params), $headers);
        var_dump($return_data);
    }

    public function bindMember()
    {
        $params['sign'] = '7c0ddd4570fcc3ef3ea61d5f9b98a067';
        $params['store_id'] = '27' ;
//        $params['tel'] = '15014608148';
        $params['member_card'] = '1001111';
        $params['member_name'] = 'test';
        $params['member_id'] = '101011';
        $params['bind_type'] = 2;
        $headers = array("Content-Type :application/json;charset=UTF-8");
        $return_data = httpRequest('http://123.207.98.106/Dock.php?c=HpMember&a=bindHpMember', "POST",json_encode($params), $headers);
        var_dump($return_data);
    }

    public function test3()
    {
        $params['sign'] = '7c0ddd4570fcc3ef3ea61d5f9b98a067';
        $params['store_id'] = '27' ;
        $params['third_member_id'] = '1908151827054599311';
        $params['coupons_type'] = '1';
        $headers = array("Content-Type :application/json;charset=UTF-8");
        $return_data = httpRequest('http://123.207.98.106/Dock.php?c=HpCoupon&a=getHpMemberCoupons', "POST",$params, $headers);
        var_dump($return_data);
    }

    public function pointChange(){
        $params['store_id'] = '27' ;
        $params['sign'] = '7c0ddd4570fcc3ef3ea61d5f9b98a067';
        $params['third_member_id'] = '1908151827054599311';
        $params['credit'] = 100;
        $params['type'] = 1;
        $headers = array("Content-Type :application/json;charset=UTF-8");
        $return_data = httpRequest('http://123.207.98.106/Dock.php?c=HpCredit&a=memberCreditTransform', "POST",$params, $headers);
        var_dump($return_data);

    }

    public function getPoint(){
        $params['store_id'] = '27' ;
        $params['sign'] = '7c0ddd4570fcc3ef3ea61d5f9b98a067';
        $params['third_member_id'] = '1908151827054599311';
        $headers = array("Content-Type :application/json;charset=UTF-8");
        $return_data = httpRequest('http://123.207.98.106/Dock.php?c=HpCredit&a=getMemberCreditInfo', "POST",$params, $headers);
//        var_dump($return_data);
        print_r($return_data);
    }




}