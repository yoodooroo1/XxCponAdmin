<?php

namespace Common\Util\DecorationComponents;

/**
 * 限时抢购
 * Class LimitTimeModule
 * @package Common\Util\DecorationComponents
 * User: hjun
 * Date: 2018-09-25 18:27:36
 * Update: 2018-09-25 18:27:36
 * Version: 1.00
 */
class LimitTimeModule extends BaseComponents
{
    protected $tplRelativePath = 'diy/module/limit_time_module';

    private $goodsList;

    private $goodsIds;
    private $tagName; // 标签名称

    /**
     * @return mixed
     */
    public function getTagName()
    {
        return $this->tagName;
    }

    /**
     * @param mixed $tagName
     */
    public function setTagName($tagName)
    {
        $this->tagName = $tagName;
    }

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
        $num = empty($module['content']['goods_num']) ? 1 : $module['content']['goods_num'];
        // 查找相关goodsId
        $goodsIds = getGoodsIdsByDiySource($module['content'], $this->getStoreInfo());
        $where = [];
        if ($goodsIds !== null) {
            $where['goods_id'] = getInSearchWhereByArr($goodsIds);
            $this->goodsIds = $goodsIds;
            // 查询标签名称
            if ($module['content']['is_select_tag'] == 1 && !empty($module['content']['tag_id'])) {
                $tagWhere = [];
                $tagWhere['tag_id'] = $module['content']['tag_id'];
                $this->tagName = D('GoodsTag')->where($tagWhere)->getField('tag_name');
            }
        }
        $goodsList = D('GoodsExtra')->getQiangGoodsList($storeId, 1, $num, $where)['data']['list'];
        $this->setGoodsList($goodsList);
        // 链接中带着模块标识 方便从redis中获取当前模块的数据
        $self = serialize($this);
        $this->moduleKey = md5($self);
        S("diy_module:{$this->moduleKey}", $self);
    }

}
