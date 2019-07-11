<?php

namespace Common\Common;


use Think\Log;


class CommonOrderApi
{
    /**
     * 获取第三方支付接口
     * @param int $store_id 商家编号（如果为商城，第三方支付绑定的信息为主商家编号）
     * @return  返回第三方支付的信息
     */
    public function check_thirdpart($store_id = '')
    {

        $store = Model('store');

        $thirdpart = '';

        if (!empty($store_id)) {
            //判断是否为商城模式
            $store_info = $store->where(array('store_id' => $store_id))->field('channel_type,channel_id,main_store')->find();

            if (($store_info['channel_type'] == 2) && ($store_info['channel_id'] > 0) && ($store_info['main_store'] != 1)) {

                $w = array();

                $w['channel_id'] = $store_info['channel_id'];

                $w['main_store'] = 1;

                $stores = $store->where($w)->field('store_id')->find();
                $store_id = $stores['store_id'];

            }
            $thirdpart = Model('mb_thirdpart_money')->where(array('store_id' => $store_id, 'status' => 1))->find();

        }

        return $thirdpart;

    }

    /**
     * 获取订单运费信息
     * @param $storeInfoArray
     * @param $latitude
     * @param $longitude
     * @param $addressId
     * User: czx
     * Date:  2018/3/19 11:45:51
     * Update: 2018/3/19 11:45:51
     * Version: 1.00
     */
    public function getOrderFreight(&$storeInfoArray, $latitude, $longitude, $addressId){
        for ($i = 0; $i < count($storeInfoArray); $i++){
            $storeshopdata_one = $storeInfoArray[$i];
            $storeDataOne = M("store")->where(array('store_id' => $storeshopdata_one['store_id']))->find();
            $addressData = array();
            if (!empty($addressId)) $addressData = D("Address")->getAddressInfo($addressId)['data'];
            if (empty($storeInfoOne)) getReturn(-1, "计算运费商家不存在");
            $isLocal = $this->getLocalValue($storeDataOne, $addressData);

            if ($isLocal == 1){  //同城方式计算

               $returnData = $this->getLocalFreight($storeshopdata_one, $storeDataOne, $latitude, $longitude);

                $storeInfoArray[$i]['freight'] = $returnData;
            }else{

                $storeInfoArray[$i]['freight'] = $this->getLongRangeFreight($storeshopdata_one, $storeDataOne, $addressData, $latitude, $longitude);
            }

        }
    }

    public function getPickFreight(&$storeInfoArray, $latitude, $longitude, $addressData){
        for ($i = 0; $i < count($storeInfoArray); $i++){
            $storeshopdata_one = $storeInfoArray[$i];
            $addressData = null;
            foreach ($storeshopdata_one['pickUpData'] as $key => $value){
               if ($value['is_select'] == 1){
                   $addressData = $value;
               }
            }
            if ($addressData == null || $addressData['freight'] == 0){
                $returnData = array();
                $returnData['freight'] = 0;
                $returnData['canBuy'] = 1;
                $returnData['sendmoney'] = 0;
                $storeInfoArray[$i]['freight'] = $returnData;
                continue;
            }
            $storeDataOne = M("store")->where(array('store_id' => $storeshopdata_one['store_id']))->find();
            if (empty($storeInfoOne)) getReturn(-1, "计算运费商家不存在");
            $isLocal = $this->getLocalValue($storeDataOne, $addressData);

            if ($isLocal == 1){  //同城方式计算

                $returnData = $this->getLocalFreight($storeshopdata_one, $storeDataOne, $latitude, $longitude);

                $storeInfoArray[$i]['freight'] = $returnData;
            }else{

                $storeInfoArray[$i]['freight'] = $this->getLongRangeFreight($storeshopdata_one, $storeDataOne, $addressData, $latitude, $longitude);
            }

        }
    }

    /**
     * 判断是否为同城
     * @param $storeData
     * @param $addressData
     * @return int
     * User: czx
     * Date: 2018/3/19 11:45:51
     * Update: 2018/3/19 11:45:51
     * Version: 1.00
     */
    public function getLocalValue($storeData, $addressData){
        $isLocal = 0;
        if (empty($storeData['province_id']) || empty($storeData['city_id'])
            || empty($storeData['area_id']) || empty($addressData['city_id'])){
            $isLocal = 1;
            return $isLocal;
        }
        if ($storeData['city_id'] == $addressData['city_id']){
            $isLocal = 1;
            return $isLocal;
        }
        return $isLocal;
    }

    /**
     * 获取同城的运费信息
     * @param $storeOrderData
     * @param $storeData
     * @param $latitude
     * @param $longitude
     * @return array
     * User: czx
     * Date: 2018/3/19 11:45:51
     * Update: 2018/3/19 11:45:51
     * Version: 1.00
     */
    public function getLocalFreight($storeOrderData, $storeData, $latitude, $longitude){
        $orderTotalPrice = 0;

        foreach ($storeOrderData['order_content'] as $value){
            if (empty($value['buy_num'])){
                $value['buy_num'] = $value['gou_num'];
            }
            $orderTotalPrice += $value['goods_price'] * $value['buy_num'];
        }
        $returnData = array();
        $returnData['freight'] = 0;
        $returnData['canBuy'] = 0;
        $returnData['sendmoney'] = 0;
        $sendmoney = $this->getSendMoney($storeData, $latitude, $longitude);
        if ($storeData['postage_tag'] == 1){
            $returnData['canBuy'] = 1;
            if ($orderTotalPrice < $sendmoney) $returnData['freight'] = $storeData['postage'];
            $returnData['sendmoney'] = $sendmoney;
        }else{
            if ($orderTotalPrice <= $sendmoney){
                $returnData['freight'] = 0;
            }else{
                $returnData['canBuy'] = 1;
                $returnData['freight'] = 0;
            }
            $returnData['sendmoney'] = $sendmoney;
        }
        return $returnData;
    }

