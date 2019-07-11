<?php

namespace Common\Controller;

/**
 * 接口基类
 * Class ApiController
 * @package Common\Controller
 * User: hjun
 * Date: 2018-06-19 10:16:59
 * Update: 2018-06-19 10:16:59
 * Version: 1.00
 */
class ApiController extends BaseController
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
        logWrite("API-请求头:" . jsonEncode($_SERVER));
        logWrite("API-请求参数:" . jsonEncode($this->req));
        $body = file_get_contents('php://input');
        logWrite("API-body:{$body}");
    }
}
