<?php

namespace Common\Model;

use Common\Common\CommonOrderApi;
use Common\Interfaces\M\RechargeCardRecord;
use Common\Logic\MarketLogic;
use Org\Util\String;

class RechargeCardRecordModel extends BaseModel implements RechargeCardRecord
{
    protected $tableName = 'mb_recharge_card_record';

    /**
     * 生成充值记录页的查询条件
     * @param array $request
     * @return mixed
     * User: hjun
     * Date: 2018-12-04 14:25:31
     * Update: 2018-12-04 14:25:31
     * Version: 1.00
     */
    public function autoRecordListQueryWhere($request = [])
    {
        $where = [];
        // 充值单号
        if (!empty($request['order_sn'])) {
            $where['a.order_sn'] = $request['order_sn'];
        }
        // 充值会员账号
        if (!empty($request['member_name'])) {
            $where['a.member_name'] = ['like', "%{$request['member_name']}%"];
        }
        //充值卡ID
        if (!empty($request['card_id'])) {
            $where['a.card_id'] = $request['card_id'];
        }
        // 充值时间
        $timeResult = getRangeWhere($request, 'recharge_min', 'recharge_max', '时间范围不正确');
        if (!isSuccess($timeResult)) {
            $this->setValidateError($timeResult['msg']);
            return false;
        }
        $timeWhere = $timeResult['data'];
        if (!empty($timeWhere)) {
            $where['a.pay_time'] = $timeWhere;
        }
        // 推荐人账号
        if (!empty($request['recommend_name'])) {
            $where['a.recommend_name'] = ['LIKE', "%{$request['recommend_name']}%"];
        }
        // 推荐人昵称
        if (!empty($request['recommend_nickname'])) {
            $where['a.recommend_nickname'] = ['LIKE', "%{$request['recommend_nickname']}%"];
        }
        return $where;
    }

    /**
     * 获取充值记录列表的数据
     * @param int $page
     * @param int $limit
     * @param array $queryWhere
     * @return array
     * User: hjun
     * Date: 2018-12-04 14:23:50
     * Update: 2018-12-04 14:23:50
     * Version: 1.00
     */
    public function getRecordListData($page = 1, $limit = 20, $queryWhere = [])
    {
        $field = [];
        $where = [];
        $where['a.store_id'] = $this->getStoreId();
        $where['a.pay_status'] = 1;
        $where['a.is_delete'] = NOT_DELETE;
        $where = array_merge($where, $queryWhere);
        $order = 'a.create_time DESC';
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = $order;
        $data = $this->queryList($options)['data'];
        // 统计结果 记录数、当前充值金额、当前赠送金额、累计充值、累计赠送
        $meta = [
            'record_num' => $data['currentTotal'], 'current_card_money' => 0,
            'current_give_money' => 0, 'total_card_money' => 0,
            'total_give_money' => 0,
        ];
        if (!empty($data['list'])) {
            foreach ($data['list'] as $key => $value) {
                $value['pay_time_string'] = date('Y-m-d H:i:s', $value['pay_time']);
                $value['market_content'] = jsonDecodeToArr($value['market_content']);
                $meta['current_card_money'] += $value['card_money'];
                $meta['current_give_money'] += $value['give_money'];
                $data['list'][$key] = $value;
            }
            $field = [
                'SUM(a.card_money) total_card_money',
                'SUM(a.give_money) total_give_money',
            ];
            $options = [];
            $options['alias'] = 'a';
            $options['field'] = $field;
            $options['where'] = $where;
            $sum = $this->selectRow($options);
            $meta['total_card_money'] = $sum['total_card_money'];
            $meta['total_give_money'] = $sum['total_give_money'];
        }
        $data['meta'] = $meta;
        return $data;
    }

