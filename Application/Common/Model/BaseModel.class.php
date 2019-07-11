<?php

namespace Common\Model;

use Common\Util\DbCache;
use Think\Exception;
use Think\Model;

/**
 * Class BaseModel
 * User: hj
 * Date:
 * Desc:
 * Update:
 * Version: 3.0
 * @package Admin\Model
 */
class BaseModel extends Model
{

    const MODEL_DELETE = 4;

    //====================店铺类型常量================//
    // 说明: 暂时 02 13 45 可以当作3种类型
    const MALL_MAIN_STORE = 0; // 商城主店 channel_type=2 main_store=1
    const MALL_CHILD_STORE = 1; // 商城子店 channel_type=2 main_store=0
    const CHAIN_MAIN_STORE = 2; // 连锁主店 channel_type=4 main_store=1
    const CHAIN_CHILD_STORE = 3; // 连锁子店 channel_type=4 main_store=0
    const ALONE_STORE = 4; // 独立商城 (单独渠道号) channel_type=3 main_store=0
    const NORMAL_STORE = 5; // 普通便利店 channel_type=0
    //====================店铺类型常量================//

    /*
     * 自动验证
     * array(验证字段1,验证规则,错误提示,[验证条件,附加规则,验证时间,参数列表]),
     * 验证规则: require 字段必须、email 邮箱、url URL地址、currency 货币、number 数字
     * 验证条件: 0 存在字段就验证（默认） 1 必须验证 2 不为空就验证
     * 附加规则:
        regex 正则验证，定义的验证规则是一个正则表达式（默认）
        function 函数验证，定义的验证规则是一个函数名
        callback 方法验证，定义的验证规则是当前模型类的一个方法
        confirm 验证表单中的两个字段是否相同，定义的验证规则是一个字段名
        equal 验证是否等于某个值，该值由前面的验证规则定义
        notequal 验证是否不等于某个值，该值由前面的验证规则定义（3.1.2版本新增）
        in 验证是否在某个范围内，定义的验证规则可以是一个数组或者逗号分割的字符串
        notin 验证是否不在某个范围内，定义的验证规则可以是一个数组或者逗号分割的字符串（3.1.2版本新增）
        length
        验证长度，定义的验证规则可以是一个数字（表示固定长度）或者数字范围（例如3,12 表示长度从3到12
        的范围）
        between 验证范围，定义的验证规则表示范围，可以使用字符串或者数组，例如1,31或者array(1,31)
        notbetween 验证不在某个范围，定义的验证规则表示范围，可以使用字符串或者数组（3.1.2版本新增）
        expire 验证是否在有效期，定义的验证规则表示时间范围，可以到时间，例如可以使用 2012-1-15,2013-1-15 表示当前提交有效期在2012-1-15到2013-1-15之间，也可以使用时间戳定义
        ip_allow 验证IP是否允许，定义的验证规则表示允许的IP地址列表，用逗号分隔，例如201.12.2.5,201.12.2.6
        ip_deny 验证IP是否禁止，定义的验证规则表示禁止的ip地址列表，用逗号分隔，例如201.12.2.5,201.12.2.6
        unique 验证是否唯一，系统会根据字段目前的值查询数据库来判断是否存在相同的值，当表单数据中包含主键字段时unique不可用于判断主键字段本身
     * 验证时间: 1 新增 2 编辑 3 全部(默认)
     * 参数列表: array() callback的参数列表
     */
    protected $_validate = [];

    /*
     * 自动完成
     * array(完成字段1,完成规则,[完成时间,附加规则]),
     * 完成字段: 需要进行处理的数据表实际字段名称。
     * 完成规则：需要处理的规则，配合附加规则完成
     * 完成时间：设置自动完成的时间 1-新增 2-更新 3-所有
     * 附加规则：配合验证规则使用:
     *  function 使用函数，表示填充的内容是一个函数名
        callback 回调方法 ，表示填充的内容是一个当前模型的方法
        field 用其它字段填充，表示填充的内容是一个其他字段的值
        string 字符串（默认方式）
        ignore 为空则忽略（3.1.2新增）
     */
    protected $_auto = [];

    /**
     * 已弃用
     * @var array
     * @deprecated 已弃用
     */
    protected $listConfig = array();

    // 乐观锁 默认关闭
    protected $optimLock = false;

    // 最大take数量
    const MAX_TAKE = 9223372036854775807;

    // 查询参数
    protected $queryOptions = [];

    /**
     * @var DbCache
     */
    protected $dbCache;

    private $storeId;
    private $validateError;
    private $lastQueryData;
    private $openTrans = true;
    private $memberId = 0;
    private $memberName = '';

    /**
     * @return mixed
     */
    public function getMemberId()
    {
        return $this->memberId;
    }

    /**
     * @param mixed $memberId
     */
    public function setMemberId($memberId)
    {
        $this->memberId = $memberId;
    }

    /**
     * @return mixed
     */
    public function getMemberName()
    {
        return $this->memberName;
    }

