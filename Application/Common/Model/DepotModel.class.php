<?php

namespace Common\Model;

/**
 * 仓库模型
 * Class DepotModel
 * @package Common\Model
 * User: hjun
 * Date: 2018-10-29 11:34:14
 * Update: 2018-10-29 11:34:14
 * Version: 1.00
 */
class DepotModel extends DepotBaseModel
{
    protected $tableName = 'mb_pickup_list';

    /**
     * 获取仓库数据
     * @param int $depotId
     * @return array
     * User: hjun
     * Date: 2018-11-08 21:07:05
     * Update: 2018-11-08 21:07:05
     * Version: 1.00
     */
    public function getDepot($depotId = 0)
    {
        $where = [];
        $where['id'] = $depotId;
        $where['isdelete'] = NOT_DELETE;
        $options = [];
        $options['where'] = $where;
        return $this->selectRow($options);
    }

    /**
     * 获取仓库列表
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @param string $field
     * @param string $order
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-08 21:11:42
     * Update: 2018-11-08 21:11:42
     * Version: 1.00
     */
    public function getDepotList($storeId = 0, $page = 1, $limit = 20, $condition = [], $field = '', $order = '')
    {
        $where = [];
        $where['isdelete'] = NOT_DELETE;
        $where['store_id'] = $storeId;
        $where = array_merge($where, $condition);
        $options = [];
        if (!empty($field)) {
            if (is_array($field)) {
                $field = implode(',', $field);
            }
            $options['field'] = $field;
        }
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        if (!empty($order)) {
            $options['order'] = $order;
        }
        return $this->queryList($options);
    }

    /**
     * 获取最大版本号
     * @return int
     * User: hjun
     * Date: 2018-11-29 10:50:29
     * Update: 2018-11-29 10:50:29
     * Version: 1.00
     */
    public function getMaxVersion()
    {
        return $this->max('version');
    }
}