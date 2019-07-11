<?php

namespace Common\Model;
class MemberGroupModel extends BaseModel
{
    protected $tableName = 'mb_membergroup';

    // card_name,card_number,tel,wx_number,remark
    protected $_validate = [
        ['card_name', 'require', '请输入姓名', 0, 'regex', 3],
        ['card_number', 'require', '请输入身份证号码', 0, 'regex', 3],
        ['tel', 'require', '请输入联系电话', 0, 'regex', 3],
    ];

    /**
     * 获取申请时的代理资料
     * @param int $memberId
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-11 11:00:53
     * Update: 2018-01-11 11:00:53
     * Version: 1.00
     */
    public function getGroupMemberInfo($memberId = 0, $storeId = 0)
    {
        if (empty($memberId) || empty($storeId)) {
            return getReturn(-1, L('INVALID_PARAM'));
        }

        $where = [];
        $where['member_id'] = $memberId;
        $where['store_id'] = $storeId;
        $field = [
            'card_name,card_number,tel,wx_number,remark'
        ];
        $options = [];
        $options['where'] = $where;
        $options['field'] = implode(',', $field);
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) {
            return $result;
        }
        $info = $result['data'];
        if (empty($info)) {
            return getReturn(-1, '资料不存在');
        }
        $condition = [];
        $condition['empty_field'] = [
            'card_name' => '',
            'card_number' => '',
            'tel' => '',
            'wx_number' => '',
            'remark' => '',
        ];
        $this->transformInfo($info, $condition);
        $result['data'] = $info;
        return $result;
    }

    /**
     * 更新代理资料
     * @param int $id
     * @param int $storeId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-11 11:09:46
     * Update: 2018-01-11 11:09:46
     * Version: 1.00
     */
    public function saveGroupMemberInfo($id, $storeId = 0, $data = [])
    {
        if (empty($storeId)) {
            return getReturn(-1, L('INVALID_PARAM'));
        }
        if ($id > 0) {
            $where = [];
            $where['id'] = $id;
            $where['store_id'] = $storeId;
            $field = [
                'card_name,card_number,tel,wx_number,remark'
            ];
            $options = [];
            $options['where'] = $where;
            $options['field'] = implode(',', $field);
            return $this->saveData($options, $data);
        } else {
            // 已经更改为代理商后 再设置的申请资料 则新增记录
            $field = [
                'store_id', 'member_id', 'member_name', 'group_id',
                'group_name', 'through', 'card_name', 'card_number',
                'tel', 'wx_number', 'remark', 'create_time', 'update_time'
            ];
            $data['through'] = 1;
            $data['create_time'] = $data['update_time'] = NOW_TIME;
            $options = [];
            $options['field'] = implode(',', $field);
            return $this->addData($options, $data);
        }
    }

    /**
     * 获取申请记录列表
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-11 15:23:06
     * Update: 2018-01-11 15:23:06
     * Version: 1.00
     */
    public function getGroupMemberList($storeId = 0, $page = 1, $limit = 0, $condition = [])
    {
        if (empty($storeId)) {
            return getReturn(-1, L('INVALID_PARAM'));
        }
        $where = [];
        $where['a.store_id'] = $storeId;
        $where = array_merge($where, $condition);
        $field = [
            'a.id,a.store_id,a.member_id,a.card_name,a.card_number',
            'a.tel,a.wx_number,a.create_time,a.through,a.update_time',
            'a.store_name', 'a.member_name', 'a.is_diy',
            'b.member_nickname',
        ];
        $join = [
            "LEFT JOIN __MEMBER__ b ON a.member_id = b.member_id"
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['where'] = $where;
        $options['join'] = $join;
        $options['field'] = implode(',', $field);
        $options['order'] = 'a.create_time DESC';
        $options['page'] = $page;
        $options['limit'] = $limit;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) {
            return $result;
        }
        $list = $result['data']['list'];
        $condition = [];
        $condition['empty_field'] = [
            'cart_name' => '',
            'card_number' => '',
            'tel' => '',
            'wx_number' => '',
        ];
        $condition['time_field'] = [
            'create_time' => [],
            'update_time' => [],
        ];
        $condition['map_field'] = [
            'through' => [
                1 => '已通过',
                2 => '已拒绝',
            ]
        ];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value, $condition);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 审核代理商申请
     * @param int $id
     * @param int $storeId
     * @param int $groupId 分组ID
     * @param int $through 1-通过 2-拒绝
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-16 10:38:36
     * Update: 2018-01-16 10:38:36
     * Version: 1.00
     */
    public function changeThrough($id = 0, $storeId = 0, $groupId = 0, $through = 1)
    {
        if (empty($id) || empty($storeId) || !in_array((int)$through, [1, 2])) {
            return getReturn(-1, L('INVALID_PARAM'));
        }
        if ($through == 1) {
            return D('StoreMember')->setAgentGroup($storeId, 0, $groupId, $id);
        } else {
            return D('StoreMember')->setAgentGroup($storeId, 0, 0, $id);
        }
    }

    /**
     * 获取申请记录数据
     * @param int $recordId
     * @return array
     * User: hjun
     * Date: 2018-11-29 19:30:07
     * Update: 2018-11-29 19:30:07
     * Version: 1.00
     */
    public function getRecord($recordId = 0)
    {
        $where = [];
        $where['id'] = $recordId;
        $options = [];
        $options['where'] = $where;
        return $this->selectRow($options);
    }

    /**
     * 获取一条有效的记录
     * 待审核或者已经通过审核的记录
     * @param int $storeId
     * @param int $memberId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-28 14:11:16
     * Update: 2018-01-28 14:11:16
     * Version: 1.00
     */
    public function getActiveMemberGroupRecord($storeId = 0, $memberId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['member_id'] = $memberId;
        $where['through'] = ['in', [0, 1]];
        $options = [];
        $options['where'] = $where;
        $options['order'] = 'create_time DESC,id DESC';
        $info = $this->selectRow($options);
        return $info;
    }

    /**
     * 重置为普通会员 则申请记录表设置为拒绝
     * @param int $storeId
     * @param int $memberId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-31 16:46:06
     * Update: 2018-01-31 16:46:06
     * Version: 1.00
     */
    public function rollbackMemberGroup($storeId = 0, $memberId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['member_id'] = $memberId;
        $where['through'] = ['in', [0, 1]];
        $options = [];
        $options['where'] = $where;
        $data['through'] = 2;
        $data['update_time'] = NOW_TIME;
        return $this->saveData($options, $data);
    }

    /**
     * 处理数据
     * @param array $info
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-11 11:01:04
     * Update: 2018-01-11 11:01:04
     * Version: 1.00
     */
    public function transformInfo($info = [], $condition = [])
    {
        $info = parent::transformInfo($info, $condition);
        return $info;
    }
}