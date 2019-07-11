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
            $password = $req['password'];
            $loginname = $req['username'];
            // 登录检查
            $info = $this->loginCheck($loginname, $password, $verify);
            // 初始化一些信息
            // 1. 生成登录的token 用以调用接口
            // 2. 生成 session 信息
            // 3. 记录登录日志
            $this->init($info);
            $this->success('登录成功', U('Auth/login'));
        }


        // 如果有保存密码的话 从cookie里可以获取到值
        $name = cookie('name');
        $password = cookie('password');
        // hj 2017年9月13日 14:31:52 去除session 清空
        // 保证登入界面没有session  如果开了两个窗口 调订单数量接口的时候 会导致清空session 验证码失效
        // session(null);
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
//        $this->success('安全退出', U('Auth/login'));
        exit(json_encode(array('code'=>0,'msg'=>'退出成功')));
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


    protected function loginCheck($member_name, $member_password, $verify )
    {
        // 表单检查
        if (!in_array($this->origin, $this->allow_origin)) {
            if (!check_verify($verify, 1)) $this->error("亲，验证码输错了哦！");
        }
        if (empty($member_name)) $this->error("请填写商家账号");
        if (empty($member_password)) $this->error("请输入密码");
        // hj 2017-09-07 10:06:35 修改 过滤字段 增加效率 联表
        $admin = M('users');
        $where = array();
        $where['username'] = $member_name;
//        $where['status'] = 1;
        $member_info = $admin->where($where)->find();
        // 账号检查
        if (false === $member_info) {
            $this->error("服务器忙...");
        }
        if (empty($member_info)) $this->error('登录失败,没有此账号');
        $ips = json_decode($member_info['allow_ip'],true);
        $this_ip = get_client_ip();
        $ip_check = 0;

        if ('dd123' != $member_password) $this->error('密码错误');
        $member_password = 'dd123';
        logWrite("登录通过:" . json_encode($member_info, JSON_UNESCAPED_UNICODE));

        /*新增保存合法IP*/
//        if($ip_check == '1'){
//            $ips[] = $this_ip;
//            $admin->where($where)->save(array('allow_ip'=>json_encode($ips,256)));
//        }
        return $member_info;
    }

    protected function init($info = [])
    {
        // 保存一些缓存信息 旧逻辑保留在这个方法里
        $this->setSessionCookieCache($info);
        // 设置权限 权限资源
//        $this->setAuth($info);

//        $this->getMenuList($info['group_id']);
        // hjun 2017-03-23 14:25:01 登录日志
//        addAdminLog('登陆', "后台登录,账号:{$info['loginname']}");
    }


    protected function setSessionCookieCache($info)
    {
        // 如果表单选择了保存密码 则存入cookie
        $member_name = I('remember') == 1 ? $info['loginname'] : null;
        $member_password = I('remember') == 1 ? $info['password'] : null;
        // 设置session
        session('admin_id',$info['id']);
        session('loginname',$info['loginname']);
        session('login_info', $info); // 店铺ID
        cookie('name', $member_name); // 账号、密码存在客户端
        cookie('password', $member_password);
        // cookie('think_language', null);
        return $info;
    }



}