    /**
     * @param mixed $memberName
     */
    public function setMemberName($memberName)
    {
        $this->memberName = $memberName;
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param mixed $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValidateError()
    {
        return $this->validateError;
    }

    /**
     * @param mixed $validateError
     * @return $this
     */
    public function setValidateError($validateError)
    {
        $this->validateError = $validateError;
        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getLastQueryData($name = '')
    {
        return $this->lastQueryData[$name];
    }

    /**
     * @param string $name
     * @param mixed $data
     * @return $this
     */
    public function setLastQueryData($name = '', $data)
    {
        $this->lastQueryData[$name] = $data;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOpenTrans()
    {
        return $this->openTrans;
    }

    /**
     * @param bool $openTrans
     * @return $this
     */
    public function setOpenTrans($openTrans)
    {
        $this->openTrans = $openTrans;
        return $this;
    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans()
    {
        if ($this->isOpenTrans()) {
            return parent::startTrans();
        }
        return;
    }

    /**
     * 提交事务
     * @access public
     * @return boolean
     */
    public function commit()
    {
        if ($this->isOpenTrans()) {
            return parent::commit();
        }
        return true;
    }

    /**
     * 事务回滚
     * @access public
     * @return boolean
     */
    public function rollback()
    {
        if ($this->isOpenTrans()) {
            return parent::rollback();
        }
        return true;
    }


    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);
        if (session('store_id') > 0) {
            $this->setStoreId(session('store_id'));
        }
        if (session('member_id') > 0) {
            $this->setMemberId(session('member_id'));
        }
        if (session('member_name')) {
            $this->setMemberName(session('member_name'));
        }
        $this->dbCache = DbCache::getInstance();
        $this->dbCache->setModel($this);
        $this->dbCache->setNoCacheTable([
            'xunxin_data_record', 'xunxin_data_record2',
            'xunxin_base', 'xunxin_mb_db_cache'
        ]);
    }

    public function getError()
    {
        $error = $this->getValidateError();
        if (isset($error)) {
            return $error;
        }
        return parent::getError(); // TODO: Change the autogenerated stub
    }

    /**
     * 上次查询的结果总数
     * @return int
     * User: hjun
     * Date: 2018-08-11 14:57:34
     * Update: 2018-08-11 14:57:34
     * Version: 1.00
     */
    public function getLastQueryTotal()
    {
        return $this->dbCache->getLastQueryTotal();
    }

    /**
     * 获取上次查询是否强制进行了分页
     * @return bool
     * User: hjun
     * Date: 2018-08-11 14:58:33
     * Update: 2018-08-11 14:58:33
     * Version: 1.00
     */
    public function getLastQueryNeedPage()
    {
        return $this->dbCache->getLastQueryNeedPage();
    }

    /**
     * 已弃用
     * 向数据库新增一条数据
     * @param array $param 要插入的数据，一般为一维数组或二维数组
     * @return bool|mixed 返回主键
     * @deprecated 已弃用 建议使用 addData
     */
    public function _add($param = array())
    {
        // 判断传入的数据
        if (count($param) == 0) {
            $this->error = "传入的参数为空";
            Lg($this, __METHOD__, __LINE__);
            return false;
        }

        // 判断数组的维度
        // 一维数组使用add()方法，二维数组使用addAll()方法，三维数组不支持
        $arr_level = getArrayLevel($param);
        $this->startTrans(); //开启事务
        if ($arr_level == 1) {
            $data = $this->create($param, 1);
            if (false === $data) {
                Lg($this, __METHOD__, __LINE__);
                return false;
            }
            $pk = $this->add();
        } elseif ($arr_level == 2) {
            $pk = $this->addAll($param); // 返回的是最后一条的主键
        } else {
            $this->error = "传入的参数维度过大";
            Lg($this, __METHOD__, __LINE__);
            return false;
        }

        if (false === $pk) {
            Lg($this, __METHOD__, __LINE__);
            $this->rollback(); //回滚事务
            return false;
        }

        $this->commit(); //提交事务
        return $pk;
    }

    /**
     * 已弃用
     * 更新数据
     * @param array $where 更新条件
     * @param array $param 更新的数据
     * @return bool 返回false 或者 影响的记录条数
     * @deprecated 已弃用 建议使用 saveData
     */
    public function _save($where = array(), $param = array())
    {
        //开启事务
        $this->startTrans();

        // 创建数据对象
        $data = $this->create($param, 2);
        if (false === $data) {
            Lg($this, __METHOD__, __LINE__);
            $this->rollback();
            return false;
        }

        // 更新数据
        $result = $this->where($where)->save();
        if (false === $result) {
            Lg($this, __METHOD__, __LINE__);
            // 回滚事务
            $this->rollback();
            return false;
        }

        // 提交事务
        $this->commit();

        return $result;
    }

    /**
     * 已弃用
     * 从数据库根据字段查询
     * @param array $where 查询条件，数组
     * @param int $limit 限制条数，-1不限制，或者是分页查询的每页数量
     * @param bool $is_page 是否是分页查询
     * @param string $order 排序方式
     * @param string $field 查询的字段，默认全部
     * @param bool $is_cache 是否设置缓存，默认false
     * @return array|bool 返回列表 加上 分页链接
     * @deprecated 已弃用 建议使用 queryList
     */
    public function _select($where = array(), $limit = -1, $is_page = false, $order = '', $field = '*', $is_cache = false)
    {
        // 返回的结果
        $result = array();
        $list = false;

        // 查询满足要求的总记录数
        $count = $this->where($where)->count();

        // 判断是否是分页查询
        // 1. 是分页查询，则使用分页类进行查询
        // 2. 不是就根据参数查询
        if ($is_page === true) {

            // 实例化分页类 传入总记录数和每页显示的记录数
            $Page = new \Think\Page($count, $limit);

            // 分页显示输出
            $show = $Page->show();

            // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
            $list = $this->field($field)->where($where)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->cache($is_cache)->select();
            $result['page'] = $show;

        } elseif ($is_page === false) {

            $limit === -1 ?
                $list = $this->field($field)->where($where)->order($order)->cache($is_cache)->select() :
                $list = $this->field($field)->where($where)->order($order)->limit($limit)->cache($is_cache)->select();

        }

        // 出错记录日志
        if (false === $list) {
            Lg($this, __METHOD__, __LINE__);
            return false;
        }

        $result['list'] = $list;
        return $result;
    }

    /**
     * 已弃用
     * 删除记录
     * @param array $where 删除条件
     * @param int $limit 删除条数限制，配合order使用
     * @param string $order 排序方式，配合limit使用
     * @return bool|mixed 返回删除的记录数，或者false
     * @deprecated 已弃用 建议使用 delData
     */
    public function _delete($where = array(), $limit = -1, $order = '')
    {
        //开启事务
        $this->startTrans();

        if ($limit === -1) {
            $result = $this->where($where)->delete();
        } elseif ($limit > 0) {
            $result = $this->where($where)->order($order)->limit($limit)->delete();
        }

        if (false === $result) {
            Lg($this, __METHOD__, __LINE__);
            // 回滚事务
            $this->rollback();
            return false;
        }

        // 提交事务
        $this->commit();

        return $result;
    }

    /**
     * 已弃用
     * 获取字段值
     * 1. 某个字段  getField('id');
     * 2. 一列的数组 getField('id', true)
     * 3. 关联数组 getField('id,nickname', true);
     * 4. 多个字段的数组 getField('id,nickname,email');  id 依旧为键名，但是value是一个array，包含所有字段
     * 5. 键名 + 字符串， getField('id,nickname,email', ':'); id => nickname:email
     * 6. 限制条数 getField('id,nickname', 3);
     * @param array $where 查询条件
     * @param string $field 查询字段
     * @param bool $option 选项
     * @param bool $is_cache 是否缓存
     * @return bool|mixed 返回结果
     * @deprecated 已弃用 建议使用 queryField
     */
    public function _getField($where = array(), $field = '', $option = false, $is_cache = false)
    {
        // 返回的结果
        $result = $this->where($where)->cache($is_cache)->getField($field, $option);

        // 出错记录日志
        if (false === $result) {
            Lg($this, __METHOD__, __LINE__);
            return false;
        }

        return $result;
    }

    /**
     * 已弃用
     * 更新某个字段
     * @param array $where 更新条件
     * @param string $field 更新字段
     * @param string $value 更新的值
     * @return bool 返回结果
     * @deprecated 已弃用 建议使用saveData
     */
    public function _setField($where = array(), $field = '', $value = '')
    {
        //开启事务
        $this->startTrans();

        $result = $this->where($where)->setField($field, $value);
        if (false === $result) {
            Lg($this, __METHOD__, __LINE__);
            // 回滚事务
            $this->rollback();
            return false;
        }

        // 提交事务
        $this->commit();

        return $result;
    }

    /**
     * 已弃用
     * 增加数值，可延迟更新
     * @param array $where 更新条件
     * @param string $field 更新字段
     * @param int $count 增加数量
     * @param int $time 延时
     * @return bool
     * @deprecated 已弃用 建议使用 saveData
     */
    public function _setInc($where = array(), $field = '', $count = 1, $time = 0)
    {
        //开启事务
        $this->startTrans();

        $result = $this->where($where)->setInc($field, $count, $time);
        if (false === $result) {
            Lg($this, __METHOD__, __LINE__);
            // 回滚事务
            $this->rollback();
            return false;
        }

        // 提交事务
        $this->commit();

        return $result;
    }

    /**
     * 已弃用
     * 减少数值，可延迟更新
     * @param array $where 更新条件
     * @param string $field 更新字段
     * @param int $count 减少数量
     * @param int $time 延时
     * @return bool
     * @deprecated 建议使用 saveData
     */
    public function _setDec($where = array(), $field = '', $count = 1, $time = 0)
    {
        //开启事务
        $this->startTrans();

        $result = $this->where($where)->setDec($field, $count, $time);
        if (false === $result) {
            Lg($this, __METHOD__, __LINE__);
            // 回滚事务
            $this->rollback();
            return false;
        }

        // 提交事务
        $this->commit();

        return $result;
    }

    /**
     * 查询一条记录
     * @param array $where 查询条件
     * @param string $field 查询字段
     * @return bool|mixed 返回结果
     * @deprecated 已弃用 建议使用 queryRow
     */
    public function _find($where = array(), $field = '*')
    {
        $result = $this->field($field)->where($where)->find();
        if (false === $result) {
            Lg($this, __METHOD__, __LINE__);
            return false;
        }

        return $result;
    }

    /**
     * 初始化查询设置.
     * @author: hjun
     * @created: 2017-07-02 17:31:17
     * @version: 1.0
     * @deprecated 已弃用
     */
    public function initListConfig()
    {
        $this->listConfig['alias'] = 'a';
        $this->listConfig['field'] = array();
        $this->listConfig['order'] = array();
        $this->listConfig['where'] = array();
        $this->listConfig['map'] = array();
        $this->listConfig['page'] = 1;
        $this->listConfig['limit'] = 0;
        $this->listConfig['join'] = array();
        $this->listConfig['callback'] = array();
        $this->listConfig['isCount'] = false;
        $this->listConfig['maxId'] = 0; // 滑动加载限制最大主键id
        $this->listConfig['group'] = '';
        $this->listConfig['distinct'] = true;
    }

    /**
     * 获取当前表列表 默认为 SELECT * FROM table.
     * @author: hjun
     * @created: 2017-06-30 22:28:20
     * @version: 1.0
     * @param array $config
     * @return array
     * @deprecated 已弃用 建议使用queryList函数
     */
    public function getList($config = array())
    {
        // 检查配置
        if (empty($config)) {
            $config = $this->listConfig;
        }

        // 统计结果数量
        $total = $this->alias($config['alias'])->where($config['where'])->join($config['join'])->count();
        $totalMap = $this->alias($config['alias'])->where(array_merge($config['where'], $config['map']))->join($config['join'])->count();
        $maxId = $this->max($this->pk);
        if ($config['isCount']) {
            if (false === $totalMap) return $this->getReturn(-1, $this->getError() . ' ' . $this->getDbError());
            return $this->getReturn(200, '成功', (int)$totalMap);
        }

        // 分页加载的限制条件
        if ((int)$config['maxId'] > 0) {
            $map[$config['alias'] . '.' . $this->pk] = array('elt', $config['maxId']);
            $config['where'] = array_merge($config['where'], $map);
        }

        // 查询列表
        $field = implode(',', $config['field']);
        $order = implode(',', $config['order']);
        $list = $this
            ->alias($config['alias'])
            ->distinct($config['distinct'])
            ->field($field)
            ->where(array_merge($config['where'], $config['map']))
            ->join($config['join'])
            ->limit(($config['page'] - 1) * $config['limit'], $config['limit'])
            ->order($order)
            ->group($config['group'])
            ->select();
        if (false === $list) return $this->getReturn(-1, $this->getError() . ' ' . $this->getDbError());
        $sql = $this->_sql();
        $resData = array();
        $resData['currentTotal'] = ($config['page'] - 1) * $config['limit'] + count($list); // 执行的sql语句
        // 自定义回调函数
        if (!empty($config['callback']['name'])) {
            $this->$config['callback']['name']($list, $config['callback']['param']);
        }

        $resData['total'] = (int)$total; // 总数
        $resData['totalMap'] = (int)$totalMap; // 包含查询条件的总数
        $resData['maxId'] = (int)$maxId; // 最大id 滑动加载用
        $resData['sql'] = $sql; // 执行的sql语句
        $resData['list'] = empty($list) ? array() : $list;
        return $this->getReturn(200, '成功', $resData);
    }

    /**
     *
     * 返回规范结构.
     * @author: hjun
     * @created: 2017-06-22 13:57:37
     * @version: 1.0
     * @param int $code 状态码
     * @param string $msg 信息
     * @param null $data 数据
     * @return array
     * @deprecated 已弃用 建议使用公共函数getReturn
     */
    public function getReturn($code = -1, $msg = '服务器忙', $data = null)
    {
        return array('code' => $code, 'msg' => $msg, 'data' => $data);
    }

    /**
     * 获取查询参数
     * @param string $name
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-26 17:01:56
     * Desc:
     * Update: 2017-10-26 17:01:57
     * Version: 1.0
     */
    public function getQueryOptions($name = '')
    {
        if (empty($name)) return $this->queryOptions;
        return $this->queryOptions[$name];
    }

    /**
     * 获取所有字段字符串(包括表别名)
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-26 17:01:59
     * Desc:
     * Update: 2017-10-26 17:02:00
     * Version: 1.0
     */
    public function getDbFieldsAddAlias()
    {
        $fields = parent::getDbFields();
        foreach ($fields as $key => $value) {
            $fields[$key] = empty($this->queryOptions['alias']) ?: $this->queryOptions['alias'] . '.' . $value;
        }
        return implode(',', $fields);
    }

    /**
     * 获取数量
     * @param array $options
     * @return mixed ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-28 12:31:44
     * Desc:
     * Update: 2017-10-28 12:31:45
     * Version: 1.0
     */
    public function getCount($options = [])
    {
        // 如果是获取数量 则直接返回数量 并且不是分组查询 才能使用count函数直接返回结果数量
        if (empty($options['group'])) {
            $result = $this->count();
        } else {
            // 只查主键 提高效率
            $pk = empty($options['alias']) ? $this->getPk() : $options['alias'] . '.' . $this->getPk();
            $this->field($pk);
            $result = $this->select();
        }
        if (false === $result) {
            $this->getFalseReturn();
            return 0;
        }
        return is_array($result) ? count($result) : (int)$result;
    }

    /**
     * 获取查询的SQL语句
     * @param array $options
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-28 02:50:01
     * Desc:
     * Update: 2017-10-28 02:50:02
     * Version: 1.0
     */
    public function getQuerySql($options = [])
    {
        return $this->exe($options)->select(false);
    }

    /**
     * SQL出错处理函数
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-28 01:48:44
     * Desc:
     * Update: 2017-10-28 01:48:45
     * Version: 1.0
     */
    public function getFalseReturn()
    {
        addExceptionLog("执行SQL语句出错,SQL:" . $this->_sql() . '错误:' . $this->getDbError() . $this->getError(), 0);
        return getReturn(CODE_ERROR);
    }

    /**
     * 解析分页参数 计算 获取limit连贯操作的两个参数
     * @param array $options \
     * - skip 跳过多少条记录
     * - take 取多少条记录
     * - page 页数
     * - limit 取多少条
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-28 11:20:58
     * Desc:
     * Update: 2017-10-28 11:20:59
     * Version: 1.0
     */
    public function parsePage($options = [])
    {
        $options = empty($options) ? $this->queryOptions : $options;
        // take和limit的值统一 如果take设置了就取take 没设置取limit -1表示取所有 mysql新版不支持 这里设置最大值
        if (isset($options['take']) && $options['take'] > 0) {
            $options['limit'] = $options['take'];
        } elseif (isset($options['limit']) && $options['limit'] > 0) {
            $options['take'] = $options['limit'];
        } elseif (
            (
                (isset($options['take']) && $options['take'] < 0) ||
                (isset($options['limit']) && $options['limit'] < 0)
            ) &&
            (isset($options['skip']) && $options['skip'] > 0)
        ) {
            $options['take'] = $options['limit'] = self::MAX_TAKE;
        } else {
            $options['take'] = 0;
            $options['limit'] = 0;
        }

        // skip 和 page
        if (isset($options['skip']) && isset($options['take']) && $options['skip'] > 0 && $options['take'] == 0) {
            $options['take'] = $options['limit'] = self::MAX_TAKE;
        } elseif (isset($options['page']) && isset($options['take']) && $options['page'] > 0 && $options['take'] == 0) {
            // 默认取20条
            $options['take'] = 0;
            $options['limit'] = 0;
            $options['skip'] = ($options['page'] - 1) * $options['take'];
        } elseif (isset($options['page']) && isset($options['take']) && $options['page'] > 0 && $options['take'] > 0) {
            $options['skip'] = ($options['page'] - 1) * $options['take'];
        } else {
            $options['skip'] = (isset($options['skip']) && $options['skip'] > 0) ? $options['skip'] : 0;
        }
        return $options;
    }

    /**
     * 如果用了union 需要重构order 和 union_table
     * @param array $options
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-28 11:25:51
     * Desc:
     * Update: 2017-10-28 11:25:52
     * Version: 1.0
     */
    public function parseUnionOrder($options = [])
    {
        $options = empty($options) ? $this->queryOptions : $options;
        // 如果union表了 并且用了order  要将order 放置在最后union的表后
        if (!empty($options['order']) && !empty($options['union']['table'])) {
            // 如果是数组
            if (is_array($options['union']['table'])) {
                $index = count($options['union']['table']) - 1;
                $options['union']['table'][$index] .= ' ORDER BY ' . $options['order'];
            } else {
                $options['union']['table'] .= ' ORDER BY ' . $options['order'];
            }
            $options['order'] = '';
        }
        return $options;
    }

    /**
     * 解析 最大ID
     * @param array $options
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-28 12:15:42
     * Desc:
     * Update: 2017-10-28 12:15:43
     * Version: 1.0
     */
    public function parseMaxId($options = [])
    {
        // 最大ID 滑动加载时用到
        $options['max_id'] = empty($options['max_id']) ? 0 : $options['max_id'];
        if ($options['max_id'] > 0) {
            $where = [];
            $where[empty($options['alias']) ? $this->getPk() : $options['alias'] . '.' . $this->getPk()] = ['elt', $options['max_id']];
            $options['where'] = array_merge($options['where'], $where);
        }
        return $options;
    }

    /**
     * 分析查询条件参数
     * @param array $options
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-28 01:50:15
     * Desc:
     * Update: 2017-10-28 01:50:16
     * Version: 1.0
     */
    public function parseOptions($options = [])
    {
        $options = empty($options) ? $this->queryOptions : $options;
        $options['table'] = empty($options['table']) ? '' : $options['table'];// 表名
        $options['alias'] = empty($options['alias']) ? '' : $options['alias'];// 别名
        $options['union']['table'] = empty($options['union']['table']) ? [] : $options['union']['table'];// union 表
        $options['union']['all'] = isset($options['union']['all']) && $options['union']['all'] === true;// unionALL ?
        $options['distinct'] = isset($options['distinct']) && $options['distinct'] === true;// 是否查询唯一
        $options['field'] = empty($options['field']) ? '*' : $options['field'];// 查询字段
        $options['where'] = empty($options['where']) ? [] : $options['where'];// 查询条件
        $options['join'] = empty($options['join']) ? [] : $options['join'];// 联表
        $options['group'] = empty($options['group']) ? '' : $options['group'];// 分组
        $options['having'] = empty($options['having']) ? '' : $options['having'];// having
        $options['order'] = empty($options['order']) ? '' : $options['order'];// 排序
        $options = $this->parsePage($options); // 分页
        $options = $this->parseUnionOrder($options); // union - order
        $options['lock'] = isset($options['lock']) && $options['lock'] === true;// 锁
        if (isset($options['cache'])) {
            $options['cache']['key'] = isset($options['cache']['key']) && $options['cache']['key'] === true ? true : (empty($options['cache']['key']) ? false : $options['cache']['key']);// 缓存
            $options['cache']['expire'] = empty($options['cache']['expire']) ? 0 : $options['cache']['expire'];

        }
        $options['comment'] = empty($options['comment']) ? '' : ($options['fetch_sql'] === true ? '' : $options['comment']);// 备注
        $options = $this->parseMaxId($options);
        $this->queryOptions = $options;
        return $options;
    }

    /**
     * 执行查询参数的连贯操作
     * @param array $options
     * where 用于查询或者更新条件的定义 字符串、数组和对象
     * table 用于定义要操作的数据表名称 字符串和数组
     * alias 用于给当前数据表定义别名 字符串
     * field 用于定义要查询的字段（支持字段排除） 字符串和数组
     * order 用于对结果排序 字符串和数组
     * group 用于对查询的group支持 字符串
     * having 用于对查询的having支持 字符串
     * join 用于对查询的join支持 字符串和数组
     * union 用于对查询的union支持 字符串、数组和对象
     * distinct 用于查询的distinct支持 布尔值
     * lock 用于数据库的锁机制 布尔值
     * cache 用于查询缓存 支持多个参数
     * relation 用于关联查询（需要关联模型支持） 字符串
     * result 用于返回数据转换 字符串
     * scope 用于命名范围 字符串、数组
     * bind 用于数据绑定操作 数组
     * comment 用于SQL注释 字符串
     * fetchSql 不执行SQL而只是返回SQL 布尔值
     * @return $this
     * User: hj
     * Date: 2017-10-28 02:45:32
     * Desc:
     * Update: 2017-10-28 02:45:33
     * Version: 1.0
     */
    public function exe($options = [])
    {
        // 支持的连贯操作
        $exeArr = [
            'where', 'table', 'alias', 'field', 'order', 'group', 'having', 'join', 'union',
            'distinct', 'lock', 'cache', 'relation', 'result', 'scope', 'bind', 'comment', 'fetchSql'
        ];
        // 解析查询参数
        $options = $this->parseOptions($options);
        // 执行查询
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'union':
                    if (!empty($value['table'])) $this->union($value['table'], $value['all']);
                    break;
                case 'cache':
                    if (!empty($value['cache'])) $this->cache($value['key'], $value['expire']);
                    break;
                case 'skip':
                case 'take':
                case 'page':
                case 'limit':
                    $this->limit($options['skip'], $options['take']);
                    break;
                default:
                    // 允许的操作执行
                    if (!empty($value) && in_array($key, $exeArr)) $this->$key($value);
                    break;
            }
        }
        return $this;
    }

