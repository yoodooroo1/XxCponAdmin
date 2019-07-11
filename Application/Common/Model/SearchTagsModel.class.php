<?php

namespace Common\Model;
class SearchTagsModel extends BaseModel
{
    protected $tableName = 'mb_search_tags';

    /**
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-11 13:59:53
     * Desc: 获取商家搜索词
     * Update: 2017-10-11 13:59:54
     * Version: 1.0
     */
    public function getStoreSearchTags($storeId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $list = $this
            ->where($where)
            ->order('is_default DESC,sort DESC,id DESC')
            ->select();
        if (false === $list) {
            logWrite("查询商家{$storeId}的搜索词出错:" . $this->getDbError());
            return getReturn();
        }
        $tags = [];
        foreach ($list as $key => $value) {
            if ($value['is_default'] == 1) {
                $tags['default']['id'] = $value['id'];
                $tags['default']['tags'] = $value['tags'];
            } else {
                $item['id'] = $value['id'];
                $item['tags'] = $value['tags'];
                $tags['list'][] = $item;
            }
        }
        return getReturn(200, '', $tags);
    }
}