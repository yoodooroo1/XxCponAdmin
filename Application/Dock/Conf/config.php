<?php

$mode = defined('MODE') ? MODE : 'common';
return array(
    // =================================================    通用设置    =================================================
    /* Cookie设置 */
    'COOKIE_PREFIX' => 'OPERATE_',      // Cookie前缀 避免冲突

    /* SESSION设置 */
    'SESSION_PREFIX' => 'OPERATE_', // session 前缀

    /*杂项*/
    'DB_CACHE_ON' => false, // 关闭缓存
    // =======================================    根据环境的而不同配置不同的配置    =======================================
    'LOAD_EXT_CONFIG' => "config.{$mode}", // 加载扩展配置文件

    //'数据库'=>'配置值'
    'DB_TYPE' => 'mysql',// 数据库类型
    'DB_HOST' => '127.0.0.1',//正式 服务器地址
    'DB_NAME' => 'test',// 数据库名
    'DB_USER' => 'localhost',// 用户名
    'DB_PWD' => '',// 密码
    'DB_PREFIX' => '',// 数据库表前缀
    'DB_PORT' => 3306,// 端口
    'DB_DEBUG' => TRUE, // 数据库调试模式 开启后可以记录SQL日志
    'DB_FIELDS_CACHE' => false,        // 启用字段缓存
    'DB_CHARSET' => 'utf8',      // 数据库编码默认采用utf8
    'DB_DEPLOY_TYPE' => 0, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'DB_RW_SEPARATE' => false,       // 数据库读写是否分离 主从式有效
    'DB_MASTER_NUM' => 1, // 读写分离后 主服务器数量
    'DB_SLAVE_NO' => '', // 指定从服务器序号
);                                  