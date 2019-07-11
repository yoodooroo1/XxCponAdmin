<?php

namespace Common\Model;


class NewCollectModel extends BaseModel
{
    protected $tableName = 'mb_news_collect';

    public function getCollectRecordByNewsId($newsId = 0, $memberId = 0)
    {
        $where = [];
        $where['member_id'] = $memberId;
        $where['news_id'] = $newsId;
        $info = $this->where($where)->find();
        return $info;
    }

    /**
     * 收藏资讯
     * @param int $newsId
     * @param int $memberId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-21 14:28:50
     * Update: 2018-12-21 14:28:50
     * Version: 1.00
     */
    public function collectNews($newsId = 0, $memberId = 0)
    {
        if (empty($newsId) || empty($memberId)) {
            return getReturn(CODE_ERROR, '您尚未登录');
        }
        $member = D('Member')->getMemberInfo($memberId)['data'];
        if (empty($member)) {
            return getReturn(CODE_ERROR, '会员不存在');
        }
        $record = $this->getCollectRecordByNewsId($newsId, $memberId);
        if ($record['is_collect'] == 1) {
            return getReturn(CODE_ERROR, '您已经收藏了');
        }
        $this->startTrans();
        $data = [];
        $data['is_collect'] = 1;
        if (empty($record)) {
            $data['store_id'] = $this->getStoreId() > 0 ? $this->getStoreId() : 0;
            $data['news_id'] = $newsId;
            $data['member_id'] = $memberId;
            $data['create_time'] = NOW_TIME;
            $results[] = $this->add($data);
        } else {
            $where = [];
            $where['id'] = $record['id'];
            $data['update_time'] = NOW_TIME;
            $results[] = $this->where($where)->save($data);
        }
        // 资讯的收藏数量+1
        $results[] = D('NewList')->changeCollectNum($newsId, 1);

        if (isTransFail($results)) {
            $this->rollback();
            return getReturn(CODE_ERROR);
        }
        $this->commit();
        D('StoreDecoration')->clearTplCache($this->getStoreId());
        return getReturn(CODE_SUCCESS, '收藏成功');
    }

    /**
     * 取消收藏资讯
     * @param int $newsId
     * @param int $memberId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-21 14:28:55
     * Update: 2018-12-21 14:28:55
     * Version: 1.00
     */
    public function disCollectNews($newsId = 0, $memberId = 0)
    {
        if (empty($newsId) || empty($memberId)) {
            return getReturn(CODE_ERROR, '参数错误');
        }
        $member = D('Member')->getMemberInfo($memberId)['data'];
        if (empty($member)) {
            return getReturn(CODE_ERROR, '会员不存在');
        }
        $record = $this->getCollectRecordByNewsId($newsId, $memberId);
        if (empty($record)) {
            return getReturn(CODE_ERROR, '您尚未收藏该资讯');
        }
        $this->startTrans();
        $data = [];
        $data['is_collect'] = 0;
        $where = [];
        $where['id'] = $record['id'];
        $data['update_time'] = NOW_TIME;
        $results[] = $this->where($where)->save($data);
        // 资讯的收藏数量-1
        $results[] = D('NewList')->changeCollectNum($newsId, -1);

        if (isTransFail($results)) {
            $this->rollback();
            return getReturn(CODE_ERROR);
        }
        $this->commit();
        D('StoreDecoration')->clearTplCache($this->getStoreId());
        return getReturn(CODE_SUCCESS, '取消收藏成功');
    }
}