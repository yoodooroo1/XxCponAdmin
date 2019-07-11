<?php

namespace Common\Model;

/**
 * Class CouponsCenterModel
 * 会员领券记录模型
 * @package Common\Model
 * User: hjun
 * Date: 2017-12-07 16:57:24
 */
class MemberCouponsCenterModel extends BaseModel
{
    protected $tableName = 'mb_member_coupons_center';

    /**
     * 获取用户领券的列表
     * @param int $memberId
     * @param int $centerId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-15 15:18:27
     * Update: 2017-12-15 15:18:27
     * Version: 1.00
     */
    public function getMemberCenterList($memberId = 0, $centerId = 0)
    {
        $field = [
            'a.*'
        ];
        $field = implode(',', $field);
        $join = [
            '__MB_MEMBERCOUPONS__ b ON a.member_coupons_id = b.id'
        ];
        $where = [];
        $where['a.member_id'] = $memberId;
        $where['a.center_id'] = $centerId;
        $where['b.isdelete'] = 0;
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $options['order'] = 'a.id DESC';
        $result = $this->queryList($options);
        return $result;
    }

    /**
     * 领券后添加一条记录
     * @param int $memberId
     * @param int $centerId
     * @param int $memberCouponsId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-15 15:20:09
     * Update: 2017-12-15 15:20:09
     * Version: 1.00
     */
    public function addMemberCenter($memberId = 0, $centerId = 0, $memberCouponsId = 0)
    {
        $data = [];
        $data['member_id'] = $memberId;
        $data['center_id'] = $centerId;
        $data['member_coupons_id'] = $memberCouponsId;
        $data['create_time'] = NOW_TIME;
        return $this->addData([], $data);
    }

    /**
     * 获取用户已经 领取的优惠券列表
     * @param int $memberId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-18 06:34:49
     * Update: 2017-12-18 06:34:49
     * Version: 1.00
     */
    public function getMemberHasTakeCoupons($memberId = 0)
    {
        $where = [];
        $where['a.member_id'] = $memberId;
        $where['b.isdelete'] = 0;
        $nowTime = NOW_TIME;
        // 查找未使用 或者 未过期
//        $where['_string'] = "(b.state = 0) OR (b.limit_time_type != 1 AND b.end_time > {$nowTime}) OR (b.limit_time_type == 1)";
        $field = [
            'a.id,a.center_id,a.member_coupons_id',
            'b.state,b.limit_time_type,b.limit_time,b.end_time'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['where'] = $where;
        $options['field'] = implode(',', $field);
        $options['join'] = [
            '__MB_MEMBERCOUPONS__ b ON a.member_coupons_id = b.id'
        ];
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            // 过期状态
            switch ((int)$value['limit_time_type']) {
                case 2:
                case 3:
                    // 是否已经过期了 1-未过期 2-过期
                    $list[$key]['time_out_status'] = $value['end_time'] <= NOW_TIME ? 2 : 1;
                    break;
                default:
                    $list[$key]['time_out_status'] = 1;
                    break;
            }

            // 是否已过期或者已使用 1-已过期或者已使用 2-未过期未使用
            if ($value['state'] == 1) {
                $list[$key]['has_use_or_time_out_status'] = 1;
            } else {
                $list[$key]['has_use_or_time_out_status'] = 2;
                // 未使用 过期
                if ($list[$key]['time_out_status'] == 2) {
                    $list[$key]['has_use_or_time_out_status'] = 1;
                }
            }
        }
        return getReturn(200, '', $list);
    }
}