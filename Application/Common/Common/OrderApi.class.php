<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/1/6
 * Time: 5:10
 */

namespace Common\Common;

use Api\Controller\MobileBaseController;
use Common\Common\commission;
use Common\Logic\GoodsPriceLogic;

class OrderApi
{
    const ERROR_OFF_SHELF = 0; // 已下架
    const ERROR_PRICE = 1; // 价格变动
    const ERROR_PROMOTE_PRICE = 2; // 促销价格变动
    const ERROR_PROMOTE_END = 3; // 促销结束
    const ERROR_LIMIT_BUY = 4; // 限购
    const ERROR_STOCK = 5; // 库存不足
    const ERROR_SPEC = 6; // 规格变动

    /**
     * 返回分组的折扣
     * @param array $groupData
     * @return double
     * User: hjun
     * Date: 2018-01-23 17:00:38
     * Update: 2018-01-23 17:00:38
     * Version: 1.00
     */
    public function getGroupDiscount($groupData = array())
    {
        switch ((int)$groupData['discount_type']) {
            case 0:
            case 1:
                $discount = $groupData['discount'];
                break;
            case 2:
            case 3:
            default:
                $discount = 10;
                break;
        }
        return $discount;
    }

    /**
     * 获取商品的规格ID
     * @param array $goods
     * @return string
     * User: hjun
     * Date: 2018-11-08 14:53:10
     * Update: 2018-11-08 14:53:10
     * Version: 1.00
     */
    public function getSpecId($goods = [])
    {
        if (!empty($goods['spec_group'])) {
            return $goods['spec_group'];
        }
        if (!empty($goods['spec_id'])) {
            return $goods['spec_id'];
        }
        if (!empty($goods['specid'])) {
            return $goods['specid'];
        }
        return '0';
    }

    /**
     * 判断商品的规格状态是否改变
     * @param array $goodsBean
     * @param array $orderGoodsBean
     * @return boolean
     * User: hjun
     * Date: 2018-11-12 14:59:30
     * Update: 2018-11-12 14:59:30
     * Version: 1.00
     */
    public function isSpecChange($goodsBean = [], $orderGoodsBean = [])
    {
        if (isset($orderGoodsBean['spec_open']) && isset($goodsBean['spec_open'])) {
            $orderGoodsBean['spec_open'] = $orderGoodsBean['spec_open'] == 1 ? 1 : 0;
            $goodsBean['spec_open'] = $goodsBean['spec_open'] == 1 ? 1 : 0;
            return $orderGoodsBean['spec_open'] !== $goodsBean['spec_open'];
        }
        return false;
    }

    /**
     * 是否是多规格组合
     * @param array $goodsBean
     * @return boolean
     * User: hjun
     * Date: 2018-11-12 15:04:33
     * Update: 2018-11-12 15:04:33
     * Version: 1.00
     */
    public function isSpecGroup($goodsBean = [])
    {
        return $goodsBean['spec_open'] == 1;
    }

    /**
     * 设置错误数据
     * @param array $errorData
     * @param $errorGoods
     * @param string $specId
     * @param int $type
     * @param string $desc
     * @return array
     * User: hjun
     * Date: 2018-11-08 16:15:33
     * Update: 2018-11-08 16:15:33
     * Version: 1.00
     */
    public function setErrorGoodsData(&$errorData = [], $errorGoods, $specId = '', $type = self::ERROR_OFF_SHELF, $desc = '')
    {
        $item = [];
        $item['goods_id'] = $errorGoods['goods_id'];
        $item['name'] = $errorGoods['goods_name'];
        if ($type === self::ERROR_OFF_SHELF || $type === self::ERROR_SPEC) {
            $item['is_delete'] = 1;
        } elseif ($type === self::ERROR_PROMOTE_PRICE) {
            $item['price'] = empty($errorGoods['new_price']) ? '0' : $errorGoods['new_price'];
        } elseif ($type === self::ERROR_PRICE || $type === self::ERROR_PROMOTE_END) {
            $item['price'] = empty($errorGoods['goods_price']) ? '0' : $errorGoods['goods_price'];
        } elseif ($type === self::ERROR_STOCK) {
            $item['kucun'] = empty($errorGoods['goods_storage']) ? '0' : $errorGoods['goods_storage'];
        }
        $item['spec_id'] = $specId;
        $item['error_desc'] = $desc;
        $errorData["{$errorGoods['goods_id']}|{$specId}"] = $item;
        return $errorGoods;
    }

