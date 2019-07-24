<?php

$mode = defined('MODE') ? MODE : 'common';
return array(
    // ==========================================    通用设置    ========================================================
    /* 默认设定 */
    'DEFAULT_MODULE' => 'Wap',  // 默认模块
    'DEFAULT_FILTER' => 'trim',  // 默认参数过滤方法 用于I函数...

    /*数据库设置*/
    'DB_PARAMS' => array(\PDO::ATTR_CASE => \PDO::CASE_NATURAL),

    /* 错误设置 */
    'ERROR_MESSAGE' => '页面错误！请稍后再试～',  //错误显示信息,非调试模式有效
    'ERROR_PAGE' => '',  // 错误定向页面

    /* SESSION设置 */
    'SESSION_AUTO_START' => true,    // 是否自动开启Session

    /* URL设置 */
    'URL_CASE_INSENSITIVE' => false,  // 默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL' => 0,  // URL访问模式,可选参数0、1、2、3,代表以下四种模式 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE  模式); 3 (兼容模式)  默认为PATHINFO 模式

    /*语言包*/
    'LANG_SWITCH_ON' => true, // 开启语言包功能
    'LANG_AUTO_DETECT' => true, // 自动侦测语言 开启多语言功能后有效
    'LANG_LIST' => 'zh-cn,en-us', // 允许切换的语言列表 用逗号分隔
    'VAR_LANGUAGE' => 'l', // 默认语言切换变量

    /*模版替换*/
    'TMPL_PARSE_STRING' => array(
        '__COMMON_IMG__' => '/Public/common/img',
        '__COMMON_CSS__' => '/Public/common/css',
        '__COMMON_JS__' => '/Public/common/js',
        '__COMMON_ICON__' => '/Public/common/icon',
        '__WAPCSS__' => '/Public/wap2/css',
        '__WAPIMG__' => '/Public/wap2/img',
        '__WAPJS__' => '/Public/wap2/js',
        '__STYLE__' => '/Public/wap2/css/common/style.css',
        '__MCSS__' => '/Public/mcss',
        '__MIMG__' => '/Public/mimages',
        '__MJS__' => '/Public/mjs',
        '__ADMIN_CSS__' => '/Public/admin2/css',
        '__ADMIN_IMG__' => '/Public/admin2/img',
        '__ADMIN_JS__' => '/Public/admin2/js',
        '__ADMIN_EXT__' => '/Public/admin2/ext',
        '__ADMIN__' => '/Public/admin2',
        // plug-in
        '__JQUERY__' => '/Public/common/js/plug-in/jquery/jquery-3.2.1.min.js',
        '__LAYUIJS__' => '/Public/common/js/plug-in/layui/layui.js',
        '__LAYUICSS__' => '/Public/common/js/plug-in/layui/css/layui.css',
        '__VUE__' => VUE,
        '__SWIPERJS__' => '/Public/common/js/plug-in/swiper/swiper.min.js',
        '__SWIPERCSS__' => '/Public/common/js/plug-in/swiper/swiper-3.4.2.min.css',
        '__LAYER_JS__' => '/Public/common/js/plug-in/layer/layer.js',
        '__APP_UTIL__' => APP_UTIL,
        '__V_CLOAK__' => '/Public/common/css/v-cloak.css',
        '__LAY_DATE__' => '/Public/common/js/plug-in/laydate/laydate.js',
        '__UE-EDITOR__' => '/Public/common/js/plug-in/ueditor/ueditor.all.min.js',
        '__UE-EDITOR-CONFIG__' => '/Public/common/js/plug-in/ueditor/ueditor.config.js',
        '__VEE-VALIDATE__' => 'https://unpkg.com/vee-validate@2.0.0-rc.7/dist/vee-validate.min.js',
        '__PUBLIC_WAP__' => '/Public/wap2/js/common/publicWap.js?v=' . EXTRA_VERSION,
        '__CUSTOMER_MSG__' => '/Public/common/customerMsg',
    ),

    /*缓存设置*/
    'DATA_CACHE_TIME' => 0, // 缓存30分钟


    'AUTOLOAD_NAMESPACE' => array(
        'JsonSchema' => VENDOR_PATH . 'JsonSchema',
    ),

    // =======================================    根据环境的而不同配置不同的配置    =======================================
    'LOAD_EXT_CONFIG' => "config.change,config.{$mode}", // 加载扩展配置文件
);