<?php

namespace Common\Model;

use Common\Util\Decoration;

/**
 * Class ThemeGirdModel
 * User: hj
 * Date: 2017-10-09 14:13:57
 * Desc: 主题格子模型
 * Update: 2017-10-09 14:14:00
 * Version: 1.0
 */
class ThemeGirdModel extends BaseModel
{
    const MODULE_THEME = 1;
    const MODULE_TWO = 2;
    const MODULE_THREE = 3;
    const MODULE_FOUR = 4;
    const MODULE_AD = 5;
    const MODULE_ONE = 6;
    const MODULE_GOODS = 7;

    const SOURCE_CLASS_GOODS = 0;
    CONST SOURCE_TAG_GOODS = 1;

    protected $tableName = 'mb_theme_gird';

    protected $_validate = [
        ['gird_name', 'require', '请输入主题名称', 1, 'regex', 3]
    ];

    /**
     * @param int $storeId 商家ID
     * @param int $channelId 渠道号
     * @param int $page 页数
     * @param int $limit 条数
     * @param array $map 额外查询条件
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-09 14:27:56
     * Desc: 根据商家ID或者渠道ID 获取主题格子列表
     * Update: 2017-10-09 14:27:57
     * Version: 1.0
     */
    public function getThemeGirdList($storeId = 0, $channelId = 0, $page = 1, $limit = 0, $map = [])
    {
        // 判断参数
        $storeId = (int)$storeId;
        $channelId = (int)$channelId;
        if ($storeId <= 0 && $channelId <= 0) return getReturn(-1, '参数错误');
        // 条件查询 使用 store_id 和 channel_id 都可以 大于0就用 没有大于0就不用
        $where = [];
        $where['is_delete'] = -1;
        $storeId > 0 ? $where['store_id'] = $storeId : null;
        $channelId > 0 ? $where['channel_id'] = $channelId : null;
        $where = array_merge($where, $map);
        $skip = ($page - 1) * $limit;
        $take = $limit;
        $order = 'sort DESC,create_time DESC';
        $list = $this
            ->field(true)
            ->where($where)
            ->order($order)
            ->limit($skip, $take)
            ->select();
        if (false === $list) {
            logWrite("查询商家{$storeId}-渠道{$channelId}的主题格子列表出错" . $this->getDbError());
            return getReturn();
        }
        foreach ($list as $key => $value) {
            switch ((int)$value['status']) {
                case 1:
                    $list[$key]['status_name'] = '已开启';
                    $list[$key]['checked'] = "checked";
                    break;
                case 2:
                    $list[$key]['status_name'] = '已关闭';
                    $list[$key]['checked'] = "";
                    break;
                default:
                    $list[$key]['status_name'] = '未知';
                    break;
            }
            $list[$key]['create_time_string'] = date('Y-m-d H:i:s', $value['create_time']);
        }
        return getReturn(200, '', $list);
    }

