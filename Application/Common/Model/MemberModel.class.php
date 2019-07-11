<?php

namespace Common\Model;
class MemberModel extends BaseModel
{
    protected $tableName = 'member';

    /**
     * 获取会员信息 ID 迅信号 昵称 密码
     * @param int $memberId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-23 21:23:33
     * Version: 1.0
     */
    public function getMemberInfo($memberId = 0)
    {
        $info = S("{$memberId}_memberInfo");
        if (empty($info)) {
            $where = [];
            $where['member_id'] = $memberId;
            $info = $this->field(true)->where($where)->find();
            if (false === $info) {
                logWrite("查询用户{$memberId}信息出错:" . $this->getDbError());
                return getReturn();
            }
            if (empty($info)) return getReturn(CODE_NOT_FOUND, '用户不存在');
            if (empty($info['member_nickname'])) {
                $info['member_nickname'] = $info['member_name'];
            }
            S("{$memberId}_memberInfo", $info);
        }
        return getReturn(200, '', $info);
    }

    /**
     * 获取会员的直推会员数量(在指定的商家)
     * @param int $memberId 会员ID
     * @param int $storeId 直推会员关注的商家
     * @return mixed
     * User: hjun
     * Date: 2018-01-11 11:56:05
     * Update: 2018-01-11 11:56:05
     * Version: 1.00
     */
    public function getMemberRelationCount($memberId = 0, $storeId = 0)
    {
        if (empty($memberId) || empty($storeId)) {
            return 0;
        }
        $model = D('StoreMember');
        $where = [];
        $where['a.isdelete'] = 0;
        $where['a.recommend_id'] = $memberId;
        $where['a.store_id'] = $storeId;
        $options = [];
        $options['alias'] = 'a';
        $options['where'] = $where;
        return $model->queryCount($options);
    }

    /**
     * 获取会员的直推会员ID数组
     * @param int $memberId
     * @param int $storeId
     * @param int $type 1-包括自己 2-不包括自己 3-只有自己 4-间推 5-三维
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-11 14:48:13
     * Update: 2018-01-11 14:48:13
     * Version: 1.00
     */
    public function getMemberRelationId($memberId = 0, $storeId = 0, $type = 1)
    {
        if (empty($memberId) || empty($storeId)) {
            return getReturn(-1, C('INVALID_PARAM'));
        }
        if ($type == 3) {
            return getReturn(200, '', [$memberId]);
        }
        $model = D('StoreMember');
        $where = [];
        $where['a.isdelete'] = 0;
        $where['a.member_id'] = $memberId;
        $where['a.store_id'] = $storeId;
        $field = [];
        $join = [];
        $field[] = 'a.member_id';
        $field[] = 'b.member_id one';
        $join[] = "__MB_STOREMEMBER__ b ON a.member_id = b.recommend_id AND b.isdelete = 0 AND b.store_id = {$storeId}";
        if ($type >= 4) {
            $field[] = 'c.member_id two';
            $join[] = "__MB_STOREMEMBER__ c ON b.member_id = c.recommend_id AND c.isdelete = 0 AND b.store_id = {$storeId}";
        }
        if ($type >= 5) {
            $field[] = 'd.member_id three';
            $join[] = "__MB_STOREMEMBER__ d ON c.member_id = d.recommend_id AND d.isdelete = 0 AND b.store_id = {$storeId}";
        }
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = implode(',', $field);
        $options['where'] = $where;
        $options['join'] = $join;
        $result = $model->queryList($options);
        if ($result['code'] !== 200) {
            return $result;
        }
        $list = $result['data']['list'];
        $id = [];
        switch ((int)$type) {
            case 1:
            case 2:
                foreach ($list as $member) {
                    $id[] = $member['one'];
                }
                if ($type == 1) {
                    $id[] = $memberId;
                }
                break;
            case 3:
                return [$memberId];
                break;
            case 4:
                foreach ($list as $member) {
                    $id[] = $member['two'];
                }
                break;
            case 5:
                foreach ($list as $member) {
                    $id[] = $member['three'];
                }
                break;
            default:
                break;
        }
        return getReturn(200, '', $id);

    }