    /**
     * 执行数据创建
     * @param array $options
     * @return $this
     * User: hjun
     * Date: 2018-01-08 22:43:26
     * Update: 2018-01-08 22:43:26
     * Version: 1.00
     */
    public function exeCreate($options = array())
    {
        $exeArr = array('field', 'validate', 'auto', 'token');
        foreach ($options as $key => $value) {
            if (!empty($value) && in_array($key, $exeArr)) $this->$key($value);
        }
        return $this;
    }

    /**
     * 执行更新的连贯操作
     * @param array $options
     * 支持的连贯操作
     * where 用于查询或者更新条件的定义 字符串、数组和对象
     * table 用于定义要操作的数据表名称 字符串和数组
     * alias 用于给当前数据表定义别名 字符串
     * field 用于定义允许更新的字段 字符串和数组
     * order 用于对数据排序 字符串和数组
     * lock 用于数据库的锁机制 布尔值
     * relation 用于关联更新（需要关联模型支持） 字符串
     * scope 用于命名范围 字符串、数组
     * bind 用于数据绑定操作 数组
     * comment 用于SQL注释 字符串
     * fetchSql 不执行SQL而只是返回SQL 布尔值
     * @return $this
     * User: hj
     * Desc:
     * Date: 2017-11-02 16:31:05
     * Update: 2017-11-02 16:31:06
     * Version: 1.0
     */
    public function exeSave($options = [])
    {
        $exeArr = ['where', 'table', 'alias', 'field', 'order', 'lock', 'relation', 'scope', 'bind', 'comment', 'fetchSql'];
        foreach ($options as $key => $value) {
            if (!empty($value) && in_array($key, $exeArr)) $this->$key($value);
        }
        return $this;
    }

