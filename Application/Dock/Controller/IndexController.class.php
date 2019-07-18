<?php
namespace Dock\Controller;
use Think\Controller;
class IndexController extends HpBaseController {
    public function index(){
        $this->show('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>','utf-8');
    }


    public function test(){
        {
//            $req = $this->req;
//            $log_str = "[Dock->HpCoupon->offlineCouponToOnline]  ".HP_ONLINE." post_data->".json_encode($req);
//            hpLogs($log_str);
            $fmch_id = '90002';
            $params = array();
            $params['ftask'] = 501;
            $params['fmch_id'] = $this->config[$fmch_id]['fmch_id'];
            $params['fsign'] = $this->config[$fmch_id]['fsign'];
            $params['ftimestamp'] = time();
            $params['fdata'] = '2019-01-11~2019-09-12';
            $params['ftype'] = 1;
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $return_data = httpRequest($this->base_url, "POST", json_encode($params), $headers);
            $log_str = "[Dock->HpCoupon->offlineCouponToOnline]  ".HP_ONLINE." returndata->".json_encode($return_data)."\n".
                "post_data:".json_encode($params);
            hpLogs($log_str);
            $return_arr = json_decode($return_data['data'],true);
            var_dump($return_arr);
        }
    }
}