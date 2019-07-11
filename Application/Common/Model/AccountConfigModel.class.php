<?php

namespace Common\Model;

use Think\Log;

/**
 * 店铺的提现账号信息 表
 * Class AccountConfigModel
 * @package Common\Model
 */
class AccountConfigModel extends BaseModel
{
    protected $tableName = 'mb_account_config';

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
    protected $_validate = array(
        array('account_type', 'require', '请选择提现方式', 1, 'regex', 2),
        array('account_type_name', 'require', '请选择提现银行', 1, 'regex', 2),
        array('account_member_name', 'require', '请输入收款户名', 1, 'regex', 2),
        array('account_card_name', 'require', '请输入银行卡号', 1, 'regex', 2),
    );

    /*
     * 自动完成
     * array(完成字段1,完成规则,[完成时间,附加规则,参数列表])
     * 完成规则: 配合附加规则
     * 完成时间: 1 新增 2 编辑 3 全部
     * 附加规则:
        function 使用函数，表示填充的内容是一个函数名
        callback 回调方法 ，表示填充的内容是一个当前模型的方法
        field 用其它字段填充，表示填充的内容是一个其他字段的值
        string 字符串（默认方式）
        ignore 为空则忽略（3.1.2新增）
     * 参数列表: array() callback的参数列表
     */
    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
    );

    /**
     * 获取店铺的提现账号信息
     * @param int $storeId 店铺id
     * @return array
     */
    public function getStoreAccountConfig($storeId = 0)
    {
        logWrite('获取店铺提现账号信息：' . $storeId);
        $where = array();
        $where['store_id'] = $storeId;
        $result = $this->where($where)->find();
        if (false === $result) {
            logWrite('获取信息出错：' . $this->getDbError());
            return $this->getReturn(-1, $this->getDbError());
        }
        return $this->getReturn(200, '成功', $result);
    }

    /**
     * 设置店铺的提现账号信息
     * @param int $storeId 店铺id
     * @param array $data 数据
     * @return array
     */
    public function setStoreAccountConfig($storeId = 0, $data = array())
    {
        logWrite('更新店铺' . $storeId . '提现账号信息：' . json_encode($data, JSON_UNESCAPED_UNICODE));
        $where = array();
        $where['store_id'] = $storeId;
        $info = $this->where($where)->find();
        $type = empty($info) ? 1 : 2;
        $data = $this->create($data, $type);
        if (false === $data) {
            logWrite('创建数据对象出错：' . $this->getError());
            return $this->getReturn(-1, $this->getError());
        }
        if (empty($info)){
            $data['store_id'] = $storeId;
            $result = $this->add($data);
        }else {
            $result = $this->where($where)->save($data);
        }
        if (false === $result) {
            logWrite('更新店铺账号信息出错：' . $this->getDbError());
            return $this->getReturn(-1, $this->getDbError());
        }
        $data['store_id'] = $storeId;
        $data['id'] = empty($info) ? $result : $info['id'];
        return $this->getReturn(200, '成功', $data);
    }
}