<?php

defined('InXunXin') or define('InXunXin', true);
defined('TIMESTAMP') or define('TIMESTAMP', $_SERVER['REQUEST_TIME']);
defined('SERVICR_KEY') or define("SERVICR_KEY", "xunxin8988998");
defined('EPSILON') or define('EPSILON', 0.01);

// hjun 定义客户端类型
define('CLIENT_WECHAT', 'wap');
define('CLIENT_H5', 'web');
define('CLIENT_MINI', 'mini');
define('CLIENT_ANDROID', 'android');
define('CLIENT_IOS', 'ios');
define('CLIENT_PC', 'pc');

// hjun　2018-03-31 23:54:32 定义状态码
define('CODE_SUCCESS', 200); // 请求成功
define('CODE_LOGIN_SUCCESS', 201); // 登录成功后跳转
define('CODE_REDIRECT', 301); // 请求后直接重定向
define('CODE_ERROR', 406); // 请求失败
define('CODE_LOGOUT', -999); // 登录过期 提示过期后会重定向
define('CODE_NOT_FOUND', 404); // 不存在

// 定义系统标签
define('TAG_HOT', 'is_hot');
define('TAG_PROMOTE', 'is_promote');
define('TAG_QIANGGOU', 'is_qianggou');
define('TAG_SPECIAL', 'is_special');
define('TAG_ABROAD', 'is_abroad');
define('TAG_BOOKING', 'is_booking');

// hjun 定义消息类型
define('ORDER_MESSAGE', 6);

define('DELETED', 1);
define('NOT_DELETE', 0);

// 证书等存放目录
defined('PUSH_CERT_PATH') or define('PUSH_CERT_PATH', VENDOR_PATH . 'Cert/pushck/'); // 定义苹果推送证书根目录
defined('WX_CERT_PATH') or define('WX_CERT_PATH', VENDOR_PATH . 'Cert/wx'); // 定义证书的根目录
defined('ALI_KEY_PATH') or define('ALI_KEY_PATH', VENDOR_PATH . 'Cert/ali/'); // 定义ALI密钥的根目录
defined('TLSSIG_KEY_PATH') or define('TLSSIG_KEY_PATH', VENDOR_PATH . 'Cert/certificate/'); // 定义腾讯云通信的密钥目录

// 支付回调通知路径 {$域名}{$PATH}{$php文件名}, 这里定义{$PATH}
defined('WX_NOTIFY_PATH') or define('WX_NOTIFY_PATH', '/payment/wxpay/');
defined('ALI_NOTIFY_PATH') or define('ALI_NOTIFY_PATH', '/payment/alipay/');

// 微信支付回调通知的上传根路径
defined('WX_NOTIFY_UPLOAD_ROOT') or define('WX_NOTIFY_UPLOAD_ROOT', realpath(THINK_PATH . '../xxapi/') . '/');

// region 操作标识

// region 通用操作
define('ACTION_ADD', 0); // 新增
define('ACTION_MODIFY', 1); // 修改
define('ACTION_DELETE', 2); // 删除
// endregion

// region 配送范围
define('DELIVERY_AREA_ACTION_SAVE_DATA', 3); // 保存配送设置
// endregion

// endregion

// 定义外部文件的版本号
defined('EXTRA_VERSION') or define('EXTRA_VERSION', '3.36');
define('PUSH_LOGS_PATH', RUNTIME_PATH.'/Logs/Push/'.date('y_m_d').'.log');
define('CCB_LOGS_PATH', RUNTIME_PATH.'/Logs/CCB/'.date('y_m_d').'.log');
define('CASHIER_LGOIN_LOGS_PATH', RUNTIME_PATH.'/Logs/cash_qrlogin/'.date('y_m_d').'.log');
define('BALANCE_LOGS_PATH', RUNTIME_PATH.'/Logs/balance/'.date('y_m_d').'.log');
define('REFUND_GOODS_PATH', RUNTIME_PATH.'/Logs/refund_goods/'.date('y_m_d').'.log');
define('HP_LOGS_PATH', RUNTIME_PATH.'/Logs/hp/'.date('y_m_d').'.log');
define('TEST_PATH', RUNTIME_PATH.'/Logs/test/'.date('y_m_d').'.log');
define('Dock_PATH', RUNTIME_PATH.'/Logs/dock/'.date('y_m_d').'.log');
$SRC = [
    'VUE' => [
        'common' => '/Public/common/js/plug-in/vue-2.x/vue.min.js?v=' . EXTRA_VERSION,
        'dev' => '/Public/common/js/plug-in/vue-2.x/vue.js?v=' . EXTRA_VERSION,
        'home_dev' => '/Public/common/js/plug-in/vue-2.x/vue.js?v=' . EXTRA_VERSION,
        'home_common' => '/Public/common/js/plug-in/vue-2.x/vue.js?v=' . EXTRA_VERSION,
    ],
    'APP_UTIL' => [
        'common' => '/Public/common/js/plug-in/vjdutil-1.0/AppUtil.js?v=' . EXTRA_VERSION,
        'dev' => '/Public/common/js/plug-in/vjdutil-1.0/AppUtil-dev.js?v=' . EXTRA_VERSION,
        'home_dev' => '/Public/common/js/plug-in/vjdutil-1.0/AppUtil-dev.js?v=' . EXTRA_VERSION,
        'home_common' => '/Public/common/js/plug-in/vjdutil-1.0/AppUtil-dev.js?v=' . EXTRA_VERSION,
    ]
];
foreach ($SRC as $const => $value) {
    $model = empty($value[MODE]) ? 'common' : MODE;
    if (!defined($const) && !empty($value[$model])) {
        define($const, $value[$model]);
    }
}

// =================================================    AdminCommon    =================================================
// =================================================    AdminCommon    =================================================
// =================================================    AdminCommon    =================================================
// =================================================    AdminCommon    ================================================
/**
 * 转换无动作
 * @param array $item
 * @param string $actionField
 * @return void
 * User: hjun
 * Date: 2018-09-12 17:51:05
 * Update: 2018-09-12 17:51:05
 * Version: 1.00
 */
function transEmptyAction(&$item = [], $actionField = \Common\Util\Decoration::ACTION_DATA_DEFAULT)
{
    if (empty($item[$actionField])) {
        $item[$actionField] = \Common\Util\Decoration::ACTION_NO;
    }
}

/**
 * 转换系统功能action
 * @param array $item
 * @param string $actionDataField
 * @param string $actionField
 * @return void
 * User: hjun
 * Date: 2018-09-12 17:35:04
 * Update: 2018-09-12 17:35:04
 * Version: 1.00
 */
function transSystemAction(&$item = [], $actionDataField = 'action_data', $actionField = 'action')
{
    if (isInApi() || isInWap()) {
        return null;
    }
    $model = D('LinkType');
    $systemAction = $model->getSystemLinkTypeActionArr()['data'];
    if (in_array($item[$actionField], $systemAction)) {
        $item[$actionDataField] = $item[$actionField];
        $item[$actionField] = \Common\Util\Decoration::ACTION_SYSTEM;
    }
}

/**
 * 反转换系统功能action
 * @param array $item
 * @param string $actionDataField
 * @param string $actionField
 * @return void
 * User: hjun
 * Date: 2018-09-19 16:26:53
 * Update: 2018-09-19 16:26:53
 * Version: 1.00
 */
function revertSystemAction(&$item = [], $actionDataField = 'action_data', $actionField = 'action')
{
    if ($item[$actionField] === \Common\Util\Decoration::ACTION_SYSTEM) {
        $item[$actionField] = $item[$actionDataField];
        $item[$actionDataField] = '';
    }
}

/**
 * 判断是否有主题格子权限
 * @param int $storeId
 * @return boolean
 * User: hjun
 * Date: 2018-09-12 11:39:20
 * Update: 2018-09-12 11:39:20
 * Version: 1.00
 */
function themeGirdCtrl($storeId = 0)
{
    $model = D('Store');
    $storeInfo = $model->getStoreInfo($storeId)['data'];
    $grant = $model->getStoreGrantInfo($storeId)['data'];
    return $grant['theme_gird_ctrl'] == 1 || in_array($storeInfo['store_type'], [0, 2, 4]);
}

/**
 * 获取广告数量
 * @param int $storeId
 * @return int
 * User: hjun
 * Date: 2018-09-12 11:42:16
 * Update: 2018-09-12 11:42:16
 * Version: 1.00
 */
function getAdvNum($storeId = 0)
{
    $model = D('Store');
    $storeInfo = $model->getStoreInfo($storeId)['data'];
    $grant = $model->getStoreGrantInfo($storeId)['data'];
    $advNum = $grant['advertise_num'] + $storeInfo['extra_advertisenum'];
    return $advNum ?: 0;
}

/**
 * 获取默认数据
 * @param string $relativePath
 * @param string $ext
 * @return array
 * User: hjun
 * Date: 2018-09-12 09:30:31
 * Update: 2018-09-12 09:30:31
 * Version: 1.00
 */
function getDefaultData($relativePath = '', $ext = 'json')
{
    $rootPath = realpath(COMMON_PATH . '/Default/') . '/';
    $path = "{$rootPath}$relativePath.{$ext}";
    $data = file_get_contents($path);
    if ($ext === 'json') {
        $data = jsonDecodeToArr($data);
    } elseif ($ext === 'php') {
        $data = unserialize($data);
    }
    return $data;
}

/**
 * 检查验证码
 * @param $code
 * @param string $id
 * @return boolean
 * User: hjun
 * Date: 2018-06-18 16:37:17
 * Update: 2018-06-18 16:37:17
 * Version: 1.00
 */
function check_verify($code, $id = '')
{
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

/**
 * 将数字乘以100
 * @param int $number
 * @return double
 * User: hjun
 * Date: 2018-06-18 16:37:53
 * Update: 2018-06-18 16:37:53
 * Version: 1.00
 */
function numberX100($number = 0)
{
    $number = (double)$number;
    return round($number * 100, 2);
}

/**
 * 将数字除以100
 * @param int $number
 * @return double
 * User: hjun
 * Date: 2018-06-18 16:38:22
 * Update: 2018-06-18 16:38:22
 * Version: 1.00
 */
function numberDivide100($number = 0)
{
    $number = (double)$number;
    return round($number / 100, 2);
}

/**
 * 循环删除目录和文件函数
 * @param string $dirName 路径
 * @param boolean $bFlag 是否删除目录
 * @return void
 */
function del_dir_file($dirName, $bFlag = false)
{
    if ($handle = @opendir("$dirName")) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..") {
                if (is_dir("$dirName/$item")) {
                    del_dir_file("$dirName/$item", $bFlag);
                } else {
                    unlink("$dirName/$item");
                }
            }
        }
        closedir($handle);
        if ($bFlag) {
            rmdir($dirName);
        }

    }
}

/**
 * 删除远程文件
 * @param string $remoteUrl
 * @return string
 * User: hjun
 * Date: 2019-04-22 15:48:07
 * Update: 2019-04-22 15:48:07
 * Version: 1.00
 */
function delRemoteFile($remoteUrl = '')
{
    $path = base64_encode($remoteUrl);
    $sign = md5("{$path}:vjd8988998");
    $url = "http://file.duinin.com/upload.php?action=delete&file_path={$path}&sign={$sign}";
    return file_get_contents($url);
}

function getSellerNum($storeId = 0)
{
    $model = D('Store');
    $storeInfo = $model->getStoreInfo($storeId)['data'];
    $grant = $model->getStoreGrantInfo($storeId)['data'];
    $num = $grant['seller_num'] + $storeInfo['extra_sellernum'];
    return $num ?: 0;
}

/**
 * 记录sessionId
 * @param string $sessionId
 * User: hjun
 * Date: 2018-08-28 12:49:54
 * Update: 2018-08-28 12:49:54
 * Version: 1.00
 */
function logSessionId($sessionId = '')
{
    if (empty($sessionId)) {
        $sessionId = session_id();
    }
    logWrite("当前session_id:{$sessionId}");
}

/**
 * 尽量获取真实的IP地址
 * @return string
 * User: hjun
 * Date: 2018-08-28 15:13:08
 * Update: 2018-08-28 15:13:08
 * Version: 1.00
 */
function getTrueClientIp()
{
    return get_client_ip(0, true);
}
// =================================================    AdminCommon    =================================================
// =================================================    AdminCommon    =================================================
// =================================================    AdminCommon    =================================================
// =================================================    AdminCommon    =================================================


// ====================================================    Admin    ====================================================
// ====================================================    Admin    ====================================================
// ====================================================    Admin    ====================================================
// ====================================================    Admin    ====================================================
/**
 * json序列化
 * @param $param
 * @return string
 * User: hjun
 * Date: 2018-08-22 15:05:55
 * Update: 2018-08-22 15:05:55
 * Version: 1.00
 */
function jsonEncode($param)
{
    if (empty($param)) return '';
    $data = json_encode($param, 256);
    if ($data === 'null' || $data === '[]' || $data === '{}') {
        return '';
    }
    return $data;
}

/**
 * TODO 可以删除 需要重构调用的地方
 * 返回数组的维度
 * @param array $array
 * @return mixed 维度
 */
function getArrayLevel($array)
{
    if (!is_array($array)) return 0;
    $max_depth = 1;
    foreach ($array as $value) {
        if (is_array($value)) {
            $depth = getArrayLevel($value) + 1;

            if ($depth > $max_depth) {
                $max_depth = $depth;
            }
        }
    }
    return $max_depth;
}

/**
 * TODO 可以删除 需要重构调用的地方
 * 日志记录，一般用于记录数据库操作的出错信息
 * @param \Think\Model $model
 * @param string $type 日志类型
 * @param string $method 出错的方法名
 * @param int $line 出错的行号
 */
function Lg(&$model, $method = "", $line = 0, $type = "ERR")
{
    $model_error = $model->getError(); // 模型错误信息
    $db_error = $model->getDbError(); // 数据库错误信息
    $sql = $model->_sql(); // 最后执行的sql语句
    $log =
        "\n=======================$type Start=======================" .
        "\n\t模块：" . MODULE_NAME .
        "\n\t控制器：" . CONTROLLER_NAME .
        "\n\t操作：" . ACTION_NAME .
        "\n\tURL：" . __SELF__ .
        "\n\t错误位置：" . $method . "  LINE：" . $line .
        "\n\t模型错误信息：" . $model_error .
        "\n\t数据库错误信息：：" . $db_error .
        "\n\t执行的SQL语句：" . $sql .
        "\n=======================$type End=======================";
    logWrite($log, 'ERR');
}

/**
 * TODO 可以移到Admin的function.php
 * Admin记录行为日志
 * @param $member_id
 * @param $store_id
 * @param $channel_id
 * @return void
 * User: hjun
 * Date: 2018-06-18 16:42:50
 * Update: 2018-06-18 16:42:50
 * Version: 1.00
 */
function trafficIndex($member_id, $store_id, $channel_id)
{
    $url = getPageURL();
    if (strtolower(ACTION_NAME) !== 'check_order' && !empty($member_id) && !empty($store_id)) {
        $data = array();
        $data['even_type'] = 'click';
        if (checkMobile()) {
            $data['terminal_type'] = 'store_wap';
        } else {
            $data['terminal_type'] = 'store_pc';
        }
        $data['ip'] = getTrueClientIp();
        $data['user_id'] = empty($member_id) ? 0 : $member_id;
        $data['store_id'] = empty($store_id) ? 0 : $store_id;
        $data['page_flag'] = empty($url) ? '' : $url;
        $data['channel_id'] = empty($channel_id) ? 0 : $channel_id;
        // hjun 2017-08-31 10:43:22 修改
        $data['addtime'] = NOW_TIME;
        $data['create_time'] = strtotime(date('Y-m-d', $data['addtime']));
        M('data_record')->add($data);
    }
}

/**
 * 获取当前页面的跳转链接
 * @return string
 * User: hjun
 * Date: 2018-06-18 16:43:46
 * Update: 2018-06-18 16:43:46
 * Version: 1.00
 */
function getPageURL()
{
    $pageURL = 'http';
    if (I('server.HTTPS') == "on") {
        $pageURL .= "s";
    }
    $pageURL .= "://";

    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}

/**
 * TODO 可以删除
 * 导出excel
 * @param string $strTable 表格内容
 * @param string $filename 文件名
 */
function downloadExcel($strTable, $filename)
{
    header("Content-type: application/vnd.ms-excel");
    header("Content-Type: application/force-download");
    header("Content-Disposition: attachment; filename=" . $filename . "_" . date('Y-m-d') . ".xls");
    header('Expires:0');
    header('Pragma:public');
    echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . $strTable . '</html>';
}

/**
 * 生成0~1随机小数
 * @param Int $min
 * @param Int $max
 * @return Float
 */
