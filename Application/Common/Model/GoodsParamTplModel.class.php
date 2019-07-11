<?php

namespace Common\Model;

use JsonSchema\Validator;

/**
 * 商品参数模版类
 * Class GoodsParamTplModel
 * @package Common\Model
 * User: hjun
 * Date: 2018-12-17 10:28:13
 * Update: 2018-12-17 10:28:13
 * Version: 1.00
 */
class GoodsParamTplModel extends BaseModel
{
    protected $tableName = 'mb_goods_param_tpl';

    /**
     * 验证参数模版
     * @param int $tplId
     * @return bool
     * User: hjun
     * Date: 2018-12-17 11:01:28
     * Update: 2018-12-17 11:01:28
     * Version: 1.00
     */
    public function validateTpl($tplId = 0)
    {
        $tpl = $this->getTplFromCache($tplId);
        return !empty($tpl);
    }

    /**
     * 验证模版参数项
     * @param array $tplParams
     * @return boolean
     * User: hjun
     * Date: 2018-12-17 11:02:02
     * Update: 2018-12-17 11:02:02
     * Version: 1.00
     */
    public function validateTplParams($tplParams = [])
    {
        // 检查JSON格式
        $schema = getDefaultData("json/goodsParam/tpl_param_schema");
        $validate = new Validator();
        $validate->check($tplParams, $schema);
        if (!$validate->isValid()) {
            $errors = $validate->getErrors();
            $message = "{$errors[0]['property']}:{$errors[0]['message']}";
            if (strpos($errors[0]['property'], 'param_name') !== false) {
                $this->setValidateError("请输入参数名称");
            } else {
                $this->setValidateError($message);
            }
            return false;
        }
        return true;
    }

    /**
     * 生成参数项JSON
     * @param array $tplParams
     * @return string
     * User: hjun
     * Date: 2018-12-17 11:06:19
     * Update: 2018-12-17 11:06:19
     * Version: 1.00
     */
    public function autoTplParams($tplParams = [])
    {
        if (empty($tplParams)) {
            return '';
        }
        // 排序
        $tplParams = array_sort($tplParams, 'param_sort', 'ASC');
        return jsonEncode($tplParams);
    }

    /**
     * 获取参数模版列表页的数据
     * @param int $page
     * @param int $limit
     * @param array $queryWhere
     * @return array
     * User: hjun
     * Date: 2018-12-17 10:32:28
     * Update: 2018-12-17 10:32:28
     * Version: 1.00
     */
    public function getTplListData($page = 1, $limit = 20, $queryWhere = [])
    {
        $where = [];
        $where['a.store_id'] = $this->getStoreId();
        $where['a.is_delete'] = NOT_DELETE;
        $where = array_merge($where, $queryWhere);
        $order = '';
        $options = [];
        $options['alias'] = 'a';
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = $order;
        $data = $this->queryList($options)['data'];
        return $data;
    }

    /**
     * 获取选择框列表数据
     * @param bool $needParams 是否需要查询参数项
     * @return array
     * User: hjun
     * Date: 2018-12-17 17:20:57
     * Update: 2018-12-17 17:20:57
     * Version: 1.00
     */
    public function getTplSelectOptions($needParams = false)
    {
        $field = ['tpl_id', 'tpl_name'];
        if ($needParams) {
            $field[] = 'tpl_params';
        }
        $where = [];
        $where['store_id'] = $this->getStoreId();
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        return $this->selectList($options);
    }

    /**
     * 获取参数模版数据
     * @param int $tplId
     * @return array
     * User: hjun
     * Date: 2018-12-17 10:44:22
     * Update: 2018-12-17 10:44:22
     * Version: 1.00
     */
    public function getTpl($tplId = 0)
    {
        $where = [];
        $where['tpl_id'] = $tplId;
        $where['store_id'] = $this->getStoreId();
        $where['is_delete'] = NOT_DELETE;
        $options = [];
        $options['where'] = $where;
        return $this->selectRow($options);
    }

    /**
     * 缓存中获取模版数据
     * @param int $tplId
     * @return array
     * User: hjun
     * Date: 2018-12-17 10:52:40
     * Update: 2018-12-17 10:52:40
     * Version: 1.00
     */
    public function getTplFromCache($tplId = 0)
    {
        $tpl = $this->getLastQueryData("param_tpl:{$tplId}");
        if (empty($tpl)) {
            $tpl = $this->getTpl($tplId);
            $this->setLastQueryData("param_tpl:{$tplId}", $tpl);
        }
        return $tpl;
    }

    /**
     * 根据action获取字段
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2018-12-17 10:47:49
     * Update: 2018-12-17 10:47:49
     * Version: 1.00
     */
    public function getFieldsByAction($action = self::MODEL_INSERT, $request = [])
    {
        $table = [];
        $table[self::MODEL_INSERT] = ['store_id', 'tpl_name', 'tpl_params', 'create_time'];
        $table[self::MODEL_UPDATE] = ['tpl_name', 'tpl_params'];
        $table[self::MODEL_DELETE] = ['is_delete'];
        return isset($table[$action]) ? $table[$action] : [];
    }

