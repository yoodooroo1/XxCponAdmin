<?php

namespace Dock\Controller;

class HpGoodsController extends BaseController
{
    public function index()
    {
        die("dock");
    }

    /**同步商品信息(301)
     * URL : /dock.php?c=HpGoods&a=getHpGoods
     * return{
     }
     */
    public function getHpGoods()
    {
        $storeData = D('StoreMemberBind')->getXXStoreMemberBindInfo();
        $validate = new ValidateController();
        foreach ($storeData as $value){
            if (empty($value['store_id'])) continue;
            $params = array();
            $params['ftask'] = HP_GETGOODS;
            $params['fmch_id'] = $value['fmch_id'];
            $params['fsign'] = $value['fsign'];
            $params['ftimestamp'] = time();
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
            $log_str = "[Dock->HpGoods->getHpGoods] 浩普回调 ".HP_GETGOODS." returndata->".json_encode($return_data)."\n".
                "post_data:".json_encode($params);
            goodsSycnLog($log_str);
            $return_arr = json_decode($return_data['data'], true);
            $this->hpResChecking($return_arr);
            $old_records = F('hpgoodslist_'.$value['store_id']);
            $new_records = $return_arr['records'];
            $info = array();
            if(md5(json_encode($old_records))!==md5(json_encode($new_records))){
                foreach ($new_records as $nkey => $new_value){
                    $oldGoodsBean = array();
                    foreach ($old_records as $okey =>$old_value){
                        if($new_value['nid'] == $old_value['nid']){
                            $oldGoodsBean = $old_value;
                            break;
                        }
                    }
                    if(!empty($oldGoodsBean)){
//                        $result_data = $validate->checkGoodsBean($new_value,$oldGoodsBean);
                        if (md5(json_encode($old_value))!= md5(json_encode($new_value))){
                            $info[] = $new_value;
                        }

                    }else{
                        $info[] = $new_value;
                    }
                }
            }else{
//                var_dump(md5(json_encode($old_records)).'|'.md5(json_encode($new_records)));
                $log_str = "[Dock->HpGoods->getHpGoods] :对比报错";
                goodsSycnLog($log_str);

            }
            F('hpgoodslist_'.$value['store_id'],NULL);
            F('hpgoodslist_'.$value['store_id'],$return_arr['records']);
            if(!empty($info)){
                $records = array();
                $records['records'] = $info;
                $post_data = array();
                $post_data['hp_mark'] = 0;
                $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
                $post_data['fmch_id'] = $value['fmch_id'];
                $post_data['store_id'] = $value['store_id'];
                $post_data['info'] = json_encode($records);
                $xx_url = $this->getXxUrl("Hp", "saveHpGoods");
                $return_data_two = httpRequest($xx_url, "post", $post_data);
                $log_str = "[Dock->HpGoods->getHpGoods]  讯信回调".HP_GETGOODS." returndata->".json_encode($return_data_two)."\n".
                    "post_data:".json_encode($post_data);
                goodsSycnLog($log_str);
            }
          break;
        }
    }

    /**同步商品库存信息(320)
     * URL : /dock.php?c=HpGoods&a=getHpGoodsStock
     * return{
    }
     */
        public function getHpGoodsStock(){

        $storeData = D('StoreMemberBind')->getXXStoreMemberBindInfo();
        $validate = new ValidateController();
        foreach ($storeData as $value){
            $goodInfo = F('hpgoodslist_'.$value['store_id']);

            foreach ($goodInfo as $gkey =>$list){
                $params = array();
                $params['ftask'] = HP_GETGOODS_STOCK;
                $params['fmch_id'] = $value['fmch_id'];
                $params['fsign'] = $value['fsign'];
                $params['fprodid'] = $list['nid'];
                $params['ftimestamp'] = time();
                $headers = array("Content-Type : text/html;charset=UTF-8");
                $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
                $log_str = "[Dock->HpGoods->getHpGoodsStock] 浩普回调 ".HP_GETGOODS_STOCK." returndata->".json_encode($return_data)."\n".
                "post_data:".json_encode($params);
                goodsSycnLog($log_str);
                $return_arr = json_decode($return_data['data'], true);
                $this->hpResChecking($return_arr);
                $old_records = F('hpgoodsstocklist_'.$value['store_id'].'_'.$list['nid']);
                $new_records = $return_arr['records'];
               
                $info = array();
                if(md5(json_encode($old_records))!==md5(json_encode($new_records))){
                foreach ($new_records as $nkey => $new_value){
                        $oldGoodsBean = array();
                        foreach ($old_records as $okey =>$old_value){
                            if($new_value['fprodid'] == $old_value['fprodid']){
                                $oldGoodsBean = $old_value;
                                break;
                            }
                        }
                        if(!empty($oldGoodsBean)){
//                            $result_data = $validate->checkGoodsBean($new_value,$old_value);
                            if (md5(json_encode($oldGoodsBean))!= md5(json_encode($new_value))){
                                $info[] = $new_value;
                            }
//                            if($result_data == true){
//                                $info[] = $new_value;
//                            }
                        }else{
                            $info[] = $new_value;
                        }
                    }
                }
                F('hpgoodsstocklist_'.$value['store_id'].'_'.$list['nid'],NULL);
                F('hpgoodsstocklist_'.$value['store_id'].'_'.$list['nid'],$return_arr['records']);

                if(!empty($info)){
                    $records = array();
                    $records['records'] = $info;
                    $post_data = array();
                    $post_data['hp_mark'] = 0;
                    $post_data['hp_token'] = md5($post_data['hp_mark'] . "vjd8988998");
                    $post_data['fmch_id'] = $value['fmch_id'];
                    $post_data['store_id'] = $value['store_id'];
                    $post_data['info'] = json_encode($records);
                    $xx_url = $this->getXxUrl("Hp", "saveHpGoodsStock");
                    $return_data_two = httpRequest($xx_url, "post", $post_data);
                    $log_str = "[Dock->HpGoods->saveHpGoodsStock] 讯信回调 ".HP_GETGOODS_STOCK." returndata->".json_encode($return_data_two)."\n".
                        "post_data:".json_encode($post_data);
                    goodsSycnLog($log_str);
                }
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
        $post_data['hp_mark'] = 0;
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
            $params['ftask'] = HP_GETGOODS_CLASS;
            $params['fmch_id'] = $this->config[$value['fmch_id']]['fmch_id'];
            $params['fsign'] = $this->config[$value['fmch_id']]['fsign'];
            //$params['nid'] = $value['nid'];
            $params['ftimestamp'] = time();
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
            $return_arr = json_decode($return_data['data'], true);
            $this->hpResChecking($return_arr);
            $post_data = array();
            $post_data['hp_mark'] = 0;
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
                $return_data = httpRequest($this->hp_base_url, "POST", json_encode($params), $headers);
                $return_arr = json_decode($return_data['data'], true);
                $this->hpResChecking($return_arr);
                $post_data = array();
                $post_data['hp_mark'] = 0;
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