function randFloat($min = 0, $max = 1)
{
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

/**
 * TODO 可以移到Admin模块
 * 记录后台操作日志
 * @param string $type
 * @param string $desc
 * User: hj
 * Date: 2017-09-07 09:39:06
 */
function addAdminLog($type = '', $desc = '')
{
    $data = array();
    $data['admin_name'] = session('member_name');
    $data['admin_id'] = session('member_id');
    $data['content'] = $desc;
    $data['ip'] = getTrueClientIp();
    $data['type'] = $type;
    $data['createtime'] = time();
    $data['status'] = 1;
    $data['control'] = strtolower(CONTROLLER_NAME);
    $data['act'] = strtolower(ACTION_NAME);
    $data['store_id'] = session('store_id') ?: 0;
    $param = empty($_POST) ? json_decode(file_get_contents("php://input"), 1) : $_POST;
    $param = empty($param) ? '' : json_encode($param, JSON_UNESCAPED_UNICODE);
    $data['param'] = $param;
    M('admin_log')->add($data);
}

/**
 * TODO 移到Admin 或者针对Super做兼容
 * 获取所有菜单
 * name 菜单名称
 * icon 一级菜单图标
 * sub_menu 二级菜单
 * control 控制器名
 * act 操作名
 * ctrl_type 权限名称 有这个属性的菜单 是由权限控制的菜单
 * @return array
 * User: hj
 * Date: 2017-09-12 22:47:46
 */
function getAllMenu()
{
    if (strtolower(MODULE_NAME) === 'super') {
        return getSuperMenu();
    }
    $menuEXCode = ['name' => L('XNWP')/*虚拟物品*/, 'icon' => 'fa fa-line-chart', 'ctrl_type' => 'present_card_ctrl', 'sub_menu' => [
        ['name' => L('WPMB')/*物品模板*/, 'act' => 'virtual_goods_template', 'control' => 'ExchangeCode'],
        ['name' => L('WHXXNK')/*未核销虚拟卡*/, 'act' => 'unexchange_list', 'control' => 'ExchangeCode'],
        ['name' => L('YHXXNK')/*已核销虚拟卡*/, 'act' => 'exchanged_list', 'control' => 'ExchangeCode'],
    ]];
    return [
        'menu1' => ['name' => L('SELLER_MANAGE') /*商家管理*/, 'icon' => 'amicon-store', 'sub_menu' => [
            ['name' => L('SELLER_INFO') /*商家信息*/, 'act' => 'store_info', 'control' => 'System'],
            ['name' => L('MALL_DECORATE') /*商城装修*/, 'act' => 'store_decoration_config', 'control' => 'System'],
            ['name' => L('WX_MALL_DECORATE') /*商城装修*/, 'act' => 'weChat', 'control' => 'Decoration'],
            ['name' => L('APP_MALL_DECORATE') /*商城装修*/, 'act' => 'app', 'control' => 'Decoration'],
            ['name' => L('PAYMENT_SET') /*支付配置*/, 'act' => 'payment_config', 'control' => 'System', 'ctrl_type' => 'pay_ctrl'],
            ['name' => '会员设置' /*会员设置*/, 'act' => 'index', 'control' => 'MemberSet'],
            ['name' => L('ORDER_SET') /*订单设置*/, 'act' => 'order_config', 'control' => 'System'],
            // ['name' => L('CREDIT_SET') /*积分设置*/, 'act' => 'creditConfig', 'control' => 'System'],
            ['name' => L('DISTRIBUTION_SET') /*分销设置*/, 'act' => 'distributionConfig', 'control' => 'System', 'ctrl_type' => 'three_ctrl'],
            ['name' => L('COMMON_SET') /*通用设置*/, 'act' => 'common_config', 'control' => 'System'],
            // ['name' => '微信配置', 'act' => 'wechat_config', 'control' => 'System', 'ctrl_type' => 'wx_menu_ctrl'],
            ['name' => L('WX_GZH') /*微信公众号*/, 'act' => 'wechat_subscription', 'control' => 'System', 'ctrl_type' => 'wx_menu_ctrl'],
            ['name' => L('PICKUP_PSONG') /*自提配送点*/, 'act' => 'pickList', 'control' => 'System', 'ctrl_type' => 'pickup_ctrl'],
            ['name' => L('CUSSERVER_SET') /*客服配置*/, 'act' => 'customer_service_config', 'control' => 'System'],
            ['name' => L('SEARCH_SET') /*搜索设置*/, 'act' => 'searchSet', 'control' => 'Search'],
            ['name' => L('ZNYJ') /*智能硬件*/, 'act' => 'sharkConfig', 'control' => 'System'],
            ['name' => L('DYYSZ') /*多语言设置*/, 'act' => 'langSet', 'control' => 'Lang'],
//            ['name' => L('PSSZ') /*配送设置*/, 'act' => 'freightSet', 'control' => 'FreightTpl'],
//            ['name' => '支付宝退款', 'act' => 'al_refund', 'control' => 'Store'],
            ['name' => L('SZTGEWM') /*设置推广二维码*/, 'act' => 'qrcode_config', 'control' => 'Store', 'ctrl_type' => 'b_code_ctrl'],
            ['name' => L('PSSJSZ') /*配送时间设置*/, 'act' => 'delivery_time', 'control' => 'Store'],
            ['name' => L('PSFWSZ') /*配送范围设置*/, 'act' => 'getData', 'control' => 'DeliveryArea'],
        ]],
        'menu2' => ['name' => L('STORE_MANAGE') /*店铺管理*/, 'icon' => 'amicon-store', 'sub_menu' => [
            ['name' => L('DPFL') /*店铺分类*/, 'act' => 'typeList', 'control' => 'StoreType'],
            ['name' => L('XZFL') /*新增分类*/, 'act' => 'typeInfo', 'control' => 'StoreType'],
            ['name' => L('STORE_LIST') /*店铺列表*/, 'act' => 'business_list', 'control' => 'Business'],
            ['name' => L('SJKH') /*商家开户*/, 'act' => 'business_info', 'control' => 'Business'],
//            ['name' => '商家推荐', 'act' => 'brand_list', 'control' => 'Store'],
//            ['name' => '新增店铺', 'act' => 'business_info', 'control' => 'Business'], 暂时隐藏
//            ['name' => '平台注册', 'act' => 'platform_register', 'control' => 'Store'],
//            ['name' => '商家列表', 'act' => 'flag_storelist', 'control' => 'Store'],
//            ['name' => '商家开户', 'act' => 'flag_openstore', 'control' => 'Store'],
//            ['name' => '店铺提现', 'act' => 'flag_openstore', 'control' => 'Store'],
        ]],
        'menu3' => ['name' => L('GOODS_MANAGE') /*商品管理*/, 'icon' => 'amicon-goods', 'sub_menu' => [
            ['name' => L('GOODS_CLASSIFY') /*商品分类*/, 'act' => 'goodsClassList', 'control' => 'GoodsClass'],
            ['name' => L('GOODS_CLASSIFY') /*商品分类*/, 'act' => 'mallClassList', 'control' => 'Mallclass'],
            ['name' => L('GOODS_LIST') /*商品列表*/, 'act' => 'goods_list', 'control' => 'Goods'],
            ['name' => L('ADD_GOODS') /*添加商品*/, 'act' => 'goods_add', 'control' => 'Goods'],
            ['name' => L('PLSCSP') /*批量上传商品*/, 'act' => 'batch_goods_upload', 'control' => 'Goods'],
            ['name' => L('TGSP') /*团购商品*/, 'act' => 'groupGoodsList', 'control' => 'GroupBuying', 'ctrl_type' => 'group_buy_ctrl'],
//            ['name' => L('CXSP') /*促销商品*/, 'act' => 'goods_sale_list', 'control' => 'Goods'],
//            ['name' => '下架商品', 'act' => 'goods_down_list', 'control' => 'Goods'],
//            ['name' => '商品回收站', 'act' => 'goods_recycle', 'control' => 'Goods'],
            ['name' => L('GOODS_TAG') /*商品标签*/, 'act' => 'goodsTag', 'control' => 'Goods'],
            ['name' => L('SPCK') /*商品仓库*/, 'act' => 'depotList', 'control' => 'GoodsDepot'],
            ['name' => L('SPFLMB') /*商品分类模版*/, 'act' => 'classifyTemplate', 'control' => 'GoodsClass'],
            ['name' => L('PJGL')/*评价管理*/, 'act' => 'commentList', 'control' => 'GoodsComment'],
            ['name' => L('GOODS_PARAM')/*商品参数*/, 'act' => 'paramTplList', 'control' => 'GoodsParam'],
        ]],

        'menu18' => ['name' => L('ACTIVITY_GOODS') /*活动商品*/, 'icon' => 'amicon-goods', 'sub_menu' => [
            ['name' => L('FIND_GOOD_STORE') /*找好店*/, 'act' => 'goodShopList', 'control' => 'ActivityGoods'],
            ['name' => L('CHOICE_GOODS_2') /*精划算*/, 'act' => 'goodDeal', 'control' => 'ActivityGoods'],
            ['name' => L('QG_KILLS_TIME_3') /*限时购*/, 'act' => 'timeLimitBuy', 'control' => 'ActivityGoods'],
            ['name' => L('CHOICE_GOODS_4') /*精品购*/, 'act' => 'boutiqueBuy', 'control' => 'ActivityGoods'],
            ['name' => L('TODAY_DISCOUNT_GOODS_2') /*每日购*/, 'act' => 'dayBuy', 'control' => 'ActivityGoods'],
            ['name' => L('TODAY_DISCOUNT_GOODS_1') /*今日特价*/, 'act' => 'dayDiscount', 'control' => 'ActivityGoods'],
            ['name' => L('HOT_GOODS_3') /*热销商品*/, 'act' => 'hotSale', 'control' => 'ActivityGoods'],
        ]],
        'menu4' => ['name' => L('BRAND_MANAGE') /*品牌管理*/, 'icon' => 'fa fa-diamond', 'sub_menu' => [
            ['name' => L('BRAND_LIST') /*品牌列表*/, 'act' => 'brand_list', 'control' => 'Mallbrand'],
            ['name' => L('ADD_BRAND') /*新增品牌*/, 'act' => 'brand_info', 'control' => 'Mallbrand'],
        ]],
        'menu5' => ['name' => L('ORDER_MANAGE') /*订单管理*/, 'icon' => 'amicon-order', 'sub_menu' => [
            ['name' => L('ORDER_LIST') /*所有订单*/, 'act' => 'order_list2', 'control' => 'Order'],
            ['name' => L('FACE_PAY_ORDER') /*直付记录*/, 'act' => 'paymentList', 'control' => 'Balance'],
            ['name' => L('DCGSP') /*待采购商品*/, 'act' => 'sendGoods', 'control' => 'DataCalculation'],
//            ['name' => '分享砍价订单', 'act' => 'order_list', 'control' => 'Sharediscount'],
//            ['name' => '分享返佣订单', 'act' => 'order_list', 'control' => 'Sharediscount'],
        ]],
        ['name' => L('SYJGL')/*收银机管理*/, 'icon' => 'amicon-cashier', 'ctrl_type' => 'cash_ctrl', 'sub_menu' => [
            ['name' => L('DNDD')/*店内订单*/, 'act' => 'inStoreOrder', 'control' => 'Cashier'],
            ['name' => L('ZFLS')/*支付流水*/, 'act' => 'paymentFlow', 'control' => 'Cashier'],
            ['name' => L('RJXQ')/*日结详情*/, 'act' => 'dailyDetails', 'control' => 'Cashier'],
            ['name' => L('KXGG')/*客显广告*/, 'act' => 'guestAd', 'control' => 'Cashier'],
            ['name' => L('SYJLB')/*收银机列表*/, 'act' => 'cashRegisterList', 'control' => 'Cashier'],
            ['name' => L('GDGL')/*挂单管理*/, 'act' => 'pendingOrder', 'control' => 'Cashier'],
            ['name' => L('PAYMENT_SET')/*支付配置*/, 'act' => 'payConfig', 'control' => 'Cashier'],
            ['name' => '打印机管理', 'act' => 'goodsPrintDevice', 'control' => 'Goods'],
        ]],
        'menu6' => ['name' => L('ASSET_MANAGE') /*资金管理*/, 'icon' => 'amicon-asset', 'sub_menu' => [
            ['name' => L('ORDER_SETTLEMENT') /*订单结算*/, 'act' => 'orderSettlement', 'control' => 'AssetMall'],
            ['name' => L('ASSET_ACCOUNT') /*资金账户*/, 'act' => 'mallIncome', 'control' => 'AssetMall'],
            ['name' => L('SELLER_WITHDRAWAL_AUDIT') /*商家提现审核*/, 'act' => 'withdrawalRecord', 'control' => 'AssetMall'],
            ['name' => L('SYSTEM_COLLECT_INFO') /*系统代收明细*/, 'act' => 'systemIncome', 'control' => 'AssetMall', 'ctrl_type' => 'sys_collection'],
            ['name' => L('SYSTEM_SET_WITHDRAWAL') /*系统提现设置*/, 'act' => 'mallWAccount', 'control' => 'AssetMall', 'ctrl_type' => 'sys_collection'],
            ['name' => L('STORE_ASSET') /*店铺资金汇总*/, 'act' => 'storeFundSummary', 'control' => 'AssetMall', 'ctrl_type' => 'sys_collection'],
        ]],
        'menu7' => ['name' => L('ASSET_MANAGE') /*资金管理*/, 'icon' => 'amicon-asset', 'sub_menu' => [
            ['name' => L('ORDER_SETTLEMENT') /*订单结算*/, 'act' => 'orderSettlement', 'control' => 'AssetShop'],
            ['name' => L('ASSET_ACCOUNT') /*资金账户*/, 'act' => 'myIncome', 'control' => 'AssetShop', 'ctrl_type' => 'sys_collection'],
            ['name' => L('WITHDRAWAL_APPLY') /*提现申请*/, 'act' => 'withdrawalRecord', 'control' => 'AssetShop', 'ctrl_type' => 'sys_collection'],
            ['name' => L('COLLECTION_SETTING') /*收款设置*/, 'act' => 'myWAccount', 'control' => 'AssetShop', 'ctrl_type' => 'sys_collection'],
        ]],
        'menu8' => ['name' => L('MEMBER_MANAGE') /*会员管理*/, 'icon' => 'amicon-member', 'sub_menu' => [
            ['name' => L('MEMBER_LIST') /*会员列表*/, 'act' => 'member_list', 'control' => 'Member'],
            ['name' => L('MEMBER_RELATION') /*会员关系*/, 'act' => 'member_relation', 'control' => 'Member', 'ctrl_type' => 'member_ship_ctrl'],
            ['name' => L('SIGN_LIST') /*签到列表*/, 'act' => 'sign_list', 'control' => 'Member'],
            ['name' => L('CREDIT_DETAIL') /*积分明细*/, 'act' => 'credits_list', 'control' => 'Credits'],
//            ['name' => '礼品核销', 'act' => 'credits_present_list', 'control' => 'Credits'],
            ['name' => L('BALANCE_DETAIL') /*余额明细*/, 'act' => 'balanceList', 'control' => 'Balance'],
            ['name' => L('INDEX_TABLE2_15') /*会员提现*/, 'act' => 'drawmoney_apply', 'control' => 'Store', 'ctrl_type' => 'withdraw_ctrl'],
            ['name' => L('WITHDRAWAL_SETTING') /*提现设置*/, 'act' => 'drawmoney_setting', 'control' => 'Store'],
            ['name' => L('KHXI') /*客户消息*/, 'act' => 'customerMsgPage', 'control' => 'CustomerMsg'],
            ['name' => L('HYBQ') /*会员标签*/, 'act' => 'memberTag', 'control' => 'Member'],
//            ['name' => L('DLSSZ') /*代理商设置*/, 'act' => 'partnerSet', 'control' => 'Partner', 'ctrl_type' => 'partner_ctrl'],
        ]],
        'agent' => ['name' => L('DLSGL') /*代理商管理*/, 'icon' => 'amicon-member', 'ctrl_type' => 'partner_ctrl', 'sub_menu' => [
            ['name' => L('SYDL') /*所有代理*/, 'act' => 'agentList', 'control' => 'AgentManage'],
            ['name' => L('SQJL') /*申请记录*/, 'act' => 'applyList', 'control' => 'AgentManage'],
            ['name' => L('DLSSZ') /*代理设置*/, 'act' => 'agentGroupList', 'control' => 'AgentManage'],
            ['name' => L('ZDYJGB') /*自定义价格表*/, 'act' => 'dyPriceList', 'control' => 'AgentManage'],
        ]],
        'menu9' => ['name' => L('FIND_MANAGE') /*发现管理*/, 'icon' => 'amicon-find', 'ctrl_type' => 'find_ctrl', 'sub_menu' => [
            ['name' => L('NEWS_MANAGE') /*资讯管理*/, 'act' => 'news_list_zx', 'control' => 'News', 'ctrl_type' => 'find_ctrl'],
            ['name' => L('NEWS_CLASSIFY') /*分类管理*/, 'act' => 'newsclass_list', 'control' => 'News', 'ctrl_type' => 'find_ctrl'],
            ['name' => L('CORRELATE_GOODS') /*关联商品*/, 'act' => 'allgoods_list', 'control' => 'News', 'ctrl_type' => 'find_ctrl'],
            ['name' => L('ACTIVITY_GOODS') /*活动商品*/, 'act' => 'activegoods_list', 'control' => 'News', 'ctrl_type' => 'find_ctrl', 'store_type' => '02'],
        ]],
        ['name' => L('BDGL') /*表单管理*/, 'icon' => 'amicon-form', 'ctrl_type' => 'find_ctrl', 'sub_menu' => [
            ['name' => L('SYBD') /*所有表单*/, 'act' => 'index', 'control' => 'Form'],
            ['name' => L('YTJJL') /*已提交记录*/, 'act' => 'submittedRecord', 'control' => 'Form'],
        ]],
        'menu10' => ['name' => L('NOTICE_MANAGE') /*公告管理*/, 'icon' => 'amicon-notice', 'ctrl_type' => 'notice_ctrl', 'sub_menu' => [
            ['name' => L('NOTICE_LIST') /*公告列表*/, 'act' => 'notice_list', 'control' => 'Notice'],
            ['name' => L('ADD_NOTICE') /*新增公告*/, 'act' => 'notice_info', 'control' => 'Notice'],
        ]],
        /*        'menu11' => ['name' => '广告管理', 'icon' => 'amicon-ad', 'sub_menu' => [
                    ['name' => '首页横幅', 'act' => 'advertiseList', 'control' => 'Advertise'],
                    ['name' => '主题列表', 'act' => 'ad_list', 'control' => 'Store'],
                ]],*/
        'menu12' => ['name' => L('EMPLOYEE_MANAGE') /*员工管理*/, 'icon' => 'amicon-seller', 'sub_menu' => [
            ['name' => L('EMPLOYEE_LIST') /*员工列表*/, 'act' => 'seller_list', 'control' => 'Seller'],
            ['name' => L('POSITION_LIST') /*职位列表*/, 'act' => 'groupList', 'control' => 'Seller'],
            ['name' => L('ADD_POSITION') /*添加职位*/, 'act' => 'groupInfo', 'control' => 'Seller'],
            ['name' => L('ADD_EMPLOYEE') /*添加员工*/, 'act' => 'seller_add', 'control' => 'Seller'],
            ['name' => L('LOG_LIST') /*操作日志*/, 'act' => 'logList', 'control' => 'Seller'],
            ['name' => L('CHANGE_PWD') /*修改密码*/, 'act' => 'edit', 'control' => 'Sys'],
        ]],
        'menu13' => ['name' => L('COUPON_MANAGE') /*优惠券管理*/, 'icon' => 'amicon-coupon', 'sub_menu' => [
            ['name' => L('COUPON_LIBRARY') /*优惠券库*/, 'act' => 'coupons_list', 'control' => 'Coupons'],
            ['name' => L('COUPON_CENTER') /*领券中心*/, 'act' => 'centerList', 'control' => 'CouponsCenter'],
            ['name' => L('SENT_COUPON') /*已发优惠券*/, 'act' => 'coupons_detail', 'control' => 'Coupons'],
        ]],
        'menu14' => ['name' => L('PRESENT_MANAGE') /*礼品管理*/, 'icon' => 'amicon-present', 'sub_menu' => [
            ['name' => L('JFLP') /*积分礼品*/, 'act' => 'present_list', 'control' => 'Present'],
            ['name' => L('LPHX') /*礼品核销*/, 'act' => 'credits_present_list', 'control' => 'Credits'],
        ]],
        'menu19' => ['name' => L('SPECIALTOPIC_MANAGE') /*专题管理*/, 'icon' => 'amicon-specialTopic', 'sub_menu' => [
            ['name' => L('ZTLB') /*专题列表*/, 'act' => 'stList', 'control' => 'SpecialTopic'],
        ]],
        ['name' => L('RECHARGE_MANAGE') /*充值管理*/, 'icon' => 'amicon-specialTopic', 'ctrl_type' => 'balance_recharge_ctrl', 'sub_menu' => [
            ['name' => L('RECHARGE_CARD_MANAGE') /*充值卡管理*/, 'act' => 'rechargeCardList', 'control' => 'Recharge'],
            ['name' => L('RECHARGE_RECORD') /*充值记录*/, 'act' => 'rechargeCardRecord', 'control' => 'Recharge'],
        ]],
        'menu15' => ['name' => L('ACTIVITY_MANAGE') /*活动管理*/, 'icon' => 'amicon-activity', 'sub_menu' => [
            ['name' => L('TYHD') /*通用活动*/, 'act' => 'activityList', 'control' => 'Otheractivity'],
            ['name' => L('JGGHD') /*九宫格活动*/, 'act' => 'turntableActivityList', 'control' => 'Otheractivity'],
        ]],
        'menu20' => ['name' => L('MARKETING_MANAGE') /*营销管理*/, 'icon' => 'amicon-marketing', 'sub_menu' => [
            ['name' => L('MJ_ACTIVITY') /*满减满送*/, 'act' => 'mjActivity', 'control' => 'Marketing'],
            ['name' => L('YXJH') /*营销计划*/, 'act' => 'marketingPlan', 'control' => 'Marketing'],

        ]],
        /* V604 更改礼品码导航为虚拟卡导航*/
        'menu22' => $menuEXCode,

        'menu21' => ['name' => L('ZBGL') /*直播管理*/, 'ctrl_type' => 'live_ctrl', 'icon' => 'amicon-liveV', 'sub_menu' => [
            ['name' => L('ZBJL') /*直播记录*/, 'act' => 'livevideoRerord', 'control' => 'Livevideo'],
            ['name' => L('ZBFA') /*直播方案*/, 'act' => 'livevideoScheme', 'control' => 'Livevideo']
        ]],
        'menu17' => ['name' => L('MSG_PUSH_MANAGE') /*推送管理*/, 'icon' => 'amicon-activity', 'sub_menu' => [
            ['name' => L('MSG_CONFIG') /*消息配置*/, 'act' => 'messageConfig', 'control' => 'Message'],
            ['name' => L('FSJL') /*发送记录*/, 'act' => 'sendRecord', 'control' => 'Message'],
        ]],
        'menu16' => ['name' => L('DATA_ANALYSIS') /*数据分析*/, 'icon' => 'fa fa-line-chart', 'sub_menu' => [
            ['name' => L('ANALYSIS_REPORT') /*分析报表*/, 'act' => 'statisticList', 'control' => 'Statistic'],
            ['name' => L('FLOW_ANALYSIS') /*数据流量分析*/, 'act' => 'flowAnalysis', 'control' => 'DataCalculation'],
            ['name' => L('MEMBER_RANKING') /*会员排行*/, 'act' => 'memberRank', 'control' => 'DataCalculation'],
            ['name' => L('SALE_RANKING') /*销售排行*/, 'act' => 'saleRank', 'control' => 'DataCalculation'],
            ['name' => L('SHPTJ') /*商品统计*/, 'act' => 'goods_visit_list', 'control' => 'DataCalculation'],
        ]],
    ];
}

/**
 * 记录日志
 * 默认是信息等级
 * @param string $message
 * @param string $level
 * @param string $method
 * @return array ['code'=>200,'msg'=>'','data'=>[]]
 * User: hj
 * Date: 2017-09-14 00:40:00
 */
function logWrite($message = '', $level = 'INFO', $method = 'record')
{
    // 定时任务不做日志记录 否则浪费资源
    if (strtolower(ACTION_NAME) === 'check_order') {
        return getReturn(200);
    }
    \Think\Log::$method($message, $level);
}

/**
 * 字符串的换行转成br
 * @param $string
 * @return array ['code'=>200,'msg'=>'','data'=>[]]
 * User: hj
 * Date: 2017-09-18 10:49:34
 * Version: 1.0
 */
function nlRl2br($string)
{
    $string = strip_tags($string);
    $string = preg_replace('/\r\n/is', '<br>', $string);
    $string = preg_replace('/\n\r/is', '<br>', $string);
    $string = preg_replace('/\n/is', '<br>', $string);
    $string = preg_replace('/\r/is', '<br>', $string);
    return $string;
}

/**
 * 获取当前URL链接
 * @return string
 * User: hjun
 * Date: 2018-05-17 09:26:49
 * Update: 2018-05-17 09:26:49
 * Version: 1.00
 */
function getCurPageURL()
{
    return URL($_SERVER["REQUEST_URI"]);
}

/**
 * 跳转
 * @param string $url
 * @param int $time
 * User: hjun
 * Date: 2018-05-17 09:27:37
 * Update: 2018-05-17 09:27:37
 * Version: 1.00
 */
function jump($url = '', $time = 0)
{
    if (empty($url)) $url = getCurPageURL();
    if ($time || headers_sent()) {
        header('location:' . $url);
    } else {
        header('location:' . $url);
    }
    exit;
}

/**
 * CURL请求
 * @param string $url 请求url地址
 * @param string $method 请求方法 get post
 * @param array $postfields post数据数组
 * @param array $headers 请求header信息
 * @param bool|false $debug 调试开启 默认false
 * @return mixed
 */
function httpRequest($url, $method = 'get', $postfields = null, $headers = array(), $debug = false)
{
    $count = 0;
    do {
        $method = strtoupper($method);
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
        curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        switch ($method) {
            case "POST":
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
                break;
            default:
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
                break;
        }
        $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
        curl_setopt($ci, CURLOPT_URL, $url);
        if ($ssl) {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        }
        //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
        curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
        $response = curl_exec($ci);
        $requestinfo = curl_getinfo($ci);
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $curl_error = curl_errno($ci);
        if ($debug) {
            echo "=====post data======\r\n";
            var_dump($postfields);
            echo "=====info===== \r\n";
            print_r($requestinfo);
            echo "=====response=====\r\n";
            print_r($response);
        }
        curl_close($ci);
        $count++;
    } while ($count < 3 && $curl_error > 0);
    //return $response;
    return array('code' => $http_code, 'data' => $response, 'msg' => $curl_error);
}

/**
 * 过滤字符串中非UTF-8字符
 * @param string $str 原始字符串
 * @param boolean $needFilterOver3Byte 是否需要过滤超过3个字节的
 * @return string
 * User: hjun
 * Date: 2018-12-19 03:48:56
 * Update: 2018-12-19 03:48:56
 * Version: 1.00
 */
function filterToUTF8($str = '', $needFilterOver3Byte = false)
{
    /*utf8 编码表：
    * Unicode符号范围           | UTF-8编码方式
    * u0000 0000 - u0000 007F   | 0xxxxxxx
    * u0000 0080 - u0000 07FF   | 110xxxxx 10xxxxxx
    * u0000 0800 - u0000 FFFF   | 1110xxxx 10xxxxxx 10xxxxxx
    *
    */
    $re = '';
    $str = str_split(bin2hex($str), 2);

    $mo = 1 << 7;
    $mo2 = $mo | (1 << 6);
    $mo3 = $mo2 | (1 << 5);         //三个字节
    $mo4 = $mo3 | (1 << 4);          //四个字节
    $mo5 = $mo4 | (1 << 3);          //五个字节
    $mo6 = $mo5 | (1 << 2);          //六个字节


    for ($i = 0; $i < count($str); $i++) {
        if ((hexdec($str[$i]) & ($mo)) == 0) {
            $re .= chr(hexdec($str[$i]));
            continue;
        }

        //4字节(包含4字节) 及其以上舍去
        if ($needFilterOver3Byte) {
            if ((hexdec($str[$i]) & ($mo6)) == $mo6) {
                $i = $i + 5;
                continue;
            }

            if ((hexdec($str[$i]) & ($mo5)) == $mo5) {
                $i = $i + 4;
                continue;
            }

            if ((hexdec($str[$i]) & ($mo4)) == $mo4) {
                $i = $i + 3;
                continue;
            }
        }

        if ((hexdec($str[$i]) & ($mo3)) == $mo3) {
            $i = $i + 2;
            if (((hexdec($str[$i]) & ($mo)) == $mo) && ((hexdec($str[$i - 1]) & ($mo)) == $mo)) {
                $r = chr(hexdec($str[$i - 2])) .
                    chr(hexdec($str[$i - 1])) .
                    chr(hexdec($str[$i]));
                $re .= $r;
            }
            continue;
        }

        if ((hexdec($str[$i]) & ($mo2)) == $mo2) {
            $i = $i + 1;
            if ((hexdec($str[$i]) & ($mo)) == $mo) {
                $re .= chr(hexdec($str[$i - 1])) . chr(hexdec($str[$i]));
            }
            continue;
        }
    }
    return $re;
}

/**
 * 根据序号数值获取Excel的column
 * @param int $num
 * @return string
 * User: hjun
 * Date: 2018-12-22 01:39:20
 * Update: 2018-12-22 01:39:20
 * Version: 1.00
 */
function getExcelColumn($num = 0)
{
    $column = '';
    $remainder = $num % 26;
    $quotient = floor($num / 26);
    if ($quotient > 0) {
        $column .= getExcelColumn($quotient - 1);
    }
    $column .= chr($remainder + 65);
    return $column;
}

/**
 * 获取字符串的首字母
 * @param $str
 * @return string A
 * User: hj
 * Date: 2017-09-27 09:32:28
 * Version: 1.0
 */
function getFirstCharter($str)
{
    if (empty($str)) {
        return '';
    }
    $fchar = ord($str{0});
    if ($fchar >= ord('A') && $fchar <= ord('z')) return strtoupper($str{0});
    $s1 = iconv('UTF-8', 'gb2312', $str);
    $s2 = iconv('gb2312', 'UTF-8', $s1);
    $s = $s2 == $str ? $s1 : $str;
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc >= -20319 && $asc <= -20284) return 'A';
    if ($asc >= -20283 && $asc <= -19776) return 'B';
    if ($asc >= -19775 && $asc <= -19219) return 'C';
    if ($asc >= -19218 && $asc <= -18711) return 'D';
    if ($asc >= -18710 && $asc <= -18527) return 'E';
    if ($asc >= -18526 && $asc <= -18240) return 'F';
    if ($asc >= -18239 && $asc <= -17923) return 'G';
    if ($asc >= -17922 && $asc <= -17418) return 'H';
    if ($asc >= -17417 && $asc <= -16475) return 'J';
    if ($asc >= -16474 && $asc <= -16213) return 'K';
    if ($asc >= -16212 && $asc <= -15641) return L('');
    if ($asc >= -15640 && $asc <= -15166) return 'M';
    if ($asc >= -15165 && $asc <= -14923) return 'N';
    if ($asc >= -14922 && $asc <= -14915) return 'O';
    if ($asc >= -14914 && $asc <= -14631) return 'P';
    if ($asc >= -14630 && $asc <= -14150) return 'Q';
    if ($asc >= -14149 && $asc <= -14091) return 'R';
    if ($asc >= -14090 && $asc <= -13319) return 'S';
    if ($asc >= -13318 && $asc <= -12839) return 'T';
    if ($asc >= -12838 && $asc <= -12557) return 'W';
    if ($asc >= -12556 && $asc <= -11848) return 'X';
    if ($asc >= -11847 && $asc <= -11056) return 'Y';
    if ($asc >= -11055 && $asc <= -10247) return 'Z';
    return null;
}

