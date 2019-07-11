<?php

namespace Common\Logic;

use Think\Log;
use Common\Logic\CashCartLogic;
use Common\Common\OrderApi;
use Common\Common\CommonOrderApi;

/**
 * Class CashOrderLogic
 * 订单逻辑
 * @package Api\Model
 * User: czx
 * Date: 2018-01-24 10:51:45
 */
class CashOrderLogic extends BaseLogic
{

    /**抵扣信息常量**/
    const PLATFORM_COUPONS = 1;  //平台优惠券
    const COUPONS = 2;  //优惠券
    const THIRPART_MONEY = 3;  //第三方余额
    const PLATFORM_CREDITS = 4;  //平台积分
    const CREDITS = 5;  //积分
    const PLATFORM_BALANCE = 6; //平台余额
    const BALNCE = 7;
    const CASH_STORE_DISCOUNT_AMOUNT = 8;
    const CASH_STORE_REDUCE_MONEY = 9;


    /**
     * @var CashCartLogic
     */
    protected $cart = [];
    protected $commonOrderApi = [];
    protected $storeData = '';
    protected $mainStoreData = '';
    protected $channelData = '';
    protected $freight_list = array();
    protected $freight_list_one = array('type' => 0, 'name' => '快递配送', 'checked' => 0);
    protected $freight_list_two = array('type' => 1, 'name' => '上门自提', 'checked' => 0);
    protected $istest = 0;

    public function __construct($storeId, $istest = 0, $name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($storeId, $name, $tablePrefix, $connection);
        $this->commonOrderApi = new CommonOrderApi();
        $this->storeData = D("Store")->getStoreInfo2($storeId)['data'];
        if (empty($this->storeData)) {
            return getReturn(-1, '商家信息不存在');
        }
        $this->channelData = D('Channel')->getChannelInfo($this->storeData['channel_id'])['data'];
        if (empty($this->channelData)) {
            return getReturn(-2, '渠道信息不存在');
        }
        if ($this->channelData['store_type'] == 2) {
            $this->mainStoreData = M("store")->where(array('channel_id' => $this->channelData['channel_id'], 'main_store' => 1))->find();
        }
        $this->istest = $istest;
    }


    public function getConfirmOrderData($storeId, $memberId, $store_discount, $store_reduce_money)
    {

        $confirmOrderData = array();
        /***第一步:获取商品数组***/
        $this->cart = new CashCartLogic($storeId, $memberId);
        $goods_datas = array();
        $store_id_array = array();
        $store_id_array[] = $storeId;
        $goodsIdArray = $this->cart->getSelectAllGoodsId();
       // if (empty($goodsIdArray)) return getReturn(-2, "商品数量为0,请添加商品");
        foreach ($goodsIdArray as $value) {
            $value_arr = explode("|",$value);
            $resultData = $this->cart->isSpecIdEmpty($value_arr[1]);
            if ($resultData == true){
                $value = $value_arr[0]."|"."0";
            }
            $goods_bean = D('CashGoods')->getCashGoodsBeanWithGsId($value, $storeId, $memberId);
            if (empty($goods_bean))
                continue;
            unset($goods_bean['spec']);
            unset($goods_bean['goods_desc']);
            unset($goods_bean['spec_option']);
            unset($goods_bean['goods_content']);
            unset($goods_bean['images']);
            unset($goods_bean['img_text']);
            $goods_bean['balance_limit'] = -1;
            if (!empty($goods_bean)) {
                if ($goods_bean['new_price'] != -1) {
                    $goods_bean['goods_price'] = $goods_bean['new_price'];
                }
                $goods_bean['price'] = $goods_bean['goods_price'];
                if (empty($goods_bean['gou_num'])) {
                    $goods_bean['gou_num'] = $goods_bean['buy_num'];
                }
                $goods_datas[] = $goods_bean;
                if (!in_array($goods_bean['store_id'], $store_id_array)) {
                    $store_id_array[] = $goods_bean['store_id'];
                }
            }
        }
       // file_put_contents("eeeeeeeeee9999999.txt",json_encode($goods_datas));

        /**第二步:商品分类**/
        $storeInfoArray = $this->getStoreGoodsBean($store_id_array, $goods_datas);





        /**第六步: 获取第三方余额抵用**/

        $thirdpart = $this->commonOrderApi->check_thirdpart($storeId);

        if (!empty($thirdpart)) {
            $data_url = $thirdpart['find_balance_api'];
            $params = array();
            $params['member_id'] = $memberId;
            $returnData = httpRequest($data_url, 'post', $params);
            if ($returnData['code'] != 200) return getReturn(-2, "请求失败");
            $datas = json_decode($returnData['data'], true);
            $thirdpart['consume_money'] = floor($datas['consume_money'] * 100) / 100;
            $confirmOrderData['thirdpart_money'] = $thirdpart['consume_money'];
            if ($thirdpart['status'] == 1) {
                $confirmOrderData['thirdpart_money_pay'] = 1;
            } else {
                $confirmOrderData['thirdpart_money_pay'] = 0;
            }
            $confirmOrderData['thirdpart_moneyname'] = $thirdpart['moneyname'];
            if ($datas['result'] != '-1') {
                $confirmOrderData['thirdpart'] = $thirdpart;
            } else {
                return getReturn(-1, $datas['desc']);
            }
        } else {
            $confirmOrderData['thirdpart_money'] = 0;
            $confirmOrderData['thirdpart_money_pay'] = 0;
            $confirmOrderData['thirdpart_moneyname'] = '';
        }

        $goods_total_num = 0;
        $total_amount = 0;
        $goods_amount = 0;

        for ($i = 0; $i < count($storeInfoArray); $i++) {
            $storeInfoArray[$i]['total_amount'] = $storeInfoArray[$i]['goods_amount'];
            $storeInfoArray[$i]['temp_total_amount'] = $storeInfoArray[$i]['goods_amount'];
            $storeInfoArray[$i]['rest_total_amount'] = $storeInfoArray[$i]['goods_amount'];
            $goods_total_num += $storeInfoArray[$i]['goods_total_num'];
            $total_amount += $storeInfoArray[$i]['total_amount'];
            $goods_amount += $storeInfoArray[$i]['goods_amount'];
        }
        $confirmOrderData['goods_amount'] = $total_amount;
        $confirmOrderData['total_amount'] = $total_amount;
        $confirmOrderData['temp_total_amount'] = $total_amount;
        $confirmOrderData['goods_total_num'] = $goods_total_num;

        /**第九步:获取个人可抵用电子币信息**/
        //积分 平台积分 余额 平台余额  第三方余额
        for ($i = 0; $i < count($storeInfoArray); $i++) {
            $storeshopdata_one = $storeInfoArray[$i];
            $storeMemberData = M("mb_storemember")
                ->where(array('store_id' => $storeshopdata_one['store_id'], 'member_id' => $memberId))->find();
            if (empty($storeMemberData)) $storeMemberData = D('StoreMember')
                ->focusStore($storeshopdata_one['store_id'], $memberId)['data'];
            $storeInfoArray[$i]['balance'] = $storeMemberData['balance'];

            $storeInfoArray[$i]['credit_num'] = $storeMemberData['sum_score'];
            $oneStoreData = D("Store")->getStoreInfo2($storeshopdata_one['store_id'])['data'];
            $storeInfoArray[$i]['credit_limit_money'] = $oneStoreData['credit_limit_money'];
            $storeInfoArray[$i]['credit_percent'] = $oneStoreData['credit_percent'];
            $storeInfoArray[$i]['credit_to_money'] = $oneStoreData['credit_to_money'];
            $storeInfoArray[$i]['credit_pay'] = $oneStoreData['credit_pay'];
            $max_credit_num = 0;
            if ($storeInfoArray[$i]['total_amount'] >= $storeInfoArray[$i]['credit_limit_money']
                && !empty($storeInfoArray[$i]['credit_to_money']) && $storeInfoArray[$i]['credit_pay'] > 0
            ) {
                $max_credit_num = floor($storeInfoArray[$i]['total_amount'] * $oneStoreData['credit_percent'] * $storeInfoArray[$i]['credit_to_money']);
            }
            if ($max_credit_num > $storeInfoArray[$i]['credit_num'])
                $max_credit_num = $storeInfoArray[$i]['credit_num'];

            $storeInfoArray[$i]['max_credit_num'] = $max_credit_num;
        }


        if ($this->storeData['channel_type'] == 2 && !empty($this->mainStoreData)) {
            $mainStoreMemberData = M("mb_storemember")
                ->where(array('store_id' => $this->mainStoreData['store_id'], 'member_id' => $memberId))->find();

            if (empty($mainStoreMemberData)) $mainStoreMemberData = D('StoreMember')
                ->focusStore($this->mainStoreData['store_id'], $memberId)['data'];
            $confirmOrderData['platform_balance_pay'] = $this->mainStoreData['balancepay'];
            $confirmOrderData['platform_balance'] = $mainStoreMemberData['balance'];
            $confirmOrderData['platform_credit_num'] = $mainStoreMemberData['sum_score'];
            $confirmOrderData['platform_credit_limit_money'] = $this->mainStoreData['credit_limit_money'];
            $confirmOrderData['platform_credit_percent'] = $this->mainStoreData['credit_percent'];
            $confirmOrderData['platform_credit_to_money'] = $this->mainStoreData['credit_to_money'];
            $confirmOrderData['platform_credit_pay'] = $this->mainStoreData['credit_pay'];

            $max_platform_credit_num = 0;
            if ($confirmOrderData['total_amount'] >= $confirmOrderData['platform_credit_limit_money']
                && !empty($confirmOrderData['platform_credit_to_money']) && $confirmOrderData['platform_credit_pay'] > 0
            ) {
                $max_platform_credit_num = floor($confirmOrderData['total_amount'] * $this->mainStoreData['credit_percent'] * $confirmOrderData['platform_credit_to_money']);
            }
            if ($max_platform_credit_num > $confirmOrderData['platform_credit_num'])
                $max_platform_credit_num = $confirmOrderData['platform_credit_num'];
            $confirmOrderData['max_platform_credit_num'] = $max_platform_credit_num;
        } else {

            $confirmOrderData['platform_balance_pay'] = 0;
            $confirmOrderData['platform_balance'] = 0;
            $confirmOrderData['platform_credit_num'] = 0;
            $confirmOrderData['platform_credit_limit_money'] = 0;
            $confirmOrderData['platform_credit_percent'] = 0;
            $confirmOrderData['platform_credit_to_money'] = 0;
            $confirmOrderData['platform_credit_pay'] = 0;
            $confirmOrderData['max_platform_credit_num'] = 0;
        }

        $confirmOrderData['storeInfoArray'] = $storeInfoArray;

        /**计算店铺折扣抵用**/
        $this->distributionStoreDiscount($confirmOrderData, $store_discount);

        /**计算店铺抵用金额**/
       $this->distributionStoreReduceMoney($confirmOrderData, $store_reduce_money);

        /**第四步:获取优惠券**/
        $storeInfoArray = $confirmOrderData['storeInfoArray'];
        for ($i = 0; $i < count($storeInfoArray); $i++) {
            $storeInfoArray[$i]['storeMemberCoupons'] = $this->get_store_member_coupons($storeInfoArray[$i], $memberId);
        }
        $confirmOrderData['storeInfoArray'] = $storeInfoArray;

        /**第五步:获取平台优惠券**/
        $confirmOrderData['platformMemberCoupons'] = $this->getPlatformCoupons($storeInfoArray, $this->mainStoreData['store_id'], $memberId);

        /**第十四: 获取最大优惠券**/
        for ($j = 0; $j < count($confirmOrderData['storeInfoArray']); $j++) {
            if (!empty($confirmOrderData['storeInfoArray'][$j]['storeMemberCoupons']['use'])) {
                $storeMaxCoupons = 0;
                $storeMaxCouponsId = 0;
                /*******计算1********/
                for ($i = 0; $i < count($confirmOrderData['storeInfoArray'][$j]['storeMemberCoupons']['use']); $i++) {
                    $returnData = $this->getMaxCouponsConfirm($confirmOrderData['storeInfoArray'][$j],
                        $confirmOrderData['storeInfoArray'][$j]['storeMemberCoupons']['use'][$i]);
                    if ($returnData['code'] != 200) {
                        return $returnData;
                    }
                    if ($storeMaxCoupons < $returnData['data']['coupons_money']) {
                        $storeMaxCoupons = $returnData['data']['coupons_money'];
                        $storeMaxCouponsId = $returnData['data']['id'];
                    }
                }
                /*******计算end********/

                /*******计算2********/
                if ($storeMaxCoupons > 0 && $storeMaxCouponsId > 0) {
                    for ($i = 0; $i < count($confirmOrderData['storeInfoArray'][$j]['storeMemberCoupons']['use']); $i++) {
                        if ($confirmOrderData['storeInfoArray'][$j]['storeMemberCoupons']['use'][$i]['id'] == $storeMaxCouponsId) {
                            $returnData = $this->getMaxCoupons($confirmOrderData['storeInfoArray'][$j], $storeMaxCouponsId);
                            if ($returnData['code'] != 200) {
                                return $returnData;
                            }
                            $confirmOrderData['storeInfoArray'][$j]['storeMemberCoupons']['use'][$i]['is_select'] = 1;
                            $confirmOrderData['storeInfoArray'][$j]['storeMemberCoupons']['use'][$i]['coupons_exmoney'] = round($storeMaxCoupons, 2);
//                            $restTotalAmount = round($confirmOrderData['storeInfoArray'][$j]['temp_total_amount'] -
//                                $confirmOrderData['storeInfoArray'][$j]['storeMemberCoupons']['use'][$i]['coupons_exmoney']
//                                - $confirmOrderData['storeInfoArray'][$j]['store_reduce_money']
//                                - $confirmOrderData['storeInfoArray'][$j]['store_discount_amount'], 2);
                            $restTotalAmount = round($confirmOrderData['storeInfoArray'][$j]['temp_total_amount'] -
                                $confirmOrderData['storeInfoArray'][$j]['storeMemberCoupons']['use'][$i]['coupons_exmoney']
                               , 2);
                            if ($restTotalAmount < 0) {
                                return getReturn(-1,  $confirmOrderData['storeInfoArray'][$j]['temp_total_amount']."获取最大优惠券分配有误".$confirmOrderData['storeInfoArray'][$j]['store_discount_amount']);
                            }
                            $confirmOrderData['storeInfoArray'][$j]['temp_total_amount']
                                = $restTotalAmount;
                            $confirmOrderData['storeInfoArray'][$j]['rest_total_amount']
                                = $restTotalAmount;
                            $confirmOrderData['temp_total_amount'] = saveTwoDecimal($confirmOrderData['temp_total_amount']
                                - $confirmOrderData['storeInfoArray'][$j]['storeMemberCoupons']['use'][$i]['coupons_exmoney']);
                            break;
                        }
                    }
                    $confirmOrderData['storeInfoArray'][$j]['coupons_exmoney'] = round($storeMaxCoupons, 2);
                } else {
                    $confirmOrderData['storeInfoArray'][$j]['coupons_exmoney'] = 0;
                }
                /*******计算end********/

            } else {
                $confirmOrderData['storeInfoArray'][$j]['coupons_exmoney'] = 0;
            }
        }


        /**第十五: 获取最大平台优惠券**/
        if (!empty($confirmOrderData['platformMemberCoupons'])) {
            for ($j = 0; $j < count($confirmOrderData['storeInfoArray']); $j++) {
                $storeMaxPlatformCoupons = 0;
                $storeMaxPlatformCouponsId = 0;
                /*******计算1********/
                for ($i = 0; $i < count($confirmOrderData['platformMemberCoupons']); $i++) {

                    $returnData = $this->getMaxPlatformCouponsConfirm($confirmOrderData['storeInfoArray'],
                        $confirmOrderData['platformMemberCoupons'][$i]);
                    if ($returnData['code'] != 200) {
                        return $returnData;
                    }

                    if ($storeMaxPlatformCoupons < $returnData['data']['coupons_money']) {
                        $storeMaxPlatformCoupons = $returnData['data']['coupons_money'];
                        $storeMaxPlatformCouponsId = $returnData['data']['id'];
                    }
                }
            }
            /*******计算end********/

            /*******计算2********/
            if ($storeMaxPlatformCoupons > 0 && $storeMaxPlatformCouponsId > 0) {
                for ($i = 0; $i < count($confirmOrderData['platformMemberCoupons']); $i++) {
                    if ($confirmOrderData['platformMemberCoupons'][$i]['id'] == $storeMaxPlatformCouponsId) {
                        $returnData = $this->getMaxPlatformCoupons($confirmOrderData['storeInfoArray'], $storeMaxPlatformCouponsId);
                        if ($returnData['code'] != 200) {
                            return $returnData;
                        }

                        $confirmOrderData['platformMemberCoupons'][$i]['is_select'] = 1;
                        $confirmOrderData['platformMemberCoupons'][$i]['coupons_exmoney'] = round($storeMaxPlatformCoupons, 2);
                        $restTotalAmount = round($confirmOrderData['temp_total_amount'] -
                            $confirmOrderData['platformMemberCoupons'][$i]['coupons_exmoney'], 2);
                        if ($restTotalAmount < 0) {
                            return getReturn(-1, "获取最大平台优惠券分配有误");
                        }
                        $confirmOrderData['temp_total_amount']
                            = $restTotalAmount;
                        break;
                    }
                }
                $confirmOrderData['platform_coupons_money'] = round($storeMaxPlatformCoupons, 2);
            }
            /*******计算end********/

        } else {
            $confirmOrderData['platform_coupons_money'] = 0;
        }

        /**第十六: 分配最大可抵用金额**/
        $returnData = $this->distributionMaxExChange($confirmOrderData);
        if ($returnData['code'] != 200) {
            return $returnData;
        }

        return getReturn(200, "返回成功", $confirmOrderData);
    }

    /**
     * 把商品分配到每个商家数组里
     * @param $store_id_array  商家编号
     * @param $goods_datas  商品数组
     * @return array
     * User: czx
     * Date: 2018/3/19 9:49:24
     * Update: 2018/3/19 9:49:24
     * Version: 1.00
     */
    public function getStoreGoodsBean($store_id_array, $goods_datas)
    {
        $storeshopdata_array = array();
        foreach ($store_id_array as $key1 => $value1) {
            $storeOneData = D("Store")->getStoreInfo2($value1)['data'];
            $one_data = array();
            $one_data['store_id'] = $value1;
            $one_data['store_name'] = $storeOneData['store_name'];
            $one_data['member_name'] = $storeOneData['store_member_name'];
            $one_data['store_label'] = $storeOneData['store_label'];
            $one_data['balancepay'] = $storeOneData['balancepay'];
            $one_data['credit_limit_money'] = $storeOneData['credit_limit_money'];
            $one_data['credit_percent'] = $storeOneData['credit_percent'];
            $one_data['credit_to_money'] = $storeOneData['credit_to_money'];
            $one_data['credit_pay'] = $storeOneData['credit_pay'];

            $one_data['province_id'] = $storeOneData['province_id'];
            $one_data['city_id'] = $storeOneData['city_id'];
            $one_data['country_id'] = $storeOneData['country_id'];
            $one_data['freight_mode'] = $storeOneData['freight_mode'];
            $one_data['balance_exchange_postage'] = 1;
            $goods_amount = 0;
            $goods_total_num = 0;
            foreach ($goods_datas as $key2 => $value2) {

                if ($value1 == $value2['store_id']) {
                    $one_data['order_content'][] = $value2;
                    $goods_amount += $value2['price'] * $value2['buy_num'];
                    $goods_total_num += $value2['buy_num'];
                }
            }
            $one_data['goods_amount'] = $goods_amount;
            $one_data['goods_total_num'] = $goods_total_num;
            $storeshopdata_array[] = $one_data;
        }
        return $storeshopdata_array;
    }

    /**
     * 获取满减信息
     * @param $storeInfoArray
     * User: czx
     * Date: 2018/3/19 9:49:24
     * Update: 2018/3/19 9:49:24
     * Version: 1.00
     */
    public function getStoreMjGoodsList(&$storeInfoArray)
    {

        foreach ($storeInfoArray as $key1 => $value1) {
            $one_data = array();
            $one_buy_num = 0;
            $one_total_price = 0;
            $store_mj_price = 0;
            $store_mj_array = [];
            foreach ($storeInfoArray[$key1]['order_content'] as $key2 => $value2) {
                $one_buy_num = $one_buy_num + $value2['buy_num'];
                $one_total_price = $one_total_price + $value2['goods_price'] * $value2['buy_num'];
                if ($value2['mj_id'] > 0) {
                    $item_i = -1;
                    for ($i = 0; $i < count($store_mj_array); $i++) {
                        if ($store_mj_array[$i]['mj_id'] == $value2['mj_id']) {
                            $item_i = $i;
                            break;
                        }
                    }
                    if ($item_i < 0) {
                        $store_mj_array[] = ['mj_id' => $value2['mj_id'],
                            'mj_level' => $value2['mj_level'],
                            'total_price' => $value2['goods_price'] * $value2['buy_num'],
                            'total_num' => $value2['buy_num']
                        ];
                    } else {
                        $store_mj_array[$item_i]['total_price'] = $store_mj_array[$item_i]['total_price'] + $value2['goods_price'] * $value2['buy_num'];
                        $store_mj_array[$item_i]['total_num'] = $store_mj_array[$item_i]['total_num'] + $value2['buy_num'];
                    }

                }

            }

            // hjun 2018-01-03 11:41:15 数组有重复的mj_id 处理一下
            for ($i = 0; $i < count($store_mj_array); $i++) {
                $where = [];
                $where['mj_id'] = $store_mj_array[$i]['mj_id'];
                $mjInfo = D('MjActivity')->getMjList(0, 0, 1, 1, $where)['data']['list'][0];
                $detail = D('Goods')->getMjDetailByLevel($store_mj_array[$i]['total_price'] * 100, $store_mj_array[$i]['total_num'], $mjInfo);
                $store_mj_price += $detail['reducePrice'];
                $store_mj_array[$i]['mj_detail'] = $detail;
            }

            $storeInfoArray[$key1]['mj_price'] = $store_mj_price;
            $storeInfoArray[$key1]['mj_detail_array'] = $store_mj_array;
            $exchange_mj_price = 0;
            for ($i = 0; $i < count($storeInfoArray[$key1]['order_content']); $i++) {
                $goodsbean = $storeInfoArray[$key1]['order_content'][$i];
                $mj_bean_price = 0;
                if ($goodsbean['mj_id'] > 0) {
                    for ($j = 0; $j < count($store_mj_array); $j++) {
                        if ($goodsbean['mj_id'] == $store_mj_array[$j]['mj_id']) {
                            $mj_bean_price = round(($goodsbean['price'] * $goodsbean['gou_num'] / $store_mj_array[$j]['total_price']) * $storeInfoArray[$key1]['mj_price'], 2);
                        }
                    }

                }
                if (($storeInfoArray[$key1]['mj_price'] - $exchange_mj_price) < $mj_bean_price) {
                    $mj_bean_price = $storeInfoArray[$key1]['mj_price'] - $exchange_mj_price;
                }
                $exchange_mj_price += $mj_bean_price;
                $storeInfoArray[$key1]['order_content'][$i]['mj_bean_price'] = $mj_bean_price;
            }
        }
    }

