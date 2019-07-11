<?php

namespace Common\Model;

/**
 * Class MemberModel
 * mb_user_token
 * @package Common\Model
 * User: hjun
 * Date: 2018-01-25 11:08:52
 */
class MemberTokenModel extends BaseModel
{
    protected $tableName = 'mb_user_token';

    /**
     * 根据token获取用户信息
     * @param string $token
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-25 11:19:57
     * Update: 2018-01-25 11:19:57
     * Version: 1.00
     */
    public function getMemberInfoByToken($token = '')
    {
        $field = [
            'a.member_id', 'b.member_name', 'b.member_truename',
            'b.member_nickname', 'b.openid', 'b.unionid'
        ];
        $field = implode(',', $field);
        $join = [
            '__MEMBER__ b ON a.member_id = b.member_id'
        ];
        $where = [];
        $where['a.token'] = $token;
        $where['b.isdelete'] = 0;
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $options['order'] = 'a.token_id DESC';
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-100, '会话已过期，请重新登录');
        return $result;
    }
}