/**
 * 获取时间的起止时间 （一天）  默认是今天
 * @param $time
 * @return array ['code'=>200,'msg'=>'','data'=>[]]
 * User: hj
 * Date: 2017-09-28 09:34:04
 * Version: 1.0
 */
function getStartAndEndTime($time = NOW_TIME)
{
    $startTime = strtotime(date('Y-m-d', $time));
    $endTime = $startTime + 3600 * 24 - 1;
    return ['start_time' => $startTime, 'end_time' => $endTime];
}

/**
 * 获取最近30天的起止
 * @param $time
 * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: hjun
 * Date: 2018-01-22 18:02:46
 * Update: 2018-01-22 18:02:46
 * Version: 1.00
 */
function getMonthStartAndEndTime($time = NOW_TIME)
{
    $startTime = strtotime(date('Y-m-d', $time - 3600 * 24 * 30));
    $endTime = $startTime + 3600 * 24 * 31 - 1;
    return ['start_time' => $startTime, 'end_time' => $endTime];
}

/**
 * If you want to keep the order when two members compare as equal, use this.
 * @param $array
 * @param $cmp_function
 * @return void
 * User: hjun
 * Date: 2018-12-20 17:10:29
 * Update: 2018-12-20 17:10:29
 * Version: 1.00
 */
function stable_uasort(&$array, $cmp_function)
{
    if (count($array) < 2) {
        return;
    }
    $halfway = count($array) / 2;
    $array1 = array_slice($array, 0, $halfway, TRUE);
    $array2 = array_slice($array, $halfway, NULL, TRUE);

    stable_uasort($array1, $cmp_function);
    stable_uasort($array2, $cmp_function);
    if (call_user_func($cmp_function, end($array1), reset($array2)) < 1) {
        $array = $array1 + $array2;
        return;
    }
    $array = array();
    reset($array1);
    reset($array2);
    while (current($array1) !== false && current($array2) !== false) {
        if (call_user_func($cmp_function, current($array1), current($array2)) < 1) {
            $array[key($array1)] = current($array1);
            next($array1);
        } else {
            $array[key($array2)] = current($array2);
            next($array2);
        }
    }
    while (current($array1) !== false) {
        $array[key($array1)] = current($array1);
        next($array1);
    }
    while (current($array2) !== false) {
        $array[key($array2)] = current($array2);
        next($array2);
    }
    return;
}

/**
 * 二维数组排序
 * @param array $arr 二维数据
 * @param string $keys 根据哪个键值排序
 * @param string $type 排序类型 升降
 * @return array ['code'=>200,'msg'=>'','data'=>[]]
 * User: hj
 * Date: 2017-10-12 10:36:43
 * Desc: 二维数组排序
 * Update: 2017-10-12 10:36:44
 * Version: 1.0
 */
function array_sort($arr, $keys, $type = 'desc')
{
    global $array_sort_type;
    $array_sort_type = strtolower($type);
    $key_value = $new_array = array();
    foreach ($arr as $k => $v) {
        $key_value[$k] = $v[$keys];
    }
    stable_uasort($key_value, function ($a, $b) {
        $a = is_numeric($a) ? (double)$a : $a;
        $b = is_numeric($b) ? (double)$b : $b;
        if ($a == $b) {
            return 0;
        }
        global $array_sort_type;
        if ($array_sort_type === 'asc') {
            return ($a < $b) ? -1 : 1;
        } else {
            return ($a < $b) ? 1 : -1;
        }
    });
    unset($array_sort_type);
    reset($key_value);
    foreach ($key_value as $k => $v) {
        $new_array[] = $arr[$k];
    }
    return $new_array;
}

/**
 * @param $arr
 * @return array
 * Author: hj
 * Desc: 刷新数组 主要是为了设置key为0开始升序
 * Date: 2017-11-08 10:13:49
 * Update: 2017-11-08 10:13:50
 * Version: 1.0
 */
function arrayFlush($arr)
{
    return array_values($arr);
}

/**
 * 深度搜索 返回所有索引
 * @param $needle
 * @param $haystack
 * @return mixed boolean|array
 * User: hjun
 * Date: 2018-08-10 11:12:43
 * Update: 2018-08-10 11:12:43
 * Version: 1.00
 */
function arraySearchDeep($needle, $haystack)
{
    $allIndex = [];
    do {
        $index = array_search($needle, $haystack);
        if ($index !== false) {
            unset($haystack[$index]);
            $allIndex[] = $index;
        }
    } while ($index !== false);
    return empty($allIndex) ? false : $allIndex;
}

/**
 * 判断开始时间和结束时间
 * @param $startTime
 * @param $endTime
 * @param $mustStart
 * @param $mustEnd
 * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: hjun
 * Date: 2017-12-07 16:08:35
 * Update: 2017-12-07 16:08:35
 * Version: 1.00
 */
function checkStartTimeAndEndTime($startTime, $endTime, $mustStart = true, $mustEnd = true)
{
    $startTime = strtotime($startTime);
    $endTime = strtotime($endTime);
    $startTime = empty($startTime) ? 0 : $startTime;
    $endTime = empty($endTime) ? 0 : $endTime;
    if (empty($startTime) && $mustStart) return getReturn(-1, '请选择开始时间');
    if (empty($endTime) && $mustEnd) return getReturn(-1, '请选择结束时间');
//    if ($startTime < NOW_TIME && $mustStart) return getReturn(-1, '开始时间不能小于当前时间');
    if ($startTime > $endTime && $mustStart && $mustEnd) return getReturn(-1, '开始时间不能大于结束时间');
    $data = [];
    $data['start_time'] = $startTime;
    $data['end_time'] = $endTime;
    if (!empty($startTime) && !empty($endTime)) {
        $data['where'] = ['between', [$startTime, $endTime]];
    } elseif (!empty($startTime) && empty($endTime)) {
        $data['where'] = ['egt', $startTime];
    } elseif (empty($startTime) && !empty($endTime)) {
        $data['where'] = ['elt', $endTime];
    }
    return getReturn(200, '', $data);
}

/**
 * 获取范围的搜索条件
 * @param array $req
 * @param string $reqKeyMin 搜索最小值
 * @param string $reqKeyMax 搜索最大值
 * @param string $error 错误提示
 * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: hjun
 * Date: 2018-04-16 11:33:48
 * Update: 2018-04-16 11:33:48
 * Version: 1.00
 */
function getRangeWhere($req = [], $reqKeyMin = 'min', $reqKeyMax = 'max', $error = '范围不正确')
{
    // 抓换时间失败则不转换 默认转换时间
    $min = strtotime($req[$reqKeyMin]) ? strtotime($req[$reqKeyMin]) : $req[$reqKeyMin];
    $max = strtotime($req[$reqKeyMax]) ? strtotime($req[$reqKeyMax]) : $req[$reqKeyMax];
    if (!empty($req[$reqKeyMin]) && empty($req[$reqKeyMax])) {
        $where = ['egt', $min];
    } elseif (empty($req[$reqKeyMin]) && !empty($req[$reqKeyMax])) {
        $where = ['elt', $max];
    } elseif (!empty($req[$reqKeyMin]) && !empty($req[$reqKeyMax])) {
        if ($min > $max) {
            $where = ['egt', $min];
        } else {
            $where = ['between', "{$min},{$max}"];
        }
    } else {
        $where = [];
    }
    return getReturn(CODE_SUCCESS, 'success', $where);
}

/**
 * TODO 调用的地方重构
 * 转换树形结构
 * @param $list
 * @param string $pk
 * @param string $pid
 * @param string $child
 * @param int $root 根节点PID
 * @param array $other 其他属性
 * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: hjun
 * Date: 2018-03-01 09:59:56
 * Update: 2018-03-01 09:59:56
 * Version: 1.00
 */
function getTreeArr($list, $pk = 'id', $pid = 'pid', $child = 'child', $root = -1, $other = [])
{
    $tree = array();// 创建Tree
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }

        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                if (!empty($other)) {
                    foreach ($other as $attr) {
                        if (isset($attr['name']) && isset($attr['pname'])) {
                            $list[$key][$attr['pname']] = $list[$key][$attr['name']];
                        }
                    }
                }
                $tree[$data[$pk]] =& $list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    if (!empty($other)) {
                        foreach ($other as $attr) {
                            if (isset($attr['name']) && isset($attr['pname'])) {
                                $link = empty($attr['link']) ? '>' : $attr['link'];
                                $list[$key][$attr['pname']] = "{$parent[$attr['pname']]}{$link}{$list[$key][$attr['name']]}";
                            }
                        }
                    }


                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 获取父级数组
 * @param array $items 数组
 * @param string $pidName 父级字段名
 * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: hjun
 * Date: 2018-01-30 10:42:39
 * Update: 2018-01-30 10:42:39
 * Version: 1.00
 */
function getParentArr($items = [], $pidName = 'pid')
{
    $arr = [];
    foreach ($items as $key => $value) {
        if ($value[$pidName] == 0) {
            $arr[] = $value;
        }
    }
    return $arr;
}

/**
 * 计算图片魔方的样式
 * @param $divArea
 * @param $item
 * @param $itemCube
 * @return string
 * User: hjun
 * Date: 2018-09-26 10:26:30
 * Update: 2018-09-26 10:26:30
 * Version: 1.00
 */
