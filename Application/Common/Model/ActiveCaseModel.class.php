<?php

namespace Common\Model;

use Common\Logic\CartLogic;

class ActiveCaseModel extends BaseModel
{
    protected $tableName = 'mb_active_case';

    /**
     * @param int $storeId 商家ID 传了就查商家的列表
     * @param int $channelId 渠道ID 不传商家ID 则查渠道下的列表
     * @param int $memberId 会员ID
     * @param int $sortType 排序类型 默认 active_sort DESC
     *  0 - active_sort DESC
     *  1 - all_sale_num ASC
     *  2 - all_sale_num DESC
     *  3 - goods_price ASC
     *  4 - goods_price DESC
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
    public function getActivityGoodsList($storeId = 0, $channelId = 0, $memberId = 0, $sortType = 0, $page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        $where['a.is_del_mall'] = 0;
        $where['a.is_del_biz'] = 0;
        $where['a.active_show'] = 1;
        $where['a.active_state'] = 1;
        if ($channelId > 0) $where['a.channel_id'] = $channelId;
        $where = array_merge($where, $condition);
        // 找好店单独查询 这里不查询 返回无效参数
        if ($where['a.active_type'] == 3) {
            return getReturn(-1, L('INVALID_PARAM'));
        }
        // 如果是限时购 还要增加查询条件 不查出已经过期的
        if ($where['a.active_type'] == 2) {
            $where['b.is_qinggou'] = 1;
            $where['b.end_time'] = ['gt', NOW_TIME];
        }
        $where['b.goods_state'] = 1;
        $where['b.goods_delete'] = 0;
        $field = [
            'a.active_id',
            'b.goods_id,b.goods_name,b.goods_img,b.goods_fig',
            'b.is_qinggou,b.is_promote,b.min_promote_price,b.max_promote_price',
            'b.min_goods_price,b.max_goods_price,b.start_time,b.end_time,b.store_name',
            'b.all_stock goods_storage'
        ];
        $join = [
            '__GOODS_EXTRA__ b ON a.goods_id = b.goods_id'
        ];
        // 排序
        $priceName = $where['a.active_type'] == 2 ? 'min_promote_price' : 'min_goods_price';
        switch ((int)$sortType) {
            case 1:
                $order = 'b.all_sale_num ASC';
                break;
            case 2:
                $order = 'b.all_sale_num DESC';
                break;
            case 3:
                $order = "b.{$priceName} ASC";
                break;
            case 4:
                $order = "b.{$priceName} DESC";
                break;
            default:
                $order = 'a.active_sort DESC';
                break;
        }
        $options = [];
        $options['alias'] = 'a';
        $options['join'] = $join;
        $options['where'] = $where;
        $options['skip'] = ($page - 1) * $limit;
        $options['take'] = $limit;
        $options['field'] = implode(',', $field);
        $options['order'] = $order;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $condition = [];
        $condition['time_field'] = [
            'start_time' => '',
            'end_time' => '',
        ];
        $condition['json_field'] = [
            'goods_img', 'goods_fig'
        ];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value, $condition);
            // 检查是否已经提醒
            $where = [];
            $where['store_id'] = $storeId;
            $where['member_id'] = $memberId;
            $where['param_id'] = $value['active_id'];
            $where['param_type'] = 2;
            $tip = M('mb_message_tip_task')->field('id')->where($where)->find();
            $list[$key]['is_alert'] = empty($tip) ? 0 : 1;
        }
        $cartTool = new CartLogic($storeId, $memberId);
        $list = $cartTool->initGoodsListPrice($list);
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 添加限时抢购提醒
     * @param int $storeId 商家ID
     * @param int $memberId 会员ID
     * @param array $request 请求参数
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-10 16:49:03
     * Update: 2018-02-10 16:49:03
     * Version: 1.00
     */
    public function addLimitGoodsMsgTip($storeId = 0, $memberId = 0, $request = [])
    {
        $field = [
            'a.goods_id', 'a.goods_name', 'a.start_time', 'a.end_time'
        ];
        $field = implode(',', $field);
        $goodsId = $request['goods_id'];
        $where = [];
        $where['a.goods_id'] = $goodsId;
        $where['a.is_qinggou'] = 1;
        $info = D('GoodsExtra')
            ->alias('a')
            ->where($where)
            ->field($field)
            ->find();
        if (empty($info)) return getReturn(-1, '活动已失效', D('GoodsExtra')->_sql());
        // 获取会员信息
        $model = D('Member');
        $result = $model->getMemberInfo($memberId);
        if ($result['code'] !== 200) return $result;
        $memberInfo = $result['data'];
        // 参数
        $data = [];
        $data['type'] = 1;
        $data['param_type'] = 2; // 限时抢购提醒
        $data['param_id'] = $goodsId;
        // 提前5分钟提醒
        $data['reminder_time'] = $info['start_time'] - 300;
        // 提醒内容设置
        $content = '{"first":{"value":"{FIRST}","color":"#173177"},"keyword1":{"value":"{MEMBER_NAME}","color":"#173177"},"keyword2":{"value":"{THEME}","color":"#173177"},"keyword3":{"value":"{TIME}","color":"#173177"},"keyword4":{"value":"{ADDRESS}","color":"#173177"},"remark":{"value":"{REMARK}","color":"#173177"}}';
        $key = ['FIRST', 'MEMBER_NAME', 'THEME', 'TIME', 'ADDRESS', 'REMARK'];
        $temp['FIRST'] = "您好,限时抢购活动即将开始。";
        $temp['MEMBER_NAME'] = $memberInfo['member_nickname'];
        $temp['THEME'] = "[抢购商品]{$info['goods_name']}";
        $temp['TIME'] = date('Y-m-d H:i:s', $info['start_time']) . ' - ' . date("Y-m-d H:i:s", $info['end_time']);
        $temp['ADDRESS'] = '微信商城商品详情';
        $temp['REMARK'] = "点击立即查看商品详情。";
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
        $data['template_content']['url'] = getStoreDomain($storeId) . "/index.php?c=Goods&a=goods_detail&id={$info['goods_id']}&se={$storeId}&f={$memberId}";
        $data['template_content']['data'] = json_decode($content, 1);
        $data['template_content'] = json_encode($data['template_content'], JSON_UNESCAPED_UNICODE);
        return D('MessageTip')->addMsgTipTask($memberId, $storeId, $data);
    }