    /**
     * 获取某个商家中某个会员的优惠券
     * @param array $storeshopdata_one 单个商家信息
     * @param string $member_id 用户编号
     * @return array $storeMemberCouponsArray  返回某个店某个会员满足条件优惠券
     */
    public function get_store_member_coupons($storeshopdata_one, $member_id)
    {
        //查询某个用户某个商家的优惠券
        $condition = array();
        $condition['store_id'] = $storeshopdata_one['store_id'];
        $condition['member_id'] = $member_id;
        $condition['isdelete'] = 0;
        $condition['state'] = 0;
        $membercoupons_datas = Model("mb_membercoupons")->where($condition)->select();
        $storeMemberAllCouponsArray = array();
        $storeMemberCouponsArray = array();
        $storeMemberUnUseCouponsArray = array();
        //删选优惠券
        for ($j = 0; $j < count($membercoupons_datas); $j++) {
            $one_coupons = $membercoupons_datas[$j];
            $one_coupons['mclass'] = $this->getCouponsClass($one_coupons);
            $one_coupons['mclass_str'] = $this->getCouponsClassStr($one_coupons);
            //第一步 判断优惠券是否在有效期内
            if ($one_coupons['limit_time_type'] == 2) {
                if ($one_coupons['end_time'] < TIMESTAMP) {
                    continue;
                }
            } else if ($one_coupons['limit_time_type'] == 3) {
                if ($one_coupons['limit_start_time'] > TIMESTAMP || $one_coupons['limit_end_time'] < TIMESTAMP) {
                    continue;
                }
            }
            //第二步 计算满足优惠券使用条件的商品总价
            $store_goods_price = $this->getShopCouponsGoodsPrice($storeshopdata_one, $one_coupons);

            if ($one_coupons['coupons_type'] == 2) {
                $discount_coupons = round($store_goods_price * (1 - $one_coupons['coupons_discount']), 2);
                $one_coupons['coupons_money'] = $discount_coupons;
            }
            if ($one_coupons['limit_time_type'] == 1) {
                $one_coupons['end_time_str'] = "无期限";
            } else {

                if ($one_coupons['limit_time_type'] == 2) {
                    $one_coupons['end_time_str'] = "截止:" . date("Y-m-d", $one_coupons['end_time']);
                } else if ($one_coupons['limit_time_type'] == 3) {
                    $one_coupons['end_time_str'] = "截止:" . date("Y-m-d", $one_coupons['limit_end_time']);
                }
            }
            if ($one_coupons['limit_money'] == 0) {
                $one_coupons['limit_money_str'] = "无门槛优惠券";
            } else {
                $one_coupons['limit_money_str'] = "满" . $one_coupons['limit_money'] . "可使用";
            }

            $one_coupons['is_select'] = 0;
            $one_coupons['coupons_exmoney'] = 0;
            $limitClassName = $this->commonOrderApi->getCouponsLimitClassName($one_coupons);
            $one_coupons['limitClassName'] = $limitClassName;
            if ($one_coupons['limit_money'] <= $store_goods_price && $store_goods_price > 0) {
                $storeMemberCouponsArray[] = $one_coupons;
            } else {
                $storeMemberUnUseCouponsArray[] = $one_coupons;
            }

        }
        $storeMemberAllCouponsArray['use'] = $storeMemberCouponsArray;
        $storeMemberAllCouponsArray['unuse'] = $storeMemberUnUseCouponsArray;
        return $storeMemberAllCouponsArray;
    }

    public function getCouponsClass($one_coupons)
    {
        $json = $one_coupons['limit_class'];
        $mdatas = json_decode($json, true);
        $length2 = count($mdatas);
        $mclass = array();
        if ($length2 > 0) {
            $mclass = array();
            for ($f = 0; $f < count($mdatas); $f++) {
                $mclass[] = $mdatas[$f]['class_id'];
            }
        }
        return $mclass;
    }

    public function getCouponsClassStr($one_coupons)
    {
        $json = $one_coupons['limit_class'];
        $mdatas = json_decode($json, true);
        $length2 = count($mdatas);
        $mclass_str = "";
        if ($length2 > 0) {
            for ($f = 0; $f < count($mdatas); $f++) {
                if (isset($mdatas[$f]['classStr']))
                    $mclass_str = $mdatas[$f]['classStr'];
            }
        }
        return $mclass_str;
    }

    /**
     * 获取满足优惠券使用条件的商品总和
     * @param array $storeshopdata_one 单个商家信息
     * @param array $one_coupons 优惠券信息
     * @return array $store_goods_price  返回满足条件的金额
     */
    public function getShopCouponsGoodsPrice($storeshopdata_one, $one_coupons)
    {

        $store_goods_price = 0;
        for ($t = 0; $t < count($storeshopdata_one['order_content']); $t++) {

            $goods_bean = $storeshopdata_one['order_content'][$t];
            if (!empty($goods_bean['buy_num']) && empty($goods_bean['gou_num'])) {
                $goods_bean['gou_num'] = $goods_bean['buy_num'];
            }
//            $goods_data = Model('goods')->where(array('goods_id' => $goods_bean['goods_id']))->find();
//            $goods_bean['gc_id'] = $goods_data['gc_id'];
//            $goods_bean['allow_coupon'] = $goods_data['allow_coupon'];
//            $goods_bean['mall_goods_class_1'] = $goods_data['mall_goods_class_1'];
//            $goods_bean['mall_goods_class_2'] = $goods_data['mall_goods_class_2'];
//            $goods_bean['mall_goods_class_3'] = $goods_data['mall_goods_class_3'];

            if ($this->checkGoodsUseCoupons($goods_bean, $one_coupons)) {

                $store_goods_price = $store_goods_price + $goods_bean['price'] * $goods_bean['gou_num'] - $goods_bean['mj_bean_price']
                    - $goods_bean['store_reduce_price']
                    - $goods_bean['store_discount_price'];
            }

        }
        return $store_goods_price;
    }


    public function checkGoodsUseCoupons($goods_bean, $one_coupons)
    {
        $goods_gc_id = $goods_bean['gc_id'];
        $allow_coupon = $goods_bean['allow_coupon'];
//        if (strpos($goods_gc_id, '|')) {
//            $goods_gc_id = substr($goods_gc_id, 0, stripos($goods_gc_id, '|'));
//        }
        $gc_id_array = array();
        if (strpos($goods_gc_id, '|')) {
            $gc_id_array = explode('|', $goods_gc_id);
        } else {
            $gc_id_array[] = $goods_gc_id;
        }

        //$goods_bean['parent_gc'] = $goods_gc_id;
        //判断是否可用优惠券
        if ($allow_coupon != 1) {
            return false;
        }
        //判断是否为促销商品
//        if ($one_coupons['limit_type'] != 2) {
//            if (!($goods_bean['state'] <= 0
//                || $goods_bean['state'] == 3 || $goods_bean['state'] == 4)
//            ) {
//                return false;
//            }
//        }


        //1-全部品类可用 2-指定商品分类不可用 3-指定商城分类不可用 4-指定商品可用
        //<=0:正常商品  3:会员价  4:合伙人  这三种类型都是可以使用优惠券
        if ($one_coupons['limit_class_type'] == 1) {
            return true;
        } else if ($one_coupons['limit_class_type'] == 2) {
//            if (in_array($goods_bean['parent_gc'], $one_coupons['mclass'])){
//                return false;
//            }
            if (count($gc_id_array) > 0) {
                foreach ($gc_id_array as $key => $val) {
                    if (in_array($gc_id_array[$key], $one_coupons['mclass'])) {
                        $tag = 1;
                        break;
                    }
                }
            }
            if ($tag == 1) {
                return false;
            }
        } else if ($one_coupons['limit_class_type'] == 3) {
            $limit_mall_class = explode(",", $one_coupons['limit_mall_class']);
            if ($goods_bean['mall_goods_class_3'] > 0) {
                if (in_array($goods_bean['mall_goods_class_3'], $limit_mall_class)) {
                    return false;
                }
            } else if ($goods_bean['mall_goods_class_2'] > 0) {
                if (in_array($goods_bean['mall_goods_class_2'], $limit_mall_class)) {
                    return false;
                }
            } else if ($goods_bean['mall_goods_class_1'] > 0) {
                if (in_array($goods_bean['mall_goods_class_1'], $limit_mall_class)) {
                    return false;
                }
            }

        } else if ($one_coupons['limit_class_type'] == 4) {
            $limit_goods = explode(",", $one_coupons['limit_goods']);
            if (!in_array($goods_bean['goods_id'], $limit_goods)) {
                return false;
            }

        }
        return true;
    }

    public function getPlatformCoupons($storeshopdata_array, $main_store_id, $member_id)
    {
        $main_store_contidtion = array();
        $main_store_contidtion['store_id'] = $main_store_id;
        $main_store_contidtion['member_id'] = $member_id;
        $main_store_contidtion['state'] = 0;
        $main_store_contidtion['isdelete'] = 0;
        $main_store_coupons = Model("mb_membercoupons")
            ->where($main_store_contidtion)->select();

        $platform_coupons = array();
        if (count($main_store_coupons) <= 0) {
            return $platform_coupons;
        }

        for ($i = 0; $i < count($main_store_coupons); $i++) {
            $one_coupons = $main_store_coupons[$i];
            $one_coupons['mclass'] = $this->getCouponsClass($one_coupons);
            $one_coupons['mclass_str'] = $this->getCouponsClassStr($one_coupons);
            //第一步 判断优惠券是否在有效期内
            if ($one_coupons['limit_time_type'] == 2) {
                if ($one_coupons['end_time'] < TIMESTAMP) {
                    continue;
                }
            } else if ($one_coupons['limit_time_type'] == 3) {
                if ($one_coupons['limit_start_time'] > TIMESTAMP || $one_coupons['limit_end_time'] < TIMESTAMP) {
                    continue;
                }
            }
            if ($one_coupons['limit_time_type'] == 1) {
                $one_coupons['end_time_str'] = "无期限";
            } else {
                if ($one_coupons['limit_time_type'] == 2) {
                    $one_coupons['end_time_str'] = "截止:" . date("Y-m-d", $one_coupons['end_time']);
                } else if ($one_coupons['limit_time_type'] == 3) {
                    $one_coupons['end_time_str'] = "截止:" . date("Y-m-d", $one_coupons['limit_end_time']);
                }
            }

            if ($one_coupons['limit_money'] == 0) {
                $one_coupons['limit_money_str'] = "无门槛优惠券";
            } else {
                $one_coupons['limit_money_str'] = "满" . $one_coupons['limit_money'] . "可使用";
            }
            $one_coupons['is_select'] = 0;
            $one_coupons['coupons_exmoney'] = 0;
            //第二步 计算满足优惠券使用条件的商品总价
            $store_goods_price = 0;
            for ($j = 0; $j < count($storeshopdata_array); $j++) {
                $storeshopdata_one = $storeshopdata_array[$j];
                $store_goods_price = $store_goods_price + $this->getShopCouponsGoodsPrice($storeshopdata_one, $one_coupons) + $storeshopdata_array[$j]['freight']['freight'];
            }

            if ($one_coupons['limit_money'] <= $store_goods_price && $store_goods_price > 0) {
                $limitClassName = $this->commonOrderApi->getCouponsLimitClassName($one_coupons);
                $one_coupons['limitClassName'] = $limitClassName;
                $platform_coupons[] = $one_coupons;
            }

        }
        return $platform_coupons;

    }


    /**
     * 获取某个商家自提地址
     * @param $storeshopdata_one
     * @param $channel_data
     * @param $main_store_id
     * @param $latitude
     * @param $longitude
     * @return mixed
     * User: czx
     * Date: 2018/3/19 11:45:51
     * Update: 2018/3/19 11:45:51
     * Version: 1.00
     */
    public function get_store_pickup($storeshopdata_one, $channel_data, $main_store_id, $latitude, $longitude)
    {

        $store_str = $storeshopdata_one['store_id'];
        if ($channel_data['store_type'] == 2) {
            $store_str = $store_str . "," . $main_store_id;
        }
        $condition = array();
        $condition['store_id'] = array('in', $store_str);
        $condition['is_pick'] = 1;
        $condition['isdelete'] = 0;
        $pickup_datas = D("PickUp")->where($condition)->select();

        $pickup_data_array = array();
        $is_select = 0;
        foreach ($pickup_datas as $key => $value) {
            $lat1 = $latitude;
            $lng1 = $longitude;
            $lng2 = $value['longitude'];
            $lat2 = $value['latitude'];
            $distanceNum = getDistance($lat1, $lng1, $lat2, $lng2);
            $snm = $distanceNum;
            if ($distanceNum < 1000) {
                $distanceNum = $distanceNum . "m";
            } else if ($distanceNum > 1000) {
                $distanceNum = round($distanceNum / 1000, 2) . "km";
            }
            $pickup_datas[$key]['distanceNum'] = $distanceNum;
            if (empty($lat1) || empty($lng1) || empty($lat2) || empty($lng2) || $snm > 3000000) {
                $pickup_datas[$key]['show_distance'] = 0;
                $pickup_datas[$key]['distance'] = '';
            } else {
                $pickup_datas[$key]['show_distance'] = 1;
                $pickup_datas[$key]['distance'] = $distanceNum;
            }
            // region hjun 2018-11-09 15:34:57 查出有库存的门店（与规格对应）
            $where = [];
            $where['depot_id'] = $value['id'];
            $where['is_delete'] = NOT_DELETE;
            $where['storage'] = ['gt', 0];
            $pickup_goods_ids = D('DepotGoods')->field('goods_id,spec_id')->where($where)->select();
            foreach ($storeshopdata_one['order_content'] as $key2 => $value2) {
                // 标记为未找到符合条件的门店
                $tag = 0;
                foreach ($pickup_goods_ids as $limit) {
                    if ($value2['goods_id'] == $limit['goods_id'] && $value2['primary_id'] == $limit['spec_id']) {
                        // 标记为已找到 一旦找到则不再循环
                        $tag = 1;
                        break;
                    }
                }
                // 只要存在一个商品未找到符合条件的门店 则当前门店就不能筛选
                if ($tag === 0) {
                    break;
                }
            }
            if (empty($tag)) {
                unset($pickup_datas[$key]);
                continue;
            }
            // endregion

            if ($value['id'] == $this->cart->getExtra()['pickup_ids'][$storeshopdata_one['store_id']]['pick_id']) {
                $pickup_datas[$key]['is_select'] = 1;
                $is_select = 1;
            } else {
                $pickup_datas[$key]['is_select'] = 0;
            }
            $pickup_data_array[] = $pickup_datas[$key];
        }
        if ($is_select == 0 && count($pickup_data_array) > 0) {
            foreach ($pickup_data_array as $key => $value) {
                $pickup_data_array[$key]['is_select'] = 1;
                break;
            }
        }
        return $pickup_data_array;
    }

    /**
     * 下单逻辑接口
     * @param $mallstore_order_array
     * @param $client
     * @param $app_name
     * @return array
     * User: czx
     * Date: 2018/3/23 23:31:44
     * Update: 2018/3/23 23:31:44
     * Version: 1.00
     */
    public function createOrderData($mallstore_order_array, $client, $app_name, $device_id)
    {

        /**第一步:检查订单是否已经插入**/

        if (empty($this->storeData)) return getReturn(-1, "商家信息为空");
        $returnData = $this->commonOrderApi->checkCashOrderExit($mallstore_order_array['order_sn']);
        if ($returnData['code'] == 200) {
            if (!empty($returnData['data'])) return $returnData;
        } else {
            return $returnData;
        }

        //F("CX", $mallstore_order_array['merge_store_id'] . "--" . json_encode($this->storeData));
        /**第二步:数据初始化**/
        $returnData = $this->checkOrderMallBaseData($mallstore_order_array, $this->channelData, $this->mainStoreData);
        if ($returnData['code'] !== 200) return $returnData;
        $orderType = $mallstore_order_array['order_type'];  // hj 2018-02-08 21:55:43 团购订单标识
        $isGroupOrder = $orderType == 'tg';
        logWrite("1-是否是团购:{$orderType},团购ID{$mallstore_order_array['group_id']}" . $isGroupOrder);
        logWrite("初始化后数据:" . json_encode($mallstore_order_array, 256));


        $returnData = $this->checkOrderCouponsData($mallstore_order_array);
        if ($returnData['code'] != 200) {
            return $returnData;
        }

//        $returnData = $this->DistributionOrderData($mallstore_order_array);
//        if ($returnData['code'] != 200) {
//            return $returnData;
//        }


//        $returnData = $this->DistributionOrderGoodsData($mallstore_order_array);
//        if ($returnData['code'] != 200) {
//            return $returnData;
//        }

        //防止重复下单

        $returnData = D("CashOrderOnly")->addOrderOnly($mallstore_order_array['order_sn']);
        if ($returnData['code'] != 200) {
            $returndata = D("CashOrderOnly")->getOrderOnly($mallstore_order_array['order_sn']);
            if (!empty($returndata) && !empty($returndata['data'])) {
                return getReturn(200, "返回成功", json_decode($returndata['data']));
            } else {
                return getReturn(-1, "网络请求超时,请检查订单是否下单成功");
            }
        }

        $morder_id = 0;
        $mergepay = 0;
        $mergeorder_id = 0;

        $mallstore_order_array['morder_id'] = $morder_id;
        $mallstore_order_array['mergepay'] = $mergepay;
        $mallstore_order_array['mergeorder_id'] = $mergeorder_id;

        $return_array = array();
        $storeorder_array = $mallstore_order_array['mergeorder_content'];
        $orderversion_max = Model("mb_cashorder")->max('version');

        for ($i = 0; $i < count($storeorder_array); $i++) {
            try {
                $this->startTrans();
                $storeorder = $storeorder_array[$i];
                $orderversion_max++;

                // hjun 2018-03-26 15:57:43 商品根据分类排序
                $goodsList = $storeorder['order_content'];
                $goodsList = array_sort($goodsList, 'gc_id', 'DESC');
                $storeorder['order_content'] = $goodsList;

                $returnData = D("CashOrder")->addOrderData($mallstore_order_array, $storeorder, $orderversion_max, $device_id);

                $score = 0;
                if ($returnData['code'] != 200) {
                    $this->rollback();
                    return $returnData;
                } else {
                    $order_id = $returnData['data']['order_id'];
//                    $returnData2 = D("CashOrderGoods")->put_order_goods_db($storeorder['order_content'], $order_id, $storeorder['storeid']);
//                    if ($returnData2['code'] != 200) {
//                        $this->rollback();
//                        return $returnData2;
//                    }

                    //收银发送推送
                    $ordermoney = intval($returnData['data']['receive_amount']);
                    $score = $this->getOrderCredit($returnData['data']['storeid'], $returnData['data']['buyer_id'], $ordermoney);
                    $this->gouWuReduceCredits(13, $score, $returnData['data']['buyer_id'], $returnData['data']['storeid']);

                    $order_return_data = $returnData;

                    $order_id = $returnData['data']['order_id'];
                    $data = $returnData['data'];
                    $mallstore_order_array['mergeorder_content'][$i]['order_id'] = $order_id;
                    $order_content_list = $storeorder['order_content'];
                    $storeid_data = array();

                    $allgoodsprice = 0.0;//总原价
                    $all_goods_count = 0;  // 自提那边要显示数量
                    for ($t = 0; $t < count($order_content_list); $t++) {
                        $one_data = array();
                        $post_goods_id = $order_content_list[$t]['goods_id']; //$_POST['goods_id'];    //商品ID
                        $gou_num = $order_content_list[$t]['gou_num'];
                        $goods_state = $order_content_list[$t]['state'];
                        $goods_price = $order_content_list[$t]['goods_price'];

                        if (array_key_exists('store_id', $order_content_list[$t])) {
                            if ($order_content_list[$t]['store_id'] != $storeorder['storeid']) {
                                if (!in_array($order_content_list[$t]['store_id'], $storeid_data)) {
                                    $storeid_data[] = $order_content_list[$t]['store_id'];
                                }
                            }
                        }
                        $allgoodsprice += $goods_price * $gou_num; //add by chenqm
                        $all_goods_count += $gou_num;
                    }


                    $input = new OrderApi();
                    if (empty($mallstore_order_array['buy_code'])) {
                        //全部检查完再扣库存
                        for ($p = 0; $p < count($storeorder_array); $p++) {
                            $storeorder2 = $storeorder_array[$p];
                            $input->optionStorageAndSales(-1, $storeorder2['order_content'],
                                0, 1, $storeorder2['storeid'], $mallstore_order_array['buyer_id']);
                        }
                    }


                    /**扣除余额**/
                    if ($storeorder['balance'] > 0 || $storeorder['platform_balance'] > 0) {
                        $type_data = Model('mb_balance_type')->where(array(
                            'id' => '1'
                        ))->find();
                        $meberitem = Model('member')->field('member_name')->where(array(
                            'member_id' => $data['buyer_id']
                        ))->find();
                        $member_name = $meberitem['member_name'];


                        if (round($data['balance'], 2) > 0) {

                            $returnData = $this->commonOrderApi->changeBalance($data['storeid'], $data['buyer_id'], "1",
                                -1 * $data['balance'], $type_data['name'], "", $order_id, $member_name);
                            if ($returnData['code'] != 200) {
                                $this->rollback();
                                return $returnData;
                            }

                            $balance_data = $returnData['data'];
                        }

                        if (round($data['platform_balance'], 2) > 0) {
                            $returnData = $this->commonOrderApi->changePlatformBalance($data['storeid'], $data['buyer_id'], "1",
                                -1 * $data['platform_balance'], $type_data['name'], "", $order_id, $member_name);
                            if ($returnData['code'] != 200) {
                                $this->rollback();
                                return $returnData;
                            }
                            $platform_balance_data = $returnData['data'];
                        }
                    }

                    /**更改店铺优惠券**/
                    if ($storeorder['gou_type'] == 1) {
                        $returnData = $this->commonOrderApi->createOrderChangeCoupons($storeorder, $order_id);
                        if ($returnData['code'] != 200) {
                            $this->rollback();
                            return $returnData;
                        }
                    }
                    /**更改店铺优惠券结束**/

                    /**增减店铺积分**/
                    if ($storeorder['credits_num'] > 0) {
                        $returnData = $this->gouWuReduceCredits(5, -1 * $storeorder['credits_num'], $mallstore_order_array['buyer_id'], $storeorder['storeid']);
                        if (empty($returnData)) {
                            $this->rollback();
                            return getReturn(-1, "下单增减积2分失败");
                        }
                    }
                    $data['order_id'] = $order_id;
                    $data['order_content'] = $storeorder['order_content'];
                    // $data['gou_info'] = $storeorder['gou_info'];
                    //判断是否需要下联盟订单

                    if (empty($balance_data)) {
                        $balance_data = 0;
                    }
                    if (empty($platform_balance_data)) {
                        $platform_balance_data = array('id' => 0);
                    }
                    $return_array['mergeorder_content'][] = $data;
                    $this->commit();

                    $send_array = [];
                    $send_array['se'] = $order_return_data['data']['storeid'];
                    $send_array['store_id'] = $order_return_data['data']['storeid'];
                    $send_array['order_id'] = $order_return_data['data']['order_id'];
                    $send_array['score'] = $score;
                    $send_array['is_api'] = 1;
                    $this->sendMessage($send_array);
                }

            } catch (Exception $e) {
                $this->rollback();
                return getReturn(-1, "下单数据异常,数据回滚");
            }


            /**扣减第三方余额**/
            if ($storeorder['thirdpart_money'] > 0) {
                $third_part = $this->commonOrderApi->check_thirdpart($this->storeData['store_id']);

                $pay_url = $third_part['reduct_balance_api'];
                $params = array();
                //$params['order_id'] = empty($mallstore_order_array['morder_id']) ? $return_array['mergeorder_content'][0]['order_id'] : $mallstore_order_array['morder_id'];
                $params['order_id'] = $order_id;
                $params['price'] = round($storeorder['thirdpart_money'], 2);
                file_put_contents("xxxxxx", json_encode($params));
                $pay_json = httpRequest($pay_url, 'post', $params);
                file_put_contents("xxxxxx2", $pay_json);
                $pay_result = json_decode($pay_json, true);
                if ($pay_result['result'] == -1) {
                    output_error(101, $pay_result['desc'], '更改数据库失败');
                } else {
                    Model("mb_cashorder")->where(array('order_id' => $order_id))->update(array('thirdpart_pay' => 1));
                }
            }

        }

        if ($mallstore_order_array['platform_credits_num'] > 0) {
            $returnData = $this->gouWuReduceCredits(5, -1 * $mallstore_order_array['platform_credits_num'], $mallstore_order_array['buyer_id'], $this->mainStoreData['store_id']);
            if (empty($returnData)) {
                $this->rollback();
                return getReturn(-1, "下单增减积分失败");
            }
        }

        if ($mallstore_order_array['use_platform_coupons'] == 1) {
            Model('mb_membercoupons')->where(array(
                'id' => $mallstore_order_array['platform_coupons_id'],
                'isdelete' => '0'
            ))->find();

            $max_version = Model('mb_membercoupons')->max('version');
            Model('mb_membercoupons')->where(array(
                'id' => $mallstore_order_array['platform_coupons_id']
            ))->update(array(
                'state' => '1',
                'bindorder' => "mg_" . $mergeorder_id,
                'version' => $max_version + 1
            ));
            $mb_coupons_model = Model('mb_coupons');
            $max_ver = $mb_coupons_model->max('version');
            $coupons_data = $mb_coupons_model->where(array(
                'coupons_id' => $mallstore_order_array['platform_coupons_id']
            ))->find();
            if (!empty($coupons_data)) {
                $mb_coupons_model->where(array(
                    'coupons_id' => $mallstore_order_array['platform_coupons_id']
                ))->update(array(
                    'use_num' => $coupons_data['use_num'] + 1,
                    'version' => $max_ver + 1
                ));
            }
        }

        /**扣减第三方余额 ** end **/
        $return_array['pay_type'] = $mallstore_order_array['pay_type'];
        $return_array['total_price'] = $mallstore_order_array['total_price'];
        if ($mallstore_order_array['channel_type'] == 2) {  //商城
            $return_array['mergeorder_id'] = $mergeorder_id;
            $return_array['morder_id'] = "mg_" . $mergeorder_id;
        } else {
            if ($mallstore_order_array['mergeorder_content'][0]['order_id'] > 0) {
                $return_array['mergeorder_id'] = $mallstore_order_array['mergeorder_content'][0]['order_id'];
                $return_array['morder_id'] = $mallstore_order_array['mergeorder_content'][0]['order_id'];
            } else {
                $return_array['mergeorder_id'] = 0;
                $return_array['morder_id'] = 0;
            }
        }
        $return_array['balancedata'] = $balance_data;
        $return_array['platform_balancedata'] = $platform_balance_data;
        $where = array();
        $where['order_sn'] = $mallstore_order_array['order_sn'];
        Model("mb_cashorder_only")->where($where)->update(array('return_data' => json_encode($return_array, 256)));
        foreach ($return_array['mergeorder_content'] as $value) {
            foreach ($value['order_content'] as $value2) {
                $delete_cart = new CashCartLogic($value['storeid'], $mallstore_order_array['buyer_id']);
                $gs_id = $value2['gs_id'];
                if (empty($gs_id)) {
                    $gs_id = $value2['gs_id'] . "|" . $value2['specid'];
                }
                $delete_cart->delItem($gs_id);
                // $cartTool->clear();
            }

        }
        return getReturn(200, "成功", $return_array);
    }

