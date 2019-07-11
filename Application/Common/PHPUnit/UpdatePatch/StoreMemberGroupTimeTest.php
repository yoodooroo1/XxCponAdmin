<?php

namespace Common\PHPUnit;

class StoreMemberGroupTimeTest extends BaseTest
{
    /**
     * 设置一次成为代理商的时间
     * User: hjun
     * Date: 2018-11-29 14:58:57
     * Update: 2018-11-29 14:58:57
     * Version: 1.00
     */
    public function testStoreMemberGroupTime()
    {
        $storeMember = D('StoreMember');
        $field = [
            'a.store_id', 'a.member_id', 'a.group_time',
            'a.register_date',
            'b.group_id',
            'c.id', 'c.create_time', 'c.update_time',
        ];
        $where = [];
        $where['a.group_id'] = ['gt', 0];
        $where['a.group_time'] = ['elt', 1];
        $where['a.store_id'] = ['gt', 0];
        $where['a.member_id'] = ['gt', 0];
        $join = [
            'LEFT JOIN __MB_STOREGROUP__ b ON a.group_id = b.group_id',
            'LEFT JOIN __MB_MEMBERGROUP__ c ON a.store_id = c.store_id AND a.member_id = c.member_id AND c.through = 1'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['where'] = $where;
        $options['join'] = $join;
        $list = $storeMember->selectList($options);
        foreach ($list as $key => $member) {
            $member['update_time_string'] = date('Y-m-d H:i:s', $member['update_time']);
            $list[$key] = $member;

            if (empty($member['group_time']) || $member['group_time'] == 1) {
                $where = [];
                $where['member_id'] = $member['member_id'];
                $where['store_id'] = $member['store_id'];
                $data = [];
                if (empty($member['id'])) {
                    // 没有申请记录 说明是用APP直接改的 或者旧数据  使用注册时间
                    $data['group_time'] = $member['register_date'];
                } elseif (!empty($member['update_time'])) {
                    // 有审核时间 使用审核时间
                    $data['group_time'] = $member['update_time'];
                } elseif (!empty($member['create_time'])) {
                    // 有申请时间 使用申请时间
                    $data['group_time'] = $member['create_time'];
                }
                $data['version'] = $storeMember->max('version') + 1;
                $storeMember->where($where)->save($data);
            }
        }
        $i = 1;
    }

    /**
     * 设置记录中的会员帐号
     * User: hjun
     * Date: 2018-11-29 16:51:35
     * Update: 2018-11-29 16:51:35
     * Version: 1.00
     */
    public function testMemberGroupName()
    {
        $model = D('MemberGroup');
        $field = ['id', 'member_id', 'member_name'];
        $where = [];
        $where['member_id'] = ['gt', 0];
        $where['store_id'] = ['gt', 0];
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        $list = $model->selectList($options);
        foreach ($list as $record) {
            if (empty($record['member_name'])) {
                $where = [];
                $where['id'] = $record['id'];
                $data = [];
                $data['member_name'] = D('Member')->where(['member_id' => $record['member_id']])->getField('member_name');
                $model->where($where)->save($data);
            }
        }
    }

    /**
     * 将失效的group_id的会员group_id设置为0
     * 对应commit_id 7584c31
     * User: hjun
     * Date: 2018-12-02 17:03:59
     * Update: 2018-12-02 17:03:59
     * Version: 1.00
     */
    public function testGroupId()
    {
        $model = D('StoreMember');
        $field = [
            'a.store_id', 'a.member_id', 'a.group_id',
            'b.group_id'
        ];
        $where = [];
        $where['a.group_id'] = ['gt', 0];
        $where['b.group_id'] = ['exp', 'IS NULL'];
        $join = [
            'LEFT JOIN __MB_STOREGROUP__ b ON a.group_id = b.group_id'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['where'] = $where;
        $options['join'] = $join;
        $list = $model->selectList($options);
        $version = $model->max('version');
        foreach ($list as $value) {
            $where = [];
            $where['store_id'] = $value['store_id'];
            $where['member_id'] = $value['member_id'];
            $data = [];
            $data['group_id'] = 0;
            $data['version'] = ++$version;
            $model->where($where)->save($data);
        }
    }
}