<?php

namespace Common\Model;
/**
 * Class IndustryModel
 * User: hj
 * Date: 2017-10-26 23:56:23
 * Desc: 运费模版模型类
 * Update: 2017-10-26 23:56:26
 * Version: 1.0
 * @package Common\Model
 */
class FreightTplModel extends BaseModel
{
    protected $tableName = 'mb_freight_tpl';

    protected $_validate = [
        ['tpl_name', 'require', '请输入模版名称', 0, 'regex', 3],
        ['tpl_type', '0', '请选择计费方式', 0, 'in', 3],
    ];

    /**
     * 新增运费模版
     * @param int $storeId
     * @param int $channelId
     * @param array $data
     * [
     *   'tpl_name' => '',
     *   'tpl_type' => 0,
     *   'tpl_mode' => [
     *
     *   ]
     * ]
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-16 09:45:24
     * Update: 2018-03-16 09:45:24
     * Version: 1.00
     */
    public function addTpl($storeId = 0, $channelId = 0, $data = [])
    {
        $this->startTrans();
        $tpl = [];
        $tpl['tpl_name'] = $data['tpl_name'];
        $tpl['tpl_type'] = $data['tpl_type'];
        $tpl['store_id'] = $storeId;
        $tpl['channel_id'] = $channelId;
        $tpl['create_time'] = NOW_TIME;
        $result = $this->addData([], $tpl);
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }
        $tplId = $result['data'];

