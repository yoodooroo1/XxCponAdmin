<?php

namespace Common\Model;

class GoodsTagLinkModel extends BaseModel
{
    protected $tableName = 'goods_tag_link';

    /**
     * 根据标签获取商品ID
     * @param array $tagIds
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2019-03-12 16:56:49
     * Update: 2019-03-12 16:56:49
     * Version: 1.00
     */
    public function getGoodsIdsByTagsIds($tagIds = [])
    {
        if (empty($tagIds)) {
            return [];
        }
        $where = [];
        $where['tag_id'] = getInSearchWhereByArr($tagIds);
        $goodsIds = $this->where($where)->getField('goods_id', true);
        if (empty($goodsIds)) {
            return [];
        }
        return $goodsIds;
    }
}