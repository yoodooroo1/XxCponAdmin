<?php

namespace Common\Model;

/**
 * Class CouponsModel
 * 优惠券模型
 * @package Common\Model
 * User: hjun
 * Date: 2017-12-04 14:24:37
 */
class CouponsModel extends BaseModel
{
    protected $tableName = 'mb_coupons';

    // 验证字段1,验证规则,错误提示,[验证条件,附加规则,验证时间,参数列表]
    protected $_validate = [
        ['limit_money_type', '1,2', '请选择使用门槛', 0, 'in', 3],
        ['limit_time_type', '1,2,3', '请选择使用期限', 0, 'in', 3],
        ['limit_type', '1,2', '请选择使用限制', 0, 'in', 3],
        ['limit_class_type', '1,2,3,4', '请选择可抵用商品类型', 0, 'in', 3],
        ['coupons_type', '1,2', '请选择优惠券类型', 0, 'in', 3],
    ];

    // array(完成字段1,完成规则,[完成时间,附加规则]),
    protected $_auto = [
        ['create_time', 'time', 1, 'function']
    ];

    /**
     * 补齐渠道号 升级时调用一次
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-04 14:31:28
     * Update: 2017-12-04 14:31:28
     * Version: 1.00
     */
    public function fillingChannelId()
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
            $options['field'] = 'coupons_id,store_id';
            $list = $this->queryList($options)['data']['list'];
            foreach ($list as $key => $value) {
                $where = [];
                $where['store_id'] = $value['store_id'];
                $options = [];
                $options['where'] = $where;
                $channelId = D('Store')->queryField($options, 'channel_id')['data'];
                $item = [];
                $item['coupons_id'] = $value['coupons_id'];
                $item['channel_id'] = $channelId;
                $item['version'] = ++$maxVersion;
                $data[] = $item;
            }
            $page++;
        } while ($page <= $count);
        return $this->saveAllData([], $data);
    }

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
            $options['field'] = 'coupons_id';
            $result = $this->queryList($options);
            if ($result['code'] !== 200) return $result;
            $list = $result['data']['list'];
            foreach ($list as $key => $value) {
                $item = [];
                $item['coupons_id'] = $value['coupons_id'];
                $item['limit_class'] = '';
                $item['version'] = ++$maxVersion;
                $data[] = $item;
            }
            $i++;
        } while ($i <= $page);
        return $this->saveAllData([], $data);
    }

    /**
     * 补齐分类名称
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-05 09:56:33
     * Update: 2017-12-05 09:56:33
     * Version: 1.00
     */
    public function fillingClassStr()
    {
        $where = [];
        $where['limit_class'] = [['notlike', '%classStr%'], ['neq', '']];
        $options = [];
        $options['where'] = $where;
        $total = $this->queryCount($options);
        $count = ceil($total / 1000);
        $page = 1;
        $data = [];
        $maxVersion = $this->max('version');
        do {
            $options['page'] = $page;
            $options['take'] = 1000;
            $options['field'] = 'coupons_id,limit_class';
            $list = $this->queryList($options)['data']['list'];
            foreach ($list as $key => $value) {
                // 查出分类对应的分类名称
                $classId = json_decode($value['limit_class'], 1);
                $id = [];
                foreach ($classId as $k => $val) {
                    $id[] = $val['class_id'];
                }
                $className = M('goods_class')
                    ->where(['gc_id' => ['in', implode(',', $id)]])
                    ->getField('gc_name', true);
                $name = implode('、', $className);
                $name = empty($name) ? '' : $name;
                $limitClass = [];
                foreach ($id as $k => $val) {
                    $item = [];
                    $item['class_id'] = $val;
                    $limitClass[] = $item;
                }
                $limitClass[] = ['classStr' => $name];
                $item['coupons_id'] = $value['coupons_id'];
                $item['limit_class'] = json_encode($limitClass, JSON_UNESCAPED_UNICODE);
                $item['version'] = ++$maxVersion;
                $data[] = $item;
            }
            $page++;
        } while ($page <= $count);
        return $this->saveAllData([], $data);
    }

    /**
     * 设置限制类型
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-07 09:36:00
     * Update: 2017-12-07 09:36:00
     * Version: 1.00
     */
    public function setLimitClassType()
    {
        $total = $this->queryTotal();
        $count = ceil($total / 1000);
        $page = 1;
        $maxVersion = $this->queryMax([], 'version')['data'];
        $data = [];
        $condition = [];
        do {
            $options = [];
            $options['page'] = $page;
            $options['limit'] = 1000;
            $options['field'] = 'coupons_id,limit_class';
            $list = $this->queryList($options)['data']['list'];
            foreach ($list as $key => $value) {
                $item = [];
                $item['version'] = ++$maxVersion;
                $item['limit_class_type'] = empty($value['limit_class']) ? 1 : 2;
                $data[] = $item;
                $where = [];
                $where['coupons_id'] = $value['coupons_id'];
                $option = [];
                $option['where'] = $where;
                $condition[] = $option;
            }
            $page++;
        } while ($page <= $count);
        return $this->saveAllData($condition, $data);
    }

    /**
     * 设置限制金额的类型
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-07 15:16:10
     * Update: 2017-12-07 15:16:10
     * Version: 1.00
     */
    public function setLimitMoneyType()
    {
        $total = $this->queryTotal();
        $count = ceil($total / 1000);
        $page = 1;
        $maxVersion = $this->queryMax([], 'version')['data'];
        $data = [];
        $condition = [];
        do {
            $options = [];
            $options['page'] = $page;
            $options['limit'] = 1000;
            $options['field'] = 'coupons_id,limit_money';
            $list = $this->queryList($options)['data']['list'];
            foreach ($list as $key => $value) {
                $item = [];
                $item['coupons_id'] = $value['coupons_id'];
                $item['version'] = ++$maxVersion;
                $item['limit_money_type'] = empty($value['limit_money']) ? 1 : 2;
                $data[] = $item;
            }
            $page++;
        } while ($page <= $count);
        return $this->saveAllData($condition, $data);
    }

    /**
     * 设置是否是平台优惠券
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-07 15:36:51
     * Update: 2017-12-07 15:36:51
     * Version: 1.00
     */
    public function setPlatform()
    {
        $total = $this->queryTotal();
        $count = ceil($total / 1000);
        $page = 1;
        $maxVersion = $this->queryMax([], 'version')['data'];
        $data = [];
        $condition = [];
        do {
            $options = [];
            $options['page'] = $page;
            $options['limit'] = 1000;
            $options['field'] = 'coupons_id,store_id';
            $list = $this->queryList($options)['data']['list'];
            foreach ($list as $key => $value) {
                $item = [];
                $item['coupons_id'] = $value['coupons_id'];
                $item['version'] = ++$maxVersion;
                $storeType = UtilModel::getStoreType($value['store_id']);
                $item['platform'] = $storeType == 2 || $storeType == 0 ? 1 : 0;
                $data[] = $item;
            }
            $page++;
        } while ($page <= $count);
        return $this->saveAllData($condition, $data);
    }

    /**
     * 设置使用期限类型
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-07 15:55:22
     * Update: 2017-12-07 15:55:22
     * Version: 1.00
     */
    public function setLimitTimeType()
    {
        $total = $this->queryTotal();
        $count = ceil($total / 1000);
        $page = 1;
        $maxVersion = $this->queryMax([], 'version')['data'];
        $data = [];
        $condition = [];
        do {
            $options = [];
            $options['page'] = $page;
            $options['limit'] = 1000;
            $options['field'] = 'coupons_id,limit_time';
            $list = $this->queryList($options)['data']['list'];
            foreach ($list as $key => $value) {
                $item = [];
                $item['coupons_id'] = $value['coupons_id'];
                $item['version'] = ++$maxVersion;
                $item['limit_time_type'] = $value['limit_time'] <= 0 ? 1 : 2;
                $data[] = $item;
            }
            $page++;
        } while ($page <= $count);
        return $this->saveAllData($condition, $data);
    }

    /**
     * 优惠券列表
     * @param int $storeId
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-05 09:40:33
     * Update: 2017-12-05 09:40:33
     * Version: 1.00
     */
    public function couponsList($storeId = 0, $channelId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        $where['isdelete'] = 0;
        if ($storeId > 0) $where['store_id'] = $storeId;
        if ($channelId > 0) $where['channel_id'] = $channelId;
        $where = array_merge($where, $condition);
        $field = [
            'coupons_id,coupons_name,coupons_money,limit_class_type,limit_type,limit_time',
            'limit_time_type,limit_start_time,limit_end_time,send_num,use_num,create_time',
            'limit_money_type,limit_class,limit_mall_class_name,limit_goods_name,limit_money',
            'coupons_type,coupons_discount',
            'available_class_name', 'available_mall_class_name',
        ];
        $options = [];
        $options['field'] = implode(',', $field);
        $options['where'] = $where;
        $options['skip'] = ($page - 1) * $limit;
        $options['take'] = $limit;
        $options['order'] = 'create_time DESC';
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
     * 转换数据结构
     * @param array $info
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-08 16:56:57
     * Update: 2017-12-08 16:56:57
     * Version: 1.00 
     */
    public function transInfo($info = [])
    {
        // 解析不可用分类
        if (isset($info['limit_class_type'])) {
            $info['check_goods'] = [];
            switch ((int)$info['limit_class_type']) {
                case 2:
                    $limitClass = json_decode($info['limit_class'], 1);
                    $length = count($limitClass);
                    $className = $limitClass[$length - 1]['classStr'];
                    $className = explode('、', $className);
                    $info['limit_class_name'] = empty($className) ? [] : $className;
                    $class = [];
                    for ($i = 0; $i < $length - 1; $i++) {
                        $class[] = $limitClass[$i]['class_id'];
                    }
                    $info['limit_class'] = implode(',', $class);
                    $className = implode('、', $info['limit_class_name']);
                    $info['use_range'] = "商品分类({$className})不可用";
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
                    if (isset($info['limit_mall_class_name'])) {
                        $data = explode('|', $info['limit_mall_class_name']);
                        $info['limit_mall_class_name'] = empty($data) ? [] : $data;
                        $className = implode('、', $data);
                        $info['use_range'] = "商城分类({$className})不可用";
                    }
                    if (!empty($info['available_mall_class_name'])) {
                        $className = str_replace('|', '、', $info['available_mall_class_name']);
                        $info['use_range'] = L('SCFLKY', ['CLASS' => $className])/*"可用商城分类({$className})"*/;
                    } else {
                        $className = str_replace('|', '、', $info['limit_mall_class_name']);
                        $info['use_range'] = L('SCFLBKY', ['CLASS' => $className])/*"不可用商城分类({$data})"*/;
                    }
                    break;
                case 4:
                    if (isset($info['limit_goods_name'])) {
                        $data = str_replace('|', '、', $info['limit_goods_name']);
                        $info['use_range'] = L('JXSPKY', ['GOODS' => $data])/*"仅限可用商品({$data})"*/;
                        $data = explode('|', $info['limit_goods_name']);
                        $info['limit_goods_name'] = empty($data) ? [] : $data;
                    }
                    if(isset($info['limit_goods'])){
                        $checkGoods = empty($info['limit_goods']) ? [] : explode(',', $info['limit_goods']);
                        $info['check_goods'] = $checkGoods;
                    }
                    break;
                default:
                    $info['use_range'] = L('COUPON_CAN_USE_ALL_CLASS')/*"全品类（除特殊商品外）"*/;
                    break;
            }
        }

        // 时间戳
        if (isset($info['create_time'])) $info['create_time_string'] = date('Y-m-d H:i:s', $info['create_time']);

        // 价值
        if (isset($info['limit_money_type'])) {
            switch ((int)$info['coupons_type']) {
                case 1:
                    $info['coupons_value']['money'] = $info['coupons_money'] . '元';
                    break;
                case 2:
                    $info['coupons_value']['money'] = $info['coupons_discount'] * 10 . '折';
                    break;
                default:
                    break;
            }
            $info['coupons_value']['limit'] = $info['limit_money_type'] == 1 ? "无门槛优惠券" : "满{$info['limit_money']}可用";
        }

        // 有效期
        if (isset($info['limit_time_type'])) {
            switch ((int)$info['limit_time_type']) {
                case 3:
                    $info['limit_start_time_string'] = date("Y-m-d H:i:s", $info['limit_start_time']);
                    $info['limit_end_time_string'] = date("Y-m-d H:i:s", $info['limit_end_time']);
                    $info['limit_start_time'] = (int)$info['limit_start_time'] <= 0 ? '' : $info['limit_start_time'];
                    $info['limit_end_time'] = (int)$info['limit_end_time'] <= 0 ? '' : $info['limit_end_time'];
                    break;
                default:
                    $info['limit_time'] = $info['limit_time'] <= 0 ? '' : $info['limit_time'];
                    break;
            }
        }

        // 优惠券类型
        if (isset($info['coupons_type'])) {
            switch ((int)$info['coupons_type']) {
                case 1:
                    $info['coupons_discount'] = '';
                    break;
                case 2:
                    $info['coupons_money'] = '';
                    $info['coupons_discount'] *= 10;
                    break;
                default:
                    break;
            }
        }

        return $info;
    }

    /**
     * 删除优惠券
     * 单条或者批量
     * 删除后还需要删除礼券
     * @param string $couponsId 单个或者多个ID
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-05 16:04:21
     * Update: 2017-12-05 16:04:21
     * Version: 1.00
     */
    public function delCoupons($couponsId = '')
    {
        $this->startTrans();
        if (empty($couponsId)) return getReturn(-1, L('INVALID_PARAM'));
        $maxVersion = $this->max('version');
        $id = explode(',', $couponsId);
        $data = [];
        foreach ($id as $key => $value) {
            $item = [];
            $item['coupons_id'] = $value;
            $item['version'] = ++$maxVersion;
            $item['isdelete'] = 1;
            $data[] = $item;
        }
        $result = $this->saveAllData([], $data);
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }

        // 删除礼券
        $model = D('Present');
        $result = $model->delPresentCoupons($couponsId);
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }
        $this->commit();
        return getReturn(200, L('_OPERATION_SUCCESS_'), $data);
    }

    /**
     * 保存优惠券
     * @param int $storeId
     * @param int $channelId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-07 16:51:06
     * Update: 2017-12-07 16:51:06
     * Version: 1.00
     */
    public function saveCoupons($storeId = 0, $channelId = 0, $data = [])
    {
        $options = [];
        // 新增时字段过滤
        $options['field'] = [
            'store_id', 'coupons_name', 'coupons_money', 'version',
            'store_name', 'store_head', 'platform', 'channel_id', 'instructions',
            'limit_time_type', 'limit_type', 'limit_class_type', 'limit_money_type',
            'coupons_type', 'coupons_discount'
        ];

        // 新增要加上create_time
        if (empty($data['coupons_id'])) {
            $options['field'][] = 'create_time';
        } else {
            $where = [];
            $where['coupons_id'] = $data['coupons_id'];
            $where['isdelete'] = 0;
            $count = $this->queryCount(['where' => $where]);
            if (empty($count)) return getReturn(-1, L('RECORD_INVALID'));
        }

        if (empty($data['coupons_name'])) {
            return getReturn(CODE_ERROR, '请输入优惠券名称');
        }

        // 优惠券类型
        switch ((int)$data['coupons_type']) {
            case 1:
                $data['coupons_money'] = round($data['coupons_money'], 2);
                if (!$this->check($data['coupons_money'], 'integer', 'regex')){
                    return getReturn(406, '优惠券金额只能为整数');
                }
                if ((int)$data['coupons_money'] <= 0) {
                    return getReturn(-1, '请输入优惠券金额');
                }
                $data['coupons_discount'] = 0;
                break;
            case 2:
                if ((int)$data['coupons_discount'] <= 0) {
                    return getReturn(-1, '请输入优惠券折扣');
                }
                if ((int)$data['coupons_discount'] >= 10){
                    return getReturn(-1, '折扣输入有误');
                }
                $data['coupons_money'] = 0;
                $data['coupons_discount'] = round($data['coupons_discount'] / 10, 2);
                break;
            default:
                break;
        }

        // 限制了使用金额
        if ($data['limit_money_type'] == 2) {
            if (empty($data['limit_money'])) return getReturn(-1, '请输入使用门槛的限制金额');
            $options['field'][] = 'limit_money';
        } elseif ($data['limit_money_type'] == 1) {
            $data['limit_money'] = 0;
            $options['field'][] = 'limit_money';
        }

        // 限制了使用期限
        if ($data['limit_time_type'] == 2) {
            if (empty($data['limit_time'])) return getReturn(-1, '请输入使用期限');
            $options['field'][] = 'limit_time';
        } elseif ($data['limit_time_type'] == 3) {
            $result = checkStartTimeAndEndTime($data['limit_start_time_string'], $data['limit_end_time_string']);
            if ($result['code'] !== 200) return $result;
            $data['limit_start_time'] = $result['data']['start_time'];
            $data['limit_end_time'] = $result['data']['end_time'];
            $options['field'][] = 'limit_start_time';
            $options['field'][] = 'limit_end_time';
        } elseif ($data['limit_time_type'] == 1) {
            $data['limit_time'] = 0;
            $options['field'][] = 'limit_time';
        }

        // 使用说明
        $data['instructions'] = empty($data['instructions']) ? '' : $data['instructions'];

        // 指定可用的分类或商品
        if ($data['limit_class_type'] == 2) {
            if (empty($data['limit_class'])) return getReturn(-1, '请选择不可用的商品分类');
            // 转换不可用分类的数据格式 查找不可用分类名称
            $model = D('GoodsClass');
            $where = [];
            $where['gc_id'] = ['in', $data['limit_class']];
            $where['isdelete'] = 0;
            $option = [];
            $option['where'] = $where;
            $result = $model->queryField($option, 'gc_name', true);
            if ($result['code'] !== 200) return $result;
            $className = implode('、', empty($result['data']) ? [] : $result['data']);
            if (empty($className)) return getReturn(-1, '选择的商品分类无效');
            $id = explode(',', $data['limit_class']);
            $limitClass = [];
            foreach ($id as $key => $value) {
                $item = [];
                $item['class_id'] = $value;
                $limitClass[] = $item;
            }
            $limitClass[] = ['classStr' => $className];
            // 转换可用分类 名称
            $where = [];
            $where['gc_id'] = ['in', $data['available_class']];
            $where['isdelete'] = 0;
            $option = [];
            $option['where'] = $where;
            $result = $model->queryField($option, 'gc_name', true);
            if ($result['code'] !== 200) return $result;
            $className = implode('|', empty($result['data']) ? [] : $result['data']);
            $data['limit_class'] = json_encode($limitClass, JSON_UNESCAPED_UNICODE);
            $data['available_class_name'] = $className;
            $options['field'][] = 'limit_class';
            $options['field'][] = 'available_class';
            $options['field'][] = 'available_class_name';
        } elseif ($data['limit_class_type'] == 3) {
            if (empty($data['limit_mall_class'])) return getReturn(-1, '请选择不可用的商城分类');
            // 查找不可用商城分类名称
            $model = D('MallGoodClass');
            $where = [];
            $where['id'] = ['in', $data['limit_mall_class']];
            $where['isdelete'] = 0;
            $option = [];
            $option['where'] = $where;
            $result = $model->queryField($option, 'classname', true);
            if ($result['code'] !== 200) return $result;
            $className = implode('|', empty($result['data']) ? [] : $result['data']);
            if (empty($className)) return getReturn(-1, '选择的商城分类无效');
            $data['limit_mall_class_name'] = $className;
            // 转换可用商城分类
            $where = [];
            $where['id'] = ['in', $data['limit_mall_class']];
            $where['isdelete'] = 0;
            $option = [];
            $option['where'] = $where;
            $result = $model->queryField($option, 'classname', true);
            if ($result['code'] !== 200) return $result;
            $className = implode('|', empty($result['data']) ? [] : $result['data']);
            $data['available_mall_class_name'] = $className;
            $options['field'][] = 'limit_mall_class';
            $options['field'][] = 'limit_mall_class_name';
            $options['field'][] = 'available_mall_class';
            $options['field'][] = 'available_mall_class_name';
        } elseif ($data['limit_class_type'] == 4) {
            if (empty($data['limit_goods'])) return getReturn(-1, '请选择可用的商品');
            $model = D('Goods');
            $where = [];
            $where['goods_id'] = ['in', $data['limit_goods']];
            $where['isdelete'] = 0;
            // 如果限制了原价可用 则要过滤掉选择的商品
            if ($data['limit_type'] == 1) {
                $where['is_qinggou'] = 0;
                $where['is_promote'] = 0;
            }
            $option = [];
            $option['where'] = $where;
            $result = $model->queryField($option, 'goods_name', true);
            if ($result['code'] !== 200) return $result;
            $goodsName = implode('|', empty($result['data']) ? [] : $result['data']);
            if (empty($goodsName)) return getReturn(-1, '选择的商品无效');
            $data['limit_goods_name'] = $goodsName;
            $options['field'][] = 'limit_goods';
            $options['field'][] = 'limit_goods_name';
        }

        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        // 是否平台优惠券
        $data['store_id'] = $storeId;
        $data['channel_id'] = $channelId;
        $data['platform'] = $storeInfo['store_type'] == 0 || $storeInfo['store_type'] == 2 ? 1 : 0;

        // 商家名称和logo
        $data['store_name'] = $storeInfo['store_name'];
        $data['store_head'] = $storeInfo['store_label'];

        // 版本号
        $data['version'] = $this->queryMax([], 'version')['data'] + 1;

        if (!empty($data['coupons_id'])) {
            $options['where'] = ['coupons_id' => $data['coupons_id']];
            return $this->saveData($options, $data);
        } else {
            return $this->addData($options, $data);
        }
    }

    /**
     * 获取优惠券信息
     * @param int $couponsId
     * @param int $storeId
     * @param int $channelId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-11 10:03:13
     * Update: 2017-12-11 10:03:13
     * Version: 1.00
     */
    public function getCouponsInfo($couponsId = 0, $storeId = 0, $channelId = 0)
    {
        if (empty($couponsId)) return getReturn(-1, L('INVALID_PARAM'));
        $where = [];
        $where['coupons_id'] = $couponsId;
        $where['isdelete'] = 0;
        if ($storeId > 0) $where['store_id'] = $storeId;
        if ($channelId > 0) $where['channel_id'] = $channelId;
        $options = [];
        $options['where'] = $where;
        $options['field'] = [
            'coupons_id,coupons_name,coupons_money,limit_money_type,limit_money,limit_time_type,limit_time',
            'limit_start_time,limit_end_time,limit_type,instructions,limit_class_type,limit_class',
            'limit_mall_class,limit_goods,coupons_type,coupons_discount',
            'available_class_name', 'available_mall_class_name',
        ];
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, L('RECORD_INVALID'));
        $result['data'] = $this->transInfo($info);
        return $result;
    }

    /**
     * 获取领券中心选择的优惠券列表
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-13 11:35:30
     * Update: 2017-12-13 11:35:30
     * Version: 1.00
     */
    public function getCenterChooseCouponsList($storeId = 0)
    {
        $where = [];
        $where['isdelete'] = 0;
        $where['store_id'] = $storeId;
        $options = [];
        $options['where'] = $where;
        $options['field'] = 'coupons_id,coupons_name,coupons_money,limit_money_type,limit_money,coupons_type,coupons_discount';
        $options['order'] = 'create_time DESC';
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            switch ((int)$value['limit_money_type']) {
                case 2:
                    switch ((int)$value['coupons_type']) {
                        case 1:
                            $list[$key]['coupons_name_text'] = "满{$value['limit_money']}元立减{$value['coupons_money']}元";
                            break;
                        case 2:
                            $discount = $value['coupons_discount'] * 10;
                            $list[$key]['coupons_name_text'] = "满{$value['limit_money']}元打{$discount}折";
                            break;
                        default:
                            break;
                    }
                    break;
                default:
                    switch ((int)$value['coupons_type']) {
                        case 1:
                            $list[$key]['coupons_name_text'] = "无门槛立减{$value['coupons_money']}元";
                            break;
                        case 2:
                            $discount = $value['coupons_discount'] * 10;
                            $list[$key]['coupons_name_text'] = "无门槛打{$discount}折";
                            break;
                        default:
                            break;
                    }
                    break;
            }
        }
        return getReturn(200, '', $list);
    }
}