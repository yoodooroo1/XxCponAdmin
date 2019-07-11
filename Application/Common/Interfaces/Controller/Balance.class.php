<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/12/4
 * Time: 14:17
 */

namespace Common\Interfaces\Controller;

interface Balance
{

    /**
     * 我的余额明细页面
     * User: hjun
     * Date: 2018-12-05 16:20:48
     * Update: 2018-12-05 16:20:48
     * Version: 1.00
     */
    public function mybalance();

    /**
     * 在线充值页面
     * User: hjun
     * Date: 2018-12-05 16:21:12
     * Update: 2018-12-05 16:21:12
     * Version: 1.00
     */
    public function rechargeCardList();

    /**
     * 微信前端获取充值卡列表数据
     * User: hjun
     * Date: 2018-12-05 17:24:02
     * Update: 2018-12-05 17:24:02
     * Version: 1.00
     */
    public function getRechargeCardListData();

    /**
     * 充值确认支付
     * User: hjun
     * Date: 2018-12-05 17:50:49
     * Update: 2018-12-05 17:50:49
     * Version: 1.00
     */
    public function rechargePay();
}