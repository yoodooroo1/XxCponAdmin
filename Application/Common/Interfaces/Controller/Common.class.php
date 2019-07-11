<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/12/4
 * Time: 14:17
 */

namespace Common\Interfaces\Controller;

interface Common
{

    /**
     * 获取商品分类的选择项
     * User: hjun
     * Date: 2018-10-30 15:49:12
     * Update: 2018-10-30 15:49:12
     * Version: 1.00
     */
    public function getGoodsClassSelectOption();

    /**
     * 判断商品是否在ERP或者门店有进货或者销售数据
     * User: hjun
     * Date: 2018-11-19 14:28:48
     * Update: 2018-11-19 14:28:48
     * Version: 1.00
     */
    public function checkGoodsHasERPData();

    /**
     * 获取充值卡列表页的选择框数据
     * User: hjun
     * Date: 2018-12-05 11:17:50
     * Update: 2018-12-05 11:17:50
     * Version: 1.00
     */
    public function getRechargeListSelectOption();
}