    /**
     * 检查商品
     * @param $is_new_discount
     * @param $order_content
     * @param $store_id
     * @param $buyer_id
     * @param $orderData
     * @return array
     * User: hjun
     * Date: 2018-11-08 15:58:28
     * Update: 2018-11-08 15:58:28
     * Version: 1.00
     */
    public function checkGoods($is_new_discount, &$order_content, $store_id, $buyer_id, $orderData = [])
    {
        logWrite("检查商品数据:" . json_encode($order_content, 256) . ',会员ID:' . $buyer_id);
        //检查是否可下单(主要是库存)
        $model_goods = Model("goods");
        $model_sales = Model("mb_sales");
        $quehuo_array = array();
        $quehuo_item = array();

        $limit_buy_array = array();
        $limit_buy_item = array();
        $storeInfo = D('Store')->getStoreInfo($store_id)['data'];

        for ($i = 0; $i < count($order_content); $i++) {

            // region 变量赋值
            $quehuo_item = array();
            $post_goods_id = $order_content[$i]['goods_id']; //商品ID
            $gou_num = $order_content[$i]['gou_num']; // 购买数量
            $goods_state = $order_content[$i]['state']; // 商品状态
            $goods_price = $order_content[$i]['goods_price']; // 前端的商品价格
            // endregion

            // region 获取规格 hjun 2018-11-08 15:04:54
            $spec_id = $this->getSpecId($order_content[$i]);
            $spec_group = $spec_id;
            $specid = $spec_id;
            // endregion

            // region 判断商品是否下架或被删除(标记为已下架) 在商品表获取该商品的库存
            $kucun = $model_goods->where(array(
                'goods_id' => $post_goods_id
            ))->find();
            if ($kucun['goods_state'] == 0 || $kucun['isdelete'] == 1) {
                $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_OFF_SHELF, '商品已下架');
                continue;
            }
            // 判断规格状态是否变动
            if ($this->isSpecChange($kucun, $order_content[$i])) {
                $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_SPEC, '规格状态变动');
                continue;
            }
            // endregion

            // region add by czx 2018/3/21 22:33:35 在此处初始化订单商品想要的数据，避免重复查询
            $order_content[$i]['goods_name'] = $kucun['goods_name'];
            $order_content[$i]['goods_figure'] = $kucun['goods_figure'];
            $order_content[$i]['goods_image'] = $kucun['goods_image'];
            $order_content[$i]['goods_pv'] = $kucun['goods_pv'];
            $order_content[$i]['thirdpart_money_limit'] = $kucun['thirdpart_money_limit'];
            $order_content[$i]['allow_coupon'] = $kucun['allow_coupon'];
            //$order_content[$i]['goods_barcode'] = $kucun['goods_barcode'];
            $order_content[$i]['gc_id'] = $kucun['gc_id'];
            $order_content[$i]['mall_goods_class_1'] = $kucun['mall_goods_class_1'];
            $order_content[$i]['mall_goods_class_2'] = $kucun['mall_goods_class_2'];
            $order_content[$i]['mall_goods_class_3'] = $kucun['mall_goods_class_3'];
            $order_content[$i]['freight_type'] = $kucun['freight_type'];
            $order_content[$i]['freight_tpl_id'] = $kucun['freight_tpl_id'];
            $order_content[$i]['store_id'] = $kucun['store_id'];
            // endregion

            // region 限购 在循环中记录每个goods_id购买的数量
            if ($kucun['limit_buy'] >= 0) {
                if (array_key_exists($kucun['goods_id'], $limit_buy_array)) {
                    $temp = $limit_buy_array[$kucun['goods_id']];
                    $temp['buy_num'] = $temp['buy_num'] + $gou_num;
                    $limit_buy_array[$kucun['goods_id']] = $temp;
                } else {
                    $limit_buy_item['goods_id'] = $kucun['goods_id'];
                    $limit_buy_item['name'] = $kucun['goods_name'];
                    $limit_buy_item['buy_num'] = $gou_num;
                    $limit_buy_item['limit_buy'] = $kucun['limit_buy'];
                    $limit_buy_array[$kucun['goods_id']] = $limit_buy_item;
                }
            }
            // endregion

