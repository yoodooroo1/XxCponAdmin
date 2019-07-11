<?php

namespace Common\Model;

class OrderModel extends BaseModel
{
    protected $tableName = 'mb_order';

    /**
     * 获取商家ID的搜索条件
     * @param string $queryStoreId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-08-14 10:04:16
     * Update: 2018-08-14 10:04:16
     * Version: 1.00
     */
    private function getStoreIdWhere($queryStoreId = '')
    {
        $queryStoreId = explode(',', $queryStoreId);
        return getInSearchWhereByArr($queryStoreId);
    }

    /**
     * 获取订单状态的数量
     * 2-代付款 3-待接单 4-待发货 5-已发货 6-已完成 7-已退款 8-已关闭
     * @param string $queryStoreId
     * @param int $orderStatus
     * @param array $map
     * @return array
     * User: hj
     * Date:
     */
    public function getOrderCount($queryStoreId = '', $orderStatus = 0, $map = [], $member_tag = 0)
    {
        logWrite("获取订单各个状态的数量: 参数列表 " . json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
        $where = [];
        $where['a.storeid'] = $this->getStoreIdWhere($queryStoreId);
        $where['a.business_close_state'] = 0;
        $where['a.cancel_nopay'] = 0;
        $where['a.isdelete'] = NOT_DELETE;
        $where = array_merge($where, $map);
        $this
            ->field('count(*) num,a.order_state,a.issuccess,a.cancel_nopay,a.get_to_store')
            ->alias('a')
            ->join('LEFT JOIN __MEMBER__ b ON b.member_id = a.buyer_id');
        if ($member_tag == 1) {
            $this->join('LEFT JOIN xunxin_mb_storemember_tag T on T.mid = a.buyer_id');
        }
        foreach ($where as $key => $value) {
            if (strpos($key, 'd.') !== false || strpos($where['_string'], 'd.') !== false) {
                $this->join("LEFT JOIN __MB_FUND_TRADE_DETAIL__ d ON d.order_id = a.order_id");
                break;
            }
        }
//            ->join('LEFT JOIN __MB_STOREMEMBER__ c ON c.store_id = a.storeid AND c.member_id = a.buyer_id')
        $list = $this
            ->join('__STORE__ e ON e.store_id = a.storeid')
            ->where($where)
            ->group('a.order_state,a.issuccess,a.cancel_nopay,a.get_to_store')
            ->select();
        logWrite("订单列表 - 查询数量：" . $this->_sql());
        $numData = [];
        $numData['全部'] = 0;
        $numData['待付款'] = 0;
        $numData['待接单'] = 0;
        $numData['待发货'] = 0;
        $numData['待自提'] = 0;
        $numData['已发货'] = 0;
        $numData['已完成'] = 0;
        $numData['已退款'] = 0;
        $numData['已关闭'] = 0;
        foreach ($list as $key => $value) {
            $numData['全部'] += $value['num'];
            switch ((int)$value['order_state']) {
                case 6:
                    // hj 2017-09-30 10:02:35 已取消的不算进来
                    if ($value['cancel_nopay'] == 0) $numData['待付款'] += $value['num'];
                    break;
                case 0:
                    $numData['待接单'] += $value['num'];
                    break;
                case 1:
                    if ($value['get_to_store'] > 0) {
                        $numData['待自提'] += $value['num'];
                    } else {
                        $numData['待发货'] += $value['num'];
                    }
                    break;
                case 7:
                    $numData['已发货'] += $value['num'];
                    break;
                case 2:
                    if ($value['issuccess'] == 1) {
                        $numData['已完成'] += $value['num'];
                    }
                    break;
                case 5:
                    $numData['已退款'] += $value['num'];
                    break;
                case 4:
                    if ($value['issuccess'] == 1) {
                        $numData['已关闭'] += $value['num'];
                    } else {
                        $numData['已退款'] += $value['num'];
                    }
                    break;
                case 3:
                    if ($value['issuccess'] == 1) {
                        $numData['已关闭'] += $value['num'];
                    }
                    break;
                default:
                    break;
            }
        }
        return ['code' => 200, 'msg' => '', 'data' => $numData];
    }

    /**
     * 获取订单列表
     * a表 xunxin_mb_order
     * b表 xunxin_member 查会员账号
     * c表 xunxin_mb_storemember 查会员在该店铺的的备注名
     * @param string $queryStoreId
     * @param int $page
     * @param int $limit
     * @param array $map
     * @param string $from
     * @param boolean $isFirstOrderId 是否是查询首单
     * @return array
     */
    public function getOrderList($queryStoreId = '', $page = 1, $limit = 0, $map = [], $from = 'list', $isFirstOrderId = false, $member_tag = 0)
    {
        // 如果是商城 则获取所有子店
        $where = [];
        $where['a.isdelete'] = NOT_DELETE;
        if (!isInSuper()) {
            $where['a.storeid'] = $this->getStoreIdWhere($queryStoreId);
        }
        if (!$isFirstOrderId) {
            $where['a.business_close_state'] = 0;
            $where['a.cancel_nopay'] = 0;
        }
        $where = array_merge($where, $map);
        /* // 订单实付金额
            $price = [
                'balance', 'platform_balance', 'platform_coupons_money', 'credits_exmoney',
                'platform_credits_exmoney', 'thirdpart_momey'
            ];
        */
        $field = [
            'SUM(a.totalprice) all_price',
            'count(a.order_id) total',
            'SUM(a.balance) all_balance',
            'SUM(a.coupons_exmoney) all_coupons_exmoney',
            'SUM(a.mj_price) all_mj_price',
            'SUM(a.platform_balance) all_platform_balance',
            'SUM(a.platform_coupons_money) all_platform_coupons_money',
            'SUM(a.credits_exmoney) all_credits_exmoney',
            'SUM(a.platform_credits_exmoney) all_platform_credits_exmoney',
            'SUM(a.thirdpart_momey) all_thirdpart_momey',
            'SUM(a.postage) all_postage',
        ];
        $this
            ->field(implode(',', $field))
            ->alias('a')
            ->join('LEFT JOIN __MEMBER__ b ON b.member_id = a.buyer_id');
        if ($member_tag == 1) {
            $this->join('LEFT JOIN xunxin_mb_storemember_tag T on T.mid = a.buyer_id');
        }
        if ($isFirstOrderId) {
            $this->join('LEFT JOIN __MB_STOREMEMBER__ c ON c.store_id = a.storeid AND c.member_id = a.buyer_id');
        }
        foreach ($where as $key => $value) {
            if (strpos($key, 'd.') !== false || strpos($where['_string'], 'd.') !== false) {
                $this->join("LEFT JOIN __MB_FUND_TRADE_DETAIL__ d ON d.order_id = a.order_id");
                break;
            }
        }
        $totalData = $this
            ->join('__STORE__ e ON e.store_id = a.storeid')
            ->where($where)
//            ->cache(true, 60)
            ->find();
        if (false === $totalData) {
            logWrite("计算总数出错: " . $this->getDbError());
            return $this->getReturn(-1, '服务器忙...');
        }

        // region 实付金额
        $price = [
            'all_balance', 'all_platform_balance', 'all_credits_exmoney',
            'all_platform_credits_exmoney', 'all_thirdpart_momey',
        ];
        $totalData['total_pay_money'] = $totalData['all_price'];
        foreach ($price as $k => $val) {
            if (isset($totalData[$val]) && $totalData[$val] > 0) {
                $totalData['total_pay_money'] += $totalData[$val];
            }
        }
        // endregion

        // region 订单总额
        $price = [
            'all_balance', 'all_platform_balance', 'all_platform_coupons_money', 'all_credits_exmoney',
            'all_platform_credits_exmoney', 'all_thirdpart_momey', 'all_coupons_exmoney', 'all_mj_price',
        ];
        foreach ($price as $k => $val) {
            if (isset($totalData[$val]) && $totalData[$val] > 0) {
                $totalData['all_price'] += $totalData[$val];
            }
        }
        // endregion

        $field = [
            'a.*',
            'b.member_name,b.member_nickname,b.recommend_name,b.recommend_id',
            'c.othername,e.store_name',
            'e.member_name store_member_name'
        ];
        if ($from === 'info') $field[] = 'd.self_collect';
        $this
            ->alias('a')
            ->join('LEFT JOIN __MEMBER__ b ON b.member_id = a.buyer_id')
            ->join('LEFT JOIN __MB_STOREMEMBER__ c ON c.store_id = a.storeid AND c.member_id = a.buyer_id');
        foreach ($where as $key => $value) {
            if (strpos($key, 'd.') !== false || strpos($where['_string'], 'd.') !== false || $from == 'info') {
                $this->join("LEFT JOIN __MB_FUND_TRADE_DETAIL__ d ON d.order_id = a.order_id");
                break;
            }
        }
        if ($member_tag == 1) {
            $this->join('LEFT JOIN xunxin_mb_storemember_tag T on T.mid = a.buyer_id');
        }
        $list = $this
            ->field(implode(',', $field))
            ->join('__STORE__ e ON e.store_id = a.storeid')
            ->where($where)
            ->order('a.create_time desc')
            ->limit(($page - 1) * $limit, $limit)
//            ->cache(true, 60)
            ->select();


        if (false === $list) {
            logWrite("查询出错: " . $this->getDbError());
            return $this->getReturn(-1, '服务器忙...');
        }
        $this->transOrder($list);

        $data = [];
        $data['total'] = $totalData['total'];
        $data['all_price'] = $totalData['all_price'];
        $data['total_pay_money'] = $totalData['total_pay_money'];
        $data['list'] = $list;
        return $this->getReturn(200, '成功', $data);
    }

    /**
     * 获取订单详情
     * @param string $queryStoreId
     * @param int $orderId
     * @return array
     * User: hj
     * Date:
     */
    public function getOrderInfo($queryStoreId = '', $orderId = 0)
    {
        $result = $this->getOrderList($queryStoreId, 1, 1, ['a.order_id' => $orderId], 'info');
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $info = $list[0];
        if (empty($info)) return $this->getReturn(-1, '订单不存在或者已经被删除');
        return $this->getReturn(200, '', $info);
    }

    /**
     * 转换订单信息 减少前端模版逻辑
     * total_num 每笔订单的总商品数量
     * all_price 每笔订单的总额
     * @param array $list
     * User: hj
     * Date:
     */
    private function transOrder(&$list = [])
    {
        $memberModel = D('Member');
        foreach ($list as $key => $value) {
            // 增加自提判断
            if ($value['get_to_store'] > 0) {
                $pickTime = date("Y-m-d H:i:s", $value['get_to_store']);
            }
            // 解析订单商品数据
            $list[$key]['order_content'] = json_decode($value['order_content'], 1);
            // 解析商品图片
            foreach ($list[$key]['order_content'] as $k => $goods) {
                if (is_string($goods['goods_figure'])) {
                    $list[$key]['order_content'][$k]['goods_figure'] = json_decode($goods['goods_figure'], 1);
                }
                if (is_string($goods['goods_image']) && empty($goods['goods_figure'])) {
                    $list[$key]['order_content'][$k]['goods_image'] = json_decode($goods['goods_image'], 1);
                }
            }
            // 解析优惠信息
            $list[$key]['gou_info'] = json_decode($value['gou_info'], 1);
            // 下单时间
            $list[$key]['create_time_string'] = date("Y-m-d H:i:s", $value['create_time']);
            // 支付时间 就是create_time
            $list[$key]['pay_time'] = $value['create_time'];
            $list[$key]['pay_time_string'] = $list[$key]['create_time_string'];
            // 接单时间
            $list[$key]['jiedan_time_string'] = date("Y-m-d H:i:s", $value['jiedan_time']);
            // 发货时间
            $list[$key]['delivery_time_string'] = date("Y-m-d H:i:s", $value['delivery_time']);
            // 交易完成时间 就是收获时间 如果没有收获时间则是发货时间并且得是issuccess=1
            $list[$key]['finish_time'] = empty($value['receive_time']) ? ($value['issuccess'] == 1 && $value['order_state'] == 2 ? $value['delivery_time'] : 0) : $value['receive_time'];
            $list[$key]['finish_time_string'] = date("Y-m-d H:i:s", $list[$key]['finish_time']);
            // 关闭时间 退单时间
            $list[$key]['close_time_string'] = date("Y-m-d H:i:s", $value['close_time']);
            // 赔送方式
            $list[$key]['logistics_name'] = $value['get_to_store'] > 0 ?
                "<span style='color: red'>" . L('USER_PICKUP_TIME') . "：{$pickTime}</span>" :
                ($value['pickup_id'] > 0 ? "商家配送(配送点:{$value['pickup_store_name']})" : L('SELLER_PSONG'));
            // 备注名
            $list[$key]['remark_name'] = empty($value['othername']) ? $value['member_nickname'] : $value['othername'];
            // 收款时间
            $list[$key]['offline_gathering_time_string'] = date("Y-m-d H:i:s", $value['offline_gathering_time']);
            // 计算订单购买的商品总数量
            //// 计算总额 = 商品价格 * 数量 最后加上运费
            $totalNum = 0;
            $allPrice = 0;
            foreach ($list[$key]['order_content'] as $k => $val) {
                $totalNum += $val['gou_num'];
                $allPrice += $val['gou_num'] * $val['goods_price'];
                $list[$key]['order_content'][$k]['goods_content'] = json_encode($val, JSON_UNESCAPED_UNICODE);
            }
            $list[$key]['total_num'] = $totalNum;
            $list[$key]['all_price'] = number_format($allPrice + $value['postage'], 2, '.', '');
            // 订单实付金额
            $price = [
                'balance', 'platform_balance', 'credits_exmoney',
                'platform_credits_exmoney', 'thirdpart_momey'
            ];
            foreach ($price as $k => $val) {
                if (isset($value[$val]) && $value[$val] > 0) {
                    $list[$key]['totalprice'] += $value[$val];
                }
            }

            // 金额都格式化
            $format = ['goods_price', 'coupons_exmoney', 'credits_exmoney', 'mj_bean_price'];
            foreach ($list[$key]['order_content'] as $k => $goods) {
                foreach ($format as $field) {
                    $list[$key]['order_content'][$k][$field] = number_format($goods[$field], 2, ".", "");
                    $list[$key]['order_content'][$k]['total'] = number_format($goods['goods_price'] * $goods['gou_num'], 2, '.', '');
                }
            }

            // 转换每笔订单的自收金额 因为旧的订单在trade_detail没有记录 设置订单的付款方式
            switch ((int)$value['pay_type']) {
                case 0:
                    $list[$key]['pay_type_name'] = L('PAY_OFFLINE');
                    $list[$key]['pay_desc'] = L('BUYER_FKTIP_TYPE1');
                    break;
                case 1:
                    $list[$key]['pay_type_name'] = L('PAY_WX');
                    $list[$key]['pay_desc'] = L('BUYER_FKTIP_TYPE2');
                    break;
                case 2:
                    $list[$key]['pay_type_name'] = "银联支付";
                    $list[$key]['pay_desc'] = L('BUYER_FKTIP_TYPE2');
                    break;
                case 3:
                    $list[$key]['pay_type_name'] = L('PAY_BALANCE');
                    $list[$key]['pay_desc'] = L('BUYER_FKTIP_TYPE3');
                    break;
                case 4:
                    $list[$key]['pay_type_name'] = L('PAY_ALIPAY');
                    $list[$key]['pay_desc'] = L('BUYER_FKTIP_TYPE4');
                    break;
                default:
                    break;
            }
            if ($value['self_collect'] !== NULL) {
                if ($value['self_collect'] > 0) {
                    $list[$key]['collect_name'] = L('PAY_SELF');
                } else {
                    $list[$key]['collect_name'] = L('TRADE_OTHER');
                    if ($value['pay_type'] != 0) {
                        $list[$key]['pay_desc'] = "买家已付款至“系统代收”待结算账户，请尽快接单，否则买家有权取消订单。";
                    }
                }
            }
            $list[$key]['pay_type_collect_name'] = empty($value['pay_name']) ? $list[$key]['pay_type_name'] : $value['pay_name'] . (empty($list[$key]['collect_name']) ? "" : " — {$list[$key]['collect_name']}");
            // 设置订单状态
            switch ((int)$value['order_state']) {
                case 6:
                    $list[$key]['order_state_name'] = (int)$value['cancel_nopay'] === 0 ? L('ORDER_MSG1') : $value['close_reason'];
                    $list[$key]['order_type'] = 1;
                    break;
                case 0:
                    $list[$key]['order_state_name'] = L('ORDER_MSG4');
                    $list[$key]['order_type'] = 2;
                    break;
                case 1:
                    $list[$key]['order_state_name'] = L('ORDER_MSG5');
                    $list[$key]['order_type'] = 301;
                    // hj 2017-09-23 15:44:14 增加自提判断
                    if ($value['get_to_store'] > 0) {
                        $list[$key]['order_type'] = 302;
                        $list[$key]['get_to_store_string'] = $pickTime;
                    }
                    break;
                case 7:
                    $list[$key]['order_state_name'] = L('ORDER_MSG9') /*等待买家收货*/
                    ;
                    $list[$key]['order_type'] = 4;
                    $list[$key]['order_act_name'] = $value['get_to_store'] > 0 ? "确定自提" : "确定收货";
                    $list[$key]['order_act_desc'] = $value['get_to_store'] > 0 ? "确认买家已经自提?" : "确认买家已经收货?";
                    break;
                case 2:
                    $list[$key]['order_state_name'] = $value['issuccess'] == 1 ? L('ORDER_PROGRESS_5')/*交易完成*/ : L('UNKNOWN')/*未知*/
                    ;
                    $list[$key]['order_type'] = $value['issuccess'] == 1 ? 5 : 0;
                    if ($value['get_to_store'] > 0) {
                        $list[$key]['get_to_store_string'] = $pickTime;
                    }
                    break;
                case 5:
                    $list[$key]['order_state_name'] = L('ORDER_STATE_11')/*已退款*/
                    ;
                    $list[$key]['order_type'] = 0;
                    break;
                case 4:
                    $list[$key]['order_state_name'] = $value['issuccess'] == 1 ? L('ORDER_PROGRESS_0')/*交易关闭*/ : L('ORDER_STATE_11')/*已退款*/
                    ;
                    $list[$key]['order_type'] = 0;
                    break;
                case 3:
                    $list[$key]['order_state_name'] = L('ORDER_PROGRESS_0')/*交易关闭*/
                    ;
                    $list[$key]['order_type'] = 0;
                    break;
                default:
                    break;
            }

            // 发票信息
            if ($value['invoice_id'] > 0) {
                $list[$key]['invoice_type_name'] = D('Invoice')->isPersonal($value) ? '个人' : '单位';
            }

            // 海外购身份信息
            if (!empty($value['idcard']) || !empty($value['truename'])) {
                $list[$key]['identity'] = "{$value['truename']}    {$value['idcard']}";
            }

            // 推荐人
            if (!empty($value['recommend_id'])) {
                $recommendMember = $memberModel->getMemberInfo($value['recommend_id'])['data'];
                if (!empty($recommendMember['member_nickname'])) {
                    $list[$key]['recommend_name'] = "{$recommendMember['member_nickname']}({$recommendMember['member_name']})";
                } else {
                    $list[$key]['recommend_name'] = "{$recommendMember['member_name']}";
                }
            }
        }
    }

    /**
     * 修改快递单号
     * @param int $orderId
     * @param string $num
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-19 16:01:10
     * Version: 1.0
     */
    public function updateWaybillNum($orderId = 0, $num = '')
    {
        $where = [];
        $where['order_id'] = $orderId;
        $info = $this->field('order_id,waybill_num')->where($where)->find();
        if (empty($info)) return getReturn(-1, '订单不存在...');
        if ($info['waybill_num'] === $num) return getReturn(-1, '快递单号未做修改...');
        $data = [];
        $data['waybill_num'] = $num;
        $data['version'] = $this->max('version') + 1;
        $result = $this->where($where)->save($data);
        if (false === $result) {
            logWrite("修改订单{$orderId}快递单号出错:" . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '', ['order_id' => $orderId, 'num' => $num, 'url' => "http://m.kuaidi100.com/result.jsp?nu={$num}"]);
    }

    /**
     * 商家确认收款
     * @param int $orderId
     * @param int $memberId
     * @param string $img
     * @param string $remark
     * @param string $password
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-23 21:30:19
     * Version: 1.0
     */
    public function confirmGathering($orderId = 0, $memberId = 0, $img = '', $remark = '', $password = '')
    {
        $model = D('Member');
        $result = $model->getMemberInfo($memberId);
        $memberName = $result['data']['member_name'];
        if (md5($password) !== $result['data']['member_passwd']) return getReturn(-1, "密码错误");
        $where = [];
        $where['order_id'] = $orderId;
        $info = $this
            ->field('offline_gathering_state,offline_gathering_member,offline_gathering_time')
            ->where($where)
            ->find();
        if (false === $info) {
            logWrite("查询订单{$orderId}的收款情况出错:" . $this->getDbError());
            return getReturn();
        }
        $time = date("Y-m-d H:i:s", $info['offline_gathering_time']);
        if ((int)$info['offline_gathering_state'] === 1) {
            return getReturn(-1, "该订单已经确认收款,操作者{$info['offline_gathering_member']},操作时间:{$time}");
        }
        $form = [];
        $form['offline_gathering_state'] = 1;
        $form['offline_gathering_member'] = $memberName;
        $form['offline_gathering_img'] = $img;
        $form['offline_gathering_remark'] = $remark;
        $form['offline_gathering_time'] = NOW_TIME;
        $data = $this->field('offline_gathering_state,offline_gathering_img,offline_gathering_remark,offline_gathering_member,offline_gathering_time')->create($form);
        if (false === $data) return getReturn(-1, $this->getError());
        $result = $this->where($where)->save($data);
        if (false === $result) {
            logWrite("订单{$orderId}确认收款出错:" . $this->getDbError());
            return getReturn(-1, '系统繁忙,请稍后重试...', $data);
        }
        return getReturn(200, "确认收款成功");
    }

    /**
     * 获取满足条件的订单ID数组
     * @param string $queryStoreId
     * @param array $map
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-21 22:37:07
     * Update: 2018-03-21 22:37:07
     * Version: 1.00
     */
    public function getOrderIdArr($queryStoreId = '', $map = [])
    {
        // 如果是商城 则获取所有子店
        $where = [];
        $where['a.storeid'] = $this->getStoreIdWhere($queryStoreId);
        $where['a.business_close_state'] = 0;
        $where['a.cancel_nopay'] = 0;
        $where = array_merge($where, $map);
        $this
            ->alias('a')
            ->join('LEFT JOIN __MEMBER__ b ON b.member_id = a.buyer_id')
            ->join('LEFT JOIN __MB_STOREMEMBER__ c ON c.store_id = a.storeid AND c.member_id = a.buyer_id');
        foreach ($where as $key => $value) {
            if (strpos($key, 'd.') !== false || strpos($where['_string'], 'd.') !== false) {
                $this->join("LEFT JOIN __MB_FUND_TRADE_DETAIL__ d ON d.order_id = a.order_id");
                break;
            }
        }
        $list = $this
            ->field('a.order_id')
            ->join('__STORE__ e ON e.store_id = a.storeid')
            ->where($where)
            ->select();
        if (false === $list) {
            logWrite("查询出错: " . $this->getDbError());
            return getReturn(-1, '服务器忙...');
        }
        $orderId = [];
        foreach ($list as $order) {
            $orderId[] = $order['order_id'];
        }
        return $orderId;
    }

    public function addOrderData($mallstore_order_array, $storeorder, $client, $orderversion_max)
    {
        $insertData = array();
        $orderType = $mallstore_order_array['order_type'];  // hj 2018-02-08 21:55:43 团购订单标识
        $isGroupOrder = $orderType == 'tg';
        if ($storeorder['pay_type'] == 0 || $storeorder['pay_type'] == 3) {
            $orderState = $isGroupOrder ? 1 : 0; // hj 2018-02-08 22:00:31 团购订单默认待发货
        } else {
            $orderState = $isGroupOrder ? 1 : 6;
        }


        $insertData['order_state'] = $orderState;
        $insertData['create_time'] = TIMESTAMP;
        $insertData['buyer_id'] = $mallstore_order_array['buyer_id'];
        if (!empty($mallstore_order_array['is_pickup'])) {
            $insertData['order_membername'] = $mallstore_order_array['order_membername'];
            $insertData['order_membertel'] = $mallstore_order_array['order_membertel'];
            $insertData['address'] = '';
        } else {
            $insertData['order_membername'] = empty($mallstore_order_array['addressData']['member_name']) ? '' : $mallstore_order_array['addressData']['member_name'];
            $insertData['order_membertel'] = empty($mallstore_order_array['addressData']['member_tel']) ? '' : $mallstore_order_array['addressData']['member_tel'];
            $insertData['address'] = $mallstore_order_array['addressData']['province'] .
                $mallstore_order_array['addressData']['city'] .
                $mallstore_order_array['addressData']['area'] .
                $mallstore_order_array['addressData']['address'];
        }

        $insertData['order_msg'] = $storeorder['order_msg'];
        $insertData['close_reason'] = '';
        $insertData['totalprice'] = $storeorder['totalprice'];
        $insertData['longitude'] = $mallstore_order_array['longitude'];
        $insertData['latitude'] = $mallstore_order_array['latitude'];
        $insertData['pay_type'] = $storeorder['pay_type'];
        $insertData['pay_name'] = $storeorder['pay_name'];
        $insertData['order_content'] = json_encode($storeorder['order_content'], JSON_UNESCAPED_UNICODE);
        $insertData['gou_type'] = $storeorder['gou_type'];
        $insertData['gou_info'] = json_encode($storeorder['gou_info'], JSON_UNESCAPED_UNICODE);
        $insertData['isdelete'] = 0;
        $insertData['buyer_close_state'] = 0;
        $insertData['store_name'] = $storeorder['store_name'];
        $insertData['business_close_state'] = 0;
        $insertData['version'] = $orderversion_max;
        $insertData['storeid'] = $storeorder['storeid'];
        $insertData['balance'] = empty($storeorder['balance']) ? 0 : $storeorder['balance'];
        $insertData['platform_balance'] = empty($storeorder['platform_balance']) ? 0 : $storeorder['platform_balance'];
        $insertData['order_pv'] = $storeorder['order_pv'];
        $insertData['get_to_store'] = empty($storeorder['get_to_store']) ? 0 : $storeorder['get_to_store'];
        $insertData['morder_id'] = $mallstore_order_array['morder_id'];
        $insertData['mergepay'] = $mallstore_order_array['mergepay'];
        $insertData['waybill_num'] = empty($storeorder['waybill_num']) ? 0 : $storeorder['waybill_num'];
        $insertData['use_platform_coupons'] = empty($storeorder['use_platform_coupons']) ? 0 : $storeorder['use_platform_coupons'];
        $insertData['platform_coupons_id'] = empty($storeorder['platform_coupons_id']) ? 0 : $storeorder['platform_coupons_id'];
        $insertData['platform_coupons_money'] = empty($storeorder['platform_coupons_money']) ? 0 : $storeorder['platform_coupons_money'];
        $insertData['credits_num'] = empty($storeorder['credits_num']) ? 0 : $storeorder['credits_num'];
        $insertData['credits_exmoney'] = empty($storeorder['credits_exmoney']) ? 0 : $storeorder['credits_exmoney'];
        $insertData['platform_credits_num'] = empty($storeorder['platform_credits_num']) ? 0 : $storeorder['platform_credits_num'];
        $insertData['platform_credits_exmoney'] = empty($storeorder['platform_credits_exmoney']) ? 0 : $storeorder['platform_credits_exmoney'];
        $insertData['channelid'] = $mallstore_order_array['channel_id'];
        $insertData['client_type'] = $client;
        $insertData['postage'] = $storeorder['postage'];
        $insertData['idcard'] = $mallstore_order_array['idcard'];
        $insertData['truename'] = $mallstore_order_array['truename'];
        $insertData['thirdpart_momey'] = empty($storeorder['thirdpart_money']) ? 0 : $storeorder['thirdpart_money'];
        $insertData['coupons_exmoney'] = empty($storeorder['coupons_exmoney']) ? 0 : $storeorder['coupons_exmoney'];
        $insertData['pickup_id'] = empty($storeorder['pickUp']['id']) ? 0 : $storeorder['pickUp']['id'];
        $insertData['pickup_address'] = $storeorder['pickUp']['province'] . $storeorder['pickUp']['city'] . $storeorder['pickUp']['area'] . $storeorder['pickUp']['address'];
        $insertData['pickup_tel'] = $storeorder['pickUp']['link_tel'];
        $insertData['pickup_name'] = $storeorder['pickUp']['link_name'];
        $insertData['pickup_longitude'] = $storeorder['pickUp']['longitude'];
        $insertData['pickup_latitude'] = $storeorder['pickUp']['latitude'];
        $insertData['pickup_store_name'] = $storeorder['pickUp']['store_name'];
        $insertData['mj_price'] = $storeorder['mj_price'];
        $insertData['mj_info'] = json_encode($storeorder['store_mj_array']);
        $insertData['order_sn'] = $mallstore_order_array['order_sn'];
        $insertData['order_amount'] = $storeorder['order_amount'];
        $insertData['group_id'] = empty($storeorder['group_id']) ? 0 : $storeorder['group_id'];
        $insertData['group_return_money'] = empty($storeorder['group_return_money']) ? 0 : $storeorder['group_return_money'];
        $insertData['share_member_id'] = empty($mallstore_order_array['share_member_id']) ? 0 : $mallstore_order_array['share_member_id'];
        $insertData['invoice_id'] = empty($mallstore_order_array['invoice_id']) ? 0 : $mallstore_order_array['invoice_id'];
        if ($insertData['invoice_id'] > 0) {
            $invoiceInfo = M("mb_invoice")->where(array('invoice_id' => $insertData['invoice_id']))->find();
            if (!empty($invoiceInfo)) {
                $insertData['invoice_title'] = $invoiceInfo['invoice_title'];
                $insertData['invoice_type'] = $invoiceInfo['invoice_type'];
                $insertData['invoice_code'] = $invoiceInfo['invoice_code'];
            }
        }
        $insertData['goods_amount'] = $storeorder['goods_amount'];
        $insertData['app_version'] = empty($mallstore_order_array['app_version']) ? 0:$mallstore_order_array['app_version'];
        $insertData['delivery_period'] = empty($storeorder['delivery_period']) ? '' : $storeorder['delivery_period'];
        if (count($storeorder['order_content']) > 50) {
            $insertData['reduce_storage'] = 0;
        } else {
            $insertData['reduce_storage'] = 1;
        }
        $insertData['pend_num_id'] = empty($mallstore_order_array['pend_num_id']) ? 0 : $mallstore_order_array['pend_num_id'];
        $insertData['pend_member_num'] = empty($mallstore_order_array['pend_member_num']) ? 0 : $mallstore_order_array['pend_member_num'];
        $insertData['pend_name'] = empty($mallstore_order_array['pend_name']) ? '' : $mallstore_order_array['pend_name'];
        $insertData['pend_type'] = empty($mallstore_order_array['pend_type']) ? 0 : $mallstore_order_array['pend_type'];
        $insertData['print_info'] = json_encode($this->getPrintInfo($insertData), JSON_UNESCAPED_UNICODE);
        $order_id = $this->add($insertData);
        logWrite("订单表的数据:" . json_encode($insertData, 256));
        logWrite("SQL语句:" . $this->_sql());
        if ($order_id === false) {
            return getReturn(-1, "插入订单失败");
        }
        $insertData['order_id'] = $order_id;
        return getReturn(200, "成功", $insertData);
    }

    public function getPrintInfo($insertData)
    {
        $print_info = array();
        $storeData = D("Store")->getStoreInfo2($insertData['storeid'])['data'];
        if (empty($storeData)) {
            return $print_info;
        }
        $channelData = D('Channel')->getChannelInfo($storeData['channel_id'])['data'];
        if (empty($channelData)) {
            return $print_info;
        }
        if ($channelData['store_type'] == 2) {
            $mainStoreData = M("store")->field('store_id')->where(array('channel_id' => $channelData['channel_id'], 'main_store' => 1))->find();
            $mainStoreMemberInfo = M("mb_storemember")
                ->where(array('store_id' => $mainStoreData['store_id'], 'member_id' => $insertData['buyer_id']))
                ->find();
            if (empty($mainStoreMemberInfo)) {
                return $print_info;
            }
        }

        $storeMemberInfo = M("mb_storemember")
            ->where(array('store_id' => $insertData['storeid'], 'member_id' => $insertData['buyer_id']))
            ->find();
        $memberInfo = M("member")->field('member_name,bindtel,member_nickname')->where(array('member_id' => $insertData['buyer_id']))->find();

        $rs_balance = $storeMemberInfo['balance'] - $insertData['balance'];
        $rs_credits_num = $storeMemberInfo['sum_score'] - $insertData['credits_num'];
        $rs_p_balance = $mainStoreMemberInfo['balance'] - $insertData['platform_balance'];
        $rs_p_credits_num = $mainStoreMemberInfo['sum_score'] - $insertData['platform_credits_num'];
        $print_info['member_name'] = $memberInfo['member_name'];
        $print_info['bindtel'] = empty($memberInfo['bindtel'])?"":$memberInfo['bindtel'];
        $print_info['member_nickname'] = $memberInfo['member_nickname'];
        $print_info['rs_balance'] = $rs_balance;
        $print_info['rs_credits_num'] = $rs_credits_num;
        $print_info['rs_p_balance'] = $rs_p_balance;
        $print_info['rs_p_credits_num'] = $rs_p_credits_num;
        return $print_info;
    }
}