<?php

namespace Dock\Controller;

use Think\Controller;

define("HP_GETSTORE", 107); //获取门店信息
define("HP_GETGOODS", 301); //获取门店的商品信息
define("HP_GETGOODS_CLASS", 302); //获取商品的分类
define("HP_GETGOODS_STOCK", 320); //获取商品的库存
define("HP_ADDMEMBER", 203); //新增会员
define("HP_GETGOODS_PIC", 304); //商品图片
define("HP_SAVEORDER", 401);  //订单
define("HP_GETMEMBER", 201);  //获取用户信息
define("HP_GETOFFLINECOUPON",501);  //查询线下优惠卷
define("HP_OFFLINE",502);//线上转线下优惠卷
define("HP_ONLINE",503);//线下转线上优惠卷

class HpBaseController extends Controller\RestController
{

    protected $base_url = "http://www.hotplutus.cn:9090/hpapi";
    protected $config = array();
    protected $req = null;

    // 请求参数数组
    protected $reqGet = array();
    protected $reqPost = array();
    protected $reqBody = array();

    public function __construct()
    {
        $this->config = C('HP');
        parent::__construct();
        $this->getReqParam();
        $this->assignPublicParam();
    }

    public function getXxUrl($act, $op)
    {
        $apiUrl = XXID . "/index.php?act=" . $act . "&op=" . $op;
        return $apiUrl;
    }

    public function getMemberInfo(){
        $member_info = M('member_bind')->select();
        return $member_info;
    }

    public function checkResult($return_arr){
        if ($return_arr['result']['code'] === "0") {

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

}