    /**
     * 创建充值订单
     * @param int $memberId
     * @param int $cardId
     * @param array $request 其他请求参数
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-05 17:56:01
     * Update: 2018-12-05 17:56:01
     * Version: 1.00
     */
    public function createRechargeRecord($memberId = 0, $cardId = 0, $request = [])
    {
        $storeId = $this->getStoreId();
        if (empty($storeId) || empty($cardId)) {
            return getReturn(CODE_ERROR, '参数错误');
        }
        // 如果是员工 检查密码
        if (!empty($request['seller_name'])) {
            if (empty($request['password'])) {
                return getReturn(CODE_ERROR, '请输入管理员密码');
            }
            $field = [
                'a.seller_id', 'a.seller_name',
                'c.member_passwd'
            ];
            $join = [
                '__STORE__ b ON a.store_id = b.store_id',
                '__MEMBER__ c ON b.member_name = c.member_name'
            ];
            $where = [];
            $where['a.seller_name'] = $request['seller_name'];
            $options = [];
            $options['alias'] = 'a';
            $options['field'] = $field;
            $options['join'] = $join;
            $options['where'] = $where;
            $seller = D('Seller')->selectRow($options);
            if (empty($seller)) {
                return getReturn(CODE_NOT_FOUND, '当前员工已失效');
            }
            if ($request['password'] != $seller['member_passwd']) {
                return getReturn(CODE_NOT_FOUND, '管理员密码错误');
            }
        }
        $member = D('Member')->getMemberInfo($memberId)['data'];
        if (empty($member)) {
            return getReturn(CODE_LOGOUT, '请先登录');
        }
        $card = D('RechargeCard')->setStoreId($storeId)->getCard($cardId);
        if (empty($card) || $card['card_money'] <= 0) {
            return getReturn(CODE_ERROR, '选择的充值卡已失效');
        }
        // 检查订单编号
        $time = date('YmdHis', time());
        $salt = String::randString(6, 1);
        $orderSn = "RECHARGE{$time}{$salt}";
        $where = [];
        $where['order_sn'] = $orderSn;
        $check = $this->field('record_id')->where($where)->find();
        if ($check) {
            return getReturn(CODE_ERROR, '请勿重复提交');
        }
        $data = [];
        $data['order_sn'] = $orderSn;
        $data['card_id'] = $cardId;
        $data['store_id'] = $storeId;
        $data['member_id'] = $memberId;
        $data['member_name'] = $member['member_name'];
        $data['pay_money'] = $card['card_money'];
        $data['card_money'] = $card['card_money'];
        $data['give_money'] = $card['give_money'];
        $data['market_id'] = $card['market_id'];
        $data['create_time'] = time();
        // 推荐人
        $recommendInfo = getRecommendInfo($memberId, $storeId);
        if (!empty($recommendInfo)) {
            $data['recommend_id'] = $recommendInfo['recommend_id'];
            $data['recommend_name'] = $recommendInfo['recommend_name'];
            $data['recommend_nickname'] = $recommendInfo['recommend_nickname'];
        }
        if (!empty($seller)) {
            $data['seller_id'] = $seller['seller_id'];
            $data['seller_name'] = $seller['seller_name'];
        }
        if (!empty($request['ps'])) {
            $data['remark'] = $request['ps'];
        }
        $data = $this->getAndValidateDataFromRequest([], $data)['data'];
        $recordId = $this->add($data);
        if (false === $recordId) {
            return getReturn(CODE_ERROR);
        }
        $url = URL("/pay/index.php?m=Wap&c=Pay&a=rechargeCardPay&pay_id=" . $recordId . "&se=" . $storeId . '&f=' . $memberId);
        $data['pay_url'] = $url;
        return getReturn(CODE_SUCCESS, 'success', $data);
    }

    /**
     * 获取某条充值记录
     * @param int $recordId
     * @param int $memberId
     * @return array
     * User: hjun
     * Date: 2018-12-05 18:29:20
     * Update: 2018-12-05 18:29:20
     * Version: 1.00
     */
    public function getMemberRecord($recordId = 0, $memberId = 0)
    {
        $where = [];
        $where['record_id'] = $recordId;
        $where['store_id'] = $this->getStoreId();
        $where['member_id'] = $memberId;
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['where'] = $where;
        return $this->selectRow($options);
    }

