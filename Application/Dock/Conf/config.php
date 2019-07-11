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

    /*********************惠普配置项****************************/
    'HP' =>array(
        '90002'=>array(
            'fmch_id'=> '90002',
            'fmch_key' => 'hpsoft',
            'fsign' => '08077d8ee01bad9968223b689925179b',
            'base_url' => "http://www.hotplutus.cn:9090/hpapi",
        ),
        '910001'=>array(
            'fmch_id'=> '910001',
            'fmch_key' => '03febf940f',
            'fsign' => 'c63f50a108dda02fa4236ba5731d72cd',
            'base_url' => "http://www.hotplutus.cn:9090/hpapi",
        ),
        '90003'=>array(
            'fmch_id'=> '90003',
            'fmch_key' => '6BA11BC6E5',
            'fsign' => '42709220ed5900aea05fdd5f0bdfa383',
            'base_url' => "http://www.hotplutus.cn:9090/hpapi",
        )

    ),

);                                  