<?php

namespace Common\Model;

use Think\Log;

/**
 * 会员标签
 * Class MemberTagModel
 * @package Common\Model
 */
class MemberTagModel extends BaseModel
{
    protected $tableName = 'mb_member_tag';

    public function getMemberTagList($storeId, $queryWhere = [])
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['is_delete'] = NOT_DELETE;
        $where = array_merge($where, $queryWhere);
        $memberTagData = $this
            ->where($where)
            ->select();
        foreach ($memberTagData as $key => $value) {
            $storeMemberData = M("mb_storemember_tag")->where(array('tid' => $value['id']))->select();
            $memberTagData[$key]['member_num'] = count($storeMemberData);
        }
        return getReturn(200, '成功', $memberTagData);
    }

    public function setMemberTagShow($id, $isShow)
    {
        $tag = $this->where(array('id' => $id))
            ->save(array('is_show' => $isShow));
        if ($tag === false) {
            return getReturn(-1, '更新失败');
        }
        return getReturn(200, '成功');
    }

    public function updateMemberTagInfo($req, $store_id)
    {
        if (empty($req['id'])) {
            $tag = $this->add(array('store_id' => $store_id,
                'name' => $req['name'],
                'is_show' => $req['is_show'],
                'mark' => $req['mark']));
            if ($tag === false) {
                return getReturn(-1, "添加失败");
            } else {
                return getReturn(200, '添加成功');
            }

        } else {
            $tag = $this->where(array('id' => $req['id']))
                ->update(array(
                    'name' => $req['name'],
                    'is_show' => $req['is_show'],
                    'mark' => $req['mark']));
            if ($tag === false) {
                return getReturn(-1, "更新失败");
            } else {
                return getReturn(200, '更新成功');
            }
        }

    }

    public function deleteMemberTag($id)
    {
        $tag = $this
            ->where(array('id' => $id))
            ->save(array('is_delete' => 1));
        if ($tag === false) {
            return getReturn(-1, '更新失败');
        }
        return getReturn(CODE_SUCCESS, '删除成功');
    }

    public function addStoreMemberTag($memberIds = '', $tagIds = '')
    {
        if (empty($tagIds)) {
            return getReturn(CODE_ERROR, '请选择标签');
        }
        if (empty($memberIds)) {
            return getReturn(CODE_ERROR, '请选择会员');
        }
        $tagIds = explode('@', $tagIds);
        $memberIds = explode('@', $memberIds);
        $allData = [];
        foreach ($memberIds as $memberId) {
            foreach ($tagIds as $tagId) {
                $data = [];
                $data['mid'] = $memberId;
                $data['tid'] = $tagId;
                $data['sid'] = $this->getStoreId();
                $allData[] = $data;
            }
        }
        $result = M('mb_storemember_tag')->addAll($allData);
        if (false === $result) {
            return getReturn(CODE_ERROR);
        }
        return getReturn(CODE_SUCCESS, '添加成功');
    }
}