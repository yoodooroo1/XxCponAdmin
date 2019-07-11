<?php

namespace Common\Util\DecorationComponents;

/**
 * 新品推荐
 * Class NewGoodsModule
 * @package Common\Util\DecorationComponents
 * User: hjun
 * Date: 2018-09-25 18:27:36
 * Update: 2018-09-25 18:27:36
 * Version: 1.00
 */
class NewGoodsModule extends BaseComponents
{
    protected $tplRelativePath = 'diy/module/new_goods_module';

    private $goodsList;

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

    public function __construct($module, $storeId, $memberId)
    {
        parent::__construct($module, $storeId, $memberId);
        $num = empty($module['content']['goods_num']) ? 1 : $module['content']['goods_num'];
        $goodsList = D('Goods')->getGoodsListsWithNew($storeId, 1, $num);
        $this->setGoodsList($goodsList);
    }
}