        // 检查配送方式
        $tplMode = [];
        $fieldNameArr = [
            ['first_piece', '首件'],
            ['second_piece', '续件'],
//            ['first_weight','首重'],
//            ['second_weight','续重'],
//            ['first_volume','首体积'],
//            ['second_volume','续体积'],
            ['first_amount', '首费'],
            ['second_amount', '续费'],
        ];
        $item = [];
        $item['tpl_id'] = $tplId;
        $regionName = [];
        $region = [];
        foreach ($data['countryList'] as $key => $country) {
            $item['country_id'] = $country['country_id'];
            foreach ($country['tpl_mode'] as $k => $mode) {
                $index = $k + 1;
                $item['is_default'] = empty($mode['is_default']) ? 0 : 1;
                // 检查地区
                if (empty($mode['region']) && $mode['is_default'] != 1) {
                    return getReturn(-1, "请选择 {$country['country_name']}-第{$index}个 的配送区域");
                }
                // 地区反查
                foreach ($mode['region'] as $areaId => $value) {
                    $areaName = getAreaNameById($areaId);
                    if (!empty($areaName)) {
                        $region[] = $areaId;
                        $regionName[] = $areaName;
                    }
                }
                $item['region'] = $mode['is_default'] == 1 ? '' : implode('@', $region);
                $item['region_name'] = $mode['is_default'] == 1 ? '全国' : implode(';', $regionName);
                foreach ($fieldNameArr as $field) {
                    if ($mode[$field[0]] < 0) {
                        return getReturn(-1, "请输入 {$country['country_name']}-第{$index}个 的{$field[1]}");
                    } else {
                        $item[$field[0]] = round($mode[$field[0]], 2);
                    }
                }
                $tplMode[] = $item;
            }
        }
        $model = M('mb_freight_tpl_mode');
        $result = $model->addAll($tplMode);
        if (false === $result) {
            $this->rollback();
            return getReturn();
        }
        $this->commit();
        return getReturn(200);
    }

    /**
     * 修改配送方式
     * @param int $storeId
     * @param int $tplId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-18 00:43:06
     * Update: 2018-03-18 00:43:06
     * Version: 1.00
     */
    public function updateTpl($storeId = 0, $tplId = 0, $data = [])
    {
        $where = [];
        $where['tpl_id'] = $tplId;
        $where['store_id'] = $storeId;
        $where['is_delete'] = 0;
        $info = $this->field('tpl_id,tpl_name')->where($where)->find();
        if (empty($info)) {
            return getReturn(-1, '记录不存在');
        }

        // 检查配送方式
        $tplMode = [];
        $fieldNameArr = [
            ['first_piece', '首件'],
            ['second_piece', '续件'],
//            ['first_weight','首重'],
//            ['second_weight','续重'],
//            ['first_volume','首体积'],
//            ['second_volume','续体积'],
            ['first_amount', '首费'],
            ['second_amount', '续费'],
        ];
        $item = [];
        $item['tpl_id'] = $tplId;
        $regionName = [];
        $region = [];
        foreach ($data['countryList'] as $key => $country) {
            $item['country_id'] = $country['country_id'];
            foreach ($country['tpl_mode'] as $k => $mode) {
                $index = $k + 1;
                $item['is_default'] = empty($mode['is_default']) ? 0 : 1;
                // 检查地区
                if (empty($mode['region']) && $mode['is_default'] != 1) {
                    return getReturn(-1, "请选择 {$country['country_name']}-第{$index}个 的配送区域");
                }
                // 地区反查
                foreach ($mode['region'] as $areaId => $value) {
                    $areaName = getAreaNameById($areaId);
                    if (!empty($areaName)) {
                        $region[] = $areaId;
                        $regionName[] = $areaName;
                    }
                }
                $item['region'] = $mode['is_default'] == 1 ? '' : implode('@', $region);
                $item['region_name'] = $mode['is_default'] == 1 ? '全国' : implode(';', $regionName);
                foreach ($fieldNameArr as $field) {
                    if ($mode[$field[0]] < 0) {
                        return getReturn(-1, "请输入 {$country['country_name']}-第{$index}个 的{$field[1]}");
                    } else {
                        $item[$field[0]] = round($mode[$field[0]], 2);
                    }
                }
                $tplMode[] = $item;
            }
        }

        // 修改运费模版 如果修改了名称才去修改数据库 否则不需要
        $where = [];
        $where['tpl_id'] = $tplId;
        $this->startTrans();
        if ($info['tpl_name'] != $data['tpl_name']) {
            $options = [];
            $options['where'] = $where;
            $options['field'] = 'tpl_name';
            $result = $this->saveData($options, $data);
            if ($result['code'] !== 200) {
                $this->rollback();
                return $result;
            }
        }
        $model = M('mb_freight_tpl_mode');
        $result = $model->where($where)->delete();
        if (false === $result) {
            $this->rollback();
            return getReturn();
        }
        $result = $model->addAll($tplMode);
        if (false === $result) {
            $this->rollback();
            return getReturn();
        }
        $this->commit();
        return getReturn(200);
    }

    /**
     * 删除模版
     * @param int $storeId
     * @param int $tplId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-16 13:54:53
     * Update: 2018-03-16 13:54:53
     * Version: 1.00
     */
    public function delTpl($storeId = 0, $tplId = 0)
    {
        $where = [];
        $where['tpl_id'] = $tplId;
        $where['store_id'] = $storeId;
        $where['is_delete'] = 0;
        $info = $this->field('tpl_id')->where($where)->find();
        if (empty($info)) {
            return getReturn(-1, '记录不存在');
        }
        $this->startTrans();
        $data = [];
        $data['is_delete'] = 1;
        $where = [];
        $where['tpl_id'] = $tplId;
        $result = $this->saveData(['where' => $where], $data);
        if ($result['code'] !== 200) {
            $this->rollback();
            return $result;
        }
        $model = M('mb_freight_tpl_mode');
        $where = [];
        $where['tpl_id'] = $tplId;
        $result = $model->where($where)->delete();
        if (false === $result) {
            $this->rollback();
            return $this->getFalseReturn();
        }
        $this->commit();
        return getReturn(200, '');
    }

    /**
     * 获取运费模版列表
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-16 12:16:47
     * Update: 2018-03-16 12:16:47
     * Version: 1.00
     */
    public function getTplList($storeId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $field = [
            'a.tpl_id', 'a.tpl_name', 'a.tpl_type',
            'b.first_amount', 'b.second_amount'
        ];
        $field = implode(',', $field);
        $join = [
            '__MB_FREIGHT_TPL_MODE__ b ON a.tpl_id = b.tpl_id'
        ];
        $where = [];
        $where['a.store_id'] = $storeId;
        $where['a.is_delete'] = 0;
        $config = D('StoreConfig')->getStoreConf($storeId);
        $countryId = explode(',', $config['area_ids']);
        $countryId = empty($countryId[0]) ? 0 : $countryId[0];
        $where['b.country_id'] = $countryId;
        $where['b.is_default'] = 1;
        $where = array_merge($where, $condition);
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $condition = [];
        $condition['map_field'] = [
            'tpl_type' => [
                '0' => '按件计费'
            ]
        ];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value, $condition);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 获取模版信息
     * @param int $storeId
     * @param int $tplId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-16 16:22:11
     * Update: 2018-03-16 16:22:11
     * Version: 1.00
     */
    public function getTplInfo($storeId = 0, $tplId = 0)
    {
        $config = D('StoreConfig')->getStoreConf($storeId);
        $countryId = explode(',', $config['area_ids']);
        $countryId = empty($countryId) ? 0 : $countryId;
        $countryList = M('mb_areas')
            ->field('area_id country_id,area_name country_name')
            ->where(['area_id' => ['in', $countryId]])
            ->select();
        if ($countryList === false) return getReturn();
        if ($tplId == -1) {
            $item = [];
            $item['first_amount'] = 0;
            $item['second_amount'] = 0;
            $item['first_piece'] = 1;
            $item['second_piece'] = 1;
            $item['first_weight'] = 1;
            $item['second_weight'] = 1;
            $item['first_volume'] = 1;
            $item['second_volume'] = 1;
            $item['region_name'] = '全国';
            $item['region'] = [];
            $item['is_default'] = 1;
            foreach ($countryList as $key => $country) {
                $item['country_id'] = $country['country_id'];
                $countryList[$key]['tpl_mode'][] = $item;
            }
            $info = [];
            $info['tpl_id'] = 0;
            $info['tpl_name'] = '';
            $info['tpl_type'] = 0;
        } else {
            $field = [
                'a.tpl_id', 'a.tpl_name', 'a.tpl_type',
                'b.first_amount', 'b.second_amount',
                'b.first_piece', 'b.second_piece',
                'b.country_id', 'b.region', 'b.region_name',
                'b.is_default'

            ];
            $field = implode(',', $field);
            $where = [];
            $where['a.tpl_id'] = $tplId;
            $where['a.store_id'] = $storeId;
            $where['a.is_delete'] = 0;
            $join = [
                '__MB_FREIGHT_TPL_MODE__ b ON a.tpl_id = b.tpl_id'
            ];
            $options = [];
            $options['alias'] = 'a';
            $options['field'] = $field;
            $options['join'] = $join;
            $options['where'] = $where;
            $result = $this->queryList($options);
            if ($result['code'] !== 200) return $result;
            $list = $result['data']['list'];
            if ($result['data']['total'] == 0) return getReturn(-1, '模版不存在');
            $info = [];
            $info['tpl_id'] = $list[0]['tpl_id'];
            $info['tpl_name'] = $list[0]['tpl_name'];
            $info['tpl_type'] = $list[0]['tpl_type'];
            foreach ($countryList as $key => $country) {
                foreach ($list as $k => $mode) {
                    $mode['region'] = explode('@', $mode['region']);
                    $object = [];
                    $sProvince = [];
                    foreach ($mode['region'] as $areaId){
                        $object[$areaId] = 'checked';
                        $pid = getAreaProvincePid($areaId);
                        if (!empty($pid) && empty($sProvince[$pid])){
                            $sProvince[$pid] = 'checked';
                        }
                    }
                    $mode['region'] = $object;
                    $mode['s_province'] = $sProvince;
                    if ($mode['country_id'] == $country['country_id']) {
                        $countryList[$key]['tpl_mode'][] = $mode;
                    }
                }
                // 如果在区域设置中新勾选了别的区域 这里做删除处理
                if (empty($countryList[$key]['tpl_mode'])) {
                    $item = [];
                    $item['first_amount'] = 0;
                    $item['second_amount'] = 0;
                    $item['first_piece'] = 1;
                    $item['second_piece'] = 1;
                    $item['first_weight'] = 1;
                    $item['second_weight'] = 1;
                    $item['first_volume'] = 1;
                    $item['second_volume'] = 1;
                    $item['region_name'] = '全国';
                    $item['region'] = [];
                    $item['is_default'] = 1;
                    $countryList[$key]['tpl_mode'][] = $item;
//                    unset($countryList[$key]);
                }
            }
        }
        $info['countryList'] = $countryList;
        return getReturn(200, '', $info);
    }

    public function getSelectTpl($storeId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['is_delete'] = 0;
        $options = [];
        $options['field'] = 'tpl_id,tpl_name';
        $options['where'] = $where;
        $result = $this->queryList($options);
        return $result['data']['list'];
    }

    public function getTpl($tplId = 0, $storeId = 0)
    {
        $where = [];
        $where['tpl_id'] = $tplId;
        $where['store_id'] = $storeId;
        $where['is_delete'] = 0;
        $info = $this->where($where)->find();
        if (empty($info)) return getReturn(-1, '模版不存在');
        return getReturn(200, '', $info);
    }
}