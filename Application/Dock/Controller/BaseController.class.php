<?php
/**
 * Created by PhpStorm.
 * User: Ydr
 * Date: 2019/7/26
 * Time: 14:30
 */

namespace Dock\Controller;
use Think\Controller;
define('HP_ADDBINDMEMBER',207);//添加或绑定会员信息
define("HP_GETSTORE", 107); //获取门店信息
define("HP_GETGOODS", 301); //获取门店的商品信息
define("HP_GETGOODS_CLASS", 302); //获取商品的分类
define("HP_GETGOODS_STOCK", 320); //获取商品的库存
define("HP_ADDMEMBER", 203); //新增会员
define("HP_CREDITCHANGE", 208); //积分转换
define("HP_GETCREDIT", 205); //会员积分查询
define('HP_REGISTE&BIND',207);//会员注册或绑定
define("HP_GETGOODS_PIC", 304); //商品图片
define("HP_SAVEORDER", 401);  //订单
define("HP_GETMEMBER", 201);  //获取用户信息
define("HP_GETOFFLINECOUPON",501);  //查询线下优惠卷
define("HP_OFFLINE",502);//线上转线下优惠卷
define("HP_ONLINE",503);//线下作废转为线上
class BaseController  extends Controller\RestController
{
    protected $req = null;
    public $hp_base_url = "http://www.hotplutus.cn:9090/hpapi";
    protected $config = array();

    // 请求参数数组
    protected $reqGet = array();
    protected $reqPost = array();
    protected $reqBody = array();

    public function __construct()
    {
        parent::__construct();
        $this->setRequestAllow();
        $this->getReqParam();
        $this->assignPublicParam();
    }

    public function getXxUrl($act, $op)
    {
        $apiUrl = XX_API . "/index.php?act=" . $act . "&op=" . $op;
        return $apiUrl;
    }

    //浩普平台返回验证
    public function hpResChecking($return_arr){
        if ($return_arr['result']['code'] === "0") {
            return $return_arr;
        } else {
            die($return_arr['result']['msg']);
        }
    }

    protected function initSystem()
    {
        $this->reqGet = $this->req;
        $this->reqPost = $this->req;
        $this->reqBody = I('put.');
    }


    protected function getReqParam()
    {
        if (isset($this->req)) {
            return $this->req;
        }
        $this->req = getRequest();
        return $this->req;
    }


    public function assignPublicParam()
    {
        $this->assign('req', $this->req);
        $this->assign('reqJson', json_encode($this->req));
        $this->assign('NOW_STR', date('Y-m-d'));
        $this->assign('NEXT_YEAR_STR', date('Y-m-d', strtotime('+1 year')));
        $this->assign('V', EXTRA_VERSION);
    }


    protected function setHeaderAllowOrigin($origin)
    {
        header("Access-Control-Allow-Headers:x-requested-with,content-type,Authorization,token");
        header('Access-Control-Allow-Credentials: true'); // 控制是否开启与Ajax的Cookie提交方式'
        header('Access-Control-Allow-Origin:' . $origin);
    }

    protected function setRequestAllow()
    {
        // 跨域访问以及session的保持
        $this->origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        if (in_array($this->origin, $this->allow_origin)) {
            $this->setHeaderAllowOrigin($this->origin);
        }
    }
}