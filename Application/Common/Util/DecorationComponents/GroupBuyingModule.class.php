<?php

namespace Common\Util\DecorationComponents;

/**
 * 团购
 * Class GroupBuyingModule
 * @package Common\Util\DecorationComponents
 * User: hjun
 * Date: 2018-09-25 18:27:36
 * Update: 2018-09-25 18:27:36
 * Version: 1.00
 */
class GroupBuyingModule extends BaseComponents
{
    protected $tplRelativePath = 'diy/module/group_buying_module';

    private $goodsList;

    private $goodsIds;

    /**
     * @return string
     */
    public function getGoodsIds()
    {
        return $this->goodsIds;
    }

    /**
     * @param string $goodsIds
     */
    public function setGoodsIds($goodsIds)
    {
        $this->goodsIds = $goodsIds;
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

    public function __construct($module, $storeId, $memberId)
    {
        parent::__construct($module, $storeId, $memberId);
        $auth = $this->getAuth();
        if ($auth['group_buy_ctrl'] == 1) {
            $num = empty($module['content']['goods_num']) ? 1 : $module['content']['goods_num'];
            // 查找相关goodsId
            $goodsIds = getGoodsIdsByDiySource($module['content'], $this->getStoreInfo());
            $where = [];
            if ($goodsIds !== null) {
                $where['a.goods_id'] = getInSearchWhereByArr($goodsIds);
                $this->goodsIds = $goodsIds;
            }
            $goodsList = D('GroupBuying')->getIndexGroupBuyList($storeId, 1, $num, $where)['data'];
            $this->setGoodsList($goodsList);
        }
        // 链接中带着模块标识 方便从redis中获取当前模块的数据
        $self = serialize($this);
        $this->moduleKey = md5($self);
        S("diy_module:{$this->moduleKey}", $self);
    }
}
