<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/25
 * Time: 11:14
 */

namespace Dock\Controller;



use Think\Model;
use CouponModel;
class HpCouponController extends HpBaseController
{
    public function index()
    {
        die("dock");
    }

    public function getHpStoreCoupons()
    {
            $storeData = D('StoreMemberBind')->getXXStoreMemberBindInfo();
            foreach ($storeData as $value){
                $req = $this->req;
                $log_str = "[Dock->HpCoupon->getHpCoupons]  " . HP_GETOFFLINECOUPON . " post_data->" . json_encode($req);
                hpLogs($log_str);
                $params = array();
                $params['ftask'] = HP_GETOFFLINECOUPON;
                $params['fmch_id'] = $value['fmch_id'];
                $params['fsign'] = $value['fsign'];
                $params['ftimestamp'] = time();
                $params['ftype'] = 0;
                $params['fdate'] =  '2019-01-01'.'~'.date('Y-m-d',time());
                $params['fshopid'] = $value['store_id'];
                $headers = array("Content-Type : text/html;charset=UTF-8");
                $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
                $log_str = "[Dock->HpCoupon->getHpCoupons]  " . HP_GETOFFLINECOUPON . " returndata->" . json_encode($return_data) . "\n" .
                    "post_data:" . json_encode($params);
                hpLogs($log_str);
                $return_arr = json_decode($return_data['data'], true);
                if($return_arr['result']['code'] == 0){
                   output_data($return_arr['records'],'获取门店线下优惠卷成功');
                }
        }

    }

    public function getHpMemberCoupons(){
        $req = $this->req;
        $log_str = "[Dock->HpMember->getHpMemberCoupons]  " . HP_GETOFFLINECOUPON . " post_data->" . json_encode($req);
        hpLogs($log_str);
        $store_info = D('StoreMemberBind')->getStoreInfoById($req['store_id']);
        $params['ftask'] = HP_GETOFFLINECOUPON;
        $params['fmch_id'] = $store_info['fmch_id'];
        $params['fsign'] = $store_info['fsign'];
        $params['ftimestamp'] = time();
        if (empty($req['data'])){
            $params['fdate'] =  '2019-01-01'.'~'.date('Y-m-d',time());
        }else{
            $params['fdate'] = $req['data'];
        }
        $params['fvipid'] = $req['third_member_id'];//会员id
//        $params['fuseshopid'] = 10000100;
        $params['ftype']=2;
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
            $log_str = "[Dock->HpCoupon->getHpMemberCoupons]  ".HP_ADDBINDMEMBER." returndata->".json_encode($return_data)."\n".
                "post_data:".json_encode($params);
            hpLogs($log_str);
            $return_arr = json_decode($return_data['data'],true);
            $coupons = array();
            if ($return_arr['result']['code'] == 0) {
                $records = $return_arr['records'];
                foreach ( $records as $key => $value){
                    $list = array();
                    $list['type'] = $value['ftype'];
                    if($list['type']=='0'){
                        $list['coupons_name']= $value['famt'].'元优惠卷';
                    }else if($list['type']=='1'){
                        $list['coupons_name']= 10*($value['famt']).'折优惠卷';
                    }
                    $list['third_coupons_id'] = $value['nid'];
                    $list['coupons_money'] = $value['famt'];
                    $list['start_time'] = $value['fstartdate'];
                    $list['end_time'] = $value['fenddate'];
                    $list['limit_money'] = $value['fuseminamt'];
                    $list['type'] = $value['ftype'];
                    $coupons[]=$list;
                }
                output_data($coupons);
            }else{
            output_error(-100,$return_arr['result']['msg'],'');
        }
    }


    public function transformIntoHpCoupons()
    {
        $req= $this->req;
        $log_str = "[Dock->HpCoupon->transformHpCouponsByXx]  ".HP_OFFLINE." post_data->".json_encode($req);
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
        $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
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
//线下作废转换为线上
    public function transformIntoxxCoupons()
    {
        $req = $this->req;
        $log_str = "[Dock->HpCoupon->transformIntoxxCoupons]  ".HP_ONLINE." post_data->".json_encode($req);
        hpLogs($log_str);
        $cpMatchInfo = D('Coupon')->getStoreCouponMatchInfo($req['store_id'],$req['coupons_id'],$req['third_coupons_id']);
        if(empty($cpMatchInfo)){
            output_error(-100,'此优惠卷未关联不能转换哦!','');
        }
        if(!$cpMatchInfo['state']=='1'){
            output_error(-100,'此优惠卷未关联不能转换哦!','');
        }
        $params = array();
        $params['ftask'] = HP_ONLINE;
        $store_info = D('StoreMemberBind')->getStoreInfoById($req['store_id']);
        $params['fmch_id'] = $store_info['fmch_id'];
        $params['fsign'] = $store_info['fsign'];
        $params['ftimestamp'] = time();
        $params['fcouponid'] = $req['third_coupons_id'];//优惠卷id
        $params['ftype'] = $req['coupons_type'];//优惠卷类型
        $params['fvipid'] = $req['third_member_id'];//会员id
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
        $log_str = "[Dock->HpCoupon->transformIntoxxCoupons]  ".HP_ONLINE." returndata->".json_encode($return_data)."\n".
            "post_data:".json_encode($params);
        hpLogs($log_str);
        $return_arr = json_decode($return_data['data'],true);
        $record = array();
        $record['xx_member_id'] = $req['member_id'];
        $record['third_member_id'] = $req['third_member_id'];
        $record['xx_coupons_id'] = $req['coupons_id'];
        $record['third_coupons_id'] = $req['third_coupons_id'];
        $record['create_time'] = time();
        if($return_arr['result']['code'] == 0){
             $record['third_transform_state'] = 1;
            $log_str = "[线下转线上成功]: third_member_id:".$req['third_member_id'].":".$info['nid']. "  coupons_type:".$req['coupons_type']."\n";
            hpLogs($log_str);


             $post_data = array();
             $post_data['version'] = 0;
             $post_data['hp_mark'] = time();
             $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
             $post_data['fmch_id'] = $params['fmch_id'];
             $info['nid'] = $params['fcouponid'];
             $info['coupons_type'] = $params['ftype'];
             $post_data['info'] = $info;
             $xx_url = $this->getXxUrl("MemberCoupons", "addcoupons");
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