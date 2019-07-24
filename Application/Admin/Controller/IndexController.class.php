<?php
namespace Admin\Controller;

class IndexController extends AdminController {
    public function index(){
        $this->display('index');
    }

    public function welcome(){
        $this->display('welcome');
    }

    public function test()
    {
//           调用xx登入接口
        $url ='http://dev-mapi.duinin.com/index.php?act=Login&op=index';
        $post_data['username'] = 666666;
        $post_data['password'] = 123456;
        $post_data['client'] = 'web';
        $post_data['user_type'] = 'seller';
        $return_data = httpRequest($url, 'POST', $post_data, $headers);
        $log_str = "[Dock->Admin->loginCheck]  "  . " returndata->" . json_encode($return_data) . "\n" .
            "post_data:" . json_encode($post_data);
        CouponAdminLogs($log_str);
        $return_info = json_decode($return_data['data'], true);
        var_dump($return_data);

//        $url = 'http://dev-mapi.duinin.com/index.php?act=Coupons&op=syncdata';
//        $post_data['version'] = 0;
//        $post_data['key'] = '593e4be03c60288827eb4d68763c56eb';
//        $post_data['client'] = 'web';
//        $post_data['user_type'] = 'seller';
//        $post_data['store_id'] = 27;
////        $post_data['comchannel_id'] = 0;
//
//
//        $bind = M('member_bind');
//        $bind->startTrans();
//        try{
//        $url = 'http://dev-mapi.duinin.com/index.php?act=Hp&op=bindErpStore';
//        $m['fmch_id'] = '123';
//        $m['fmch_sign'] = '123';
//        $m['store_id'] = '25';
//        $m['addtime'] = time();
//        $res = $bind->data($m)->add();
//        if(!$res){
//            output_error();
//        }
//        $post_data['hp_mark	'] = 0;
//        $post_data['hp_token'] = md5($post_data['hp_mark']. 'vjd8988998');
//        $post_data['fmch_id'] = $m['fmch_id'];
//        $post_data['fmch_key'] = $m['fmch_key'];
//        $post_data['store_id'] = '27';
//        $post_data['state'] = 'web';
//        $post_data['user_type'] = 'seller';
//            $url = $this->getXxUrl('Hp','bindErpStore');
//        $headers = array("Content-Type : text/html;charset=UTF-8");
//        $return_data = httpRequest($url, 'POST', $post_data, $headers);
//        $log_str = "[Dock->Admin->memberBind]  " . " returndata->" . json_encode($return_data) . "\n" .
//            "post_data:" . json_encode($post_data);
//        CouponAdminLogs($log_str);
//        $return_info = json_decode($return_data['data'], true);
//        $bind->commit();
//        output_data($return_data);
//        }catch (\Exception $e){
//            $bind->rollback();
//        }
//        $res = httpRequest('http://123.207.98.106/Dock.php?c=HpGoods&a=getHpGoods','POST','','');
//        var_dump($res);
    }

    function get_local(){
        $ip = $this->getIp();
        $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
        $ipinfo=json_decode(file_get_contents($url));
        if($ipinfo->code=='1'){
            return false;
        }
        $city = $ipinfo->data->region.' '.$ipinfo->data->city;
        var_dump($city);
    }

}