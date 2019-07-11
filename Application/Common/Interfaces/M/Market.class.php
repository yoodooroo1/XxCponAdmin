<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/12/4
 * Time: 14:18
 */

namespace Common\Interfaces\M;

interface Market
{
    /**
     * 获取可以用于绑定的礼包（营销计划）的选择框数据
     * @return array
     * User: hjun
     * Date: 2018-12-04 20:29:00
     * Update: 2018-12-04 20:29:00
     * Version: 1.00
     */
    public function getCanBindSelectOptions();

    /**
     * 获取某个计划的数据
     * @param int $marketId
     * @return array
     * User: hjun
     * Date: 2018-12-05 12:09:20
     * Update: 2018-12-05 12:09:20
     * Version: 1.00
     */
    public function getMarket($marketId = 0);

    /**
     * 获取营销计划的缓存数据
     * @param int $marketId
     * @return array
     * User: hjun
     * Date: 2018-12-05 12:10:49
     * Update: 2018-12-05 12:10:49
     * Version: 1.00
     */
    public function getMarketCache($marketId = 0);
}