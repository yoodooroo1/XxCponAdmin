<?php
/**
 * Created by PhpStorm.
 * User: Ydr
 * Date: 2019/7/26
 * Time: 14:30
 */

namespace Dock\Controller;
use Think\Controller;

class BaseController  extends Controller\RestController
{
    protected $req = null;

    // 请求参数数组
    protected $reqGet = array();
    protected $reqPost = array();
    protected $reqBody = array();

    public function __construct()
    {
        parent::__construct();
        $this->getReqParam();
        $this->assignPublicParam();
    }
    //获取讯信绑定信息
    public function getXxBindInfo(){
        $member_info = M('member_bind')->where(array('state'=>1))->select();
        return $member_info;
    }

    public function getXxUrl($act, $op)
    {
        $apiUrl = XX_API . "/index.php?act=" . $act . "&op=" . $op;
        return $apiUrl;
    }

    public function getSignByBindInfo($store_id,$fmch_id){

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