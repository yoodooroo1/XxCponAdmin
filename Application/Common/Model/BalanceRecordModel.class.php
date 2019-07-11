<?php

namespace Common\Model;

class BalanceRecordModel extends BaseModel
{
    protected $tableName = 'mb_balancerecord';

    /**
     * 获取余额记录的查询条件
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2019-06-21 12:46:34
     * Update: 2019-06-21 12:46:34
     * Version: 1.00
     */
    public function getRecordListQueryWhere($request = [])
    {
        $where = [];
        // 会员ID
        if (!empty($request['member_id'])) {
            $where['a.mid'] = $request['member_id'];
        }
        return $where;
    }

    /**
     * 查询余额列表
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2019-06-21 12:45:06
     * Update: 2019-06-21 12:45:06
     * Version: 1.00
     */
    public function queryRecordList($request = [])
    {
        $field = [
            'a.type_name', 'a.create_time', 'a.money',
        ];
        $where = $this->getRecordListQueryWhere($request);
        $where['a.sid'] = $this->getStoreId();
        $order = 'a.id DESC';
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['where'] = $where;
        $options['page'] = $request['page'];
        $options['limit'] = $request['limit'];
        $options['order'] = $order;
        $result = $this->queryList($options)['data'];
        $meta = [
            'sum' => 0, // 累计充值
        ];
        if ($result['total'] > 0) {
            $field = [
                "IFNULL(SUM((a.money > 0) * (a.tid != 5) * a.money),0) sum"
            ];
            $options = [];
            $options['alias'] = 'a';
            $options['field'] = $field;
            $options['where'] = $where;
            $meta = $this->selectRow($options);
        }
        foreach ($result['list'] as $key => $value) {
            $value['create_time_text'] = date('Y-m-d H:i:s');
            $result['list'][$key] = $value;
        }
        $result['meta'] = $meta;
        return $result;
    }
}