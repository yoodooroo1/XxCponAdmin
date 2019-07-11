<?php

namespace Common\Model;


class GroupBuyOrderModel extends BaseModel
{
    protected $tableName = 'mb_group_buying_order';

    /**
     * 定时任务 下单
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-08 17:20:20
     * Update: 2018-02-08 17:20:20
     * Version: 1.00
     */
    public function addGroupOrderToOrder()
    {
        // 查询已经成团的订单
        $field = [
            'a.*',
            'b.group_return', 'b.return_rule', 'b.join_num', 'b.base_num'
        ];
        $field = implode(',', $field);
        $join = [
            '__MB_GROUP_BUYING__ b ON a.group_id = b.group_id'
        ];
        $nowTime = NOW_TIME;
        $where = [];
        $where['a.pay_success'] = 1;
        $where['a.group_complete_status'] = 1;
        $where['a.add_order_success'] = 0;
        $where['_string'] = "b.group_status = 2 AND (b.end_time <= {$nowTime} OR b.close_status = 2)";
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $options['order'] = 'order_id ASC';
        $result = $this->queryList($options);
        $sql = $this->getQuerySql($options);
        logWrite("查询SQL-团购下单:{$sql}");
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        if (!empty($list)) {
            $condition = [];
            $condition['json_field'] = [
                'return_rule'
            ];
            foreach ($list as $key => &$order) {
                // 转换数据
                $order = $this->transFormInfo($order, $condition);
                // 如果开启返现 则判断到达哪个等级 更新订单的数据 线上支付才有返利
                logWrite("订单详情:" . json_encode($order, JSON_UNESCAPED_UNICODE));
                $returnMoney = 0;
                if ($order['group_return'] == 1 && !empty($order['return_rule']) && $order['pay_type'] > 0) {
                    $order['has_join_num'] = $order['join_num'] + $order['group_num'];
                    logWrite("参团人数:" . $order['has_join_num']);
                    foreach ($order['return_rule'] as $k => $val) {
                        if ($order['has_join_num'] >= $val['num']) {
                            $returnMoney = empty($val['return_money']) ? 0 : round($val['return_money'] * $order['buy_num'], 2);
                        }
                    }
                    if ($returnMoney > 0) {
                        $where = [];
                        $where['order_id'] = $order['order_id'];
                        $result = $this->where($where)->setField('return_money', $returnMoney);
                        if (false === $result) {
                            logWrite("更新返利金额出错:" . $this->getError() . '-' . $this->getDbError());
                            continue;
                        }
                    }
                }

                // 下单
                $storeInfo = D('Store')->getStoreInfo($order['store_id'])['data'];
                $data = [];
                $data['buyer_id'] = $order['member_id'];
                $data['date_from'] = $storeInfo['date_from'];
                // 如果返利的钱大于支付的钱 则不返利
                if ($order['pay_price'] - $returnMoney < 0) {
                    $returnMoney = 0;
                }
                $data['total_price'] = $order['pay_price'] - $returnMoney;
                $payType = [0, 1, 4];
                $payName = ['线下支付', '微信支付', '支付宝支付'];
                $data['pay_type'] = $payType[$order['pay_type']];
                $data['pay_name'] = $payName[$order['pay_type']];
                $data['channel_id'] = $order['channel_id'];
                $data['merge_store_id'] = $order['store_id'];
                $data['order_sn'] = $order['order_sn'];
                $data['order_type'] = 'tg';
                $data['group_id'] = $order['group_id'];
                $data['mergeorder_content'][] = [
                    'storeid' => $order['store_id'],
                    'buyer_id' => $order['member_id'],
                    'balance' => 0,
                    'store_name' => $storeInfo['store_name'],
                    'gou_type' => 0,
                    'gou_info' => '',
                    'pay_type' => $payType[$order['pay_type']],
                    'pay_name' => $payName[$order['pay_type']],
                    'order_msg' => $order['remark'],
                    'totalprice' => $order['pay_price'] - $returnMoney,
                    'group_id' => $order['group_id'],
                    'group_return_money' => $returnMoney,
                    'address' => $order['address'],
                    'order_membertel' => $order['member_tel'],
                    'order_membername' => $order['consignee'],
                    'get_to_store' => 0,
                    'postage' => $order['postage'],
                    'pay_id' => $order['pay_id'],
                    'order_pv' => $order['order_pv'],
                    "idcard" => '',
                    "truename" => '',
                    "thirdpart_momey" => '',
                    "coupons_exmoney" => 0,
                    "pickup_id" => 0,
                    "pickup_address" => '',
                    "pickup_tel" => '',
                    "pickup_name" => '',
                    "pickup_longitude" => 0,
                    "pickup_latitude" => 0,
                    "pickup_store_name" => '',
                    'order_content' => [
                        [
                            'goods_id' => $order['goods_id'],
                            'specid' => $order['spec_id'],
                            'goods_price' => $order['goods_group_price'],
                            'spec_name' => $order['spec_name'],
                            'goods_name' => $order['goods_name'],
                            'goods_figure' => [['url' => $order['goods_image']]],
                            'gou_num' => $order['buy_num'],
                            'state' => 0,
                            'group_id' => $order['group_id'],
                            'group_return_money' => $returnMoney,
                        ]
                    ],
                ];
                $params = [];
                $params['user_type'] = 'buyer';
                $params['member_id'] = $order['member_id'];
                $params['store_id'] = $order['store_id'];
                $params['key'] = $this->getApiToken($order['member_id']);
                $params['client'] = 'wap';
                $params['comchannel_id'] = $order['channel_id'];
                $params['ios_version'] = 1.81;
                $params['buy_code'] = '';
                $params['content'] = json_encode($data, JSON_UNESCAPED_UNICODE);
                $baseUrl = C('new_api_url');
                $apiUrl = "$baseUrl/?act=neworder&op=addorder";
                $result = httpRequest($apiUrl, 'post', $params);
                if ($result['code'] == 200) {
                    $addSuccess = 0;
                    $returnData = json_decode($result['data'], 1);
                    if ($returnData["result"] == 0) {
                        $quehuoarray = $returnData['datas'];
                        if (array_key_exists('isok', $quehuoarray)) {
                            if ($returnData['datas']['isok'] == 1) {
                                // 下单成功更新状态
                                $where = [];
                                $where['order_id'] = $order['order_id'];
                                $result = $this->where($where)->setField('add_order_success', 1);
                                if ($result !== false) {
                                    $addSuccess = 1;
                                }
                            }
                        }
                    }
                    $this->addLog($order['order_id'], $data, $returnData, $addSuccess);
                }
            }
            usleep(20);
        }
        return getReturn(200, '');
    }