function capCubeImgStyle($divArea, $item, $itemCube)
{
    if ($divArea == 1) {
        if($item['cube_module'] == 0 || $item['cube_module'] == 1 || $item['cube_module'] == 2){
            if($item['cube_layout']['allPickMatrix'][0]['img_url'] !== ''){
                $width = $item['cube_layout']['allPickMatrix'][0]['width'];
                $width = str_replace('px', '', $width);
                $height = $item['cube_layout']['allPickMatrix'][0]['height'];
                $height = str_replace('px', '', $height);
                $ratio1 = ((int)$width) / ((int)$height);
                $ratio = (100 / $ratio1) / ((int)$item['cube_module'] + 2);
            }else{
                $ratio = (100 / (int)$item['cube_module'] );
            }
            $pd = 'calc('.$ratio.'% + ' . $item['img_gap'] . 'px)';
        }else if($item['cube_module'] == 7){
            if (isset($item['cube_density_row'])) {
                $row = $item['cube_density_row'];
                $col = $item['cube_density_col'];
            } else {
                $row = $col = $item['cube_density'];
            }
            $pd_val = ($row / $col) * 100 . '%';
            $pd = 'calc(' . $pd_val . ' + ' . $item['img_gap'] . 'px)';
        }else{
            $pd = 'calc(100% + ' . $item['img_gap'] . 'px)';
        }
        $w = 'calc(100% + ' . $item['img_gap'] . 'px)';
        $m = '-' . ($item['img_gap']) / 2 . 'px';
        $style = "padding-bottom: {$pd};width: {$w};margin: {$m};";
    } else if ($divArea == 2) {
        $w = 'calc(' . $itemCube['width2'] . ' - ' . ($item['img_gap']) . 'px)';
        if($item['cube_module'] == 0 || $item['cube_module'] == 1 || $item['cube_module'] == 2){
            $h = 'calc(100% - ' . ($item['img_gap']) . 'px)';
        }else{
            $h = 'calc(' . $itemCube['height2'] . ' - ' . ($item['img_gap']) . 'px)';
        }
        $l = $itemCube['left2'];
        $t = $itemCube['top2'];
        $imgWidth = ceil((double)$itemCube['width'] * 2);
        $bgImg = 'url(' . $itemCube['img_url'] . '?_' .$imgWidth. 'xx4)';
        $m = ($item['img_gap']) / 2 . 'px';
        $style = "width: {$w};height: {$h};left: {$l};top: {$t};background-image: {$bgImg};margin: {$m};";
    } else {
        //cap-cube-img
        $isImg = isCubeImg($item);
        if (!$isImg) {
            $pd = '0';
        } else {
            if($item['cube_module'] == 0 || $item['cube_module'] == 1 || $item['cube_module'] == 2){
                if($item['cube_layout']['allPickMatrix'][0]['img_url'] !== ''){
                    $width = $item['cube_layout']['allPickMatrix'][0]['width'];
                    $width = str_replace('px', '', $width);
                    $height = $item['cube_layout']['allPickMatrix'][0]['height'];
                    $height = str_replace('px', '', $height);
                    $ratio1 = ((int)$width) / ((int)$height);
                    $ratio = (100 / $ratio1) / ((int)$item['cube_module'] + 2);
                }else{
                    $ratio = (100 / (int)$item['cube_module'] );
                }
                $pd = $ratio. '%';
            }else if($item['cube_module'] == 7){
                $row_ = 0;
                foreach ($item['cube_layout']['allPickMatrix'] as $value) {
                    if ($value['img_url'] !== '') {
                        foreach ($value['matrix'] as $value2) {
                            if ($row_ <= $value2['y']) {
                                $row_ = intval($value2['y']);
                            }
                        }
                    }
                }
                $row_result = intval($row_) + 1;
                $col_ = intval($item['cube_density_col']);
                $pd = (($row_result / $col_) * 100) . '%';
            }else{
                $pd = '100%';
            }
        }
        $style = "padding-bottom:{$pd}";
    }
    return $style;
}

/**
 * 计算图片魔方是否上图
 */
