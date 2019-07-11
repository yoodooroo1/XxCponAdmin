<?php

namespace Common\Model;

use Common\Interfaces\M\RechargeCard;
use Think\Model;

class RechargeCardModel extends BaseModel implements RechargeCard
{
    const ACTION_CHANGE_STATUS = 5;

    protected $tableName = 'mb_recharge_card';

    /**
     * 生成充值卡列表的查询条件
     * @param array $request
     * @return mixed
     * User: hjun
     * Date: 2018-12-04 15:00:40
     * Update: 2018-12-04 15:00:40
     * Version: 1.00
     */
    public function autoCardListQueryWhere($request = [])
    {
        $where = [];
        return $where;
    }

    /**
     * 获取充值卡列表页的数据
     * @param int $page
     * @param int $limit
     * @param array $queryWhere
     * @return array
     * User: hjun
     * Date: 2018-12-04 14:23:50
     * Update: 2018-12-04 14:23:50
     * Version: 1.00
     */
    public function getCardListData($page = 1, $limit = 20, $queryWhere = [])
    {
        $field = [];
        $where = [];
        $where['a.store_id'] = $this->getStoreId();
        $where['a.is_delete'] = NOT_DELETE;
        $where = array_merge($where, $queryWhere);
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $data = $this->queryList($options)['data'];
        $statusNameTable = [
            '0' => L('OFF'),
            '1' => L('ON'),
        ];
        foreach ($data['list'] as $key => $value) {
            $value['status_name'] = $statusNameTable[$value['status']];
            $data['list'][$key] = $value;
        }
        return $data;
    }

    /**
     * 获取某个充值卡数据
     * @param int $cardId
     * @return array
     * User: hjun
     * Date: 2018-12-04 14:45:06
     * Update: 2018-12-04 14:45:06
     * Version: 1.00
     */
    public function getCard($cardId = 0)
    {
        $where = [];
        $where['card_id'] = $cardId;
        $where['store_id'] = $this->getStoreId();
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['where'] = $where;
        return $this->selectRow($options);
    }

    /**
     * 获取缓存数据 只查询一次
     * @param int $cardId
     * @return array
     * User: hjun
     * Date: 2018-12-05 12:07:07
     * Update: 2018-12-05 12:07:07
     * Version: 1.00
     */
    public function getCardCache($cardId = 0)
    {
        $card = $this->getLastQueryData("card:{$cardId}");
        if (empty($card)) {
            $card = $this->getCard($cardId);
            $this->setLastQueryData("card:{$cardId}", $card);
        }
        return $card;
    }

    /**
     * 验证充值卡是否存在
     * @param int $cardId
     * @return boolean
     * User: hjun
     * Date: 2018-12-04 14:45:34
     * Update: 2018-12-04 14:45:34
     * Version: 1.00
     */
    public function validateCard($cardId = 0)
    {
        $info = $this->getCardCache($cardId);
        return !empty($info);
    }

    /**
     * 验证金额是否正确
     * @param float $money
     * @return boolean
     * User: hjun
     * Date: 2018-12-04 16:18:47
     * Update: 2018-12-04 16:18:47
     * Version: 1.00
     */
    public function validateMoney($money = 0.00)
    {
        if ($money < 0) {
            $this->setValidateError('金额格式错误');
            return false;
        }
        if (!$this->check($money, 'double')) {
            $this->setValidateError('金额格式错误');
            return false;
        }
        return true;
    }

    /**
     * 验证营销礼包有效性
     * @param int $marketId
     * @return boolean
     * User: hjun
     * Date: 2018-12-04 21:20:48
     * Update: 2018-12-04 21:20:48
     * Version: 1.00
     */
    public function validateMarket($marketId = 0)
    {
        if ($marketId > 0) {
            $info = D('Market')->setStoreId($this->getStoreId())->getMarketCache($marketId);
            return !empty($info);
        }
        return true;
    }

    /**
     * 自动完成金额
     * @param float $money
     * @return double
     * User: hjun
     * Date: 2018-12-04 21:27:57
     * Update: 2018-12-04 21:27:57
     * Version: 1.00
     */
    public function autoMoney($money = 0.00)
    {
        return round($money, 2);
    }

