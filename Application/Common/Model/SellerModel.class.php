<?php

namespace Common\Model;

use Think\Cache\Driver\Redis;
use Think\Log;

class SellerModel extends BaseModel
{
    protected $tableName = "seller";

    /**
     * 根据员工ID 获取 员工的权限
     * @param int $sellerId
     * @return array
     * User: hj
     * Date: 2017-09-10 23:05:02
     */
    public function getSellerRole($sellerId = 0)
    {
        $data = S("{$sellerId}_sellerRoleInfo");
        if (empty($data)) {
            $where = [];
            $where['a.seller_id'] = $sellerId;
            $field = [
                "a.seller_id,a.role_id,a.store_id,a.is_admin",
                "b.act_list"
            ];
            $field = implode(',', $field);
            $info = $this
                ->field($field)
                ->alias('a')
                ->join('LEFT JOIN __SELLER_GROUP__ b ON b.group_id = a.role_id')
                ->where($where)
                ->find();
            if (false === $info) {
                logWrite("查询员工{$sellerId}权限出错:" . $this->getDbError());
                return getReturn();
            }
            if (empty($info)) return getReturn(-1, "员工不存在");
            // 如果是 店主 或者 all 或者 没有设置权限组 则权限就是当前店铺权限
            if (empty($info['act_list']) || $info['act_list'] == 'all' || $info['is_admin'] == 1) {
                $modelS = D('Store');
                $result = $modelS->getStoreRole($info['store_id']);
                if ($result['code'] !== 200) return $result;
                $data = $result['data'];
            } else {
                $model = D('AuthMenu');
                $result = $model->getMenuRole($info['act_list']);
                if ($result['code'] !== 200) return $result;
                $right = $result['data'];
                $data = [];
                $data['act_list'] = $info['act_list'];
                $data['right'] = $right;
            }
            S("{$sellerId}_sellerRoleInfo", $data);
        }
        return getReturn(200, '', $data);
    }

    /**
     * 获取员工的信息
     * @param int $sellerId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-26 11:21:45
     * Update: 2018-01-26 11:21:45
     * Version: 1.00
     */
    public function getSellerInfo($sellerId = 0)
    {
        $redis = Redis::getInstance();
        $info = $redis->hGetAll("seller:{$sellerId}");
        if (empty($info)) {
            $where = [];
            $where['seller_id'] = $sellerId;
            $options = [];
            $options['where'] = $where;
            $result = $this->queryRow($options);
            $info = $result['data'];
            $redis->hMset("seller:{$sellerId}", $info);
        }
        return empty($info) ? [] : $info;
    }

    /**
     * 改变员工的默认语言设置
     * @param int $sellerId
     * @param string $lang
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-26 12:43:24
     * Update: 2018-01-26 12:43:24
     * Version: 1.00
     */
    public function changeLang($sellerId = 0, $lang = '')
    {
        $where = [];
        $where['seller_id'] = $sellerId;
        $data = [];
        $data['default_lang'] = $lang;
        $options = [];
        $options['where'] = $where;
        $result = $this->saveData($options, $data);
        if ($result['code'] !== 200) return $result;
        $redis = Redis::getInstance();
        $redis->hSet("seller:{$sellerId}", 'default_lang', $lang);
        return $result;
    }
}