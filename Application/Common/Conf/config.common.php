<?php

defined('Operate_Root') or define('Operate_Root', 'http://jiameng.duinin.com');
defined('SEND_MESSAGE_URL') or define('SEND_MESSAGE_URL', 'http://m.duinin.com/');
defined('DOMAIN') or define('DOMAIN', "http://m.duinin.com/index.php");
defined('XXAPI') or define('XXAPI', "https://mapi.duinin.com");
defined('XXID') or define('XXID', "https://mapi.duinin.com");
defined('TXIM_APPID') or define('TXIM_APPID', "1400007927");
defined('TXIM_AccountTYPE') or define('TXIM_AccountTYPE', "3988");
defined('TXIM_ADMIN_SIG') or define('TXIM_ADMIN_SIG', "eJw1zU1Pg0AQBuD-wrVG94MFauJBKQeS9tACjRgTsrLLOm2BdVkr1fjfXQmd4-vMO-Pj5evsltd1-9nZyl609O49uowi72YSELKz0IA0LueihW4GrjWIituKGuEIzfEgjtVELsM*chMuSTijHDUYWfHGTucwY4y4jVnP0gzQdw4IwgwT*t**ooVWThU-QgzTgF7-gXLxJinidLsa0esdRov48AjBqTY0z6ANyE68hV9K7-x39q2yfbxQo*EqVWm80sVz2Jf7cyaHU3z4KGVxzF*2uknatpf7SziSdZk8qc2D9-sHLKVZag__");
// 腾讯云万象优图
defined('QCI_APPID') or define('QCI_APPID', '1251996578');
defined('QCI_SECRETID') or define('QCI_SECRETID', 'AKIDicUOzzqUQZKVDl8X9sgGARO7R5ndFvAa');
defined('QCI_SECRETKEY') or define('QCI_SECRETKEY', 'Xf1JtJYwsf7IXbdJOAmEBdJs8QsRfobK');
defined('QCI_BUCKET') or define('QCI_BUCKET', '');
return array(
    /* 数据库设置 */
    'DB_TYPE' => 'mysql',// 数据库类型
    'DB_HOST' => '10.135.135.41',//正式 服务器地址
    'DB_NAME' => 'xunxin_db',// 数据库名
    'DB_USER' => 'xunxin_remote',// 用户名
    'DB_PWD' => 'htxQrhOUKOpG',// 密码
    'DB_PREFIX' => 'xunxin_',// 数据库表前缀
    'DB_PORT' => 3306,// 端口
    'DB_DEBUG' => TRUE, // 数据库调试模式 开启后可以记录SQL日志
    'DB_FIELDS_CACHE' => true,        // 启用字段缓存
    'DB_CHARSET' => 'utf8',      // 数据库编码默认采用utf8
    'DB_DEPLOY_TYPE' => 0, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'DB_RW_SEPARATE' => false,       // 数据库读写是否分离 主从式有效
    'DB_MASTER_NUM' => 1, // 读写分离后 主服务器数量
    'DB_SLAVE_NO' => '', // 指定从服务器序号
    // 读写分离配置
//    'DB_HOST' => '10.135.135.41,10.135.95.156',//正式 服务器地址
//    'DB_USER' => 'xunxin_remote,xunxin_remote',// 用户名
//    'DB_PWD' => 'htxQrhOUKOpG,HfyHI6Xs1n6U',// 密码
//    'DB_DEPLOY_TYPE' => 1, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
//    'DB_RW_SEPARATE' => true,       // 数据库读写是否分离 主从式有效
//    'DB_MASTER_NUM' => 1, // 读写分离后 主服务器数量

    /*Redis设置*/
    'DATA_CACHE_PREFIX' => 'XX_',//缓存前缀
    'DATA_CACHE_TYPE' => 'Redis',//默认动态缓存为Redis
    'REDIS_HOST' => '127.0.0.1', //redis服务器ip，多台用逗号隔开；读写分离开启时，第一台负责写，其它[随机]负责读；
    'REDIS_PORT' => '6379',//端口号
    'DATA_CACHE_TIMEOUT' => '300',//超时时间
    'REDIS_PERSISTENT' => false,//是否长连接 false=短连接
    'REDIS_AUTH' => 'xunxin8988998',//AUTH认证密码

    /*API地址*/
    'api_url' => "https://mapi.duinin.com/index.php", // 迅信接口
    'upload_url' => "//file.duinin.com/upload.php", // 图片上传
    'renew_url' => "http://jiameng.duinin.com/index.php?m=Api&c=Fund&a=getPackageInfo", //续费页面用到的接口地址
    'send_wx_msg' => 'http://m.duinin.com/index.php?m=Service&c=SendMessage&a=sendWxMsg', // 发送微信客服消息接口地址
    'new_api_url' => 'https://mapi.duinin.com/index.php',
    'new_api_key' => 'vjd8988998',
    'LIVE_SERVICE' => 'http://zhibo.duinin.com/interface.php', // 直播
    'LIVE_API_URL' => 'http://zhibo.duinin.com/service/index.php', // 直播
    'CHAT_URL' => 'http://api.cnt.wlsd.com.cn/xxapi/chat/sendmessage.php', //聊天消息接口

    /*日志设置*/
    'LOG_RECORD' => true, // 开启日志记录
    'LOG_LEVEL' => 'EMERG,ALERT,CRIT,ERR,INFO', // 只记录EMERG ALERT CRIT ERR 错误

    /*管理系统设置*/
    'site_name' => '',
    'site_title' => '商城管理后台',
    'site_logo' => '/Public/media/image/logo.png',
    'company' => '',
    'phone' => '400-991-5998',
    'site_url' => 'https://m.duinin.com',
    'keyword' => '',
    'content' => '',
    'channel_id' => '0',
    'help_url' => 'http://m.duinin.com/Public/media/pdf/xunxin_shop_index2.pdf',

    /*杂项*/
    'MALL_DOMAIN' => 'm.duinin.com', // 商城访问域名
    'MALL_SSL' => 'http', // 商城访问协议
);
