<?php

namespace Common\Model;
class GoodsOptionModel extends BaseModel
{
    protected $tableName = 'goods_option';

    /**
     * 获取商品的规格所有选项 ID组合 数组
     * @param int $goodsId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-04 22:16:10
     * Update: 2018-01-04 22:16:10
     * Version: 1.00
     */
    public function getGoodsSpecOption($goodsId = 0)
    {
        $where = [];
        $where['goods_id'] = $goodsId;
        $options = [];
        $options['where'] = $where;
        return $this->queryField($options, 'specs', true);
    }
}