    /**
     * 获取起送价
     * @param $storeData
     * @param $latitude
     * @param $longitude
     * @return int
     * User: czx
     * Date: 2018/3/19 11:45:51
     * Update: 2018/3/19 11:45:51
     * Version: 1.00
     */
    public function  getSendMoney($storeData, $latitude, $longitude){
        $sendmoney = 0;
        if (!empty($storeData["distance_info"])) {
            $distanceinfo = json_decode($storeData["distance_info"], true);
            if (!empty($distanceinfo) && count($distanceinfo) > 0) {
                $lat1 = $latitude;
                $lng1 = $longitude;
                $lng2 = $storeData['longitude'];
                $lat2 = $storeData['latitude'];

                $distance = getDistance($lat1, $lng1, $lat2, $lng2);
                $money1 = 0;
                $money2 = 0;
                $money3 = 0;
                $money4 = 0;

                for ($i = 0; $i < count($distanceinfo); $i++) {
                    if ($distanceinfo[$i]['distance'] == 300) {
                        $money1 = $distanceinfo[$i]['money'];
                    } else if ($distanceinfo[$i]['distance'] == 500) {
                        $money2 = $distanceinfo[$i]['money'];
                    } else if ($distanceinfo[$i]['distance'] == 1000 && $distanceinfo[$i]['ismore'] == 0) {
                        $money3 = $distanceinfo[$i]['money'];
                    } else {
                        $money4 = $distanceinfo[$i]['money'];
                    }
                }

                if ($distance <= 300) {
                    $sendmoney = $money1;
                } else if ($distance <= 500) {
                    $sendmoney = $money2;
                } else if ($distance <= 1000) {
                    $sendmoney = $money3;
                } else {
                    $sendmoney = $money4;
                }
            }
        }

        return $sendmoney;
    }

    /**
     * 获取非同城的运费信息
     * @param $storeOrderData
     * @param $storeData
     * @param $addressData
     * @param $latitude
     * @param $longitude
     * @return array
     * User: czx
     * Date: 2018/3/19 11:45:51
     * Update: 2018/3/19 11:45:51
     * Version: 1.00
     */
    public function getLongRangeFreight($storeOrderData, $storeData, $addressData, $latitude, $longitude){
        $returnData = array();
        $returnData['freight'] = 0;
        $returnData['canBuy'] = 0;
        $returnData['sendmoney'] = 0;

         //一:商品按运费模板区分 (同城和区域模板划分 并计算划分后各个运费)
         $goodsFreigthArray = array();
         $goodsFreigthArray[] = array('id' => 0,'goodsPrice' => 0, 'goodsNum' => 0, 'freight' => 0);
        foreach($storeOrderData['order_content'] as $value){
            if (empty($value['buy_num'])){
                $value['buy_num'] = $value['gou_num'];
            }

             if ($value['freight_type'] == 1 && $value['freight_tpl_id'] > 0){  //按运费模板
                 $freightId = $this->getFreightId($value['freight_tpl_id'], $addressData);
                 if (empty($freightId)) continue;
                 $tag = 0;

                 foreach ($goodsFreigthArray as $key2 => $value2){
                     if ($value2['id'] == $freightId){
                         $goodsFreigthArray[$key2]['goodsPrice'] += $value['goods_price'] * $value['buy_num'];
                         $goodsFreigthArray[$key2]['goodsNum'] += $value['buy_num'];
                         $tag = 1;
                     }
                 }
                 if ($tag == 0){
                     $goodsFreigthArray[] = array('id' => $freightId,
                         'goodsPrice' => ($value['goods_price'] * $value['buy_num']),
                         'goodsNum' => $value['buy_num'],
                         'freight' => 0);
                 }

             }else{
                 $goodsFreigthArray[0]['goodsPrice'] += $value['goods_price'] * $value['buy_num'];
                 $goodsFreigthArray[0]['goodsNum'] += $value['buy_num'];
             }
        }

        foreach ($goodsFreigthArray as $key => $value){
            if ($key == 0){
                if ($value['goodsNum'] > 0){
                    $sendmoney = $this->getSendMoney($storeData, $latitude, $longitude);
                    if ($storeData['postage_tag'] == 1){
                        $returnData['canBuy'] = 1;
                        if ($value['goodsPrice'] < $sendmoney) $goodsFreigthArray[$key]['freight'] = $storeData['postage'];
                        $returnData['sendmoney'] = $sendmoney;
                    }else{
                        $returnData['canBuy'] = 0;
                        if ($value['goodsPrice'] < $sendmoney){
                            $returnData['freight'] = 0;
                        }else{
                            $returnData['canBuy'] = 1;
                            $returnData['freight'] = 0;
                        }
                        $returnData['sendmoney'] = $sendmoney;

                    }
                }else{
                    $returnData['canBuy'] = 1;
                    $returnData['freight'] = 0;
                }
            }else{
                $freightData = M("mb_freight_tpl_mode")->where(array('id' => $value['id']))->find();
                if (empty($freightData)) continue;
                $goodsFreigthArray[$key]['freight'] = $freightData['first_amount'];
                if (($goodsFreigthArray[$key]['goodsNum'] - $freightData['first_piece']) > 0){
                    // hjun 2018-06-08 10:47:53 如果设置了0元 这里做除法会报错 所以改一下
                    if (!empty($freightData['second_amount']) && !empty($freightData['second_piece'])){
                        $goodsFreigthArray[$key]['freight'] += ceil(($goodsFreigthArray[$key]['goodsNum'] -
                                    $freightData['first_piece']) / $freightData['second_piece']) * $freightData['second_amount'];
                    }
                }

            }
        }
         //二:按累计计算或最大值计算
        if ($storeData['freight_mode'] == 0){
            foreach ($goodsFreigthArray as $key => $value){
                $returnData['freight'] += $value['freight'];
            }
        }else{
            $max_freight = 0;
            foreach ($goodsFreigthArray as $key => $value){
                if ($value['freight'] > $max_freight){
                    $max_freight = $value['freight'];
                }
            }
            $returnData['freight'] = $max_freight;
        }
        return $returnData;

    }

    /**
     * 获取运费模板id
     * @param $freight_tpl_id
     * @param $addressData
     * @return int
     * User: czx
     * Date: 2018/3/19 11:45:51
     * Update: 2018/3/19 11:45:51
     * Version: 1.00
     */
    public function getFreightId($freight_tpl_id, $addressData){

        $freightTplData = M("mb_freight_tpl_mode")->where(array('tpl_id' => $freight_tpl_id))->select();

        foreach ($freightTplData as $key => $value){
            $freightTplData[$key]['regionArray'] = explode("@", $value['region']);
        }

        $freightId = $this->checkFreightExit($addressData['area_id'], $freightTplData);
        if (!empty($freightId)){
            return $freightId;
        }

        $freightId = $this->checkFreightExit($addressData['city_id'], $freightTplData);
        if (!empty($freightId)){
            return $freightId;
        }

        $freightId = $this->checkFreightExit($addressData['province_id'], $freightTplData);
        if (!empty($freightId)){
            return $freightId;
        }
        foreach ($freightTplData as $key => $value){
            if ($value['is_default'] == 1){
                $freightId = $value['id'];
               break;
            }
        }
         return $freightId;
    }

