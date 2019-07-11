<?php

namespace Common\Common;

use Think\Log;

class commission
{

    protected $ratedata = array();
    protected $ratetype = 0;

    public function getParentname($member_name)
    {

        $data = Model('member')->field('recommend_name')->where(array('member_name' => $member_name))->find();
        return $data['recommend_name'];
    }

    // 预算佣金
    public function budget($order_id, $channel_id)
    {
        if (empty($channel_id)) {
            $channel_id = 0;
        }

        $orderdata = Model('mb_order')->field('storeid,buyer_id,order_pv,totalprice,pay_type')->where(array('order_id' => $order_id))->find();

        if (empty($orderdata['order_pv'])) {
            return false;
        }

        $this->getrate($channel_id, $orderdata['storeid']);
        if ($this->ratetype == 0) {
            return false;
        }
        //货到付款不算佣金
        if ($orderdata['cash_pay'] == 0 && $orderdata['pay_type'] == 0) {
            return false;
        }


        //余额支付不算佣金
//        if ($orderdata['balance_pay'] == 0 && $orderdata['pay_type'] == 3) {
//            return false;
//        }


//        if ($orderdata['order_pv'] > $orderdata['totalprice']) {
//            $orderdata['order_pv'] = $orderdata['totalprice'];
//        }

        $member = Model('member')->field('member_name')->where(array('member_id' => $orderdata['buyer_id']))->find();

        $name = $member['member_name'];
        $rm_member_id = $orderdata['buyer_id'];
        $data = array();
        $data[0]['member_name'] = $name;
        $data[0]['member_id'] = $orderdata['buyer_id'];
        $i = 1;
        $mainStoreId = getMainStoreId($orderdata['storeid']);
        while ($i < 4) {
            $rname = getStoreParentname($rm_member_id, $mainStoreId);
            if (empty($rname)) {
                break;
            }
            $data[$i]['member_name'] = $rname;
            $temp = Model('member')->field('member_id')->where(array('member_name' => $rname))->find();
            $data[$i]['member_id'] = $temp['member_id'];
            $name = $rname;
            $rm_member_id = $temp['member_id'];
            $i++;
        }
        Log::record("budget->data" . json_encode($data), Log::ERR);
        Log::record("budget->22rate" . json_encode($this->ratedata), Log::ERR);
        for ($i = 0; $i < count($data); $i++) {
//            $ratekey = 'rate' . $i;
//            $rate = $this->ratedata[$ratekey];

            $storeMemberData = Model('mb_storemember')->field('sum_score,level')->where(array(
                'member_id' => $data[$i]['member_id'],
                'store_id' => $mainStoreId
            ))->find();

            if ($storeMemberData['level'] == 0){
                $ratekey = "rate".$i;
            }else if ($storeMemberData['level'] == 1){
                $ratekey = "vip1_rate".$i;
            }else if ($storeMemberData['level'] == 2){
                $ratekey = "vip2_rate".$i;
            }else if ($storeMemberData['level'] == 3){
                $ratekey = "vip3_rate".$i;
            }



            $rate = $this->ratedata[$ratekey];

            Log::record($ratekey. "budget->rate" . $rate, Log::ERR);
            if (!empty($rate)) {

                $adata = array();
                $adata['channel_id'] = $channel_id;
                $adata['store_id'] = $orderdata['storeid'];
                $adata['order_id'] = $order_id;
                $adata['buyer_id'] = $orderdata['buyer_id'];

                $adata['buyer_name'] = $member['member_name'];
                Log::record("budget->rate111" . $rate, Log::ERR);
                $adata['order_pv'] = $orderdata['order_pv'];
                $adata['generation'] = $i;

                $adata['member_name'] = $data[$i]['member_name'];
                $adata['rate'] = $rate;
                $adata['money'] = $orderdata['order_pv'] * $rate;
                $adata['type'] = $this->ratetype;
                $adata['status'] = 0;
                $adata['create_time'] = time();
                 M('mb_distribution')->add($adata);
            }
        }

        return true;

    }

