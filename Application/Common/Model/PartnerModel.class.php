<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2017/4/26
 * Time: 15:35
 */

namespace Common\Model;
/**
 * 搜索模型.
 * @author: hjun
 * @created: 2017-05-23 14:13:45
 * @version: 1.0
 */
class PartnerModel extends BaseModel
{

    protected $tableName = 'mb_storegroup';

    /**
     * 获取商家的代理分组列表
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-28 11:58:19
     * Update: 2017-12-28 11:58:19
     * Version: 1.00
     */
    public function getStoreGroup($storeId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $options = [];
        $options['where'] = $where;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $result['data']['list'] = $list;
        return $result;
    }

}