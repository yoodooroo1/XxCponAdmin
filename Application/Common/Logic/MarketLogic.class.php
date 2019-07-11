<?php

namespace Common\Logic;

use Api\Controller\MobileBaseController;
use Common\Common\CommonApi;

class MarketLogic extends BaseLogic
{
    /**
     * 会员注册,推荐会员使用
     * @param $store_id
     * @param $member_id
     * User: czx
     * Date:
     * Update:
     * Version: 1.00
     */
    public function registerMemberGiftCoupons($store_id, $member_id)
    {
        $commonApi = new CommonApi();
        $mainStoreId = getMainStoreId($store_id);
        $storeMemberData = M("mb_storemember")->where(array('store_id' => $mainStoreId, 'member_id' => $member_id))->find();
        $memberData = M("member")->where(array('member_id' => $member_id))->find();
        $registerMarket = M('mb_market')->where(array('store_id' => $store_id, 'is_delete' => 0, 'state' => 1, 'market_type' => 0))->find();
        if (!empty($registerMarket) && ($registerMarket['total_num'] == -1 || $registerMarket['total_num'] > $registerMarket['join_ci_num'])) {
            $memberCouponsIdArr = $commonApi->marketSendCoupons($registerMarket, $member_id, $store_id);
            //注册赠送积分
            if ($registerMarket['send_credit'] > 0 && $registerMarket['select_credit'] = 1) {
                $credits_type = 10;
                $reason = '';
                $credits_name = '注册奖励';
                $mbCt = new MobileBaseController();
                $mbCt->changeCredit($store_id, $member_id, $memberData['member_name'], $credits_type, $credits_name, $registerMarket['send_credit'], $reason);
            }

            $commonApi->addMarketOtherInfo($memberCouponsIdArr, $registerMarket, $memberData);

        }
        //推荐会员奖励
        if ($storeMemberData['recommend_id'] > 0) {
            $rmMemberData = M("member")->where(array('member_id' => $storeMemberData['recommend_id']))->find();
            $rmRegisterMarket = M('mb_market')->where(array('store_id' => $store_id, 'is_delete' => 0, 'state' => 1, 'market_type' => 1))->find();
            if (!empty($rmRegisterMarket) && ($rmRegisterMarket['total_num'] == -1 || $rmRegisterMarket['total_num'] > $rmRegisterMarket['join_ci_num'])) {
                //注册赠送优惠券
                $memberCouponsIdArr = $commonApi->marketSendCoupons($rmRegisterMarket, $storeMemberData['recommend_id'], $store_id);

                //注册赠送积分
                if ($rmRegisterMarket['send_credit'] > 0) {
                    $credits_type = 10;
                    $reason = '';
                    $credits_name = '推荐注册奖励';
                    $mbCt = new MobileBaseController();
                    $mbCt->changeCredit($store_id, $storeMemberData['recommend_id'], $storeMemberData['recommend_name'], $credits_type, $credits_name, $rmRegisterMarket['send_credit'], $reason);
                }

                $commonApi->addMarketOtherInfo($memberCouponsIdArr, $rmRegisterMarket, $rmMemberData);
            }
        }
    }