    /**
     * 根据action获取验证规则
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2018-12-17 10:47:49
     * Update: 2018-12-17 10:47:49
     * Version: 1.00
     */
    public function getValidateByAction($action = self::MODEL_INSERT, $request = [])
    {
        $table = [];
        $table[self::MODEL_INSERT] = [
            ['tpl_name', 'require', '请输入模版名称', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT],
            ['tpl_params', 'validateTplParams', '参数项错误', self::MUST_VALIDATE, 'callback', self::MODEL_INSERT],
        ];
        $table[self::MODEL_UPDATE] = [
            ['tpl_id', 'validateTpl', '参数模版已失效', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE],
            ['tpl_name', 'require', '请输入模版名称', self::MUST_VALIDATE, 'regex', self::MODEL_UPDATE],
            ['tpl_params', 'validateTplParams', '参数项错误', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE],
        ];
        $table[self::MODEL_DELETE] = [
            ['tpl_id', 'validateTpl', '参数模版已删除', self::MUST_VALIDATE, 'callback', self::MODEL_UPDATE],
        ];
        return isset($table[$action]) ? $table[$action] : [];
    }

    /**
     * 根据action获取完成规则
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2018-12-17 10:47:49
     * Update: 2018-12-17 10:47:49
     * Version: 1.00
     */
    public function getAutoByAction($action = self::MODEL_INSERT, $request = [])
    {
        $table = [];
        $table[self::MODEL_INSERT] = [
            ['store_id', $this->getStoreId(), self::MODEL_INSERT, 'string'],
            ['create_time', NOW_TIME, self::MODEL_INSERT, 'string'],
            ['tpl_params', $this->autoTplParams($request['tpl_params']), self::MODEL_INSERT, 'string'],
        ];
        $table[self::MODEL_UPDATE] = [
            ['tpl_params', $this->autoTplParams($request['tpl_params']), self::MODEL_UPDATE, 'string'],
        ];
        $table[self::MODEL_DELETE] = [
            ['is_delete', '1', self::MODEL_UPDATE, 'string'],
        ];
        return isset($table[$action]) ? $table[$action] : [];
    }

    /**
     * 根据action获取数据库操作类型
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2018-12-17 10:47:49
     * Update: 2018-12-17 10:47:49
     * Version: 1.00
     */
    public function getTypeByAction($action = self::MODEL_INSERT, $request = [])
    {
        $table = [];
        $table[self::MODEL_INSERT] = self::MODEL_INSERT;
        $table[self::MODEL_UPDATE] = self::MODEL_UPDATE;
        $table[self::MODEL_DELETE] = self::MODEL_UPDATE;
        return isset($table[$action]) ? $table[$action] : [];
    }

    /**
     * 参数模版的操作
     * @param int $action
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-17 10:54:15
     * Update: 2018-12-17 10:54:15
     * Version: 1.00
     */
    public function tplAction($action = self::MODEL_INSERT, $request = [])
    {
        $fields = $this->getFieldsByAction($action, $request);
        $validate = $this->getValidateByAction($action, $request);
        $auto = $this->getAutoByAction($action, $request);
        $type = $this->getTypeByAction($action, $request);
        $result = $this->getAndValidateDataFromRequest($fields, $request, $validate, $auto, $type);
        if (!isSuccess($result)) {
            return $result;
        }
        $data = $result['data'];
        $this->startTrans();
        if ($type === self::MODEL_INSERT) {
            $result = $this->add($data);
            $data['tpl_id'] = $result;
        } else {
            $where = [];
            $where['tpl_id'] = $request['tpl_id'];
            $result = $this->where($where)->save($data);
            $old = $this->getTplFromCache($request['tpl_id']);
            $data = array_merge($old, $data);
        }
        if (false === $result) {
            $this->rollback();
            return getReturn(CODE_ERROR);
        }
        // 如果是删除 还要把选择该模版的分类的数据清空
        if (self::MODEL_DELETE === $action) {
            $where = [];
            $where['goods_param_tpl_id'] = $data['tpl_id'];
            $result = D('GoodsClass')->where($where)->setField('goods_param_tpl_id', 0);
            if (false === $result) {
                $this->rollback();
                return getReturn(CODE_ERROR);
            }
        }
        $this->commit();
        return getReturn(CODE_SUCCESS, 'success', $data);
    }

    /**
     * 新增参数模版
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-17 17:17:14
     * Update: 2018-12-17 17:17:14
     * Version: 1.00
     */
    public function addTpl($request = [])
    {
        $result = $this->tplAction(self::MODEL_INSERT, $request);
        if (isSuccess($result)) {
            $result['msg'] = "新增成功";
        }
        return $result;
    }

    /**
     * 修改参数模版
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-17 17:17:46
     * Update: 2018-12-17 17:17:46
     * Version: 1.00
     */
    public function updateTpl($request = [])
    {
        $result = $this->tplAction(self::MODEL_UPDATE, $request);
        if (isSuccess($result)) {
            $result['msg'] = "修改成功";
        }
        return $result;
    }

    /**
     * 删除参数模版
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-17 17:18:13
     * Update: 2018-12-17 17:18:13
     * Version: 1.00
     */
    public function deleteTpl($request = [])
    {
        $result = $this->tplAction(self::MODEL_DELETE, $request);
        if (isSuccess($result)) {
            $result['msg'] = "删除成功";
        }
        return $result;
    }
}