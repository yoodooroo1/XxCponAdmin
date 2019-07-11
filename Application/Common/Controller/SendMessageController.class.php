<?php
/**
 * Class SendMessageController
 *
 * 消息发送接口
 *
 * @author    liuweizong
 * @version  1.0
 * @company 微聚点（厦门）信息科技有限公司
 * Copyright 2016年 www.vjudian.com   All rights reserved
 */

namespace Common\Controller;


use Common\Util\WxApi;

class SendMessageController extends BaseController
{
    private $storeId;

    private $wxApi;

    // 发送给商家的消息ID
    private $storeMsgId = [16, 26];

    public function __construct()
    {
        parent::__construct();
        $this->getReqParam();;
        logWrite("MSG-请求头:" . jsonEncode($_SERVER));
        logWrite("MSG-请求参数:" . jsonEncode($this->req));
        $body = file_get_contents('php://input');
        logWrite("MSG-body:{$body}");
    }

    /**
     * 获取accessToken
     * @return string
     * User: hjun
     * Date: 2019-03-19 16:12:12
     * Update: 2019-03-19 16:12:12
     * Version: 1.00
     */
    public function getAccessToken()
    {
        if (!isset($this->wxApi)) {
            $this->wxApi = new WxApi($this->storeId);
        }
        return $this->wxApi->getAccessToken();
    }

    /**
     * POST请求接口
     * @param $url
     * @param $data
     * @return mixed
     * User: hjun
     * Date: 2019-03-19 16:14:00
     * Update: 2019-03-19 16:14:00
     * Version: 1.00
     */
    public function request_post($url, $data)
    {
        return httpRequest($url, 'POST', $data)['data'];
    }