    /**
     * 自动完成营销礼包ID
     * @param int $marketId
     * @return string
     * User: hjun
     * Date: 2018-12-04 21:21:05
     * Update: 2018-12-04 21:21:05
     * Version: 1.00
     */
    public function autoMarketId($marketId = 0)
    {
        if ($marketId > 0) {
            $market = D('Market')->setStoreId($this->getStoreId())->getMarketCache($marketId);
            return $market['id'] > 0 ? $market['id'] : 0;
        }
        return 0;
    }

    /**
     * 自动完成营销礼包名称
     * @param int $marketId
     * @return string
     * User: hjun
     * Date: 2018-12-04 21:20:09
     * Update: 2018-12-04 21:20:09
     * Version: 1.00
     */
    public function autoMarketName($marketId = 0)
    {
        if ($marketId > 0) {
            $market = D('Market')->setStoreId($this->getStoreId())->getMarketCache($marketId);
            return empty($market['market_name']) ? '' : $market['market_name'];
        }
        return '';
    }

    /**
     * 自动完成状态
     * @param int $status
     * @return int
     * User: hjun
     * Date: 2018-12-04 21:21:26
     * Update: 2018-12-04 21:21:26
     * Version: 1.00
     */
    public function autoStatus($status = 0)
    {
        return $status == 1 ? 1 : 0;
    }

    /**
     * 根据操作获取字段规则
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2018-12-04 15:33:58
     * Update: 2018-12-04 15:33:58
     * Version: 1.00
     */
    public function getFieldsByAction($action = Model::MODEL_INSERT, $request = [])
    {
        $fieldTable = [];
        $fieldTable[self::MODEL_INSERT] = [
            'store_id', 'card_money', 'give_money',
            'market_id', 'market_name', 'status', 'create_time'
        ];
        $fieldTable[self::MODEL_UPDATE] = [
            'card_money', 'give_money', 'market_id',
            'market_name', 'status',
        ];
        $fieldTable[self::MODEL_DELETE] = [
            'is_delete'
        ];
        $fieldTable[self::ACTION_CHANGE_STATUS] = [
            'status'
        ];
        $fields = isset($fieldTable[$action]) ? $fieldTable[$action] : [];
        return $fields;
    }

    /**
     * 根据操作获取验证规则
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2018-12-04 15:33:58
     * Update: 2018-12-04 15:33:58
     * Version: 1.00
     */
    public function getValidateByAction($action = Model::MODEL_INSERT, $request = [])
    {
        $validateTable = [];
        $validateTable[self::MODEL_INSERT] = [
            ['card_money', 'validateMoney', '', self::MUST_VALIDATE, 'callback', self::MODEL_INSERT],
            ['give_money', 'validateMoney', '', self::MUST_VALIDATE, 'callback', self::MODEL_INSERT],
            ['market_id', 'validateMarket', '选择的礼包已经失效', self::MUST_VALIDATE, 'callback', self::MODEL_INSERT],
        ];
        $validateTable[self::MODEL_UPDATE] = [
            ['card_id', 'validateCard', '当前充值卡已经失效', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE],
            ['card_money', 'validateMoney', '', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE],
            ['give_money', 'validateMoney', '', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE],
            ['market_id', 'validateMarket', '选择的礼包已经失效', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE],
        ];
        $validateTable[self::ACTION_CHANGE_STATUS] = [
            ['card_id', 'validateCard', '当前充值卡已经失效', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE],
        ];
        $validate = isset($validateTable[$action]) ? $validateTable[$action] : [];
        return $validate;
    }

