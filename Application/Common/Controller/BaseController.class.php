<?php

namespace Common\Controller;

use Think\Build;
use Think\Controller;

/**
 * 公共基础控制器 所有模块内的基础控制器都继承该控制器
 * 这里主要做一些公共的事
 * Class BaseController
 * @package Common\Controller
 * User: hjun
 * Date: 2018-03-29 01:17:57
 * Update: 2018-03-29 01:17:57
 * Version: 1.00
 */
class BaseController extends Controller\RestController
{
    // 访问的外部域名
    protected $origin = '';

    // 允许的域名
    protected $allow_origin = [
        'http://localhost:8080',
        'http://www.sosoapi.com',
    ];

    // 允许的域名模糊判断
    protected $allow_origin_needles = [
        'duinin.com'
    ];

    // 请求参数
    protected $req = null;

    /**
     * 初始化
     * BaseController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        if (REQUEST_METHOD === 'OPTIONS') {
            $this->setRequestAllow();
            $this->apiResponse(getReturn(CODE_SUCCESS));
        }
    }

    /**
     * 头部设置
     * @param string $origin
     * User: hjun
     * Date: 2018-06-19 11:35:21
     * Update: 2018-06-19 11:35:21
     * Version: 1.00
     */
    protected function setHeaderAllowOrigin($origin)
    {
        header("Access-Control-Allow-Headers:x-requested-with,content-type,Authorization,token");
        header('Access-Control-Allow-Credentials: true'); // 控制是否开启与Ajax的Cookie提交方式'
        header('Access-Control-Allow-Origin:' . $origin);
    }

    /**
     * 身份认证
     * User: hjun
     * Date: 2018-03-31 23:16:21
     * Update: 2018-03-31 23:16:21
     * Version: 1.00
     */
    protected function setRequestAllow()
    {
        // 跨域访问以及session的保持
        $this->origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        if (in_array($this->origin, $this->allow_origin)) {
            $this->setHeaderAllowOrigin($this->origin);
        } else {
            foreach ($this->allow_origin_needles as $needle) {
                if (strpos($this->origin, $needle) !== false) {
                    $this->setHeaderAllowOrigin($this->origin);
                    break;
                }
            }
        }
    }

    /**
     * 如果有sessionId 则初始化session
     * User: hjun
     * Date: 2018-06-19 10:37:20
     * Update: 2018-06-19 10:37:20
     * Version: 1.00
     */
    protected function initAuthBySessionId()
    {
        // 跨域访问时需要携带sessionID
        $sessionId = I('get.token');
        // 先TP中已经自动session_start() 当时的session_id与跨域的不同 所以需要先销毁
        if (!empty($sessionId)) {
            session_destroy();
            session_id($sessionId);
            session_start();
        }
    }

    /**
     * 获取当前请求参数
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-04-01 01:42:55
     * Update: 2018-04-01 01:42:55
     * Version: 1.00
     */
    protected function getReqParam()
    {
        if (isset($this->req)) {
            return $this->req;
        }
        $this->req = getRequest();
        return $this->req;
    }

    /**
     * 赋值一些通用的参数
     * User: hjun
     * Date: 2018-05-30 12:05:40
     * Update: 2018-05-30 12:05:40
     * Version: 1.00
     */
    public function assignPublicParam()
    {
        $this->assign('req', $this->req);
        $this->assign('reqJson', json_encode($this->req));
        $this->assign('NOW_STR', date('Y-m-d'));
        $this->assign('NEXT_YEAR_STR', date('Y-m-d', strtotime('+1 year')));
        $this->assign('V', EXTRA_VERSION);
    }

    /**
     * 响应函数。默认使用JSON
     * @param $data
     * @param string $type
     * @param int $code HTTP状态
     * @param int $json_option json编码选项
     * Author: hj
     * Desc:
     * Date: 2017-11-23 19:28:36
     * Update: 2017-11-23 19:28:36
     * Version: 1.0
     */
    protected function apiResponse($data, $type = 'json', $code = 200, $json_option = 256)
    {
        $this->sendHttpStatus($code);
        if (empty($data)) $data = '';
        // 如果是API接口 则去掉多余的数据
        if (is_array($data) && strtolower(MODULE_NAME) === 'api') {
            if (!empty($data['data'])) {
                $data['datas'] = $data['data'];
            }
            unset($data['data']);
        }
        if (APP_DEBUG && is_array($data)) {
            $data['time'] = G('a', 'b') . 's';
        }
        if ('json' == $type) {
            // 返回JSON数据格式到客户端 包含状态信息
            $data = json_encode($data, $json_option);
        } elseif ('xml' == $type) {
            // 返回xml格式数据
            $data = xml_encode($data);
        } elseif ('php' == $type) {
            $data = serialize($data);
        }// 默认直接输出
        $this->setContentType($type);
        exit($data);
    }

    /**
     * 重写redirect
     * @param string $url
     * @param array $params
     * @param int $delay
     * @param string $msg
     * @return void
     * User: hjun
     * Date: 2018-03-31 23:50:54
     * Update: 2018-03-31 23:50:54
     * Version: 1.00
     */
    public function redirect($url, $params = array(), $delay = 0, $msg = '')
    {
        if (IS_AJAX) {
            $url = U($url, $params);
            $this->apiResponse(getReturn(CODE_REDIRECT, L('_OPERATION_SUCCESS_'), $url));
        } else {
            parent::redirect($url, $params, $delay, $msg);
        }
    }

    /**
     * 重写success
     * @param string $message
     * @param string $jumpUrl
     * @param bool $ajax
     * User: hjun
     * Date: 2018-03-31 23:30:17
     * Update: 2018-03-31 23:30:17
     * Version: 1.00
     */
    public function success($message = '', $jumpUrl = '', $ajax = false)
    {
        if (IS_AJAX) {
            $this->apiResponse(['debug' => $message, 'result' => 0,'code' => 200, 'msg' => $message, 'datas' => ['url' => $jumpUrl]]);

        } else {
            parent::success($message, $jumpUrl, $ajax);
        }
    }

    /**
     * 重写error
     * @param string $message
     * @param string $jumpUrl
     * @param bool $ajax
     * User: hjun
     * Date: 2018-03-31 23:30:17
     * Update: 2018-03-31 23:30:17
     * Version: 1.00
     */
    public function error($message = '', $jumpUrl = '', $ajax = false)
    {
        if (IS_AJAX) {
            $this->apiResponse(['info' => $message, 'status' => 0, 'url' => $jumpUrl, 'code' => CODE_ERROR, 'msg' => $message, 'data' => ['url' => $jumpUrl]]);
        } else {
            parent::error($message, $jumpUrl, $ajax);
        }
    }
}
