<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/6
 * Time: 9:39
 */

namespace Admin\Controller;

use Common\Controller\AdminController;

class BaseController extends  AdminController
{
    //====================常用变量================//
    protected $admin_id = '';
    protected $loginname = '';
    protected $store_id = '';
    protected $key = '';
//    protected $role = '';
//    protected $group_id = '';

    //====================常用变量================//
    public function __construct()
    {
        parent::__construct();
    }

    public function getXxUrl($act,$op){
        $apiUrl = XX_API . "/index.php?act=" . $act . "&op=" . $op;
        return $apiUrl;
    }

    protected function initSystem()
    {
        // 请求参数获取
        $this->reqGet = $this->req;
        $this->reqPost = $this->req;
        $this->reqBody = I('put.');
        // 设置常用变量值
        $this->setAttr();

    }


    protected function setAttr()
    {
        // 初始化属性
        $this->admin_id = (int)session('login_info.id');
        $this->loginname = session('login_info.loginname');
        $this->store_id = session('store_id');
        $this->key = session('key');
//        $this->role = session('login_info.role');
//        $this->group_id = session('login_info.group_id');
        return true;
    }


    public function _empty()
    {
        $this->error("非法操作", 'admin.php', 1);
    }




    /**
     * 请求接口
     * @param string $url
     * @param string $param
     * @return bool|mixed
     * User: hj
     * Date: 2017-09-07 20:21:03
     */
    protected function request_post($url = '', $param = '')
    {
        // die($url.$param);
        if (empty($url) || empty($param)) {
            return false;
        }

        $try = 0;
        $curl_errno = -1;
        do {
            $postUrl = $url;
            $curlPost = $param;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $postUrl);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
            $data = curl_exec($ch);
            $curl_errno = curl_errno($ch);
            curl_close($ch);
        } while ($curl_errno > 0 && ++$try < 3);

        return $data;
    }

    /**
     * 设置API的传递参数
     * @param mixed $param
     * @return boolean
     */
    protected function getApiParams($param = array())
    {

        $key = $this->getApiToken();
        if (empty($key)) {
            return false;
        }
        $params = array(
            "user_type" => 'seller',
            "store_id" => session('store_id'),
            "key" => $key,
            "comchannel_id" => session('channel_id'),
            "client" => "web"
        );
        foreach ($param as $k => $v) {
            $params[$k] = $v;
        }

        // 请求第一种方式
        $data = "";
        foreach ($params as $k => $v) {
            $data .= "$k=" . urlencode($v) . "&";
        }

        $data = substr($data, 0, -1);

        return $data;
    }

    /**
     * 获得要调用的API的通行证
     */
    protected function getApiToken()
    {
        $Token = M('Mb_user_token');
        $where = array();
        $where['member_id'] = session('member_id');
        $key = $Token->where($where)->find();
        return $key['token'];
    }


    /**
     * 获得其他界面的补充条件
     * @param mixed $condition
     * @param mixed $where
     * @return mixed
     */
    protected function getOtherWhere($where, $condition)
    {
        if (!empty($condition) && is_array($condition)) {

            foreach ($condition as $k => $v) {
                $where[$k] = $v;
            }
        }
        return $where;
    }


    /**
     * 设置各种上限的session
     */
    protected function setLimitNumSession()
    {
        $limit = $this->getLimitNum();
        session('seller_num', $limit['seller_num']);
        session('member_num', $limit['member_num']);
        session('goods_num', $limit['goods_num']);
        session('advertise_num', $limit['advertise_num']);
        session('goods_code', $limit['goods_code']);

    }

    /**
     * 获得session
     * @param mixed $flag
     */
    protected function getLimiteNumSession($flag = false)
    {
        if ($flag) {
            $this->setLimitNumSession();
        } else {
            if (!session('?seller_num') || !session('?member_num') || !session('?goods_num')) {
                $this->setLimitNumSession();
            }
        }
    }



    /**
     * hjun
     * 2017年3月8日 09:02:16
     * 检查CURD权限
     *
     * 弃用 2017-09-09 20:40:37
     * @param string $limit 权限
     * @param int $type 请求类型，ajax还是普通请求
     * @return mixed
     */
    protected function checkCurd($limit = '', $type = 0)
    {
        return true;
    }




    protected function getIp()
    {
        $ip = '未知IP';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $this->is_ip($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $ip;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $this->is_ip($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $ip;
        } else {
            return $this->is_ip($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : $ip;
        }
    }

    protected function is_ip($str)
    {
        $ip = explode('.', $str);
        for ($i = 0; $i < count($ip); $i++) {
            if ($ip[$i] > 255) {
                return false;
            }
        }
        return preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $str);
    }



    public function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }


}