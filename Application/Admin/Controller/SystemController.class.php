<?php
/**
 * Created by PhpStorm.
 * User: Ydr
 * Date: 2019/8/15
 * Time: 10:34
 */

namespace Admin\Controller;


class SystemController extends AdminController
{
    public function index(){
        $rateData = M('store_credit_rate')->where(array('store_id'=>$this->store_id))->find();
        $storeData= M('store_member_bind')->where(array('store_id'=>$this->store_id))->find();
        $this->assign('rateData',$rateData);
        $this->assign('storeData',$storeData);
        $this->display('index');
    }
}