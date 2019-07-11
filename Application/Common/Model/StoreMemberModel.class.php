<?php

namespace Common\Model;

class StoreMemberModel extends BaseModel
{
    protected $tableName = 'mb_storemember';

    /**
     * 获取商家的会员总数
     * @param int $storeId storeId
     * @param array $condition 额外的查询条件 因为查询有两张表查询条家里 需要 a.xxx b.xxx
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-28 08:48:14
     * Version: 1.0
     */
    public function getMemberNum($storeId = 0, $condition = [])
    {
        if ((int)$storeId <= 0) return getReturn(200, '', 0);
        $queryStoreId = D('Store')->getStoreQueryId($storeId)['data'];
        if (empty($queryStoreId)) return getReturn(200, '', 0);
        $arr = explode(',', $queryStoreId);
        $where = [];
        $where['a.isdelete'] = 0;
        $where['a.store_id'] = ['in', $queryStoreId];
        $where['b.isdelete'] = 0;
        $where = array_merge($where, $condition);
        if (count($arr) > 1) {
            // 商城
            $list = $this
                ->alias('a')
                ->field('a.member_id')
                ->where($where)
                ->join('__MEMBER__ b ON b.member_id = a.member_id')
                ->group('a.member_id')
                ->select();
            if (false === $list) {
                logWrite("查询商家{$queryStoreId}的会员数量出错:" . $this->getDbError());
                return getReturn();
            }
            $count = count($list);
        } else {
            // 非商城
            $count = $this
                ->alias('a')
                ->where($where)
                ->join('__MEMBER__ b ON b.member_id = a.member_id')
                ->count();
        }
        return getReturn(200, '', $count);
    }

    /**
     * 获取今日新增会员数量
     * 即 register_date 范围在今日
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-28 09:29:59
     * Version: 1.0
     */
    public function getTodayNewMemberNum($storeId = 0)
    {
        $time = getStartAndEndTime();
        $startTime = $time['start_time'];
        $endTime = $time['end_time'];
        $where = [];
        $where['a.register_date'] = ['between', [$startTime, $endTime]];
        return $this->getMemberNum($storeId, $where);
    }

    /**
     * 获取会员的直推、间推、三级关系
     * @param int $storeId
     * @param int $memberId
     * @return array
     * User: hjun
     * Date: 2018-12-10 11:26:26
     * Update: 2018-12-10 11:26:26
     * Version: 1.00
     */
    public function getRelationData($storeId = 0, $memberId = 0)
    {
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        $storeId = $storeInfo['main_store_id'];
        $relation = S("store_member_relation:{$storeId}{$memberId}");
        if (empty($relation)) {
            $field = [
                'a.member_id',
                'b.member_id one_member_id',
                'c.member_id two_member_id',
                'd.member_id three_member_id',
            ];
            $where = [];
            $where['a.store_id'] = $storeId;
            $where['a.member_id'] = $memberId;
            $where['a.isdelete'] = 0;
            $join = [
                "LEFT JOIN __MB_STOREMEMBER__ b ON a.member_id = b.recommend_id AND b.store_id = {$storeId} AND b.isdelete = 0",
                "LEFT JOIN __MB_STOREMEMBER__ c ON b.member_id = c.recommend_id AND c.store_id = {$storeId} AND c.isdelete = 0",
                "LEFT JOIN __MB_STOREMEMBER__ d ON c.member_id = d.recommend_id AND d.store_id = {$storeId} AND d.isdelete = 0",
            ];
            $order = 'd.member_id DESC,c.member_id DESC,b.member_id DESC';
            $options = [];
            $options['alias'] = 'a';
            $options['field'] = $field;
            $options['where'] = $where;
            $options['join'] = $join;
            $options['order'] = $order;
            $relation = $this->selectRow($options);
            S("store_member_relation:{$storeId}{$memberId}", $relation);
        }
        return $relation;
    }