    public function addLog($orderId = 0, $orderInfo = [], $result = [], $addSuccess = 0)
    {
        $data = [];
        $data['order_id'] = $orderId;
        $data['success'] = $addSuccess;
        $data['group_id'] = $orderInfo['group_id'];
        $data['msg'] = json_encode($result, JSON_UNESCAPED_UNICODE);
        $data['data'] = json_encode($orderInfo, JSON_UNESCAPED_UNICODE);
        $data['add_time'] = NOW_TIME;
        M('mb_group_order_add_log')->add($data);
    }

    /**
     * 未成团订单回滚库存和销量
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-10 13:52:06
     * Update: 2018-02-10 13:52:06
     * Version: 1.00
     */
    public function rollbackOrderGoods()
    {
        // 查询未成团的订单
        $field = [
            'a.order_id', 'a.goods_id', 'a.spec_id', 'a.buy_num'
        ];
        $field = implode(',', $field);
        $join = [
            '__MB_GROUP_BUYING__ b ON a.group_id = b.group_id'
        ];
        $where = [];
        $where['a.pay_success'] = 1;
        $where['a.group_complete_status'] = 0;
        $where['a.add_order_success'] = 0;
        $where['a.rollback_goods_success'] = 0;
        $where['b.group_status'] = 1;
        $map = [];
        $map['b.end_time'] = ['elt', NOW_TIME];
        $map['b.close_status'] = 2;
        $map['_logic'] = 'or';
        $where['_complex'] = $map;
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $result = $this->queryList($options);
        $sql = $this->getQuerySql($options);
        logWrite("回滚订单商品SQL:" . $sql);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $this->startTrans();
            $result = $this->rollbackStorageAndSales($value['goods_id'], $value['spec_id'], $value);
            if ($result['code'] !== 200) {
                $this->rollback();
                continue;
            }
            // 更新库存后设置已回滚
            $where = [];
            $where['order_id'] = $value['order_id'];
            $result = $this->where($where)->setField('rollback_goods_success', 1);
            if (false === $result) {
                $this->rollback();
                continue;
            }
            $this->commit();
        }

