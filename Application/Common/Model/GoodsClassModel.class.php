<?php

namespace Common\Model;

use Org\Util\ArrayList;

// TODO 测试
class GoodsClassModel extends BaseModel
{

    protected $tableName = 'goods_class';

    /**
     * 获取商品分类
     * @param int $storeId 商家ID
     * @param int $page 页数
     * @param int $limit 条数
     * @param array $condition 额外的查询条件 因为可能会被别的方法嗲用
     * @param int $type 类型
     *  1- 只获取一级分类
     *  2- 分类加子分类 树结构
     *  3- 列表格式的分类加子分类  ID是最低级 名称是每级拼接的
     *  4- 列表格式的分类加子分类  ID是每级拼接 名称是每级拼接
     *  5- 列表格式的分类加子分类  ID是最低级 名称是最低级、
     *  6- 列表格式的分类加子分类  ID是每级拼接 名称是最低级
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-27 14:24:46
     * Version: 1.0
     */
    public function getFirstLevelClass($storeId = 0, $page = 1, $limit = 0, $condition = [], $type = 1)
    {
        $where = [];
        $where['store_id'] = $storeId;
        if ($type == 1) $where['gc_parent_id'] = 0;
        $where['gc_show'] = 1;
        $where['isdelete'] = 0;
        $where = array_merge($where, $condition);
        $pid = $where['gc_parent_id'] > 0 ? $where['gc_parent_id'] : 0;
        if ($type != 1) {
            unset($where['gc_parent_id']);
        }
        $skip = ($page - 1) * $limit;
        $take = $limit;
        $list = $this
            ->field('store_id,gc_id class_id,gc_parent_id,gc_name class_name,gc_description description,gc_keywords keyword')
            ->where($where)
            ->order('gc_sort ASC,gc_id DESC')
            ->limit($skip, $take)
            ->select();
        $other = [];
        switch ((int)$type) {
            case 3:
                $other = [
                    ['name' => 'class_name', 'pname' => 'class_name', 'link' => '>']
                ];
                break;
            case 4:
                $other = [
                    ['name' => 'class_name', 'pname' => 'class_name', 'link' => '>'],
                    ['name' => 'class_id', 'pname' => 'class_p_id', 'link' => '|'],
                ];
                break;
            case 5:
                break;
            case 6:
                $other = [
                    ['name' => 'class_id', 'pname' => 'class_p_id', 'link' => '|'],
                ];
                break;
            default:
                break;
        }
        $list = getTreeArr($list, 'class_id', 'gc_parent_id', 'child', $pid);
        $list = arrayFlush($list);
        if ($type > 2) {
            $list = unTree($list);
            $list = getTreeArr($list, 'class_id', 'gc_parent_id', 'child', $pid, $other);
            $list = unTree($list);
        }
        return getReturn(200, '', $list);
    }

