<?php

namespace Common\Model;

/**
 * 满减满送
 * Class MjActivityModel
 * @package Common\Model
 * User: hjun
 * Date: 2017-12-25 00:03:14
 * Update: 2017-12-25 00:03:14
 * Version: 1.00
 */
class MjActivityModel extends BaseModel
{
    protected $tableName = 'mb_mj_activity';

    //array(验证字段1,验证规则,错误提示,[验证条件,附加规则,验证时间,参数列表])
    protected $_validate = [
        ['mj_name', 'require', '请输入活动名称', 0, 'regex', 3],
        ['start_time', 'require', '请选择开始时间', 0, 'regex', 3],
        ['end_time', 'require', '请选择结束时间', 0, 'regex', 3],
        ['mj_type', '1,2,3', '请选择优惠方式', 0, 'in', 3],
        ['mj_rule', 'require', '请输入优惠规则', 0, 'regex', 3],
        ['limit_goods_type', '1,2', '请选择活动商品', 0, 'in', 3],
    ];

    // array(完成字段1,完成规则,[完成时间,附加规则]),
    protected $_auto = [
        ['create_time', 'time', 1, 'function']
    ];

    /**
     * 获取优惠规则字符换
     * @param $data
     * @return array
     * User: hjun
     * Date: 2018-07-05 09:43:51
     * Update: 2018-07-05 09:43:51
     * Version: 1.00
     */
    public function getDiscountNameAndRule($data)
    {
        $storeInfo = D('Store')->getStoreInfo($data['store_id'])['data'];
        if (is_string($data['mj_rule'])) {
            $data['mj_rule'] = json_decode($data['mj_rule'], 1);
            if ($data['mj_type'] === '3'){
                $data['mj_rule'] = [$data['mj_rule']];
            }
        }
        switch ((int)$data['mj_type']) {
            case 1:
            case 2:
                $discountsName = [];
                if (empty($data['mj_rule'])) return getReturn(-1, '请输入优惠规则');
                $mjRule = [];
                foreach ($data['mj_rule'] as $key => $value) {
                    if ($key >= 5) {
                        break;
                    }
                    $limit = (int)$value['limit'];
                    $discounts = $data['mj_type'] == 1 ? (int)$value['discounts'] : round($value['discounts'] / 10, 2);
                    $item = [];
                    $item['level'] = $key + 1;
                    $item['limit'] = $limit;
                    $item['discounts'] = $discounts;
                    $item['is_top'] = 0;
                    $item['dis_num'] = '';
                    if ($limit <= 0 || $data['mj_type'] == 2 && $discounts >= 1) {
                        $level = $key + 1;
                        return getReturn(-1, "请正确输入第{$level}层级的优惠规则");
                    }
                    $mjRule[] = $item;
                    $itemName = $data['mj_type'] == 1 ?
                        L('MXXJXX', ['limit' => $limit, 'unit' => $storeInfo['currency_unit'], 'discounts' => $discounts]) : //"满{$limit}元减{$discounts}" :
                        L('MXXJZJ', ['limit' => $limit, 'discounts' => $discounts * 10]); //"满{$limit}件总价" . $discounts * 10 . "折";
                    $discountsName[] = $itemName;
                }
                $data['mj_rule'] = [];
                $data['mj_rule'] = $mjRule;
                break;
            case 3:
                $mjRule = [];
                foreach ($data['mj_rule'] as $key => $value) {
                    $mjRule['level'] = 1;
                    $mjRule['limit'] = (int)$data['mj_rule'][$key]['limit'];
                    $mjRule['discounts'] = (int)$data['mj_rule'][$key]['discounts'];
                    $mjRule['is_top'] = empty($data['mj_rule'][$key]['is_top']) ? 0 : 1;
                    $mjRule['dis_num'] = (int)$data['mj_rule'][$key]['dis_num'];
                    break;
                }
                $data['mj_rule'] = [];
                $data['mj_rule'] = $mjRule;
                if ($data['mj_rule']['limit'] <= 0 || $data['mj_rule']['discounts'] <= 0) {
                    return getReturn(-1, "请正确输入优惠规则");
                }
                if ($data['mj_rule']['is_top'] == 1) {
                    if ($data['mj_rule']['dis_num'] <= 0) {
                        return getReturn(-1, "请正确输入优惠规则");
                    }
                } else {
                    $data['mj_rule']['dis_num'] = '';
                }
                $maxMoney = $data['mj_rule']['discounts'] * $data['mj_rule']['dis_num'];
                $msg = $data['mj_rule']['is_top'] == 1 ?
                    L('ZDJXX', ['maxMoney' => $maxMoney, 'unit' => $storeInfo['currency_unit']]) : //"最多减{$maxMoney}元" : //
                    L('UNCAPPED'); // "上不封顶";
                $itemName = L('MMXXJXX', ['limit' => $data['mj_rule']['limit'], 'money' => $data['mj_rule']['discounts'], 'unit' => $storeInfo['currency_unit']]) . $msg;// "每满{$data['mj_rule']['limit']}元减{$data['mj_rule']['discounts']},{$msg}";
                $discountsName[] = $itemName;
                break;
            default:
                $discountsName = [];
                break;
        }
        $name = implode(',', $discountsName);
        return getReturn(CODE_SUCCESS, 'success', ['name' => $name, 'rule' => $data['mj_rule']]);
    }

