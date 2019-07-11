<?php

namespace Common\Util\DecorationComponents;
use Common\Logic\CartLogic;

/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/9/25
 * Time: 15:20
 */
class Goods extends BaseComponents
{
    // region 商品来源
    const SOURCE_CLASS = 0; // 来源分类
    const SOURCE_TAG = 1; // 来源标签
    // endregion

    // region 列表样式
    const LIST_STYLE_TWO_IN_LINE = 0; // 一行两个
    const LIST_STYLE_THREE_IN_LINE = 1; // 一行三个
    const LIST_STYLE_TRANSVERSE = 2; // 横向滑动
    // endregion

    // region 显示样式
    const SHOW_STYLE_NO_WHITE_RIGHT_ANGLE = 0;
    const SHOW_STYLE_WHITE_ROUND_CORNER = 1;
    // endregion

    // region 商品名称
    const GOODS_NAME_NO_SHOW = 0;
    const GOODS_NAME_SHOW_NAME = 1;
    // endregion

    // region 商品价格
    const GOODS_PRICE_NO_SHOW = 0; // 不显示
    const GOODS_PRICE_SHOW_NOW = 2; // 只显示现价
    const GOODS_PRICE_SHOW_NOW_AND_ORIGIN = 1; // 显示现价和原价
    // endregion

    // region 购买按钮
    const BUY_NOW_SHOW = 0; // 不显示
    const BUY_STYLE_CART = 1; // 购物车样式
    const BUY_STYLE_BUY = 2; // 购买文字
    const BUY_STYLE_LIMIT = 3; // 马上抢文字
    // endregion

    // region 商品角标
    const CORNER_NOW_SHOW = 0;
    const CORNER_STYLE_NEW = 1;
    const CORNER_STYLE_HOT = 2;
    const CORNER_STYLE_NEW_EN = 3;
    const CORNER_STYLE_HOW_EN = 4;
    // endregion

    private $goodsSource; // 商品来源
    private $sourceId; // 来源ID
    private $goodsNum; // 商品显示数量
    private $listStyle; // 列表样式
    private $showStyle; // 显示样式
    private $showGoodsName; // 商品名称
    private $showGoodsPrice; // 价格
    private $buyBtnStyle; // 购买按钮
    private $cornerMarkType; // 角标
    private $goodsList; // 商品列表

    protected $tplRelativePath = 'diy/module/goods';

    /**
     * @return mixed
     */
    public function getGoodsSource()
    {
        return $this->goodsSource;
    }

    /**
     * @param mixed $goodsSource
     */
    public function setGoodsSource($goodsSource)
    {
        $goodsSource = (int)$goodsSource;
        if (!in_array($goodsSource, [self::SOURCE_CLASS, self::SOURCE_TAG])) {
            $this->goodsSource = self::SOURCE_CLASS;
        } else {
            $this->goodsSource = $goodsSource;
        }
    }

