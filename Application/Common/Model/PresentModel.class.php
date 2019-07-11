<?php

namespace Common\Model;
/**
 * Class PresentModel
 * 礼品模型
 * @package Common\Model
 * User: hjun
 * Date: 2017-12-05 16:13:41
 */
class PresentModel extends BaseModel
{
    protected $tableName = 'mb_present';

    /**
     * 删除礼券 根据优惠券ID删除
     * @param string $couponsId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-05 16:16:17
     * Update: 2017-12-05 16:16:17
     * Version: 1.00
     */
    public function delPresentCoupons($couponsId = '')
    {
        if (empty($couponsId)) return getReturn(-1, L('INVALID_PARAM'));
        $maxVersion = $this->max('version');
        $where = [];
        $where['coupons_id'] = ['in', $couponsId];
        $options = [];
        $options['where'] = $where;
        $result = $this->queryField($options, 'present_id', true);
        if ($result['code'] !== 200) return $result;
        $presentId = $result['data'];
        if (!empty($presentId)) {
            $data = [];
            foreach ($presentId as $key => $value) {
                $item = [];
                $item['present_id'] = $value;
                $item['version'] = ++$maxVersion;
                $item['isdelete'] = 1;
                $data[] = $item;
            }
            return $this->saveAllData([], $data);
        }
        return getReturn(200, '');
    }
}