    /**
     * 支付成功通知回调
     * @param string $orderSn
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-05 19:13:16
     * Update: 2018-12-05 19:13:16
     * Version: 1.00
     */
    public function paySuccessNotify($orderSn = '')
    {
        logWrite("充值卡支付成功通知,订单编号:{$orderSn}");
        $this->startTrans();
        $where = [];
        $where['order_sn'] = $orderSn;
        $where['is_delete'] = NOT_DELETE;
        $record = $this->where($where)->lock(true)->find();
        logWrite("SQL:" . $this->_sql() . ',结果:' . jsonEncode($record));
        if (empty($record)) {
            logWrite("订单不存在");
            return getReturn(CODE_ERROR, '订单不存在');
        }
        if ($record['pay_status'] == 1) {
            logWrite("订单已经支付");
            return getReturn(CODE_ERROR, '订单已经支付完成');
        }

        // 给会员增加余额
        $api = new CommonOrderApi();
        $store = D('Store')->getStoreInfo($record['store_id'])['data'];
        $money = round($record['pay_money'] + $record['give_money'], 2);
        if (isMall($store['store_type'])) {
            // 如果是商城 则充值平台余额
            $result = $api->changePlatformBalance(
                $store['store_id'],
                $record['member_id'],
                6,
                $money,
                '充值卡充值',
                "充值卡充值,订单编号:{$record['order_sn']}",
                $record['record_id'],
                $record['member_name']
            );
        } else {
            // 否则充值店铺余额
            $result = $api->changeBalance(
                $store['store_id'],
                $record['member_id'],
                6,
                $money,
                '充值卡充值',
                "充值卡充值,订单编号:{$record['order_sn']}",
                $record['record_id'],
                $record['member_name']
            );
        }
        logWrite("修改余额结果:" . jsonEncode($result));
        if (!isSuccess($result)) {
            $this->rollback();
            return $result;
        }

        // 充值卡次数+1
        $cardModel = D('RechargeCard');
        $result = $cardModel->changeRechargeNum($record['card_id']);
        logWrite('SQL:' . $cardModel->_sql() . ',结果:' . jsonEncode($result));
        if (false === $result) {
            $this->rollback();
            return getReturn(CODE_ERROR);
        }

        // 订单结果
        $data = [];

        // 如果有礼包 则赠送礼包
        $marketContent = [];
        if ($record['market_id'] > 0) {
            $market = D('Market')->setStoreId($store['store_id'])->getMarket($record['market_id']);
            if (!empty($market)) {
                $marketApi = new MarketLogic();
                $storeMember = D('StoreMember')->setOpenTrans(false);
                $outerData = [
                    'credits_name' => '充值送积分',
                ];
                $marketApi->sendMarketGift($market, $store['store_id'], $record['member_id'], 0, $outerData);
                $storeMember->setOpenTrans(true);
                if ($market['select_credit'] == 1) {
                    $marketContent['send_credit'] = $market['send_credit'];
                }
                if ($market['select_coupons'] == 1) {
                    $coupons = jsonDecodeToArr($market['coupons_content']);
                    $marketContent['coupons_content'] = $coupons;
                }
                if ($market['select_group'] == 1) {
                    $group = jsonDecodeToArr($market['group_content']);
                    $marketContent['group_id'] = $group['group_id'];
                    $marketContent['group_name'] = $group['group_name'];
                }
            }
        }

        // 修改支付状态为已支付
        $data['pay_status'] = 1;
        $data['pay_time'] = time();
        $data['market_content'] = jsonEncode($marketContent);
        $where = [];
        $where['record_id'] = $record['record_id'];
        logWrite("订单结果:" . jsonEncode($data));
        $result = $this->where($where)->save($data);
        logWrite("SQL:" . $this->_sql() . ',结果:' . jsonEncode($result));
        if (false === $result) {
            $this->rollback();
            return getReturn(CODE_ERROR);
        }

        $this->commit();

        // 发送充值成功消息提醒
        $params = array();
        $params['order_id'] = $record['record_id'];
        $send = A('SendMessage');
        $send->sendWxMsg($store['store_id'], 22, $record['member_id'], $params);

        return getReturn(CODE_SUCCESS, 'success');
    }
}