    /**
     * 执行写入的连贯操作
     * @param array $options
     * table 用于定义要操作的数据表名称 字符串和数组
     * data 用于指定要写入的数据对象 数组和对象
     * field 用于定义要写入的字段 字符串和数组
     * relation 用于关联查询（需要关联模型支持） 字符串
     * validate 用于数据自动验证 数组
     * auto 用于数据自动完成 数组
     * filter 用于数据过滤 字符串
     * scope 用于命名范围 字符串、数组
     * bind 用于数据绑定操作 数组
     * token 用于令牌验证 布尔值
     * comment 用于SQL注释 字符串
     * fetchSql 不执行SQL而只是返回SQL 布尔值
     * @return $this
     * User: hj
     * Desc:
     * Date: 2017-11-02 16:32:01
     * Update: 2017-11-02 16:32:04
     * Version: 1.0
     */
    public function exeAdd($options = [])
    {
        $exeArr = ['table', 'data', 'field', 'relation', 'validate', 'auto', 'filter', 'scope', 'bind', 'token', 'comment', 'fetchSql'];
        foreach ($options as $key => $value) {
            if (!empty($value) && in_array($key, $exeArr)) $this->$key($value);
        }
        return $this;
    }

    /**
     * 执行删除的连贯操作
     * @param array $options
     * where 用于查询或者更新条件的定义 字符串、数组和对象
     * table 用于定义要操作的数据表名称 字符串和数组
     * alias 用于给当前数据表定义别名 字符串
     * order 用于对数据排序 字符串和数组
     * lock 用于数据库的锁机制 布尔值
     * relation 用于关联删除（需要关联模型支持） 字符串
     * scope 用于命名范围 字符串、数组
     * bind 用于数据绑定操作 数组
     * comment 用于SQL注释 字符串
     * fetchSql 不执行SQL而只是返回SQL 布尔值
     * @return $this
     * User: hj
     * Desc:
     * Date: 2017-11-02 17:10:00
     * Update: 2017-11-02 17:10:01
     * Version: 1.0
     */
    public function exeDel($options = [])
    {
        $exeArr = ['where', 'table', 'alias', 'order', 'lock', 'relation', 'scope', 'bind', 'comment', 'fetchSql'];
        foreach ($options as $key => $value) {
            if (!empty($value) && in_array($key, $exeArr)) $this->$key($value);
        }
        return $this;
    }