    public function DistributionOrderData(&$mallstore_order_array)
    {

        $platform_balance_tag = 0;
        $use_platform_coupons_tag = 0;
        $platform_credits_num_tag = 0;
        $thirdpart_money_tag = 0;
        //使用平台余额，则进行分配
        if ($mallstore_order_array['platform_balance'] > 0) {
            $platform_balance_tag = 1;
        }
        //使用平台优惠券，则进行分配
        if ($mallstore_order_array['use_platform_coupons'] == 1) {
            $use_platform_coupons_tag = 1;
        }
        //使用平台积分，则进行分配
        if ($mallstore_order_array['platform_credits_num'] > 0) {
            $platform_credits_num_tag = 1;
        }

        if ($mallstore_order_array['thirdpart_money'] > 0) {
            $thirdpart_money_tag = 1;
        }

        if ($platform_balance_tag + $use_platform_coupons_tag + $platform_credits_num_tag + $thirdpart_money_tag > 0) {
            $use_platform_balance_num = 0;
            $use_platform_coupons_num = 0;
            $use_platform_credits_exmoney = 0;
            $use_thirdpart_money = 0;

            for ($i = 0; $i < count($mallstore_order_array['mergeorder_content']); $i++) {

                if ($use_platform_coupons_tag == 1 && $mallstore_order_array['mergeorder_content'][$i]['platform_coupons_money'] > 0) {
//                    if ($i != $this->commonOrderApi->getLastPosition($mallstore_order_array['mergeorder_content'], 'store_pcoupons_price')) {
//                        $one_platform_coupons = round(($mallstore_order_array['mergeorder_content'][$i]['store_pcoupons_price']
//                                / $mallstore_order_array['mallstore_pcoupons_price'])
//                            * $mallstore_order_array['platform_coupons_money'], 2);
//                    } else {
//                        $one_platform_coupons = $mallstore_order_array['platform_coupons_money'] - $use_platform_coupons_num;
//                    }
                    $one_platform_coupons = $mallstore_order_array['mergeorder_content'][$i]['platform_coupons_money'];
                    $use_platform_coupons_num = $use_platform_coupons_num + $mallstore_order_array['mergeorder_content'][$i]['platform_coupons_money'];
//                    if ($one_platform_coupons > 0) {
                    $mallstore_order_array['mergeorder_content'][$i]['use_platform_coupons'] = 1;
                    $mallstore_order_array['mergeorder_content'][$i]['platform_coupons_id'] = $mallstore_order_array['platform_coupons_id'];
//                    } else {
//                        return getReturn(-1, "商场分配优惠券失败,请稍后重试");
//                    }

                } else {
                    $mallstore_order_array['mergeorder_content'][$i]['use_platform_coupons'] = 0;
                    $mallstore_order_array['mergeorder_content'][$i]['platform_coupons_id'] = 0;
                    $mallstore_order_array['mergeorder_content'][$i]['platform_coupons_money'] = 0;
                    $one_platform_coupons = 0;
                }

                if ($thirdpart_money_tag == 1) {
                    if ($i != $this->commonOrderApi->getLastPosition($mallstore_order_array['mergeorder_content'], 'thirdpart_order_money')) {
                        $one_thirdpart_money = round(($mallstore_order_array['mergeorder_content'][$i]['thirdpart_order_money']
                                / $mallstore_order_array['mall_thirdpart_order_money'])
                            * $mallstore_order_array['thirdpart_money'], 2);
                    } else {
                        $one_thirdpart_money = $mallstore_order_array['thirdpart_money'] - $use_thirdpart_money;
                    }


                    if ($one_thirdpart_money > $mallstore_order_array['mergeorder_content'][$i]['thirdpart_order_money']) {
                        return getReturn(-1, "商场分配第三方余额失败,请稍后重试");
                    }
                    $use_thirdpart_money = $use_thirdpart_money + $one_thirdpart_money;
                    if ($one_thirdpart_money < 0) {
                        return getReturn(-1, "商场分配第三方余额失败,请稍后重试");
                    }
                    $mallstore_order_array['mergeorder_content'][$i]['thirdpart_money'] = $one_thirdpart_money;

                } else {
                    $mallstore_order_array['mergeorder_content'][$i]['thirdpart_money'] = 0;
                }


                if ($platform_balance_tag == 1) {
                    if ($i != count($mallstore_order_array['mergeorder_content']) - 1) {
                        $one_balance = round((($mallstore_order_array['mergeorder_content'][$i]['totalprice'] - $one_thirdpart_money - $one_platform_coupons)
                                / ($mallstore_order_array['mall_order_price'] - $mallstore_order_array['thirdpart_money'] -
                                    $mallstore_order_array['platform_coupons_money'])) * $mallstore_order_array['platform_balance'], 2);
                    } else {
                        $one_balance = $mallstore_order_array['platform_balance'] - $use_platform_balance_num;
                    }
                    $use_platform_balance_num = $use_platform_balance_num + $one_balance;
                    if ($one_balance < 0 || $one_balance > ($mallstore_order_array['mergeorder_content'][$i]['totalprice'] - $one_thirdpart_money - $one_platform_coupons)) {
                        return getReturn(-1, "商场分配平台余额失败,请稍后重试");
                    }
                    $mallstore_order_array['mergeorder_content'][$i]['platform_balance'] = $one_balance;

                } else {
                    $mallstore_order_array['mergeorder_content'][$i]['platform_balance'] = 0;
                }


                if ($platform_credits_num_tag == 1) {

                    if ($i != count($mallstore_order_array['mergeorder_content']) - 1) {
                        $one_platform_credits_exmoney = round((($mallstore_order_array['mergeorder_content'][$i]['totalprice'] -
                                    $one_thirdpart_money - $one_platform_coupons - $mallstore_order_array['mergeorder_content'][$i]['platform_balance']) /
                                ($mallstore_order_array['mall_order_price'] - $mallstore_order_array['thirdpart_money'] -
                                    $mallstore_order_array['platform_coupons_money'] -
                                    $mallstore_order_array['platform_balance'])) * $mallstore_order_array['platform_credits_exmoney'], 2);
                    } else {
                        $one_platform_credits_exmoney = $mallstore_order_array['platform_credits_exmoney'] - $use_platform_credits_exmoney;
                    }
                    $use_platform_credits_exmoney = $use_platform_credits_exmoney + $one_platform_credits_exmoney;
                    if ($one_platform_credits_exmoney > 0) {
                        $mallstore_order_array['mergeorder_content'][$i]['platform_credits_exmoney'] = $one_platform_credits_exmoney;
                        $mallstore_order_array['mergeorder_content'][$i]['platform_credits_num'] = round(($one_platform_credits_exmoney / $mallstore_order_array['platform_credits_exmoney']) * $mallstore_order_array['platform_credits_num'], 2);
                    } else {
                        return getReturn(-1, "商场分配平台积分失败,请稍后重试");
                    }
                } else {
                    $mallstore_order_array['mergeorder_content'][$i]['platform_credits_exmoney'] = 0;
                    $mallstore_order_array['mergeorder_content'][$i]['platform_credits_num'] = 0;
                }


                $mallstore_order_array['mergeorder_content'][$i]['totalprice'] = $mallstore_order_array['mergeorder_content'][$i]['totalprice']
                    - $mallstore_order_array['mergeorder_content'][$i]['platform_balance']
                    - $mallstore_order_array['mergeorder_content'][$i]['platform_coupons_money']
                    - $mallstore_order_array['mergeorder_content'][$i]['platform_credits_exmoney']
                    - $mallstore_order_array['mergeorder_content'][$i]['thirdpart_money'];

            }
        }

        return getReturn(200, "商场分配成功");
    }

    /**
     * 初始化订单商城基本数据
     * @param $mallstore_order_array
     * @param $channelData
     * @return array
     * User: czx
     * Date: 2018/3/20 20:34:47
     * Update: 2018/3/20 20:34:47
     * Version: 1.00
     */
    public function checkOrderMallBaseData(&$mallstore_order_array, $channelData, $mainStoreData)
    {
        //TODO 初始化订单数组最外层数据
        if (empty($channelData)) return getReturn(-1, "订单渠道信息不存在");
        if (empty($mallstore_order_array['buyer_id'])) return getReturn(-2, "用户编号为空");
        if (empty($mallstore_order_array['platform_balance'])) $mallstore_order_array['platform_balance'] = 0;
        if (empty($mallstore_order_array['use_platform_coupons'])) $mallstore_order_array['use_platform_coupons'] = 0;
        if (empty($mallstore_order_array['platform_coupons_id'])) $mallstore_order_array['platform_coupons_id'] = 0;
        if (empty($mallstore_order_array['platform_coupons_money'])) $mallstore_order_array['platform_coupons_money'] = 0;
        if (empty($mallstore_order_array['platform_credits_num'])) $mallstore_order_array['platform_credits_num'] = 0;
        if (empty($mallstore_order_array['platform_credits_exmoney'])) $mallstore_order_array['platform_credits_exmoney'] = 0;
        if (empty($mallstore_order_array['thirdpart_money'])) $mallstore_order_array['thirdpart_money'] = 0;
        if (empty($mallstore_order_array['total_price'])) $mallstore_order_array['total_price'] = 0;
        if (empty($mallstore_order_array['merge_store_id'])) return getReturn(-3, "订单商家编号为空");
        if (empty($mallstore_order_array['order_sn']))
            return getReturn(-3, "订单唯一标识不能为空");

        if (empty($mallstore_order_array['store_reduce_money'])) $mallstore_order_array['store_reduce_money'] = 0;
        if (empty($mallstore_order_array['cash_charge_account'])) $mallstore_order_array['cash_charge_account'] = 0;
        if (empty($mallstore_order_array['bank_charge_account'])) $mallstore_order_array['bank_charge_account'] = 0;
        if (empty($mallstore_order_array['ali_charge_account'])) $mallstore_order_array['ali_charge_account'] = 0;
        if (empty($mallstore_order_array['wx_charge_account'])) $mallstore_order_array['wx_charge_account'] = 0;
        if (empty($mallstore_order_array['free_order'])) $mallstore_order_array['free_order'] = 0;
        if (empty($mallstore_order_array['free_order_reason'])) $mallstore_order_array['free_order_reason'] = '';

        if (empty($mallstore_order_array['member_discount_amount'])) $mallstore_order_array['member_discount_amount'] = 0;
        if (empty($mallstore_order_array['member_discount'])) $mallstore_order_array['member_discount'] = 0;
        if (empty($mallstore_order_array['odd_change'])) $mallstore_order_array['odd_change'] = 0;
        $mallstore_order_array['store_name'] =  $this->storeData['store_name'];
        $memberData = M('member')->where(array('member_id' => $mallstore_order_array['buyer_id']))->find();
        $mallstore_order_array['member_name'] = $memberData['member_name'];
        $mallstore_order_array['member_tel'] = $memberData['member_tel'];

        //额外添加
        $mallstore_order_array['channel_type'] = $channelData['store_type'];
        $mallstore_order_array['channel_id'] = $channelData['channel_id'];

        if ($mallstore_order_array['channel_type'] == 2) {
            if (empty($mainStoreData)) {
                return getReturn(-7, "该渠道下暂未找到主商家信息");
            }
            $mallstore_order_array['platform_credit_to_money'] = $mainStoreData['credit_to_money'];
            $mallstore_order_array['platform_credit_percent'] = $mainStoreData['credit_percent'];
            $mallstore_order_array['platform_credit_limit_money'] = $mainStoreData['credit_limit_money'];
        } else {
            $mallstore_order_array['platform_credit_to_money'] = 0;
            $mallstore_order_array['platform_credit_percent'] = 0;
            $mallstore_order_array['platform_credit_limit_money'] = 0;
        }
        //$confirmData = M("mb_order_confirm_data")->where(array('order_sn' => $mallstore_order_array['order_sn']))->find();
        $confirmData = F("cashorder/{$mallstore_order_array['order_sn']}");
        if (empty($confirmData)) {
            return getReturn(-8, "订单缓存数据为空,请您重新从购物车下单");
        }
        $mallstore_order_array['total_amount'] = $confirmData['total_amount'];
        if ($mallstore_order_array['free_order'] == 1){
             $mallstore_order_array['order_actual_amount'] = 0;
             $mallstore_order_array['platform_balance'] = 0;
             $mallstore_order_array['use_platform_coupons'] = 0;
             $mallstore_order_array['platform_coupons_id'] = 0;
             $mallstore_order_array['platform_coupons_money'] = 0;
             $mallstore_order_array['platform_credits_num'] = 0;
             $mallstore_order_array['platform_credits_exmoney'] = 0;
             $mallstore_order_array['thirdpart_money'] = 0;
             $mallstore_order_array['store_discount_amount'] = 0;
             $mallstore_order_array['store_discount'] = 10;
            $mallstore_order_array['store_reduce_money'] = 0;

        }else{
//            $mallstore_order_array['order_actual_amount'] = $mallstore_order_array['platform_credits_exmoney']
//                + $mallstore_order_array['thirdpart_momey'] + $mallstore_order_array['platform_balance']
//                + $mallstore_order_array['mergeorder_content'][0]['balance'] + $mallstore_order_array['mergeorder_content'][0]['credits_exmoney']
//                + $mallstore_order_array['cash_charge_account'] + $mallstore_order_array['bank_charge_account']
//                + $mallstore_order_array['wx_charge_account'] + $mallstore_order_array['ali_charge_account'];

            $mallstore_order_array['order_actual_amount'] =
                 $mallstore_order_array['cash_charge_account'] + $mallstore_order_array['bank_charge_account']
                + $mallstore_order_array['wx_charge_account'] + $mallstore_order_array['ali_charge_account']
                 - $mallstore_order_array['odd_change'];

        }

        //$confirmData = json_decode($confirmData['data_content'], true);
        for ($i = 0; $i < count($mallstore_order_array['mergeorder_content']); $i++) {
            $mallstore_order_array['mergeorder_content'][$i]['order_content'] = array();

            for ($j = 0; $j < count($confirmData['storeInfoArray']); $j++) {

                if ($mallstore_order_array['mergeorder_content'][$i]['storeid'] == $confirmData['storeInfoArray'][$j]['store_id']) {

                    $mallstore_order_array['mergeorder_content'][$i]['total_amount'] = $confirmData['storeInfoArray'][$j]['total_amount'];
                    if ($mallstore_order_array['free_order'] == 1){
                        $mallstore_order_array['mergeorder_content'][$i]['receive_amount'] = 0;
                        $mallstore_order_array['mergeorder_content'][$i]['credits_num'] = 0;
                        $mallstore_order_array['mergeorder_content'][$i]['credits_exmoney'] = 0;

                        $mallstore_order_array['mergeorder_content'][$i]['platform_credits_num'] = 0;
                        $mallstore_order_array['mergeorder_content'][$i]['platform_credits_exmoney'] = 0;
                        $mallstore_order_array['mergeorder_content'][$i]['thirdpart_money'] = 0;
                        $mallstore_order_array['mergeorder_content'][$i]['gou_type'] = 0;
                        $mallstore_order_array['mergeorder_content'][$i]['balance'] = 0;
                        $mallstore_order_array['mergeorder_content'][$i]['platform_balance'] = 0;
                        $mallstore_order_array['mergeorder_content'][$i]['use_coupons'] = 0;
                        $mallstore_order_array['mergeorder_content'][$i]['coupons_id'] = 0;
                        $mallstore_order_array['mergeorder_content'][$i]['coupons_exmoney'] = 0;
                        $mallstore_order_array['mergeorder_content'][$i]['store_discount_amount'] = 0;
                        $mallstore_order_array['mergeorder_content'][$i]['store_reduce_money'] = 0;

                    }else{
                        $mallstore_order_array['mergeorder_content'][$i]['receive_amount'] = $confirmData['storeInfoArray'][$j]['total_amount']
                            - $confirmData['storeInfoArray'][$j]['coupons_exmoney'] - $mallstore_order_array['platform_coupons_money']
                            - $mallstore_order_array['store_discount_amount'] - $mallstore_order_array['store_reduce_money'];
                        $mallstore_order_array['mergeorder_content'][$i]['store_discount_amount'] = $mallstore_order_array['store_discount_amount'];
                        $mallstore_order_array['mergeorder_content'][$i]['store_reduce_money'] = $mallstore_order_array['store_reduce_money'];
                    }

        // - $mallstore_order_array['platform_credits_exmoney']
        //                        - $mallstore_order_array['thirdpart_momey'] + $mallstore_order_array['platform_balance']
        //                        - $mallstore_order_array['mergeorder_content'][0]['balance']
        //                        - $mallstore_order_array['mergeorder_content'][0]['credits_exmoney']

                    if (empty($confirmData['storeInfoArray'][$j]['freight']['platform_coupons_money'])) {
                        $mallstore_order_array['mergeorder_content'][$i]['postage_platform_coupons_money'] = 0;
                    } else {
                        $mallstore_order_array['mergeorder_content'][$i]['postage_platform_coupons_money'] = $confirmData['storeInfoArray'][$j]['freight']['platform_coupons_money'];
                    }
                    if (empty($confirmData['storeInfoArray'][$j]['platform_coupons_money'])) {
                        $mallstore_order_array['mergeorder_content'][$i]['platform_coupons_money'] = 0;
                    } else {
                        $mallstore_order_array['mergeorder_content'][$i]['platform_coupons_money'] = $confirmData['storeInfoArray'][$j]['platform_coupons_money'];
                    }
                    $goodsBeanArray = array();
                    $orderContent = $confirmData['storeInfoArray'][$j]['order_content'];
                    for ($t = 0; $t < count($orderContent); $t++) {
                        $goodsData = $orderContent[$t];
                        $goodsBean = array();
                        $goodsBean['goods_id'] = $goodsData['goods_id'];
                        $goodsBean['goods_price'] = $goodsData['goods_price'];
                        $goodsBean['gou_num'] = $goodsData['gou_num'];
                        $goodsBean['goods_name'] = $goodsData['goods_name'];
                        $goodsBean['specid'] = $goodsData['specid'];
                        $goodsBean['mj_id'] = $goodsData['mj_id'];
                        $goodsBean['mj_level'] = $goodsData['mj_level'];
                        $goodsBean['gc_id'] = $goodsData['gc_id'];
                        $goodsBean['state'] = $goodsData['state'];
                        $goodsBean['spec_open'] = $goodsData['spec_open'];
                        if($goodsBean['spec_open'] == 1){
                            $goodsBean['spec_group'] = $goodsData['spec_id'];
                            $goodsBean['specid'] = $goodsData['spec_id'];
                        }else{
//                            $goodsBean['specid'] = $goodsData['spec_id'];
                            $goodsBean['specid'] = $goodsData['spec_id'];
                        }
                        $goodsBean['spec_id'] = $goodsData['spec_id'];

                        $goodsBean['gs_id'] = $goodsData['gs_id'];
                        $goodsBean['main_img'] = $goodsData['main_img'];
                        $goodsBean['goods_figure'] = $goodsData['goods_figure'];
                        $goodsBean['spec_name'] = $goodsData['spec_name_title'];
                        $goodsBean['goods_barcode'] = $goodsData['goods_barcode'];
                        $goodsBean['goods_number'] = $goodsData['goods_number'];
                        $goodsBean['print_ids'] = $goodsData['print_ids'];
                        if($mallstore_order_array['free_order'] == 1){
                            $goodsBean['store_reduce_price'] = 0;
                            $goodsBean['store_discount_price'] = 0;
                            $goodsBean['allow_coupon'] = $goodsData['allow_coupon'];
                            $goodsBean['coupons_exmoney'] = 0;
                            $goodsBean['platform_coupons_money'] = 0;

                        }else{
                            $goodsBean['store_reduce_price'] = $goodsData['store_reduce_price'];
                            $goodsBean['store_discount_price'] = $goodsData['store_discount_price'];
                            $goodsBean['allow_coupon'] = $goodsData['allow_coupon'];
                            if (empty($goodsData['coupons_exmoney'])) {
                                $goodsBean['coupons_exmoney'] = 0;
                            } else {
                                $goodsBean['coupons_exmoney'] = $goodsData['coupons_exmoney'];
                            }

                            if (empty($goodsData['platform_coupons_money'])) {
                                $goodsBean['platform_coupons_money'] = 0;
                            } else {
                                $goodsBean['platform_coupons_money'] = $goodsData['platform_coupons_money'];
                            }
                        }

                        $goodsBean['balance_limit'] = -1;
                        if ($goodsData['supplier_price']) {
                            $goodsBean['supplier_price'] = $goodsData['supplier_price'];
                            $goodsBean['supplier_tel'] = $goodsData['supplier_tel'];
                            $goodsBean['agent_owner'] = $goodsData['agent_owner'];
                            $goodsBean['supplier_sid'] = $goodsData['supplier_sid'];
                            $goodsBean['supplier_owner'] = $goodsData['supplier_owner'];
                            $goodsBean['supplier_state'] = $goodsData['supplier_state'];
                            $goodsBean['agent_sid'] = $goodsData['agent_sid'];
                            $goodsBean['supplier_name'] = $goodsData['supplier_name'];
                            $goodsBean['agent_name'] = $goodsData['agent_name'];
                            $goodsBean['agent_tel'] = $goodsData['agent_tel'];
                            $goodsBean['store_id'] = $goodsData['supplier_sid'];
                        }
                        $goodsBeanArray[] = $goodsBean;
                    }
                    $mallstore_order_array['mergeorder_content'][$i]['order_content'] = $goodsBeanArray;
                }
            }


        }
        return getReturn(200, "成功");
    }

