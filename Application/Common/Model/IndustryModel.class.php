<?php

namespace Common\Model;
/**
 * Class IndustryModel
 * User: hj
 * Date: 2017-10-26 23:56:23
 * Desc: 行业模型类
 * Update: 2017-10-26 23:56:26
 * Version: 1.0
 * @package Common\Model
 */
class IndustryModel extends BaseModel
{
    protected $tableName = 'mb_commontype';

    /**
     * @param int $pid
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-27 00:09:23
     * Desc: 获取行业列表
     * Update: 2017-10-27 00:09:24
     * Version: 1.0
     */
    public function getIndustryList($pid = 0)
    {
        // 先查出一级行业
        $where = [];
        $where['isdelete'] = 0;
        $where['store_parenttype_id'] = $pid;
        $list = $this
            ->field('id,store_type_name,store_parenttype_id')
            ->where($where)
            ->cache(true, 0)
            ->select();
        if (false === $list) {
            logWrite("查询行业出错:" . $this->getDbError());
            return getReturn();
        }
        foreach ($list as $key => $value) {
            $result = $this->getIndustryList($value['id']);
            if ($result['code'] !== 200) return $result;
            $list[$key]['child'] = $result['data'];
        }
        return getReturn(200, '', $list);
    }
}