    /**
     * 单次购物, 满购物, 购买指定商品,赠送给会员,查看指定商品，分享指定资讯，评论指定资讯，点赞资讯
     * @param $marketData
     * @param $store_id
     * @param $member_id
     * @param $send_content
     * @param array $outerData 自定义数据
     * User: czx
     * Date:
     * Update:
     * Version: 1.00
     */
    public function sendMarketGift($marketData, $store_id, $member_id, $send_content = 0, $outerData = [])
    {

        $commonApi = new CommonApi();
        $mainStoreId = getMainStoreId($store_id);
        $marketData = M("mb_market")->where(array('id' => $marketData['id']))->find();
        $memberData = M("member")->where(array('member_id' => $member_id))->find();
        $storeMemberData = M("mb_storemember")->where(array('store_id' => $mainStoreId, 'member_id' => $member_id))->find();
        $checkTag = $this->checkLevel($marketData, $storeMemberData);
        if (!$checkTag) return;

        if (!empty($marketData) && ($marketData['total_num'] == -1 || $marketData['total_num'] > $marketData['join_ci_num'])) {

            $memberCouponsIdArr = $commonApi->marketSendCoupons($marketData, $member_id, $store_id);
            $memberPresentIdArr = $commonApi->marketSendPresent($marketData, $member_id, $store_id);
            // hjun 2018-12-04 18:05:12 升级至代理分组
            D('StoreMember')->autoMarketGroup($store_id, $member_id, $marketData);
            
            //注册赠送积分
            if ($marketData['send_credit'] > 0 && $marketData['select_credit'] = 1 && !empty($memberData)) {
                if($marketData['daily_market'] == 1){
                    $credits_type = 14;
                    $reason = '';
                    $credits_name = '每日任务';
                }else{
                    if ($marketData['market_type'] == 13){
                        $credits_type = 15;
                        $reason = '生日赠送';
                        $credits_name = '生日赠送';
                    }else{
                        $credits_type = 10;
                        $reason = '';
                        $credits_name = '购物奖励';
                    }
                    if (isset($outerData['credits_name'])) {
                        $credits_name = $outerData['credits_name'];
                    }
                }

                $mbCt = new MobileBaseController();
                $mbCt->changeCredit($mainStoreId, $member_id, $memberData['member_name'], $credits_type, $credits_name, $marketData['send_credit'], $reason);
            }
            if (!empty($memberData)){
                $commonApi->addMarketOtherInfo($memberCouponsIdArr, $marketData, $memberData, $memberPresentIdArr);
            }
            if ($marketData['market_type'] == 12){
                $todayTime = strtotime(date("Y-m-d", time()));
                M("mb_daily_market_num")->where(array('store_id' => $store_id, 'member_id' => $member_id,
                    'today_create_time' => $todayTime))->save(array('complete' => 1));
                M("mb_daily_market_record")->add(array('market_id' => $marketData['id'],
                    'market_type' => $marketData['market_type'], 'member_id' => $member_id,
                    'store_id' => $store_id, 'create_time' => time(), 'today_create_time' => $todayTime,
                    'send_content' => $send_content
                ));
            }

            if ($marketData['daily_market'] == 1 && $marketData['market_type'] != 12){
                $todayTime = strtotime(date('Y-m-d', time()));
                $dailyMarketNum = M("mb_daily_market_num")->where(array('store_id' =>$store_id, 'member_id' => $member_id,
                    'today_create_time' => $todayTime))->find();
                $num = 1;
                if (empty($dailyMarketNum)){
                    M("mb_daily_market_num")->add(array('store_id' =>$store_id, 'member_id' => $member_id,
                        'today_create_time' => $todayTime,'num' => $num, 'create_time' => time()));
                }else{
                    $num = $dailyMarketNum['num'];
                    $num++;
                    M("mb_daily_market_num")->where(array('store_id' =>$store_id, 'member_id' => $member_id,
                        'today_create_time' => $todayTime))->save(array('num' => $num));
                }
                M("mb_daily_market_record")->add(array('market_id' => $marketData['id'],
                    'market_type' => $marketData['market_type'], 'member_id' => $member_id,
                    'store_id' => $store_id, 'create_time' => time(), 'today_create_time' => $todayTime,
                    'send_content' => $send_content
                ));

                $dailyMarketData = M("mb_market")->where(array('store_id' => $store_id,'state' => 1, 'is_delete' => 0, 'daily_market' => 1))->select();
                $dailyNum = 0;
                foreach ($dailyMarketData as $key => $value){
                    if ($value['market_type'] != 12){
                        $dailyNum += $value['limit_max_number'];
                    }
                }
                if ($dailyMarketNum['complete'] != 1 && ($num == $dailyNum)){
                    $completeDailyMarketData = M("mb_market")->where(array('store_id' => $store_id,'state' => 1,
                        'is_delete' => 0, 'market_type' => 12))->find();
                    $this->sendMarketGift($completeDailyMarketData, $store_id, $member_id);
                }
            }

        }
    }



    public function checkLevel($marketData, $storeMemberData){
       $gradeTag = $marketData['member_grade'] != -1 && ($marketData['member_grade'] > $storeMemberData['level']);
       $groupTag = true;
       if (!empty($marketData['group_ids'])){
           $group_ids = json_encode($marketData['group_ids']);
           foreach ($group_ids as $value){
               if($storeMemberData['group_id'] = $value['group_id']){
                   $groupTag = false;
                   break;
               }
           }
       }else{
           $groupTag = false;
       }
       if ($gradeTag && $groupTag){
           return false;
       }
       return true;
    }

}