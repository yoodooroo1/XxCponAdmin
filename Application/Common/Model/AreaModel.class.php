<?php

namespace Common\Model;
/**
 * Class AreaModel
 * User: hj
 * Date: 2017-10-27 00:27:51
 * Desc: 省市区表
 * Update: 2017-10-27 00:27:54
 * Version: 1.0
 * @package Common\Model
 */
class AreaModel extends BaseModel
{
    protected $tableName = 'mb_areas';

    protected $_validate = [
        ['area_name', 'require', '请输入区域名称', 0, 'regex', 3],
        ['status', '0,1', '请选择是否显示', 0, 'in', 3],
    ];

    private $lastTotal;

    /**
     * @return mixed
     */
    public function getLastTotal()
    {
        return $this->lastTotal;
    }

    /**
     * @param mixed $lastTotal
     */
    public function setLastTotal($lastTotal)
    {
        $this->lastTotal = $lastTotal;
    }

    /**
     * @param int $pid
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-27 00:30:22
     * Desc: 获取省市区
     * Update: 2017-10-27 00:30:24
     * Version: 1.0
     */
    public function getAreaList($pid = 0)
    {
        $list = S('areaList');
        if (empty($list)) {
            $model = D('Area');
            $where = [];
            $where['a.is_delete'] = 0;
            $where['a.status'] = 1;
            $list = $model
                ->alias('a')
                ->field('a.area_id,a.area_name,a.area_pid')
                ->where($where)
                ->order('a.area_sort ASC,a.area_id ASC')
                ->select();
            $list = getTreeArr($list, 'area_id', 'area_pid', 'child');
            $list = arrayFlush($list);
            S('areaList', $list);
        }
        return getReturn(200, '', $list);
    }

    /**
     * 获取所有区划列表
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-04-12 14:10:17
     * Update: 2018-04-12 14:10:17
     * Version: 1.00
     */
    public function getAllArea()
    {
        $list = S('allAreaList');
        if (empty($list)) {
            $model = M('mb_areas');
            $where = [];
            $where['a.is_delete'] = 0;
            $where['a.status'] = 1;
            $list = $model
                ->alias('a')
                ->field('a.area_id,a.area_name,a.area_pid,a.area_type')
                ->where($where)
                ->order('a.area_sort ASC,a.area_id ASC')
                ->select();
            S('allAreaList', $list);
        }
        return $list;
    }

    public function getAreaData()
    {
        $list = $this->getAllArea();
        $country = [];
        $province = [];
        $city = [];
        $area = [];
        foreach ($list as $item) {
            switch ((int)$item['area_type']) {
                case -1:
                    $country[] = $item;
                    break;
                case 0:
                    $province[] = $item;
                    break;
                case 1:
                    $city[] = $item;
                    break;
                case 2:
                    $area[] = $item;
                    break;
                default:
                    break;
            }
        }
        $data['country'] = $country;
        $data['province'] = $province;
        $data['city'] = $city;
        $data['area'] = $area;
        return $data;
    }

    /**
     * 根据父级ID获取地区列表
     * @param int $pid
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-18 01:46:36
     * Update: 2018-03-18 01:46:36
     * Version: 1.00
     */
    public function getAreaListByPid($pid = -1, $page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        $where['area_pid'] = $pid;
        $where['is_delete'] = 0;
        $where = array_merge($where, $condition);
        $options = [];
        $options['field'] = 'area_id,area_pid,area_name,status,area_sort,area_type,short_name,area_name_en-us';
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = 'area_sort ASC,area_id ASC';
        $result = $this->queryList($options);
        $this->setLastTotal($result['data']['total']);
        return $result['data']['list'];
    }

    public function getArea($areaId = 0)
    {
        $where = [];
        $where['area_id'] = $areaId;
        $where['is_delete'] = 0;
        $options = [];
        $options['field'] = 'area_id,area_pid,area_name,status,area_sort,area_type,short_name,area_name_en-us';
        $options['where'] = $where;
        $result = $this->queryRow($options);
        if (empty($result['data'])) return getReturn(-1, '记录不存在');
        return $result;
    }

    /**
     * 新增/修改 区域
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-18 03:11:24
     * Update: 2018-03-18 03:11:24
     * Version: 1.00
     */
    public function saveArea($data = [])
    {
        if ($data['area_type'] > -1) {
            $where = [];
            $where['area_id'] = $data['area_pid'];
            $where['is_delete'] = 0;
            $pInfo = $this->where($where)->find();
            if (empty($pInfo)) return getReturn(-1, '上级区域不存在');
        }
        $data['area_sort'] = empty($data['area_sort']) ? 0 : $data['area_sort'];
        $data['version'] = $this->max('version') + 1;
        if (isset($data['area_id'])) {
            $options = [];
            $options['where'] = ['area_id' => $data['area_id']];
            $options['field'] = 'area_name,status,area_sort,version,short_name,area_name_en-us';
            $result = $this->saveData($options, $data);
        } else {
            $result = $this->addData([], $data);
        }
        return $result;
    }

