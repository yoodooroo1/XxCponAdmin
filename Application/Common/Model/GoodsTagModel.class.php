<?php

namespace Common\Model;

class GoodsTagModel extends BaseModel
{
    protected $tableName = 'goods_tag';

    /**
     * @param int $tagId
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-20 10:12:55
     * Desc: 修改标签信息
     * Update: 2017-10-20 10:12:56
     * Version: 1.0
     */
    public function updateTag($tagId = 0, $data = [])
    {
        $tagId = (int)$tagId;
        if ($tagId <= 0) return getReturn(-1, '参数无效');
        if (empty($data)) return getReturn(-1, '参数无效');
        $where = [];
        $where['tag_id'] = $tagId;
        $where['isdelete'] = 0;
        $info = $this->field('tag_id,tag_status,tag_desc')->where($where)->find();
        if (!empty($data['tag_status'])) {
            $msg = $data['tag_status'] == 1 ? "该标签已经是显示状态" : "该标签已经是隐藏状态";
            if ($data['tag_status'] == $info['tag_status']) {
                return getReturn(-1, $msg);
            }
            if ($data['tag_status'] == 1 && empty($info['tag_desc'])) {
                return getReturn(-1, '显示前请先保证标签描述不为空');
            }
        }
        if (empty($info)) return getReturn(-1, '该标签不存在或者已被删除');
        $result = $this->where($where)->save($data);
        if ($result === false) {
            logWrite("修改标签{$tagId}出错:" . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '修改成功', $data);
    }

    /**
     * @param int $tagId
     * @param int $status
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-20 10:14:06
     * Desc: 修改标签的显示状态
     * Update: 2017-10-20 10:14:07
     * Version: 1.0
     */
    public function changeTagStatus($tagId = 0, $status = 1)
    {
        $data['tag_status'] = $status;
        return $this->updateTag($tagId, $data);
    }

    /**
     * @param int $storeId
     * @param int $channelId
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-24 10:16:44
     * Desc: 获取商品标签列表 子店要查出主店的标签
     * Update: 2017-10-24 10:16:45
     * Version: 1.0
     */
    public function getGoodsTag($storeId = 0, $channelId = 0, $condition = [])
    {
        $storeId = (int)$storeId;
        $channelId = (int)$channelId;
        if ($storeId <= 0 && $channelId <= 0) return getReturn(-1, '参数错误');
        $where = [];
        if ($storeId > 0) $where['store_id'] = $storeId;
        $where['isdelete'] = 0;
        $where['tag_status'] = 1;
        if ($channelId > 0) {
            // 是子店的话还要查出主店的标签
            $map = [];
            $map['channel_id'] = $channelId;
            $map['main_store'] = 1;
            $mainStoreId = M('store')->where($map)->getField('store_id');
            $storeId = implode(',', [$storeId, $mainStoreId]);
            $where['store_id'] = ['in', $storeId];
        }

        $where = array_merge($where, $condition);
        $list = $this
            ->field('tag_id,tag_name')
            ->where($where)
            ->order('tag_id DESC')
            ->select();
        if (false === $list) return getReturn();
        return getReturn(200, '', $list);
    }
}