    /**
     * 比较订单的数据
     * @param $mallstore_order_array
     * @param $storeData
     * @param $mainStoreData
     * @return array
     * User: czx
     * Date: 2018/3/21 11:1:47
     * Update: 2018/3/21 11:1:47
     * Version: 1.00
     */
    public function checkOrderData(&$mallstore_order_array, $storeData, $mainStoreData)
    {


        if ($storeData['vip_endtime'] < TIMESTAMP && $storeData['channel_id'] == 0) {
            return getReturn(-1, '该商家已打烊,请联系商家');
        }
        //出掉店铺抵用后订单总额
        $mall_order_price = $mallstore_order_array['total_price']
            + $mallstore_order_array['platform_balance']
            + $mallstore_order_array['platform_coupons_money']
            + $mallstore_order_array['platform_credits_exmoney']
            + $mallstore_order_array['thirdpart_money'];
        $mallstore_order_array['mall_order_price'] = $mall_order_price;

        if ($storeData['channel_type'] == 2) {
            $main_store = $mainStoreData;
        } else {
            $main_store = $storeData;
        }
        if (empty($main_store)) return getReturn(-2, "checkOrderData:检查订单商家信息为空,请检查传递数据");

        $storemember_data = M("mb_storemember")
            ->where(array('store_id' => $main_store['store_id'], 'member_id' => $mallstore_order_array['buyer_id']))
            ->find();
        if (empty($storemember_data)) return getReturn(-3, "checkOrderData:商家会员信息为空,暂未关注该商家");

        if ($mallstore_order_array['platform_balance'] > 0) {
            if ($storemember_data['platform_balance'] < $mallstore_order_array['platform_balance']) return getReturn(-4, "平台余额不足请充值");
        } else {
            if ($mallstore_order_array['platform_balance'] < 0) return getReturn(-5, "平台余额抵用不能为负数");
        }

        if ($mallstore_order_array['platform_credits_num'] > 0) {
            if ($mallstore_order_array['platform_credits_num'] > $storemember_data['sum_score']) return getReturn(-6, "平台积分不足请充值");

            if ($mallstore_order_array['platform_credits_exmoney'] > round($mallstore_order_array['platform_credits_num'] / $mallstore_order_array['platform_credit_to_money'], 2)) {
                return getReturn(-7, "平台积分抵用金额有误");
            }

            if ($mallstore_order_array['total_amount'] < $mallstore_order_array['platform_credit_limit_money']) return getReturn(-7, "订单平台金额小于平台积分可使用金额");

            if ($mallstore_order_array['platform_credits_exmoney'] > $mallstore_order_array['total_amount'] * $mallstore_order_array['platform_credit_percent']) {
                return getReturn(-8, $mallstore_order_array['platform_credits_exmoney'] . "平台积分抵用金额超过订单平台金额可使用比例" . $mall_order_price);
            }
        }

        //检查第三方支付是否满足条件 by lwz
        if ($mallstore_order_array['thirdpart_money'] > 0) {
            /* 外部数据判断*/
            $thirdpart = $this->commonOrderApi->check_thirdpart($main_store['store_id']);
            if (!empty($thirdpart)) {
                $data_url = $thirdpart['find_balance_api'];
                $params = array();
                $params['member_id'] = $mallstore_order_array['buyer_id'];
                $returnData = httpRequest($data_url, 'post', $params);
                if ($returnData['code'] != 200) return getReturn(-9, "请求第三方余额接口失败");
                $datas = json_decode($returnData['data'], true);
                $thirdpart['consume_money'] = floor($datas['consume_money'] * 100) / 100;
                if ($datas['result'] == '-1') return getReturn(-10, "第三方接口返回失败");
                if ($thirdpart['status'] == 1) {
                    if ($mallstore_order_array['thirdpart_money'] > $thirdpart['consume_money'])
                        return getReturn(-11, "第三方余额不足,请充值");
                } else {
                    return getReturn(-12, "该商家暂未开启第三方余额抵用");
                }
                $mallstore_order_array['thirdpart_data'] = $thirdpart;
            } else {
                return getReturn(-13, "该商家暂未开启第三方余额抵用");
            }
        } else {
            $mallstore_order_array['thirdpart_data'] = array();
        }


        //add by honglj
        $storeorder_array = $mallstore_order_array['mergeorder_content'];
        if (empty($mallstore_order_array['buy_code'])) {
            //第二步:检查商品价格,库存是否符合要求
            $input = new OrderApi();
            $quehuo_array = array();

            for ($i = 0; $i < count($storeorder_array); $i++) {
                $storeorder = $storeorder_array[$i];
                $quehuo_array = array_merge($quehuo_array, $input->checkGoods(0,
                    $storeorder['order_content'], $storeorder['storeid'], $mallstore_order_array['buyer_id']));
                $mallstore_order_array['mergeorder_content'][$i] = $storeorder;
            }

            if (count($quehuo_array) > 0) {
                return getReturn(-9999, "有缺货商品", $quehuo_array);
            }
        } else {
            $qrcode_buy_data = Model('mb_qrcode_buy')->where(array('code' => $mallstore_order_array['buy_code']))->find();
            if ($qrcode_buy_data['is_use'] == 1) {
                return getReturn(-14, "该二维码已被使用");
            }
            $storeorder = $storeorder_array[0];
            $buy_bean1 = $storeorder['order_content'][0];

            $buy_content = json_decode($qrcode_buy_data['order_content']);
            $buy_bean2 = $buy_content[0];
            if ($buy_bean1['goods_id'] == $buy_bean2->goods_id && doubleval($buy_bean1['goods_price']) == doubleval($buy_bean2->goods_price)) {
                //核对无误
                Model('mb_qrcode_buy')->where(array('code' => $mallstore_order_array['buy_code']))
                    ->update(array('is_use' => 1, 'user' => $storeorder_array[0]['buyer_id']));
            } else {
                return getReturn(-15, '数据核对有误' . $buy_bean1['goods_id'] . ' ' . $buy_bean2->goods_id . ' '
                    . $buy_bean1['goods_price'] . ' ' . $buy_bean2->goods_price);
            }

        }

        //查看总金额是否正确
        $mall_total_amount = 0; //订单总额
        $mall_integral_money = 0; //抵扣多少钱
        $all_balance = 0;
        $mall_thirdpart_limit_money = 0;
        $storeorder_array = $mallstore_order_array['mergeorder_content'];
        for ($i = 0; $i < count($storeorder_array); $i++) {
            $storeorder = $storeorder_array[$i];

            if ($mallstore_order_array['is_pickup'] == 1 && empty($storeorder['pickup_id'])) {
                return getReturn(-17, '请选择自提点');
            }
            $total_amount = 0; //子订单总额
            $integral_money = 0; //抵扣多少钱
            $goods_amount = 0;  //商品总额
            $storemember_one_data = Model("mb_storemember")
                ->where(array(
                    'store_id' => $storeorder['storeid'], 'member_id' => $mallstore_order_array['buyer_id']
                ))->find();
            if (empty($storemember_one_data)) {
                return getReturn(-17, '编号为' . $mallstore_order_array['buyer_id'] . '的用户暂未关注' . $storeorder['storeid'] . "商家,error:102");
            }

            if ($storeorder['totalprice'] < 0) return getReturn(-16, "子订单总额不能为负数");

            if ($storeorder['balance'] > 0) {
                if ($storeorder['balance'] > $storemember_one_data['balance']) {
                    return getReturn(-18, '编号为' . $storeorder['storeid'] . '的余额不足,请充值');
                }
                $all_balance = $all_balance + $storeorder['balance'];
            } else {
                if ($storeorder['balance'] < 0) {
                    return getReturn(-19, '编号为' . $storeorder['storeid'] . '的余额不能为负数');
                }
            }

            $goods_array = $storeorder['order_content'];
            $thirdpart_limit_money = 0;
            if (count($goods_array) <= 0) return getReturn(-20, "订单商品数量不能为0");
            for ($j = 0; $j < count($goods_array); $j++) {
                $goods_bean = $goods_array[$j];
                if ($goods_bean['gou_num'] <= 0) return getReturn(-21, "商品数量不能为0");
                $goods_amount += $goods_bean['goods_price'] * $goods_bean['gou_num'];

                if ($goods_bean['thirdpart_money_limit'] == -1) {
                    if (empty($goods_bean['coupons_exmoney'])) {
                        $goods_bean['coupons_exmoney'] = 0;
                    }
                    if (empty($goods_bean['platform_coupons_money'])) {
                        $goods_bean['platform_coupons_money'] = 0;
                    }
                    $thirdpart_limit_money += ($goods_bean['goods_price'] * $goods_bean['gou_num']
                        - $goods_bean['coupons_exmoney'] - $goods_bean['platform_coupons_money']);
                } else {
                    if ($goods_bean['thirdpart_money_limit'] * $goods_bean['gou_num'] > ($goods_bean['goods_price'] * $goods_bean['gou_num']
                            - $goods_bean['coupons_exmoney'] - $goods_bean['platform_coupons_money'])
                    ) {
                        $thirdpart_limit_money += ($goods_bean['goods_price'] * $goods_bean['gou_num']
                            - $goods_bean['coupons_exmoney'] - $goods_bean['platform_coupons_money']);
                    } else {
                        $thirdpart_limit_money += $goods_bean['thirdpart_money_limit'] * $goods_bean['gou_num'];
                    }
                }
            }


            if (!empty($mallstore_order_array['thirdpart_data']) && $mallstore_order_array['thirdpart_data']['exchange_postage'] == 1) {
                if (empty($storeorder['postage_platform_coupons_money'])) {
                    $storeorder['postage_platform_coupons_money'] = 0;
                }
                $thirdpart_limit_money += ($storeorder['postage'] - $storeorder['postage_platform_coupons_money']);
            }
            $mall_thirdpart_limit_money += $thirdpart_limit_money;

            $mallstore_order_array['mergeorder_content'][$i]['thirdpart_order_money'] = $thirdpart_limit_money;

            $total_amount += $goods_amount + $storeorder['postage'];
            $integral_money += $storeorder['balance'] + $storeorder['mj_price'] + $storeorder['credits_exmoney'];

            if ($storeorder['gou_type'] == 1) {
                $integral_money += $storeorder['coupons_exmoney'];
            }
            $mallstore_order_array['mergeorder_content'][$i]['total_amount'] = $total_amount;
            $mallstore_order_array['mergeorder_content'][$i]['goods_amount'] = $goods_amount;
            $mallstore_order_array['mergeorder_content'][$i]['integral_money'] = $integral_money;

            $mall_integral_money += $integral_money;
            $mall_total_amount += $total_amount;
            $tempValue = $storeorder['totalprice'] + $integral_money;
            if (abs($total_amount - $tempValue) > EPSILON)
                return getReturn(-22, $storeorder['totalprice'] . "--" . $integral_money . "子订单总额不一致,请稍后重试!tempValue:" . $tempValue . "-total_amount:" . $total_amount);

            $oneStoreData = M("store")->where(array('store_id' => $storeorder['storeid']))->find();
            //可以在此处初始化订单数组缺少的值
            $mallstore_order_array['mergeorder_content'][$i]['credit_percent'] = $oneStoreData['credit_percent'];
            $mallstore_order_array['mergeorder_content'][$i]['credit_limit_money'] = $oneStoreData['credit_limit_money'];
            $mallstore_order_array['mergeorder_content'][$i]['credit_to_money'] = $oneStoreData['credit_to_money'];
            if ($storeorder['credits_num'] > 0) {
                if ($storeorder['credits_num'] > $storemember_one_data['sum_score']) {
                    return getReturn(-20, '编号为' . $storeorder['storeid'] . '的积分不足,请充值');
                }

                if ($storeorder['credits_exmoney'] > round($storeorder['credits_num'] / $oneStoreData['credit_to_money'], 2)) {
                    return getReturn(-21, '店铺积分抵扣有误');
                }

                if ($total_amount < $oneStoreData['credit_limit_money']) {
                    return getReturn(-22, '订单金额小于积分可使用金额');
                }

                if ($storeorder['credits_exmoney'] > $total_amount * $oneStoreData['credit_percent']) {
                    return getReturn(-22, '积分抵用金额超过订单可使用比例');
                }
            }

        }
        $mallstore_order_array['mall_thirdpart_order_money'] = $mall_thirdpart_limit_money;
        $tempValue = $mall_total_amount - $mall_integral_money;
        if (abs($mall_order_price - $tempValue) > EPSILON)
            return getReturn(-1, $mall_total_amount . "商城订单总额不一致,请稍后重试!tempValue:" . $tempValue . "-mall_order_price:" . $mall_order_price);

        return getReturn(200, "检查成功");
    }


    /**
     * 检查优惠券是否正确使用
     * @param $mallstore_order_array
     * @return mixed
     * User: czx
     * Date: 2018/3/21 11:1:47
     * Update: 2018/3/21 11:1:47
     * Version: 1.00
     */
    public function checkOrderCouponsData(&$mallstore_order_array)
    {

        $storeorder_array = $mallstore_order_array['mergeorder_content'];
        for ($i = 0; $i < count($storeorder_array); $i++) {
            $storeorder = $storeorder_array[$i];

            if ($storeorder['gou_type'] == 1) {

                $coupons_data = Model('mb_membercoupons')->where(array(
                    'id' => $storeorder['coupons_id'],
                    'isdelete' => '0'
                ))->find();
                $mallstore_order_array['mergeorder_content'][$i]['gou_info'] = $coupons_data;
                if (!empty($coupons_data)) {
                    if ($coupons_data['state'] == 1) {
                        return getReturn(-1, $coupons_data['coupons_name'] . '已使用,请刷新首页');
                    }

                    if ($coupons_data['limit_time_type'] == 2) {
                        if ($coupons_data['end_time'] < TIMESTAMP) {
                            return getReturn(-2, $coupons_data['coupons_name'] . '已过期,请刷新首页');
                        }
                    } else if ($coupons_data['limit_time_type'] == 3) {
                        if ($coupons_data['limit_start_time'] > TIMESTAMP || $coupons_data['limit_end_time'] < TIMESTAMP) {
                            return getReturn(-3, $coupons_data['coupons_name'] . '未在规定的时间内使用,请刷新首页');
                        }
                    }
                } else {
                    return getReturn(-4, "暂未找到该订单使用的优惠券信息");
                }
                // 优惠券的分类
                $mclass = $this->getCouponsClass($coupons_data);
                $coupons_data['mclass'] = $mclass;

                $coupons_goods_price = 0;  //能使用优惠券的商品总价
                $goods_array = $storeorder['order_content'];
                for ($j = 0; $j < count($goods_array); $j++) {
                    $goods_bean = $goods_array[$j];
                    if ($this->checkGoodsUseCoupons($goods_bean, $coupons_data)) {
                        $coupons_goods_price += ($goods_bean['goods_price'] * $goods_bean['gou_num']
                            - $goods_bean['mj_bean_price'] - $goods_bean['store_reduce_price']
                            - $goods_bean['store_discount_price']);
                    }
                }

                if ($coupons_goods_price < $coupons_data['limit_money'] && $coupons_data['limit_money'] > 0) {
                    return getReturn(-5, $coupons_goods_price . "--" . $coupons_data['coupons_name'] . '不能使用,购买金额未满' .
                        $coupons_data['limit_money'] . ",error:107--" . $coupons_goods_price);
                }

                if ($storeorder['coupons_exmoney'] < 0) {
                    return getReturn(-6, "优惠券抵用不能为负数,error:108");
                }

                if ($coupons_data['coupons_type'] == 2) {
                    $coupons_data['coupons_money'] = $coupons_goods_price * $coupons_data['coupons_discount'];
                    $coupons_goods_price = $coupons_goods_price * $coupons_data['coupons_discount'];

                }

                if ($storeorder['coupons_exmoney'] - $coupons_data['coupons_money'] > 0.01) {
                    return getReturn(-6, $storeorder['coupons_exmoney'] . "优惠券抵用金额超过优惠券金额,error:108" . "--" . $coupons_data['coupons_money']);
                }

                if ($storeorder['coupons_exmoney'] - $coupons_goods_price > 0.01) {
                    return getReturn(-7, $storeorder['coupons_exmoney'] . "优惠券抵用金额超过可使用优惠券商品总金额,error:108" . "--" . $coupons_goods_price);
                }
                $mallstore_order_array['mergeorder_content'][$i]['use_coupons'] = 1;
            }

        }

        if ($mallstore_order_array['use_platform_coupons'] == 1) {
            $platform_coupons_data = Model('mb_membercoupons')->where(array(
                'id' => $mallstore_order_array['platform_coupons_id'],
                'isdelete' => '0'
            ))->find();
            if (!empty($platform_coupons_data)) {
                if ($platform_coupons_data['state'] == 1) {
                    return getReturn(-8, $platform_coupons_data['coupons_name'] . '已使用,请刷新首页,error:104');
                }
                if ($platform_coupons_data['limit_time_type'] == 2) {
                    if ($platform_coupons_data['end_time'] < TIMESTAMP) {
                        return getReturn(-9, $platform_coupons_data['coupons_name'] . '已过期,请刷新首页,error:104');
                    }
                } else if ($platform_coupons_data['limit_time_type'] == 3) {
                    if ($platform_coupons_data['limit_start_time'] > TIMESTAMP || $platform_coupons_data['limit_end_time'] < TIMESTAMP) {
                        return getReturn(-10, $platform_coupons_data['coupons_name'] . '未在规定的时间内使用,请刷新首页,error:104');
                    }
                }
            } else {
                return getReturn(-11, "未找到此平台优惠券,请刷新重试");
            }

            if ($platform_coupons_data['coupons_money'] < $mallstore_order_array['platform_coupons_money']) {
                return getReturn(-12, '平台优惠券使用金额大于优惠券可使用金额');
            }
            $mallstore_pcoupons_price = 0;
            for ($i = 0; $i < count($mallstore_order_array['mergeorder_content']); $i++) {
                $one_store_order = $mallstore_order_array['mergeorder_content'][$i];
                $store_pcoupons_price = $this->getStorePlatformCouponsPrice($one_store_order, $platform_coupons_data);
                $store_pcoupons_price += $one_store_order['postage'];
                $mallstore_order_array['mergeorder_content'][$i]['store_pcoupons_price'] = $store_pcoupons_price;
                $mallstore_pcoupons_price = $mallstore_pcoupons_price + $store_pcoupons_price;
            }
            $mallstore_order_array['mallstore_pcoupons_price'] = $mallstore_pcoupons_price;

            if ($mallstore_order_array['mallstore_pcoupons_price'] < $platform_coupons_data['limit_money'] && $platform_coupons_data['limit_money'] > 0) {

                return getReturn(-12, $platform_coupons_data['coupons_name'] . '不能使用,平台购买金额未满' .
                    $platform_coupons_data['limit_money'] . "-mall_order_price:" . $mallstore_order_array['mallstore_pcoupons_price']);
            }
            $mallstore_order_array['platform_coupons_content'] = $platform_coupons_data;
        }
        return getReturn(200, "检查通过");

    }

