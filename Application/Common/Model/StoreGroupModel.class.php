<?php

namespace Common\Model;
class StoreGroupModel extends PartnerModel
{

    protected $_validate = [
        ['group_name', 'require', '请填写分组名称', 0, 'regex', 3],
        ['discount_type', '0,1,2,3', '请设置代理折扣', 0, 'in', 3],
    ];

    /**
     * 获取商家的代理分组
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-11 16:17:22
     * Update: 2018-01-11 16:17:22
     * Version: 1.00
     */
    public function getStoreGroupList($storeId = 0, $page = 1, $limit = 0, $condition = [])
    {
        if ($storeId <= 0) return getReturn(-1, L('INVALID_PARAM'));
        $where = [];
        $where['a.store_id'] = $storeId;
        $where = array_merge($where, $condition);
        $field = [
            'a.group_id,a.store_id,a.group_name,a.discount,a.discount_type',
            'a.store_group_price_id,a.create_time', 'a.recommend_group_id',
            'a.weight',
            'b.name group_price_name'
        ];
        $join = [
            'LEFT JOIN __MB_STORE_GROUP_PRICE__ b ON a.store_group_price_id = b.id'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = 'a.group_id DESC';
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $condition = [];
        $condition['empty_field'] = [
            'group_price_name' => ''
        ];
        $condition['time_field'] = [
            'create_time' => []
        ];
        // 0-按销售价打折，1-按利润打折, 2-自定义价格表 3-无折扣
        $condition['map_field'] = [
            'discount_type' => ['总价折扣', '利润折扣', '自定义价格', '无折扣']
        ];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value, $condition);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 保存代理分组信息
     * @param int $groupId
     * @param int $storeId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-18 15:16:51
     * Update: 2018-01-18 15:16:51
     * Version: 1.00
     */
    public function saveGroupInfo($groupId = 0, $storeId = 0, $data = [])
    {
        $field = [
            'store_id', 'group_name', 'discount', 'discount_type',
            'store_group_price_id', 'create_time', 'is_delete',
            'recommend_group_id', 'weight',
        ];
        $data['store_id'] = $storeId;
        $options = [];
        $options['field'] = $field;
        if ($data['discount_type'] == 2) {
            $priceId = (int)$data['store_group_price_id'];
            if ($priceId <= 0) return getReturn(-1, '请选择价格表');
            $where = [];
            $where['id'] = $priceId;
            $where['is_delete'] = 0;
            $price = M('mb_store_group_price')->where($where)->find();
            if (empty($price)) return getReturn(-1, '价格表已过期');
        } elseif ($data['discount_type'] != 3) {
            $data['discount'] = round($data['discount'], 1);
            if ($data['discount'] > 10 || $data['discount'] <= 0) {
                return getReturn(-1, '折扣填写0.1 - 10内的数字');
            }
        } else {
            $data['discount'] = 0;
        }
        if (empty($groupId)) {
            $data['create_time'] = NOW_TIME;
            $data = $this->getAndValidateDataFromRequest([], $data)['data'];
            return $this->addData($options, $data);
        } else {
            $where = [];
            $where['store_id'] = $storeId;
            $where['group_id'] = $groupId;
            $options['where'] = $where;
            $data = $this->getAndValidateDataFromRequest([], $data)['data'];
            return $this->saveData($options, $data);
        }
    }

    /**
     * 删除代理分组
     * @param int $groupId
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-18 15:42:07
     * Update: 2018-01-18 15:42:07
     * Version: 1.00
     */
    public function delGroup($groupId = 0, $storeId = 0)
    {
        // 判断是否为不可删除分组
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        $cantDelIds = explode(',', $storeInfo['cant_del_group_ids']);
        if (in_array($groupId, $cantDelIds)) {
            return getReturn(CODE_ERROR, '当前分组为系统分组,不可被删除');
        }
        $this->startTrans();
        $where = [];
        $where['group_id'] = $groupId;
        $where['store_id'] = $storeId;
        $info = $this->where($where)->find();
        if (empty($info)) return getReturn(-1, '记录不存在');
        $options = [];
        $options['where'] = $where;
        $result = $this->delData($options);
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }
        // 清空该组的会员为0
        $where = [];
        $where['group_id'] = $groupId;
        $list = M('mb_storemember')->field('store_id,member_id,group_id')->where($where)->select();
        if (false === $list) {
            $this->rollback();
            return getReturn(-1);
        }
        // 更新该组会员为普通会员 版本号递增
        $maxVersion = M('mb_storemember')->max('version');
        foreach ($list as $key => $value) {
            $where = [];
            $where['store_id'] = $value['store_id'];
            $where['member_id'] = $value['member_id'];
            $data = [];
            $data['group_id'] = 0;
            $data['version'] = ++$maxVersion;
            $res = M('mb_storemember')->where($where)->save($data);
            if (false === $res) {
                $this->rollback();
                return getReturn(-1);
            }
        }

        // 删除申请记录
        /*$where = [];
        $where['group_id'] = $groupId;
        $where['store_id'] = $storeId;
        $res = M('mb_membergroup')->where($where)->delete();
        if (false === $res) {
            $this->rollback();
            return getReturn(-1);
        }*/
        $this->commit();
        return $result;
    }

