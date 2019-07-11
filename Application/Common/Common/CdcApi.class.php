<?php
namespace Common\Common;

class CdcApi
{

    /**
     * 创造key值
     *
     * @param string $mkey
     *
     * @return string $result 返回请求结果
     */
    private static function makeKey($mkey)
    {
        return md5($mkey . SERVICR_KEY);
    }

    /**
     *
     * 获取渠道信息
     *
     */
    public static function channelSwitch($channelId)
    {
        $mb_channel = Model('mb_channel');
        $channelData = $mb_channel->where(array('channel_id' => $channelId))->find();
        if (empty($channelData)) {
            return false;
        } else {
            if ($channelData['datacenter'] == 1) {
                return true;
            } else {
                return false;
            }

        }
    }

    /**
     *
     * 会员登录
     * @param array $inputArr
     *
     * @return string $result 返回请求结果
     */
    public static function register($user_name, $password, $member_tel = "", $member_email = "", $recommend_name = "")
    {

        $inputArr['member_name'] = $user_name;
        $inputArr['password'] = $password;
        $inputArr['member_tel'] = $member_tel;
        $inputArr['member_email'] = $member_email;
        $inputArr['recommend_name'] = $recommend_name;
        $url = "http://cdc.17wg.cn/index.php/service/member/register";
        $inputArr["key"] = self::makeKey($inputArr["member_name"]);
        $inputArr["from"] = FROM;
        $inputArr["mall_name"] = MALL_NAME;
        $result = self::postdataCurl($url, $inputArr);
        return $result;
    }

    /**
     *
     * 会员登录
     * @param array $inputArr
     *
     * @return string $result 返回请求结果
     */
    public static function login($user_name, $password, $ismd5 = TRUE)
    {
        $inputArr['member_name'] = $user_name;
        //if($ismd5){
        $inputArr['password'] = $password;
        // }else{
        // 	$inputArr['password'] = md5($password);
        // }

        $url = "http://cdc.17wg.cn/index.php/service/member/login";

        $inputArr["key"] = self::makeKey($inputArr["member_name"]);
        $inputArr["from"] = FROM;
        $inputArr["mall_name"] = MALL_NAME;
        $inputArr['nosalt'] = true;

        $result = self::postdataCurl($url, $inputArr);
        return $result;
    }

    /**
     *
     * 修改会员信息
     * @param array $inputArr
     *
     *
     * @return string $result 返回请求结果
     */

    public static function updatepassword($user_name, $password)
    {
        $inputArr['member_name'] = $user_name;
        $inputArr['password'] = $password;
        $url = "http://cdc.17wg.cn/index.php/service/member/updatepassword";
        $inputArr["key"] = self::makeKey($inputArr["member_name"]);
        $inputArr["from"] = FROM;
        $inputArr["mall_name"] = MALL_NAME;

        $result = self::postdataCurl($url, $inputArr);
        return $result;
    }

    /**
     *
     * 会员登录
     * @param array $inputArr
     *
     * @return string $result 返回请求结果
     */
    public static function getmember($user_name)
    {

        $inputArr['member_name'] = $user_name;
        $url = "http://cdc.17wg.cn/index.php/service/member/getmember";
        $inputArr["key"] = self::makeKey($inputArr["member_name"]);
        $inputArr["from"] = FROM;
        $inputArr["mall_name"] = MALL_NAME;

        $result = self::postdataCurl($url, $inputArr);
        return $result;
    }

    /**
     *
     * 密保验证
     * @param array $inputArr
     *
     * @return string $result 返回请求结果
     */
    public static function confirmtel($user_name, $tel)
    {

        $inputArr['member_name'] = $user_name;
        $inputArr['member_tel'] = $tel;
        $url = "http://cdc.17wg.cn/index.php/service/member/confirmtel";
        $inputArr["key"] = self::makeKey($inputArr["member_name"]);
        $inputArr["from"] = FROM;
        $inputArr["mall_name"] = MALL_NAME;

        $result = self::postdataCurl($url, $inputArr);
        return $result;
    }


    /**
     *
     * 支付宝支付
     * @param 用户名 $inputArr
     *
     * @return string $result 返回请求结果
     */