    /**
     * 处理数据
     * @param array $info
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-25 00:53:32
     * Update: 2017-12-25 00:53:32
     * Version: 1.00
     */
    public function transformInfo($info = [], $condition = [])
    {
        // 优惠类型
        if (isset($info['mj_type'])) {
            switch ((int)$info['mj_type']) {
                case 1:
                    $info['mj_type_name'] = isInAdmin() ? '满额减现金' : L('MJ');
                    break;
                case 2:
                    $info['mj_type_name'] = isInAdmin() ? '满件数打折' : L('FULL_TO_DISCOUNT');
                    break;
                case 3:
                    $info['mj_type_name'] = isInAdmin() ? '每满额减现金' : L('MJ');
                    break;
                default:
                    break;
            }
        }

        // 活动商品范围
        if (isset($info['limit_goods_type'])) {
            switch ((int)$info['limit_goods_type']) {
                case 2:
                    $info['limit_goods'] = explode(',', $info['limit_goods']);
                    $info['limit_goods_name'] = explode('|', $info['limit_goods_name']);
                    break;
                default:
                    $info['limit_goods'] = [];
                    $info['limit_goods_name'] = [];
                    break;
            }
        }

        // 时间状态
        if (isset($info['start_time']) && isset($info['end_time'])) {
            if ($info['start_time'] > NOW_TIME) {
                $info['status_name'] = L('STATE_NOT_BEGINNING');
            } elseif ($info['start_time'] <= NOW_TIME && $info['end_time'] > NOW_TIME) {
                $info['status_name'] = L('STATE_ING');
            } else {
                $info['status_name'] = L('STATE_ENDED');
            }
        }

        // 时间字符串
        if (isset($info['start_time'])) {
            $info['start_time_string'] = date("Y-m-d H:i:s", $info['start_time']);
        }
        if (isset($info['end_time'])) {
            $info['end_time_string'] = date("Y-m-d H:i:s", $info['end_time']);
        }

        // 优惠规则
        if (isset($info['mj_rule'])) {
            $info['mj_rule'] = json_decode($info['mj_rule'], 1);
            switch ((int)$info['mj_type']) {
                case 2:
                    foreach ($info['mj_rule'] as $key => $value) {
                        $info['mj_rule'][$key]['discounts'] = $value['discounts'] * 10;
                    }
                    break;
                case 3:
                    $data = $info['mj_rule'];
                    $info['mj_rule'] = [];
                    $info['mj_rule'][] = $data;
                    break;
                default:
                    break;
            }
        }

        // 跳转编辑链接
        if (isset($info['mj_id'])) {
            if (isInAdmin()) {
                $info['edit_url'] = U('Marketing/mjActivityInfo', ['mj_id' => $info['mj_id']]);
            } else {
                $info['edit_url'] = getStoreDomain($info['store_id']) . "/index.php?c=Coupon&a=couponGoods&type=mj_goods&id={$info['mj_id']}&se={$info['store_id']}";
            }
        }
        return $info;
    }

