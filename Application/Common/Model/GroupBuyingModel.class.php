<?php

namespace Common\Model;
class GroupBuyingModel extends BaseModel
{
    protected $tableName = 'mb_group_buying';

    /**
     * 获取首页的轮播团购列表
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-06 10:38:12
     * Update: 2018-02-06 10:38:12
     * Version: 1.00
     */
    public function getIndexGroupBuyList($storeId = 0, $page = 1, $limit = 0, $condition = [])
    {
        // 如果超过一个月未续费 商品查出来为空即可
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        if ((NOW_TIME - $storeInfo['vip_endtime']) >= 3600 * 24 * 30) {
            return getReturn(CODE_SUCCESS, '', []);
        }
        $where = [];
        $where['a.recommend_index'] = 1;
        $where = array_merge($where, $condition);
        $result = $this->getGroupList($storeId, $page, $limit, $where);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        return getReturn(200, '', $list);
    }

    /**
     * 获取团购列表
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-06 17:35:42
     * Update: 2018-02-06 17:35:42
     * Version: 1.00
     */
    public function getGroupList($storeId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $field = [
            'a.group_id', 'a.min_group_price', 'a.max_group_price', 'a.start_time', 'a.end_time',
            'a.group_mode', 'a.group_num', 'a.start_time <= ' . NOW_TIME . ' is_start',
            'a.group_status', 'a.join_num', 'a.base_num',
            'b.goods_id', 'b.goods_name', 'b.goods_img', 'b.goods_fig', 'b.min_goods_price', 'b.max_goods_price',
            'b.all_stock', 'b.all_stock != 0 has_stock'
        ];
        $join = [
            '__GOODS_EXTRA__ b ON a.goods_id = b.goods_id'
        ];
        $field = implode(',', $field);
        // 未抢光,已开始,快结束
        $order = 'b.all_stock != 0 DESC,' . 'a.start_time <= ' . NOW_TIME . ' DESC,a.end_time ASC,a.start_time ASC,a.group_id DESC';
        $where = [];
        $where['a.store_id'] = $storeId;
        $where['a.end_time'] = ['gt', NOW_TIME];
        $where['a.close_status'] = 1;
        $where['a.is_delete'] = 0;
        $where['b.goods_state'] = 1;
        $where['b.goods_delete'] = 0;
        $where = array_merge($where, $condition);
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = $order;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $condition = [];
        $condition['time_field'] = [
            'create_time' => '',
            'start_time' => '',
            'end_time' => '',
        ];
        $condition['json_field'] = [
            'goods_img', 'goods_fig'
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
        foreach ($list as $key => &$value) {
            $value = $this->transformInfo($value, $condition);
        }
        $result['data']['list'] = $list;
        return $result;
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
    public function getGroupInfo($storeId = 0, $groupId = 0)
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
        if (empty($info)) return getReturn(-1, '团购不存在');
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
        $info['share_desc'] = '';
        $info = $this->transformInfo($info, $condition);
        return getReturn(200, '', $info);
    }

    /**
     * 根据订单获取团购信息
     * @param int $storeId
     * @param int $memberId
     * @param int $orderId
     * @return array
     * User: hjun
     * Date: 2018-02-09 13:51:02
     * Update: 2018-02-09 13:51:02
     * Version: 1.00
     */
    public function getGroupInfoByOrderId($storeId = 0, $memberId = 0, $orderId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
//        $where['member_id'] = $memberId;
        $where['order_id'] = $orderId;
        $model = M('mb_group_buying_order');
        $order = $model->where($where)->find();
        if (empty($order)) return getReturn(-1, '未获取到参团信息');
        // 如果是分享的 因为小程序没有处理分享逻辑
        if ($order['member_id'] != $memberId) {
            return getReturn(CODE_ERROR, '正在跳转...', ['group_id' => $order['group_id']]);
        }
        $groupInfo = $this->find($order['group_id']);
        if (empty($groupInfo)) return getReturn(-1, '团购已失效', $groupInfo);
        $groupInfo['end_time_string'] = date('Y-m-d H:i:s', $groupInfo['end_time']);
        $endRemainTime = $groupInfo['end_time'] - NOW_TIME;
        $groupStatus = $groupInfo['group_status'];
        $needNum = $groupInfo['group_num'] - ($groupInfo['join_num'] + $groupInfo['base_num']);
        $shareDesc = "团购火热进行中\n还需要{$needNum}件成团\n截止时间:{$groupInfo['end_time_string']}";
        if (($groupInfo['join_num'] + $groupInfo['base_num'] >= $groupInfo['group_num']) || $groupInfo['group_status'] == 2) {
            $groupStatus = 2;
            $needNum = 0;
            $hasJoinNum = $groupInfo['join_num'] + $groupInfo['base_num'];
            $shareDesc = "团购火热进行中,已经抢了{$hasJoinNum}件啦";
            if ($groupInfo['group_mode'] == 1 || $groupInfo['close_status'] == 2) {
                $endRemainTime = 0;
                $shareDesc = "团购已经结束咯,下次早点来哦~";
            }
        } elseif ($groupInfo['close_status'] == 2) {
            $groupStatus = 3;
            $endRemainTime = 0;
            $shareDesc = "团购已经结束咯,下次早点来哦~";
        }
        $data = [];
        $data['group_id'] = $groupInfo['group_id'];
        $data['end_time_remaining'] = $endRemainTime;
        $data['need_num'] = $needNum;
        $data['group_status'] = $groupStatus;
        $data['recommend_goods'] = $this->getIndexGroupBuyList($storeId)['data'];
        $data['share_desc'] = $shareDesc;
        $data['goods_image'] = $order['goods_image'];
        $data['goods_name'] = $order['goods_name'];
        $data['group_id'] = $order['group_id'];
        return getReturn(200, '', $data);
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
            // 成团描述
            $lang = ['', 'XLJ', 'CTLJ'];
            $descLang = $lang[$info['group_mode']];
            $info['group_mode_desc'] = L($descLang, ['num' => $info['limit_num']]);
            // 分享描述
            $info['share_desc'] = "团购即将开始,开始时间:{$info['start_time_string']}";
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
                $info['share_desc'] = "已经抢光啦,下次早点来哦~";
            } else {
                if ($info['group_status'] == 1) {
                    // 还需多少人成团
                    $info['remain_join_num'] = $info['group_num'] - ($info['base_num'] + $info['join_num']);
                    $info['all_num'] = $info['group_num'];
                    $info['value_num'] = $info['base_num'] + $info['join_num'];
                    $info['num_ratio'] = round(($info['value_num'] / $info['all_num']), 2) * 100;
                    $info['share_desc'] = "团购火热进行中\n还需要{$info['remain_join_num']}件成团\n截止时间:{$info['end_time_string']}";
                } else {
                    // 已成团
                    $info['all_num'] = $info['all_stock'] == -1 ? $info['has_join_num'] + 1000 : $info['all_stock'];
                    $info['value_num'] = $info['has_join_num'];
                    $info['num_ratio'] = round(($info['value_num'] / $info['all_num']), 2) * 100;
                    $info['share_desc'] = "团购火热进行中,已经抢了{$info['value_num']}件啦";
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
        if (isset($info['goods_img'])) {
            $goodsImg = empty($info['goods_img']) ? [] : $info['goods_img'];
            $img = [];
            foreach ($goodsImg as $key => $value) {
                if (!empty($value['url'])) {
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