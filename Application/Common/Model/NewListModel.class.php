<?php

namespace Common\Model;


class NewListModel extends BaseModel
{
    protected $tableName = "newslist";

    public function getNewsList($storeId = 0, $page = 1, $limit = 20, $condition = [], $otherOptions = [])
    {
        $where = [];
        $where['is_show'] = 1;
        $where['is_delete'] = -1;
        if ($storeId > 0) $where['storeid'] = $storeId;
        $where = array_merge($where, $condition);

        $options = [];
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options = array_merge($options, $otherOptions);
        $result = $this->queryList($options);
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 获取单个显示的资讯数据
     * @param int $newsId
     * @return array
     * User: hjun
     * Date: 2018-12-21 13:31:51
     * Update: 2018-12-21 13:31:51
     * Version: 1.00
     */
    public function getShowNews($newsId = 0)
    {
        $where = [];
        $where['newsid'] = $newsId;
        $where['storeid'] = $this->getStoreId();
        $where['is_show'] = 1;
        $where['is_delete'] = -1;
        $options = [];
        $options['where'] = $where;
        return $this->selectRow($options);
    }

    /**
     * 修改资讯的收藏数量
     * @param int $newsId
     * @param int $changeNum
     * @return mixed
     * User: hjun
     * Date: 2018-12-21 14:27:57
     * Update: 2018-12-21 14:27:57
     * Version: 1.00
     */
    public function changeCollectNum($newsId = 0, $changeNum = 0)
    {
        if (empty($newsId) || empty($changeNum)) {
            return true;
        }
        $where = [];
        $where['newsid'] = $newsId;
        $result = $this->where($where)->setInc('collect_num', $changeNum);
        return $result;
    }
}