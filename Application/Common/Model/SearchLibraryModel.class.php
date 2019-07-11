<?php

namespace Common\Model;
/**
 * Class SearchLibraryModel
 * @package Common\Model
 * author: hj
 * desc: 商家搜索库
 * date: 2017-11-06 10:06:30
 * update: 2017-11-06 10:06:31
 * version: 1.0
 */
class SearchLibraryModel extends BaseModel
{
    protected $tableName = 'mb_search_library';

    // 自动验证
    protected $_validate = [
        ['keywords', 'require', '请输入搜索关键字', 2, 'regex', 1]
    ];

    // 自动完成
    protected $_auto = [
        ['create_time', 'time', 1, 'function']
    ];

    /**
     * @param int $storeId 商家ID
     * @param int $channelId 渠道ID
     * @param string $keyword 关键字
     * @return array
     * author: hj
     * desc: 新增搜索关键词
     * date: 2017-11-06 10:13:32
     * update: 2017-11-06 10:13:33
     * version: 1.0
     */
    public function addSearchToLibrary($storeId = 0, $channelId = 0, $keyword = '')
    {
        // 关键词唯一
        $where = [];
        $where['store_id'] = $storeId;
        $where['keywords'] = $keyword;
        $options = [];
        $options['where'] = $where;
        $count = $this->queryCount($options);
        if ($count <= 0) {
            $data = [];
            $data['store_id'] = $storeId;
            $data['keywords'] = $keyword;
            $data['channel_id'] = $channelId;
            $result = $this->addData([], $data);
            return $result;
        }
        return getReturn(200, '关键词已存在');
    }

    /**
     * @param int $storeId
     * @param int $channelId
     * @param string $keyword
     * @return array
     * Author: hj
     * Desc: 获取相似搜索关键词
     * Date: 2017-11-06 10:20:32
     * Update: 2017-11-06 10:20:33
     * Version: 1.0
     */
    public function getSearchLibrary($storeId = 0, $channelId = 0, $keyword = '')
    {
        $where = [];
        if ($storeId > 0) {
            $where['store_id'] = $storeId;
        } elseif ($channelId > 0) {
            $where['channel_id'] = $channelId;
        }
        $where['keywords'] = ['LIKE', "%{$keyword}%"];
        $options = [];
        $options['where'] = $where;
        $result = $this->queryField($options, 'keywords', true);
        return $result;
    }
}