    /**
     * 查询列表
     * @param array $options 查询参数
     * - table [] OR string 例子：'think_user user,think_role role', ['think_user'=>'user','think_role'=>'role']
     * - alias string 表名别名
     * - union_table [] OR string 联合的完整表名
     * - union_all bool 是否union all
     * - distinct bool 是否返回唯一
     * - field [] OR string 过滤字段 例子：array('id','concat(name,'-',id)'=>'truename','LEFT(title,7)'=>'sub_title') 'id,nickname as name'
     * - where [] 查询条件
     * - join [] OR string 链表 例子：['think_a a ON a.id = b.id']
     * - group string 分组查询
     * - having string 分组筛选
     * - order [] OR string 排序 例子：['order','id'=>'desc'] 'order,id DESC
     * - skip int
     * - take int
     * - page int
     * - limit int
     * - lock bool
     * - cache_key bool OR string 缓存键值或者true
     * - cache_expire int 缓存有效期
     * - comment string sql注释
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-28 04:04:49
     * Desc:
     * Update: 2017-10-28 04:04:50
     * Version: 1.0
     */
    public function queryList($options = [])
    {
        // 查询结果
        try {
            $list = $this->selectList($options);
            // 如果上一次查询时进行了分页 则总数需要重新查一下 否则总数就是上一次结果的总数
            $doPage = $this->getLastQueryNeedPage();
            if ($doPage) {
                $total = $this->exeQuery($options)->getCount($options);
            } else {
                $total = $this->getLastQueryTotal();
            }
            $result = [];
            $result['list'] = $list;
            $result['total'] = $total;
            $result['currentTotal'] = count($list);// 查询结束后是否要清空查询参数 ?
            return getReturn(CODE_SUCCESS, 'success', $result);
            $list = $this->exe($options)->select();
            if (false === $list) return $this->getFalseReturn();
            $options = $this->queryOptions;// 如果是分页 要计算总数
            if ($options['take'] > 0 && $options['take'] < self::MAX_TAKE) {
                $total = $this->queryTotal($options);
                if ($total['code'] === -1) return $total;
            }
            $result = [];
            $result['list'] = $list;
            $result['total'] = empty($total) ? count($list) : $total;
            $result['currentTotal'] = count($list);// 查询结束后是否要清空查询参数 ?
            // $this->queryOptions = [];
            return getReturn(200, '', $result);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            $result = ['list' => [], 'total' => 0, 'currentTotal' => 0];
            return getReturn(CODE_ERROR, '', $result);
        }
    }