    public static function alipay($inputArr)
    {

        $url = "http://cdc.17wg.cn/index.php/service/alipay/getconfig";
        $inputArr["key"] = self::makeKey($inputArr["order_id"]);
        $inputArr["fromtype"] = 1;
        $result = self::postdataCurl($url, $inputArr);
        return $result;
    }


    /**
     *
     * 申请会员账号
     * @param 用户名 $user_name
     * @param 用户密码 $password
     *
     * @return string $result 返回请求结果
     */

    public static function addmember($user_name = "", $password)
    {

        $url = "http://cdc.17wg.cn/index.php/service/member/addmember";
        $inputArr['member_name'] = $user_name;
        $inputArr['password'] = $password;
        $inputArr["key"] = self::makeKey($user_name);
        $inputArr["from"] = FROM;
        $inputArr["mall_name"] = MALL_NAME;

        $result = self::postdataCurl($url, $inputArr);

        $data = json_decode($result, TRUE);
        if ($data['result'] == 0) {
            $user_name = $data['datas'];
        }

        return $user_name;
    }

    /**
     *
     * 修改会员信息
     * @param 数据库对象 $db
     * @param 用户id $uid
     *
     * @return string $result 返回请求结果
     */

    public static function updateTel($username, $member_tel)
    {

        $url = "http://cdc.17wg.cn/index.php/service/member/updateTel";
        $inputArr['member_name'] = $username;
        $inputArr['member_tel'] = $member_tel;
        $inputArr["key"] = self::makeKey($inputArr["member_name"]);
        $inputArr["from"] = FROM;
        $inputArr["mall_name"] = MALL_NAME;

        self::postdataCurl($url, $inputArr);
        return $result;
    }

    public static function updateInfo($username, $member_avatar, $nick_name, $sex, $birthday, $member_tel)
    {

        $url = "http://cdc.17wg.cn/index.php/service/member/updatedetail";
        $inputArr['member_name'] = $username;
        $inputArr['member_avatar'] = $member_avatar;
        $inputArr['nick_name'] = $nick_name;
        $inputArr["key"] = self::makeKey($inputArr["member_name"]);
        $inputArr["from"] = FROM;
        $inputArr["mall_name"] = MALL_NAME;
        $inputArr['member_sex'] = $sex + 1;
        $inputArr['birthday'] = $birthday;
        $inputArr['member_tel'] = $member_tel;
        self::postdataCurl($url, $inputArr);
        return $result;
    }

    /**
     *
     * 订单退款
     * @param array $inputArr
     *
     * @return string $result 返回请求结果
     */
    public static function refund($order_id)
    {

        $inputArr['order_id'] = $order_id;
        // if ($order['pay_id']==6) {
        // 	$url = "http://cdc.17wg.cn/index.php/service/wxpay/refund";
        // }else if ($order['pay_id']==9){
        // 	$url = "http://cdc.17wg.cn/index.php/service/unionpay/refund";
        // }else if ($order['pay_id']==1){
        // 	$url = "http://cdc.17wg.cn/index.php/service/balancepay/refund";
        // }
        $url = "http://cdc.17wg.cn/index.php/service/balancepay/refund";

        $inputArr["key"] = self::makeKey($inputArr["order_id"]);
        $inputArr["from"] = FROM;
        $inputArr["mall_name"] = MALL_NAME;
        $inputArr["fromtype"] = 1;
        $result = self::postdataCurl($url, $inputArr);
        return $result;
    }

    /**
     * 以post方式提交数据到对应的接口url
     * @param string $params 需要post的数据
     * @param string $url url
     * @return string $data 返回请求结果
     */
    private static function postdataCurl($url, $params = array(), $times = 1)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        //运行curl
        $data = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        //返回结果
        if ($curl_errno == '0') {
            curl_close($ch);
            return $data;
        } else if ($times < 3) {
            $times++;
            return self::postdataCurl($url, $params, $times);
        } else {
            curl_close($ch);
            $resultdata['result'] = -1;
            $resultdata['error'] = "curl出错，错误码:" . $curl_errno;
            return json_encode($resultdata, JSON_UNESCAPED_UNICODE);
        }

    }


}

?>
