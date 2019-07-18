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

    public function test()
    {
        //调用xx登入接口
//        $url ='http://dev-mapi.duinin.com/index.php?act=Login&op=index';
//        $post_data['username'] = 666666;
//        $post_data['password'] = 123456;
//        $post_data['client'] = 'web';
//        $post_data['user_type'] = 'seller';

        $url = 'http://dev-mapi.duinin.com/index.php?act=Coupons&op=syncdata';
        $post_data['version'] = 0;
        $post_data['key'] = '079a2b1814ab89a1ccf393a6261ca123';
        $post_data['client'] = 'web';
        $post_data['user_type'] = 'seller';
        $post_data['store_id'] = 27;
        $post_data['comchannel_id'] = 0;

        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($url, 'POST', $post_data, $headers);
        $log_str = "[Dock->Admin->loginCheck]  " . HP_GETGOODS . " returndata->" . json_encode($return_data) . "\n" .
            "post_data:" . json_encode($post_data);
        CouponAdminLogs($log_str);
        $return_info = json_decode($return_data['data'], true);
        var_dump($return_info['datas']);
    }
}