    /*
     *发送微信消息
     * params  int $store_id  店铺id
     * params  tinyint $type      发送模板类（ 1：接单提醒 2：发货提醒 3：收货提醒  5：退货提醒 6：优惠券或礼品券发送 7：推荐人提醒 8：被推荐人提醒 9:积分变动提醒 	 10:直接佣金分成提醒 ，11：间接佣金分成提醒 12：三维佣金分成提醒 13:绑定手机号提醒 14：购物返利提醒   15：下单提醒（新）    17：优惠券或礼品券使用（核销）（新） 18：发货至自提门店提醒） 19:直接支付提醒   20：发送用户下单消息给商家 21：分享佣金提醒 22: 充值成功提醒 23:经销商欠款提醒（沃田源） 24:代理商申请通知 25:代理商审核结果通知
     * params  int $member_id 发送对象用户id
     * params  array $params 参数 当 type = 1: $params['order_id'] 订单id ;
     *                            当 type = 2: $params['order_id'] 订单id ;
     *                            当 type = 3: $params['order_id'] 订单id ;
     *                            当 type = 4: $params['order_id'] 订单id ;
     *                            当 type = 5: $params['order_id'] 订单id ,$params['refund_money'];
     *                            当 type = 6: $params['membercoupon_id'] 表 xunxin_mb_membercoupons id字段   或    $params['present_id'] 表 xunxin_mb_present present_id 字段;   （新）
     *                            当 type = 7: $params['childeren_id'] 推荐对象会员id ;
     *                            当 type = 8: $params['parent_id'] 推荐人会员id ;
     *                            当 type = 9: $params['credit_value'] 积分数 ，$params['credit_type'] 获取积分类型;
     *                            当 type = 10: $params['order_id'] 订单ID ，$params['value'] 佣金数;
     *                            当 type = 11: $params['order_id'] 订单ID ，$params['value'] 佣金数;
     *                            当 type = 12: $params['order_id'] 订单ID ，$params['value'] 佣金数;
     *                            当 type = 14: $params['order_id'] 订单ID ，$params['value'] 佣金数;
     *                            当 type = 15: $params['order_id'] 订单ID ; （新）
     *                            当 type = 16: $params['credit_value'] 积分数 （正数），$params['credit_type'] 减少积分类型;  （新）
     *                            当 type = 17: $params['membercoupon_id'] 表 xunxin_mb_membercoupons id字段   或    $params['present_id'] 表 xunxin_mb_present present_id 字段; （新）
     *                            当 type = 18: $params['order_id']
     *                            当 type = 19: $params['facepay_id']
     *                            当 type = 20: $params['order_id']
     *                            当 type = 20: $params['order_id'] 订单ID ，$params['value'] 佣金数;
     *                            当 type = 22: $params['order_id'] 充值记录的record_id
     *                            当 type = 23: $params['content'] 数据中心传过来的一些参数 经过base64编码 需要解码
     *                            当 type = 24: $params['form_info'] 会员提交的表单信息
     *                            当 type = 25: $params['result_info'] 审核结果信息
     *
     *
     *
     * params tinyint  is_api   是否是api接口 0-否 1-是
     * return bool  true/false
     */
    public function sendWxMsg($store_id = '', $type = '', $member_id = '', $params = array())
    {
        logWrite("发送消息参数:" . json_encode(I(''), JSON_UNESCAPED_UNICODE));
        $member = M('member');
        $tempalte = M('mb_msg_template');
        $store = M('store');
        $order = M('mb_order');
        $coupons = M('mb_membercoupons');
        $present = M('mb_present');
        $is_api = I('is_api', 0);
        $state = 1;

        if ($is_api == 1) {
            $store_id = I('store_id');
            $type = I('type');
            $member_id = I('member_id', 0);
            $params['order_id'] = I('order_id', 0);
            $params['membercoupon_id'] = I('membercoupon_id', 0);
            $params['childeren_id'] = I('childeren_id', 0);
            $params['parent_id'] = I('parent_id', 0);
            $params['credit_value'] = I('credit_value', 0);
            $params['credit_type'] = I('credit_type', '');
            $params['value'] = I('value', 0);
            $params['refund_money'] = I('refund_money', 0);
            $params['present_id'] = I('present_id');
            $params['facepay_id'] = I('facepay_id', 0);
            $params['content'] = $this->req['content'];
            $result = array();
            $result['status'] = 1;

        }

        // 赋值store_id
        $this->storeId = $store_id;

        $style = 0;
        switch ($type) {
            case '13':
                $message_id = 1;  //会员绑定手机号提醒
                break;
            case '15':
                $message_id = 2;  //成功下单提醒
                break;
            case '1':
                $message_id = 3;  //接单提醒
                break;
            case '2':
                $message_id = 4;  //发货提醒
                break;
            case '3':
                $message_id = 5;  //收货提醒
                break;
            case '5':
                $message_id = 6;  //退单提醒
                break;
            case '10':
                $message_id = 7;   //直接佣金提醒
                break;
            case '11':
                $message_id = 8;   //间接佣金提醒
                break;
            case '12':
                $message_id = 9;   //三维佣金提醒
                break;
            case '9':
                $message_id = 10;  //积分变动提醒
                break;
            case '6':
                $message_id = 11;  // 奖礼品获得通知
                break;
            case '17':
                $message_id = 12; //奖礼品核销通知
                break;
            case '7':
                $message_id = 13;  //推荐人消息通知
                break;
            case '8':
                $message_id = 14;  //被推荐人消息通知
                break;
            case '14':
                $message_id = 15;  //购物返利提醒
                break;
            case '18':
                $message_id = 17;  //发货至自提门店提醒
                break;
            case '19':
                $message_id = 18; // 直接支付提醒
                break;
            case '20':
                $message_id = 16; // 发送用户下单消息给商家
                break;
            case '21':
                $message_id = 23; // 发送用户下单消息给商家
                break;
            case 22:
                $message_id = 24; // 充值成功提醒
                break;
            case 23:
                $message_id = 25; // 经销商欠款提醒
                break;
            case 24:
                $message_id = 26; // 代理商申请通知
                break;
            case 25:
                $message_id = 27; // 代理商审核结果通知
                break;
            default :
                $message_id = 0;
                break;
        }
        $w1 = array();
        $w1['xunxin_mb_msg_template.store_id'] = $store_id;
        $w1['xunxin_mb_msg_template.message_id'] = $message_id;
        $w1['xunxin_mb_msg_template.status'] = 1;
        $w1['xunxin_mb_msg_template.isdelete'] = 0;
        /*获取发送模板内容*/
        $template_info = $tempalte->join('xunxin_mb_template_message ON xunxin_mb_template_message.message_id = xunxin_mb_msg_template.message_id')->where($w1)->field('xunxin_mb_template_message.message_id,xunxin_mb_template_message.purpose,xunxin_mb_template_message.params,xunxin_mb_msg_template.remark,xunxin_mb_msg_template.template_id,xunxin_mb_msg_template.wx_status,xunxin_mb_msg_template.sms_status,xunxin_mb_template_message.sms_params,xunxin_mb_template_message.default_url,xunxin_mb_msg_template.link_url')->find();


        $storeinfo = $store->where(array('store_id' => $store_id))->find();
        $channel_id = $storeinfo['channel_id'];
        $store_domain = $storeinfo['store_domain'];

        if (empty($template_info)) {
            if ($channel_id > 0) {
                $main_store_id = $store->where(array('channel_id' => $channel_id, 'main_store' => 1))->getField('store_id');
                if (!empty($main_store_id)) {
                    if (empty($store_domain)) {
                        $store_domain = $store->where(array('store_id' => $main_store_id))->getField('store_domain');
                    }
                    $w1 = array();
                    $w1['xunxin_mb_msg_template.store_id'] = $main_store_id;
                    $w1['xunxin_mb_msg_template.message_id'] = $message_id;
                    $w1['xunxin_mb_msg_template.status'] = 1;
                    $w1['xunxin_mb_msg_template.isdelete'] = 0;
                    $template_info = $tempalte->join('xunxin_mb_template_message ON xunxin_mb_template_message.message_id = xunxin_mb_msg_template.message_id')->where($w1)->field('xunxin_mb_template_message.message_id,xunxin_mb_template_message.purpose,xunxin_mb_template_message.params,xunxin_mb_msg_template.remark,xunxin_mb_msg_template.template_id,xunxin_mb_msg_template.wx_status,xunxin_mb_msg_template.sms_status,xunxin_mb_template_message.sms_params,xunxin_mb_template_message.default_url,xunxin_mb_msg_template.link_url')->find();
                }
            }
        }

        $storeInfo = D('Store')->getStoreInfo($store_id)['data'];
        $host_url = getStoreDomain($store_id);
        if ($message_id == 16) {     //商家订单提醒
            $message_id2 = 16;
            $w2 = array();
            $w2['xunxin_mb_msg_template.store_id'] = $store_id;
            $w2['xunxin_mb_msg_template.message_id'] = $message_id2;
            $w2['xunxin_mb_msg_template.status'] = 1;
            $w2['xunxin_mb_msg_template.isdelete'] = 0;
            /*获取发送模板内容*/
            $template_info2 = $tempalte->join('xunxin_mb_template_message ON xunxin_mb_template_message.message_id = xunxin_mb_msg_template.message_id')->where($w2)->field('xunxin_mb_template_message.message_id,xunxin_mb_template_message.purpose,xunxin_mb_template_message.params,xunxin_mb_msg_template.remark,xunxin_mb_msg_template.template_id,xunxin_mb_msg_template.wx_status,xunxin_mb_msg_template.sms_status,xunxin_mb_template_message.sms_params,xunxin_mb_template_message.default_url,xunxin_mb_msg_template.link_url')->find();
            if (empty($template_info2)) {
                if ($channel_id > 0) {
                    if (!empty($main_store_id)) {
                        $w2 = array();
                        $w2['xunxin_mb_msg_template.store_id'] = $main_store_id;
                        $w2['xunxin_mb_msg_template.message_id'] = $message_id2;
                        $w2['xunxin_mb_msg_template.status'] = 1;
                        $w2['xunxin_mb_msg_template.isdelete'] = 0;
                        $template_info2 = $tempalte->join('xunxin_mb_template_message ON xunxin_mb_template_message.message_id = xunxin_mb_msg_template.message_id')->where($w2)->field('xunxin_mb_template_message.message_id,xunxin_mb_template_message.purpose,xunxin_mb_template_message.params,xunxin_mb_msg_template.remark,xunxin_mb_msg_template.template_id,xunxin_mb_msg_template.wx_status,xunxin_mb_msg_template.sms_status,xunxin_mb_template_message.sms_params,xunxin_mb_template_message.default_url,xunxin_mb_msg_template.link_url')->find();
                    }
                }
            }
            if (!empty($template_info2)) {
                $template_info2['url'] = empty($template_info2['link_url']) ? (empty($template_info2['default_url']) ? '' : $host_url . $template_info2['default_url']) : $template_info2['link_url'];
            }
        }

        if (!empty($template_info)) {
            $template_info['url'] = empty($template_info['link_url']) ? (empty($template_info['default_url']) ? '' : $host_url . $template_info['default_url']) : $template_info['link_url'];
            $datas = array();
            $datas['REMARK'] = $template_info['remark'];
            $datas['SE'] = $store_id;
            $datas['STORE_NAME'] = $storeInfo['store_name'];
            if (!empty($params['order_id'])) {
                $datas['ORDER_ID'] = $params['order_id'];
            }
            /*获取发送用户信息*/
            $member_info = $member->where(array('member_id' => $member_id))->field('member_id,member_name,member_nickname,wx_openid,bindtel')->find();
            $datas['MEMBER_NAME'] = empty($member_info['member_nickname']) ? $member_info['member_name'] : $member_info['member_nickname'];
            $open_id = $this->getUserOpenid($member_id, $store_id);
            logWrite('发送消息-会员信息：' . json_encode($member_info), 'INFO');
            $order_id = $params['order_id'];
            if (!empty($order_id)) {
                $order_info = $order->join('LEFT JOIN xunxin_mb_coupons ON xunxin_mb_coupons.coupons_id = xunxin_mb_order.platform_coupons_id')->where(array('order_id' => $order_id))->field('xunxin_mb_order.*,xunxin_mb_coupons.coupons_name')->find();
            }
            if ($message_id == '1') {   //绑定手机
                if (empty($member_info)) {
                    $state = 0;
                }
                $datas['MEMBER_NAME'] = empty($member_info['member_nickname']) ? $member_info['member_name'] : $member_info['member_nickname'];
                $datas['TEL'] = $member_info['bindtel'];
                $datas['TIME'] = date('Y-m-d H:i');
            } elseif ($message_id == '2') {  //下单提醒
                if (empty($order_info)) {
                    $state = 0;
                }
                $datas['ORDER_SN'] = $order_id;
                $datas['ORDER_MONEY'] = $order_info['totalprice'] + $order_info['balance'] + $order_info['platform_balance'] + $order_info['credits_exmoney'] + $order_info['platform_credits_exmoney'] + $order_info['thirdpart_momey'];
                $datas['CONSIGNEE'] = $order_info['order_membername'];
                $datas['TEL'] = $order_info['order_membertel'];
                $datas['ADDRESS'] = $order_info['address'];
            } elseif ($message_id == '3') { //接单提醒
                if (empty($order_info)) {
                    $state = 0;
                }
                $datas['ORDER_SN'] = $order_id;
                $datas['TIME'] = date('Y-m-d H:i', $order_info['jiedan_time']);
            } elseif ($message_id == '4') { //发货提醒
                if (empty($order_info)) {
                    $state = 0;
                }
                $store_name = $store->where(array('store_id' => $order_info['storeid']))->getField('store_name');
                $pick_name = $order_info['pickup_store_name'];
                $datas['STORE_NAME'] = $store_name;
                $datas['TIME'] = date('Y-m-d H:i', $order_info['create_time']);
                $datas['TIME2'] = date('Y-m-d H:i:s', $order_info['delivery_time']);
                $datas['ORDER_MONEY'] = $order_info['totalprice'] + $order_info['balance'] + $order_info['platform_balance'] + $order_info['credits_exmoney'] + $order_info['platform_credits_exmoney'] + $order_info['thirdpart_momey'];
                $datas['PICK_NAME'] = empty($pick_name) ? $store_name : $pick_name;
            } elseif ($message_id == '5') { //收货提醒
                if (empty($order_info) || empty($member_info)) {
                    $state = 0;
                }
                $datas['MEMBER_NAME'] = empty($member_info['member_nickname']) ? $member_info['member_name'] : $member_info['member_nickname'];
                $datas['ORDER_SN'] = $order_id;
                $datas['ORDER_MONEY'] = $order_info['totalprice'] + $order_info['balance'] + $order_info['platform_balance'] + $order_info['credits_exmoney'] + $order_info['platform_credits_exmoney'] + $order_info['thirdpart_momey'];
                $datas['TIME'] = date('Y-m-d H:i', $order_info['create_time']);
            } elseif ($message_id == '6') { //退单提提醒
                if (empty($order_info)) {
                    $state = 0;
                }
                $goods_num = M('mb_order_goods')->where(array('order_id' => $order_info['order_id']))->sum('gou_num');
                $datas['ORDER_SN'] = $order_id;
                $datas['ORDER_MONEY'] = $params['refund_money'];
                $datas['GOODS_NUM'] = empty($goods_num) ? 0 : $goods_num;
                $datas['REASON'] = $order_info['close_reason'];
            } elseif ($message_id == '7') { //直接佣金提醒
                $datas['ORDER_SN'] = $order_id;
                $datas['PV'] = $params['value'];
                $datas['TIME'] = date('Y-m-d H:i');
            } elseif ($message_id == '8') { //间接佣金提醒
                $datas['ORDER_SN'] = $order_id;
                $datas['PV'] = $params['value'];
                $datas['TIME'] = date('Y-m-d H:i');
            } elseif ($message_id == '9') { //三维佣金提醒
                $datas['ORDER_SN'] = $order_id;
                $datas['PV'] = $params['value'];
                $datas['TIME'] = date('Y-m-d H:i');
            } elseif ($message_id == '10') { //积分变更提醒
                $datas['TIME'] = date('Y-m-d H:i');
                $datas['TYPE'] = $params['credit_type'];
                $datas['VALUE'] = abs($params['credit_value']);
                if ($params['credit_value'] > 0) {
                    $datas['EDIT'] = '增加';
                    $datas['ADD_VALUE'] = $params['credit_value'];
                    $datas['CUT_VALUE'] = 0;
                } else {
                    $datas['EDIT'] = '减少';
                    $datas['ADD_VALUE'] = 0;
                    $datas['CUT_VALUE'] = 0 - $params['credit_value'];
                }
            } elseif ($message_id == '11') { //获得优惠券/礼品券提醒
                $datas['MEMBER_NAME'] = empty($member_info['member_nickname']) ? $member_info['member_name'] : $member_info['member_nickname'];
                if (!empty($params['membercoupon_id'])) {
                    $mcid = $params['membercoupon_id'];
                    $coupon_info = $coupons->where(array('id' => $mcid))->find();
                    $datas['TIME'] = date('Y-m-d H:i', $coupon_info['create_time']);
                    $datas['PRIZE'] = $coupon_info['coupons_name'];
                    $datas['TIME_LIMIT'] = ($coupon_info['limit_time'] == 0) ? '无期限' : '有效期为' . $coupon_info['limit_time'] . '天';
                    if (empty($member_info) || empty($coupon_info)) {
                        $state = 0;
                    }
                } elseif (!empty($params['present_id'])) {
                    $present_info = $present->where(array('present_id' => $params['present_id']))->find();
                    $datas['TIME'] = date('Y-m-d H:i');
                    $datas['PRIZE'] = $present_info['name'];
                    $datas['TIME_LIMIT'] = ($present_info['day'] == 0) ? '无期限' : '有效期为' . $present_info['day'] . '天';
                    if (empty($present_info)) {
                        $state = 0;
                    }
                }

            } elseif ($message_id == '12') { //获得优惠券/礼品券提醒
                $datas['TIME'] = date('Y-m-d H:i');
                if (!empty($params['membercoupon_id'])) {
                    $mcid = $params['membercoupon_id'];
                    $coupon_info = $coupons->where(array('id' => $mcid))->find();
                    $datas['PRIZE'] = $coupon_info['coupons_name'];
                    $datas['PRIZE_MONEY'] = $coupon_info['coupons_money'] . '元';
                    if (empty($coupon_info)) {
                        $state = 0;
                    }
                } elseif (!empty($params['present_id'])) {
                    $present_info = $present->where(array('present_id' => $params['present_id']))->find();
                    $datas['PRIZE'] = $present_info['name'];
                    $datas['PRIZE_MONEY'] = $present_info['score'] . '积分';
                    if (empty($present_info)) {
                        $state = 0;
                    }
                }
            } elseif ($message_id == '13') { //推荐人消息通知
                $childeren_id = $params['childeren_id'];
                $childeren_info = $member->where(array('member_id' => $childeren_id))->field('member_name,member_nickname')->find();
                $datas['RECOMMEND_NAME'] = empty($member_info['member_nickname']) ? $member_info['member_name'] : $member_info['member_nickname'];
                $datas['RECOMMENDED_NAME'] = empty($childeren_info['member_nickname']) ? $childeren_info['member_name'] : $childeren_info['member_nickname'];
                if (empty($member_info) || empty($childeren_info)) {
                    $state = 0;
                }
            } elseif ($message_id == '14') { //被推荐人消息通知
                $parent_id = $params['parent_id'];
                $parent_info = $member->where(array('member_id' => $parent_id))->field('member_name,member_nickname')->find();
                $datas['RECOMMEND_NAMEE'] = empty($parent_info['member_nickname']) ? $parent_info['member_name'] : $parent_info['member_nickname'];
                $datas['RECOMMENDED_NAM'] = empty($member_info['member_nickname']) ? $member_info['member_name'] : $member_info['member_nickname'];
                if (empty($member_info) || empty($parent_info)) {
                    $state = 0;
                }
            } elseif ($message_id == '15') {  // 返利提醒
                $datas['ORDER_SN'] = $order_id;
                $datas['TIME'] = date('Y-m-d H:i');
                $datas['PV'] = $params['value'];
                if ($params['value'] <= 0) {
                    $state = 0;
                }
            } elseif ($message_id == '17') {
                if (empty($order_info)) {
                    $state = 0;
                }
                $datas['ORDER_SN'] = $order_id;
                $datas['ORDER_MONEY'] = $order_info['totalprice'];
                $datas['CONSIGNEE'] = $order_info['order_membername'];
                $datas['TEL'] = $order_info['order_membertel'];
                $datas['ADDRESS'] = $order_info['address'];
                $datas['TIME'] = date('Y-m-d H:i');
            } elseif ($message_id == '18') {  //直接支付提醒
                $payorder_info = M('mb_facepay')->where(array('id' => $params['facepay_id']))->find();
                if (empty($payorder_info)) {
                    $state = 0;
                }
                if ($payorder_info['allmoney'] > 0) {
                    $datas['MEMBER_NAME'] = empty($member_info['member_nickname']) ? $member_info['member_name'] : $member_info['member_nickname'];
                    $datas['PAY_MONEY'] = $payorder_info['allmoney'];
                    $datas['TIME'] = date('Y-m-d H:i');
                    if ($payorder_info['pickid'] > 0) {   //向自提点发送直接支付提醒
                        $storeid = empty($main_store_id) ? $store_id : $main_store_id;
                        $message_id3 = 19;
                        $w3 = array();
                        $w3['xunxin_mb_msg_template.store_id'] = $store_id;
                        $w3['xunxin_mb_msg_template.message_id'] = $message_id3;
                        $w3['xunxin_mb_msg_template.status'] = 1;
                        $w3['xunxin_mb_msg_template.isdelete'] = 0;
                        /*获取发送模板内容*/
                        $template_info3 = $tempalte->join('xunxin_mb_template_message ON xunxin_mb_template_message.message_id = xunxin_mb_msg_template.message_id')->where($w3)->field('xunxin_mb_template_message.message_id,xunxin_mb_template_message.purpose,xunxin_mb_template_message.params,xunxin_mb_msg_template.remark,xunxin_mb_msg_template.template_id,xunxin_mb_msg_template.wx_status,xunxin_mb_msg_template.sms_status,xunxin_mb_template_message.sms_params,xunxin_mb_template_message.default_url,xunxin_mb_msg_template.link_url')->find();
                        if (empty($template_info3)) {
                            if ($channel_id > 0) {
                                if (!empty($main_store_id)) {
                                    $w3 = array();
                                    $w3['xunxin_mb_msg_template.store_id'] = $main_store_id;
                                    $w3['xunxin_mb_msg_template.message_id'] = $message_id3;
                                    $w3['xunxin_mb_msg_template.status'] = 1;
                                    $w3['xunxin_mb_msg_template.isdelete'] = 0;
                                    $template_info3 = $tempalte->join('xunxin_mb_template_message ON xunxin_mb_template_message.message_id = xunxin_mb_msg_template.message_id')->where($w3)->field('xunxin_mb_template_message.message_id,xunxin_mb_template_message.purpose,xunxin_mb_template_message.params,xunxin_mb_msg_template.remark,xunxin_mb_msg_template.template_id,xunxin_mb_msg_template.wx_status,xunxin_mb_msg_template.sms_status,xunxin_mb_template_message.sms_params,xunxin_mb_template_message.default_url,xunxin_mb_msg_template.link_url')->find();
                                }
                            }
                        }
                        if (!empty($template_info3)) {
                            $template_info3['url'] = empty($template_info3['link_url']) ? (empty($template_info3['default_url']) ? '' : $host_url . $template_info3['default_url']) : $template_info3['link_url'];
                        }
                        $this->sendPickupMsg($template_info3, $datas, $payorder_info['pickid'], $storeid, $is_api);
                    }
                }
            } elseif ($message_id == '23') { //分享佣金提醒
                $datas['ORDER_SN'] = $order_id;
                $datas['PV'] = $params['value'];
                $datas['TIME'] = date('Y-m-d H:i');
            } elseif ($message_id == 24) {
                // 充值成功提醒
                $record = D('RechargeCardRecord')->find($params['order_id']);
                $datas['ORDER_TIME'] = date('Y年m月d日 H:i:s', $record['pay_time']);
                $datas['ORDER_MONEY'] = $record['card_money'];
                $datas['BONUS_MONEY'] = $record['give_money'];
                $datas['STORE_NAME'] = $storeInfo['store_name'];
            } elseif ($message_id == 25) {
                // 经销商欠款提醒
                $content = base64_decode($params['content']);
                $content = jsonDecodeToArr($content);
                $datas['STORE_NAME'] = $storeInfo['store_name'];
                $datas['ARREARS_MONEY'] = abs($content['balance']);
                $datas['ARREARS_INFO'] = $content['arrears_info'];
            } elseif ($message_id == 26) {
                // 代理商申请通知商家
                $datas['FORM_INFO'] = $params['form_info'];
            } elseif ($message_id == 27) {
                // 代理商申请审核结果通知会员
                $datas['RESULT_INFO'] = $params['result_info'];
            }

            if ($message_id == '17') {
                $storeid = empty($main_store_id) ? $store_id : $main_store_id;
                $this->sendPickupMsg($template_info, $datas, $order_info['pickup_id'], $storeid, $is_api);
                exit;
            }
            /*发送微信消息*/
            if ($template_info['wx_status'] == '1' && $state == 1 && !in_array($message_id, $this->storeMsgId)) {
                if (!empty($open_id)) {
                    $template_data = $template_info['params'];
                    $url = $template_info['url'];
                    foreach ($datas as $key => $da) {
                        if (strpos($template_data, '{' . $key . '}') !== false) {
                            $template_data = str_replace('{' . $key . '}', $da, $template_data);
                        }
                        if (strpos($url, '{' . $key . '}') !== false) {
                            $url = str_replace('{' . $key . '}', $da, $url);
                        }
                    }
                    $check = $this->sendWxTemplateMsg($open_id, $template_info['template_id'], $template_data, $url);
                    logWrite('必要参数：' . json_encode($datas) . '\n\r模板内容:' . $template_data . "\n\r 类型：" . $type . "\n\r 会员ID:" . $member_id . "\n\r 参数：" . json_encode($params) . "\n\r结果：" . json_encode($check), 'INFO');
                    if ($check == '1') {
                        if ($is_api == 1) {
                            $result['status'] = 1;
                        }
                        $status = 1;
                    } else {
                        if ($is_api == 1) {
                            $result['status'] = -1;
                            $result['error'] = $check;
                        }
                        $status = 0;
                    }
                    //添加记录
                    $store_id1 = empty($main_store_id) ? $store_id : $main_store_id;
                    $this->addMsgRecord($store_id1, $channel_id, 1, 1, $member_id, $member_info['member_name'], $template_info['message_id'], $template_info['purpose'], $status, '');
                } else {
                    if ($is_api == 1) {
                        $result['status'] = -1;
                        $result['error'] = '用户非微信登录';
                    }
                }
            }
            /*发送短信消息*/
            if ($template_info['sms_status'] == '1' && $state == 1 && !in_array($message_id, $this->storeMsgId)) {
                $store_id2 = empty($main_store_id) ? $store_id : $main_store_id;
                $sms_info = $this->checkStoreSms($store_id2, $channel_id, 1);
                logWrite('短信消息店铺参数：' . json_encode($sms_info) . '\n\r会员手机号:' . $member_info['bindtel'], 'INFO');
                if ($sms_info['status'] == -1) {
                    $result['status'] = -1;
                    $result['error'] = $sms_info['error'];
                } else if (empty($member_info['bindtel'])) {
                    $result['status'] = -1;
                    $result['error'] = $result['error'] . '  该会员没有绑定手机号';
                } else {
                    $template_data2 = $template_info['sms_params'];
                    foreach ($datas as $key => $da) {
                        if (strpos($template_data2, '{' . $key . '}') !== false) {
                            $template_data2 = str_replace('{' . $key . '}', $da, $template_data2);
                        }
                    }
                    $check2 = $this->smsChinaSend($member_info['bindtel'], $template_data2, $sms_info['sms_header'], $sms_info['youe_account'], $sms_info['youe_password']);
                    logWrite('短信消息发送模板：' . $template_data2 . '\n\r结果:' . $check2, 'INFO');
                    if ($check2) {
                        $status = 1;
                        $store->where(array('store_id' => $store_id2))->setDec('sms_num', 1);
                    } else {
                        $status = 0;
                    }
                    $this->addMsgRecord($store_id2, $channel_id, 2, 1, $member_id, $member_info['member_name'], $template_info['message_id'], $template_info['purpose'], $status, $member_info['bindtel']);
                }
            }


            /*推送消息给商家绑定店铺*/
            if (in_array($message_id, $this->storeMsgId)) {
                $template_info2 = isset($template_info2) ? $template_info2 : $template_info;
                $success_num = 0;
                $datas['TIME'] = date('Y-m-d H:i');
                if ($order_info['pickup_id'] > 0 && $order_info['get_to_store'] > 0) {
                    $type = '上门自提';
                } elseif ($order_info['pickup_id'] > 0 && empty($order_info['get_to_store'])) {
                    $type = '商家配送至自提点';
                } else {
                    $type = "普通订单";
                }
                $datas['ORDER_TYPE'] = $type;
                $datas['ORDER_SN'] = $order_id;
                $datas['ORDER_MONEY'] = $order_info['totalprice'] + $order_info['balance'] + $order_info['platform_balance'] + $order_info['credits_exmoney'] + $order_info['platform_credits_exmoney'] + $order_info['thirdpart_momey'];
                $datas['CONSIGNEE'] = $order_info['order_membername'];
                $datas['TEL'] = $order_info['order_membertel'];
                $datas['ADDRESS'] = $order_info['address'];
                if (!empty($main_store_id)) {
                    $sid = $main_store_id;
                } elseif (!empty($store_id)) {
                    $sid = $store_id;
                } else {
                    $sid = $order_info['storeid'];
                }
                $json = $store->where(array('store_id' => $sid))->getField('bind_msg_member');
                $staffs = json_decode($json, true);
                $marray = array();
                foreach ($staffs as $sta) {
                    $marray[] = $sta['member_id'];
                }
                if (!empty($marray)) {
                    $str = implode(',', $marray);
                    if ($template_info2['wx_status'] == '1') {
                        $template_data3 = $template_info2['params'];
                        $url2 = $template_info2['url'];
                        foreach ($datas as $key => $da) {
                            if (strpos($template_data3, '{' . $key . '}') !== false) {
                                $template_data3 = str_replace('{' . $key . '}', $da, $template_data3);
                            }
                            if (strpos($url2, '{' . $key . '}') !== false) {
                                $url2 = str_replace('{' . $key . '}', $da, $url2);
                            }
                        }

                        $w3 = array();
                        $w3['member_id'] = array('in', $str);
                        //$w3['wx_openid'] = array(array('neq',''),array('exp','is not null'));
                        $store_member = $member->where($w3)->field('wx_openid,member_id,member_name')->select();

                        foreach ($store_member as $sm) {
                            $open_id = $this->getUserOpenid($sm['member_id'], $store_id);
                            if (!empty($open_id)) {
                                $check = $this->sendWxTemplateMsg($open_id, $template_info2['template_id'], $template_data3, $url2);
                                logWrite('必要参数：' . json_encode($datas) . '\n\r模板内容:' . $template_data3 . "\n\r 类型：" . $type . "\n\r 会员ID:" . $member_id . "\n\r 参数：" . json_encode($params) . "\n\r结果：" . json_encode($check), 'INFO');
                                if ($check == '1') {
                                    $success_num = $success_num + 1;
                                    if ($is_api == 1) {
                                        $result['status'] = 1;
                                    }
                                    $status = 1;
                                } else {
                                    if ($is_api == 1) {
                                        $result['status'] = -1;
                                        $result['error'] = $result['error'] . $check . "\n";
                                    }
                                    $status = 0;
                                }
                                //添加记录
                                $store_id1 = empty($main_store_id) ? $store_id : $main_store_id;
                                $this->addMsgRecord($store_id1, $channel_id, 1, 1, $sm['member_id'], $sm['member_name'], $template_info2['message_id'], $template_info2['purpose'], $status, '');
                            }

                        }
                    }
                }
                $json2 = $store->where(array('store_id' =>$sid))->getField('bind_msg_tel');
                $tels = json_decode($json2, true);
                $tarray = array();
                foreach ($tels as $te) {
                    $tarray[] = $te['tel'];
                }

                if (!empty($tarray)) {
                    if ($template_info2['sms_status'] == '1') {
                        $store_id2 = empty($main_store_id) ? $store_id : $main_store_id;
                        /* $w3 = array();
                        $w3['member_id'] = array('in',$str);
                        $w3['bindtel'] = array(array('neq',''),array('exp','is not null'));
                        $store_member2 = $member->where($w3)->field('bindtel,member_id,member_name')->select();  */
                        $sms_info = $this->checkStoreSms($store_id2, $channel_id, count($tarray));

                        if ($sms_info['status'] == '-1') {
                            $result['status'] = -1;
                            $result['error'] = $result['error'] . $sms_info['error'] . "\n";
                        } else {
                            $success_num = $success_num + 1;
                            $template_data4 = $template_info2['sms_params'];
                            foreach ($datas as $key => $da) {
                                if (strpos($template_data4, '{' . $key . '}') !== false) {
                                    $template_data4 = str_replace('{' . $key . '}', $da, $template_data4);
                                }
                            }
                            foreach ($tarray as $tel2) {
                                //file_put_contents('text','tel :'.$tel2." template_data:".$template_data4." sms_header :".$sms_info['sms_header']." you_account:".$sms_info['youe_account']." pass:".$sms_info['youe_password']."\n\r",FILE_APPEND);
                                $check2 = $this->smsChinaSend($tel2, $template_data4, $sms_info['sms_header'], $sms_info['youe_account'], $sms_info['youe_password']);
                                logWrite('短信消息发送模板：' . $template_data4 . '\n\r结果:' . $check2, 'INFO');
                                if ($check2) {
                                    $status = 1;
                                    $store->where(array('store_id' => $store_id2))->setDec('sms_num', 1);
                                } else {
                                    $status = 0;
                                }
                                $this->addMsgRecord($store_id2, $channel_id, 2, 1, $storeinfo['member_id'], $storeinfo['member_name'], $template_info2['message_id'], $template_info2['purpose'], $status, $tel2);
                            }

                        }
                    }
                }
                if ($success_num > 0) {
                    $result['status'] = 1;
                } else {
                    $result['status'] = 0;
                }


            }

            /*推送消息给商家绑定店铺结束*/

        } else {
            if ($is_api == 1) {
                $result['status'] = -1;
                $result['error'] = '模板不存在';
            }
        }
        if ($is_api == 1) {
            $this->apiResponse($result);
        }

    }