    /**
     * 获取商城一级分类
     * @param int $storeId 商家ID
     * @param int $page 页数
     * @param int $limit 条数
     * @param array $condition 额外的查询条件 因为可能会被别的方法嗲用
     * @param int $type 类型
     *  1- 只获取一级分类
     *  2- 分类加子分类 树结构
     *  3- 列表格式的分类加子分类  ID是最低级 名称是每级拼接的
     *  4- 列表格式的分类加子分类  ID是每级拼接 名称是每级拼接
     *  5- 列表格式的分类加子分类  ID是最低级 名称是最低级、
     *  6- 列表格式的分类加子分类  ID是每级拼接 名称是最低级
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-27 14:24:46
     * Version: 1.0
     */
    public function getFirstLevelClass2($storeId = 0, $page = 1, $limit = 0, $condition = [], $type = 1)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['gc_parent_id'] = 0;
        $where['gc_show'] = 1;
        $where['isdelete'] = 0;
        $where = array_merge($where, $condition);
        $skip = ($page - 1) * $limit;
        $take = $limit;
        $list = $this
            ->field('store_id,gc_id class_id,gc_name class_name,gc_description description,gc_keywords keyword')
            ->where($where)
            ->order('gc_sort ASC,gc_id DESC')
            ->limit($skip, $take)
            ->select();
        if (false === $list) {
            logWrite("查询商家{$storeId}分类出错:" . $this->getDbError());
            return getReturn();
        }
        switch ((int)$type) {
            // 分类加子分类 树结构
            case 2:
                foreach ($list as $key => $value) {
                    $list[$key]['child'] = $this->getChildClass($value['class_id']);
                }
                break;
            // 列表格式的分类加子分类  ID是最低级 名称是每级拼接的
            case 3:
                foreach ($list as $key => $value) {
                    $child = $this->getChildClass($value['class_id'], $value['store_id'], 2, $value['class_name']);
                    $arrayUtil = new ArrayList($list);
                    $index = $arrayUtil->indexOf($value);
                    array_splice($list, $index + 1, 0, $child);
                }
                break;
            // 列表格式的分类加子分类  ID是每级拼接 名称是每级拼接
            case 4:
                foreach ($list as $key => $value) {
                    $child = $this->getChildClass($value['class_id'], $value['store_id'], 3, $value['class_name'], $value['class_id']);
                    $arrayUtil = new ArrayList($list);
                    $index = $arrayUtil->indexOf($value);
                    $list[$index]['class_p_id'] = $value['class_id'];
                    array_splice($list, $index + 1, 0, $child);
                }
                break;
            // 列表格式的分类加子分类  ID是最低级 名称是最低级
            case 5:
                foreach ($list as $key => $value) {
                    $child = $this->getChildClass($value['class_id'], $value['store_id'], 4);
                    $arrayUtil = new ArrayList($list);
                    $index = $arrayUtil->indexOf($value);
                    array_splice($list, $index + 1, 0, $child);
                }
                break;
            // 列表格式的分类加子分类  ID是每级拼接 名称是最低级
            case 6:
                foreach ($list as $key => $value) {
                    $child = $this->getChildClass($value['class_id'], $value['store_id'], 5, '', $value['class_id']);
                    $arrayUtil = new ArrayList($list);
                    $index = $arrayUtil->indexOf($value);
                    $list[$index]['class_p_id'] = $value['class_id'];
                    array_splice($list, $index + 1, 0, $child);
                }
                break;
            // 默认只获取一级分类
            default:
                break;
        }
        return getReturn(200, '', $list);
    }

    /**
     * 获取某个分类下的所有子分类
     * @param int $classId 分类ID
     * @param int $storeId 商家ID 可以不传
     * @param int $type 1-子集类型 2-列表类型
     *  1- 树结构
     *  2- 列表结构 ID是最低 名称拼接
     *  3- 列表结构 ID拼接 名称拼接
     *  4- 列表结构 ID最底 名称最低
     *  5- 列表结构 ID拼接 名称最底
     * @param string $pName 父分类的名称 type为2的时候传
     * @param string $pid 父分类的ID type为2的时候传
     * @return array 子分类数组 多维
     * User: hj
     * Date: 2017-09-27 16:14:52
     * Version: 1.0
     */
    public function getChildClass($classId = 0, $storeId = 0, $type = 1, $pName = '', $pid = '')
    {
        if ((int)$classId <= 0 && (int)$storeId <= 0) return [];
        $where = [];
        $where['gc_parent_id'] = $classId;
        $where['gc_show'] = 1;
        $where['isdelete'] = 0;
        if ((int)$storeId > 0) $where['store_id'] = $storeId;
        $child = $this
            ->field('gc_id class_id,gc_name class_name,gc_description description,gc_keywords keyword')
            ->order('gc_sort DESC,gc_id DESC')
            ->where($where)
            ->select();
        foreach ($child as $key => $value) {
            switch ((int)$type) {
                // 分类加子分类 树结构
                case 1:
                    $child[$key]['child'] = $this->getChildClass($value['class_id'], $storeId);
                    break;
                // 列表格式的分类加子分类  ID是最低级 名称是每级拼接的
                case 2:
                    // index 要先算出来
                    $arrUtil = new ArrayList($child);
                    $index = $arrUtil->indexOf($value);
                    $child[$index]['class_name'] = $pName . '>' . $value['class_name'];
                    $newChild = $this->getChildClass($value['class_id'], $storeId, $type, $child[$index]['class_name']);
                    if (!empty($newChild)) {
                        array_splice($child, $index + 1, 0, $newChild);
                    }
                    break;
                // 列表格式的分类加子分类  ID是拼接 名称是每级拼接的
                case 3:
                    $arrUtil = new ArrayList($child);
                    $index = $arrUtil->indexOf($value);
                    $child[$index]['class_name'] = $pName . '>' . $value['class_name'];
                    $child[$index]['class_p_id'] = $pid . '|' . $value['class_id'];
                    $newChild = $this->getChildClass($value['class_id'], $storeId, $type, $child[$index]['class_name'], $child[$index]['class_p_id']);
                    if (!empty($newChild)) {
                        array_splice($child, $index + 1, 0, $newChild);
                    }
                    break;
                // 列表格式的分类加子分类  ID是最低级 名称是最低级
                case 4:
                    $arrUtil = new ArrayList($child);
                    $index = $arrUtil->indexOf($value);
                    $newChild = $this->getChildClass($value['class_id'], $storeId, $type);
                    if (!empty($newChild)) {
                        array_splice($child, $index + 1, 0, $newChild);
                    }
                    break;
                // 列表格式的分类加子分类  ID是每级拼接 名称是最低级
                case 5:
                    $arrUtil = new ArrayList($child);
                    $index = $arrUtil->indexOf($value);
                    $child[$index]['class_p_id'] = $pid . '|' . $value['class_id'];
                    $newChild = $this->getChildClass($value['class_id'], $storeId, $type, '', $child[$index]['class_p_id']);
                    if (!empty($newChild)) {
                        array_splice($child, $index + 1, 0, $newChild);
                    }
                    break;
                default:
                    break;
            }
        }
        return $child;
    }

    /**
     * 获取分类ID下的子分类ID数组
     * @param int $classId
     * @return array []
     * User: hj
     * Date: 2017-09-27 17:41:01
     * Version: 1.0
     */
    public function getChildClassId($classId = 0)
    {
        if ((int)$classId <= 0) return [];
        $where = [];
        $where['isdelete'] = 0;
        $where['gc_show'] = 1;
        $where['gc_parent_id'] = $classId;
        $childId = $this->where($where)->getField('gc_id', true);
        $childId = empty($childId) ? [] : $childId;
        foreach ($childId as $key => $value) {
            $childId = array_merge($childId, $this->getChildClassId($value));
        }
        return $childId;
    }

    /**
     * 获取分类ID的所有父级ID数组
     * @param string $pIdStr ID字符串 1|2|3
     * @return array
     * User: hjun
     * Date: 2018-01-04 11:42:05
     * Update: 2018-01-04 11:42:05
     * Version: 1.00
     */
    public function getClassParentId($pIdStr = '')
    {
        if (empty($pIdStr)) return [];
        $id = explode('|', $pIdStr);
        $length = count($id);
        $pId = [];
        for ($i = 0; $i < $length - 1; $i++) {
            if (!in_array($id[$i], $pId)) {
                $pId[] = $id[$i];
            }
        }
        return $pId;
    }

    /**
     * 获取分类的所有子级ID数组
     * @param string $classId
     * @param array $classList
     * @return array
     * User: hjun
     * Date: 2018-01-04 11:48:02
     * Update: 2018-01-04 11:48:02
     * Version: 1.00
     */
    public function getClassChildId($classId = '', $classList = [])
    {
        $cid = [];
        foreach ($classList as $key => $value) {
            if (!isset($value['pid'])) {
                $value['pid'] = $this->getClassParentId($value['class_p_id']);
            }
            if (in_array($classId, $value['pid']) && !in_array($value['class_id'], $cid)) {
                $cid[] = $value['class_id'];
            }
        }
        return $cid;
    }

    /**
     * 判断一个分类是否拥有同级分类
     * @param array $classInfo 该分类的信息
     * @param array $classList 分类列表
     * @return bool
     * User: hjun
     * Date: 2018-01-04 12:23:28
     * Update: 2018-01-04 12:23:28
     * Version: 1.00
     */
    public function hasSameLevelClass($classInfo = [], $classList = [])
    {
        $pid = $classInfo['pid'];
        if (empty($pid)) return false;
        sort($pid);
        $pidStr = implode('', $pid);
        foreach ($classList as $key => $value) {
            if (!isset($value['pid'])) {
                $value['pid'] = $this->getClassParentId($value['class_p_id']);
            }
            if (!empty($value['pid']) && $classInfo['class_id'] != $value['class_id']) {
                $currentPid = $value['pid'];
                sort($currentPid);
                $currentPidStr = implode('', $currentPid);
                $bool = $pidStr === $currentPidStr;
                if ($bool) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取分类的同级分类ID数组
     * @param array $classInfo
     * @param array $classList
     * @return array
     * User: hjun
     * Date: 2018-01-05 15:53:13
     * Update: 2018-01-05 15:53:13
     * Version: 1.00
     */
    public function getSameLevelClass($classInfo = [], $classList = [])
    {
        $pid = $classInfo['pid'];
        if (empty($pid)) return [];
        sort($pid);
        $pidStr = implode('', $pid);
        $sameLevel = [];
        foreach ($classList as $key => $value) {
            if (!isset($value['pid'])) {
                $value['pid'] = $this->getClassParentId($value['class_p_id']);
            }
            if (!empty($value['pid']) && $classInfo['class_id'] != $value['class_id']) {
                $currentPid = $value['pid'];
                sort($currentPid);
                $currentPidStr = implode('', $currentPid);
                $bool = $pidStr === $currentPidStr;
                if ($bool) {
                    $sameLevel[] = $value['class_id'];
                }
            }
        }
        return $sameLevel;
    }

    /**
     * 获取商品分类
     * @param int $storeId 商家ID
     * @param int $arrType
     *  1- 只获取一级分类
     *  2- 分类加子分类 树结构
     *  3- 列表格式的分类加子分类  ID是最低级 名称是每级拼接的
     *  4- 列表格式的分类加子分类  ID是每级拼接 名称是每级拼接
     *  5- 列表格式的分类加子分类  ID是最低级 名称是最低级、
     *  6- 列表格式的分类加子分类  ID是每级拼接 名称是最低级
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-30 10:55:44
     * Update: 2018-01-30 10:55:44
     * Version: 1.00
     */
    public function getGoodsClass($storeId = 0, $arrType = 1, $condition = [])
    {
        $field = [
            'store_id', 'gc_id', 'gc_name', 'gc_parent_id'
        ];
        $where = [];
        $where['store_id'] = $storeId;
        $where['isdelete'] = 0;
        $where['gc_show'] = 1;
        $where = array_merge($where, $condition);
        $options = [];
        $options['field'] = implode(',', $field);
        $options['where'] = $where;
        $options['order'] = 'gc_sort ASC,gc_id DESC';
        $result = $this->queryList($options);
        $list = empty($result['data']['list']) ? [] : $result['data']['list'];
        switch ((int)$arrType) {
            case 1:
                $list = getParentArr($list, 'gc_parent_id');
                break;
            case 2:
                $list = getTreeArr($list, 'gc_id', 'gc_parent_id', 'list', 0);
                break;
            case 3:
                break;
            default:
                break;
        }
        return $list;
    }

    /**
     * 根据分类ID数组 获取分类的默认模版ID
     * @param array $classIds
     * @return string
     * User: hjun
     * Date: 2018-12-24 00:35:03
     * Update: 2018-12-24 00:35:03
     * Version: 1.00
     */
    public function getParamTplIdByClassIds($classIds = [])
    {
        if (empty($classIds)) {
            return '0';
        }
        $classIds = array_reverse($classIds);
        foreach ($classIds as $id) {
            $where = [];
            $where['gc_id'] = $id;
            $where['isdelete'] = NOT_DELETE;
            $tplId = $this->where($where)->getField('goods_param_tpl_id');
            if (!empty($tplId)) {
                return $tplId;
            }
        }
        return '0';
    }
}