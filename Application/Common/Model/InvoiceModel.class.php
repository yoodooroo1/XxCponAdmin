<?php

namespace Common\Model;

/**
 * 发票模型
 * Class InvoiceModel
 * @package Common\Model
 * User: hjun
 * Date: 2018-03-14 10:22:27
 * Update: 2018-03-14 10:22:27
 * Version: 1.00
 */
class InvoiceModel extends BaseModel
{
    const TYPE_COMPANY = 0;
    const TYPE_PERSONAL = 1;

    protected $tableName = 'mb_invoice';

    // 验证字段1,验证规则,错误提示,[验证条件,附加规则,验证时间,参数列表
    // 验证规则: require 字段必须、email 邮箱、url URL地址、currency 货币、number 数字
    protected $_validate = [
        ['invoice_type', '0,1', '请选择发票类型', 0, 'in', 3],
        ['invoice_title', 'require', '请输入发票抬头', 0, 'regex', 3],
    ];

    /**
     * 获取会员的发票列表
     * @param int $memberId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-14 10:36:43
     * Update: 2018-03-14 10:36:43
     * Version: 1.00
     */
    public function getInvoiceList($memberId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $field = [
            'invoice_id', 'member_id', 'invoice_type', 'invoice_title', 'invoice_code',
            'is_default'
        ];
        $field = implode(',', $field);
        $where = [];
        $where['is_delete'] = 0;
        $where['member_id'] = $memberId;
        $where = array_merge($where, $condition);
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        $checkedId = 0;
        foreach ($list as $key => $value) {
            if ($value['is_default'] == 1) {
                $checkedId = $value['invoice_id'];
                break;
            }
        }
        $result['data']['checkedId'] = $checkedId;
        $result['datas']['checkedId'] = $checkedId;
        return $result;
    }

    public function isPersonal($invoice)
    {
        return $invoice['invoice_type'] == self::TYPE_PERSONAL;
    }

    /**
     * 根据ID获取发票信息
     * @param int $memberId
     * @param int $invoiceId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-14 10:59:10
     * Update: 2018-03-14 10:59:10
     * Version: 1.00
     */
    public function getInvoiceById($invoiceId = 0)
    {
        if (empty($invoiceId)) return getReturn(-1, '参数错误');
        $field = [
            'invoice_id', 'member_id', 'invoice_type', 'invoice_title', 'invoice_code'
        ];
        $field = implode(',', $field);
        $where = [];
        $where['invoice_id'] = $invoiceId;
        $where['is_delete'] = 0;
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        $result = $this->queryRow($options);
        $data = empty($result['data']) ? [] : $result['data'];
        $data['invoice_type_name'] = $this->isPersonal($data) ? '个人' : '单位';
        return $data;
    }

    /**
     * 根据ID获取发票信息
     * @param int $memberId
     * @param int $invoiceId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-14 10:59:10
     * Update: 2018-03-14 10:59:10
     * Version: 1.00
     */
    public function getMemberInvoiceById($memberId = 0, $invoiceId = 0)
    {
        if (empty($invoiceId)) return getReturn(-1, '参数错误');
        $field = [
            'invoice_id', 'member_id', 'invoice_type', 'invoice_title', 'invoice_code'
        ];
        $field = implode(',', $field);
        $where = [];
        $where['invoice_id'] = $invoiceId;
        $where['member_id'] = $memberId;
        $where['is_delete'] = 0;
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        $result = $this->queryRow($options);
        $data = empty($result['data']) ? [] : $result['data'];
        $data['invoice_type_name'] = $this->isPersonal($data) ? '个人' : '单位';
        return $data;
    }

    /**
     * 新增/编辑 发票
     * @param int $memberId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-14 11:51:27
     * Update: 2018-03-14 11:51:27
     * Version: 1.00
     */
    public function saveInvoiceInfo($memberId = 0, $data = [])
    {
        if ($data['invoice_type'] == 0 && empty($data['invoice_code'])) {
            return getReturn(-1, '请输入税号');
        }
        $maxVersion = $this->max('version');
        $data['version'] = (int)$maxVersion + 1;
        if ($data['invoice_id'] > 0) {
            $where = [];
            $where['invoice_id'] = $data['invoice_id'];
            $where['member_id'] = $memberId;
            $where['is_delete'] = 0;
            $info = $this->field('invoice_id')->where($where)->find();
            if (empty($info)) return getReturn(-1, '记录不存在');
            return $this->saveData([], $data);
        } else {
            $data['member_id'] = $memberId;
            $data['create_time'] = NOW_TIME;
            $result = $this->addData([], $data);
            if ($result['code'] !== 200) return $result;
            $data['invoice_id'] = $result['data'];
            return getReturn(200, '', $data);
        }
    }

    /**
     * 删除发票信息
     * @param int $memberId
     * @param int $invoiceId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-14 12:09:39
     * Update: 2018-03-14 12:09:39
     * Version: 1.00
     */
    public function delInvoice($memberId = 0, $invoiceId = 0)
    {
        $where = [];
        $where['invoice_id'] = $invoiceId;
        $where['member_id'] = $memberId;
        $where['is_delete'] = 0;
        $info = $this->field('invoice_id')->where($where)->find();
        if (empty($info)) return getReturn(-1, '记录不存在');
        $data = [];
        $data['invoice_id'] = $invoiceId;
        $data['is_delete'] = 1;
        $maxVersion = $this->max('version');
        $data['version'] = (int)$maxVersion + 1;
        return $this->saveData([], $data);
    }

    /**
     * 设置默认的发票
     * @param int $memberId
     * @param int $invoiceId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-03-25 21:15:47
     * Update: 2018-03-25 21:15:47
     * Version: 1.00
     */
    public function setDefaultInvoice($memberId = 0, $invoiceId = 0)
    {
        $maxVersion = $this->max('version');
        $where = [];
        $where['member_id'] = $memberId;
        $where['is_delete'] = 0;
        $where['is_default'] = 1;
        $data = [];
        $data['is_default'] = 0;
        $data['version'] = ++$maxVersion;
        $this->where($where)->save($data);
        $where = [];
        $where['invoice_id'] = $invoiceId;
        $where['member_id'] = $memberId;
        $where['is_delete'] = 0;
        $data = [];
        $data['is_default'] = 1;
        $data['version'] = ++$maxVersion;
        $result = $this->where($where)->save($data);
        if (false === $result) {
            return getReturn(CODE_ERROR);
        }
        return getReturn(CODE_SUCCESS, 'success', $result);
    }

    /**
     * 设置不开发票
     * @param int $memberId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-14 14:22:00
     * Update: 2018-12-14 14:22:00
     * Version: 1.00
     */
    public function setNoDefault($memberId = 0)
    {
        $where = [];
        $where['member_id'] = $memberId;
        $where['is_default'] = 1;
        $where['is_delete'] = 0;
        $data = [];
        $maxVersion = $this->max('version');
        $data['version'] = (int)$maxVersion + 1;
        $data['is_default'] = 0;
        $result = $this->where($where)->save($data);
        if (false === $result) {
            return getReturn(CODE_ERROR);
        }
        return getReturn(CODE_SUCCESS, 'success', $result);
    }

    /**
     * 获取默认的发票
     * @param int $memberId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-14 14:26:23
     * Update: 2018-12-14 14:26:23
     * Version: 1.00
     */
    public function getMemberDefaultInvoice($memberId = 0)
    {
        $where = [];
        $where['member_id'] = $memberId;
        $where['is_default'] = 1;
        $where['is_delete'] = NOT_DELETE;
        return $this->where($where)->find();
    }
}