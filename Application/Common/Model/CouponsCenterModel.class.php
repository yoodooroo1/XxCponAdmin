<?php

namespace Common\Model;

/**
 * TODO 测试
 * Class CouponsCenterModel
 * 领券中心模型
 * @package Common\Model
 * User: hjun
 * Date: 2017-12-07 16:57:24
 */
class CouponsCenterModel extends BaseModel
{

    protected $tableName = 'mb_coupons_center';

    protected $_validate = [
        ['coupons_id', 'require', '请选择优惠券', 0, 'regex', 3],
        ['coupons_name', 'require', '请输入优惠券名称', 0, 'regex', 3],
        ['status', '1,2', '请选择状态', 0, 'in', 3],
        ['send_type', '1,2', '请选择发放方式', 0, 'in', 3],
        ['total_num', 'require', '请输入发放总量', 0, 'regex', 3],
        ['take_after_limit_type', '1,2', '请选择过期后是否可再领取', 0, 'in', 3],
        ['take_start_time', 'require', '请选择开始时间', 0, 'regex', 3],
        ['take_end_time', 'require', '请选择结束时间', 0, 'regex', 3],
        ['to_mall_status', '1,2', '请选择是否推荐到主商城', 0, 'in', 3],
    ];

    protected $_auto = [
        ['create_time', 'time', 1, 'function']
    ];

