<?php

namespace Common\Model;
class OtherActivityModel extends BaseModel
{
    protected $tableName = 'mb_others_activity_list';

    /**
     * 修改活动是否推荐到首页
     * @param int $storeId 店铺ID
     * @param int $actId 活动ID
     * @param int $isIndex 是否推荐到首页 1-是 -1-不是
     * @return array
     */
    public function changeIsIndex($storeId = 0, $actId = 0, $isIndex = -1)
    {
        // 只能修改自己店铺的活动 店铺ID和活动ID 要对应
        $where = [];
        $where['store_id'] = $storeId;
        $where['id'] = $actId;
        $actInfo = $this->where($where)->find();
        if (empty($actInfo)) return $this->getReturn(-1, '活动不存在');

        // 如果是推荐到首页 则判断是否已经有推荐到首页的活动
        if ($isIndex == 1){
            $where = [];
            $where['store_id'] = $storeId;
            $where['id'] = ['neq', $actId];
            $where['is_index'] = 1;
            $info = $this->where($where)->find();
            if (!empty($info))
                return $this->getReturn(-1, "您已经设置了活动 {$info['activity_name']} 到首页,不能再设置了");
        }

        // 修改
        $where = [];
        $where['store_id'] = $storeId;
        $where['id'] = $actId;
        $data = [];
        $data['is_index'] = $isIndex;
        $result = $this->where($where)->save($data);
        if (false === $result) return $this->getReturn(-1, $this->getDbError());
        return $this->getReturn(200, '设置成功', $isIndex);
    }

}