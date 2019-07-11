<?php

namespace Common\Model;
class GoodsExtraModel extends BaseModel
{
    protected $tableName = 'goods_extra';

    // region 门店
    const SPEC_TYPE_NONE = -1; // 无规格
    const SPEC_TYPE_COMMON = 1; // 通用多规格
    const SPEC_TYPE_GROUP = 2; // 多规格组合

    /**
     * 获取商品规格的价格
     * @param array $goods
     * @param int $primaryId
     * @return double
     * User: hjun
     * Date: 2018-11-06 23:31:17
     * Update: 2018-11-06 23:31:17
     * Version: 1.00
     */
    public function getGoodsSpecPrice($goods = [], $primaryId = 0)
    {
        $priceField = 'spec_price';
        if ($goods['is_qinggou'] == 1 || $goods['is_promote'] == 1) {
            $priceField = 'spec_promote_price';
        }
        if (is_string($goods['spec_attr'])) {
            $goods['spec_attr'] = jsonDecodeToArr($goods['spec_attr']);
        }
        foreach ($goods['spec_attr'] as $spec) {
            if ($spec['primary_id'] == $primaryId) {
                return $spec[$priceField];
            }
        }
        return 0;
    }

    /**
     * 获取商品的范围价格
     * @param array $goods
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-10-31 12:15:15
     * Update: 2018-10-31 12:15:15
     * Version: 1.00
     */
    public function getGoodsRangePrice($goods = [])
    {
        $minField = 'min_goods_price';
        $maxField = 'max_goods_price';
        if ($goods['is_qinggou'] == 1 || $goods['is_promote'] == 1) {
            $minField = 'min_promote_price';
            $maxField = 'max_promote_price';
        }
        $rangePrice = $goods[$minField] == $goods[$maxField] ?
            $goods[$minField] : "{$goods[$minField]}~{$goods[$maxField]}";
        return $rangePrice;
    }

    /**
     * 获取商品主图
     * @param array $goods
     * @return string
     * User: hjun
     * Date: 2018-10-17 16:14:34
     * Update: 2018-10-17 16:14:34
     * Version: 1.00
     */
    protected function getGoodsMainImg($goods = [])
    {
        if (!empty($goods['goods_img'])) {
            $img = $goods['goods_img'];
            if (is_string($goods['goods_img'])) {
                $img = jsonDecodeToArr($goods['goods_img']);
            }
        } else {
            $img = $goods['goods_fig'];
            if (is_string($goods['goods_fig'])) {
                $img = jsonDecodeToArr($goods['goods_fig']);
            }
        }
        return $img[0]['url'];
    }

    /**
     * 解析规格
     * @param string $specAttr
     * @return array
     * User: hjun
     * Date: 2018-10-17 16:12:42
     * Update: 2018-10-17 16:12:42
     * Version: 1.00
     */
    protected function decodeSpecAttr($specAttr = '')
    {
        if (is_array($specAttr)) {
            return $specAttr;
        }
        return jsonDecodeToArr($specAttr);
    }

    /**
     * 获取商品
     * @param int $goodsId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-10-17 16:13:40
     * Update: 2018-10-17 16:13:40
     * Version: 1.00
     */
    public function getGoods($goodsId = 0)
    {
        $where = [];
        $where['goods_id'] = $goodsId;
        $where['goods_delete'] = 0;
        $options = [];
        $options['where'] = $where;
        $info = $this->selectRow($options);
        if (!empty($info)) {
            $this->auto([
                ['spec_attr', 'decodeSpecAttr', self::MODEL_BOTH, 'callback', [$info['spec_attr']]],
                ['main_img', 'getGoodsMainImg', self::MODEL_BOTH, 'callback', [$info]],
            ]);
            $this->autoOperation($info, self::MODEL_UPDATE);
        }
        return $info;
    }

    /**
     * 获取搜索条件
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2018-10-30 18:16:01
     * Update: 2018-10-30 18:16:01
     * Version: 1.00
     */
    public function getSearchWhere($request = [])
    {
        $where = [];
        if (!empty($request['goods_id'])) {
            $where['a.goods_id'] = $request['goods_id'];
        }
        if (!empty($request['goods_name'])) {
            $where['a.goods_name'] = ['like', "%{$request['goods_name']}%"];
        }
        if (!empty($request['goods_class'])) {
            $class = explode('|', $request['goods_class']);
            $level = count($class);
            $where["a.goods_class_{$level}"] = $class[$level - 1];
        }
        return getReturn(CODE_SUCCESS, 'success', $where);
    }

