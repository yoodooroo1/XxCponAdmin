<?php

namespace Common\Common;

/**
 * 发送短信类
 */
use Think\Log;
use Common\Common\CommonApi;

vendor('ApiWxPay.WxPay#Api');
vendor('ApiBizLib.BizWxPay#Api');

class ReceiveApi
{


    // 确认收货
    public function receivemethod($callday, $orderId)
    {

        $post_order_id = $orderId;
        if (!$post_order_id) {
            return "确认发货失败, 订单编号不能为空";
        }

        $this->checkRefundGoods($orderId);
        $condition = array();
        $condition['order_id'] = $post_order_id;
        $m = Model("mb_order");
        $state_data = $m->where($condition)->find();
        if ($state_data['pickup_id'] > 0) {
            $pickup_editor = empty($_POST['pickup_editor']) ? '' : $_POST['pickup_editor'];
        }
        if ($state_data['order_state'] != 7) {
            return "订单状态已改变,请刷新应用";
        }
        $version_max = $m->field('version')->order('version desc')->limit(1)->find();
        $max = $version_max['version'] + 1;
        //因为没有传memberid上来，所以只能查询memberid了，兼顾老版本
        $orderItem = $m->where(array('order_id' => $post_order_id))->find();
        $store_data = Model('store')->where(array('store_id' => $orderItem['storeid']))->find();
        //add by czx

        $commonApi = new CommonApi();
        $commonApi->orderSuccessSendMarket($orderItem);
        
        $data = array(
            'order_state' => 2,
            'issuccess' => 1,
            'receive_time' => TIMESTAMP,
            'version' => $max, 'pickup_editor' => $pickup_editor
        );
        $b = $m->where($condition)->update($data);
        if (!$b) {
            return "确认收货失败, 数据更新失败";
        }

        $credits_data = null;
        $send_sharknum = 0;


        //收货通知
        $send_array = [];
        $send_array['se'] = $orderItem['storeid'];
        $send_array['store_id'] = $orderItem['storeid'];
        $send_array['type'] = 3;
        $send_array['member_id'] = $orderItem['buyer_id'];
        $send_array['order_id'] = $post_order_id;
        $send_array['is_api'] = 1;
        $this->sendMessage($send_array);



        if (intval($orderItem['totalprice'] + $orderItem['balance'] + $orderItem['platform_balance'] + $orderItem['platform_coupons_money'] + $orderItem['platform_credits_exmoney'] + $orderItem['thirdpart_momey']) > 0) {
            $credits_data = $this->gouWucredits(5, intval($orderItem['totalprice'] + $orderItem['balance'] + $orderItem['platform_balance'] + $orderItem['platform_coupons_money'] + $orderItem['platform_credits_exmoney'] + $orderItem['thirdpart_momey']), $orderItem['buyer_id'], $orderItem['storeid'], $post_order_id);
        }
        if (intval($orderItem['totalprice']) > 0) {
            //$orderItem['totalprice']  总价格  $orderItem['buyer_id'] 订单生成者    $orderItem['storeid']  商铺id

            //赠送摇奖机会
            if (!empty($store_data)) {
                if ($store_data['order_sharknum'] != 0) {
                    if ($store_data['order_sharkmoney'] == 0) {
                        if (intval($orderItem['totalprice']) > 0) {
                            //赠送摇一摇次数
                            $my_shark = Model('mb_shark')->where(array(
                                'store_id' => $orderItem['storeid'],
                                'member_id' => $orderItem['buyer_id']
                            ))->find();
                            if (empty($my_shark)) {
                                $shark_model = Model('mb_shark');
                                $insert_sharkdata = array();
                                $insert_sharkdata['member_id'] = $orderItem['buyer_id'];
                                $insert_sharkdata['store_id'] = $orderItem['storeid'];
                                $insert_sharkdata['shark_time'] = TIMESTAMP;
                                $insert_sharkdata['send_sharknum'] = $store_data['order_sharknum'];
                                $tag = $shark_model->insert($insert_sharkdata);
                                if ($tag) {
                                    $m->where($condition)->update(array(
                                        'send_sharknum' => $store_data['order_sharknum']
                                    ));
                                }
                            } else {
                                $shark_model = Model('mb_shark');
                                $tag = $shark_model->where(array(
                                    'store_id' => $orderItem['storeid'],
                                    'member_id' => $orderItem['buyer_id']
                                ))->update(array(
                                    'send_sharknum' => $my_shark['send_sharknum'] + $store_data['order_sharknum']
                                ));
                                if ($tag) {
                                    $m->where($condition)->update(array(
                                        'send_sharknum' => $store_data['order_sharknum']
                                    ));
                                }
                            }
                        }
                    } else {
                        if (intval($orderItem['totalprice']) > 0) {
                            //赠送摇一摇次数
                            $rest = $orderItem['totalprice'] / $store_data['order_sharkmoney'];
                            $send_sharknum = (intval($rest)) * $store_data['order_sharknum'];
                            $my_shark = Model('mb_shark')->where(array(
                                'store_id' => $orderItem['storeid'],
                                'member_id' => $orderItem['buyer_id']
                            ))->find();
                            if (empty($my_shark)) {
                                $shark_model = Model('mb_shark');
                                $insert_sharkdata = array();
                                $insert_sharkdata['member_id'] = $orderItem['buyer_id'];
                                $insert_sharkdata['store_id'] = $orderItem['storeid'];
                                $insert_sharkdata['shark_time'] = TIMESTAMP;
                                $insert_sharkdata['send_sharknum'] = $send_sharknum;
                                $tag = $shark_model->insert($insert_sharkdata);
                                if ($tag) {
                                    $m->where($condition)->update(array(
                                        'send_sharknum' => $send_sharknum
                                    ));
                                }
                            } else {
                                $shark_model = Model('mb_shark');
                                $tag = $shark_model->where(array(
                                    'store_id' => $orderItem['storeid'],
                                    'member_id' => $orderItem['buyer_id']
                                ))->update(array(
                                    'send_sharknum' => $my_shark['send_sharknum'] + $send_sharknum
                                ));
                                if ($tag) {
                                    $m->where($condition)->update(array(
                                        'send_sharknum' => $send_sharknum
                                    ));
                                }
                            }
                        }
                    }
                }
            }
        }

        //增加VIP值
        $vipvalues = Model('mb_storemember')->where(array(
            'store_id' => $orderItem['storeid'],
            'member_id' => $orderItem['buyer_id']
        ))->select();
        $values = $vipvalues[0]['vip_values'];
        $level = $vipvalues[0]['level'];
        $sumvalues = $values + $orderItem['totalprice'] + $orderItem['balance'] + $orderItem['platform_balance'] + $orderItem['platform_coupons_money'] + $orderItem['platform_credits_exmoney'] + $orderItem['thirdpart_momey'];
        $maxlevel = Model('mb_storevip')->where(array(
            'store_id' => $orderItem['storeid']
        ))->max('vip_level');
        if ($level >= $maxlevel) {
            Model('mb_storemember')->where(array(
                'store_id' => $orderItem['storeid'],
                'member_id' => $orderItem['buyer_id']
            ))->update(array(
                'vip_values' => $sumvalues
            ));
        } else {
            $mlevelvalues = 0;
            if ($level > 0) {
                $leveldata1 = Model('mb_storevip')->where(array(
                    'store_id' => $orderItem['storeid'],
                    'vip_level' => $level
                ))->select();
                $mlevelvalues = $leveldata1[0]['vip_price'];
            }
            $mlevel = $level;
            $mvalues = $sumvalues;
            $leveldata = Model('mb_storevip')->where(array(
                'store_id' => $orderItem['storeid']
            ))->select();
            if ($leveldata) {
                for ($i = 0; $i < count($leveldata); $i++) {
                    if ($leveldata[$i]['vip_level'] > $mlevel) {
                        $levelprice = $leveldata[$i]['vip_price'] - $mlevelvalues;
                        if ($levelprice <= $sumvalues) {
                            $mlevel = $leveldata[$i]['vip_level'];
                            $mvalues = $sumvalues - $levelprice;
                        }
                    }
                }
            }
            Model('mb_storemember')->where(array(
                'store_id' => $orderItem['storeid'],
                'member_id' => $orderItem['buyer_id']
            ))->update(array(
                'vip_values' => $mvalues,
                'level' => $mlevel
            ));
        }

        $store_data = Model('store')->where(array('store_id' => $orderItem['storeid']))->find();
        if ($store_data['channel_id'] == 33) {
            $mainstore_data = Model('store')->where(array('channel_id' => $store_data['channel_id'], 'main_store' => 1))->find();
            $change_store_id = $mainstore_data['store_id'];
            //增加商城VIP值
            $vipvalues = Model('mb_storemember')->where(array(
                'store_id' => $change_store_id,
                'member_id' => $orderItem['buyer_id']
            ))->select();
            $values = $vipvalues[0]['vip_values'];
            $level = $vipvalues[0]['level'];
            $sumvalues = $values + $orderItem['totalprice'] + $orderItem['balance'] + $orderItem['platform_balance'];
            $maxlevel = Model('mb_storevip')->where(array(
                'store_id' => $change_store_id
            ))->max('vip_level');
            if ($level >= $maxlevel) {
                Model('mb_storemember')->where(array(
                    'store_id' => $change_store_id,
                    'member_id' => $orderItem['buyer_id']
                ))->update(array(
                    'vip_values' => $sumvalues
                ));
            } else {
                $mlevelvalues = 0;
                if ($level > 0) {
                    $leveldata1 = Model('mb_storevip')->where(array(
                        'store_id' => $change_store_id,
                        'vip_level' => $level
                    ))->select();
                    $mlevelvalues = $leveldata1[0]['vip_price'];
                }
                $mlevel = $level;
                $mvalues = $sumvalues;
                $leveldata = Model('mb_storevip')->where(array(
                    'store_id' => $change_store_id
                ))->select();
                if ($leveldata) {
                    for ($i = 0; $i < count($leveldata); $i++) {
                        if ($leveldata[$i]['vip_level'] > $mlevel) {
                            $levelprice = $leveldata[$i]['vip_price'] - $mlevelvalues;
                            if ($levelprice <= $sumvalues) {
                                $mlevel = $leveldata[$i]['vip_level'];
                                $mvalues = $sumvalues - $levelprice;
                            }
                        }
                    }
                }
                Model('mb_storemember')->where(array(
                    'store_id' => $change_store_id,
                    'member_id' => $orderItem['buyer_id']
                ))->update(array(
                    'vip_values' => $mvalues,
                    'level' => $mlevel
                ));
            }
        }

        // $commission = new commission();
        // $commission->budget($post_order_id,$this->comchannel_id);
        if ($orderItem['platform_balance'] > 0) {
            $trade_data = Model('mb_trade')->where(array(
                'out_trade_no' => 'platform_balance_' . $post_order_id
            ))->find();
            if (empty($trade_data)) {
                Model('mb_trade')->insert(array(
                    'total_fee' => $orderItem['platform_balance'] * 100,
                    'out_trade_no' => 'platform_balance_' . $post_order_id,
                    'attach' => 'platform_pay',
                    'order_id' => $orderItem['order_id'],
                    'store_id' => $orderItem['storeid'],
                    'member_id' => $orderItem['buyer_id'],
                    'paystate' => 1,
                    'pay_time' => TIMESTAMP,
                    'finish_time' => TIMESTAMP,
                    'channelid' => $this->comchannel_id
                ));
            }
        }

        if ($orderItem['platform_credits_exmoney'] > 0) {  //add by czx
            $trade_data = Model('mb_trade')->where(array(
                'out_trade_no' => 'platform_credits_exmoney_' . $post_order_id
            ))->find();
            if (empty($trade_data)) {
                Model('mb_trade')->insert(array(
                    'total_fee' => $orderItem['platform_credits_exmoney'] * 100,
                    'out_trade_no' => 'platform_credits_exmoney_' . $post_order_id,
                    'attach' => 'credits_pay',
                    'order_id' => $orderItem['order_id'],
                    'store_id' => $orderItem['storeid'],
                    'member_id' => $orderItem['buyer_id'],
                    'paystate' => 1,
                    'pay_time' => TIMESTAMP,
                    'finish_time' => TIMESTAMP,
                    'channelid' => $this->comchannel_id
                ));
            }
        }

        if ($orderItem['platform_coupons_money'] > 0) {  //add by czx
            $trade_data = Model('mb_trade')->where(array(
                'out_trade_no' => 'platform_coupons_money_' . $post_order_id
            ))->find();
            if (empty($trade_data)) {
                Model('mb_trade')->insert(array(
                    'total_fee' => $orderItem['platform_coupons_money'] * 100,
                    'out_trade_no' => 'platform_coupons_money_' . $post_order_id,
                    'attach' => 'coupons_pay',
                    'order_id' => $orderItem['order_id'],
                    'store_id' => $orderItem['storeid'],
                    'member_id' => $orderItem['buyer_id'],
                    'paystate' => 1,
                    'pay_time' => TIMESTAMP,
                    'finish_time' => TIMESTAMP,
                    'channelid' => $this->comchannel_id
                ));
            }
        }

        $nowtime = TIMESTAMP;
        $this->distribution_fund($post_order_id, $store_data['channel_id']);

        if ($state_data['pay_type'] == 1 || $state_data['pay_type'] == 4) {


            Model('mb_trade')->where(array(
                'out_trade_no' => $post_order_id
            ))->update(array(
                'paystate' => 1,
                'finish_time' => TIMESTAMP
            ));
            // if($nowtime-$orderItem['delivery_time']>=$store_data['settlement']){

            // $this->companypay($orderItem);
            // }
            $trade = Model('mb_trade')->field('channelid')->where(array('out_trade_no' => $post_order_id))->find();
//            $integral_data = Model('mb_channel')
//                ->field('integral_pv_switch,integral_rate0,integral_rate1,integral_rate2,integral_rate3')
//                ->where(array('channel_id'=>$this->comchannel_id))->find();
            $store_id = $store_data['store_id'];
            if ($store_data['channel_type'] == 2) {
                $mainstore_data = Model('store')->where(array('channel_id' => $store_data['channel_id'], 'main_store' => 1))->find();
                $store_id = $mainstore_data['store_id'];
            }
            $integral_data = Model('mb_store_info')->field('integral_pv_switch,integral_rate0,integral_rate1,integral_rate2,integral_rate3')->where(array('store_id' => $store_id))->find();
            if (empty($integral_data) || empty($integral_data['integral_pv_switch'])) {
                $commission = new commission();
                $commission->changeStatus($post_order_id, 1, $trade['channelid']);
            }
        } else {
            // add by chenqm 确认收货完成后，结算佣金
            $trade = Model('mb_trade')->field('channelid')->where(array('out_trade_no' => $post_order_id))->find();
//            $integral_data = Model('mb_channel')
//                ->field('integral_pv_switch,integral_rate0,integral_rate1,integral_rate2,integral_rate3')
//                ->where(array('channel_id'=>$this->comchannel_id))->find();
            $store_id = $store_data['store_id'];
            if ($store_data['channel_type'] == 2) {
                $mainstore_data = Model('store')->where(array('channel_id' => $store_data['channel_id'], 'main_store' => 1))->find();
                $store_id = $mainstore_data['store_id'];
            }
            $integral_data = Model('mb_store_info')->field('integral_pv_switch,integral_rate0,integral_rate1,integral_rate2,integral_rate3')->where(array('store_id' => $store_id))->find();
            if (empty($integral_data) || empty($integral_data['integral_pv_switch'])) {
                $commission = new commission();
                $commission->changeStatus($post_order_id, 1, $trade['channelid']);
            }
        }

        // hjun 2018-08-15 18:08:19 确认收货完成后 往storemember表里设置会员的首单ID
        D('StoreMember')->setStoreMemberFirstOrder($orderItem['storeid'], $orderItem['buyer_id'], $orderItem['order_id']);

        $return_data = array(
            'order_id' => $post_order_id,
            'ordertime' => $state_data['delivery_time'],
            'credits_data' => $credits_data,
            'send_sharknum' => $send_sharknum
        );
        return $return_data;
    }


