<?php

namespace Common\Model;

/**
 * Class SearchHistoryModel
 * @package Common\Model
 * Author: hj
 * Desc: 搜索历史模型
 * Date: 2017-11-06 17:56:31
 * Update: 2017-11-06 17:56:33
 * Version: 1.0
 */
class SearchHistoryModel extends BaseModel
{
    protected $tableName = 'mb_search_history';

    protected $_validate = [
        ['keywords', 'require', '请输入关键词', 2, 'regex', 1]
    ];

    protected $_auto = [
        ['create_time', 'time', 1, 'function']
    ];

    /**
     * @param int $memberId
     * @param int $storeId
     * @param int $channelId
     * @param string $keyword
     * @return array
     * Author: hj
     * Desc: 添加搜索历史
     * Date: 2017-11-06 18:00:09
     * Update: 2017-11-06 18:00:12
     * Version: 1.0
     */
    public function addSearchHistory($memberId = 0, $storeId = 0, $channelId = 0, $keyword = '')
    {
        $where = [];
        $where['member_id'] = $memberId;
        $where['store_id'] = $storeId;
        $where['keywords'] = $keyword;
        $options = [];
        $options['where'] = $where;
        $count = $this->queryCount($options);
        if ($count > 0) return getReturn(200, '');
        $data = [];
        $data['member_id'] = $memberId;
        $data['store_id'] = $storeId;
        $data['channel_id'] = $channelId;
        $data['keywords'] = $keyword;
        $result = $this->addData([], $data);
        return $result;
    }

    /**
     * @param int $memberId 会员ID
     * @param array $condition 其他条件
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取会员的搜索历史
     * Date: 2017-11-06 18:03:56
     * Update: 2017-11-06 18:03:57
     * Version: 1.0
     */
    public function getMemberSearchHistory($memberId = 0, $condition = [])
    {
        $where = [];
        $where['member_id'] = $memberId;
        $where = array_merge($where, $condition);
        $options = [];
        $options['where'] = $where;
        $options['order'] = 'id DESC';
        return $this->queryList($options);
    }

    /**
     * @param int $memberId
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 删除搜索历史
     * Date: 2017-11-10 22:08:02
     * Update: 2017-11-10 22:08:03
     * Version: 1.0
     */
    public function delMemberSearchHistory($memberId = 0, $storeId = 0)
    {
        $where = [];
        $where['member_id'] = $memberId;
        $where['store_id'] = $storeId;
        $options = [];
        $options['where'] = $where;
        return $this->delData($options);
    }
}