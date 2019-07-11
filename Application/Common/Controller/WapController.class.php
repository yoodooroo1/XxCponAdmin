<?php

namespace Common\Controller;

/**
 * 微信商城基类
 * Class WapController
 * @package Common\Controller
 * User: hjun
 * Date: 2018-06-19 10:18:41
 * Update: 2018-06-19 10:18:41
 * Version: 1.00
 */
class WapController extends BaseController
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
            logWrite("WAP-请求参数:" . jsonEncode($this->req));
            $body = file_get_contents('php://input');
            logWrite("WAP-body:{$body}");
        }
        $this->assignPublicParam();
    }
}