            // region 初始化商品规格的基本价格(供应商价格,如果不供应了,标记为已下架)、库存
            if ($this->isSpecGroup($kucun)) {
                //add by honglj 如果是多规格的情况则获取其对应的价格
                $where = [];
                $where['goods_id'] = $kucun['goods_id'];
                $where['specs'] = $spec_id;
                $spec_detail = Model('goods_option')->field('id,goods_pv,stock,goods_promoteprice,goods_price')->where($where)->find();
                if (empty($spec_detail)) {
                    $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_SPEC, '规格变动');
                    continue;
                }
                // 如果是在门店下单并且门店是独立销售的 则查询门店的库存
                if ($orderData['pickup_id'] > 0 && pickupIsAlone($storeInfo)) {
                    $spec_detail['stock'] = D('DepotGoods')->getGoodsStorage($orderData['pickup_id'], $kucun['goods_id'], $spec_detail['id']);
                }
                $kucun['goods_price'] = $spec_detail['goods_price'];
                $kucun['goods_storage'] = $spec_detail['stock'];
                $kucun['goods_pv'] = $spec_detail['goods_pv'];
                $kucun['new_price'] = $spec_detail['goods_promoteprice'];
            } else {
                // 不是第一个规格的话，就把价格和库存替换
                if ($spec_id > 0) {
                    $goods_spec_array = json_decode($kucun['goods_spec'], true);
                    if (!isset($goods_spec_array[$spec_id])) {
                        $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_SPEC, '规格变动');
                        continue;
                    }
                    $kucun['goods_price'] = $goods_spec_array[$spec_id]['price'];
                    $kucun['goods_storage'] = $goods_spec_array[$spec_id]['storage'];
                    $kucun['goods_pv'] = $goods_spec_array[$spec_id]['pv'];
                }
            }
            //add by honglj 如果是供应商的商品则获取其真实的价格
            if ($kucun['store_id'] != $store_id) {
                $discount_ratio = Model('mb_supplier_agent')->field('discount, ratio')->where(array('supplier_sid' => $kucun['store_id'], 'agent_sid' => $store_id, 'is_delete' => '0', 'state' => '2'))->find();
                if ($discount_ratio) {
                    $temp_price = round($kucun['goods_price'] - ($kucun['goods_pv'] * (1.0 - $discount_ratio['discount'] / 10)), 2);
                    $kucun['goods_price'] = round($temp_price * (1.0 + 1.0 * $discount_ratio['ratio'] / 100), 2);
                    $kucun['goods_pv'] = $kucun['goods_price'] - $temp_price;
                } else {
                    $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_OFF_SHELF, '供应商停止供应');
                    continue;
                }
            }
            // endregion

            // region 根据会员信息获取商品真实价格
            $compareData = $this->getAgentAndVipPrice($kucun, $spec_group, $spec_id, $buyer_id, $store_id, $goods_state);
            $kucun['goods_price'] = $compareData['price'];
            // endregion

            // region 价格判断
            if (($goods_state == 1 || $goods_state == 2) && ($specid == 0 || $this->isSpecGroup($kucun))) {
                // region 促销价格判断
                if ($this->isSpecGroup($kucun)) {
                    // region 新版多规格 检查促销价格是否有误，并且判断促销时间
                    if ($kucun['is_qianggou'] == 1) {
                        if ($kucun['qianggou_start_time'] > TIMESTAMP) {
                            output_error(-12, $order_content[$i]['goods_name'] . '商品促销还未开始', '下单失败');
                        }
                        if ($kucun['qianggou_end_time'] < TIMESTAMP) {
                            $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_PROMOTE_END, '促销已结束');
                            continue;
                        }
                        if (isPriceDifferent($goods_price, $kucun['new_price'])) {
                            $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_PROMOTE_PRICE, '促销价格变动');
                            continue;
                        }
                    } else if ($kucun['is_promote'] == 1) {
                        if (isPriceDifferent($goods_price, $kucun['new_price'])) {
                            $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_PROMOTE_PRICE, '促销价格变动');
                            continue;
                        }
                    } else {
                        $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_PROMOTE_END, '促销已结束');
                        continue;
                    }
                    // endregion
                } else {
                    // region 旧版多规格的第一个规格 在促销表获取该商品的促销库存和销售量和促销价
                    $sales_bean = $model_sales->where(array(
                        'gid' => $post_goods_id,
                        'isdelete' => 0
                    ))->order('sales_id desc')->limit(1)->find();
                    if ($sales_bean) {
                        //检查促销价格是否有误，并且判断促销时间
                        $kucun['new_price'] = $sales_bean['newprice'];
                        if ($sales_bean['islongtime'] == 1) {
                            if (isPriceDifferent($goods_price, $sales_bean['newprice'])) {
                                $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_PROMOTE_PRICE, '促销价格已变动');
                                continue;
                            }
                        } else {
                            if ($sales_bean['start_time'] > TIMESTAMP) {
                                output_error(-12, $order_content[$i]['goods_name'] . '商品促销还未开始', '下单失败');
                            }
                            if ($sales_bean['end_time'] < TIMESTAMP) {
                                $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_PROMOTE_END, '促销已结束');
                                continue;
                            }
                            if (isPriceDifferent($goods_price, $sales_bean['newprice'])) {
                                $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_PROMOTE_PRICE, '促销价已变动');
                                continue;
                            }
                        }
                    } else {
                        //该商品不在促销表里面
                        if (isPriceDifferent($goods_price, $kucun['goods_price'])) {
                            $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_PROMOTE_END, '促销已结束');
                            continue;
                        }
                    }
                    // endregion
                }
                // endregion
            } else {
                // region 普通价格判断
                if (isPriceDifferent($goods_price, $kucun['goods_price'])) {
                    $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_PRICE, '价格已变动');
                    continue;
                }
                // endregion
            }
            // endregion

            // region 库存判断
            if ($kucun['goods_storage'] != -1) {
                if ($kucun['goods_storage'] >= $gou_num) {

                } else {
                    $this->setErrorGoodsData($quehuo_array, $kucun, $spec_id, self::ERROR_STOCK, '库存不足');
                    continue;
                }
            }
            // endregion
        }

        // region 错误返回
        $quehuo_array = array_values($quehuo_array);
        if (count($quehuo_array) > 0) {
            logWrite("错误数据:" . jsonEncode($quehuo_array));
            return $quehuo_array;
        }
        // endregion

        // region 无错误继续判断限购
        if (count($limit_buy_array) > 0) {
            $model_limit_buy = Model("mb_limit_buy");
            foreach ($limit_buy_array as $item) {
                $buy_data = $model_limit_buy->where(array(
                    'member_id' => $buyer_id,
                    'goods_id' => $item['goods_id']
                ))->find();
                $buy_history = 0;
                if (!empty($buy_data) && !empty($buy_data['buy_num'])) {
                    $buy_history = $buy_data['buy_num'];
                }
                if ($item['buy_num'] + $buy_history > $item['limit_buy']) {
                    $quehuo_item['goods_id'] = $item['goods_id'];
                    $quehuo_item['name'] = $item['name'];
                    $quehuo_item['limit_buy'] = $item['limit_buy'];
                    $quehuo_item['history_buy'] = $buy_history;
                    $quehuo_item['kucun'] = $item['limit_buy'] - $buy_history;
                    $quehuo_array[] = $quehuo_item;
                    logWrite("错误数据:" . jsonEncode($quehuo_array));
                    return $quehuo_array;
                }
            }
        }
        // endregion

        return array();
    }


    /**
     * 订单的库存和销量增减
     * $order_id 订单ID或联盟订单ID
     * $order_content
     * $order_type 订单类型，0:普通订单 1:联盟订单
     * $option_type 操作类型 0:恢复库存减销量 1:减库存增销量 2:下架减销量
     */
    public function optionStorageAndSales($order_id, $order_content, $order_type, $option_type, $store_id, $buyer_id = 0, $trueOrderId)
    {
        if ($order_id != -1 && $option_type == 0 && $order_type == 0) {
            // 在联盟订单表标记已恢复过库存
            Model("mb_unionorder")->where(array('order_id' => $order_id))->update(array('restored_storage' => 1));
        } else if ($order_id != -1 && $option_type == 0 && $order_type == 1) {
            $result = Model("mb_unionorder")->field('restored_storage')->where(array('id' => $order_id))->find();
            if ($result['restored_storage'] == 1) {
                // 说明该联盟订单对应的商品已经被恢复库存过
                return getReturn(CODE_SUCCESS, 'success');
            }
            Model("mb_unionorder")->where(array('id' => $order_id))->update(array('restored_storage' => 1));
        }

        $model_goods = Model("goods");
        $model_sales = Model("mb_sales");
        $mb_goods_exp = Model('mb_goods_exp');
        $orderlist = $order_content;
        $storeInfo = D('Store')->getStoreInfo($store_id)['data'];

        $limit_buy_array = array();
        $limit_buy_item = array();

        for ($i = 0; $i < count($orderlist); $i++) {

            if (array_key_exists('supplier_state', $orderlist[$i])) {
                if ($orderlist[$i]['supplier_state'] == 3) {
                    continue;
                }
            }

            $post_goods_id = $orderlist[$i]['goods_id']; //商品ID
            $gou_num = $orderlist[$i]['gou_num'];
            $goods_state = $orderlist[$i]['goods_state'];

            if (array_key_exists('store_id', $orderlist[$i])) {
                $store_id = $orderlist[$i]['store_id'];
            } else {
                $store_id = $store_id;
            }
            $specid = $this->getSpecId($orderlist[$i]);
            $spec_group = $specid;
            //在商品表获取该商品的库存
            $kucun = $model_goods->field('goods_storage,goods_spec,spec_open,limit_buy')->where(array(
                'goods_id' => $post_goods_id
            ))->find();

            // 如果商品规格状态已经变动的话 则忽略库存、销量的增减
            logWrite("规格状态:{$kucun['spec_open']},{$order_content[$i]['spec_open']}");
            if ($this->isSpecChange($kucun, $order_content[$i])) {
                continue;
            }

            if ($kucun['limit_buy'] >= 0) {//限购
                if (array_key_exists($post_goods_id, $limit_buy_array)) {
                    $temp = $limit_buy_array[$post_goods_id];
                    $temp['buy_num'] = $temp['buy_num'] + $gou_num;
                    $limit_buy_array[$post_goods_id] = $temp;
                } else {
                    $limit_buy_item['goods_id'] = $post_goods_id;
                    $limit_buy_item['buy_num'] = $gou_num;
                    $limit_buy_array[$post_goods_id] = $limit_buy_item;
                }
            }

            if ($this->isSpecGroup($kucun)) {
                // 如果是门店订单并且门店是独立的 则库存和销量的增减都在门店商品表
                if (!isset($trueOrderId)) {
                    if ($order_id > 0) {
                        $trueOrderId = $order_id;
                    }
                }
                $order = D('Order')->field('order_id,pickup_id')->find($trueOrderId);
                logWrite("库存操作:{$option_type},订单:" . jsonEncode($order));
                if ($order['pickup_id'] > 0 && pickupIsAlone($storeInfo)) {
                    $srModel = D('DepotSendDetail')->setStoreId($store_id);
                    if ($option_type == 1) {
                        // 减少库存增销量
                        $result = $srModel->addDetailByOrderGoods($trueOrderId, $order_content[$i], $srModel::STOCK_OUT, $srModel::TYPE_SALE_OUT);
                    } else {
                        // 恢复库存减销量
                        $result = $srModel->addDetailByOrderGoods($trueOrderId, $order_content[$i], $srModel::STOCK_IN, $srModel::TYPE_REFUND_IN);
                    }
                    if (!isSuccess($result)) {
                        return $result;
                    }
                    continue;
                }
                $spec_detail = Model('goods_option')->field('stock,sales')->where("specs = '" . $spec_group . "'")->find();
                //更新库存
                if ($option_type == 1) {
                    // 库存不充足时需要处理库存
                    if ($spec_detail['stock'] > -1) {
                        $spec_detail['stock'] = intval($spec_detail['stock']) - intval($gou_num);
                        if ($spec_detail['stock'] < 0) {
                            $spec_detail['stock'] = 0;
                        }
                    }
                    $spec_detail['sales'] = intval($spec_detail['sales']) + intval($gou_num);
                    $saleNum = intval($gou_num);
                } else {
                    if ($spec_detail['stock'] > -1) {
                        $spec_detail['stock'] = intval($spec_detail['stock']) + intval($gou_num);
                    }
                    $spec_detail['sales'] = intval($spec_detail['sales']) - intval($gou_num);
                    if ($spec_detail['sales'] < 0) {
                        $spec_detail['sales'] = 0;
                    }
                    $saleNum = -1 * intval($gou_num);
                }
                Model('goods_option')->where("specs = '" . $spec_group . "'")->update($spec_detail);
                // 更新销量
                $saleData = [];
                $saleData['version'] = $mb_goods_exp->max('version') + 1;
                $saleData['sales_vol'] = ['exp', "sales_vol + {$saleNum}"];
                $mb_goods_exp->where(array(
                    'goods_id' => $post_goods_id
                ))->update($saleData);
                continue;
            }

            // if ($goods_state == 2) {
            //     //在促销表获取该商品的促销库存和销售量和促销价
            //     $sales_bean = $model_sales->field('num,sales_num,newprice,sales_id')->where(array(
            //         'gid' => $post_goods_id
            //     ))->order('sales_id desc')->limit(1)->find();
            //     if ($sales_bean != false) {
            //         //在促销表获取最大版本号后 + 1
            //         $version_max = $model_sales->field('version')->where(array(
            //             'storeid' => $store_id
            //         ))->order('version desc')->limit(1)->find();
            //         if ($version_max >= 0) $version_new = $version_max['version'] + 1;
            //         else $version_new = 2;
            //         //更新销量
            //         $sales_num = $sales_bean['sales_num'] - intval($gou_num);
            //         if ($sales_num < 0) $sales_num = 0;
            //         $returntest = $model_sales->where(array(
            //             'sales_id' => $sales_bean['sales_id']
            //         ))->update(array(
            //             'sales_num' => $sales_num,
            //             'version' => $version_new
            //         ));
            //         if ($kucun['goods_storage'] != - 1) {
            //             //在商品表获取最大版本号后 + 1
            //             $version_max = $model_goods->field('version')->where(array(
            //                 'store_id' => $store_id
            //             ))->order('version desc')->limit(1)->find();
            //             if ($version_max >= 0) $version_new = $version_max['version'] + 1;
            //             else $version_new = 2;
            //             //更新库存
            //             $kucun_now = intval($kucun['goods_storage']) + intval($gou_num);
            //             $returntest = $model_goods->where(array(
            //                 'goods_id' => $post_goods_id
            //             ))->update(array(
            //                 'goods_storage' => $kucun_now,
            //                 'version' => $version_new
            //             ));
            //             if (!$returntest) {
            //                 output_error(101, '恢复促销库存失败', '数据更新失败');
            //             }
            //         }
            //     }
            // }
            $goods_spec_array = json_decode($kucun['goods_spec'], true);
            if ($specid > 0 && !empty($goods_spec_array) && count($goods_spec_array) > 1) {
                $kucun['goods_storage'] = $goods_spec_array[$specid]['storage'];
            }
            if ($kucun['goods_storage'] != -1) {
                //在商品表获取最大版本号后 + 1
                $version_max = $model_goods->field('version')->order('version desc')->limit(1)->find();
                if ($version_max >= 0) $version_new = $version_max['version'] + 1;
                else $version_new = 2;
                //更新库存
                if ($option_type == 1) {
                    $kucun_now = intval($kucun['goods_storage']) - intval($gou_num);
                    if ($kucun_now < 0) {
                        $kucun_now = 0;
                    }
                } else {
                    $kucun_now = intval($kucun['goods_storage']) + intval($gou_num);
                }

                if ($option_type == 2) {
                    $returntest = $model_goods->where(array(
                        'goods_id' => $post_goods_id
                    ))->update(array(
                        'goods_state' => 0,
                        'version' => $version_new
                    ));
                } else {
                    if ($specid >= 0 && !empty($goods_spec_array)) { // 如果是有规格，也要恢复库存
                        if ($specid == 0) {
                            $goods_spec_array[$specid]['storage'] = $kucun_now;
                            $goods_spec = json_encode($goods_spec_array, JSON_UNESCAPED_UNICODE);
                            $returntest = $model_goods->where(array(
                                'goods_id' => $post_goods_id
                            ))->update(array(
                                'goods_storage' => $kucun_now,
                                'goods_spec' => $goods_spec,
                                'version' => $version_new
                            ));
                        } else {
                            $goods_spec_array[$specid]['storage'] = $kucun_now;
                            $goods_spec = json_encode($goods_spec_array, JSON_UNESCAPED_UNICODE);
                            $returntest = $model_goods->where(array(
                                'goods_id' => $post_goods_id
                            ))->update(array(
                                'goods_spec' => $goods_spec,
                                'version' => $version_new
                            ));
                        }
                    } else {
                        if (!empty($goods_spec_array)) {
                            $goods_spec_array[0]['storage'] = $kucun_now;
                            $goods_spec = json_encode($goods_spec_array, JSON_UNESCAPED_UNICODE);
                            $returntest = $model_goods->where(array(
                                'goods_id' => $post_goods_id
                            ))->update(array(
                                'goods_storage' => $kucun_now,
                                'goods_spec' => $goods_spec,
                                'version' => $version_new
                            ));
                        } else {
                            $returntest = $model_goods->where(array(
                                'goods_id' => $post_goods_id
                            ))->update(array(
                                'goods_storage' => $kucun_now,
                                'version' => $version_new
                            ));
                        }
                    }
                }

                if (!$returntest) {
                    output_error(102, '恢复商品库存失败', '数据更新失败');
                } else {
                    $sales_vol_data = $mb_goods_exp->where(array(
                        'goods_id' => $post_goods_id
                    ))->find();
                    $version_max = $mb_goods_exp->field('version')->order('version desc')->limit(1)->find();
                    if ($version_max >= 0) $version_new = $version_max['version'] + 1;
                    else $version_new = 2;
                    if ($option_type == 1) {
                        $sales_vol = intval($sales_vol_data['sales_vol']) + intval($gou_num);
                    } else {
                        $sales_vol = intval($sales_vol_data['sales_vol']) - intval($gou_num);
                        if ($sales_vol < 0) {
                            $sales_vol = 0;
                        }
                    }

                    $rt = $mb_goods_exp->where(array(
                        'goods_id' => $post_goods_id
                    ))->update(array(
                        'sales_vol' => $sales_vol,
                        'version' => $version_new
                    ));
                    if (!$rt) {
                        $mb_goods_exp->add(array(
                            'goods_id' => $post_goods_id,
                            'sales_vol' => $sales_vol,
                            'version' => $version_new
                        ));
                        // output_error(102, '恢复销量失败2', '数据更新失败');
                    }
                }
            } else {
                if ($option_type == 2) {
                    //在商品表获取最大版本号后 + 1
                    $version_max = $model_goods->field('version')->order('version desc')->limit(1)->find();
                    if ($version_max >= 0) $version_new = $version_max['version'] + 1;
                    else $version_new = 2;
                    $returntest = $model_goods->where(array(
                        'goods_id' => $post_goods_id
                    ))->update(array(
                        'goods_state' => 0,
                        'version' => $version_new
                    ));
                }

                $sales_vol_data = $mb_goods_exp->where(array(
                    'goods_id' => $post_goods_id
                ))->find();
                $version_max = $mb_goods_exp->field('version')->order('version desc')->limit(1)->find();
                if ($version_max >= 0) $version_new = $version_max['version'] + 1;
                else $version_new = 2;
                if ($option_type == 1) {
                    $sales_vol = intval($sales_vol_data['sales_vol']) + intval($gou_num);
                } else {
                    $sales_vol = intval($sales_vol_data['sales_vol']) - intval($gou_num);
                    if ($sales_vol < 0) {
                        $sales_vol = 0;
                    }
                }
                $rt = $mb_goods_exp->where(array(
                    'goods_id' => $post_goods_id
                ))->update(array(
                    'sales_vol' => $sales_vol,
                    'version' => $version_new
                ));
                if (!$rt) {
                    $mb_goods_exp->add(array(
                        'goods_id' => $post_goods_id,
                        'sales_vol' => $sales_vol,
                        'version' => $version_new
                    ));
                    //output_error(102, '恢复销量失败', '数据更新失败');
                }
            }
        }
        if (count($limit_buy_array) > 0) {
            if ($order_id > 0 && $buyer_id == 0) {
                if ($order_type == 1) {
                    $buyer_data = Model("mb_unionorder")->field('buyer_id')->where(array('id' => $order_id))->find();
                } else {
                    $buyer_data = Model("mb_order")->field('buyer_id')->where(array('order_id' => $order_id))->find();
                }
                $buyer_id = $buyer_data['buyer_id'];
            }

            $model_limit_buy = Model("mb_limit_buy");
            foreach ($limit_buy_array as $item) {
                $buy_data = $model_limit_buy->where(array(
                    'member_id' => $buyer_id,
                    'goods_id' => $item['goods_id']
                ))->find();

                if (!empty($buy_data)) {
                    if ($option_type == 1) {
                        $buy_num = intval($buy_data['buy_num']) + intval($item['buy_num']);
                    } else {
                        $buy_num = intval($buy_data['buy_num']) - intval($item['buy_num']);
                        if ($buy_num < 0) {
                            $buy_num = 0;
                        }
                    }
                    $model_limit_buy->where(array(
                        'member_id' => $buyer_id,
                        'goods_id' => $item['goods_id']
                    ))->update(array(
                        'buy_num' => $buy_num
                    ));
                } else {
                    if ($option_type == 1) {
                        $buy_num = intval($item['buy_num']);
                        $model_limit_buy->insert(array(
                            'member_id' => $buyer_id,
                            'goods_id' => $item['goods_id'],
                            'buy_num' => $buy_num
                        ));
                    }
                }
            }
        }
        return getReturn(CODE_SUCCESS, 'success');
    }

    /**
     * 获取代理价或者会员价
     * @param array $kucun 商品对象
     * @param string $spec_group 商品新版多规格
     * @param string $specid 商品旧版规格
     * @param int $buyer_id 会员ID
     * @param int $store_id 商家ID
     * @param int $goods_state 商品状态
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-04-19 18:14:35
     * Update: 2018-04-19 18:14:35
     * Version: 1.00
     */
    protected function getAgentAndVipPrice($kucun = [], $spec_group = '', $specid = '', $buyer_id, $store_id, &$goods_state = 0)
    {
        logWrite("商品基础价格：{$kucun['goods_price']},PV:{$kucun['goods_pv']},商品状态：{$goods_state}");
        // hjun 当前商品的商家ID 商品的规格ID
        $goodsId = $kucun['goods_id'];
        $goodsSpecId = empty($spec_group) ? $specid : $spec_group;
        $goodsSpecId = $goodsSpecId == -1 ? '0' : $goodsSpecId;
        $goodsPriceUtil = new GoodsPriceLogic();
        $goods = ['new_price' => $kucun['goods_price']];
        $goodsPriceUtil->setGoodsTruePriceAndState(
            $store_id, $buyer_id, $goods, $goodsId, $kucun['goods_price'], $kucun['goods_pv'], $goodsSpecId, $goods_state
        );
        return ['price' => $goods['new_price']];
        // 获取代理商价格
        $model = D('Member');
        // hjun 2018-04-03 19:57:25 查询会员价、代理价
        $result = $model->getMemberAgentDiscount($buyer_id, $store_id);
        logWrite("-----start----");
        $discount = 10;
        if ($result['type'] == 2) {
            // 如果自定义价格
            $priceId = $result['store_group_price_id'];
            $where = [];
            $where['goods_id'] = $goodsId;
            $where['spec_id'] = $goodsSpecId;
            $where['store_group_price_id'] = $priceId;
            $price = M('mb_store_group_price_data')->where($where)->getField('price');
            if ($price > 0) {
                $kucun['goods_price'] = $price;
                logWrite("代理折扣自定义价格:{$kucun['goods_price']}");
                $goods_state = 4;
            } else {
                logWrite("代理折扣自定义价格:没设置价格");
            }

        } elseif ($result['type'] == 0) {
            // 销售价打折
            $kucun['goods_price'] = round($kucun['goods_price'] * $result['discount'], 2);
            logWrite("代理销售价打折价:{$kucun['goods_price']},折扣:{$result['discount']}");
            $goods_state = 4;
        } elseif ($result['type'] == 1) {
            // 利润打折
            $kucun['goods_price'] = round($kucun['goods_price'] - $kucun['goods_pv'] * (1 - $result['discount']), 2);
            logWrite("代理利润打折价:{$kucun['goods_price']},折扣:{$result['discount']}");
            $goods_state = 4;
        } elseif ($result['type'] == 3) {
            logWrite("代理商不打折");
            $goods_state = 4;
        } else {
            // 查询会员折扣 商品不是促销或者抢购才需要查会员价
            if (!($goods_state == 1 || $goods_state == 2)) {
                $discount = $model->getMemberVipDiscount($buyer_id, $store_id);
                $kucun['goods_price'] = round($kucun['goods_price'] * $discount, 2);
                $discount *= 10;
                logWrite("会员折扣价:{$kucun['goods_price']},折扣:{$discount}");
            }
        }
        logWrite("-----end----");
        return ['price' => $kucun['goods_price'], 'discount' => $discount];
    }


    public function confirmMethod($order_id, $channel_id)
    {

        if (empty($order_id)) {
            return;
        }
        $m = Model("mb_order");
        $condition = array();
        $condition['order_id'] = $order_id;
        //判断订单是否属于未接单状态
        $state_data = $m->where($condition)->find();
        if ($state_data['order_state'] != 0) {
            return;
        }

        $store_id = $state_data['storeid'];
        $store_data = Model('store')->where(array('store_id' => $state_data['storeid']))->find();

        if ($store_data['channel_type'] == 2) {
            $mainstore_data = Model('store')->where(array('channel_id' => $store_data['channel_id'], 'main_store' => 1))->find();
            $store_id = $mainstore_data['store_id'];
            if ($mainstore_data['auto_accept_order'] == 0) {
                return;
            }
        } else {
            if ($store_data['auto_accept_order'] == 0) {
                return;
            }
        }

        $version_max = $m->field('version')->order('version desc')->limit(1)->find();
        $max = $version_max['version'] + 1;
        $data = array(
            'order_state' => 1,
            'jiedan_time' => TIMESTAMP,
            'version' => $max
        );
        $b = $m->where($condition)->update($data);
        if ($b === false) {
            return;
        }


        $integral_data = Model('mb_store_info')->field('integral_pv_switch,integral_rate0,integral_rate1,integral_rate2,integral_rate3')->where(array('store_id' => $store_id))->find();


        if (empty($integral_data) || empty($integral_data['integral_pv_switch'])) {
            //add by chenqm 佣金插入记录
            $commission = new commission();
            $commission->budget($order_id, $channel_id);
        }
        //处理联盟订单状态
        $this->changeUnionOrderState($order_id, 1, "");

        $send_array = [];
        $send_array['se'] = $state_data['storeid'];
        $send_array['store_id'] = $state_data['storeid'];
        $send_array['type'] = 1;
        $send_array['member_id'] = $state_data['buyer_id'];
        $send_array['order_id'] = $order_id;
        $send_array['is_api'] = 1;
        $mobileController = new MobileBaseController();
        $mobileController->sendMessage($send_array);

        if ($state_data['pickup_id'] > 0) {
            $send_array = [];
            $send_array['se'] = $this->reqStoreId;
            $send_array['store_id'] = $this->reqStoreId;
            $send_array['type'] = 3;
            $send_array['member_id'] = $state_data['buyer_id'];
            $send_array['order_id'] = $order_id;
            $send_array['is_api'] = 1;
            $mobileController->sendMessage($send_array);
        }

    }

    public function changeUnionOrderState($order_id, $order_state, $cancel_reason)
    {

        $unionorder_version = Model('mb_unionorder')->max('version');
        $unionorder_data = array();
        $unionorder_data['version'] = $unionorder_version + 1;
        $unionorder_data['order_state'] = $order_state;
        if ($order_state == 5) {
            $unionorder_data['unionorder_state'] = 3;
            if ($cancel_reason == "退单") {
                $unionorder_data['cancel_reason'] = "退单";
            } else {
                $unionorder_data['cancel_reason'] = "代理商关闭";
            }
            $unionorder_data['cancel_time'] = TIMESTAMP;
        }
        Model('mb_unionorder')->where(array('order_id' => $order_id))->update($unionorder_data);
    }
}