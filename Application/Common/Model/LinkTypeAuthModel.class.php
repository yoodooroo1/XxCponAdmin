<?php

namespace Common\Model;
/**
 * Class LinkTypeAuthModel
 * User: hj
 * Desc: 跳转权限
 * Date: 2017-10-31 21:28:48
 * Update: 2017-10-31 21:28:49
 * Version: 1.0
 * @package Common\Model
 */
class LinkTypeAuthModel extends BaseModel
{

    protected $tableName = 'mb_link_type_auth';

    protected $_validate = [
        ['auth_name', 'require', '请输入名称', 0, 'regex', 3],
        ['auth_desc', 'require', '请输入描述', 0, 'regex', 3],
        ['channel_id', 'require', '请选择渠道号', 0, 'regex', 3],
        ['store_grade', 'require', '请选择商家等级', 0, 'regex', 3],
        ['store_type', 'require', '请选择商家类型', 0, 'regex', 3],
//        ['link_type_auth', 'require', '请选择可选跳转方式', 0, 'regex', 3],
        ['client_type', 'require', '请选择客户端类型', 0, 'regex', 3],
        ['auth_type', 'require', '请选择权限类型', 0, 'regex', 3],
    ];

    protected $_auto = [
        ['create_time', 'time', 1, 'function']
    ];

    public function getType($type = '')
    {
        if (empty($type)) {
            $type = 'web';
        } elseif ($type === 'wx' || $type === 'web') {
            $type = 'web';
        } else {
            $type = 'app';
        }
        return strtolower($type);
    }

    /**
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取跳转权限列表
     * Date: 2017-10-31 21:29:50
     * Update: 2017-10-31 21:29:51
     * Version: 1.0
     */
    public function getTypeAuthList($page = 1, $limit = -1, $condition = [])
    {
        $where = [];
        $where['a.is_delete'] = -1;
        $where = array_merge($where, $condition);
        $option = [];
        $option['alias'] = 'a';
        $field = [
            'a.id,a.auth_name,a.auth_desc,a.link_type_auth,a.create_time,a.store_type,a.client_type,a.store_grade,a.channel_id',
            'a.auth_type,b.channel_name,c.store_name'
        ];
        $option['field'] = implode(',', $field);
        $option['where'] = $where;
        $option['page'] = $page;
        $option['limit'] = $limit;
        $option['order'] = 'a.auth_type ASC,a.channel_id ASC,a.store_type ASC,a.store_grade ASC';
        $option['join'] = [
            '__MB_CHANNEL__ b ON b.channel_id = a.channel_id',
            'LEFT JOIN __STORE__ c ON c.store_id = a.store_id'
        ];
        $result = $this->queryList($option);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $total = $result['data']['total'];
        $modelType = D('LinkType');
        foreach ($list as $key => $value) {
            switch ((int)$value['store_type']) {
                case 0:
                    $storeTypeName = '商城主店';
                    break;
                case 1:
                    $storeTypeName = '商城子店';
                    break;
                case 2:
                    $storeTypeName = '连锁主店';
                    break;
                case 3:
                    $storeTypeName = '连锁子店';
                    break;
                case 4:
                    $storeTypeName = '独立店';
                    break;
                case 5:
                    $storeTypeName = '普通便利店';
                    break;
                default:
                    $storeTypeName = '普通便利店';
                    break;
            }
            switch ($this->getType($value['client_type'])) {
                case 'web':
                    $clientName = '微信';
                    break;
                case 'app':
                    $clientName = 'APP';
                    break;
                case 'wap':
                    $clientName = '移动';
                    break;
                default:
                    $clientName = 'APP';
                    break;
            }
            switch ((int)$value['auth_type']) {
                case 1:
                    $authTypeName = '通用权限';
                    break;
                case 2:
                    $authTypeName = '套餐权限';
                    break;
                case 3:
                    $authTypeName = '单独权限';
                    break;
                default:
                    $authTypeName = '通用权限';
                    break;
            }
            $list[$key]['store_type_name'] = $storeTypeName;
            $list[$key]['client_type_name'] = $clientName;
            $list[$key]['auth_type_name'] = $authTypeName;
            $where = [];
            $where['type_id'] = ['in', $value['link_type_auth']];
            $option = [];
            $option['where'] = $where;
            $result = $modelType->queryField($option, 'type_name', true);
            if ($result['code'] !== 200) return $result;
            $auth = [];
            foreach ($result['data'] as $k => $val) {
                if ($k <= 5) array_push($auth, $val);
                if ($k > 5) break;
            }
            $list[$key]['auth'] = implode(' ', $auth);
            $list[$key]['auth_title'] = implode('&#10;', $result['data']);
        }
        $data = [];
        $data['list'] = $list;
        $data['total'] = $total;
        return getReturn(200, '成功', $data);
    }

