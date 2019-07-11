<?php

namespace Common\Model;


use Common\Common\CommonOrderApi;
use Common\Logic\CartLogic;
use Common\Logic\OrderLogic;

class GroupBuyModel extends BaseModel
{
    protected $tableName = 'mb_group_buying';

    protected $optimLock = 'lock_version';

    protected $_auto = [
        ['create_time', 'time', 1, 'function']
    ];

    /**
     * @param int $groupId
     * @param string $queryField 查询字段 a.xxx
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取团购信息
     * Date: 2017-11-16 11:09:08
     * Update: 2017-11-16 11:09:09
     * Version: 1.0
     */
    public function getGroupInfo($groupId = 0, $queryField = '', $condition = [])
    {
        $where = [];
        $where['a.group_id'] = $groupId;
        $where['a.is_delete'] = 0;
        $where['b.goods_state'] = 1;
        $where['b.goods_delete'] = 0;
        $where = array_merge($where, $condition);
        $field = [
            'a.group_id,a.start_time,a.end_time,a.group_num,a.group_return,a.return_rule',
            'a.group_desc,a.spec_group_price,a.group_mode,a.close_status',
            'a.is_limit', 'a.limit_buy_num',
            'b.goods_id,b.spec_type,b.is_promote,b.min_promote_price,b.max_promote_price,b.spec_attr,b.goods_spec',
            'b.min_goods_price,b.max_goods_price,b.all_stock goods_storage,b.goods_img,b.goods_fig,b.goods_name',

        ];
        $field = implode(',', $field);
        $options = [];
        $options['alias'] = 'a';
        $options['join'] = [
            '__GOODS_EXTRA__ b ON a.goods_id = b.goods_id'
        ];
        $options['where'] = $where;
        $options['field'] = empty($queryField) ? $field : $queryField;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, "团购已被删除");
        if (!empty($info)) $info = $this->initGroupInfo($info);
        return getReturn(200, '', $info);
    }

    /**
     * @param int $storeId
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取团购列表
     * Date: 2017-11-02 15:01:59
     * Update: 2017-11-02 15:02:00
     * Version: 1.0
     */
    public function getGroupList($storeId = 0, $channelId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        $where['a.is_delete'] = 0;
        if ($storeId > 0) $where['a.store_id'] = $storeId;
        if ($channelId > 0) $where['a.channel_id'] = $channelId;
        $where['b.goods_state'] = 1;
        $where['b.goods_delete'] = 0;
        $where = array_merge($where, $condition);
        $options = [];
        $field = [
            'a.group_id,a.start_time,a.end_time,a.recommend_index,a.group_num,a.close_status',
            'a.join_num,a.base_num,a.group_status,a.complete_time,a.min_group_price,a.max_group_price',
            'b.goods_id,b.is_promote,b.min_promote_price,b.max_promote_price',
            'b.min_goods_price,b.max_goods_price,b.all_stock goods_storage,b.goods_img,b.goods_fig,b.goods_name',

        ];
        $options['join'] = [
            '__GOODS_EXTRA__ b ON a.goods_id = b.goods_id'
        ];
        $options['where'] = $where;
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $result = $this->queryList($options);
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key] = $this->initGroupInfo($value);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * @param array $info
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 初始化列表的一些字段
     * Date: 2017-11-16 10:09:39
     * Update: 2017-11-16 10:09:39
     * Version: 1.0
     */
    private function initGroupInfo($info = [])
    {
        // 商品主图
        if (isset($info['goods_img'])) {
            $image = json_decode($info['goods_img'], 1);
            $info['goods_img'] = empty($image[0]['url']) ? '' : $image[0]['url'];
            if (empty($info['goods_img'])) {
                $image = json_decode($info['goods_fig'], 1);
                $info['goods_img'] = empty($image[0]['url']) ? '' : $image[0]['url'];
            }
        }
        // 商品原价 (是个范围)
        if (isset($info['is_promote']) || isset($info['is_qinggou'])) {
            if ($info['is_promote'] == 1 || $info['is_qinggou'] == 1) {
                $info['goods_price'] = $info['min_promote_price'] == $info['max_promote_price'] ?
                    $info['min_promote_price'] : "{$info['min_promote_price']}~{$info['max_promote_price']}";
            } else {
                $info['goods_price'] = $info['min_goods_price'] == $info['max_goods_price'] ?
                    $info['min_goods_price'] : "{$info['min_goods_price']}~{$info['max_goods_price']}";
            }
        }
        // 库存
        if (isset($info['goods_storage'])) {
            $info['goods_storage'] = $info['goods_storage'] == -1 ? "充足" : $info['goods_storage'];
        }
        // 团购价格
        if (isset($info['min_group_price']) && isset($info['max_goods_price'])) {
            $info['group_price'] = $info['min_group_price'] == $info['max_group_price'] ?
                $info['min_group_price'] : "{$info['min_group_price']}~{$info['max_group_price']}";
        }

        // 开始时间 结束时间 成团时间
        if (isset($info['start_time'])) $info['start_time_string'] = date('Y-m-d H:i:s', $info['start_time']);
        if (isset($info['end_time'])) $info['end_time_string'] = date('Y-m-d H:i:s', $info['end_time']);
        if (isset($info['complete_time'])) $info['complete_time_string'] = date('Y-m-d H:i:s', $info['complete_time']);
        // 时间状态
        if (isset($info['close_status']) || (isset($info['start_time']) || isset($info['end_time']))) {
            if ($info['close_status'] == 2) {
                $info['time_status'] = 3;
                $info['time_status_string'] = "已结束";
            } else {
                if ($info['start_time'] > NOW_TIME) {
                    $info['time_status'] = 1;
                    $info['time_status_string'] = "未开始";
                } elseif ($info['start_time'] <= NOW_TIME && $info['end_time'] > NOW_TIME) {
                    $info['time_status'] = 2;
                    $info['time_status_string'] = "进行中";
                } elseif ($info['end_time'] <= NOW_TIME) {
                    $info['time_status'] = 3;
                    $info['time_status_string'] = "已结束";
                } else {
                    logWrite("团购数据异常:" . json_encode($info, JSON_UNESCAPED_UNICODE));
                }
            }
        }
        // 团购状态
        if (isset($info['group_status'])) {
            switch ((int)$info['group_status']) {
                case 2:
                    $info['group_status_string'] = '已成团';
                    break;
                default:
                    $info['group_status_string'] = '未成团';
                    break;
            }
        }
        // 商品规格 团购价格
        if (isset($info['goods_spec'])) $info['goods_spec'] = json_decode($info['goods_spec'], 1);
        if (isset($info['spec_group_price'])) {
            $info['spec_group_price'] = json_decode($info['spec_group_price'], 1);
            $info['spec_attr'] = $info['spec_group_price'];
        }
        // 合并商品规格属性
        /*if (!empty($info['spec_attr']) && !empty($info['spec_group_price'])) {
            foreach ($info['spec_group_price'] as $key => $value) {
                if (array_key_exists($key, $info['spec_attr'])){
                    $info['spec_attr'][$key]['spec_group_price'] = $value['spec_group_price'];
                }
            }
        }*/

        // 返利规则
        if (isset($info['return_rule'])) {
            $info['return_rule'] = json_decode($info['return_rule'], 1);
            $info['return_rule'] = empty($info['return_rule']) ? [] : $info['return_rule'];
        }
        return $info;
    }

    /**
     * @param int $groupId
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 保存团购信息
     * Date: 2017-11-02 15:02:24
     * Update: 2017-11-02 15:02:25
     * Version: 1.0
     */
    public function saveGroup($groupId = 0, $data = [])
    {
        $where = [];
        $where['group_id'] = $groupId;
        $where['is_delete'] = 0;
        // 要查出乐观锁字段才有效
        $field = 'group_id,recommend_index,base_num,join_num,group_num,end_time,lock_version,close_status,group_status,group_mode';
        $options = [];
        $options['where'] = $where;
        $options['field'] = $field;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, '该团购已被删除');
        // 推荐首页
        if (isset($data['recommend_index']) && $data['recommend_index'] == $info['recommend_index']) {
            $msg = $data['recommend_index'] == 1 ? "推荐" : "不推荐";
            return getReturn(-1, "该团购已经设置了{$msg}");
        }
        // 增加基数
        if (isset($data['base_num'])) $data['base_num'] = $info['base_num'] + $data['base_num'];
        // 关闭团购
        if (isset($data['close_status']) && ($info['end_time'] < NOW_TIME || $info['close_status'] == 2)) {
            return getReturn(-1, '团购已经下线');
        }
        $options = [];
        $options['where'] = $where;
        $result = $this->saveData($options, $data);
        if ($result['code'] !== 200) return $result;
        $data = $result['data'];
        foreach ($info as $key => $value) {
            $data[$key] = isset($data[$key]) ? $data[$key] : $value;
        }
        return getReturn(200, '', $data);
    }

    /**
     * @param int $groupId
     * @param int $status
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 改变团购是否推荐到首页
     * Date: 2017-11-02 14:55:12
     * Update: 2017-11-02 14:55:13
     * Version: 1.0
     */
    public function changeGroupRecommend($groupId = 0, $status = 1)
    {
        $status = (int)$status;
        if (!in_array($status, [1, 2])) return getReturn(-1, '参数无效');
        $data = [];
        $data['recommend_index'] = $status;
        $result = $this->saveGroup($groupId, $data);
        if ($result['code'] !== 200) return $result;
        $status = $result['data']['recommend_index'];
        return getReturn(200, '', $status);
    }

    /**
     * @param int $groupId
     * @param $addNum
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 添加基数
     * Date: 2017-11-03 10:03:39
     * Update: 2017-11-03 10:03:40
     * Version: 1.0
     */
    public function setGroupBaseNum($groupId = 0, $addNum)
    {
        $addNum = (int)$addNum;
        if ((int)$addNum <= 0) return getReturn(-1, '基数无效');
        $this->startTrans();
        $data = [];
        $data['base_num'] = $addNum;
        $result = $this->saveGroup($groupId, $data);
        if ($result['code'] !== 200) return $result;
        $num = $result['data']['base_num'];
        $groupNewInfo = $result['data'];
        if ($groupNewInfo['join_num'] + $groupNewInfo['base_num'] >= $groupNewInfo['group_num']) {
            $where = [];
            $where['group_id'] = $groupNewInfo['group_id'];
            $groupData = [];
            if ($groupNewInfo['group_status'] == 1) {
                $groupData['group_status'] = 2;
                $groupData['complete_time'] = NOW_TIME;
                if ($groupNewInfo['group_mode'] == 1) {
                    $groupData['close_status'] = 2;
                    $groupData['close_time'] = NOW_TIME;
                    $groupData['close_remark'] = '成团后下线';
                }
                $result = $this->where($where)->save($groupData);
                if (false === $result) {
                    $this->rollback();
                    logWrite("修改团购状态失败" . $this->getError() . '-' . $this->getDbError());
                    return getReturn();
                }
            }
            // 更新团购订单的团购状态
            $where = [];
            $where['group_id'] = $groupNewInfo['group_id'];
            $result = M('mb_group_buying_order')->where($where)->setField('group_complete_status', 1);
            if (false === $result) {
                $this->rollback();
                logWrite("修改团购订单的成团失败:" . $this->getError() . '-' . $this->getDbError());
                return getReturn();
            }
        }
        $this->commit();
        return getReturn(200, '', $num);
    }

    /**
     * @param int $groupId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 关闭团购
     * Date: 2017-11-16 10:23:42
     * Update: 2017-11-16 10:23:42
     * Version: 1.0
     */
    public function closeGroup($groupId = 0)
    {
        if ($groupId <= 0) return getReturn(-1, '数据异常');
        $data = [];
        $data['close_time'] = NOW_TIME;
        $data['close_status'] = 2;
        $result = $this->saveGroup($groupId, $data);
        if ($result['code'] !== 200) return $result;
        $data = [];
        $data['close_status'] = 2;
        $data = $this->initGroupInfo($data);
        return getReturn(200, '', $data);
    }

    /**
     * @param int $groupId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 删除团购
     * Date: 2017-11-16 11:17:53
     * Update: 2017-11-16 11:17:54
     * Version: 1.0
     */
    public function delGroup($groupId = 0)
    {
        if ($groupId <= 0) return getReturn(-1, '数据异常');
        $data = [];
        $data['is_delete'] = 1;
        $result = $this->saveGroup($groupId, $data);
        if ($result['code'] !== 200) return $result;
        return getReturn(200, '', 1);
    }

    /**
     * @param int $storeId
     * @param int $channelId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 保存团购信息
     * Date: 2017-11-16 18:08:27
     * Update: 2017-11-16 18:08:28
     * Version: 1.0
     */
    public function saveGroupInfo($storeId = 0, $channelId = 0, $data = [])
    {
        if ($storeId < 0) return getReturn(-1, "数据异常");
        $data['store_id'] = $storeId;
        $data['channel_id'] = $channelId;
        $result = $this->checkSpecGroupData($data);
        if ($result['code'] !== 200) return $result;
        $data = $result['data'];
        if ($data['group_id'] > 0) {
            $result = $this->saveGroup($data['group_id'], $data);
        } else {
            $result = $this->addData([], $data);
            $groupId = $result['data'];
            $data['group_id'] = $groupId;
            $result['data'] = $data;
        }
        return $result;
    }

    /**
     * @param array $data
     * {
     * "goods_id": "string,商品ID"
     * "group_id": "string,团购ID",
     * "goods_img": "string,商品图片",
     * "goods_name": "string,商品名称",
     * "goods_storage": "string,商品库存",
     * "group_num": "string,成团数量",
     * "group_mode": "string,团购模式 1-成团后下线 2-成团购可继续购买",
     * "group_return": "string,是否返现 1-返现 2-不返现",
     * "return_rule": [
     * {
     * "num": "string,件数",
     * "return_money": "double,每件返现的金额"
     * }
     * ],
     * "start_time_string": "string,团购开始时间",
     * "end_time_string": "string,团购结束时间",
     * "group_desc": "string,团购描述",
     * "spec_attr": {
     * "spec_id_xx": {
     * "spec_id": "string,规格ID, -1表示该商品没有规格",
     * "spec_name": "string,规格名称",
     * "spec_price": "string,原价",
     * "spec_stock": "string,库存",
     * "spec_group_price": "string,团购价"
     * }
     * }
     * }
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc:
     * Date: 2017-11-16 19:52:01
     * Update: 2017-11-16 19:52:02
     * Version: 1.0
     */
    protected function checkSpecGroupData($data = [])
    {
        // 如果是编辑 只能修改描述
        if ($data['group_id'] > 0) {
            $result = $this->field('group_desc')->create($data, 2);
            if (false === $result) return getReturn(-1, $this->getError());
            return getReturn(200, '', $data);
        }
        // 检查商品
        $where = [];
        $where['goods_id'] = $data['goods_id'];
        $where['store_id'] = $data['store_id'];
        $count = M('goods')->field('goods_id')->where($where)->find();
        if (empty($count)) return getReturn(-1, '请选择商品');
        // 检查成团数量
        if ($data['group_num'] <= 0) return getReturn(-1, '请填写成团数量');
        // 检查限购数量
        if ($data['is_limit'] == 1) {
            if ($data['limit_buy_num'] <= 0) {
                return getReturn(CODE_ERROR, '请填写限购数量');
            }
        }
        // 检查起始时间
        if (empty($data['start_time_string']) || empty($data['end_time_string'])) return getReturn(-1, '请选择团购有效期');
        $data['start_time'] = strtotime($data['start_time_string']);
        $data['end_time'] = strtotime($data['end_time_string']);
        if ($data['end_time'] <= NOW_TIME) return getReturn(-1, '团购结束时间不能小于当前时间');
        if ($data['start_time'] >= $data['end_time']) return getReturn(-1, '团购开始时间必须小于结束时间');
        // 检查团购价
        $minGroupPrice = 0;
        foreach ($data['spec_attr'] as $key => $value) {
            $minGroupPrice = $value['spec_group_price'];
            break;
        }
        if ($minGroupPrice <= 0) return getReturn(-1, "请填写团购价");
        $maxGroupPrice = 0;
        foreach ($data['spec_attr'] as $key => $value) {
            if ($value['spec_group_price'] <= 0) return getReturn(-1, '请填写团购价');
            if ($value['spec_group_price'] > $value['spec_price']) {
                $msg = "团购价不能大于原价";
                return getReturn(-1, $msg);
            }
            // 计算最小 大团购价
            if ($value['spec_group_price'] < $minGroupPrice) $minGroupPrice = $value['spec_group_price'];
            if ($value['spec_group_price'] > $maxGroupPrice) $maxGroupPrice = $value['spec_group_price'];
        }
        // 检查返现 返现的金额不能大于最小团购价
        if ($data['group_return'] == 1) {
            $maxReturnMoney = 0;
            $levelName = '';
            $level = ['一', '二', '三', '四', '五'];
            if (count($data['return_rule']) > 5) return getReturn(-1, '最多只能设置五档');
            foreach ($data['return_rule'] as $key => $value) {
                if (empty($value['num'])) return getReturn(-1, "请输入第{$level[$key]}档的件数");
                if (empty($value['return_money'])) return getReturn(-1, "请输入第{$level[$key]}档的每件返现金额");
                if ($value['return_money'] > $maxReturnMoney) {
                    $levelName = $level[$key];
                    $maxReturnMoney = $value['return_money'];
                }
            }
            if ($maxReturnMoney > $minGroupPrice) {
                return getReturn(-1, "第{$levelName}档的返利金额要小于团购价格");
            }
            $data['return_rule'] = json_encode($data['return_rule'], JSON_UNESCAPED_UNICODE);
        } else {
            $data['return_rule'] = '';
        }
        $data['spec_group_price'] = json_encode($data['spec_attr'], JSON_UNESCAPED_UNICODE);
        $data['min_group_price'] = $minGroupPrice;
        $data['max_group_price'] = $maxGroupPrice;
        // 创建数据对象
        $field = [
            'goods_id,group_num,min_group_price,max_group_price,spec_group_price,base_num',
            'group_mode,group_return,return_rule,start_time,end_time,group_desc,store_id,channel_id',
            'is_limit', 'limit_buy_num'
        ];
        $field = implode(',', $field);
        $data = $this->field($field)->create($data, 1);
        if (false === $data) return getReturn(-1, $this->getError());
        return getReturn(200, '', $data);

    }

    /**
     * 判断商品是否在团购中 商品列表点编辑时做相应的提示
     * @param int $goodsId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-05 15:50:20
     * Update: 2018-02-05 15:50:20
     * Version: 1.00
     */
    public function checkGoodsInGroup($goodsId = 0)
    {
        $field = [
            'goods_id', 'group_id', 'min_group_price', 'max_group_price',
            'spec_group_price',
        ];
        $where = [];
        $where['goods_id'] = $goodsId;
        $where['end_time'] = ['gt', NOW_TIME];
        $where['close_status'] = 1;
        $where['is_delete'] = 0;
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        $info = $this->selectRow($options);
        if (!empty($info)) {
            return getReturn(200, L('SPZTGZ')/*该商品正在团购中,请慎重编辑规格信息*/, $info);
        }
        return getReturn(200, '', $info);
    }

    /**
     * 获取运费
     * @param int $storeId
     * @param int $memberId
     * @param array $cartData
     * @param array $addressInfo
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-11 17:56:34
     * Update: 2018-11-11 17:56:34
     * Version: 1.00
     */
    public function getFreight($storeId = 0, $memberId = 0, $cartData = [], $addressInfo = [])
    {
        $isAbroad = 0;
        $goodsIdArray = ["{$cartData['goods_id']}|{$cartData['spec_id']}"];
        $store_id_array = [$storeId];
        $cartLogic = new CartLogic($storeId, $memberId);
        $goods_datas = [];
        foreach ($goodsIdArray as $value) {
            $value_arr = explode("|", $value);
            $resultData = $cartLogic->isSpecIdEmpty($value_arr[1]);
            if ($resultData == true) {
                $value = $value_arr[0] . "|" . "0";
            }
            $goods_bean = D('Goods')->getGoodsBeanWithGsId($value, $storeId, $memberId);
            if (empty($goods_bean))
                continue;
            unset($goods_bean['spec']);
            unset($goods_bean['goods_desc']);
            unset($goods_bean['spec_option']);
            unset($goods_bean['goods_content']);
            unset($goods_bean['images']);
            unset($goods_bean['img_text']);
            $goods_bean['balance_limit'] = -1;
            if (!empty($goods_bean)) {
                if ($goods_bean['new_price'] != -1) {
                    $goods_bean['goods_price'] = $goods_bean['new_price'];
                }
                $goods_bean['price'] = $goods_bean['goods_price'];
                if (empty($goods_bean['gou_num'])) {
                    $goods_bean['gou_num'] = $goods_bean['buy_num'];
                }
                if (!in_array($goods_bean['store_id'], $store_id_array)) {
                    $store_id_array[] = $goods_bean['store_id'];
                }
            }
            if (!empty($goods_bean['is_abroad'])) {
                $isAbroad = 1;
            }
            $goods_bean['goods_price'] = $cartData['goods_price'];
            $goods_bean['price'] = $cartData['goods_price'];
            $goods_bean['gou_num'] = $cartData['buy_num'];
            $goods_bean['buy_num'] = $cartData['buy_num'];
            $goods_datas[] = $goods_bean;
        }
        $confirmOrderData['isAbroad'] = $isAbroad;

        /**第二步:商品分类**/
        $orderLogic = new OrderLogic($storeId);
        $storeInfoArray = $orderLogic->getStoreGoodsBean($store_id_array, $goods_datas);
        $commonOrderApi = new CommonOrderApi();
        $commonOrderApi->getOrderFreight($storeInfoArray, getLat(), getLng(), $addressInfo['address_id']);
        return $storeInfoArray;
    }

    /**
     * 创建团购订单
     * @param int $storeId
     * @param int $memberId
     * @param int $channelId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-08 09:47:37
     * Update: 2018-02-08 09:47:37
     * Version: 1.00
     */
    public function createOrder($storeId = 0, $memberId = 0, $channelId = 0, $data = [])
    {
        // 参数
        $orderSn = $data['order_sn'];
        $addressId = $data['address_id'];
        $payType = (int)$data['pay_type'];
        $remark = empty($data['remark']) ? '' : $data['remark'];

        // 判断支付方式
        if (!in_array($payType, [0, 1, 2])) return getReturn(-1, '请选择支付方式');

        // 判断收货地址
        if (empty($addressId)) return getReturn(-1, '请选择收货地址');
        $addressInfo = M('mb_address')->find($addressId);
        $addressInfo['address'] = $addressInfo['province'] . $addressInfo['city'] . $addressInfo['area'] . $addressInfo['address'];
        if (empty($addressInfo['member_tel']) || empty($addressInfo['address']) || empty($addressInfo['member_name'])) {
            return getReturn(-1, '请填写收货信息');
        }

        // 判断配送范围
        $result = validateDeliveryArea($storeId, $addressInfo);
        if (!isSuccess($result)) {
            return $result;
        }

        // 判断购物车
        $where = [];
        $where['member_id'] = $memberId;
        $where['store_id'] = $storeId;
        $groupCart = M('mb_group_buying_cart')->where($where)->find();
        if (empty($groupCart['cart_data'])) return getReturn(CODE_REDIRECT, '当前订单已失效,请重新下单');
        $cartData = json_decode($groupCart['cart_data'], 1);
        logWrite("购物车数据:" . json_encode($cartData, JSON_UNESCAPED_UNICODE));

        // 判断团购、核对商品价格、规格
        $result = $this->getGroupInfoInStore($storeId, $cartData['group_id']);
        if ($result['code'] !== 200) return $result;
        $groupInfo = $result['data'];
        logWrite("团购信息:" . json_encode($groupInfo, JSON_UNESCAPED_UNICODE));
        $selectSpec = $cartData['spec_id'];
        $buyNum = $cartData['buy_num'];
        // 核对限购
        $result = D('GroupBuyingLimit')->validateBuyNum($groupInfo, $memberId, $buyNum);
        if (!isSuccess($result)) {
            return $result;
        }
        $buyPrice = $cartData['goods_price'];
        $totalPrice = $cartData['total_price'];
        $currentGoodsSpec = $groupInfo['spec_attr']["spec_id_{$selectSpec}"];
        if (empty($currentGoodsSpec)) return getReturn(CODE_REDIRECT, '商品规格已变动,请重新下单');
        if ($buyNum > $currentGoodsSpec['spec_stock']) return getReturn(CODE_REDIRECT, '商品库存不足');
        if (abs($currentGoodsSpec['spec_group_price'] - $buyPrice) > 0.01) {
            return getReturn(-1, "商品价格已变动-{$currentGoodsSpec['spec_group_price']}");
        }

        // 运费
        $freight = $this->getFreight($storeId, $memberId, $cartData, $addressInfo)[0]['freight'];
        logWrite("团购运费数据:" . jsonEncode($freight));
        if ($freight['canBuy'] == 0) {
            return getReturn(CODE_ERROR, '超出配送范围或未满足起送价');
        }
        $totalPrice = $totalPrice + $freight['freight'];

        // 开始下单
        $this->startTrans();
        // 重复提交判断
        $result = M('mb_group_buying_order_sn')->add(['order_sn' => $orderSn]);
        if (false === $result) {
            $this->rollback();
            return getReturn(-1, '请勿重复提交订单');
        }

        // 下单
        $orderData = [];
        $orderData['order_sn'] = $orderSn;
        $orderData['postage'] = $freight['freight'];
        $orderData['store_id'] = $storeId;
        $orderData['channel_id'] = $channelId;
        $orderData['member_id'] = $memberId;
        $orderData['group_id'] = $cartData['group_id'];
        $orderData['goods_id'] = $cartData['goods_id'];
        $orderData['goods_name'] = $groupInfo['goods_name'];
        $goodsImg = empty($currentGoodsSpec['spec_goods_img']) ? $groupInfo['goods_image'] : $currentGoodsSpec['spec_goods_img'];
        $orderData['goods_image'] = $goodsImg;
        $orderData['spec_id'] = $cartData['spec_id'];
        $orderData['spec_name'] = $cartData['spec_name'];
        $orderData['buy_num'] = $cartData['buy_num'];
        $orderData['goods_group_price'] = $cartData['goods_price'];
        $orderData['goods_group_info'] = json_encode($groupInfo['spec_attr'], JSON_UNESCAPED_UNICODE);
        $orderData['pay_type'] = $payType;
        $pv = empty($currentGoodsSpec['spec_goods_pv']) ? 0 : $currentGoodsSpec['spec_goods_pv'];
        $specPrice = $currentGoodsSpec['spec_price'] - $pv;
        $pv = $cartData['goods_price'] - $specPrice > 0 ? $cartData['goods_price'] - $specPrice : 0;
        $orderData['order_pv'] = round($pv * $buyNum, 2);
        $orderData['goods_pv'] = $pv;
        if ($payType == 0) {
            $orderData['pay_success'] = 1;
            $orderData['pay_time'] = NOW_TIME;
        }
        $orderData['pay_price'] = $totalPrice;
        $orderData['create_time'] = NOW_TIME;
        $orderData['address_id'] = $addressInfo['address_id'];
        $orderData['consignee'] = $addressInfo['member_name'];
        $orderData['address'] = $addressInfo['address'];
        $orderData['member_tel'] = $addressInfo['member_tel'];
        $orderData['remark'] = $remark;
        $model = M('mb_group_buying_order');
        $orderId = $model->add($orderData);
        if (false === $orderId) {
            $this->rollback();
            logWrite("下单失败:" . $model->getError() . '-' . $model->getDbError());
            return getReturn();
        }

        // 团购数量增加 商品库存减少 放到支付通知里做 线下支付直接处理
        if ($payType == 0) {
            $where = [];
            $where['group_id'] = $groupInfo['group_id'];
            $result = $this->where($where)->setInc('join_num', $buyNum);
            if (false === $result) {
                $this->rollback();
                logWrite("增加团购数量失败" . $model->getError() . '-' . $model->getDbError());
                return getReturn();
            }

            // 减少库存 增加销量
            $result = $this->reduceStorageAndAddSales($orderData['goods_id'], $orderData['spec_id'], $orderData);
            if ($result['code'] !== 200) {
                $this->rollback();
                return $result;
            }
            $groupNewInfo = $this->field('group_status,group_num,base_num,join_num,group_mode')->find($groupInfo['group_id']);
            logWrite("增加数量后的团购信息:" . json_encode($groupNewInfo, JSON_UNESCAPED_UNICODE));
            if ($groupNewInfo['join_num'] + $groupNewInfo['base_num'] >= $groupNewInfo['group_num']) {
                $groupData = [];
                if ($groupNewInfo['group_status'] == 1) {
                    $groupData['group_status'] = 2;
                    $groupData['complete_time'] = NOW_TIME;
                    if ($groupNewInfo['group_mode'] == 1) {
                        $groupData['close_status'] = 2;
                        $groupData['close_time'] = NOW_TIME;
                        $groupData['close_remark'] = '成团后下线';
                    }
                    $result = $this->where($where)->save($groupData);
                    if (false === $result) {
                        $this->rollback();
                        logWrite("修改团购状态失败" . $this->getError() . '-' . $this->getDbError());
                        return getReturn();
                    }
                }
                // 更新团购订单的团购状态
                $where = [];
                $where['group_id'] = $groupInfo['group_id'];
                $result = M('mb_group_buying_order')->where($where)->setField('group_complete_status', 1);
                if (false === $result) {
                    $this->rollback();
                    logWrite("修改团购订单的成团失败:" . $this->getError() . '-' . $this->getDbError());
                    return getReturn();
                }
            }
        }


        // 清空购物车
        $where = [];
        $where['member_id'] = $memberId;
        $where['store_id'] = $storeId;
        $result = M('mb_group_buying_cart')->where($where)->setField('cart_data', '');
        if (false === $result) {
            $this->rollback();
            logWrite("清空购物车出错:" . $model->getError() . '-' . $model->getDbError());
            return getReturn();
        }
        $this->commit();
        // 根据支付方式返回不同的跳转路径
        $url = URL("/index.php?c=GroupBuying&a=joinGroupSuccess&se=" . $storeId . "&f=" . $memberId . "&order_id=" . $orderId);
        // 保证唯一性 不能与普通订单的重复
        $out_trade_no = "group_{$orderId}";
        if ($payType == 1) {
            $total_fee = $totalPrice * 100;
            $return_url = urlencode($url);
            $url = URL("/pay/index.php?c=Pay&a=wxpay&type=5&total_fee=" . $total_fee .
                "&out_trade_no=" . $out_trade_no . "&order_id=" . $orderId . "&return_url=" . $return_url . "&se=" . $storeId . "&f=" . $memberId);
        } else if ($payType == 2) {
            $total_fee = $totalPrice;
            $return_url = urlencode($url);
            $url = URL("/index.php?c=Pay&a=alipay&type=5&total_fee=" . $total_fee .
                "&out_trade_no=" . $out_trade_no . "&order_id=" . $orderId . "&return_url=" . $return_url . "&se=" . $storeId . "&f=" . $memberId);
        }
        $returnData['order_id'] = $orderId;
        $returnData['total_fee'] = $total_fee;
        $returnData['out_trade_no'] = $out_trade_no;
        $returnData['return_url'] = $return_url;
        $returnData['se'] = $storeId;
        $returnData['f'] = $memberId;
        $returnData['member_id'] = $memberId;
        $returnData['channel_id'] = $channelId;
        $returnData['pay_type'] = $payType;
        $returnData['url'] = $url;
        // 立即更新
        D('Goods')->refreshGoods($storeId);
        D('StoreDecoration')->clearTplCache($storeId);
        return getReturn(200, '', $returnData);
    }

    /**
     * 减少库存
     * @param int $goodsId
     * @param string $specId
     * @param array $orderInfo
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-08 15:32:50
     * Update: 2018-02-08 15:32:50
     * Version: 1.00
     */
    public function reduceStorageAndAddSales($goodsId = 0, $specId = '', $orderInfo = [])
    {
        $model = M('goods');
        $specId = empty($specId) ? 0 : $specId;
        $goodsInfo = $model->field('goods_id,goods_storage,goods_spec,spec_open')->find($goodsId);
        logWrite("减少库存,商品信息:" . json_encode($goodsInfo, JSON_UNESCAPED_UNICODE));
        if (!empty($goodsInfo)) {
            // 限购
            $result = D('GroupBuyingLimit')->addBuyNum($orderInfo['group_id'], $orderInfo['member_id'], $goodsId, $orderInfo['buy_num']);
            if (!isSuccess($result)) {
                return $result;
            }
            $maxVersion = $model->max('version');
            $data = [];
            if ($goodsInfo['spec_open'] == 0) {
                // 旧规格
                $spec = json_decode($goodsInfo['goods_spec'], 1);
                if (empty($spec) && $goodsInfo['goods_storage'] != -1) {
                    // 无规格
                    $data = [];
                    $data['version'] = $maxVersion + 1;
                    $storage = $goodsInfo['goods_storage'] - $orderInfo['buy_num'];
                    $storage = $storage <= 0 ? 0 : $storage;
                    $data['goods_storage'] = $storage;
                } elseif (!empty($spec)) {
                    $specLength = count($spec);
                    foreach ($spec as $key => $value) {
                        if ($value['spec_id'] == $specId && $value['storage'] != -1) {
                            $data = [];
                            $data['version'] = $maxVersion + 1;
                            $storage = $value['storage'] - $orderInfo['buy_num'];
                            $storage = $storage <= 0 ? 0 : $storage;
                            if ($specLength <= 1) {
                                $data['goods_storage'] = $storage;
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
                // 增加销量
                $where = [];
                $where['goods_id'] = $goodsId;
                $modelGE = M('mb_goods_exp');
                $data = [];
                $data['version'] = $modelGE->max('version') + 1;
                $data['sales_vol'] = ['exp', "sales_vol+{$orderInfo['buy_num']}"];
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
                    $storage = $spec['stock'] - $orderInfo['buy_num'];
                    $storage = $storage <= 0 ? 0 : $storage;
                    $data = [];
                    if ($spec['stock'] != -1) {
                        $data['stock'] = $storage;
                    }
                    $data['sales'] = ['exp', "sales+{$orderInfo['buy_num']}"];
                    $where = [];
                    $where['id'] = $spec['id'];
                    $result = $modelGO->where($where)->save($data);
                    if (false === $result) {
                        logWrite("更新库存销量出错:" . $modelGO->getError() . '-' . $modelGO->getDbError());
                        return getReturn();
                    }

                    // 增加销量
                    $where = [];
                    $where['goods_id'] = $goodsId;
                    $modelGE = M('mb_goods_exp');
                    $data = [];
                    $data['version'] = $modelGE->max('version') + 1;
                    $data['sales_vol'] = ['exp', "sales_vol+{$orderInfo['buy_num']}"];
                    $result = $modelGE->where($where)->save($data);
                    if (false === $result) {
                        logWrite("更新销量出错:" . $modelGE->getError() . '-' . $modelGE->getDbError());
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
     * 获取团购详情
     * @param int $storeId
     * @param int $groupId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-07 19:34:43
     * Update: 2018-02-07 19:34:43
     * Version: 1.00
     */
    public function getGroupInfoInStore($storeId = 0, $groupId = 0)
    {
        $field = [
            'a.*',
            'b.goods_name', 'b.goods_img', 'b.goods_fig', 'b.goods_content',
            'b.goods_spec', 'b.all_stock', 'b.all_stock != 0 has_stock', 'b.min_goods_price',
            'b.max_goods_price', 'b.spec_attr'
        ];
        $field = implode(',', $field);
        $join = [
            '__GOODS_EXTRA__ b ON a.goods_id = b.goods_id'
        ];
        $where = [];
        $where['a.end_time'] = ['gt', NOW_TIME];
        $where['a.group_id'] = $groupId;
        $where['a.store_id'] = $storeId;
        $where['a.is_delete'] = 0;
        $where['a.close_status'] = 1;
        $where['b.goods_state'] = 1;
        $where['b.goods_delete'] = 0;
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, '团购已过期');
        $condition = [];
        $condition['time_field'] = [
            'create_time' => '',
            'start_time' => '',
            'end_time' => '',
        ];
        $condition['json_field'] = [
            'goods_img', 'goods_fig', 'return_rule', 'spec_group_price',
            'goods_spec', 'spec_attr'
        ];
        $condition['map_field'] = [
            'group_mode' => ['', '限量', '成团量']
        ];
        $condition['callback_field'] = [
            'min_goods_price' => ['min_goods_price', 'numberFormat'],
            'max_goods_price' => ['max_goods_price', 'numberFormat'],
            'min_group_price' => ['min_group_price', 'numberFormat'],
            'max_group_price' => ['max_group_price', 'numberFormat'],
        ];
        $info = $this->transformInfo($info, $condition);
        return getReturn(200, '', $info);
    }

    /**
     * 转换数据
     * @param array $info
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-07 19:34:52
     * Update: 2018-02-07 19:34:52
     * Version: 1.00
     */
    public function transformInfo($info = array(), $condition = array())
    {
        $info = parent::transformInfo($info, $condition);

        // 商品图片
        if (isset($info['goods_img']) || isset($info['goods_fig'])) {
            $url1 = empty($info['goods_img'][0]['url']) ? '' : $info['goods_img'][0]['url'];
            $url2 = empty($info['goods_fig'][0]['url']) ? '' : $info['goods_fig'][0]['url'];
            $info['goods_image'] = empty($url1) ? $url2 : $url1;
        }
        // 价格
        if (isset($info['min_goods_price']) && isset($info['max_goods_price'])) {
            $info['goods_price_string'] = $info['min_goods_price'] == $info['max_goods_price'] ?
                $info['min_goods_price'] :
                "{$info['min_goods_price']}~{$info['max_goods_price']}";
        }
        // 团购价
        if (isset($info['min_group_price']) && isset($info['max_group_price'])) {
            $info['group_price_string'] = $info['min_group_price'] == $info['max_group_price'] ?
                $info['min_group_price'] :
                "{$info['min_group_price']}~{$info['max_group_price']}";
        }
        // 团购状态
        if ($info['start_time'] > NOW_TIME) {
            // 未开始
            $info['group_state'] = 0;
            $info['limit_num'] = $info['group_num'];
            $info['start_time_remain'] = $info['start_time'] - NOW_TIME;
            $info['end_time_remain'] = $info['end_time'] - $info['start_time'];
        } elseif ($info['start_time'] <= NOW_TIME && $info['end_time'] > NOW_TIME) {
            // 参团人数
            $info['has_join_num'] = $info['base_num'] + $info['join_num'];
            // 已开始
            $info['group_state'] = 1;
            // 距结束时间
            $info['end_time_remain'] = $info['end_time'] - NOW_TIME;
            $info['start_time_remain'] = 0;
            if ($info['has_stock'] == 0) {
                // 已抢光
                $info['group_state'] = 2;
                $info['stock_num'] = $info['group_num'];
            } else {
                if ($info['group_status'] == 1) {
                    // 还需多少人成团
                    $info['remain_join_num'] = $info['group_num'] - ($info['base_num'] + $info['join_num']);
                    $info['all_num'] = $info['group_num'];
                    $info['value_num'] = $info['base_num'] + $info['join_num'];
                    $info['num_ratio'] = round(($info['value_num'] / $info['all_num']), 2) * 100;
                } else {
                    // 已成团
                    $info['all_num'] = $info['all_stock'] == -1 ? $info['has_join_num'] + 1000 : $info['all_stock'];
                    $info['value_num'] = $info['has_join_num'];
                    $info['num_ratio'] = round(($info['value_num'] / $info['all_num']), 2) * 100;
                }
            }
        }

        // 组装图文详情
        if (isset($info['goods_content']) || isset($info['goods_fig'])) {
            $goodsContent = empty($info['goods_content']) ? '' : $info['goods_content'];
            $goodsFig = empty($info['goods_fig']) ? [] : $info['goods_fig'];
            $html = '';
            foreach ($goodsFig as $key => $value) {
                if ($key == 0) continue;
                $img = empty($value['url']) ? '' : "<img data-src='{$value['url']}'>";
                $text = empty($value['text']) ? '' : $value['text'];
                $html .= "<div>{$img}<br>{$text}</div>";
            }
            $info['goods_desc'] = $html . '<br>' . $goodsContent;
        }

        // 组装商品规格
        if (isset($info['goods_spec'])) {
            $info['spec_option'] = [];
            foreach ($info['goods_spec'] as $key => $value) {
                $item = [];
                $item['spec_name'] = $key;
                $item['options'] = $value;
                $info['spec_option'][] = $item;
            }
        }
        // 规格团购价
        if (isset($info['spec_group_price']) && isset($info['spec_attr'])) {
            foreach ($info['spec_attr'] as $key => $value) {
                $specGroupPrice = $info['spec_group_price'][$key]['spec_group_price'];
                $info['spec_attr'][$key]['spec_group_price'] = empty($specGroupPrice) ? $value['spec_price'] : $specGroupPrice;
            }
            $info['spec_length'] = empty($info['goods_spec']) ? 0 : count($info['spec_group_price']);
        }

        // 返利规则
        if (isset($info['group_return'])) {
            $info['return_rule'] = $info['group_return'] == 2 ? [] : $info['return_rule'];
        }

        // 合并商品相册
        if (isset($info['goods_img']) || isset($info['goods_fig'])) {
            $goodsImg = empty($info['goods_img']) ? [] : $info['goods_img'];
            $goodsFig = empty($info['goods_fig']) ? [] : $info['goods_fig'];
            $img = [];
            foreach ($goodsImg as $key => $value) {
                if (!empty($value['url'])) {
                    $img[] = $value['url'];
                }
            }
            foreach ($goodsFig as $key => $value) {
                if (!empty($value['url']) && !in_array($value['url'], $img)) {
                    $img[] = $value['url'];
                }
            }
            $info['goods_img'] = $img;
        }

        // 库存
        if (isset($info['all_stock'])) {
            $info['goods_stock'] = $info['all_stock'] == -1 ? '充足' : $info['all_stock'];
        }
        return $info;
    }

}