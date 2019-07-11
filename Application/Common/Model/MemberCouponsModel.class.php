<?php

namespace Common\Model;
/**
 * Class MemberCouponsModel
 * 会员优惠券
 * @package Common\Model
 * User: hjun
 * Date: 2017-12-14 10:23:21
 */
class MemberCouponsModel extends BaseModel
{
    protected $tableName = 'mb_membercoupons';

    /**
     * 空的限制分类值设置为 ''
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-05 10:25:32
     * Update: 2017-12-05 10:25:32
     * Version: 1.00
     */
    public function setEmptyLimitClass()
    {
        $where = [];
        $where['limit_class'] = [['EXP', ' IS NULL '], '', '""', '[]', 'OR'];
        $options = [];
        $options['where'] = $where;
        $count = $this->queryCount($options);
        $page = ceil($count / 1000);
        $i = 1;
        $data = [];
        $maxVersion = $this->max('version');
        do {
            $options['page'] = $i;
            $options['limit'] = 1000;
            $options['field'] = 'id';
            $result = $this->queryList($options);
            if ($result['code'] !== 200) return $result;
            $list = $result['data']['list'];
            foreach ($list as $key => $value) {
                $item = [];
                $item['id'] = $value['id'];
                $item['limit_class'] = '';
                $item['version'] = ++$maxVersion;
                $data[] = $item;
            }
            $i++;
        } while ($i <= $page);
        return $this->saveAllData([], $data);
    }

    /**
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-14 12:03:15
     * Update: 2017-12-14 12:03:15
     * Version: 1.00
     */
    public function setTypeAndChannelIdAndPlatform()
    {
        $total = $this->queryTotal();
        $count = ceil($total / 1000);
        $page = 1;
        $data = [];
        $maxVersion = $this->max('version');
        do {
            $options = [];
            $options['page'] = $page;
            $options['take'] = 1000;
            $options['field'] = 'id,store_id,limit_time,limit_money,limit_class';
            $list = $this->queryList($options)['data']['list'];
            foreach ($list as $key => $value) {
                $storeInfo = D('Store')->getStoreInfo($value['store_id'])['data'];
                $item = [];
                $item['id'] = $value['id'];
                $item['channel_id'] = $storeInfo['channel_id'];
                $item['platform'] = $storeInfo['store_type'] == 0 || $storeInfo['store_type'] == 2 ? 1 : 0;
                $item['limit_money_type'] = empty($value['limit_money']) ? 1 : 2;
                $item['limit_class_type'] = empty($value['limit_class']) ? 1 : 2;
                $item['limit_time_type'] = $value['limit_time'] > 0 ? 2 : 1;
                $item['version'] = ++$maxVersion;
                $data[] = $item;
            }
            $page++;
        } while ($page <= $count);
        return $this->saveAllData([], $data);
    }

