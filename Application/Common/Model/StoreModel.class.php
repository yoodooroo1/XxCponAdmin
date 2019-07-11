<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2017/4/26
 * Time: 15:38
 */

namespace Common\Model;

use Think\Cache\Driver\Redis;

class StoreModel extends BaseModel
{
    protected $tableName = "store";

    /**
     * 获取商家的微信客服配置
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-18 09:38:36
     */
    public function getWxCustomerServiceConfig($storeId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $field = ['store_id,wx_service_name,wx_service_description,wx_service_qrcode'];
        $field = implode(',', $field);
        $info = $this->where($where)->field($field)->find();
        if (false === $info) {
            logWrite("查询商家{$storeId}客服配置信息出错:" . $this->getDbError());
            return getReturn();
        }
        // 检查商家是否有配置客服
        foreach ($info as $key => $value) {
            if ($key === 'wx_service_qrcode' && empty($value)) {
                return getReturn(-1, '商家暂未配置客服');
            }
        }
        $info['wx_service_description'] = nlRl2br($info['wx_service_description']);
        return getReturn(200, '', $info);
    }

    /**
     * 获取商家的VIP信息
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-20 10:40:37
     * Version: 1.0
     */
    public function getStoreVip($storeId = 0)
    {
        return D('StoreVip')->getStoreVip($storeId);
    }

    /**
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-11 10:03:35
     * Desc: 获取商家的顶部导航按钮
     * Update: 2017-10-11 10:03:36
     * Version: 1.0
     */
    public function getStoreItem($storeId = 0)
    {
        $list = [
            ['class' => 'tCoupon', 'name' => '积分商城', 'url' => U('Credit/creditstore', array('se' => $storeId))],
            ['class' => 'tSign', 'name' => '签到', 'url' => U('Credit/creditstore', array('se' => $storeId))],
            ['class' => 'tCollect', 'name' => '我的收藏', 'url' => U('Goods/collection', array('se' => $storeId))],
            ['class' => 'tPay', 'name' => '直接付款', 'url' => U('FacePay/index', array('se' => $storeId))],
            ['class' => 'tService', 'name' => '服务中心', 'url' => U('Store/serviceCenter', array('se' => $storeId))],
        ];
        return getReturn(200, '', $list);
    }

