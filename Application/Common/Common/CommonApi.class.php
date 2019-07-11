<?php

namespace Common\Common;


use Think\Log;
use Common\Logic\MarketLogic;


class CommonApi
{
    /**
     * 营销活动赠送优惠券
     * User: czx
     * Date:
     * Update:
     * Version: 1.00
     */
    public function marketSendCoupons($marketData, $member_id, $store_id){
        //注册赠送优惠券
        $max_version = Model('mb_membercoupons')->max('version');
        $memberCouponsIdArr = array();
        if (!empty($marketData['coupons_content']) && $marketData['select_coupons'] == 1) {
            $giftCouponsArr = json_decode($marketData['coupons_content'], true);
            foreach ($giftCouponsArr as $key => $value) {
                for ($i = 0; $i < $value['num']; $i++) {
                    $coupons_data = Model('mb_coupons')->where(array(
                        'coupons_id' => $value['coupons_id'],
                        'isdelete' => 0
                    ))->find();
                    if ($coupons_data) {
                        $member_coupons_id = $this->sendMemberCoupons($coupons_data, $member_id, $store_id, $max_version);
                        $memberCouponsIdArr[] = $member_coupons_id;
                    }
                }
            }
        }
        return $memberCouponsIdArr;
    }

    /**
     * 营销活动赠送礼品
     * User: czx
     * Date:
     * Update:
     * Version: 1.00
     */
    public function marketSendPresent($marketData, $member_id, $store_id){
        //注册赠送优惠券
        $max_version = Model('mb_membercoupons')->max('version');
        $memberPresentIdArr = array();
        if (!empty($marketData['present_content']) && $marketData['select_present'] == 1) {
            $giftPresentArr = json_decode($marketData['present_content'], true);
            foreach ($giftPresentArr as $key => $value) {
                for ($i = 0; $i < $value['num']; $i++) {
                    $present_data = Model('mb_present')->where(array(
                        'present_id' => $value['present_id'],
                        'isdelete' => 0
                    ))->find();
                    if ($present_data) {
                        $member_prenset_id = $this->sendMemberPresent($present_data, $member_id, $store_id, $max_version);
                        $memberPresentIdArr[] = $member_prenset_id;
                    }
                }
            }
        }
        return $memberPresentIdArr;
    }

    public function addMarketOtherInfo($memberCouponsIdArr, $marketData, $memberData, $memberPresentIdArr = array()){

        $groupData = jsonDecodeToArr($marketData['group_content']);
        if (count($memberCouponsIdArr) > 0 || ($marketData['send_credit'] > 0 && $marketData['select_credit'] == 1) ||
            ($marketData['select_group'] == 1 && $groupData['group_id'] > 0) || count($memberPresentIdArr) > 0){
            if (count($memberCouponsIdArr) > 0){
                foreach ($memberCouponsIdArr as $key => $value){
                    $addData = array();
                    $addData['market_id'] = $marketData['id'];
                    $addData['coupons_id'] = $value;
                    $addData['create_time'] = time();
                    M("mb_market_coupons")->add($addData);
                }
            }
            if (count($memberPresentIdArr) > 0){
                foreach ($memberPresentIdArr as $key => $value){
                    $addData = array();
                    $addData['market_id'] = $marketData['id'];
                    $addData['present_id'] = $value;
                    $addData['create_time'] = time();
                    M("mb_market_present")->add($addData);
                }
            }
            $joinMemberData = M("mb_market_join_member")->where(array('market_id' => $marketData['id'], 'member_id' => $memberData['member_id']))->find();

            $join_num = $marketData['join_num'];
            if (empty($joinMemberData)){
                $join_num++;
            }
            $join_ci_num = $marketData['join_ci_num'] + 1;
            M("mb_market")->where(array('id' => $marketData['id']))->save(array('join_ci_num' => $join_ci_num,
                'join_num' => $join_num));
            $read = 0;
            if ( ($marketData['daily_market'] == 1 && count($memberCouponsIdArr) == 0) || $marketData['market_type'] == 11) {
                $read = 1;
            }
            M("mb_market_join_member")->add(array(
                'market_id' => $marketData['id'],
                'store_id' => $marketData['store_id'],
                'member_id' => $memberData['member_id'],
                'member_name' => $memberData['member_name'],
                'nick_name' => $memberData['member_nickname'],
                'market_content' => json_encode($marketData),
                'create_time' => time(),
                'read' => $read));
        }

    }