    /**
     * 改变区域的排序 越大越后
     * @param int $areaId
     * @param int $sort
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-18 02:26:40
     * Update: 2018-03-18 02:26:40
     * Version: 1.00
     */
    public function changeAreaSort($areaId = 0, $sort = 0)
    {
        $where = [];
        $where['area_id'] = $areaId;
        $where['is_delete'] = 0;
        $info = $this->field('area_id,area_sort')->where($where)->find();
        if (empty($info)) return getReturn(-1, '记录不存在');
        $data = [];
        $data['area_sort'] = empty($sort) ? 0 : $sort;
        $data['version'] = $this->max('version') + 1;
        $result = $this->where($where)->save($data);
        if (false === $result) return getReturn();
        return getReturn(200, '', $sort);
    }

    /**
     * 改变显示状态
     * @param int $areaId
     * @param int $status 1-显示 0-隐藏
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-18 02:26:56
     * Update: 2018-03-18 02:26:56
     * Version: 1.00
     */
    public function changeStatus($areaId = 0, $status = 0)
    {
        $where = [];
        $where['area_id'] = $areaId;
        $where['is_delete'] = 0;
        $info = $this->field('area_id,status')->where($where)->find();
        if (empty($info)) return getReturn(-1, '记录不存在');
        if ($info['status'] == $status) return getReturn(-1, '记录已更新');
        $data = [];
        $data['status'] = empty($status) ? 0 : 1;
        $data['version'] = $this->max('version') + 1;
        $result = $this->where($where)->save($data);
        if (false === $result) return getReturn();
        return getReturn(200, '', $status);
    }

    /**
     * 删除区域
     * @param int $areaId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-18 02:27:19
     * Update: 2018-03-18 02:27:19
     * Version: 1.00
     */
    public function delArea($areaId = 0)
    {
        $where = [];
        $where['area_id'] = $areaId;
        $where['is_delete'] = 0;
        $info = $this->field('area_id,status')->where($where)->find();
        if (empty($info)) return getReturn(-1, '记录不存在');
        $data = [];
        $data['is_delete'] = 1;
        $data['version'] = $this->max('version') + 1;
        $result = $this->where($where)->save($data);
        if (false === $result) return getReturn();
        return getReturn(200, '');
    }

    public function getCountry($store_id)
    {
        $config = M('mb_store_config');
        $w = array();
        $w['store_id'] = $store_id;
        $areas = $config->where($w)->getField('area_ids');
        $area_ids = empty($areas) ? '0' : $areas;
        $w2 = array();
        $w2['area_id'] = array('in', $area_ids);
        $w2['area_pid'] = -1;
        $w2['is_delete'] = 0;
        $w2['status'] = 1;
        $w2['area_type'] = -1;
        $area = M('mb_areas');
        $country_list = $area->where($w2)->field('area_id,area_name')->select();
        return $country_list;
    }

    public function getCountryList()
    {
        $list = $this->getAllArea();
        $country = [];
        foreach ($list as $area) {
            if ($area['area_type'] == -1) {
                $country[] = $area;
            }
        }
        return $country;
    }

    public function getChildByPid($pid = -2)
    {
        $list = $this->getAllArea();
        $child = [];
        foreach ($list as $area) {
            if ($area['area_pid'] == $pid) {
                $child[] = $area;
            }
        }
        return $child;
    }

    public function getNameById($id = -2)
    {
        $list = $this->getAllArea();
        foreach ($list as $area) {
            if ($area['area_id'] == $id) {
                return $area['area_name'];
            }
        }
        return '';
    }

    public function getIdByName($name = '')
    {
        $list = $this->getAllArea();
        foreach ($list as $area) {
            if ($area['area_name'] === $name) {
                return $area['area_id'];
            }
            if (strpos($area['area_name'], $name) !== false ||
                strpos($name, $area['area_name']) !== false) {
                return $area['area_id'];
            }
        }
        return 0;
    }

    public function getAreaByName($name = '')
    {
        $list = $this->getAllArea();
        foreach ($list as $area) {
            if ($area['area_name'] === $name) {
                return $area;
            }
            if (strpos($area['area_name'], $name) !== false ||
                strpos($name, $area['area_name']) !== false) {
                return $area;
            }
        }
        return [];
    }

    /**
     * 根据区域的ID 或 名字 获取 国家、省份、城市、区域的ID
     * @param int $areaId
     * @param string $areaName
     * @return array
     * User: hjun
     * Date: 2018-11-15 15:35:19
     * Update: 2018-11-15 15:35:19
     * Version: 1.00
     */
    public function getCPCAByAreaIdOrAreaName($areaId = 0, $areaName = '')
    {
        $area = $this->getArea($areaId)['data'];
        if (empty($area)) {
            $area = $this->getAreaByName($areaName);
        }
        if (empty($area)) {
            return [0, 0, 0, 0];
        }
        $areaId = $area['area_id'];
        $city = $this->getArea($area['area_pid'])['data'];
        $cityId = $city['area_id'] > 0 ? $city['area_id'] : 0;
        $province = $this->getArea($city['area_pid'])['data'];
        $provinceId = $province['area_id'] > 0 ? $province['area_id'] : 0;
        $country = $this->getArea($province['area_pid'])['data'];
        $countryId = $country['area_id'] > 0 ? $country['area_id'] : 0;
        return [$countryId, $provinceId, $cityId, $areaId];
    }
}