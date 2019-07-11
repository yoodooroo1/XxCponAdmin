<?php

namespace Common\Controller;

/**
 * 总后台基类
 * Class SuperController
 * @package Common\Controller
 * User: hjun
 * Date: 2018-06-19 10:18:20
 * Update: 2018-06-19 10:18:20
 * Version: 1.00
 */
class SuperController extends BaseController
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
        $this->assignPublicParam();
    }
}