    /**
     * 检查满减活动
     * @param $mallstore_order_array
     * @return mixed
     * User: czx
     * Date: 2018/3/21 11:1:47
     * Update: 2018/3/21 11:1:47
     * Version: 1.00
     */
    public function checkMjData(&$mallstore_order_array)
    {
        $storeorder_array = $mallstore_order_array['mergeorder_content'];
        for ($ii = 0; $ii < count($storeorder_array); $ii++) {
            $storeorder = $storeorder_array[$ii];
            $goods_array = $storeorder['order_content'];
            $store_mj_array = [];
            for ($j = 0; $j < count($goods_array); $j++) {
                $goods_bean = $goods_array[$j];
                if ($goods_bean['mj_id'] > 0) {
                    $item_i = -1;
                    for ($k = 0; $k < count($store_mj_array); $k++) {
                        if ($store_mj_array[$k]['mj_id'] == $goods_bean['mj_id']) {
                            $item_i = $k;
                            break;
                        }
                    }
                    if ($item_i < 0) {
                        $store_mj_array[] = ['mj_id' => $goods_bean['mj_id'],
                            'mj_level' => $goods_bean['mj_level'],
                            'total_price' => $goods_bean['goods_price'] * $goods_bean['gou_num'],
                            'total_num' => $goods_bean['gou_num']
                        ];
                    } else {
                        $store_mj_array[$item_i]['total_price'] = $store_mj_array[$item_i]['total_price'] +
                            $goods_bean['goods_price'] * $goods_bean['gou_num'];
                        $store_mj_array[$item_i]['total_num'] = $store_mj_array[$item_i]['total_num'] + $goods_bean['gou_num'];
                    }

                }
            }

            $store_mj_price = 0;
            for ($i = 0; $i < count($store_mj_array); $i++) {
                $mj_activity = Model('mb_mj_activity')->where(array('mj_id' => $store_mj_array[$i]['mj_id']))->find();
                if ($mj_activity['mj_type'] == 1) {
                    $mj_rule = json_decode($mj_activity['mj_rule'], true);
                    for ($j = 0; $j < count($mj_rule); $j++) {
                        if ($mj_rule[$j]['level'] == $store_mj_array[$i]['mj_level']) {
                            if ($store_mj_array[$i]['total_price'] >= $mj_rule[$j]['limit']) {
                                $store_mj_price = $store_mj_price + $mj_rule[$j]['discounts'];
                            }
                            break;
                        }
                    }
                } else if ($mj_activity['mj_type'] == 2) {
                    $mj_rule = json_decode($mj_activity['mj_rule'], true);
                    for ($j = 0; $j < count($mj_rule); $j++) {
                        if ($mj_rule[$j]['level'] == $store_mj_array[$i]['mj_level']) {
                            if ($store_mj_array[$i]['total_num'] >= $mj_rule[$j]['limit']) {
                                $store_mj_price = $store_mj_price + (1 - $mj_rule[$j]['discounts']) * $store_mj_array[$i]['total_price'];
                            }
                            break;
                        }
                    }

                } else {
                    $mj_rule = json_decode($mj_activity['mj_rule'], true);
                    $mj_discount = intval($store_mj_array[$i]['total_price'] / $mj_rule['limit']);
                    if ($mj_rule['is_top'] == 1) {
                        if ($mj_rule['dis_num'] < $mj_discount) {
                            $mj_discount = $mj_rule['dis_num'];
                        }
                    }
                    $store_mj_price = $store_mj_price + $mj_discount * $mj_rule['discounts'];
                }
            }

            if (empty($storeorder['mj_price'])) {
                $storeorder['mj_price'] = 0;
            }
            if (abs(round($store_mj_price, 2) - $storeorder['mj_price']) > EPSILON) {
                return getReturn(-1, $store_mj_price . '店铺满减活动计算有误' . $storeorder['mj_price']);
            }
            if (empty($mallstore_order_array['mergeorder_content'][$ii]['mj_price'])) {
                $mallstore_order_array['mergeorder_content'][$ii]['mj_price'] = 0;
            }

            for ($i = 0; $i < count($goods_array); $i++) {
                $goodsbean = $goods_array[$i];
                $mj_bean_price = 0;
                if ($goodsbean['mj_id'] > 0) {
                    for ($j = 0; $j < count($store_mj_array); $j++) {
                        if ($goodsbean['mj_id'] == $store_mj_array[$j]['mj_id']) {
                            $mj_bean_price = ($goodsbean['goods_price'] * $goodsbean['gou_num'] / $store_mj_array[$j]['total_price'])
                                * $store_mj_price;
                        }
                    }
                }
                $mallstore_order_array['mergeorder_content'][$ii]['order_content'][$i]['mj_bean_price'] = $mj_bean_price;
            }

            $mallstore_order_array['mergeorder_content'][$ii]['store_mj_array'] = $store_mj_array;

        }
        return getReturn(200, "检查通过");
    }

    public function checkPayType($client, $storeId, $app_name, $pay_type)
    {

        $payMode = $this->commonOrderApi->getPayMode($client, $storeId, $app_name);
        if ($pay_type == 0) {
            if ($payMode['cashpay'] == 0) {
                return getReturn(-1, '暂不支持货到付款');
            }
        } else if ($pay_type == 1) {
            if ($payMode['wxpay'] == 0) {
                return getReturn(-2, '暂不支持微信支付');
            }
        } else if ($pay_type == 4) {
            if ($payMode['alipay'] == 0) {
                return getReturn(-3, '暂不支持支付宝支付');
            }
        }
        return getReturn(200, '检查通过');
    }

    public function checkFreight(&$mallstore_order_array, $latitude, $longitude, $addressId)
    {

        for ($i = 0; $i < count($mallstore_order_array['mergeorder_content']); $i++) {
            $storeshopdata_one = $mallstore_order_array['mergeorder_content'][$i];
            $storeDataOne = M("store")->where(array('store_id' => $storeshopdata_one['storeid']))->find();
            $mallstore_order_array['mergeorder_content'][$i]['store_name'] = $storeDataOne['store_name'];
            $addressData = array();
            if (!empty($addressId)) $addressData = D("Address")->getAddressInfo($addressId)['data'];
            $mallstore_order_array['addressData'] = $addressData;
            if (empty($storeInfoOne)) getReturn(-1, "计算运费商家不存在");
            $isLocal = $this->commonOrderApi->getLocalValue($storeDataOne, $addressData);
            if (empty($storeshopdata_one['pickup_id'])) {

                if ($isLocal == 1) {  //同城方式计算
                    $returnData = $this->commonOrderApi->getLocalFreight($storeshopdata_one, $storeDataOne, $latitude, $longitude);


                } else {
                    $returnData = $this->commonOrderApi->getLongRangeFreight($storeshopdata_one, $storeDataOne, $addressData, $latitude, $longitude);
                }

                if ($storeshopdata_one['postage'] != $returnData['freight']) {
                    return getReturn(-1, $storeshopdata_one['postage'] . "订单运费计算有误" . $returnData['freight']);
                }

                if ($returnData['canBuy'] == 0) {
                    return getReturn(-2, $storeshopdata_one['store_name'] . "商家" . $returnData['sendmoney'] . "元起送");
                }

            }
        }
        return getReturn(200, "成功");

    }


    public function getStorePlatformCouponsPrice($storeorder, $one_coupons)
    {

        $store_goods_price = 0;
        for ($t = 0; $t < count($storeorder['order_content']); $t++) {
            $goods_bean = $storeorder['order_content'][$t];
            if ($this->checkGoodsUseCoupons($goods_bean, $one_coupons)) {
                $store_goods_price = $store_goods_price + $goods_bean['goods_price'] * $goods_bean['gou_num'] - $goods_bean['mj_bean_price'];
            }
        }
        return $store_goods_price;
    }


    public function DistributionOrderGoodsData(&$mallstore_order_array)
    {

        $storeorder_array = $mallstore_order_array['mergeorder_content'];
        for ($i = 0; $i < count($storeorder_array); $i++) {
            $storeorder = $storeorder_array[$i];

            $store_totalprice = $storeorder['totalprice'] + $storeorder['platform_balance']
                + $storeorder['platform_coupons_money'] + $storeorder['platform_credits_exmoney']
                + $storeorder['thirdpart_money'] + $storeorder['coupons_exmoney'] + $storeorder['balance']
                + $storeorder['credits_exmoney'] + $storeorder['mj_price']
                + $mallstore_order_array['store_discount_amount'] + $mallstore_order_array['store_reduce_money'];

            $use_postage = empty($storeorder['postage_platform_coupons_money']) ? 0:$storeorder['postage_platform_coupons_money'];
            if (empty($use_postage)) $use_postage = 0;
            $order_pv = 0;
            /************计算可用优惠券商品的总金额---start---*****/
            //$this->distribution_goods_exchange($storeorder, "coupons_exmoney", "coupons_exmoney",
            //   self::COUPONS, 0, $use_postage);
            /************计算可用优惠券商品的总金额---end---*****/

            /************计算平台优惠券---start---*****/
            //$this->distribution_goods_exchange($storeorder, "platform_coupons_money", "platform_coupons_money",
            //   self::PLATFORM_COUPONS, 1, $use_postage);
            /************计算平台优惠券---end---*****/


            /************计算自定义折扣---start---*****/
            $this->distribution_goods_exchange($storeorder, "store_discount_amount", "store_discount_amount",
                self::CASH_STORE_DISCOUNT_AMOUNT, 0, $use_postage);

            /************计算自定义折扣---end---*****/

            /************计算自定义减免---start---*****/
            $this->distribution_goods_exchange($storeorder, "store_reduce_money", "store_reduce_money",
                self::CASH_STORE_REDUCE_MONEY, 0, $use_postage);

            /************计算自定义减免---end---*****/


            /************计算第三方余额---start---*****/
            $this->distribution_goods_exchange($storeorder, "thirdpart_money", "thirdpart_money",
                self::THIRPART_MONEY, 1, $use_postage);

            /************计算第三方余额---end---*****/


            /************计算余额---start---*****/
            $this->distribution_goods_exchange($storeorder, "balance", "balance",
                self::BALNCE, 1, $use_postage);
            /************计算余额---end---*****/


            /************计算平台积分---start---*****/
            $this->distribution_goods_exchange($storeorder, "platform_credits_exmoney", "platform_credits_exmoney",
                self::PLATFORM_CREDITS, 0, $use_postage);
            /************计算平台积分---end---*****/

            /************计算积分---start---*****/
            $this->distribution_goods_exchange($storeorder, "credits_exmoney", "credits_exmoney",
                self::CREDITS, 0, $use_postage);
            /************计算积分---end---*****/

            $mallstore_order_array['mergeorder_content'][$i] = $storeorder;
            $goods_array = $storeorder['order_content'];
            for ($j = 0; $j < count($goods_array); $j++) {
                $goods_bean = $goods_array[$j];
                $goods_original_price = $this->get_original_price($goods_bean, $storeorder['storeid']);
                $goods_bean['original_price'] = $goods_original_price;
                // hjun 2017-07-11 14:23:15  最低佣金比例
                $dataInfo = Model('store')->where(array('store_id' => $storeorder['storeid']))->find();
                $channelInfo = Model('mb_channel')->where(array('channel_id' => $dataInfo['channel_id']))->find();
                $min_commission = $dataInfo['min_commission'];

                if ($min_commission == 0) {
                    $min_commission = $channelInfo['min_commission'];
                }

                //获取商品标签
                $goods_tag_link_datas = Model("goods_tag_link")->where(array('goods_id' => $goods_bean['goods_id']))
                    ->select();
                $tag_link_arr = array();
                foreach ($goods_tag_link_datas as $key => $val) {
                    $tag_link_arr[] = $val['tag_id'];
                }

                $goods_bean['tag_link'] = $tag_link_arr;

                //每个商品的实际pv ＝ 商品售价 － 成本 －店内优惠
                if ($channelInfo['pv_calculate_balance'] == 1) {
                    $goods_pv = ($goods_bean['goods_price'] - $goods_original_price
                        - $goods_bean['credits_exmoney'] / $goods_bean['gou_num']
                        - $goods_bean['balance'] / $goods_bean['gou_num']
                        - $goods_bean['coupons_exmoney'] / $goods_bean['gou_num']
                        - $goods_bean['mj_bean_price'] / $goods_bean['gou_num']);
                } else {
                    $goods_pv = ($goods_bean['goods_price'] - $goods_original_price
                        - $goods_bean['credits_exmoney'] / $goods_bean['gou_num']
                        - $goods_bean['coupons_exmoney'] / $goods_bean['gou_num'] -
                        $goods_bean['mj_bean_price'] / $goods_bean['gou_num']);
                }
                if ($goods_pv < 0) {
                    $goods_pv = 0;
                }


                if ((double)$min_commission > 0) {
                    if ($channelInfo['pv_calculate_balance'] == 1) {
                        $commission_pv = ($goods_bean['goods_price']
                                - $goods_bean['coupons_exmoney'] / $goods_bean['gou_num']
                                - $goods_bean['balance'] / $goods_bean['gou_num']
                                - $goods_bean['credits_exmoney'] / $goods_bean['gou_num']
                                - $goods_bean['mj_bean_price'] / $goods_bean['gou_num']) * (double)$min_commission;
                    } else {
                        $commission_pv = ($goods_bean['goods_price']
                                - $goods_bean['coupons_exmoney'] / $goods_bean['gou_num']
                                - $goods_bean['credits_exmoney'] / $goods_bean['gou_num']
                                - $goods_bean['mj_bean_price'] / $goods_bean['gou_num']) * (double)$min_commission;
                    }

                    if ($goods_pv < $commission_pv) {
                        $goods_pv = $commission_pv;
                    }
                }
                $goods_bean['pv'] = $goods_pv;
                $goods_bean['refund'] = 0;
                $order_pv = $order_pv + $goods_bean['pv'] * $goods_bean['gou_num'];

                $mallstore_order_array['mergeorder_content'][$i]['order_content'][$j] = $goods_bean;

            }
            $mallstore_order_array['mergeorder_content'][$i]['order_pv'] = $order_pv;
            $mallstore_order_array['mergeorder_content'][$i]['order_amount'] = $store_totalprice;
        }
        return getReturn(200, "分配商品金额成功");
    }


    public function distribution_goods_exchange(&$storeOrder, $storeKeyName, $beanKeyName, $exchangeType, $calulatePostage, &$usePostage)
    {

        $goods_array = $storeOrder['order_content'];
        $exchangeGoodsPriceSum = 0;
        $useExchangeGoodsPriceSum = 0;
//        if ($exchangeType == self::PLATFORM_COUPONS) { //平台优惠券
//
//            if ($storeOrder['use_platform_coupons'] == 1 && $storeOrder['platform_coupons_money'] > 0) {
//                $platform_coupons_data = Model('mb_membercoupons')->where(array(
//                    'id' => $storeOrder['platform_coupons_id'],
//                    'isdelete' => '0'
//                ))->find();
//                $mclass = $this->getCouponsClass($platform_coupons_data);
//                $platform_coupons_data['mclass'] = $mclass;
//
//                for ($j = 0; $j < count($goods_array); $j++) {
//                    $one_bean = $goods_array[$j];
//                    if ($this->checkGoodsUseCoupons($one_bean, $platform_coupons_data)) {
//                        $exchangeGoodsPriceSum += $this->commonOrderApi->getRestPrice($one_bean) * $one_bean['gou_num'];
//                    }
//                }
//            }
//            if ($calulatePostage == 1) {
//                $exchangeGoodsPriceSum += ($storeOrder['postage'] - $usePostage);
//            }
//            for ($j = 0; $j < count($goods_array); $j++) {
//                $goods_bean = $goods_array[$j];
//                if ($storeOrder['use_platform_coupons'] == 1 && $storeOrder['platform_coupons_money'] > 0) {
//                    if ($this->checkGoodsUseCoupons($goods_bean, $platform_coupons_data)) {
//                        $oneValue = $this->commonOrderApi->getRestPrice($goods_bean);
//
//                        $goods_bean[$beanKeyName] = $this->commonOrderApi->getScaleValue($oneValue,
//                            $exchangeGoodsPriceSum, $storeOrder[$storeKeyName], $goods_bean['gou_num']);
//
//                        $useExchangeGoodsPriceSum += ($goods_bean[$beanKeyName] * $goods_bean['gou_num']);
//                    } else {
//                        $goods_bean[$beanKeyName] = 0;
//                    }
//                } else {
//                    $goods_bean[$beanKeyName] = 0;
//                }
//                $storeOrder['order_content'][$j] = $goods_bean;
//            }
//        } else if ($exchangeType == self::COUPONS) {
//            if ($storeOrder['gou_type'] == 1) {
//                $coupons_data = Model('mb_membercoupons')->where(array(
//                    'id' => $storeOrder['gou_info']['id'],
//                    'isdelete' => '0'
//                ))->find();
//                $mclass = $this->getCouponsClass($coupons_data);
//                $coupons_data['mclass'] = $mclass;
//                for ($j = 0; $j < count($goods_array); $j++) {
//                    $goods_bean = $goods_array[$j];
//                    if ($this->checkGoodsUseCoupons($goods_bean, $coupons_data)) {
//                        $exchangeGoodsPriceSum += $this->commonOrderApi->getRestPrice($goods_bean) * $goods_bean['gou_num'];
//                    }
//                }
//            }
//            for ($j = 0; $j < count($goods_array); $j++) {
//                if ($storeOrder['gou_type'] == 1) {
//                    if ($this->checkGoodsUseCoupons($goods_bean, $coupons_data)) {
//                        $oneValue = $this->commonOrderApi->getRestPrice($goods_bean);
//
//                        $goods_bean[$beanKeyName] = $this->commonOrderApi->getScaleValue($oneValue,
//                            $exchangeGoodsPriceSum, $storeOrder[$storeKeyName], $goods_bean['gou_num']);
//
//                        $useExchangeGoodsPriceSum += ($goods_bean[$beanKeyName] * $goods_bean['gou_num']);
//                    } else {
//                        $goods_bean[$beanKeyName] = 0;
//                    }
//                } else {
//                    $goods_bean[$beanKeyName] = 0;
//                }
//                $storeOrder['order_content'][$j] = $goods_bean;
//            }
//
//        }
        if ($exchangeType == self::THIRPART_MONEY) {
            if ($storeOrder['thirdpart_money'] > 0) {
                for ($j = 0; $j < count($goods_array); $j++) {
                    $goods_bean = $goods_array[$j];
                    if ($goods_bean['thirdpart_money_limit'] == -1) {
                        $exchangeGoodsPriceSum += $this->commonOrderApi->getCashGoodsRestPrice($goods_bean);
                    } else {
                        if (($goods_bean['thirdpart_money_limit'] * $goods_bean['gou_num']) > $this->commonOrderApi->getCashGoodsRestPrice($goods_bean)) {
                            $exchangeGoodsPriceSum += $this->commonOrderApi->getCashGoodsRestPrice($goods_bean);
                        } else {
                            $exchangeGoodsPriceSum += $goods_bean['thirdpart_money_limit'] * $goods_bean['gou_num'];
                        }
                    }
                }
            }
            if ($calulatePostage == 1) {
                $exchangeGoodsPriceSum += ($storeOrder['postage'] - $usePostage);
            }
            for ($j = 0; $j < count($goods_array); $j++) {
                $goods_bean = $goods_array[$j];
                if ($storeOrder['thirdpart_money'] > 0) {
                    $oneValue = $this->commonOrderApi->getCashGoodsRestPrice($goods_bean);
                    if ($goods_bean['thirdpart_money_limit'] != -1) {
                        if (($goods_bean['thirdpart_money_limit'] * $goods_bean['gou_num']) < $oneValue) {
                            $oneValue = $goods_bean['thirdpart_money_limit'] * $goods_bean['gou_num'];
                        }
                    }

                    $goods_bean[$beanKeyName] = $this->commonOrderApi->getScaleValue($oneValue,
                        $exchangeGoodsPriceSum, $storeOrder[$storeKeyName], $goods_bean['gou_num']);

                    $useExchangeGoodsPriceSum += $goods_bean[$beanKeyName];
                } else {
                    $goods_bean[$beanKeyName] = 0;
                }
                $storeOrder['order_content'][$j] = $goods_bean;
            }


        } else if ($exchangeType == self::BALNCE) {
            if ($storeOrder['balance'] > 0) {
                for ($j = 0; $j < count($goods_array); $j++) {
                    $goods_bean = $goods_array[$j];
                    if ($goods_bean['balance_limit'] == -1) {
                        $exchangeGoodsPriceSum += $this->commonOrderApi->getCashGoodsRestPrice($goods_bean);
                    } else {
                        if (($goods_bean['balance_limit'] * $goods_bean['gou_num']) > $this->commonOrderApi->getCashGoodsRestPrice($goods_bean)) {
                            $exchangeGoodsPriceSum += $this->commonOrderApi->getCashGoodsRestPrice($goods_bean);
                        } else {
                            $exchangeGoodsPriceSum += $goods_bean['balance_limit'] * $goods_bean['gou_num'];
                        }
                    }
                }
            }
            if ($calulatePostage == 1) {
                $exchangeGoodsPriceSum += ($storeOrder['postage'] - $usePostage);
            }

            for ($j = 0; $j < count($goods_array); $j++) {
                $goods_bean = $goods_array[$j];
                if ($storeOrder['balance'] > 0) {
                    $oneValue = $this->commonOrderApi->getCashGoodsRestPrice($goods_bean);
                    if ($goods_bean['balance_limit'] != -1) {
                        if (($goods_bean['balance_limit'] * $goods_bean['gou_num']) < $oneValue) {
                            $oneValue = $goods_bean['balance_limit'] * $goods_bean['gou_num'];
                        }
                    }

                    $goods_bean[$beanKeyName] = $this->commonOrderApi->getScaleValue($oneValue,
                        $exchangeGoodsPriceSum, $storeOrder[$storeKeyName], $goods_bean['gou_num']);

                    $useExchangeGoodsPriceSum += ($goods_bean[$beanKeyName] * $goods_bean['gou_num']);
                } else {
                    $goods_bean[$beanKeyName] = 0;
                }
                $storeOrder['order_content'][$j] = $goods_bean;
            }
        } else {

            if ($storeOrder[$storeKeyName] > 0) {
                for ($j = 0; $j < count($goods_array); $j++) {
                    $goods_bean = $goods_array[$j];

                    $exchangeGoodsPriceSum += $this->commonOrderApi->getCashGoodsRestPrice($goods_bean);
                }
            }
            if ($calulatePostage == 1) {
                $exchangeGoodsPriceSum += ($storeOrder['postage'] - $usePostage);
            }

            for ($j = 0; $j < count($goods_array); $j++) {
                $goods_bean = $goods_array[$j];
                if ($storeOrder[$storeKeyName] > 0) {

                    $oneValue = $this->commonOrderApi->getCashGoodsRestPrice($goods_bean);

                    $goods_bean[$beanKeyName] = $this->commonOrderApi->getScaleValue($oneValue,
                        $exchangeGoodsPriceSum, $storeOrder[$storeKeyName], $goods_bean['gou_num']);

                    $useExchangeGoodsPriceSum += ($goods_bean[$beanKeyName] * $goods_bean['gou_num']);
                } else {
                    $goods_bean[$beanKeyName] = 0;
                }
                $storeOrder['order_content'][$j] = $goods_bean;
            }

        }

        if ($calulatePostage == 1) {
            $use_postage_str = 'postage_' . $storeKeyName;
            $storeOrder[$use_postage_str] = round(($storeOrder['postage'] - $usePostage) / $exchangeGoodsPriceSum
                * $storeOrder[$storeKeyName], 2);
            $usePostage += round(($storeOrder['postage'] - $usePostage) / $exchangeGoodsPriceSum
                * $storeOrder[$storeKeyName], 2);
        }

    }

    public function gouWuReduceCredits($credits_type, $ordermoney, $member_id, $store_id)
    {
        $type = Model('mb_credits_type');
        $numitem = $type->where(array(
            'type_id' => $credits_type
        ))->find();
        $credits_name = $numitem['name'];
        $score = $ordermoney;
        $meberitem = Model('member')->field('member_name')->where(array(
            'member_id' => $member_id
        ))->find();
        $member_name = $meberitem['member_name'];
        $data = $this->commonOrderApi->changeCredit($store_id, $member_id, $member_name, $credits_type, $credits_name, $score, null);
        //输出信息
        return $data;
    }