    /**
     * 获取某个区域运费模板id
     * @param $areaNum
     * @param $freightTplData
     * @return int
     * User: czx
     * Date: 2018/3/19 11:45:51
     * Update: 2018/3/19 11:45:51
     * Version: 1.00
     */
    public function checkFreightExit($areaNum, $freightTplData){
        $freightId = 0;
        foreach ($freightTplData as $key => $value){
            if (in_array($areaNum, $value['regionArray'])){
                $freightId = $value['id'];
                return $freightId;
            }
        }
        return $freightId;
    }

    /**
     * 获取支付方式
     * @param string $client
     * @param int $storeId
     * @return array
     * User: czx
     * Date: 2018/3/19 11:45:51
     * Update: 2018/3/19 11:45:51
     * Version: 1.00
     */
    public function getPayMode($client = 'app', $storeId = 0, $app_name, $is_pickup = 0)
    {

        //类型：wechat-微信商城 ,web-移动商城 pc-PC商城 app-APP商城
        if ($client == 'wap' || $client == 'mini') $client = 'wechat';
        if ($client == 'web') $client = 'web';
        if ($client == 'android') $client = 'app';
        if ($client == 'ios') $client = 'app';
        if ($client == 'pc') $client = 'pc';
        $device = $client;
        if (empty($app_name)) {
            $app_name = 'xunxincnt';
        }
        $pay_mode = array();
        $pay_mode['wxpay'] = 0;
        $pay_mode['alipay'] = 0;
        $pay_mode['cashpay'] = 0;
        $store_data = Model('store')->field('channel_id')->where(array('store_id' => $storeId))->find();
        $channel_id = $store_data['channel_id'];
        if ($channel_id == 0) { // 0渠道默认都允许系统代收,也没有商城和子店收款的情况
            $pay_switch_data = Model('mb_pay_switch')->field('payment_type,online_switch,offline_switch,online_pickup_switch,offline_pickup_switch')
                ->where(array('store_id' => $storeId, 'device' => $device))->find();
            if (!empty($pay_switch_data)) {
                if ($pay_switch_data['offline_switch'] == 1) {// 线下支付开
                    $pay_mode['cashpay'] = 1;
                }
                //add by czx
                if ($is_pickup == 1){
                    if ($pay_switch_data['offline_pickup_switch'] == 1) {// 线下支付开
                        $pay_mode['cashpay'] = 1;
                    }else{
                        $pay_mode['cashpay'] = 0;
                    }
                }
                if ($pay_switch_data['online_switch'] == 1) {// 在线支付开
                    if ($pay_switch_data['payment_type'] == 1) {// 系统代收
                        $payconfig = Model('mb_pay_config')->field('type,status')
                            ->where(array('store_id' => -1, 'channel_id' => 0, 'device' => $device, 'app_name' => $app_name))
                            ->select();
                        if (!empty($payconfig)) {
                            foreach ($payconfig as $value) {
                                if ($value['type'] == 'wx' && $value['status'] == 1) {
                                    $pay_mode['wxpay'] = 1;
                                }
                                if ($value['type'] == 'ali' && $value['status'] == 1) {
                                    $pay_mode['alipay'] = 1;
                                }
                            }
                        }
                    } else {// 自己收款
                        $payconfig = Model('mb_pay_config')->field('type,status')
                            ->where(array('store_id' => $storeId, 'channel_id' => 0, 'device' => $device, 'app_name' => $app_name))
                            ->select();
                        if (!empty($payconfig)) {
                            foreach ($payconfig as $value) {
                                if ($value['type'] == 'wx' && $value['status'] == 1) {
                                    $pay_mode['wxpay'] = 1;
                                }
                                if ($value['type'] == 'ali' && $value['status'] == 1) {
                                    $pay_mode['alipay'] = 1;
                                }
                            }
                        }
                    }
                }
                // add by czx
                if ($is_pickup == 1){
                    if ($pay_switch_data['online_pickup_switch'] == 1) {// 在线支付开
                        if ($pay_switch_data['payment_type'] == 1) {// 系统代收
                            $payconfig = Model('mb_pay_config')->field('type,status')
                                ->where(array('store_id' => -1, 'channel_id' => 0, 'device' => $device, 'app_name' => $app_name))
                                ->select();
                            if (!empty($payconfig)) {
                                foreach ($payconfig as $value) {
                                    if ($value['type'] == 'wx' && $value['status'] == 1) {
                                        $pay_mode['wxpay'] = 1;
                                    }
                                    if ($value['type'] == 'ali' && $value['status'] == 1) {
                                        $pay_mode['alipay'] = 1;
                                    }
                                }
                            }
                        } else {// 自己收款
                            $payconfig = Model('mb_pay_config')->field('type,status')
                                ->where(array('store_id' => $storeId, 'channel_id' => 0, 'device' => $device, 'app_name' => $app_name))
                                ->select();
                            if (!empty($payconfig)) {
                                foreach ($payconfig as $value) {
                                    if ($value['type'] == 'wx' && $value['status'] == 1) {
                                        $pay_mode['wxpay'] = 1;
                                    }
                                    if ($value['type'] == 'ali' && $value['status'] == 1) {
                                        $pay_mode['alipay'] = 1;
                                    }
                                }
                            }
                        }
                    }else{
                        $pay_mode['wxpay'] = 0;
                        $pay_mode['alipay'] = 0;
                    }
                }

            }
        } else {// 非0渠道,需要判断是否允许系统代收,是否允许子店自己配置收款
            $channel_data = Model('mb_channel')
                ->field('store_type,system_replay_receipt_switch,sub_shop_receipt_switch')
                ->where(array('channel_id' => $channel_id))->find();
            if ($channel_data['sub_shop_receipt_switch'] == 1) { // 允许子店自己配置
                $pay_switch_data = Model('mb_pay_switch')
                    ->field('payment_type,online_switch,offline_switch,online_pickup_switch,offline_pickup_switch')
                    ->where(array('store_id' => $storeId, 'device' => $device))->find();
                if (!empty($pay_switch_data)) { // 子店有去配置
                    if ($pay_switch_data['payment_type'] == 1 && $channel_data['system_replay_receipt_switch'] == 1) { // 开启系统代收,且渠道允许代收
                        $payconfig = Model('mb_pay_config')->field('type,status')
                            ->where(array('store_id' => -1, 'channel_id' => 0, 'device' => $device, 'app_name' => $app_name))
                            ->select();
                        if (!empty($payconfig) && $pay_switch_data['online_switch'] == 1) {
                            foreach ($payconfig as $value) {
                                if ($value['type'] == 'wx' && $value['status'] == 1) {
                                    $pay_mode['wxpay'] = 1;
                                }
                                if ($value['type'] == 'ali' && $value['status'] == 1) {
                                    $pay_mode['alipay'] = 1;
                                }
                            }
                        }
                        if ($pay_switch_data['offline_switch'] == 1) {// 线下支付开
                            $pay_mode['cashpay'] = 1;
                        }

                        //add by czx
                        if ($is_pickup == 1){
                            if (!empty($payconfig) && $pay_switch_data['online_pickup_switch'] == 1) {
                                foreach ($payconfig as $value) {
                                    if ($value['type'] == 'wx' && $value['status'] == 1) {
                                        $pay_mode['wxpay'] = 1;
                                    }
                                    if ($value['type'] == 'ali' && $value['status'] == 1) {
                                        $pay_mode['alipay'] = 1;
                                    }
                                }
                            }else{
                                $pay_mode['wxpay'] = 0;
                                $pay_mode['alipay'] = 0;
                            }
                            if ($pay_switch_data['offline_pickup_switch'] == 1) {// 线下支付开
                                $pay_mode['cashpay'] = 1;
                            }else{
                                $pay_mode['cashpay'] = 0;
                            }
                        }

                        return $pay_mode;
                    } else {

                        if ($pay_switch_data['online_switch'] == 1) {// 在线支付开
                            $payconfig = Model('mb_pay_config')->field('type,status')
                                ->where(array('store_id' => $storeId, 'device' => $device, 'app_name' => $app_name))
                                ->select();
                            if (!empty($payconfig)) {
                                foreach ($payconfig as $value) {
                                    if ($value['type'] == 'wx' && $value['status'] == 1) {
                                        $pay_mode['wxpay'] = 1;
                                    }
                                    if ($value['type'] == 'ali' && $value['status'] == 1) {
                                        $pay_mode['alipay'] = 1;
                                    }
                                }
                            }
                        }
                        if ($pay_switch_data['offline_switch'] == 1) {// 线下支付开
                            $pay_mode['cashpay'] = 1;
                        }

                        //add by czx
                        if ($is_pickup == 1){
                            if ($pay_switch_data['online_pickup_switch'] == 1) {// 在线支付开
                                $payconfig = Model('mb_pay_config')->field('type,status')
                                    ->where(array('store_id' => $storeId, 'device' => $device, 'app_name' => $app_name))
                                    ->select();
                                if (!empty($payconfig)) {
                                    foreach ($payconfig as $value) {
                                        if ($value['type'] == 'wx' && $value['status'] == 1) {
                                            $pay_mode['wxpay'] = 1;
                                        }
                                        if ($value['type'] == 'ali' && $value['status'] == 1) {
                                            $pay_mode['alipay'] = 1;
                                        }
                                    }
                                }
                            }else{
                                $pay_mode['wxpay'] = 0;
                                $pay_mode['alipay'] = 0;
                            }
                            if ($pay_switch_data['offline_pickup_switch'] == 1) {// 线下支付开
                                $pay_mode['cashpay'] = 1;
                            }else{
                                $pay_mode['cashpay'] = 0;
                            }
                        }
                    }
                    return $pay_mode;
                }
            }

            // 如果不允许子店自己配置或允许子店自己配置但是子店没有去配置,会来到这里,直接获取主店ID,跟随主店配置
            if ($channel_data['store_type'] != 3) { // 如果不是独立店,才需要查询主店ID
                $store_data = Model('store')->field('store_id')
                    ->where(array('channel_id' => $channel_id, 'main_store' => 1))->find();
                $store_id = $store_data['store_id'];
            } else {
                $store_id = $storeId;
            }

            $pay_switch_data = Model('mb_pay_switch')->field('payment_type,online_switch,offline_switch,online_pickup_switch,offline_pickup_switch')
                ->where(array('store_id' => $store_id, 'device' => $device))->find();
            if (!empty($pay_switch_data)) { // 主店有配置
                if ($pay_switch_data['payment_type'] == 1 && $channel_data['system_replay_receipt_switch'] == 1) { // 开启系统代收,且渠道允许代收
                    $payconfig = Model('mb_pay_config')->field('type,status')
                        ->where(array('store_id' => -1, 'channel_id' => 0, 'device' => $device, 'app_name' => $app_name))
                        ->select();
                    if (!empty($payconfig) && $pay_switch_data['online_switch'] == 1) {
                        foreach ($payconfig as $value) {
                            if ($value['type'] == 'wx' && $value['status'] == 1) {
                                $pay_mode['wxpay'] = 1;
                            }
                            if ($value['type'] == 'ali' && $value['status'] == 1) {
                                $pay_mode['alipay'] = 1;
                            }
                        }

                    }
                    if ($pay_switch_data['offline_switch'] == 1) {// 线下支付开
                        $pay_mode['cashpay'] = 1;
                    }
                    // add by czx
                    if ($is_pickup == 1){
                        if (!empty($payconfig) && $pay_switch_data['online_pickup_switch'] == 1) {
                            foreach ($payconfig as $value) {
                                if ($value['type'] == 'wx' && $value['status'] == 1) {
                                    $pay_mode['wxpay'] = 1;
                                }
                                if ($value['type'] == 'ali' && $value['status'] == 1) {
                                    $pay_mode['alipay'] = 1;
                                }
                            }

                        }else{
                                $pay_mode['wxpay'] = 0;
                                $pay_mode['alipay'] = 0;
                        }
                        if ($pay_switch_data['offline_pickup_switch'] == 1) {// 线下支付开
                            $pay_mode['cashpay'] = 1;
                        }else{
                            $pay_mode['cashpay'] = 0;
                        }
                    }
                    return $pay_mode;
                } else {
                    if ($pay_switch_data['online_switch'] == 1) {// 在线支付开
                        $payconfig = Model('mb_pay_config')->field('type,status')
                            ->where(array('store_id' => $store_id, 'device' => $device, 'app_name' => $app_name))
                            ->select();
                        if (!empty($payconfig)) {
                            foreach ($payconfig as $value) {
                                if ($value['type'] == 'wx' && $value['status'] == 1) {
                                    $pay_mode['wxpay'] = 1;
                                }
                                if ($value['type'] == 'ali' && $value['status'] == 1) {
                                    $pay_mode['alipay'] = 1;
                                }
                            }
                        }
                    }
                    if ($pay_switch_data['offline_switch'] == 1) {// 线下支付开
                        $pay_mode['cashpay'] = 1;
                    }
                    // add by czx
                    if ($is_pickup == 1){
                        if ($pay_switch_data['online_pickup_switch'] == 1) {// 在线支付开
                            $payconfig = Model('mb_pay_config')->field('type,status')
                                ->where(array('store_id' => $store_id, 'device' => $device, 'app_name' => $app_name))
                                ->select();
                            if (!empty($payconfig)) {
                                foreach ($payconfig as $value) {
                                    if ($value['type'] == 'wx' && $value['status'] == 1) {
                                        $pay_mode['wxpay'] = 1;
                                    }
                                    if ($value['type'] == 'ali' && $value['status'] == 1) {
                                        $pay_mode['alipay'] = 1;
                                    }
                                }
                            }
                        }else{
                            $pay_mode['wxpay'] = 0;
                            $pay_mode['alipay'] = 0;
                        }
                        if ($pay_switch_data['offline_pickup_switch'] == 1) {// 线下支付开
                            $pay_mode['cashpay'] = 1;
                        }else{
                            $pay_mode['cashpay'] = 0;
                        }
                    }
                }
                return $pay_mode;
            }
        }
        return $pay_mode;
    }

