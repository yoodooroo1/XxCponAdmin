<?php

namespace Common\Util;

/**
 * 商城装修用的工具类
 * Class Decoration
 * @package Common\Util
 * User: hjun
 * Date: 2018-08-02 15:54:42
 * Update: 2018-08-02 15:54:42
 * Version: 1.00
 */
class Decoration extends Base
{
    const TYPE_WX = 'wx';
    const TYPE_APP = 'app';

    // 旧版跳转参数的字段名称你刚才
    const ACTION_DATA_ADV = 'murl';
    const ACTION_DATA_BUTTON = 'weburl';
    const ACTION_DATA_DEFAULT = 'action_data';

    const ACTION_SYSTEM = 'system';
    const ACTION_NO = 'no_action'; // 不跳转
    const ACTION_GOOD_DEAL = 'good_deal'; // 精划算
    const ACTION_GO_STORES = 'go_stores'; // 逛商铺
    const ACTION_MAP = 'map'; // 地图
    const ACTION_MY_FOOTPRINT = 'my_footprint'; // 我的足迹
    const ACTION_MALL_NOTICE_LIST = 'mall_notice_list'; // 市场公告
    const ACTION_SHAKE_PRIZE = 'shake_prize'; // 摇奖品
    const ACTION_POINTS_MALL = 'points_mall'; // 积分商城
    const ACTION_SERVICE_CENTER = 'service_center'; // 服务中心
    const ACTION_FIND_GOOD_STORE = 'find_good_store'; // 找好店
    const ACTION_BOUTIQUE_SHOPPING = 'boutique_shopping'; // 精品购
    const ACTION_DAY_SHOPPING = 'day_shopping'; // 每日购
    const ACTION_MY_COLLECTION = 'my_collection'; // 我的收藏
    const ACTION_MY_PRIZE_GIFT = 'my_prize_gift'; // 我的奖/礼品
    const ACTION_MY_COUPON = 'my_coupon'; // 我的优惠券
    const ACTION_COMMUNICATION = 'communication'; // 消息
    const ACTION_SIGN_IN = 'sign_in'; // 签到
    const ACTION_DIRECT_PAYMENT = 'direct_payment'; // 直接付款
    const ACTION_STORE_NOTICE_LIST = 'store_notice_list'; // 活动公告
    const ACTION_APPLY_FOR_AGENT = 'apply_for_agent'; // 申请代理
    const ACTION_SEARCH_GOODS = 'search_goods'; // 搜索商品
    const ACTION_MALL_CLASS_GOODS = 'mall_class_goods'; // 商城分类商品
    const ACTION_CHILD_CLASS_GOODS = 'child_class_goods'; // 单店分类商品
    const ACTION_WEB_URL = 'web_url'; // 自定义链接
    const ACTION_ONE_GOODS = 'one_goods'; // 单件商品
    const ACTION_ONE_UNION_STORE = 'one_union_store'; // 联盟商家
    const ACTION_ONE_NOTICE = 'one_notice'; // 单条公告
    const ACTION_ONE_NEWS = 'one_news'; // 单条资讯
    const ACTION_TAG_GOODS = 'tag_goods'; // 标签商品
    const ACTION_SPECIAL_OFFER = 'special_offer'; // 今日特价
    const ACTION_HOT_SALE_GOODS = 'hot_sale_goods'; // 热销商品
    const ACTION_MALL_SEARCH_GOODS = 'mall_search_goods'; // 商城搜索商品
    const ACTION_MALL_MAP = 'mall_map'; // 商城地图
    const ACTION_COUPONS_CENTER = 'coupons_center'; // 领券中心

    const SHOP_MODULE = 'shop_module'; // 首页按钮
    const CLASSIFY_MODULE = 'classify_module'; // 分类按钮
    const GUIDE_MODULE = 'guide_module'; // 发现按钮
    const CUSTOMER_SERVICE_MODULE = 'customer_service_module'; // 客服按钮
    const CART_MODULE = 'cart_module'; // 购物车按钮
    const USER_MODULE = 'setting_module'; // 个人中心

    protected $storeId;
    protected $memberId;
    protected $type;