    //  修改订单佣金状态：2退单， 1结算完成
    public function changeStatus($order_id, $status, $channel_id)
    {
        Log::record("佣金状态" . $order_id, Log::ERR);
        if (empty($channel_id)) {
            $channel_id = 0;
        }


        $orderdata = Model('mb_order')->field('storeid,order_pv,pay_type,channelid')->where(array('order_id' => $order_id))->find();
        if ($channel_id != $orderdata['channelid']){
            $channel_id = $orderdata['channelid'];
        }
        if (empty($orderdata['order_pv'])) {
            return false;
        }

        $this->getrate($channel_id, $orderdata['storeid']);
        if ($this->ratetype == 0) {
            return false;
        }
        //货到付款不算佣金
        if ($orderdata['cash_pay'] == 0 && $orderdata['pay_type'] == 0) {
            return false;
        }


        //余额支付不算佣金
//        if ($orderdata['balance_pay'] == 0 && $orderdata['pay_type'] == 3) {
//            return false;
//        }


        $mdata = Model('mb_distribution')->where(array('order_id' => $order_id))->select();
        if ($status == 2) {
            Model('mb_distribution')->where(array('order_id' => $order_id))->update(array('status' => 2, 'return_time' => time()));
        } else if ($mdata[0]['status'] != 1) {
            Model('mb_distribution')->where(array('order_id' => $order_id))->update(array('status' => 1, 'finish_time' => time()));
            $data = Model('mb_distribution')->where(array('order_id' => $order_id))->select();
            for ($i = 0; $i < count($data); $i++) {
                $member_name = $data[$i]['member_name'];
                $money = $data[$i]['money'];
                $store_id = $data[$i]['store_id'];
                if ($channel_id > 0) {
                    $channel_data = Model("mb_channel")->where(array('channel_id' => $channel_id))->find();
                    if ($channel_data['store_type'] == 2) {
                        $main_store_data = Model("store")->where(array('channel_id' => $channel_id, 'main_store' => 1))->find();
                        $store_id = $main_store_data['store_id'];
                    }
                }
                $this->addwallet($channel_id, $member_name, $money, $store_id);

                $send_array = [];
                $send_array['se'] = $data[$i]['store_id'];
                $send_array['store_id'] = $data[$i]['store_id'];
                if ($data[$i]['generation'] == 1) {
                    $send_array['type'] = 10;
                } else if ($data[$i]['generation'] == 2) {
                    $send_array['type'] = 11;
                } else if ($data[$i]['generation'] == 3) {
                    $send_array['type'] = 12;
                } else if ($data[$i]['generation'] == 0) {
                    $send_array['type'] = 14;
                }
                $member_data = Model("member")->where(array('member_name' => $data[$i]['member_name']))->find();
                $send_array['member_id'] = $member_data['member_id'];
                $send_array['value'] = $data[$i]['money'];
                $send_array['order_id'] = $data[$i]['order_id'];
                $send_array['is_api'] = 1;
                $this->sendMessage($send_array);

            }
        }
        // $mdata = Model('mb_distribution')->where(array('order_id'=>$order_id))->select();
        // if (!empty($mdata)) {
        //     if ($status==2) {
        //         Model('mb_distribution')->where(array('order_id'=>$order_id))->update(array('status'=>2,'return_time'=>time()));
        //     }else if ($status==1 && $mdata[0]['status']==0){
        //         Model('mb_distribution')->where(array('order_id'=>$order_id))->update(array('status'=>1,'finish_time'=>time()));
        //         for ($i=0; $i <count($mdata) ; $i++) {
        //             $member_name = $mdata[$i]['member_name'];
        //             $money = $mdata[$i]['money'];
        //             $this->addwallet($channel_id,$member_name,$money);
        //         }
        //     }

        // }

        return true;
    }

    // 结算完成 加入提现钱包
    public function addwallet($channel_id, $member_name, $amount, $store_id)
    {
        $log_data = "[加入提现钱包] channel_id:" . $channel_id . "--member_name:" . $member_name . "--amount:" . $amount . "--store_id:" . $store_id;
        Log::record($log_data, Log::ERR);

        $result = Model('mb_wallet')->where(array('channel_id' => $channel_id, 'member_name' => $member_name, 'store_id' => $store_id))->find();
        if (empty($result)) {
            $tag = Model('mb_wallet')->insert(array('channel_id' => $channel_id, 'member_name' => $member_name, 'money' => $amount, 'store_id' => $store_id));
            if (!$tag) {
                $log_data = "[加入提现钱包]mb_wallet为空，插入失败";
                Log::record($log_data, Log::ERR);
            }
        } else {
            $money = $amount + $result['money'];
            $tag = Model('mb_wallet')->where(array('channel_id' => $channel_id, 'member_name' => $member_name, 'store_id' => $store_id))->update(array('money' => $money));
            if (!$tag) {
                $log_data = "[更新提现钱包] channel_id:" . $channel_id . "--member_name:" . $member_name . "--amount:" . $money . "--store_id:" . $store_id;
                Log::record($log_data, Log::ERR);
            }
        }

        $tag = Model('mb_walletrecord')->insert(array('channel_id' => $channel_id, 'store_id' => $store_id, 'member_name' => $member_name, 'money' => $amount, 'type' => 2, 'change_time' => TIMESTAMP));
        if (!$tag) {
            $log_data = "[插入提现钱包记录] channel_id:" . channel_id . "--member_name:" . $member_name . "--amount:" . $amount . "--store_id:" . $store_id;
            Log::record($log_data, Log::ERR);
        }

    }


    public function getrate($channel_id, $store_id)
    {

        $data1 = Model('mb_store_info')->where(array('store_id' => $store_id))->find();

        $data2 = Model('mb_channel')->where(array('channel_id' => $channel_id))->find();

        if ($data1['rateswitch'] == 1) {
            $this->ratedata = $data1;
            $this->ratetype = 1;
        } else if ($data2['rateswitch'] == 1) {
            $this->ratedata = $data2;
            $this->ratetype = 2;
        }
    }

    public function sendMessage($params = array())
    {
        // $send_message_url = 'http://m.duinin.com/';
        $send_message_url = SEND_MESSAGE_URL;
        $send_message_url = $send_message_url . "index.php?c=SendMessage&a=sendWxMsg";
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