<?php

namespace Common\Model;

use Think\Page;

/**
 * Class StoreTypeModel
 * User: hj
 * Date:2017-10-27 00:12:24
 * Desc: 店铺分类模型
 * Update: 2017-10-27 00:12:26
 * Version: 1.0
 * @package Common\Model
 */
class StoreTypeModel extends BaseModel
{
    protected $tableName = 'mb_store_type';

    protected $_validate = [
        ['store_type_name', 'require', '请输入分类名称', 3, 'regex', 3],
        ['sort', 'require', '请输入分类排序', 3, 'regex', 3],
    ];

    /**
     * @param int $channelId
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-27 00:13:45
     * Desc: 获取渠道下的店铺分类
     * Update: 2017-10-27 00:13:48
     * Version: 1.0
     */
    public function getStoreTypeList($channelId = 0, $condition = [])
    {
        $where = [];
        $where['channel_id'] = $channelId;
        $where['isdelete'] = 0;
        $where = array_merge($where, $condition);
        $options = [];
        $options['where'] = $where;
        $options['field'] = 'store_type_id,store_type_name,channel_id,sort';
        $options['order'] = 'sort ASC,store_type_id ASC';
        return $this->queryList($options);
    }

    public function getStoreTypeInfo($id = 0, $option = [])
    {
        $where = [];
        $where['store_type_id'] = $id;
        $where['isdelete'] = 0;
        $options = [];
        $options['where'] = $where;
        $options = array_merge($options, $option);
        return $this->queryRow($options);
    }

    public function addStoreType($channelId = 0, $data = [])
    {
        $data['channel_id'] = $channelId;
        $data = $this->create($data, 1);
        if (false === $data) return getReturn(-1, $this->getError());
        $result = $this->add($data);
        if (false === $result) return $this->getFalseReturn();
        $data['store_type_id'] = $result;
        return getReturn(200, '新增成功', $data);
    }

    public function saveStoreType($typeId = 0, $data = [])
    {
        $where = [];
        $where['store_type_id'] = $typeId;
        $where['isdelete'] = 0;
        $options = [];
        $options['where'] = $where;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, '分类不存在');
        $data = $this->create($data, 2);
        if (false === $data) return getReturn(-1, $this->getError());
        $result = $this->where($where)->save($data);
        if (false === $result) return $this->getFalseReturn();
        foreach ($info as $key => $value) {
            $data[$key] = empty($data[$key]) ? $value : $data[$key];
        }
        return getReturn(200, '保存成功', $data);
    }

    public function delStoreType($typeId = 0)
    {
        $data = [];
        $data['isdelete'] = 1;
        return $this->saveStoreType($typeId, $data);
    }

    public function changeStoreTypeSort($typeId = 0, $sort = 0)
    {
        $data = [];
        $data['sort'] = $sort;
        return $this->saveStoreType($typeId, $data);
    }
}