    public function __construct($storeId = 0, $memberId = 0)
    {
        $this->storeId = $storeId;
        $this->memberId = $memberId;
        $this->type = self::TYPE_WX;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
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
     */
    public function setMemberId($memberId)
    {
        $this->memberId = $memberId;
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
     */
    public function setType($type)
    {
        if (in_array($type, [self::TYPE_WX, self::TYPE_APP])) {
            $this->type = $type;
        } else {
            $this->type = self::TYPE_WX;
        }
    }

    /**
     * 获取首页链接
     * @return string
     * User: hjun
     * Date: 2018-08-02 15:56:32
     * Update: 2018-08-02 15:56:32
     * Version: 1.00
     */
    public function getShopUrl()
    {
        return getStoreDomain($this->storeId) . "/index.php?c=Index&a=store&se={$this->storeId}&f={$this->memberId}";
    }

    /**
     * 获取分类页链接
     * @return string
     * User: hjun
     * Date: 2018-08-02 15:56:49
     * Update: 2018-08-02 15:56:49
     * Version: 1.00
     */
    public function getClassifyUrl()
    {
        $url = U('Classify/classify', ['se' => $this->storeId, 'f' => $this->memberId]);
        $url = str_replace('admin.php', 'index.php', $url);
        return getStoreDomain($this->storeId) . $url;
    }

    /**
     * 获取发现页链接
     * @return string
     * User: hjun
     * Date: 2018-08-02 15:59:16
     * Update: 2018-08-02 15:59:16
     * Version: 1.00
     */
    public function getGuideUrl()
    {
        $url = U('MallNews/index', ['se' => $this->storeId, 'f' => $this->memberId]);
        $url = str_replace('admin.php', 'index.php', $url);
        return getStoreDomain($this->storeId) . $url;
    }

    /**
     * 获取购物车链接
     * @return string
     * User: hjun
     * Date: 2018-08-02 15:59:54
     * Update: 2018-08-02 15:59:54
     * Version: 1.00
     */
    public function getCartUrl()
    {
        $url = U('Shop/shop', ['se' => $this->storeId, 'f' => $this->memberId]);
        $url = str_replace('admin.php', 'index.php', $url);
        return getStoreDomain($this->storeId) . $url;
    }

    /**
     * 获取个人中心链接
     * @return string
     * User: hjun
     * Date: 2018-08-02 16:00:32
     * Update: 2018-08-02 16:00:32
     * Version: 1.00
     */
    public function getUserUrl()
    {
        $url = U('User/user', ['se' => $this->storeId, 'f' => $this->memberId]);
        $url = str_replace('admin.php', 'index.php', $url);
        return getStoreDomain($this->storeId) . $url;
    }

    /**
     * 根据类型获取底部导航单个按钮
     * @param string $type
     * @return array
     * User: hjun
     * Date: 2018-08-03 17:12:46
     * Update: 2018-08-03 17:12:46
     * Version: 1.00
     */
    public function getBottomButtonByType($type = self::SHOP_MODULE)
    {
        $img = [
            self::SHOP_MODULE => '/Public/wap2/img/common/fHome.png',
            self::CLASSIFY_MODULE => '/Public/wap2/img/common/fType.png',
            self::GUIDE_MODULE => '/Public/wap2/img/common/fFind.png',
            self::CUSTOMER_SERVICE_MODULE => '/Public/wap2/img/common/fFind.png',
            self::CART_MODULE => '/Public/wap2/img/common/fCart.png',
            self::USER_MODULE => '/Public/wap2/img/common/fUser.png',
        ];
        $imgOn = [
            self::SHOP_MODULE => '/Public/wap2/img/common/fHome_on.png',
            self::CLASSIFY_MODULE => '/Public/wap2/img/common/fType_on.png',
            self::GUIDE_MODULE => '/Public/wap2/img/common/fFind_on.png',
            self::CUSTOMER_SERVICE_MODULE => '/Public/wap2/img/common/fFind_on.png',
            self::CART_MODULE => '/Public/wap2/img/common/fCart_on.png',
            self::USER_MODULE => '/Public/wap2/img/common/fUser_on.png',
        ];
        $name = [
            self::SHOP_MODULE => '首页',
            self::CLASSIFY_MODULE => '分类',
            self::GUIDE_MODULE => '发现',
            self::CUSTOMER_SERVICE_MODULE => '客服',
            self::CART_MODULE => '购物车',
            self::USER_MODULE => '个人中心',
        ];
        $action = [
            self::SHOP_MODULE => self::ACTION_WEB_URL,
            self::CLASSIFY_MODULE => self::ACTION_WEB_URL,
            self::GUIDE_MODULE => self::ACTION_WEB_URL,
            self::CUSTOMER_SERVICE_MODULE => self::ACTION_COMMUNICATION,
            self::CART_MODULE => self::ACTION_WEB_URL,
            self::USER_MODULE => self::ACTION_WEB_URL,
        ];
        $actionData = [
            self::SHOP_MODULE => $this->getShopUrl(),
            self::CLASSIFY_MODULE => $this->getClassifyUrl(),
            self::GUIDE_MODULE => $this->getGuideUrl(),
            self::CUSTOMER_SERVICE_MODULE => '',
            self::CART_MODULE => $this->getCartUrl(),
            self::USER_MODULE => $this->getUserUrl(),
        ];
        $button = [
            'label' => $type, // 前端用的选中值
            'normal_img' => $img[$type], // 普通图片
            'selected_img' => $imgOn[$type], // 高亮图片
            'name' => $name[$type], // 文字
            'action' => $action[$type], // 跳转方式
            'action_data' => $actionData[$type], // 跳转参数
            'is_show' => 1, // 是否显示 0-否 1-是
            'ctrl' => 1, // 是否有这个按钮的权限 0-否 1-是
        ];
        return $button;
    }

    /**
     * 获取初始的底部按钮数组
     * @return array
     * User: hjun
     * Date: 2018-08-03 16:46:51
     * Update: 2018-08-03 16:46:51
     * Version: 1.00
     */
    public function getBottomButtons()
    {
        $type = $this->getType();
        // 初始化5个按钮
        $shopButton = $this->getBottomButtonByType(self::SHOP_MODULE);
        $classifyButton = $this->getBottomButtonByType(self::CLASSIFY_MODULE);
        $guideButton = $this->getBottomButtonByType(self::GUIDE_MODULE);
        $serviceButton = $this->getBottomButtonByType(self::CUSTOMER_SERVICE_MODULE);
        $cartButton = $this->getBottomButtonByType(self::CART_MODULE);
        $userButton = $this->getBottomButtonByType(self::USER_MODULE);
        $tempButtons = [
            self::SHOP_MODULE => &$shopButton,
            self::CLASSIFY_MODULE => &$classifyButton,
            self::GUIDE_MODULE => &$guideButton,
            self::CUSTOMER_SERVICE_MODULE => &$serviceButton,
            self::CART_MODULE => &$cartButton,
            self::USER_MODULE => &$userButton
        ];

        $model = D('Store');
        // 获取按钮开关
        $storeInfo = $model->getStoreInfo($this->storeId)['data'];
        // 获取发现权限
        $storeGrant = $model->getStoreGrantInfo($this->storeId)['data'];
        $findCtrl = $storeGrant['find_ctrl'] == 1;

        // 设置按钮
        foreach ($tempButtons as $module => $button) {
            $tempButtons[$module]['is_show'] = $storeInfo[$module] == 1 ? 1 : 0;
            // 如果没有发现权限 要关闭
            if (!$findCtrl && $module === self::GUIDE_MODULE) {
                $tempButtons[$module]['ctrl'] = 0;
                $tempButtons[$module]['is_show'] = 0;
            }
        }
        if ($type === self::TYPE_WX) {
            // 如果是微信 要关闭客服的显示
            $serviceButton['ctrl'] = 0;
            $serviceButton['is_show'] = 0;
        } else if ($type === self::TYPE_APP) {
            // 如果是APP 如果5个都开启 客服需要关闭
            $isOtherFiveShow = !($shopButton['is_show'] == 0 ||
                $classifyButton['is_show'] == 0 ||
                $guideButton['is_show'] == 0 ||
                $cartButton['is_show'] == 0 ||
                $userButton['is_show'] == 0);
            if ($isOtherFiveShow) {
                $serviceButton['is_show'] = 0;
            }
        }
        $buttons = [$shopButton, $guideButton, $classifyButton, $serviceButton, $cartButton, $userButton];
        return $buttons;
    }

    /**
     * 获取跳转方式
     * @return array
     * User: hjun
     * Date: 2018-08-16 10:43:17
     * Update: 2018-08-16 10:43:17
     * Version: 1.00
     */
    public function getLinkTypeOption()
    {
        $storeId = $this->getStoreId();
        $clientType = $this->getType();
        // 获取商家信息
        $modelStore = D('Store');
        $storeInfo = $modelStore->getStoreInfo($storeId)['data'];
        $channelId = $storeInfo['channel_id'];
        $storeGrade = $storeInfo['store_grade'];
        $storeType = $storeInfo['store_type'];
        // 获取单独跳转
        $model = D('LinkTypeAuth');
        $auth = $model->getStoreIdAuth($storeId, $clientType)['data'];
        // 没有配置单独跳转 则获取通用跳转
        if ($auth === NULL) {
            // 获取通用跳转 通用权限用商家类型查询
            $typeAuth = $model->getStoreTypeAuth($storeType, $clientType)['data'];
            // 获取套餐跳转
            $gradeAuth = $model->getStoreGradeAuth($channelId, $storeGrade, $clientType)['data'];
            // 合并两个跳转权限
            $auth = $typeAuth . ',' . $gradeAuth;
            $auth = explode(',', $auth);
            foreach ($auth as $key => $value) {
                if (empty($value)) unset($auth[$key]);
            }
            $auth = implode(',', $auth);
        }

        // 查询出跳转方式
        $model = D('LinkType');
        $linkOption = $model->getLinkTypeListByAuth($auth)['data'];
        // 跳转方式再组装
        $system = [];
        $system['name'] = '系统功能';
        $system['type'] = 'system';
        $system['is_system'] = 1;
        $system['input_type'] = 'select';
        $system['tips'] = '请选择系统功能';
        $system['sort'] = 0;
        $system['url'] = '';
        $system['option'] = [['name' => '请选择系统功能', 'value' => '']];
        foreach ($linkOption as $key => $value) {
            // 系统功能重组
            if ($value['is_system'] == 1) {
                $value['value'] = $value['type'];
                $system['option'][] = $value;
                unset($linkOption[$key]);
            }
            // 特殊跳转需要查询一些参数
            switch ($value['type']) {
                case 'mall_class_goods':
                    // 查找商城分类
                    $model = D('MallGoodClass');
                    $list = $model->getFirstLevelClass($channelId, 1, 0, [], 4)['data'];
                    $item = [];
                    $item['name'] = $value['tips'];
                    $item['value'] = '';
                    $linkOption[$key]['option'][] = $item;
                    foreach ($list as $k => $val) {
                        $item = [];
                        $item['name'] = $val['class_name'];
                        $item['value'] = $val['class_p_id'];
                        $linkOption[$key]['option'][] = $item;
                    }
                    break;
                case 'child_class_goods':
                    // 查找商品分类
                    $model = D('GoodsClass');
                    $list = $model->getFirstLevelClass($storeId, 1, 0, [], 4)['data'];
                    $item = [];
                    $item['name'] = $value['tips'];
                    $item['value'] = '';
                    $linkOption[$key]['option'][] = $item;
                    foreach ($list as $k => $val) {
                        $item = [];
                        $item['name'] = $val['class_name'];
                        $item['value'] = $val['class_p_id'];
                        $linkOption[$key]['option'][] = $item;
                    }
                    break;
                case 'one_union_store':
                    // 查找联盟商家
                    $model = D('StoreUnion');
                    $list = $model->getUnionStoreList($storeId)['data'];
                    $item = [];
                    $item['name'] = $value['tips'];
                    $item['value'] = '';
                    $linkOption[$key]['option'][] = $item;
                    foreach ($list as $k => $val) {
                        $item = [];
                        $item['name'] = $val['store_name'];
                        $item['value'] = $val['member_name'];
                        $linkOption[$key]['option'][] = $item;
                    }
                    // 没有联盟商家就隐藏
                    if (empty($list)) unset($linkOption[$key]);
                    break;
                case 'tag_goods':
                    // 查找标签商品
                    $model = D('GoodsTag');
                    $where = [];
                    $where['tag_status'] = ['in', [1, 2]];
                    $list = $model->getGoodsTag($storeId, $channelId, $where)['data'];
                    $item = [];
                    $item['name'] = $value['tips'];
                    $item['value'] = '';
                    $linkOption[$key]['option'][] = $item;
                    foreach ($list as $k => $val) {
                        $item = [];
                        $item['name'] = $val['tag_name'];
                        $item['value'] = $val['tag_id'];
                        $linkOption[$key]['option'][] = $item;
                    }
                    break;
                default:
                    break;
            }

        }
        $linkOption[] = $system;
        $linkOption = array_sort($linkOption, 'sort', 'ASC');
        return $linkOption;
    }
}