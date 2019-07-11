<?php
namespace Dock\Controller;
use Think\Controller;
class IndexController extends HpBaseController {
    public function index(){
        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
    }


//    public function test(){
//        $params = array();
//        $params['fmch_id'] = '90002';
//        $content['coupons_type'] = '1';
//        $content['coupons_discount'] = '0.8';
//        $content['create_time'] = '1561432894';
//        $content['end_time'] = '1561432894';
//        $content['store_id'] = '1';
//        $content['member_id'] = '34354';
//        $params['coupon_content'] = $content;
//        var_dump($params);
//        $headers = array("Content-Type : text/html;charset=UTF-8");
//        $return = httpRequest('http://haoputest.com/Dock.php?c=index&a=onlineCouponToOffline',
//            'POST',json_encode($params),$headers);
//        var_dump($return);
//    }

    public function onlineCouponToOffline()
    {
        $req = $this->req;
        $log_str = "[Dock->HpCoupon->onlineCouponToOffline]  ".HP_OFFLINE." post_data->".json_encode($req);
        hpLogs($log_str);
        $fmch_id = $req['fmch_id'];
        $cp = $req['coupon_content'];
        $params = array();
        $params['ftask'] = HP_OFFLINE;
        $params['fmch_id'] = $this->config[$fmch_id]['fmch_id'];
        $params['fsign'] = $this->config[$fmch_id]['fsign'];
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
        $log_str = "[Dock->HpCoupon->onlineCouponToOffline]  ".HP_OFFLINE." post_data->".json_encode($params);
        hpLogs($log_str);
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
//            $xx_url = $this->getXxUrl("Hp", "toOfflineCoupon");
//            $return_data_two = httpRequest($xx_url, "post", $post_data);
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
}