    /**
     * @return mixed
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param mixed $sourceId
     * @return $this
     */
    public function setSourceId($sourceId)
    {
        // 获取商品列表 根据选择的来源 获取商品ID数组 求交集
        $content = $this->module['content'];
        $goodsIds = getGoodsIdsByDiySource($content, $this->getStoreInfo());
        if (!empty($goodsIds)) {
            $where = [];
            $where['a.store_id'] = $this->getStoreId();
            $where['a.goods_id'] = ['in', $goodsIds];
            $where['a.goods_state'] = 1;
            $where['a.isdelete'] = 0;
            $list = M('goods')
                ->alias('a')
                ->field('a.goods_id,a.goods_name,a.store_id,a.goods_price,a.goods_image,a.goods_figure,a.is_promote,a.goods_spec,a.spec_open,a.is_qianggou,a.qianggou_start_time,a.qianggou_end_time')
                ->where($where)
                ->order('a.sort DESC,a.top DESC')
                ->limit($this->getGoodsNum())
                ->select();
            $goodsList = D('Goods')->initGoodsBeans($this->getStoreId(), $list, 0, $this->getMemberId(), CartLogic::INIT_TYPE_ONLY_PRICE);
            $this->setGoodsList($goodsList);
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGoodsNum()
    {
        return $this->goodsNum;
    }

    /**
     * @param mixed $goodsNum
     */
    public function setGoodsNum($goodsNum)
    {
        if (empty($goodsNum) && $goodsNum !== '0' && $goodsNum !== 0) {
            $this->goodsNum = 6;
        } elseif ($goodsNum > 50) {
            $this->goodsNum = 50;
        } else {
            $this->goodsNum = $goodsNum;
        }
    }

    /**
     * @return mixed
     */
    public function getListStyle()
    {
        return $this->listStyle;
    }

    /**
     * @param mixed $listStyle
     */
    public function setListStyle($listStyle)
    {
        $listStyle = (int)$listStyle;
        if (!in_array($listStyle, [self::LIST_STYLE_THREE_IN_LINE, self::LIST_STYLE_TRANSVERSE, self::LIST_STYLE_TWO_IN_LINE])) {
            $this->listStyle = self::LIST_STYLE_TWO_IN_LINE;
        } else {
            $this->listStyle = $listStyle;
        }
    }

    /**
     * @return mixed
     */
    public function getShowStyle()
    {
        return $this->showStyle;
    }

    /**
     * @param mixed $showStyle
     */
    public function setShowStyle($showStyle)
    {
        $showStyle = (int)$showStyle;
        if (!in_array($showStyle, [self::SHOW_STYLE_NO_WHITE_RIGHT_ANGLE, self::SHOW_STYLE_WHITE_ROUND_CORNER])) {
            $this->showStyle = self::SHOW_STYLE_NO_WHITE_RIGHT_ANGLE;
        } else {
            $this->showStyle = $showStyle;
        }
    }

    /**
     * @return mixed
     */
    public function getShowGoodsName()
    {
        return $this->showGoodsName;
    }

    /**
     * @param mixed $showGoodsName
     */
    public function setShowGoodsName($showGoodsName)
    {
        $showGoodsName = (int)$showGoodsName;
        if (!in_array($showGoodsName, [self::GOODS_NAME_NO_SHOW, self::GOODS_NAME_SHOW_NAME])) {
            $this->showGoodsName = self::GOODS_NAME_SHOW_NAME;
        } else {
            $this->showGoodsName = $showGoodsName;
        }
    }

    /**
     * @return mixed
     */
    public function getShowGoodsPrice()
    {
        return $this->showGoodsPrice;
    }

    /**
     * @param mixed $showGoodsPrice
     */
    public function setShowGoodsPrice($showGoodsPrice)
    {
        $showGoodsPrice = (int)$showGoodsPrice;
        if (!in_array($showGoodsPrice, [self::GOODS_PRICE_NO_SHOW, self::GOODS_PRICE_SHOW_NOW, self::GOODS_PRICE_SHOW_NOW_AND_ORIGIN])) {
            $this->showGoodsPrice = self::GOODS_PRICE_SHOW_NOW_AND_ORIGIN;
        } else {
            $this->showGoodsPrice = $showGoodsPrice;
        }
    }

    /**
     * @return mixed
     */
    public function getBuyBtnStyle()
    {
        return $this->buyBtnStyle;
    }

    /**
     * @param mixed $buyBtnStyle
     */
    public function setBuyBtnStyle($buyBtnStyle)
    {
        $buyBtnStyle = (int)$buyBtnStyle;
        if (!in_array($buyBtnStyle, [self::BUY_NOW_SHOW, self::BUY_STYLE_BUY, self::BUY_STYLE_CART, self::BUY_STYLE_LIMIT])) {
            $this->buyBtnStyle = self::BUY_NOW_SHOW;
        } else {
            $this->buyBtnStyle = $buyBtnStyle;
        }
    }

    /**
     * @return mixed
     */
    public function getCornerMarkType()
    {
        return $this->cornerMarkType;
    }

    /**
     * @param mixed $cornerMarkType
     */
    public function setCornerMarkType($cornerMarkType)
    {
        $cornerMarkType = (int)$cornerMarkType;
        if (!in_array($cornerMarkType, [self::CORNER_NOW_SHOW, self::CORNER_STYLE_HOT, self::CORNER_STYLE_HOW_EN, self::CORNER_STYLE_NEW, self::CORNER_STYLE_NEW_EN])) {
            $this->cornerMarkType = self::CORNER_NOW_SHOW;
        } else {
            $this->cornerMarkType = $cornerMarkType;
        }
    }

    /**
     * @return mixed
     */
    public function getGoodsList()
    {
        return $this->goodsList;
    }

    /**
     * @param mixed $goodsList
     */
    public function setGoodsList($goodsList)
    {
        $this->goodsList = $goodsList;
    }

    public function __construct($module = [], $storeId, $memberId)
    {
        parent::__construct($module, $storeId, $memberId);
        $content = $module['content'];
        $this->setGoodsSource($content['goods_source']);
        $this->setGoodsNum($content['goods_num']);
        $this->setListStyle($content['list_style']);
        $this->setShowStyle($content['show_style']);
        $this->setShowGoodsName($content['show_goods_name']);
        $this->setShowGoodsPrice($content['show_goods_price']);
        $this->setBuyBtnStyle($content['buy_btn_style']);
        $this->setCornerMarkType($content['corner_mark_type']);
        $this->setSourceId($content['source_id']);
    }
}