    public function sendMemberCoupons($coupons_data, $member_id, $store_id, $max_version)
    {
        $membercoupons_data = array();
        $membercoupons_data['store_id'] = $coupons_data['store_id'];
        $membercoupons_data['member_id'] = $member_id;
        $membercoupons_data['coupons_id'] = $coupons_data['coupons_id'];
        $membercoupons_data['coupons_name'] = $coupons_data['coupons_name'];
        $membercoupons_data['limit_time'] = $coupons_data['limit_time'];
        $membercoupons_data['limit_money'] = $coupons_data['limit_money'];
        $membercoupons_data['coupons_money'] = $coupons_data['coupons_money'];
        $createtime = TIMESTAMP;
        $membercoupons_data['create_time'] = $createtime;
        $membercoupons_data['end_time'] = $createtime + $coupons_data['limit_time'] * 24 * 3600;
        $membercoupons_data['remark'] = '注册赠送';
        $coupons_maxversion = $max_version + 1;
        $membercoupons_data['version'] = $coupons_maxversion;
        $membercoupons_data['state'] = '0';
        $membercoupons_data['store_head'] = $coupons_data['store_head'];
        $membercoupons_data['store_name'] = $coupons_data['store_name'];
        $membercoupons_data['limit_sales'] = $coupons_data['limit_sales'];
        $membercoupons_data['limit_class'] = $coupons_data['limit_class'];
        // hj 2017-12-14 12:25:07 优惠券新增的一些限制字段也需要插入到会员优惠券表中
        $field = [
            'limit_money_type', 'limit_time_type', 'limit_type', 'limit_class_type',
            'platform', 'channel_id', 'instructions', 'limit_start_time', 'limit_end_time',
            'limit_mall_class', 'limit_mall_class_name', 'limit_goods', 'limit_goods_name',
            'coupons_type', 'coupons_discount', 'available_class', 'available_mall_class',
            'available_class_name', 'available_mall_class_name'
        ];
        foreach ($field as $key => $value) {
            if (isset($coupons_data[$value])) {
                $membercoupons_data[$value] = $coupons_data[$value];
            }
        }
        // 有效期截止根据类型改变计算方式
        if ($coupons_data['limit_time_type'] == 3) {
            $membercoupons_data['end_time'] = $coupons_data['limit_end_time'];
        }
        $member_coupons_id = Model('mb_membercoupons')->add($membercoupons_data);
        Model('mb_coupons')->where(array(
            'coupons_id' => $value['coupons_id']
        ))->save(array(
            'send_num' => $coupons_data['send_num'] + 1
        ));
        Model('mb_storemember')->where(array(
            'store_id' => $store_id,
            'member_id' => $member_id
        ))->save(array(
            'first_giftbag' => 1
        ));
        return $member_coupons_id;
    }

    public function sendMemberPresent($present_data, $member_id, $store_id, $max_version){
        $present_id = $present_data['present_id'];
        $model = Model('mb_exchange');
        $w = array();
        $w['member_id'] = $member_id;
        $userinfo = M('member')->where($w)->find();
        $info = array();
        $info['member_id'] = $member_id;
        $info['store_id'] = $store_id;
        $info['member_name'] = $userinfo['member_name'];
        $info['present_id'] = $present_id;
        $info['present_name'] = $present_data['name'];
        $info['day']= $present_data['day'];
        $info['present_url']= $present_data['purl'];
        $exchange_code = md5(time().mt_rand(0,1000));
        $info['exchange_code'] = $exchange_code;
        $info['exchange_time'] = TIMESTAMP;
        $info['exchange_type'] = 0;
        $info['type'] = 0;
        $info['version'] = ++$max_version;
        //插入操作
        $exchange_id = $model->insert($info);
        //判断是否插入成功并返回
        if ($exchange_id > 0) {
           return $present_id;
        }
        return 0;
    }


