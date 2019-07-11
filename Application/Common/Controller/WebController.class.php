<?php

namespace Common\Controller;

/**
 * PC商城基类
 * Class WebController
 * @package Common\Controller
 * User: hjun
 * Date: 2018-06-19 10:19:08
 * Update: 2018-06-19 10:19:08
 * Version: 1.00
 */
class WebController extends BaseController
{
    /**
     * 初始化
     * BaseController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setRequestAllow();
        $this->getReqParam();
        if (IS_POST) {
            logWrite("WAP-请求头:" . jsonEncode($_SERVER));
            logWrite("WEB-请求参数:" . jsonEncode($this->req));
            $body = file_get_contents('php://input');
            logWrite("WEB-body:{$body}");
        }
        $this->assignPublicParam();
    }
}