    /* 发送短信消息提醒（表单预约功能）
         * params int  $store_id   商城模式时传主店store_id ,否则传当前store_id
         * params varchar  $content  发送内容
         * params string $tel  手机号码
         * params int $member_id 会员ID
         * params string $member_name 会员账号
         */

    public function sendFormSmsMsg($store_id = '', $channel_id = '', $content = '', $tel = '', $member_id = '', $member_name = '')
    {
        $store = M('store');
        $sms_info = $this->checkStoreSms($store_id, $channel_id, 1);

        if ($sms_info['status'] == -1) {
            $result['status'] = -1;
            $result['error'] = $sms_info['error'];
        } else if (empty($tel)) {
            $result['status'] = -1;
            $result['error'] = $result['error'] . '  手机号为空';
        } else {

            $check2 = $this->smsChinaSend($tel, $content, $sms_info['sms_header'], $sms_info['youe_account'], $sms_info['youe_password']);

            logWrite('短信消息发送模板：' . $content . '\n\r结果:' . $check2, 'INFO');
            if ($check2) {
                $status = 1;
                $store->where(array('store_id' => $store_id))->setDec('sms_num', 1);
            } else {
                $status = 0;
            }
            $this->addMsgRecord($store_id, $channel_id, 2, 1, $member_id, $member_name, 0, '表单提交提醒', $status, $tel);
        }
    }