    /**
     * @param int $id
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取跳转权限信息
     * Date: 2017-10-31 21:57:53
     * Update: 2017-10-31 21:57:54
     * Version: 1.0
     */
    public function getTypeAuthInfo($id = 0)
    {
        $where = [];
        $where['id'] = $id;
        $where['is_delete'] = -1;
        $options = [];
        $options['where'] = $where;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (!empty($info)) $info['link_type_auth'] = explode(',', $info['link_type_auth']);
        $result['data'] = $info;
        return $result;
    }

    /**
     * @param int $id
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 保存跳转权限
     * Date: 2017-10-31 22:25:58
     * Update: 2017-10-31 22:25:59
     * Version: 1.0
     */
    public function saveTypeAuth($id = 0, $data = [])
    {
        $result = $this->getTypeAuthInfo($id);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, '数据不存在');
        if (isset($data['link_type_auth'])) $data['link_type_auth'] = implode(',', $data['link_type_auth']);
        if (!empty($data['client_type'])) $data['client_type'] = $this->getType($data['client_type']);
        $data = $this->create($data);
        if (false === $data) return getReturn(-1, $this->getError());
        $where = [];
        $where['id'] = $id;
        $result = $this->where($where)->save($data);
        if (false === $result) return getReturn();
        if (!empty($data['link_type_auth'])) $data['link_type_auth'] = explode(',', $data['link_type_auth']);
        foreach ($info as $key => $value) {
            $data[$key] = isset($data[$key]) ? $data[$key] : $value;
        }
        return getReturn(200, '保存成功', $data);
    }

    /**
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 新增跳转权限
     * Date: 2017-10-31 22:38:08
     * Update: 2017-10-31 22:38:09
     * Version: 1.0
     */
    public function addTypeAuth($data = [])
    {
        $data['link_type_auth'] = implode(',', $data['link_type_auth']);
        $data['client_type'] = $this->getType($data['client_type']);
        $data = $this->create($data);
        if (false === $data) return getReturn(-1, $this->getError());
        $result = $this->add($data);
        if (false === $result) return getReturn();
        $data['id'] = $result;
        $data['link_type_auth'] = explode(',', $data['link_type_auth']);
        return getReturn(200, '新增成功', $data);
    }

    /**
     * @param int $id
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 删除跳转权限
     * Date: 2017-10-31 22:47:36
     * Update: 2017-10-31 22:47:37
     * Version: 1.0
     */
    public function delTypeAuth($id = 0)
    {
        $data = [];
        $data['is_delete'] = 1;
        return $this->saveTypeAuth($id, $data);
    }

    /**
     * @param int $storeId
     * @param string $clientType
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 根据store_id获取单独配置的权限
     * Date: 2017-11-03 15:48:02
     * Update: 2017-11-03 15:48:02
     * Version: 1.0
     */
    public function getStoreIdAuth($storeId = 0, $clientType = 'app')
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['client_type'] = $this->getType($clientType);
        $where['auth_type'] = 3;
        $options = [];
        $options['field'] = 'link_type_auth';
        $options['where'] = $where;
        $auth = $this->selectRow($options);
        return getReturn(CODE_SUCCESS, 'success', $auth['link_type_auth']);
    }

    /**
     * @param $storeType
     * @param string $clientType
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取通用跳转权限
     * Date: 2017-11-01 11:35:32
     * Update: 2017-11-01 11:35:33
     * Version: 1.0
     */
    public function getStoreTypeAuth($storeType, $clientType = 'app')
    {
        $where = [];
        $where['store_type'] = $storeType;
        $where['client_type'] = $this->getType($clientType);
        $where['auth_type'] = 1;
        $options = [];
        $options['field'] = 'link_type_auth';
        $options['where'] = $where;
        $auth = $this->selectRow($options);
        return getReturn(CODE_SUCCESS, 'success', $auth['link_type_auth']);
    }

    /**
     * @param int $channelId
     * @param int $storeGrade
     * @param string $clientType
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取商家套餐跳转权限
     * Date: 2017-11-01 11:37:13
     * Update: 2017-11-01 11:37:13
     * Version: 1.0
     */
    public function getStoreGradeAuth($channelId = 0, $storeGrade = 0, $clientType = 'app')
    {
        $where = [];
        $where['channel_id'] = $channelId;;
        $where['store_grade'] = $storeGrade;
        $where['auth_type'] = 2;
        $where['client_type'] = $this->getType($clientType);
        $options = [];
        $options['field'] = 'link_type_auth';
        $options['where'] = $where;
        $auth = $this->selectRow($options)['link_type_auth'];
        // 如果没找到 则查找讯信套餐
        if ($auth === NULL) {
            $where['channel_id'] = 0;
            $options['where'] = $where;
            $auth = $this->selectRow($options)['link_type_auth'];
        }
        return getReturn(CODE_SUCCESS, 'success', $auth);
    }

}