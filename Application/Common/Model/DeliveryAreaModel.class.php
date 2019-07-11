<?php

namespace Common\Model;

use Common\Interfaces\M\Action;

/**
 * 配送范围模型
 * Class DeliveryAreaModel
 * @package Common\Model
 * User: hjun
 * Date: 2018-03-14 10:22:27
 * Update: 2018-03-14 10:22:27
 * Version: 1.00
 */
class DeliveryAreaModel extends BaseModel implements Action
{
    protected $tableName = 'mb_delivery_area';

    /**
     * 检查设置的范围地址
     * @param array $list
     * @return boolean
     * User: hjun
     * Date: 2019-06-04 15:21:10
     * Update: 2019-06-04 15:21:10
     * Version: 1.00
     */
    public function checkAreaList($list = [])
    {
        $ids = [
            'country_id' => ['name_field' => 'country', 'error' => '请选择国家'],
            'province_id' => ['name_field' => 'province', 'error' => '请选择省份'],
            'city_id' => ['name_field' => 'city', 'error' => '请选择城市'],
            'area_id' => ['name_field' => 'area', 'error' => '请选择地区'],
        ];
        foreach ($list as $index => $value) {
            // 删除的跳过
            if ($value['is_delete'] == 1) {
                continue;
            }
            foreach ($ids as $id => $info) {
                $name = $value[$info['name_field']];
                if (!isset($value[$id]) || empty($name)) {
                    $this->setValidateError($info['error']);
                    return false;
                }
            }
            if (empty($value['address'])) {
                $this->setValidateError('请输入自定义地址');
                return false;
            }
        }
        return true;
    }

    /**
     * 自动生成区域列表
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2019-06-04 17:58:59
     * Update: 2019-06-04 17:58:59
     * Version: 1.00
     */
    public function autoAreaList($request = [])
    {
        if (empty($request['list'])) {
            return [];
        }
        $storeInfo = D('Store')->getStoreInfo($request['store_id'])['data'];
        $list = [];
        $item = [];
        $item['store_id'] = $storeInfo['store_id'];
        $item['channel_id'] = $storeInfo['channel_id'];
        $ids = [
            'country_id' => ['name_field' => 'country'],
            'province_id' => ['name_field' => 'province'],
            'city_id' => ['name_field' => 'city'],
            'area_id' => ['name_field' => 'area'],
        ];
        foreach ($request['list'] as $value) {
            foreach ($ids as $id => $info) {
                $item[$id] = $value[$id];
                $item[$info['name_field']] = $value[$info['name_field']];
            }
            $item['id'] = $value['id'] > 0 ? $value['id'] : 0;
            $item['address'] = $value['address'];
            $list[] = $item;
        }
        return $list;
    }

    /**
     * 获取所有配送范围列表
     * @param int $storeId
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2019-06-04 12:22:48
     * Update: 2019-06-04 12:22:48
     * Version: 1.00
     */
    public function queryAreaList($storeId = 0, $request = [])
    {
        $field = [];
        if (isset($request['field'])) {
            $field = $request['field'];
        }
        $where = [];
        $where['a.store_id'] = $storeId;
        $where['a.is_delete'] = NOT_DELETE;
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['where'] = $where;
        $list = $this->selectList($options);
        return $list;
    }

    /**
     * 查询配送范围ID数组
     * @param int $storeId
     * @return array
     * User: hjun
     * Date: 2019-06-05 17:58:18
     * Update: 2019-06-05 17:58:18
     * Version: 1.00
     */
    public function queryActiveAreaIds($storeId = 0)
    {
        $request['field'] = ['id'];
        $list = $this->queryAreaList($storeId, $request);
        $ids = [];
        foreach ($list as $value) {
            $ids[] = $value['id'];
        }
        return $ids;
    }

    /**
     * 获取操作字段
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2019-01-13 13:33:25
     * Update: 2019-01-13 13:33:25
     * Version: 1.00
     */
    public function getFieldsByAction($action = 0, $request = [])
    {
        $result = [];
        switch ($action) {
            case DELIVERY_AREA_ACTION_SAVE_DATA:
                $result = [
                    'store_id', 'channel_id', 'country', 'country_id', 'province', 'province_id',
                    'city', 'city_id', 'area', 'area_id', 'address', 'is_delete',
                ];
                break;
            default:
                break;
        }
        return $result;
    }

    /**
     * 根据操作获取验证规则
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2019-01-13 14:55:28
     * Update: 2019-01-13 14:55:28
     * Version: 1.00
     */
    public function getValidateByAction($action = 1, $request = [])
    {
        $result = [];
        switch ($action) {
            case DELIVERY_AREA_ACTION_SAVE_DATA:
                $result[] = ['list', 'checkAreaList', '设置的范围有误', self::MUST_VALIDATE, 'callback', self::MODEL_BOTH];
                break;
            default:
                break;
        }
        return $result;
    }

