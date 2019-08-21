<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/6
 * Time: 9:39
 */

namespace Admin\Controller;

use Think\Verify;
class AuthController extends BaseController
{
    public function __construct()
    {
        session_start();
        parent::__construct();
        header("Content-Type:text/html;Charset=utf-8");
    }

    protected $req = null;

    public function login()
    {
        $m = M();
        if (session('admin_id') && session('admin_id') > 0) {
            $this->error("页面跳转中", 'admin.php?m=Admin&c=Index&a=index', 0);
        }
        if (IS_POST) {
            $req = $this->req;
            $verify = $req['verify'];
            $username = $req['username'];
            $password = $req['password'];
            $info = $this->loginCheck($username, $password, $verify);
            $this->init($info);
            $this->success('登录成功', U('Auth/login'));
        }
        $name = cookie('name');
        $password = $req['password'];
        $this->assign('name', $name);
        $this->assign('password', $password);
        $this->display('login');
    }

    /**
     * 退出
     * User: hj
     * Date: 2017-09-07 09:55:53
     */
    public function logout()
    {
//        if (session('admin_id') > 0) {
//            addAdminLog('退出', "管理员退出,账号:" . session('loginname'));
//        }
        session(null);
        $this->success('安全退出', U('Auth/login'));
    }

    /**
     * 获取验证码
     * User: hj
     * Date: 2017-09-07 10:18:20
     */
    public function verify()
    {
        ob_clean();
        $config = array(
            'length' => 4,
            'useCurve' => false,
            'codeSet' => '023456789',
        );
        $verify = new Verify($config);
        $verify->entry(1);
    }


    protected function loginCheck($member_name, $member_password, $verify)
    {
        // 表单检查
        if (!in_array($this->origin, $this->allow_origin)) {
            if (!check_verify($verify, 1)) $this->error("亲，验证码输错了哦！");
        }
        if (empty($member_name)) $this->error("请填写商家账号");
        if (empty($member_password)) $this->error("请输入密码");

        //调用xx登入接口
        $url = $this->getXxUrl('Login','index');
        $post_data['username'] = $member_name;
        $post_data['password'] = $member_password;
        $post_data['client'] = 'web';
        $post_data['user_type'] = 'seller';
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $return_data = httpRequest($url,'POST',$post_data,$headers);
        $log_str = "[Admin->Auth->loginCheck]  "." returndata->".json_encode($return_data)."\n".
            "post_data:".json_encode($post_data);
        xxAdmin($log_str);
        $return_info = json_decode($return_data['data'], true);

        if (!$return_info) {
            $this->error("服务器忙...");
        }
        if (!$return_info['result'] == 0) $this->error($return_info['error']);

//        logWrite("登录通过:" . json_encode($return_info, JSON_UNESCAPED_UNICODE));

        return $return_info['datas'];
    }

    protected function init($info = array())
    {
        $this->setSessionCookieCache($info);
    }


    protected function setSessionCookieCache($info)
    {
        // 如果表单选择了保存密码 则存入cookie
        $member_name = I('remember') == 1 ? $info['username'] : null;
        $member_password = I('remember') == 1 ? $info['password'] : null;
        // 设置session
        session('admin_id',$info['id']);//登入id
        session('loginname',$info['username']);//用户名
        session('key',$info['key']);//访问令牌
        session('store_id', $info['store_id']); // 店铺ID
        cookie('name', $member_name); // 账号、密码存在客户端
        cookie('password', $member_password);
        return $info;
    }

}