    /**
     * 获取选择商品列表的数据
     * @param int $pickupId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array
     * User: hjun
     * Date: 2018-10-17 17:52:14
     * Update: 2018-10-17 17:52:14
     * Version: 1.00
     */
    public function getPickupGoodsSelectData($pickupId, $page = 1, $limit = 20, $condition = [])
    {
        // 商城要查询所有子店的商品
        $queryId = D('Store')->getStoreQueryId($this->getStoreId(), 2)['data'];
        $queryId = explode(',', $queryId);
        $where = [];
        $where['a.store_id'] = getInSearchWhereByArr($queryId);
        $where['a.spec_type'] = self::SPEC_TYPE_GROUP;
        $where['a.goods_delete'] = NOT_DELETE;
        $where = array_merge($where, $condition);
        $order = 'a.top DESC,a.sort DESC';
        $options = [];
        $options['alias'] = 'a';
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = $order;
        $data = $this->queryList($options)['data'];
        $cantSelectList = D('DepotGoods')->getCantSelectGoodsSpec($pickupId);
        foreach ($data['list'] as $key => $value) {
            $this->auto([
                ['spec_attr', 'decodeSpecAttr', self::MODEL_BOTH, 'callback', [$value['spec_attr']]],
                ['new_price', $this->getGoodsRangePrice($value), self::MODEL_BOTH, 'string'],
            ]);
            $this->autoOperation($data['list'][$key], self::MODEL_UPDATE);
            setGoodsIsSelect($data['list'][$key], $cantSelectList);
        }
        return $data;
    }
    // endregion

    /**
     * 处理数据
     * @param array $info
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-25 14:36:11
     * Update: 2017-12-25 14:36:11
     * Version: 1.00
     */
    public function transformInfo($info = [], $condition = [])
    {
        $info = parent::transformInfo($info, $condition);
        // 商品价格
        if (isset($info['min_goods_price']) && isset($info['max_goods_price'])) {
            $minPriceName = 'min_goods_price';
            $maxPriceName = 'max_goods_price';
        }
        if (isset($info['is_qinggou']) || isset($info['is_promote'])) {
            if ($info['is_promote'] == 1 || $info['is_qinggou'] == 1) {
                $minPriceName = 'min_promote_price';
                $maxPriceName = 'max_promote_price';
            }
        }
        if (isset($maxPriceName) && isset($minPriceName)) {
            $minPrice = round($info[$minPriceName], 2);
            $maxPrice = round($info[$maxPriceName], 2);
            $goodsPrice = $minPrice == $maxPrice ?
                $minPrice : "{$maxPrice}-{$maxPrice}";
            $info['goods_price'] = $goodsPrice;
        }

        // 库存
        if (isset($info['all_stock'])) {
            $info['all_stock'] = $info['all_stock'] == -1 ? "充足" : $info['all_stock'];
        }

        // 图片
        if (isset($info['goods_img']) || isset($info['goods_fig'])) {
            $goodsImg = json_decode($info['goods_img'], 1);
            if (empty($goodsImg)) {
                $goodsImg = json_decode($info['goods_fig'], 1);
            }
            $info['goods_img'] = empty($goodsImg[0]['url']) ? '' : "{$goodsImg[0]['url']}";
        }

        // 商品规格的成本
        if (isset($info['spec_attr'])) {
            if ($info['spec_type'] == -1) {
                $info['goods_cost'] = $info['goods_price'] - $info['goods_pv'];
            }
            foreach ($info['spec_attr'] as $key => $value) {
                $info['spec_attr'][$key]['goods_cost'] = $value['spec_price'] - $value['spec_goods_pv'];
                // 每个规格的价格表的价格
                if (isset($condition['price_id']) && $condition['price_id'] > 0) {
                    $where = [];
                    $where['store_group_price_id'] = $condition['price_id'];
                    $where['goods_id'] = $info['goods_id'];
                    $where['spec_id'] = $value['spec_id'];
                    $agentPrice = D('StoreGroupPriceData')->getGroupGoodsSpecPrice($condition['price_id'], $info['goods_id'], $value['spec_id']);
                    $info['spec_attr'][$key]['agent_price'] = empty($agentPrice) ? $value['spec_price'] : $agentPrice;
                    $info['spec_attr'][$key]['agent_price'] = (double)$info['spec_attr'][$key]['agent_price'];
                } elseif (isset($condition['from']) && $condition['from'] === 'agent') {
                    switch ((int)$condition['discount_type']) {
                        case 0:
                            $info['spec_attr'][$key]['agent_price'] = round($value['spec_price'] * ($condition['discount'] / 10), 2);
                            break;
                        case 1:
                            $pv = round($value['spec_goods_pv'] * ($condition['discount'] / 10), 2);
                            $info['spec_attr'][$key]['agent_price'] = $info['spec_attr'][$key]['goods_cost'] + $pv;
                            break;
                        default:
                            $info['spec_attr'][$key]['agent_price'] = (double)$value['spec_price'];
                            break;
                    }
                } else {
                    $info['spec_attr'][$key]['agent_price'] = (double)$value['spec_price'];
                }
            }
        }


        return $info;
    }