function isCubeImg($item)
{
    $imgL = 0;
    foreach ($item['cube_layout']['allPickMatrix'] as $v) {
        if ($v['img_url'] !== '') {
            $imgL++;
        }
    }
    if ($imgL > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * 创建目录
 * @param string $dir
 * @return void
 * User: hjun
 * Date: 2019-02-19 19:29:50
 * Update: 2019-02-19 19:29:50
 * Version: 1.00
 */
function makeDir($dir = '')
{
    $log_dir = dirname($dir);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
}

/**
 * 压缩文件夹下面的文件
 * @param $basePath
 * @param $zipName
 * User: hjun
 * Date: 2019-03-19 11:52:36
 * Update: 2019-03-19 11:52:36
 * Version: 1.00
 */
function zipDir($basePath, $zipName)
{
    $zip = new \ZipArchive();
    if (is_dir($basePath)) {
        if ($dh = opendir($basePath)) {
            $zip->open($zipName, \ZipArchive::CREATE);
            while (($file = readdir($dh)) !== false) {
                if (in_array($file, ['.', '..',])) continue; //无效文件，重来
                $zip->addFile($basePath . '/' . $file, $file);
            }
            $zip->close();
            closedir($dh);
        }
    }
}

/**
 * 获取代理商表单ID
 * @param int $storeId
 * @return int
 * User: hjun
 * Date: 2019-04-10 17:38:58
 * Update: 2019-04-10 17:38:58
 * Version: 1.00
 */
function getAgentDiyFormId($storeId = 0)
{
    return D('AgentForm')->where("isdelete=0 AND store_id={$storeId}")->getField('form_id');
}

/**
 * 将本地服务器的文件上传至文件服务器
 * @param string $filePath
 * @param int $type 文件类型 总共11中选项
 *  // 上传类型 1-商品类型 2-分类文件 3-资讯 4-聊天 5-广告 6-临时 7-默认 8-视频 9-人脸识别 10-附件 11-压缩包
 * @return string
 * {"result": 0, "datas": {"ori_url": ""}}
 * User: hjun
 * Date: 2019-03-19 11:56:46
 * Update: 2019-03-19 11:56:46
 * Version: 1.00
 */
function uploadLocalFileToRemote($filePath = '', $type = 7)
{
    $params = array();
    $params['file'] = "@{$filePath}";
    if (in_array($type, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11])) {
        $params['type'] = $type;
    }
    $url = C('upload_url') ? C('upload_url') : "http://file.duinin.com/upload.php";
    // 如果没有http,则需要拼接上协议
    if (strpos($url, 'http') === false) {
        // 如果开头是//,则只需要拼接http
        $suffix = strpos($url, '//') === 0 ? 'http:' : 'http://';
        $url = $suffix . $url;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $response = curl_exec($ch);
    return $response;
}

/**
 * 树形转换为二维
 * @param array $arr
 * @param string $childName
 * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: hjun
 * Date: 2018-03-01 10:09:57
 * Update: 2018-03-01 10:09:57
 * Version: 1.00
 */
function unTree($arr = [], $childName = 'child')
{
    $res = array();
    foreach ($arr as $v) {
        $t = $v[$childName];
        unset($v[$childName]);
        $res[] = $v;
        if ($t) $res = array_merge($res, untree($t));
    }
    return $res;
}

/**
 * 判断specid是否为空
 * @param string $specId
 * @return boolean
 * User: hjun
 * Date: 2019-06-10 15:01:44
 * Update: 2019-06-10 15:01:44
 * Version: 1.00
 */
function isSpecIdEmpty($specId = '')
{
    if (strpos($specId, 'null') !== false) {
        return true;
    }
    return empty($specId);
}

/**
 * 根据DIY的商品来源设置 获取 商品ID数组
 * @param array $source
 * @param array $storeInfo
 * @return array
 * User: hjun
 * Date: 2019-06-06 21:35:18
 * Update: 2019-06-06 21:35:18
 * Version: 1.00
 */
function getGoodsIdsByDiySource($source = [], $storeInfo = [])
{
    $goodsIds = [];
    $isSelect = false;
    if ($source['is_select_class'] == 1 && !empty($source['class_id'])) {
        $classId = $source['class_id'];
        $id = explode('|', $classId);
        $level = count($id);
        $id = $id[$level - 1];
        $where = [];
        if (isMall($storeInfo['store_type'])) {
            $where["mall_class_{$level}"] = $id;
        } else {
            $where["goods_class_{$level}"] = $id;
        }
        $goodsIds = M('goods_extra')->where($where)->getField('goods_id', true);
        if (empty($goodsIds)) {
            $goodsIds = [];
        }
        $isSelect = true;
    }
    if ($source['is_select_tag'] == 1 && !empty($source['tag_id'])) {
        $tagId = $source['tag_id'];
        $where['tag_id'] = $tagId;
        $goodsIds2 = M('goods_tag_link')->where($where)->getField('goods_id', true);
        if (empty($goodsIds2)) {
            $goodsIds2 = [];
        }
        $isSelect = true;
        // 如果有选择分类 则要交集
        if ($source['is_select_class'] == 1 && !empty($source['class_id'])) {
            $goodsIds = array_values(array_intersect($goodsIds, $goodsIds2));
        } else {
            $goodsIds = $goodsIds2;
        }
    }
    if (!$isSelect) {
        return null;
    }
    // 未设置 则返回null 表示不需要筛选
    return $goodsIds;
}

/**
 * 构建批量更新SQL语句
 * @param string $table
 * @param array $dataList
 * @param string $caseField
 * @return string
 * User: hjun
 * Date: 2019-05-23 21:41:19
 * Update: 2019-05-23 21:41:19
 * Version: 1.00
 */
function buildSaveAllSQL($table = '', $dataList = [], $caseField = '')
{
    $sql = "UPDATE {$table} SET ";
    $case = [];
    foreach ($dataList[0] as $field => $value) {
        if ($field == $caseField) {
            continue;
        }
        $sql .= "{$field} = CASE {$caseField} ";
        foreach ($dataList as $val) {
            $sql .= "WHEN {$val[$caseField]} THEN '{$val[$field]}' ";
            if (!in_array($val[$caseField], $case)) {
                $case[] = $val[$caseField];
            }
        }
        $sql .= "END, ";
    }
    // 去掉最后的逗号
    $sql = substr($sql, 0, strrpos($sql, ','));
    $case = implode(',', $case);
    $sql .= " WHERE {$caseField} IN ({$case})";
    return $sql;
}

/**
 * 获取所有地区列表 一维数组
 * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: hjun
 * Date: 2018-03-18 00:36:40
 * Update: 2018-03-18 00:36:40
 * Version: 1.00
 */
function getAllAreaList()
{
    $list = S('allAreaList');
    if (empty($list)) {
        $model = M('mb_areas');
        $where = [];
        $where['a.is_delete'] = 0;
        $where['a.status'] = 1;
        $list = $model
            ->alias('a')
            ->field('a.area_id,a.area_name,a.area_pid,a.area_type')
            ->where($where)
            ->order('a.area_sort ASC,a.area_id ASC')
            ->select();
        S('allAreaList', $list);
    }
    return $list;
}

/**
 * 根据地区ID获取地区名称
 * @param int $id
 * @return string
 * User: hjun
 * Date: 2018-03-18 00:36:47
 * Update: 2018-03-18 00:36:47
 * Version: 1.00
 */
function getAreaNameById($id = 0)
{
    $list = getAllAreaList();
    foreach ($list as $area) {
        if ($area['area_id'] == $id) {
            return $area['area_name'];
        }
    }
    return '';
}

/**
 * 获取地区的子地区列表
 * @param int $pid
 * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: hjun
 * Date: 2018-03-18 00:37:31
 * Update: 2018-03-18 00:37:31
 * Version: 1.00
 */
function getAreaListByPid($pid = 0)
{
    $child = [];
    $list = getAllAreaList();
    foreach ($list as $area) {
        if ($area['area_pid'] == $pid) {
            $child[] = $area;
        }
    }
    return $child;
}

/**
 * 获取区域的省级父ID
 * @param int $areaId
 * @return string
 * User: hjun
 * Date: 2018-03-22 20:53:05
 * Update: 2018-03-22 20:53:05
 * Version: 1.00
 */
function getAreaProvincePid($areaId = 0)
{
    $list = getAllAreaList();
    foreach ($list as $area) {
        if ($area['area_id'] == $areaId && $area['area_type'] == 1) {
            return $area['area_pid'];
        }
    }
    return '';
}

/**
 * 记录崩溃日志
 * @param string $error
 * @param int $code
 * User: hjun
 * Date: 2018-01-11 21:31:56
 * Update: 2018-01-11 21:31:56
 * Version: 1.00
 */
function addExceptionLog($error = '', $code = 0)
{
    // 链接访问异常不做记录
    if ((strpos($error, "无法加载") !== false ||
            strpos($error, "非法操作") !== false ||
            strpos($error, 'Got a packet bigger than')) &&
        $code != 1) {
        return;
    }
    $root = strtolower(__APP__);
    $module = strtolower(MODULE_NAME);
    $ctrl = strtolower(CONTROLLER_NAME);
    $act = strtolower(ACTION_NAME);
    $url = $_SERVER['REQUEST_URI'];
    if ($ctrl !== 'controller_name' && $act !== 'action_name') {
        $param = getRequest();
        $data = array();
        $data['exception_msg'] = $error;
        $data['exception_code'] = $code;
        $data['url'] = empty($url) ? '' : $url;
        $data['param'] = $param;
        $data['root'] = $root;
        $data['module'] = $module;
        $data['control'] = $ctrl;
        $data['act'] = $act;
        $data['server_info'] = $_SERVER;
        $data['create_time'] = NOW_TIME;
        $content = json_encode($data, 256);
        $path = DATA_PATH . 'exception/' . date('Y-m-d') . '/exception.log';
        $file = new \Think\Log\Driver\File();
        $file->write($content . "\r\n", $path);
    }
}

// ====================================================    Admin    ====================================================
// ====================================================    Admin    ====================================================
// ====================================================    Admin    ====================================================
// ====================================================    Admin    ====================================================

// region 门店管理
/**
 * 判断价格是否不同
 * @param $buyPrice
 * @param $goodsPrice
 * @return boolean
 * User: hjun
 * Date: 2018-11-08 16:04:28
 * Update: 2018-11-08 16:04:28
 * Version: 1.00
 */
function isPriceDifferent($buyPrice, $goodsPrice)
{
    return abs(round($goodsPrice, 2) - round($buyPrice, 2)) > 0.009;
}

/**
 * 设置商品规格是否可选
 * @param array $goods 待选列表
 * @param array $cantList 已选列表
 * @return void
 * User: hjun
 * Date: 2018-10-22 12:13:44
 * Update: 2018-10-22 12:13:44
 * Version: 1.00
 */
function setGoodsSpecIsSelect(&$goods = [], $cantList = [])
{
    foreach ($cantList as $value) {
        if ($value['goods_id'] == $goods['goods_id']) {
            foreach ($goods['spec_attr'] as $key => $spec) {
                if ($spec['primary_id'] == $value['spec_id']) {
                    $goods['spec_attr'][$key]['is_select'] = 1;
                    break;
                }
            }
        }
    }
}

/**
 * 设置商品是否被选中
 * @param array $goods
 * @param array $cantSpecList
 * @return void
 * User: hjun
 * Date: 2018-10-31 12:19:59
 * Update: 2018-10-31 12:19:59
 * Version: 1.00
 */
function setGoodsIsSelect(&$goods = [], $cantSpecList = [])
{
    foreach ($cantSpecList as $value) {
        if ($value['goods_id'] == $goods['goods_id']) {
            $goods['is_select'] = 1;
            break;
        }
    }
}
// endregion


// ==================================================    ApiCommon    ==================================================
// ==================================================    ApiCommon    ==================================================
// ==================================================    ApiCommon    ==================================================
// ==================================================    ApiCommon    ==================================================
/**
 * 成功返回数据
 * @access public
 * @param object $datas 返回数据
 * @param bool $log 是否需要打印日志
 * @param string $fag 日志标记
 * @return string
 */
function output_data($datas, $log = false, $fag = 'output_data')
{
    $data = array();
    $data['result'] = 0;
    $data['datas'] = $datas;
    if ($log) {
        logRecord($fag . "->" . json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    header("Content-type:text/html;charset=utf-8");
    die;
}

/**
 * 数据同步数据返回
 * @access public
 * @param string $version 版本信息
 * @param array $citems 有效数据
 * @param array $ditems 删除数据
 * @param bool $hasmore 是否有下一页
 * @param bool $log 是否打印日志
 * @param string $fag 日志标记
 * @return string
 */
function output_synchrodata($version, $citems, $ditems, $hasmore = false, $log = false, $fag = 'output_synchrodata')
{
    $data = array();
    $data['result'] = 0;
    $data['version'] = $version;
    $datas = array();
    $datas['citems'] = $citems;
    $datas['ditems'] = $ditems;
    $data['datas'] = $datas;
    $data['hasmore'] = $hasmore;
    if ($log) {
        logRecord($fag . "->" . json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    header("Content-type:text/html;charset=utf-8");
    die;
}

/**
 * 数据错误返回
 * @access public
 * @param string $result 错误等级,不能为0
 * @param string $error 错误信息
 * @param string $debug 调试信息
 * @param bool $log 是否打印日志
 * @param string $fag 日志标记
 * @return string
 */
function output_error($result, $error, $debug, $datas = array(), $log = false, $fag = 'output_error')
{
    $data = array();
    $data['result'] = $result;
    $data['error'] = $error;
    $data['debug'] = $debug;
    $data['datas'] = $datas;
    if ($log) {
        logRecord($fag . "->" . json_encode($data, JSON_UNESCAPED_UNICODE), \Think\Log::ERR, 2);
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    die;
}

/**
 * TODO 针对API和其他模块做兼容
 * 返回规范格式
 * @param int $result 错误等级
 * @param string $error 错误信息
 * @param string $debug 调试信息
 * @param array $data 返回数据
 * @return array
 * User: hjun
 * Date: 2017-12-27 00:07:52
 * Update: 2017-12-27 00:07:52
 * Version: 1.00
 */
function getReturn($result = -1, $error, $data = [], $debug = '')
{
    $error = !isset($error) ? L('REQUEST_ERROR_SYSTEM') : $error;
    if (strtolower(MODULE_NAME) === 'api') {
        // 处理code 和 result
        if ($result === 200 || $result === 0) {
            $result = 0;
            $code = 200;
        } else {
            $code = $result;
        }
        return [
            'result' => $result,
            'error' => $error,
            'datas' => $data,
            'debug' => $debug,
            'code' => $code,
            'msg' => $error,
            'data' => $data
        ];
    } else {
        return ['code' => $result, 'msg' => $error, 'data' => $data];
    }

}

/**
 * 获取APP的返回格式
 * @param array $result
 * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: hjun
 * Date: 2018-01-04 15:06:18
 * Update: 2018-01-04 15:06:18
 * Version: 1.00
 */
function transformAppReturn($result = [])
{
    return [
        'result' => $result['result'],
        'error' => $result['error'],
        'datas' => $result['datas'],
        'debug' => $result['debug'],
    ];
}

/**
 * 记录日志
 * 默认是信息等级
 * @param string $message
 * @param string $level
 * @param int $method 1:record 方式 2:write 方式
 * @return void
 * User: czx
 * Date: 2017/12/26 11:41:22
 */
function logRecord($message = '', $level = 'INFO', $method = 1)
{
    if ($method == 1) {
        \Think\Log::record($message, $level);
    } else {
        \Think\Log::write($message, $level);
    }
}

/**
 * 数字格式化
 * @param $number
 * @return double
 * User: hjun
 * Date: 2018-06-18 17:29:53
 * Update: 2018-06-18 17:29:53
 * Version: 1.00
 */
function numberFormat($number)
{
    return round($number, 2);
}

/**
 * 兼容shopnc
 * @param string $name
 * @return Think\Model
 * User: hjun
 * Date: 2018-03-08 11:23:11
 * Update: 2018-03-08 11:23:11
 * Version: 1.00
 */
function Model($name = '')
{
    return M($name);
}

/**
 * 数据XML编码
 * @param mixed $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id 数字索引key转换为的属性名
 * @return string
 */
function dataToXml($data, $item, $id)
{
    $xml = $attr = '';
    foreach ($data as $key => $val) {
        if (is_numeric($key)) {
            $id && $attr = " {$id}=\"{$key}\"";
            $key = $item;
        }
        $xml .= "<{$key}{$attr}>";
        $xml .= (is_array($val) || is_object($val)) ? dataToXml($val, $item, $id) : $val;
        $xml .= "</{$key}>";
    }
    return $xml;
}

/**
 * XML编码
 * @param mixed $data 数据
 * @param string $root 根节点名
 * @param string $item 数字索引的子节点名
 * @param string $attr 根节点属性
 * @param string $id 数字索引子节点key转换的属性名
 * @param string $encoding 数据编码
 * @return string
 */
function xmlEncode($data, $root, $item, $attr, $id, $encoding)
{
    if (is_array($attr)) {
        $array = [];
        foreach ($attr as $key => $value) {
            $array[] = "{$key}=\"{$value}\"";
        }
        $attr = implode(' ', $array);
    }
    $attr = trim($attr);
    $attr = empty($attr) ? '' : " {$attr}";
    $xml = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
    $xml .= "<{$root}{$attr}>";
    $xml .= dataToXml($data, $item, $id);
    $xml .= "</{$root}>";
    return $xml;
}

/**
 * JSON返回未unicode
 * @param $result
 * @return void
 * User: hjun
 * Date: 2018-03-08 18:27:08
 * Update: 2018-03-08 18:27:08
 * Version: 1.00
 */
function json_returndata($result)
{
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    die;
}

/**
 * xml返回
 * @param $result
 * @return void
 * User: hjun
 * Date: 2018-06-18 17:30:59
 * Update: 2018-06-18 17:30:59
 * Version: 1.00
 */
function xml_returndata($result)
{
    $options = [
        // 根节点名
        'root_node' => 'api',
        // 根节点属性
        'root_attr' => '',
        //数字索引的子节点名
        'item_node' => 'item',
        // 数字索引子节点key转换的属性名
        'item_key' => 'id',
        // 数据编码
        'encoding' => 'utf-8',
    ];
    echo xmlEncode($result, $options['root_node'], $options['item_node'], $options['root_attr'], $options['item_key'], $options['encoding']);
    die;
}


/**
 * 获取经纬度之间的距离
 * @param $lat1
 * @param $lng1
 * @param $lat2
 * @param $lng2
 * @return boolean
 * User: hjun
 * Date: 2018-06-18 17:31:10
 * Update: 2018-06-18 17:31:10
 * Version: 1.00
 */
function getDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6367000; //approximate radius of earth in meters
    $lat1 = ($lat1 * pi()) / 180;
    $lng1 = ($lng1 * pi()) / 180;

    $lat2 = ($lat2 * pi()) / 180;
    $lng2 = ($lng2 * pi()) / 180;
    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;

    return round($calculatedDistance);
}

/**
 * 空值转为0
 * @param $value
 * @return mixed
 * User: hjun
 * Date: 2018-06-18 17:31:42
 * Update: 2018-06-18 17:31:42
 * Version: 1.00
 */
function emptyToZero($value)
{
    if (empty($value))
        return 0;
    return $value;
}

/**
 * 空值转为空字符串
 * @param $value
 * @return string
 * User: hjun
 * Date: 2018-06-18 17:32:11
 * Update: 2018-06-18 17:32:11
 * Version: 1.00
 */
function emptyToStr($value)
{
    if (empty($value))
        return '';
    return $value;
}

/**
 * 保留两位小数
 * @param $value
 * @return double
 * User: hjun
 * Date: 2018-06-18 17:32:22
 * Update: 2018-06-18 17:32:22
 * Version: 1.00
 */
function saveTwoDecimal($value)
{
    return round($value, 2);
}

/**
 *
 * @param $value
 * @return double
 * User: hjun
 * Date: 2018-06-18 17:32:31
 * Update: 2018-06-18 17:32:31
 * Version: 1.00
 */
function saveDownDecimal($value)
{
    $threeNum = ($value * 1000) % 10;
    if ($threeNum == 5) {
        $value = floor($value * 100) / 100;
    }
    return $value;
}

/**
 * 获取距离字符串
 * @param $lat1
 * @param $lng1
 * @param $lat2
 * @param $lng2
 * @return string
 * User: hjun
 * Date: 2018-05-25 10:13:12
 * Update: 2018-05-25 10:13:12
 * Version: 1.00
 */
function getDistanceStr($lat1, $lng1, $lat2, $lng2)
{
    $distance = getDistance($lat1, $lng1, $lat2, $lng2);
    if ($distance < 1000) {
        return $distance . 'm';
    } else {
        return round($distance / 1000, 1) . 'km';
    }
}

/**
 * 获取自提点距离用户的距离
 * @param $lat1
 * @param $lng1
 * @param $lat2
 * @param $lng2
 * @return string
 * User: hjun
 * Date: 2018-05-25 11:35:27
 * Update: 2018-05-25 11:35:27
 * Version: 1.00
 */
function getPickupDistance($lat1, $lng1, $lat2, $lng2)
{
    if ((empty($lat1) && empty($lng1)) || (empty($lat2) && empty($lng2))) {
        return '';
    }
    $distanceNum = getDistance($lat1, $lng1, $lat2, $lng2);
    $snm = $distanceNum;
    if ($snm > 3000000) {
        $distanceNum = '';
    } elseif ($snm < 1000) {
        $distanceNum = $distanceNum . "m";
    } elseif ($snm > 1000) {
        $distanceNum = round($distanceNum / 1000, 2) . "km";
    }
    return $distanceNum;
}

/**
 * 获取商城地址的基础路径 https://m.duinin.com
 * @return string
 * User: hjun
 * Date: 2018-05-25 21:13:49
 * Update: 2018-05-25 21:13:49
 * Version: 1.00
 */
function getBaseUrl()
{
    $domain = C('MALL_DOMAIN');
    return C('MALL_SSL') . '://' . $domain;
}

/**
 *
 * @param $item1
 * @param $item2
 * @return string
 * User: hjun
 * Date: 2018-06-27 16:25:40
 * Update: 2018-06-27 16:25:40
 * Version: 1.00
 */
function getStoreItem($item1, $item2)
{
    $data1 = json_decode($item1, true);
    $data2 = json_decode($item2, true);
    if (!empty($data1['type'])) {
        $data['type'] = $data1['type'];
    } else {
        $data['type'] = $data2['type'];
    }

    if (!empty($data1['weburl'])) {
        $data['weburl'] = $data1['weburl'];
    } else {
        $data['weburl'] = $data2['weburl'];
    }

    if (!empty($data1['title'])) {
        $data['title'] = $data1['title'];
    } else {
        $data['title'] = $data2['title'];
    }

    if (!empty($data1['imgurl'])) {
        $data['imgurl'] = $data1['imgurl'];
    } else {
        $data['imgurl'] = $data2['imgurl'];
    }

    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

/**
 * get_store接口查询字段
 * @return array
 * User: hjun
 * Date: 2018-08-17 15:47:19
 * Update: 2018-08-17 15:47:19
 * Version: 1.00
 */
function getStoreFields()
{
    return [
        'store.member_name', 'store.sendmoney', 'store.store_id', 'store.store_name', 'store.isshow_agents_promotion',
        'store.store_company_name', 'store.store_tel', 'store.store_time', 'store.store_end_time',
        'store.is_show_number_type', 'store.store_description', 'store.description', 'store.store_workingtime',
        'store.store_sendtime', 'store.position', 'store.exchange_rule', 'store.prize_people',
        'store.prize_numtimes', 'store.prize_mswitch', 'store.prize_day',
        'store.store_label', 'store.store_address', 'store.longitude', 'store.latitude',
        'store.extra_advertisenum', 'store.store_img', 'cashpay', 'wxpay',
        'alipay', 'store.channel_id', 'store.store_extra', 'store.store_yz',
        'store.auditstate', 'store.channel_type', 'store.store_type', 'store.member_vip',
        'store.order_sharkmoney', 'store.order_sharknum', 'store.distance_info', 'store.register_coupons',
        'store.recommend_coupons', 'store.store_parenttype_id', 'store.store_childtype_id', 'store.register_coupons_info',
        'store.recommend_coupons_info', 'store.signa_info', 'store.unionpay', 'store.from_name',
        'store.mall_name', 'store.balancepay', 'store.storehead_img', 'store.storedisplay_img',
        'store.browsenum', 'store.main_store', 'store.supplier_switch', 'store.shopshark_switch',
        'store.store_domain', 'store.discount_type', 'store.major', 'store.minor',
        'store.uuid', 'store.flagstore', 'store.guide_module', 'store.find_module_url',
        'store.find_module_img', 'store.find_module_name', 'store.map_type', 'store.price_is_hide',
        'store.price_hide_desc', 'store.advertisement', 'store.vip_endtime', 'store.package_store',
        'store.sign_one_day', 'store.sign_two_day', 'store.sign_three_day', 'store.sign_four_day',
        'store.sign_five_day', 'store.sign_shop', 'store.store_pv_hide', 'store.new_goods_index',
        'store.consignee_tag', 'store.consignee_hint',
        'store.classify_tpl', 'store.bindordertel',
        'store.extra_sellernum',
        'mb_storegrade.*',
        'c.currency_id'
    ];
}

/**
 * 获取运费提示数据
 * @param array $cartData
 * @param array $storeInfo
 * @return array
 * User: hjun
 * Date: 2019-05-25 16:18:54
 * Update: 2019-05-25 16:18:54
 * Version: 1.00
 */
function setFreightTipsData(&$cartData = [], $storeInfo = [])
{
    // region 1.1 根据用户与商家的距离找出起送价 longitude latitude
    $distanceData = jsonDecodeToArr($storeInfo['distance_info']);
    $req = getRequest();
    $distance = getDistance($storeInfo['latitude'], $storeInfo['longitude'], $req['lat'], $req['lng']);
    $maxPostage = 0;
    foreach ($distanceData as $value) {
        if ($distance <= $value['distance']) {
            $maxPostage = $value['money'];
            break;
        }
        $maxPostage = $value['money'];
    }
    $maxPostage = round($maxPostage, 2);
    // endregion

    // region 1.2 处理数据
    $storeInfo['postage'] = round($storeInfo['postage'], 2);
    $freightData = [];
    $freightData['is_show'] = 1;
    if ($cartData['totalPrice'] < $maxPostage) {
        $dis = round($maxPostage - $cartData['totalPrice'], 2);
        if ($storeInfo['postage_tag'] == 1) {
            // 1.2.1 未满起送价+运费：还差{{field1.DATA}}免运费{{field2.DATA}}
            $freightData['template'] = L('FREIGHT_DATA_TPL_1'); // 还差{{field1.DATA}}免运费{{field2.DATA}}
            $freightData['field1'] = "{$dis}{$storeInfo['currency_unit']}";
            $freightData['field2'] = "{$storeInfo['postage']}{$storeInfo['currency_unit']}";
            $freightData['is_btn'] = 0;
        } else {
            // 1.2.2 未满起送价+不可下单：还差{{field1.DATA}}就能起送
            $freightData['template'] = L('FREIGHT_DATA_TPL_2'); // 还差{{field1.DATA}}就能起送
            $freightData['field1'] = "{$dis}{$storeInfo['currency_unit']}";
            $freightData['is_btn'] = 1;
            $freightData['btn_text'] = L('FREIGHT_DATA_DIS_1', ['money' => "{$dis}{$storeInfo['currency_unit']}"]); // 还差10元
            if ($cartData['canSettle'] == 1) {
                $cartData['canSettle'] = 0;
                $cartData['settleMsg'] = L('FREIGHT_DATA_DIS_2', ['money' => "{$dis}{$storeInfo['currency_unit']}"]); // "还差{$dis}元才能下单";
            }
        }
    } else {
        if ($storeInfo['postage_tag'] == 1) {
            // 1.2.3 已满起送价+运费：订单已满{{field1.DATA}}，免运费{{field2.DATA}}
            $freightData['template'] = L('FREIGHT_DATA_TPL_3'); // 订单已满{{field1.DATA}}，免运费{{field2.DATA}}
            $freightData['field1'] = "{$maxPostage}{$storeInfo['currency_unit']}";
            $freightData['field2'] = "{$storeInfo['postage']}{$storeInfo['currency_unit']}";
            $freightData['is_btn'] = 0;
        } else {
            // 1.2.4 已满起送价：订单已满足起送价{{field1.DATA}}
            $freightData['template'] = L('FREIGHT_DATA_TPL_4'); // 订单已满足起送价{{field1.DATA}}
            $freightData['field1'] = "{$maxPostage}{$storeInfo['currency_unit']}";
            $freightData['is_btn'] = 0;
        }
    }
    $cartData['freight_data'] = $freightData;
    return $freightData;
}

/**
 * 引入腾讯云万象优图
 * User: hjun
 * Date: 2018-08-20 16:31:01
 * Update: 2018-08-20 16:31:01
 * Version: 1.00
 */
function vendorQCloudImage()
{
    vendor('QcloudImage.Auth');
    vendor('QcloudImage.CIClient');
    vendor('QcloudImage.Conf');
    vendor('QcloudImage.Error');
    vendor('QcloudImage.HttpClient');
}

function getMainStoreId($store_id)
{
    $storeInfo = D('Store')->getStoreInfo($store_id)['data'];
    return $storeInfo['main_store_id'];
    $storeData = M('store')->field('store_id,channel_type,channel_id')
        ->where(array('store_id' => $store_id))
        ->find();
    if (empty($storeData)) {
        return 0;
    }
    if ($storeData['channel_type'] == 2) {
        $mainStoreData = M("store")->field('store_id,channel_type,channel_id')->where(array('channel_id' => $storeData['channel_id'], 'main_store' => 1))->find();
        if (empty($mainStoreData)) {
            return 0;
        } else {
            return $mainStoreData['store_id'];
        }
    } else {
        return $storeData['store_id'];
    }
}

function getStoreParentname($member_id, $store_id)
{
    $data = Model('mb_storemember')
        ->field('recommend_name')
        ->where(array('member_id' => $member_id, 'store_id' => $store_id))
        ->find();
    return $data['recommend_name'];
}

function getRecommendInfo($member_id, $store_id){
    $main_store_id = getMainStoreId($store_id);
    $data = Model('mb_storemember')
        ->where(array('member_id' => $member_id, 'store_id' => $main_store_id))
        ->find();
    if (!empty($data['recommend_id'])) {
        $recommendInfo = D('Member')->getMemberInfo($data['recommend_id'])['data'];
        $data['recommend_nickname'] = empty($recommendInfo['member_nickname']) ? '' : $recommendInfo['member_nickname'];
    }
    return $data;
}


function saveRecommendInfo($member_id, $rm_member_name, $store_id){
    $main_store_id = getMainStoreId($store_id);
    $rmMemberData = M('member')->field('member_id,member_name')->where(array('member_name' => $rm_member_name))->find();
    if (empty($rmMemberData) && !empty($rm_member_name)){
        return getReturn(-1,"暂未找到该推荐人");
    }
    $rmStoreMemberData = Model('mb_storemember')
        ->where(array('member_id' => $rmMemberData['member_id'], 'store_id' => $main_store_id))
        ->find();
    if (empty($rmStoreMemberData) && !empty($rm_member_name)){
        return getReturn(-2,"该推荐人暂未关注该商家");
    }

    if ($rmMemberData['member_id'] == $member_id){
        return getReturn(-2,"该推荐人不能为自己");
    }
    $childrens = getAllchildren($member_id, $main_store_id);
    if (in_array($rmMemberData['member_id'], $childrens)) {
        return getReturn(-2,"该推荐人是你底下的成员，不能成为你的推荐人");

    }
    $max_version =  Model('mb_storemember')->max('version');
    $returnData = Model('mb_storemember')
        ->where(array('member_id' => $member_id, 'store_id' => $main_store_id))
        ->save(array('recommend_id' => $rmMemberData['member_id'], 'recommend_name' => $rmMemberData['member_name'],'version'=>($max_version+1)));
    if ($returnData === false){
        return getReturn(-4,"更新推荐人失败");
    }
    $max_version2 =  M('member')->max('version');
    M('member')->where(array('member_id' => $member_id))
        ->save(array('recommend_id' => $rmMemberData['member_id'], 'recommend_name' => $rmMemberData['member_name'],'version'=>($max_version2+1)));
    S("store_member_relation:{$main_store_id}{$member_id}", null);
    return getReturn(200,"成功");
}


/*获取用户所有的底下会员*/

function getAllchildren($member_id,$store_id)
{
    $m = M('mb_storemember');
    $w = array();
    $w['recommend_id'] = $member_id;
    $w['store_id'] = $store_id;
    $rt = $m->where($w)->field('member_id')->select();
    $result = array();
    $tip = 1;
    while (!empty($rt) && ($tip == 1)) {
        $news = array();
        foreach ($rt as $r) {
            $result[] = $r['member_id'];
            $news[] = $r['member_id'];
        }
        $str = implode(',', $news);
        $where = array();
        $where['recommend_id'] = array('in', $str);
        $where['store_id'] = $store_id;
        $rt = $m->where($where)->field('member_id')->select();
        foreach ($rt as $r2) {
            if (in_array($r2['member_id'], $result)) {
                $tip = 0;
            }
        }
    }
    return $result;
}
// ==================================================    ApiCommon    ==================================================
// ==================================================    ApiCommon    ==================================================
// ==================================================    ApiCommon    ==================================================
// ==================================================    ApiCommon    ==================================================


// ====================================================    Super    ====================================================
// ====================================================    Super    ====================================================
// ====================================================    Super    ====================================================
// ====================================================    Super    ====================================================
/**
 * TODO 兼容Super
 * 获取所有菜单
 * @return array
 * User: hj
 * Date:
 */
function getSuperMenu()
{
    return [
        'menu1' => ['name' => '商家权限管理', 'icon' => 'amicon-store', 'sub_menu' => [
            ['name' => '权限资源', 'act' => 'rightList', 'control' => 'SuperAuth'],
            ['name' => '新增权限', 'act' => 'rightInfo', 'control' => 'SuperAuth'],
            ['name' => '商家权限列表', 'act' => 'roleList', 'control' => 'SuperAuth'],
            ['name' => '新增商家权限', 'act' => 'roleInfo', 'control' => 'SuperAuth'],
        ]],
        /*'menu2' => ['name' => '套餐管理', 'icon' => 'amicon-store', 'sub_menu' => [
            ['name' => '套餐列表', 'act' => 'roleList', 'control' => 'SuperAuth'],
            ['name' => '新增套餐', 'act' => 'roleInfo', 'control' => 'SuperAuth'],
        ]],*/
        'menu2' => ['name' => '商家导航管理', 'icon' => 'amicon-store', 'sub_menu' => [
            ['name' => '跳转方式', 'act' => 'typeList', 'control' => 'LinkType'],
            ['name' => '新增跳转', 'act' => 'typeInfo', 'control' => 'LinkType'],
            ['name' => '商家可选跳转', 'act' => 'typeAuthList', 'control' => 'LinkTypeAuth'],
            ['name' => '新增可选跳转', 'act' => 'typeAuthInfo', 'control' => 'LinkTypeAuth'],
            ['name' => '商家默认导航', 'act' => 'roleInfo', 'control' => 'SuperAuth'],
            ['name' => '新增默认导航', 'act' => 'roleInfo', 'control' => 'SuperAuth'],
        ]],
        ['name' => '语言管理', 'icon' => 'amicon-store', 'sub_menu' => [
            ['name' => '语言列表', 'act' => 'langList', 'control' => 'Lang'],
            ['name' => '新增语言', 'act' => 'langInfo', 'control' => 'Lang'],
        ]],
        ['name' => '货币管理', 'icon' => 'amicon-store', 'sub_menu' => [
            ['name' => '货币列表', 'act' => 'currencyList', 'control' => 'Currency'],
            ['name' => '新增货币', 'act' => 'currencyInfo', 'control' => 'Currency'],
        ]],
        ['name' => '区域管理', 'icon' => 'amicon-store', 'sub_menu' => [
            ['name' => '地区列表', 'act' => 'areaList', 'control' => 'Area'],
        ]],
        'menu16' => ['name' => L('DATA_ANALYSIS') /*数据分析*/, 'icon' => 'fa fa-line-chart', 'sub_menu' => [
            ['name' => L('ANALYSIS_REPORT') /*分析报表*/, 'act' => 'statisticList', 'control' => 'Statistic'],
        ]],
    ];
}

/**
 * 获取权限分组
 * User: hj
 * Date: 2017-09-08 00:09:03
 */
function getAllGroup()
{
    return [
        'system' => '商家管理', 'store' => '店铺管理', 'marketing' => '营销管理', 'goods' => '商品管理', 'activity_goods' => '活动商品',
        'brand' => '品牌管理', 'order' => '订单管理', 'fund' => '资金管理', 'member' => '会员管理', 'find' => '发现管理',
        'notice' => '公告管理', 'ad' => '广告管理', 'staff' => '员工管理', 'coupons' => '优惠券管理',
        'present' => '礼品管理', 'special_topic' => '专题管理', 'recharge' => '充值管理', 'activity' => '活动管理', 'report' => '数据分析',
        'send_msg' => '推送管理', 'livevideo' => '直播管理', 'exchange_code' => '兑换码管理', 'goods_depot' => '仓库管理',
        'form' => '表单管理','cash_register' => '收银机管理'
    ];
}

// ====================================================    Super    ====================================================
// ====================================================    Super    ====================================================
// ====================================================    Super    ====================================================
// ====================================================    Super    ====================================================


// ==================================================    WapCommon    ==================================================
// ==================================================    WapCommon    ==================================================
// ==================================================    WapCommon    ==================================================
// ==================================================    WapCommon    ==================================================
/**
 * 获取日期的下个周期的日期
 * @param int $time 日期时间戳
 * @param int $period 周期 月份数
 * @return int
 * User: hjun
 * Date: 2018-04-22 23:23:36
 * Update: 2018-04-22 23:23:36
 * Version: 1.00
 */
function getNextPeriodDate($time = NOW_TIME, $period = 1)
{
    // 基础判断
    if (empty($period)) return $time;
    $date = date('Y-m-d', $time);
    if (empty($date)) return 0;

    // 用字符串做周期加法,如果超过12个月,则年份要进位
    list($year, $month, $day) = explode('-', $date);
    $func = $period > 0 ? 'floor' : 'ceil';
    $yearDiff = $func($period / 12);
    $nextYear = $year + $yearDiff;
    $nextMonth = $month + $period % 12;
    $nextDay = $day;

    // 如果月数超过了12
    if ($nextMonth > 12) {
        $nextMonth = $nextMonth - 12;
        $nextYear++;
    } elseif ($nextMonth < 0) {
        $nextMonth = $nextMonth + 12;
        $nextYear--;
    }

    // 如果下个周期对应的日期超过了下个周期月份的实际天数 则日期为实际天数
    $nextMonthDays = date('t', strtotime("{$nextYear}-{$nextMonth}"));
    // 如果当前日期为月末 则下个周期对应的日期也要为月末
    $currentMonthDays = date('t', strtotime("{$year}-{$month}"));
    if ($nextMonthDays < $nextDay || $day == $currentMonthDays) {
        $nextDay = $nextMonthDays;
    }
    $nextTime = strtotime("{$nextYear}-{$nextMonth}-{$nextDay}");
    return $nextTime;
}

/**
 * 获取商品下一次的抢购周期
 * @param array $goods
 * @param string $nowTime
 * @return array
 * User: hjun
 * Date: 2019-05-17 17:06:16
 * Update: 2019-05-17 17:06:16
 * Version: 1.00
 */
function getGoodsQGNextTime($goods = [], $nowTime = NOW_TIME)
{
    switch ($goods['qianggou_type']) {
        case 2:
            // 每天
            // 1. 取出每天的 时分秒
            $start = date('His', $goods['qianggou_start_time']);
            $end = date('His', $goods['qianggou_end_time']);
            // 2. 取今天的日期
            $day = date('Ymd', $nowTime);
            $endTime = strtotime("{$day}{$end}");
            // 3. 如果今天的结束时间都已经过期了  则更新为明天的时间
            if ($endTime <= $nowTime) {
                $day = date("Ymd", $nowTime + 3600 * 24);
            }
            $startDate = "{$day}{$start}";
            $endDate = "{$day}{$end}";
            break;
        case 3:
            // 每周
            $start = date('His', $goods['qianggou_start_time']);
            $end = date('His', $goods['qianggou_end_time']);
            // 获取开始是星期几 结束是星期几
            $startDW = $goods['start_dw'];
            $endDW = $goods['end_dw'];
            // 今天星期几 根据今天星期几计算出开始和结束时间
            $todayDW = date('w', $nowTime);
            $todayDW = $todayDW == 0 ? 7 : $todayDW;
            $startDay = date('Ymd', $nowTime + ($startDW - $todayDW) * 3600 * 24);
            $endDay = date('Ymd', $nowTime + ($endDW - $todayDW) * 3600 * 24);
            $endTime = strtotime("{$endDay}{$end}");
            // 如果过期了 则更新为下周
            if ($endTime <= $nowTime) {
                $startDay = date('Ymd', ($nowTime + 3600 * 24 * 7) + ($startDW - $todayDW) * 3600 * 24);
                $endDay = date('Ymd', ($nowTime + 3600 * 24 * 7) + ($endDW - $todayDW) * 3600 * 24);
            }
            $startDate = "{$startDay}{$start}";
            $endDate = "{$endDay}{$end}";
            break;
        case 4:
            // 每月
            // 1. 取 日时分秒
            $start = date('His', $goods['qianggou_start_time']);
            $end = date('His', $goods['qianggou_end_time']);
            $startDay = $goods['start_dw'];
            $endDay = $goods['end_dw'];
            // 2. 取 本月 判断本月天数是否足够
            $monthDays = date('t', $nowTime);
            if ($endDay > $monthDays) {
                $startDay = $monthDays - ($endDay - $startDay);
                $endDay = $monthDays;
            }
            $month = date('Ym', $nowTime);
            $endTime = strtotime("{$month}{$endDay}{$end}");
            // 3. 如果结束时间都已经过期了  则更新为下个月
            if ($endTime <= $nowTime) {
                $nextTime = getNextPeriodDate($nowTime);
                $monthDays = date('t', $nextTime);
                if ($endDay > $monthDays) {
                    $startDay = $monthDays - ($endDay - $startDay);
                    $endDay = $monthDays;
                }
                $month = date("Ym", $nextTime);
            }
            $startDate = "{$month}{$startDay}{$start}";
            $endDate = "{$month}{$endDay}{$end}";
            break;
        default:
            return ['start' => 0, 'end' => 0];
            break;
    }
    return ['start' => strtotime($startDate), 'end' => strtotime($endDate)];
}


/**
 *
 * 产生随机字符串，不长于32位
 * @param int $length 随机字符串的长度
 * @return string 产生的随机字符串
 */
function getNonceStr($length = 32)
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

/**
 * 将数组格式化为 key1=value1&key2=value2 url参数
 * @param array $param
 * @return string
 */
function toUrlParam($param = array())
{
    $str = '';
    // 组成 key=value 的形式
    foreach ($param as $key => $value) {
        if ($key != "sign" && $key != "" && !is_array($key)) {
            $str .= "$key=$value&";
        }
    }
    // 去除首尾的 & 符号
    return trim($str, "&");
}

/**
 * 根据参数生成签名
 * @param array $param
 * @return string
 */
function makeSign($param = array())
{
    //签名步骤一：按字典序排序参数
    ksort($param);
    $string = toUrlParam($param);
    //签名步骤二：在string后加入KEY
    $string = $string . "&key=" . C("new_api_key");
    //签名步骤三：MD5加密
    $string = md5($string);
    //签名步骤四：所有字符转为大写
    $result = strtoupper($string);
    return $result;
}

/**
 * 设置参数的签名
 * @param array $param
 * @return string
 */
function setSign(&$param = array())
{
    $sign = makeSign($param);
    $param['sign'] = $sign;
    return $sign;
}

/**
 * 截取文本
 * @param $string
 * @param $sublen
 * @return string
 * User: hjun
 * Date: 2018-06-18 17:46:14
 * Update: 2018-06-18 17:46:14
 * Version: 1.00
 */
function cutstr_html($string, $sublen)
{
    $string = strip_tags($string);
    $string = preg_replace('/\n/is', '', $string);
    $string = preg_replace('/\r/is', '', $string);
    $string = preg_replace('/\r\n/is', '', $string);
    $string = preg_replace('/\n\r/is', '', $string);
    $string = preg_replace('/ |　/is', '', $string);
    $string = preg_replace('/&nbsp;/is', '', $string);
    preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $t_string);
    if (!empty($sublen)) {
        if (count($t_string[0]) - 0 > $sublen) $string = join('', array_slice($t_string[0], 0, $sublen)) . "…";
        else $string = join('', array_slice($t_string[0], 0, $sublen));
    }
    return $string;
}

/**
 * 请求接口
 * @param string $act 控制器
 * @param string $op 操作
 * @param array $params 参数
 * @param bool $needToken 是否需要登录
 * @param bool $checkToken 是否检查是否登录
 * @param string $dataPosition 参数位置
 * @param string $module 请求接口的模块
 * @return string ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: hjun
 * Date: 2018-03-15 14:42:24
 * Update: 2018-03-15 14:42:24
 * Version: 1.00
 */
function requestApi($act = '', $op = '', $params = [], $needToken = true, $checkToken = true, $dataPosition = '', $module = '')
{
    if (empty($act) || empty($op)) return json_encode(getReturn(-1, '接口参数错误'), 256);
    if ($needToken) {
        $memberId = session('member_id');
        if (empty($memberId) && $checkToken) return json_encode(getReturn(-100, '会话已过期,请重新登录'), 256);
        if (!empty($memberId)) {
            $where = [];
            $where['member_id'] = $memberId;
            $token = M('mb_user_token')->where($where)->getField('token');
            if (empty($token)) {
                return json_encode(getReturn(-100, '会话已过期,请重新登录'), 256);
            }
            $params['key'] = $token;
        }
    }
    $sign = [];
    $sign['nonceStr'] = getNonceStr(16);
    $sign['timeStamp'] = NOW_TIME;
    setSign($sign);
    // 增加经纬度
    $params['lng'] = getLng();
    $params['lat'] = getLat();
    // 增加会话标识
    $params['session_id'] = session_id();
    $params = array_merge($params, $sign);
    $baseUrl = C('new_api_url');
    if (empty($module)) {
        $module = strtolower(MODULE_NAME);
    }
    $url = "{$baseUrl}?act={$act}&op={$op}&request_from=mall&module={$module}&localizable=" . LANG_SET;
    $header = [];
    // hjun 2018-03-14 10:13:49 参数位置在body则用JSON传输
    if ($dataPosition === 'body') {
        $params = json_encode($params, 256);
        $header[] = 'Content-Type:application/json;charset=utf-8;';
    }
    $result = httpRequest($url, 'post', $params, $header);
    if ($result['code'] !== 200) return json_encode(getReturn(-1, 'access api failed'));
    return ($result['data']);
}

/**
 * 获取经度
 * @return double
 * User: hjun
 * Date: 2018-06-18 17:47:13
 * Update: 2018-06-18 17:47:13
 * Version: 1.00
 */
function getLng()
{
    return cookie('longitude') > 0 ? cookie('longitude') : 0;
}

/**
 * 获取纬度
 * @return double
 * User: hjun
 * Date: 2018-06-18 17:47:18
 * Update: 2018-06-18 17:47:18
 * Version: 1.00
 */
function getLat()
{
    return cookie('latitude') > 0 ? cookie('latitude') : 0;
}

// ==================================================    WapCommon    ==================================================
// ==================================================    WapCommon    ==================================================
// ==================================================    WapCommon    ==================================================
// ==================================================    WapCommon    ==================================================


// =====================================================    Wap    =====================================================
// =====================================================    Wap    =====================================================
// =====================================================    Wap    =====================================================
// =====================================================    Wap    =====================================================
/**
 * @param int $type 跳转方式
 * @param string $linkParam 跳转参数
 * @param array $storeInfo 商家信息 必须有 ['main_store'=>1, 'store_id'=>1]
 * @param int $location 1-顶部广告||中间通告广告 2-顶部导航
 * @return string ['code'=>200,'msg'=>'','data'=>[]]
 * User: hj
 * Date: 2017-10-16 10:11:37
 * Desc: 获取跳转方式的链接
 * Update: 2017-10-16 10:11:39
 * Version: 1.0
 */
function getLinkTypeUrl($type = 0, $linkParam = '', $storeInfo = [], $location = 1)
{
    $storeId = (int)$storeInfo['store_id'];
    return \Common\Model\UtilModel::getLinkTypeUrl($type, $linkParam, $storeId);
}

/**
 * 获取搜索条件
 * @param string $keyword
 * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: hjun
 * Date: 2018-01-12 10:18:51
 * Update: 2018-01-12 10:18:51
 * Version: 1.00
 */
function getSearchArr($keyword = '')
{
    if (empty($keyword)) {
        return ['like', '%%'];
    }
    $arr = explode(' ', $keyword);
    if (count($arr) <= 1) {
        return ['like', "%{$keyword}%"];
    } else {
        $where = [];
        foreach ($arr as $key => $value) {
            $item = ['like', "%{$value}%"];
            $where[] = $item;
        }
        $where[] = ['and'];
        return $where;
    }
}

// =====================================================    Wap    =====================================================
// =====================================================    Wap    =====================================================
// =====================================================    Wap    =====================================================
// =====================================================    Wap    =====================================================


// ====================================================    Core    ====================================================
// ====================================================    Core    ====================================================
// ====================================================    Core    ====================================================
// ====================================================    Core    ====================================================
/**
 * 记录
 * @param array $params
 * @return void
 * User: lwz
 * Date: 2018-06-19 09:50:06
 * Update: 2018-06-19 09:50:06
 * Version: 1.00
 */
function DataRecoed($params = array())
{
    $connect = isFormal() ?
        'mysql://duininjiameng:mysUL5bX@118.89.53.225:3306/qqcg_jm_db' :
        'mysql://www:vjudiancMcas6XZrO1JHmQz@121.41.35.56:3306/qqcg_jmtest_db';
    $log = M('data_record', 'lm_', $connect);
    /*浏览器信息*/
    $browser = get_user_browser();
    /*来源和访问路径*/
    /* 来源 */
    if (!empty($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER']) > 9) {
        $domain = $_SERVER['HTTP_REFERER'];
    } else {
        $domain = '';
    }
    if (checkWeixin()) {
        $origin = '微信';
    } else {
        $origin = 'PC';
    }
    if (checkMobile()) {
        $device = '移动设备';
    } else {
        $device = '计算机';
    }

    /*访问时长*/
    $timeinfo = json_decode(session('timeinfo'), true);
    if (!empty($timeinfo)) {
        $log_id = $timeinfo['log_id'];
        $log_time = $timeinfo['log_time'];
        $keep_time = time() - $log_time;
        $log->where(array('log_id' => $log_id))->save(array('keep_time' => $keep_time));
    }
    $ip = getTrueClientIp();
    $area = GetIpLookup($ip);
    $city = $area['city'];
    $params['browser'] = $browser;
    $params['device'] = $device;
    $params['origin'] = $origin;
    $params['ip'] = $ip;
    $params['ip_city'] = $city;
    $params['view_url'] = getViewUrl();
    $params['domain_url'] = $domain;
    $params['addtime'] = time();
    $id = $log->add($params);
    $timeinfo = array();
    $timeinfo['log_id'] = $id;
    $timeinfo['log_time'] = time();
    session('timeinfo', json_encode($timeinfo));
}

/**
 * 获取当前页面完整URL地址
 */
function getViewUrl()
{
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
    $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
    return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
}

/**
 * 检验是否是微信
 * @return boolean
 * User: hjun
 * Date: 2018-06-19 09:50:44
 * Update: 2018-06-19 09:50:44
 * Version: 1.00
 */
function checkWeixin()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    }
    return false;
}

/**
 * 检查是否是手机
 * @return boolean
 * User: hjun
 * Date: 2018-06-19 09:50:53
 * Update: 2018-06-19 09:50:53
 * Version: 1.00
 */
function checkMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
        return true;
    //此条摘自TPM智能切换模板引擎，适合TPM开发
    if (isset ($_SERVER['HTTP_CLIENT']) && 'PhoneClient' == $_SERVER['HTTP_CLIENT'])
        return true;
    //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA']))
        //找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
    //判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
        );
        //从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    //协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}

/**
 * 获得浏览器名称和版本
 * @access  public
 * @return  string
 */
function get_user_browser()
{
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        return '';
    }
    $agent = $_SERVER['HTTP_USER_AGENT'];
    $browser = '';
    $browser_ver = '';
    if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs)) {
        $browser = 'Internet Explorer';
        $browser_ver = $regs[1];
    } elseif (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs)) {
        $browser = 'FireFox';
        $browser_ver = $regs[1];
    } elseif (preg_match('/Maxthon/i', $agent, $regs)) {
        $browser = '(Internet Explorer ' . $browser_ver . ') Maxthon';
        $browser_ver = '';
    } elseif (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs)) {
        $browser = 'Opera';
        $browser_ver = $regs[1];
    } elseif (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $agent, $regs)) {
        $browser = 'OmniWeb';
        $browser_ver = $regs[2];
    } elseif (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs)) {
        $browser = 'Netscape';
        $browser_ver = $regs[2];
    } elseif (preg_match('/safari\/([^\s]+)/i', $agent, $regs)) {
        $browser = 'Safari';
        $browser_ver = $regs[1];
    } elseif (preg_match('/NetCaptor\s([^\s|;]+)/i', $agent, $regs)) {
        $browser = '(Internet Explorer ' . $browser_ver . ') NetCaptor';
        $browser_ver = $regs[1];
    } elseif (preg_match('/Lynx\/([^\s]+)/i', $agent, $regs)) {
        $browser = 'Lynx';
        $browser_ver = $regs[1];
    }
    if (!empty($browser)) {
        return addslashes($browser . ' ' . $browser_ver);
    } else {
        return 'Unknow browser';
    }
}

