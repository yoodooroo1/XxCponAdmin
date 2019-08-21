<?php
/**
 * Created by PhpStorm.
 * User: Ydr
 * Date: 2019/8/13
 * Time: 11:48
 */

namespace Dock\Controller;


class HpCreditController extends HpBaseController
{
    public function index()
    {
        die("dock");
    }

    public function getMemberCreditInfo(){
        $req = $this->req;
        $store_info = D('StoreMemberBind')->getStoreInfoById($req['store_id']);
        $params = array();
        $log_str = "[Dock->HpCredit->getMemberCreditInfo]  " . HP_CREDITCHANGE . " post_data->" . json_encode($req);
        creditLogs($log_str);
        $params['ftask'] = HP_GETCREDIT;
        $params['fmch_id'] = $store_info['fmch_id'];
        $params['fsign'] = $store_info['fsign'];
        $params['fvipid'] = $req['third_member_id'];
        $params['fshopid'] = $req['store_id'];
        $params['ftimestamp'] = time();
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
        $log_str = "[Dock->HpCredit->memberCreditTransform]  ".HP_GETCREDIT." returndata->".json_encode($return_data)."\n".
            "post_data:".json_encode($params);
        creditLogs($log_str);
        $return_arr = json_decode($return_data['data'], true);
        if ($return_arr['result']['code'] == 0) {
            $records = $return_arr['records'];
            $result['usable_credit'] = $records[0]['fnpoint'];
            $result['amount_credit'] = $records[0]['ftpoint'];
            output_data($result);
        }else{
            output_error(-100,$return_arr['result']['msg'],'');
        }
    }
//线上积分转线下积分
/**
 * $params int type 1 线上转线下 2线下转线上
 *
**/

    public function memberCreditTransform(){
        $req = $this->req;
        $store_info = D('StoreMemberBind')->getStoreInfoById($req['store_id']);
        $member_info = D('StoreMemberBind')->getMemberInfoById($req['third_member_id']);
        $log_str = "[Dock->HpCredit->memberCreditTransform]  " . HP_CREDITCHANGE . " post_data->" . json_encode($req);
        creditLogs($log_str);
        $params = array();
        $rate_data = M('store_credit_rate')->where(array('store_id'=>$req['store_id']))->find();
        $rate = $rate_data['rate'];
        if(empty($rate)){
            output_error(-100,'缺少参数','转换比例率未配置');
        }
        if(empty($req['type'])){
            output_error(-100,'缺少参数','缺少转换类型');
        }
        if(empty($req['credit'])){
            output_error(-100,'缺少参数','转换积分不能为空');
        }
        $params['ftask'] = HP_CREDITCHANGE;
        $params['fmch_id'] = $store_info['fmch_id'];
        $params['fsign'] = $store_info['fsign'];
        $params['fvipid'] = $req['third_member_id'];
        $params['fshopid'] = $store_info['fshopid'];
        $params['ftimestamp'] = time();
        $headers = array("Content-Type : text/html;charset=UTF-8");
        if($req['type'] == 1){
            $num = intval($rate * intval($req['credit']));
            $params['fpoint'] = $num;
            $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
            $log_str = "[Dock->HpCredit->memberCreditTransform]  ".HP_CREDITCHANGE." returndata->".json_encode($return_data)."\n".
                "post_data:".json_encode($params);
            creditLogs($log_str);
            $return_arr = json_decode($return_data['data'], true);
            $record = array();
            $record['type'] = 1;
            $record['create_time'] = time();
            $record['store_id'] = $req['store_id'];
            $record['xx_member_id'] = $member_info['xx_member_id'];
            $record['xx_credit_amount'] = -intval($req['credit']);
            $record['third_credit_amount'] = $num;
            if ($return_arr['result']['code'] == 0) {
                $record['third_transform_state'] = 1;
            }
            $post_data = array();
            $post_data['hp_mark'] = 0;
            $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
            $post_data['member_id'] = $member_info['xx_member_id'];
            $post_data['store_id'] = $req['store_id'];
            $post_data['num'] = -intval($req['credit']);
            $xx_url = $this->getXxUrl("Hp", "hpChangeCredit");
            $return_data_two = httpRequest($xx_url, "post", $post_data);
            if (!empty($return_data_two)&&$return_data_two['result'] == 0) {
                $record['xx_transform_state'] = 1;
            }
            $add = M('credit_transform_record')->add($record);
            $log_str = "[Dock->HpCredit->memberCreditTransform]  ".'线上转线下:'.HP_CREDITCHANGE." ,转换记录:->id:".json_encode($add).":".json_encode($record)."\n";
            creditLogs($log_str);
        }else if ($req['type'] == 2){
            $num = intval(intval($req['credit'])/$rate);
            $params['fpoint'] = -intval($req['credit']);
            $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
            $log_str = "[Dock->HpCredit->memberCreditTransform]  ".HP_CREDITCHANGE." returndata->".json_encode($return_data)."\n".
                "post_data:".json_encode($params);
            creditLogs($log_str);
            $return_arr = json_decode($return_data['data'], true);
            $record = array();
            $record['type'] = 2;
            $record['create_time'] = time();
            $record['store_id'] = $req['store_id'];
            $record['xx_member_id'] = $member_info['xx_member_id'];
            $record['xx_credit_amount'] = $num;
            $record['third_credit_amount'] = -intval($req['credit']);
            if ($return_arr['result']['code'] == 0) {
                $record['third_transform_state'] = 1;
            }
            $post_data = array();
            $post_data['hp_mark'] = 0;
            $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
            $post_data['member_id'] = $member_info['xx_member_id'];
            $post_data['store_id'] = $req['store_id'];
            $post_data['num'] = $num;
            $xx_url = $this->getXxUrl("Hp", "hpChangeCredit");
            $return_data_two = httpRequest($xx_url, "post", $post_data);
            if (!empty($return_data_two)&&$return_data_two['result'] == 0) {
                $record['xx_transform_state'] = 1;
            }
            $add = M('credit_transform_record')->add($record);
            $log_str = "[Dock->HpCredit->memberCreditTransform]  ".'线下转线上:'.HP_CREDITCHANGE." ,转换记录:->id:".json_encode($add).":".json_encode($record)."\n";
            creditLogs($log_str);
        }
    }
}