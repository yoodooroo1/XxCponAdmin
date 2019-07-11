<?php

namespace Common\Model;
/**
 * Class IndustryModel
 * User: hj
 * Date: 2017-10-26 23:56:23
 * Desc: 行业模型类
 * Update: 2017-10-26 23:56:26
 * Version: 1.0
 * @package Common\Model
 */
class CurrencyModel extends BaseModel
{
    protected $tableName = 'mb_currency';

    protected $_validate = [
        ['currency_name', 'require', '请输入货币名称', 0, 'regex', 3],
        ['currency_symbol', 'require', '请输入货币符号', 0, 'regex', 3],
        ['currency_unit', 'require', '请输入货币单位', 0, 'regex', 3],
    ];

    /**
     * 获取货币列表
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-06 16:00:45
     * Update: 2018-03-06 16:00:45
     * Version: 1.00
     */
    public function getCurrencyList($page = 1, $limit = 0, $condition = [])
    {
        $where = [];
        $where['is_delete'] = 0;
        $where = array_merge($where, $condition);
        $options = [];
        $options['field'] = true;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $condition = [];
        $condition['time_field'] = [
            'create_time' => []
        ];
        foreach ($list as $key => $value) {
            $list[$key] = $this->transformInfo($value, $condition);
        }
        $result['data']['list'] = $list;
        return $result;
    }

    /**
     * 保存货币
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-06 17:07:47
     * Update: 2018-03-06 17:07:47
     * Version: 1.00
     */
    public function saveCurrency($data = [])
    {
        if ($data['currency_id'] > 0 || $data['currency_id'] === '0') {
            $where = [];
            $where['is_delete'] = 0;
            $where['currency_id'] = $data['currency_id'];
            $info = $this->field(true)->where($where)->find();
            if (empty($info)) {
                return getReturn(-1, '记录不存在');
            }
        } else {
            unset($data['currency_id']);
            $data['create_time'] = NOW_TIME;
        }
        $data['version'] = $this->max('version') + 1;
        $act = $data['currency_id'] > 0 || $data['currency_id'] === '0' ? 'saveData' : 'addData';
        return $this->$act([], $data);
    }

    /**
     * 获取货币
     * @param int $landId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-06 17:12:43
     * Update: 2018-03-06 17:12:43
     * Version: 1.00
     */
    public function getCurrency($landId = 0)
    {
        $where = [];
        $where['currency_id'] = $landId;
        $where['is_delete'] = 0;
        $info = $this->field(true)->where($where)->find();
        if (false === $info) return getReturn();
        if (empty($info)) return getReturn(-1, '记录不存在');
        return getReturn(200, '', $info);
    }

    /**
     * 删除货币
     * @param int $langId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-06 17:45:11
     * Update: 2018-03-06 17:45:11
     * Version: 1.00
     */
    public function delCurrency($langId = 0)
    {
        $where = [];
        $where['currency_id'] = $langId;
        $where['is_delete'] = 0;
        $info = $this->field(true)->where($where)->find();
        if (false === $info) return getReturn();
        if (empty($info)) return getReturn(-1, '记录不存在');
        $data = [];
        $data['currency_id'] = $langId;
        $data['version'] = $this->max('version') + 1;
        $data['is_delete'] = 1;
        return $this->saveData([], $data);
    }
}