<?php

namespace Dock\Controller;

use Think\Controller;
class HpBaseController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        $sign = $this->req['sign'];
        $this->checkSign($sign);
    }

    public function checkSign($sign){
        if($sign == strtolower(md5('dock8988998'))){
            session('sign',$sign);
        }else{
        output_error('-100','签名错误','signError');
        }
    }



}