    public function scanStoreDiscountType()
    {
        $list = $this->select();
        $newList = [];
        $storeId = [];
        foreach ($list as $key => $value) {
            $newList[$value['store_id']][] = $value;
            $storeId[$value['store_id']] = $value['store_id'];
        }
        $where = [];
        $where['store_id'] = ['in', implode(',', $storeId)];
        $discountType = M('store')->where($where)->getField('store_id,discount_type', true);
        foreach ($discountType as $key => $value) {
            $where = [];
            $where['store_id'] = $key;
            $result = M('mb_storegroup')->where($where)->setField('discount_type', $value);
            dump($result);
        }
    }

    /**
     * 处理信息
     * @param array $info
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-16 11:12:02
     * Update: 2018-01-16 11:12:02
     * Version: 1.00
     */
    public function transformInfo($info = array(), $condition = array())
    {
        $info = parent::transformInfo($info, $condition);

        if (isset($info['discount_type']) && isset($info['discount'])) {
            switch ((int)$info['discount_type']) {
                case 0:
                    $info['discount_desc'] = "本组购物享商品总额" . $info['discount'] . "折";
                    break;
                case 1:
                    $info['discount_desc'] = "本组购物享商品利润" . $info['discount'] . "折";
                    break;
                case 2:
                    $info['discount_desc'] = "本组购物按代理价格表 {$info['group_price_name']}";
                    break;
                case 3:
                default:
                    $info['discount_desc'] = '本组购物无折扣';
                    break;
            }
        }

        return $info;
    }

    /**
     * 获取选择框的列表数据
     * @return array
     * User: hjun
     * Date: 2018-12-04 11:10:44
     * Update: 2018-12-04 11:10:44
     * Version: 1.00
     */
    public function getSelectOptions()
    {
        $field = [
            'group_id', 'group_name'
        ];
        $where = [];
        $where['store_id'] = $this->getStoreId();
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        return $this->selectList($options);
    }

    /**
     * 获取某个代理分组信息
     * @param int $groupId
     * @return array
     * User: hjun
     * Date: 2018-12-04 11:40:02
     * Update: 2018-12-04 11:40:02
     * Version: 1.00
     */
    public function getGroup($groupId = 0)
    {
        $where = [];
        $where['group_id'] = $groupId;
        $where['store_id'] = $this->getStoreId();
        $options = [];
        $options['where'] = $where;
        return $this->selectRow($options);
    }

    /**
     * 修改分组的权重
     * @param int $groupId
     * @param int $weight
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-04 19:48:56
     * Update: 2018-12-04 19:48:56
     * Version: 1.00
     */
    public function updateGroupWeight($groupId = 0, $weight = 0)
    {
        $where = [];
        $where['group_id'] = $groupId;
        $where['store_id'] = $this->getStoreId();
        $data = [];
        $data['weight'] = $weight;
        $data = $this->getAndValidateDataFromRequest([], $data)['data'];
        $result = $this->where($where)->save($data);
        if (false === $result) {
            return getReturn(CODE_ERROR);
        }
        return getReturn(CODE_SUCCESS, L('UPD_SUCCESS'));
    }
}