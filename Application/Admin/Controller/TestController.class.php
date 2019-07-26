<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/9
 * Time: 17:13
 */

namespace Admin\Controller;


class TestController extends AdminController
{
    public function test(){
        $params = array();
        $params['store_id'] = session('store_id');
        $params['state'] = 0;
        $coupons_lists = M('xx_coupons_lists_record')->field('is_delete,state,store_id',true)->where($params)->select();
        output_data($coupons_lists);
    }

    public function t(){
        $ip = '140.243.5.7';
        $key = 'WINBZ-7V6R3-SPD3V-YNVEO-2KOVJ-G4BZ2';
        $tx_url = "http://apis.map.qq.com/ws/location/v1/ip?ip=".$ip."&key=".$key;
        $tx_ip_info = json_decode(file_get_contents($tx_url));
        $ali_url = 'http://ip.taobao.com/service/getIpInfo.php?ip='.$ip;
        $ali_ip_info = json_decode(file_get_contents($ali_url));
        var_dump($tx_ip_info);echo "</br>";
        var_dump($ali_ip_info);
//        if(!$tx_ip_info->status=='0'){
//            output_error(-1);
//        }
//        $city = $ipinfo->result->ad_info->city;
//        $local['city'] = $city;
//        output_data($local);
    }
}