    /**
     * 查询一行
     * @param array $options
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-29 13:26:50
     * Desc:
     * Update: 2017-10-29 13:26:51
     * Version: 1.0
     */
    public function queryRow($options = [])
    {
        // 查询结果
        try {
            $info = $this->selectRow($options);
            return getReturn(CODE_SUCCESS, 'success', $info);
            $info = $this->exe($options)->find();
            if (false === $info) return $this->getFalseReturn();
            return getReturn(200, '', $info);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return getReturn(CODE_ERROR);
        }
    }

    /**
     * 查询数量
     * @param array $options
     * @return int
     * User: hj
     * Date: 2017-10-28 02:58:06
     * Desc:
     * Update: 2017-10-28 02:58:08
     * Version: 1.0
     */
    public function queryCount($options = [])
    {
        try {
            $this->exe($options);
            return $this->getCount($options);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return 0;
        }
    }

    /**
     * 获取总数
     * @param array $options
     * @return int
     * User: hj
     * Date: 2017-10-28 02:59:09
     * Desc:
     * Update: 2017-10-28 02:59:10
     * Version: 1.0
     */
    public function queryTotal($options = [])
    {
        try {
            $options['skip'] = $options['take'] = $options['page'] = $options['limit'] = 0;
            return $this->queryCount($options);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return 0;
        }
    }

    /**
     * 查询字段
     * @param array $options
     * @param string $field
     * @param mixed $spe
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-30 16:01:59
     * Desc:
     * Update: 2017-10-30 16:02:00
     * Version: 1.0
     */
    public function queryField($options = [], $field = '', $spe = null)
    {
        try {
            $result = $this->exe($options)->getField($field, $spe);
            if (false === $result) return $this->getFalseReturn();
            return getReturn(200, '', $result);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return getReturn(CODE_ERROR);
        }
    }

    /**
     * 查询最大值
     * @param array $options
     * @param string $field
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-30 16:07:07
     * Desc:
     * Update: 2017-10-30 16:07:08
     * Version: 1.0
     */
    public function queryMax($options = [], $field = '')
    {
        try {
            $result = $this->exe($options)->max($field);
            if (false === $result) return $this->getFalseReturn();
            return getReturn(200, '', $result);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return getReturn(CODE_ERROR);
        }
    }

    /**
     * 查询最小值
     * @param array $options
     * @param string $field
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-30 16:07:07
     * Desc:
     * Update: 2017-10-30 16:07:08
     * Version: 1.0
     */

    public function queryMin($options = [], $field = '')
    {
        try {
            $result = $this->exe($options)->min($field);
            if (false === $result) return $this->getFalseReturn();
            return getReturn(200, '', $result);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return getReturn(CODE_ERROR);
        }
    }

    /**
     * 查询平均值
     * @param array $options
     * @param string $field
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-30 16:07:07
     * Desc:
     * Update: 2017-10-30 16:07:08
     * Version: 1.0
     */
    public function queryAvg($options = [], $field = '')
    {
        try {
            $result = $this->exe($options)->avg($field);
            if (false === $result) return $this->getFalseReturn();
            return getReturn(200, '', $result);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return getReturn(CODE_ERROR);
        }
    }

    /**
     * 查询总和
     * @param array $options
     * @param string $field
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-30 16:07:07
     * Desc:
     * Update: 2017-10-30 16:07:08
     * Version: 1.0
     */
    public function querySum($options = [], $field = '')
    {
        try {
            $result = $this->exe($options)->sum($field);
            if (false === $result) return $this->getFalseReturn();
            return getReturn(200, '', $result);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return getReturn(CODE_ERROR);
        }
    }

    /**
     * 新增数据
     * @param array $options
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc:
     * Date: 2017-11-02 16:06:52
     * Update: 2017-11-02 16:06:53
     * Version: 1.0
     */
    public function addData($options = [], $data = [])
    {
        try {
            $data = $this->exeCreate($options)->create($data, 1);
            if (false === $data) return getReturn(-1, $this->getError());
            $result = $this->exeAdd($options)->add($data);
            if (false === $result) return $this->getFalseReturn();
            return getReturn(200, '', $result);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return getReturn(CODE_ERROR);
        }
    }

    /**
     * 批量增加数据
     * @param array $options
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc:
     * Date: 2017-11-02 22:04:21
     * Update: 2017-11-02 22:04:23
     * Version: 1.0
     */
    public function addAllData($options = [], $data = [])
    {
        try {
            $result = $this->exeAdd($options)->addAll($data);
            if (false === $result) return $this->getFalseReturn();
            return getReturn(200, '', $result);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return getReturn(CODE_ERROR);
        }
    }

    /**
     * 保存信息
     * @param array $options
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc:
     * Date:2017年11月2日 16:05:28
     * Update: 2017-11-02 16:05:29
     * Version: 1.0
     */
    public function saveData($options = [], $data = [])
    {
        try {
            $data = $this->exeCreate($options)->create($data, 2);
            if (false === $data) return getReturn(-1, $this->getError());
            $result = $this->exeSave($options)->save($data);
            if (false === $result) return $this->getFalseReturn();
            return getReturn(200, '', $data);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return getReturn(CODE_ERROR);
        }
    }