    /**
     * 获取优惠券限制使用的名称
     * @param array $info
     * @return string
     * User: czx
     * Date: 2018/3/20 16:48:2
     * Update: 2018/3/20 16:48:2
     * Version: 1.00
     */
    public function getCouponsLimitClassName($info = [])
    {

        $limitClassName = '全品类可用';
        if (isset($info['limit_class_type'])) {
            switch ((int)$info['limit_class_type']) {
                case 2:
                    $limitClass = json_decode($info['limit_class'], 1);
                    $length = count($limitClass);
                    $className = $limitClass[$length - 1]['classStr'];
                    $limitClassName = "不可用分类:".$className;
                    break;
                case 3:
                    if (isset($info['limit_mall_class_name'])) {
                        $limitClassName = "不可用商城分类:".$info['limit_mall_class_name'];
                    }
                    break;
                case 4:
                    if (isset($info['limit_goods_name'])) {
                        $limitClassName = "指定商品可用:".$info['limit_goods_name'];
                    }
                    break;
                default:
                    break;
            }
        }
        return $limitClassName;
    }

    /**
     * 检查订单是否已经存在
     * @param $order_sn
     * @return array
     * User: czx
     * Date: 2018/3/20 20:34:47
     * Update: 2018/3/20 20:34:47
     * Version: 1.00
     *
     */
    public function checkOrderExit($order_sn, $timeCheck = 1){
        if (!empty($order_sn)){
            $where = array();
            $where['order_sn'] = $order_sn;
            $returndata = Model("mb_order_only")->where($where)->find();
            if (!empty($returndata)){
                if(!empty($returndata['return_data'])){
                    return getReturn(200, "已经存在", json_decode($returndata['return_data']));
                }else{
                    if ($timeCheck < 5){
                         sleep(2);
                         return $this->checkOrderExit($order_sn, ++$timeCheck);
                    }else{
                        return getReturn(-1, "订单正在提交中,请勿返回,5秒后再次提交");
                    }
                }
            }
        }
        return getReturn(200, "可下单", array());
    }

