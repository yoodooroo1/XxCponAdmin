<?php

namespace Common\Model;

use Think\Log;

class AuthRoleModel extends BaseModel
{
    protected $tableName = "mb_admin_auth_role";

    /**
     * 根据 storeId 获取商家的总后台分配的权限
     * 为 权限资源ID字符串 '1,2,3,4,5'
     * 返回结果
     * data => [
     *  'act_list' => '1,2,3',
     *  'right' => [
     *          'Controller @ act',
     *          'Controller @ act',
     *      ]
     * ]
     * @param int $storeId
     * @return array
     * User: hj
     * Date: 2017-09-10 20:33:54
     */
    public function getStoreRole($storeId = 0)
    {
        $info = S("{$storeId}_roleInfo");
        if (empty($info)) {
            // 获取商家信息
            $model = D('Store');
            $result = $model->getStoreInfo($storeId);
            if ($result['code'] !== 200) return $result;
            $store = $result['data'];

            // 没有单独权限 则获取 通用权限 和 特殊权限
            $result1 = $this->getStoreTypeRole($store['store_type']);
            if ($result1['code'] !== 200) return $result1;
            $actList1 = $result1['data'];

            // 特殊权限分为 单独权限和套餐权限  如果有单独权限就是用单独权限，没有就是用套餐权限
            $actList2 = $this->getStoreIdRole($storeId)['data']['act_list'];
            if (empty($actList2)) {
                $result2 = $this->getStoreGradeRole($store['channel_id'], $store['store_grade']);
                if ($result2['code'] !== 200) return $result2;
                $actList2 = $result2['data'];
            }
            if (empty($actList1) && empty($actList2)) return getReturn(-1, "暂未获得权限,请联系商城管理员");
            // 合并权限
            if (empty($actList1) && !empty($actList2)) {
                $actList = $actList1 . $actList2;
            } elseif (!empty($actList1) && empty($actList2)) {
                $actList = $actList1 . $actList2;
            } elseif (!empty($actList1) && !empty($actList2)) {
                $actList = $actList1 . ',' . $actList2;
            } else {
                $actList = '';
            }
            // 根据权限资源查找 权限码
            $modelAR = D('AuthMenu');
            $result = $modelAR->getMenuRole($actList);
            if ($result['code'] !== 200) return $result;
            $right = $result['data'];
            $info['act_list'] = $actList;
            $info['right'] = $right;
            S("{$storeId}_roleInfo", $info);
        }
        return getReturn(200, '', $info);
    }

    /**
     * 根据storeId 获取 商家的 单独权限信息
     * 没有返回-1
     * 有的话直接返回数组 act_list=>'', right=>[]
     * @param int $storeId
     * @return array
     * User: hj
     * Date: 2017-09-10 21:45:41
     */
    protected function getStoreIdRole($storeId = 0)
    {
        $field = "role_id,act_list";
        $where = [];
        $where['is_del'] = 0;
        $where['role_id'] = ["neq", 1];
        $where['store_id'] = $storeId;
        $role = $this->field($field)->where($where)->find();
        if (false === $role) {
            logWrite("查询权限资源出错:" . $this->getDbError());
            return getReturn();
        }
        if (!empty($role['act_list'])) {
            if ($role['act_list'] == 'all') {
                $info['act_list'] = 'all';
                $info['right'] = 'all';
            } else {
                $model = D('AuthMenu');
                $result = $model->getMenuRole($role['act_list']);
                if ($result['code'] !== 200) return $result;
                $right = $result['data'];
                $info['act_list'] = $role['act_list'];
                $info['right'] = $right;
            }
            return getReturn(200, '', $info);
        } else {
            return getReturn();
        }
    }

    /**
     * 根据storeType 获取 通用权限
     * @param int $storeType
     * @return array
     * User: hj
     * Date: 2017-09-10 21:39:21
     */
    protected function getStoreTypeRole($storeType = 0)
    {
        $where = [];
        $where['store_type'] = $storeType;
        $where['is_del'] = 0;
        $where['role_id'] = ["neq", 1];
        $actList = $this->where($where)->getField('act_list');
        if (false === $actList) {
            logWrite("商家类型{$storeType}的权限查询出错:" . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '', $actList);
    }

    /**
     * 根据渠道号和等级 获取 商家的 特殊权限
     * @param int $channelId
     * @param int $storeGrade
     * @return array
     * User: hj
     * Date: 2017-09-10 21:44:12
     */
    protected function getStoreGradeRole($channelId = 0, $storeGrade = 0)
    {
        // 根据 channel_id 和 store_grade 获取特殊权限
        $field = 'role_id,act_list';
        $where = [];
        $where['is_del'] = 0;
        $where['role_id'] = ["neq", 1];
        $where['channel_id'] = $channelId;
        $where['store_grade'] = $storeGrade;
        $role = $this->field($field)->where($where)->find();
        if (false === $role) {
            logWrite("查询{$channelId}-{$storeGrade}(渠道,等级)权限资源出错:" . $this->getDbError());
            return getReturn();
        }
        if (empty($role)) {
            // 如果还没有找到 则查找 channel_id = 0 store_grade=store_grade的权限资源
            $where['channel_id'] = 0;
            $role = $this->field($field)->where($where)->find();
            if (false === $role) {
                logWrite("查询权限资源出错:" . $this->getDbError());
                return getReturn();
            }
        }
        return getReturn(200, '', $role['act_list']);
    }
}