    /**
     * 批量更新
     * @param array $options
     * @param array $dataSet
     * @return mixed
     * User: hj
     * Desc:
     * Date: 2017-11-20 00:52:42
     * Update: 2017-11-20 00:52:42
     * Version: 1.0
     */
    public function saveAllData($options = [], $dataSet = [])
    {

        try {
            $results = [];
            $this->startTrans();
            foreach ($dataSet as $key => $data) {
                // 如果有设置则用options里面的where 否则使用主键更新
                if (empty($options[$key]['where']) && (!isset($data[$this->pk]) || $data[$this->pk] <= 0)) {
                    $this->rollback();
                    return getReturn(-1, "数据{$key}缺少更新条件");
                }
                $where = [];
                $where[$this->pk] = $data[$this->pk];
                $option = [];
                $option['where'] = $where;
                $option = array_merge($option, $options[$key]);
                $result = $this->saveData($option, $data);
                if ($result['code'] !== 200) {
                    $this->rollback();
                    return $result;
                }
                if (empty($data[$this->pk])) {
                    $results[] = array_merge($result['data'], $option['where']);
                } else {
                    $results[$data[$this->pk]] = $result['data'];
                }
            }
            $this->commit();
            return getReturn(200, '', $results);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return getReturn(CODE_ERROR);
        }
    }

    /**
     * 删除数据
     * @param array $options
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc:
     * Date: 2017-11-02 22:16:06
     * Update: 2017-11-02 22:16:07
     * Version: 1.0
     */
    public function delData($options = [])
    {
        try {
            $result = $this->exeDel($options)->delete();
            if (false === $result) return $this->getFalseReturn();
            return getReturn(200, '', $result);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog(__FUNCTION__ . "异常:{$msg}", 0);
            return getReturn(CODE_ERROR);
        }
    }

    /**
     * 处理数据
     * @param array $info
     * @param array $condition
     *  empty_field => [
     *      'filed_name' => 'empty_value'
     *  ]
     *
     *  time_field => [
     *      'field_name' => ['format', 'key_name']
     *  ]
     *
     *  map_field => [
     *      'field_name' => [
     *          'value' => 'map_value'
     *      ]
     *  ]
     *
     *  json_field => [
     *      'field_name', 'field_name'
     *  ]
     *
     *  callback_field => [
     *      'field_name' => ['map_filed', 'callback_name', [other_param]]
     *  ]
     * @return array
     * User: hjun
     * Date: 2018-01-11 11:01:04
     * Update: 2018-01-11 11:01:04
     * Version: 1.00
     */
    public function transformInfo($info = array(), $condition = array())
    {
        if (empty($info)) return array();
        // 处理空数据
        if (isset($condition['empty_field']) && !empty($condition['empty_field'])) {
            foreach ($condition['empty_field'] as $key => $value) {
                if (array_key_exists($key, $info) && empty($info[$key])) {
                    $info[$key] = $value;
                }
            }
        }

        // 处理映射数据
        if (isset($condition['map_field']) && !empty($condition['map_field'])) {
            foreach ($condition['map_field'] as $key => $value) {
                if (array_key_exists($key, $info) && !empty($value)) {
                    $info["{$key}_name"] = empty($value[$info[$key]]) ? '' : $value[$info[$key]];
                }
            }
        }

        // 转换时间数据
        if (isset($condition['time_field']) && !empty($condition['time_field'])) {
            foreach ($condition['time_field'] as $key => $value) {
                if (array_key_exists($key, $info)) {
                    $format = isset($value[0]) && !empty($value[0]) ? $value[0] : 'Y-m-d H:i:s';
                    $newKey = isset($value[1]) ? $value[1] : "{$key}_string";
                    $info[$newKey] = date($format, $info[$key]);
                }
            }
        }

        // 转换JSON格式数据
        if (isset($condition['json_field']) && !empty($condition['json_field'])) {
            foreach ($condition['json_field'] as $key => $value) {
                if (array_key_exists($value, $info) && is_string($info[$value])) {
                    $arr = json_decode($info[$value], 1);
                    $info[$value] = empty($arr) ? array() : $arr;
                }
            }
        }

        // 类中的回调函数 或者 公共函数
        if (isset($condition['callback_field']) && !empty($condition['callback_field'])) {
            foreach ($condition['callback_field'] as $key => $value) {
                if (array_key_exists($key, $info) && method_exists($this, $value[1])) {
                    $info[$value[0]] = $this->$value[1]($info[$key]);
                } elseif (array_key_exists($key, $info) && function_exists($value[1])) {
                    $info[$value[0]] = $value[1]($info[$key]);
                }
            }
        }
        return $info;
    }

    /**
     * 处理新增/编辑时传递的数据
     * @param array $data
     * @param array $condition
     * @return array
     * User: hjun
     * Date: 2018-01-13 17:49:45
     * Update: 2018-01-13 17:49:45
     * Version: 1.00
     */
    public function transformData(&$data = array(), $condition = array())
    {
        // 处理空数据
        if (isset($condition['empty_field']) && !empty($condition['empty_field'])) {
            foreach ($condition['empty_field'] as $key => $value) {
                if (array_key_exists($key, $data) && empty($data[$key])) {
                    $data[$key] = empty($value) ? '' : $value;
                }
            }
        }

        return getReturn(200);
    }

    /**
     * 从列表中根据分页获取部分列表
     * @param int $page
     * @param int $limit
     * @param array $list
     * @param mixed $offset
     * @param mixed $length
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-04-02 04:01:25
     * Update: 2018-04-02 04:01:25
     * Version: 1.00
     */
    public function getListByPage($page = 1, $limit = 20, $list = [], $offset = null, $length = null)
    {
        // 有分页才进行分页截取
        if (isset($offset) && isset($length)) {
            $offset = $offset < 0 ? 0 : $offset;
            $length = $length <= 0 ? null : $length;
            $list = array_slice($list, $offset, $length);
        } else {
            if ($page > 1 || $limit > 0) {
                $page = $page > 0 ? $page : 1;
                $limit = $limit > 0 ? $limit : 20;
                $offset = ($page - 1) * $limit;
                $length = $limit;
                $list = empty($list) ? [] : $list;
                $list = array_slice($list, $offset, $length);
            }
        }
        return empty($list) ? [] : $list;
    }

    /**
     * 获取表的所有字段
     * @param string $alias 别名
     * @return mixed
     * User: hjun
     * Date: 2018-05-22 01:28:16
     * Update: 2018-05-22 01:28:16
     * Version: 1.00
     */
    public function getDbFields($alias = '')
    {
        $fields = parent::getDbFields();
        if (false === $fields) return false;
        if (!empty($alias)) {
            foreach ($fields as &$field) {
                $field = "{$alias}.{$field}";
            }
        }
        return $fields;
    }

