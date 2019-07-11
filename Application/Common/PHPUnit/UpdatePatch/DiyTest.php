<?php

namespace Common\PHPUnit;


use Common\Model\IndexPageTplModel;
use Common\Util\Decoration;
use Common\Util\DecorationComponents\Goods;

class DiyTest extends BaseTest
{
    public function testDiy()
    {
        $model = D('IndexPageTpl');
        $list = $model->selectList();
        F("back_" . NOW_TIME, $list);
        $updateList = [];
        foreach ($list as $value) {
            $tplContent = jsonDecodeToArr($value['tpl_content']);
            $isUpdate = false;

            foreach ($tplContent['tpl_content'] as $key => $module) {
                if ($module['module_type'] == IndexPageTplModel::MODULE_GOODS) {
                    $goodsSource = $module['content']['goods_source'];
                    $sourceId = $module['content']['source_id'];
                    $module['content']['class_id'] = "0";
                    $module['content']['tag_id'] = "0";
                    $module['content']['is_select_class'] = "0";
                    $module['content']['is_select_tag'] = "0";
                    if ($goodsSource == Goods::SOURCE_TAG) {
                        $module['content']['tag_id'] = $sourceId;
                        $module['content']['is_select_tag'] = "1";
                    } else {
                        $module['content']['class_id'] = $sourceId;
                        $module['content']['is_select_class'] = "1";
                    }
                    $isUpdate = true;
                } elseif ($module['module_type'] == IndexPageTplModel::MODULE_GOODS_GROUP) {
                    $menuList = $module['content']['menu_list'];
                    foreach ($menuList as $k => $menu) {
                        $goodsSource = $menu['goods_source'];
                        $sourceId = $menu['source_id'];
                        $menu['class_id'] = "0";
                        $menu['tag_id'] = "0";
                        $menu['is_select_class'] = "0";
                        $menu['is_select_tag'] = "0";
                        if ($goodsSource == Goods::SOURCE_TAG) {
                            $menu['tag_id'] = $sourceId;
                            $menu['is_select_tag'] = "1";
                        } else {
                            $menu['class_id'] = $sourceId;
                            $menu['is_select_class'] = "1";
                        }
                        $menuList[$k] = $menu;
                    }
                    $module['content']['menu_list'] = $menuList;
                    $isUpdate = true;
                } elseif ($module['module_type'] == IndexPageTplModel::MODULE_LIMIT_TIME) {
                    $module['content']['is_select_tag'] = "0";
                    $module['content']['is_select_class'] = "0";
                    $module['content']['class_id'] = "0";
                    $module['content']['tag_id'] = "0";
                    $isUpdate = true;
                } elseif ($module['module_type'] == IndexPageTplModel::MODULE_GROUP_BUYING) {
                    $module['content']['is_select_tag'] = "0";
                    $module['content']['is_select_class'] = "0";
                    $module['content']['class_id'] = "0";
                    $module['content']['tag_id'] = "0";
                    $isUpdate = true;
                }
                $tplContent['tpl_content'][$key] = $module;
            }

            if ($isUpdate) {
                $updateList[] = [
                    'tpl_id' => $value['tpl_id'],
                    'tpl_content' => jsonEncode($tplContent),
                ];
            }
        }
        $sql = buildSaveAllSQL('xunxin_mb_index_page_tpl', $updateList, 'tpl_id');
        $result = $model->execute($sql);
    }
}