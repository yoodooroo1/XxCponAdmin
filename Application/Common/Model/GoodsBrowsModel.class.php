<?php

namespace Common\Model;
class GoodsBrowsModel extends BaseModel
{

    protected $tableName = 'mb_goods_browsing_history';

    /**
     * @param int $goodsId
     * @param int $memberId
     * @param int $storeId
     * @param int $channelId
     * @param string $client
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 记录浏览商品历史
     * Date: 2017-11-17 02:20:46
     * Update: 2017-11-17 02:20:47
     * Version: 1.0
     */
    public function browGoods($goodsId = 0, $memberId = 0, $storeId = 0, $channelId = 0, $client = 'wap')
    {
        $data = [];
        $data['member_id'] = empty($memberId) ? 0 : $memberId;
        $data['goods_id'] = $goodsId;
        $data['store_id'] = $storeId;
        $data['channel_id'] = $channelId;
        $data['create_time'] = NOW_TIME;
        $data['year'] = date('Y');
        $data['month'] = date('m');
        $data['day'] = date('d');
        $data['client_type'] = empty($client) ? 'wap' : $client;
        $http = is_ssl() ? 'https' : 'http';
        $data['page_tag'] = "{$http}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
        $result = $this->addData([], $data);
        if ($result['code'] !== 200) return $result;
        // 浏览量+1
        $data = [];
        $data['browse_vol'] = ['exp', 'browse_vol+1'];
        $data['version'] = M('mb_goods_exp')->max('version') + 1;
        M('mb_goods_exp')->where(['goods_id'=>$goodsId])->save($data);
        return getReturn(200, '');
    }

    /**
     * 获取最近一个月浏览的商品ID数组
     * @param int $storeId
     * @param int $memberId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-05-02 11:18:07
     * Update: 2018-05-02 11:18:07
     * Version: 1.00
     */
    public function getBrowGoodsIdsNearMonth($storeId = 0, $memberId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['member_id'] = $memberId;
        $start = NOW_TIME - 3600 * 24 * 30;
        $end = NOW_TIME;
        $where['create_time'] = ['between', "{$start},{$end}"];
        $list = $this->field('goods_id')->where($where)->select();
        if (empty($list)) {
            return [];
        }
        $ids = [];
        foreach ($list as $value) {
            if (!in_array($value['goods_id'], $ids)) {
                $ids[] = $value['goods_id'];
            }
        }
        return $ids;
    }

    /*获取最近n个月浏览商品的记录
	 * @param int $storeId 店铺ID
     * @param int $memberId 用户ID
     * @param int $month 月数
     * @param array $w 附加条件
	*/
    public function getBrowGoodsInfo($storeId = 0, $memberId = 0,$month = 1,$w = array()){
        $where = [];
        $where['xunxin_mb_goods_browsing_history.store_id'] = $storeId;
        $where['xunxin_mb_goods_browsing_history.member_id'] = $memberId;
        $start = NOW_TIME - 3600 * 24 * 30 * $month;
        $end = NOW_TIME;
        $where['xunxin_mb_goods_browsing_history.create_time'] = ['between', "{$start},{$end}"];
        if(!empty($w)){
            $where['_complex'] = $w;
        }
        $lists = $this->join('LEFT JOIN xunxin_goods_extra ON xunxin_goods_extra.goods_id = xunxin_mb_goods_browsing_history.goods_id')->where($where)->field('xunxin_mb_goods_browsing_history.*,xunxin_goods_extra.goods_name,xunxin_goods_extra.goods_img,xunxin_goods_extra.goods_class_1')->order('xunxin_mb_goods_browsing_history.create_time DESC')->select();
        if(!empty($lists)){
            foreach($lists as &$list){
                $list['img'] = json_decode($list['goods_img'],true)[0]['url'];
            }
        }
        return empty($lists) ? [] : $lists;
    }
}