    /**
     * 获取商家 代理商列表
     * @param int $storeId 商家ID  不管是商城还是子店还是单店 都是查自己店的会员
     * @param int $channelId 渠道ID
     * @param int $page 页数
     * @param int $limit 条数
     * @param array $condition
     * @param array $otherOptions
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-10 16:35:32
     * Update: 2018-01-10 16:35:32
     * Version: 1.00
     */
    public function getStoreGroupMember($storeId = 0, $channelId = 0, $page = 1, $limit = 0, $condition = [], $otherOptions = [])
    {
        $where = [];
        if ($storeId > 0) $where['a.store_id'] = $storeId;
        if ($channelId > 0) $where['a.channel_id'] = $channelId;
        $where['a.group_id'] = ['gt', 0];
        $where['a.isdelete'] = 0;
        // 申请记录表的条件不限制
        // $where['d.store_id'] = $storeId;
        // $where['d.through'] = 1;
        $where['c.group_id'] = ['exp', 'IS NOT NULL'];
        $where = array_merge($where, $condition);
        $field = [
            'a.store_id,a.member_id,a.othername', 'a.group_time',
            'b.member_name,b.member_nickname',
            'c.group_name',
            'd.card_name,d.card_number,d.tel,d.wx_number',
            'd.id,d.remark,d.create_time,d.update_time'
        ];
        $join = [
            '__MEMBER__ b ON a.member_id = b.member_id',
            'LEFT JOIN __MB_STOREGROUP__ c ON a.group_id = c.group_id',
            'LEFT JOIN __MB_MEMBERGROUP__ d ON a.member_id = d.member_id AND d.store_id = a.store_id AND d.through = 1',
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = implode(',', $field);
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['join'] = $join;
        $options['order'] = 'a.member_id DESC';
        $options = array_merge($options, $otherOptions);
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $condition = [];
        $condition['get_recommend_count'] = true;
        $condition['time_field'] = [
            'create_time' => [],
            'update_time' => [],
            'group_time' => [],
        ];
        $condition['empty_field'] = [
            'member_truename' => '',
            'member_tel' => '',
            'card_name' => '',
            'card_number' => '',
            'tel' => '',
            'wx_number' => '',
            'id' => 0,
            'remark' => '',
            'update_time' => 0,
            'create_time' => 0,
            'group_time' => 0,
        ];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value, $condition);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 获取会员信息
     * @param int $storeId
     * @param int $memberId
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-31 16:52:52
     * Update: 2018-01-31 16:52:52
     * Version: 1.00
     */
    public function getStoreMemberInfo($storeId = 0, $memberId = 0, $condition = [])
    {
        $field = [
            'a.store_id,a.member_id', 'a.group_time',
            'b.group_id', 'b.recommend_group_id', 'b.weight'
        ];
        $join = [
            'LEFT JOIN __MB_STOREGROUP__ b ON a.group_id = b.group_id'
        ];
        $field = implode(',', $field);
        $where = [];
        $where['a.store_id'] = $storeId;
        $where['a.member_id'] = $memberId;
        $where['a.isdelete'] = 0;
        $where = array_merge($where, $condition);
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $result = $this->queryRow($options);
        $info = $result['data'];
        return empty($info) ? [] : $info;
    }

    /**
     * 获取商家会员的余额
     * @param int $memberId
     * @return double
     * User: hjun
     * Date: 2018-12-05 17:35:06
     * Update: 2018-12-05 17:35:06
     * Version: 1.00
     */
    public function getStoreMemberBalance($memberId = 0)
    {
        $field = ['balance'];
        $where = [];
        $where['store_id'] = $this->getStoreId();
        $where['member_id'] = $memberId;
        $where['isdelete'] = NOT_DELETE;
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        $info = $this->selectRow($options);
        $balance = empty($info['balance']) ? '0.00' : $info['balance'];
        return $balance;
    }

    /**
     * 获取openId
     * @param int $storeId
     * @param int $memberId
     * @return string
     * User: hjun
     * Date: 2018-08-27 17:41:33
     * Update: 2018-08-27 17:41:33
     * Version: 1.00
     */
    public function getStoreMemberOpenId($storeId = 0, $memberId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['member_id'] = $memberId;
        $openId = $this->where($where)->getField('wx_openid');
        return empty($openId) ? '' : $openId;
    }

    /**
     * 处理数据
     * @param array $info
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-10 16:50:38
     * Update: 2018-01-10 16:50:38
     * Version: 1.00
     */
    public function transformInfo($info = [], $condition = [])
    {
        $info = parent::transformInfo($info, $condition);
        // 备注或昵称
        $name = empty($info['othername']) ? $info['member_nickname'] : $info['othername'];
        $info['other_name'] = empty($name) ? '' : $name;

        // 发展会员数
        if (isset($condition['get_recommend_count']) &&
            $condition['get_recommend_count'] &&
            isset($info['member_id']) &&
            isset($info['store_id'])) {
            $info['recommend_count'] = D('Member')->getMemberRelationCount($info['member_id'], $info['store_id']);
        }

        return $info;
    }

    /**
     * 查询会员关系列表
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array
     * User: hjun
     * Date: 2018-06-14 16:53:10
     * Update: 2018-06-14 16:53:10
     * Version: 1.00
     */
    public function getRelationList($storeId = 0, $page = 1, $limit = 20, $condition = [])
    {
        /*
         * member_id 会员编号
         * member_name 会员帐号
         * member_nickname 会员昵称
         * recommend_id 会员的推荐人
         * recommend_name 会员的推荐人帐号
         * tui_name 会员的推荐人昵称
         * children_num 推广人数
         * member_time 加入时间
         */
        $where = [];
        $where['a.store_id'] = $storeId;
        $where['a.shield'] = 0;
        $where['b.isdelete'] = 0;
        $where = array_merge($where, $condition);
        $field = [
            'b.member_id', 'b.member_name', 'b.member_nickname', 'b.member_time',
            'c.member_id recommend_id', 'c.member_name recommend_name', 'c.member_nickname as tui_name',
            'COUNT(e.member_id) children_num'
        ];
        $field = implode(',', $field);
        // 如果要查下一级人数 则得是LEFT JOIN
        if (!empty($condition['b.recommend_id'])) {
            $joinSuffix = 'LEFT JOIN ';
        } else {
            $joinSuffix = '';
        }
        $join = [];
        $join[] = '__MEMBER__ b ON a.member_id = b.member_id'; // 查询会员自己的信息
        $join[] = 'LEFT JOIN __MEMBER__ c ON b.recommend_id = c.member_id AND c.isdelete = 0'; // 查询会员的推荐人信息
        $join[] = "{$joinSuffix}__MEMBER__ d ON b.member_id = d.recommend_id AND d.isdelete = 0"; // 查询会员的推广人信息
        $join[] = "{$joinSuffix}__MB_STOREMEMBER__ e ON d.member_id = e.member_id AND e.shield = 0 AND e.store_id = {$storeId}"; // 会员的推广人得是该商家的会员
        $totalList = $this
            ->alias('a')
            ->join($join)
            ->where($where)->field($field)->order('b.member_time DESC')->group('a.member_id')->select();
        $list = $this->getListByPage($page, $limit, $totalList);
        $total = count($totalList);
        return ['list' => $list, 'total' => $total];
    }

    /**
     * 获取会员商家列表
     * @param int $memberId
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-01 17:57:27
     * Update: 2018-02-01 17:57:27
     * Version: 1.00
     */
    public function getMemberStoreList($memberId = 0, $condition = [])
    {
        $field = [
            'a.store_id,a.member_id,a.group_id',
            'b.discount,b.discount_type,b.store_group_price_id',
            'c.vip_level,c.discount vip_discount'
        ];
        $join = [
            'LEFT JOIN __MB_STOREGROUP__ b ON a.group_id = b.group_id',
            'LEFT JOIN __MB_STOREVIP__ c ON a.level = c.vip_level AND a.store_id = c.store_id'
        ];
        $where = [];
        $where['a.member_id'] = $memberId;
        $where = array_merge($where, $condition);
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = implode(',', $field);
        $options['join'] = $join;
        $options['where'] = $where;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return [];
        return $result['data']['list'];
    }

    public function focusStore($storeId, $memberId)
    {
        M('mb_storemember')
            ->insert(array('store_id' => $storeId, 'member_id' => $memberId));
        $storeMemberData = M("mb_storemember")
            ->where(array('store_id' => $storeId, 'member_id' => $memberId))->find();
        return getReturn(200, "成功", $storeMemberData);
    }

    /**
     * 获取会员在商家的VIP等级
     * @param int $memberId
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-19 11:19:10
     * Version: 1.0
     */
    public function getMemberVip($memberId = 0, $storeId = 0)
    {
        // 2018-08-20 16:54:17 增加积分的查询
        $default = ['member_id' => $memberId, 'store_id' => $storeId, 'level' => 0, 'vip_values' => 0, 'sum_score' => 0];
        $where = [];
        $where['store_id'] = $storeId;
        $where['member_id'] = $memberId;
        $field = ['member_id,store_id,level,vip_values,sum_score'];
        $field = implode(',', $field);
        $info = $this->field($field)->where($where)->find();
        if (false === $info) {
            logWrite("查询会员{$memberId}在商家{$storeId}的VIP等级出错:" . $this->getDbError());
            return getReturn();
        }
        return empty($info) ? getReturn(200, '', $default) : getReturn(200, '', $info);
    }

    /**
     * 设置会员的首单ID
     * @param int $storeId
     * @param int $memberId
     * @param int $orderId
     * @return boolean
     * User: hjun
     * Date: 2018-08-15 17:44:29
     * Update: 2018-08-15 17:44:29
     * Version: 1.00
     */
    public function setStoreMemberFirstOrder($storeId = 0, $memberId = 0, $orderId = 0)
    {
        if ($storeId > 0 && $memberId > 0 && $orderId > 0) {
            $where = [];
            $where['store_id'] = $storeId;
            $where['member_id'] = $memberId;
            $where['isdelete'] = 0;
            $options = [];
            $options['where'] = $where;
            $options['field'] = 'first_order_id';
            $info = $this->queryRow($options)['data'];
            $data = [];
            $data['first_order_id'] = $orderId;
            // 没有设置过首单,说明该笔订单是首单
            if (empty($info['first_order_id'])) {
                return $this->where($where)->save($data);
            }
            return true;
        }
        return false;
    }

    /**
     * 设置成为代理分组
     * @param int $storeId
     * @param int $memberId
     * @param int $groupId
     * @return mixed
     * User: hjun
     * Date: 2018-11-29 18:51:28
     * Update: 2018-11-29 18:51:28
     * Version: 1.00
     */
    public function setGroup($storeId = 0, $memberId = 0, $groupId = 0)
    {
        $data = [];
        $data['group_id'] = $groupId > 0 ? $groupId : 0;
        if ($groupId > 0) {
            $oldInfo = $this->getStoreMemberInfo($storeId, $memberId);
            if (empty($oldInfo['group_time'])) {
                $data['group_time'] = NOW_TIME;
            }
        }
        $data['version'] = $this->max('version') + 1;
        $where = [];
        $where['store_id'] = $storeId;
        $where['member_id'] = $memberId;
        $result = $this->where($where)->save($data);
        return $result;
    }

    /**
     * 设置代理分组
     * @param int $storeId
     * @param int $memberId
     * @param int $groupId
     * @param int $recordId 申请记录ID
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-11-29 18:43:40
     * Update: 2018-11-29 18:43:40
     * Version: 1.00
     */
    public function setAgentGroup($storeId = 0, $memberId = 0, $groupId = 0, $recordId = 0)
    {
        // 参数处理
        $memberGroup = D('MemberGroup');
        if ($recordId > 0) {
            $record = D('MemberGroup')->getRecord($recordId);
            if (empty($record)) {
                return getReturn(CODE_ERROR, '申请记录不存在');
            }
            $storeId = $record['store_id'];
            $memberId = $record['member_id'];
            if (empty($storeId) || empty($memberId)) {
                return getReturn(CODE_ERROR, '申请记录无效');
            }
        } else {
            $record = $memberGroup->getActiveMemberGroupRecord($storeId, $memberId);
        }

        // 逻辑处理
        $oldInfo = $this->getStoreMemberInfo($storeId, $memberId);
        $this->startTrans();
        $results = [];
        // 设置代理商
        $results[] = $this->setGroup($storeId, $memberId, $groupId);
        if ($groupId > 0 && $groupId != $oldInfo['group_id']) {
            $groupName = D('StoreGroup')->where(['group_id' => $groupId])->getField('group_name');
            if (empty($groupName)) {
                $this->rollback();
                return getReturn(CODE_ERROR, '选择的代理分组已失效');
            }

            $data = [];
            $data['store_id'] = $storeId;
            $data['member_id'] = $memberId;
            $data['group_id'] = $groupId;
            $data['update_time'] = NOW_TIME;
            $data['group_name'] = $groupName;
            $data['through'] = 1;
            if (!empty($record)) {
                $results[] = $memberGroup->where(['id' => $record['id']])->save($data);
            } else {
                $memberInfo = D('Member')->getMemberInfo($memberId)['data'];
                $data['member_name'] = $memberInfo['member_name'];
                $data['create_time'] = NOW_TIME;
                $results[] = $memberGroup->add($data);
            }
        } elseif ($groupId <= 0) {
            if ($oldInfo['group_id'] > 0) {
                // 没有选择代理商 并且之前是代理商  申请表设置为未通过
                $results[] = $memberGroup->rollbackMemberGroup($storeId, $memberId);
            } elseif ($recordId > 0) {
                $where = [];
                $where['id'] = $recordId;
                $data = [];
                $data['through'] = 2;
                $data['update_time'] = NOW_TIME;
                $results[] = $memberGroup->where($where)->save($data);
            }
        }
        if (isTransFail($results)) {
            $this->rollback();
            return getReturn(CODE_ERROR);
        }
        $this->commit();
        $data = [];
        $data['store_id'] = $storeId;
        $data['member_id'] = $memberId;
        $data['group_id'] = $groupId;
        $data['group_name'] = $groupName;
        return getReturn(CODE_SUCCESS, 'success', $data);
    }

    /**
     * 推广的会员自动移至某个代理分组
     * @param int $storeId
     * @param int $memberId
     * @param int $recommendId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-04 11:50:06
     * Update: 2018-12-04 11:50:06
     * Version: 1.00
     */
    public function autoRecommendGroup($storeId = 0, $memberId = 0, $recommendId = 0)
    {
        if ($recommendId > 0) {
            // 1. 先查出推荐人所在的代理分组是否有设置推广分组
            $recommendStoreMember = $this->getStoreMemberInfo($storeId, $recommendId);
            if ($recommendStoreMember['recommend_group_id'] > 0) {
                // 2. 如果设置了推广分组 则将当前会员设置为该分组
                $result = $this->setAgentGroup($storeId, $memberId, $recommendStoreMember['recommend_group_id']);
                logWrite("推广的会员自动移至分组,结果:" . jsonEncode($result));
                return $result;
            }
        }
        return getReturn(CODE_SUCCESS, 'success');
    }

    /**
     * 营销礼包 自动升级至某个代理分组
     * @param int $storeId
     * @param int $memberId
     * @param array $marketData
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-04 18:06:31
     * Update: 2018-12-04 18:06:31
     * Version: 1.00
     */
    public function autoMarketGroup($storeId = 0, $memberId = 0, $marketData = [])
    {
        // 如果礼包有选择升级分组
        if ($marketData['select_group'] == 1) {
            $groupContent = jsonDecodeToArr($marketData['group_content']);
            $groupId = $groupContent['group_id'];
            if ($groupId > 0) {
                $storeMember = $this->getStoreMemberInfo($storeId, $memberId);
                $groupModel = D('StoreGroup')->setStoreId($storeId);
                $newGroup = $groupModel->getGroup($groupId);
                // 如果分组存在 并且分组不同 并且权重大于等于会员之前所在分组的权重 则升级
                if (!empty($newGroup) &&
                    $groupId != $storeMember['group_id'] &&
                    (int)$newGroup['weight'] >= (int)$storeMember['weight']) {
                    return $this->setAgentGroup($storeId, $memberId, $groupId);
                }
            }
        }
        return getReturn(CODE_SUCCESS, 'success');
    }
}