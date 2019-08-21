<?php

namespace Dock\Controller;

class HpMemberController extends HpBaseController
{
    public function index()
    {
        die("dock");
    }


    public function addHpMember()
    {
        $storeData = $this->getHpStoreDatas();
        foreach ($storeData as $value) {
            if (empty($value['store_id'])) continue;
            $member_version = M("mb_hp_sync_version")->where(array('store_id' => $value['store_id']))->find();
            $mWhere = array();
            $mWhere['version'] = array('gt', $member_version['member_version']);
            $mWhere['store_id'] = $value['store_id'];
            $storeMemberData = M("mb_storemember")->where($mWhere)->limit(900)->order('version asc')->select();
            $log_str = "[Dock->HpMember->addHpMember]  "." storeMemberData->".json_encode($storeMemberData);
            hpLogs($log_str);
            foreach ($storeMemberData as $svalue) {
                $memberData = M('member')->where(array('member_id' => $svalue['member_id']))->find();
                if (!empty($memberData['bindtel']) && !empty($svalue['wx_openid'])) {
                    $params = array();
                    $params['ftask'] = HP_ADDMEMBER;
                    $params['fmch_id'] = $this->config[$value['fmch_id']]['fmch_id'];
                    $params['fsign'] = $this->config[$value['fmch_id']]['fsign'];
                    $params['fmobile'] = $memberData['bindtel'];
                    $params['fname'] = $memberData['member_name'];
                    $params['fopenid'] = $svalue['wx_openid'];
                    $params['ftimestamp'] = time();
                    $headers = array("Content-Type : text/html;charset=UTF-8");
                    $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
                    $return_arr = json_decode($return_data['data'], true);
                    if ($return_arr['result']['code'] == 0) {
                        $tag = M("mb_hp_sync_version")->where(array('store_id' => $value['store_id']))->save(array('member_version' => $svalue['version']));
                        $log_str = "[会员添加同步]: member_id:".$memberData['member_id']. "  fmobile:".$memberData['bindtel']."  wx_openid:".$svalue['wx_openid']."\n".
                            " version:".$svalue['version']. " 更新版本数据库结果:".$tag."\n".
                             "请求数据:".json_encode($params)."\n".
                              " result:".json_encode($return_data);
                        hpLogs($log_str);
                    } else {
                        $log_str = "[会员添加同步失败]: member_id:".$memberData['member_id']. "  fmobile:".$memberData['bindtel']."  wx_openid:".$svalue['wx_openid']."\n".
                            "请求数据:".json_encode($params)."\n".
                            " result:".json_encode($return_data);
                        hpLogs($log_str);
                        break;
                    }
                }else{
                    $tag = M("mb_hp_sync_version")->where(array('store_id' => $value['store_id']))->save(array('member_version' => $svalue['version']));
                    $log_str = "[会员无需同步]: member_id:".$memberData['member_id']. "  fmobile:".$memberData['bindtel']."  wx_openid:".$svalue['wx_openid']."\n".
                        " version:".$svalue['version']. " 更新版本数据库结果:".$tag."\n".
                    hpLogs($log_str);
                }
            }

        }
    }
/**会员绑定接口
 * /Dock.php?c=HpMember&a=bindHpMember
 * $params sign
 * $params string fmch_id
 * $params string fsign
 * $params int store_id //门店id
 * $params string tel //电话
 * $params string openid //微信号
 * $params string member_card //绑定卡号
 * $params string member_name
 * return{
        code int 200
 *      data{
        code
 * }
 * }
**/
    public function bindHpMember(){
        $req = $this->req;
        $log_str = "[Dock->HpMember->bindHpMember]  " . HP_ADDBINDMEMBER . " post_data->" . json_encode($req);
        hpLogs($log_str);
        $store_info = D('StoreMemberBind')->getStoreInfoById($req['store_id']);
        $params = array();
        $params['ftask'] = HP_ADDBINDMEMBER;
        $params['fmch_id'] = $store_info['fmch_id'];
        $params['fsign'] = $store_info['fsign'];
        if(empty($req['bind_type'])){
            output_error('-100','类型错误','请选择绑定类型');
        }
        $params['fmobile'] = $req['tel'];
        $params['fcardno'] = $req['member_card'];
        $params['password'] = $req['password'];
        $params['fopenid'] = $req['fopenid'];
        $params['fname'] = $req['member_name'];
        $params['fshopid'] = $store_info['fshopid'];
        $params['ftimestamp'] = time();
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
        $log_str = "[Dock->HpMember->bindHpMember]  ".HP_ADDBINDMEMBER." returndata->".json_encode($return_data)."\n".
            "post_data:".json_encode($params);
        hpLogs($log_str);
        $return_arr = json_decode($return_data['data'], true);
        if ($return_arr['result']['code'] == 0) {
            $records = $return_arr['records'][0];
            $rs = array();
            $rs['xx_member_id'] = $req['member_id'];
            $rs['third_member_id'] = $records['nid'];
            $rs['tel'] = $records['fmobile'];
            $rs['openid'] = $records['fopenid'];
            $rs['member_card'] = $records['fcardno'];
            $rs['summary'] = $records['fsummary'];
            $rs['store_id'] = $req['store_id'];
            $checkMember = M('member_bind')->where(array('third_member_id'=>$records['nid']))->find();
            if(!empty($checkMember)){
                $save = M('member_bind')->where(array('id'=>$checkMember['id']))->save($rs);
                if(!empty($save)){
                    output_data($rs,'用户绑定成功');
                }
                output_data($rs,'用户绑定成功');
            }
            $add = M('member_bind')->add($rs);
            if(!empty($add)){
                $log_str = "[Dock->HpMember->bindHpMember]  " ."会员绑定成功," . " 会员信息:信息会员ID".$req['member_id'] . json_encode($rs);
                hpLogs($log_str);
                output_data($rs,'用户绑定成功');
            }

        }else{
            $log_str = "[Dock->HpMember->bindHpMember]  " ."会员绑定失败," . " 失败信息:" . $return_arr['result']['msg'];
            hpLogs($log_str);
            output_error(-100,$return_arr['result']['msg'],'请检查参数');
        }
    }
    public function getHpStoreDatas()
    {
        $post_data = array();
        $post_data['hp_mark'] = 0;
        $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
        $xx_url = $this->getXxUrl("Hp", "getHpStoreAccount");
        $return_data = httpRequest($xx_url, "post", $post_data);
        $return_data = json_decode($return_data['data'], true);
        if ($return_data['result'] == 0) {
            var_dump($return_data['datas']);
            return $return_data['datas'];
        }

        return array();
    }

    public function getMemberPoint(){
        $params = array();
        $params['ftask'] = HP_ADDMEMBER;
        $params['fmch_id'] = '';
        $params['fsign'] = '';
        $params['fmobile'] = '';
        $params['fvipid'] = '';
        $params['fopenid'] = '';
        $params['ftimestamp'] = time();
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
        $return_arr = json_decode($return_data['data'], true);
    }

}