    /**
     * 根据操作获取完成规则
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2019-01-13 15:06:04
     * Update: 2019-01-13 15:06:04
     * Version: 1.00
     */
    public function getAutoByAction($action = 2, $request = [])
    {
        $result = [];
        switch ($action) {
            case DELIVERY_AREA_ACTION_SAVE_DATA:
                break;
            default:
                break;
        }
        return $result;
    }

    /**
     * 根据操作获取 数据库操作类型
     * @param int $action
     * @param array $request
     * @return int
     * User: hjun
     * Date: 2019-01-13 15:06:26
     * Update: 2019-01-13 15:06:26
     * Version: 1.00
     */
    public function getTypeByAction($action = 3, $request = [])
    {
        $result = self::MODEL_INSERT;
        switch ($action) {
            case DELIVERY_AREA_ACTION_SAVE_DATA:
                $result = self::MODEL_BOTH;
                break;
            default:
                break;
        }
        return $result;
    }

    /**
     * 根据操作获取描述
     * @param int $action
     * @param array $request
     * @return string
     * User: hjun
     * Date: 2019-01-13 18:26:59
     * Update: 2019-01-13 18:26:59
     * Version: 1.00
     */
    public function getDescByAction($action = 4, $request = [])
    {
        switch ($action) {
            case DELIVERY_AREA_ACTION_SAVE_DATA:
                break;
            default:
                break;
        }
    }

    /**
     * 操作
     * @param int $action
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2019-01-13 14:44:47
     * Update: 2019-01-13 14:44:47
     * Version: 1.00
     */
    public function action($action = 0, $request = [])
    {
        $fields = $this->getFieldsByAction($action, $request);
        $validate = $this->getValidateByAction($action, $request);
        $auto = $this->getAutoByAction($action, $request);
        $type = $this->getTypeByAction($action, $request);
        $result = $this->getAndValidateDataFromRequest($fields, $request, $validate, $auto, $type);
        if (!isSuccess($result)) {
            return $result;
        }

        $this->startTrans();
        try {
            $results = [];
            $data = $result['data'];
            switch ($action) {
                case DELIVERY_AREA_ACTION_SAVE_DATA:
                    goto save_store_data;
                    break;
                default:
                    goto def;
                    break;
            }
            // region 默认逻辑
            def:
            {
                if ($type == self::MODEL_INSERT) {
                    $oldData = [];
                    $id = $this->add($data);
                    $results[] = $id;
                } else {
                    $oldData = [];
                    $results[] = $this->save($data);
                }
            };
            // endregion

            // region 保存商家配置
            save_store_data:
            {
                $model = D('Store');
                $storeInfo = D('Store')->getStoreInfo($request['store_id'])['data'];
                // 生成配送范围列表
                $list = $this->autoAreaList($request);
                $addList = [];
                $updateList = [];
                $ids = []; // 记录所有id
                foreach ($list as $value) {
                    if (empty($value['id'])) {
                        $addList[] = $value;
                    } else {
                        $updateList[] = $value;
                        $ids[] = $value['id'];
                    }
                }
                // 删除原来的所有
                $results[] = $this->where(['store_id' => $storeInfo['store_id']])->delete();
                // 新增
                if (!empty($updateList)) {
                    $results[] = $this->addAll($updateList);
                }
                if (!empty($addList)) {
                    $firstId = $this->addAll($addList);
                    $results[] = $firstId;
                    foreach ($addList as $value) {
                        $ids[] = $firstId++;
                    }
                }

                // 修改商家设置 存储id数据
                $data = [];
                $data['store_id'] = $storeInfo['store_id'];
                $data['delivery_area_ids'] = implode(',', $ids);
                $data['delivery_area_ctrl'] = $request['delivery_area_ctrl'] == 1 ? 1 : 0;
                $results[] = $model->save($data);
            };
            // endregion

            if (in_array(false, $results, true)) {
                $this->rollback();
                return getReturn(CODE_ERROR);
            }
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            return getReturn(CODE_ERROR, $e->getMessage());
        }
        return getReturn(CODE_SUCCESS, '', $data);
    }

    /**
     * 保存商家的配送范围设置
     * @param int $storeId
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2019-06-04 13:10:12
     * Update: 2019-06-04 13:10:12
     * Version: 1.00
     */
    public function saveStoreData($storeId = 0, $request = [])
    {
        $request['store_id'] = $storeId;
        $result = $this->action(DELIVERY_AREA_ACTION_SAVE_DATA, $request);
        if (isSuccess($result)) {
            $result['msg'] = L('SAVE_SUCCESSFUL');
        }
        return $result;
    }
}