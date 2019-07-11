<?php

namespace Common\Logic;

/**
 * Class LoginLogic
 * 登录逻辑
 * @package Api\Model
 * User: hjun
 * Date: 2018-01-24 10:51:45
 */
class LoginLogic extends BaseLogic
{

    /**
     * 检查token
     * @param $token
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-24 10:55:58
     * Update: 2018-01-24 10:55:58
     * Version: 1.00
     */
    public function checkToken($token)
    {
        $model = D('MemberToken');
        $result = $model->getMemberInfoByToken($token);
        return $result;
    }
}