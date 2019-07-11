<?php

namespace Common\Util\DecorationComponents;

/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/9/25
 * Time: 15:20
 */
class GoodsModule extends BaseComponents
{
    protected $tplRelativePath = 'diy/module/goods_module';
    private $goodsHtml;

    /**
     * @return mixed
     */
    public function getGoodsHtml()
    {
        return $this->goodsHtml;
    }

    /**
     * @param mixed $goodsHtml
     * @return $this
     */
    public function setGoodsHtml($goodsHtml)
    {
        $this->goodsHtml = $goodsHtml;
        return $this;
    }

    public function __construct($module = [], $storeId, $memberId)
    {
        parent::__construct($module, $storeId, $memberId);
        $goods = new Goods($module, $storeId, $memberId);
        $goodsHtml = $goods->toHtml();
        $this->setGoodsHtml($goodsHtml);
    }
}