    /**
     * 获取会员的直推会员列表 (在指定的商家)
     * @param int $memberId
     * @param int $storeId 直推会员关注的商家
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-11 11:34:01
     * Update: 2018-01-11 11:34:01
     * Version: 1.00
     */
    public function getMemberRelationList($memberId = 0, $storeId = 0, $page = 1, $limit = 0, $condition = [])
    {
        if (empty($memberId) || empty($storeId)) {
            return getReturn(-1, L('INVALID_PARAM'));
        }
        $model = D('StoreMember');
        $where = [];
        $where['a.recommend_id'] = $memberId;
        $where['a.isdelete'] = 0;
        $where['a.store_id'] = $storeId;
        $where = array_merge($where, $condition);
        $field = [
            'a.member_id,b.member_name,b.member_nickname',
            'a.level,a.register_date'
        ];
        $join = [
            '__MEMBER__ b ON a.member_id = b.member_id'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['join'] = $join;
        $options['where'] = $where;
        $options['field'] = implode(',', $field);
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = 'a.member_id DESC';
        $result = $model->queryList($options);
        $list = $result['data']['list'];
        $condition = [];
        $condition['empty_field'] = [
            'member_nickname' => ''
        ];
        $condition['time_field'] = [
            'register_date' => ['Y-m-d H:i:s']
        ];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value, $condition);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 处理数据
     * @param array $info
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-11 11:43:47
     * Update: 2018-01-11 11:43:47
     * Version: 1.00
     */
    public function transformInfo($info = [], $condition = [])
    {
        $info = parent::transformInfo($info, $condition);

        // 等级
        if (isset($info['level'])) {
            if ($info['level'] > 0) {
                $info['level_name'] = "VIP{$info['level']}";
            } else {
                $info['level_name'] = "普通会员";
            }
        }
        return $info;
    }

    /**
     * 获取会员直推业绩报表
     * @param int $memberId
     * @param int $storeId
     * @param int $type
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-11 15:03:38
     * Update: 2018-01-11 15:03:38
     * Version: 1.00
     */
    public function getMemberRelationOrderList($memberId = 0, $storeId = 0, $type = 1, $page = 1, $limit = 0, $condition = [])
    {
        if (empty($memberId) || empty($storeId)) {
            return getReturn(-1, L('INVALID_PARAM'));
        }
        $result = $this->getMemberRelationId($memberId, $storeId, $type);
        if ($result['code'] !== 200) {
            return $result;
        }
        $memberIdArr = $result['data'];
        $where = [];
        $where['a.buyer_id'] = ['in', implode(',', $memberIdArr)];
        $where = array_merge($where, $condition);
        $queryStoreId = D('Store')->getStoreQueryId($storeId, 2);
        $model = D('Order');
        return $model->getOrderList($queryStoreId, $page, $limit, $where);
    }

    public function setMemberVersion()
    {
        set_time_limit(300);
        $options = [];
        $options['field'] = 'version';
        $options['group'] = 'version';
        $options['having'] = 'COUNT(*) > 10';
        $result = $this->queryField($options, 'version', true);
        if ($result['code'] !== 200) return $result;
        $version = $result['data'];
        if (empty($version)) return getReturn(200, '没有需要设置的会员');
        $where = [];
        $where['version'] = ['in', $version];
        $options = [];
        $options['where'] = $where;
        $total = $this->queryCount($options);
        $count = ceil($total / 1000);
        $page = 1;
        $maxVersion = $this->max('version');
        $data = [];
        $allOptions = [];
        do {
            $options['page'] = $page;
            $options['limit'] = 1000;
            $options['field'] = 'member_id';
            $result = $this->queryList($options);
            if ($result['code'] !== 200) return $result;
            $list = $result['data']['list'];
            foreach ($list as $key => $value) {
                $item = [];
                $itemOption['where'] = ['member_id' => $value['member_id']];
                $allOptions[] = $itemOption;
                $item['member_id'] = $value['member_id'];
                $item['version'] = ++$maxVersion;
                $data[] = $item;
            }
            $page++;
        } while ($page <= $count);
        return $this->saveAllData($allOptions, $data);
    }

    /**
     * 获取商家会员折扣
     * @param int $memberId
     * @param int $storeId
     * @return int
     * User: hjun
     * Date: 2018-04-03 18:18:44
     * Update: 2018-04-03 18:18:44
     * Version: 1.00
     */
    public function getMemberVipDiscount($memberId = 0, $storeId = 0)
    {
        $storeInfo = M('Store')->field('member_vip')->find($storeId);
        if ($storeInfo['member_vip'] == 0) return 1;
        $where = [];
        $where['member_id'] = $memberId;
        $where['store_id'] = $storeId;
        $smInfo = M('mb_storemember')->field('level')->where($where)->find();
        $level = $smInfo['level'];
        if (!($level > 0)) return 1;
        $where = [];
        $where['store_id'] = $storeId;
        $where['vip_level'] = $level;
        $svInfo = M('mb_storevip')->where($where)->find();
        $discount = $svInfo['discount'];
        if (!($discount > 0 && $discount < 10)) return 1;
        return round($discount / 10, 2);
    }

    /**
     * 获取会员代理商折扣
     * @param int $memberId
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-04-03 20:04:51
     * Update: 2018-04-03 20:04:51
     * Version: 1.00
     */
    public function getMemberAgentDiscount($memberId = 0, $storeId = 0)
    {
        $result = [];
        $result['type'] = 4;
        $result['discount'] = 1;
        $storeInfo = D('StoreGrade')->getStoreGrantInfo($storeId)['data'];
        if (!($storeInfo['partner_ctrl'] == 1)) return $result;
        $where = [];
        $where['a.member_id'] = $memberId;
        $where['a.store_id'] = $storeId;
        $smInfo = M('mb_storemember')
            ->alias('a')
            ->field('b.discount_type,b.store_group_price_id,b.discount')
            ->join('__MB_STOREGROUP__ b ON a.group_id = b.group_id')
            ->where($where)
            ->find();
        if (empty($smInfo)) return $result;
        $result['type'] = $smInfo['discount_type'];
        $discount = $smInfo['discount'] > 0 && $smInfo['discount'] < 10 ? round($smInfo['discount'] / 10, 2) : 1;
        $result['discount'] = $discount;
        $result['store_group_price_id'] = $smInfo['store_group_price_id'];
        return $result;
    }

    /**
     * 获取会员等级
     * @param int $memberId
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-19 11:11:51
     * Version: 1.0
     */
    public function getMemberVip($memberId = 0, $storeId = 0)
    {
        return D('StoreMember')->getMemberVip($memberId, $storeId);
    }

    /**
     * 会员等级与商家等级进行比较 返回会员当前等级的描述
     * @param array $memberVip
     *  ['level'=>0, 'vip_values'=>0] 会员等级、消费金额
     * @param array $storeVip
     *  [
     *      ['vip_level'=>1,'vip_price'=>0,'discount'=>9.8] 等级、需要消费的金额、享受折扣
     *  ]
     * @return string 描述
     * User: hj
     * Date: 2017-09-20 19:56:29
     * Version: 1.0
     */
    public function getVipDesc(&$memberVip = [], $storeVip = [])
    {
        if (empty($storeVip) || empty($memberVip)) return '目前全店购物不享受打折';

        // hjun 根据等级加上缺少的数值 因为升级后会清空VIP值
        if ($memberVip['level'] > 0) {
            foreach ($storeVip as $value) {
                if ($value['vip_level'] == $memberVip['level']) {
                    $memberVip['vip_values'] += $value['vip_price'];
                }
            }
        }

        $desc = '';
        foreach ($storeVip as $key => $value) {
            if ($memberVip['level'] >= $value['vip_level']) {
                // 当前折扣
                $currentDis = $value['discount'];
                // 还需消费的金额
                $needMoney = round($storeVip[$key + 1]['vip_price'] - $memberVip['vip_values'], 2);
                // 下一等级
                $nextLevel = $storeVip[$key + 1]['vip_level'];
                // 没有下一等级就显示当前
                $msg1 = $currentDis == 1 ? "目前全店购物不享受打折" : "目前全店购物享受{$currentDis}折";
                // hj 2017-09-29 22:37:55  修改描述
                $msg1 = '';
                $msg2 = empty($nextLevel) ? "" : "距离下一级还需消费{$needMoney}";
                $desc = "$msg1$msg2";
            } else {
                // 当前折扣
                $currentDis = $storeVip[$key - 1]['discount'];
                // 还需消费的金额
                $needMoney = round($value['vip_price'] - $memberVip['vip_values'], 2);
                // 下一等级
                $nextLevel = $value['vip_level'];
                $msg = empty($currentDis) || $currentDis == 1 ? "目前全店购物不享受打折" : "目前全店购物享受{$currentDis}折";
                $msg = '';
                $desc = "距离下一级还需消费{$needMoney}";
                // 等级低直接break 不需要循环下一等级
                break;
            }
        }
        $memberVip['next_need_money'] = $needMoney;
        $memberVip['current_discount'] = $currentDis;
        return $desc;
    }

    /**
     * 根据名称模糊查找会员ID
     * @param string $name
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-09-15 16:23:22
     * Update: 2018-09-15 16:23:22
     * Version: 1.00
     */
    public function getMemberIdsByName($name = '')
    {
        $where = [];
        $where['member_name'] = ['like', "%{$name}%"];
        $where['member_nickname'] = ['like', "%{$name}%"];
        $where['_logic'] = 'or';
        $ids = $this->where($where)->getField('member_id', true);
        return empty($ids) ? [] : $ids;
    }
}