<?php

namespace Common\Logic;

use Think\Model;

class BaseLogic extends Model
{
    protected $autoCheckFields = false;

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

    protected $optimLock = false;

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);
        G('a');
    }

    public function __destruct()
    {
        G('b');
    }

}