<?php

namespace Common\Logic;

use Think\Cache\Driver\Redis;

/**
 * Class CartService
 * 收银机购物车 模型
 * @package Api\Service
 * User: hjun
 * Date: 2018-01-25 12:06:07
 */
class CashCartLogic extends BaseLogic
{
    const INIT_TYPE_ALL = 'all';
    const INIT_TYPE_ONLY_PRICE = 'only_price';

    // 当前访问的商家ID
    private $visitStoreId = 0;
    // 购物车的商家ID
    private $cartStoreId;
    // 访问的商家的类型
    private $visitStoreType = 0;
    // 会员ID
    private $memberId;
    // 购物车数据
    private $items = array();
    //
    private $oldItems = [];
    //
    private $storeInfo = [];
    private $storeGrant = [];
    // 版本号
    private $version = 0;
    /*
     * 额外数据
     * {
         "freight_type": 0, //0-快递配送 1-上门自提
         "pickup_ids": [
            "149": {
              "store_id": 148,
              "pick_id": 1
            },
            "150": {
              "store_id": 149,
              "pick_id": 2
            }
          ]
        }
     */
    private $extra = '';

    private $canSave = true;

    private $noPickupDesc = '';

    /**
     * @return string
     */
    public function getNoPickupDesc()
    {
        return empty($this->noPickupDesc) ? '' : $this->noPickupDesc;
    }

    /**
     * @param string $noPickupDesc
     */
    public function setNoPickupDesc($noPickupDesc)
    {
        $this->noPickupDesc = $noPickupDesc;
    }

    /**
     * CartService constructor.
     * 构造函数
     * @param int $storeId
     * @param int $memberId
     * @param string $name
     * @param string $tablePrefix
     * @param string $connection
     */
    public function __construct($storeId = 0, $memberId = 0, $name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);

