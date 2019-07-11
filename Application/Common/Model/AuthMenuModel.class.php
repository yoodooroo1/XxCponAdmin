<?php

namespace Common\Model;

use Think\Log;

class AuthMenuModel extends BaseModel
{
    protected $tableName = "mb_admin_auth_menu";

    /**
     * 根据权限字符串 获取权限资源
     * actList = '1,2,3,4,5'
     * 返回一个权限码数组
     * @param string $actList
     * @return array
     * User: hj
     * Date: 2017-09-10 10:47:38
     */
    public function getMenuRole($actList = '')
    {
        logWrite("获取权限{$actList}的权限");
        $actList = $actList . '';
        $right = S("{$actList}_rightInfo");
        if (empty($right)) {
            $where = [];
            $where['is_del'] = 0;
            $where['id'] = ["in", $actList];
            $right = $this->where($where)->getField('right', true);
            if (false === $right) {
                logWrite("获取权限出错:" . $this->getDbError());
                return getReturn();
            }
            if (empty($right)) return getReturn(-1, "暂未获得权限,请联系商城管理员分配");
            // 将获得权限码数组，先拼接成字符串，再分割成单独的数组, 存入内存
            $right = implode(',', $right);
            $right = explode(',', $right);
            logWrite("{$actList}的权限资源存入内存:" . json_encode($right, JSON_UNESCAPED_UNICODE));
            S("{$actList}_rightInfo", $right);
        }
        logWrite("{$actList}的权限为:" . json_encode($right, JSON_UNESCAPED_UNICODE));
        return getReturn(200, '', $right);

    }
}