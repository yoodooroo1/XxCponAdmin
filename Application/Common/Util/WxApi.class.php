<?php

namespace Common\Util;

class WxApi extends Base
{
    // region 错误码
    // 网页授权CODE错误码
    const ERROR_INVALID_CODE = 40029;
    // endregion

    // region API地址
    // 获取设置的行业
    const GET_INDUSTRY_API = 'https://api.weixin.qq.com/cgi-bin/template/get_industry?access_token={%accessToken}';
    // 查询卡卷列表
    const GET_CARD_LIST_API = 'https://api.weixin.qq.com/card/batchget?access_token={%accessToken}';
    // 创建会员卡
    const CREATE_CARD_API = 'https://api.weixin.qq.com/card/create?access_token={%accessToken}';
    // 设置开卡时填写的表单
    const SET_ACTIVATE_USER_FORM_API = 'https://api.weixin.qq.com/card/membercard/activateuserform/set?access_token={%accessToken}';
    // 设置会员卡领取白名单
    const SET_CARD_WHITE_API = 'https://api.weixin.qq.com/card/testwhitelist/set?access_token={%accessToken}';
    // 获取开会员卡卡组件的链接
    const GET_MEMBER_CARD_API = 'https://api.weixin.qq.com/card/membercard/activate/geturl?access_token={%accessToken}';
    // 创建卡券二维码
    const CREATE_CARD_QRCODE_API = 'https://api.weixin.qq.com/card/qrcode/create?access_token={%accessToken}';
    // 删除卡卷接口
    const DELETE_CARD_API = 'https://api.weixin.qq.com/card/delete?access_token={%accessToken}';
    // 长链接转短连接
    const SHORT_URL_API = 'https://api.weixin.qq.com/cgi-bin/shorturl?access_token={%accessToken}';
    // 小程序登录
    const MINI_LOGIN_API = 'https://api.weixin.qq.com/sns/jscode2session?appid={%appId}&secret={%appSecret}&js_code={%code}&grant_type=authorization_code';
    // 通过该接口生成的小程序码，永久有效，数量暂无限制
    const MINI_CODE_UN_API = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={%accessToken}';
    // 小程序二维码 通过该接口生成的小程序码，永久有效，有数量限制
    const MINI_QR_CODE_API = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token={%accessToken}';
    // endregion

    // region 卡券ACTION
    const CARD_ACTION_ONE = 'QR_CARD'; // 扫描二维码领取单张卡券
    const CARD_ACTION_MULTI = 'QR_MULTIPLE_CARD'; // 扫描二维码领取多张卡券
    // endregion

    // region 卡券状态
    const CARD_STATUS_NOT_VERIFY = 'CARD_STATUS_NOT_VERIFY'; //  待审核
    const CARD_STATUS_VERIFY_FAIL = 'CARD_STATUS_VERIFY_FAIL'; // 审核失败
    const CARD_STATUS_VERIFY_OK = 'CARD_STATUS_VERIFY_OK'; // 通过审核
    const CARD_STATUS_DELETE = 'CARD_STATUS_DELETE'; // 卡券被商户删除
    const CARD_STATUS_DISPATCH = 'CARD_STATUS_DISPATCH'; // 在公众平台投放过的卡券
    // endregion