    public function initConfirmData(&$confirmData)
    {
        if(empty($confirmData['storeInfoArray'][0]['store_discount_amount'])){
            $confirmData['storeInfoArray'][0]['store_discount_amount'] = 0;
        }

        if (empty($confirmData['storeInfoArray'][0]['store_reduce_money'])){
            $confirmData['storeInfoArray'][0]['store_reduce_money'] = 0;
        }

        $confirmData['temp_total_amount'] = $confirmData['total_amount']
            - $confirmData['storeInfoArray'][0]['store_discount_amount']
            - $confirmData['storeInfoArray'][0]['store_reduce_money'];
//        return getReturn(-1, "优惠券编号不能为空". $confirmData['temp_total_amount']);
        $confirmData['rm_thirdpart_money'] = 0;
        $confirmData['rm_platform_credit_price'] = 0;
        $confirmData['rm_platform_credit_num'] = 0;
        $confirmData['rm_platform_balance'] = 0;
        $confirmData['platform_coupons_money'] = 0;
        for ($i = 0; $i < count($confirmData['storeInfoArray']); $i++) {
            $confirmData['storeInfoArray'][$i]['freight']['platform_coupons_money'] = 0;
            $confirmData['storeInfoArray'][$i]['freight']['postage_thirdpart_money'] = 0;
            $confirmData['storeInfoArray'][$i]['freight']['postage_balance'] = 0;
            if ($i == 0){
                $confirmData['storeInfoArray'][$i]['temp_total_amount'] = $confirmData['storeInfoArray'][$i]['total_amount']
                    - $confirmData['storeInfoArray'][0]['store_discount_amount']
                    - $confirmData['storeInfoArray'][0]['store_reduce_money'];
                $confirmData['storeInfoArray'][$i]['rest_total_amount'] = $confirmData['storeInfoArray'][$i]['total_amount']
                    - $confirmData['storeInfoArray'][0]['store_discount_amount']
                    - $confirmData['storeInfoArray'][0]['store_reduce_money'];
            }else{
                $confirmData['storeInfoArray'][$i]['temp_total_amount'] = $confirmData['storeInfoArray'][$i]['total_amount'];
                $confirmData['storeInfoArray'][$i]['rest_total_amount'] = $confirmData['storeInfoArray'][$i]['total_amount'];
            }


            $confirmData['storeInfoArray'][$i]['rm_balance'] = 0;
            $confirmData['storeInfoArray'][$i]['rm_credit_num'] = 0;
            $confirmData['storeInfoArray'][$i]['rm_credit_price'] = 0;
            $confirmData['storeInfoArray'][$i]['rm_platform_credit_num'] = 0;
            $confirmData['storeInfoArray'][$i]['rm_platform_credit_price'] = 0;
            $confirmData['storeInfoArray'][$i]['rm_platform_balance'] = 0;
            for ($j = 0; $j < count($confirmData['storeInfoArray'][$i]['order_content']); $j++) {
                $confirmData['storeInfoArray'][$i]['order_content'][$j]['coupons_exmoney'] = 0;
                $confirmData['storeInfoArray'][$i]['order_content'][$j]['platform_coupons_money'] = 0;
                $confirmData['storeInfoArray'][$i]['order_content'][$j]['thirdpart_money'] = 0;
                $confirmData['storeInfoArray'][$i]['order_content'][$j]['balance'] = 0;
                $confirmData['storeInfoArray'][$i]['order_content'][$j]['platform_credits_exmoney'] = 0;
                $confirmData['storeInfoArray'][$i]['order_content'][$j]['credits_exmoney'] = 0;
                $confirmData['storeInfoArray'][$i]['order_content'][$j]['platform_balance'] = 0;
                if (empty($confirmData['storeInfoArray'][$i]['order_content'][$j]['store_reduce_price'])){
                    $confirmData['storeInfoArray'][$i]['order_content'][$j]['store_reduce_price'] = 0;
                }

                if (empty($confirmData['storeInfoArray'][$i]['order_content'][$j]['store_discount_price'])){
                    $confirmData['storeInfoArray'][$i]['order_content'][$j]['store_discount_price'] = 0;
                }
            }

        }
    }

    public function getMaxCoupons(&$storeOrder, $couponsId)
    {
        if (empty($couponsId)) return getReturn(-1, "优惠券编号不能为空");
        $coupons_data = Model('mb_membercoupons')->where(array(
            'id' => $couponsId,
            'isdelete' => '0'
        ))->find();
        // 优惠券的分类
        $mclass = $this->getCouponsClass($coupons_data);
        $coupons_data['mclass'] = $mclass;
        $coupons_goods_price = 0;  //能使用优惠券的商品总价
        for ($i = 0; $i < count($storeOrder['order_content']); $i++) {
            $goods_bean = $storeOrder['order_content'][$i];
            if ($this->checkGoodsUseCoupons($goods_bean, $coupons_data)) {
                $coupons_goods_price += ($goods_bean['goods_price'] * $goods_bean['gou_num']
                    - $goods_bean['mj_bean_price'] - $goods_bean['store_reduce_price']
                    - $goods_bean['store_discount_price']);
            }
        }
        if ($coupons_goods_price < $coupons_data['limit_money'] && $coupons_data['limit_money'] > 0) {
            return getReturn(-2, $coupons_goods_price . "--" . $coupons_data['coupons_name'] . '不能使用,购买金额未满' .
                $coupons_data['limit_money'] . ",error:107--" . $coupons_goods_price);
//            $coupons_data['coupons_money'] = 0;
//            return getReturn(200, "success", $coupons_data);
        }
        if ($coupons_data['coupons_type'] == 2) {
            $coupons_data['coupons_money'] = $coupons_goods_price * (1 - $coupons_data['coupons_discount']);
        }

        if ($coupons_data['coupons_money'] > $coupons_goods_price) {
            $coupons_data['coupons_money'] = $coupons_goods_price;
        }
        $coupons_data['coupons_money'] = saveDownDecimal($coupons_data['coupons_money']);
        if ($coupons_data['coupons_money'] > 0) {
            for ($i = 0; $i < count($storeOrder['order_content']); $i++) {
                $goods_bean = $storeOrder['order_content'][$i];
                if ($this->checkGoodsUseCoupons($goods_bean, $coupons_data)) {
                    $storeOrder['order_content'][$i]['coupons_exmoney'] = round(($goods_bean['goods_price'] * $goods_bean['gou_num']
                            - $goods_bean['mj_bean_price'] - $goods_bean['store_reduce_price']
                            - $goods_bean['store_discount_price']) / $coupons_goods_price * $coupons_data['coupons_money'], 4);

                }

            }
        }
        return getReturn(200, "成功", $coupons_data);
    }

    public function getMaxCouponsConfirm($storeOrder, $coupons_data)
    {
        if (empty($coupons_data)) return getReturn(-1, "优惠券不能为空");
        $coupons_goods_price = 0;  //能使用优惠券的商品总价
        for ($i = 0; $i < count($storeOrder['order_content']); $i++) {
            $goods_bean = $storeOrder['order_content'][$i];
            if ($this->checkGoodsUseCoupons($goods_bean, $coupons_data)) {

                $coupons_goods_price += ($goods_bean['goods_price'] * $goods_bean['gou_num']
                    - $goods_bean['mj_bean_price'] - $goods_bean['store_reduce_price']
                    - $goods_bean['store_discount_price']);
            }
        }

        if ($coupons_goods_price < $coupons_data['limit_money'] && $coupons_data['limit_money'] > 0) {
            return getReturn(-2, $coupons_goods_price . "--" . $coupons_data['coupons_name'] . '不能使用,购买金额未满' .
                $coupons_data['limit_money'] . ",error:107--" . $coupons_goods_price);
//            $coupons_data['coupons_money'] = 0;
//            return getReturn(200, "success", $coupons_data);
        }
        if ($coupons_data['coupons_type'] == 2) {
            $coupons_data['coupons_money'] = $coupons_goods_price * (1 - $coupons_data['coupons_discount']);
        }

        if ($coupons_data['coupons_money'] > $coupons_goods_price) {
            $coupons_data['coupons_money'] = $coupons_goods_price;
        }
        $coupons_data['coupons_money'] = saveDownDecimal($coupons_data['coupons_money']);
        if ($coupons_data['coupons_money'] > 0) {
            for ($i = 0; $i < count($storeOrder['order_content']); $i++) {
                $goods_bean = $storeOrder['order_content'][$i];
                if ($this->checkGoodsUseCoupons($goods_bean, $coupons_data)) {
                    $storeOrder['order_content'][$i]['coupons_exmoney'] = round(($goods_bean['goods_price'] * $goods_bean['gou_num']
                            - $goods_bean['mj_bean_price'] - $goods_bean['store_reduce_price']
                            - $goods_bean['store_discount_price']) / $coupons_goods_price * $coupons_data['coupons_money'], 2);
                }

            }
        }

        return getReturn(200, "success", $coupons_data);
    }

    public function getMaxPlatformCoupons(&$storeArray, $couponsId)
    {
        if (empty($couponsId)) return getReturn(-1, "优惠券编号不能为空");
        $coupons_data = Model('mb_membercoupons')->where(array(
            'id' => $couponsId,
            'isdelete' => '0'
        ))->find();
        // 优惠券的分类
        $mclass = $this->getCouponsClass($coupons_data);
        $coupons_data['mclass'] = $mclass;
        $mallstore_pcoupons_price = 0;  //能使用优惠券的商品总价
        for ($i = 0; $i < count($storeArray); $i++) {
            $store_pcoupons_price = 0;
            for ($j = 0; $j < count($storeArray[$i]['order_content']); $j++) {
                $goods_bean = $storeArray[$i]['order_content'][$j];
                if ($this->checkGoodsUseCoupons($goods_bean, $coupons_data)) {
                    if (empty($goods_bean['coupons_exmoney']))
                        $goods_bean['coupons_exmoney'] = 0;
                    $store_pcoupons_price += ($goods_bean['goods_price'] * $goods_bean['gou_num']
                        - $goods_bean['mj_bean_price'] - $goods_bean['coupons_exmoney']
                        - $goods_bean['store_reduce_price']
                        - $goods_bean['store_discount_price']);
                }
            }
            $store_pcoupons_price += $storeArray[$i]['freight']['freight'];
            $storeArray[$i]['store_pcoupons_price'] = $store_pcoupons_price;
            $mallstore_pcoupons_price = $mallstore_pcoupons_price + $store_pcoupons_price;
        }
        if ($coupons_data['coupons_money'] > $mallstore_pcoupons_price) {
            $coupons_data['coupons_money'] = $mallstore_pcoupons_price;
        }
        $coupons_data['coupons_money'] = saveDownDecimal($coupons_data['coupons_money']);
        if ($coupons_data['coupons_money'] > 0) {
            for ($i = 0; $i < count($storeArray); $i++) {
                if ($mallstore_pcoupons_price == 0) {
                    $storeArray[$i]['platform_coupons_money'] = 0;
                } else {
                    $storeArray[$i]['platform_coupons_money'] = round($storeArray[$i]['store_pcoupons_price'] /
                        $mallstore_pcoupons_price * $coupons_data['coupons_money'], 2);

                    $storeArray[$i]['rest_total_amount'] = $storeArray[$i]['rest_total_amount'] - $storeArray[$i]['platform_coupons_money']
                        - $storeArray[$i]['store_reduce_money'] - $storeArray[$i]['store_discount_amount'];

                    if ($storeArray[$i]['rest_total_amount'] < 0) return getReturn(-1, "平台优惠券分配金额有误");
                }

                for ($j = 0; $j < count($storeArray[$i]['order_content']); $j++) {
                    $goods_bean = $storeArray[$i]['order_content'][$j];
                    if ($this->checkGoodsUseCoupons($goods_bean, $coupons_data)) {
                        if (empty($goods_bean['coupons_exmoney']))
                            $goods_bean['coupons_exmoney'] = 0;
                        $tempValue = $goods_bean['goods_price'] * $goods_bean['gou_num']
                            - $goods_bean['mj_bean_price'] - $goods_bean['coupons_exmoney']
                            - $goods_bean['store_reduce_price']
                            - $goods_bean['store_discount_price'];
                        if ($storeArray[$i]['store_pcoupons_price'] == 0) {
                            $storeArray[$i]['order_content'][$j]['platform_coupons_money'] = 0;
                        } else {
                            $storeArray[$i]['order_content'][$j]['platform_coupons_money'] = round($tempValue /
                                $storeArray[$i]['store_pcoupons_price'] * $storeArray[$i]['platform_coupons_money'], 4);
                        }
                    }
                    if ($storeArray[$i]['store_pcoupons_price'] == 0) {
                        $storeArray[$i]['freight']['platform_coupons_money'] = 0;
                    } else {
                        $storeArray[$i]['freight']['platform_coupons_money'] = round($storeArray[$i]['freight']['freight'] /
                            $storeArray[$i]['store_pcoupons_price'] * $storeArray[$i]['platform_coupons_money'], 4);
                    }

                }
            }
        }

        return getReturn(200, "成功", $coupons_data);
    }

    public function getMaxPlatformCouponsConfirm($storeArray, $coupons_data)
    {
        if (empty($coupons_data)) return getReturn(-1, "优惠券不能为空");
        $mallstore_pcoupons_price = 0;  //能使用优惠券的商品总价
        for ($i = 0; $i < count($storeArray); $i++) {
            $store_pcoupons_price = 0;
            for ($j = 0; $j < count($storeArray[$i]['order_content']); $j++) {
                $goods_bean = $storeArray[$i]['order_content'][$j];
                if ($this->checkGoodsUseCoupons($goods_bean, $coupons_data)) {
                    if (empty($goods_bean['coupons_exmoney']))
                        $goods_bean['coupons_exmoney'] = 0;
                    $store_pcoupons_price += ($goods_bean['goods_price'] * $goods_bean['gou_num']
                        - $goods_bean['mj_bean_price'] - $goods_bean['coupons_exmoney'] - $goods_bean['store_reduce_price']
                        - $goods_bean['store_discount_price']);
                }
            }
            $store_pcoupons_price += $storeArray[$i]['freight']['freight'];
            $storeArray[$i]['store_pcoupons_price'] = $store_pcoupons_price;
            $mallstore_pcoupons_price = $mallstore_pcoupons_price + $store_pcoupons_price;
        }

        if ($coupons_data['coupons_money'] > $mallstore_pcoupons_price) {
            $coupons_data['coupons_money'] = $mallstore_pcoupons_price;
        }
        if ($coupons_data['coupons_money'] > 0) {
            for ($i = 0; $i < count($storeArray); $i++) {
                if ($mallstore_pcoupons_price == 0) {
                    $storeArray[$i]['platform_coupons_money'] = 0;
                } else {
                    $storeArray[$i]['platform_coupons_money'] = round($storeArray[$i]['store_pcoupons_price'] /
                        $mallstore_pcoupons_price * $coupons_data['coupons_money'], 2);
                }

                for ($j = 0; $j < count($storeArray[$i]['order_content']); $j++) {
                    $goods_bean = $storeArray[$i]['order_content'][$j];
                    if ($this->checkGoodsUseCoupons($goods_bean, $coupons_data)) {
                        if (empty($goods_bean['coupons_exmoney']))
                            $goods_bean['coupons_exmoney'] = 0;
                        $tempValue = $goods_bean['goods_price'] * $goods_bean['gou_num']
                            - $goods_bean['mj_bean_price'] - $goods_bean['coupons_exmoney']
                            - $goods_bean['store_reduce_price']
                            - $goods_bean['store_discount_price'];
                        if ($storeArray[$i]['store_pcoupons_price'] == 0) {
                            $storeArray[$i]['order_content'][$j]['platform_coupons_money'] = 0;
                        } else {
                            $storeArray[$i]['order_content'][$j]['platform_coupons_money'] = round($tempValue /
                                $storeArray[$i]['store_pcoupons_price'] * $storeArray[$i]['platform_coupons_money'], 2);
                        }
                    }
                    if ($storeArray[$i]['store_pcoupons_price'] == 0) {
                        $storeArray[$i]['freight']['platform_coupons_money'] = 0;
                    } else {
                        $storeArray[$i]['freight']['platform_coupons_money'] = round($storeArray[$i]['freight']['freight'] /
                            $storeArray[$i]['store_pcoupons_price'] * $storeArray[$i]['platform_coupons_money'], 2);
                    }

                }
            }
        }
        return getReturn(200, "success", $coupons_data);
    }

    public function getMaxThirdpartMoney(&$changeData, &$storeArray)
    {

        $thirdpart_limit_money = 0;  //能使用优惠券的商品总价
        for ($i = 0; $i < count($storeArray); $i++) {
            for ($j = 0; $j < count($storeArray[$i]['order_content']); $j++) {
                $goods_bean = $storeArray[$i]['order_content'][$j];
                $tempValue = $goods_bean['goods_price'] * $goods_bean['gou_num']
                    - $goods_bean['mj_bean_price'] - $goods_bean['coupons_exmoney'] - $goods_bean['platform_coupons_money'];
                if ($goods_bean['thirdpart_money_limit'] == -1) {
                    $thirdpart_limit_money += $tempValue;
                } else {
                    if (($goods_bean['thirdpart_money_limit'] * $goods_bean['gou_num']) > $tempValue) {
                        $thirdpart_limit_money += $tempValue;
                    } else {
                        $thirdpart_limit_money += $goods_bean['thirdpart_money_limit'] * $goods_bean['gou_num'];
                    }
                }
            }
            if (empty($storeArray[$i]['freight']['platform_coupons_money']))
                $storeArray[$i]['freight']['platform_coupons_money'] = 0;
            $thirdpart_limit_money += ($storeArray[$i]['freight']['freight']
                - $storeArray[$i]['freight']['platform_coupons_money']);
        }
        $changeData['thirdpart_limit_money'] = $thirdpart_limit_money;
    }

    /**
     * 获取商品原价
     * @param int $goods_id 商品id
     * @param int $specid 规格id
     * @return  返回商品原价
     */
    public function get_original_price($goods_bean, $store_id)
    {
        $cost = 0;
        $goods_id = $goods_bean['goods_id'];
        $specid = "";
        if (array_key_exists("spec_group", $goods_bean)) {
            $specid = $goods_bean['spec_group'];
        } else {
            if (array_key_exists("specid", $goods_bean)) {
                $specid = $goods_bean['specid'];
            } else {
                $specid = "-1";
            }
        }


        $model_goods = Model("goods");

        $goodsbean = $model_goods->where(array(
            'goods_id' => $goods_id
        ))->find();

        $goods_spec_array = json_decode($goodsbean['goods_spec'], true);

        //如果是旧多规格的情况则获取其对应的价格
        if ($specid > -1 && !empty($goods_spec_array) && $goodsbean['spec_open'] == 0) { // 如果是有商品规格的情况，就把价格和库存替换
            $goodsbean['goods_price'] = $goods_spec_array[$specid]['price'];
            $goodsbean['goods_pv'] = $goods_spec_array[$specid]['pv'];
        }

        //如果是新多规格的情况则获取其对应的价格
        if ($specid > -1 && $goodsbean['spec_open'] == 1) {
            $spec_detail = Model('goods_option')->field('goods_pv,stock,goods_promoteprice,goods_price')->where("specs = '" . $specid . "'")->find();
            $goodsbean['goods_price'] = $spec_detail['goods_price'];
            $goodsbean['goods_pv'] = $spec_detail['goods_pv'];

        }

        //如果是供应商的商品则获取其真实的价格
        if ($goodsbean['store_id'] != $store_id) {
            $discount_ratio = Model('mb_supplier_agent')->field('discount, ratio')->where(array('supplier_sid' => $goodsbean['store_id'], 'agent_sid' => $store_id, 'is_delete' => '0', 'state' => '2'))->find();
            if ($discount_ratio) {
                $temp_price = round($goodsbean['goods_price'] - ($goodsbean['goods_pv'] * (1.0 - $discount_ratio['discount'] / 10)), 2);
                $goodsbean['goods_price'] = round($temp_price * (1.0 + 1.0 * $discount_ratio['ratio'] / 100), 2);
                $goodsbean['goods_pv'] = $goodsbean['goods_price'] - $temp_price;
            }
        }

        $cost = $goodsbean['goods_price'] - $goodsbean['goods_pv'];
        if ($cost < 0) {
            $cost = 0;
        }
        return $cost;
    }


    public function distributionStoreDiscount(&$confirmOrderData, $store_discount)
    {
        $storeInfoArray = $confirmOrderData['storeInfoArray'];
        $store_discount_amount = 0;
        if ($store_discount < 10) {
            for ($i = 0; $i < count($storeInfoArray[0]['order_content']); $i++) {
                $goods_bean = $storeInfoArray[0]['order_content'][$i];
                $confirmOrderData['storeInfoArray'][0]['order_content'][$i]['store_discount_price'] =
                    $goods_bean['price'] * $goods_bean['gou_num'] * round(((10 - $store_discount) / 10), 2);
                $store_discount_amount += ($confirmOrderData['storeInfoArray'][0]['order_content'][$i]['store_discount_price']);
            }
        }
        $confirmOrderData['storeInfoArray'][0]['store_discount_amount'] = $store_discount_amount;
        $confirmOrderData['storeInfoArray'][0]['temp_total_amount'] = $confirmOrderData['storeInfoArray'][0]['temp_total_amount'] - $store_discount_amount;
        $confirmOrderData['storeInfoArray'][0]['rest_total_amount'] = $confirmOrderData['storeInfoArray'][0]['temp_total_amount'];
        $confirmOrderData['temp_total_amount'] = $confirmOrderData['temp_total_amount'] - $store_discount_amount;

    }

    public function distributionStoreReduceMoney(&$confirmOrderData, $store_reduce_money)
    {
        $storeInfoArray = $confirmOrderData['storeInfoArray'];
        if ($store_reduce_money > $confirmOrderData['temp_total_amount']) {
            $store_reduce_money = $confirmOrderData['temp_total_amount'];
        }
        if ($store_reduce_money > 0) {
            for ($i = 0; $i < count($storeInfoArray[0]['order_content']); $i++) {
                $goods_bean = $storeInfoArray[0]['order_content'][$i];
                $oneValue = $goods_bean['price'] * $goods_bean['gou_num'] - $goods_bean['store_discount_price'];
                $confirmOrderData['storeInfoArray'][0]['order_content'][$i]['store_reduce_price'] =
                    $this->commonOrderApi->getScaleValue($oneValue,
                        $confirmOrderData['temp_total_amount'], $store_reduce_money, $goods_bean['gou_num']);
            }
        }
        $confirmOrderData['storeInfoArray'][0]['store_reduce_money'] = $store_reduce_money;
        $confirmOrderData['storeInfoArray'][0]['temp_total_amount'] = $confirmOrderData['storeInfoArray'][0]['temp_total_amount'] - $store_reduce_money;
        $confirmOrderData['storeInfoArray'][0]['rest_total_amount'] = $confirmOrderData['storeInfoArray'][0]['temp_total_amount'];
        $confirmOrderData['temp_total_amount'] = $confirmOrderData['temp_total_amount'] - $store_reduce_money;

    }