    /**
     * 检查订单是否已经存在
     * @param $order_sn
     * @return array
     * User: czx
     * Date: 2018/3/20 20:34:47
     * Update: 2018/3/20 20:34:47
     * Version: 1.00
     */
    public function checkCashOrderExit($order_sn, $timeCheck = 1){
        if (!empty($order_sn)){
            $where = array();
            $where['order_sn'] = $order_sn;
            $returndata = Model("mb_cashorder_only")->where($where)->find();
            if (!empty($returndata)){
                if(!empty($returndata['return_data'])){
                    return getReturn(200, "已经存在", json_decode($returndata['return_data']));
                }else{
                    if ($timeCheck < 5){
                        sleep(2);
                        return $this->checkOrderExit($order_sn, ++$timeCheck);
                    }else{
                        return getReturn(-1, "订单正在提交中,请勿返回,5秒后再次提交");
                    }
                }
            }
        }
        return getReturn(200, "可下单", array());
    }

    /**
     * 判断数组中某个值最后的位置
     * @param $checkArray
     * @param $keyName
     * @return int
     * User: czx
     * Date: 2018/3/20 20:34:47
     * Update: 2018/3/20 20:34:47
     * Version: 1.00
     */
    public function getLastPosition($checkArray, $keyName){

        for ($i = count($checkArray) - 1; $i >=0; $i--){
            if ($checkArray[$i][$keyName] > 0){
                return $i;
            }
        }
        return 0;
    }

    public function getScaleValue($oneValue, $allValue, $spendValue, $gouNum){
        return round(($oneValue / $allValue * $spendValue), 4);
    }