    /**
     * 根据操作获取完成规则
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2018-12-04 15:33:58
     * Update: 2018-12-04 15:33:58
     * Version: 1.00
     */
    public function getAutoByAction($action = Model::MODEL_INSERT, $request = [])
    {
        $autoTable = [];
        $autoTable[self::MODEL_INSERT] = [
            ['store_id', $this->getStoreId(), self::MODEL_INSERT, 'string'],
            ['card_money', $this->autoMoney($request['card_money']), self::MODEL_INSERT, 'string'],
            ['give_money', $this->autoMoney($request['give_money']), self::MODEL_INSERT, 'string'],
            ['market_id', $this->autoMarketId($request['market_id']), self::MODEL_INSERT, 'string'],
            ['market_name', $this->autoMarketName($request['market_id']), self::MODEL_INSERT, 'string'],
            ['status', $this->autoStatus($request['status']), self::MODEL_INSERT, 'string'],
            ['create_time', NOW_TIME, self::MODEL_INSERT, 'string'],
        ];
        $autoTable[self::MODEL_UPDATE] = [
            ['card_money', $this->autoMoney($request['card_money']), self::MODEL_UPDATE, 'string'],
            ['give_money', $this->autoMoney($request['give_money']), self::MODEL_UPDATE, 'string'],
            ['market_id', $this->autoMarketId($request['market_id']), self::MODEL_UPDATE, 'string'],
            ['market_name', $this->autoMarketName($request['market_id']), self::MODEL_UPDATE, 'string'],
            ['status', $this->autoStatus($request['status']), self::MODEL_UPDATE, 'string'],
        ];
        $autoTable[self::MODEL_DELETE] = [
            ['is_delete', DELETED, self::MODEL_UPDATE, 'string'],
        ];
        $autoTable[self::ACTION_CHANGE_STATUS] = [
            ['status', $this->autoStatus($request['status']), self::MODEL_UPDATE, 'string'],
        ];
        $auto = isset($autoTable[$action]) ? $autoTable[$action] : [];
        return $auto;
    }

    /**
     * 根据操作获取类型规则
     * @param int $action
     * @param array $request
     * @return int
     * User: hjun
     * Date: 2018-12-04 15:35:19
     * Update: 2018-12-04 15:35:19
     * Version: 1.00
     */
    public function getTypeByAction($action = Model::MODEL_INSERT, $request = [])
    {
        $typeTable = [];
        $typeTable[self::MODEL_INSERT] = self::MODEL_INSERT;
        $typeTable[self::MODEL_UPDATE] = self::MODEL_UPDATE;
        $typeTable[self::MODEL_DELETE] = self::MODEL_UPDATE;
        $typeTable[self::ACTION_CHANGE_STATUS] = self::MODEL_UPDATE;
        $type = isset($typeTable[$action]) ? $typeTable[$action] : self::MODEL_BOTH;
        return $type;
    }

    /**
     * 充值卡操作
     * @param int $action
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-04 14:44:39
     * Update: 2018-12-04 14:44:39
     * Version: 1.00
     */
    public function cardAction($action = Model::MODEL_INSERT, $request = [])
    {
        $fields = $this->getFieldsByAction($action, $request);
        $validate = $this->getValidateByAction($action, $request);
        $auto = $this->getAutoByAction($action, $request);
        $type = $this->getTypeByAction($action, $request);
        $result = $this->getAndValidateDataFromRequest($fields, $request, $validate, $auto, $type);
        if (!isSuccess($result)) {
            return $result;
        }
        $data = $result['data'];
        if ($type === self::MODEL_INSERT) {
            $result = $this->add($data);
            $data['card_id'] = $result;
        } else {
            $where = [];
            $where['card_id'] = $request['card_id'];
            $result = $this->where($where)->save($data);
            $oldData = $this->getLastQueryData("card:{$request['card_id']}");
            $data = array_merge($oldData, $data);
        }
        if (false === $result) {
            return getReturn(CODE_ERROR);
        }
        return getReturn(CODE_SUCCESS, 'success', $data);
    }

    /**
     * 新增充值卡
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-04 14:42:26
     * Update: 2018-12-04 14:42:26
     * Version: 1.00
     */
    public function addCard($request = [])
    {
        $result = $this->cardAction(self::MODEL_INSERT, $request);
        if (isSuccess($result)) {
            $result['msg'] = L('ADD_SUCCESS');
        }
        return $result;
    }