    public function orderSuccessSendMarket($orderItem){

        // add czx  营销送礼品
        $storeMemberData = M("mb_storemember")->where(array('store_id' => $orderItem['storeid'], 'member_id' => $orderItem['buyer_id']))->find();
        $actual_total_price = $orderItem['totalprice'] + $orderItem['balance'] + $orderItem['platform_balance']
            + $orderItem['platform_coupons_money'] + $orderItem['platform_credits_exmoney'] + $orderItem['thirdpart_momey']
            + $orderItem['coupons_exmoney'];


        //单次消费
        $marketWhere = array();
        $marketWhere['market_type'] = 3;
        $marketWhere['store_id'] = $orderItem['storeid'];
        $marketWhere['member_grade'] = array(array('eq',$storeMemberData['level']),array('eq',-1), 'or');
        $marketWhere['state'] = 1;
        $marketWhere['is_delete'] = 0;
        $marketData = M("mb_market")->where($marketWhere)->select();
        $marketLogic = new MarketLogic();
        foreach ($marketData as $key => $value){

            if ($value['once_shopping'] < $actual_total_price){
                $marketLogic->sendMarketGift($value, $value['store_id'], $orderItem['buyer_id']);
            }
        }


        //总共消费
        $marketWhere = array();
        $marketWhere['market_type'] = 4;
        $marketWhere['store_id'] = $orderItem['storeid'];
        $marketWhere['state'] = 1;
        $marketWhere['is_delete'] = 0;
        $marketData = M("mb_market")->where($marketWhere)->select();
        $marketLogic = new MarketLogic();
        foreach ($marketData as $key => $value){
            $joinMemberData = M("mb_market_join_member")->where(array('market_id' => $value['id'], 'member_id' => $orderItem['buyer_id']))->find();
            if ($value['total_shopping'] < ($actual_total_price + $storeMemberData['order_total_amount']) && empty($joinMemberData)) {
                $marketLogic->sendMarketGift($value, $value['store_id'], $orderItem['buyer_id']);
            }
        }


        //商品消费
        $goodsData = json_decode($orderItem['order_content'],true);

        for ($m = 0; $m < count($goodsData); $m++){
            $marketWhere = array();
            $marketWhere['store_id'] = $orderItem['storeid'];
            $marketWhere['member_grade'] = array(array('eq',$storeMemberData['level']),array('eq',-1), 'or');
            $marketWhere['state'] = 1;
            $marketWhere['is_delete'] = 0;
            $marketWhere['market_type'] = 5;
            $marketWhere['goods_content'] = ['like', "%{$goodsData[$m]['goods_id']}%"];
            $marketData = M("mb_market")->where($marketWhere)->select();
            $marketLogic = new MarketLogic();
            foreach ($marketData as $key => $value){
                $marketGoodsData = json_decode($value['goods_content'],true);
                $isExist = 0;
                for ($g = 0; $g < count($marketGoodsData); $g++){
                    if($goodsData[$m]['goods_id'] == $marketGoodsData[$g]['gs_id']){
                        $isExist = 1;
                        break;
                    }
                }
                if ($isExist == 1) {
                    $marketLogic->sendMarketGift($value, $value['store_id'], $orderItem['buyer_id']);
                }
            }
        }
        //更新

        $order_total_amount = $storeMemberData['order_total_amount'] + $actual_total_price;

        M("mb_storemember")->where(array('store_id' => $orderItem['storeid'], 'member_id' => $orderItem['buyer_id']))
            ->save(array('order_total_amount' => $order_total_amount));

        if ($orderItem['gou_type'] == 1){
            $orderCoupons = json_decode($orderItem['gou_info'], true);
            $member_coupons_id = $orderCoupons['id'];
            $marketCoupons = M("mb_market_coupons")->where(array('coupons_id' => $member_coupons_id))->find();
            if (!empty($marketCoupons)){
                $memberData = M("member")->where(array('member_id' => $orderItem['buyer_id']))->find();
                $isExistData = M('mb_market_use_order')->where(array('market_id' => $marketCoupons['market_id'], 'member_name' => $memberData['member_name']))->find();
                M("mb_market_use_order")->add(array(
                   'market_id' => $marketCoupons['market_id'],
                   'member_name' => $memberData['member_name'],
                   'nick_name' => $memberData['member_nickname'],
                   'coupons_id' => $member_coupons_id,
                   'order_id' => $orderItem['order_id'],
                   'create_time' => time()
                ));
                $marketData = M("mb_market")->where(array('id' => $marketCoupons['market_id']))->find();
                $use_num = $marketData['use_num'];
                $use_order_num = $marketData['use_order_num'];
                $use_order_num++;
                if (empty($isExistData)){
                    $use_num++;
                }
                M("mb_market")->where(array('id' => $marketData['id']))->save(array('use_num' => $use_num, 'use_order_num' => $use_order_num));

            }
        }

        if ($orderItem['platform_coupons_id'] > 0){
            $marketCoupons = M("mb_market_coupons")->where(array('coupons_id' => $orderItem['platform_coupons_id']))->find();
            if (!empty($marketCoupons)){
                $memberData = M("member")->where(array('member_id' => $orderItem['buyer_id']))->find();
                $isExistData = M('mb_market_use_order')->where(array('market_id' => $marketCoupons['market_id'],'member_name' => $memberData['member_name']))->find();
                M("mb_market_use_order")->add(array(
                    'market_id' => $marketCoupons['market_id'],
                    'member_name' => $memberData['member_name'],
                    'nick_name' => $memberData['member_nickname'],
                    'coupons_id' => $orderItem['platform_coupons_id'],
                    'order_id' => $orderItem['order_id'],
                    'create_time' => time()
                ));
                $marketData = M("mb_market")->where(array('id' => $marketCoupons['market_id']))->find();
                $use_num = $marketData['use_num'];
                $use_order_num = $marketData['use_order_num'];
                $use_order_num++;
                if (empty($isExistData)){
                    $use_num++;
                }
                M("mb_market")->where(array('id' => $marketData['id']))->save(array('use_num' => $use_num, 'use_order_num' => $use_order_num));
            }
        }

    }


}




?>