    /**
     * @param $goods_bean
     * @param int $type '':全部扣除  "TP":不扣除第三方余额 "PC":不扣除平台积分 "PB":不扣除平台余额 "SC":不扣除店铺积分分  "SB":不扣除余额
     * @return mixed
     * User: czx
     * Date:
     * Update:
     * Version: 1.00
     */
    public function  getRestPrice($goods_bean, $type = 0){
        if (empty($goods_bean['store_discount_price'])) $goods_bean['store_discount_price'] = 0;
        if (empty($goods_bean['store_reduce_price'])) $goods_bean['store_reduce_price'] = 0;
        if (empty($goods_bean['mj_bean_price'])) $goods_bean['mj_bean_price'] = 0;
        if (empty($goods_bean['coupons_exmoney'])) $goods_bean['coupons_exmoney'] = 0;
        if (empty($goods_bean['thirdpart_money'])) $goods_bean['thirdpart_money'] = 0;
        if (empty($goods_bean['platform_credits_exmoney'])) $goods_bean['platform_credits_exmoney'] = 0;
        if (empty($goods_bean['platform_balance'])) $goods_bean['platform_balance'] = 0;
        if (empty($goods_bean['platform_coupons_money'])) $goods_bean['platform_coupons_money'] = 0;
        if (empty($goods_bean['credits_exmoney'])) $goods_bean['credits_exmoney'] = 0;
        if (empty($goods_bean['balance'])) $goods_bean['balance'] = 0;
        $mj_bean_price = round(($goods_bean['mj_bean_price'] / $goods_bean['gou_num']), 4);
        $coupons_exmoney = round(($goods_bean['coupons_exmoney'] / $goods_bean['gou_num']), 4);

        $thirdpart_money = round(($goods_bean['thirdpart_money'] / $goods_bean['gou_num']), 4);
        if ($type == "TP"){
            $thirdpart_money = 0;
        }

        $platform_credits_exmoney =  round(($goods_bean['platform_credits_exmoney'] / $goods_bean['gou_num']), 4);
        if ($type == "PC"){
            $platform_credits_exmoney = 0;
        }

        $platform_balance =  round(($goods_bean['platform_balance'] / $goods_bean['gou_num']), 4);
        if ($type == "PB"){
            $platform_balance = 0;
        }

        $platform_coupons_money =  round(($goods_bean['platform_coupons_money'] / $goods_bean['gou_num']), 4);

        $credits_exmoney =  round(($goods_bean['credits_exmoney'] / $goods_bean['gou_num']), 4);
        if ($type == "SC"){
            $credits_exmoney = 0;
        }

        $balance =  round(($goods_bean['balance'] / $goods_bean['gou_num']), 4);
        if ($type == "SB"){
            $balance = 0;
        }

        $store_discount_price = round(($goods_bean['store_discount_price'] /$goods_bean['gou_num']), 4);

        $store_reduce_price = round(($goods_bean['store_reduce_price'] /$goods_bean['gou_num']), 4);

        return $goods_bean['goods_price']  - $coupons_exmoney
            - $thirdpart_money - $platform_credits_exmoney
            - $platform_balance
            - $platform_coupons_money - $credits_exmoney - $balance - $mj_bean_price
            - $store_discount_price - $store_reduce_price;
    }



    public function  getGoodsRestPrice($goods_bean){
        if (empty($goods_bean['mj_bean_price'])) $goods_bean['mj_bean_price'] = 0;
        if (empty($goods_bean['coupons_exmoney'])) $goods_bean['coupons_exmoney'] = 0;
        if (empty($goods_bean['thirdpart_money'])) $goods_bean['thirdpart_money'] = 0;
        if (empty($goods_bean['platform_credits_exmoney'])) $goods_bean['platform_credits_exmoney'] = 0;
        if (empty($goods_bean['platform_balance'])) $goods_bean['platform_balance'] = 0;
        if (empty($goods_bean['platform_coupons_money'])) $goods_bean['platform_coupons_money'] = 0;
        if (empty($goods_bean['credits_exmoney'])) $goods_bean['credits_exmoney'] = 0;
        if (empty($goods_bean['balance'])) $goods_bean['balance'] = 0;
        $exchangeMoney = round(($goods_bean['coupons_exmoney']
            + $goods_bean['thirdpart_money'] +  $goods_bean['platform_credits_exmoney']
            +  $goods_bean['platform_balance'] +  $goods_bean['platform_coupons_money']
            +  $goods_bean['credits_exmoney'] +  $goods_bean['balance'] +  $goods_bean['mj_bean_price']), 4);
        return round(($goods_bean['goods_price'] * $goods_bean['gou_num']  - $exchangeMoney), 4);
    }

    public function  getCashGoodsRestPrice($goods_bean){
        if (empty($goods_bean['store_discount_price'])) $goods_bean['store_discount_price'] = 0;
        if (empty($goods_bean['store_reduce_price'])) $goods_bean['store_reduce_price'] = 0;
        if (empty($goods_bean['mj_bean_price'])) $goods_bean['mj_bean_price'] = 0;
        if (empty($goods_bean['coupons_exmoney'])) $goods_bean['coupons_exmoney'] = 0;
        if (empty($goods_bean['thirdpart_money'])) $goods_bean['thirdpart_money'] = 0;
        if (empty($goods_bean['platform_credits_exmoney'])) $goods_bean['platform_credits_exmoney'] = 0;
        if (empty($goods_bean['platform_balance'])) $goods_bean['platform_balance'] = 0;
        if (empty($goods_bean['platform_coupons_money'])) $goods_bean['platform_coupons_money'] = 0;
        if (empty($goods_bean['credits_exmoney'])) $goods_bean['credits_exmoney'] = 0;
        if (empty($goods_bean['balance'])) $goods_bean['balance'] = 0;
        $exchangeMoney = round(($goods_bean['coupons_exmoney']
            + $goods_bean['thirdpart_money'] +  $goods_bean['platform_credits_exmoney']
            +  $goods_bean['platform_balance'] +  $goods_bean['platform_coupons_money']
            +  $goods_bean['credits_exmoney'] +  $goods_bean['balance'] +  $goods_bean['mj_bean_price']
            + $goods_bean['store_discount_price'] + $goods_bean['store_reduce_price']), 4);
        return round(($goods_bean['goods_price'] * $goods_bean['gou_num']  - $exchangeMoney), 4);
    }

    public function sendMessage($params = array())
    {
        $send_message_url = SEND_MESSAGE_URL . "index.php?c=SendMessage&a=sendWxMsg";
        $result_data =httpRequest($send_message_url, "post", $params);
        $log_data = "[postCurl]result_data:" . json_encode($result_data);
        Log::record($log_data, Log::ERR);
    }


    //余额操作
    public function changeBalance($sid, $mid, $tid, $money, $type_name, $ps, $orderid, $member_name)
    {

        if (abs($money) < 0.01) {
            return getReturn(200, "成功", array());
        }
        $mb_balancerecord = Model("mb_balancerecord");
        $max_version = $mb_balancerecord->max('version');
        $data = array(
            'sid' => $sid,
            'mid' => $mid,
            'tid' => $tid,
            'money' => $money,
            'type_name' => $type_name,
            'ps' => $ps,
            'orderid' => $orderid,
            'member_name' => $member_name,
            'version' => $max_version + 1,
            'create_time' => TIMESTAMP
        );


        $member = Model('mb_storemember');
        $sumscores = $member->field('balance')->where(array(
            'member_id' => $mid,
            'store_id' => $sid
        ))->find();
        $sum_balance = $sumscores['balance'] + $money;

        if ($sum_balance < 0) {
            return getReturn(-2, "店铺余额不足,请充值");
        }
        $mversion = $member->max('version');
        $flag = $member->where(array(
            'member_id' => $mid,
            'store_id' => $sid
        ))->save(array(
            'balance' => $sum_balance,
            'version' => $mversion + 1
        ));
        if ($flag == false) {
            return getReturn(-3, "增减店铺余额失败");
        }

        $id = $mb_balancerecord->add($data);
        if ($id == false) {
            return getReturn(-3, "添加余额记录失败");
        }
        $data['id'] = $id;
        $data['balance'] = $sum_balance;
        return getReturn(200, "成功", $data);
    }

