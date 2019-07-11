<?php

namespace Common\Model;

/**
 * Class GoodsDepotModel
 * @package Common\Model
 */
class GoodsDepotModel extends DepotModel
{
    /**
     * 获取仓库列表
     * @param array $where
     * @param int $limit
     * @param int $page
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-08 21:51:25
     * Update: 2018-11-08 21:51:25
     * Version: 1.00
     */
    public function getGoodsDepotList($where = array(), $limit = 20, $page = 1)
    {
        $field = [
            'id depot_id', 'store_name depot_name', 'link_name',
            'link_tel', 'address', 'remark'
        ];
        $where['type'] = self::DEPOT;
        $order = 'id DESC';
        return $this->getDepotList($this->getStoreId(), $page, $limit, $where, $field, $order);
    }

    /**
     * 删除仓库
     * @param string $depot_id
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-25 11:02:43
     * Update: 2017-12-25 11:02:43
     * Version: 1.00
     */
    public function delDepot($depot_id = '')
    {
        if (empty($depot_id)) return getReturn(-1, L('INVALID_PARAM'));
        $depot_ids = explode(',', $depot_id);
        $data = [];
        $version = $this->getMaxVersion();
        foreach ($depot_ids as $key => $value) {
            $item = [];
            $item['id'] = $value;
            $item['isdelete'] = 1;
            $item['version'] = ++$version;
            $data[] = $item;
        }
        return $this->saveAllData([], $data);
    }
}     