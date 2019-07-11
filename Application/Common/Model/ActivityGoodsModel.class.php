<?php

namespace Common\Model;
/**
 * Class ActivityGoodsModel
 * @package Common\Model
 * Author: hj
 * Desc: 活动商品模型类
 * Date: 2017-11-27 15:54:10
 * Update: 2017-11-27 15:54:11
 * Version: 1.0
 */
class ActivityGoodsModel extends BaseModel
{
    protected $tableName = 'mb_active_case';

    /**
     * @param int $storeId 商家ID 传了就查商家的列表
     * @param int $channelId 渠道ID 不传商家ID 则查渠道下的列表
     * @param int $page 页数
     * @param int $limit 条数限制
     * @param array $condition 其他查询条件
     *  1 - 精划算
     *  2 - 限时购
     *  4 - 精品购
     *  5 - 每日购
     *  6 - 今日特价
     *  7 - 热销商品
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取活动列表 不查出被删除的
     * Date: 2017-11-27 16:30:12
     * Update: 2017-11-27 16:30:13
     * Version: 1.0
     */
    public function getActivityGoodsList($storeId = 0, $channelId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        $where['a.is_del_mall'] = 0;
        $where['a.is_del_biz'] = 0;
        if ($storeId > 0) $where['a.store_id'] = $storeId;
        if ($channelId > 0) $where['a.channel_id'] = $channelId;
        $where = array_merge($where, $condition);
        // 找好店单独查询 这里不查询 返回无效参数
        if ($where['a.active_type'] == 3) {
            return getReturn(-1, L('INVALID_PARAM'));
        }
        // 如果是限时购 还要增加查询条件 不查出 未开始的和已经过期的
        if ($where['a.active_type'] == 2) {
            $where['b.is_qinggou'] = 1;
            $where['b.end_time'] = ['gt', NOW_TIME];
        }
        $field = [
            'a.active_id,a.goods_id,a.active_type,a.active_state,a.active_sort,a.active_show,a.active_creat',
            'b.goods_name,b.goods_img,b.goods_fig,b.is_hot,b.is_qinggou,b.is_promote,b.min_promote_price,b.max_promote_price',
            'b.min_goods_price,b.max_goods_price,b.start_time,b.end_time,b.store_name',
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['join'] = [
            '__GOODS_EXTRA__ b ON a.goods_id = b.goods_id'
        ];
        $options['where'] = $where;
        $options['skip'] = ($page - 1) * $limit;
        $options['take'] = $limit;
        $options['field'] = implode(',', $field);
        $options['order'] = 'a.active_sort DESC';
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key] = $this->initGoodsInfo($value);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取找好店列表
     * Date: 2017-11-28 11:47:56
     * Update: 2017-11-28 11:47:57
     * Version: 1.0
     */
    public function getGoodShopList($channelId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        $where['a.is_del_mall'] = 0;
        $where['a.is_del_biz'] = 0;
        $where['a.channel_id'] = $channelId;
        $where = array_merge($where, $condition);
        $field = [
            'a.active_id,a.goods_id,a.active_type,a.active_state,a.active_sort,a.active_show,a.active_creat',
            'b.store_id,b.store_name,b.store_address,b.store_label'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = implode(',', $field);
        $options['join'] = [
            '__STORE__ b ON a.store_id = b.store_id'
        ];
        $options['where'] = $where;
        $options['skip'] = ($page - 1) * $limit;
        $options['take'] = $limit;
        $options['order'] = 'a.active_sort DESC';
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key] = $this->initGoodsInfo($value);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * @param mixed $id 主键
     * @param mixed $channelId 渠道号
     * @param mixed $type 活动类型
     * @param array $data 更新的数据
     * @param mixed $callback 回调函数
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 修改活动 审核、排序、置顶等操作
     * Date: 2017-11-28 12:06:23
     * Update: 2017-11-28 12:06:24
     * Version: 1.0
     */
    public function updateActivityCase($id, $channelId = 0, $type = 1, $data = [], $callback)
    {
        // 先检查记录
        $where = [];
        $where['active_id'] = is_array($id) ? ['in', implode(',', $id)] : $id;
        $where['is_del_mall'] = 0;
        $where['is_del_biz'] = 0;
        if ($channelId > 0) $where['channel_id'] = $channelId;
        $where['active_type'] = $type;
        $options = [];
        $options['where'] = $where;
        $options['field'] = 'active_id,active_state,active_sort,active_show';
        // 批量修改 则不去检查数据了
        if (is_array($id)) {
            $options = [];
            $options['where'] = $where;
            $data['version'] = $this->max('version') + 1;
            return $this->saveData($options, $data);
        }
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, L('RECORD_INVALID'));

        // 自定义回调函数 如果继承了Closure类 或者存在这样一个函数
        // 参数是该条记录信息 以及更新的数据
        // 返回结果规定为 code msg data
        // code不为200则需要返回错误, data里面返回处理后的data 没有处理原样返回data
        if ($callback instanceof \Closure || function_exists($callback)) {
            $result = $callback($info, $data);
            if ($result['code'] !== 200) return $result;
            $data = $result['data'];
        }

        // 版本号更新
        $data['version'] = $this->max('version') + 1;

        // 保存信息
        $where = [];
        $where['active_id'] = $id;
        $options = [];
        $options['where'] = $where;
        $result = $this->saveData($options, $data);
        if ($result['code'] !== 200) return $result;
        foreach ($result['data'] as $key => $value) {
            $info[$key] = $value;
        }
        $result['data'] = $info;
        return $result;
    }

    /**
     * @param int $id
     * @param int $channelId 渠道
     * @param int $type 类型
     * @param int $state 1-通过 2-拒绝
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 审核活动
     * Date: 2017-11-28 13:37:41
     * Update: 2017-11-28 13:37:42
     * Version: 1.0
     */
    public function verActivityCase($id = 0, $channelId = 0, $type = 1, $state = 1)
    {
        $param = [1, 2];
        $state = (int)$state;
        if (!in_array($state, $param)) return getReturn(-1, L('INVALID_PARAM'));
        // 自定义回调函数
        $callback = function ($info, $data) {
            if ($data['active_state'] == $info['active_state']) {
                return getReturn(-1, L('OUT_OF_DATE'));
            }
            return getReturn(200, '', $data);
        };
        $data = [];
        $data['active_state'] = $state;
        return $this->updateActivityCase($id, $channelId, $type, $data, $callback);
    }

    /**
     * @param int $id
     * @param int $channelId 渠道
     * @param int $type 类型
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 删除某个活动
     * Date: 2017-11-28 14:21:16
     * Update: 2017-11-28 14:21:18
     * Version: 1.0
     */
    public function delActivityCase($id = 0, $channelId = 0, $type = 1)
    {
        // 进行中 更新不同的字段
        $callback = function ($info, $data) {
            if ($info['active_state'] == 1) {
                $data['active_state'] = 3;
            } else {
                $data['is_del_mall'] = 1;
            }
            return getReturn(200, '', $data);
        };
        $id = is_array($id) ? $id : [$id];
        $this->startTrans();
        foreach ($id as $key => $value) {
            $result = $this->updateActivityCase($value, $channelId, $type, [], $callback);
            if ($result['code'] !== 200) {
                $this->rollback();
                return $result;
            }
        }
        $this->commit();
        return $result;
    }

    /**
     * @param int $id
     * @param int $channelId 渠道
     * @param int $type 类型
     * @param float $sort
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 排序活动商品
     * Date: 2017-11-28 15:43:20
     * Update: 2017-11-28 15:43:21
     * Version: 1.0
     */
    public function sortActivityCase($id = 0, $channelId = 0, $type = 1, $sort = 0.00)
    {
        $data = [];
        $data['active_sort'] = (double)$sort;
        return $this->updateActivityCase($id, $channelId, $type, $data);
    }

    /**
     * @param int $id 主键
     * @param int $channelId 渠道号
     * @param int $type 活动类型
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 置顶活动商品
     * Date: 2017-11-28 15:43:40
     * Update: 2017-11-28 15:43:41
     * Version: 1.0
     */
    public function topActivityCase($id = 0, $channelId = 0, $type = 1)
    {
        $where = [];
        $where['channel_id'] = $channelId;
        $where['is_del_mall'] = 0;
        $where['is_del_biz'] = 0;
        $where['active_type'] = $type;
        $where['active_state'] = 1;
        $options = [];
        $options['where'] = $where;
        $data = [];
        $data['active_sort'] = $this->queryMax($options, 'active_sort')['data'] + 2;
        return $this->updateActivityCase($id, $channelId, $type, $data);
    }

    /**
     * @param int $id
     * @param int $channelId 渠道
     * @param int $type 类型
     * @param int $state
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 显示/隐藏活动
     * Date: 2017-11-28 15:50:44
     * Update: 2017-11-28 15:50:45
     * Version: 1.0
     */
    public function showOrHideActivityCase($id = 0, $channelId = 0, $type = 1, $state = 1)
    {
        $param = [0, 1];
        $state = (int)$state;
        if (!in_array($state, $param)) return getReturn(-1, L('INVALID_PARAM'));
        $callback = function ($info, $data) {
            if ($info['active_show'] == $data['active_show']) {
                return getReturn(-1, L('OUT_OF_DATE'));
            }
            return getReturn(200, '', $data);
        };
        $data = [];
        $data['active_show'] = $state;
        return $this->updateActivityCase($id, $channelId, $type, $data, $callback);
    }

    /**
     * @param $goodsId
     * @param int $channelId
     * @param int $activeType 活动类型
     * @param int $storeType 商家类型
     *  1 - 精划算
     *  2 - 限时购
     *  4 - 精品购
     *  5 - 每日购
     *  6 - 今日特价
     *  7 - 热销商品
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 商城主动将商品推荐到活动商品
     *  推荐时要生成相应的规则 如果已经有规则了 则不需要生成
     *      1 - 1
     *      2 - 2
     *      3 - 0
     *      4 - 0
     *      5 - 0
     *      6 - 1
     *      7 - 4
     *  判断是否已经有商品加入了 有返回错误
     *  判断商品是否复合规则：0-所有商品 1-只降商品 2-只抢购 4-只热卖 不符合返回错误
     * Date: 2017-11-28 16:49:26
     * Update: 2017-11-28 16:49:27
     * Version: 1.0
     */
    public function recommendGoodsToActivity($goodsId, $channelId = 0, $activeType = 1, $storeType = 0)
    {
        $param = [1, 2, 4, 5, 6, 7];
        if (!in_array((int)$activeType, $param) || empty($goodsId)) return getReturn(-1, L('INVALID_PARAM'));
        // 判断是否已经有规则了没有规则生成一个规则
        $modelRule = D('ActivityRule');
        $modelGE = D('GoodsExtra');
        $hasRule = $modelRule->checkHasRule($channelId, $activeType);
        if (!$hasRule) {
            $result = $modelRule->addRule($channelId, $activeType);
            if ($result['code'] !== 200) return $result;
        }

        // 判断商品是否已经加入了活动
        $result = $this->checkHasJoin($goodsId, $channelId, $activeType);
        if ($result['code'] !== 200) return $result;

        // 获取类型的规则
        $ruleLimit = $modelRule->getTypeRule($channelId, $activeType);
        // 获取商品数据
        $result = $modelGE->getGoodsListInfo($goodsId);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        if (empty($list)) return getReturn(-1, L('RECORD_INVALID'));
        // 判断是否符合规则
        $result = $this->checkRule($list, $ruleLimit);
        if ($result['code'] !== 200) return $result;
        $goodsData = $result['data'];

        // 加入活动
        $data = [];
        $item = [];
        $item['channel_id'] = $channelId;
        $item['active_type'] = $activeType;
        // 商城主动直接进行中 否则待审核
        $item['active_state'] = strpos('02', $storeType . '') === false ? 0 : 1;
        $item['active_creat'] = NOW_TIME;
        $item['version'] = $this->max('version') + 1;
        foreach ($goodsData as $key => $value) {
            $item['goods_id'] = $value['goods_id'];
            $item['item_name'] = $value['goods_name'];
            $item['store_id'] = $value['store_id'];
            $data[] = $item;
        }
        return $this->addAllData([], $data);
    }

    /**
     * @param $storeId
     * @param int $channelId
     * @param int $type type默认为3 未来如果扩展了
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 将店铺推荐到找好店
     *  判断是否已经有规则了 没规则要生成规则
     *  判断是否已经加入了 加入了返回错误
     *  判断是否符合规则 不符合返回错误
     * Date: 2017-11-29 10:22:08
     * Update: 2017-11-29 10:22:09
     * Version: 1.0
     */
    public function recommendStoreToActivity($storeId, $channelId = 0, $type = 3)
    {
        // 判断是否有规则 没有则生成
        $model = D('ActivityRule');
        $hasRule = $model->checkHasRule($channelId, $type);
        if (!$hasRule) {
            $result = $model->addRule($channelId, $type);
            if ($result['code'] !== 200) return $result;
        }

        // 判断是否已经加入了
        $result = $this->checkHasJoin($storeId, $channelId, $type);
        if ($result['code'] !== 200) return $result;

        // 判断是否符合规则 暂时不需要判断


        // 获取店铺信息 加入活动
        $model = D('Store');
        $where = [];
        $where['store_id'] = is_array($storeId) ? ['in', implode(',', $storeId)] : $storeId;
        $result = $model->getStoreList($where);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        if (empty($list)) return getReturn(-1, L('RECORD_INVALID'));
        $data = [];
        $item = [];
        $item['channel_id'] = $channelId;
        $item['active_type'] = $type;
        $item['active_state'] = 1;
        $item['active_creat'] = NOW_TIME;
        $version = $this->max('version');
        foreach ($list as $key => $value) {
            $item['store_id'] = $value['store_id'];
            $item['item_name'] = $value['store_name'];
            $item['version'] = ++$version;
            $data[] = $item;
        }
        return $this->addAllData([], $data);
    }

    /**
     * @param $goodsIdOrStoreId
     * @param int $channelId
     * @param int $type
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     *      code=200 表示通过检查
     *      code!=200 表示没通过 返回错误
     * Author: hj
     * Desc: 判断是否已经加入了活动
     * Date: 2017-11-29 12:29:04
     * Update: 2017-11-29 12:29:05
     * Version: 1.0
     */
    public function checkHasJoin($goodsIdOrStoreId, $channelId = 0, $type = 1)
    {
        $where = [];
        $where['channel_id'] = $channelId;
        $where['active_type'] = $type;
        $where['is_del_mall'] = 0;
        $where['is_del_biz'] = 0;
        $where['active_state'] = ['not in', '2,3'];
        if ($type == 3) {
            $where['store_id'] = is_array($goodsIdOrStoreId) ? ['in', implode(',', $goodsIdOrStoreId)] : $goodsIdOrStoreId;
        } else {
            $where['goods_id'] = is_array($goodsIdOrStoreId) ? ['in', implode(',', $goodsIdOrStoreId)] : $goodsIdOrStoreId;
        }
        $options = [];
        $options['where'] = $where;
        $options['field'] = $type == 3 ? 'store_id,item_name,active_state' : 'goods_id,item_name,active_state';
        $joinList = $this->queryList($options)['data']['list'];
        if (!empty($joinList)) {
            $stateName = [0 => L('APPLY_FOR_PARTNER_STATE_2'), 1 => L('STATE_ING')];
            $msg = $type == 3 ? L('STORE_HAS_JOIN_ACTIVE') : L('GOODS_HAS_JOIN_ACTIVE');
            $str = [];
            // 已经参加的ID
            $id = [];
            foreach ($joinList as $key => $value) {
                $str[] = $type == 3 ?
                    "{$value['store_id']}-{$value['item_name']}-{$stateName[(int)$value['active_state']]}" :
                    "{$value['goods_id']}-{$value['item_name']}-{$stateName[(int)$value['active_state']]}";
                $id[] = $type == 3 ? (int)$value['store_id'] : (int)$value['goods_id'];
            }
            // 取出不在参加的ID
            $dataId = [];
            foreach ($goodsIdOrStoreId as $key => $value) {
                if (!in_array((int)$value, $id)) $dataId[] = (int)$value;
            }
            $str = implode('<br>', $str);
            $msg = "{$msg}<br>{$str}";
            return getReturn(-2, $msg, $dataId);
        }
        return getReturn(200, '');
    }

    /**
     * @param array $list
     * @param int $ruleLimit
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     *  code != 200 返回错误
     *  data 返回符合规则的列表
     * Author: hj
     * Desc: 判断是否符合规则
     * Date: 2017-11-29 12:33:27
     * Update: 2017-11-29 12:33:28
     * Version: 1.0
     */
    public function checkRule($list = [], $ruleLimit = 1)
    {
        if (empty($list)) return getReturn(-1, L('INVALID_PARAM'));
        // 如果规则不需要判断 则直接返回所有数据
        if (!in_array($ruleLimit, [1, 2, 4])) return getReturn(200, '', $list);

        // 符合规则的数据
        $goodsData = [];
        // 不符合的数据
        $invalidData = [];
        foreach ($list as $key => $value) {
            switch ($ruleLimit) {
                case 1:
                    // 促销 降价
                    if ($value['is_promote'] == 1) {
                        $goodsData[] = $value;
                    } else {
                        $invalidData[] = $value;
                    }
                    break;
                case 2:
                    // 是抢购 并且时间正常
                    if ($value['is_qinggou'] == 1 && NOW_TIME < $value['end_time']) {
                        $goodsData[] = $value;
                    } else {
                        $invalidData[] = $value;
                    }
                    break;
                case 4:
                    // 热卖
                    if ($value['is_hot'] == 1) {
                        $goodsData[] = $value;
                    } else {
                        $invalidData[] = $value;
                    }
                    break;
                default:
                    $invalidData[] = $value;
                    break;
            }
        }

        // 判断结果
        if (!empty($invalidData)) {
            $tip = [1 => '促销', 2 => '抢购', 4 => '热卖'];
            $msg = L('NONCONFORMITY', ['rule' => $tip[(int)$ruleLimit]]);
            $str = [];
            foreach ($invalidData as $key => $value) {
                $str[] = "{$value['goods_id']}-{$value['goods_name']}";
            }
            $str = implode('<br>', $str);
            $msg = "{$msg}<br>{$str}";
            // 符合的数据
            $data = [];
            foreach ($goodsData as $key => $value) {
                $data[] = (int)$value['goods_id'];
            }
            return getReturn(-2, $msg, $data);
        }
        return getReturn(200, '', $goodsData);
    }

    /**
     * @param array $info
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 初始化商品信息
     * Date: 2017-11-28 11:24:46
     * Update: 2017-11-28 11:24:47
     * Version: 1.0
     */
    private function initGoodsInfo($info = [])
    {
        // 活动创建时间
        if (isset($info['active_creat'])) $info['active_create_time_string'] = date("Y-m-d H:i:s", $info['active_creat']);
        // 商品主图
        if (isset($info['goods_img'])) {
            $image = json_decode($info['goods_img'], 1);
            $info['goods_img'] = empty($image[0]['url']) ? '' : $image[0]['url'];
            if (empty($info['goods_img'])) {
                $image = json_decode($info['goods_fig'], 1);
                $info['goods_img'] = empty($image[0]['url']) ? '' : $image[0]['url'];
            }
        }
        // 商品价格 (是个范围)
        if (isset($info['min_goods_price']) && isset($info['max_goods_price'])) {
            $info['goods_price'] = $info['min_goods_price'] == $info['max_goods_price'] ?
                $info['min_goods_price'] : "{$info['min_goods_price']}~{$info['max_goods_price']}";
        }
        if (isset($info['is_promote']) && $info['is_promote'] == 1 ||
            isset($info['is_qinggou']) && $info['is_qinggou'] == 1) {
            //促销价
            $info['goods_new_price'] = $info['min_promote_price'] == $info['max_promote_price'] ?
                $info['min_promote_price'] : "{$info['min_promote_price']}~{$info['max_promote_price']}";
        }
        // 抢购开始时间 结束时间
        if (isset($info['is_qinggou']) && $info['is_qinggou'] == 1) {
            if (isset($info['start_time'])) $info['start_time_string'] = date('Y-m-d H:i:s', $info['start_time']);
            if (isset($info['end_time'])) $info['end_time_string'] = date('Y-m-d H:i:s', $info['end_time']);
        }
        return $info;
    }
}