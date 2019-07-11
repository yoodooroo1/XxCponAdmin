<?php

namespace Common\Model;

use Org\Util\ArrayList;

// TODO 测试
class MallGoodClassModel extends BaseModel
{
    protected $tableName = 'mb_mallclass';

    /**
     * 获取商城一级分类
     * @param int $channelId 渠道号
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
    public function getFirstLevelClass($channelId = 0, $page = 1, $limit = 0, $condition = [], $type = 1)
    {
        $where = [];
        $where['channel_id'] = $channelId;
        if ($type == 1) $where['pid'] = 0;
        $where['isshow'] = 1;
        $where['isdelete'] = 0;
        $where = array_merge($where, $condition);
        $pid = $where['pid'] > 0 ? $where['pid'] : 0;
        if ($type != 1){
            unset($where['pid']);
        }
        $skip = ($page - 1) * $limit;
        $take = $limit;
        $list = $this
            ->field('id class_id,pid,classname class_name,logo,banner,description,keyword,url')
            ->where($where)
            ->order('sort DESC,id ASC')
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
        $list = getTreeArr($list, 'class_id', 'pid', 'child', $pid);
        $list = arrayFlush($list);
        if ($type > 2) {
            $list = unTree($list);
            $list = getTreeArr($list, 'class_id', 'pid', 'child', $pid, $other);
            $list = unTree($list);
        }
        return getReturn(200, '', $list);
    }

    /**
     * 获取商城一级分类
     * @param int $channelId 渠道号
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
    public function getFirstLevelClass2($channelId = 0, $page = 1, $limit = 0, $condition = [], $type = 1)
    {
        $where = [];
        $where['channel_id'] = $channelId;
        $where['pid'] = 0;
        $where['isshow'] = 1;
        $where['isdelete'] = 0;
        $where = array_merge($where, $condition);
        $skip = ($page - 1) * $limit;
        $take = $limit;
        $list = $this
            ->field('id class_id,classname class_name,logo,banner,description,keyword')
            ->where($where)
            ->order('sort DESC,id ASC')
            ->limit($skip, $take)
            ->select();
        if (false === $list) {
            logWrite("查询商城分类出错:" . $this->getDbError());
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
                    $child = $this->getChildClass($value['class_id'], $channelId, 2, $value['class_name']);
                    $arrayUtil = new ArrayList($list);
                    $index = $arrayUtil->indexOf($value);
                    array_splice($list, $index + 1, 0, $child);
                }
                break;
            // 列表格式的分类加子分类  ID是每级拼接 名称是每级拼接
            case 4:
                foreach ($list as $key => $value) {
                    $child = $this->getChildClass($value['class_id'], $channelId, 3, $value['class_name'], $value['class_id']);
                    $arrayUtil = new ArrayList($list);
                    $index = $arrayUtil->indexOf($value);
                    $list[$index]['class_p_id'] = $value['class_id'];
                    array_splice($list, $index + 1, 0, $child);
                }
                break;
            // 列表格式的分类加子分类  ID是最低级 名称是最低级
            case 5:
                foreach ($list as $key => $value) {
                    $child = $this->getChildClass($value['class_id'], $channelId, 4);
                    $arrayUtil = new ArrayList($list);
                    $index = $arrayUtil->indexOf($value);
                    array_splice($list, $index + 1, 0, $child);
                }
                break;
            // 列表格式的分类加子分类  ID是每级拼接 名称是最低级
            case 6:
                foreach ($list as $key => $value) {
                    $child = $this->getChildClass($value['class_id'], $channelId, 5, '', $value['class_id']);
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
     * @param int $channelId 渠道ID 可以不传
     * @param int $type
     *  1-树结构
     *  2-列表 ID最低 名称拼接
     *  3-列表 ID拼接 名称拼接
     *  4-列表 ID最低 名称最低
     *  5-列表 ID拼接 名称最低
     * @param string $pName 父分类的名称 type为2的时候传
     * @param string $pid 父分类的ID type为2的时候传
     * @return array 子分类数组 多维
     * User: hj
     * Date: 2017-09-27 16:14:52
     * Version: 1.0
     */
    public function getChildClass($classId = 0, $channelId = 0, $type = 1, $pName = '', $pid = '')
    {
        if ((int)$classId <= 0 && (int)$channelId <= 0) return [];
        $where = [];
        $where['pid'] = $classId;
        $where['isshow'] = 1;
        $where['isdelete'] = 0;
        if ((int)$channelId > 0) $where['channel_id'] = $channelId;
        $child = $this
            ->field('id class_id,classname class_name,banner,logo,description,keyword')
            ->order('sort DESC,id ASC')
            ->where($where)
            ->select();
        foreach ($child as $key => $value) {
            switch ((int)$type) {
                // 分类加子分类 树结构
                case 1:
                    $child[$key]['child'] = $this->getChildClass($value['class_id'], $channelId);
                    break;
                // 列表格式的分类加子分类  ID是最低级 名称是每级拼接的
                case 2:
                    // index 要先算出来
                    $arrUtil = new ArrayList($child);
                    $index = $arrUtil->indexOf($value);
                    $child[$index]['class_name'] = $pName . '>' . $value['class_name'];
                    $newChild = $this->getChildClass($value['class_id'], $channelId, $type, $child[$index]['class_name']);
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
                    $newChild = $this->getChildClass($value['class_id'], $channelId, $type, $child[$index]['class_name'], $child[$index]['class_p_id']);
                    if (!empty($newChild)) {
                        array_splice($child, $index + 1, 0, $newChild);
                    }
                    break;
                // 列表格式的分类加子分类  ID是最低级 名称是最低级
                case 4:
                    $arrUtil = new ArrayList($child);
                    $index = $arrUtil->indexOf($value);
                    $newChild = $this->getChildClass($value['class_id'], $channelId, $type);
                    if (!empty($newChild)) {
                        array_splice($child, $index + 1, 0, $newChild);
                    }
                    break;
                // 列表格式的分类加子分类  ID是每级拼接 名称是最低级
                case 5:
                    $arrUtil = new ArrayList($child);
                    $index = $arrUtil->indexOf($value);
                    $child[$index]['class_p_id'] = $pid . '|' . $value['class_id'];
                    $newChild = $this->getChildClass($value['class_id'], $channelId, $type, '', $child[$index]['class_p_id']);
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
        $where['isshow'] = 1;
        $where['pid'] = $classId;
        $childId = $this->where($where)->getField('id', true);
        $childId = empty($childId) ? [] : $childId;
        foreach ($childId as $key => $value) {
            $childId = array_merge($childId, $this->getChildClassId($value));
        }
        return $childId;
    }
}