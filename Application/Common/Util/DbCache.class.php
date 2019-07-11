<?php

namespace Common\Util;

use Common\Model\BaseModel;
use Think\Cache\Driver\File;
use Think\Exception;

/**
 * 数据库缓存操作类
 * Class DbCache
 * @package Common\Util
 * User: hjun
 * Date: 2018-06-01 01:10:17
 * Update: 2018-06-01 01:10:17
 * Version: 1.00
 */
class DbCache extends File
{
    const INSERT_ACTION = 0;
    const UPDATE_ACTION = 1;
    const DELETE_ACTION = 2;
    const STORE_TABLE = 'xunxin_store';

    /**
     * 操作的模型类
     * @var BaseModel
     */
    private $model;

    // 允许操作的方法
    private $allowMethods = ['select', 'find'];

    // 表名和缓存键值的关联表
    private $tableCacheMapKey = 'tableCacheMap';
    private $tableCacheMap = [];

    /**
     * 操作表名和缓存键值的工具类
     * @var ArraySet
     */
    private $arrSet;

    /**
     * @var CacheLock
     */
    private $lock;

    /**
     * @var $this
     */
    private static $_instance;

    /**
     * 不需要缓存的表
     * @var array
     */
    private $noCacheTable = [];

    /**
     * 最大缓存文件数量 达到最大值做一次清理
     * @var int
     */
    private $needClearNum = 5000;

    private $cacheOn; // 是否开启缓存
    private $tableCacheMapType; // 0-数据库 1-数组
    private $tableCacheName = 'mb_db_cache';

    private $lastQueryNeedPage = false; // 上一次查询是否用了分页
    private $lastQueryTotal = 0; // 上一次查询的总数量

