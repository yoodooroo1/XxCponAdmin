<?php

namespace Common\Model;

use Common\Util\Decoration;
use Common\Util\DecorationComponents\AuxiliaryDivModule;
use Common\Util\DecorationComponents\AuxiliaryLineModule;
use Common\Util\DecorationComponents\BaseComponents;
use Common\Util\DecorationComponents\BtnNaves;
use Common\Util\DecorationComponents\CouponModule;
use Common\Util\DecorationComponents\CubeModule;
use Common\Util\DecorationComponents\CustomerModule;
use Common\Util\DecorationComponents\GoodsGroupModule;
use Common\Util\DecorationComponents\GoodsModule;
use Common\Util\DecorationComponents\GroupBuyingModule;
use Common\Util\DecorationComponents\ImageAds;
use Common\Util\DecorationComponents\LimitTimeModule;
use Common\Util\DecorationComponents\NewGoodsModule;
use Common\Util\DecorationComponents\NewsModule;
use Common\Util\DecorationComponents\NoticeModule;
use Common\Util\DecorationComponents\SearchModule;
use Common\Util\DecorationComponents\Tpl;

class IndexPageTplModel extends BaseModel
{
    const DEFAULT_ID = 'default';

    const MODULE_GOODS = 10;
    const MODULE_ADV = 11;
    const MODULE_CUBE = 12;
    const MODULE_BTN_NAV = 13;
    const MODULE_GOODS_GROUP = 14;
    const MODULE_COUPON = 15;
    const MODULE_LIMIT_TIME = 16;
    const MODULE_GROUP_BUYING = 17;
    const MODULE_NEW_GOODS = 18;
    const MODULE_SEARCH = 19;
    const MODULE_NOTICE = 20;
    const MODULE_AUXILIARY_LINE = 21;
    const MODULE_AUXILIARY_DIV = 22;
    const MODULE_CUSTOMER_MSG_MODULE = 23;
    const MODULE_NEWS = 25;

    private $storeId = 0;
    private $memberId = 0;

    protected $tableName = "mb_index_page_tpl";

    protected $type = Decoration::TYPE_WX;

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @return int
     */
    public function getMemberId()
    {
        return $this->memberId;
    }