    public function companypay($orderItem)
    {
        // add by chenqm 确认收货完成后，结算佣金
        $post_order_id = $orderItem['order_id'];
        $trade = Model('mb_trade')->field('channelid,incomestate,paystate')->where(array('out_trade_no' => $post_order_id))->find();
        if ($trade["incomestate"] != 0 || $trade["paystate"] != 1) {
            return;
        }

        if ($orderItem['totalprice'] > 0) {
            return;
        }

        $channel_id = $trade['channelid'];
        $commission = new commission();
        $commission->changeStatus($post_order_id, 1, $channel_id);
        $openid = Model('store')->field('openid,wx_nickname,store_name,member_name')->where(array(
            'store_id' => $orderItem['storeid']
        ))->find();
        if (empty($openid['openid'])) {
            return;
        }
        // $mtype = $orderItem['pay_type'];
        $mtype = 1;
        if ($mtype == 1) {
            $partner_trade_no = 'wx' . $orderItem['order_id'];
            $info["cash_type"] = '微信';
        } else {
            $partner_trade_no = 'ali' . $orderItem['order_id'];
            $info["cash_type"] = '支付宝';
        }
        $info['partner_trade_no'] = $partner_trade_no;
        $info['store_id'] = $orderItem['storeid'];
        $info['store_name'] = $openid["store_name"];
        $info['member_name'] = $openid["member_name"];
        $info['cash_kind'] = 0;
        $out_trade_nos = array();
        $out_trade_nos[0] = $orderItem['order_id'];
        $info['out_trade_nos'] = json_encode($out_trade_nos, JSON_UNESCAPED_UNICODE);
        $info['amount'] = ($orderItem['totalprice'] - $orderItem['order_pv']) * 100;
        $info['open_id'] = $openid['openid'];
        $info['cash_name'] = $openid['wx_nickname'];

        if ($info['amount'] <= 0) {
            Model('mb_trade')->where(array('out_trade_no' => $orderItem['order_id']))->update(array('incomestate' => 3, 'income_time' => TIMESTAMP));
            return;
        }

        $cashop = Model('mb_cashop')->where(array(
            'store_id' => $orderItem['storeid']
        ))->find();

        if (!empty($cashop)) {

            $input = new \BizWxPayCompanyQuery();
            $input->SetPartner_trade_no($cashop['partner_trade_no']);
            $wxdata = \BizWxPayApi::companyPayQuery($input);

            if ($wxdata['return_code'] == 'SUCCESS' && $wxdata['result_code'] == 'SUCCESS') {
                if (($wxdata['status'] == 'SUCCESS' || $wxdata['status'] == 'PROCESSING') && $wxdata['reason'] != "余额不足") {

                    $paylog = array(
                        'partner_trade_no' => $cashop['partner_trade_no'],
                        'openid' => $wxdata["openid"],
                        'amount' => $wxdata["payment_amount "],
                        'desc' => $wxdata["desc"],
                        'payment_no' => $wxdata["detail_id"],
                        'reason' => $wxdata['reason'],
                    );
                    $filename = 'paylogs/' . date("Ymd") . ".log";
                    $txt = date("Y-m-d h:i:s") . " : " . json_encode($paylog, JSON_UNESCAPED_UNICODE) . "\n";
                    file_put_contents($filename, $txt, FILE_APPEND);


                    $notfiyOutput = array();
                    $notfiyOutput["mchid"] = $wxdata["mch_id"];
                    $notfiyOutput["result_code"] = $wxdata["result_code"];
                    $notfiyOutput["payment_no"] = $wxdata["detail_id"];
                    $notfiyOutput["payment_time"] = $wxdata["transfer_time"];
                    $notfiyOutput["return_code"] = $wxdata["return_code"];
                    $notfiyOutput["end_time"] = time();
                    Model('mb_cash')->where(array(
                        'partner_trade_no' => $cashop['partner_trade_no']
                    ))->update($notfiyOutput);

                    $mout_trade_nos = json_decode($cashop['out_trade_nos'], true);
                    $mcash_kind = $cashop['cash_kind'];

                    $flag = Model('mb_cashop')->where(array('store_id' => $orderItem['storeid']))->delete();
                    if (empty($flag)) {
                        return;
                    }

                    if (empty($mcash_kind) || $mcash_kind == 0) {
                        for ($i = 0; $i < count($mout_trade_nos); $i++) {
                            $out_trade_no = $mout_trade_nos[$i];
                            $mflag = Model('mb_trade')->where(array('out_trade_no' => $out_trade_no))->update(array('incomestate' => 3, 'income_time' => TIMESTAMP));

//                            if ($mflag){ //add by czx  17-8-3
//
//                                $trade_data = Model('mb_trade')->where(array('out_trade_no'=> $out_trade_no))->find();
//                                if (!empty($trade_data)){
//                                    $mb_fund_store_detail_data = Model('mb_fund_store_detail')->where(array('order_id'=>$out_trade_no,'trade_type' =>0))->find();
//                                    if (!empty($mb_fund_store_detail_data) ){
//                                        $this->put_fund_sum_data($mb_fund_store_detail_data['trade_id'], $mb_fund_store_detail_data['owe_store_id'],
//                                            $mb_fund_store_detail_data['store_id'], -1 * $mb_fund_store_detail_data['money'],$out_trade_no,
//                                            0, 0 );
//                                        $this->out_fund_sum_data($out_trade_no);
//                                    }
//
//                                }
//                            }

                            if (empty($mflag)) {
                                return;
                            }

                        }

                    } else if ($mcash_kind == 1) {
                        $mflag = Model('mb_ad_case')->where(array('ad_id' => $mout_trade_nos[0]))->update(array('state' => 8));
                        if (empty($mflag)) {
                            return;
                        }

                    } else if ($mcash_kind == 2) {
                        $maxver = Model('mb_ad_flow')->max('version');
                        for ($i = 0; $i < count($mout_trade_nos); $i++) {
                            $flow_id = $mout_trade_nos[$i];
                            $mflag = Model('mb_ad_flow')->where(array('flow_id' => $flow_id))->update(array('state' => 4, 'version' => $maxver + 1));
                            if (empty($mflag)) {
                                return;
                            }
                        }
                    }
                    return;
                } else {
                    $flag = Model('mb_cashop')->where(array('store_id' => $orderItem['storeid']))->delete();
                    if (empty($flag)) {
                        return;
                    }
                }
            } else if ($wxdata['err_code'] == "NOT_FOUND") {

                $flag = Model('mb_cashop')->where(array('store_id' => $orderItem['storeid']))->delete();
                if (empty($flag)) {
                    return;
                }
            } else {
                return;
            }
        }


        if ($info['amount'] < 100) {
            return;
        }

        $cashdata = Model('mb_cash')->where(array('out_trade_nos' => $info['out_trade_nos']))->find();
        if (!empty($cashdata["payment_no"])) {
            return;
        }


        // 提现手续费和服务费
        $money = $info['amount'];
        $feedata = $this->getFeeData($openid["store_grade"], $channel_id);
        if (!empty($feedata['service_fee'])) {
            if ($money < $feedata['service_fee'] * 100) {
                return;
            }
            $money = intval($money - $feedata['service_fee'] * 100);
            if ($money < 100) {
                return;
            }
        }
        if (!empty($feedata['counter_fee'])) {
            $counterfee = $money * $feedata['counter_fee'];
            $money = intval($money - $counterfee);
            if ($money < 100) {
                return;
            }
        }
        $info['amount'] = $money;

        // 提现手续费和服务费 end

        $mdate = date("Y-m-d h:i:sa");
        $cashdata = array(
            'store_id' => $orderItem['storeid'],
            'store_name' => $info['store_name'],
            'amount' => $info['amount'],
            'out_trade_nos' => $info['out_trade_nos'],
            'cash_info' => '订单自动提现',
            'cash_time' => $mdate,
            'cash_kind' => $cash_kind,
            'partner_trade_no' => $partner_trade_no
        );

        $flag = Model('mb_cashop')->insert($cashdata);
        if (empty($flag)) {
            return;
        }
        $flag = Model('mb_cash')->insert($info);
        if (empty($flag)) {
            return;
        }
        $desc = "自动提现，购物订单号：" . $info['out_trade_nos'];

        if ($orderItem['order_pv'] > 0) {
            $desc = $desc . ",总价格：" . $orderItem['totalprice'] . "，总佣值：" . $orderItem['order_pv'];
        }

        // $this->submittrades($out_trade_nos);

        $data = $this->wxtopay($partner_trade_no, $info['amount'], $info['open_id'], $desc);

        if ($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {
            for ($i = 0; $i < count($out_trade_nos); $i++) {
                $out_trade_no = $out_trade_nos[$i];
                $mflag = Model('mb_trade')->where(array(
                    'out_trade_no' => $out_trade_no
                ))->update(array(
                    'incomestate' => 3,
                    'income_time' => TIMESTAMP
                ));


                if (empty($mflag)) {
                    return;
                }
            }
            $flag = Model('mb_cashop')->where(array(
                'store_id' => $orderItem['storeid']
            ))->delete();
            if (empty($flag)) {
                return;
            }
            return;
        }
        $flag = Model('mb_cashop')->where(array(
            'store_id' => $orderItem['storeid']
        ))->delete();
        if (empty($flag)) {
            return;
        }
        Model('mb_cash')->where(array(
            'partner_trade_no' => $info['partner_trade_no']
        ))->delete();
    }

    public function wxtopay($partner_trade_no, $amount, $openid, $desc)
    {
        $input = new \BizWxPayCompany();
        $input->SetPartner_trade_no($partner_trade_no);
        $input->SetAmount($amount);
        $input->SetCheck_name("NO_CHECK");
        $input->SetDesc($desc);
        $input->SetOpenid($openid);
        $data = \BizWxPayApi::companyPay($input);
        if ($data['return_code'] == 'SUCCESS' && $data['result_code'] == 'SUCCESS') {

            $paylog = array(
                'partner_trade_no' => $partner_trade_no,
                'openid' => $openid,
                'amount' => $amount,
                'desc' => $desc,
                'payment_no' => $data["payment_no"],
            );
            $filename = 'paylogs/' . date("Ymd") . ".log";
            $txt = date("Y-m-d h:i:s") . " : " . json_encode($paylog, JSON_UNESCAPED_UNICODE) . "\n";
            file_put_contents($filename, $txt, FILE_APPEND);


            $notfiyOutput = array();
            $notfiyOutput["mch_appid"] = $data["mch_appid"];
            $notfiyOutput["mchid"] = $data["mchid"];
            $notfiyOutput["device_info"] = $data["device_info"];
            $notfiyOutput["result_code"] = $data["result_code"];
            $notfiyOutput["payment_no"] = $data["payment_no"];
            $notfiyOutput["payment_time"] = $data["payment_time"];
            $notfiyOutput["return_code"] = $data["return_code"];
            $notfiyOutput["end_time"] = time();
            Model('mb_cash')->where(array(
                'partner_trade_no' => $partner_trade_no
            ))->update($notfiyOutput);
        }
        return $data;
    }


    public function getFeeData($store_grade, $channel_id)
    {
        $data1 = Model('mb_storegrade')->field('counter_fee,service_fee')->where(array('channelid' => $channel_id, 'store_grade' => $store_grade))->find();
        $data2 = Model('mb_channel')->field('counter_fee,service_fee')->where(array('channel_id' => $channel_id))->find();
        $data = array();

        if (!empty($data1['counter_fee'])) {
            $data['counter_fee'] = $data1['counter_fee'];
        } else {
            $data['counter_fee'] = $data2['counter_fee'];
        }


        if (!empty($data1['service_fee'])) {
            $data['service_fee'] = $data1['service_fee'];
        } else {
            $data['service_fee'] = $data2['service_fee'];
        }

        return $data;

    }

    //购物和退货添加积分
    public function gouWucredits($credits_type, $ordermoney, $member_id, $store_id, $order_id = 0)
    {
        $type = Model('mb_credits_type');
        $numitem = $type->where(array(
            'type_id' => $credits_type
        ))->find();
        $credits_name = $numitem['name'];
        if ($credits_type == 5) {
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
                        return ;
                    }
                } else {
                    return;
                }

            } else {
                return;
            }
        }
        $score = $ordermoney;
        $meberitem = Model('member')->field('member_name')->where(array(
            'member_id' => $member_id
        ))->find();
        $member_name = $meberitem['member_name'];
        $data = $this->changeCredit($store_id, $member_id, $member_name, $credits_type, $credits_name, $score, null, $order_id);
        //输出信息
        return $data;
    }

    public function changeCredit($store_id, $member_id, $member_name, $credit_type, $credit_name, $score, $reason, $order_id = 0)
    {

        Log::record("credit_01:".$score."->order:".$order_id, Log::ERR);

        global $CACHE_NO_USE;
        $store_data = Model('store')->where(array('store_id' => $store_id))->find();
        $channel_data = Model('mb_channel')->where(array('channel_id' => $store_data['channel_id']))->find();
        if (!empty($channel_data['credits_switch'])) {
            $mainstore_data = Model('store')->where(array('channel_id' => $store_data['channel_id'], 'main_store' => 1))->find();
            $store_id = $mainstore_data['store_id'];
        }
        $mainstore_id = $store_id;
        if ($store_data['channel_type'] == 2) {
            $mainstore_data = Model('store')->where(array('channel_id' => $store_data['channel_id'], 'main_store' => 1))->find();
            $mainstore_id = $mainstore_data['store_id'];
        }
        $integral_data = Model('mb_store_info')
            ->where(array('store_id' => $mainstore_id))->find();
        //$integral_data = Model('mb_channel')->field('integral_pv_switch,integral_rate0,integral_rate1,integral_rate2,integral_rate3')->where(array('channel_id'=>$store_data['channel_id']))->find();

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
            }else if ($selfStoreMember['level'] == 3){
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
            $CACHE_NO_USE = 1;
            $sumscores = $member->field('sum_score')->where(array(
                'member_id' => $member_id,
                'store_id' => $store_id
            ))->find();
            $CACHE_NO_USE = 0;
            $sum_score = $sumscores['sum_score'] + $score * $self_integral_rate;


            $mversion = $member->max('version');
            $flag = $member->where(array(
                'member_id' => $member_id,
                'store_id' => $store_id
            ))->update(array(
                'sum_score' => $sum_score,
                'version' => $mversion + 1
            ));
            Log::record("credit_00:".json_encode($data)."->rate:".$self_integral_rate, Log::ERR);

            $credit_id = $credits->insert($data, true); //往积分表里面添加积分项


            $data['credits_id'] = $credit_id;
            $data['sum_score'] = $sum_score;


            $name = $member_name;
            $rm_member_id = $member_id;
            $member_data = array();
            $member_data[0]['member_name'] = $name;
            $member_data[0]['member_id'] = $member_id;
            $i = 1;
            while ($i < 4) {
                $rname = getStoreParentname($rm_member_id, $mainstore_id);
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
            Log::record("credit_012:".json_encode($member_data), Log::ERR);
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
                Log::record("credit_02:".$i."->rate:".$rate, Log::ERR);
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

                    $mversion = $member->max('version');
                    $flag = $member->where(array(
                        'member_id' => $member_data[$i]['member_id'],
                        'store_id' => $store_id
                    ))->update(array(
                        'sum_score' => $sum_score,
                        'version' => $mversion + 1
                    ));
                    Log::record("credit_03:".json_encode($data_temp), Log::ERR);

                    $credit_id = $credits->insert($data_temp, true); //往积分表里面添加积分项

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
            $CACHE_NO_USE = 1;
            $sumscores = $member->field('sum_score')->where(array(
                'member_id' => $member_id,
                'store_id' => $store_id
            ))->find();
            $CACHE_NO_USE = 0;
            $sum_score = $sumscores['sum_score'] + $score;

            if ($sum_score < 0) {
                output_error(-5, '总积分数不够', '积分项插入数据库失败');
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

            $mversion = $member->max('version');
            $flag = $member->where(array(
                'member_id' => $member_id,
                'store_id' => $store_id
            ))->update(array(
                'sum_score' => $sum_score,
                'version' => $mversion + 1
            ));
            if (!$flag) {
                output_error(-4, $member_id . '修改积分失败' . $store_id, '更新会员总积分失败');
            }

            $credit_id = $credits->insert($data, true); //往积分表里面添加积分项
            if (!$credit_id) {
                output_error(-3, $member_id . '修改积分失败' . $store_id, '积分项插入数据库失败');
            }

            $data['credits_id'] = $credit_id;
            $data['sum_score'] = $sum_score;
            return $data;

        }
    }

    public function getParentname($member_name)
    {

        $data = Model('member')->field('recommend_name')->where(array('member_name' => $member_name))->find();
        return $data['recommend_name'];
    }

    /**
     * 资金分配
     * @param int $order_id 订单编号
     * @param int $channel_id 渠道编号
     * @return
     */
    public function distribution_fund($order_id, $channel_id)
    {

        $fund_trade_detail_one = Model("mb_fund_trade_detail")->where(array('order_id' => $order_id, 'trade_type' => 0))->find();
        if (empty($fund_trade_detail_one)) {
            $log_data = "distribution_fund_1->" . $order_id . "--channel_id--" . $channel_id . "。查询mb_fund_trade_detail表为空";
            Log::record($log_data, Log::ERR);
            return;
        }

        $store_data = Model("store")->where(array('store_id' => $fund_trade_detail_one['store_id']))->find();
        if (empty($store_data)) {
            $log_data = "distribution_fund_2->" . "暂未找到编号为:" . $fund_trade_detail_one['store_id'] . "的商家";
            Log::record($log_data, Log::ERR);
            return;

        }
       //防止重复提交
        try {
            $resultData = M("mb_fundorder_only")->add(array('order_id' => $order_id));
        } catch (\Exception $e) {
            logWrite("重复提交mb_fundorder_only:" . $e->getMessage());
            return ;
        }
        $log_data = "mb_fundorder_only->" . $resultData;
        Log::record($log_data, Log::ERR);
        if ($resultData == false) {
            return;
        }

        if ($store_data['channel_type'] == 2) {
            $account_money = $fund_trade_detail_one['xx_replace_collect'] + $fund_trade_detail_one['platform_replace_collect']
                + $fund_trade_detail_one['platform_exchange'] + $fund_trade_detail_one['platform_balance'];
            if ($account_money > 0) {
                $main_store_data = Model("store")->field('store_id')->where(array('channel_id' => $channel_id, 'main_store' => '1'))->find();
                if (!empty($main_store_data)) {
                    $this->put_fund_income_data($fund_trade_detail_one['id'], $main_store_data['store_id'], $fund_trade_detail_one['store_id'],
                        $account_money - $fund_trade_detail_one['commission'],
                        $fund_trade_detail_one['order_id'], 1, 0);
                }
            }
            if ($fund_trade_detail_one['xx_replace_collect'] > 0) {
                $this->put_fund_income_data($fund_trade_detail_one['id'], 0, $main_store_data['store_id'],
                    $fund_trade_detail_one['xx_replace_collect'], $fund_trade_detail_one['order_id'], 0, 0);

            }

        } else {
            if ($fund_trade_detail_one['xx_replace_collect'] > 0) {
                $this->put_fund_income_data($fund_trade_detail_one['id'], 0, $fund_trade_detail_one['store_id'],
                    $fund_trade_detail_one['xx_replace_collect'], $fund_trade_detail_one['order_id'], 0, 0);

            }
        }

        $tag = Model("mb_fund_trade_detail")->where(array('order_id' => $order_id, 'trade_type' => 0))->update(array('account_success' => 1, 'trade_success' => 1, 'update_time' => TIMESTAMP));
        if (!$tag) {
            $log_data = "distribution_fund_3_更新失败->" . "订单编号为:" . $order_id;
            Log::record($log_data, Log::ERR);
            return;
        }

    }

    /**
     * 添加资金收入
     * @param int $trade_id 资金明细编号
     * @param int $owe_store_id 收款商家编号
     * @param int $store_id 商家编号
     * @param int $money 变动金额
     * @param int $order_id 订单变薄
     * @param int $platform_type 资金来源迅信还是商城 （0迅信，1商城）
     * @param int $trade_type 交易类型 （0订单，1直接支付）
     * @return
     */
    public function put_fund_income_data($trade_id, $owe_store_id, $store_id, $money, $order_id, $platform_type, $trade_type)
    {
        global $CACHE_NO_USE;
        $CACHE_NO_USE = 1;
        $fund_store_sum_data = Model("mb_fund_store_sum")->where(array('store_id' => $store_id))->find();

        if (empty($fund_store_sum_data)) {
            Model("mb_fund_store_sum")->insert(array('store_id' => $store_id, 'owe_store_id' => $owe_store_id,
                'withdraw_money' => 0, 'withdraw_out' => 0, 'withdraw_ongoing' => 0, 'update_time' => TIMESTAMP));
            $fund_store_sum_data = Model("mb_fund_store_sum")->where(array('store_id' => $store_id))->find();
        }
        $mb_trade_data = Model("mb_trade")->where(array('order_id' => $order_id))->find();
        $CACHE_NO_USE = 0;
        $insert_data = array();
        $insert_data['trade_id'] = $trade_id;
        $insert_data['trade_type'] = $trade_type;
        $insert_data['order_id'] = $order_id;
        $insert_data['owe_store_id'] = $owe_store_id;
        $insert_data['store_id'] = $store_id;
        $insert_data['member_id'] = $mb_trade_data['member_id'];
        $insert_data['money'] = $money;
        $insert_data['withdraw_money'] = $fund_store_sum_data['withdraw_money'] + $money;
        $insert_data['create_time'] = TIMESTAMP;
        $insert_data['update_time'] = TIMESTAMP;
        $insert_data['is_delete'] = 0;
        $tag = Model("mb_fund_store_income_detail")->insert($insert_data);

        if ($tag) {
            $log_data = "put_fund_income_data_1->" . $tag . "--" . json_encode($insert_data);
            Log::record($log_data, Log::ERR);

            $update_data = array();
            $update_data['withdraw_money'] = $fund_store_sum_data['withdraw_money'] + $money;
            $update_data['update_time'] = TIMESTAMP;
            $tag = Model("mb_fund_store_sum")->where(array('store_id' => $store_id, 'owe_store_id' => $owe_store_id))->update($update_data);

            if ($tag) {
                $log_data = "put_fund_income_data_2->" . $tag . "--" . json_encode($update_data);
                Log::record($log_data, Log::ERR);

                //xunxin_mb_fund_store_detail_record
                $record_insert_data = array();
                $record_insert_data['store_id'] = $store_id;
                $record_insert_data['owe_store_id'] = $owe_store_id;
                $record_insert_data['change_money'] = $money;
                $record_insert_data['change_after_money'] = $fund_store_sum_data['withdraw_money'] + $money;
                //trade_type:0 商品下单  trade_type:1 直接支付
                $describe_str = '';
                if ($trade_type == 0) {
                    $describe_str = $describe_str . "[商品下单] 订单编号: " . $order_id . " 商家资金增加" . $money . "元";
                } else if ($trade_type == 1) {
                    $describe_str = $describe_str . "[直接支付] 交易编号: " . $order_id . " 商家资金增加" . $money . "元";
                } else if ($trade_type == 2) {
                    $describe_str = $describe_str . "[平台余额] 交易编号: " . $order_id . " 商家资金增加" . $money . "元";
                }
                $record_insert_data['`desc`'] = $describe_str;
                $record_insert_data['income_id'] = $order_id;
                $record_insert_data['cash_id'] = 0;
                $record_insert_data['create_time'] = TIMESTAMP;
                $tag = Model("mb_fund_store_detail_record")->insert($record_insert_data);
                if (!$tag) {
                    $log_data = "put_fund_income_data_3_插入失败->" . $tag . "--" . json_encode($record_insert_data);
                    Log::record($log_data, Log::ERR);
                    return;
                }

            } else {
                $log_data = "put_fund_income_data_2_更新失败->" . $tag . "--" . json_encode($update_data);
                Log::record($log_data, Log::ERR);
                return;
            }

        } else {
            $log_data = "put_fund_income_data_1_插入失败->" . $tag . "--" . json_encode($insert_data);
            Log::record($log_data, Log::ERR);
            return;
        }

    }

    /**
     * 提现通过
     * @param int $out_trade_no 订单编号
     * @return
     */
//    public function out_fund_sum_data($out_trade_no){
//           $wheres = array();
//           $wheres['order_id'] = $out_trade_no;
//           $wheres['operate_type'] = 1;
//           $wheres['incomestate'] = 0;
//           $store_detail_data = Model("mb_fund_store_detail")->where($wheres)->find();
//           if (!empty($store_detail_data)){
//               Model("mb_fund_store_detail")->where($wheres)->update(array('incomestate'=> 3, 'update_time'=> TIMESTAMP));
//           }
//
//    }

    public function sendMessage($params = array())
    {
        // $send_message_url = 'http://m.duinin.com/';
        $send_message_url = SEND_MESSAGE_URL;
        $send_message_url = $send_message_url . "index.php?c=SendMessage&a=sendWxMsg";
        $result_data = $this->postCurl($send_message_url, $params, 1);
        $log_data = "[postCurl]result_data:" . json_encode($result_data);
        Log::record($log_data, Log::ERR);
    }

    public function checkRefundGoods($order_id)
    {
        $back_order_goods_data = Model("mb_back_order_goods")->where(array('order_id' => $order_id))->select();
        foreach ($back_order_goods_data as $key => $val) {
            if ($val['line_goods_price'] > 0 && ($val['pay_type'] == 1 || $val['pay_type'] == 4) && $val['line_pay_refund_state'] == 0) {
                output_error(-11, '单品退款尚未完成,不能完成交易', '单品退款尚未完成,不能完成交易');
            }

            if ($val['platform_credits_exmoney'] > 0 && $val['platform_credits_exmoney_refund_state'] == 0) {
                output_error(-11, '单品退款尚未完成,不能完成交易', '单品退款尚未完成,不能完成交易');
            }

            if ($val['platform_balance'] > 0 && $val['platform_balance_refund_state'] == 0) {
                output_error(-11, '单品退款尚未完成,不能完成交易', '单品退款尚未完成,不能完成交易');
            }


            if ($val['thirdpart_momey'] > 0 && $val['thirdpart_money_refund_state'] == 0) {
                output_error(-11, '单品退款尚未完成,不能完成交易', '单品退款尚未完成,不能完成交易');
            }

            if ($val['credits_num'] > 0 && $val['credits_exmoney_refund_state'] == 0) {
                output_error(-11, '单品退款尚未完成,不能完成交易', '单品退款尚未完成,不能完成交易');
            }
        }
    }

    /**
     * 以post方式提交数据到对应的接口url
     * @param string $params 需要post的数据
     * @param string $url url
     * @return string $data 返回请求结果
     */
    public function postCurl($url, $params = array(), $times = 1)
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

}

?>