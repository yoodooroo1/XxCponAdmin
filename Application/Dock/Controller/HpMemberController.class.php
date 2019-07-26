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
                    $return_data = httpRequest($this->Hp_base_url, "POST", json_encode($params), $headers);
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

    public function getHpStoreDatas()
    {
        $post_data = array();
        $post_data['hp_mark'] = 0;
        $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
        $xx_url = $this->getXxUrl("Hp", "getHpStoreAccount");
        $return_data = httpRequest($xx_url, "post", $post_data);
        $return_data = json_decode($return_data['data'], true);
        if ($return_data['result'] == 0) {
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
        $return_data = httpRequest($this->Hp_base_url, "POST", json_encode($params), $headers);
        $return_arr = json_decode($return_data['data'], true);
    }

}