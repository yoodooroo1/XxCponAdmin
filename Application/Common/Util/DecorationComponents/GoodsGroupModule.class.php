<?php

namespace Common\Util\DecorationComponents;

/**
 * 图片分组
 * Class GoodsGroupModule
 * @package Common\Util\DecorationComponents
 * User: hjun
 * Date: 2018-09-25 18:27:36
 * Update: 2018-09-25 18:27:36
 * Version: 1.00
 */
class GoodsGroupModule extends BaseComponents
{
    protected $tplRelativePath = 'diy/module/goods_group_module';

    public function __construct($module, $storeId, $memberId)
    {
        parent::__construct($module, $storeId, $memberId);
        // 获取每个标签对应的商品列表
        foreach ($module['content']['menu_list'] as $key => $tab) {
            $tabContent = $module['content'];
            $tabContent = array_merge($tabContent, $tab);
            $tabModule['content'] = $tabContent;
            $goods = new Goods($tabModule, $storeId, $memberId);
            $tab['goodsHtml'] = $goods->toHtml();
            $module['content']['menu_list'][$key] = $tab;
        }
        $this->setModule($module);
    }
}