    /**
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-11 10:08:34
     * Desc: 获取商家底部导航按钮的状态
     * Update: 2017-10-11 10:08:35
     * Version: 1.0
     */
    public function getStoreModule($storeId = 0)
    {
        $info = $this
            ->field('store_id,index_module,classify_module,guide_module,customer_service_module,cart_module,setting_module')
            ->find($storeId);
        if (false === $info) {
            logWrite("查询商家{$storeId}底部导航状态出错:" . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '', $info);
    }

    /**
     * 获取比例字段
     * @param int $vipLevel
     * @param int $relationLevel
     * @param string $type
     * @return string
     * User: hjun
     * Date: 2018-12-11 15:00:33
     * Update: 2018-12-11 15:00:33
     * Version: 1.00
     */
    private function getRateField($vipLevel = 0, $relationLevel = 0, $type = 'commission')
    {
        $vipLevel = $vipLevel > 0 ? $vipLevel : 0;
        $relationLevel = $relationLevel > 0 ? $relationLevel : 0;
        $fieldTable = [
            'commission' => [
                '0' => "rate{$relationLevel}",
                '1' => "vip1_rate{$relationLevel}",
                '2' => "vip2_rate{$relationLevel}",
                '3' => "vip3_rate{$relationLevel}",
            ],
            'credit' => [
                '0' => "integral_rate{$relationLevel}",
                '1' => "vip1_integral_rate{$relationLevel}",
                '2' => "vip2_integral_rate{$relationLevel}",
                '3' => "vip3_integral_rate{$relationLevel}",
            ]
        ];
        return $fieldTable[$type][$vipLevel];
    }

    /**
     * 获取DOM的ID
     * @param int $relationLevel
     * @param string $type
     * @return string
     * User: hjun
     * Date: 2018-12-11 15:46:46
     * Update: 2018-12-11 15:46:46
     * Version: 1.00
     */
    private function getDOMId($relationLevel = 0, $type = 'commission')
    {
        $relationLevel = $relationLevel > 0 ? $relationLevel : 0;
        $langTable = [
            'commission' => [
                '0' => "tag_back_commission",
                '1' => "tag_one_recommend",
                '2' => "tag_two_recommend",
                '3' => "tag_three_recommend",
            ],
            'credit' => [
                '0' => "tag_back_commission",
                '1' => "tag_one_recommend",
                '2' => "tag_two_recommend",
                '3' => "tag_three_recommend",
            ]
        ];
        return $langTable[$type][$relationLevel];
    }

    /**
     * 获取标题的语言包变量
     * @param int $relationLevel
     * @param string $type
     * @return string
     * User: hjun
     * Date: 2018-12-11 15:46:46
     * Update: 2018-12-11 15:46:46
     * Version: 1.00
     */
    private function getTitleLang($relationLevel = 0, $type = 'commission')
    {
        $relationLevel = $relationLevel > 0 ? $relationLevel : 0;
        $langTable = [
            'commission' => [
                '0' => "BACK_COMMISSION",
                '1' => "RECOMMEND",
                '2' => "RECOMMEND",
                '3' => "RECOMMEND",
            ],
            'credit' => [
                '0' => "BACK_COMMISSION",
                '1' => "RECOMMEND",
                '2' => "RECOMMEND",
                '3' => "RECOMMEND",
            ]
        ];
        return $langTable[$type][$relationLevel];
    }

    /**
     * 获取描述的语言包变量
     * @param int $relationLevel
     * @param string $type
     * @return string
     * User: hjun
     * Date: 2018-12-11 15:47:25
     * Update: 2018-12-11 15:47:25
     * Version: 1.00
     */
    private function getDescLang($relationLevel = 0, $type = 'commission')
    {
        $relationLevel = $relationLevel > 0 ? $relationLevel : 0;
        $langTable = [
            'commission' => [
                '0' => "SELF_COMMISSION",
                '1' => "ONE_COMMISSION",
                '2' => "TWO_COMMISSION",
                '3' => "THREE_COMMISSION",
            ],
            'credit' => [
                '0' => "SELF_CREDIT",
                '1' => "ONE_CREDIT",
                '2' => "TWO_CREDIT",
                '3' => "THREE_CREDIT",
            ]
        ];
        return $langTable[$type][$relationLevel];
    }

    /**
     * 判断是否有推荐关系
     * @param array $relation
     * @param int $relationLevel
     * @return boolean
     * User: hjun
     * Date: 2018-12-11 15:26:59
     * Update: 2018-12-11 15:26:59
     * Version: 1.00
     */
    private function hasRelation($relation = [], $relationLevel = 0)
    {
        if ($relationLevel == 0) {
            return true;
        }
        $relationTable = [
            '1' => 'one_member_id',
            '2' => 'two_member_id',
            '3' => 'three_member_id',
        ];
        $relationField = $relationTable[$relationLevel];
        return $relation[$relationField] > 0;
    }

    /**
     * 判断是否需要计算佣金、积分
     * @param array $config
     * @param array $storeInfo
     * @param array $goodsBean
     * @param array $storeMember
     * @param array $relation
     * @param int $relationLevel
     * @param string $type
     * @return boolean
     * User: hjun
     * Date: 2018-12-11 15:32:32
     * Update: 2018-12-11 15:32:32
     * Version: 1.00
     */
    private function isNeedCalculateRate($config = [], $storeInfo = [], $goodsBean = [], $storeMember = [], $relation = [], $relationLevel = 0, $type = 'commission')
    {
        // 判断商家是否设置了显示pv == 1表示隐藏PV 则不需要计算
        if ($storeInfo['store_pv_hide'] == 1) {
            return false;
        }
        // 判断分销开关 和 商品PV
        $ctrlField = [
            'commission' => 'rateswitch',
            'credit' => 'integral_pv_switch',
        ];
        if ($config[$ctrlField[$type]] != 1 || $goodsBean['goods_pv'] <= 0) {
            return false;
        }

        // 判断佣金比例
        $commissionField = $this->getRateField($storeMember['level'], $relationLevel, $type);
        if ($config[$commissionField] <= 0) {
            return false;
        }

        return true;
    }

    /**
     * 设置佣金、积分的标签数据
     * @param array $marketData
     * @param array $config
     * @param array $storeInfo
     * @param array $goodsBean
     * @param array $storeMember
     * @param array $relation
     * @param int $relationLevel
     * @param string $type
     * @return array
     * User: hjun
     * Date: 2018-12-11 15:36:16
     * Update: 2018-12-11 15:36:16
     * Version: 1.00
     */
    private function setMarketData(&$marketData = [], $config = [], $storeInfo = [], $goodsBean = [], $storeMember = [], $relation = [], $relationLevel = 0, $type = 'commission')
    {
        $bool = $this->isNeedCalculateRate($config, $storeInfo, $goodsBean, $storeMember, $relation, $relationLevel, $type);
        if (!$bool) {
            return $marketData;
        }
        $rateField = $this->getRateField($storeMember['level'], $relationLevel, $type);
        $commission = round(($goodsBean['goods_pv'] * $config[$rateField]) / 100, 2);
        if (empty($commission)) {
            return $marketData;
        }
        $domId = $this->getDOMId($relationLevel, $type);
        $langData = [
            'value' => "<font class=\"textRed {$domId}\">{$commission}</font>",
            'currencyUnit' => $storeInfo['currency_unit']
        ];
        $titleLang = $this->getTitleLang($relationLevel, $type);
        $descLang = $this->getDescLang($relationLevel, $type);
        $marketData[] = [
            'title' => L($titleLang), // 返佣、推荐
            'desc' => L($descLang, $langData), // 自己消费购买每件可获得 直接推荐
        ];
        return $marketData;
    }

    /**
     * @param int $storeId 商家ID
     * @param int $memberId 商家ID
     * @param array $goodsBean 商品数据
     * @param array $payMode 支付方式数据
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-11 13:14:03
     * Desc: 获取商家商品详情页的标签开关
     * Update: 2017-10-11 13:14:07
     * Version: 1.0
     */
    public function getStoreGoodsTag($storeId = 0, $memberId = 0, $goodsBean = [], $payMode = [])
    {
        $goodsInfo = $goodsBean;
        $storeId = (int)$storeId;
        $goodsId = $goodsInfo['goods_id'];
        if ($storeId <= 0 || $goodsId < 0) return [];
        // 查询店铺信息
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        $tag = [
            [
                'name' => L('SJFH')/*'商家发货'*/,
                'desc' => L('YSJFH', ['name' => $storeInfo['store_name']]),
            ]
        ];
        // 判断是否有自提
        if (pickupIsOpen($storeId)) {
            $isAlone = $storeInfo['pickup_sale_type'] == 1;
            if ($isAlone) {
                $pickPoint = D('DepotGoods')->validateGoodsCanPickup($goodsId);
            }
            if (!$isAlone || $pickPoint) {
                $pick = [
                    'name' => L('ZCZT')/*'支持自提'*/,
                    'desc' => L('SMZTFW'),/*'我们提供上门自提服务，订单结算时可以选择上门自提点及预约时间。'*/
                ];
                array_push($tag, $pick);
            }
        }
        // 判断是否有货到付款
        $cashPay = $payMode['cashpay'];
        if ((int)$cashPay === 1) {
            $cash = [
                'name' => L('PAY_COD')/*'货到付款'*/,
                'desc' => L('HDFK')/*'支持送货上门后再收款，支持现金方式。'*/
            ];
            array_push($tag, $cash);
        }
        // 判断是否允许使用优惠券
        $goodsState = $goodsInfo['state'];
        $couponAllow = $goodsInfo['allow_coupon'];
        if ((int)$couponAllow === 1 && in_array((int)$goodsState, [1, 2]) !== false) {
            $coupon = [
                'name' => L('KSYHQ')/*'可使用优惠券'*/,
                'desc' => L('ZCSYYHQ'),/*'商品支持使用优惠券，可以通过参与商家活动获得优惠券奖励。'*/
            ];
            array_push($tag, $coupon);
        }
        // 判断是否赠送积分
        $where = [];
        $where['channelid'] = $storeInfo['channel_id'];
        $where['store_grade'] = $storeInfo['store_grade'];
        $gradeInfo = M('mb_storegrade')->field(true)->where($where)->find();
        if (empty($gradeInfo)) {
            $where['channelid'] = 0;
            $gradeInfo = M('mb_storegrade')->field(true)->where($where)->find();
        }
        $creditHide = $gradeInfo['credit_hide'];
        if ((int)$creditHide === 0 && $storeInfo['close_shop_send_credit'] == 0) {
            $credit = [
                'name' => L('ZSJF')/*'积分赠送'*/,
                'desc' => L('ZSYDJF'),/*'商品交易完成后会赠送一定的积分，可在积分商城中兑换礼品。'*/
            ];
            array_push($tag, $credit);
        }
        // 判断XX元免运费 判断超出距离后运费
        $distanceInfo = $storeInfo['distance_info'];
        $distanceInfo = jsonDecodeToArr($distanceInfo);
        foreach ($distanceInfo as $key => $value) {
            if ($value['ismore'] == 1) {
                $money = $value['money'];
            }
        }
        $canPostage = (int)$storeInfo['postage_tag'];
        $postage = (double)$storeInfo['postage'];
        if (!empty($money)) {
            $name = $canPostage === 1 ?
                L('YMYF', ['price' => "{$money}{$storeInfo['currency_unit']}"])/*"{$money}元免运费"*/ :
                L('YQS', ['price' => "{$money}{$storeInfo['currency_unit']}"])/*"{$money}元起送"*/
            ;
            $desc = $canPostage === 1 ?
                L('BZJESQYF', ['price' => "{$postage}{$storeInfo['currency_unit']}"])/*"不足金额订单收取{$postage}元/单基础运费，超出重量加收续重运费"*/ :
                L('BZJEBKXD')/*"不足金额不可下单"*/
            ;
            $freight = [
                'name' => $name,
                'desc' => L('SXDZYMYF', ['money' => $money, 'desc' => $desc])/*"所选地址商家订单满{$money}元免基础运费（10kg内），{$desc}，详情咨询客服。"*/
            ];
            array_push($tag, $freight);
        }

        // 查找标签
        $where = [];
        $where['a.goods_id'] = $goodsId;
        $where['b.isdelete'] = 0;
        $where['b.tag_status'] = 1;
        $list = M('goods_tag_link')
            ->alias('a')
            ->field('b.tag_name,b.tag_desc')
            ->where($where)
            ->join('__GOODS_TAG__ b ON b.tag_id = a.tag_id')
            ->select();
        foreach ($list as $key => $value) {
            array_push($tag, ['name' => $value['tag_name'], 'desc' => "{$value['tag_desc']}"]);
        }

        // 第三方余额可低
        $where = [];
        $where['a.goods_id'] = $goodsId;
        // 是子店查主店ID
        $mainStoreId = $storeInfo['main_store_id'];
        $info = M('goods')
            ->alias('a')
            ->field('a.thirdpart_money_limit,b.moneyname,b.status')
            ->where($where)
            ->join("LEFT JOIN __MB_THIRDPART_MONEY__ b ON b.store_id = {$mainStoreId}")
            ->find();
        if ($info['thirdpart_money_limit'] != 0 && $info['status'] == 1) {
            $name = empty($info['moneyname']) ?
                L('KSYDSFJE', ['name' => L('DSFJE')])/*'第三方余额'*/ :
                L('KSYDSFJE', ['name' => $info['moneyname']])/*$info['moneyname']*/
            ;
            array_push($tag, [
                'name' => $name,
                'desc' => L('SPZCYEZF')/*"商品支持余额支付"*/
            ]);
        }
        $data = [];
        // 商品标签以及服务标签
        $data['service_tags'] = $tag;
        // 不在微信商城 则不要再进行查询逻辑了
        if (!isInWap()) {
            return $data;
        }
        // region 佣金、积分、第三方余额的标签数据
        $marketData = [];
        $configData['is_show_pv'] = 0;
        if ($storeInfo['store_pv_hide'] == 0 && $memberId > 0) {
            $types = ['commission', 'credit'];
            $memberVip = D('StoreMember')->getMemberVip($memberId, $storeId)['data'];
            $storeInfoConfig = D('StoreInfo')->getDistributionConfig($storeId)['data'];
            // 【返佣】 （佣金、积分）
            foreach ($types as $type) {
                $this->setMarketData($marketData, $storeInfoConfig, $storeInfo, $goodsBean, $memberVip, [], 0, $type);
            }
            // 【推荐】 （佣金、积分）
            foreach ($types as $type) {
                for ($i = 1; $i <= 3; $i++) {
                    $this->setMarketData($marketData, $storeInfoConfig, $storeInfo, $goodsBean, $memberVip, [], $i, $type);
                }
            }
            $configData = array_merge($memberVip, $storeInfoConfig, []);
            $configData['is_show_pv'] = 1;
        }

        // 【积分】 消耗积分
        if (false && $storeInfo['credits_model'] == 1 && $goodsBean['credits_limit'] > 0) {
            $marketData[] = [
                'title' => L('CREDIT')/*积分*/,
                'desc' => L('GMXHJF', [
                    'value' => "<font class=\"textRed\">{$goodsBean['credits_limit']}</font>",
                    'money' => "<font class=\"textRed tag_credit_money\">{$goodsBean['new_price']}</font>",
                ]), // 购买产品需要消耗xx积分
            ];
        }
        // 【第三方余额】
        if ($info['thirdpart_money_limit'] != 0 && $info['status'] == 1) {
            $info['moneyname'] = empty($info['moneyname']) ? L('DSFJE')/*'第三方余额'*/ : $info['moneyname'];
            if ($info['thirdpart_money_limit'] == -1) {
                $value = '';
            } else {
                $value = "<font class=\"textRed\">{$info['thirdpart_money_limit']}</font>";
            }
            $marketData[] = [
                'title' => "{$info['moneyname']}", // 第三方余额
                'desc' => L('GMSYTM', ['value' => $value, 'name' => $info['moneyname']]), // 购买产品可以使用
            ];
        }
        $data['market_data'] = $marketData;
        $data['config_data'] = $configData;
        // endregion


        return $data;
    }

    public function getMainStoreId($channelId = 0)
    {
        $w = array();
        $w['channel_id'] = $channelId;
        $w['main_store'] = 1;
        return $this->where($w)->getField('store_id');
    }

    /**
     * 获取商家信息
     * 返回常用信息
     * store_id,store_name,store_label,member_id store_member_id,member_name store_member_name
     * channel_id,channel_type,store_grade,main_store,store_type,index_module,classify_module,guide_module,customer_service_module
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-09 21:58:02
     */
    public function getStoreInfo($storeId = 0)
    {
        // 如果内存中没有 就从数据库取 并存入内存
        $info = S("{$storeId}_storeInfo");
        if (empty($info)) {
            $where = [];
            $where['store_id'] = $storeId;
            /*$field = [
                "store_id,store_name,store_label,member_id store_member_id,member_name store_member_name",
                "channel_id,channel_type,store_grade,main_store,index_module,classify_module,guide_module,customer_service_module",
                'close_shop_send_credit,shop_exchange_credit,close_sign,sign_one_day,sign_two_day,sign_three_day',
                'sign_four_day,sign_five_day,sign_shop,store_domain',
                'store_address', 'date_from', 'extra_live_room_num', 'extra_advertisenum'
            ];
            $field = implode(',', $field);*/
            $field = true;
            $info = $this->field($field)->where($where)->find();
            if (false === $info) {
                logWrite("查询店铺信息出错:" . $this->getDbError());
                return getReturn(CODE_ERROR);
            }
            if (empty($info)) return getReturn(-1, "商家不存在");
            // 字段替换
            $fields = [
                'store_member_id' => 'member_id',
                'store_member_name' => 'member_name',
            ];
            foreach ($fields as $key => $value) {
                $info[$key] = $info[$value];
            }
            // 设置店铺类型 可以看AdminController的类型
            switch ((int)$info['channel_type']) {
                case 0:
                    $info['store_type'] = 5;
                    break;
                case 2:
                    $info['store_type'] = $info['main_store'] == 1 ? 0 : 1;
                    break;
                case 3:
                    $info['store_type'] = 4;
                    break;
                case 4:
                    $info['store_type'] = $info['main_store'] == 1 ? 2 : 3;
                    break;
                default:
                    $info['store_type'] = 5;
                    break;
            }
            // hj 2017-11-08 09:56:24 如果是子店 签到的配置查询主店的 域名也查主店的
            if (strpos('13', $info['store_type'] . '') !== false) {
                $where = [];
                $where['channel_id'] = $info['channel_id'];
                $where['main_store'] = 1;
                $options = [];
                $options['where'] = $where;
                $field = [
                    'store_id main_store_id', 'close_sign', 'sign_one_day', 'sign_two_day', 'sign_three_day',
                    'sign_four_day', 'sign_five_day', 'sign_shop', 'store_domain', 'date_from', 'pickup_sale_type',
                    'access_token_api', 'ticket_api',
                ];
                $options['field'] = implode(',', $field);
                $result = $this->queryRow($options);
                if ($result['code'] !== 200) return getReturn(CODE_ERROR);
                $mainInfo = $result['data'];
                foreach ($field as $key => $value) {
                    $info[$value] = $mainInfo[$value];
                }
                $info['main_store_id'] = $mainInfo['main_store_id'];
            } else {
                $info['main_store_id'] = $info['store_id'];
            }
            $info['close_sign'] = empty($info['close_sign']) ? 0 : $info['close_sign'];

            // 域名判断
            if (!empty($info['store_domain'])) {
                $isSetDomain = true;
                if (strpos($info['store_domain'], 'http') !== false) {
                    $domain = explode('//', $info['store_domain']);
                    $info['store_domain'] = $domain[1];
                }
            }

            // 查询mb_wxconfig有没有配置
            $where = [];
            $where['store_id'] = $info['main_store_id'];
            $where['isdelete'] = 0;
            $wx = M('mb_wxconfig')->field('appid,appsecret')->where($where)->find();
            if (!empty($wx['appid']) && !empty($wx['appsecret'])) {
                if (!$isSetDomain) {
                    $prefix = $this->where(['store_id' => $info['main_store_id']])->getField('member_name');
                    $info['store_domain'] = "{$prefix}.duinin.com";
                }
                $info['has_wx_config'] = 1;
            }

            // 货币
            $model = M('mb_store_config');
            $config = $model->find($storeId);
            $model = M('mb_currency');
            $where = [];
            $where['currency_id'] = $config['currency_id'];
            $where['is_delete'] = 0;
            $currency = $model->where($where)->find();
            if (empty($currency)) {
                $currency = $model->find(0);
            }
            $info['currency_unit'] = $currency['currency_unit']; // 单位
            $info['currency_symbol'] = $currency['currency_symbol']; // 符号

            S("{$storeId}_storeInfo", $info);
        }
        return getReturn(200, '', $info);
    }

    /**
     * 获取商家信息
     * 返回常用信息
     * store_id,store_name,store_label,member_id store_member_id,member_name store_member_name
     * channel_id,channel_type,store_grade,main_store,store_type,index_module,classify_module,guide_module,customer_service_module
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-09 21:58:02
     */
    public function getStoreInfo2($storeId = 0)
    {
        return $this->getStoreInfo($storeId);
        // 如果内存中没有 就从数据库取 并存入内存
        $where = [];
        $where['store_id'] = $storeId;
        $field = [
            "store_id,store_name,store_label,member_id store_member_id,member_name store_member_name",
            "channel_id,channel_type,store_grade,main_store,index_module,classify_module,guide_module,customer_service_module",
            'close_shop_send_credit,shop_exchange_credit,close_sign,sign_one_day,sign_two_day,sign_three_day',
            'sign_four_day,sign_five_day,sign_shop,store_domain', 'store_address', 'date_from',
            'balancepay,credit_limit_money,credit_percent,credit_to_money,credit_pay',
            'province_id,city_id,country_id,freight_mode,vip_endtime,delivery_type,shop_close,shop_close_content',
        ];
        $field = implode(',', $field);
        $info = $this->field($field)->where($where)->find();
        if (false === $info) {
            logWrite("查询店铺信息出错:" . $this->getDbError());
            return getReturn();
        }
        if (empty($info)) return getReturn(-1, "商家不存在");
        // 设置店铺类型 可以看AdminController的类型
        switch ((int)$info['channel_type']) {
            case 0:
                $info['store_type'] = 5;
                break;
            case 2:
                $info['store_type'] = $info['main_store'] == 1 ? 0 : 1;
                break;
            case 3:
                $info['store_type'] = 4;
                break;
            case 4:
                $info['store_type'] = $info['main_store'] == 1 ? 2 : 3;
                break;
            default:
                $info['store_type'] = 5;
                break;
        }
        // hj 2017-11-08 09:56:24 如果是子店 签到的配置查询主店的 域名也查主店的
        if (strpos('13', $info['store_type'] . '') !== false) {
            $where = [];
            $where['channel_id'] = $info['channel_id'];
            $where['main_store'] = 1;
            $options = [];
            $options['where'] = $where;
            $field = ['store_id main_store_id',
                'close_sign', 'sign_one_day',
                'sign_two_day', 'sign_three_day',
                'sign_four_day', 'sign_five_day',
                'sign_shop', 'store_domain', 'date_from'
            ];
            $options['field'] = implode(',', $field);
            $result = $this->queryRow($options);
            if ($result['code'] !== 200) return getReturn();
            $mainInfo = $result['data'];
            foreach ($field as $key => $value) {
                $info[$value] = $mainInfo[$value];
            }
            $info['main_store_id'] = $mainInfo['main_store_id'];
        } else {
            $info['main_store_id'] = $info['store_id'];
        }
        $info['close_sign'] = empty($info['close_sign']) ? 0 : $info['close_sign'];

        // 域名判断
        if (!empty($info['store_domain'])) {
            if (strpos($info['store_domain'], 'http') !== false) {
                $domain = explode('//', $info['store_domain']);
                $info['store_domain'] = $domain[1];
            }
        }

        return getReturn(200, '', $info);
    }

    /**
     * 获取商家的套餐信息
     * 1.一般是获取一下几种  未来可以再加
     *  代理商 partnerManagePms 1-有 0-没有
     *  是否显示PV pvShowPms 1-显示 0-不显示
     *  会员关系 memberShipPms
     *  三级分销 levelThreePms
     *  APP下载 app_download_tips
     *  会员提现审核 withdrawal
     *  设置推广二维码 promote_qrcode
     *  发现 find_manage
     *  微信菜单配置 wx_menu
     *  价格隐藏 price_hide
     *  商品成本开关 costPms 0-不显示 1-非必填 2-必填
     *  自定义支付配置 selfPayPms
     *  子店支付配置权限 sub_pay_switch
     *  是否开启系统代收 sys_collection
     *  微信支付配置 wx_pay_ctrl
     *  H5支付配置  web_pay_ctrl
     *  PC商城支付配置 pc_pay_ctrl
     *  APP支付配置 app_pay_ctrl
     * @param int $storeId
     * @return array
     * User: hj
     * Date: 2017-09-09 22:15:19
     */
    public function getStoreGrantInfo($storeId = 0)
    {
        return D('StoreGrade')->getStoreGrantInfo($storeId);
    }


    /**
     * 根据 storeId 获取总后台分配给商家的权限信息
     * @param int $storeId
     * @return array
     * User: hj
     * Date: 2017-09-10 20:57:35
     */
    public function getStoreRole($storeId = 0)
    {
        return D('AuthRole')->getStoreRole($storeId);
    }

    /**
     * 根据 storeId 获取该 storeId 一般的查询字符串 因为这个经常用到
     * 如果是主店，就是返回子店+自己的ID字符串
     * @param int $storeId
     * @param int $type 类型
     *  1- 商城查出的字符串包括自己
     *  2- 查出的字符换不包括自己
     * @return array
     * User: hj
     * Date: 2017-09-11 21:01:22
     */
    public function getStoreQueryId($storeId = 0, $type = 1)
    {
        $result = $this->getStoreInfo($storeId);
        if ($result['code'] !== 200) return $result;
        $store = $result['data'];
        if ($store['main_store'] == 1) {
            $where = [];
            $where['channel_id'] = $store['channel_id'];
            switch ((int)$type) {
                case 2:
                    $where['main_store'] = 0;
                    break;
                default:
                    break;
            }
            $id = $this->where($where)->getField('store_id', true);
            if (false === $id) {
                logWrite("查询{$storeId}的查询字符串出错:" . $this->_sql());
                return getReturn();
            }
            $id = implode(',', $id);
        } else {
            $id = $storeId . '';
        }
        return getReturn(200, '', $id);
    }

    /**
     * @param int $storeId 商家ID
     * @param string $fieldName 模块在数据库中的字段名
     * @param int $status 1-开启 0-关闭
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-10 11:28:04
     * Desc: 修改商店底部模块的开启和关闭
     * Update: 2017-10-10 11:28:06
     * Version: 1.0
     */
    public function changeModuleStatus($storeId = 0, $fieldName = '', $status = 1)
    {
        $storeId = (int)$storeId;
        $status = (int)$status;
        if ($storeId <= 0 || (in_array($status, [1, 0]) === false)) return getReturn(-1, '参数错误');
        $info = $this
            ->field('shop_module,classify_module,guide_module,customer_service_module,setting_module,cart_module')
            ->find($storeId);
        $msg = $status === 1 ? '开启' : '关闭';
        if ((int)$info[$fieldName] === $status) return getReturn(-1, "该模块已经{$msg}");
        $where = [];
        $where['store_id'] = $storeId;
        $data = [];
        $data[$fieldName] = $status;
        $result = $this->where($where)->save($data);
        if (false === $result) {
            logWrite("修改商家{$storeId}的{$fieldName}模块出错:" . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '', $status);
    }

    /**
     * @param int $storeId
     * @param int $status
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-10 11:59:45
     * Desc: 修改首页模块的状态
     * Update: 2017-10-10 11:59:50
     * Version: 1.0
     */
    public function changeIndexModuleStatus($storeId = 0, $status = 1)
    {
        return $this->changeModuleStatus($storeId, 'shop_module', $status);
    }

    /**
     * @param int $storeId
     * @param int $status
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-10 12:00:04
     * Desc: 修改分类模块的状态
     * Update: 2017-10-10 12:00:06
     * Version: 1.0
     */
    public function changeClassifyModuleStatus($storeId = 0, $status = 1)
    {
        return $this->changeModuleStatus($storeId, 'classify_module', $status);
    }

    /**
     * @param int $storeId
     * @param int $status
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-10 12:00:16
     * Desc: 修改发现模块的状态
     * Update: 2017-10-10 12:00:18
     * Version: 1.0
     */
    public function changeGuideModuleStatus($storeId = 0, $status = 1)
    {
        return $this->changeModuleStatus($storeId, 'guide_module', $status);
    }

    /**
     * @param int $storeId
     * @param int $status
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-10 12:00:29
     * Desc: 修改客服模块的状态
     * Update: 2017-10-10 12:00:30
     * Version: 1.0
     */
    public function changeCustomerServiceModuleStatus($storeId = 0, $status = 1)
    {
        return $this->changeModuleStatus($storeId, 'customer_service_module', $status);
    }

    /**
     * 修改个人中心模块的状态
     * @param int $storeId
     * @param int $status
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-31 15:47:33
     * Update: 2018-03-31 15:47:33
     * Version: 1.00
     */
    public function changeUserModule($storeId = 0, $status = 1)
    {
        return $this->changeModuleStatus($storeId, 'setting_module', $status);
    }

    /**
     * 修改购物车模块的状态
     * @param int $storeId
     * @param int $status
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-31 15:47:46
     * Update: 2018-03-31 15:47:46
     * Version: 1.00
     */
    public function changeCartModule($storeId = 0, $status = 1)
    {
        return $this->changeModuleStatus($storeId, 'cart_module', $status);
    }

    /**
     * @param int $channelId
     * @param array $condition
     * @param string $field
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-25 15:13:54
     * Desc: 获取子店列表
     * Update: 2017-10-25 15:13:55
     * Version: 1.0
     */
    public function getChildStoreList($channelId = 0, $condition = [], $field = '')
    {
        $where = [];
        $where['channel_id'] = $channelId;
        $where['main_store'] = 0;
        $where['isdelete'] = 0;
        $where = array_merge($where, $condition);
        $field = empty($field) ?
            'store_id,store_name' :
            $field;
        $list = $this->field($field)->where($where)->select();
        if (false === $list) {
            return getReturn();
        }
        return getReturn(200, '', $list);
    }

    /**
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取店铺列表
     * Date: 2017-11-03 15:08:02
     * Update: 2017-11-03 15:08:03
     * Version: 1.0
     */
    public function getStoreList($condition = [])
    {
        $where = [];
        $where['isdelete'] = 0;
        $where = array_merge($where, $condition);
        $options = [];
        $options['where'] = $where;
        $options['field'] = 'store_id,store_name';
        $options['cache']['key'] = true;
        return $this->queryList($options);
    }

    public function afterOpenStore($storeId = 0, $data = [])
    {

    }

    /**
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取商家的签到、积分配置
     * Date: 2017-11-07 17:16:25
     * Update: 2017-11-07 17:16:26
     * Version: 1.0
     */
    public function getStoreSendCreditAndSignSetting($storeId = 0)
    {
        return $this->getStoreInfo($storeId);
    }

    /**
     * @param int $storeId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 设置商家的签到 积分 等配置
     * Date: 2017-11-07 17:14:17
     * Update: 2017-11-07 17:14:18
     * Version: 1.0
     */
    public function setStoreSendCreditAndSignSetting($storeId = 0, $data = [])
    {
        $options = [];
        $field = [
            'close_shop_send_credit', 'shop_exchange_credit', 'shop_exchange_credit_1',
            'shop_exchange_credit_2', 'shop_exchange_credit_3', 'close_sign', 'sign_one_day',
            'sign_two_day', 'sign_three_day', 'sign_four_day', 'sign_five_day', 'sign_shop',
            'version', 'recommend_point'
        ];
        $where = [];
        $where['store_id'] = $storeId;
        $options['where'] = $where;
        $options['field'] = implode(',', $field);
        // 如果开启了赠送积分 判断积分
        if ($data['close_shop_send_credit'] == 0) {
            $data['shop_exchange_credit'] = round($data['shop_exchange_credit']);
            if ($data['shop_exchange_credit'] < 0) return getReturn(-1, '赠送积分不能小于0');
            $data['shop_exchange_credit_1'] = round($data['shop_exchange_credit_1']);
            if ($data['shop_exchange_credit_1'] < 0) return getReturn(-1, '赠送积分不能小于0');
            $data['shop_exchange_credit_2'] = round($data['shop_exchange_credit_2']);
            if ($data['shop_exchange_credit_2'] < 0) return getReturn(-1, '赠送积分不能小于0');
            $data['shop_exchange_credit_3'] = round($data['shop_exchange_credit_3']);
            if ($data['shop_exchange_credit_3'] < 0) return getReturn(-1, '赠送积分不能小于0');
        }
        $data['recommend_point'] = round($data['recommend_point']);
        if ($data['recommend_point'] < 0) return getReturn(-1, '赠送积分不能小于0');

        // 如果开启了签到 判断积分
        if ($data['close_sign'] == 0) {
            $sign = ['sign_one_day', 'sign_two_day', 'sign_three_day', 'sign_four_day', 'sign_five_day', 'sign_shop'];
            foreach ($sign as $key => $value) {
                $data[$value] = round($data[$value]);
                if ($data[$value] <= 0) return getReturn(-1, '赠送积分不能小于1');
            }
        }
        // 版本号+1
        $data['version'] = $this->max('version') + 1;
        $result = $this->saveData($options, $data);
        if ($result['code'] === 200) {
            // 更新缓存
            $redis = new Redis();
            $redis->clear();
        }
        return $result;
    }

    /**
     * 获取渠道号
     * @param int $storeId
     * @return string
     * User: hjun
     * Date: 2019-03-26 20:03:59
     * Update: 2019-03-26 20:03:59
     * Version: 1.00
     */
    public function getChannelId($storeId = 0)
    {
        $storeInfo = $this->getStoreInfo($storeId)['data'];
        return $storeInfo['channel_id'];
    }
}