    /**
     * 获取满减活动列表
     * @param int $storeId
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @param array $otherOptions
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-25 00:53:21
     * Update: 2017-12-25 00:53:21
     * Version: 1.00
     */
    public function getMjList($storeId = 0, $channelId = 0, $page = 1, $limit = 0, $condition = [], $otherOptions = [])
    {
        $where = [];
        $where['is_delete'] = 0;
        // 进行中的
        if (!isInAdmin()) {
            $where['start_time'] = ['elt', NOW_TIME];
            $where['end_time'] = ['gt', NOW_TIME];
        }
        if ($storeId > 0) $where['store_id'] = $storeId;
        if ($channelId > 0) $where['channel_id'] = $channelId;
        $where = array_merge($where, $condition);
        $field = [
            'mj_id,mj_name,start_time,end_time,mj_type,limit_goods_type,limit_goods_name',
            'store_id', 'discounts_name', 'mj_rule', 'limit_goods',
        ];
        $order = 'mj_id DESC';
        $options = [];
        $options['field'] = implode(',', $field);
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = $order;
        $options = array_merge($options, $otherOptions);
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value);
            $temp = $list[$key];
            $list[$key]['discounts_name'] = $this->getDiscountNameAndRule($temp)['data']['name'];
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 获取满减活动信息
     * @param int $mjId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-25 10:59:30
     * Update: 2017-12-25 10:59:30
     * Version: 1.00
     */
    public function getMjInfo($mjId = 0)
    {
        $where = [];
        $where['mj_id'] = $mjId;
        $where['is_delete'] = 0;
        $field = [
            'mj_id,mj_name,start_time,end_time,mj_type,mj_rule,limit_goods_type,limit_goods',
        ];
        $options = [];
        $options['where'] = $where;
        $options['field'] = implode(',', $field);
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, L('RECORD_INVALID'));
        $info = $this->transformInfo($info);
        $result['data'] = $info;
        return $result;
    }

    /**
     * 保存满减活动
     * @param int $storeId
     * @param int $channelId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-25 10:52:34
     * Update: 2017-12-25 10:52:34
     * Version: 1.00
     */
    public function saveMjInfo($storeId = 0, $channelId = 0, $data = [])
    {
        if (empty($storeId)) return getReturn(-1, L('INVALID_PARAM'));

        if ($data['mj_id'] > 0) {
            $result = $this->getMjInfo($data['mj_id']);
            if ($result['code'] !== 200) return $result;
            $mjInfo = $result['data'];
        }

        $data['store_id'] = $storeId;
        $data['channel_id'] = $channelId;
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        $storeType = $storeInfo['store_type'];
        if ($storeType === self::MALL_MAIN_STORE || $storeType === self::CHAIN_MAIN_STORE) {
            $data['platform'] = 1;
        }

        // 检查时间
        $result = checkStartTimeAndEndTime($data['start_time_string'], $data['end_time_string']);
        if ($result['code'] !== 200) return $result;
        $data['start_time'] = $result['data']['start_time'];
        $data['end_time'] = $result['data']['end_time'];

        // 检查优惠规则
        $discountsNameResult = $this->getDiscountNameAndRule($data);
        if (!isSuccess($discountsNameResult)) {
            return $discountsNameResult;
        }
        $data['mj_rule'] = json_encode($discountsNameResult['data']['rule'], JSON_UNESCAPED_UNICODE);
        $data['discounts_name'] = $discountsNameResult['data']['name'];

        // 检查活动商品
        if ($data['limit_goods_type'] == 2) {
            $data['limit_goods'] = implode(',', $data['limit_goods']);
            if (empty($data['limit_goods'])) return getReturn(-1, '请选择参与活动的商品');
            $where = [];
            $where['goods_id'] = ['in', $data['limit_goods']];
            $result = D('GoodsExtra')->queryField(['where' => $where], 'goods_name', true);
            if ($result['code'] !== 200) return $result;
            $goodsName = $result['data'];
            if (empty($goodsName)) return getReturn(-1, '选择的商品无效');
            $data['limit_goods_name'] = implode('|', $goodsName);
        } else {
            $data['limit_goods_name'] = '';
        }

        // 版本号
        $data['version'] = $this->queryMax([], 'version')['data'] + 1;

        if ($data['mj_id'] > 0) {
            $where = [];
            $where['mj_id'] = $data['mj_id'];
            return $this->saveData(['where' => $where], $data);
        } else {
            return $this->addData([], $data);
        }
    }

    /**
     * 删除满减活动
     * @param string $mjId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-25 11:02:43
     * Update: 2017-12-25 11:02:43
     * Version: 1.00
     */
    public function delMjActivity($mjId = '')
    {
        if (empty($mjId)) return getReturn(-1, L('INVALID_PARAM'));
        $mjId = explode(',', $mjId);
        $data = [];
        $maxVersion = $this->queryMax([], 'version')['data'];
        foreach ($mjId as $key => $value) {
            $item = [];
            $item['mj_id'] = $value;
            $item['is_delete'] = 1;
            $item['version'] = ++$maxVersion;
            $data[] = $item;
        }
        return $this->saveAllData([], $data);
    }

    /**
     * 定时任务设置商品的满减标志
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-26 10:25:54
     * Update: 2017-12-26 10:25:54
     * Version: 1.00
     */
    public function setMjFlagOnGoods()
    {
        // 开启事务
        $this->startTrans();

        // 先将有活动标志的上架商品去除标志
        $result = D('Goods')->removeGoodsMjFlag();
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }

        // 查找出已经开始的活动
        $result = $this->getBeginningMj();
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }
        $allList = $result['data'];

        // 同店铺分组
        $storeMjList = $this->mjListGroupByStoreId($allList);

        // 按店铺 对店铺商品进行标志处理
        foreach ($storeMjList as $key => $value) {
            $storeId = $key;
            switch ((int)$value['limit_goods_type']) {
                case 1:
                    $where = [];
                    $where['store_id'] = $storeId;
                    $result = D('Goods')->setGoodsMjFlagByStoreId($where);
                    break;
                case 2:
                    $goodsId = implode(',', $value['limit_goods']);
                    $where = [];
                    $where['goods_id'] = ['in', $goodsId];
                    $result = D('Goods')->setGoodsMjFlagByStoreId($where);
                    break;
                default:
                    break;
            }
            if ($result['code'] !== 200) {
                $this->rollback();
                return $result;
            }
        }

        $this->commit();
        return getReturn(200, '', $storeMjList);
    }

    /**
     * 获取全部正在开始的活动
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-26 15:17:12
     * Update: 2017-12-26 15:17:12
     * Version: 1.00
     */
    public function getBeginningMj()
    {
        $where = [];
        $where['is_delete'] = 0;
        $where['platform'] = 0;
        $where['start_time'] = ['egt', NOW_TIME];
        $where['end_time'] = ['gt', NOW_TIME];
        $options = [];
        $options['where'] = $where;
        $options['field'] = 'mj_id,store_id,limit_goods_type,limit_goods';
        $result = $this->queryTotal($options);
        if ($result['code'] === -1) return $result;
        $total = $result;
        $limit = 1000;
        $count = ceil($total / $limit);
        $page = 1;
        $allList = [];
        do {

            $options['page'] = $page;
            $options['limit'] = $limit;
            $result = $this->queryList($options);
            if ($result['code'] !== 200) {
                return $result;
            }
            $list = $result['data']['list'];
            $allList = array_merge($allList, $list);
            $page++;
        } while ($page <= $count);
        return getReturn(200, '', $allList);
    }

    /**
     * 活动列表按店铺分组
     * @param array $allList
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-26 15:52:53
     * Update: 2017-12-26 15:52:53
     * Version: 1.00
     */
    private function mjListGroupByStoreId($allList = [])
    {
        // 同店铺分组
        // [
        //   'store_id' => [
        //      'limit_goods_type' => 1,
        //      'limit_goods' => ['1,2,3', '2,3,4']
        //   ]
        // ]
        $storeMjList = [];
        foreach ($allList as $key => $value) {
            // 如果该店铺有全商品参加的活动 则记录type=1即可
            if ($storeMjList[$value['store_id']]['limit_goods_type'] != 1) {
                $storeMjList[$value['store_id']]['limit_goods_type'] = $value['limit_goods_type'];
            }
            // 将该店铺所有参加的商品存到数组中
            if (!empty($value['limit_goods']) && $value['limit_goods_type'] == 2) {
                $storeMjList[$value['store_id']]['limit_goods'][] = $value['limit_goods'];
            }
        }
        return $storeMjList;
    }

    /**
     * 获取某个商品可用的满减活动
     * @param int $storeId
     * @param int $channelId
     * @param int $goodsId
     * @param array $condition
     * @param array $otherOptions
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-02 10:10:40
     * Update: 2018-01-02 10:10:40
     * Version: 1.00
     */
    public function getGoodsMjInfo($storeId = 0, $channelId = 0, $goodsId = 0, $condition = [], $otherOptions = [])
    {
        $where = [];
        $where['is_delete'] = 0;
        $where['start_time'] = ['elt', NOW_TIME];
        $where['end_time'] = ['gt', NOW_TIME];
        if ($storeId > 0) $where['store_id'] = $storeId;
        if ($channelId > 0) $where['channel_id'] = $channelId;
        $where['_string'] = "(limit_goods_type = 1) OR (limit_goods_type = 2 AND (limit_goods = {$goodsId} OR limit_goods LIKE '{$goodsId},%' OR limit_goods LIKE '%,{$goodsId},%' OR limit_goods LIKE '%,{$goodsId}'))";
        $where = array_merge($where, $condition);
        $field = [
            'mj_id,store_id,discounts_name,start_time,end_time,mj_type,limit_goods_type,limit_goods,limit_goods_name',
            'mj_rule'
        ];
        $options = [];
        $options['field'] = implode(',', $field);
        $options['where'] = $where;
        $options['order'] = 'mj_id DESC';
        $options = array_merge($options, $otherOptions);
        $result = $this->queryRow($options);
        $info = $result['data'];
        $result['data'] = $this->transformInfo($info);
        return $result;
    }
}