    public function distributionMaxExChange(&$confirmOrderData)
    {
        if (empty($confirmOrderData)) return getReturn(-1, "数据不能为空");

        /**平台第三方抵用**/
        $most_thirdpart_money = 0;
        $rm_thirdpart_money = 0;
        if ($confirmOrderData['thirdpart']
            && $confirmOrderData['thirdpart_money'] > 0
            && $confirmOrderData['temp_total_amount'] > 0
        ) {
            for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
                $store_thirdpart_money_most = 0;
                $storeshopdata_one = $confirmOrderData['storeInfoArray'][$i];
                for ($j = 0; $j < count($storeshopdata_one['order_content']); $j++) {
                    $goods_bean = $storeshopdata_one['order_content'][$j];
                    if ($goods_bean['thirdpart_money_limit'] == -1) {
                        $store_thirdpart_money_most += $this->commonOrderApi->getCashGoodsRestPrice($goods_bean);
                    } else {
                        if ($goods_bean['thirdpart_money_limit'] <= $this->commonOrderApi->getRestPrice($goods_bean)) {
                            $store_thirdpart_money_most += $goods_bean['thirdpart_money_limit'] * $goods_bean['buy_num'];
                        } else {
                            $store_thirdpart_money_most += $this->commonOrderApi->getCashGoodsRestPrice($goods_bean);
                        }
                    }
                }
                if ($confirmOrderData['thirdpart']['exchange_postage'] == 1) {  //运费放置前端计算
                    $restFreight = $storeshopdata_one['freight']['freight']
                        - emptyToZero($storeshopdata_one['freight']['platform_coupons_money']);
                    if ($restFreight < 0)
                        return getReturn(-1, "运费不能小于0");
                    $store_thirdpart_money_most += $restFreight;
                }
                $confirmOrderData['storeInfoArray'][$i]['most_thirdpart_money'] = $store_thirdpart_money_most;
                $most_thirdpart_money += $store_thirdpart_money_most;
                // $rm_thirdpart_money += $confirmOrderData['storeInfoArray'][$i]['thirdpart_money'];
            }

            $use_thirdpart_money = $most_thirdpart_money;
            if ($use_thirdpart_money > $confirmOrderData['thirdpart_money']) {
                $use_thirdpart_money = $confirmOrderData['thirdpart_money'];
            }
            $use_thirdpart_money = saveDownDecimal($use_thirdpart_money);
            if ($use_thirdpart_money == 0) {
                $confirmOrderData['rm_thirdpart_money'] = 0;
                $confirmOrderData['most_thirdpart_money'] = 0;
            } else {
                $rm_thirdpart_money = 0;
                for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
                    $confirmOrderData['storeInfoArray'][$i]['thirdpart_money'] =
                        round($confirmOrderData['storeInfoArray'][$i]['most_thirdpart_money']
                            / $most_thirdpart_money * $use_thirdpart_money, 4);
                    $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] =
                        saveTwoDecimal($confirmOrderData['storeInfoArray'][$i]['rest_total_amount']
                            - $confirmOrderData['storeInfoArray'][$i]['thirdpart_money']);
                    if ($confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] < 0) {
                        return getReturn(-1, "分配平台第三方抵用失败");
                    }
                    $rm_thirdpart_money += $confirmOrderData['storeInfoArray'][$i]['thirdpart_money'];
                }

                $most_thirdpart_money = $confirmOrderData['thirdpart_money'];

                /**** start *****/
                for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
                    $storeorder = $confirmOrderData['storeInfoArray'][$i];
                    $storeorder['postage'] = $storeorder['freight']['freight'];
                    $use_postage = $storeorder['freight']['platform_coupons_money'];
                    $this->distribution_goods_exchange($storeorder, "thirdpart_money", "thirdpart_money",
                        self::THIRPART_MONEY, $confirmOrderData['thirdpart']['exchange_postage'], $use_postage);
                    $storeorder['freight']['postage_thirdpart_money'] = $storeorder['postage_thirdpart_money'];
                    $confirmOrderData['storeInfoArray'][$i] = $storeorder;
                }
                /**** end *****/

                $confirmOrderData['rm_thirdpart_money'] = $rm_thirdpart_money;
                $confirmOrderData['most_thirdpart_money'] = $most_thirdpart_money;
                $confirmOrderData['temp_total_amount'] = round($confirmOrderData['temp_total_amount'] - $rm_thirdpart_money, 2);
                if ($confirmOrderData['temp_total_amount'] < 0) {
                    return getReturn(-1, "平台第三方抵用分配金额有误");
                }
            }

        } else {
            $confirmOrderData['rm_thirdpart_money'] = 0;
            $confirmOrderData['most_thirdpart_money'] = 0;
        }

        /** 店铺余额抵用 **/
        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
            if ($confirmOrderData['storeInfoArray'][$i]['balancepay'] > 0
                && $confirmOrderData['storeInfoArray'][$i]['balance'] > 0
                && $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] > 0
            ) {
                $most_balance = 0;
                $storeshopdata_one = $confirmOrderData['storeInfoArray'][$i];
                for ($j = 0; $j < count($storeshopdata_one['order_content']); $j++) {
                    $goods_bean = $storeshopdata_one['order_content'][$j];
                    if ($goods_bean['balance_limit'] == -1) {
                        $most_balance += $this->commonOrderApi->getCashGoodsRestPrice($goods_bean);

                    } else {
                        if ($goods_bean['balance_limit'] <= $goods_bean['goods_price']) {
                            $goods_balance_limit = $this->commonOrderApi->getRestPrice($goods_bean);
                            if ($goods_balance_limit > $goods_bean['balance_limit'])
                                $goods_balance_limit = $goods_bean['balance_limit'];
                            $most_balance += ($goods_balance_limit * $goods_bean['buy_num']);
                        } else {
                            $most_balance += $this->commonOrderApi->getCashGoodsRestPrice($goods_bean);
                        }
                    }

                }

                if ($storeshopdata_one['balance_exchange_postage'] == 1) {
                    $restFreight = $storeshopdata_one['freight']['freight']
                        - emptyToZero($storeshopdata_one['freight']['platform_coupons_money'])
                        - emptyToZero($storeshopdata_one['freight']['postage_thirdpart_money']);
                    $most_balance += $restFreight;
                }
                $most_balance = saveDownDecimal($most_balance);

                if ($most_balance > $confirmOrderData['storeInfoArray'][$i]['balance']) {
                    $confirmOrderData['storeInfoArray'][$i]['most_balance'] = $most_balance;
                    $confirmOrderData['storeInfoArray'][$i]['rm_balance'] = saveTwoDecimal($confirmOrderData['storeInfoArray'][$i]['balance']);

                } else {
                    $confirmOrderData['storeInfoArray'][$i]['most_balance'] = $most_balance;
                    $confirmOrderData['storeInfoArray'][$i]['rm_balance'] = saveTwoDecimal($most_balance);
                }


                $confirmOrderData['storeInfoArray'][$i]['temp_total_amount']
                    = round($confirmOrderData['storeInfoArray'][$i]['temp_total_amount']
                    - $confirmOrderData['storeInfoArray'][$i]['rm_balance'], 2);
                $confirmOrderData['temp_total_amount']
                    = round($confirmOrderData['temp_total_amount']
                    - $confirmOrderData['storeInfoArray'][$i]['rm_balance'], 2);
                $confirmOrderData['storeInfoArray'][$i]['rest_total_amount']
                    = round($confirmOrderData['storeInfoArray'][$i]['rest_total_amount']
                    - $confirmOrderData['storeInfoArray'][$i]['rm_balance'], 2);
                /**** start *****/
                $storeorder = $confirmOrderData['storeInfoArray'][$i];
                $storeorder['postage'] = $storeorder['freight']['freight'];
                $use_postage = emptyToZero($storeshopdata_one['freight']['platform_coupons_money'])
                    + emptyToZero($storeshopdata_one['freight']['postage_thirdpart_money']);
                $this->distribution_goods_exchange($storeorder, "rm_balance", "balance",
                    self::BALNCE, $storeshopdata_one['balance_exchange_postage'], $use_postage);
                $storeorder['freight']['postage_balance'] = $storeorder['postage_rm_balance'];
                $confirmOrderData['storeInfoArray'][$i] = $storeorder;
                /**** end *****/

            } else {
                $confirmOrderData['storeInfoArray'][$i]['most_balance'] = 0;
                $confirmOrderData['storeInfoArray'][$i]['rm_balance'] = 0;
            }

        }


        /**积分最大抵扣**/
        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
//            $storeExchange = 0;
//            $storeExchange += emptyToZero($confirmOrderData['storeInfoArray'][$i]['coupons_exmoney'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['platform_coupons_money'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_thirdpart_money'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_balance']);


            if ($confirmOrderData['storeInfoArray'][$i]['credit_pay'] > 0
                && $confirmOrderData['storeInfoArray'][$i]['credit_num'] > 0
                && $confirmOrderData['storeInfoArray'][$i]['credit_to_money'] > 0
                && $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] > 0
            ) {

                $exchange_credit_price = $confirmOrderData['storeInfoArray'][$i]['max_credit_num']
                    / $confirmOrderData['storeInfoArray'][$i]['credit_to_money'];
                $exchange_credit_price = saveDownDecimal($exchange_credit_price);
                $exchange_credit_num = $confirmOrderData['storeInfoArray'][$i]['max_credit_num'];
                if ($exchange_credit_price > $confirmOrderData['storeInfoArray'][$i]['rest_total_amount']) {
                    $exchange_credit_num = floor($confirmOrderData['storeInfoArray'][$i]['rest_total_amount']
                        * $confirmOrderData['storeInfoArray'][$i]['credit_to_money']);
                    $exchange_credit_price = saveTwoDecimal($exchange_credit_num / $confirmOrderData['storeInfoArray'][$i]['credit_to_money'], 2);
                }
                $confirmOrderData['storeInfoArray'][$i]['rm_credit_num'] = $exchange_credit_num;
                $confirmOrderData['storeInfoArray'][$i]['rm_credit_price'] = $exchange_credit_price;

                $confirmOrderData['temp_total_amount']
                    = saveTwoDecimal($confirmOrderData['temp_total_amount']
                    - $confirmOrderData['storeInfoArray'][$i]['rm_credit_price']);
                $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'] = saveTwoDecimal($confirmOrderData['storeInfoArray'][$i]['temp_total_amount']
                    - $confirmOrderData['storeInfoArray'][$i]['rm_credit_price']);
                $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] = saveTwoDecimal($confirmOrderData['storeInfoArray'][$i]['rest_total_amount']
                    - $confirmOrderData['storeInfoArray'][$i]['rm_credit_price']);

            } else {
                $confirmOrderData['storeInfoArray'][$i]['rm_credit_num'] = 0;
                $confirmOrderData['storeInfoArray'][$i]['rm_credit_price'] = 0;
            }
        }


        /** 最大平台积分抵用**/

        $rm_platform_credit_num = 0;
        $rm_platform_credit_price = 0;
        if ($confirmOrderData['max_platform_credit_num'] > 0
            && $confirmOrderData['platform_credit_to_money'] > 0
            && $confirmOrderData['platform_credit_pay'] > 0
            && $confirmOrderData['temp_total_amount'] > 0
        ) {
            $max_platform_credit_price = round($confirmOrderData['max_platform_credit_num']
                / $confirmOrderData['platform_credit_to_money'], 2);
            $exchange_credit_price = saveDownDecimal($max_platform_credit_price);
            if ($max_platform_credit_price > $confirmOrderData['temp_total_amount']) {
                $max_platform_credit_price = $confirmOrderData['temp_total_amount'];
            }

//            $restTotalAmount = 0;
//            for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
//                $storeExchange += emptyToZero($confirmOrderData['storeInfoArray'][$i]['coupons_exmoney'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['platform_coupons_money'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_thirdpart_money'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_balance'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_credit_price']);
//                $storeRestTotalAmount = $confirmOrderData['storeInfoArray'][$i]['total_amount'] - $storeExchange;
//                if ($storeRestTotalAmount < 0) {
//                    return getReturn(-1, "最大抵用金额分配错误1.1");
//                }
//                $restTotalAmount += $storeRestTotalAmount;
//                $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'] = $storeRestTotalAmount;
//            }

            for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
                if ($confirmOrderData['platform_credit_pay'] > 0
                    && $confirmOrderData['platform_credit_num'] > 0
                    && $confirmOrderData['platform_credit_to_money'] > 0
                ) {
                    $store_platform_credit_price = round(($confirmOrderData['storeInfoArray'][$i]['rest_total_amount']
                            / $confirmOrderData['temp_total_amount']) * $max_platform_credit_price, 2);

                    $store_platform_credit_num = floor($store_platform_credit_price / $confirmOrderData['platform_credit_to_money']);
                    $store_platform_credit_price = round(($store_platform_credit_num
                        / $confirmOrderData['platform_credit_to_money']), 2);
                    $rm_platform_credit_num += $store_platform_credit_num;
                    $rm_platform_credit_price += $store_platform_credit_price;
                    $confirmOrderData['storeInfoArray'][$i]['rm_platform_credit_num'] = $rm_platform_credit_num;
                    $confirmOrderData['storeInfoArray'][$i]['rm_platform_credit_price'] = $rm_platform_credit_price;
                    $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] =
                        $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] - $rm_platform_credit_price;

                } else {
                    $confirmOrderData['storeInfoArray'][$i]['rm_platform_credit_num'] = 0;
                    $confirmOrderData['storeInfoArray'][$i]['rm_platform_credit_price'] = 0;
                }

            }

        }

        $confirmOrderData['rm_platform_credit_num'] = $rm_platform_credit_num;
        $confirmOrderData['rm_platform_credit_price'] = $rm_platform_credit_price;
        $confirmOrderData['temp_total_amount'] =
            saveTwoDecimal($confirmOrderData['temp_total_amount'] - $rm_platform_credit_price);


        /** 最大平台余额抵用 **/
        if ($confirmOrderData['platform_balance_pay'] > 0
            && $confirmOrderData['platform_balance'] > 0
            && $confirmOrderData['temp_total_amount'] > 0
        ) {

//            $restTotalAmount = 0;
//            for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
//                $storeExchange += emptyToZero($confirmOrderData['storeInfoArray'][$i]['coupons_exmoney'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['platform_coupons_money'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_thirdpart_money'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_balance'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_credit_price'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_platform_credit_price']);
//                $storeRestTotalAmount = $confirmOrderData['storeInfoArray'][$i]['total_amount'] - $storeExchange;
//                if ($storeRestTotalAmount < 0) {
//                    return getReturn(-1, "最大抵用金额分配错误1.2");
//                }
//                $restTotalAmount += $storeRestTotalAmount;
//                $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'] = $storeRestTotalAmount;
//            }
            $most_platform_balance = $confirmOrderData['platform_balance'];
            if ($confirmOrderData['temp_total_amount'] < $confirmOrderData['platform_balance']) {
                $most_platform_balance = $confirmOrderData['temp_total_amount'];
            }
            $most_platform_balance = saveDownDecimal($most_platform_balance);
            $temp_most_platform_balance = 0;
            for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
                $store_platform_balance = round(($confirmOrderData['storeInfoArray'][$i]['rest_total_amount']
                        / $confirmOrderData['temp_total_amount']) * $most_platform_balance, 2);
                $confirmOrderData['storeInfoArray'][$i]['rm_platform_balance'] = $store_platform_balance;
                $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] = saveTwoDecimal($confirmOrderData['storeInfoArray'][$i]['rest_total_amount']
                    - $store_platform_balance);
                $temp_most_platform_balance += $store_platform_balance;
            }
            $confirmOrderData['rm_platform_balance'] = $temp_most_platform_balance;
            $confirmOrderData['temp_total_amount'] = saveTwoDecimal($confirmOrderData['temp_total_amount'] - $temp_most_platform_balance);
        } else {
            $confirmOrderData['rm_platform_balance'] = 0;
        }


//        $mall_total_amount = 0;
//        $storeExchange = 0;
//        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
//            $storeExchange += (emptyToZero($confirmOrderData['storeInfoArray'][$i]['coupons_exmoney'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_balance'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_credit_price']));
//
//            $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'] = $confirmOrderData['storeInfoArray'][$i]['total_amount']
//                - $storeExchange;
//            if ($confirmOrderData['storeInfoArray'][$i]['total_amount'] < 0) {
//                return getReturn(-1, "最大抵用金额分配错误1.3");
//            }
//            $mall_total_amount += $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'];
//        }
//
//        $confirmOrderData['temp_total_amount'] = $mall_total_amount
//            - emptyToZero($confirmOrderData['platform_coupons_money'])
//            - emptyToZero($confirmOrderData['rm_thirdpart_money'])
//            - emptyToZero($confirmOrderData['rm_platform_credit_price'])
//            - emptyToZero($confirmOrderData['rm_platform_balance']);
        logWrite("抵用后数据:" . json_encode($confirmOrderData, 256));
        if ($confirmOrderData['temp_total_amount'] < 0) {
            return getReturn(-1,"最大抵用金额分配错误1.4.1");
        }

        return getReturn(200, 'success', $confirmOrderData);

    }

    public function changeMoney($type, $num, $store_id, &$confirmOrderData)
    {
        // 1: 店铺积分 2: 店铺余额  3: 平台积分  4:平台余额  5.平台第三方
        switch ($type) {
            case 1:
                $returnData = $this->changeCredit($type, $num, $store_id, $confirmOrderData);
                return $returnData;
                break;
            case 2:
                $returnData = $this->changeBalance($type, $num, $store_id, $confirmOrderData);
                return $returnData;
                break;

            case 3:
                $returnData = $this->changePlatformCredit($type, $num, $store_id, $confirmOrderData);
                return $returnData;
                break;

            case 4:
                $returnData = $this->changePlatformBalance($type, $num, $store_id, $confirmOrderData);
                return $returnData;
                break;

            case 5:
                $returnData = $this->changeThirdpart($type, $num, $store_id, $confirmOrderData);
                return $returnData;
                break;
        }

    }

    public function changeCredit($type, $num, $store_id, &$confirmOrderData)
    {
        /**积分最大抵扣**/
        $returnData = array();
        $returnData['type'] = $type;
        $returnData['store_id'] = $store_id;
        $tag = 0;
        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
            if ($confirmOrderData['storeInfoArray'][$i]['store_id'] == $store_id) {
                $tag = 1;
//                $storeExchange = 0;
//                $storeExchange += emptyToZero($confirmOrderData['storeInfoArray'][$i]['coupons_exmoney'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['platform_coupons_money'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_thirdpart_money'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_balance'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_platform_credit_price']);
                $confirmOrderData['temp_total_amount'] += $confirmOrderData['storeInfoArray'][$i]['rm_credit_price'];
                $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'] += $confirmOrderData['storeInfoArray'][$i]['rm_credit_price'];
                $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] += $confirmOrderData['storeInfoArray'][$i]['rm_credit_price'];

                if ($confirmOrderData['storeInfoArray'][$i]['credit_pay'] <= 0) {
                    return getReturn(-1, "该店铺积分抵用已关闭");
                }

                if ($confirmOrderData['storeInfoArray'][$i]['max_credit_num'] < $num) {
                    return getReturn(-1, "该店铺积分最多可使用" . (int)$confirmOrderData['storeInfoArray'][$i]['max_credit_num'] . "积分");
                }

                if ($confirmOrderData['storeInfoArray'][$i]['credit_num'] < $num) {
                    return getReturn(-1, "该店铺积分最多可使用" . (int)$confirmOrderData['storeInfoArray'][$i]['credit_num'] . "积分");
                }

                if ($confirmOrderData['storeInfoArray'][$i]['credit_to_money'] == 0) {
                    $exchange_money = 0;
                } else {
                    $exchange_money = round($num / $confirmOrderData['storeInfoArray'][$i]['credit_to_money'], 2);
                }

                if ($confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] < $exchange_money)
                    return getReturn(-1, "该店铺积分最多可使用" . $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] . "元");


                if ($exchange_money > $confirmOrderData['storeInfoArray'][$i]['temp_total_amount']) {
                    return getReturn(-1, "该店铺积分已超过商品可抵用金额");
                }
                $confirmOrderData['storeInfoArray'][$i]['rm_credit_num'] = $num;
                $confirmOrderData['storeInfoArray'][$i]['rm_credit_price'] = $exchange_money;
                $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'] -= $exchange_money;
                $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] -= $exchange_money;
                $confirmOrderData['temp_total_amount'] -= $exchange_money;
                $returnData['store_temp_total_amount'] = $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'];
                $returnData['num'] = $num;
                $returnData['exchange_money'] = $exchange_money;

                break;
            }
        }
        if ($tag == 0) return getReturn(-1, "暂未找到该积分抵用商家");

//        $mall_total_amount = 0;
//        $storeExchange = 0;
//        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
//            $storeExchange += emptyToZero($confirmOrderData['storeInfoArray'][$i]['coupons_exmoney'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_balance'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_credit_price']);
//            $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'] = $confirmOrderData['storeInfoArray'][$i]['total_amount']
//                - $storeExchange;
//            if ($confirmOrderData['storeInfoArray'][$i]['total_amount'] < 0) {
//                return getReturn(-1, "最大抵用金额分配错误1.3");
//            }
//            $mall_total_amount += $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'];
//        }
//        $confirmOrderData['temp_total_amount'] = $mall_total_amount
//            - emptyToZero($confirmOrderData['platform_coupons_money'])
//            - emptyToZero($confirmOrderData['rm_thirdpart_money'])
//            - emptyToZero($confirmOrderData['rm_platform_credit_price'])
//            - emptyToZero($confirmOrderData['rm_platform_balance']);
        if ($confirmOrderData['temp_total_amount'] < 0) {
            return getReturn(-1, "最大抵用金额分配错误1.4.2");
        }
        $returnData['mall_temp_total_amount'] = $confirmOrderData['temp_total_amount'];
        return getReturn(200, "success", $returnData);
    }

    public function changeBalance($type, $num, $store_id, &$confirmOrderData)
    {
        $returnData = array();
        $returnData['type'] = $type;
        $returnData['store_id'] = $store_id;
        $tag = 0;
        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
            if ($confirmOrderData['storeInfoArray'][$i]['store_id'] == $store_id) {
                $tag = 1;
                if ($confirmOrderData['storeInfoArray'][$i]['balancepay'] <= 0) {
                    return getReturn(-1, "该店铺余额抵用已关闭");
                }
                if ($num > $confirmOrderData['storeInfoArray'][$i]['balance']) {
                    return getReturn(-1, "该店铺余额最多可使用" . $confirmOrderData['storeInfoArray'][$i]['balance']);
                }
                $confirmOrderData['temp_total_amount'] += $confirmOrderData['storeInfoArray'][$i]['rm_balance'];
                $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'] += $confirmOrderData['storeInfoArray'][$i]['rm_balance'];
                $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] += $confirmOrderData['storeInfoArray'][$i]['rm_balance'];
                $most_balance = 0;
                $storeshopdata_one = $confirmOrderData['storeInfoArray'][$i];
                for ($j = 0; $j < count($storeshopdata_one['order_content']); $j++) {
                    $goods_bean = $storeshopdata_one['order_content'][$j];
                    $rest_goods_price = $this->commonOrderApi->getRestPrice($goods_bean)
                        + emptyToZero($goods_bean['balance']);
                    if ($goods_bean['balance_limit'] == -1) {
                        $most_balance += ($rest_goods_price * $goods_bean['buy_num']);
                    } else {
                        if ($goods_bean['balance_limit'] > $rest_goods_price) {
                            $most_balance += $rest_goods_price * $goods_bean['buy_num'];
                        } else {
                            $most_balance += ($goods_bean['balance_limit'] * $goods_bean['buy_num']);
                        }
                    }
                }
                if ($storeshopdata_one['balance_exchange_postage'] == 1) {
                    $restFreight = $storeshopdata_one['freight']['freight']
                        - emptyToZero($storeshopdata_one['freight']['platform_coupons_money'])
                        - emptyToZero($storeshopdata_one['freight']['postage_thirdpart_money']);
                    $most_balance += $restFreight;
                }

                if ($num > $most_balance) {
                    return getReturn(-1, "该店铺余额最多可使用" . $most_balance);
                }

                if ($confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] < $num)
                    return getReturn(-1, "该店铺余额最多可使用" . $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] . "元");

                $confirmOrderData['storeInfoArray'][$i]['most_balance'] = $most_balance;
                $confirmOrderData['storeInfoArray'][$i]['rm_balance'] = $num;
                $exchange_money = $num;

