<?php

namespace Dock\Controller;


class HpStoreController extends HpBaseController
{
    /**
     * 获取门店信息(107)
     * User: czx
     * Date: 2019-04-17 11:25:21
     * Update: 2019-04-17 11:25:21
     * Version: 1.00
     * URL : /dock.php?c=HpGoods&a=getHpGoods
     */
    public function getHpStore()
    {
        $storeData = D('StoreMemberBind')->getXXStoreMemberBindInfo();
        foreach ($storeData as $key => $value){
            $params = array();
            $params['ftask'] = HP_GETSTORE;
            $params['fmch_id'] = $value['fmch_id'];
            $params['fsign'] = $value['fsign'];
            $params['ftimestamp'] = time();
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
            $log_str = "[Dock->HpStore->getHpStore]  ".HP_GETSTORE." returndata->".json_encode($return_data)."\n".
                "post_data:".json_encode($params);
            hpLogs($log_str);
            $return_arr = json_decode($return_data['data'], true);
            $this->hpResChecking($return_arr);
            $post_data = array();
            $post_data['hp_mark'] = 0;
            $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
            $post_data['fmch_id'] = $value['fmch_id'];
            $post_data['info'] = $return_data['data'];
            $xx_url = $this->getXxUrl("Hp", "saveHpStore");
            $return_data_two = httpRequest($xx_url, "post", $post_data);
        }
    }

}