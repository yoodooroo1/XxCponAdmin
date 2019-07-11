<?php

namespace Common\Model;


class ChannelModel extends BaseModel
{
    protected $tableName = 'mb_channel';

    /**
     * 获取渠道的信息 暂时为了获取子店是否拥有支付配置的权限
     * @param int $channelId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-25 16:19:47
     * Version: 1.0
     */
    public function getChannelInfo($channelId = 0)
    {
        $info = S("{$channelId}_channelIfo");
        if (empty($info)) {
            $where = [];
            $where['channel_id'] = $channelId;
            $info = $this->where($where)->find();
            if (false === $info) {
                logWrite("查询渠道{$channelId}信息出错" . $this->getDbError());
                return getReturn();
            }
            if (empty($info)) return getReturn(-1, "渠道信息不存在");
            S("{$channelId}_channelInfo", $info);
        }
        return getReturn(200, '', $info);
    }

    /**
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取渠道列表
     * Date: 2017-10-31 22:59:19
     * Update: 2017-10-31 22:59:20
     * Version: 1.0
     */
    public function getChannelList($page = 1, $limit = -1, $condition = [])
    {
        $where = [];
        $where = array_merge($where, $condition);
        $options = [];
        $field = [
            'channel_id,channel_name'
        ];
        $field = implode(',', $field);
        $options['field'] = $field;
        $options['where'] = $where;
        $options['order'] = 'channel_id ASC';
        $options['page'] = $page;
        $options['limit'] = $limit;
        $result = $this->queryList($options);
        return $result;
    }
}