/**
 * 通过IP获取城市
 * @param string $ip
 * @return mixed
 * User: hjun
 * Date: 2018-06-19 09:51:12
 * Update: 2018-06-19 09:51:12
 * Version: 1.00
 */
function GetIpLookup($ip = '')
{
    if (empty($ip)) {
        return;
    } else {
        $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
        if (empty($res)) {
            return false;
        }
        $jsonMatches = array();
        preg_match('#\{.+?\}#', $res, $jsonMatches);
        if (!isset($jsonMatches[0])) {
            return false;
        }
        $json = json_decode($jsonMatches[0], true);
        if (isset($json['ret']) && $json['ret'] == 1) {
            $json['ip'] = $ip;
            unset($json['ret']);
        } else {
            return false;
        }
        return $json;
    }
}

/**
 * 获取URL地址
 * @param string $uri 网址参数，如/index.php?xx...;
 * @param string $domain 指定域名，可选
 * @return string
 * User: yzx
 * Date: 2018-06-19 09:51:34
 * Update: 2018-06-19 09:51:34
 * Version: 1.00
 */
function URL($uri = '', $domain = '')
{
    if (empty($domain)) {
        $domain = $_SERVER["HTTP_HOST"];
    }

    if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) {
        return 'https://' . $domain . $uri;
    } else {
        return 'http://' . $domain . $uri;
    }
}