    // region 属性
    private $storeId;

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->__construct($storeId);
        return $this;
    }

    private $appId;
    private $appSecret;
    private $accessToken;
    private $jsTicket;
    private $cardTicket;
    // 用户授权后的信息 包括openid 和 access_token
    private $userGrant;
    // 微信配置
    private $wxConfig;

    /**
     * @return mixed
     */
    public function getWxConfig()
    {
        return $this->wxConfig;
    }

    /**
     * @param mixed $wxConfig
     */
    public function setWxConfig($wxConfig)
    {
        $this->wxConfig = $wxConfig;
    }

    /**
     * @return mixed
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @return mixed
     */
    public function getAppSecret()
    {
        return $this->appSecret;
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @return mixed
     */
    public function getJsTicket()
    {
        return $this->jsTicket;
    }

    /**
     * @return mixed
     */
    public function getCardTicket()
    {
        return $this->cardTicket;
    }

    /**
     * 判断商家公众号是否是独立的
     * @return boolean
     * User: hjun
     * Date: 2019-03-26 19:59:22
     * Update: 2019-03-26 19:59:22
     * Version: 1.00
     */
    public function isAlone()
    {
        $config = $this->getWxConfig();
        // 如果store_id大于0 说明是独立授权
        if ($config['store_id'] > 0) {
            return true;
        }
        return false;
    }

    /**
     * @var WxCard
     */
    private $wxCard;

    // endregion

    public function __construct($storeId = 0, $type = 'wx')
    {
        switch ($type) {
            case 'mini':
                $result = D('MiniUtil')->getMiniConfigInfo($storeId);
                break;
            default:
                $result = D('WxUtil')->getWxConfigInfo($storeId);
                break;
        }
        if (!isSuccess($result)) {
            logWrite("公众号错误信息：" . jsonEncode($result));
        }
        $config = $result['data'];
        if (!$this->isConfigActive($config)) {
            $this->error("请配置公众号信息");
        }
        $this->appId = $config['appid'];
        $this->appSecret = $config['appsecret'];
        $this->accessToken = $config['accesstoken'];
        $this->jsTicket = $config['ticket'];
        $this->cardTicket = $config['card_ticket'];
        $this->storeId = $storeId;
        // 此处让其加载WxCard类 否则后续很多类无法正确识别
        $this->wxCard = WxCard::CARD_TYPE_MEMBER_CARD;
        $this->setWxConfig($config);
    }

    /**
     * 判断是否成功
     * @param array $response
     * @return bool
     * User: hjun
     * Date: 2018-12-12 15:04:11
     * Update: 2018-12-12 15:04:11
     * Version: 1.00
     */
    public function isSuccess($response = [])
    {
        if (is_string($response)) {
            $response = jsonDecodeToArr($response);
        }
        return $response['errcode'] === 0 || empty($response['errcode']);
    }

    /**
     * 获取错误信息
     * @param array $response
     * @return string
     * User: hjun
     * Date: 2018-12-12 15:46:21
     * Update: 2018-12-12 15:46:21
     * Version: 1.00
     */
    public function getErrorMsg($response = [])
    {
        if (is_string($response)) {
            $response = jsonDecodeToArr($response);
        }
        return "{$response['errmsg']}:{$response['errcode']}";
    }

    /**
     * 获取返回数据
     * @param array $response
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-12 15:48:32
     * Update: 2018-12-12 15:48:32
     * Version: 1.00
     */
    private function getReturn($response = [])
    {
        if (is_string($response)) {
            $response = jsonDecodeToArr($response);
        }
        if ($this->isSuccess($response)) {
            return getReturn(CODE_SUCCESS, 'success', $response);
        }
        return getReturn(CODE_ERROR, $this->getErrorMsg($response), $response['errcode']);
    }

    /**
     * 判断配置是否有效
     * @param $config
     * @return boolean
     * User: hjun
     * Date: 2018-05-17 09:17:07
     * Update: 2018-05-17 09:17:07
     * Version: 1.00
     */
    public function isConfigActive($config)
    {
        return !empty($config['appid']) && !empty($config['appsecret']);
    }

    /**
     * 是否是无效的code
     * @param int $code
     * @return boolean
     * User: hjun
     * Date: 2018-05-17 09:48:47
     * Update: 2018-05-17 09:48:47
     * Version: 1.00
     */
    public function isInvalidCode($code = 0)
    {
        return $code === self::ERROR_INVALID_CODE;
    }

    private function get($appid, $appsecret)
    {
        if (!isset($_GET['code'])) {
            $thisurl = urlencode(getCurPageURL());
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid .
                '&redirect_uri=' . $thisurl . '&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect&connect_redirect=1';
            jump($url, 1);
        } else {
            $code = $_GET['code'];
        }
        $get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid .
            '&secret=' . $appsecret . '&code=' . $code . '&grant_type=authorization_code';
        $response = httpRequest($get_token_url)['data'];
        $response = jsonDecodeToArr($response);
        return $this->getReturn($response);
    }

    /**
     * 获取用户授权信息
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-05-17 10:02:41
     * Update: 2018-05-17 10:02:41
     * Version: 1.00
     */
    public function getUserGrantInfo()
    {
        if (isset($this->userGrant)) {
            return $this->userGrant;
        }
        $appid = $this->appId;
        $appsecret = $this->appSecret;
        // 尝试3次获取
        for ($i = 0; $i < 3; $i++) {
            $result = $this->get($appid, $appsecret);
            if (isSuccess($result)) {
                $this->userGrant = $result['data'];
                return $result['data'];
            } elseif ($this->isInvalidCode($result['data'])) {
                unset($_GET['code']);
            }
        }
        $this->error($result['msg']);
    }

    /**
     * 获取openid
     * @return string
     * User: hjun
     * Date: 2018-05-17 09:25:49
     * Update: 2018-05-17 09:25:49
     * Version: 1.00
     */
    public function getOpenId()
    {
        if (isset($this->userGrant)) {
            return $this->userGrant['openid'];
        }
        $this->getUserGrantInfo();
        return $this->userGrant['openid'];
    }

    /**
     * 获取网页授权的access_token
     * @return string
     * User: hjun
     * Date: 2018-05-17 10:05:48
     * Update: 2018-05-17 10:05:48
     * Version: 1.00
     */
    public function getGrantAccessToken()
    {
        if (isset($this->userGrant['access_token'])) {
            return $this->userGrant['access_token'];
        }
        $this->getUserGrantInfo();
        return $this->userGrant['openid'];
    }

    /**
     * 获取用户基本信息
     * @return array
     * User: hjun
     * Date: 2018-05-17 10:00:59
     * Update: 2018-05-17 10:00:59
     * Version: 1.00
     */
    public function getUserBaseInfo()
    {
        $openId = $this->getOpenId();
        $accessToken = $this->getGrantAccessToken();
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $accessToken . "&openid=" . $openId . "&lang=zh_CN";
        $response = httpRequest($url)['data'];
        $response = jsonDecodeToArr($response);
        if (!$this->isSuccess($response)) {
            $this->error($response['errmsg']);
        }
        return $response;
    }

    /**
     * 获取JSAPI的签名
     * @return array
     * User: hjun
     * Date: 2018-12-07 21:42:55
     * Update: 2018-12-07 21:42:55
     * Version: 1.00
     */
    public function getJSSign()
    {
        $config = $this->getWxConfig();
        $data = [];
        $data['jsapi_ticket'] = $config['ticket'];
        $data['noncestr'] = \Org\Util\String::randString(16);
        $data['timestamp'] = NOW_TIME;
        $data['url'] = getCurPageURL();
        ksort($data);
        $string = toUrlParam($data);
        $data['sign'] = sha1($string);
        return $data;
    }

    /**
     * 获取拉去卡券列表签名
     * @param string $cardId 指定类目的卡券列表
     * @param string $cardType 卡券类型
     * @param string $shopId 指定门店
     * @return array
     * User: hjun
     * Date: 2018-12-07 21:18:23
     * Update: 2018-12-07 21:18:23
     * Version: 1.00
     */
    public function getChooseCardSign($cardId = '', $cardType = WxCard::CARD_TYPE_MEMBER_CARD, $shopId = '')
    {
        $config = $this->getWxConfig();
        $data = [];
        $data['card_id'] = $cardId;
        $data['card_type'] = $cardType;
        $data['api_ticket'] = $config['card_ticket'];
        $data['app_id'] = $config['appid'];
        $data['location_id'] = $shopId;
        $data['time_stamp'] = NOW_TIME;
        $data['nonce_str'] = \Org\Util\String::randString(32);
        $cardSdk = new Signature();
        foreach ($data as $key => $value) {
            $cardSdk->add_data($value);
        }
        $data['sign'] = $cardSdk->get_signature();
        return $data;
    }

    /**
     * 获取添加卡券的签名
     * @param string $cardId
     * @param string $outerStr 场景值
     * @param string $code 卡券CODE码
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-12 16:20:52
     * Update: 2018-12-12 16:20:52
     * Version: 1.00
     */
    public function getAddCardSign($cardId = '', $outerStr = '', $code = '')
    {
        $config = $this->getWxConfig();
        $data = [];
        $data['card_id'] = $cardId;
        $data['api_ticket'] = $config['card_ticket'];
        $data['time_stamp'] = NOW_TIME;
        $data['nonce_str'] = \Org\Util\String::randString(32);
        $data['code'] = $code;
        $cardSdk = new Signature();
        foreach ($data as $key => $value) {
            $cardSdk->add_data($value);
        }
        $data['sign'] = $cardSdk->get_signature();
        $ext = [
            'timestamp' => $data['time_stamp'],
            'nonce_str' => $data['nonce_str'],
            'signature' => $data['sign'],
            'outer_str' => $outerStr,
            'code' => $data['code'],
        ];
        $data['cardExt'] = jsonEncode($ext);
        return $data;
    }

    /**
     * 获取行业信息
     * User: hjun
     * Date: 2018-12-07 11:04:04
     * Update: 2018-12-07 11:04:04
     * Version: 1.00
     */
    public function getIndustry()
    {
        $wxConfig = $this->getWxConfig();
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::GET_INDUSTRY_API);
        $response = httpRequest($url, 'GET')['data'];
        return $this->getReturn($response);
    }

    /**
     * 获取通过审核的会员卡
     * @param int $page
     * @param int $limit
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-13 14:25:00
     * Update: 2018-12-13 14:25:00
     * Version: 1.00
     */
    public function getVerifyOkCardList($page = 1, $limit = 10)
    {
        $status = [self::CARD_STATUS_VERIFY_OK];
        return $this->getCardList($page, $limit, $status);
    }

    /**
     * 获取有效的卡券列表 待审核、审核通过
     * @param int $page
     * @param int $limit
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-12 15:18:51
     * Update: 2018-12-12 15:18:51
     * Version: 1.00
     */
    public function getActiveCardList($page = 1, $limit = 10)
    {
        $status = [self::CARD_STATUS_NOT_VERIFY, self::CARD_STATUS_VERIFY_OK, self::CARD_STATUS_DISPATCH];
        return $this->getCardList($page, $limit, $status);
    }

    /**
     * 获取无效的卡券列表
     * @param int $page
     * @param int $limit
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-12 15:20:04
     * Update: 2018-12-12 15:20:04
     * Version: 1.00
     */
    public function getInvalidCardList($page = 1, $limit = 10)
    {
        $status = [self::CARD_STATUS_VERIFY_FAIL, self::CARD_STATUS_DELETE];
        return $this->getCardList($page, $limit, $status);
    }

    /**
     * 获取卡卷列表
     * @param int $page
     * @param int $limit
     * @param array $status
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-07 18:15:48
     * Update: 2018-12-07 18:15:48
     * Version: 1.00
     */
    public function getCardList($page = 1, $limit = 10, $status = [self::CARD_STATUS_VERIFY_OK, self::CARD_STATUS_NOT_VERIFY])
    {
        $wxConfig = $this->getWxConfig();
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::GET_CARD_LIST_API);
        $data = [];
        $data['offset'] = $page - 1;
        $data['count'] = $limit;
        $data['status_list'] = $status;
        $response = httpRequest($url, 'POST', jsonEncode($data))['data'];
        return $this->getReturn($response);
    }

    /**
     *
     * @param string $jsonData
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-06 23:20:14
     * Update: 2018-12-06 23:20:14
     * Version: 1.00
     */
    public function createCard($jsonData = '')
    {
        $wxConfig = $this->getWxConfig();
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::CREATE_CARD_API);
        $response = httpRequest($url, 'POST', $jsonData)['data'];
        return $this->getReturn($response);
    }

    /**
     * 设置开卡表单
     * @param string $json
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-12 15:40:58
     * Update: 2018-12-12 15:40:58
     * Version: 1.00
     */
    public function setActiveUserForm($json = '')
    {
        $wxConfig = $this->getWxConfig();
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::SET_ACTIVATE_USER_FORM_API);
        $response = httpRequest($url, 'POST', $json)['data'];
        return $this->getReturn($response);
    }

    /**
     * 设置测试白名单
     * @param array $openIds
     * @param array $wxNumbers
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-07 15:35:48
     * Update: 2018-12-07 15:35:48
     * Version: 1.00
     */
    public function setCardWhite($openIds = [], $wxNumbers = [])
    {
        $wxConfig = $this->getWxConfig();
        $data = [];
        if (!empty($openIds) && is_array($openIds)) {
            $data['openid'] = $openIds;
        }
        if (!empty($wxNumbers) && is_array($wxNumbers)) {
            $data['username'] = $wxNumbers;
        }
        $json = jsonEncode($data);
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::SET_CARD_WHITE_API);
        $response = httpRequest($url, 'POST', $json)['data'];
        return $this->getReturn($response);
    }

    /**
     * 获取领取单张卡券的二维码
     * @param string $cardId 卡券ID
     * @param string $code 卡券标识
     * @param string $outerStr 场景标识
     * @return array
     * User: hjun
     * Date: 2018-12-07 15:44:29
     * Update: 2018-12-07 15:44:29
     * Version: 1.00
     */
    public function getTakeOneCardQRCode($cardId = '', $code = '', $outerStr = '')
    {
        $wxConfig = $this->getWxConfig();
        $data = [
            'action_name' => self::CARD_ACTION_ONE,
            'action_info' => [
                'card' => [
                    'card_id' => $cardId,
                    'code' => $code,
                    'outer_str' => $outerStr
                ],
            ],
        ];
        $json = jsonEncode($data);
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::CREATE_CARD_QRCODE_API);
        $response = httpRequest($url, 'POST', $json)['data'];
        return $this->getReturn($response);
    }

    /**
     * 获取开卡链接
     * @param string $cardId 卡券ID
     * @param string $outerStr 场景值
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-06 20:37:38
     * Update: 2018-12-06 20:37:38
     * Version: 1.00
     */
    public function getUserCreateMemberCardUrl($cardId = '', $outerStr = '')
    {
        $wxConfig = $this->getWxConfig();
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::GET_MEMBER_CARD_API);
        $data = [
            'card_id' => $cardId,
            'outer_str' => $outerStr
        ];
        $response = httpRequest($url, 'post', jsonEncode($data))['data'];
        return $this->getReturn($response);
    }

    /**
     * 删除卡券
     * @param string $cardId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-12 14:48:35
     * Update: 2018-12-12 14:48:35
     * Version: 1.00
     */
    public function deleteCard($cardId = '')
    {
        $wxConfig = $this->getWxConfig();
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::DELETE_CARD_API);
        $data = [
            'card_id' => $cardId,
        ];
        $response = httpRequest($url, 'post', jsonEncode($data))['data'];
        return $this->getReturn($response);
    }

    /**
     * 短连接
     * @param string $longUrl
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-28 12:28:21
     * Update: 2018-12-28 12:28:21
     * Version: 1.00
     */
    public function getShortUrl($longUrl = '')
    {
        $wxConfig = $this->getWxConfig();
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::SHORT_URL_API);
        $data = [];
        $data['action'] = 'long2short';
        $data['long_url'] = $longUrl;
        $response = httpRequest($url, 'post', jsonEncode($data))['data'];
        return $this->getReturn($response);
    }

    /**
     * 获取小程序登录信息
     * @param string $code
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2019-03-11 12:10:59
     * Update: 2019-03-11 12:10:59
     * Version: 1.00
     */
    public function getMiniLoginInfo($code = '')
    {
        $config = $this->getWxConfig();
        $url = str_replace('{%appId}', $config['appid'], self::MINI_LOGIN_API);
        $url = str_replace('{%appSecret}', $config['appsecret'], $url);
        $url = str_replace('{%code}', $code, $url);
        $response = httpRequest($url, 'GET')['data'];
        return $this->getReturn($response);
    }

    /**
     * 获取小程序码，适用于需要的码数量极多的业务场景。通过该接口生成的小程序码，永久有效，数量暂无限制
     * @param string $scene
     * 最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~，
     * 其它字符请自行编码为合法字符（因不支持%，中文无法使用 urlencode 处理，请使用其他编码方式）
     * @param string $page
     * 必须是已经发布的小程序存在的页面（否则报错），
     * 例如 pages/index/index, 根路径前不要填加 /,不能携带参数（参数请放在scene字段里），如果不填写这个字段，默认跳主页面
     * @param string $width 二维码的宽度，单位 px，最小 280px，最大 1280px
     * @param string $autoColor 自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调，默认 false
     * @param string $lineColor auto_color 为 false 时生效，使用 rgb 设置颜色 例如 {"r":"xxx","g":"xxx","b":"xxx"} 十进制表示
     * @param string $isHyaline 是否需要透明底色，为 true 时，生成透明底色的小程序
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2019-03-19 11:48:31
     * Update: 2019-03-19 11:48:31
     * Version: 1.00
     */
    public function getMiniCodeUN($scene = '', $page = '', $width = '', $autoColor = '', $lineColor = '', $isHyaline = '')
    {
        $config = $this->getWxConfig();
        $url = str_replace('{%accessToken}', $config['accesstoken'], self::MINI_CODE_UN_API);
        $data = [];
        $data['scene'] = $scene;
        if (!empty($page)) {
            $data['page'] = $page;
        }
        if (!empty($width)) {
            $data['width'] = $width;
        }
        if (!empty($autoColor)) {
            $data['auto_color'] = $autoColor;
        }
        if (!empty($lineColor)) {
            $data['line_color'] = $lineColor;
        }
        if (!empty($isHyaline)) {
            $data['is_hyaline'] = $isHyaline;
        }
        $response = httpRequest($url, 'POST', jsonEncode($data))['data'];
        return $response;
    }

    /**
     * 获取小程序二维码
     * 数量限制100,000
     * @param string $page
     * 扫码进入的小程序页面路径，最大长度 128 字节，不能为空；对于小游戏，可以只传入 query 部分，来实现传参效果，如：传入 "?foo=bar"，即可在 wx.getLaunchOptionsSync 接口中的 query 参数获取到 {foo:"bar"}。
     * @param array $params 参数
     * @param string $width 二维码的宽度，单位 px。最小 280px，最大 1280px
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2019-04-23 15:34:07
     * Update: 2019-04-23 15:34:07
     * Version: 1.00
     */
    public function getMiniQRCode($page = '', $params = [], $width = '')
    {
        $config = $this->getWxConfig();
        $url = str_replace('{%accessToken}', $config['accesstoken'], self::MINI_QR_CODE_API);
        $data = [];
        $data['path'] = $page;
        if (!empty($params)) {
            $query = http_build_query($params);
            $data['path'] = "{$page}?{$query}";
        }
        if (!empty($width)) {
            $data['width'] = $width;
        }
        $response = httpRequest($url, 'POST', jsonEncode($data))['data'];
        return $response;
    }
}