<?php

namespace Dock\Controller;


class HpOrderController extends HpBaseController
{
    public function index()
    {
        die("dock");
    }

    public function saveOrder()
    {
            $req = I();
            $log_str = "[Dock->HpOrder->saveOrder]  ".HP_SAVEORDER." post_data->".json_encode($req);
            hpLogs($log_str);
            $fmch_id = $req['fmch_id'];
            $params = array();
            $params['ftask'] = HP_SAVEORDER;
            $params['fmch_id'] = $this->config[$fmch_id]['fmch_id'];
            $params['fsign'] = $this->config[$fmch_id]['fsign'];
            $params['fshopid'] = $req['fshopid'];

            $order_goods = json_decode($req['order_content'],true);
            $prod_arr = array();
            $ftnum = 0;
            foreach ($order_goods as $key => $value){
                $one = array();
                $one['frow'] = $key;
                $one['fprodetailid'] = $value['goods_barcode'];
                $one['fprodid'] = $value['goods_qrcode'];
                $one['famt'] = strval($value['gou_num'] * $value['goods_price']);
                $one['fprice'] =  $value['goods_price'];
                $one['fnum'] =  strval($value['gou_num']);
                $prod_arr[] = $one;
                $ftnum += $value['gou_num'];
            }
            $params['prod'] = $prod_arr;
            $pay_arr = array();
            $pay_arr['fcardno'] = '';
            $pay_arr['fcoupon'] = '';
            $pay_arr['fwxamt'] = strval($req['totalprice']);
            $pay_arr['fpoint'] = '';
            $pay_arr['fpointamt'] = '';
            $pay_arr['fcard'] = '';
            $pay_arr['fcardamt'] = '';
            $pay_arr['fpayamt'] = $req['totalprice'];
            $params['pay'] =  $pay_arr;
            $params['ftnum'] = strval($ftnum);
            $wx_openid = $req['wx_openid'];
            $member_info = $this->getMemberInfo($params['fmch_id'], $params['fsign'], $wx_openid);
            $log_str = "[Dock->HpOrder->saveOrder]  wx_openid:".$wx_openid." fmch_id:".$params['fmch_id'].
                " fsign:".$params['fsign']." getMemberInfo>".json_encode($member_info['records']);
            hpLogs($log_str);
            if ($member_info['result']['code'] === "0") {
                $vipinfo = $member_info['records'][0];
                $params['fvipid'] = $vipinfo['nid'];
                if (empty($params['fvipid'])){
                    $params['fvipid'] = '';
                }
             }else{
                $params['fvipid'] = '';
    }
            $params['ftimestamp'] = time();
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $return_data = httpRequest($this->Hp_base_url, "POST", json_encode($params), $headers);
            $log_str = "[Dock->HpOrder->saveOrder]  ".HP_SAVEORDER." returndata->".json_encode($return_data)."\n".
                "post_data:".json_encode($params);
            hpLogs($log_str);
    }


    public function getMemberInfo($fmch_id, $fsign, $wx_open_id)
    {
//        $fmch_id = "90002";
//        $fsign = "08077d8ee01bad9968223b689925179b";
//        $wx_open_id  = "o6-UsuFXd4B5lUnsoNFQ9AuV0X0A";
        $params = array();
        $params['ftask'] = HP_GETMEMBER;
        $params['fmch_id'] = $fmch_id;
        $params['fsign'] = $fsign;
        $params['fopenid'] = $wx_open_id;
        $params['fmobile'] = "";
        $params['nid'] = "";
        $params['ftimestamp'] = time();
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($this->Hp_base_url, "POST", json_encode($params), $headers);
        $return_arr = json_decode($return_data['data'],true);
        return $return_arr;
    }


}