    /**
     * 领券中心
     * @param int $storeId
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun 0
     * Date: 2017-12-08 09:44:59
     * Update: 2017-12-08 09:44:59
     * Version: 1.00
     */
    public function centerList($storeId = 0, $channelId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        if ($storeId > 0) $where['a.store_id'] = $storeId;
        if ($channelId > 0) $where['a.channel_id'] = $channelId;
        $where['a.is_delete'] = -1;
        $where['b.isdelete'] = 0;
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        if ($storeInfo['store_type'] == 2 || $storeInfo['store_type'] == 0) {
            unset($where['a.store_id']);
            $where['_string'] = "(a.store_id = {$storeId}) OR (a.channel_id = {$storeInfo['channel_id']} AND a.to_mall_status = 1)";
        }
        $where = array_merge($where, $condition);
        $field = [
            'a.id,a.store_id,a.coupons_name,a.sort,a.status,a.send_type,a.total_num,a.has_take_num',
            'a.take_start_time,a.take_end_time,a.mall_status,a.mall_sort,a.to_mall_status',
            'b.coupons_id,b.platform,b.store_id,b.store_name,b.coupons_money,b.limit_money_type,b.limit_money',
            'b.coupons_type,b.coupons_discount'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = implode(',', $field);
        $options['join'] = [
            '__MB_COUPONS__ b ON b.coupons_id = a.coupons_id'
        ];
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        // 商城按照商城排序排 单店就按照排序
        $options['order'] = strpos('02', $storeInfo['store_type'] . '') === false ?
            'a.sort DESC,a.id DESC' : 'a.mall_sort DESC,a.id DESC';
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transInfo($value);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 处理数据格式
     * @param array $info
     * @param array $storeInfo
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-12 16:51:16
     * Update: 2017-12-12 16:51:16
     * Version: 1.00
     */
    public function transInfo($info = [], $storeInfo)
    {
        if (empty($storeInfo)) {
            $storeInfo = D('Store')->getStoreInfo($info['store_id'])['data'];
        }
        // 价值
        if (isset($info['coupons_money'])) {
            switch ((int)$info['coupons_type']) {
                case 1:
                    $info['coupons_value']['value'] = $info['coupons_money'];
                    $info['coupons_value']['unit'] = $storeInfo['currency_unit'];
                    break;
                case 2:
                    $info['coupons_value']['value'] = round($info['coupons_discount'] * 10, 2);
                    $info['coupons_value']['unit'] = L('FOLD');
                    break;
                default:
                    break;
            }
            $info['coupons_value']['money'] = "{$info['coupons_value']['value']}{$info['coupons_value']['unit']}";
        }
        if (isset($info['limit_money_type'])) {
            $info['coupons_value']['limit'] = $info['limit_money_type'] == 1 ?
                L('WMKYHQ') :
                L('MDSKY', ['MONEY' => $info['limit_money']]); // "满{$info['limit_money']}可用"; // 无门槛优惠券  满xx可用
            $discountsName = $info['coupons_type'] == 1 ? L('J') : L('D'); // 减  打
            $info['coupons_value']['name'] = $info['limit_money_type'] == 1 ?
                "{$info['coupons_value']['money']}" . L('COUPON') :
                L('M') . "{$info['limit_money']}{$discountsName}{$info['coupons_value']['money']}"; // 满
        }

        // 领取起止时间
        if (isset($info['take_start_time']) || isset($info['take_end_time'])) {
            $info['take_start_time_string'] = date('Y-m-d H:i:s', $info['take_start_time']);
            $info['take_end_time_string'] = date('Y-m-d H:i:s', $info['take_end_time']);

            if ($info['take_start_time'] > NOW_TIME) {
                // 还未开始就计算 距离开始的剩余时间 秒
                $info['start_time_remaining'] = $info['take_start_time'] - NOW_TIME;
                // 超过1天 take_type = 0 否则 =1
                $info['take_type'] = $info['start_time_remaining'] > 24 * 3600 ? 0 : 1;
            } else {
                // 已经开始 未领取
                $info['take_type'] = 2;
            }
        }


        // 领取人数的百分比
        if (isset($info['send_type'])) {
            switch ((int)$info['send_type']) {
                case 1:
                    $info['take_num_radio'] = round(($info['has_take_num'] / $info['total_num']) * 100, 2);
                    break;
                case 2:
                    $info['take_num_radio'] = round(($info['today_take_num'] / $info['total_num']) * 100, 2);
                    $info['has_take_num'] = $info['today_take_num'];
                    break;
                default:
                    break;
            }
            // 如果抢光了
            if ($info['take_num_radio'] >= 100) {
                $info['take_type'] = 4;
            }
        }

        // 使用范围
        if (isset($info['limit_class_type'])) {
            switch ((int)$info['limit_class_type']) {
                case 1:
                    $info['use_range'] = L('COUPON_CAN_USE_ALL_CLASS'); /*全品类（特殊商品除外）*/
                    break;
                case 2:
                    if (!empty($info['available_class_name'])) {
                        $className = str_replace('|', '、', $info['available_class_name']);
                        $info['use_range'] = L('SPFLKY', ['CLASS' => $className]);/*"可用商品分类({$className})"*/
                    } else {
                        $limitClass = json_decode($info['limit_class'], 1);
                        $length = count($limitClass);
                        $className = $limitClass[$length - 1]['classStr'];
                        $info['use_range'] = L('SPFLBKY', ['CLASS' => $className]); //"不可用商品分类({$className})";
                    }
                    break;
                case 3:
                    if (!empty($info['available_mall_class_name'])) {
                        $className = str_replace('|', '、', $info['available_mall_class_name']);
                        $info['use_range'] = L('SCFLKY', ['CLASS' => $className]);/*"可用商城分类({$className})"*/
                    } else {
                        $data = str_replace('|', '、', $info['limit_mall_class_name']);
                        $info['use_range'] = L('SCFLBKY', ['CLASS' => $data]);//"不可用商城分类({$data})";
                    }
                    break;
                case 4:
                    $data = str_replace('|', '、', $info['limit_goods_name']);
                    $info['use_range'] = L('JXSPKY', ['GOODS' => $data]);//"仅限商品({$data})可用";
                    break;
                default:
                    break;
            }
        }

        // 优惠券期限
        if (isset($info['limit_time_type'])) {
            switch ((int)($info['limit_time_type'])) {
                case 1:
                    $info['limit_time_range'] = L('INDEFINITE_PERIOD'); // "无限期";
                    break;
                case 2:
                    $info['limit_time_range'] = L('LQH', ['LIMIT' => $info['limit_time']]);//"领取后{$info['limit_time']}天内有效";
                    break;
                case 3:
                    $startTime = date('Y.m.d', $info['limit_start_time']);
                    $endTime = date('Y.m.d', $info['limit_end_time']);
                    $info['limit_time_range'] = "{$startTime}-{$endTime}";
                    break;
                default:
                    break;
            }
        }

        return $info;
    }


    /**
     * 获取领券优惠券的信息
     * @param int $id
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-13 11:28:11
     * Update: 2017-12-13 11:28:11
     * Version: 1.00
     */
    public function getCenterCouponsInfo($id = 0)
    {
        $where = [];
        $where['id'] = $id;
        $where['is_delete'] = -1;
        $field = [
            'id,store_id,coupons_id,coupons_name,coupons_img,send_type,total_num,take_limit_num,take_after_limit_type',
            'take_start_time,take_end_time,status,to_mall_status,sort'
        ];
        $options = [];
        $options['where'] = $where;
        $options['field'] = implode(',', $field);
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, L('RECORD_INVALID'));
        $result['data'] = $this->transInfo($info);
        return $result;
    }

    /**
     * 获取首页领券中心列表
     * @param int $storeId
     * @param int $channelId
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-15 14:02:18
     * Update: 2017-12-15 14:02:18
     * Version: 1.00
     */
    public function getIndexCenterList($storeId = 0, $channelId = 0, $condition = [])
    {
        $where = [];
        if ($storeId > 0) $where['a.store_id'] = $storeId;
        if ($channelId > 0) $where['a.channel_id'] = $channelId;
        $where['a.is_delete'] = -1;
        $where['a.status'] = 1;
        $where['a.take_start_time'] = ['elt', NOW_TIME];
        $where['a.take_end_time'] = ['gt', NOW_TIME];
        $where['b.isdelete'] = 0;
        $where['_string'] = "(a.send_type = 1 AND a.total_num > a.has_take_num) OR (a.send_type = 2 AND a.total_num > a.today_take_num)";
        $map = [];
        $map['b.limit_time_type'] = [['eq', 1], 2, 'or'];
        $map['_string'] = "b.limit_time_type = 3 AND b.limit_end_time > " . NOW_TIME;
        $map['_logic'] = 'or';
        $where['_complex'] = $map;
        $where = array_merge($where, $condition);
        $field = [
            'a.id,a.store_id',
            'b.coupons_id, b.coupons_money,b.limit_money_type,b.limit_money',
            'b.coupons_type,b.coupons_discount'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = implode(',', $field);
        $options['join'] = [
            '__MB_COUPONS__ b ON b.coupons_id = a.coupons_id'
        ];
        $options['where'] = $where;
        $options['order'] = 'a.sort DESC,a.id DESC';
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transInfo($value);
        }
        return getReturn(200, '', $list);
    }

    /**
     * 判断优惠券是否过期
     * @param array $coupons
     * @return boolean
     * User: hjun
     * Date: 2018-06-01 20:41:44
     * Update: 2018-06-01 20:41:44
     * Version: 1.00
     */
    public function isCouponsTimeOut($coupons = [])
    {
        switch ((int)$coupons['limit_time_type']) {
            case 2:
            case 3:
                if ($coupons['end_time'] <= NOW_TIME) {
                    return true;
                }
                break;
            default:
                break;
        }
        return false;
    }

    /**
     * 领取领券中心的优惠券
     * @param int $id 领券中心ID
     * @param int $memberId 会员ID
     * @return array
     * User: hjun
     * Date: 2017-12-15 14:32:31
     * Update: 2017-12-15 14:32:31
     * Version: 1.00
     */
    public function takeCenterCoupons($id = 0, $memberId = 0)
    {
        $where = [];
        $where['a.id'] = $id;
        $where['a.is_delete'] = -1;
        $where['b.isdelete'] = 0;
        $where['a.status'] = 1;
        $field = [
            'a.id,a.coupons_id,a.send_type,a.total_num,a.has_take_num,a.today_take_num,a.take_limit_num',
            'a.take_after_limit_type,a.take_start_time,a.take_end_time',
            'b.store_id,b.coupons_name,b.limit_time,b.limit_money,b.coupons_money',
            'b.store_head,b.store_name,b.limit_sales,b.limit_class,b.platform',
            'b.channel_id,b.instructions,b.limit_type,b.limit_money_type,b.limit_time_type',
            'b.limit_start_time,limit_end_time,b.limit_class_type,b.limit_mall_class',
            'b.limit_mall_class_name,b.limit_goods,b.limit_goods_name',
            'b.available_class', 'b.available_mall_class', 'b.available_class_name',
            'b.available_mall_class_name',
            'b.coupons_type,b.coupons_discount'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['join'] = [
            '__MB_COUPONS__ b ON a.coupons_id = b.coupons_id'
        ];
        $options['where'] = $where;
        $options['field'] = implode(',', $field);
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) {
            return $result;
        }
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, L('RECORD_INVALID'));

        // 判断领取时间是否有效
        if ($info['take_start_time'] > NOW_TIME) {
            return getReturn(-1, '领取时间未到');
        }
        if ($info['take_end_time'] <= NOW_TIME) {
            return getReturn(-1, '领取时间已过,去看看别的券吧');
        }

        // 判断优惠券是否过期
        if ($info['limit_time_type'] == 3) {
            if ($info['limit_end_time'] <= NOW_TIME) {
                return getReturn(406, '优惠券已过期,去看看别的券吧');
            }
        }

        // 判断领取的数量是否足够
        switch ((int)$info['send_type']) {
            case 1:
                if ($info['has_take_num'] >= $info['total_num']) {
                    return getReturn(-1, '已经领完啦,下次早点来吧');
                }
                break;
            case 2:
                if ($info['today_take_num'] >= $info['total_num']) {
                    return getReturn(-1, '今天已经领完啦,明天再来吧');
                }
                break;
            default:
                break;
        }

        // 获取会员已经领取的优惠券
        $model = D('MemberCouponsCenter');
        $result = $model->getMemberCenterList($memberId, $id);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $total = count($list);

        // 判断会员领取的数量是否超过限制
        if ($total >= $info['take_limit_num']) {
            $cantTakeMsg = '不能领取更多哦！贪心会长胖哦,去看看别的券吧';
            // 超过限制的话 判断是否设置了领取后还能领取
            switch ((int)$info['take_after_limit_type']) {
                case 1:
                    // 不能领取
                    $cantTakeMsg = '不能领取更多哦！贪心会长胖哦,去看看别的券吧';
                    return getReturn(-1, $cantTakeMsg);
                    break;
                case 2:
                    // 过期或使用后还能领取 需要判断之前领取的券是否已经过期或者使用
                    $canTake = true;
                    $model = D('MemberCoupons');
                    foreach ($list as $key => $value) {
                        $where = [];
                        $where['id'] = $value['member_coupons_id'];
                        $options = [];
                        $options['where'] = $where;
                        $options['field'] = [
                            'id,limit_time,end_time,limit_time_type,state'
                        ];
                        $result = $model->queryRow($options);
                        if ($result['code'] !== 200) return $result;
                        $memberCouponInfo = $result['data'];
                        // 只要有一个没有使用过 就不能再领取了
                        if ($memberCouponInfo['state'] == 0 && !$this->isCouponsTimeOut($memberCouponInfo)) {
                            $canTake = false;
                            // 直接不再循环
                            break;
                        }
                    }
                    if ($canTake === false) return getReturn(-1, $cantTakeMsg);
                    break;
                default:
                    break;
            }
        }
        // 领取优惠券
        $this->startTrans();
        $model = D('MemberCoupons');
        $data = [];
        $result = $model->queryMax([], 'version');
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }
        $data['version'] = $result['data'] + 1;
        $data['member_id'] = $memberId;
        $data['remark'] = "买家领取";
        $data['create_time'] = NOW_TIME;
        switch ((int)$info['limit_time_type']) {
            case 1:
                $data['end_time'] = NOW_TIME;
                break;
            case 2:
                $data['end_time'] = NOW_TIME + $info['limit_time'] * 24 * 60 * 60;
                break;
            case 3:
                $data['end_time'] = $info['limit_end_time'];
                break;
            default:
                break;
        }
        $createField = [
            'store_id', 'coupons_id', 'coupons_name', 'limit_time', 'limit_money',
            'coupons_money', 'store_head', 'store_name', 'limit_sales', 'limit_class',
            'platform', 'channel_id', 'instructions', 'limit_type', 'limit_money_type',
            'limit_time_type', 'limit_start_time', 'limit_end_time', 'limit_class_type',
            'limit_mall_class', 'limit_mall_class_name', 'limit_goods', 'limit_goods_name',
            'coupons_type', 'coupons_discount', 'available_class', 'available_mall_class',
            'available_class_name', 'available_mall_class_name',
        ];
        foreach ($createField as $key => $value) {
            if (isset($info[$value])) {
                $data[$value] = $info[$value];
            }
        }
        $result = $model->addData([], $data);
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }
        // 领取成功添加一条领取记录
        $memberCouponId = $result['data'];
        $result = D('MemberCouponsCenter')->addMemberCenter($memberId, $id, $memberCouponId);
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }

        // 领取数量+1
        $data = [];
        $result = $this->queryMax(['where' => []], 'version');
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }
        $data['version'] = $result['data'] + 1;
        $data['has_take_num'] = ['exp', 'has_take_num+1'];
        $data['today_take_num'] = ['exp', 'today_take_num+1'];
        $where = [];
        $where['id'] = $id;
        $result = $this->saveData(['where' => $where], $data);
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }
        // 发放数量+1
        $data = [];
        $result = D('Coupons')->queryMax([], 'version');
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }
        $data['version'] = $result['data'] + 1;
        $data['send_num'] = ['exp', 'send_num+1'];
        $where = [];
        $where['coupons_id'] = $info['coupons_id'];
        $result = D('Coupons')->saveData(['where' => $where], $data);
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }

        $this->commit();
        return getReturn(200, '领取成功', $memberCouponId);
    }

    /**
     * 获取领券中心的优惠券列表
     * @param int $storeId
     * @param int $memberId 会员ID
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-17 22:10:46
     * Update: 2017-12-17 22:10:46
     * Version: 1.00
     */
    public function getCenterCouponsList($storeId = 0, $memberId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        if ($storeId > 0) $where['a.store_id'] = $storeId;
        $where['a.is_delete'] = -1;
        $where['a.status'] = 1;
        $where['b.isdelete'] = 0;
        // 查出未结束的
        $where['a.take_end_time'] = ['gt', NOW_TIME];
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        if (empty($storeInfo)) return getReturn(404, '商家信息加载错误,请重试...');
        // 商城需要查出子店推送过来的
        if ($storeInfo['store_type'] == 2 || $storeInfo['store_type'] == 0) {
            unset($where['a.store_id']);
            unset($where['a.status']);
            $where['_string'] = "(a.store_id = {$storeId} AND a.status = 1) OR (a.channel_id = {$storeInfo['channel_id']} AND a.to_mall_status = 1 AND a.mall_status = 1)";
        }
        $map = [];
        $map['b.limit_time_type'] = [['eq', 1], 2, 'or'];
        $map['_string'] = "b.limit_time_type = 3 AND b.limit_end_time > " . NOW_TIME;
        $map['_logic'] = 'or';
        $where['_complex'] = $map;
        $where = array_merge($where, $condition);
        $field = [
            'a.id,a.coupons_name,a.coupons_img,a.send_type,a.total_num,a.has_take_num,a.today_take_num',
            'a.take_start_time,a.take_end_time,a.take_after_limit_type,a.to_mall_status',
            'b.coupons_id,b.coupons_money,b.limit_money_type,b.limit_money,b.store_name,b.store_id',
            'b.coupons_type,b.coupons_discount,b.limit_class_type,b.limit_class,b.limit_goods_name,b.limit_mall_class_name',
            'b.limit_time_type,b.limit_start_time,b.limit_end_time,b.limit_time',
            'b.limit_type,b.limit_class,b.limit_mall_class,b.limit_goods',
            'b.available_class_name', 'b.available_mall_class_name',
            'a.store_id',
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = implode(',', $field);
        $options['join'] = [
            '__MB_COUPONS__ b ON b.coupons_id = a.coupons_id'
        ];
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        // 商城按照商城排序排 单店就按照排序
        $options['order'] = strpos('02', $storeInfo['store_type'] . '') === false ?
            'a.sort DESC,a.id DESC' : 'a.mall_sort DESC,a.id DESC';
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        // 获取用户领取过的优惠券列表
        $model = D('MemberCouponsCenter');
        $memberCouponList = $model->getMemberHasTakeCoupons($memberId)['data'];
        // 获取用户设置的未提醒的领券ID
        $model = D('MessageTip');
        $where = [];
        $where['param_type'] = 1;
        $where['status'] = 2;
        $hasTipCenterId = $model->queryField(['where' => $where], 'param_id', true)['data'];

        foreach ($list as $key => $value) {
            $list[$key] = $this->transInfo($value, $storeInfo);

            // 判断是否设置过了提醒
            $list[$key]['has_tip'] = 0;
            foreach ($hasTipCenterId as $val) {
                if ($value['id'] == $val) {
                    $list[$key]['has_tip'] = 1;
                }
            }

            // 如果是商城 子店推送过来的优惠券要显示店铺名称
            if (($storeInfo['store_type'] == 2 || $storeInfo['store_type'] == 0) &&
                $value['to_mall_status'] == 1) {
                $list[$key]['coupons_name'] = "[{$value['store_name']}]{$value['coupons_name']}";
                // 是否是子店推送过来的
                $list[$key]['is_child_push'] = 1;
            }

            if (!empty($memberCouponList)) {
                // 根据不同的设置判断用户是否领取了
                switch ((int)$value['take_after_limit_type']) {
                    case 2:
                        // 领取后还可领 需要筛选出未过期或使用的
                        foreach ($memberCouponList as $k => $val) {
                            if ($val['center_id'] == $value['id'] && $val['has_use_or_time_out_status'] == 2) {
                                // 已经领取了
                                $list[$key]['take_type'] = 3;
                                $list[$key]['use_status'] = $val['state'];
                                $list[$key]['member_coupons_id'] = $val['member_coupons_id'];
                                break;
                            }
                        }
                        break;
                    default:
                        // 不可再领
                        foreach ($memberCouponList as $k => $val) {
                            if ($val['center_id'] == $value['id']) {
                                // 已经领取了
                                $list[$key]['take_type'] = 3;
                                $list[$key]['use_status'] = $val['has_use_or_time_out_status'] == 1 ? 2 : $val['state'];
                                $list[$key]['member_coupons_id'] = $val['member_coupons_id'];
                                break;
                            }
                        }
                        break;
                }
            }


            // 设置提醒的人数
            $where = [];
            $where['status'] = 2;
            $where['param_type'] = 1;
            $where['param_id'] = $value['id'];
            $list[$key]['tip_num'] = D('MessageTip')->queryCount(['where' => $where]);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 添加提醒任务
     * @param int $memberId
     * @param int $storeId
     * @param int $centerId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-21 11:45:35
     * Update: 2017-12-21 11:45:35
     * Version: 1.00
     */
    public function addTakeCouponsMsgTip($memberId = 0, $storeId = 0, $centerId = 0)
    {
        // 获取优惠券信息
        $where = [];
        $where['a.id'] = $centerId;
        $field = [
            'a.id,a.coupons_name,a.take_start_time,a.take_end_time'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['where'] = $where;
        $options['field'] = $field;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, L('RECORD_INVALID'));

        // 获取会员信息
        $model = D('Member');
        $result = $model->getMemberInfo($memberId);
        if ($result['code'] !== 200) return $result;
        $memberInfo = $result['data'];
        $memberInfo['wx_openid'] = D('StoreMember')->getStoreMemberOpenId($storeId, $memberId);

        // 参数
        $data = [];
        $data['type'] = 1;
        $data['param_type'] = 1;
        $data['param_id'] = $centerId;
        // 提前5分钟提醒
        $data['reminder_time'] = $info['take_start_time'] - 300;
        // 提醒内容设置
        $content = '{"first":{"value":"{FIRST}","color":"#173177"},"keyword1":{"value":"{MEMBER_NAME}","color":"#173177"},"keyword2":{"value":"{THEME}","color":"#173177"},"keyword3":{"value":"{TIME}","color":"#173177"},"keyword4":{"value":"{ADDRESS}","color":"#173177"},"remark":{"value":"{REMARK}","color":"#173177"}}';
        $key = ['FIRST', 'MEMBER_NAME', 'THEME', 'TIME', 'ADDRESS', 'REMARK'];
        $temp['FIRST'] = "您好,您参加的领券活动即将开始。";
        $temp['MEMBER_NAME'] = $memberInfo['member_nickname'];
        $temp['THEME'] = "[领券]{$info['coupons_name']}";
        $temp['TIME'] = date('Y-m-d H:i:s', $info['take_start_time']) . ' - ' . date("Y-m-d H:i:s", $info['take_end_time']);
        $temp['ADDRESS'] = '微信商城领券中心';
        $temp['REMARK'] = '点击立即前往领券中心。';
        foreach ($key as $value) {
            if (strpos($content, "{{$value}}") !== false) {
                $content = str_replace("{{$value}}", $temp[$value], $content);
            }
        }
        $data['template_content'] = [];
        $data['template_content']['touser'] = $memberInfo['wx_openid'];
        $result = D('WxUtil')->getTemplateIDByTitle($storeId, '报名成功通知', 'OPENTM413295887');
        if ($result['code'] !== 200) {
            return $result;
        }
        $templateId = $result['data'];
        $data['template_content']['template_id'] = $templateId;
        $data['template_content']['url'] = "http://{$_SERVER['HTTP_HOST']}/index.php?c=Coupon&a=couponCenter&se={$storeId}";
        $data['template_content']['data'] = json_decode($content, 1);
        $data['template_content'] = json_encode($data['template_content'], JSON_UNESCAPED_UNICODE);
        return D('MessageTip')->addMsgTipTask($memberId, $storeId, $data);
    }

    /**
     * 获取商品店内可用的优惠券
     * @param int $goodsId
     * @param int $memberId
     * @return array
     * User: hjun
     * Date: 2017-12-28 14:44:37
     * Update: 2017-12-28 14:44:37
     * Version: 1.00
     */
    public function getGoodsCenterCouponsList($goodsId = 0, $memberId = 0)
    {
        // 获取商品的信息
        $model = D('GoodsExtra');
        $where = [];
        $where['goods_id'] = $goodsId;
        $field = [
            'goods_id,store_id,goods_class_1,goods_class_2,goods_class_3,is_qinggou,is_promote',
            'mall_class_1,mall_class_2,mall_class_3'
        ];
        $options = [];
        $options['where'] = $where;
        $options['field'] = implode(',', $field);
        $result = $model->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(404, L('SPSJSB'));

        // 获取该商品可用的领券优惠券
        $where = [];
        $where['a.is_delete'] = -1;
        $where['a.store_id'] = $info['store_id'];
        $where['a.take_start_time'] = ['elt', NOW_TIME];
        $where['a.take_end_time'] = ['gt', NOW_TIME];
        $result = $this->getCenterCouponsList($info['store_id'], $memberId, 1, 0, $where);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $newList = [
            'can_take' => [
                'list' => [],
                'total' => 0
            ],
            'has_take' => [
                'list' => [],
                'total' => 0
            ],
            'list' => [],
            'total' => 0
        ];
        foreach ($list as $key => $value) {
            $value['Id'] = $value['id'];
            // hj 2018-02-24 15:24:56 判断跳过使用标记变量 因为swtich中不能使用continue;
            $continue = false;
            switch ((int)$value['take_type']) {
                case 2:
                    // 判断该优惠券该商品是否可用
                    if ($value['limit_type'] == 1 && ($info['is_qinggou'] == 1 || $info['is_promote'] == 1)) {
                        $continue = true;
                    }
                    if ($continue) {
                        break;
                    }
                    switch ((int)$value['limit_class_type']) {
                        case 2:
                            $classId = [];
                            $limitClass = json_decode($value['limit_class'], 1);
                            foreach ($limitClass as $k => $val) {
                                if (!empty($val['class_id'])) {
                                    $classId[] = $val['class_id'];
                                }
                            }
                            if (in_array($info['goods_class_1'], $classId) ||
                                in_array($info['goods_class_2'], $classId) ||
                                in_array($info['goods_class_3'], $classId)) {
                                $continue = true;
                            }
                            break;
                        case 3:
                            $classId = explode(',', $value['limit_mall_class']);
                            if (in_array($info['mall_class_1'], $classId) ||
                                in_array($info['mall_class_2'], $classId) ||
                                in_array($info['mall_class_3'], $classId)) {
                                $continue = true;
                            }
                            break;
                        case 4:
                            $goodsId = explode(',', $value['limit_goods']);
                            if (!in_array($info['goods_id'], $goodsId)) {
                                $continue = true;
                            }
                            break;
                        default:
                            break;
                    }
                    if (!$continue) {
                        $newList['can_take']['total']++;
                        $newList['can_take']['list'][] = $value;
                        $newList['total']++;
                    }
                    break;
                case 3:
                    // 已经领取的重新计算使用期限
                    $model = D('MemberCoupons');
                    $where = [];
                    $where['id'] = $value['member_coupons_id'];
                    $options = [];
                    $options['where'] = $where;
                    $options['field'] = 'limit_time_type,create_time,end_time,limit_start_time,limit_end_time';
                    $mpInfo = $model->queryRow($options)['data'];
                    switch ((int)$mpInfo['limit_time_type']) {
                        case 1:
                            $list[$key]['limit_time_range'] = L('INDEFINITE_PERIOD');
                            break;
                        case 2:
                            $startTime = date("Y.m.d", $mpInfo['create_time']);
                            $endTime = date("Y.m.d", $mpInfo['end_time']);
                            $list[$key]['limit_time_range'] = "{$startTime}-{$endTime}";
                            break;
                        case 3:
                            $startTime = date("Y.m.d", $mpInfo['limit_start_time']);
                            $endTime = date("Y.m.d", $mpInfo['limit_end_time']);
                            $list[$key]['limit_time_range'] = "{$startTime}-{$endTime}";
                            break;
                        default:
                            break;
                    }
                    $newList['has_take']['total']++;
                    $newList['has_take']['list'][] = $value;
                    $newList['total']++;
                    break;
                default:
                    break;
            }
        }
        $newList['list'] = array_merge($newList['can_take']['list'], $newList['has_take']['list']);
        return getReturn(200, '', $newList);
    }

    /**
     * 获取购物车 店铺的领券列表
     * @param int $storeId
     * @param int $memberId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-29 10:28:22
     * Update: 2017-12-29 10:28:22
     * Version: 1.00
     */
    public function getCartCentCouponsList($storeId = 0, $memberId = 0)
    {
        // 获取该商品可用的领券优惠券
        $where = [];
        $where['a.is_delete'] = -1;
        $where['a.store_id'] = $storeId;
        $where['a.take_start_time'] = ['elt', NOW_TIME];
        $where['a.take_end_time'] = ['gt', NOW_TIME];
        $result = $this->getCenterCouponsList($storeId, $memberId, 1, 0, $where);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $newList = [
            'can_take' => [
                'list' => [],
                'total' => 0
            ],
            'has_take' => [
                'list' => [],
                'total' => 0
            ],
            'total' => 0
        ];
        foreach ($list as $key => $value) {
            $value['Id'] = $value['id'];
            switch ((int)$value['take_type']) {
                case 2:
                    $newList['can_take']['total']++;
                    $newList['can_take']['list'][] = $value;
                    $newList['total']++;
                    break;
                case 3:
                    // 已经领取的重新计算使用期限
                    $model = D('MemberCoupons');
                    $where = [];
                    $where['id'] = $value['member_coupons_id'];
                    $options = [];
                    $options['where'] = $where;
                    $options['field'] = 'limit_time_type,create_time,end_time,limit_start_time,limit_end_time';
                    $mpInfo = $model->queryRow($options)['data'];
                    switch ((int)$mpInfo['limit_time_type']) {
                        case 1:
                            $list[$key]['limit_time_range'] = "无限期";
                            break;
                        case 2:
                            $startTime = date("Y.m.d", $mpInfo['create_time']);
                            $endTime = date("Y.m.d", $mpInfo['end_time']);
                            $list[$key]['limit_time_range'] = "{$startTime}-{$endTime}";
                            break;
                        case 3:
                            $startTime = date("Y.m.d", $mpInfo['limit_start_time']);
                            $endTime = date("Y.m.d", $mpInfo['limit_end_time']);
                            $list[$key]['limit_time_range'] = "{$startTime}-{$endTime}";
                            break;
                        default:
                            break;
                    }
                    $newList['has_take']['total']++;
                    $newList['has_take']['list'][] = $list[$key];
                    $newList['total']++;
                    break;
                default:
                    break;
            }
        }
        return getReturn(200, '', $newList);
    }

    /**
     * 获取商家的领券中心可领数量
     * @param int $storeId
     * @return int
     * User: hjun
     * Date: 2018-01-04 09:18:23
     * Update: 2018-01-04 09:18:23
     * Version: 1.00
     */
    public function getCouponsCenterCountByStoreId($storeId = 0)
    {
        $where = [];
        if ($storeId > 0) $where['a.store_id'] = $storeId;
        $where['a.is_delete'] = -1;
        $where['a.status'] = 1;
        $where['a.take_start_time'] = ['elt', NOW_TIME];
        $where['a.take_end_time'] = ['gt', NOW_TIME];
        $where['b.isdelete'] = 0;
        $map = [];
        $map['b.limit_time_type'] = [['eq', 1], 2, 'or'];
        $map['_string'] = "b.limit_time_type = 3 AND b.limit_end_time > " . NOW_TIME;
        $map['_logic'] = 'or';
        $where['_complex'] = $map;
        $num = $this
            ->alias('a')
            ->field('b.coupons_id,b.limit_start_time,b.limit_end_time,b.limit_time_type')
            ->join('__MB_COUPONS__ b ON a.coupons_id = b.coupons_id')
            ->where($where)
            ->count();
        return $num > 0 ? $num : 0;
    }

    /**
     * 保存优惠券到领券中心
     * @param int $storeId
     * @param int $channelId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-08 13:51:30
     * Update: 2017-12-08 13:51:30
     * Version: 1.00
     */
    public function saveCouponsToCenter($storeId = 0, $channelId = 0, $data = [])
    {
        $options = [];
        $options['field'] = [
            'coupons_id', 'coupons_name', 'coupons_img', 'store_id', 'channel_id', 'sort', 'status',
            'send_type', 'total_num', 'take_limit_num', 'take_after_limit_type', 'take_start_time', 'take_end_time',
            'to_mall_status'
        ];

        if (empty($data['id'])) {
            // 新增要加入创建时间
            $options['field'][] = 'create_time';
        } else {
            // 编辑检查数据有效性
            $where = [];
            $where['id'] = $data['id'];
            $where['is_delete'] = -1;
            $count = $this->queryCount(['where' => $where]);
            if (empty($count)) return getReturn(-1, L('RECORD_INVALID'));
        }


        // 检查优惠券
        $model = D('Coupons');
        $where = [];
        $where['coupons_id'] = $data['coupons_id'];
        $where['isdelete'] = 0;
        $couponsCount = $model->queryCount(['where' => $where]);
        if (empty($couponsCount)) return getReturn(-1, '选择的优惠券已经失效');

        // 排序
        $data['sort'] = isset($data['sort']) ? $data['sort'] : 9;

        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        // 如果当前是商城 商城排序和排序相等
        if (strpos('02', $storeInfo['store_type'] . '') !== false) {
            $data['mall_sort'] = $data['sort'];
            $data['to_mall_status'] = 2;
            $options['field'][] = 'mall_sort';
        }

        $data['store_id'] = $storeId;
        $data['channel_id'] = $channelId;

        // 检查logo 空的话默认店铺logo
        if (empty($data['coupons_img'])) {
            $data['coupons_img'] = $storeInfo['store_label'];
        }

        // 检查时间
        $result = checkStartTimeAndEndTime($data['take_start_time_string'], $data['take_end_time_string']);
        if ($result['code'] !== 200) return $result;
        $data['take_start_time'] = $result['data']['start_time'];
        $data['take_end_time'] = $result['data']['end_time'];

        // 如果是推荐到商城并且是子店 默认状态关闭 排序9
        if ($data['to_mall_status'] == 1 && strpos('13', $storeInfo['store_type'] . '') !== false) {
            $data['mall_status'] = 2;
            $data['mall_sort'] = 9;
            $options['field'][] = 'mall_status';
            $options['field'][] = 'mall_sort';
        }

        $data['version'] = $this->max('version') + 1;
        if (!empty($data['id'])) {
            $options['where'] = ['id' => $data['id']];
            return $this->saveData($options, $data);
        } else {
            return $this->addData($options, $data);
        }
    }

    /**
     * 下架领券中心的优惠券
     * 单个或者批量
     * @param string $id
     * @param int $storeId
     * @param int $channelId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-08 14:32:35
     * Update: 2017-12-08 14:32:35
     * Version: 1.00
     */
    public function delCouponsInCenter($id = '', $storeId = 0, $channelId = 0)
    {
        if (empty($id)) return getReturn(-1, L('INVALID_PARAM'));
        $idArr = explode(',', $id);
        $data = [];
        $maxVersion = $this->queryMax([], 'version')['data'];
        foreach ($idArr as $key => $value) {
            $where = [];
            $where['id'] = $value;
            $where['channel_id'] = $channelId;
            $where['is_delete'] = -1;
            $info = $this->queryRow(['where' => $where, 'field' => 'id,store_id'])['data'];
            $index = $key + 1;
            if (empty($info)) return getReturn(-1, "第{$index}条记录已经失效,请刷新页面重试...");
            $item = [];
            $item['version'] = ++$maxVersion;
            $item['id'] = $info['id'];
            // 自己的移除 子店的是不推荐到商城
            if ($info['store_id'] == $storeId) {
                $item['is_delete'] = 1;
            } else {
                $item['to_mall_status'] = 2;
            }
            $data[] = $item;
        }
        return $this->saveAllData([], $data);
    }

    /**
     * 改变排序
     * @param int $id
     * @param int $sort
     * @param int $storeId 当前操作的商家ID
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-08 14:36:51
     * Update: 2017-12-08 14:36:51
     * Version: 1.00
     */
    public function changeSort($id = 0, $sort = 9, $storeId = 0)
    {
        $where = [];
        $where['id'] = $id;
        $where['is_delete'] = -1;
        $info = $this->queryRow(['where' => $where, 'field' => 'id,store_id'])['data'];
        if (empty($info)) return getReturn(-1, L('RECORD_INVALID'));
        $data = [];
        $data['sort'] = isset($sort) ? (int)$sort : 9;
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        if (strpos('02', $storeInfo['store_type'] . '') !== false && $storeId == $info['store_id']) {
            // 如果是商城改自己的排序 也要同时改商城排序
            $data['mall_sort'] = $data['sort'];
        } elseif ($storeId != $info['store_id']) {
            // 如果是商城改子店的排序 改的是商城排序 不能改排序
            $data['mall_sort'] = $data['sort'];
            unset($data['sort']);
        }
        $data['version'] = $this->queryMax([], 'version')['data'] + 1;
        return $this->saveData(['where' => $where], $data);
    }

    /**
     * 改变状态
     * @param int $id
     * @param int $status
     * @param int $storeId 当前的商家ID
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-08 14:37:33
     * Update: 2017-12-08 14:37:33
     * Version: 1.00
     */
    public function changeStatus($id = 0, $status = 1, $storeId = 0)
    {
        $where = [];
        $where['id'] = $id;
        $where['is_delete'] = -1;
        $info = $this->queryRow(['where' => $where, 'field' => 'id,status,mall_status,store_id'])['data'];
        if (empty($info)) return getReturn(-1, L('RECORD_INVALID'));
        if ($info['status'] == $status && $info['store_id'] == $storeId) return getReturn(-1, L('OUT_OF_DATE'));
        if ($info['mall_status'] == $status && $info['store_id'] != $storeId) return getReturn(-1, L('OUT_OF_DATE'));
        $data = [];
        $data['status'] = $status;
        if ($info['store_id'] != $storeId) {
            // 如果是商城改子店的状态 改的是商城状态 不能改状态
            $data['mall_status'] = $status;
            unset($data['status']);
        }
        $data['version'] = $this->queryMax([], 'version')['data'] + 1;
        return $this->saveData(['where' => $where], $data);
    }

    /**
     * 清理每日领取数量
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-18 15:17:59
     * Update: 2017-12-18 15:17:59
     * Version: 1.00
     */
    public function clearTodayTakeNum()
    {
        $time = date("H:i");
        $sec = date('s');
        if ($time === '00:00' && $sec >= 0 && $sec <= 59) {
            $i = 0;
            do {
                $where = [];
                $where['is_delete'] = -1;
                $data = [];
                $data['today_take_num'] = 0;
                $result = $this->saveData(['where' => $where], $data);
                $i++;
            } while ($result['code'] !== 200 && $i < 5);
            return $result;
        }
    }

    /*获取店铺再领券中心领取记录*/
    public function getCouponsCenterLog($store_id = 0, $limit = 50)
    {
        $w = array();
        $w['xunxin_mb_membercoupons.store_id'] = $store_id;
        $center = M('mb_member_coupons_center');
        $log = $center->join('xunxin_mb_membercoupons ON xunxin_mb_membercoupons.id = xunxin_mb_member_coupons_center.member_coupons_id')
            ->join('xunxin_member ON xunxin_member.member_id = xunxin_mb_member_coupons_center.member_id')
            ->where($w)
            ->field('xunxin_member.member_name,xunxin_member.member_truename,xunxin_member.member_avatar,xunxin_member.member_nickname,xunxin_mb_membercoupons.coupons_name')
            ->order('xunxin_mb_member_coupons_center.id DESC')
            ->limit($limit)
            ->select();
        return $log;
    }
}