    /**
     * @param int $memberId
     * @return $this
     */
    public function setMemberId($memberId)
    {
        $this->memberId = $memberId;
        return $this;
    }

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        if (in_array($type, [Decoration::TYPE_WX, Decoration::TYPE_APP])) {
            $this->type = $type;
        } else {
            $this->type = Decoration::TYPE_WX;
        }
        return $this;
    }


    /**
     * 判断模版是否是主页
     * @param $tpl
     * @return boolean
     * User: hjun
     * Date: 2018-07-26 15:21:03
     * Update: 2018-07-26 15:21:03
     * Version: 1.00
     */
    public function isMainTpl($tpl)
    {
        return $tpl['is_main'] == 1;
    }

    /**
     * 判断是否是默认模版
     * @param $tpl
     * @return boolean
     * User: hjun
     * Date: 2018-07-27 15:39:44
     * Update: 2018-07-27 15:39:44
     * Version: 1.00
     */
    public function isDefaultTpl($tpl)
    {
        return $tpl['tpl_id'] == self::DEFAULT_ID;
    }

    /**
     * 获取店铺的首页模版里诶报
     * @param int $storeId
     * @return array
     * User: hjun
     * Date: 2018-07-26 15:16:47
     * Update: 2018-07-26 15:16:47
     * Version: 1.00
     */
    public function getTplList($storeId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['device'] = $this->getType();
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['where'] = $where;
        $list = $this->selectList($options);
        $isDefault = 1;
        foreach ($list as $tpl) {
            if ($this->isMainTpl($tpl)) {
                $isDefault = 0;
                break;
            }
        }
        // 默认模版
        $default = [
            'tpl_id' => self::DEFAULT_ID,
            'store_id' => $storeId,
            'tpl_name' => '默认模版',
            'tpl_img' => '',
            'tpl_content' => '',
            'is_main' => $isDefault,
            'device' => $this->getType(),
        ];
        array_unshift($list, $default);
        foreach ($list as $key => $tpl) {
            if ($this->isDefaultTpl($tpl)) {
                $url = getStoreDomain($storeId) . "/index.php?se={$storeId}";
            } elseif ($this->isMainTpl($tpl)) {
                $url = getStoreDomain($storeId) . "/index.php?c=Decoration&a=mainTpl&se={$storeId}";
            } else {
                $url = getStoreDomain($storeId) . "/index.php?c=Decoration&a=tpl&tpl_id={$tpl['tpl_id']}&se={$storeId}";
            }
            $this->auto([
                ['link_url', $url, self::MODEL_BOTH, 'string'],
            ]);
            $list[$key] = $this->autoOperation($tpl, self::MODEL_UPDATE);
        }
        return $list;
    }

    /**
     * 获取系统模版列表
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-07-27 15:53:50
     * Update: 2018-07-27 15:53:50
     * Version: 1.00
     */
    public function getSystemTplList()
    {
        $where = [];
        $where['device'] = $this->getType();
        $where['is_system'] = 1;
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['where'] = $where;
        return $this->selectList($options);
    }

    /**
     * 获取模版数据
     * @param int $storeId
     * @param int $tplId
     * @return array
     * User: hjun
     * Date: 2018-07-27 15:42:58
     * Update: 2018-07-27 15:42:58
     * Version: 1.00
     */
    public function getTpl($storeId = 0, $tplId = 0)
    {
        if ($tplId === self::DEFAULT_ID) {
            return [
                'tpl_id' => self::DEFAULT_ID
            ];
        }
        // 如果空 则返回默认数据
        if (empty($tplId)) {
            $data = getDefaultData('json/diy/defaultDiyTplData');
            $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
            $data['page_config']['title_name'] = $storeInfo['store_name'];
            return $data;
        }
        $where = [];
        $where['store_id'] = $storeId;
        $where['tpl_id'] = $tplId;
        $where['device'] = $this->getType();
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['field'] = true;
        $options['where'] = $where;
        return $this->selectRow($options);
    }

    /**
     * 获取微信商城默认模版数据
     * 默认模版数据是各个表数据的汇总
     * @param int $storeId
     * @return array
     * User: hjun
     * Date: 2018-07-27 15:43:46
     * Update: 2018-07-27 15:43:46
     * Version: 1.00
     */
    public function getWxDefaultTpl($storeId = 0)
    {
        return $this->setType(Decoration::TYPE_WX)->getDefaultTpl($storeId);
    }

    /**
     * 获取APP默认模版
     * @param int $storeId
     * @return array
     * User: hjun
     * Date: 2018-07-27 15:45:07
     * Update: 2018-07-27 15:45:07
     * Version: 1.00
     */
    public function getAppDefaultTpl($storeId = 0)
    {
        return $this->setType(Decoration::TYPE_APP)->getDefaultTpl($storeId);
    }

    /**
     * 设置模版为主页
     * @param int $storeId
     * @param int $tplId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-07-27 15:36:31
     * Update: 2018-07-27 15:36:31
     * Version: 1.00
     */
    public function setMainTpl($storeId = 0, $tplId = 0)
    {
        $tpl = $this->getTpl($storeId, $tplId);
        if (empty($tpl)) {
            return getReturn(CODE_NOT_FOUND, L('_SELECT_NOT_EXIST_'));
        }
        $where = [];
        $where['store_id'] = $storeId;
        $where['is_delete'] = NOT_DELETE;
        $this->startTrans();
        $results = [];
        // 先把原来的主页设置为非主页
        $data = [];
        $data['is_main'] = 0;
        if (!$this->isDefaultTpl($tpl)) {
            $where['is_main'] = 1;
        }
        $results[] = $this->where($where)->save($data);
        // 将模版设置为主页
        if (!$this->isDefaultTpl($tpl)) {
            $where = [];
            $where['tpl_id'] = $tplId;
            $data = [];
            $data['is_main'] = 1;
            $results[] = $this->where($where)->save($data);
        }

        if (isTransFail($results)) {
            $this->rollback();
            return getReturn(CODE_ERROR);
        }
        $this->commit();
        return getReturn(CODE_SUCCESS, '设置成功');
    }

    /**
     * 删除模版
     * @param int $storeId
     * @param int $tplId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-08-02 10:56:54
     * Update: 2018-08-02 10:56:54
     * Version: 1.00
     */
    public function delTpl($storeId = 0, $tplId = 0)
    {
        $tpl = $this->getTpl($storeId, $tplId);
        if (empty($tpl)) {
            return getReturn(CODE_NOT_FOUND, '模版不存在');
        }
        if ($this->isDefaultTpl($tpl)) {
            return getReturn(CODE_ERROR, '默认模版不可删除');
        }
        if ($this->isMainTpl($tpl)) {
            return getReturn(CODE_ERROR, '主页模版不可删除');
        }
        $where = [];
        $where['device'] = $this->getType();
        $where['tpl_id'] = $tplId;
        $data = [];
        $data['is_delete'] = DELETED;
        $results[] = $this->where($where)->save($data);
        if (isTransFail($results)) {
            return getReturn(CODE_ERROR);
        }
        return getReturn(CODE_SUCCESS, L('DEL_SUCCESS'));
    }

    /**
     * 获取默认模版的开关数据
     * @param int $storeId
     * @return array
     * User: hjun
     * Date: 2018-09-24 19:57:02
     * Update: 2018-09-24 19:57:02
     * Version: 1.00
     */
    public function getDefaultCtrlData($storeId = 0)
    {
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        $data = jsonDecodeToArr($storeInfo['default_tpl_data']);
        // 如果没有数据 则默认
        if (empty($data)) {
            $data = getDefaultData('json/diy/defaultTplData');
        }
        return $data;
    }

    /**
     * 获取默认模版数据
     * 默认模版数据是各个表数据的汇总
     * @param int $storeId
     * @return array
     * User: hjun
     * Date: 2018-07-27 15:45:50
     * Update: 2018-07-27 15:45:50
     * Version: 1.00
     */
    public function getDefaultTpl($storeId = 0)
    {
        // 广告数量
        $advNum = getAdvNum($storeId);

        // region 开关数据存在该字段
        $data = $this->getDefaultCtrlData($storeId);
        // endregion

        // region 其他旧数据需要查表

        // region 顶部轮播广告 图片广告
        $advList = D('Advertise')->getAdvertiseList($storeId, [], $advNum)['data'];
        $data['ad_img']['img_list'] = [];
        foreach ($advList as $adv) {
            $data['ad_img']['img_list'][] = [
                'adv_id' => $adv['advertise_id'], // 要存储ID 保存到的时候要保存到对应的ID去
                'name' => $adv['advertise_name'], // 存储name，保存的时候调用方法是需要的参数
                'word' => $adv['advertise_link']['word'], // 存储word，保存的时候调用方法是需要的参数
                'action' => $adv['advertise_link']['action'],
                'action_data' => $adv['advertise_link'][Decoration::ACTION_DATA_ADV],
                'img_url' => $adv['purl']
            ];
        }
        // endregion

        // region 按钮导航
        $type = $this->getType() === Decoration::TYPE_APP ?: 'web';
        $result = D('NavItem')->getNavItemList($storeId, $type);
        $btns = jsonDecodeToArr($result['data']);
        $data['btn_nav']['btn_list'] = [];
        foreach ($btns as $btn) {
            $data['btn_nav']['btn_list'][] = [
                'action' => $btn['action'],
                'action_data' => $btn[Decoration::ACTION_DATA_BUTTON],
                'img_url' => $btn['imgurl'],
                'btn_text' => $btn['title']
            ];
        }
        // endregion

        // region 中间通栏广告
        $midList = D('MiddleAd')->getMiddleAdList($storeId)['data'];
        $midList = $this->getListByPage(1, $advNum, $midList);
        $data['ad_center']['img_list'] = [];
        foreach ($midList as $mid) {
            $data['ad_center']['img_list'][] = [
                'action' => $mid['mid_link']['action'],
                'action_data' => $mid['mid_link'][Decoration::ACTION_DATA_ADV],
                'advertise_name' => $mid['mid_link']['word'],
                'img_url' => $mid['mid_link']['purl'],
                'show' => $mid['mid_status'] == 1 ? 1 : 0,
            ];
        }
        // endregion

        // region 主题格子
        $data['ad_theme']['ctrl'] = themeGirdCtrl($storeId) ? 1 : 0;
        if ($data['ad_theme']['ctrl']) {
            // 如果有权限再去查询数据
            $data['ad_theme']['theme_list'] = D('ThemeGird')->getDefaultTplGirdData($storeId);
            $maxId = 0;
            foreach ($data['ad_theme']['theme_list'] as $gird) {
                if ($gird['gird_id'] > $maxId) {
                    $maxId = $gird['gird_id'];
                }
            }
            $data['ad_theme']['max_id'] = $maxId;
        }
        // endregion

        // endregion
        unset($data['type']);
        return $data;
    }

    /**
     * 保存默认模版数据
     * @param int $storeId
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-09-19 11:26:44
     * Update: 2018-09-19 11:26:44
     * Version: 1.00
     */
    public function saveDefaultTpl($storeId = 0, $request = [])
    {
        unset($request['type']);
        $this->startTrans();
        // region 保存显示隐藏的数据
        $results = [];
        $model = M('store');
        $where = [];
        $where['store_id'] = $storeId;
        $data = [];
        $data['default_tpl_data'] = jsonEncode($request);
        $results[] = $model->where($where)->save($data);
        // endregion

        // region 新品推荐的显示
        $data = [];
        $data['new_goods_index'] = $request['new_goods']['show'] == 1 ? 1 : 0;
        $where = [];
        $where['store_id'] = $storeId;
        $results[] = M('store')->where($where)->save($data);
        // endregion

        // region 保存轮播
        $swiperAdvModel = D('Advertise');
        $swiperAdv = $request['ad_img']['img_list'];
        $result = $swiperAdvModel->saveAdvList($storeId, $swiperAdv);
        if (!isSuccess($result)) {
            $this->rollback();
            return $result;
        }
        // endregion

        // region 保存按钮
        $btnNavModel = D('NavItem');
        $btns = $request['btn_nav']['btn_list'];
        $result = $btnNavModel->saveBtnNav($storeId, $this->getType(), $btns);
        if (!isSuccess($result)) {
            $this->rollback();
            return $result;
        }
        // endregion

        // region 保存中间通栏
        $midModel = D('MiddleAd');
        $midAdvs = $request['ad_center']['img_list'];
        $result = $midModel->saveMidAdv($storeId, $midAdvs);
        if (!isSuccess($result)) {
            $this->rollback();
            return $result;
        }
        // endregion

        // region 保存主题格子
        $girdModel = D('ThemeGird');
        $girds = $request['ad_theme']['theme_list'];
        $result = $girdModel->saveDefaultGird($storeId, $girds);
        if (!isSuccess($result)) {
            $this->rollback();
            return $result;
        }
        // endregion

        if (isTransFail($results)) {
            $this->rollback();
            return getReturn(CODE_ERROR);
        }
        $this->commit();
        return getReturn(CODE_SUCCESS, L('SAVE_SUCCESSFUL'), $request);
    }

    /**
     * 获取组件数据
     * @param int $storeId
     * @return array
     * User: hjun
     * Date: 2018-09-25 02:51:36
     * Update: 2018-09-25 02:51:36
     * Version: 1.00
     */
    public function getComponentGroup($storeId = 0)
    {
        $data = getDefaultData('json/diy/componentGroup');
        $auth = D('StoreGrade')->getStoreGrantInfo($storeId)['data'];
        foreach ($data as $key => $item) {
            foreach ($item['component_list'] as $k => $val) {
                if ($val['module_type'] == self::MODULE_GROUP_BUYING && $auth['group_buy_ctrl'] != 1) {
                    unset($data[$key]['component_list'][$k]);
                }
            }
        }
        return $data;
    }

    /**
     * 保存模版数据
     * @param int $storeId
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2018-09-25 03:17:38
     * Update: 2018-09-25 03:17:38
     * Version: 1.00
     */
    public function saveTpl($storeId = 0, $request = [])
    {
        $tplId = $request['tpl_id'];
        $data = [];
        $data['store_id'] = $storeId;
        $data['tpl_img'] = empty($request['tpl_img']) ? '' : $request['tpl_img'];
        $data['tpl_name'] = $request['page_config']['title_name'];
        $data['tpl_content'] = jsonEncode($request);
        $data['device'] = $this->getType();
        if ($tplId > 0) {
            $where = [];
            $where['tpl_id'] = $tplId;
            $where['store_id'] = $storeId;
            $where['device'] = $this->getType();
            $result = $this->where($where)->save($data);
        } else {
            $data['create_time'] = NOW_TIME;
            $result = $this->add($data);
            $tplId = $result;
        }
        if (false === $result) {
            return getReturn(CODE_ERROR);
        }
        D('StoreDecoration')->clearTplCache($storeId);
        return getReturn(CODE_SUCCESS, L('SAVE_SUCCESSFUL'), ['tpl_id' => $tplId]);
    }

    /**
     * 获取主模版
     * @param int $storeId
     * @return mixed
     * User: hjun
     * Date: 2018-09-25 04:30:03
     * Update: 2018-09-25 04:30:03
     * Version: 1.00
     */
    public function getMainTplContent($storeId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['is_main'] = 1;
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['field'] = 'tpl_content';
        $options['where'] = $where;
        $data = $this->selectRow($options);
        if (isset($data['tpl_content'])) {
            return jsonDecodeToArr($data['tpl_content']);
        }
        return false;
    }

    /**
     * 获取模版内容
     * @param int $storeId
     * @param int $tplId
     * @return array
     * User: hjun
     * Date: 2018-09-25 18:07:38
     * Update: 2018-09-25 18:07:38
     * Version: 1.00
     */
    public function getTplContent($storeId = 0, $tplId = 0)
    {
        $tpl = $this->getTpl($storeId, $tplId);
        $tplContent = jsonDecodeToArr($tpl['tpl_content']);
        return $tplContent;
    }

    /**
     * 获取模块对应的处理类
     * @param $module
     * @return BaseComponents|mixed
     * User: hjun
     * Date: 2018-09-25 17:47:24
     * Update: 2018-09-25 17:47:24
     * Version: 1.00
     */
    public function getModuleClass($module)
    {
        $storeId = $this->getStoreId();
        $memberId = $this->getMemberId();
        switch ($module['module_type']) {
            case self::MODULE_GOODS:
                $class = new GoodsModule($module, $storeId, $memberId);
                break;
            case self::MODULE_ADV:
                $class = new ImageAds($module, $storeId, $memberId);
                break;
            case self::MODULE_CUBE:
                $class = new CubeModule($module, $storeId, $memberId);
                break;
            case self::MODULE_BTN_NAV:
                $class = new BtnNaves($module, $storeId, $memberId);
                break;
            case self::MODULE_GOODS_GROUP:
                $class = new GoodsGroupModule($module, $storeId, $memberId);
                break;
            case self::MODULE_COUPON:
                $class = new CouponModule($module, $storeId, $memberId);
                break;
            case self::MODULE_LIMIT_TIME:
                $class = new LimitTimeModule($module, $storeId, $memberId);
                break;
            case self::MODULE_GROUP_BUYING:
                $class = new GroupBuyingModule($module, $storeId, $memberId);
                break;
            case self::MODULE_NEW_GOODS:
                $class = new NewGoodsModule($module, $storeId, $memberId);
                break;
            case self::MODULE_SEARCH:
                $class = new SearchModule($module, $storeId, $memberId);
                break;
            case self::MODULE_NOTICE:
                $class = new NoticeModule($module, $storeId, $memberId);
                break;
            case self::MODULE_AUXILIARY_LINE:
                $class = new AuxiliaryLineModule($module, $storeId, $memberId);
                break;
            case self::MODULE_AUXILIARY_DIV:
                $class = new AuxiliaryDivModule($module, $storeId, $memberId);
                break;
            case self::MODULE_CUSTOMER_MSG_MODULE:
                $class = new CustomerModule($module, $storeId, $memberId);
                break;
            case self::MODULE_NEWS:
                $class = new NewsModule($module, $storeId, $memberId);
                break;
            default:
                return false;
                break;
        }
        if (!$class) return false;
        return $class->setMemberId($this->getMemberId());
    }

    /**
     * 生成模版的HTML
     * @param array $tplContent
     * @return string
     * User: hjun
     * Date: 2018-09-25 17:47:17
     * Update: 2018-09-25 17:47:17
     * Version: 1.00
     */
    public function buildTplHtml($tplContent = [])
    {
        G('a');
        $modules = $tplContent['tpl_content'];
        $pageConfig = $tplContent['page_config'];
        $customerMsg = $tplContent['customer_msg'];
        if ($customerMsg['is_add'] == 1) {
            $customerMsg['module_type'] = self::MODULE_CUSTOMER_MSG_MODULE;
            array_unshift($modules, $customerMsg);
        }

        // 生成模块HTML
        $moduleHtml = '';
        foreach ($modules as $module) {
            $moduleClass = $this->getModuleClass($module);
            if ($moduleClass) {
                $moduleHtml .= $moduleClass->toHtml();
            }
        }

        // 生成整体HTML
        $tplClass = new Tpl($tplContent, $this->getStoreId(), $this->getMemberId());
        $html = $tplClass->setModulesHtml($moduleHtml)->setBgColor($pageConfig['page_color'])->toHtml();
        G('b');
        $time = G('a', 'b');
        logWrite("生成首页时间:{$time}");
        return $html;
    }
}