        if (!($storeId > 0)) {
            exit(json_encode(getReturn(-1, 'GWX-缺少商家编号'), JSON_UNESCAPED_UNICODE));
        }
        $result = D('Store')->getStoreInfo($storeId);
        if ($result['code'] !== 200) {
            exit(json_encode($result, JSON_UNESCAPED_UNICODE));
        }
        $storeInfo = $result['data'];
        $this->storeInfo = $storeInfo;
        $this->storeGrant = D('Store')->getStoreGrantInfo($storeId)['data'];
        $this->visitStoreId = $storeId;
        $this->cartStoreId = $storeInfo['main_store_id'];
        $this->memberId = $memberId;
        $this->visitStoreType = $storeInfo['store_type'];
        $cart = D('CashCart')->getCart($this->cartStoreId, $this->memberId);
        $this->items = unserialize($cart['serialized_data']);
        $this->oldItems = $this->items;
        $this->clearDirtyItems();
        $extra = json_decode($cart['extra'], 1);
        $this->extra = empty($extra) ? [] : $extra;
        $this->version = $cart['version'] > 0 ? $cart['version'] : 0;
    }

    /**
     * 添加商品
     * @param string $goods_id 商品ID
     * @param int $spec_id 规格ID
     * @param int $num 数量
     * @param array $otherData 额外数据
     * @return mixed
     */
    public function addItem($goods_id, $spec_id = 0, $num = 1, $otherData = [])
    {
        return $this->modNum($goods_id, $spec_id, $num, $otherData);
    }

    /**
     * 修改购物车中的商品数量
     * @param  string $goods_id 商品ID
     * @param string $spec_id 规格ID
     * @param int $num 某个商品修改后的数量，即直接把某商品的数量改为$num
     * @param array $otherData 额外数据
     * @return string
     */
    public function modNum($goods_id, $spec_id, $num, $otherData = [])
    {
        $num = (double)$num;
        if ($num <= 0) {
            // hj 2017-10-17 16:31:18 如果数量为0的话 直接删除该商品
            unset($this->items[$goods_id . '|' . $spec_id]);
        } else {

            $goodsbean = M("goods")->field("goods_spec, spec_open, goods_storage, limit_buy,isdelete,goods_state")->where(array(
                'goods_id' => $goods_id
            ))->find();
            if ($this->isDown($goodsbean)) {
                return '商品已经下架';
            }
            $buy_history = 0;
            $buy_data = M("mb_limit_buy")->where(array(
                'member_id' => $this->memberId,
                'goods_id' => $goods_id
            ))->find();
            if (!empty($buy_data) && !empty($buy_data['buy_num'])) {
                $buy_history = $buy_data['buy_num'];
            }
            if ($goodsbean['limit_buy'] >= 0 && $goodsbean['limit_buy'] < $buy_history + $num) {
                if ($buy_history <= 0) {
                    $this->items[$goods_id . '|' . $spec_id]['num'] = $goodsbean['limit_buy'];
                    $this->saveItem();
                    return "该商品限购" . $goodsbean['limit_buy'] . "件";
                } else if ($buy_history < $goodsbean['limit_buy']) {
                    $temp = $goodsbean['limit_buy'] - $buy_history;
                    $this->items[$goods_id . '|' . $spec_id]['num'] = $temp;
                    $this->saveItem();
                    return "该商品每人限购" . $goodsbean['limit_buy'] . "件，您已购买过" . $buy_history . "件,最多可以再买" . $temp . "件";
                } else {
                    unset($this->items[$goods_id . '|' . $spec_id]);
                    $this->saveItem();
                    return "该商品每人限购" . $goodsbean['limit_buy'] . "件，您已购买过，无法再次购买，已从购物车移除";
                }
            }

            $goods_spec_array = json_decode($goodsbean['goods_spec'], true);

            //如果是旧多规格的情况
            if ($spec_id > -1 && !empty($goods_spec_array) && $goodsbean['spec_open'] == 0) { // 如果是有商品规格的情况，就把价格和库存替换
                $goodsbean['goods_storage'] = $goods_spec_array[$spec_id]['storage'];
            }

            //如果是新多规格的情况
            if ($spec_id > -1 && $goodsbean['spec_open'] == 1) {
                $spec_detail = M('goods_option')->field('id,goods_pv,stock,goods_promoteprice,goods_price')->where("specs = '" . $spec_id . "'")->find();
                $goodsbean['goods_storage'] = $spec_detail['stock'];
                // 如果是自提 并且是独立的 库存的判断需要查询门店的库存
                $storeInfo = D('Store')->getStoreInfo($this->cartStoreId)['data'];
                if (pickupIsAlone($storeInfo) && $otherData['pickup_id'] > 0) {
                    $goodsbean['goods_storage'] = D('DepotGoods')->getGoodsStorage($otherData['pickup_id'], $goods_id, $spec_detail['id']);
                }
            }
            if ($goodsbean['goods_storage'] != -1 && $goodsbean['goods_storage'] < $num) {
                if ($goodsbean['goods_storage'] == 0) {
                    unset($this->items[$goods_id . '|' . $spec_id]);
                    $this->saveItem();
                    return "库存不足，已从购物车移除";
                } else {
                    $this->items[$goods_id . '|' . $spec_id]['num'] = $goodsbean['goods_storage'];
                    $this->saveItem();
                    return "库存剩余" . $goodsbean['goods_storage'];
                }
            }


            if ($this->hasItem($goods_id . '|' . $spec_id)) {
                $this->items[$goods_id . '|' . $spec_id]['num'] = $num;
                if (!empty($otherData['mj_id'])) {
                    $this->setMjId($goods_id, $spec_id, $otherData['mj_id']);
                } elseif ($otherData['from_type'] == 1) {
                    $this->setMjId($goods_id, $spec_id, $this->items[$goods_id . '|' . $spec_id]['mj_id'], -1, false, true, $num);
                }
            } else {
                $item = array();
                $item['num'] = $num;
                $item['state'] = 1;
                $this->items[$goods_id . '|' . $spec_id] = $item;
                // hjun 第一次加入购物车设置mj_id
                $mjId = empty($otherData['mj_id']) ? 0 : $otherData['mj_id'];
                $this->setMjId($goods_id, $spec_id, $mjId, -1, false, true, $num);
            }
        }

        $this->saveItem();
        return "";
    }

    /**
     * 修改购物车中的商品的选中状态
     * @param string $goods_id 商品ID
     * @param int $spec_id 规格ID
     * @param string $state 某个商品修改后的状态，1为选中 0为未选中
     */
    public function modState($goods_id, $spec_id = 0, $state)
    {
        if ($this->hasItem($goods_id . '|' . $spec_id)) {
            $this->items[$goods_id . '|' . $spec_id]['state'] = $state;
            $this->saveItem();
        }
    }


    /**
     * 商品数量减少
     * @param string $goods_id 商品ID
     * @param int $spec_id 规格ID
     * @param $num
     * @return bool
     */
    public function decNum($goods_id, $spec_id = 0, $num = 0)
    {
        return $this->modNum($goods_id, $spec_id, $num);
    }

    /**
     * 商品数量编辑
     * @param string $goods_id 商品ID
     * @param int $spec_id 规格ID
     * @param string $num
     * @return bool $bool
     */
    public function editNum($goods_id, $num, $spec_id = 0)
    {
        return $this->modNum($goods_id, $spec_id, $num);
    }

    /**
     * 删除商品
     * @param $gs_id 商品ID|规格ID
     */
    public function delItem($gs_id)
    {
        unset($this->items[$gs_id]);
        $this->saveItem();
    }

    /**
     * 批量删除商品
     * @param 存放（ $gs_id 商品ID|规格ID） 的数组
     */
    public function delItems($gs_id_arr)
    {
        for ($i = 0; $i < count($gs_id_arr); $i++) {
            unset($this->items[$gs_id_arr[$i]]);
        }
        $this->saveItem();
    }

    /**
     * 判断商品是否存在
     * @param string $id 商品ID|规格ID
     * @return bool
     */
    public function hasItem($id)
    {
        return array_key_exists($id, $this->items);
    }

    /**
     * 更改规格
     * @param $old_gs_id 旧 商品ID|规格ID
     * @param $new_gs_id 新 商品ID|规格ID
     */
    public function changeSpec($old_gs_id, $new_gs_id)
    {
        if ($this->hasItem($old_gs_id)) {
            $this->items[$new_gs_id] = $this->items[$old_gs_id];
            unset($this->items[$old_gs_id]);
            $this->saveItem();
        }
    }

    /**
     * 设置商品参加的活动
     * @param int $goodsId
     * @param string $specId
     * @param int $mjId
     * @param int $level 达到的等级 -1表示新加入购物车 判断当前商品达到的等级
     * @param bool $doSave
     * @param bool $doQuery
     * @param int $num 首次加入购物车的时候会传入该参数
     * @return mixed
     * User: hjun
     * Date: 2018-01-02 09:17:35
     * Update: 2018-01-02 09:17:35
     * Version: 1.00
     */
    public function setMjId($goodsId = 0, $specId = '', $mjId = 0, $level = 0, $doSave = true, $doQuery = true, $num = 0)
    {
        $gsId = "{$goodsId}|{$specId}";
        if ($this->hasItem($gsId)) {
            if ($doQuery) {
                // 查询满减活动
                $model = D('MjActivity');
                $where = [];
                // 加入购物车时 或者 指定了满减活动
                if ($mjId > 0) $where['mj_id'] = $mjId;
                if ($mjId > 0 || $level == -1) {
                    if ($this->isMall()) {
                        $storeId = D('Goods')->getGoodsStoreId($goodsId);
                    } else {
                        $storeId = $this->visitStoreId;
                    }
                    $mjInfo = $model->getGoodsMjInfo($storeId, 0, $goodsId, $where)['data'];
                }
            }

            $this->items[$gsId]['mj_id'] = empty($mjInfo) ? $mjId : (int)$mjInfo['mj_id'];
            if ($level >= 0) {
                $this->items[$gsId]['mj_level'] = (int)$level;
            } else {
                // 新加入购物车的时候要判断商品达到的等级
                if (!empty($mjInfo) && $level == -1) {
                    $model = D('Goods');
                    $goodsInfo = $model->getGoodsBeanById($goodsId);
                    $goodsBeans = [$goodsInfo];
                    $goodsBeans = $this->initGoodsBeans($goodsBeans);
                    $result = $this->getGoodsListByItems($goodsBeans);
                    $goodsInfo = $result[0];
                    $totalPrice = $goodsInfo['new_price'] * 100 * $num;
                    $totalNum = $num;
                    $detail = $model->getMjDetailByLevel($totalPrice, $totalNum, $mjInfo);
                    $level = $detail['level'];
                    $this->items[$gsId]['mj_level'] = (int)$level;
                }
            }
        }
        if ($doSave) {
            $this->saveItem();
        }
    }

    /**
     * 保存购物车
     * @return mixed
     * User: hjun
     * Date: 2018-01-02 10:06:20
     * Update: 2018-01-02 10:06:20
     * Version: 1.00
     */
    public function saveItem()
    {
        if (!$this->canSave) return true;
        $model = D('CashCart');
        if (!empty($this->cartStoreId) && !empty($this->memberId)) {
            $shop_cart_data = $model->where(array('member_id' => $this->memberId, 'store_id' => $this->cartStoreId))->find();
            $data = [];
            $data['serialized_data'] = serialize($this->items);
            if (!empty($shop_cart_data)) {
                // 如果数据发生了改变 才更新数据库
                $old = serialize($this->oldItems);
                if ($old != $data['serialized_data']) {
                    // 不是app才更新版本号
                    $req = getRequest();
                    if (!($req['client'] == 'ios' || $req['client'] == 'android')) {
                        $data['version'] = ++$this->version;
                    }
                    $result = $model->where(array('member_id' => $this->memberId, 'store_id' => $this->cartStoreId))->save($data);
                    // 更新后同步旧数据 下次比较才是正确的
                    if ($result !== false) {
                        $this->oldItems = $this->items;
                    }
                    return $result;
                }
            } else {
                $data['member_id'] = $this->memberId;
                $data['store_id'] = $this->cartStoreId;
                return $model->add($data);
            }
        }
    }

    /**
     * 商品种类
     * @return int
     */
    public function getCnt()
    {
        return count($this->items);
    }

    /**
     * 查询购物车中商品的个数
     * @return int
     */
    public function getNum()
    {
        if ($this->getCnt() == 0) {
            return 0;
        }
        $sum = 0;
        foreach ($this->items as $key => $item) {
            $ids = explode("|", $key);
            $specStr = $ids[1];
            // hj 2018-01-04 18:21:13 新增查出规格字段
            $delete = M('goods')->where(array('goods_id' => $ids[0]))->field('isdelete,goods_state,spec_open,goods_spec')->find();
            $goodsSpec = json_decode($delete['goods_spec'], 1);
            $specOption = [];
            if (!empty($goodsSpec) && $delete['spec_open'] == 0) {
                // 如果没开启多规格 并且 规格字段不空 则把规格ID存起来
                foreach ($goodsSpec as $k => $val) {
                    $specOption[] = $val['spec_id'];
                }
            } elseif (empty($goodsSpec) && $delete['spec_open'] == 0) {
                $specOption[] = '0';
            } elseif ($delete['spec_open'] == 1) {
                // 如果开启了多规格 查询商品的多规格
                $specOption = D('GoodsOption')->getGoodsSpecOption($ids[0])['data'];
            }
            if ($delete['isdelete'] != 1 && $delete['goods_state'] == 1) {
                if (in_array($specStr, $specOption)) {
                    $sum += $item['num'];
                }
                /*if ($ids[1] == 0 && $delete['spec_open'] == 1) {
                } else {
                    $sum += $item['num'];
                }*/
            }
        }
        return $sum;
    }

    /**
     * 查询某个商品的购买数
     * @return int
     */
    public function getNumWithID($id)
    {
        if ($this->getCnt() == 0) {
            return 0;
        }
        if ($this->hasItem($id)) {
            return $this->items[$id]['num'];
        } else {
            return 0;
        }
    }

    /**
     * 查询某个商品的选中状态
     * @return int
     */
    public function getStateWithID($id)
    {
        if ($this->hasItem($id)) {
            return $this->items[$id]['state'];
        } else {
            return 0;
        }
    }

    /**
     * 查询某个商品的活动满减id
     * author czx
     */
    public function getMjWithId($id)
    {
        if ($this->hasItem($id)) {
            if (empty($this->items[$id]['mj_id'])) {
                return 0;
            } else {
                return $this->items[$id]['mj_id'];
            }
        } else {
            return 0;
        }
    }

    /**
     * 查询某个商品的活动满减等级
     * author czx
     */
    public function getMjLevelWithId($id)
    {
        if ($this->hasItem($id)) {
            if (empty($this->items[$id]['mj_level'])) {
                return 0;
            } else {
                return $this->items[$id]['mj_level'];
            }
        } else {
            return 0;
        }
    }


    /**
     * 再次下单
     */
    public function onceAgain($items)
    {
        // hjun 2018-04-20 14:45:44  增加失败结果的返回
        $goodsNum = count($items);
        $failedData = [];
        foreach ($items as $item) {
            if (empty($item['specid']) || $item['specid'] == 'NULL') {
                $result = $this->addItem($item['goods_id'], 0, $item['gou_num']);
            } else {
                $result = $this->addItem($item['goods_id'], $item['specid'], $item['gou_num']);
            }
            if (!empty($result)) {
                $error = [];
                $error['goods_id'] = $item['goods_id'];
                $error['specid'] = (empty($item['specid']) || $item['specid'] == 'NULL') ? 0 : $item['specid'];
                $error['msg'] = $result;
                $failedData[] = $error;
            }
        }
        $failedNum = count($failedData);
        // 如果全部商品都失败了
        if ($failedNum >= $goodsNum) {
            return getReturn(406, 'failed', $failedData);
        } else {
            return getReturn(200, 'success', $failedData);
        }
    }

    /**
     * 返回购物车中的所有商品
     */
    public function getAll()
    {
        return $this->items;
    }

    /**
     * 返回选中的购物车的商品
     */
    public function getSelectAllGoodsId()
    {

        $select_goods = array();
        foreach ($this->items as $key => $val) {
            if ($val['state'] == 1) {
                $select_goods[] = $key;
            }
        }
        return $select_goods;
    }

    /**
     * 清空购物车
     */
    public function clear()
    {
        $this->items = array();
        $this->saveItem();
    }

    /**
     * 所有商品选中状态变更
     * @param int $state 1选中 0不选
     * @return mixed
     * User: hjun
     * Date: 2018-01-02 15:37:04
     * Update: 2018-01-02 15:37:04
     * Version: 1.00
     */
    public function modAllState($state = 1)
    {
        if (!empty($this->items)) {
            foreach ($this->items as $key => $val) {
                $this->items[$key]['state'] = (int)$state > 0 ? 1 : 0;
            }
            return $this->saveItem();
        }
        return true;
    }

    /**
     * 批量修改商品选中状态
     * @param array $gsId
     * @param int $state
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-02 15:46:27
     * Update: 2018-01-02 15:46:27
     * Version: 1.00
     */
    public function modMultiState($gsId = [], $state = 1)
    {
        foreach ($gsId as $key => $value) {
            if ($this->hasItem($value)) {
                $this->items[$value]['state'] = (int)$state > 0 ? 1 : 0;
            }
        }
        return $this->saveItem();
    }

    /**
     * 获取购物车商品详细数据列表
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-25 18:10:22
     * Update: 2018-01-25 18:10:22
     * Version: 1.00
     */
    public function getShopCartGoodsList()
    {
        $items = $this->items;
        $goods_id_array = array();
        foreach ($items as $k => $v) {
            $ids = explode('|', $k);
            if (!array_key_exists($ids[0], $goods_id_array)) {
                $goods_id_array[$ids[0]] = $ids[0];
            }
        }
        $id_array = array();
        foreach ($goods_id_array as $id) {
            if (!empty($id) && $id != 'undefined') {
                $id_array[] = $id;
            }
        }
        if (empty($id_array)) return [];
        $where = [];
        $where['isdelete'] = 0;
        $where['goods_state'] = 1;
        $where['goods_id'] = ['in', implode(',', $id_array)];
        $goodsBeans = D('Goods')->getGoodsListByWhere($where);
        if (empty($goodsBeans)) return [];

        $goodsBeans = $this->initGoodsBeans($goodsBeans);
        $result = $this->getGoodsListByItems($goodsBeans);
        return $result;
    }

    public function isInitGoodsClass($initType = self::INIT_TYPE_ALL)
    {
        if ($initType === self::INIT_TYPE_ALL) {
            return true;
        }
        return false;
    }

    public function isInitGoodsIsHot($initType = self::INIT_TYPE_ALL)
    {
        if ($initType === self::INIT_TYPE_ALL) {
            return true;
        }
        return false;
    }

    public function isInitGoodsSupplierAgent($initType = self::INIT_TYPE_ALL)
    {
        if ($initType === self::INIT_TYPE_ALL) {
            return true;
        }
        return false;
    }

    public function isInitGoodsSaleNum($initType = self::INIT_TYPE_ALL)
    {
        if ($initType === self::INIT_TYPE_ALL) {
            return true;
        }
        return false;
    }

    /**
     * 商品初始化
     * @param $goodsBeans
     * @param int $type
     * @param string $initType 初始化的内容类型
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-25 18:21:45
     * Update: 2018-01-25 18:21:45
     * Version: 1.00
     */
    public function initGoodsBeans($goodsBeans, $type = 0, $initType = self::INIT_TYPE_ALL)
    {
        // 如果超过一个月未续费 商品查出来为空即可
        if ((NOW_TIME - $this->storeInfo['vip_endtime']) >= 3600 * 24 * 30) {
            return [];
        }
        $cartTool = $this;
        $memberId = $this->memberId;
        $goodsPriceUtil = new GoodsPriceLogic();
        for ($i = 0; $i < count($goodsBeans); $i++) {
            // 如果是供应商提供的商品 商家ID要选择当前访问的商家
            $tempStoreId = $goodsBeans[$i]['store_id'];
            if ($this->isSupplierGoods($goodsBeans[$i])) {
                $tempStoreId = $this->visitStoreId;
            }

            // hjun
            $goodsBeans[$i]['buy_num'] = 0;

            if (!is_array($goodsBeans[$i]['goods_image'])) {
                $images = json_decode($goodsBeans[$i]['goods_image'], true);
            } else {
                $images = $goodsBeans[$i]['goods_image'];
            }
            if (!is_array($goodsBeans[$i]['goods_figure'])) {
                $figure = json_decode($goodsBeans[$i]['goods_figure'], true);
            } else {
                $figure = $goodsBeans[$i]['goods_figure'];
            }
            if (empty($images)) {
                $goodsBeans[$i]['images'] = array();
                $goodsBeans[$i]['images'][] = $figure[0];
                $goodsBeans[$i]['main_img'] = $figure[0]['url'];
            } else {
                $goodsBeans[$i]['main_img'] = $images[0]['url'];
                $goodsBeans[$i]['images'] = $images;
            }

            if ($type == 1) {
                $goodsBeans[$i]['img_text'] = array();
                for ($j = 1; $j < count($figure); $j++) {
                    $goodsBeans[$i]['img_text'][] = str_replace("\r\n", "<br/>", $figure[$j]);
                }
                // unset($goodsBeans[$i]['goods_figure']);
            }
            // $goodsBeans[$i]['goods_figure'] = $goodsBeans[$i]['goods_image'];
            unset($goodsBeans[$i]['goods_image']);

            if ($this->isInitGoodsClass($initType)) {
                $CLASS = M('goods_class');
                /*商品分类*/
                $class = $goodsBeans[$i]['gc_id'];
                $classes = explode('|', $class);
                $goods_class = array();

                foreach ($classes as $cla) {
                    $goods_class[] = $CLASS->where(array('gc_id' => $cla))->getField('gc_name');
                }
                $goodsBeans[$i]['goodsclass'] = $goods_class;
            }

            $goodsBeans[$i]['viplevel'] = empty($memberDiscountInfo['vip_level']) ? 0 : $memberDiscountInfo['vip_level'];
            $goodsBeans[$i]['original_goods_storage'] = $goodsBeans[$i]['goods_storage'];
            $goodsBeans[$i]['goods_storage'] = ($goodsBeans[$i]['goods_storage'] == -1) ? '充足' : $goodsBeans[$i]['goods_storage'];

            if ($this->isInitGoodsIsHot($initType)) {
                // 判断是否为热卖
                $is_hot = M('mb_goods_exp')->where(array(
                    'goods_id' => $goodsBeans[$i]['goods_id'],
                    'isdelete' => 0
                ))->find();
            }
            if (empty($is_hot) || empty($is_hot['is_hot'])) {
                $goodsBeans[$i]['is_hot'] = 0;
            } else {
                $goodsBeans[$i]['is_hot'] = 1;
            }

            if ($this->isInitGoodsSupplierAgent($initType)) {
                $discount_ratio = M('mb_supplier_agent')
                    ->field('agent_sid, agent_name, supplier_name, agent_tel, supplier_owner, supplier_sid, agent_owner, supplier_tel, discount, ratio')
                    ->where(array('supplier_sid' => $goodsBeans[$i]['store_id'], 'agent_sid' => $this->cartStoreId, 'is_delete' => '0', 'state' => '2'))
                    ->find();
            }
            if ($discount_ratio) {
                $goodsBeans[$i]['supplier_tel'] = $discount_ratio['supplier_tel'];
                $goodsBeans[$i]['agent_tel'] = $discount_ratio['agent_tel'];
                $goodsBeans[$i]['supplier_name'] = $discount_ratio['supplier_name'];
                $goodsBeans[$i]['agent_name'] = $discount_ratio['agent_name'];
                $goodsBeans[$i]['supplier_sid'] = $discount_ratio['supplier_sid'];
                $goodsBeans[$i]['agent_sid'] = $discount_ratio['agent_sid'];
                $goodsBeans[$i]['supplier_owner'] = $discount_ratio['supplier_owner'];
                $goodsBeans[$i]['agent_owner'] = $discount_ratio['agent_owner'];

                $goodsBeans[$i]['supplier_state'] = 0;
            }
            //判断是否为促销或抢购
            $sale = [];
            if ($goodsBeans[$i]['spec_open'] == 0) {    //未开启新版规格
                if (!$discount_ratio) {
                    $sale = M('mb_sales')->where(array(
                        'gid' => $goodsBeans[$i]['goods_id'],
                        'isdelete' => 0
                    ))->order('sales_id desc')->find();
                }
                if (!empty($sale) && $sale['islongtime'] == 1 && $sale['isdelete'] == 0) {
                    $state = 1;
                    $goodsBeans[$i]['new_price'] = $sale['newprice'];
                    $goodsBeans[$i]['lower_price'] = number_format($goodsBeans[$i]['goods_price'] - $sale['newprice'], 2, '.', '');
                    $goodsBeans[$i]['zhe_kou'] = number_format($sale['newprice'] * 10 / $goodsBeans[$i]['goods_price'], 1, '.', '');
                } else {
                    /*  */
                    if (time() <= $sale['end_time'] && time() >= $sale['start_time'] && $sale['isdelete'] == 0) {
                        $goodsBeans[$i]['qianggou_start_time'] = $sale['start_time'];
                        $goodsBeans[$i]['qianggou_end_time'] = $sale['end_time'];
                        $state = 2;
                        $goodsBeans[$i]['new_price'] = $sale['newprice'];
                        $goodsBeans[$i]['sheng_yu_time'] = $sale['end_time'] - time();
                        $goodsBeans[$i]['lower_price'] = number_format($goodsBeans[$i]['goods_price'] - $sale['newprice'], 2, '.', '');
                        $goodsBeans[$i]['zhe_kou'] = number_format($sale['newprice'] * 10 / $goodsBeans[$i]['goods_price'], 1, '.', '');
                    } else {
                        $state = 0;
                        if ($discount_ratio) {
                            $goodsBeans[$i]['supplier_price'] = $goodsBeans[$i]['goods_price'] - ($goodsBeans[$i]['goods_pv'] * (1.0 - $discount_ratio['discount'] / 10));
                            $goodsBeans[$i]['goods_price'] = round($goodsBeans[$i]['supplier_price'] * (1.0 + 1.0 * $discount_ratio['ratio'] / 100), 2);
                            $goodsBeans[$i]['goods_pv'] = $goodsBeans[$i]['goods_price'] - $goodsBeans[$i]['supplier_price'];
                        }
                    }
                }
            } else {        //开启新版规格
                if ($goodsBeans[$i]['is_promote'] == 1 && !$discount_ratio) {
                    $state = 1;
                    $goodsBeans[$i]['new_price'] = ($goodsBeans[$i]['goods_promoteprice'] <= 0) ? $goodsBeans[$i]['goods_price'] : number_format($goodsBeans[$i]['goods_promoteprice'], 2, '.', '');
                    $goodsBeans[$i]['lower_price'] = number_format($goodsBeans[$i]['goods_price'] - $goodsBeans[$i]['goods_promoteprice'], 2, '.', '');
                    $goodsBeans[$i]['zhe_kou'] = number_format($goodsBeans[$i]['goods_promoteprice'] * 10 / $goodsBeans[$i]['goods_price'], 1, '.', '');
                } else {
                    if (!$discount_ratio && time() <= $goodsBeans[$i]['qianggou_end_time'] && time() >= $goodsBeans[$i]['qianggou_start_time'] && $goodsBeans[$i]['is_qianggou'] == 1) {
                        $state = 2;
                        $goodsBeans[$i]['sheng_yu_time'] = $goodsBeans[$i]['qianggou_end_time'] - time();
                        $goodsBeans[$i]['new_price'] = ($goodsBeans[$i]['goods_promoteprice'] <= 0) ? $goodsBeans[$i]['goods_price'] : number_format($goodsBeans[$i]['goods_promoteprice'], 2, '.', '');
                        $goodsBeans[$i]['lower_price'] = number_format($goodsBeans[$i]['goods_price'] - $goodsBeans[$i]['goods_promoteprice'], 2, '.', '');
                        $goodsBeans[$i]['zhe_kou'] = number_format($goodsBeans[$i]['goods_promoteprice'] * 10 / $goodsBeans[$i]['goods_price'], 1, '.', '');
                    } else {
                        $state = 0;
                        if ($discount_ratio) {
                            $goodsBeans[$i]['supplier_price'] = $goodsBeans[$i]['goods_price'] - ($goodsBeans[$i]['goods_pv'] * (1.0 - $discount_ratio['discount'] / 10));
                            $goodsBeans[$i]['goods_price'] = round($goodsBeans[$i]['supplier_price'] * (1.0 + 1.0 * $discount_ratio['ratio'] / 100), 2);
                            $goodsBeans[$i]['goods_pv'] = $goodsBeans[$i]['goods_price'] - $goodsBeans[$i]['supplier_price'];
                        }
                    }
                }
            }
            // hj 2018-01-19 15:08:52 修改折扣的判断
            $goodsPriceUtil->setGoodsTruePriceAndState(
                $tempStoreId, $memberId, $goodsBeans[$i], $goodsBeans[$i]['goods_id'], $goodsBeans[$i]['goods_price'], $goodsBeans[$i]['goods_pv'], 0, $state
            );
            $goodsBeans[$i]['state'] = $state;


            if ($goodsBeans[$i]['spec_open'] == 1) {    //有开启新版规格
                unset($goodsBeans[$i]['goods_spec']);
                $item = M('goods_spec_item');
                $goods_option = M('goods_option');
                $spec = M('goods_spec')->where(array('store_id' => $goodsBeans[$i]['store_id'], 'goods_id' => $goodsBeans[$i]['goods_id']))->order('displayorder')->field('id,title')->select();
                foreach ($spec as $k => $sp) {
                    $spec[$k]['item'] = $item->where(array('store_id' => $goodsBeans[$i]['store_id'], 'specid' => $sp['id'], 'show' => 1))->order('displayorder')->field('id,title')->select();
                }
                $goodsBeans[$i]['spec'] = $spec;
                $goodsBeans[$i]['spec_option'] = $goods_option->where(array('store_id' => $goodsBeans[$i]['store_id'], 'goods_id' => $goodsBeans[$i]['goods_id']))->field('id,title,thumb,goods_price,goods_promoteprice,goods_pv,stock,sales,specs,goods_barcode,sales_base')->select();
//                $sale_num = $goods_option->where(array('store_id' => $goodsBeans[$i]['store_id'], 'goods_id' => $goodsBeans[$i]['goods_id']))->sum('sales');
//                $sale_num = empty($sale_num) ? 0 : $sale_num;

//                $goodsBeans[$i]['sale_num'] = $sale_num;
                if ($discount_ratio) {
                    $spec_option = array();
                    foreach ($goodsBeans[$i]['spec_option'] as $arr) {
                        $arr['supplier_price'] = $arr['goods_price'] - ($arr['goods_pv'] * (1.0 - $discount_ratio['discount'] / 10));
                        $arr['goods_price'] = round($arr['supplier_price'] * (1.0 + 1.0 * $discount_ratio['ratio'] / 100), 2);
                        $arr['goods_pv'] = $arr['goods_price'] - $arr['supplier_price'];
                        $spec_option[] = $arr;
                    }
                    $goodsBeans[$i]['spec_option'] = $spec_option;
                }
            }

            if ($goodsBeans[$i]['spec_open'] == 0) {    //未开启新版规格
                // 解析规格
                $spec = array();
                //销量
                if ($this->isInitGoodsSaleNum($initType)) {
                    $sale_num = M('mb_goods_exp')->where(array('store_id' => $goodsBeans[$i]['store_id'], 'goods_id' => $goodsBeans[$i]['goods_id'], 'isdelete' => 0))->getField('sales_vol');
                }
                $sale_num = empty($sale_num) ? 0 : $sale_num;
                $goodsBeans[$i]['sale_num'] = $sale_num;
                if (!empty($goodsBeans[$i]['goods_spec']) && $goodsBeans[$i]['goods_spec'] != '[]') {
                    $goods_spec_list = json_decode($goodsBeans[$i]['goods_spec']);
                    foreach ($goods_spec_list as $k => $v) {
                        $spec[$k]['spec_id'] = $v->spec_id;
                        $spec[$k]['name'] = $v->name;
                        $spec[$k]['price'] = number_format($v->price, 2, '.', '');
                        $spec[$k]['storage'] = $v->storage;   //库存
                        $spec[$k]['sale_num'] = $sale_num;
                        $spec[$k]['pv'] = $v->pv;
                        $spec[$k]['new_price'] = -1;
                        $goodsBeans[$i]['sale_num'] += $v->sales_base;
                        if (empty($v->barcode)) {
                            $spec[$k]['barcode'] = $goodsBeans[$i]['goods_barcode'];
                        } else {
                            $spec[$k]['barcode'] = $v->barcode;
                        }

                        // hjun 库存
                        $goodsBeans[$i]['storage'][$v->spec_id] = $v->storage;
                    }
                } else {
                    $goodsBeans[$i]['sale_num'] += $goodsBeans[$i]['sales_base'];
                    // hjun 库存
                    $goodsBeans[$i]['storage']['0'] = $goodsBeans[$i]['original_goods_storage'];
                }

                unset($goodsBeans[$i]['goods_spec']);
                if (count($spec) < 2) {
                    // 2018-01-04 18:03:45
                    $goodsBeans[$i]['spec_id'] = 0;
                    $goodsBeans[$i]['gs_id'] = $goodsBeans[$i]['goods_id'] . '|0';
                    $goodsBeans[$i]['gid_sid'] = $goodsBeans[$i]['goods_id'] . '_0';
                    $goodsBeans[$i]['buy_num'] = $cartTool->getNumWithID($goodsBeans[$i]['goods_id'] . '|' . '0'); // 查询用户该商品的购买量
                    $goodsBeans[$i]['select_state'] = $cartTool->getStateWithID($goodsBeans[$i]['goods_id'] . '|' . '0');
                    // hjun 2018-01-01 21:40:45 mj_id 和 mj_level
                    $goodsBeans[$i]['mj_id'] = (int)$cartTool->getMjWithId($goodsBeans[$i]['goods_id'] . '|' . '0');
                    $goodsBeans[$i]['mj_level'] = (int)$cartTool->getMjLevelWithId($goodsBeans[$i]['goods_id'] . '|' . '0');
                }
                $spec_option = array();
                foreach ($spec as $spe) {
                    $arr = array();
                    $arr['id'] = $spe['spec_id'];
                    $arr['title'] = $spe['name'];
                    $arr['thumb'] = $goodsBeans[$i]['main_img'];
                    $arr['goods_price'] = $spe['price'];
                    $arr['new_price'] = $spe['price'];
                    $arr['original_stock'] = $spe['storage'];
                    $arr['stock'] = ($spe['storage'] == -1) ? '充足' : $spe['storage'];
                    $arr['sales'] = $spe['sale_num'];
                    $arr['goods_pv'] = $spe['pv'];
                    $arr['specs'] = $spe['spec_id'];
                    $arr['goods_barcode'] = $spe['barcode'];
                    if ($discount_ratio) {
                        $arr['supplier_price'] = $arr['goods_price'] - ($arr['goods_pv'] * (1.0 - $discount_ratio['discount'] / 10));
                        $arr['goods_price'] = round($arr['supplier_price'] * (1.0 + 1.0 * $discount_ratio['ratio'] / 100), 2);
                        $arr['goods_pv'] = $arr['goods_price'] - $arr['supplier_price'];
                    }
                    $spec_option[] = $arr;
                }
                if (!empty($spec_option)) {
                    if ($state == 1 || $state == 2) {
                        $spec_option[0]['new_price'] = $goodsBeans[$i]['new_price'];
                    }
                    foreach ($spec_option as $k2 => $so) {
                        $goodsPriceUtil->setGoodsTruePriceAndState(
                            $tempStoreId, $memberId, $spec_option[$k2], $goodsBeans[$i]['goods_id'], $so['goods_price'], $so['goods_pv'], $so['id'], $state
                        );
                    }
                    $goodsBeans[$i]['spec_option'] = $spec_option;

                    $goodsBeans[$i]['spec'][] = array('title' => L('SPEC'), 'item' => $spec_option);
                }
            } else {
                if (count($goodsBeans[$i]['spec_option']) == 1) {
                    $goodsBeans[$i]['buy_num'] = $cartTool->getNumWithID($goodsBeans[$i]['goods_id'] . '|' . $goodsBeans[$i]['spec_option'][0]['specs']); // 查询用户该商品的购买量
                }
                foreach ($goodsBeans[$i]['spec_option'] as $k3 => $gspec) {
                    // 销量基数
                    $goodsBeans[$i]['sale_num'] += $gspec['sales'] + $gspec['sales_base'];
                    $goodsBeans[$i]['spec_option'][$k3]['original_stock'] = $goodsBeans[$i]['spec_option'][$k3]['stock'];
                    $goodsBeans[$i]['spec_option'][$k3]['stock'] = ($goodsBeans[$i]['spec_option'][$k3]['stock'] == -1) ? '充足' : $goodsBeans[$i]['spec_option'][$k3]['stock'];
                    // 库存
                    $goodsBeans[$i]['storage'][$gspec['specs']] = $gspec['stock'];
                    $goodsBeans[$i]['spec_option'][$k3]['thumb'] = empty($goodsBeans[$i]['spec_option'][$k3]['thumb']) ? $goodsBeans[$i]['main_img'] : $goodsBeans[$i]['spec_option'][$k3]['thumb'];
                    if ($state == 1 || $state == 2) {
                        $goodsBeans[$i]['spec_option'][$k3]['new_price'] = ($gspec['goods_promoteprice'] <= 0) ? $gspec['goods_price'] : $gspec['goods_promoteprice'];
                    }
                    $goodsPriceUtil->setGoodsTruePriceAndState(
                        $tempStoreId, $memberId, $goodsBeans[$i]['spec_option'][$k3], $goodsBeans[$i]['goods_id'], $gspec['goods_price'], $gspec['goods_pv'], $gspec['specs'], $state
                    );
                }

            }


            //如果有一个以上的规格则显示价格区间
            $tempArr = array();
            foreach ($goodsBeans[$i]['spec_option'] as $bean) {
                $tempArr[] = $bean['new_price'];
            }

            $min_price = min($tempArr);
            $max_price = max($tempArr);

            if ($min_price != $max_price) {
                if ($goodsBeans[$i]['state'] > 0) {
                    if ($type == 2) {
                        $goodsBeans[$i]['new_price'] = number_format($min_price, 2, '.', '');
                    } else {
                        $goodsBeans[$i]['new_price'] = number_format($min_price, 2, '.', '') . '~' . number_format($max_price, 2, '.', '');
                    }
                } else {
                    if ($type == 2) {
                        $goodsBeans[$i]['new_price'] = number_format($min_price, 2, '.', '');
                    } else {
                        $goodsBeans[$i]['new_price'] = number_format($min_price, 2, '.', '') . '~' . number_format($max_price, 2, '.', '');
                    }
                }
            } else if (count($tempArr) > 0) {
                $goodsBeans[$i]['new_price'] = $tempArr[0];
            }

            $tempArr2 = array();
            foreach ($goodsBeans[$i]['spec_option'] as $bean) {
                $tempArr2[] = $bean['goods_price'];
            }
            $min_price = min($tempArr2);
            $max_price = max($tempArr2);
            if ($min_price != $max_price) {
                if ($goodsBeans[$i]['state'] > 0) {
                    if ($type == 2) {
                        $goodsBeans[$i]['goods_price'] = number_format($min_price, 2, '.', '');
                    } else {
                        $goodsBeans[$i]['goods_price'] = number_format($min_price, 2, '.', '') . '~' . number_format($max_price, 2, '.', '');
                    }
                } else {
                    if ($type == 2) {
                        $goodsBeans[$i]['goods_price'] = number_format($min_price, 2, '.', '');
                    } else {
                        $goodsBeans[$i]['goods_price'] = number_format($min_price, 2, '.', '') . '~' . number_format($max_price, 2, '.', '');
                    }
                }
            } else if (count($tempArr2) > 0) {
                $goodsBeans[$i]['goods_price'] = $tempArr2[0];
            }

            if (count($goodsBeans[$i]['spec_option']) > 0) {
                $goodsBeans[$i]['is_spec'] = 1; //是否有规格
            } else {

                $goodsBeans[$i]['is_spec'] = 0; //是否有规格
            }
            if ($goodsBeans[$i]['agent_sid']) {
                $goodsBeans[$i]['store_id'] = $goodsBeans[$i]['agent_sid'];
            }
            $store_name = D('Store')->getStoreInfo($goodsBeans[$i]['store_id'])['data']['store_name'];

            $goodsBeans[$i]['store_name'] = $store_name;

            $this->setGoodsPvStr($goodsBeans[$i]);
        }
        return $goodsBeans;
    }

    /**
     * 设置商品的PV字符串
     * @param $goods
     * @return void
     * User: hjun
     * Date: 2018-10-15 15:59:00
     * Update: 2018-10-15 15:59:00
     * Version: 1.00
     */
    public function setGoodsPvStr(&$goods)
    {
        $goods['goods_pv_str'] = '';
        $goods['goods_pv_str_info'] = '';
        return null;
        // goods_pv_str_info(商品标题显示的PV,多规格为空不显示)
        // goods_pv_str(默认的第一个规格的PV)
        $storeGrant = $this->storeGrant;
        $storeInfo = $this->storeInfo;
        if ($storeGrant['pv_ctrl'] == 1 && $storeInfo['store_pv_hide'] == 0 && $goods['goods_pv'] > 0) {
            $goods['goods_pv_str'] = 'P' . $goods['goods_pv'] * 100;
            if (count($goods['spec_option']) <= 1) {
                $goods['goods_pv_str_info'] = $goods['goods_pv_str'];
            } else {
                foreach ($goods['spec_option'] as $key => $spec) {
                    if ($spec['goods_pv'] > 0) {
                        $spec['goods_pv_str_info'] = 'P' . $spec['goods_pv'] * 100;
                    } else {
                        $spec['goods_pv_str_info'] = '';
                    }
                    $goods['spec_option'][$key] = $spec;
                }
                $goods['goods_pv_str_info'] = '';
            }
        } else {
            $goods['goods_pv_str'] = '';
            $goods['goods_pv_str_info'] = '';
        }
    }

    /**
     * 初始化商品的特殊价格 (会员、代理商、供应商)
     * @param array $goodsList 需要从  goods_extra表中查出的数据
     *  必备参数:
     *  [
     *      [0] => [
     *          'goods_id' => '商品ID'
     *          'goods_price' => '' // 商品原价
     *          'store_id' => '' // 商品商家
     *      ]
     *  ]
     * @return array
     * User: hjun
     * Date: 2018-02-01 11:56:41
     * Update: 2018-02-01 11:56:41
     * Version: 1.00
     */
    public function initGoodsVipPrice($goodsList = [])
    {
        return $goodsList;
    }

    /**
     * 设置商品会员、代理折扣
     * @param array $memberDiscountInfo
     * @param array $goodsBean
     * @param int $goodsId
     * @param int $goodsPrice
     * @param int $goodsPv
     * @param int $specId
     * @param $state
     * User: hjun
     * Date: 2018-01-19 16:32:09
     * Update: 2018-01-19 16:32:09
     * Version: 1.00
     */
    public function setGoodsBeanDiscount($memberDiscountInfo = [], &$goodsBean = [], $goodsId = 0, $goodsPrice = 0, $goodsPv = 0, $specId = 0, &$state)
    {
        if ($memberDiscountInfo['group_id'] > 0 && isset($memberDiscountInfo['discount']) && $memberDiscountInfo['partner_ctrl'] == 1) {
            $state = 4;
            $discount = round($memberDiscountInfo['discount'] / 10, 2);
            $discountType = $memberDiscountInfo['discount_type'];
            switch ((int)$discountType) {
                case 0:
                    $goodsBean['new_price'] = round($goodsPrice * $discount, 2);
                    break;
                case 1:
                    $goodsBean['new_price'] = round($goodsPrice - $goodsPv + $goodsPv * $discount, 2);
                    break;
                case 2:
                    $where = [];
                    $where['store_group_price_id'] = $memberDiscountInfo['store_group_price_id'];
                    $where['goods_id'] = $goodsId;
                    $where['spec_id'] = $specId;
                    $price = M('mb_store_group_price_data')->where($where)->getField('price');
                    $goodsBean['new_price'] = empty($price) ? $goodsPrice : $price;
                    break;
                default:
                    $goodsBean['new_price'] = $goodsPrice;
                    break;
            }
        } elseif ($memberDiscountInfo['vip_discount'] > 0 && !($state == 1 || $state == 2)) {
            $state = 3;
            $discount = round($memberDiscountInfo['vip_discount'] / 10, 2);
            $goodsBean['new_price'] = round($goodsPrice * $discount, 2);
        } elseif (!($state == 1 || $state == 2)) {
            $goodsBean['new_price'] = $goodsPrice;
        }
    }

    /**
     * 获取商品列表中所有的商家ID数组
     * @param array $goodsList
     * @return array
     * User: hjun
     * Date: 2018-01-02 00:37:44
     * Update: 2018-01-02 00:37:44
     * Version: 1.00
     */
    public function getGoodsListStoreId($goodsList = [])
    {
        $storeId = [];
        foreach ($goodsList as $key => $value) {
            if (!in_array($value['store_id'], $storeId)) {
                $storeId[] = $value['store_id'];
            }
        }
        return $storeId;
    }

    /**
     * 商品列表按照store_id分组 并且还返回所有的store_id数组
     * @param array $goodsList
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-01 22:02:46
     * Update: 2018-01-01 22:02:46
     * Version: 1.00
     */
    public function groupGoodsListByStoreId($goodsList = [])
    {
        $list = [];
        foreach ($goodsList as $key => $value) {
            $storeId = $value['store_id'];
//            if ($this->isSupplierGoods($value)) {
//                $storeId = $this->visitStoreId;
//            }
            $list[$storeId][] = $value;
        }
        return $list;
    }

    /**
     * 将商品列表按照mj_id 分组 mj_id 倒序排列
     * 如果活动结束了 则设置为0
     * @param array $goodsList
     * @param array $mjList 当前商家有的满减活动列表
     * @return array
     * User: hjun
     * Date: 2018-01-02 00:41:57
     * Update: 2018-01-02 00:41:57
     * Version: 1.00
     */
    public function groupGoodsListByMjId($goodsList = [], $mjList = [])
    {
        $list = [];
        $mjId = [];
        foreach ($goodsList as $key => $value) {
            $mjInfo = $this->getMjInfoFromMjList($value['mj_id'], $mjList);
            $value['mj_id'] = (int)$mjInfo['mj_id'];
            if (!in_array((int)$value['mj_id'], $mjId)) {
                $mjId[] = (int)$value['mj_id'];
            }
        }
        rsort($mjId);
        foreach ($mjId as $key => $value) {
            $list[$value] = [];
        }
        foreach ($goodsList as $key => $value) {
            $mjId = (int)$value['mj_id'];
            $mjInfo = $this->getMjInfoFromMjList($mjId, $mjList);
            $mjId = $this->mjHasGoods($value['goods_id'], $mjInfo) ? $mjInfo['mj_id'] : 0;
            $value['mj_id'] = $mjId;
            $value['mj_level'] = 0;
            $list[$mjId][] = $value;
        }
        return $list;
    }

    /**
     * 判断满减活动是否包含了该商品
     * @param int $goodsId
     * @param array $mjInfo
     * @return bool
     * User: hjun
     * Date: 2018-01-03 17:23:18
     * Update: 2018-01-03 17:23:18
     * Version: 1.00
     */
    public function mjHasGoods($goodsId = 0, $mjInfo = [])
    {
        switch ((int)$mjInfo['limit_goods_type']) {
            case 1:
                return true;
                break;
            case 2:
                return in_array($goodsId, $mjInfo['limit_goods']);
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * 满减活动按照store_id分组
     * @param array $mjList
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-01 22:50:49
     * Update: 2018-01-01 22:50:49
     * Version: 1.00
     */
    public function groupMjListByStoreId($mjList = [])
    {
        $list = [];
        foreach ($mjList as $key => $value) {
            $list[$value['store_id']][] = $value;
        }
        return $list;
    }

    /**
     * 计算某个活动下 商品的消费情况
     * @param array $mjInfo 活动信息
     *  "mjItem": {
     * "promoteFlag": "string,标志字样。例如：满减、折扣",
     * "promoteTitle": "string,活动标题。例如：已购满2件，已减7.16{$this->storeInfo['currency_unit']}; 满100{$this->storeInfo['currency_unit']}减20{$this->storeInfo['currency_unit']}",
     * "actLineTitle": "string,链接字样。例如：去凑单。无则为空 前端也不需要显示",
     * "actLinkUrl": "string,链接地址"
     * },
     * @param array $mjGoodsList 参与该活动的商品列表 包含商品价格 数量信息
     * @param array $otherData 其他数据
     * @return array
     * User: hjun
     * Date: 2018-01-02 00:35:16
     * Update: 2018-01-02 00:35:16
     * Version: 1.00
     */
    public function calculateMjDiscountsDetail($mjInfo = [], $mjGoodsList = [], $otherData = [])
    {
        $detail = [
            'promoteFlag' => $mjInfo['mj_type_name'],
            'promoteTitle' => (int)$mjInfo['mj_id'] <= 0 ? '' : $mjInfo['discounts_name'],
            'actLineTitle' => '',
            'actLinkUrl' => '',
            'reducePrice' => 0,
            'level' => 0,
            'tap' => 1,   //是否未满足条件
        ];
        // 计算商品总价格 计算商品总件数 转换成分进行计算
        $totalPrice = 0;
        $totalNum = 0;
        foreach ($mjGoodsList as $key => $value) {
            if ($value['select_state'] == 1) {
                $totalPrice += $value['new_price'] * 100 * (double)$value['buy_num'];
                $totalNum += (double)$value['buy_num'];
            }
        }

        // 计算活动到达哪个等级 并计算优惠价格 或者 还需凑单
        $result = $this->getMjDetailByLevel($totalPrice, $totalNum, $mjInfo);
        $level = $result['level'];
        $reducePrice = $result['reducePrice'];
        $promoteTitle = $result['promoteTitle'];
        $needCD = $result['needCD'];

        if ($needCD == 1 && (int)$mjInfo['mj_id'] > 0) {
            $detail['actLineTitle'] = L('TO_POOLED_ORDER');
            $request = getRequest();
            if ($request['module'] === strtolower(MODULE_WEB)) {
                $detail['actLinkUrl'] = "http://{$otherData['domain']}/index.php?c=Goods&a=getAllGoods&se={$mjInfo['store_id']}&f={$otherData['member_id']}&gl_type=1&mj_id={$mjInfo['mj_id']}";
            } else {
                $detail['actLinkUrl'] = "http://{$otherData['domain']}/index.php?c=Coupon&a=couponGoods&type=mj_goods&id={$mjInfo['mj_id']}&se={$mjInfo['store_id']}&f={$otherData['member_id']}";
            }
            $detail['tap'] = -1;
        }

        // 购物车数据修改
        $cartTool = $this;
        foreach ($mjGoodsList as $key => $value) {
            $goodsId = explode('|', $value['gs_id'])[0];
            $specId = explode('|', $value['gs_id'])[1];
            $cartTool->setMjId($goodsId, $specId, (int)$mjInfo['mj_id'], $level, false, false);
        }
//        $cartTool->saveItem();

        $detail['level'] = $level;
        $detail['reducePrice'] = $reducePrice;
        $detail['promoteTitle'] = $promoteTitle;
        return $detail;
    }

    /**
     *
     * @param int $totalPrice 总消费 分为单位
     * @param int $totalNum 总消费数量
     * @param array $mjInfo 满减活动信息
     * @return array
     * User: hjun
     * Date: 2018-01-02 11:29:25
     * Update: 2018-01-02 11:29:25
     * Version: 1.00
     */
    public function getMjDetailByLevel($totalPrice = 0, $totalNum = 0, $mjInfo = [])
    {
        $detail = [];
        $level = 0;
        $reducePrice = 0;
        $promoteTitle = '';
        $maxLevel = 0;
        foreach ($mjInfo['mj_rule'] as $key => $value) {
            // 记录最大等级
            if ($value['level'] > $maxLevel) {
                $maxLevel = $value['level'];
            }

            // 记录达到的优惠信息
            switch ((int)$mjInfo['mj_type']) {
                case 1:
                    if ($totalPrice >= $value['limit'] * 100) {
                        $level = $value['level'];
                        $reducePrice = $value['discounts'];
                        $promoteTitle = L('YMYJ', [
                            'value' => "{$value['limit']}{$this->storeInfo['currency_unit']}",
                            'discount' => "{$value['discounts']}{$this->storeInfo['currency_unit']}"
                        ]);
                        // "已购满{$value['limit']}{$this->storeInfo['currency_unit']}，已减{$value['discounts']}{$this->storeInfo['currency_unit']}";
                    }
                    break;
                case 2:
                    if ($totalNum >= $value['limit']) {
                        $level = $value['level'];
                        $reducePrice = $totalPrice * (1 - round($value['discounts'] / 10, 2));
                        $reducePrice = round($reducePrice / 100, 2);
                        $promoteTitle = L('YMYJ', [
                            'value' => "{$value['limit']}" . L('PIECE'),
                            'discount' => "{$reducePrice}{$this->storeInfo['currency_unit']}",
                        ]);
                        // "已购满{$value['limit']}件，已减{$reducePrice}{$this->storeInfo['currency_unit']}";
                    }
                    break;
                case 3:
                    if ($totalPrice >= $value['limit'] * 100) {
                        $level = 1;
                        // 计算减的次数
                        $disNum = floor($totalPrice / ($value['limit'] * 100));
                        // 封顶的话最大只能是封顶的次数
                        if ($value['is_top'] == 1) {
                            if ($disNum > $value['dis_num']) {
                                $disNum = $value['dis_num'];
                            }
                        }
                        $reducePrice = $disNum * ($value['discounts'] * 100);
                        $reducePrice = round($reducePrice / 100, 2);
                        $limitMoney = $disNum * ($value['limit'] * 100);
                        $limitMoney = round($limitMoney / 100, 2);
                        $promoteTitle = L('YMYJ', [
                            'value' => "{$limitMoney}{$this->storeInfo['currency_unit']}",
                            'discount' => "{$reducePrice}{$this->storeInfo['currency_unit']}"
                        ]);;
                        // "已购满{$limitMoney}{$this->storeInfo['currency_unit']}，已减{$reducePrice}{$this->storeInfo['currency_unit']}";
                    }
                    break;
                default:
                    break;
            }
        }

        // 判断是否满足优惠条件 或 还有更高优惠
        $needCD = 0;
        if ($level > 0) {
            if ($level < $maxLevel) {
                $needCD = 1;
            }
        } else {
            $maxLevel = 1;
            $needCD = 1;
        }
        if ($needCD == 1) {
            foreach ($mjInfo['mj_rule'] as $key => $value) {
                if ($value['level'] == $maxLevel) {
                    switch ((int)$mjInfo['mj_type']) {
                        case 1:
                            $needMoney = round(($value['limit'] * 100 - $totalPrice) / 100, 2);
                            if (empty($promoteTitle)) {
                                $promoteTitle = "{$mjInfo['discounts_name']}，" . L('HCKJ', [
                                        'value' => "{$needMoney}{$this->storeInfo['currency_unit']}",
                                        'discount' => L('J') . "{$value['discounts']}{$this->storeInfo['currency_unit']}"
                                    ]);
                                // "{$mjInfo['discounts_name']}，还差{$needMoney}{$this->storeInfo['currency_unit']}可减{$value['discounts']}{$this->storeInfo['currency_unit']}";
                            } else {
                                $promoteTitle .= '，' . L('HCKJ', [
                                        'value' => "{$needMoney}{$this->storeInfo['currency_unit']}",
                                        'discount' => L('J') . "{$value['discounts']}{$this->storeInfo['currency_unit']}"
                                    ]);
                                // "，还差{$needMoney}{$this->storeInfo['currency_unit']}可减{$value['discounts']}{$this->storeInfo['currency_unit']}";
                            }
                            break;
                        case 2:
                            $needNum = $value['limit'] - $totalNum;
                            if (empty($promoteTitle)) {
                                $promoteTitle = "{$mjInfo['discounts_name']}，" . L('HCKJ', [
                                        'value' => "{$needNum}" . L('PIECE'),
                                        'discount' => L('D') . "{$value['discounts']}" . L('FOLD')
                                    ]);
                                // "{$mjInfo['discounts_name']}，还差{$needNum}件可打{$value['discounts']}折";
                            } else {
                                $promoteTitle .= '，' . L('HCKJ', [
                                        'value' => "{$needNum}" . L('PIECE'),
                                        'discount' => L('D') . "{$value['discounts']}" . L('FOLD')
                                    ]);
                                // "，还差{$needNum}件可打{$value['discounts']}折";
                            }
                            break;
                        case 3:
                            $promoteTitle = $mjInfo['discounts_name'];
                            break;
                        default:
                            break;
                    }
                }
            }
        }


        $detail['level'] = $level;
        $detail['reducePrice'] = $reducePrice;
        $detail['promoteTitle'] = $promoteTitle;
        $detail['needCD'] = $needCD;
        return $detail;
    }

    /**
     * 获取某个商品可以选择的活动列表
     * @param int $goodsId 商品ID
     * @param array $mjList 满减活动列表
     * @param array $mjInfo 该商品参加的满减活动
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-02 00:34:43
     * Update: 2018-01-02 00:34:43
     * Version: 1.00
     */
    public function getGoodsMjList($goodsId = 0, $mjList = [], $mjInfo = [])
    {
        $list = [];
        foreach ($mjList as $key => $value) {
            switch ((int)$value['limit_goods_type']) {
                case 1:
                    $value['Id'] = $value['mj_id'];
                    $value['name'] = $value['discounts_name'];
                    $value['state'] = $mjInfo['mj_id'] == $value['mj_id'] ? 1 : 0;
                    $list[] = $value;
                    break;
                case 2:
                    $limitGoods = is_array($value['limit_goods']) ?
                        $value['limit_goods'] : json_decode($value['limit_goods'], 1);
                    if (in_array($goodsId, $limitGoods)) {
                        $value['Id'] = $value['mj_id'];
                        $value['name'] = $value['discounts_name'];
                        $value['state'] = $mjInfo['mj_id'] == $value['mj_id'] ? 1 : 0;
                        $list[] = $value;
                    }
                    break;
                default:
                    if ((int)$value['mj_id'] <= 0) {
                        $value['Id'] = $value['mj_id'];
                        $value['name'] = $value['discounts_name'];
                        $value['state'] = $mjInfo['mj_id'] == $value['mj_id'] ? 1 : 0;
                        $list[] = $value;
                    }
                    break;
            }
        }
        return $list;
    }

    /**
     * 根据mj_id从满减活动列表中获取满减信息
     * @param int $mjId
     * @param array $mjList
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-02 01:56:13
     * Update: 2018-01-02 01:56:13
     * Version: 1.00
     */
    public function getMjInfoFromMjList($mjId = 0, $mjList = [])
    {
        foreach ($mjList as $key => $value) {
            if ($value['mj_id'] == $mjId) {
                return $value;
            }
        }
        return [];
    }

    /**
     * 转换商品数据的字段
     * @param array $goodsInfo
     * @param array $otherData
     * @return array
     * User: hjun
     * Date: 2018-01-02 01:41:31
     * Update: 2018-01-02 01:41:31
     * Version: 1.00
     */
    public function transformGoodsField($goodsInfo = [], $otherData = [])
    {
        $info = [];
        $info['gId'] = $goodsInfo['gid_sid'];
        $info['gs_id'] = $goodsInfo['gs_id'];
        $info['primary_id'] = $goodsInfo['primary_id'];
        $info['goods_id'] = explode('|', $goodsInfo['gs_id'])[0];
        $info['shopBol'] = $goodsInfo['select_state'] == 1;
        $info['gName'] = $goodsInfo['goods_name'];
        $info['img'] = $goodsInfo['main_img'] . '?_90x90x2';
        $info['gLink'] = "http://{$otherData['domain']}//index.php?m=Service&c=Goods&a=goods_detail&id={$goodsInfo['gs_id']}&se={$this->visitStoreId}&f={$otherData['member_id']}";
        $info['gPrice'] = $goodsInfo['new_price'];
        $info['num'] = $goodsInfo['buy_num'];
        $info['gTtl'] = $goodsInfo['new_price'] * $goodsInfo['buy_num'];
        $info['gAbroad'] = (int)$goodsInfo['is_abroad'] == 1;
        $info['gHot'] = (int)$goodsInfo['is_hot'] == 1;
        $info['gSnap'] = (int)$goodsInfo['state'] == 2;
        $info['gSale'] = (int)$goodsInfo['state'] == 1;
        $info['gVip'] = (int)$goodsInfo['viplevel'] > 0;
        $info['gVipLayer'] = (int)$goodsInfo['viplevel'];
        $info['gSpec'] = $goodsInfo['spec_name'];
        $info['gc_id'] = $goodsInfo['gc_id'];
        $info['supplier_sid'] = empty($goodsInfo['supplier_sid']) ? 0 : $goodsInfo['supplier_sid'];
        $info['goods_storage'] = empty($goodsInfo['goods_storage']) ? 0 : $goodsInfo['goods_storage'];
        $info['limit_buy'] = empty($goodsInfo['limit_buy']) ? 0 : $goodsInfo['limit_buy'];
        $info['goods_unit'] = $goodsInfo['goods_unit'];
        $info['print_ids'] = $goodsInfo['print_ids'];
        return $info;
    }

    public function getCartDataV1()
    {
        $memberId = $this->memberId;
        // 返回数据结构
        $returnData = [];
        $returnData['cartList'] = [];
        $returnData['totalPrice'] = 0;
        $returnData['checkNum'] = 0;
        $returnData['factTotalPrice'] = 0;
        $returnData['reduceTotalPrice'] = 0;
        $returnData['checkAll'] = 1;
        $returnData['totalNum'] = 0;
        $returnData['canSettle'] = 1;

        // 获取购物车数据
        $goodsList = $this->getShopCartGoodsList();
        if (empty($goodsList)) return getReturn(200, '', $returnData);

        // 购物车商品根据store_id分组 key为store_id value是个商品列表数组
        $cartGoods = $this->groupGoodsListByStoreId($goodsList);

        // 获取所有商家ID
        // 获取需要的所有商家活动 将活动也按照store_id分组
        $storeId = $this->getGoodsListStoreId($goodsList);
        $where = [];
        $where['store_id'] = count($storeId) > 1 ? ['in', implode(',', $storeId)] : $storeId[0];
        $mjList = D('MjActivity')->getMjList(0, 0, 1, 0, $where)['data']['list'];
        $mjList = $this->groupMjListByStoreId($mjList);

        foreach ($cartGoods as $key => $value) {
            // 当前商家ID
            $currentStoreId = $key;
            // 当前商品列表
            $currentGoodsList = $value;
            // 获取商家信息
            $storeInfo = D('Store')->getStoreInfo($currentStoreId)['data'];
            // 商家活动列表
            $storeMjList = empty($mjList[$currentStoreId]) ? [] : $mjList[$currentStoreId];
            // 每个商家活动都需要添加一个无活动的数据
            $storeMjList[] = [
                'mj_id' => 0,
                'mj_type_name' => '',
                'discounts_name' => '不参加优惠活动',
            ];

            // 封装数据
            $item = [];
            $item['shopId'] = $currentStoreId;
            $domain = empty($storeInfo['store_domain']) ? C('MALL_DOMAIN') : $storeInfo['store_domain'];
            $item['shopLink'] = getStoreDomain($currentStoreId) . "/index.php?m=Service&c=index&comemall=1&se={$currentStoreId}&f={$memberId}";
            $item['shopName'] = $storeInfo['store_name'];
            $item['hasCoupons'] = (int)(D('CouponsCenter')->getCouponsCenterCountByStoreId($currentStoreId)) > 0 ? 1 : 0;
            $item['checkAll'] = 1;
            // 商家根据mj_id分组的商品列表数据
            $item['sortedItems'] = [];
            // 商家拥有的gs_id
            $item['shopGsId'] = [];
            // 商家的自提列表
            $item['pickup_list'] = empty($pickupList[$currentStoreId]) ? [] : $pickupList[$currentStoreId];

            // 封装sortedItems的数据
            // 将当前商品列表按照mj_id分组
            $currentMjGoodsList = $this->groupGoodsListByMjId($currentGoodsList, $storeMjList);
            foreach ($currentMjGoodsList as $k => $val) {
                $mjId = $k;
                $mjInfo = $this->getMjInfoFromMjList($mjId, $storeMjList);
                $mjGoodsList = $val;
                $i = [];
                $i['mjId'] = $mjId;
                $i['cartGoods'] = [];
                foreach ($mjGoodsList as $kk => &$vv) {
                    // 转换商品字段
                    $ii = $this->transformGoodsField($vv, ['member_id' => $memberId, 'domain' => $domain]);
                    // 获取商品可以选择的活动
                    $ii['selectMj'] = $this->getGoodsMjList($vv['goods_id'], $storeMjList, $mjInfo);
                    $i['cartGoods'][] = $ii;

                    // hjun 2018-03-12 11:42:58 计算总数
                    $returnData['totalNum'] += $ii['num'];
                    if ($vv['select_state'] == 0) {
                        // 如果有一个商品没有选中 则商家的checkAll就为0
                        $item['checkAll'] = 0;
                    } else {
                        // 如果选中了 则总数量增加
                        $returnData['checkNum'] += $vv['buy_num'];
                        // 计算总原价
                        $returnData['totalPrice'] += $ii['gTtl'];
                    }
                    // 累计商家里面的商品gs_id
                    $item['shopGsId'][] = $vv['gs_id'];
                }

                // 经过自提的筛选后 再计算满减的金额
                $i['mjItem'] = $this->calculateMjDiscountsDetail($mjInfo, $mjGoodsList, ['member_id' => $memberId, 'domain' => $domain]);

                // 计算总共优惠的价格
                $returnData['reduceTotalPrice'] += $i['mjItem']['reducePrice'];
                if (!empty($i['cartGoods'])) {
                    $item['sortedItems'][] = $i;
                }


            }

            // 合并商家的商品gs_id
            $item['shopGsId'] = implode('@', $item['shopGsId']);

            if ($item['checkAll'] == 0) {
                // 如果有一个商家没有选中 则全部全中为0
                $returnData['checkAll'] = 0;
            }

            $item['check_pick'] = empty($item['check_pick']) ? (object)[] : $item['check_pick'];

            $returnData['cartList'][] = $item;
        }
        // 计算实际价格
        $returnData['factTotalPrice'] = $returnData['totalPrice'] - $returnData['reduceTotalPrice'];
        if (empty($returnData['cartList'])) {
            $returnData['checkAll'] = 0;
        }
        // 这里要更新购物车数据 因为修改过
        $this->saveItem();
        return getReturn(200, '', $returnData);
    }

    /**
     * 选择自提点
     * @param array $item
     * @param array $currentOptions
     * @param int $pickId
     * @return boolean
     * User: hjun
     * Date: 2018-09-03 17:38:05
     * Update: 2018-09-03 17:38:05
     * Version: 1.00
     */
    public function checkedPickup(&$item = [], &$currentOptions = [], $pickId = 0)
    {
        $success = false;
        foreach ($item['pickup_list'] as $k => $pick) {
            $item['pickup_list'][$k]['checked'] = 0;
        }
        foreach ($item['pickup_list'] as $k => $pick) {
            if ($pick['pick_id'] == $pickId) {
                $item['check_pick'] = $pick;
                $item['pickup_list'][$k]['checked'] = 1;
                $success = true;
                break;
            }
        }
        $success && $currentOptions['pick_id'] = $pickId;
        return $success;
    }

    /**
     * 获取我的推荐人是主账号的距离最近的自提点
     * @param array $pickupList
     * @return int
     * User: hjun
     * Date: 2018-09-03 17:56:14
     * Update: 2018-09-03 17:56:14
     * Version: 1.00
     */
    public function getRecommendPick($pickupList = [])
    {
        if (empty($pickupList)) return 0;
        $recommendInfo = getRecommendInfo($this->memberId, $this->visitStoreId);
        $recommendId = $recommendInfo['recommend_id'];
        $recommendPickups = [];
        // 找出符合条件的自提点
        if (!empty($recommendId)) {
            foreach ($pickupList as $pickup) {
                if ($pickup['main_staff'] == $recommendId) {
                    $recommendPickups[] = $pickup;
                    break;
                }
            }
        }
        // 从符合条件中的自提点选出距离最近的 如果没有符合条件的 就从全部自提点里找最近的
        $recommendPickups = empty($recommendPickups) ? $pickupList : $recommendPickups;
        if (!empty($recommendPickups)) {
            if (count($recommendPickups) === 1) {
                return $recommendPickups[0]['id'];
            }
            foreach ($recommendPickups as $key => $pickup) {
                $isKm = strpos($pickup['distance'], 'distance') !== false;
                if ($isKm) {
                    $recommendPickups[$key]['distance'] = str_replace('km', '', $pickup['distance']);
                    $recommendPickups[$key]['distance'] *= 1000;
                }
            }
            $min = $recommendPickups[0]['distance'];
            $id = $recommendPickups[0]['id'];
            foreach ($recommendPickups as $pickup) {
                if ($pickup['distance'] < $min) {
                    $min = $pickup['distance'];
                    $id = $pickup['id'];
                }
            }
            return $id;
        }
        return 0;
    }

    /**
     * 获取购物车数据 格式按规定的
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-29 16:10:43
     * Update: 2017-12-29 16:10:43
     * Version: 1.00
     */
    public function getCartData()
    {
        $options = $this->extra;
        $memberId = $this->memberId;
        // 返回数据结构
        $returnData = [];
        $returnData['cartList'] = [];
        $returnData['totalPrice'] = 0;
        $returnData['checkNum'] = 0;
        $returnData['factTotalPrice'] = 0;
        $returnData['reduceTotalPrice'] = 0;
        $returnData['checkAll'] = 1;
        $returnData['totalNum'] = 0;
        $returnData['canSettle'] = 1;
        $returnData['is_pickup'] = 0;
        // 配送方式列表
        $returnData['freight_list'] = [];
        // 获取商家自提权限
        $storeGrant = D('StoreGrade')->getStoreGrantInfo($this->cartStoreId)['data'];
        if ($storeGrant['pickup_ctrl'] == 1) {
            $returnData['freight_list'][] = ['type' => 0, 'name' => L('KDPS'), 'checked' => $options['freight_type'] == 0 ? 1 : 0];
            $returnData['freight_list'][] = ['type' => 1, 'name' => L('SMZT'), 'checked' => $options['freight_type'] == 0 ? 0 : 1];
            if ($options['freight_type'] == 1) {
                $returnData['is_pickup'] = 1;
            }
        } else {
            // 如果原来是自提的 设置为快递配送
            if ($options['freight_type'] == 1) {
                $this->setFreightType(0);
                $options = $this->extra;
            }
        }
        // 获取购物车数据
        $goodsList = $this->getShopCartGoodsList();
        if (empty($goodsList)) {
            $returnData['checkAll'] = 0;
            $returnData['canSettle'] = 0;
            $returnData['settleMsg'] = '朋友，您还没有选择任何商品哦，这样不能结算啦^^';
            return getReturn(200, '', $returnData);
        }

        // 购物车商品根据store_id分组 key为store_id value是个商品列表数组
        $cartGoods = $this->groupGoodsListByStoreId($goodsList);

        // 获取所有商家ID
        // 获取需要的所有商家活动 将活动也按照store_id分组
        $storeIds = $this->getGoodsListStoreId($goodsList);
        $where = [];
        $where['store_id'] = getInSearchWhereByArr($storeIds);
        $mjList = D('MjActivity')->getMjList(0, 0, 1, 0, $where)['data']['list'];
        $mjList = $this->groupMjListByStoreId($mjList);

        // 获取所有商品可用的自提列表
        $pickupList = [];
        if ($storeGrant['pickup_ctrl'] == 1) { /*&& $options['freight_type'] == 1*/
            $pickupList = $this->getPickupListByGoodsList($goodsList);
            $pickupList = $this->filterPickupListByCartGoods($pickupList, $cartGoods);
            // 如果没有自提点 并且选择的是快递配送 则顶部的选择框就不需要了
            if (empty($pickupList)) {
                $this->setFreightType(0);
                $options = $this->extra;
//                if ($options['freight_type'] == 0) {
                $returnData['freight_list'] = [];
//                }
            }
        }

        foreach ($cartGoods as $key => $value) {
            // 当前商家ID
            $currentStoreId = $key;
            // 当前商品列表
            $currentGoodsList = $value;
            // 获取商家信息
            $storeInfo = D('Store')->getStoreInfo($currentStoreId)['data'];
            // 商家活动列表
            $storeMjList = empty($mjList[$currentStoreId]) ? [] : $mjList[$currentStoreId];
            // 每个商家活动都需要添加一个无活动的数据
            $storeMjList[] = [
                'mj_id' => 0,
                'mj_type_name' => '',
                'discounts_name' => L('BCJYHHD')/*不参加优惠活动*/,
            ];

            // 封装数据
            $item = [];
            $item['shopId'] = $currentStoreId;
            $domain = empty($storeInfo['store_domain']) ? C('MALL_DOMAIN') : $storeInfo['store_domain'];
            $item['shopLink'] = getStoreDomain($currentStoreId) . "/index.php?m=Service&c=index&comemall=1&se={$currentStoreId}&f={$memberId}";
            $item['shopName'] = $storeInfo['store_name'];
            $item['hasCoupons'] = (int)(D('CouponsCenter')->getCouponsCenterCountByStoreId($currentStoreId)) > 0 ? 1 : 0;
            $item['checkAll'] = 1;
            // 商家根据mj_id分组的商品列表数据
            $item['sortedItems'] = [];
            // 商家拥有的gs_id
            $item['shopGsId'] = [];
            // 商家的自提列表
            $item['pickup_list'] = empty($pickupList[$currentStoreId]) ? [] : $pickupList[$currentStoreId];
            // 选择的自提点
            $item['check_pick'] = [];
            if ($options['pickup_ids'][$currentStoreId]['pick_id'] > 0) {
                $oldCheckPickIsSuccess = $this->checkedPickup($item, $options['pickup_ids'][$currentStoreId], $options['pickup_ids'][$currentStoreId]['pick_id']);
            }
            // 如果旧自提点失效了
            if (!$oldCheckPickIsSuccess && $options['freight_type'] == 1) {
                if (count($item['pickup_list']) == 1) {
                    // 如果只有一个自提点默认选中
                    $pickId = $item['pickup_list'][0]['pick_id'];
                } else {
                    // 如果有多个的话 找出核销人是我的推荐人的并且距离最近的一个自提点
                    $pickId = $this->getRecommendPick($item['pickup_list']);
                }
                $this->checkedPickup($item, $options['pickup_ids'][$currentStoreId], $pickId);
                $this->setPickId($currentStoreId, $pickId);
            }

            // 不支持自提的列表
            $item['no_support_list'] = [];

            // 封装sortedItems的数据
            // 将当前商品列表按照mj_id分组
            $currentMjGoodsList = $this->groupGoodsListByMjId($currentGoodsList, $storeMjList);
            foreach ($currentMjGoodsList as $k => $val) {
                $mjId = $k;
                $mjInfo = $this->getMjInfoFromMjList($mjId, $storeMjList);
                $mjGoodsList = $val;
                $i = [];
                $i['mjId'] = $mjId;
                $i['cartGoods'] = [];
                foreach ($mjGoodsList as $kk => &$vv) {
                    // 转换商品字段
                    $ii = $this->transformGoodsField($vv, ['member_id' => $memberId, 'domain' => $domain]);
                    // hjun 2018-03-12 11:42:58 计算总数
                    $returnData['totalNum'] += $ii['num'];
                    // 如果有选中商品 判断是否有选择自提点
                    if ($options['freight_type'] == 1) {
                        if ($vv['select_state'] == 1 && (empty($item['check_pick']) || empty($item['pickup_list']))) {
                            $returnData['canSettle'] = 0;
                            $returnData['settleMsg'] = "请选择{$storeInfo['store_name']}的自提点";
                        }
                    }
                    $ii['can_pick'] = 1;
                    // 查看选中的商品是否可以自提
                    if ($options['freight_type'] == 1 && !empty($item['check_pick'])) {
                        $canPick = $this->checkGoodsCanPick($vv, $item['check_pick']);
                        if (!$canPick) {
                            $ii['can_pick'] = 0;
                            $ii['no_pick_desc'] = $this->getNoPickupDesc();
                            $item['no_support_list'][] = $ii;
//                            $returnData['canSettle'] = 0;
//                            $returnData['settleMsg'] = "{{$storeInfo['store_name']}}存在不支持上门自提的商品,无法结算";
                            // 不能自提不计算价格 直接跳过 并且取消选中
                            $this->items[$ii['gs_id']]['state'] = 0;
                            $vv['select_state'] = 0; // 修改一会要计算满减的商品的选中状态
                            continue;
                        }
                    }
                    // 获取商品可以选择的活动
                    $ii['selectMj'] = $this->getGoodsMjList($vv['goods_id'], $storeMjList, $mjInfo);
                    $i['cartGoods'][] = $ii;

                    if ($vv['select_state'] == 0) {
                        // 如果有一个商品没有选中 则商家的checkAll就为0
                        $item['checkAll'] = 0;
                    } else {
                        // 如果选中了 则总数量增加
                        $returnData['checkNum'] += $vv['buy_num'];
                        // 计算总原价
                        $returnData['totalPrice'] += $ii['gTtl'];
                    }
                    // 累计商家里面的商品gs_id
                    $item['shopGsId'][] = $vv['gs_id'];
                }

                // 经过自提的筛选后 再计算满减的金额
                $i['mjItem'] = $this->calculateMjDiscountsDetail($mjInfo, $mjGoodsList, ['member_id' => $memberId, 'domain' => $domain]);

                // 计算总共优惠的价格
                $returnData['reduceTotalPrice'] += $i['mjItem']['reducePrice'];
                if (!empty($i['cartGoods'])) {
                    $item['sortedItems'][] = $i;
                }


            }

            // 合并商家的商品gs_id
            $item['shopGsId'] = implode('@', $item['shopGsId']);

            if ($item['checkAll'] == 0) {
                // 如果有一个商家没有选中 则全部全中为0
                $returnData['checkAll'] = 0;
            }

            $item['check_pick'] = empty($item['check_pick']) ? (object)[] : $item['check_pick'];

            $returnData['cartList'][] = $item;
        }
        // 计算实际价格
        $returnData['factTotalPrice'] = $returnData['totalPrice'] - $returnData['reduceTotalPrice'];
        // 判断是否可结算
        if ($returnData['checkNum'] == 0) {
            $returnData['checkAll'] = 0;
            $returnData['canSettle'] = 0;
            $returnData['settleMsg'] = L('NO_SELECT_GOODS_TO_SETTLEMENT'); /*不能结算*/
        }
        // 这里要更新购物车数据 因为修改过
        $this->saveItem();
        $returnData['version'] = $this->version;
        $returnData['totalPrice'] = number_format($returnData['totalPrice'], 2, '.', '');
        $returnData['factTotalPrice'] = number_format($returnData['factTotalPrice'], 2, '.', '');
        return getReturn(200, '', $returnData);
    }

    /**
     * 初始化商品列表的价格
     * @param array $goodsList 商品列表 goods_extra 查出的列表
     * @return array
     * User: hjun
     * Date: 2018-02-01 16:45:11
     * Update: 2018-02-01 16:45:11
     * Version: 1.00
     */
    public function initGoodsListPrice($goodsList = [])
    {
        if (empty($goodsList)) return [];
        $storeId = $this->cartStoreId;
        $memberId = $this->memberId;
        // 查出商品涉及到的商家与当前会员的折扣信息
        $modelSM = D('StoreMember');
        $modelSP = D('SupplierAgent');
        $storeMemberGroup = [];
        if ($memberId > 0) {
            $storeIdArr = [];
            foreach ($goodsList as $key => &$value) {
                $storeIdArr[] = $value['store_id'];
            }
            $where = [];
            $storeIdStr = implode(',', $storeIdArr);
            $storeIdStr = empty($storeIdStr) ? '' : $storeIdStr;
            $where['a.store_id'] = ['in', $storeIdStr];
            $list = $modelSM->getMemberStoreList($memberId, $where);
            foreach ($list as $key => $value) {
                $storeMemberGroup[$value['store_id']] = $value;
            }
        }

        // 处理价格
        foreach ($goodsList as $key => &$value) {
            // 初始商品状态 0-普通 1-促销 2-抢购 3-会员 4-代理商
            $value['state'] = 0;
            // 处理商品原价
            $value['goods_price'] = $value['min_goods_price'] == $value['max_goods_price'] ?
                $value['min_goods_price'] : "{$value['min_goods_price']}~{$value['max_goods_price']}";
            // 当前 会员-商家 的折扣信息
            $memberDiscountInfo = $storeMemberGroup[$value['store_id']];
            // 查询该商品是否时供应商的
            $supplierInfo = $modelSP->getStoreSupplierInfo($value['store_id'], $storeId);
            // 判断商品 促销、抢购(不能是供应商提供的商品)  否则处理供应商、会员、代理
            if ($value['is_promote'] == 1 && empty($supplierInfo)) {
                // 促销
                $value['state'] = 1;
                $value['new_price'] = $value['min_promote_price'] == $value['max_promote_price'] ?
                    $value['min_promote_price'] : "{$value['min_promote_price']}~{$value['max_promote_price']}";
            } elseif ($value['is_qinggou'] == 1 && empty($supplierInfo) && $value['end_time'] > NOW_TIME) {
                // 抢购
                $value['state'] = 2;
                $value['new_price'] = $value['min_promote_price'] == $value['max_promote_price'] ?
                    $value['min_promote_price'] : "{$value['min_promote_price']}~{$value['max_promote_price']}";
            } else {
                // 循环每个规格
                $spec = json_decode($value['spec_attr'], 1);
                if ($value['spec_type'] == -1 || empty($spec)) {
                    // 没有规格时
                    if (!empty($supplierInfo)) {
                        // 是供应商
                        $value['supplier_price'] = $value['goods_price'] - ($value['goods_pv'] * (1.0 - $supplierInfo['discount'] / 10));
                        $value['goods_price'] = round($value['supplier_price'] * (1.0 + 1.0 * $supplierInfo['ratio'] / 100), 2);
                    }
                    $this->setGoodsBeanDiscount($memberDiscountInfo,
                        $value, $value['goods_id'], $value['goods_price'], $value['goods_pv'], 0, $value['state']);
                } else {
                    // 有规格时
                    foreach ($value['spec_attr'] as $k => &$val) {
                        if (!empty($supplierInfo)) {
                            // 是供应商
                            $val['supplier_price'] = $val['spec_price'] - ($val['spec_goods_pv'] * (1.0 - $supplierInfo['discount'] / 10));
                            $val['spec_price'] = round($val['supplier_price'] * (1.0 + 1.0 * $supplierInfo['ratio'] / 100), 2);
                        }
                        $this->setGoodsBeanDiscount($memberDiscountInfo,
                            $val, $value['goods_id'], $val['spec_price'], $val['spec_goods_pv'], $val['spec_id'], $value['state']);
                    }
                    // 计算真实的价格范围
                    $minNewPrice = 0;
                    $maxNewPrice = 0;
                    foreach ($value['spec_attr'] as $k => $val) {
                        if ($val['new_price'] > $maxNewPrice) {
                            $maxNewPrice = $val['new_price'];
                        }
                        if ($val['new_price'] < $minNewPrice || $minNewPrice == 0) {
                            $minNewPrice = $val['new_price'];
                        }
                    }
                    $value['new_price'] = $minNewPrice == $maxNewPrice ? $minNewPrice : "{$minNewPrice}~{$maxNewPrice}";
                }
            }

        }
        return $goodsList;
    }

    /**
     * 检查商品能否自提
     * @param array $goods
     * @param array $pick
     * @return bool
     * User: hjun
     * Date: 2018-03-30 12:56:30
     * Update: 2018-03-30 12:56:30
     * Version: 1.00
     */
    public function checkGoodsCanPick($goods = [], $pick = [])
    {
        if (pickupIsMall($this->storeInfo)) {
            return true;
        }
        $limitList = $pick['limit_goods_list'];
        foreach ($limitList as $limit) {
            if ($limit['goods_id'] == $goods['goods_id'] && $limit['spec_id'] == $goods['primary_id']) {
                if ($limit['storage'] >= $goods['buy_num']) {
                    return true;
                }
                $this->setNoPickupDesc("库存剩余{$limit['storage']}");
                return false;
            }
        }
        return false;
    }

    /**
     * 保存额外数据
     * @return boolean
     * User: hjun
     * Date: 2018-03-30 17:15:03
     * Update: 2018-03-30 17:15:03
     * Version: 1.00
     */
    public function saveExtra()
    {
        $where = [];
        $where['store_id'] = $this->cartStoreId;
        $where['member_id'] = $this->memberId;
        $extra = json_encode($this->extra, 256);
        $extra = empty($extra) ? '' : $extra;
        $result = M('mb_shop_cart')->where($where)->setField('extra', $extra);
        return $result;
    }

    /**
     * 清空额外数据
     * @return bool
     * User: hjun
     * Date: 2018-03-30 17:24:48
     * Update: 2018-03-30 17:24:48
     * Version: 1.00
     */
    public function clearExtra()
    {
        $where = [];
        $where['store_id'] = $this->cartStoreId;
        $where['member_id'] = $this->memberId;
        $result = M('mb_shop_cart')->where($where)->setField('extra', '');
        return $result;
    }

    /**
     * 设置配送方式
     * @param int $type 0-快递配送 1-自提
     * @return boolean
     * User: hjun
     * Date: 2018-03-30 17:04:16
     * Update: 2018-03-30 17:04:16
     * Version: 1.00
     */
    public function setFreightType($type = 0)
    {
        $this->extra['freight_type'] = empty($type) ? 0 : 1;
        return $this->saveExtra();
    }

    /**
     * 设置该店选择的自提点
     * @param int $storeId
     * @param int $pickId
     * @return boolean
     * User: hjun
     * Date: 2018-03-30 17:23:24
     * Update: 2018-03-30 17:23:24
     * Version: 1.00
     */
    public function setPickId($storeId = 0, $pickId = 0)
    {
        $where = [];
        $where['id'] = $pickId;
        $where['is_pick'] = 1;
        $where['isdelete'] = 0;
        $result = D('PickUp')->field('id')->where($where)->find();
        if (empty($result)) return getReturn(-1, '选择的自提点已失效,请刷新页面');
        $this->extra['freight_type'] = 1;
        $this->extra['pickup_ids'][$storeId]['store_id'] = $storeId;
        $this->extra['pickup_ids'][$storeId]['pick_id'] = $pickId;
        $result = $this->saveExtra();
        if ($result === false) return getReturn(-1, '系统繁忙,请稍后重试...');
        return getReturn(200, '');
    }

    /**
     * 获取额外数据
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-30 17:45:13
     * Update: 2018-03-30 17:45:13
     * Version: 1.00
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * 根据商品列表获取自提列表
     * @param array $goodsList
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-30 22:18:41
     * Update: 2018-03-30 22:18:41
     * Version: 1.00
     */
    public function getPickupListByGoodsList($goodsList = [])
    {
        return D('DepotGoods')->getPickupListByGoodsList($this->cartStoreId, $goodsList);
    }

    /**
     * 根据每家店购买的商品，筛选出这家店可以选择的门店列表
     * @param array $pickups
     * @param array $cartGoods
     * @return array
     * User: hjun
     * Date: 2018-11-09 16:53:51
     * Update: 2018-11-09 16:53:51
     * Version: 1.00
     */
    public function filterPickupListByCartGoods($pickups = [], $cartGoods = [])
    {
        $activePickups = [];
        foreach ($cartGoods as $key => $value) {
            $storeId = $key;
            $storeGoodsList = $value;
            // 如果是0类型 则不需要过滤
            if (pickupIsMall($this->storeInfo)) {
                foreach ($pickups as $pickup) {
                    if ($pickup['store_id'] == $storeId || $pickup['store_id'] == $this->cartStoreId) {
                        if (!isset($activePickups[$storeId])) {
                            $activePickups[$storeId] = [];
                        }
                        $activePickups[$storeId][] = $pickup;
                    }
                }
            } else {
                foreach ($pickups as $pickup) {
                    // limit_goods_list为当前门店关联的goods_id spec_id信息, 只要当前店铺购买的商品中存在一个符合关联的商品，则该门店就可以选择
                    foreach ($pickup['limit_goods_list'] as $limit) {
                        // 这里的循环是拿着$limit规则去购买的商品中找到符合规则的商品，且有库存, 则找到则当前门店即可选
                        foreach ($storeGoodsList as $goods) {
                            if ($goods['goods_id'] == $limit['goods_id'] &&
                                $goods['primary_id'] == $limit['spec_id'] &&
                                $limit['storage'] >= $goods['buy_num']) {
                                if (!isset($activePickups[$storeId])) {
                                    $activePickups[$storeId] = [];
                                }
                                $activePickups[$storeId][] = $pickup;
                                break 2;
                            }
                        }
                    }
                }
            }
        }
        return $activePickups;
    }

    public function buyAgain($orderId = 0)
    {
        $model = M("mb_order");
        $where = array();
        $where['order_id'] = $orderId;
        $order = $model->where($where)->find();
        $goods = json_decode($order['order_content'], true);
        $result = $this->onceAgain($goods);
        $failedData = $result['data'];
        // 组装下失败的提示语
        $tipData = [];
        if (!empty($failedData)) {
            foreach ($failedData as $failedGood) {
                foreach ($goods as $good) {
                    $good['specid'] = (empty($good['specid']) || $good['specid'] == 'NULL') ? 0 : $good['specid'];
                    if ($failedGood['goods_id'] == $good['goods_id'] && $failedGood['specid'] == $good['specid']) {
                        $tip = "[商品：{$good['goods_name']}-{$failedGood['msg']}]";
                        $tipData[] = $tip;
                    }
                }
            }
        }
        $msg = empty($tipData) ? '' : implode("\n", $tipData);
        return getReturn($result['code'], $msg, $msg);
    }

    /**
     * 商品详情页加入购物车
     * @param string $goodsId
     * @param string $specId
     * @param int $addNum
     * @param string $buyType
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-04-19 15:53:00
     * Update: 2018-04-19 15:53:00
     * Version: 1.00
     */
    public function infoAdd($goodsId = '', $specId = '', $addNum = 0, $buyType = '')
    {
        $oldNum = $this->getNumWithID("{$goodsId}|{$specId}");
        $addNum = $addNum > 0 ? $addNum : 1;
        $num = $oldNum + $addNum;
        $otherData = [];
        if ($buyType == 'quickBuy') {
            $num = 1;
            $otherData['from_type'] = 1;
        }
        $result = $this->modNum($goodsId, $specId, $num, $otherData);
        if (!empty($result)) return getReturn(406, $result);
        return getReturn(200, 'success', $this->getNum());
    }

    /**
     * 判断当前商家是否是商城
     * @return boolean
     * User: hjun
     * Date: 2018-05-04 22:04:28
     * Update: 2018-05-04 22:04:28
     * Version: 1.00
     */
    public function isMall()
    {
        if ($this->visitStoreId != $this->cartStoreId) {
            return true;
        } else {
            $storeInfo = D('Store')->getStoreInfo($this->cartStoreId)['data'];
            return in_array($storeInfo['store_type'], [0, 2]);
        }
    }

    /**
     * 判断商品是否是供应商提供的商品
     * @param array $goodsInfo
     * @return boolean
     * User: hjun
     * Date: 2018-05-04 22:02:08
     * Update: 2018-05-04 22:02:08
     * Version: 1.00
     */
    public function isSupplierGoods($goodsInfo = [])
    {
        if ($this->isMall()) {
            return false;
        } else {
            // 如果商品的商家ID和当前商家ID不相等 则是供应商
            return $this->cartStoreId != $goodsInfo['store_id'];
        }
    }

    /**
     * 判断规格是否为空
     * @param $specId
     * @return boolean
     * User: hjun
     * Date: 2018-05-05 17:36:50
     * Update: 2018-05-05 17:36:50
     * Version: 1.00
     */
    public function isSpecIdEmpty($specId)
    {
        if (strpos($specId, 'null') !== false) {
            return true;
        }
        return empty($specId);
    }

    /**
     * 清理脏数据
     * User: hjun
     * Date: 2018-05-05 17:59:04
     * Update: 2018-05-05 17:59:04
     * Version: 1.00
     */
    public function clearDirtyItems()
    {
        $isDirty = false;
        foreach ($this->items as $gsId => $value) {
            $ids = explode('|', $gsId);
            $goodsId = $ids[0];
            $specId = $ids[1];
            // 这里能判断出是否是脏数据 只要specId不是0但又为空
            if ($this->isSpecIdEmpty($specId) && $specId !== '0') {
                $isDirty = true;
                if ($this->hasItem("{$goodsId}|0")) {
                    $this->items["{$goodsId}|0"]['num'] += $value['num'];
                } else {
                    $this->items["{$goodsId}|0"] = $value;
                }
                unset($this->items[$gsId]);
            }
        }
        if ($isDirty) {
            $this->saveItem();
        }
    }

    /**
     * 获取购物车商品列表
     * @param $goodsBeans
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-05-05 17:51:37
     * Update: 2018-05-05 17:51:37
     * Version: 1.00
     */
    public function getGoodsListByItems($goodsBeans)
    {
        $items = $this->getAll();
        foreach ($items as $k => $v) {
            $ids = explode('|', $k);
            $specId = $this->isSpecIdEmpty($ids[1]) ? 0 : $ids[1];
            for ($i = 0; $i < count($goodsBeans); $i++) {
                if ($ids[0] == $goodsBeans[$i]['goods_id']) {
                    $goodsBean = $goodsBeans[$i];
                    if (!empty($goodsBean['is_spec'])) {
                        for ($j = 0; $j < count($goodsBean['spec_option']); $j++) {
                            if ($specId == $goodsBean['spec_option'][$j]['specs']) {
                                if ($goodsBean['spec_option'][$j]['supplier_price']) {
                                    $goodsBean['supplier_price'] = number_format($goodsBean['spec_option'][$j]['supplier_price'], 2, '.', '');
                                }
                                $goodsBean['goods_price'] = number_format($goodsBean['spec_option'][$j]['goods_price'], 2, '.', '');
                                $goodsBean['new_price'] = number_format($goodsBean['spec_option'][$j]['new_price'], 2, '.', '');
                                $suffix = empty($goodsBean['spec_option'][$j]['title']) ? '' : L('SPEC');
                                $goodsBean['spec_name'] = $suffix . $goodsBean['spec_option'][$j]['title'];
                                $goodsBean['spec_name_title'] = $goodsBean['spec_option'][$j]['title'];
                                $goodsBean['buy_num'] = $v['num'];
                                $goodsBean['select_state'] = $v['state'];
                                // hj 2018-01-01 21:40:03 获取mj_id 和 mj_level
                                $goodsBean['mj_id'] = (int)$v['mj_id'];
                                $goodsBean['mj_level'] = (int)$v['mj_level'];

                                $goodsBean['goods_pv'] = $goodsBean['spec_option'][$j]['goods_pv'];
                                $goodsBean['spec_id'] = $goodsBean['spec_option'][$j]['specs'];
                                $goodsBean['primary_id'] = $goodsBean['spec_option'][$j]['id'];
                                $goodsBean['gs_id'] = $goodsBean['goods_id'] . '|' . $goodsBean['spec_option'][$j]['specs'];
                                $goodsBean['gid_sid'] = $goodsBean['goods_id'] . '_' . $goodsBean['spec_option'][$j]['specs'];
                                $goodsBean['goods_storage'] = $goodsBean['storage'][$goodsBean['spec_id']];
                                $result[] = $goodsBean;
                            }
                        }
                    } else {
                        if ((double)$items[$goodsBean['goods_id'] . '|0']['num'] > 0) {
                            $goodsBean['spec_id'] = 0;
                            $goodsBean['primary_id'] = '0';
                            $goodsBean['gs_id'] = $goodsBean['goods_id'] . '|0';
                            $goodsBean['gid_sid'] = $goodsBean['goods_id'] . '_0';
                            $goodsBean['goods_storage'] = $goodsBean['storage']['0'];
                            $result[] = $goodsBean;
                        }
                    }
                }
            }
        }
        return empty($result) ? [] : $result;
    }

    public function flushItem()
    {
        $this->items = [];
    }

    public function getVersion()
    {
        return $this->version;
    }

    /**
     * 批量编辑
     * @param array $cartData
     * @return mixed
     * User: hjun
     * Date: 2018-05-31 15:12:43
     * Update: 2018-05-31 15:12:43
     * Version: 1.00
     */
    public function batchMod($cartData = [])
    {
        // 批量修改时不能保存
        $this->canSave = false;
        $error = [];
        foreach ($cartData as $goods) {
            $ids = explode('|', $goods['gs_id']);
            $goodsId = $ids[0];
            $specId = $this->isSpecIdEmpty($ids[1]) ? 0 : $ids[1];
            $num = $goods['num'] > 0 ? $goods['num'] : 0;
            $results = $this->modNum($goodsId, $specId, $num);
            if (!empty($results)) $error[] = $results;
        }
        $this->canSave = true;
        $result = $this->saveItem();
        logWrite("保存结果:" . json_encode($result) . ",批量编辑后的数据:" . json_encode($this->getAll()));
        if (false === $result) {
            return false;
        }
        return $error;
    }

    /**
     * 判断商品是否下架
     * @param array $goods
     * @return boolean
     * User: hjun
     * Date: 2018-06-19 13:50:59
     * Update: 2018-06-19 13:50:59
     * Version: 1.00
     */
    public function isDown($goods = [])
    {
        return ($goods['isdelete'] != 0 || $goods['goods_state'] != 1);
    }
}