<?php

namespace Common\Model;

class XunXinNumModel extends BaseModel
{
    protected $tableName = 'mb_xunxinnum';

    /**
     * @param int $channelId
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-27 02:12:16
     * Desc: 获取可选的迅信号
     * Update: 2017-10-27 02:12:17
     * Version: 1.0
     */
    public function getXXNum($channelId = 0, $condition = [])
    {
        $where = [];
        $where['channel_id'] = $channelId;
        $where['is_select'] = 0;
        $where = array_merge($where, $condition);
        $list = $this->field('xunxin_num_name')->where($where)->select();
        if (false === $list) return getReturn();
        return getReturn(200, '', $list);
    }
}