/**
 * 验证配送范围
 * @param int $storeId
 * @param array $addressData
 * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: hjun
 * Date: 2019-06-07 13:44:53
 * Update: 2019-06-07 13:44:53
 * Version: 1.00
 */
function validateDeliveryArea($storeId = 0, $addressData = [])
{
    $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
    if ($storeInfo['main_store_id'] != $storeId) {
        $storeInfo = D('Store')->getStoreInfo($storeInfo['main_store_id'])['data'];
    }
    if ($storeInfo['delivery_area_ctrl'] == 1) {
        if (empty($addressData['delivery_area_id'])) {
            return getReturn(CODE_ERROR, "收货地址未在商家配送范围,请添加新地址");
        }
        $ids = explode(',', $storeInfo['delivery_area_ids']);
        if (!in_array($addressData['delivery_area_id'], $ids)) {
            return getReturn(CODE_ERROR, "收货地址未在商家配送范围,请添加新地址");
        }
    }
    return getReturn(CODE_SUCCESS, "");
}

/**
 * 判断结果是否成功
 * @param $result
 * @return boolean
 * User: hjun
 * Date: 2018-06-19 09:51:57
 * Update: 2018-06-19 09:51:57
 * Version: 1.00
 */
function isSuccess($result)
{
    return $result['code'] === CODE_SUCCESS;
}

/**
 * 判断自提是否开启
 * @param int $storeId
 * @return boolean
 * User: hjun
 * Date: 2019-02-21 10:28:16
 * Update: 2019-02-21 10:28:16
 * Version: 1.00
 */
function pickupIsOpen($storeId = 0)
{
    $model = D('Store');
    $storeInfo = $model->getStoreInfo($storeId)['data'];
    $storeGrade = $model->getStoreGrantInfo($storeId)['data'];
    if ($storeGrade['pickup_ctrl'] == 1 && $storeInfo['pickup_ctrl'] == 1) {
        return true;
    }
    return false;
}

/**
 * 判断门店销售类型是否是独立
 * @param array $storeInfo
 * @return boolean
 * User: hjun
 * Date: 2018-11-12 09:57:03
 * Update: 2018-11-12 09:57:03
 * Version: 1.00
 */
function pickupIsAlone($storeInfo = [])
{
    return $storeInfo['pickup_sale_type'] == 1;
}

/**
 * 判断门店销售类型是否是商城为主
 * @param array $storeInfo
 * @return boolean
 * User: hjun
 * Date: 2018-11-12 09:57:15
 * Update: 2018-11-12 09:57:15
 * Version: 1.00
 */
function pickupIsMall($storeInfo = [])
{
    return !pickupIsAlone($storeInfo);
}

/**
 * 判断是否是商城
 * @param $storeType
 * @return boolean
 * User: hjun
 * Date: 2018-06-19 09:52:07
 * Update: 2018-06-19 09:52:07
 * Version: 1.00
 */
function isMall($storeType)
{
    return $storeType == 0 || $storeType == 2;
}

/**
 * 判断是否是子店
 * @param $storeType
 * @return boolean
 * User: hjun
 * Date: 2018-07-30 12:03:42
 * Update: 2018-07-30 12:03:42
 * Version: 1.00
 */
function isChildMall($storeType)
{
    return $storeType == 1 || $storeType == 3;
}

/**
 * 判断是否是独立店
 * @param $storeType
 * @return boolean
 * User: hjun
 * Date: 2018-07-30 12:03:51
 * Update: 2018-07-30 12:03:51
 * Version: 1.00
 */
function isAloneMall($storeType)
{
    return $storeType == 4 || $storeType == 5;
}

/**
 * 判断是否是测试环境
 * @return boolean
 * User: hjun
 * Date: 2018-06-19 09:52:22
 * Update: 2018-06-19 09:52:22
 * Version: 1.00
 */
function isTest()
{
    $test = ['dev', 'home_dev'];
    return in_array(MODE, $test);
}

/**
 * 判断是否是正式环境
 * @return boolean
 * User: hjun
 * Date: 2018-06-19 09:52:33
 * Update: 2018-06-19 09:52:33
 * Version: 1.00
 */
function isFormal()
{
    $common = ['common', 'home_common'];
    return in_array(MODE, $common);
}

/**
 * 不需要验证密码
 * @return boolean
 * User: hjun
 * Date: 2018-10-20 17:59:15
 * Update: 2018-10-20 17:59:15
 * Version: 1.00
 */
function isNeedPassword()
{
    $common = ['common'];
    return in_array(MODE, $common);
}

/**
 * 是否开启了系统代收
 * @param int $storeId
 * @return boolean
 * User: hjun
 * Date: 2018-07-30 12:07:28
 * Update: 2018-07-30 12:07:28
 * Version: 1.00
 */
function isOpenSystemCollection($storeId = 0)
{
    $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
    $storeGrant = D('StoreGrade')->getStoreGrantInfo($storeId)['data'];
    // 如果开启了系统代收
    if ($storeGrant['sys_collection'] == 1) {
        // 如果是子店 未开启子店收款 则是系统收款
        if (isChildMall($storeInfo['store_type'])) {
            if ($storeGrant['sub_shop_receipt_switch'] == 0) {
                return true;
            }
        }
        // 如果有一个是自己收款 则就算未开启系统代收
        $collectType = S("collect_type_{$storeId}");
        if (empty($collectType)) {
            $where = [];
            $where['store_id'] = $storeId;
            $where['payment_type'] = 2;
            $options = [];
            $options['where'] = $where;
            $options['field'] = 'id';
            $info = M('mb_pay_switch')->selectRow($options);
            $collectType = empty($info) ? 'system' : 'self';
            S("collect_type_{$storeId}", $collectType, 20 * 60);
        }
        if ($collectType === 'system') {
            return true;
        }
    }
    return false;
}

/**
 * 获取请求参数
 * @param string $type
 * @return array
 * User: hjun
 * Date: 2018-05-25 10:23:33
 * Update: 2018-05-25 10:23:33
 * Version: 1.00
 */
function getRequest($type = 'all')
{
    $type = strtolower($type);
    if ($type === 'get') {
        $req = I('get.');
    } else {
        $reqPost = I('post.');
        $reqBody = I('put.');
        if ($type === 'post') {
            $req = array_merge($reqPost, $reqBody);
        } else {
            $reqGet = I('get.');
            $req = array_merge($reqGet, $reqPost);
            $req = array_merge($req, $reqBody);
        }
    }
    return empty($req) ? [] : $req;
}

// ====================================================    Core    ====================================================
// ====================================================    Core    ====================================================
// ====================================================    Core    ====================================================
// ====================================================    Core    ====================================================
/**
 * 获取IN查询条件
 * @param array $arr
 * @return mixed
 * User: hjun
 * Date: 2018-08-15 17:15:56
 * Update: 2018-08-15 17:15:56
 * Version: 1.00
 */
function getInSearchWhereByArr($arr = [])
{
    if (empty($arr)) {
        return ['exp', 'IS NULL'];
    } elseif (count($arr) === 1) {
        return is_numeric($arr[0]) && strpos($arr[0], '.') === false ? (int)$arr[0] : $arr[0];
    } else {
        return ['in', implode(',', $arr)];
    }
}

/**
 * 判断是否在某个模块中
 * @param string $target 目标模块
 * @param string $moduleName 当前模块名 可选
 * @return boolean
 * User: hjun
 * Date: 2018-06-20 16:31:31
 * Update: 2018-06-20 16:31:31
 * Version: 1.00
 */
function isInModule($target = '', $moduleName = '')
{
    if (empty($moduleName)) {
        $moduleName = MODULE_NAME;
    }
    return strtolower($moduleName) === strtolower($target);
}

/**
 * 判断是否在后台模块
 * @return boolean
 * User: hjun
 * Date: 2018-06-20 16:35:42
 * Update: 2018-06-20 16:35:42
 * Version: 1.00
 */
function isInAdmin()
{
    return isInModule(MODULE_ADMIN);
}

/**
 * 判断是否在接口模块
 * @return boolean
 * User: hjun
 * Date: 2018-06-20 16:35:55
 * Update: 2018-06-20 16:35:55
 * Version: 1.00
 */
function isInApi()
{
    return isInModule(MODULE_API);
}

/**
 * 判断是否在总后台模块
 * @return boolean
 * User: hjun
 * Date: 2018-06-20 16:36:12
 * Update: 2018-06-20 16:36:12
 * Version: 1.00
 */
function isInSuper()
{
    return isInModule(MODULE_SUPER);
}

/**
 * 判断是否在微信模块
 * @return boolean
 * User: hjun
 * Date: 2018-06-20 16:55:15
 * Update: 2018-06-20 16:55:15
 * Version: 1.00
 */
function isInWap()
{
    return isInModule(MODULE_WAP);
}

/**
 * 判断是否在PC商城模块
 * @return boolean
 * User: hjun
 * Date: 2018-06-20 16:55:27
 * Update: 2018-06-20 16:55:27
 * Version: 1.00
 */
function isInWeb()
{
    return isInModule(MODULE_WEB);
}

/**
 * 过滤不可见字符
 * @param $str
 * @return string
 * User: hjun
 * Date: 2018-07-31 10:42:22
 * Update: 2018-07-31 10:42:22
 * Version: 1.00
 */
function filterNonPrintableChar($str)
{
    $i = 0;
    $newStr = '';
    while (isset($str[$i])) {
        $char = $str[$i];
        $asc = ord($char);
        if ($asc > 31 && $asc < 127) {
            $newStr .= $char;
        }
        $i++;
    }
    return $newStr;
}


/**
 * 判断是否是商品分类模版的菜单
 * @param string $ctrl
 * @param string $act
 * @return boolean
 * User: hjun
 * Date: 2018-06-22 14:55:30
 * Update: 2018-06-22 14:55:30
 * Version: 1.00
 */
function isGoodsClassTemplateMenu($ctrl = '', $act = '')
{
    return strtolower($ctrl) === 'goodsclass' && strtolower($act) === 'classifytemplate';
}

/*获取店铺域名*/
function getStoreDomain($store_id = 0)
{
    $ssl = C('MALL_SSL');
    $domain = C('MALL_DOMAIN');
    $storeInfo = D('Store')->getStoreInfo($store_id)['data'];
    $hasMiniProgram = $storeInfo['has_mini_program'] == 1;
    if (!empty($storeInfo['store_domain'])) {
        $domain = $storeInfo['store_domain'];
        // 如果没有小程序 又是自己的公众号 为了防止没有启用SSL 则要使用http协议
        if (!$hasMiniProgram) {
            $ssl = 'http';
        }
    }
    return "{$ssl}://{$domain}";
}

function flowExchange($flow_num)
{
    if (empty($flow_num)) {
        $flow_num = 0;
    }
    if($flow_num > 0){
        if ($flow_num > 1024 * 1024) {
            $flow_num = round($flow_num / 1024 / 1024, 2) . "GB";
        } else if ($flow_num > 1024) {
            $flow_num = round($flow_num / 1024, 2) . "MB";
        } else {
            $flow_num = $flow_num . "KB";
        }
    }else{
        if ($flow_num < -1 * 1024 * 1024) {
            $flow_num = round($flow_num / 1024 / 1024, 2) . "GB";
        } else if ($flow_num  < -1 * 1024) {
            $flow_num = round($flow_num / 1024, 2) . "MB";
        } else {
            $flow_num = $flow_num . "KB";
        }
    }

    return $flow_num;
}

/**获取不同版本的收货人tag和hint信息**/
function getStoreConsignee($grade)
{
    $consignee = array();
    $consignee['consignee_tag'] = '收货人';
    $consignee['consignee_hint'] = '填写收货人姓名或单位';
    switch ($grade) {
        case 1:
            break;
        case 2:
            break;
        case 3:
            break;
        case 4:
            $consignee['consignee_tag'] = '收货人';
            $consignee['consignee_hint'] = '填写收商家名或收货人姓名';
            break;
        case 5:
            break;
        case 6:
            break;
        case 7:
            break;
    }
    return $consignee;
}

function sendTxMessage($fromAccount, $toAccount, $msgContent)
{
    //$usersig = sig($fromAccount, "");
    $usersig = TXIM_ADMIN_SIG;
    //file_put_contents("cbnotifytttttt444.txt", $usersig);
    $sdkappid = TXIM_APPID;
    $url = "https://console.tim.qq.com/v4/openim/sendmsg?usersig=" .
        $usersig . "&identifier=admin&sdkappid=" . $sdkappid . "&random=" . time() . "&contenttype=json";
    /*
     *  "SyncOtherMachine": 1, //消息同步至发送方
  "From_Account": "lumotuwe1",
  "To_Account": "lumotuwe2",
  "MsgRandom": 1287657,
  "MsgTimeStamp": 5454457,
  "MsgBody": [
      {
          "MsgType": "TIMTextElem",
          "MsgContent": {
              "Text": "hi, beauty"
          }
      }
  ]
     */

    $msg = array();
    $oneMsg = array();
    $oneMsg['MsgType'] = "TIMTextElem";
    $oneMsg['MsgContent'] = array('Text' => $msgContent);
    $msg[] = $oneMsg;

    $offlinePushInfo = array();
    $offlinePushInfo['PushFlag'] = 0;
    $offlinePushInfo['Desc'] = json_decode(json_decode($msgContent, true)['external_data'], true)['order_desc'];
    $params = [];
    $params['SyncOtherMachine'] = 1;
    $params['From_Account'] = $fromAccount;
    $params['To_Account'] = $toAccount;
    $params['MsgRandom'] = time();
    $params['MsgTimeStamp'] = time();
    $params['MsgBody'] = $msg;
    $params['OfflinePushInfo'] = $offlinePushInfo;
    $log_params = $params;
    $params = json_encode($params, 256);

    $header['Content-Type'] = 'application/json;charset=utf-8;';
    $returnData = httpRequest($url, 'post', $params, $header);
    $log_str = "[消息推送] fromAccount:".$fromAccount."  toAccount:".$toAccount."\n".
        " result:".json_encode($returnData)."\n"." params:".json_encode($log_params,true)."\n".$url;
    \Think\Log::write_now($log_str, \Think\Log::INFO, "", PUSH_LOGS_PATH);
    $returnData = json_decode($returnData['data'], true);
    return $returnData;
//        if ($returnData['ActionStatus'] == 'OK'){
//             die("ok");
//        }else{
//            die(dump($returnData));
//        }
}

function changeBalance($sid, $mid, $tid, $money, $type_name, $ps, $orderid, $member_name)
{

    if (abs($money) < 0.01) {
        $data = array();
        return $data;
    }

    $mb_balancerecord = Model("mb_balancerecord");

    $max_version = $mb_balancerecord->max('version');

    $data = array(
        'sid' => $sid,
        'mid' => $mid,
        'tid' => $tid,
        'money' => $money,
        'type_name' => $type_name,
        'ps' => $ps,
        'orderid' => $orderid,
        'member_name' => $member_name,
        'version' => $max_version + 1,
        'create_time' => TIMESTAMP
    );


    $member = Model('mb_storemember');
    $sumscores = $member->field('balance')->where(array(
        'member_id' => $mid,
        'store_id' => $sid
    ))->find();
    $sum_balance = $sumscores['balance'] + $money;

    if ($sum_balance < 0) {
        output_error(-5, '余额不足', '积分项插入数据库失败');
    }
    $log_str = "[操作余额]  接口MobileBaseController->changeBalance:member_id" .$mid." store_id".$sid."\n" .
        "[数据]old:" . $sumscores['balance']." new:".$sum_balance."\n".
        "[record]".json_encode($data);
    balanceLogs($log_str);
    $mversion = $member->max('version');
    $flag = $member->where(array(
        'member_id' => $mid,
        'store_id' => $sid
    ))->save(array(
        'balance' => $sum_balance,
        'version' => $mversion + 1
    ));
    if (!$flag) {
        output_error(-4, $mid . '修改余额失败' . $sid, '更新会员总余额失败');
    }

    $id = $mb_balancerecord->add($data);
    if (!$id) {
        output_error(-3, $mid . '修改余额失败' . $sid, '余额项插入数据库失败');
    }

    $data['id'] = $id;
    $data['balance'] = $sum_balance;
    return $data;
}

function check_thirdpart($store_id = '')
{

    $store = Model('store');

    $thirdpart = array();

    if (!empty($store_id)) {

        $store_info = $store->where(array('store_id' => $store_id))->field('channel_type,channel_id,main_store')->find();

        if (($store_info['channel_type'] == 2) && ($store_info['channel_id'] > 0) && ($store_info['main_store'] != 1)) {

            $w = array();

            $w['channel_id'] = $store_info['channel_id'];

            $w['main_store'] = 1;

            $stores = $store->where($w)->field('store_id')->find();
            $store_id = $stores['store_id'];

        }

        $thirdpart = Model('mb_thirdpart_money')->where(array('store_id' => $store_id, 'status' => 1))->find();

    }

    return $thirdpart;

}

