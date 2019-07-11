<?php

namespace Common\Logic;

class GoodsPriceLogic extends BaseLogic
{
    // 会员商家信息数据块
    private $storeMemberDiscountInfoBlock;

    // 自定义价格表数据块
    private $priceTableBlock;

    // 商品自定义价格数据块
    private $goodsCustomDefinitionPrice;


    // 是否有代理商权限
    private function isAgent($info)
    {
        return $info['partner_ctrl'] == 1 && $info['group_id'] > 0;
    }

    // 是否开启了VIP
    private function isOpenVip($info)
    {
        return $info['member_vip'] == 1;
    }

    // 是否有VIP折扣
    public function isVipDiscount($info)
    {
        return $this->isOpenVip($info) && $info['vip_discount'] > 0;
    }

    // 是否是自定义价格
    private function isCustomDefinitionPrice($info)
    {
        return $this->isAgent($info) && $info['discount_type'] == 2;
    }

    // 是否不是自定义价格
    private function isNotCustomDefinitionPrice($info)
    {
        return $this->isAgent($info) && !$this->isCustomDefinitionPrice($info);
    }

    // 商品是否促销
    private function isGoodsPromotion($state)
    {
        return $state == 1 || $state == 2;
    }

    /**
     * 判断是否强制团购
     * @param $info
     * @return boolean
     * User: hjun
     * Date: 2019-05-20 15:43:35
     * Update: 2019-05-20 15:43:35
     * Version: 1.00
     */
    private function isForceGroup($info)
    {
        return $info['group_buy_ctrl'] == 1 && $info['is_force_group'] == 1;
    }

    /**
     * 获取会员在某商家的折扣信息
     * @param int $storeId
     * @param int $memberId
     * @return array
     * User: hjun
     * Date: 2018-09-28 17:12:09
     * Update: 2018-09-28 17:12:09
     * Version: 1.00
     */
    public function getStoreMemberDiscountInfo($storeId = 0, $memberId = 0)
    {
        if (isset($this->storeMemberDiscountInfoBlock[$storeId][$memberId])) {
            return $this->storeMemberDiscountInfoBlock[$storeId][$memberId];
        }
        if (empty($storeId) || empty($memberId)) {
            return [];
        }
        $field = [
            'a.store_id', 'a.member_id',
            'b.group_id', 'b.discount agent_discount', 'b.discount_type', 'b.store_group_price_id',
            'c.vip_level', 'c.discount vip_discount',
            'd.member_vip', 'd.is_force_group', 'd.price_conflict_type',
        ];
        $field = implode(',', $field);
        $where = [];
        $where['a.store_id'] = $storeId;
        $where['a.member_id'] = $memberId;
        $info = M('mb_storemember')
            ->alias('a')
            ->field($field)
            ->where($where)
            ->join('__STORE__ d ON a.store_id = d.store_id')
            ->join('LEFT JOIN __MB_STOREGROUP__ b ON a.group_id = b.group_id')
            ->join('LEFT JOIN __MB_STOREVIP__ c ON a.level = c.vip_level AND a.store_id = c.store_id')
            ->find();
        $auth = D('Store')->getStoreGrantInfo($storeId)['data'];
        $info['partner_ctrl'] = $auth['partner_ctrl'];
        $info['group_buy_ctrl'] = $auth['group_buy_ctrl'];
        $this->storeMemberDiscountInfoBlock[$storeId][$memberId] = $info;
        return $info;
    }

    /**
     * 获取自定义价格表
     * @param $priceId
     * @return array
     * User: hjun
     * Date: 2018-09-28 18:34:45
     * Update: 2018-09-28 18:34:45
     * Version: 1.00
     */
    public function getPriceTable($priceId)
    {
        if (isset($this->priceTableBlock[$priceId])) {
            return $this->priceTableBlock[$priceId];
        }
        $where = [];
        $where['store_group_price_id'] = $priceId;
        $this->priceTableBlock[$priceId] = M('mb_store_group_price_data')->field(true)->where($where)->select();
        return $this->priceTableBlock[$priceId];
    }