        return getReturn(200, '');
    }

    /**
     * 增加库存 减少销量
     * @param int $goodsId
     * @param string $specId
     * @param array $orderInfo
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-08 15:32:50
     * Update: 2018-02-08 15:32:50
     * Version: 1.00
     */
    public function rollbackStorageAndSales($goodsId = 0, $specId = '', $orderInfo = [])
    {
        $model = M('goods');
        $specId = empty($specId) ? 0 : $specId;
        $goodsInfo = $model->field('goods_id,goods_storage,goods_spec,spec_open')->find($goodsId);
        logWrite("增加库存,商品信息:" . json_encode($goodsInfo, JSON_UNESCAPED_UNICODE));
        if (!empty($goodsInfo)) {
            $maxVersion = $model->max('version');
            $data = [];
            if ($goodsInfo['spec_open'] == 0) {
                // 旧规格
                $spec = json_decode($goodsInfo['goods_spec'], 1);
                if (empty($spec) && $goodsInfo['goods_storage'] != -1) {
                    // 无规格
                    $data = [];
                    $data['version'] = $maxVersion + 1;
                    $data['goods_storage'] = ['exp', "goods_storage+{$orderInfo['buy_num']}"];
                } elseif (!empty($spec)) {
                    $specLength = count($spec);
                    foreach ($spec as $key => $value) {
                        if ($value['spec_id'] == $specId && $value['storage'] != -1) {
                            $data = [];
                            $data['version'] = $maxVersion + 1;
                            $storage = $value['storage'] + $orderInfo['buy_num'];
                            $storage = $storage <= 0 ? 0 : $storage;
                            if ($specLength <= 1) {
                                $data['goods_storage'] = ['exp', "goods_storage+{$orderInfo['buy_num']}"];
                            }
                            $spec[$key]['storage'] = $storage;
                            $data['goods_spec'] = json_encode($spec, JSON_UNESCAPED_UNICODE);
                            break;
                        }
                    }
                }
                if (!empty($data)) {
                    // 更新库存
                    $where = [];
                    $where['goods_id'] = $goodsId;
                    $result = $model->where($where)->save($data);
                    if (false === $result) {
                        logWrite("更新库存出错:" . $model->getError() . '-' . $model->getDbError());
                        return getReturn();
                    }
                }
                // 减少销量
                $where = [];
                $where['goods_id'] = $goodsId;
                $modelGE = M('mb_goods_exp');
                $goodsExp = $modelGE->where($where)->find();
                $data = [];
                $data['version'] = $modelGE->max('version') + 1;
                if ($goodsExp['sales_vol'] - $orderInfo['buy_num'] > 0) {
                    $data['sales_vol'] = ['exp', "sales_vol-{$orderInfo['buy_num']}"];
                } else {
                    $data['sales_vol'] = 0;
                }
                $result = $modelGE->where($where)->save($data);
                if (false === $result) {
                    logWrite("更新销量出错:" . $modelGE->getError() . '-' . $modelGE->getDbError());
                    return getReturn();
                }

            } else {
                // 新规格
                $where = [];
                $where['goods_id'] = $goodsId;
                $where['specs'] = $specId;
                $modelGO = M('goods_option');
                $spec = $modelGO->where($where)->find();
                logWrite("多规格信息:" . json_encode($spec, JSON_UNESCAPED_UNICODE));
                if (!empty($spec)) {
                    $sales = $spec['sales'] - $orderInfo['buy_num'];
                    $sales = $sales <= 0 ? 0 : ['exp', "sales-{$orderInfo['buy_num']}"];
                    $data = [];
                    if ($spec['stock'] != -1) {
                        $data['stock'] = ['exp', "stock+{$orderInfo['buy_num']}"];
                    }
                    $data['sales'] = $sales;
                    $where = [];
                    $where['id'] = $spec['id'];
                    $result = $modelGO->where($where)->save($data);
                    if (false === $result) {
                        logWrite("更新库存销量出错:" . $modelGO->getError() . '-' . $modelGO->getDbError());
                        return getReturn();
                    }
                    $where = [];
                    $where['goods_id'] = $goodsId;
                    $data = [];
                    $data['version'] = $maxVersion + 1;
                    $result = $model->where($where)->save($data);
                    if (false === $result) {
                        logWrite("更新商品版本号出错:" . $model->getError() . '-' . $model->getDbError());
                        return getReturn();
                    }
                }
            }
        }
        return getReturn(200, '');
    }

    /**
     * 定时返利
     * User: hjun
     * Date: 2018-02-08 21:18:49
     * Update: 2018-02-08 21:18:49
     * Version: 1.00
     */
    public function refundReturnMoney()
    {
        // 查询已经成团的订单
        $field = [
            'a.*',
        ];
        $field = implode(',', $field);
        $where = [];
        $where['a.pay_success'] = 1;
        $where['a.pay_type'] = 1;
        $where['a.return_status'] = 0;
        $where['a.return_money'] = ['gt', 0];
        $where['a.group_complete_status'] = 1;
        $where['a.add_order_success'] = 1;
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['where'] = $where;
        $options['order'] = 'order_id ASC';
        $result = $this->queryList($options);
        $sql = $this->getQuerySql($options);
        logWrite("查询SQL-团购返利:{$sql}");
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $this->startTrans();
        if (!empty($list)) {
            foreach ($list as $key => &$order) {

            }
        }
        $this->commit();
        return $result;
    }

    /**
     * 获得要调用的API的通行证
     * @param int $memberId
     * @return string $token
     */
    public function getApiToken($memberId = 0)
    {
        $Token = M('mb_user_token');
        $where = array();
        $where['member_id'] = $memberId;
        $key = $Token->where($where)->find();
        if (empty($key['token'])) {
            if (!empty($where['member_id'])) {
                $member_data = M('member')->where($where)->find();
                $key['token'] = $this->_get_token($where['member_id'], $member_data['member_name'], 'web');
            }
        }

        return $key['token'];
    }

    /**
     * 登陆生成token
     */
    private function _get_token($member_id, $member_name, $client)
    {
        $model_mb_user_token = M('mb_user_token');
        //重新登陆后以前的令牌失效
        //暂时停用
        $condition = array();
        $condition['member_id'] = $member_id;
        $condition['client_type'] = $client;
        $model_mb_user_token->delete($condition);
        //生成新的token
        $mb_user_token_info = array();
        $token = md5($member_name . strval(NOW_TIME) . strval(rand(0, 999999)));
        $mb_user_token_info['member_id'] = $member_id;
        $mb_user_token_info['member_name'] = $member_name;
        $mb_user_token_info['token'] = $token;
        $mb_user_token_info['login_time'] = NOW_TIME;
        $mb_user_token_info['client_type'] = $client;
        $result = $model_mb_user_token->add($mb_user_token_info);
        if ($result) {
            return $token;
        } else {
            return null;
        }
    }

    /**
     * 获取参与列表
     * @param int $groupId
     * @param int $storeId
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-09 02:02:53
     * Update: 2018-02-09 02:02:53
     * Version: 1.00
     */
    public function getJoinMemberList($groupId = 0, $storeId = 0, $channelId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $field = [
            'a.order_id', 'a.member_id', 'a.spec_name', 'a.create_time',
            'a.consignee', 'a.member_tel', 'a.address', 'a.group_complete_status',
            'a.refund_status', 'a.buy_num', 'a.pay_type',
            'b.member_name', 'b.member_nickname',
            'c.group_status', 'c.close_status', 'c.end_time'
        ];
        $field = implode(',', $field);
        $join = [
            '__MEMBER__ b ON a.member_id = b.member_id',
            '__MB_GROUP_BUYING__ c ON a.group_id = c.group_id'
        ];
        $where = [];
        $where['a.store_id'] = $storeId;
        $where['a.group_id'] = $groupId;
        $where['a.pay_success'] = 1;
        $where = array_merge($where, $condition);
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $condition = [];
        $condition['time_field'] = [
            'create_time' => ''
        ];
        $condition['map_field'] = [
            'group_status' => ['', '待成团', '已成团'],
            'refund_status' => ['未退款', '已退款'],
        ];
        $showRefund = 0;
        foreach ($list as $key => &$order) {
            $order = $this->transFormInfo($order, $condition);
            // 昵称
            $nickName = empty($order['member_nickname']) ? '' : "[{$order['member_nickname']}]";
            $order['name'] = "{$order['member_name']}{$nickName}";
            // 备注
            if ($order['group_status'] == 1 && ($order['end_time'] <= NOW_TIME || $order['close_status'] == 2)) {
                $order['group_status_name'] = "未成团,{$order['refund_status_name']}";
                if ($order['refund_status'] == 0 && $order['pay_type'] == 1){
                    $showRefund = 1;
                }
            }
        }
        $result['data']['list'] = $list;
        $result['data']['show_refund'] = $showRefund;
        return $result;
    }

    public function transFormInfo($info = [], $condition = [])
    {
        $info = parent::transformInfo($info, $condition);

        if (isset($info['refund_status'])) {
            if ($info['pay_type'] == 0) {
                $info['refund_status_name'] = '已退款';
            }
        }
        return $info;
    }

}