<?php

namespace Common\Model;
/**
 * Class LinkTypeModel
 * User: hj
 * Date: 2017-10-30 17:27:19
 * Desc: 跳转方式 模型
 * Update: 2017-10-30 17:27:20
 * Version: 1.0
 * @package Common\Model
 */
class LinkTypeModel extends BaseModel
{

    protected $tableName = 'mb_link_type';

    protected $_validate = [
        ['type_name', 'require', '请输入跳转名称', 0, 'regex', 3],
        ['type_action', 'require', '请输入跳转动作标识', 0, 'regex', 3],
        ['is_system', 'require', '请选择是否是系统功能', 0, 'regex', 3]
    ];

    protected $_auto = [
        ['create_time', 'time', 1, 'function']
    ];

    /**
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @param string $field
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-31 10:35:23
     * Desc: 获取跳转方式列表
     * Update: 2017-10-31 10:35:24
     * Version: 1.0
     */
    public function getLinkTypeList($page = 1, $limit = -1, $condition = [], $field = '')
    {
        $where = [];
        $where['is_delete'] = -1;
        $where = array_merge($where, $condition);
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = 'sort';
        $result = $this->queryList($options);
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key]['is_system_name'] = $value['is_system'] == 1 ? "是" : '否';
            switch ($value['input_type']) {
                case 'select':
                    $inputTypeName = '选择框';
                    break;
                case 'text':
                    $inputTypeName = '输入框';
                    break;
                default:
                    $inputTypeName = '无参数';
                    break;
            }
            $list[$key]['input_type_name'] = $inputTypeName;
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * @param int $typeId
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-31 10:35:34
     * Desc: 获取跳转方式信息
     * Update: 2017-10-31 10:35:35
     * Version: 1.0
     */
    public function getLinkTypeInfo($typeId = 0, $condition = [])
    {
        $where = [];
        $where['type_id'] = $typeId;
        $where = array_merge($where, $condition);
        $options = [];
        $options['where'] = $where;
        return $this->queryRow($options);
    }

    /**
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-31 10:35:44
     * Desc: 添加跳转方式
     * Update: 2017-10-31 10:35:45
     * Version: 1.0
     */
    public function addLinkType($data = [])
    {
        $data = $this->create($data, 1);
        if (false === $data) return getReturn(-1, $this->getError());
        $result = $this->add($data);
        if (false === $result) return $this->getFalseReturn();
        $data['type_id'] = $result;
        return getReturn(200, '新增成功', $data);
    }

    /**
     * @param int $typeId
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-31 10:35:53
     * Desc: 更新跳转方式
     * Update: 2017-10-31 10:35:54
     * Version: 1.0
     */
    public function saveLinkType($typeId = 0, $data = [])
    {
        $where = [];
        $where['type_id'] = $typeId;
        $options = [];
        $options['where'] = $where;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, '未查询到相关数据');
        $data = $this->create($data, 2);
        if (false === $data) return getReturn(-1, $this->getError());
        $result = $this->where($where)->save($data);
        if ($result === false) return $this->getFalseReturn();
        foreach ($info as $key => $value) {
            $data[$key] = isset($data[$key]) ? $data[$key] : $value;
        }
        return getReturn(200, '保存成功', $data);
    }

    /**
     * @param int $typeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-31 10:36:04
     * Desc: 删除跳转方式
     * Update: 2017-10-31 10:36:05
     * Version: 1.0
     */
    public function delLinkType($typeId = 0)
    {
        $data = [];
        $data['is_delete'] = 1;
        return $this->saveLinkType($typeId, $data);
    }

    /**
     * @param string $auth
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 根据权限获取跳转方式列表
     * Date: 2017-11-01 12:13:28
     * Update: 2017-11-01 12:13:29
     * Version: 1.0
     */
    public function getLinkTypeListByAuth($auth = '')
    {
        $where = [];
        $where['type_id'] = ['in', $auth];
        $field = 'type_name name,type_action type,is_system,input_type,type_tips tips,type_url url,sort';
        $result = $this->getLinkTypeList(1, 0, $where, $field);
        if ($result['code'] !== 200) return $result;
        return getReturn(200, '成功', $result['data']['list']);
    }

    /**
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取系统功能的action数组
     * Date: 2017-11-02 21:53:19
     * Update: 2017-11-02 21:53:20
     * Version: 1.0
     */
    public function getSystemLinkTypeActionArr()
    {
        $where = [];
        $where['is_system'] = 1;
        $where['is_delete'] = -1;
        $options = [];
        $options['field'] = 'type_action';
        $options['where'] = $where;
        $list = $this->selectList($options);
        $systemAction = [];
        foreach ($list as $action) {
            $systemAction[] = $action['type_action'];
        }
        return getReturn(CODE_SUCCESS, 'success', $systemAction);
    }
}