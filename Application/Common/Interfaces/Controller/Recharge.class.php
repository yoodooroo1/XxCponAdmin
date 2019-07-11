<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/12/4
 * Time: 14:17
 */

namespace Common\Interfaces\Controller;

interface Recharge
{
    /**
     * 充值卡列表
     * User: hjun
     * Date: 2018-12-04 14:17:42
     * Update: 2018-12-04 14:17:42
     * Version: 1.00
     */
    public function rechargeCardList();

    /**
     * 获取充值卡列表数据
     * User: hjun
     * Date: 2018-12-04 14:40:41
     * Update: 2018-12-04 14:40:41
     * Version: 1.00
     */
    public function getRechargeCardListData();

    /**
     * 新增充值卡
     * User: hjun
     * Date: 2018-12-04 14:41:14
     * Update: 2018-12-04 14:41:14
     * Version: 1.00
     */
    public function addRechargeCard();

    /**
     * 修改充值卡
     * User: hjun
     * Date: 2018-12-04 14:41:29
     * Update: 2018-12-04 14:41:29
     * Version: 1.00
     */
    public function updateRechargeCard();

    /**
     * 充值记录
     * User: hjun
     * Date: 2018-12-04 14:18:28
     * Update: 2018-12-04 14:18:28
     * Version: 1.00
     */
    public function rechargeCardRecord();

    /**
     * 删除充值卡
     * User: hjun
     * Date: 2018-12-04 14:41:44
     * Update: 2018-12-04 14:41:44
     * Version: 1.00
     */
    public function deleteRechargeCard();

    /**
     * 改变充值卡状态
     * User: hjun
     * Date: 2018-12-05 11:45:39
     * Update: 2018-12-05 11:45:39
     * Version: 1.00
     */
    public function changeCardStatus();

    /**
     * 获取充值记录数据
     * User: hjun
     * Date: 2018-12-04 14:40:49
     * Update: 2018-12-04 14:40:49
     * Version: 1.00
     */
    public function getRechargeCardRecordData();
}