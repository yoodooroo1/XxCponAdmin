<?php

namespace Common\Model;
class StoreGroupPriceModel extends BaseModel
{
    protected $tableName = 'mb_store_group_price';

    protected $_validate = [
        ['name', 'require', '请输入价格表名称', 0, 'regex', 3]
    ];

    protected $_auto = [
        ['create_time', 'time', 1, 'function'],
        ['update_time', 'time', 3, 'function'],
    ];


    /**
     * 获取自定义价格表列表
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-15 23:57:46
     * Update: 2018-01-15 23:57:46
     * Version: 1.00
     */
    public function getGroupPriceList($storeId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $field = true;
        $where = [];
        $where['store_id'] = $storeId;
        $where['is_delete'] = 0;
        $where = array_merge($where, $condition);
        $options = [];
        $options['where'] = $where;
        $options['field'] = $field;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = 'id DESC';
        $result = $this->queryList($options);
        $list = $result['data']['list'];
        $condition = [];
        $condition['time_field'] = [
            'create_time' => [],
            'update_time' => [],
        ];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value, $condition);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 新增价格表
     * @param int $storeId
     * @param int $channelId
     * @param array $data
     * @return array
     * User: hjun
     * Date: 2018-01-12 16:23:34
     * Update: 2018-01-12 16:23:34
     * Version: 1.00
     */
    public function addGroupPrice($storeId = 0, $channelId = 0, $data = [])
    {
        if ($storeId <= 0) return getReturn(-1, L('INVALID_PARAM'));
        $data['store_id'] = $storeId;
        $data['channel_id'] = $channelId;
        $maxVersion = $this->queryMax([], 'version')['data'];
        $data['version'] = ++$maxVersion;
        $options = [];
        $options['field'] = 'name,store_id,channel_id,version,create_time,update_time';
        return $this->addData($options, $data);
    }

    /**
     * 修改/删除价格表
     * @param int $id
     * @param int $storeId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-12 16:39:29
     * Update: 2018-01-12 16:39:29
     * Version: 1.00
     */
    public function saveInfo($id = 0, $storeId = 0, $data = [])
    {
        if ($id <= 0 || $storeId <= 0) {
            return getReturn(-1, L('INVALID_PARAM'));
        }
        $where = [];
        $where['id'] = $id;
        $where['store_id'] = $storeId;
        $maxVersion = $this->queryMax([], 'version')['data'];
        $data['version'] = ++$maxVersion;
        $options = [];
        $options['where'] = $where;
        $options['field'] = 'name,update_time,version';
        return $this->saveData($options, $data);
    }

    /**
     * 删除价格
     * @param int $id
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-18 16:46:13
     * Update: 2018-01-18 16:46:13
     * Version: 1.00
     */
    public function delPrice($id = 0, $storeId = 0)
    {
        $where = [];
        $where['store_group_price_id'] = $id;
        $where['discount_type'] = 2;
        $where['store_id'] = $storeId;
        $info = M('mb_storegroup')->where($where)->find();
        if (!empty($info)) {
            return getReturn(-2, "该价格表已关联到代理分组[{$info['group_name']}]，无法删除<br>先到代理分组中取消关联该价格表后再来删除");
        }
        $where = [];
        $where['id'] = $id;
        $where['store_id'] = $storeId;
        $maxVersion = $this->queryMax([], 'version')['data'];
        $data['is_delete'] = 1;
        $data['version'] = ++$maxVersion;
        $options = [];
        $options['where'] = $where;
        $options['field'] = 'version,is_delete';
        return $this->saveData($options, $data);
    }

    /**
     * 检查价格表数量
     * @param int $storeGrade
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-31 10:54:24
     * Update: 2018-01-31 10:54:24
     * Version: 1.00
     */
    public function checkPriceNum($storeGrade = 0, $storeId = 0)
    {
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        $storeGrade = D('Store')->getStoreGrantInfo($storeId)['data'];
        $where = [];
        $where['store_id'] = $storeId;
        $where['is_delete'] = 0;
        $num = $this->queryCount(['where' => $where]);
        $maxNum = $storeInfo['price_data_num'] + $storeGrade['price_data_num'];
        $data['nowNum'] = $num;
        $data['maxNum'] = $maxNum;
        if ($num >= $maxNum) {
            return getReturn(-1, "最多设置{$maxNum}个价格表", $data);
        }
        return getReturn(200, '', $data);
    }
}