//                $storeExchange = 0;
//                $storeExchange += emptyToZero($confirmOrderData['storeInfoArray'][$i]['coupons_exmoney'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['platform_coupons_money'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_thirdpart_money'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_balance'])
//                    + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_platform_credit_price']);
//                $storeExchange += $exchange_money;
                $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'] -= $exchange_money;
                $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] -= $exchange_money;
                $confirmOrderData['temp_total_amount'] -= $exchange_money;
                $returnData['store_temp_total_amount'] = $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'];
                $returnData['num'] = $num;
                $returnData['exchange_money'] = $exchange_money;
                /**** start *****/
                $storeorder = $confirmOrderData['storeInfoArray'][$i];
                for ($j = 0; $j < count($storeorder['order_content']); $j++) {
                    $storeorder['order_content'][$j]['balance'] = 0;
                }
                $storeorder['postage'] = $storeorder['freight']['freight'];
                $use_postage = emptyToZero($storeshopdata_one['freight']['platform_coupons_money'])
                    + emptyToZero($storeshopdata_one['freight']['postage_thirdpart_money']);
                $this->distribution_goods_exchange($storeorder, "rm_balance", "balance",
                    self::BALNCE, 1, $use_postage);
                $storeorder['freight']['postage_balance'] = $storeorder['postage_rm_balance'];
                $confirmOrderData['storeInfoArray'][$i] = $storeorder;
                /**** end *****/
                break;
            }
        }
        if ($tag == 0) return getReturn(-1, "暂未找到该余额抵用商家");
//        $mall_total_amount = 0;
//        $storeExchange = 0;
//        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
//            $storeExchange += emptyToZero($confirmOrderData['storeInfoArray'][$i]['coupons_exmoney'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_balance'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_credit_price']);
//            $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'] = $confirmOrderData['storeInfoArray'][$i]['total_amount']
//                - $storeExchange;
//            if ($confirmOrderData['storeInfoArray'][$i]['total_amount'] < 0) {
//                return getReturn(-1, "最大抵用金额分配错误1.3");
//            }
//            $mall_total_amount += $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'];
//        }
//        $confirmOrderData['temp_total_amount'] = $mall_total_amount
//            - emptyToZero($confirmOrderData['platform_coupons_money'])
//            - emptyToZero($confirmOrderData['rm_thirdpart_money'])
//            - emptyToZero($confirmOrderData['rm_platform_credit_price'])
//            - emptyToZero($confirmOrderData['rm_platform_balance']);
        if ($confirmOrderData['temp_total_amount'] < 0) {
            return getReturn(-1, "最大抵用金额分配错误1.4.3");
        }
        $returnData['mall_temp_total_amount'] = $confirmOrderData['temp_total_amount'];
        return getReturn(200, 'success', $returnData);
    }

    public function changePlatformCredit($type, $num, $store_id, &$confirmOrderData)
    {
        $returnData = array();
        $returnData['type'] = $type;
        $returnData['store_id'] = $store_id;
        $returnData['store_temp_total_amount'] = 0;

        if ($confirmOrderData['platform_credit_pay'] <= 0) return getReturn(-1, "该店铺平台积分抵用已关闭");
        if ($confirmOrderData['max_platform_credit_num'] < $num) {
            if ($confirmOrderData['platform_credit_num'] < $confirmOrderData['max_platform_credit_num']) {
                return getReturn(-1, "该店铺最多可使用平台积分" . $confirmOrderData['platform_credit_num']);
            } else {
                return getReturn(-1, "该店铺最多可使用平台积分" . $confirmOrderData['max_platform_credit_num']);
            }
        }
        if ($confirmOrderData['platform_credit_num'] < $num) {
            return getReturn(-1, "该店铺最多可使用平台积分" . $confirmOrderData['platform_credit_num']);
        }

        $rm_platform_credit_num = 0;
        $rm_platform_credit_price = 0;

//        $restTotalAmount = 0;
//        $storeExchange = 0;
        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
//            $storeExchange += emptyToZero($confirmOrderData['storeInfoArray'][$i]['coupons_exmoney'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['platform_coupons_money'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_thirdpart_money'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_balance'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_platform_balance'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_credit_price']);
//            $storeRestTotalAmount = $confirmOrderData['storeInfoArray'][$i]['total_amount'] - $storeExchange;
//            if ($storeRestTotalAmount < 0) {
//                return getReturn(-1, "最大抵用金额分配错误1.1");
//            }
//            $confirmOrderData['storeInfoArray'][$i]['storeRestTotalAmount'] = $storeRestTotalAmount;
//            $restTotalAmount += $storeRestTotalAmount;
            $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] +=
                $confirmOrderData['storeInfoArray'][$i]['rm_platform_credit_price'];
        }
        $confirmOrderData['temp_total_amount'] += $confirmOrderData['rm_platform_credit_price'];

        $max_platform_credit_price = 0;
        if ($confirmOrderData['max_platform_credit_num'] > 0 && $confirmOrderData['platform_credit_to_money'] > 0) {
            $max_platform_credit_price = round($confirmOrderData['max_platform_credit_num']
                / $confirmOrderData['platform_credit_to_money'], 2);
            if ($max_platform_credit_price > $confirmOrderData['temp_total_amount']) {
                $max_platform_credit_price = $confirmOrderData['temp_total_amount'];
            }
        }
        if ($confirmOrderData['platform_credit_to_money'] == 0) {
            $exchange_money = 0;
        } else {
            $exchange_money = round($num / $confirmOrderData['platform_credit_to_money'], 2);
        }
        $returnData['num'] = $num;
        $returnData['exchange_money'] = $exchange_money;
        if ($max_platform_credit_price < $exchange_money)
            return getReturn(-1, "该店铺最多可使用平台积分为" . $confirmOrderData['max_platform_credit_num']);
        if ($confirmOrderData['platform_credit_pay'] > 0
            && $confirmOrderData['platform_credit_num'] > 0
            && $confirmOrderData['platform_credit_to_money'] > 0
        ) {
            for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
                $store_platform_credit_price = round(($confirmOrderData['storeInfoArray'][$i]['rest_total_amount']
                        / $confirmOrderData['temp_total_amount']) * $exchange_money, 2);
                $store_platform_credit_num = floor($store_platform_credit_price * $confirmOrderData['platform_credit_to_money']);
                $store_platform_credit_price = round(($store_platform_credit_num
                    * $confirmOrderData['platform_credit_to_money']), 2);
                $rm_platform_credit_num += $store_platform_credit_num;
                $rm_platform_credit_price += $store_platform_credit_price;
                $confirmOrderData['storeInfoArray'][$i]['rm_platform_credit_num'] = $rm_platform_credit_num;
                $confirmOrderData['storeInfoArray'][$i]['rm_platform_credit_price'] = $rm_platform_credit_price;
                $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] -= $rm_platform_credit_price;
            }
        }
        $confirmOrderData['rm_platform_credit_num'] = $rm_platform_credit_num;
        $confirmOrderData['rm_platform_credit_price'] = $rm_platform_credit_price;
        $confirmOrderData['temp_total_amount'] -= $rm_platform_credit_price;
//        $mall_total_amount = 0;
//
//        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
//            $mall_total_amount += $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'];
//        }
//
//        $confirmOrderData['temp_total_amount'] = $mall_total_amount
//            - emptyToZero($confirmOrderData['platform_coupons_money'])
//            - emptyToZero($confirmOrderData['rm_thirdpart_money'])
//            - emptyToZero($confirmOrderData['rm_platform_credit_price'])
//            - emptyToZero($confirmOrderData['rm_platform_balance']);
        if ($confirmOrderData['temp_total_amount'] < 0) {
            return getReturn(-1, "最大抵用金额分配错误1.4.4");
        }
        $returnData['mall_temp_total_amount'] = $confirmOrderData['temp_total_amount'];
        return getReturn(200, 'success', $returnData);
    }


    public function changePlatformBalance($type, $num, $store_id, &$confirmOrderData)
    {
        $returnData = array();
        $returnData['type'] = $type;
        $returnData['store_id'] = $store_id;
        $returnData['store_temp_total_amount'] = 0;

        if ($confirmOrderData['platform_balance_pay'] <= 0) return getReturn(-1, "该店铺平台余额抵用已关闭");
        if ($confirmOrderData['platform_balance'] < $num)
            return getReturn(-1, "该店铺最多可使用平台余额为" . $confirmOrderData['platform_balance']);

//        $restTotalAmount = 0;
//        $storeExchange = 0;
        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
//            $storeExchange += emptyToZero($confirmOrderData['storeInfoArray'][$i]['coupons_exmoney'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['platform_coupons_money'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_thirdpart_money'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_balance'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_credit_price'])
//                + emptyToZero($confirmOrderData['storeInfoArray'][$i]['rm_platform_credit_price']);
//            $storeRestTotalAmount = $confirmOrderData['storeInfoArray'][$i]['total_amount'] - $storeExchange;
//            if ($storeRestTotalAmount < 0) {
//                return getReturn(-1, "最大抵用金额分配错误1.1");
//            }
//            $confirmOrderData['storeInfoArray'][$i]['storeRestTotalAmount'] = $storeRestTotalAmount;
//            $restTotalAmount += $storeRestTotalAmount;
            $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] +=
                $confirmOrderData['storeInfoArray'][$i]['rm_platform_balance'];
        }
        $confirmOrderData['temp_total_amount'] += $confirmOrderData['rm_platform_balance'];
        $max_platform_balance = $num;
        if ($confirmOrderData['temp_total_amount'] < $max_platform_balance) {
            return getReturn(-1, "该店铺最多可使用平台余额" . $confirmOrderData['temp_total_amount']);
        }
        $exchange_money = $num;
        $returnData['num'] = $num;
        $returnData['exchange_money'] = $exchange_money;
        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
            $store_platform_balance = round(($confirmOrderData['storeInfoArray'][$i]['rest_total_amount']
                    / $confirmOrderData['temp_total_amount']) * $exchange_money, 2);
            $confirmOrderData['storeInfoArray'][$i]['rm_platform_balance'] = $store_platform_balance;
            $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] -= $store_platform_balance;
        }
        $confirmOrderData['temp_total_amount'] -= $exchange_money;
        $confirmOrderData['rm_platform_balance'] = $exchange_money;
//        $mall_total_amount = 0;
//        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
//            $mall_total_amount += $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'];
//        }
//
//        $confirmOrderData['temp_total_amount'] = $mall_total_amount
//            - emptyToZero($confirmOrderData['platform_coupons_money'])
//            - emptyToZero($confirmOrderData['rm_thirdpart_money'])
//            - emptyToZero($confirmOrderData['rm_platform_credit_price'])
//            - emptyToZero($confirmOrderData['rm_platform_balance']);
        if ($confirmOrderData['temp_total_amount'] < 0) {
            return getReturn(-1, "最大抵用金额分配错误1.4.5");
        }
        $returnData['mall_temp_total_amount'] = $confirmOrderData['temp_total_amount'];
        return getReturn(200, 'success', $returnData);
    }

    public function changeThirdpart($type, $num, $store_id, &$confirmOrderData)
    {
        $returnData = array();
        $returnData['type'] = $type;
        $returnData['store_id'] = $store_id;
        $returnData['store_temp_total_amount'] = 0;

        if ($confirmOrderData['thirdpart_money_pay'] != 1) {
            return getReturn(-1, "该店铺第三方平台余额已关闭");
        }

        if ($confirmOrderData['thirdpart_money'] < $num) {
            return getReturn(-1, "该店铺第三方平台余额最多可抵用" . $confirmOrderData['thirdpart_money']);
        }

        $most_thirdpart_money = 0;
        $rm_thirdpart_money = 0;
        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
            $store_thirdpart_money_most = 0;
            $storeshopdata_one = $confirmOrderData['storeInfoArray'][$i];
            for ($j = 0; $j < count($storeshopdata_one['order_content']); $j++) {
                $goods_bean = $storeshopdata_one['order_content'][$j];
                if ($goods_bean['thirdpart_money_limit'] == -1) {
                    $store_thirdpart_money_most += ($this->commonOrderApi->getCashGoodsRestPrice($goods_bean)
                        + emptyToZero($goods_bean['thirdpart_money']));
                } else {
                    $rest_goods_price = ($this->commonOrderApi->getRestPrice($goods_bean)
                        + emptyToZero($goods_bean['thirdpart_money'] / $goods_bean['']));
                    if ($goods_bean['thirdpart_money_limit'] <= $rest_goods_price) {
                        $store_thirdpart_money_most += $goods_bean['thirdpart_money_limit'] * $goods_bean['buy_num'];
                    } else {
                        $store_thirdpart_money_most += $rest_goods_price * $goods_bean['buy_num'];
                    }
                }
            }
            if ($confirmOrderData['thirdpart']['exchange_postage'] == 1) {  //运费放置前端计算
                $restFreight = $storeshopdata_one['freight']['freight']
                    - emptyToZero($storeshopdata_one['freight']['platform_coupons_money'])
                    - emptyToZero($storeshopdata_one['freight']['postage_balance']);
                if ($restFreight < 0)
                    return getReturn(-1, "运费不能小于0");
                $store_thirdpart_money_most += $restFreight;
            }
            $confirmOrderData['storeInfoArray'][$i]['most_thirdpart_money'] = $store_thirdpart_money_most;
//            $confirmOrderData['storeInfoArray'][$i]['thirdpart_money'] = $store_thirdpart_money_most;
            $most_thirdpart_money += $store_thirdpart_money_most;
//            $rm_thirdpart_money += $confirmOrderData['storeInfoArray'][$i]['thirdpart_money'];
        }

        if ($most_thirdpart_money < $num) {
            return getReturn(-1, "该店铺第三方平台余额最多可抵用" . $most_thirdpart_money);
        }
        $returnData['num'] = $num;
        $returnData['exchange_money'] = $num;
        $rm_thirdpart_money = 0;
        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
            $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] +=
                $confirmOrderData['storeInfoArray'][$i]['thirdpart_money'];
            $confirmOrderData['storeInfoArray'][$i]['thirdpart_money'] =
                round($confirmOrderData['storeInfoArray'][$i]['most_thirdpart_money']
                    / $most_thirdpart_money * $num, 2);
            $confirmOrderData['storeInfoArray'][$i]['rest_total_amount'] -=
                $confirmOrderData['storeInfoArray'][$i]['thirdpart_money'];
            $rm_thirdpart_money += $confirmOrderData['storeInfoArray'][$i]['thirdpart_money'];
        }
        //$confirmOrderData['thirdpart_money'] = $rm_thirdpart_money;
        $most_thirdpart_money = $confirmOrderData['thirdpart_money'];
        /**** start *****/
        $storeorder = $confirmOrderData['storeInfoArray'][$i];
        $storeorder['postage'] = $storeorder['freight']['freight'];
        $use_postage = $storeorder['freight']['platform_coupons_money']
            + emptyToZero($storeshopdata_one['freight']['postage_balance']);
        $this->distribution_goods_exchange($storeorder, "thirdpart_money", "thirdpart_money",
            self::THIRPART_MONEY, $confirmOrderData['thirdpart']['exchange_postage'], $use_postage);
        $storeorder['freight']['postage_thirdpart_money'] = $storeorder['postage_thirdpart_money'];
        $confirmOrderData['storeInfoArray'][$i] = $storeorder;
        /**** end *****/
        $confirmOrderData['temp_total_amount'] += $confirmOrderData['rm_thirdpart_money'];
        $confirmOrderData['rm_thirdpart_money'] = $rm_thirdpart_money;
        $confirmOrderData['most_thirdpart_money'] = $most_thirdpart_money;
//        $mall_total_amount = 0;
//        for ($i = 0; $i < count($confirmOrderData['storeInfoArray']); $i++) {
//            $mall_total_amount += $confirmOrderData['storeInfoArray'][$i]['temp_total_amount'];
//        }
//
//        $confirmOrderData['temp_total_amount'] = $mall_total_amount
//            - emptyToZero($confirmOrderData['platform_coupons_money'])
//            - emptyToZero($confirmOrderData['rm_thirdpart_money'])
//            - emptyToZero($confirmOrderData['rm_platform_credit_price'])
//            - emptyToZero($confirmOrderData['rm_platform_balance']);
        $confirmOrderData['temp_total_amount'] -= $confirmOrderData['rm_thirdpart_money'];
        if ($confirmOrderData['temp_total_amount'] < 0) {
            return getReturn(-1, "最大抵用金额分配错误1.4.6");
        }
        $returnData['mall_temp_total_amount'] = $confirmOrderData['temp_total_amount'];
        return getReturn(200, 'success', $returnData);
    }

    public function createServerCashOrder($orderData){
        $orderversion_max = Model("mb_cashorder")->max('version');
        $orderversion_max++;
        $use_postage = 0;
        /************计算自定义折扣---start---*****/
//        if ($orderData['store_reduce_money'] > 0){
//            $this->distribution_goods_exchange($orderData, "store_discount_amount", "store_discount_amount",
//                self::CASH_STORE_DISCOUNT_AMOUNT, 0, $use_postage);
//        }
//
//
//        /************计算自定义减免---start---*****/
//        if($orderData['store_reduce_money'] > 0){
//            $this->distribution_goods_exchange($orderData, "store_reduce_money", "store_reduce_money",
//                self::CASH_STORE_REDUCE_MONEY, 0, $use_postage);
//        }

//        $log_data = "订单为类容:" .json_encode($orderData);
//        refundGoodsLogs($log_data);
        $cashOrderData = M("mb_cashorder")->where(array('order_sn' => $orderData['order_sn']))->find();
        if (!empty($cashOrderData)){
            return $cashOrderData;
        }
        $returnData = D("CashOrder")->addServerOrderData($orderData, $this->storeData, $orderversion_max);

        if ($returnData['code'] != 200) {

        } else {
//            $input = new OrderApi();
//            $input->optionStorageAndSales(-1, $orderData['order_content'],
//                    0, 1, $orderData['storeId'], $orderData['buyer_id']);
            $order_id = $returnData['data']['order_id'];
           // D("CashOrderGoods")->put_order_goods_db($orderData['order_content'], $order_id, $orderData['storeId']);


        }
        return $returnData;
    }


    public function sendMessage($params = array())
    {
        // $send_message_url = 'http://m.duinin.com/';
        $send_message_url = SEND_MESSAGE_URL;
        $send_message_url = $send_message_url . "index.php?c=SendMessage&a=sendCashRegisterMsg";
        $result_data = $this->postCurl($send_message_url, $params, 1);
        $log_data = "[postCurl]result_data:" . json_encode($result_data);
        Log::record($log_data, Log::ERR);
    }
    /**
     * 以post方式提交数据到对应的接口url
     * @param string $params 需要post的数据
     * @param string $url url
     * @return string $data 返回请求结果
     */
    public  function postCurl($url, $params = array(), $times = 1)
    {

        $log_data = "[postCurl]url:" . $url . "--params" . json_encode($params);
        Log::record($log_data, Log::ERR);
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        //运行curl
        $data = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        //返回结果
        if ($curl_errno == '0') {
            curl_close($ch);
            return $data;
        } else if ($times < 3) {
            $times++;
            return self::postCurl($url, $params, $times);
        } else {
            curl_close($ch);
            $resultdata['result'] = -1;
            $resultdata['error'] = "curl出错，错误码:" . $curl_errno;
            return json_encode($resultdata, JSON_UNESCAPED_UNICODE);
        }

    }
    /**
     * 以post方式提交数据到对应的接口url
     * @param string $params 需要post的数据
     * @param string $url url
     * @return string $data 返回请求结果
     */
    /*public function postCurl($url, $params = array(), $times = 1)
    {
        $log_data = "[postCurl]url:" . $url . "--params" . json_encode($params);
        Log::record($log_data, Log::ERR);
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        //运行curl
        $data = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        //返回结果
        if ($curl_errno == '0') {
            curl_close($ch);
            return $data;
        } else if ($times < 3) {
            $times++;
            return self::postCurl($url, $params, $times);
        } else {
            curl_close($ch);
            $resultdata['result'] = -1;
            $resultdata['error'] = "curl出错，错误码:" . $curl_errno;
            return json_encode($resultdata, JSON_UNESCAPED_UNICODE);
        }

    }*/

    public function getOrderCredit($store_id, $member_id, $ordermoney){

        $store_data = Model('store')->where(array('store_id' => $store_id))
            ->field('store_id,store_name,channel_id,channel_type,close_shop_send_credit,shop_exchange_credit,
                     shop_exchange_credit_1, shop_exchange_credit_2, shop_exchange_credit_3')
            ->find();
        if (!empty($store_data)) {
            if ($store_data['channel_type'] == 2) {
                $store_data = Model('store')->where(array('channel_id' => $store_data['channel_id'], 'main_store' => 1))->find();
            }
            $where = array();
            $where['store_id'] = $store_data['store_id'];
            $where['member_id'] = $member_id;
            $storeMemberData = Model('mb_storemember')->where($where)->find();
            if(empty($storeMemberData)) return ;


            if (empty($store_data['close_shop_send_credit'])) {  //未关闭积分赠送

                $sendCredit = 1;
                if ($storeMemberData['level'] == 0){
                    $sendCredit = $store_data['shop_exchange_credit'];
                }else if($storeMemberData['level'] == 1){
                    $sendCredit = $store_data['shop_exchange_credit_1'];
                }else if($storeMemberData['level'] == 2){
                    $sendCredit = $store_data['shop_exchange_credit_2'];
                }else if($storeMemberData['level'] == 3){
                    $sendCredit = $store_data['shop_exchange_credit_3'];
                }

                $ordermoney = $ordermoney * $sendCredit;
                if ($ordermoney == 0){
                    return 0;
                }
                return $ordermoney;
            } else {
                return 0;
            }

        } else {
            return 0;
        }
    }
}