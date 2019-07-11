<?php

namespace Common\Util;

/**
 * 二维数组操作类
 * 主要用来操作数据库查询的结果集
 * Class ArraySet
 * @package Common\Util
 * User: hjun
 * Date: 2018-06-01 11:31:08
 * Update: 2018-06-01 11:31:08
 * Version: 1.00
 */
class ArraySet
{
    private $logic = 'AND';

    /**
     * 集合元素
     * @var array
     * @access protected
     */
    protected $_elements = array();

    /**
     * 架构函数
     * @access public
     * @param array $elements 初始化数组元素
     */
    public function __construct($elements = array())
    {
        if (!empty($elements)) {
            $this->_elements = $elements;
        }
    }

    /**
     * 增加元素
     * @access public
     * @param mixed $element 要添加的元素
     * @return boolean
     */
    public function add($element)
    {
        return (array_push($this->_elements, $element)) ? true : false;
    }

    //
    public function unshift($element)
    {
        return (array_unshift($this->_elements, $element)) ? true : false;
    }

    //
    public function pop()
    {
        return array_pop($this->_elements);
    }

    /**
     * 增加元素列表
     * @access public
     * @param array $list 元素列表
     * @return boolean
     */
    public function addAll($list)
    {
        $before = $this->size();
        foreach ($list as $element) {
            $this->add($element);
        }
        $after = $this->size();
        return ($before < $after);
    }

    /**
     * 清除所有元素
     * @access public
     */
    public function clear()
    {
        $this->_elements = array();
    }

    /**
     * 是否包含某个元素
     * @access public
     * @param mixed $element 查找元素
     * @return string
     */
    public function contains($element)
    {
        return (array_search($element, $this->_elements) !== false);
    }

    /**
     * 根据索引取得元素
     * @access public
     * @param integer $index 索引
     * @return mixed
     */
    public function get($index)
    {
        return $this->_elements[$index];
    }

    /**
     * 查找匹配元素，并返回第一个元素所在位置
     * 注意 可能存在0的索引位置 因此要用===False来判断查找失败
     * @access public
     * @param mixed $element 查找元素
     * @return integer
     */
    public function indexOf($element)
    {
        return array_search($element, $this->_elements);
    }

    /**
     * 判断元素是否为空
     * @access public
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->_elements);
    }

    /**
     * 最后一个匹配的元素位置
     * @access public
     * @param mixed $element 查找元素
     * @return integer
     */
    public function lastIndexOf($element)
    {
        for ($i = (count($this->_elements) - 1); $i > 0; $i--) {
            if ($element == $this->get($i)) {
                return $i;
            }
        }
        return false;
    }

    public function toJson()
    {
        return json_encode($this->_elements);
    }

    /**
     * 根据索引移除元素
     * 返回被移除的元素
     * @access public
     * @param integer $index 索引
     * @return mixed
     */
    public function remove($index)
    {
        $element = $this->get($index);
        if (!is_null($element)) {
            array_splice($this->_elements, $index, 1);
        }
        return $element;
    }

    /**
     * 移出一定范围的数组列表
     * @access public
     * @param integer $offset 开始移除位置
     * @param integer $length 移除长度
     */
    public function removeRange($offset, $length)
    {
        array_splice($this->_elements, $offset, $length);
    }

    /**
     * 移出重复的值
     * @access public
     */
    public function unique()
    {
        $this->_elements = array_unique($this->_elements);
    }

    /**
     * 取出一定范围的数组列表
     * @access public
     * @param integer $offset 开始位置
     * @param integer $length 长度
     * @return array
     */
    public function range($offset, $length = null)
    {
        return array_slice($this->_elements, $offset, $length);
    }

    /**
     * 设置列表元素
     * 返回修改之前的值
     * @access public
     * @param integer $index 索引
     * @param mixed $element 元素
     * @return mixed
     */
    public function set($index, $element)
    {
        $previous = $this->get($index);
        $this->_elements[$index] = $element;
        return $previous;
    }

    /**
     * 获取列表长度
     * @access public
     * @return integer
     */
    public function size()
    {
        return count($this->_elements);
    }

    /**
     * 转换成数组
     * @access public
     * @return array
     */
    public function toArray()
    {
        return $this->_elements;
    }

    // 列表排序
    public function ksort()
    {
        ksort($this->_elements);
    }

    // 列表排序
    public function asort()
    {
        asort($this->_elements);
    }

    // 逆向排序
    public function rsort()
    {
        rsort($this->_elements);
    }

    // 自然排序
    public function natsort()
    {
        natsort($this->_elements);
    }

    /**
     * 逻辑或运算
     * @param $results
     * @return boolean
     * User: hjun
     * Date: 2018-06-01 13:04:05
     * Update: 2018-06-01 13:04:05
     * Version: 1.00
     */
    public function logicOR($results)
    {
        return in_array(true, $results);
    }

