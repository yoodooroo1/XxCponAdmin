<?php

namespace Common\Model;
class SupplierAgentModel extends BaseModel
{
    protected $tableName = 'mb_supplier_agent';

    /**
     * 获取商家的供应商商家ID数组
     * @param int $storeId 商家ID
     * @param int $returnType 1-返回不包括自身的ID数组    2-返回的数组包括自身
     * @return array
     * User: hjun
     * Date: 2018-01-30 09:54:35
     * Update: 2018-01-30 09:54:35
     * Version: 1.00
     */
    public function getStoreSupplierAgentId($storeId = 0, $returnType = 1)
    {
        $where = [];
        $where['agent_sid'] = $storeId;
        $where['is_delete'] = 0;
        $where['state'] = 2;
        $options = [];
        $options['where'] = $where;
        $result = $this->queryField($options, 'supplier_sid', true);
        $id = empty($result['data']) ? [] : $result['data'];
        if ($returnType == 2) {
            array_unshift($id, $storeId);
        }
        return $id;
    }

    /**
     * 查询供应商信息
     * @param int $supplierId
     * @param int $agentId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-01 18:01:43
     * Update: 2018-02-01 18:01:43
     * Version: 1.00
     */
    public function getStoreSupplierInfo($supplierId = 0, $agentId = 0)
    {
        if ($supplierId == $agentId) return [];
        $field = [
            'agent_sid','agent_name','supplier_name','agent_tel',
            'supplier_owner','supplier_sid','agent_owner',
            'supplier_tel','discount','ratio'
        ];
        $where = [];
        $where['supplier_sid'] = $supplierId;
        $where['agentId'] = $agentId;
        $where['is_delete'] = 0;
        $where['state'] = 2;
        $options = [];
        $options['where'] = $where;
        $options['field'] = implode(',', $field);
        $result = $this->queryRow($options);
        return empty($result['data']) ? [] : $result['data'];
    }
}