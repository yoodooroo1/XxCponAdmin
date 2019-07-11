<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2017/4/26
 * Time: 15:38
 */

namespace Common\Model;
class NoticeModel extends BaseModel
{

    protected $tableName = 'mb_notice';

    public function getIndexNoticeList($storeId = 0, $page = 1, $limit = 3)
    {
        $where = [];
        $where['storeid'] = $storeId;
        $where['isdelete'] = 0;
        $options = [];
        $options['field'] = 'notice_id,title,notice_type,notice_web_url';
        $options['where'] = $where;
        $options['order'] = 'notice_id DESC';
        $options['page'] = $page;
        $options['limit'] = $limit;
        return $this->selectList($options);
    }
}