    /* 发送短信消息提醒（表单预约功能）
     * params int  $store_id   商城模式时传主店store_id ,否则传当前store_id
     * params varchar  $content  发送内容
     */

    public function sendSmsMsg($store_id = '', $content = '', $tel = '', $member_id = '', $member_name = '')
    {
        $store = M('store');
        $channel_id = $store->where(array('store_id' => $store_id))->getField('channel_id');
        $sms_info = $this->checkStoreSms($store_id, $channel_id, 1);

        if ($sms_info['status'] == -1) {
            $result['status'] = -1;
            $result['error'] = $sms_info['error'];
        } else if ($tel) {
            $result['status'] = -1;
            $result['error'] = $result['error'] . '  手机号为空';
        } else {
            $check2 = $this->smsChinaSend($tel, $content, $sms_info['sms_header'], $sms_info['youe_account'], $sms_info['youe_password']);
            logWrite('短信消息发送模板：' . $content . '\n\r结果:' . $check2, 'INFO');
            if ($check2) {
                $status = 1;
                $store->where(array('store_id' => $store_id))->setDec('sms_num', 1);
            } else {
                $status = 0;
            }
            $this->addMsgRecord($store_id, $channel_id, 2, 1, $member_id, $member_name, 0, '表单提交提醒', $status, $tel);
        }
    }