    /**
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取商品列表供选择团购
     * Date: 2017-11-16 23:00:32
     * Update: 2017-11-16 23:00:33
     * Version: 1.0
     */
    public function getChoiceGroupGoodsList($storeId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        $where['a.store_id'] = $storeId;
        $where['a.goods_delete'] = 0;
        $where['a.goods_state'] = 1;
        $where = array_merge($where, $condition);
        $field = [
            'a.goods_id,a.goods_name,a.goods_img,a.goods_fig,a.min_goods_price,a.max_goods_price',
            'a.min_stock,a.max_stock,a.spec_attr,a.is_promote,a.min_promote_price',
            'a.max_promote_price,b.goods_edittime edit_time'
        ];
        $field = implode(',', $field);
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = [
            '__GOODS__ b ON a.goods_id = b.goods_id'
        ];
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = 'b.top DESC,b.sort DESC';
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $total = $result['data']['total'];
        // 查询团购未结束的的商品ID数组 判断列表商品哪些正在团购 做标记
        $where = [];
        $where['close_status'] = 1;
        $where['end_time'] = ['gt', NOW_TIME];
        $where['is_delete'] = 0;
        $model = D('GroupBuy');
        $options = [];
        $options['where'] = $where;
        $result = $model->queryField($options, 'goods_id', true);
        if ($result['code'] !== 200) return $result;
        $goodsId = $result['data'];
        foreach ($list as $key => $value) {
            $goodsImg = json_decode($value['goods_img'], 1);
            if (empty($goodsImg)) {
                $goodsImg = json_decode($value['goods_fig'], 1);
            }
            $list[$key]['goods_img'] = empty($goodsImg[0]['url']) ? '' : "{$goodsImg[0]['url']}?_100x100x2";
            $list[$key]['in_group'] = in_array($value['goods_id'], $goodsId) ? 1 : -1;
            $list[$key]['spec_attr'] = empty($value['spec_attr']) ? "" : json_decode($value['spec_attr'], 1);
            $list[$key]['edit_time'] = date("Y-m-d H:i:s", $value['edit_time']);
            $list[$key]['goods_stock'] = $value['min_stock'] == $value['max_stock'] ?
                ($value['min_stock'] == -1 ? "充足" : $value['min_stock']) :
                (($value['min_stock'] == -1 ? "充足" : $value['min_stock']) . "~" . ($value['max_stock'] == -1 ? "充足" : $value['max_stock']));
            $minField = $value['is_promote'] == 1 ? 'min_promote_price' : 'min_goods_price';
            $maxField = $value['is_promote'] == 1 ? 'max_promote_price' : 'max_goods_price';
            $list[$key]['goods_price'] = $value[$minField] == $value[$maxField] ?
                $value[$minField] : "{$value[$minField]}~{$value[$maxField]}";
        }
        $data = [];
        $data['list'] = $list;
        $data['total'] = $total;
        return getReturn(200, '', $data);
    }

    /**
     * @param
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取商品信息
     * Date: 2017-11-28 23:28:19
     * Update: 2017-11-28 23:28:19
     * Version: 1.0
     */
    public function getGoodsListInfo($goodsId)
    {
        if (empty($goodsId)) return getReturn(-1, L('INVALID_PARAM'));
        $where = [];
        $where['goods_id'] = is_array($goodsId) ? ['in', implode(',', $goodsId)] : $goodsId;
        $where['goods_delete'] = 0;
        $where['goods_state'] = 1;
        $options = [];
        $options['where'] = $where;
        $options['field'] = 'goods_id,store_id,goods_name,is_qinggou,is_promote,is_hot,start_time,end_time';
        return $this->queryList($options);
    }