    /**
     * 获取商品自定义价格
     * @param $priceId
     * @param $goodsId
     * @param $specId
     * @return mixed false表示没有
     * User: hjun
     * Date: 2018-09-28 18:28:39
     * Update: 2018-09-28 18:28:39
     * Version: 1.00
     */
    public function getGoodsCustomDefinitionPrice($priceId, $goodsId, $specId)
    {
        if (isset($this->goodsCustomDefinitionPrice[$priceId][$goodsId][$specId])) {
            return $this->goodsCustomDefinitionPrice[$priceId][$goodsId][$specId];
        }
        $result = false;
        $priceTable = $this->getPriceTable($priceId);
        foreach ($priceTable as $price) {
            if ($price['goods_id'] === "{$goodsId}" && $price['spec_id'] === "{$specId}") {
                if (isset($price['price'])) {
                    $result = $price['price'];
                }
                break;
            }
        }
        $this->goodsCustomDefinitionPrice[$priceId][$goodsId][$specId] = $result;
        return $result;
    }

    /**
     * 获取商品真实价格以及状态
     * @param int $storeId
     * @param int $memberId
     * @param $goods
     * @param $goodsId
     * @param $goodsPrice
     * @param $goodsPv
     * @param $goodsSpecId
     * @param $state 1-促销 2-抢购 3-会员 4-代理商 5-团购
     * @return mixed
     * User: hjun
     * Date: 2018-09-28 17:35:50
     * Update: 2018-09-28 17:35:50
     * Version: 1.00
     */
    public function setGoodsTruePriceAndState($storeId = 0, $memberId = 0, &$goods, $goodsId, $goodsPrice, $goodsPv, $goodsSpecId, &$state)
    {
        // 优先级 团购价 > 自定义价格 > 促销价格 > 不是自定义价格的代理商 > 会员
        $discountInfo = $this->getStoreMemberDiscountInfo($storeId, $memberId);
        // 团购
        if ($this->isForceGroup($discountInfo)) {
            $result = D('GroupBuy')->checkGoodsInGroup($goodsId);
            if (!empty($result['msg'])) {
                $group = $result['data'];
            }
        }
        if (!empty($group)) {
            $state = 5;
            $spec = jsonDecodeToArr($group['spec_group_price']);
            $goods['new_price'] = $spec["spec_id_{$goodsSpecId}"]['spec_group_price'];
        } elseif ($this->isCustomDefinitionPrice($discountInfo)) {
            // 自定义价格
            $price = $this->getGoodsCustomDefinitionPrice($discountInfo['store_group_price_id'], $goodsId, $goodsSpecId);
            logWrite("商品:" . jsonEncode($goods));
            logWrite("价格:{$price} - {$goods['new_price']}，状态:{$state}");
            // 如果是促销或者抢购 此时冲突 根据商家配置
            if ($this->isGoodsPromotion($state)) {
                $priceConflictType = $discountInfo['price_conflict_type'];
                switch ($priceConflictType) {
                    case 1:
                        // 最低价 如果促销价较低 则使用促销价 直接返回即可
                        if ($goods['new_price'] < $price) {
                            return [$goods['new_price'], $state];
                        }
                        break;
                    default:
                        // 代理价
                        break;
                }
            }
            if ($price !== false) {
                $goods['new_price'] = $price;
            } else {
                $goods['new_price'] = $goodsPrice;
            }
            $state = 4;
        } elseif ($this->isGoodsPromotion($state)) {
            // 促销价格不必修改
        } elseif ($this->isNotCustomDefinitionPrice($discountInfo)) {
            // 折扣价格
            $state = 4;
            $discount = round($discountInfo['agent_discount'] / 10, 2);
            if ($discount > 0) {
                switch ((int)$discountInfo['discount_type']) {
                    case 0:
                        $goods['new_price'] = round($goodsPrice * $discount, 2);
                        break;
                    case 1:
                        $goods['new_price'] = round($goodsPrice - $goodsPv + $goodsPv * $discount, 2);
                        break;
                    default:
                        break;
                }
            } else {
                $goods['new_price'] = $goodsPrice;
            }
        } elseif ($this->isVipDiscount($discountInfo)) {
            // 会员价格
            $state = 3;
            $discount = round($discountInfo['vip_discount'] / 10, 2);
            if ($discount > 0) {
                $goods['new_price'] = round($goodsPrice * $discount, 2);
            } else {
                $goods['new_price'] = $goodsPrice;
            }
        } else {
            $goods['new_price'] = $goodsPrice;
        }
        return [$goods['new_price'], $state];
    }
}