    /**
     * @param int $girdId 主题ID
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-09 17:02:38
     * Desc: 根据ID删除单个主题
     * Update: 2017-10-09 17:02:42
     * Version: 1.0
     */
    public function delThemeGirdById($girdId = 0)
    {
        $girdId = (int)$girdId;
        if ($girdId <= 0) return getReturn(-1, '参数错误');
        $where = [];
        $where['gird_id'] = $girdId;
        $info = $this->field('gird_id,is_delete')->find($girdId);
        if (empty($info)) return getReturn(-1, '要删除的主题不存在或已被管理员删除');
        if ((int)$info['is_delete'] === 1) return getReturn(-1, '该主题已被删除');
        $data = [];
        $data['is_delete'] = 1;
        $result = $this->where($where)->save($data);
        if (false === $result) {
            logWrite("删除主题{$girdId}出错:" . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '删除成功');
    }

    /**
     * @param int $girdId 主题ID
     * @param int $status 1-显示 2-隐藏
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-09 17:05:38
     * Desc: 修改主题的状态
     * Update: 2017-10-09 17:05:40
     * Version: 1.0
     */
    public function changeThemeGirdStatus($girdId = 0, $status = 1)
    {
        $girdId = (int)$girdId;
        $status = (int)$status;
        if ($girdId <= 0 || (in_array($status, [1, 2]) === false)) return getReturn(-1, '参数错误');
        $where = [];
        $where['gird_id'] = $girdId;
        $where['is_delete'] = -1;
        $info = $this->field('gird_id,status')->find($girdId);
        if (empty($info)) return getReturn(-1, '要修改的主题不存在或者已被管理员删除');
        $msg = $status === 1 ? '该主题已经开启' : '该主题已经关闭';
        if ((int)$info['status'] === $status) return getReturn(-1, $msg);
        $data = [];
        $data['status'] = $status;
        $result = $this->where($where)->save($data);
        if (false === $result) {
            logWrite("更改主题{$girdId}状态出错:" . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '修改成功', $status);
    }

    /**
     * @param int $girdId
     * @param int $sort
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-09 17:12:39
     * Desc: 修改主题的排序
     * Update: 2017-10-09 17:12:40
     * Version: 1.0
     */
    public function changeThemeGirdSort($girdId = 0, $sort = 99)
    {
        $girdId = (int)$girdId;
        $sort = (int)$sort;
        if ($girdId <= 0) return getReturn(-1, '参数错误');
        $where = [];
        $where['gird_id'] = $girdId;
        $where['is_delete'] = -1;
        $info = $this->field('gird_id')->find($girdId);
        if (empty($info)) return getReturn(-1, '要修改的主题不存在或者已被管理员删除');
        $data = [];
        $data['sort'] = $sort;
        $result = $this->where($where)->save($data);
        if (false === $result) {
            logWrite("更改主题{$girdId}排序出错:" . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '修改成功', $sort);
    }

    /**
     * @param int $girdId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-09 18:01:28
     * Desc: 获取主题信息
     * Update: 2017-10-09 18:01:33
     * Version: 1.0
     */
    public function getThemeGirdInfo($girdId = 0)
    {
        $girdId = (int)$girdId;
        $where = [];
        $where['gird_id'] = $girdId;
        $where['is_delete'] = -1;
        $info = $this->field(true)->where($where)->find();
        if (empty($info)) return getReturn(-1, "该主题不存在或已被管理员删除");
        // 解析格子组件
        $info['gird_modules'] = empty($info['gird_modules']) ? '' : json_decode($info['gird_modules'], 1);
        // 排序
        $info['gird_modules'] = array_sort($info['gird_modules'], 'sort', 'ASC');
        // 获取组件最大ID
        $maxMId = $info['gird_modules'][0]['module_id'];
        foreach ($info['gird_modules'] as $key => $value) {
            if ($value['module_id'] > $maxMId) $maxMId = $value['module_id'];
            if ($value['module_type'] == self::MODULE_THEME) $info['type_1_key'] = $key;
            if ($value['module_type'] == self::MODULE_AD) $info['type_5_key'] = $key;
        }
        $info['maxModuleId'] = $maxMId;
        // 获取系统功能的action
        $model = D('LinkType');
        $result = $model->getSystemLinkTypeActionArr();
        if ($result['code'] !== 200) return $result;
        $systemAction = $result['data'];
        foreach ($info['gird_modules'] as $key => $value) {
            // 转换系统功能的type 适用于界面上数值绑定
            foreach ($value['content']['dataset'] as $k => $val) {
                if (in_array($val['action'], $systemAction)) {
                    $info['gird_modules'][$key]['content']['dataset'][$k]['weburl'] = $val['action'];
                    $info['gird_modules'][$key]['content']['dataset'][$k]['action'] = 'system';
                }
                $type = $info['gird_modules'][$key]['content']['dataset'][$k]['action'];
                if (empty($type)) {
                    $info['gird_modules'][$key]['content']['dataset'][$k]['action'] = 'no_action';
                }
            }
        }
        return getReturn(200, '', $info);
    }

    /**
     * @param int $girdId
     * @param int $storeId
     * @param int $channelId
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-13 16:53:06
     * Desc: 保存/新增 主题格子信息
     * Update: 2017-10-13 16:53:07
     * Version: 1.0
     */
    public function saveThemeGirdInfo($girdId = 0, $storeId = 0, $channelId = 0, $data = [])
    {
        $girdId = (int)$girdId;
        $storeId = (int)$storeId;
        $channelId = (int)$channelId;
        if ($storeId <= 0 || $channelId < 0) return getReturn(-1, '商家参数错误');
        $info = [];
        $where = [];
        if ($girdId > 0) {
            $where['gird_id'] = $girdId;
            $info = $this->field(true)->find($girdId);
            if (empty($info)) return getReturn(-1, "该主题不存在或已被管理员删除");
        }
        $data['store_id'] = $storeId;
        $data['channel_id'] = $channelId;
        $data['create_time'] = empty($info['create_time']) ? NOW_TIME : $info['create_time'];
        // DOM缓存字符串
        $dom = [];
        foreach ($data['gird_modules'] as $key => $value) {
            // 界面上绑定系统功能为-1 这里要进行转换
            $index = $key + 1;
            foreach ($value['content']['dataset'] as $k => $val) {
                $index2 = $k + 1;
                if ($val['action'] == 'system') {
                    $result = UtilModel::checkDatasetSystemParam($value, $val, $index, $index2);
                    if (!isSuccess($result)) return $result;
                    $data['gird_modules'][$key]['content']['dataset'][$k]['action'] = $val['weburl'];
                    $data['gird_modules'][$key]['content']['dataset'][$k]['weburl'] = '';
                }
                // action 转换旧版本的导航type
                $data['gird_modules'][$key]['content']['dataset'][$k]['type'] = UtilModel::actionToType($data['gird_modules'][$key]['content']['dataset'][$k]['action'], UtilModel::getStoreType($storeId));
                $data['gird_modules'][$key]['content']['dataset'][$k]['title'] = UtilModel::getParamTitle($data['gird_modules'][$key]['content']['dataset'][$k]['action'], $data['gird_modules'][$key]['content']['dataset'][$k]['weburl']);
                // 检查每个参数
                if ($data['gird_modules'][$key]['status'] == 1) {
                    $result = UtilModel::checkLinkType($data['gird_modules'][$key]['content']['dataset'][$k]['action'], $data['gird_modules'][$key]['content']['dataset'][$k]['weburl']);
                    if (!isSuccess($result)) {
                        $msg = $result['msg'];
                        $result = UtilModel::getModuleError($msg, $value, $index, $index2);
                        if (!isSuccess($result)) {
                            return $result;
                        }
                    }
                }
            }

            // 如果是商品模块 独立检查数据
            if ($value['module_type'] == self::MODULE_GOODS) {
                $result = UtilModel::checkGoodsModule($storeId, $value, $index);
                if (!isSuccess($result)) {
                    return $result;
                }
            }
            // 获取每个组件的DOM编码
            $data['gird_modules'][$key]['dom_item'] = $this->getThemeGirdModuleDom($data['gird_modules'][$key], $storeId, $data['gird_name']);
            $dom[] = $data['gird_modules'][$key]['dom_item'];
        }
        $data['gird_modules'] = json_encode($data['gird_modules'], JSON_UNESCAPED_UNICODE);
        $data = $this->create($data);
        if (false === $data) return getReturn(-1, $this->getError());
        if ($girdId > 0) {
            $options = [];
            $options['where'] = $where;
            $result = $this->saveData($options, $data);
        } else {
            $result = $this->addData([], $data);
        }
        if ($result['code'] !== 200) return $result;
        $info = $this->getThemeGirdInfo($girdId > 0 ? $girdId : $result['data']);
        return getReturn(200, '', $info);
    }

    public function isTitleModule($module)
    {
        return $module == self::MODULE_THEME;
    }

    public function isGoodsModule($module)
    {
        return $module == self::MODULE_GOODS;
    }

    public function isAdvModule($module)
    {
        return $module == self::MODULE_AD;
    }

    public function isImageModule($module)
    {
        return in_array($module, [
            self::MODULE_ONE, self::MODULE_TWO,
            self::MODULE_THREE, self::MODULE_FOUR
        ]);
    }

    public function isSortableModule($module)
    {
        return $this->isGoodsModule($module) || $this->isImageModule($module);
    }

    public function setGoodsModuleSourceId(&$data)
    {
        if ($data['goods_source'] == 0) {
            if (empty($data['class_id'])) {
                $data['class_id'] = $data['source_id'];
            }
        } else {
            if (empty($data['tag_id'])) {
                $data['tag_id'] = $data['source_id'];
            }
        }
        return $this;
    }

    public function saveDefaultGird($storeId = [], $girds = [])
    {
        // 检查数据
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        $moduleId = 11111;
        foreach ($girds as $key => $gird) {
            $girdIndex = $key + 1;
            // 合并所有模块
            $gird['gird_modules'] = [];
            $gird['gird_modules'][] = $gird['list_title'];
            foreach ($gird['list_content'] as $module) {
                $gird['gird_modules'][] = $module;
            }
            $gird['gird_modules'][] = $gird['list_ad'];

            // 处理数据
            foreach ($gird['gird_modules'] as $k => $module) {
                $moduleIndex = $k + 1;
                $location = "第{$girdIndex}个格子的第{$moduleIndex}个";
                $newModule = [
                    'module_id' => $moduleId++,
                    'module_type' => $module['module_type'],
                    'module_name' => $module['module_name'] ? $module['module_name'] : '',
                    'draggable' => $this->isSortableModule($module['module_type']) ? 1 : -1,
                    'status' => $module['status'] == 1 ? '1' : '2',
                    'sort' => $k + 1,
                ];
                $dataSet = [];
                foreach ($module['content']['dataset'] as $kk => $set) {
                    $setIndex = $kk + 1;
                    $newSet = [
                        'type' => $set['type'],
                        'title' => $set['title'],
                        'imgurl' => $set['img_url'] ? $set['img_url'] : '',
                        'action' => $set['action'],
                        'weburl' => $set['action_data'],
                    ];
                    if ($set['action'] == 'system') {
                        $result = UtilModel::checkDatasetSystemParam($newModule, $newSet, $location, $setIndex);
                        if (!isSuccess($result)) return $result;
                        revertSystemAction($newSet, 'weburl');
                    }
                    // action 转换旧版本的导航type
                    $newSet['type'] = UtilModel::actionToType($newSet['action'], UtilModel::getStoreType($storeId));
                    $newSet['title'] = UtilModel::getParamTitle($newSet['action'], $newSet['weburl']);
                    // 检查每个参数
                    if ($newModule['status'] == 1) {
                        $result = UtilModel::checkLinkType($newSet['action'], $newSet['weburl']);
                        if (!isSuccess($result)) {
                            $msg = $result['msg'];
                            $result = UtilModel::getModuleError($msg, $newModule, $location, $setIndex);
                            if (!isSuccess($result)) {
                                return $result;
                            }
                        }
                    }
                    $dataSet[] = $newSet;
                }
                $newModule['content']['dataset'] = $dataSet;

                // 如果是商品模块 独立检查数据
                if ($this->isGoodsModule($newModule['module_type'])) {
                    $newModule['goods_data'] = $module['goods_data'];
                    $this->setGoodsModuleSourceId($newModule['goods_data']);
                    $result = UtilModel::checkGoodsModule($storeId, $newModule, $location);
                    if (!isSuccess($result)) {
                        return $result;
                    }
                }
                // 获取每个组件的DOM编码
                $newModule['dom_item'] = $this->getThemeGirdModuleDom($newModule, $storeId, $gird['title']);

                $gird['gird_modules'][$k] = $newModule;
            }
            $girds[$key] = $gird;
        }

        $results = [];
        // 先删除
        $where = [];
        $where['store_id'] = $storeId;
        $results[] = $this->where($where)->delete();

        // 全部新增
        $data = [];
        $item = [];
        $item['store_id'] = $storeId;
        $item['channel_id'] = $storeInfo['channel_id'];
        $item['create_time'] = NOW_TIME;
        $length = count($girds);
        foreach ($girds as $key => $gird) {
            $item['sort'] = $length - $key;
            $item['status'] = $gird['show'] == 1 ? '1' : '2';
            $item['gird_name'] = $gird['title'];
            $item['gird_modules'] = jsonEncode($gird['gird_modules']);
            $data[] = $item;
        }
        if (!empty($data)) {
            $results[] = $this->addAll($data);
        }


        if (isTransFail($results)) {
            return getReturn(CODE_ERROR);
        }
        return getReturn(CODE_SUCCESS, L('SAVE_SUCCESSFUL'));
    }

    /**
     * 获取商品模块的列表
     * @param $storeId
     * @param $memberId
     * @param $module
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-05-16 02:09:43
     * Update: 2018-05-16 02:09:43
     * Version: 1.00
     */
    public function getModuleGoodsList($storeId, $memberId, $module)
    {
        $modelGE = M('goods_extra');
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        switch ((int)$module['goods_data']['goods_source']) {
            case self::SOURCE_TAG_GOODS:
                $tagId = $module['goods_data']['tag_id'];
                $where['tag_id'] = $tagId;
                $goodsIds = M('goods_tag_link')->where($where)->getField('goods_id', true);
                break;
            default:
                $classId = $module['goods_data']['class_id'];
                $id = explode('|', $classId);
                $level = count($id);
                $id = $id[$level - 1];
                $where = [];
                if (isMall($storeInfo['store_type'])) {
                    $where["mall_class_{$level}"] = $id;
                } else {
                    $where["goods_class_{$level}"] = $id;
                }
                $goodsIds = $modelGE->where($where)->getField('goods_id', true);
                break;
        }
        if (!empty($goodsIds)) {
            $where = [];
            $where['a.goods_id'] = ['in', $goodsIds];
            $list = $modelGE
                ->alias('a')
                ->field('a.goods_id,a.goods_name,a.store_id,a.min_goods_price goods_price,a.goods_img goods_image,a.goods_fig goods_figure')
                ->join('__GOODS__ b ON a.goods_id = b.goods_id')
                ->where($where)
                ->order('b.sort DESC,b.top DESC')
                ->limit($module['goods_data']['goods_num'])
                ->select();
            $list = D('Goods')->initGoodsBeans($storeId, $list, 0, $memberId);
        }
        return empty($list) ? [] : $list;
    }

    /**
     * 获取商品模块的HTML代码
     * @param $storeId
     * @param $module
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-05-16 01:31:48
     * Update: 2018-05-16 01:31:48
     * Version: 1.00
     */
    public function getModuleGoodsHTML($storeId, $module)
    {
        $html = '<div class="theme-goods swiper-list3">
  <ul class="swiper-wrapper">
    {$LI}
  </ul>
  <div class="swiper-button-prev"></div>
  <div class="swiper-button-next"></div>
</div>';
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        if ($module['goods_data']['show_goods_price'] == 1) {
            $price = '<p class="goods-price">' . $storeInfo['currency_symbol'] . '{$PRICE}</p>';
        } else {
            $price = '';
        }
        if ($module['goods_data']['show_goods_name'] == 1) {
            $name = '<p class="goods-name">{$NAME}</p>';
        } else {
            $name = '';
        }
        // <i class="myicon-cart-red"></i> 购物车按钮 暂时去掉
        $li = '<li class="swiper-slide">
      <a href="{$URL}">
        <div class="goods-img"><img data-src="{$GOODS_IMG}?_750xx2"></div>
        <div class="goods-info">
          {$NAME}
          {$PRICE}
          
        </div>
      </a>
    </li>';
        $modelGE = M('goods_extra');
        switch ((int)$module['goods_data']['goods_source']) {
            case self::SOURCE_TAG_GOODS:
                $tagId = $module['goods_data']['tag_id'];
                $where['tag_id'] = $tagId;
                $goodsIds = M('goods_tag_link')->where($where)->getField('goods_id', true);
                break;
            default:
                $classId = $module['goods_data']['class_id'];
                $id = explode('|', $classId);
                $level = count($id);
                $id = $id[$level - 1];
                $where = [];
                if (isMall($storeInfo['store_type'])) {
                    $where["mall_class_{$level}"] = $id;
                } else {
                    $where["goods_class_{$level}"] = $id;
                }
                $goodsIds = $modelGE->where($where)->getField('goods_id', true);
                break;
        }
        $lis = '';
        if (!empty($goodsIds)) {
            $where = [];
            $where['a.goods_id'] = ['in', $goodsIds];
            $list = $modelGE
                ->alias('a')
                ->field('a.goods_id,a.goods_name,a.store_id,a.min_goods_price goods_price,a.goods_img goods_image,a.goods_fig goods_figure')
                ->join('__GOODS__ b ON a.goods_id = b.goods_id')
                ->where($where)
                ->order('b.sort DESC,b.top DESC')
                ->limit($module['goods_data']['goods_num'])
                ->select();
            $list = D('Goods')->initGoodsBeans($storeId, $list);
            foreach ($list as $goods) {
                $tempLi = str_replace('{$GOODS_IMG}', $goods['main_img'], $li);
                $tmpPrice = '';
                if (!empty($price)) {
                    $tmpPrice = str_replace('{$PRICE}', $goods['new_price'], $price);
                }
                $tmpName = '';
                if (!empty($name)) {
                    $tmpName = str_replace('{$NAME}', $goods['goods_name'], $name);
                }
                $tempLi = str_replace('{$NAME}', $tmpName, $tempLi);
                $tempLi = str_replace('{$PRICE}', $tmpPrice, $tempLi);
                $memberId = session('member_id') > 0 ? session('member_id') : 0;
                $url = U('Goods/goods_detail', ['id' => $goods['goods_id'], 'se' => $storeId, 'f' => $memberId]);
                $tempLi = str_replace('{$URL}', $url, $tempLi);
                $lis .= $tempLi;
            }
        }
        $html = str_replace('{$LI}', $lis, $html);
        return $html;
    }

    /**
     * @param int $storeId
     * @param int $channelId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-14 23:16:28
     * Desc: 获取商家状态开启的主题格子 并解码组件DOM
     * Update: 2017-10-14 23:16:30
     * Version: 1.0
     */
    public function getStoreThemeGirdHTML($storeId = 0, $channelId = 0)
    {
        $html = S("theme_gird:{$storeId}:{$channelId}");
        if (empty($html)) {
            $list = $this->getStoreThemeGird($storeId, $channelId);
            $html = "";
            foreach ($list as $key => $value) {
                $modules = json_decode($value['gird_modules'], 1);
                foreach ($modules as $k => $val) {
                    if ($val['status'] == 1) {
                        $html .= base64_decode($this->getThemeGirdModuleDom($val, $storeId, $value['gird_name']));
                        if ($val['module_type'] == self::MODULE_GOODS) {
                            $dom = UtilModel::getModuleGoodsHTML($storeId, $val);
                            $html .= $dom;
                        }
                    }
                }
            }
            S("theme_gird:{$storeId}:{$channelId}", $html);
        }
        return getReturn(200, '', $html);
    }

    /**
     * 获取商家的主题格子列表
     * @param int $storeId
     * @param int $channelId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-06-27 16:42:19
     * Update: 2018-06-27 16:42:19
     * Version: 1.00
     */
    public function getStoreThemeGird($storeId = 0, $channelId = 0)
    {
        // 判断参数
        $storeId = (int)$storeId;
        $channelId = (int)$channelId;
        if ($storeId <= 0 && $channelId <= 0) return [];
        // 条件查询 使用 store_id 和 channel_id 都可以 大于0就用 没有大于0就不用
        $where = [];
        $where['is_delete'] = -1;
        $where['status'] = 1;
        $storeId > 0 ? $where['store_id'] = $storeId : null;
        $channelId > 0 ? $where['channel_id'] = $channelId : null;
        $order = 'sort DESC,create_time DESC';
        $list = $this
            ->field('gird_modules,gird_name')
            ->where($where)
            ->order($order)
            ->select();
        return empty($list) ? [] : $list;
    }

    /**
     * @param array $modules 组件信息
     * @param int $storeId
     * @param string $themeName 主题名称
     * @return string '' DOM代码密文
     * User: hj
     * Date: 2017-10-13 17:32:16
     * Desc: 获取主题格子 每个组件的DOM代码
     * Update: 2017-10-13 17:32:18
     * Version: 1.0
     */
    public function getThemeGirdModuleDom($modules = [], $storeId = 0, $themeName = '')
    {
        if (empty($modules)) return '';
        $dom = '';
        switch ((int)$modules['module_type']) {
            case self::MODULE_THEME:
                $url = $this->getModulesDataLinkTypeUrl($modules['content']['dataset'][0], $storeId);
                $more = $url == 'javascript:;' ? '' : '<span class="_more">更多</span>';
                $defaultWord = '';
                // 如果没有配图，则默认配图+文字 否则使用配图不加文字
                if (empty($modules['content']['dataset'][0]['imgurl'])) {
                    $modules['content']['dataset'][0]['imgurl'] = "/Public/admin2/img/system/md_themeAdInfo/tName_no.png";
                    $defaultWord = '<h4>' . $themeName . '</h4>';
                }
                $dom = '<a class="columnT" href="' . $url . '"><img data-src="' . $modules['content']['dataset'][0]['imgurl'] . '?_750xx2">' . $defaultWord . $more . '</a>';
                break;
            case self::MODULE_TWO:
                $html = '';
                foreach ($modules['content']['dataset'] as $key => $value) {
                    $url = $this->getModulesDataLinkTypeUrl($value, $storeId);
                    $html .= '<a href="' . $url . '"><img data-src="' . $value['imgurl'] . '?_750xx2"></a>';
                }
                $dom = '<div class="list2 border_b1">' . $html . '</div>';
                break;
            case self::MODULE_THREE:
            case self::MODULE_FOUR:
                $html = '';
                foreach ($modules['content']['dataset'] as $key => $value) {
                    $url = $this->getModulesDataLinkTypeUrl($value, $storeId);
                    $html .= '<a href="' . $url . '"><img data-src="' . $value['imgurl'] . '?_750xx2"></a>';
                }
                $dom = '<div class="list' . $modules['module_type'] . '">' . $html . '</div>';
                break;
            case self::MODULE_AD:
                $html = '';
                foreach ($modules['content']['dataset'] as $key => $value) {
                    $url = $this->getModulesDataLinkTypeUrl($value, $storeId);
                    $html .= '<li class="swiper-slide"><a href="' . $url . '"><img data-src="' . $value['imgurl'] . '?_750xx2"></a></li>';
                }
                $dom = '<div class="adBanner"><ul class="swiper-wrapper">' . $html . '</ul><ul class="swiper-pagination"></ul></div>';
                break;
            // 单列
            case self::MODULE_ONE:
                $html = '';
                foreach ($modules['content']['dataset'] as $key => $value) {
                    $url = $this->getModulesDataLinkTypeUrl($value, $storeId);
                    $html .= '<a href="' . $url . '"><img data-src="' . $value['imgurl'] . '?_750xx2"></a>';
                }
                $dom = '<div class="list1 border_b1">' . $html . '</div>';
                break;
            // 商品模块
            case self::MODULE_GOODS:
                // 商品模块需要在获取的时候才去组装 因为商品不固定
                break;
            default:
                break;
        }
        return base64_encode($dom);
    }

    /**
     * @param array $data
     * @param int $storeId
     * @return string ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-14 20:55:19
     * Desc: 获取组件的跳转方式
     * Update: 2017-10-14 20:55:20
     * Version: 1.0
     */
    private function getModulesDataLinkTypeUrl($data = [], $storeId = 0)
    {
        return UtilModel::getLinkTypeUrl($data['action'], $data['weburl'], $storeId);
    }

    /**
     * 获取默认模版的主题格子数据
     * @param int $storeId
     * @return array
     * User: hjun
     * Date: 2018-09-21 16:27:35
     * Update: 2018-09-21 16:27:35
     * Version: 1.00
     */
    public function getDefaultTplGirdData($storeId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['is_delete'] = -1;
        $options = [];
        $options['field'] = true;
        $options['where'] = $where;
        $options['order'] = 'sort DESC,gird_id DESC';
        $list = $this->selectList($options);
        $themeList = [];
        foreach ($list as $theme) {
            $item = [];
            $item['gird_id'] = $theme['gird_id'];
            $item['theme_id'] = $theme['gird_id'];
            $item['show'] = $theme['status'] == 1 ? 1 : 0;
            $item['title'] = $theme['gird_name'];
            $modules = jsonDecodeToArr($theme['gird_modules']);
            $maxId = 0;
            foreach ($modules as $module) {
                if ($module['module_id'] > $maxId) {
                    $maxId = $module['module_id'];
                }
                foreach ($module['content']['dataset'] as $key => $data) {
                    $data['img_url'] = $data['imgurl'];
                    $data['action_data'] = $data[Decoration::ACTION_DATA_BUTTON];
                    $module['content']['dataset'][$key] = $data;
                }
                if ($this->isTitleModule($module['module_type'])) {
                    $item['list_title'] = $module;
                } elseif ($this->isAdvModule($module['module_type'])) {
                    $item['list_ad'] = $module;
                } else {
                    if ($this->isGoodsModule($module['module_type'])) {
                        $module['goods_data']['show_goods_name'] = $module['goods_data']['show_goods_name'] ? '1' : '0';
                        $module['goods_data']['show_goods_price'] = $module['goods_data']['show_goods_price'] ? '1' : '0';
                    }
                    $item['list_content'][] = $module;
                }
            }
            $item['max_id'] = $maxId;
            $themeList[] = $item;
        }
        return $themeList;
    }
}