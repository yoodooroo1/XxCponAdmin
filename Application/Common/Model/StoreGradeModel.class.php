<?php

namespace Common\Model;

class StoreGradeModel extends BaseModel
{
    protected $tableName = 'mb_storegrade';

    /**
     * 获取商家的套餐信息
     * 1.一般是获取一下几种  未来可以再加
     *  代理商 partnerManagePms 1-有 0-没有 partner_ctrl
     *  是否显示PV pvShowPms 1-显示 0-不显示 pv_ctrl
     *  会员关系 memberShipPms member_ship_ctrl
     *  三级分销 levelThreePms three_ctrl
     *  APP下载 app_download_tips app_ctrl
     *  会员提现审核 withdrawal withdraw_ctrl
     *  设置推广二维码 promote_qrcode b_code_ctrl
     *  发现 find_manage find_ctrl
     *  微信菜单配置 wx_menu wx_menu_ctrl
     *  价格隐藏 price_hide price_ctrl
     *  商品成本开关 costPms 0-不显示 1-非必填 2-必填 cost_ctrl
     *  自定义支付配置 selfPayPms pay_ctrl
     *  子店支付配置权限 sub_pay_switch
     *  H5支付配置  web_pay_ctrl
     *  PC商城支付配置 pc_pay_ctrl
     *  APP支付配置 app_pay_ctrl
     *  公告权限 notice_ctrl
     *  摇一摇 shake_ctrl
     *  微信商城 wx_status 1-开 0-关
     *  直播开关 live_ctrl
     *  团购开关 group_buy_ctrl
     *  直播房间数量 live_room_num
     *  礼品卡权限 present_card_ctrl
     *  自提点权限 pickup_ctrl
     * @param int $storeId
     * @return array
     * User: hj
     * Date: 2017-09-09 22:15:19
     */
    public function getStoreGrantInfo($storeId = 0)
    {
        $info = S("{$storeId}_grantInfo");
        if (empty($info)) {
            $model = D('Store');
            $store = $model->field('store_id,store_grade,channel_id')->find($storeId);
            $where = [];
            $where['channelid'] = $store['channel_id'];
            $where['store_grade'] = $store['store_grade'];
            $field = [
                "partnerManagePms partner_ctrl,pvShowPms pv_ctrl,memberShipPms member_ship_ctrl",
                "levelThreePms three_ctrl,app_download_tips app_ctrl",
                "withdrawal withdraw_ctrl,promote_qrcode b_code_ctrl,find_manage find_ctrl",
                "wx_menu wx_menu_ctrl,price_hide price_ctrl,costPms cost_ctrl,selfPayPms pay_ctrl,app_state has_app",
                "credit_hide,advertise_num,wx_pay_ctrl,app_pay_ctrl,web_pay_ctrl,pc_pay_ctrl,notice notice_ctrl",
                'shake shake_ctrl,wx_status', 'live_ctrl', 'group_buy_ctrl', 'live_room_num', 'present_card_ctrl',
                'pickup_ctrl', 'show_agent_generalize_ctrl', 'theme_gird_ctrl', 'pv_show_ctrl',
                'form_ctrl', 'language_ctrl', 'pickup_num', 'seller_num', 'price_data_num',
                'app_state'
            ];
            $field = implode(',', $field);
            $info = $this->field($field)->where($where)->find();
            if (false === $info) {
                logWrite("查询商家{$storeId}套餐出错:" . $this->getDbError());
                return getReturn();
            }
            if (empty($info)) {
                $where['channelid'] = 0;
                $info = $this->field($field)->where($where)->find();
                if (false === $info) {
                    logWrite("查询商家{$storeId}套餐出错:" . $this->getDbError());
                    return getReturn();
                }
                if (empty($info)) return getReturn(-1, "未查询到商家权限信息");
            }
            // hj 2017-09-25 16:20:44 获取子店支付配置的权限 获取是否开启系统代收
            $result = D('Channel')->getChannelInfo($store['channel_id']);
            if ($result['code'] !== 200) return $result;
            $info['sub_pay_switch'] = $result['data']['sub_shop_receipt_switch'] == 1 ? 1 : 0;
            $info['sys_collection'] = $result['data']['system_replay_receipt_switch'] == 1 ? 1 : 0;
            S("{$storeId}_grantInfo", $info);
        }
        return getReturn(200, '', $info);
    }

    /**
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-27 16:13:22
     * Desc: 获取现在的套餐列表
     * Update: 2017-10-27 16:13:24
     * Version: 1.0
     */
    public function getStoreGradeList($channelId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        if ($channelId > 0) $where['channelid'] = $channelId;
        $where = array_merge($where, $condition);
        $list = $this
            ->field(true)
            ->where($where)
            ->cache(true)
            ->select();
        return getReturn(200, '', $list);
    }
}