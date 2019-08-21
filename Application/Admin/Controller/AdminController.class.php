<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/6
 * Time: 17:41
 */

namespace Admin\Controller;


/**
 * XUNXIN PC 后台管理 登入管理
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业
 * 目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: AuthController.class.php
 */
class AdminController extends BaseController
{

    // ajax返回格式
    protected $result = array(
        'code' => -1,
        'msg' => '失败',
        'data' => null,
        'comment' => '备注说明'
    );
    // 请求参数数组
    protected $reqGet = array();
    protected $reqPost = array();
    protected $reqBody = array();
    //====================其他变量================//


    public function __construct()
    {
        session_start();
        parent::__construct();
        header("Content-Type:text/html;Charset=utf-8");
        if (session('admin_id') > 0) {
//             初始化系统
            $this->initSystem();
        } else {
            session(null);
            header("Location:" . U('Auth/login'));
            exit;
        }
    }




    public function __destruct()
    {
        parent::__destruct();
    }


}