    public function sendPickupMsg($template_info = array(), $datas = array(), $pickup_id = '', $store_id = '', $is_api = 0)
    {
        $member = M('member');
        $store = M('store');
        $channel_id = $store->where(array('store_id' => $store_id))->getField('channel_id');
        $json = D('PickUp')->where(array('id' => $pickup_id))->getField('staff');
        $staffs = json_decode($json, true);
        $marray = array();
        foreach ($staffs as $sta) {
            $marray[] = $sta['member_id'];
        }
        if (!empty($marray)) {
            $str = implode(',', $marray);
            if ($template_info['wx_status'] == '1') {
                $template_data3 = $template_info['params'];
                $url = $template_info['url'];
                foreach ($datas as $key => $da) {
                    if (strpos($template_data3, '{' . $key . '}') !== false) {
                        $template_data3 = str_replace('{' . $key . '}', $da, $template_data3);
                    }
                    if (strpos($url, '{' . $key . '}') !== false) {
                        $url = str_replace('{' . $key . '}', $da, $url);
                    }
                }

                $w3 = array();
                $w3['member_id'] = array('in', $str);
                //$w3['wx_openid'] = array(array('neq',''),array('exp','is not null'));
                $store_member = $member->where($w3)->field('wx_openid,member_id,member_name')->select();

                foreach ($store_member as $sm) {
                    $open_id = $this->getUserOpenid($sm['member_id'], $store_id);
                    if (!empty($open_id)) {
                        $check = $this->sendWxTemplateMsg($open_id, $template_info['template_id'], $template_data3, $url);
                        logWrite('必要参数：' . json_encode($datas) . '\n\r模板内容:' . $template_data3 . "\n\r结果：" . json_encode($check), 'INFO');
                        if ($check == '1') {
                            if ($is_api == 1) {
                                $result['status'] = 1;
                            }
                            $status = 1;
                        } else {
                            if ($is_api == 1) {
                                $result['status'] = -1;
                                $result['error'] = $check;
                            }
                            $status = 0;
                        }
                        //添加记录
                        $this->addMsgRecord($store_id, $channel_id, 1, 1, $sm['member_id'], $sm['member_name'], $template_info['message_id'], $template_info['purpose'], $status, '');
                    }

                }
            }
            if ($template_info['sms_status'] == '1') {
                $w3 = array();
                $w3['member_id'] = array('in', $str);
                $w3['bindtel'] = array(array('neq', ''), array('exp', 'is not null'));
                $store_member2 = $member->where($w3)->field('bindtel,member_id,member_name')->select();
                $sms_info = $this->checkStoreSms($store_id, $channel_id, count($store_member2));
                if ($sms_info['status'] == '-1') {
                    $result['status'] = -1;
                    $result['error'] = $sms_info['error'];
                } else {
                    $template_data4 = $template_info['sms_params'];
                    foreach ($datas as $key => $da) {
                        if (strpos($template_data4, '{' . $key . '}') !== false) {
                            $template_data4 = str_replace('{' . $key . '}', $da, $template_data4);
                        }
                    }
                    foreach ($store_member2 as $sm2) {
                        $check2 = $this->smsChinaSend($sm2['bindtel'], $template_data4, $sms_info['sms_header'], $sms_info['youe_account'], $sms_info['youe_password']);
                        logWrite('短信消息发送模板：' . $template_data4 . '\n\r结果:' . $check2, 'INFO');
                        if ($check2) {
                            if ($is_api == 1) {
                                $result['status'] = 1;
                            }
                            $status = 1;
                            $store->where(array('store_id' => $store_id))->setDec('sms_num', 1);
                        } else {
                            if ($is_api == 1) {
                                $result['status'] = -1;
                                $result['error'] = $check;
                            }
                            $status = 0;
                        }
                        $this->addMsgRecord($store_id, $channel_id, 2, 1, $sm2['member_id'], $sm2['member_name'], $template_info['message_id'], $template_info['purpose'], $status, $sm2['bindtel']);
                    }

                }
            }
        }
        if ($is_api == 1) {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
    }

    public function sendThirdPartWxMsg()
    {
        $member = M('member');
        $tempalte = M('mb_msg_template');
        $store = M('store');
        $is_api = I('is_api', 0);
        if ($is_api == 1) {
            $store_id = I('store_id');
            $type = I('type');
            $member_id = I('member_id');
            $order_sn = I('order_id', 0);
            $price = I('price', 0);
            $message_id = 2;  //成功下单提醒
            $result = array();
        }
        $shop_exchange_credit = $store->where(array('store_id' => $store_id))->getField('shop_exchange_credit');

        $w1 = array();
        $w1['xunxin_mb_msg_template.store_id'] = $store_id;
        $w1['xunxin_mb_msg_template.message_id'] = $message_id;
        $w1['xunxin_mb_msg_template.status'] = 1;
        $w1['xunxin_mb_msg_template.isdelete'] = 0;
        /*获取发送模板内容*/
        $template_info = $tempalte->join('xunxin_mb_template_message ON xunxin_mb_template_message.message_id = xunxin_mb_msg_template.message_id')->where($w1)->field('xunxin_mb_template_message.message_id,xunxin_mb_template_message.purpose,xunxin_mb_template_message.params,xunxin_mb_msg_template.remark,xunxin_mb_msg_template.template_id,xunxin_mb_msg_template.wx_status,xunxin_mb_msg_template.sms_status,xunxin_mb_template_message.sms_params')->find();
        $channel_id = $store->where(array('store_id' => $store_id))->getField('channel_id');
        $store_domain = $store->where(array('store_id' => $store_id))->getField('store_domain');
        if (empty($template_info)) {
            if ($channel_id > 0) {
                $main_store_id = $store->where(array('channel_id' => $channel_id, 'main_store' => 1))->getField('store_id');
                if (empty($store_domain)) {
                    $store_domain = $store->where(array('store_id' => $main_store_id))->getField('store_domain');
                }
                if (!empty($main_store_id)) {
                    $w1 = array();
                    $w1['xunxin_mb_msg_template.store_id'] = $main_store_id;
                    $w1['xunxin_mb_msg_template.message_id'] = $message_id;
                    $w1['xunxin_mb_msg_template.status'] = 1;
                    $w1['xunxin_mb_msg_template.isdelete'] = 0;
                    $template_info = $tempalte->join('xunxin_mb_template_message ON xunxin_mb_template_message.message_id = xunxin_mb_msg_template.message_id')->where($w1)->field('xunxin_mb_template_message.message_id,xunxin_mb_template_message.purpose,xunxin_mb_template_message.params,xunxin_mb_msg_template.remark,xunxin_mb_msg_template.template_id,xunxin_mb_msg_template.wx_status,xunxin_mb_msg_template.sms_status,xunxin_mb_template_message.sms_params')->find();
                }
            }
        }
        if (!empty($template_info)) {
            $member_info = $member->where(array('member_id' => $member_id))->field('member_id,member_name,member_nickname,wx_openid,bindtel')->find();
            $open_id = $this->getUserOpenid($member_id, $store_id);
            $datas = array();
            $datas['REMARK'] = $template_info['remark'];
            $datas['ORDER_SN'] = $order_sn;
            $datas['ORDER_MONEY'] = $price;
            $datas['INTERGAL'] = intval($price * $shop_exchange_credit);
            $datas['CONSIGNEE'] = empty($member_info['member_nickname']) ? $member_info['member_name'] : $member_info['member_nickname'];
            $datas['TEL'] = $member_info['bindtel'];
            $datas['ADDRESS'] = '门店消费';
            /*获取发送用户信息*/
            if ($template_info['wx_status'] == '1') {
                if (!empty($open_id)) {
                    $template_data = $template_info['params'];
                    foreach ($datas as $key => $da) {
                        if (strpos($template_data, '{' . $key . '}') !== false) {
                            $template_data = str_replace('{' . $key . '}', $da, $template_data);
                        }
                    }
                    /*第三方订单默认跳转到会员中心*/
                    $host_url = empty($store_domain) ? URL() : 'http://' . $store_domain;
                    $url = $host_url . '/index.php?m=Service&c=User&a=user&se=' . $store_id;
                    $check = $this->sendWxTemplateMsg($open_id, $template_info['template_id'], $template_data, $url);
                    logWrite('必要参数：' . json_encode($datas) . '\n\r模板内容:' . $template_data . "\n\r 类型：" . $type . "\n\r 会员ID:" . $member_id . "\n\r结果：" . json_encode($check), 'INFO');
                    if ($check == '1') {
                        if ($is_api == 1) {
                            $result['status'] = 1;
                        }
                        $status = 1;
                    } else {
                        if ($is_api == 1) {
                            $result['status'] = -1;
                            $result['error'] = $check;
                        }
                        $status = 0;
                    }
                    //添加记录
                    $store_id1 = empty($main_store_id) ? $store_id : $main_store_id;
                    $this->addMsgRecord($store_id1, $channel_id, 1, 1, $member_id, $member_info['member_name'], $template_info['message_id'], $template_info['purpose'], $status, '');
                } else {
                    if ($is_api == 1) {
                        $result['status'] = -1;
                        $result['error'] = '用户非微信登录';
                    }
                }
            }

            /*发送短信消息*/
            if ($template_info['sms_status'] == '1') {
                $store_id2 = empty($main_store_id) ? $store_id : $main_store_id;

                $sms_info = $this->checkStoreSms($store_id2, $channel_id, 1);
                if ($sms_info['status'] == '-1') {
                    $result['status'] = -1;
                    $result['error'] = $sms_info['error'];
                } else if (empty($member_info['bindtel'])) {
                    $result['status'] = -1;
                    $result['error'] = $result['error'] . '  该会员没有绑定手机号';
                } else {
                    $template_data2 = $template_info['sms_params'];
                    foreach ($datas as $key => $da) {
                        if (strpos($template_data2, '{' . $key . '}') !== false) {
                            $template_data2 = str_replace('{' . $key . '}', $da, $template_data2);
                        }
                    }
                    $check2 = $this->smsChinaSend($member_info['bindtel'], $template_data2, $sms_info['sms_header'], $sms_info['youe_account'], $sms_info['youe_password']);
                    logWrite('短信消息发送模板：' . $template_data2 . '\n\r结果:' . $check2, 'INFO');
                    if ($check2) {
                        $status = 1;
                        $store->where(array('store_id' => $store_id2))->setDec('sms_num', 1);
                    } else {
                        $status = 0;
                    }
                    $this->addMsgRecord($store_id2, $channel_id, 2, 1, $member_id, $member_info['member_name'], $template_info['message_id'], $template_info['purpose'], $status, $member_info['bindtel']);
                }
            }


        } else {
            if ($is_api == 1) {
                $result['status'] = -1;
                $result['error'] = '模板不存在';
            }
        }
        if ($is_api == 1) {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
    }

    /*发送收银机下单消息
     * params int $store_id 店铺ID
     * params int $order_id 订单ID
     * params int $score 可获得积分
     * params int $is_api 是否是api接口 1-是 0-不是
    */
    public function sendCashRegisterMsg()
    {
        $member = M('member');
        $tempalte = M('mb_msg_template');
        $store = M('store');
        $is_api = I('is_api', 1);
        if ($is_api == 1) {
            $store_id = I('store_id');
            $score = I('score', 0);
            $type = I('type');
            $order_id = I('order_id', 0);
            $message_id = 21;  //收银机下单提醒
            $result = array();
        }
        $w1 = array();
        $w1['xunxin_mb_msg_template.store_id'] = $store_id;
        $w1['xunxin_mb_msg_template.message_id'] = $message_id;
        $w1['xunxin_mb_msg_template.status'] = 1;
        $w1['xunxin_mb_msg_template.isdelete'] = 0;
        /*获取发送模板内容*/
        $template_info = $tempalte->join('xunxin_mb_template_message ON xunxin_mb_template_message.message_id = xunxin_mb_msg_template.message_id')->where($w1)->field('xunxin_mb_template_message.message_id,xunxin_mb_template_message.purpose,xunxin_mb_template_message.params,xunxin_mb_msg_template.remark,xunxin_mb_msg_template.template_id,xunxin_mb_msg_template.wx_status,xunxin_mb_msg_template.sms_status,xunxin_mb_template_message.sms_params')->find();
        $channel_id = $store->where(array('store_id' => $store_id))->getField('channel_id');
        $store_domain = $store->where(array('store_id' => $store_id))->getField('store_domain');
        if (empty($template_info)) {
            if ($channel_id > 0) {
                $main_store_id = $store->where(array('channel_id' => $channel_id, 'main_store' => 1))->getField('store_id');
                if (empty($store_domain)) {
                    $store_domain = $store->where(array('store_id' => $main_store_id))->getField('store_domain');
                }
                if (!empty($main_store_id)) {
                    $w1 = array();
                    $w1['xunxin_mb_msg_template.store_id'] = $main_store_id;
                    $w1['xunxin_mb_msg_template.message_id'] = $message_id;
                    $w1['xunxin_mb_msg_template.status'] = 1;
                    $w1['xunxin_mb_msg_template.isdelete'] = 0;
                    $template_info = $tempalte->join('xunxin_mb_template_message ON xunxin_mb_template_message.message_id = xunxin_mb_msg_template.message_id')->where($w1)->field('xunxin_mb_template_message.message_id,xunxin_mb_template_message.purpose,xunxin_mb_template_message.params,xunxin_mb_msg_template.remark,xunxin_mb_msg_template.template_id,xunxin_mb_msg_template.wx_status,xunxin_mb_msg_template.sms_status,xunxin_mb_template_message.sms_params')->find();
                }
            }
        }

        if (!empty($template_info)) {
            $order_info = M('mb_cashorder')->where(array('order_id' => $order_id))->field('order_sn,buyer_id,order_amount,create_time,store_name')->find();
            $member_id = $order_info['buyer_id'];
            $member_info = $member->where(array('member_id' => $member_id))->field('member_id,member_name,member_nickname,wx_openid,bindtel')->find();
            $open_id = $this->getUserOpenid($member_id, $store_id);
            $datas = array();
            $datas['REMARK'] = '获得积分：' . $score . '积分';
            $datas['ORDER_SN'] = $order_info['order_sn'];
            $datas['SCORE'] = $score;
            $datas['ORDER_MONEY'] = $order_info['order_amount'];
            $datas['STORE_NAME'] = $order_info['store_name'];
            $datas['TIME'] = date('Y-m-d H:i', $order_info['create_time']);

            /*获取发送用户信息*/
            if ($template_info['wx_status'] == '1') {
                if (!empty($open_id)) {
                    $template_data = $template_info['params'];
                    foreach ($datas as $key => $da) {
                        if (strpos($template_data, '{' . $key . '}') !== false) {
                            $template_data = str_replace('{' . $key . '}', $da, $template_data);
                        }
                    }
                    /*第三方订单默认跳转到会员中心*/
                    $host_url = empty($store_domain) ? URL() : 'http://' . $store_domain;
                    $url = $host_url . '/index.php?m=Service&c=User&a=user&se=' . $store_id;
                    $check = $this->sendWxTemplateMsg($open_id, $template_info['template_id'], $template_data, $url);
                    logWrite('必要参数：' . json_encode($datas) . '\n\r模板内容:' . $template_data . "\n\r 类型：收银机下单提醒" . "\n\r 会员ID:" . $member_id . "\n\r结果：" . json_encode($check), 'INFO');
                    if ($check == '1') {
                        if ($is_api == 1) {
                            $result['status'] = 1;
                        }
                        $status = 1;
                    } else {
                        if ($is_api == 1) {
                            $result['status'] = -1;
                            $result['error'] = $check;
                        }
                        $status = 0;
                    }
                    //添加记录
                    $store_id1 = empty($main_store_id) ? $store_id : $main_store_id;
                    $this->addMsgRecord($store_id1, $channel_id, 1, 1, $member_id, $member_info['member_name'], $template_info['message_id'], $template_info['purpose'], $status, '');
                } else {
                    if ($is_api == 1) {
                        $result['status'] = -1;
                        $result['error'] = '用户非微信授权账号';
                    }
                }
            }

            /*发送短信消息*/
            if ($template_info['sms_status'] == '1' && !empty($member_info)) {
                $store_id2 = empty($main_store_id) ? $store_id : $main_store_id;

                $sms_info = $this->checkStoreSms($store_id2, $channel_id, 1);
                if ($sms_info['status'] == '-1') {
                    $result['status'] = -1;
                    $result['error'] = $sms_info['error'];
                } else if (empty($member_info['bindtel'])) {
                    $result['status'] = -1;
                    $result['error'] = $result['error'] . '  该会员没有绑定手机号';
                } else {
                    $template_data2 = $template_info['sms_params'];
                    foreach ($datas as $key => $da) {
                        if (strpos($template_data2, '{' . $key . '}') !== false) {
                            $template_data2 = str_replace('{' . $key . '}', $da, $template_data2);
                        }
                    }
                    $check2 = $this->smsChinaSend($member_info['bindtel'], $template_data2, $sms_info['sms_header'], $sms_info['youe_account'], $sms_info['youe_password']);
                    logWrite('短信消息发送模板：' . $template_data2 . '\n\r结果:' . $check2, 'INFO');
                    if ($check2) {
                        $status = 1;
                        $store->where(array('store_id' => $store_id2))->setDec('sms_num', 1);
                    } else {
                        $status = 0;
                    }
                    $this->addMsgRecord($store_id2, $channel_id, 2, 1, $member_id, $member_info['member_name'], $template_info['message_id'], $template_info['purpose'], $status, $member_info['bindtel']);
                }
            }


        } else {
            if ($is_api == 1) {
                $result['status'] = -1;
                $result['error'] = '模板不存在';
            }
        }
        if ($is_api == 1) {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 通用短信平台HTTP接口POST_urldecode方式发送短信
     * 短信内容中带有空格、换行等特殊字符时调用此方法
     * @param $mobile 手机
     * @param $msg 消息内容
     * @return string
     */
    public function smsChinaSend($mobile = '', $msg = '', $sign = '', $uid = '', $pwd = '')
    {
        $url = "http://www.smsadmin.cn/smsmarketing/wwwroot/api/post_send_urldecode/";   //通用短信平台接口地址
        //$uid='microunite';         //您在通用短信平台上注册的用户ID
        $uid = mb_convert_encoding($uid, 'GB2312', 'UTF-8'); //内容为UTF-8时转码成GB2312
        //  $pwd= 'vjdxx8988998';         //用户密码
        //$msg="您本次的注册验证码678123，在30分钟内输入有效。POST_URLDECODE提交。【通用短信平台】";         //要发送的短信内容，必须要加签名，签名格式：【签名内容】
        $msg = '【' . $sign . '】' . $msg;
        $msg = mb_convert_encoding($msg, 'GB2312', 'UTF-8'); //内容为UTF-8时转码成GB2312
        //$mobile="13712345678;13712345679";      //接收短信的手机号码，多个手机号码用英文下的分号(;)间隔,最多不能超过1000个手机号码。
        $uid = urlencode($uid);
        $msg = urlencode($msg);
        $params = array(
            "uid" => $uid,
            "pwd" => $pwd,
            "mobile" => $mobile,
            "msg" => $msg,
            "dtime" => "",   //为空，表示立即发送短信;写入时间即为定时发送短信时间，时间格式：0000-00-00 00:00:00
            "linkid" => ""   //为空，表示没有流水号;写入流水号,获取状态报告和短信回复时返回流水号,流水号格式要求:最大长度不能超过32位，数字、字母、数字字母组合的字符串
        );

        $results = httpRequest($url, 'POST', $params)['data'];

        $results = mb_convert_encoding($results, 'GBK', 'GB2312');
        $str = "0发送成功!";
        $str = mb_convert_encoding($str, 'GBK', 'UTF-8');

        if ($results == $str) {
            return true;
        } else {
            $results = mb_convert_encoding($results, 'UTF-8', 'GB2312');
            logWrite($results, 'WARN');
            return false;
        }
    }

    /*
     *发送微信客服消息
     */
    private function sendWxCustomMsg($openid = '', $title = '', $template_info = '')
    {
        logWrite("发送参数:{$openid},{$title},{$template_info}");
        $access_token = $this->getAccessToken();
        $data = array();
        $data['touser'] = $openid;
        $data['msgtype'] = 'news';
        $data['news']['articles'][0]['title'] = $title;
        $data['news']['articles'][0]['description'] = $template_info;
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $json = $this->request_post('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $access_token, $json);
        logWrite("请求发送消息接口:" . $json);
        $rt = json_decode($json, true);
        if ($rt['errmsg'] == 'ok') {
            return '1';
        } else {
            logWrite($rt['errmsg'], 'WARN');
            return $rt['errmsg'];
        }
    }

    /*
    *发送微信模板消息
    */
    private function sendWxTemplateMsg($openid = '', $template_id = '', $template_data = '', $url = '')
    {
        logWrite("发送参数:{$openid},{$template_id},{$template_data}");
        $renew = '';
        $num = 1;
        do {
            $access_token = $this->getAccessToken();
            $data = json_decode($template_data, true);
            $template = array();
            $template['touser'] = $openid;
            $template['template_id'] = $template_id;
            $template['url'] = $url;
            $template['data'] = $data;
            $datas = json_encode($template, JSON_UNESCAPED_UNICODE);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access_token;
            $json = httpRequest($url, 'POST', $datas)['data'];
            $rt = json_decode($json, true);
            if ($rt['errcode'] == '40001') {
                $renew = '1';
            }
            $num++;
        } while ($rt['errcode'] == '40001' && $num < 3);


        logWrite("请求发送消息接口:" . $json);

        if ($rt['errmsg'] == 'ok') {
            return '1';
        } else {
            logWrite($rt['errmsg'], 'WARN');
            return $rt['errmsg'];
        }
    }

    /*获取用户OPENID*/
    private function getUserOpenid($member_id = '', $store_id = 0)
    {
        $openid = '';
        $storemember = M('mb_storemember');
        $w1 = array();
        $w1['store_id'] = $store_id;
        $w1['member_id'] = $member_id;
        $w1['isdelete'] = 0;
        $openid = $storemember->where($w1)->getField('wx_openid');
        if (!empty($openid)) {
            return $openid;
        } else {
            $member = M('member');
            $w2 = array();
            $w2['member_id'] = $member_id;
            $openid = $member->where($w2)->getField('wx_openid');
            return $openid;
        }
    }

    /*检查店铺是否符合发短信条件*/
    private function checkStoreSms($store_id = '', $channel_id = 0, $num = 1)
    {
        $channel = M('mb_channel');
        $store = M('store');
        $rt = array();
        if ($channel_id == 0) {
            $sms_num = $store->where(array('store_id' => $store_id))->getField('sms_num');
            if ($sms_num < $num) {
                $rt['status'] = -1;
                $rt['error'] = '短信条数不足,请充值';
            } else {
                $sms_info = $channel->where(array('channel_id' => 0, 'youe_is_platform' => 1))->field('sms_header,youe_account,youe_password')->find();
                $rt['status'] = 1;
                $rt['sms_header'] = $sms_info['sms_header'];
                $rt['youe_account'] = $sms_info['youe_account'];
                $rt['youe_password'] = $sms_info['youe_password'];
            }
        } else {
            $sms_info = $channel->where(array('channel_id' => $channel_id))->field('youe_is_platform,sms_header,youe_account,youe_password')->find();
            if (empty($sms_info['sms_header'])) {
                $rt['status'] = -1;
                $rt['error'] = '店铺短信签名未设置';
            } else {
                if ($sms_info['youe_is_platform'] == '1') {  //使用迅信平台短信
                    $sms_num = $store->where(array('store_id' => $store_id))->getField('sms_num');
                    if ($sms_num < $num) {
                        $rt['status'] = -1;
                        $rt['error'] = '短信条数不足,请充值';
                    } else {
                        $rt['status'] = 1;
                        $rt['sms_header'] = $sms_info['sms_header'];
                        $rt['youe_account'] = $sms_info['youe_account'];
                        $rt['youe_password'] = $sms_info['youe_password'];
                    }

                } else {
                    $rt['status'] = 1;
                    $rt['sms_header'] = $sms_info['sms_header'];
                    $rt['youe_account'] = $sms_info['youe_account'];
                    $rt['youe_password'] = $sms_info['youe_password'];
                }
            }

        }
        return $rt;
    }

    /**
     * 新增发送消息记录
     * params int $store_id 店铺ID
     * params int $channel_id 渠道ID
     * params tinyint $type 类型 1-微信消息 2-短信消息
     * params tinyint $style 1-模板消息 2-自定义消息
     * params int $member_id 会员ID
     * params varchar $member_name会员账号
     * params int $message_id 发送模板ID
     * params text $content 发送内容
     * params tinyint $status 发送结果 0-发送失败 1-发送成功
     */
    public function addMsgRecord($store_id = '', $channel_id = '', $type = '', $style = '', $member_id = '', $member_name = '', $message_id = '', $content = '', $status = '', $tel = '')
    {
        $m = M('mb_message_send_record');
        $da = array();
        $da['store_id'] = $store_id;
        $da['channel_id'] = $channel_id;
        $da['type'] = $type;
        $da['style'] = $style;
        $da['member_id'] = $member_id;
        $da['member_name'] = $member_name;
        $da['content'] = $content;
        $da['message_id'] = $message_id;
        $da['status'] = $status;
        $da['tel'] = $tel;
        $da['addtime'] = time();
        $m->add($da);
    }

}
