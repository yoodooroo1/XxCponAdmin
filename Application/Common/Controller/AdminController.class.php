<?php

namespace Common\Controller;

/**
 * 后台基类
 * Class AdminController
 * @package Common\Controller
 * User: hjun
 * Date: 2018-06-19 10:16:36
 * Update: 2018-06-19 10:16:36
 * Version: 1.00
 */
class AdminController extends BaseController
{
    /**
     * 初始化
     * BaseController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setRequestAllow();
        $this->initAuthBySessionId();
        $this->getReqParam();
        logWrite("ADMIN-请求头:" . jsonEncode($_SERVER));
        logWrite("ADMIN-请求参数:" . jsonEncode($this->req));
        $body = file_get_contents('php://input');
        logWrite("ADMIN-body:{$body}");
        $this->assignPublicParam();
    }
}