    /**
     * 更新充值卡
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-04 14:42:39
     * Update: 2018-12-04 14:42:39
     * Version: 1.00
     */
    public function updateCard($request = [])
    {
        $result = $this->cardAction(self::MODEL_UPDATE, $request);
        if (isSuccess($result)) {
            $result['msg'] = L('UPD_SUCCESS');
        }
        return $result;
    }

    /**
     * 修改充值卡的状态
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-05 11:43:28
     * Update: 2018-12-05 11:43:28
     * Version: 1.00
     */
    public function changeCardStatus($request = [])
    {
        $result = $this->cardAction(self::ACTION_CHANGE_STATUS, $request);
        if (isSuccess($result)) {
            $result['msg'] = L('UPD_SUCCESS');
        }
        return $result;
    }

    /**
     * 删除充值卡
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-04 14:43:01
     * Update: 2018-12-04 14:43:01
     * Version: 1.00
     */
    public function deleteCard($request = [])
    {
        $result = $this->cardAction(self::MODEL_DELETE, $request);
        if (isSuccess($result)) {
            $result['msg'] = L('DEL_SUCCESS');
        }
        return $result;
    }

    /**
     * 删除礼包后(营销计划)的处理逻辑
     * @param int $marketId
     * @return mixed
     * User: hjun
     * Date: 2018-12-05 12:17:39
     * Update: 2018-12-05 12:17:39
     * Version: 1.00
     */
    public function afterDeleteMarket($marketId = 0)
    {
        $where = [];
        $where['market_id'] = $marketId;
        $data = [];
        $data['market_id'] = 0;
        $data['market_name'] = '';
        return $this->where($where)->save($data);
    }

    /**
     * 获取前端可显示的充值卡列表
     * @return array
     * User: hjun
     * Date: 2018-12-05 17:25:30
     * Update: 2018-12-05 17:25:30
     * Version: 1.00
     */
    public function getShowCardListData()
    {
        $field = [
            'a.*',
            'b.select_coupons', 'b.coupons_content',
            'b.select_credit', 'b.send_credit',
            'b.select_group', 'b.group_content',
        ];
        $where = [];
        $where['a.status'] = 1;
        $where['a.is_delete'] = NOT_DELETE;
        $where['a.store_id'] = $this->getStoreId();
        $join = [
            'LEFT JOIN __MB_MARKET__ b ON a.market_id = b.id'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['where'] = $where;
        $options['join'] = $join;
        $data = $this->selectList($options);
        foreach ($data as $key => $value) {
            $couponsName = null;
            $sendCredit = null;
            $text = "充{$value['card_money']}元";
            $giveMoney = round($value['give_money'], 2);
            if ($value['give_money'] > 0) {
                $text .= "，送{$giveMoney}元余额";
            }
            if ($value['market_id'] > 0) {
                // 送优惠券
                if ($value['select_coupons'] == 1) {
                    $coupons = jsonDecodeToArr($value['coupons_content']);
                    $couponsName = [];
                    foreach ($coupons as $coupon) {
                        $couponsName[] = $coupon['coupons_name'] . "优惠券{$coupon['num']}张";
                    }
                    $couponsName = implode('，', $couponsName);
                    $text .= "，送{$couponsName}";
                }
                // 送积分 没有优惠券 要加个送字
                if ($value['select_credit'] == 1) {
                    $sendCredit = round($value['send_credit']) . "积分";
                    $prefix = isset($couponsName) ? '' : '送';
                    $text .= "，{$prefix}{$sendCredit}";
                }
                // 啥都没有 显示礼包名称
                if (!isset($couponsName) && !isset($sendCredit)) {
                    $text .= "，送{$value['market_name']}";
                }
            }
            $value['text'] = $text;
            $data[$key] = $value;
        }
        return $data;
    }

    /**
     * 修改充值次数
     * @param int $cardId
     * @param int $num
     * @return mixed
     * User: hjun
     * Date: 2018-12-05 20:17:35
     * Update: 2018-12-05 20:17:35
     * Version: 1.00
     */
    public function changeRechargeNum($cardId = 0, $num = 1)
    {
        $where = [];
        $where['card_id'] = $cardId;
        return $this->where($where)->setInc('recharge_num', $num);
    }
}