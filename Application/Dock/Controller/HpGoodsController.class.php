<?php

namespace Dock\Controller;

use Think\Controller;

class HpGoodsController extends HpBaseController
{
    public function index()
    {
        die("dock");
    }

    public function getHpGoods()
    {
        $storeData = $this->getHpStoreDatas();
        foreach ($storeData as $value){
            if (empty($value['store_id'])) continue;
            $params = array();
            $params['ftask'] = HP_GETGOODS;
            $params['fmch_id'] = $this->config[$value['fmch_id']]['fmch_id'];
            $params['fsign'] = $this->config[$value['fmch_id']]['fsign'];
            //$params['nid'] = $value['nid'];
            $params['ftimestamp'] = time();
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $return_data = httpRequest($this->base_url, "POST", json_encode($params), $headers);
            $log_str = "[Dock->HpGoods->getHpGoods]  ".HP_GETGOODS." returndata->".json_encode($return_data)."\n".
                "post_data:".json_encode($params);
            hpLogs($log_str);
            $return_arr = json_decode($return_data['data'], true);
            $this->checkResult($return_arr);
            $post_data = array();
            $post_data['hp_mark'] = time();
            $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
            $post_data['fmch_id'] = $value['fmch_id'];
            $post_data['info'] = $return_data['data'];
            $post_data['store_id'] = $value['store_id'];
            $xx_url = $this->getXxUrl("Hp", "saveHpGoods");
            $return_data_two = httpRequest($xx_url, "post", $post_data);

          break;
        }
    }

    public function getHpGoodsStock(){
        $storeData = $this->getHpStoreGoodsDatas();
        foreach ($storeData as $value){
            foreach ($value['nid'] as $nid_value){
                $params = array();
                $params['ftask'] = HP_GETGOODS_STOCK;
                $params['fmch_id'] = $this->config[$value['fmch_id']]['fmch_id'];
                $params['fsign'] = $this->config[$value['fmch_id']]['fsign'];
                $params['fprodid'] = $nid_value['goods_qrcode'];
                $params['ftimestamp'] = time();
                $headers = array("Content-Type : text/html;charset=UTF-8");
                $return_data = httpRequest($this->base_url, "POST", json_encode($params), $headers);
                $log_str = "[Dock->HpGoods->getHpGoodsStock]  ".HP_GETGOODS_STOCK." returndata->".json_encode($return_data)."\n".
                "post_data:".json_encode($params);
                hpLogs($log_str);
                $return_arr = json_decode($return_data['data'], true);
                $this->checkResult($return_arr);
                $post_data = array();
                $post_data['hp_mark'] = time();
                $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
                $post_data['fmch_id'] = $value['fmch_id'];
                $post_data['info'] = $return_data['data'];
                $post_data['store_id'] = $value['store_id'];
                $xx_url = $this->getXxUrl("Hp", "saveHpGoodsStock");
                $return_data_two = httpRequest($xx_url, "post", $post_data);
            }
            break;
        }
    }


    public function getHpStoreDatas(){
        $post_data = array();
        $post_data['hp_mark'] = time();
        $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
        $xx_url = $this->getXxUrl("Hp", "getHpStoreAccount");
        $return_data = httpRequest($xx_url, "post", $post_data);
        $return_data = json_decode($return_data['data'], true);
        if ($return_data['result'] == 0){
            return $return_data['datas'];
        }
        return array();
    }

    public function getHpStoreGoodsDatas(){
        $post_data = array();
        $post_data['hp_mark'] = time();
        $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
        $xx_url = $this->getXxUrl("Hp", "getHpStoreGoods");
        $return_data = httpRequest($xx_url, "post", $post_data);
        $return_data = json_decode($return_data['data'], true);
        if ($return_data['result'] == 0){
            return $return_data['datas'];
        }
        return array();
    }


    public function getHpStoreClass(){
        $storeData = $this->getHpStoreDatas();
        foreach ($storeData as $value){
            $params = array();
            $params['ftask'] = HP_GETGOODS_STOCK;
            $params['fmch_id'] = $this->config[$value['fmch_id']]['fmch_id'];
            $params['fsign'] = $this->config[$value['fmch_id']]['fsign'];
            //$params['nid'] = $value['nid'];
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
            $post_data['store_id'] = $value['store_id'];
            $post_data['store_id'] = $value['store_id'];
            $xx_url = $this->getXxUrl("Hp", "saveHpGoodsClass");
            $return_data_two = httpRequest($xx_url, "post", $post_data);

            break;
        }
    }


    public function getHpGoodsPic(){
        $storeData = $this->getHpStoreGoodsDatas();
        foreach ($storeData as $value){
            foreach ($value['nid'] as $nid_value){
                $params = array();
                $params['ftask'] = HP_GETGOODS_PIC;
                $params['fmch_id'] = $this->config[$value['fmch_id']]['fmch_id'];
                $params['fsign'] = $this->config[$value['fmch_id']]['fsign'];
                $params['fprodid'] = $nid_value['goods_qrcode'];
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
                $post_data['store_id'] = $value['store_id'];
//                $xx_url = $this->getXxUrl("Hp", "saveHpGoodsStock");
//                $return_data_two = httpRequest($xx_url, "post", $post_data);
            }
            break;
        }
    }

}