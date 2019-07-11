<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/1/6
 * Time: 5:10
 */

namespace Common\Common;

/**
 * 签名算法类
 * Class Sign
 * @package Common\Common
 * User: hjun
 * Date: 2018-01-06 05:11:17
 * Update: 2018-01-06 05:11:17
 * Version: 1.00
 */
class Sign
{
    protected $values = array();

    /**
     * 构造函数 传递需要处理的参数数组
     * Sign constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        $this->values = $params;
    }

    /**
     * 设置签名，详见签名生成算法
     * @return  string sign
     **/
    public function SetSign()
    {
        $sign = $this->MakeSign();
        $this->values['sign'] = $sign;
        return $sign;
    }

    /**
     * 获取签名，详见签名生成算法的值
     * @return string 值
     **/
    public function GetSign()
    {
        return $this->values['sign'];
    }

    /**
     * 判断签名，详见签名生成算法是否存在
     * @return true 或 false
     **/
    public function IsSignSet()
    {
        return array_key_exists('sign', $this->values);
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function ToUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 生成签名
     * @return string 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function MakeSign()
    {
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->ToUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . C('API_SIGN_KEY');
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 获取设置的值
     */
    public function GetValues()
    {
        return $this->values;
    }

    /**
     *
     * 检测签名
     */
    public function CheckSign()
    {
        //fix异常
        if (!$this->IsSignSet()) {
            return getReturn(-1, '签名错误');
        }

        $sign = $this->MakeSign();
        if ($this->GetSign() == $sign) {
            return getReturn(200);
        }
        return getReturn(-1, '签名错误');
    }
}