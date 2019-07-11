<?php

namespace Dock\Controller;

use Think\Controller;
use Think\Log;

class HpStoreController extends HpBaseController
{
    /**
     * 获取门店信息
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: czx
     * Date: 2019-04-17 11:25:21
     * Update: 2019-04-17 11:25:21
     * Version: 1.00
     */
    public function getHpStore()
    {
        foreach ($this->config as $key => $value){
            $params = array();
            $params['ftask'] = HP_GETSTORE;
            $params['fmch_id'] = $value['fmch_id'];
            $params['fsign'] = $value['fsign'];
            $params['ftimestamp'] = time();
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $return_data = httpRequest($this->base_url, "POST", json_encode($params), $headers);
            $return_arr = json_decode($return_data['data'], true);
            $this->checkResult($return_arr);
            $post_data = array();
            $post_data['hp_mark'] = time();
            $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
            $post_data['fmch_id'] = $value['fmch_id'];
            $post_data['info'] = $return_data['data'];
            $xx_url = $this->getXxUrl("Hp", "saveHpStore");
            $return_data_two = httpRequest($xx_url, "post", $post_data);
        }
    }




}