    public function transformInfo($info = array(), $condition = array())
    {
        $info = parent::transformInfo($info, $condition);

        // 图片
        if (!empty($info['goods_img'])) {
            $info['goods_img'] = $info['goods_img'][0]['url'];
        } elseif (!empty($info['goods_fig'])) {
            $info['goods_img'] = $info['goods_fig'][0]['url'];
        } else {
            $info['goods_img'] = '';
        }

        // 时间状态
        if ($info['start_time'] > NOW_TIME) {
            $info['flashSale_state'] = 0;
            $info['start_time_remaining'] = $info['start_time'] - NOW_TIME;
            $info['end_time_remaining'] = $info['end_time'] - $info['start_time'];
        } elseif ($info['start_time'] <= NOW_TIME && $info['end_time'] > NOW_TIME) {
            $info['flashSale_state'] = 1;
            $info['end_time_remaining'] = $info['end_time'] - NOW_TIME;
            $info['start_time_remaining'] = 0;
        } else {
            $info['flashSale_state'] = 3;
            $info['end_time_remaining'] = 0;
            $info['start_time_remaining'] = 0;
        }

        return $info;
    }

    /**
     * @param int $storeId 商家ID 传了就查商家的列表
     * @param int $memberId 会员ID
     * @param int $sortType 排序类型 默认 active_sort DESC
     *  0 - active_sort DESC
     *  1 - all_sale_num ASC
     *  2 - all_sale_num DESC
     *  3 - goods_price ASC
     *  4 - goods_price DESC
     *  5 - create_time ASC
     *  6 - create_time DESC
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
    public function getShopFlashGoodsList($storeId = 0, $memberId = 0, $sortType = 0, $page = 1, $limit = 0, $condition = [])
    {
        $model = D('GoodsExtra');
        $where = [];
        $where = array_merge($where, $condition);
        $where['b.is_qinggou'] = 1;
        $where['b.end_time'] = ['gt', NOW_TIME];
        $where['b.goods_state'] = 1;
        $where['b.goods_delete'] = 0;
        $where['b.store_id'] = $storeId;
        $field = [
            'b.goods_id,b.goods_name,b.goods_img,b.goods_fig',
            'b.is_qinggou,b.is_promote,b.min_promote_price,b.max_promote_price',
            'b.min_goods_price,b.max_goods_price,b.start_time,b.end_time,b.store_name',
            'b.all_stock goods_storage',
            'a.goods_desc',
        ];
        $join = [
            '__GOODS__ a ON a.goods_id = b.goods_id'
        ];
        // 排序
        $priceName = 'min_promote_price';
        switch ((int)$sortType) {
            case 1:
                $order = 'b.all_sale_num ASC';
                break;
            case 2:
                $order = 'b.all_sale_num DESC';
                break;
            case 3:
                $order = "b.{$priceName} ASC";
                break;
            case 4:
                $order = "b.{$priceName} DESC";
                break;
            case 5:
                $order = "b.create_time ASC";
                break;
            case 6:
                $order = "b.create_time DESC";
                break;
            default:
                $order = '';
                break;
        }
        $options = [];
        $options['alias'] = 'b';
        $options['join'] = $join;
        $options['where'] = $where;
        $options['skip'] = ($page - 1) * $limit;
        $options['take'] = $limit;
        $options['field'] = implode(',', $field);
        $options['order'] = $order;
        $result = $model->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $condition = [];
        $condition['time_field'] = [
            'start_time' => '',
            'end_time' => '',
        ];
        $condition['json_field'] = [
            'goods_img', 'goods_fig'
        ];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value, $condition);
            // 检查是否已经提醒
            $where = [];
            $where['store_id'] = $storeId;
            $where['member_id'] = $memberId;
            $where['status'] = 2;
            $where['param_id'] = $value['goods_id'];
            $where['param_type'] = 2;
            $tip = M('mb_message_tip_task')->field('id')->where($where)->find();
            $list[$key]['is_alert'] = empty($tip) ? 0 : 1;
        }
        $cartTool = new CartLogic($storeId, $memberId);
        $list = $cartTool->initGoodsListPrice($list);
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 获取商城下限时抢购的商品列表
     * @param int $channelId 渠道号
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-28 14:49:44
     * Version: 1.0
     */
    public function getLimitTimeGoods($channelId = 0)
    {

    }

}