    /**
     * 优惠券指定可用商品 商品列表
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-11 14:48:11
     * Update: 2017-12-11 14:48:11
     * Version: 1.00
     */
    public function getChooseCouponsList($storeId = 0, $page = 1, $limit = 20, $condition = [])
    {
        $where = [];
        $where['goods_delete'] = 0;
        $where['goods_state'] = 1;
        if ($storeId > 0) $where['store_id'] = $storeId;
        $where = array_merge($where, $condition);
        $options = [];
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['field'] = 'goods_id,goods_name,goods_img,create_time';
        $options['order'] = 'create_time DESC';
        $result = $this->queryList($options);
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $img = json_decode($value['goods_img'], 1);
            $list[$key]['goods_img'] = empty($img) ? '' : "{$img[0]['url']}?_100x100x2";
            $list[$key]['create_time_string'] = date('Y-m-d H:i:s', $value['create_time']);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 获取商品列表 基本信息 价格、库存、图片、名称
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @param array $otherOptions
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-25 14:53:05
     * Update: 2017-12-25 14:53:05
     * Version: 1.00
     */
    public function getGoodsList($storeId = 0, $page = 1, $limit = 20, $condition = [], $otherOptions = [])
    {
        $where = [];
        $where['goods_delete'] = 0;
        $where['goods_state'] = 1;
        if ($storeId > 0) $where['store_id'] = $storeId;
        $where = array_merge($where, $condition);
        $field = [
            'goods_id,goods_name,goods_img,goods_fig,all_stock,is_qinggou,is_promote',
            'min_goods_price,max_goods_price,min_promote_price,max_promote_price'
        ];
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options = array_merge($options, $otherOptions);
        $result = $this->queryList($options);
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 获取价格表的商品列表
     * @param int $priceId
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @param array $other
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-12 11:16:43
     * Update: 2018-01-12 11:16:43
     * Version: 1.00
     */
    public function getGroupPriceGoodsList($priceId = 0, $storeId = 0, $page = 1, $limit = 0, $condition = [], $other = [])
    {
        $where = [];
        $where['a.goods_delete'] = 0;
        $where['a.store_id'] = $storeId;
        $where = array_merge($where, $condition);
        $field = [
            'a.goods_id,a.goods_img,a.goods_fig,a.spec_type',
            'a.store_id,a.goods_name,a.goods_spec,a.spec_attr',
            'a.goods_pv,a.min_goods_price,a.max_goods_price'
        ];
        switch ($other['sort_type']) {
            case 1:
                $order = 'a.create_time ASC';
                break;
            case 2:
                $order = 'a.create_time DESC';
                break;
            default:
                $order = 'a.top DESC,a.sort DESC';
                break;
        }
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = implode(',', $field);
        $options['join'] = ['__GOODS__ b ON a.goods_id = b.goods_id'];
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = $order;
        $options['where'] = $where;
        $result = $this->queryList($options);
        $list = $result['data']['list'];
        $condition = [];
        $condition['price_id'] = $priceId;
        $condition['callback_field'] = [
            'spec_attr' => ['spec_length', 'getSpecLength']
        ];
        $condition['json_field'] = ['spec_attr', 'goods_spec'];
        $condition = array_merge($condition, $other);
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value, $condition);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    public function getSpecLength($specArr = [])
    {
        if (empty($specArr)) return 0;
        if (!empty($specArr['spec_id_-1'])) return 0;
        return count($specArr);
    }

    /**
     * 获取限时抢购商品
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-09 11:59:35
     * Update: 2018-03-09 11:59:35
     * Version: 1.00
     */
    public function getQiangGoodsList($storeId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $field = [
            'goods_id', 'goods_img', 'goods_fig', 'goods_name',
            'min_goods_price', 'max_goods_price', 'min_promote_price',
            'max_promote_price', 'end_time'
        ];
        $field = implode(',', $field);
        $where = [];
        $where['store_id'] = $storeId;
        $where['goods_delete'] = 0;
        $where['goods_state'] = 1;
        $where['is_qinggou'] = 1;
        $where['end_time'] = ['gt', NOW_TIME];
        $where['start_time'] = ['elt', NOW_TIME];
        $where = array_merge($where, $condition);
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = 'top DESC,sort DESC';
        $result = $this->queryList($options);
        $list = $result['data']['list'];
        $condition = [];
        $condition['json_field'] = ['goods_fig', 'goods_img'];
        foreach ($list as $key => $value) {
            $value = $this->transformInfo($value, $condition);
            $image = empty($value['goods_img'][0]['url']) ? '' : $value['goods_img'][0]['url'];
            $image = empty($image) ? $value['goods_fig'][0]['url'] : $image;
            $value['sheng_yu_time'] = $value['end_time'] - NOW_TIME;
            $value['main_img'] = $image;
            $minField = 'min_goods_price';
            $maxField = 'max_goods_price';
            $value['goods_price'] = $value[$minField] == $value[$maxField] ?
                $value[$minField] : "{$value[$minField]}~{$value[$maxField]}";
            $minField = 'min_promote_price';
            $maxField = 'max_promote_price';
            $value['new_price'] = $value[$minField] == $value[$maxField] ?
                $value[$minField] : "{$value[$minField]}~{$value[$maxField]}";
            $list[$key] = $value;
        }
        $result['data']['list'] = $list;
        return $result;
    }
}