    /**
     * 单例模式
     * @param string $type
     * @param array $options
     * @return $this
     * User: hjun
     * Date: 2018-06-07 01:34:29
     * Update: 2018-06-07 01:34:29
     * Version: 1.00
     */
    public static function getInstance($type = '', $options = array())
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self($options);
        }
        return self::$_instance;
    }

    /**
     * 构造函数
     * DbCache constructor.
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
        // 默认关闭缓存机制
        $this->cacheOn = C('DB_CACHE_ON') ? C('DB_CACHE_ON') : false;
        $this->tableCacheMapType = C('DB_CACHE_MAP_TYPE') ? C('DB_CACHE_MAP_TYPE') : 0;
        if (!$this->isDbType()) {
            $this->lock = new CacheLock($this->tableCacheMapKey);
        }
    }

    /**
     * @return array
     */
    public function getNoCacheTable()
    {
        return $this->noCacheTable;
    }

    /**
     * @param array $noCacheTable
     */
    public function setNoCacheTable($noCacheTable)
    {
        $this->noCacheTable = $noCacheTable;
    }

    /**
     * 判断是否是不需要缓存的表
     * @param $tableName
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-07-26 16:10:25
     * Update: 2018-07-26 16:10:25
     * Version: 1.00
     */
    public function isNoCacheTable($tableName)
    {
        return in_array($tableName, $this->noCacheTable);
    }

    public function start()
    {
        $this->lock->lock();
        $this->tableCacheMap = $this->get($this->tableCacheMapKey) ?: [];
        $this->arrSet = new ArraySet($this->tableCacheMap);
    }

    public function finish()
    {
        return $this->lock->unlock();
    }

    /**
     * 设置Model
     * @param $model
     * @return void
     * User: hjun
     * Date: 2018-06-07 01:34:58
     * Update: 2018-06-07 01:34:58
     * Version: 1.00
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * @param array $allowMethods
     */
    public function setAllowMethods($allowMethods)
    {
        $this->allowMethods = $allowMethods;
    }

    /**
     * @param string $tableCacheMapKey
     */
    public function setTableCacheMapKey($tableCacheMapKey)
    {
        $this->tableCacheMapKey = $tableCacheMapKey;
    }

    /**
     * @param array $tableCacheMap
     */
    public function setTableCacheMap($tableCacheMap)
    {
        $this->tableCacheMap = $tableCacheMap;
    }

    /**
     * @param ArraySet $arrSet
     */
    public function setArrSet($arrSet)
    {
        $this->arrSet = $arrSet;
    }

    /**
     * @param CacheLock $lock
     */
    public function setLock($lock)
    {
        $this->lock = $lock;
    }

    /**
     * @param int $needClearNum
     */
    public function setNeedClearNum($needClearNum)
    {
        $this->needClearNum = $needClearNum;
    }

    /**
     * @param int|mixed $tableCacheMapType
     */
    public function setTableCacheMapType($tableCacheMapType)
    {
        $this->tableCacheMapType = $tableCacheMapType;
    }

    /**
     * @param string $tableCacheName
     */
    public function setTableCacheName($tableCacheName)
    {
        $this->tableCacheName = $tableCacheName;
    }

    /**
     * @return bool
     */
    public function getLastQueryNeedPage()
    {
        return $this->lastQueryNeedPage;
    }

    /**
     * @param bool $lastQueryNeedPage
     */
    public function setLastQueryNeedPage($lastQueryNeedPage)
    {
        $this->lastQueryNeedPage = $lastQueryNeedPage;
    }

    /**
     * @return int
     */
    public function getLastQueryTotal()
    {
        return $this->lastQueryTotal;
    }

    /**
     * @param int $lastQueryTotal
     */
    public function setLastQueryTotal($lastQueryTotal)
    {
        $this->lastQueryTotal = $lastQueryTotal;
    }

    public function set($name, $value, $expire = null)
    {
        return parent::set($name, $value, $expire);
    }

    public function get($name)
    {
        return parent::get($name);
    }

    public function rm($name)
    {
        return parent::rm($name);
    }

    public function clear()
    {
        if ($this->isDbType()) {
            M($this->tableCacheName)->where('1')->delete();
        }
        return parent::clear();
    }

    /**
     * 判断缓存是否无效
     * @param $result
     * @return boolean
     * User: hjun
     * Date: 2018-06-01 00:38:26
     * Update: 2018-06-01 00:38:26
     * Version: 1.00
     */
    public function isInvalid($result)
    {
        return false === $result;
    }

    /**
     * 如果是数据库类型
     * @return bool
     * User: hjun
     * Date: 2018-08-11 00:30:56
     * Update: 2018-08-11 00:30:56
     * Version: 1.00
     */
    public function isDbType()
    {
        return $this->tableCacheMapType == 0;
    }

    /**
     * 获取缓存键值
     * @param string $sql
     * @return string
     * User: hjun
     * Date: 2018-06-01 00:38:49
     * Update: 2018-06-01 00:38:49
     * Version: 1.00
     */
    public function buildKey($sql = '')
    {
        return md5($sql);
    }

    /**
     * 判断是否需要分页
     * @param array $options
     * @param $method
     * @param $sql
     * @return bool
     * User: hjun
     * Date: 2018-08-11 00:31:14
     * Update: 2018-08-11 00:31:14
     * Version: 1.00
     */
    public function needPage(&$options = [], $method, $sql)
    {
        $hasPage = $options['skip'] > 0 || $options['take'] > 0;
        $noCache = (!$this->cacheOn || $options['cache'] === false) && $hasPage;
        if ($noCache) {
            return true;
        }
        // 如果查询条件有包含时间戳 基本上都是范围查询（用于查询未过期或者过期一类的数据），这类查询做缓存没意义
        if (strpos($sql, NOW_TIME . '') !== false) {
            $options['cache'] = false;
            return $hasPage;
        }
        // 如果没有分页就不需要
        $bool1 = $method === 'select' && $hasPage;
        if (!$bool1) {
            return false;
        }
        SQL::setSql($sql);
        $tables = SQL::selectTableNames();
        $needPageTable = ['xunxin_goods', 'xunxin_store', 'xunxin_mb_order'];
        $intersect = array_intersect($tables, $needPageTable);
        return !empty($intersect);
    }

    /**
     * 执行查询
     * 注意：
     *  对于select除了设置了强制分页（见needPage方法里的表名数组）
     *  其余的查询都是将所有结果缓存起来,使用函数array_slice进行分页
     * @param array $options
     * @param string $method
     * @return mixed
     * User: hjun
     * Date: 2018-06-01 01:05:01
     * Update: 2018-06-01 01:05:01
     * Version: 1.00
     */
    public function query(&$options = [], $method = 'select')
    {
        // 判断操作是否允许
        if (!in_array($method, $this->allowMethods)) {
            return [];
        }

        // 获取缓存数据 model中的options不需要再手动清空了
        $sql = $this->model->exeQuery($options)->fetchSql(true)->$method();
        $options = $this->model->parsePage($options);
        // 如果强制要分页 则结果是分页的结果
        $needPage = $this->needPage($options, $method, $sql);
        if ($needPage) {
            $this->model->exeQuery($options);
            $sql = $this->model->limit($options['skip'], $options['take'])->fetchSql(true)->$method();
        }
        $key = $this->buildKey($sql);
        $result = $this->get($key);

        // 缓存无效则查询数据库
        if ($this->isInvalid($result)) {
            $this->model->exeQuery($options);
            // 如果强制分页要分页查询
            if ($needPage) {
                $this->model->limit($options['skip'], $options['take']);
            }
            $result = $this->model->$method();
            if (is_null($result)) $result = [];
            $this->afterQuery($options, $result);
        }

        $this->setLastQueryNeedPage($needPage);
        $this->setLastQueryTotal(count($result));

        return $result;
    }

    /**
     * 查询数据集
     * @param array $options
     * @return array
     * User: hjun
     * Date: 2018-06-01 00:38:59
     * Update: 2018-06-01 00:38:59
     * Version: 1.00
     */
    public function select($options = [])
    {
        $result = $this->query($options, 'select');
        // 进行了分页直接返回结果
        return $this->getLastQueryNeedPage() ?
            $result :
            $this->model->getListByPage(0, 0, $result, $options['skip'], $options['take']);
    }

    /**
     * 查询数据
     * @param array $options
     * @return array
     * User: hjun
     * Date: 2018-06-01 00:39:16
     * Update: 2018-06-01 00:39:16
     * Version: 1.00
     */
    public function find($options = [])
    {
        return $this->query($options, 'find');
    }

    /**
     * 保存表名和缓存键值的关联表
     * @return boolean
     * User: hjun
     * Date: 2018-06-01 15:28:19
     * Update: 2018-06-01 15:28:19
     * Version: 1.00
     */
    public function saveTableCacheMap()
    {
        $this->tableCacheMap = $this->arrSet->toArray();
        return $this->set($this->tableCacheMapKey, $this->tableCacheMap);
    }

    /**
     * 执行查询SQL语句后 保存表名和缓存键值
     * @param array $options 查询条件
     * @param array $result 结果
     * @return mixed
     * User: hjun
     * Date: 2018-06-01 14:50:47
     * Update: 2018-06-01 14:50:47
     * Version: 1.00
     */
    public function afterQuery($options = [], $result = [])
    {
        if (!$this->cacheOn || $options['cache'] === false){
            return true;
        }

        $sql = $this->model->_sql();
        $key = $this->buildKey($sql);
        SQL::setSql($sql);
        $tables = SQL::selectTableNames();

        // 无需做下面的操作
        if (count($tables) === 1 && $this->isNoCacheTable($tables[0])) {
            return true;
        }

        $this->set($key, $result);

        $set = [];
        $item['cache'] = $key;
        foreach ($tables as $table) {
            $item['table'] = $table;
            $set[] = $item;
        }
        if (empty($set)) {
            return true;
        }

        return $this->saveQueryCacheMap($set);
    }

    /**
     * 数据库数据发生改变 要清除缓存
     * @param int $action
     * @param array $data
     * @param array $options
     * @return mixed
     * User: hjun
     * Date: 2018-06-01 15:25:05
     * Update: 2018-06-01 15:25:05
     * Version: 1.00
     */
    public function afterChange($action = self::INSERT_ACTION, $data, $options)
    {
        $sql = $this->model->_sql();
        SQL::setSql($sql);
        $methods = ['insertTableNames', 'updateTableNames', 'deleteTableNames'];
        $method = $methods[$action];
        $tables = SQL::$method();
        if (in_array(self::STORE_TABLE, $tables)) {
            if (isset($options['where']['store_id'])) {
                $storeId = $options['where']['store_id'];
                S("{$storeId}_storeInfo", null);
                new WxApi($storeId);
            }
        }

        // 没开启缓存不操作
        if (!$this->cacheOn) {
            return true;
        }

        // 无需做下面的操作
        if (count($tables) === 1 && $this->isNoCacheTable($tables[0])) {
            return true;
        }

        return $this->saveChangeCacheMap($tables, $data, $options);
    }

    /**
     * 新增后要清除缓存
     * @param array $data
     * @param array $options
     * @return boolean
     * Date: 2018-06-01 14:59:09
     * Update: 2018-06-01 14:59:09
     * Version: 1.00
     */
    public function afterInsert($data, $options)
    {
        return $this->afterChange(self::INSERT_ACTION, $data, $options);
    }

    /**
     * 更新后清除缓存
     * @param array $data
     * @param array $options
     * @return boolean
     * User: hjun
     * Date: 2018-06-01 15:24:34
     * Update: 2018-06-01 15:24:34
     * Version: 1.00
     */
    public function afterUpdate($data, $options)
    {
        return $this->afterChange(self::UPDATE_ACTION, $data, $options);
    }

    /**
     * 删除后清除缓存
     * @param array $data
     * @param array $options
     * @return boolean
     * User: hjun
     * Date: 2018-06-01 15:24:50
     * Update: 2018-06-01 15:24:50
     * Version: 1.00
     */
    public function afterDelete($data, $options)
    {
        return $this->afterChange(self::DELETE_ACTION, $data, $options);
    }

    /**
     * 判断是否需要直接使用clear
     * @param array $deleteKeys
     * @return boolean
     * User: hjun
     * Date: 2018-08-09 13:39:29
     * Update: 2018-08-09 13:39:29
     * Version: 1.00
     */
    public function isNeedClear($deleteKeys = [])
    {
        $num = count($deleteKeys);
        return $num >= $this->needClearNum;
    }

    /**
     * 保存查询缓存映射关系
     * @param $set
     * @return mixed
     * User: hjun
     * Date: 2018-08-10 17:48:10
     * Update: 2018-08-10 17:48:10
     * Version: 1.00
     */
    public function saveQueryCacheMap($set)
    {
        try {
            if ($this->isDbType()) {
                return M($this->tableCacheName)->addAll($set);
            } else {
                $this->start();
                $this->arrSet->addAll($set);
                $this->saveTableCacheMap();
            }
        } catch (\Exception $e) {
            $this->clear();
        }
        return $this->isDbType() ? false : $this->finish();
    }

    /**
     * 保存修改后的缓存映射
     * @param array $tables
     * @param array $data
     * @param array $options
     * @return mixed
     * User: hjun
     * Date: 2018-08-10 17:52:02
     * Update: 2018-08-10 17:52:02
     * Version: 1.00
     */
    public function saveChangeCacheMap($tables, $data, $options)
    {
        if ($this->isDbType()) {
            try {
                $where = [];
                if (count($tables) === 1) {
                    $where['table'] = $tables[0];
                } else {
                    $where['table'] = ['in', $tables];
                }
                $list = M($this->tableCacheName)->where($where)->select();
                $where = [];
                $cacheWhere = [];
                foreach ($list as $cache) {
                    $bool = $this->rm($cache['cache']);
                    if ($bool) {
                        $cacheWhere[] = $cache['id'];
                    }
                }
                if (!empty($cacheWhere)) {
                    $where['id'] = ['in', $cacheWhere];
                    return M($this->tableCacheName)->where($where)->delete();
                }
                return true;
            } catch (\Exception $e) {
                $this->clear();
            }
            return false;

        } else {
            try {
                // 获取表相关的所有缓存键值
                $this->start();
                $allCacheKeys = [];
                foreach ($tables as $table) {
                    $where = [];
                    $where['table'] = $table;
                    $cacheKeys = $this->arrSet->getField($where, 'cache', true);
                    $allCacheKeys = array_merge($allCacheKeys, $cacheKeys);
                    if ($table === self::STORE_TABLE) {
                        if (isset($options['where']['store_id'])) {
                            $storeId = $options['where']['store_id'];
                            S("{$storeId}_storeInfo", null);
                        }
                    }
                }
                if (empty($allCacheKeys)) {
                    $this->finish();
                    return true;
                }
                if ($this->isNeedClear($allCacheKeys)) {
                    $this->clear();
                } else {
                    // 清除所有相关缓存
                    foreach ($allCacheKeys as $cacheKey) {
                        $bool = $this->rm($cacheKey);
                        // 更新关联表
                        if ($bool) {
                            $where = [];
                            $where['cache'] = $cacheKey;
                            $this->arrSet->delete($where);
                        }
                    }
                    $this->saveTableCacheMap();
                }
            } catch (\Exception $e) {
                $this->clear();
            }
            return $this->finish();
        }
    }
}