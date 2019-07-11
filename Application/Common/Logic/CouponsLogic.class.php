<?php

namespace Common\Logic;
use Api\Controller\MobileController;

class CouponsLogic extends BaseLogic
{
    public function registerMemberGiftCoupons($store_id, $member_id)
    {
        \Think\Log::record($store_id."--registerMemberGiftCoupons--".$member_id, \Think\Log::ERR);
        $store_data = Model('store')->where(array(
            'store_id' => $store_id
        ))->find();
        $memberdata = Model('member')->where(array(
            'member_id' => $member_id
        ))->find();
       $storeMemberData = Model('mb_storemember')->where(array(
            'store_id' => $store_id,
            'member_id' => $member_id
        ))->find();

        if (!empty($store_data) && $storeMemberData['first_giftbag'] == 0) {
            //添加优惠券
            $coupons_maxversion = Model('mb_membercoupons')->max('version');
            $coupons_data = Model('mb_coupons')->where(array(
                'coupons_id' => $store_data['register_coupons']
            ))->find();
            if ($coupons_data) {

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
                $coupons_maxversion = $coupons_maxversion + 1;
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
                Model('mb_membercoupons')->add($membercoupons_data);
                Model('mb_coupons')->where(array(
                    'coupons_id' => $store_data['register_coupons']
                ))->save(array(
                    'send_num' => $coupons_data['send_num'] + 1
                ));
                Model('mb_storemember')->where(array(
                    'store_id' => $store_id,
                    'member_id' => $member_id
                ))->save(array(
                    'first_giftbag' => 1
                ));
            }

            $recommend_coupons_data = Model('mb_coupons')->where(array(
                'coupons_id' => $store_data['recommend_coupons']
            ))->find();
            if (!empty($recommend_coupons_data)) {
                $cmembercoupons_data = array();
                $cmembercoupons_data['store_id'] = $recommend_coupons_data['store_id'];
                $cmembercoupons_data['coupons_id'] = $recommend_coupons_data['coupons_id'];
                $cmembercoupons_data['coupons_name'] = $recommend_coupons_data['coupons_name'];
                $cmembercoupons_data['limit_time'] = $recommend_coupons_data['limit_time'];
                $cmembercoupons_data['limit_money'] = $recommend_coupons_data['limit_money'];
                $cmembercoupons_data['coupons_money'] = $recommend_coupons_data['coupons_money'];
                $createtime = TIMESTAMP;
                $cmembercoupons_data['create_time'] = $createtime;
                $cmembercoupons_data['end_time'] = $createtime + $recommend_coupons_data['limit_time'] * 24 * 3600;
                $cmembercoupons_data['state'] = '0';
                $cmembercoupons_data['store_head'] = $recommend_coupons_data['store_head'];
                $cmembercoupons_data['store_name'] = $recommend_coupons_data['store_name'];
                $cmembercoupons_data['limit_sales'] = $recommend_coupons_data['limit_sales'];
                $cmembercoupons_data['limit_class'] = $recommend_coupons_data['limit_class'];
                $cmembercoupons_data['member_id'] = $memberdata['recommend_id'];
                $cmembercoupons_data['remark'] = '推荐赠送';
                $coupons_maxversion = $coupons_maxversion + 1;
                $cmembercoupons_data['version'] = $coupons_maxversion;
                // hj 2017-12-14 12:25:07 优惠券新增的一些限制字段也需要插入到会员优惠券表中
                $field = [
                    'limit_money_type', 'limit_time_type', 'limit_type', 'limit_class_type',
                    'platform', 'channel_id', 'instructions', 'limit_start_time', 'limit_end_time',
                    'limit_mall_class', 'limit_mall_class_name', 'limit_goods', 'limit_goods_name',
                    'coupons_type', 'coupons_discount', 'available_class', 'available_mall_class',
                    'available_class_name', 'available_mall_class_name'
                ];
                foreach ($field as $key => $value) {
                    if (isset($recommend_coupons_data[$value])) {
                        $cmembercoupons_data[$value] = $recommend_coupons_data[$value];
                    }
                }
                // 有效期截止根据类型改变计算方式
                if ($recommend_coupons_data['limit_time_type'] == 3) {
                    $cmembercoupons_data['end_time'] = $recommend_coupons_data['limit_end_time'];
                }
                Model('mb_membercoupons')->add($cmembercoupons_data);

            }
            

            if (!empty($store_data) && $store_data['recommend_point'] > 0) {
                if (!empty($memberdata['recommend_id'])) {
                    $score = $store_data['recommend_point'];

                    $credits_name = '推荐奖励';
                    $meberitem2 = Model('member')->field('member_name')->where(array(
                        'member_id' => $memberdata['recommend_id']
                    ))->find();
                    $member_name2 = $meberitem2['member_name'];
                    $credits_type = 10;
                    $reason = '';
                    $mbCt = new MobileBaseController();
                    $mbCt->changeCredit($store_id, $memberdata['recommend_id'], $member_name2, $credits_type, $credits_name, $score, $reason);
                }
            }
        }

    }
}