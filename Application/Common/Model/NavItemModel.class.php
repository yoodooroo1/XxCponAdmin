<?php

namespace Common\Model;

use Common\Util\Decoration;

/**
 * Class NavItemModel
 * User: hj
 * Desc: 导航模型
 * Date: 2017-11-03 02:03:19
 * Update: 2017-11-03 02:03:20
 * Version: 1.0
 * @package Common\Model
 */
class NavItemModel extends BaseModel
{

    protected $tableName = 'mb_nav_item';
    protected $actionData = Decoration::ACTION_DATA_BUTTON;

    public function getType($type = '')
    {
        if (empty($type)) {
            $type = 'web';
        } elseif ($type === 'wx' || $type === 'web') {
            $type = 'web';
        } else {
            $type = 'app';
        }
        return strtolower($type);
    }

    /**
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取所有的item列表
     * Date: 2017-11-03 17:03:32
     * Update: 2017-11-03 17:03:33
     * Version: 1.0
     */
    public function getAllItemList($page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        $where['is_delete'] = -1;
        $where = array_merge($where, $condition);
        $options = [];
        $options['where'] = $where;
        $options['order'] = 'item_type ASC,channel_id ASC,store_type ASC,store_grade ASC';
        $options['page'] = $page;
        $options['limit'] = $limit;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $newList = [];
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $itemList = json_decode($value['item_list'], 1);
            if (!empty($itemList)) {
                foreach ($itemList as $k => $val) {
                    $val['action_name'] = UtilModel::getLinkName($val['action'], $val[$this->actionData]);
                    $value['item'] = $val;
                    $value['item_id'] = $k;
                    unset($value['item_list']);
                    $newList[] = $value;
                }
            }

        }
        $result['data']['list'] = $newList;
        return $result;
    }

    public function handleInfo(&$info = [], $client = 'web', $storeTypeName = 'alone')
    {
        transEmptyAction($info, $this->actionData);
        $info['action_name'] = UtilModel::getLinkName($info['action'], $info[$this->actionData]);
        $info['old_img'] = $info['imgurl'];
        if (isInAdmin()) {
            $info['imgurl'] = empty($info['imgurl']) ? "/Public/common/img/navIcon/{$client}/{$storeTypeName}/{$info['action']}.png" : $info['imgurl'];
        } else {
            $info['imgurl'] = empty($info['imgurl']) ? '' : $info['imgurl'];
        }
        transSystemAction($info, $this->actionData);
    }

    /**
     * @param int $storeId
     * @param int $index
     * @param string $client
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取某个导航的信息
     * Date: 2017-11-03 17:35:35
     * Update: 2017-11-03 17:35:36
     * Version: 1.0
     */
    public function getItemInfo($storeId = 0, $index = 0, $client = 'app')
    {
        $client = $this->getType($client);
        $where = [];
        $where['store_id'] = $storeId;
        $where['client_type'] = $client;
        $where['item_type'] = 3;
        $where['is_delete'] = -1;
        $options = [];
        $options['where'] = $where;
        $result = $this->queryField($options, 'item_list');
        if ($result['code'] !== 200) return $result;
        $itemList = $result['data'];
        $itemList = json_decode($itemList, 1);
        if (empty($itemList)) return getReturn(-1, '数据异常');
        $item = $itemList[$index];
        $storeType = UtilModel::getStoreType($storeId);
        $storeTypeName = strpos('02', $storeType . '') === false ? "alone" : "mall";
        $this->handleInfo($item, $client, $storeTypeName);
        return getReturn(200, '', $item);
    }

    /**
     * @param int $storeId
     * @param string $client
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取商家的导航 JSON字符串
     * Date: 2017-11-03 10:02:04
     * Update: 2017-11-03 10:02:05
     * Version: 1.0
     */
    public function getNavItemList($storeId = 0, $client = 'app', $condition = [])
    {
        $actionData = $this->actionData;
        $client = $this->getType($client);
        $result = $this->getItemListByStoreId($storeId, $client, $condition);
        if ($result['code'] !== 200) return $result;
        $item = $result['data'];
        if (empty($item)) {
            $result = $this->getTypeGradeItemList($storeId, $client);
            if ($result['code'] !== 200) return $result;
            $info = $result['data']['store_info'];
            $item = $result['data']['base_item_list'];
            // 不存在数据 则为该店铺生成一条新的
            $item = json_encode($item, JSON_UNESCAPED_UNICODE);
            $data = [];
            $data['item_list'] = $item;
            $data['store_id'] = $storeId;
            $data['item_type'] = 3;
            $data['client_type'] = $client;
            $data['create_time'] = NOW_TIME;
            $data['store_grade'] = $info['store_grade'];
            $data['store_type'] = $info['store_type'];
            $data['channel_id'] = $info['channel_id'];
            $data['item_name'] = $info['store_name'];
            $data['item_desc'] = "生成";
            $result = $this->addData([], $data);
            if ($result['code'] !== 200) return $result;
        }
        $item = json_decode($item, 1);
        $storeType = UtilModel::getStoreType($storeId);
        $storeTypeName = strpos('02', $storeType . '') === false ? "alone" : "mall";
        foreach ($item as $key => $value) {
            $item[$key]['item_id'] = $key;
            $this->handleInfo($item[$key], $client, $storeTypeName);
        }
        $item = arrayFlush($item);
        $item = json_encode($item, JSON_UNESCAPED_UNICODE);
        return getReturn(200, '', $item);
    }

    /**
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取某个类别的导航
     * Date: 2017-11-03 09:54:32
     * Update: 2017-11-03 09:54:33
     * Version: 1.0
     */
    public function getItemList($condition = [])
    {
        $options = [];
        $options['field'] = 'item_list';
        $options['where'] = $condition;
        $itemList = $this->selectRow($options)['item_list'];
        return getReturn(CODE_SUCCESS, 'success', $itemList);
    }

    /**
     * @param int $storeId
     * @param string $clientType
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 根据store_id获取商家的单独导航配置
     * Date: 2017-11-03 09:21:26
     * Update: 2017-11-03 09:21:27
     * Version: 1.0
     */
    public function getItemListByStoreId($storeId = 0, $clientType = 'app', $condition = [])
    {
        $clientType = $this->getType($clientType);
        $where = [];
        $where['store_id'] = $storeId;
        $where['item_type'] = 3;
        $where['is_delete'] = -1;
        $where['client_type'] = $clientType;
        $where = array_merge($where, $condition);
        return $this->getItemList($where);
    }

    /**
     * @param int $storeType
     * @param string $clientType
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取商家类型的导航
     * Date: 2017-11-03 09:23:58
     * Update: 2017-11-03 09:23:59
     * Version: 1.0
     */
    public function getItemListByStoreType($storeType = 0, $clientType = 'app', $condition = [])
    {
        $clientType = $this->getType($clientType);
        $where = [];
        $where['store_type'] = $storeType;
        $where['item_type'] = 1;
        $where['is_delete'] = -1;
        $where['client_type'] = $clientType;
        $where = array_merge($where, $condition);
        return $this->getItemList($where);
    }

    /**
     * @param int $channelId
     * @param int $storeGrade
     * @param string $clientType
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取套餐的导航按钮
     * Date: 2017-11-03 09:57:15
     * Update: 2017-11-03 09:57:16
     * Version: 1.0
     */
    public function getItemListByStoreGrade($channelId = 0, $storeGrade = 0, $clientType = 'app', $condition = [])
    {
        $clientType = $this->getType($clientType);
        $where = [];
        $where['channel_id'] = $channelId;
        $where['store_grade'] = $storeGrade;
        $where['item_type'] = 2;
        $where['is_delete'] = -1;
        $where['client_type'] = $clientType;
        $where = array_merge($where, $condition);
        $result = $this->getItemList($where);
        if ($result['code'] !== 200) return $result;
        $itemList = $result['data'];
        // 没有找到则找迅信套餐的
        if (empty($itemList)) {
            $where['channel_id'] = 0;
            $result = $this->getItemList($where);
        }
        return $result;
    }

    /**
     * @param int $storeId
     * @param string $client
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取套餐和店铺的导航 经过筛选
     * Date: 2017-11-03 22:31:45
     * Update: 2017-11-03 22:31:46
     * Version: 1.0
     */
    public function getTypeGradeItemList($storeId = 0, $client = 'app')
    {
        $client = $this->getType($client);
        $result = D('Store')->getStoreInfo($storeId);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        $result = $this->getItemListByStoreGrade($info['channel_id'], $info['store_grade'], $client);
        if ($result['code'] !== 200) return $result;
        $storeGradeItem = $result['data'];
        $result = $this->getItemListByStoreType($info['store_type'], $client);
        if ($result['code'] !== 200) return $result;
        $storeTypeItem = $result['data'];
        $storeGradeItem = json_decode($storeGradeItem, 1);
        $storeTypeItem = json_decode($storeTypeItem, 1);
        $storeGradeItem = empty($storeGradeItem) ? [] : $storeGradeItem;
        $storeTypeItem = empty($storeTypeItem) ? [] : $storeTypeItem;
        $item = array_merge($storeGradeItem, $storeTypeItem);
        $baseItemList = array_sort($item, 'sort', 'ASC');
        // 这一步先记录每个action在数组中的索引
        // 如果没有积分商城 用我的优惠券代替 如果有积分商城 把优惠券去掉
        // 如果有申请代理 把摇奖品或者我的收藏去掉
        $needle = [];
        foreach ($baseItemList as $key => $value) {
            $needle[$value['action']] = $key;
        }
        if (isset($needle['points_mall']) && isset($needle['my_coupon'])) unset($baseItemList[$needle['my_coupon']]);
        // 单店
        if (strpos('0123', $info['store_type'] . '') === false) {
            if (isset($needle['apply_for_agent']) && isset($needle['my_collection'])) unset($baseItemList[$needle['my_collection']]);
            if (isset($needle['apply_for_agent']) && isset($needle['shake_prize'])) unset($baseItemList[$needle['shake_prize']]);
        } else {
            if (isset($needle['apply_for_agent'])) unset($baseItemList[$needle['apply_for_agent']]);
        }
        $list = [];
        foreach ($baseItemList as $key => $value) {
            $list[] = $value;
        }
        $data = [];
        $data['store_info'] = $info;
        $data['base_item_list'] = $list;
        return getReturn(200, '', $data);
    }

    /**
     * @param int $storeId
     * @param int $index
     * @param string $client
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc:保存导航信息
     * Date: 2017-11-03 18:06:38
     * Update: 2017-11-03 18:06:39
     * Version: 1.0
     */
    public function saveItemInfo($storeId = 0, $index = 0, $client = '', $data = [])
    {
        $client = $this->getType($client);
        $result = $this->getNavItemList($storeId, $client);
        if ($result['code'] !== 200) return $result;
        $itemList = json_decode($result['data'], 1);
        if (empty($itemList)) return getReturn(-1, '数据异常');
        // 将默认图片去除
        foreach ($itemList as $key => $value) {
            $itemList[$key]['imgurl'] = $value['old_img'];
            revertSystemAction($itemList[$key], $this->actionData);
        }
        if ($data['action'] == 'system') {
            if (empty($data[$this->actionData])) return getReturn(-1, '请选择系统功能');
            $data['action'] = $data[$this->actionData];
            $data['type'] = UtilModel::actionToType($data['action'], UtilModel::getStoreType($storeId));
            $data[$this->actionData] = '';
        }
        if (empty($data['title'])) {
            $data['title'] = UtilModel::getParamTitle($data['action'], $data[$this->actionData]);
        }
        $result = UtilModel::checkLinkType($data['action'], $data[$this->actionData]);
        if ($result['code'] !== 200) return $result;
        $itemList[$index] = $data;
        $itemList = json_encode($itemList, JSON_UNESCAPED_UNICODE);
        $where = [];
        $where['store_id'] = $storeId;
        $where['client_type'] = $client;
        $where['item_type'] = 3;
        $options = [];
        $options['where'] = $where;
        $data = [];
        $data['item_list'] = $itemList;
        $result = $this->saveData($options, $data);
        // 保存缓存文件
        if ($result['code'] == 200) {
            $htmlDom = $this->buildCacheHtml($itemList, $storeId);
            S("navItem/{$client}/{$storeId}", $itemList);
            if ($client == 'web') S("navItem/{$client}/{$storeId}_dom", $htmlDom);
        }
        return $result;
    }

    /**
     * 新版装修 保存按钮导航
     * @param int $storeId
     * @param string $client
     * @param array $btns
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-09-20 15:59:19
     * Update: 2018-09-20 15:59:19
     * Version: 1.00
     */
    public function saveBtnNav($storeId = 0, $client = '', $btns = [])
    {
        // region 检查参数
        foreach ($btns as $key => $btn) {
            $index = $key + 1;
            // 如果没有http 说明是系统默认图片 路径去除
            if (strpos($btn['img_url'], 'http') === false) {
                $btn['img_url'] = '';
            }
            if ($btn['action'] === Decoration::ACTION_SYSTEM && empty($btn['action_data'])) {
                return getReturn(CODE_ERROR, "请选择第{$index}个按钮导航的系统功能");
            }
            revertSystemAction($btn);
            $result = UtilModel::checkLinkType($btn['action'], $btn['action_data']);
            if (!isSuccess($result)) {
                return getReturn(CODE_ERROR, "第{$index}个按钮导航错误:{$result['msg']}");
            }
            $btn['type'] = UtilModel::actionToType($btn['action'], UtilModel::getStoreType($storeId)); // 兼容旧版APP
            $btn['btn_text'] = empty($btn['btn_text']) ? UtilModel::getParamTitle($btn['action'], $btn['action_data']) : $btn['btn_text'];
            $btns[$key] = $btn;
        }
        // endregion

        // region 保存数据
        $navItem = [];
        foreach ($btns as $btn) {
            $navItem[] = [
                'action' => $btn['action'],
                Decoration::ACTION_DATA_BUTTON => $btn['action_data'],
                'title' => $btn['btn_text'],
                'imgurl' => $btn['img_url'],
                'type' => $btn['type']
            ];
        }
        $client = $this->getType($client);
        $where = [];
        $where['store_id'] = $storeId;
        $where['client_type'] = $client;
        $where['item_type'] = 3;
        $data = [];
        $data['item_list'] = jsonEncode($navItem);
        $result = $this->where($where)->save($data);
        if (false === $result) {
            return getReturn(CODE_ERROR);
        }
        // 保存缓存文件
        $htmlDom = $this->buildCacheHtml($data['item_list'], $storeId);
        S("navItem/{$client}/{$storeId}", $data['item_list']);
        if ($client == 'web') S("navItem/{$client}/{$storeId}_dom", $htmlDom);
        // endregion
        return getReturn(CODE_SUCCESS, L('SAVE_SUCCESSFUL'));
    }

    /**
     * @param string $itemList
     * @param int $storeId
     * @param int $memberId
     * @return mixed ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 生成导航按钮的DOM
     *
     * 单店
     * <style>.toolList a {width: 25%}</style>
     * <div class="toolList border_t10">
     * <a href="{:U('Credit/creditstore',array('se' => $se,'f' => $f))}" class="tCoupon">积分商城</a>
     * <a class="tSign"><span id="needCheck">签到</span></a>
     * <a href="{:U('Goods/collection',array('se' => $se,'f' => $f))}" class="tCollect">我的收藏</a>
     * <a href="{:U('FacePay/index',array('se' => $se,'f' => $f))}" class="tPay textColorFB">直接付款</a>
     * <a href="{:U('Store/serviceCenter',array('se' => $se,'f' => $f))}" class="tService">服务中心</a>
     * </div>
     *
     * 商城
     * <div class="mallItem">
     * <a class="m1_jhs" href="{:U('Goods/mall_goods_list',array('tp' => 1,'se' => $se,'f' => $f))}">精划算</a>
     * <a class="m2_gsp" href="{:U('MallStore/store_classify', array('se'=>$se,'f'=>$f))}">逛商铺</a>
     * <a class="m3_dt" href="/index.php?m=Service&c=Store&a=storemap&se={$se}&f={$f}">地图</a>
     * <a class="m4_wdzj" href="{:U('Goods/mall_goods_list',array('tp' => 9,'se' => $se,'f' => $f))}">我的足迹</a>
     * <a class="m5_scgg" href="{:U('Bulletin/bulletinlist',array('se' => $se,'f' => $f))}">市场公告</a>
     * <a class="m6_yjp" onclick="signIn()">签到</a>
     * <a class="m7_lpdh" href="{:U('Credit/creditstore',array('se' => $se,'f' => $f))}">积分兑换</a>
     * <a class="m8_fwzx" href="{:U('Store/serviceCenter',array('se' => $se,'f' => $f))}">服务中心</a>
     * </div>
     *
     * Date: 2017-11-04 01:47:09
     * Update: 2017-11-04 01:47:11
     * Version: 1.0
     */
    private function buildCacheHtml($itemList = '', $storeId = 0, $memberId = 0)
    {
        if (empty($itemList)) return "";
        $itemList = json_decode($itemList, 1);
        $storeType = UtilModel::getStoreType($storeId) . '';
        //
        if (strpos('02', $storeType) === false) {
            $rate = 100 / count($itemList);
            $html = "<style>.toolList a {width: {$rate}%}</style>" . '<div class="toolList border_t10" {$BG_COLOR}>{$DOM}</div>';
        } else {
            $html = '<div class="mallItem" {$BG_COLOR}>{$DOM}</div>';
        }
        // 2018-09-24 20:14:34 获取文字颜色和背景颜色
        $defaultTplData = D('IndexPageTpl')->getDefaultCtrlData($storeId);
        $bgColor = $defaultTplData['btn_nav']['bg_color'];
        $textColor = $defaultTplData['btn_nav']['text_color'];
        if (!empty($bgColor)) {
            $bgStyle = "style=\"background-color: {$bgColor}\"";
            $html = str_replace('{$BG_COLOR}', $bgStyle, $html);
        }
        $dom = '';
        foreach ($itemList as $key => $value) {
            switch ($storeType) {
                case 0:
                case 2:
                    // 商城
                    $url = UtilModel::getLinkTypeUrl($value['action'], $value[$this->actionData], $storeId);
                    if (empty($value['imgurl'])) {
                        $dom .= '<a {$TEXT_COLOR} href="' . $url . '" class="mall-' . $value['action'] . '">' . $value['title'] . '</a>';
                    } else {
                        $dom .= '<a {$TEXT_COLOR} href="' . $url . '"><img data-src="' . $value['imgurl'] . '">' . $value['title'] . '</a>';
                    }
                    break;
                case 1:
                case 3:
                case 4:
                case 5:
                default:
                    // 单店
                    $url = UtilModel::getLinkTypeUrl($value['action'], $value[$this->actionData], $storeId);
                    if (empty($value['imgurl'])) {
                        $dom .= '<a {$TEXT_COLOR} href="' . $url . '" class="store-' . $value['action'] . '">' . $value['title'] . '</a>';
                    } else {
                        $dom .= '<a {$TEXT_COLOR} href="' . $url . '"><img data-src="' . $value['imgurl'] . '">' . $value['title'] . '</a>';
                    }
                    break;
            }
            // 文字颜色
            if (!empty($textColor)) {
                $textStyle = "style=\"color: {$textColor}\"";
                $dom = str_replace('{$TEXT_COLOR}', $textStyle, $dom);
            }
        }
        $html = str_replace('{$DOM}', $dom, $html);
        $dom = base64_encode($html);
        return $dom;
    }

    /**
     * @param int $storeId
     * @param int $index
     * @param string $client
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 还原某个导航
     * Date: 2017-11-03 23:44:29
     * Update: 2017-11-03 23:44:30
     * Version: 1.0
     */
    public function rollBackItem($storeId = 0, $index = 0, $client = 'app')
    {
        $client = $this->getType($client);
        // 取出还原的样本
        $result = $this->getTypeGradeItemList($storeId, $client);
        if ($result['code'] !== 200) return $result;
        $baseItemList = $result['data']['base_item_list'];
        $rollBackItem = $baseItemList[$index];
        // 还原指定导航
        $result = $this->getNavItemList($storeId, $client);
        if ($result['code'] !== 200) return $result;
        $itemList = json_decode($result['data'], 1);
        if (empty($itemList)) return getReturn(-1, '数据异常');
        unset($rollBackItem['action_name']);
        unset($rollBackItem['item_id']);
        unset($rollBackItem['sort']);
        // 去除默认图
        foreach ($itemList as $key => $value) {
            $itemList[$key]['imgurl'] = $value['old_img'];
        }
        $itemList[$index] = $rollBackItem;
        $itemList = json_encode($itemList, JSON_UNESCAPED_UNICODE);
        // 重置缓存
        $htmlDom = $this->buildCacheHtml($itemList, $storeId);
        S("navItem/{$client}/{$storeId}", $itemList);
        if ($client == 'web') S("navItem/{$client}/{$storeId}_dom", $htmlDom);
        $where = [];
        $where['store_id'] = $storeId;
        $where['client_type'] = $client;
        $where['item_type'] = 3;
        $options = [];
        $options['where'] = $where;
        $data = [];
        $data['item_list'] = $itemList;
        $result = $this->saveData($options, $data);
        return $result;
    }

    /**
     * @param int $storeId
     * @param string $client
     * @param int $memberId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取导航
     * Date: 2017-11-04 02:34:21
     * Update: 2017-11-04 02:34:22
     * Version: 1.0
     */
    public function getCacheHtml($storeId = 0, $client = 'web', $memberId = 0)
    {
        $client = $this->getType($client);
        // 先读缓存
        $item = S("navItem/{$client}/{$storeId}");
        if (is_string($item)) {
            $item = jsonDecodeToArr($item);
        }
        if (empty($item)) {
            $result = $this->getNavItemList($storeId, $client);
            if ($result['code'] !== 200) return $result;
            $item = $result['data'];
            S("navItem/{$client}/{$storeId}", $item);
        } else {
            if (is_string($item)) {
                $item = jsonDecodeToArr($item);
            }
            $item = arrayFlush($item);
            $item = jsonEncode($item);
        }
        $html = $this->buildCacheHtml($item, $storeId, $memberId);
        return base64_decode($html);
    }
}