    //平台余额操作
    public function changePlatformBalance($sid, $mid, $tid, $money, $type_name, $ps, $orderid, $member_name, $pay_type = 0, $pay_name = '', $pay_id = '')
    {

        if (abs($money) < 0.01) {
            return getReturn(200, "成功", array());
        }

        $mb_balancerecord = Model("mb_balancerecord");

        $max_version = $mb_balancerecord->max('version');

        $storedata = Model('store')->field('channel_id')->where(array(
            'store_id ' => $sid
        ))->find();
        $mainstore_data = Model('store')->where(array(
            'channel_id' => $storedata['channel_id'],
            'main_store' => 1
        ))->find();

        if (empty($mainstore_data)) {
            return getReturn(-1, "更改平台余额失败，暂未找到平台主商家", array());
        }
        $sid = $mainstore_data['store_id'];
        $data = array(
            'sid' => $sid,
            'mid' => $mid,
            'tid' => $tid,
            'money' => $money,
            'type_name' => $type_name,
            'ps' => $ps,
            'orderid' => $orderid,
            'member_name' => $member_name,
            'version' => $max_version + 1,
            'create_time' => TIMESTAMP,
            'pay_type' => $pay_type,
            'pay_name' => $pay_name,
            'pay_id' => $pay_id
        );

        $member = Model('mb_storemember');
        $sumscores = $member->field('platform_balance')->where(array(
            'member_id' => $mid,
            'store_id' => $sid
        ))->find();
        $sum_balance = $sumscores['platform_balance'] + $money;

        if ($sum_balance < 0) {
            return getReturn(-2, "平台余额不足,请充值", array());
        }
        $mversion = $member->max('version');
        $flag = $member->where(array(
            'member_id' => $mid,
            'store_id' => $sid
        ))->save(array(
            'platform_balance' => $sum_balance,
            'version' => $mversion + 1
        ));

        if ($flag === false) {
            return getReturn(-3, "修改平台余额失败", array());
        }

        $id = $mb_balancerecord->add($data);
        if ($id === false) {
            return getReturn(-3, "添加平台余额记录失败", array());
        }
        $data['id'] = $id;
        $data['platform_balance'] = $sum_balance;
        return getReturn(200, "成功", $data);
    }



    public function  createOrderChangeCoupons($storeorder, $order_id){
        $coupons_info = $storeorder['gou_info'];
        $max_version = Model('mb_membercoupons')->max('version');
        $returnData = Model('mb_membercoupons')->where(array(
            'id' => $coupons_info['id']
        ))->update(array(
            'state' => '1',
            'bindorder' => $order_id,
            'version' => $max_version + 1
        ));
        if ($returnData === false){
            return getReturn(-1, "更改店铺优惠券状态失败");
        }
        $mb_coupons_model = Model('mb_coupons');
        $max_ver = $mb_coupons_model->max('version');
        $coupons_data = $mb_coupons_model->where(array(
            'coupons_id' => $coupons_info['coupons_id']
        ))->find();
        if (!empty($coupons_data)) {
            $returnData =  $mb_coupons_model->where(array(
                'coupons_id' => $coupons_info['coupons_id']
            ))->update(array(
                'use_num' => $coupons_data['use_num'] + 1,
                'version' => $max_ver + 1
            ));
            if ($returnData === false){
                return getReturn(-2, "更改店铺优惠券库状态失败");
            }
        }
        //add by czx 17/5/17  改变活动优惠券的状态
        $membercoupons_data = Model('mb_membercoupons')->where(array(
            'id' => $coupons_info['id']
        ))->find();
        if (!empty($membercoupons_data) && !empty($membercoupons_data['exchange_id'])) {

            $returnData =  Model('mb_exchange')->where(array('exchange_id' => $membercoupons_data['exchange_id']))
                ->update(array('finished_time' => TIMESTAMP, 'exchange_type' => 1));
            if ($returnData === false){
                return getReturn(-2, "更改优惠券礼品表失败");
            }
        }
        return  getReturn(200, "成功");
    }