    /**
     * 获取列表
     * @param array $options
     * @return array
     * User: hjun
     * Date: 2018-05-20 02:48:35
     * Update: 2018-05-20 02:48:35
     * Version: 1.00
     */
    public function selectList($options = [])
    {
        $this->dbCache->setModel($this);
        return $this->dbCache->select($options);
    }

    /**
     * 获取信息
     * @param array $options
     * @return array
     * User: hjun
     * Date: 2018-05-20 02:48:47
     * Update: 2018-05-20 02:48:47
     * Version: 1.00
     */
    public function selectRow($options = [])
    {
        $this->dbCache->setModel($this);
        return $this->dbCache->find($options);
    }


    /**
     *
     * @param $data
     * @param $options
     * User: hjun
     * Date: 2018-05-24 17:43:01
     * Update: 2018-05-24 17:43:01
     * Version: 1.00
     */
    public function _after_insert($data, $options)
    {
        $this->dbCache->setModel($this);
        $this->dbCache->afterInsert($data, $options);
        parent::_after_insert($data, $options);
    }

    /**
     *
     * @param $data
     * @param $options
     * User: hjun
     * Date: 2018-05-24 17:43:05
     * Update: 2018-05-24 17:43:05
     * Version: 1.00
     */
    public function _after_update($data, $options)
    {
        $this->dbCache->setModel($this);
        $this->dbCache->afterUpdate($data, $options);
        parent::_after_update($data, $options);
    }

    /**
     *
     * @param $data
     * @param $options
     * User: hjun
     * Date: 2018-05-24 17:43:09
     * Update: 2018-05-24 17:43:09
     * Version: 1.00
     */
    public function _after_delete($data, $options)
    {
        $this->dbCache->setModel($this);
        $this->dbCache->afterDelete($data, $options);
        parent::_after_delete($data, $options);
    }

    /**
     * 从请求参数中获取数据对象
     * @param array $fields 需要的字段值
     * @param array $request 请求参数
     * @param array $validate 自动验证规则
     * @param array $auto 自动完成规则
     * @param int $type
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-04-28 09:41:58
     * Update: 2018-04-28 09:41:58
     * Version: 1.00
     */
    public function getAndValidateDataFromRequest($fields = [], $request = [], $validate = [], $auto = [], $type = self::MODEL_BOTH)
    {
        // 先手动进行自动验证 和 自动完成, 防止两者冲突,  验证前先清空错误信息
        $this->error = '';
        if (!$this->validate($validate)->autoValidation($request, $type)) {
            return getReturn(CODE_ERROR, $this->getError());
        }
        $this->auto($auto)->autoOperation($request, $type);

        if (!empty($fields)) {
            if (is_array($fields)) {
                $fields = implode(',', $fields);
            }
            $this->field($fields);
        }
        $data = $this->create($request, $type);
        if (false === $data) {
            return getReturn(CODE_ERROR, $this->getError(), $request);
        }
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $data[$key] = '';
            }
        }
        return getReturn(CODE_SUCCESS, 'success', $data);
    }

    /**
     * 自动表单处理
     * @access public
     * @param array $data 创建数据
     * @param string $type 创建类型
     * @return mixed
     */
    public function autoOperation(&$data, $type)
    {
        if (!empty($this->options['auto'])) {
            $_auto = $this->options['auto'];
            unset($this->options['auto']);
        } elseif (!empty($this->_auto)) {
            $_auto = $this->_auto;
        }
        // 自动填充
        if (isset($_auto)) {
            foreach ($_auto as $auto) {
                // 填充因子定义格式
                // array('field','填充内容','填充条件','附加规则',[额外参数])
                if (empty($auto[2])) $auto[2] = self::MODEL_INSERT; // 默认为新增的时候自动填充
                if ($type == $auto[2] || $auto[2] == self::MODEL_BOTH) {
                    if (empty($auto[3])) $auto[3] = 'string';
                    switch (trim($auto[3])) {
                        case 'function':    //  使用函数进行填充 字段的值作为参数
                        case 'callback': // 使用回调方法
                            $args = isset($auto[4]) ? (array)$auto[4] : array();
                            if (isset($data[$auto[0]])) {
                                // 自动完成时我希望参数由自己定义,所以注释了这里,否则会自动传入数组中存在的数据
                                // array_unshift($args,$data[$auto[0]]);
                            }
                            if ('function' == $auto[3]) {
                                $data[$auto[0]] = call_user_func_array($auto[1], $args);
                            } else {
                                $data[$auto[0]] = call_user_func_array(array(&$this, $auto[1]), $args);
                            }
                            break;
                        case 'field':    // 用其它字段的值进行填充
                            $data[$auto[0]] = $data[$auto[1]];
                            break;
                        case 'ignore': // 为空忽略
                            if ($auto[1] === $data[$auto[0]])
                                unset($data[$auto[0]]);
                            break;
                        case 'string':
                        default: // 默认作为字符串填充
                            $data[$auto[0]] = $auto[1];
                    }
                    if (isset($data[$auto[0]]) && false === $data[$auto[0]]) unset($data[$auto[0]]);
                }
            }
        }
        return $data;
    }

    /**
     * 执行查询参数的连贯操作
     * @param array $options
     * where 用于查询或者更新条件的定义 字符串、数组和对象
     * table 用于定义要操作的数据表名称 字符串和数组
     * alias 用于给当前数据表定义别名 字符串
     * field 用于定义要查询的字段（支持字段排除） 字符串和数组
     * order 用于对结果排序 字符串和数组
     * group 用于对查询的group支持 字符串
     * having 用于对查询的having支持 字符串
     * join 用于对查询的join支持 字符串和数组
     * union 用于对查询的union支持 字符串、数组和对象
     * distinct 用于查询的distinct支持 布尔值
     * lock 用于数据库的锁机制 布尔值
     * cache 用于查询缓存 支持多个参数
     * relation 用于关联查询（需要关联模型支持） 字符串
     * result 用于返回数据转换 字符串
     * scope 用于命名范围 字符串、数组
     * bind 用于数据绑定操作 数组
     * comment 用于SQL注释 字符串
     * fetchSql 不执行SQL而只是返回SQL 布尔值
     * @return $this
     * User: hj
     * Date: 2017-10-28 02:45:32
     * Desc:
     * Update: 2017-10-28 02:45:33
     * Version: 1.0
     */
    public function exeQuery($options = [])
    {
        // 支持的连贯操作
        $exeArr = [
            'where', 'table', 'alias', 'field', 'order', 'group', 'having', 'join', 'distinct',
            'lock', 'cache', 'relation', 'result', 'scope', 'bind', 'comment', 'fetchSql'
        ];
        // 执行查询
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'cache':
                    if (!empty($value)) call_user_func_array(array(&$this, 'cache'), $value);
                    break;
                default:
                    // 允许的操作执行
                    if (!empty($value) && in_array($key, $exeArr)) $this->$key($value);
                    break;
            }
        }
        return $this;
    }
}