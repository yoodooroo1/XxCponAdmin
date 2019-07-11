<?php

namespace Common\Model;

use Common\Interfaces\M\Market;

class MarketModel extends BaseModel implements Market
{

    // 营销计划类别(0: 新注册 1:推荐会员 2: 定期赠送 3:单次消费 4:累计消费 5:购买指定商品 6:手动发放)
    const MARKET_TYPE_REGISTER = 0;
    const MARKET_TYPE_RECOMMEND = 1;
    const MARKET_TYPE_PERIOD = 2;
    const MARKET_TYPE_SINGLE_COST = 3;
    const MARKET_TYPE_TOTAL_COST = 4;
    const MARKET_TYPE_GOODS = 5;
    const MARKET_TYPE_MANUAL = 6;
    const MARKET_TYPE_DAILY = 11;

    protected $tableName = 'mb_market';

    /**
     * 获取可以用于绑定的礼包（营销计划）的选择框数据
     * @return array
     * User: hjun
     * Date: 2018-12-04 20:29:00
     * Update: 2018-12-04 20:29:00
     * Version: 1.00
     */
    public function getCanBindSelectOptions()
    {
        $field = [
            'id market_id', 'market_name'
        ];
        $where = [];
        $where['store_id'] = $this->getStoreId();
        $where['market_type'] = self::MARKET_TYPE_MANUAL;
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        return $this->selectList($options);
    }

    /**
     * 获取 某个营销计划的数据
     * @param int $marketId
     * @return array
     * User: hjun
     * Date: 2018-12-04 21:23:06
     * Update: 2018-12-04 21:23:06
     * Version: 1.00
     */
    public function getMarket($marketId = 0)
    {
        $where = [];
        $where['id'] = $marketId;
        $where['store_id'] = $this->getStoreId();
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['where'] = $where;
        return $this->selectRow($options);
    }

    /**
     * 获取营销计划的缓存数据
     * @param int $marketId
     * @return array
     * User: hjun
     * Date: 2018-12-05 12:10:49
     * Update: 2018-12-05 12:10:49
     * Version: 1.00
     */
    public function getMarketCache($marketId = 0)
    {
        $info = $this->getLastQueryData("market:{$marketId}");
        if (empty($info)) {
            $info = $this->getMarket($marketId);
            $this->setLastQueryData("market:{$marketId}", $info);
        }
        return $info;
    }
}