    //统一积分改变
    public function changeCredit($store_id, $member_id, $member_name, $credit_type, $credit_name, $score, $reason, $order_id = 0)
    {


        $store_data = Model('store')->where(array('store_id' => $store_id))->find();
        $channel_data = Model('mb_channel')->where(array('channel_id' => $store_data['channel_id']))->find();
        if (!empty($channel_data['credits_switch'])) {
            $mainstore_data = Model('store')->where(array('channel_id' => $store_data['channel_id'], 'main_store' => 1))->find();
            $store_id = $mainstore_data['store_id'];
        }
        if ($channel_data['store_type'] == 2) {
            $mainstore_data = Model('store')->where(array('channel_id' => $store_data['channel_id'], 'main_store' => 1))->find();
            if ($mainstore_data['credit_pay'] == 1) {
                $store_id = $mainstore_data['store_id'];
            }
        }

        //$integral_data = Model('mb_channel')->field('integral_pv_switch,integral_rate0,integral_rate1,integral_rate2,integral_rate3')->where(array('channel_id'=>$store_data['channel_id']))->find();
        $mainstore_id = $store_id;
        if ($store_data['channel_type'] == 2) {
            $mainstore_data = Model('store')->where(array('channel_id' => $store_data['channel_id'], 'main_store' => 1))->find();
            $mainstore_id = $mainstore_data['store_id'];
        }
        $integral_data = Model('mb_store_info')
            ->where(array('store_id' => $mainstore_id))->find();
        Log::record("member_data_count--2", Log::ERR);

        if (!empty($integral_data['integral_pv_switch']) && ($credit_type == 5 || $credit_type == 7)) {

            $credits = Model("mb_credits");

            $max_version = $credits->max('version');

            $self_integral_rate = $integral_data['integral_rate0'];
            $selfStoreMember = Model('mb_storemember')
                ->where(array('member_id' => $member_id, 'store_id' => $mainstore_id))
                ->find();
            if ($selfStoreMember['level'] == 1){
                $self_integral_rate = $integral_data['vip1_integral_rate0'];
            }else if ($selfStoreMember['level'] == 2){
                $self_integral_rate = $integral_data['vip2_integral_rate0'];
            }else if ($selfStoreMember['level'] == 2){
                $self_integral_rate = $integral_data['vip3_integral_rate0'];
            }

            $data = array(
                'mid' => $member_id,
                'tid' => $credit_type,
                'create_time' => TIMESTAMP,
                'score' => $score * $self_integral_rate,
                'credits_name' => $credit_name,
                'version' => $max_version + 1,
                'member_name' => $member_name,
                'storeid' => $store_id,
                'order_id' => $order_id
            );

            if (!empty($reason)) {
                $data['give_reason'] = $reason;
            }


            $member = Model('mb_storemember');



            $sumscores = $member->field('sum_score')->where(array(
                'member_id' => $member_id,
                'store_id' => $store_id
            ))->find();


            $sum_score = $sumscores['sum_score'] + $score * $self_integral_rate;


            $mversion = $member->max('version');
            $flag = $member->where(array(
                'member_id' => $member_id,
                'store_id' => $store_id
            ))->save(array(
                'sum_score' => $sum_score,
                'version' => $mversion + 1
            ));


            $credit_id = $credits->add($data); //往积分表里面添加积分项


            $data['credits_id'] = $credit_id;
            $data['sum_score'] = $sum_score;


            $name = $member_name;
            $rm_member_id = $member_id;
            $member_data = array();
            $member_data[0]['member_name'] = $name;
            $member_data[0]['member_id'] = $member_id;
            $i = 1;
            while ($i < 4) {
                $rname = getStoreParentname($rm_member_id, $store_id);
                if (empty($rname)) {
                    break;
                }
                $temp = Model('member')->field('member_id')->where(array('member_name' => $rname))->find();
                $member_data[$i]['member_name'] = $rname;
                $member_data[$i]['member_id'] = $temp['member_id'];
                $name = $rname;
                $rm_member_id = $temp['member_id'];
                $i++;
            }
            Log::record("member_data_count" . json_encode($member_data), Log::ERR);


            for ($i = 1; $i < count($member_data); $i++) {
                $member = Model('mb_storemember');
                $sumscores = $member->field('sum_score,level')->where(array(
                    'member_id' => $member_data[$i]['member_id'],
                    'store_id' => $store_id
                ))->find();
                if ($sumscores['level'] == 0){
                    $ratekey = "integral_rate".$i;
                }else if ($sumscores['level'] == 1){
                    $ratekey = "vip1_integral_rate".$i;
                }else if ($sumscores['level'] == 2){
                    $ratekey = "vip2_integral_rate".$i;
                }else if ($sumscores['level'] == 3){
                    $ratekey = "vip3_integral_rate".$i;
                }
                $rate = $integral_data[$ratekey];

                if (!empty($rate)) {
                    $credits = Model("mb_credits");

                    $max_version = $credits->max('version');

                    $data_temp = array(
                        'mid' => $member_data[$i]['member_id'],
                        'tid' => $credit_type,
                        'create_time' => TIMESTAMP,
                        'score' => $score * $rate,
                        'credits_name' => $member_name . $credit_name,
                        'version' => $max_version + 1,
                        'member_name' => $member_data[$i]['member_name'],
                        'give_reason' => $member_name . $credit_name,
                        'storeid' => $store_id
                    );

                    $sum_score = $sumscores['sum_score'] + $score * $rate;

                    $send_array = [];
                    $send_array['se'] = $store_id;
                    $send_array['store_id'] = $store_id;
                    $send_array['type'] = 9;
                    $send_array['member_id'] = $member_data[$i]['member_id'];
                    $send_array['credit_value'] = $score * $rate;
                    $send_array['credit_type'] = $credit_name;
                    $send_array['is_api'] = 1;
                    $this->sendMessage($send_array);
                    Log::record("jifengaigai" . json_encode($send_array), Log::ERR);
                    $mversion = $member->max('version');
                    $flag = $member->where(array(
                        'member_id' => $member_data[$i]['member_id'],
                        'store_id' => $store_id
                    ))->save(array(
                        'sum_score' => $sum_score,
                        'version' => $mversion + 1
                    ));


                    $credit_id = $credits->add($data_temp); //往积分表里面添加积分项

                }
            }

            return $data;
        } else {
            $credits = Model("mb_credits");

            $max_version = $credits->max('version');

            $data = array(
                'mid' => $member_id,
                'tid' => $credit_type,
                'create_time' => TIMESTAMP,
                'score' => $score,
                'credits_name' => $credit_name,
                'version' => $max_version + 1,
                'member_name' => $member_name,
                'storeid' => $store_id,
                'order_id' => $order_id
            );

            if (!empty($reason)) {
                $data['give_reason'] = $reason;
            }


            $member = Model('mb_storemember');
            $sumscores = $member->field('sum_score')->where(array(
                'member_id' => $member_id,
                'store_id' => $store_id
            ))->find();
            $sum_score = $sumscores['sum_score'] + $score;

            if ($sum_score < 0) {
                output_error(-5, $store_id.'总积分数不够'.$member_id, '积分项插入数据库失败');
            }
            $mversion = $member->max('version');
            $flag = $member->where(array(
                'member_id' => $member_id,
                'store_id' => $store_id
            ))->save(array(
                'sum_score' => $sum_score,
                'version' => $mversion + 1
            ));
            if (!$flag) {
                output_error(-4, $member_id . '修改积分'.$sum_score.'失败' . $store_id . "--" . $sum_score, '更新会员总积分失败');
            }

            $send_array = [];
            $send_array['se'] = $store_id;
            $send_array['store_id'] = $store_id;
            $send_array['type'] = 9;
            $send_array['member_id'] = $member_id;
            $send_array['credit_value'] = $score;
            $send_array['credit_type'] = $credit_name;
            $send_array['is_api'] = 1;
            $this->sendMessage($send_array);

            $credit_id = $credits->add($data); //往积分表里面添加积分项
            if (!$credit_id) {
                output_error(-3, $member_id . '添加积分项失败' . $store_id . "--" . json_encode($data), '积分项插入数据库失败');
            }

            $data['credits_id'] = $credit_id;
            $data['sum_score'] = $sum_score;
            return $data;

        }

    }


}




?>