function fromXml($xml)
{
    if (!$xml) {
        throw new WxPayException("xml数据异常！");
    }
    //将XML转为array
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

function sendSysOrderMsg($order, $user, $type)
{

    $tousers = "";
    $isadmin = "";
    $seller = M('seller')->where(array('store_id' => $order['storeid']))->select();;
    for ($i = 0; $i < count($seller); $i++) {
        if ($seller[$i]['isserver'] == 1 && $seller[$i]['allow_order'] == 1) {
//            $tousers = $tousers . $seller[$i]['seller_name'] . ";";
            $isadmin = $seller[$i]['seller_name'];
            if ($type == 0) {
                $msgdesc = date("Y-m-d H:i") . " 买家有提交新的订单，请快去订单里查看，订单号：" . $order["order_id"];
            } else if ($type == 1) {
                $msgdesc = date("Y-m-d H:i") . " 买家因其它原因已退单，订单号：" . $order["order_id"];
            }

            $external_data = array("order_id" => $order["order_id"], "order_state" => $order["order_state"], "order_reason" => $order["close_reason"], "order_desc" => $msgdesc);

            sendSysMsg($user, 6, json_encode($external_data), $isadmin, $order['storeid']);

        }
//        if ($seller[$i]['is_admin'] == 1) {
//            $isadmin = $seller[$i]['seller_name'];
//        }
    }
//    $tousers = rtrim($tousers, ";");
//    if (empty($tousers)) {
//        $tousers = $isadmin;
//    }



}

function sendSysMsg($user, $msgtype, $external_data, $isadmin, $store_id)
{


    $store = M('store')->field('store_name')->where(array('store_id' => $store_id))->find();

    if (empty($user["member_nickname"])) {
        $sender_name = $user["member_nickname"];
    } else {
        $sender_name = $user["member_name"];
    }

    $body = array(
        "to" => $isadmin,
        "from" => $user["member_name"],
        "packetId" => "wb-" . create_unique(),
        "remote_url" => "",
        "file_size" => 0,
        "thumb_url" => "",
        "store_id" => $store_id,
        "sender_type" => "buyer",
        "sender_name" => $sender_name,
        "voice_url" => "",
        "type" => $msgtype,
        "store_name" => $store["store_name"],
        "voice_len" => 0
    );
    if ($msgtype == 6) {
        $body["text"] = "[订单]";
        $body["external_data"] = $external_data;
    } else if ($msgtype == 7) {
        $body["text"] = "[礼品兑换]";
        $body["external_data"] = $external_data;
    } else if ($msgtype == 20) {
        $body["text"] = "[直接付款]";
        $body["external_data"] = $external_data;
    }

//        $param = array("type" => "chat", "username" => $user["member_name"], "password" => $user["member_passwd"], "tousers" => $tousers, "body" => json_encode($body, JSON_UNESCAPED_UNICODE));
//        $url = C('CHAT_URL');
//        if (empty($url)){
//            return false;
//        }
//
//        return $this->request_post($url, $param);

    return sendTxMessage($user["member_name"], $isadmin, json_encode($body, JSON_UNESCAPED_UNICODE));
}


function create_unique()
{
    $data = $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']
        . time() . rand();
    return sha1($data);
    //return md5(time().$data);
    //return $data;
}


function sendOrderMsg($order_id, $type)
{


    $order = M('mb_order')->field('order_id,order_state,close_reason,client_type,storeid,buyer_id')->where(array('order_id' => $order_id))->find();
    if (!(($order['client_type'] == 'pc') || ($order['client_type'] == 'mini')))
        return;
    $user = M('member')->field('member_name,member_passwd,member_nickname')->where(array('member_id' => $order['buyer_id']))->find();

    return sendSysOrderMsg($order, $user, $type);

}

function joinTxIm($member_name)
{
    $usersig = TXIM_ADMIN_SIG;
    $sdkappid = TXIM_APPID;
    $url = "https://console.tim.qq.com/v4/im_open_login_svc/multiaccount_import?usersig=" .
        $usersig . "&identifier=admin&sdkappid=" . $sdkappid . "&random=" . time() . "&contenttype=json";
    $member_array = [];
    $member_array[] = $member_name;
    $params = [];
    $params['Accounts'] = $member_array;
    $params = json_encode($params, 256);
    $header['Content-Type'] = 'application/json;charset=utf-8;';
    $returnData = httpRequest($url, 'post', $params, $header);
    json_decode($returnData['data'], true);

}

/**
 * 判断事务是否成功
 * @param array $results
 * @return boolean
 * User: hjun
 * Date: 2018-07-26 18:12:04
 * Update: 2018-07-26 18:12:04
 * Version: 1.00
 */
function isTransFail($results = [])
{
    return in_array(false, $results, true);
}

/**
 * JSON解析成数组
 * @param $data
 * @return array
 * User: hjun
 * Date: 2018-07-27 16:09:45
 * Update: 2018-07-27 16:09:45
 * Version: 1.00
 */
function jsonDecodeToArr($data)
{
    $data = json_decode($data, 1);
    return empty($data) ? [] : $data;
}

/**
 * 转换签到
 * @param array $data
 * @param string $actionDataField
 * @param string $imgField
 * @param array $storeInfo
 * @param int $memberId
 * @return void
 * User: hjun
 * Date: 2018-12-11 12:01:20
 * Update: 2018-12-11 12:01:20
 * Version: 1.00
 */
function changeSignInForApp(&$data = [], $actionDataField = \Common\Util\Decoration::ACTION_DATA_DEFAULT, $imgField = 'imgurl', $storeInfo = [], $memberId = 0)
{
    if ($data['action'] === \Common\Util\Decoration::ACTION_SIGN_IN) {
        $data['action'] = 'web_url';
        if (empty($data[$imgField])) {
            $data[$imgField] = getSignInImg($storeInfo);
        }
        $data[$actionDataField] = getStoreDomain($storeInfo['store_id']) . \Common\Model\UtilModel::getLinkTypeUrl('sign_in', '', $storeInfo['store_id'], $memberId);
    }
}

/**
 * 获取每日任务的图片
 * @param array $storeInfo
 * @return string
 * User: hjun
 * Date: 2018-12-11 11:52:19
 * Update: 2018-12-11 11:52:19
 * Version: 1.00
 */
function getSignInImg($storeInfo = [])
{
    if (isMall($storeInfo['store_type'])) {
        $url = 'https://dl.duinin.com/data/advert/20181211/05978442741142982.png';
    } else {
        $url = 'https://dl.duinin.com/data/advert/20181211/05978438307606213.png';
    }
    return $url;
}

/*增加一条未读数量*/
function addUnreadNum($from_name='' , $to_name='' , $store_id = 0,$text=''){
    if(!empty($from_name) && !empty($to_name)){
        $record = M('mb_unreadmsg_record');
        $w = array();
        $w['from_name'] = $from_name;
        $w['to_name'] = $to_name;
        $w['store_id'] = $store_id;
        $id = $record->where($w)->getField('id');
        if(empty($id)){
            $w['num'] = 1;
            $w['text'] = $text;
            $w['send_time'] = time();
            $record->add($w);
        }else{
            $da = array();
            $da['num'] = array('exp',"`num` + 1");
            $da['is_send'] = 0;
            $da['text'] = $text;
            $da['send_time'] = time();
            $record->where($w)->save($da);
        }
    }
}

/*清空未读数量*/
function clearUnreadNum($from_name='' , $to_name='' , $store_id = 0){
    if(!empty($from_name) && !empty($to_name)){
        $record = M('mb_unreadmsg_record');
        $w = array();
        $w['from_name'] = $from_name;
        $w['to_name'] = $to_name;
        $w['store_id'] = $store_id;
        $num = $record->where($w)->save(array('num'=>0));
    }
}

/*清空所有未读消息*/
function clearAllUnreadNum($to_name = '' , $store_id = 0){
    if(!empty($to_name)){
        $record = M('mb_unreadmsg_record');
        $w = array();
        $w['to_name'] = $to_name;
        $w['store_id'] = $store_id;
        $num = $record->where($w)->save(array('num'=>0));
    }
}
/*获取未读数量*/
function getUnreadNum($from_name = '' , $to_name = '' , $store_id = 0){
    if(!empty($from_name) && !empty($to_name)){
        $record = M('mb_unreadmsg_record');
        $w = array();
        $w['from_name'] = $from_name;
        $w['to_name'] = $to_name;
        $w['store_id'] = $store_id;
        $num = $record->where($w)->getField('num');
        $num = empty($num) ? 0 : $num;
        return $num;
    }
}

/*获取所有未读数量*/
function getAllUnreadNum($to_name = '',$store_id = 0){
    if(!empty($to_name)){
        $record = M('mb_unreadmsg_record');
        $w = array();
        $w['store_id'] = $store_id;
        $w['to_name'] = $to_name;
        $num = $record->where($w)->sum('num');
        $num = empty($num) ? 0 : $num;
        return $num;
    }
}

/**
 * @param $store_id
 * @param $order_id
 * @param $money
 * @param int $type  0:订单  1:直接付款  2:直接充值 3:合并支付 5:团购
 * @return string
 */
function getCCBWxPay($store_id, $member_id, $order_id, $money, $type = 0){
    $store_id = $store_id;
    $order_id = $order_id;
    $money = $money;
    $cashPayConfig = M("mb_cashier_pay_config")->where(array('store_id' => $store_id))->find();
    if (empty($cashPayConfig)) {
        return "";
    }
    $REMARK1 = "".$type."---"."";
    if ($type == 1){
        $out_trade_no = "face_" . time() . $order_id;
    }else{
        $out_trade_no =  $order_id;
    }
    $REMARK2 = md5($out_trade_no."8988998");
    $REMARK2 = substr($REMARK2 , 0 , 10);
    $tmp = "";
    $tmp = $tmp."MERCHANTID=".$cashPayConfig['merchantid'];
    $tmp = $tmp."&POSID=".$cashPayConfig['posid'];
    $tmp = $tmp."&BRANCHID=".$cashPayConfig['branchid'];

    if ($type == 1){
        $tmp = $tmp."&ORDERID=".$out_trade_no;
    }else{
        $tmp = $tmp."&ORDERID=".$order_id;
    }

    $tmp = $tmp."&PAYMENT=".$money;
    $tmp = $tmp."&CURCODE=01";
    $tmp = $tmp."&TXCODE=530550";
    $tmp = $tmp."&REMARK1=".$REMARK1;
    $tmp = $tmp."&REMARK2=".$REMARK2;
    $tmp = $tmp."&RETURNTYPE=3";
    $tmp = $tmp."&TIMEOUT=";
    $tmp = $tmp."&PUB=".$cashPayConfig['pub32tr2'];

    $postData = array();
    $postData['CCB_IBSVersion'] = "V6";
    $postData['MERCHANTID'] = $cashPayConfig['merchantid'];
    $postData['BRANCHID'] = $cashPayConfig['branchid'];
    $postData['POSID'] = $cashPayConfig['posid'];
    if ($type == 1) {
        $postData['ORDERID'] = $out_trade_no;
    }else{
        $postData['ORDERID'] = $order_id;
    }
    $postData['PAYMENT'] = $money;
    $postData['CURCODE'] = "01";
    $postData['TXCODE'] = "530550";
    $postData['REMARK1'] = $REMARK1;
    $postData['REMARK2'] = $REMARK2;
    $postData['RETURNTYPE'] = "3";
    $postData['TIMEOUT'] = "";
    $postData['MAC'] = md5($tmp);
    $resultData = array();

    $url = "https://ibsbjstar.ccb.com.cn/CCBIS/ccbMain";
    $returnData = httpRequest($url, "post", $postData);
    $returnArray = json_decode($returnData['data'], true);
    $url = $returnArray['PAYURL'];
    $returnData = httpRequest($url, "post", array());
    $returnArray= json_decode($returnData['data'], true);
    $QRURL = $returnArray['QRURL'];
    $QRURL = urldecode($QRURL);
    if(empty($QRURL)){
        $resultData['code'] = -1;
        $resultData['msg'] = "生成支付路径失败";
        $resultData['QRURL'] = $QRURL;
        return $resultData;
    }
    $resultData['code'] = 200;
    $resultData['QRURL'] = $QRURL;

    if ($type == 0) {
        $order = M('mb_order')->where(array('order_id' => $order_id))->find();
        if ($order['totalprice']  != $money) {
            // $QRURL = "";
//                $resultData['code'] = -1;
//                $resultData['msg'] = "支付金额有误";
        }
    } else if ($type == 1) {
        $face = M('mb_facepay')->where(array('id' => $order_id))->find();
        if ($face['paymoney'] != $money) {
            //$QRURL = "";
//                $resultData['code'] = -1;
//                $resultData['msg'] = "支付金额有误";
        }
    } else if ($type == 3) { //add by czx 合并订单支付
        $mergeorder = M('mb_mergeorder')->where(array('morder_id' => $order_id))->find();

        if ($mergeorder['totalprice']  != $money) {
            //$QRURL = "";
//                $resultData['code'] = -1;
//                $resultData['msg'] = "支付金额有误";
        }
    } else if ($type == 5){
        //hjun 2018-02-08 12:16:55 团购
        $model = M('mb_group_buying_order');
        $order = $model->find($order_id);
        $groupOrderInfo = $order;
        if ($order['pay_price'] != $money) {
            // $QRURL = "";
//                $resultData['code'] = -1;
//                $resultData['msg'] = "支付金额有误";
        }
    } else {
        // $QRURL = "";
        $resultData['code'] = -1;
        $resultData['msg'] = "支付类型参数有误";
    }

    $order_store_id = $store_id;
    $info = M('mb_trade')->where(array('out_trade_no' => $out_trade_no))->find();
    $storeData = M("store")->where(array('store_id' => $store_id))->find();
    if (!empty($info)) {
        $order_store_id = $info['store_id'];
        $order_store_name = $info['store_name'];

        if ($type != 3 && $info['mergepay'] == 1) {   //add by czx
            M('mb_trade')->where(array('out_trade_no' => $order_id))->save(array('type' => 0, 'mergepay' => 0));

            M('mb_order')->where(array('order_id' => $order_id))->save(array('mergepay' => 0));
        }

    } else {
        $mstore = M('store')->field('store_name')->where(array('store_id' => $store_id))->find();
        $order_store_name = $mstore['store_name'];
    }
    if (empty($order_store_name)) {
        $mstore = M('store')->field('store_name')->where(array('store_id' =>$store_id))->find();
        $order_store_name = $mstore['store_name'];
    }

    $muser = M('member')->field('member_name')->where(array('member_id' => $member_id))->find();
    $adata = array(
        'total_fee' =>  $money * 100,
        'out_trade_no' => $out_trade_no,
        'channelid' => $storeData['channel_id'],
        'store_id' => $order_store_id,
        'member_id' => $member_id,
        'store_name' => $order_store_name,
        'member_name' => $muser['member_name'],
        'order_id' => $order_id,
        'type' => $type,
        'is_platform' => 0
    );
    if ($type == 3) {  //add by czx
        $adata['morder_id'] = $order_id;
        $adata['mergepay'] = 1;
    }
    if (empty($info)) {
        M('mb_trade')->add($adata);
    } else {
        M('mb_trade')->where(array('out_trade_no' => $order_id))->save($adata);
    }


    $body = $order_store_name;
    if ($type == 0) {
        $attach = "web正常购物";
        $goodstag = "web购物";
    } else if ($type == 3) {   //add by czx
        $attach = "合并正常购物";
        $goodstag = "合并正常购物";
    } else if ($type == 5){
        $attach = "web正常团购";
        $goodstag = "web团购";
    } else {
        $attach = "web直接付款";
        $goodstag = "web直接付款";
    }


    if ($type == 3) {  //add by czx
        $morder_list = M("mb_order")->where(array('morder_id' => $order_id))->select();
        for ($i = 0; $i < count($morder_list); $i++) {
            $morder = $morder_list[$i];
            $mdata = array(
                'out_trade_no' => $morder['order_id'],
                'channelid' => $storeData['channel_id'],
                'store_id' => $morder['storeid'],
                'member_id' => $member_id,
                'store_name' => $morder['store_name'],
                'member_name' => $muser['member_name'],
                'order_id' => $morder['order_id'],
                'type' => $type,
                'total_fee' => $morder['totalprice'] * 100,
                'morder_id' => $order_id,
                'mergepay' => 1,
                'attach' => $attach,
                'is_platform' => 0,
            );
            $pdata = M('mb_trade')->where(array('out_trade_no' => $morder['order_id']))->find();
            if (empty($pdata)) {
                M('mb_trade')->add($mdata);
            } else {
                M('mb_trade')->where(array('out_trade_no' => $morder['order_id']))->save(array('is_platform' => 0));
            }
        }
    }
    return $resultData;
}


function ccbRefundOrder($order_id, $refund_money){
    $headData = array();
    $headData['Content-type'] = "text/xml";
    $postDataStr = "<?xml version='1.0' encoding='GB2312' standalone='yes' ?> 
                        <TX>
                          <REQUEST_SN>" . time() .
        "</REQUEST_SN>
                          <CUST_ID>105350250940001</CUST_ID>
                          <USER_ID>105350250940001-066</USER_ID>
                          <PASSWORD>000000</PASSWORD>
                          <TX_CODE>5W1004</TX_CODE>
                          <LANGUAGE>CN</LANGUAGE>
                          <TX_INFO>
                            <MONEY>".$refund_money."</MONEY>
                            <ORDER>".$order_id."</ORDER>
                            <REFUND_CODE> </REFUND_CODE>
                          </TX_INFO>
                          <SIGN_INFO></SIGN_INFO>
                          <SIGNCERT></SIGNCERT>
                        </TX>
                        ";
    $url = "http://123.207.98.106:8088";
    //$url = "http://127.0.0.1:8088";
    $postData = array();
    $postData['requestXml'] = $postDataStr;
    $returnData = httpRequest($url, "post", $postData, $headData);
    $xmlString = trim($returnData['data']);
    $xmlArr = fromXml($xmlString);
    return $xmlArr;
}

function sig($member_name, $type)
{
    vendor('Tencent.TLSSig');
    try {
        $api = new \TLSSigAPI();
        $api->SetAppid(TXIM_APPID);
        $api->setAccount_type(TXIM_AccountTYPE);
        $private = file_get_contents(TLSSIG_KEY_PATH . 'private_key');
        $api->SetPrivateKey($private);
        $public = file_get_contents(TLSSIG_KEY_PATH . 'public_key');
        $api->SetPublicKey($public);
        $sig = $api->genSig($member_name,  180 * 24 * 3600);

        if($type == 'api'){
            output_data($sig);

        }else{
            return $sig;
        }

    } catch (Exception $e) {
        //echo $e->getMessage();
        if($type == 'api'){
            echo '';
        }else{
            return '';
        }
    }
}

function rushTime($mtime)
{
    $second = intval($mtime % 60);
    $second = sprintf("%02d", $second);
    $minute = intval(intval($mtime / 60) % 60);
    $minute = sprintf("%02d", $minute);
    $hour = intval($mtime / 3600) % 24;
    $hour = sprintf("%02d", $hour);
    $day = intval($mtime / 3600 / 24);
    return array("day" => $day, "hour" => $hour, "minute" => $minute, "second" => $second);
}

/**
 * 建设支付日志
 * @param $log_str
 * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
 * User: czx
 * Date: 2019-04-21 15:28:23
 * Update: 2019-04-21 15:28:23
 * Version: 1.00
 */

function ccbLogs($log_str){
    \Think\Log::write($log_str, \Think\Log::INFO, "", CCB_LOGS_PATH);
}



function balanceLogs($log_str){
    \Think\Log::write($log_str, \Think\Log::INFO, "", BALANCE_LOGS_PATH);
}


function hpLogs($log_str){
    \Think\Log::write($log_str, \Think\Log::INFO, "", HP_LOGS_PATH);
}

function refundGoodsLogs($log_str){
    
    \Think\Log::write($log_str, \Think\Log::INFO, "", REFUND_GOODS_PATH);
}

function TestLogs($log_str){

    \Think\Log::write($log_str, \Think\Log::INFO, "", TEST_PATH);
}

function CouponAdminLogs($log_str){
    \Think\Log::write($log_str, \Think\Log::INFO, "", CP_AD);
}