    /**
     * 逻辑与运算
     * @param $results
     * @return boolean
     * User: hjun
     * Date: 2018-06-01 13:04:12
     * Update: 2018-06-01 13:04:12
     * Version: 1.00
     */
    public function logicAND($results)
    {
        return !in_array(false, $results);
    }


    /**
     * 结果取集
     * @param array $results
     * @param string $rule
     * @return array
     * User: hjun
     * Date: 2018-08-10 12:08:49
     * Update: 2018-08-10 12:08:49
     * Version: 1.00
     */
    public function resultMerge($results = [], $rule = 'AND')
    {
        $result = [];
        foreach ($results as $res) {
            $result = $res;
            break;
        }
        $method = strtoupper($rule) === 'OR' ? 'array_merge' : 'array_intersect';
        foreach ($results as $res) {
            $result = $method($result, $res);
        }
        return array_values(array_unique($result));
    }

    /**
     * where条件搜索
     * @param $key
     * @param $value
     * @return array 返回符合条件的索引数组
     * User: hjun
     * Date: 2018-08-10 12:00:44
     * Update: 2018-08-10 12:00:44
     * Version: 1.00
     */
    public function whereSearch($key, $value)
    {
        $column = array_column($this->_elements, $key);
        if (is_array($value)) {
            $searchRes = [];
            $count = count($value);
            $rule = strtoupper($value[$count - 1]);
            if (in_array($rule, array('AND', 'OR'))) {
                $count = $count - 1;
            } else {
                $rule = 'AND';
            }
            for ($i = 0; $i < $count; $i++) {
                $searchRes[] = arraySearchDeep($value[$i], $column);
            }
            $result = $this->resultMerge($searchRes, $rule);
            return $result;
        } else {
            return arraySearchDeep($value, $column);
        }
    }

    /**
     * 查询
     * @param array $where
     * @param string $type
     * @return array
     * User: hjun
     * Date: 2018-06-01 13:23:10
     * Update: 2018-06-01 13:23:10
     * Version: 1.00
     */
    public function query($where = [], $type = 'select')
    {
        if (empty($where)) return $this->_elements;
        if (isset($where['_logic'])) {
            $this->logic = $where['_logic'];
            unset($where['_logic']);
        }
        // 搜索索引
        $searchRes = [];
        foreach ($where as $key => $value) {
            $searchRes[] = $this->whereSearch($key, $value);
        }
        $result = $this->resultMerge($searchRes, $this->logic);
        if ($type === 'delete') {
            return $result;
        }
        $arr = [];
        foreach ($result as $index) {
            $arr[] = $this->get($index);
        }
        return $arr;
        $method = strtolower($this->logic) == 'or' ? 'logicOR' : 'logicAND';
        $result = [];
        $delete = [];
        foreach ($this->_elements as $index => $data) {
            $bool = [];
            foreach ($where as $key => $value) {
                $bool[] = $data[$key] == $value;
            }
            if ($this->$method($bool)) {
                $result[] = $data;
                $delete[] = $index;
                // 如果是find 则直接返回这一条数据集
                if ($type === 'find') return $result;
            }
        }
        return $type === 'delete' ? $delete : $result;
    }

    /**
     * 根据条件获取相应的结果集
     * @param array $where
     * @return array
     * User: hjun
     * Date: 2018-06-01 11:51:02
     * Update: 2018-06-01 11:51:02
     * Version: 1.00
     */
    public function select($where = [])
    {
        return $this->query($where, 'select');
    }

    /**
     * 查找数据
     * @param array $where
     * @return array
     * User: hjun
     * Date: 2018-06-01 13:22:56
     * Update: 2018-06-01 13:22:56
     * Version: 1.00
     */
    public function find($where = [])
    {
        $result = $this->query($where, 'find');
        return empty($result[0]) ? [] : $result[0];
    }

    /**
     * 查询字段
     * @param array $where
     * @param string $field
     * @param bool $spea
     * @return mixed
     * User: hjun
     * Date: 2018-06-01 15:07:21
     * Update: 2018-06-01 15:07:21
     * Version: 1.00
     */
    public function getField($where = [], $field = '', $spea = false)
    {
        if ($spea) {
            $results = $this->select($where);
            $set = [];
            foreach ($results as $data) {
                if (isset($data[$field])) {
                    $set[] = $data[$field];
                }
            }
            return $set;
        } else {
            $info = $this->find($where);
            return isset($info[$field]) ? $info[$field] : '';
        }
    }

    /**
     * 根据条件移除元素
     * @param array $where
     * @return array
     * User: hjun
     * Date: 2018-06-01 15:35:45
     * Update: 2018-06-01 15:35:45
     * Version: 1.00
     */
    public function delete($where = [])
    {
        $indexs = $this->query($where, 'delete');
        $deleted = [];
        foreach ($indexs as $index) {
            $delete = $this->get($index);
            if (!is_null($delete)) {
                unset($this->_elements[$index]);
                $deleted[] = $delete;
            }
        }
        $this->_elements = array_values($this->_elements);
        return $deleted;
    }
}