    /**
     * 获取已发优惠券列表
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array
     * User: hjun
     * Date: 2017-12-14 13:36:41
     * Update: 2017-12-14 13:36:41
     * Version: 1.00
     */
    public function getSendList($storeId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        if ($storeId > 0) $where['store_id'] = $storeId;
        $where['a.isdelete'] = 0;
        $where = array_merge($where, $condition);
        $field = [
            'a.id,a.coupons_id,a.member_id,a.create_time,a.end_time,a.remark,a.limit_money_type,a.limit_money',
            'a.coupons_name',
            'a.limit_time_type,a.state,a.bindorder order_id,a.coupons_money',
            'a.coupons_type,a.coupons_discount',
            'b.member_name,b.member_nickname',
            'a.store_id',
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = implode(',', $field);
        $options['join'] = [
            '__MEMBER__ b ON a.member_id = b.member_id'
        ];
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = 'a.id DESC';
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
     * 处理信息数据
     * @param array $info
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-14 13:47:42
     * Update: 2017-12-14 13:47:42
     * Version: 1.00
     */
    public function transInfo($info = [])
    {
        // 使用金额限制
        $storeInfo = D('Store')->getStoreInfo($info['store_id'])['data'];
        if (isset($info['limit_money_type'])) {
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
            $info['coupons_value']['limit'] = $info['limit_money_type'] == 1 ?
                L('WMKYHQ')/*"无门槛优惠券"*/ :
                L('MDSKY', ['MONEY' => $info['limit_money']])/*"满{$info['limit_money']}可用"*/;
        }

        // 领取时间
        if (isset($info['create_time'])) {
            $info['take_time_string'] = date("Y-m-d H:i:s", $info['create_time']);
        }

        // 有效期
        if (isset($info['limit_time_type'])) {
            switch ((int)$info['limit_time_type']) {
                case 1:
                    $info['limit_time_range'] = L('INDEFINITE_PERIOD');
                    $info['valid_time_string'] = L('INDEFINITE_PERIOD');
                    break;
                case 2:
                    $startTime = date("Y.m.d", $info['create_time']);
                    $endTime = date("Y.m.d", $info['end_time']);
                    $info['limit_time_range'] = "{$startTime}-{$endTime}";
                    if (isset($info['end_time'])) {
                        $info['valid_time_string'] = date("Y-m-d H:i:s", $info['end_time']);
                    }
                    break;
                case 3:
                    $startTime = date("Y.m.d", $info['limit_start_time']);
                    $endTime = date("Y.m.d", $info['limit_end_time']);
                    $info['limit_time_range'] = "{$startTime}-{$endTime}";
                    if (isset($info['end_time'])) {
                        $info['valid_time_string'] = date("Y-m-d H:i:s", $info['end_time']);
                    }
                    break;
                default:
                    if (isset($info['end_time'])) {
                        $info['valid_time_string'] = date("Y-m-d H:i:s", $info['end_time']);
                    }
                    break;
            }
        }

        // 使用状态
        if (isset($info['state'])) {
            switch ((int)$info['state']) {
                case 1:
                    $info['use_status'] = 2;
                    break;
                default:
                    if ($info['limit_time_type'] != 1) {
                        // 已过期 未使用
                        $info['use_status'] = $info['end_time'] <= NOW_TIME ? 3 : 1;
                    } else {
                        // 未使用
                        $info['use_status'] = 1;
                    }
                    break;
            }
        }

        // 使用范围
        if (isset($info['limit_class_type'])) {
            switch ((int)$info['limit_class_type']) {
                case 1:
                    $info['use_range'] = L('COUPON_CAN_USE_ALL_CLASS')/*"全品类（除特殊商品外）"*/;
                    break;
                case 2:
                    if (!empty($info['available_class_name'])) {
                        $className = str_replace('|', '、', $info['available_class_name']);
                        $info['use_range'] = L('SPFLKY', ['CLASS' => $className])/*"可用商品分类({$className})"*/;
                    } else {
                        $limitClass = json_decode($info['limit_class'], 1);
                        $length = count($limitClass);
                        $className = $limitClass[$length - 1]['classStr'];
                        $info['use_range'] = L('SPFLBKY', ['CLASS' => $className])/*"不可用商品分类({$className})"*/;
                    }
                    break;
                case 3:
                    if (!empty($info['available_mall_class_name'])) {
                        $className = str_replace('|', '、', $info['available_mall_class_name']);
                        $info['use_range'] = L('SCFLKY', ['CLASS' => $className])/*"可用商城分类({$className})"*/;
                    } else {
                        $className = str_replace('|', '、', $info['limit_mall_class_name']);
                        $info['use_range'] = L('SCFLBKY', ['CLASS' => $className])/*"不可用商城分类({$data})"*/;
                    }
                    break;
                case 4:
                    $data = str_replace('|', '、', $info['limit_goods_name']);
                    $info['use_range'] = L('JXSPKY', ['GOODS' => $data])/*"仅限可用商品({$data})"*/;
                    break;
                default:
                    break;
            }
        }

        // 使用说明
        if (isset($info['instructions'])) {
            $info['instructions'] = nlRl2br($info['instructions']);
        }

        return $info;
    }

    /**
     * 撤回已经发送的优惠券
     * 只能撤除未使用未过期的
     * 回滚的话 需要回滚发送数量
     * @param string $id
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-14 15:19:01
     * Update: 2017-12-14 15:19:01
     * Version: 1.00
     */
    public function rollBackSendCoupons($id = '')
    {
        if (empty($id)) return getReturn(-1, L('INVALID_PARAM'));
        $idArr = explode(',', $id);
        $dataAll = [];
        $maxVersion = $this->queryMax([], 'version')['data'];
        $model = D('Coupons');
        $couponVersion = $model->queryMax([], 'version')['data'];
        $nowTime = NOW_TIME;
        $this->startTrans();
        foreach ($idArr as $key => $value) {
            // 只能撤回 未使用 未过期的
            $where = [];
            $where['id'] = $value;
            $where['state'] = 0;
            $where['_string'] = "(limit_time_type = 1) OR (limit_time_type != 1 AND end_time > {$nowTime})";
            $where['isdelete'] = 0;
            $options = [];
            $options['where'] = $where;
            $options['field'] = 'id,coupons_id';
            $result = $this->queryRow($options);
            if ($result['code'] !== 200) return $result;
            $info = $result['data'];
            if (!empty($info)) {
                $item = [];
                $item['version'] = ++$maxVersion;
                $item['id'] = $info['id'];
                $item['isdelete'] = 1;
                $dataAll[] = $item;
                $where = [];
                $where['coupons_id'] = $info['coupons_id'];
                $data = [];
                $data['version'] = ++$couponVersion;
                $data['send_num'] = ['exp', 'send_num-1'];
                $result = $model->saveData(['where' => $where], $data);
                if ($result['code'] !== 200) {
                    $this->rollback();
                    return $result;
                }
            }
        }
        if (empty($dataAll)) return getReturn(-1, L('RECORD_INVALID'));
        return $this->saveAllData([], $dataAll);
    }

    /**
     * 获取会员的优惠券列表
     * @param int $memberId
     * @param int $storeId
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-15 09:32:05
     * Update: 2017-12-15 09:32:05
     * Version: 1.00
     */
    public function getMemberCouponList($memberId = 0, $storeId = 0, $channelId = 0, $page = 1, $limit = 20, $condition = [])
    {
        $where = [];
        $where['member_id'] = $memberId;
        if ($channelId > 0) {
            $where['channel_id'] = $channelId;
        } else {
            $where['store_id'] = $storeId;
        }
        $where['isdelete'] = 0;
        $where = array_merge($where, $condition);
        $field = [
            'id,coupons_id,coupons_name,coupons_money,state,create_time,end_time,limit_time_type,limit_start_time,limit_end_time',
            'limit_money_type,limit_money,limit_class_type,limit_class,limit_mall_class_name',
            'available_class_name', 'available_mall_class_name',
            'limit_goods_name,instructions,coupons_type,coupons_discount',
            'store_id',
        ];
        $options = [];
        $options['where'] = $where;
        $options['field'] = implode(',', $field);
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = 'id DESC';
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
     * 获取会员的优惠券总量
     * @param int $memberId
     * @param int $storeId
     * @param int $channelId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-15 09:50:47
     * Update: 2017-12-15 09:50:47
     * Version: 1.00
     */
    public function getMemberCouponTotal($memberId = 0, $storeId = 0, $channelId = 0)
    {
        $where = [];
        $where['member_id'] = $memberId;
        if ($channelId > 0) {
            $where['channel_id'] = $channelId;
        } else {
            $where['store_id'] = $storeId;
        }
        $where['isdelete'] = 0;
        $field = [
            'id,state,limit_time_type,end_time'
        ];
        $options = [];
        $options['where'] = $where;
        $options['field'] = implode(',', $field);
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $num = [];
        $num['status_1_num'] = 0;
        $num['status_2_num'] = 0;
        $num['status_3_num'] = 0;
        foreach ($list as $key => $value) {
            switch ((int)$value['state']) {
                case 1:
                    // 已使用数量+1
                    $num['status_3_num']++;
                    break;
                default:
                    if ($value['limit_time_type'] != 1) {
                        // 已过期 未使用
                        $value['end_time'] <= NOW_TIME ? $num['status_2_num']++ : $num['status_1_num']++;
                    } else {
                        // 未使用
                        $num['status_1_num']++;
                    }
                    break;
            }
        }
        return getReturn(200, '', $num);
    }
}