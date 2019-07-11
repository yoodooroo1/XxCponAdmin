<?php

namespace Common\Model;

use Think\Cache\Driver\Redis;

class WxUtilModel extends BaseModel
{
    protected $tableName = 'mb_wxconfig';

    // 获取access_token的接口地址
    const ACCESS_TOKEN_API = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={%appId}&secret={%appSecret}";
    // 获取ticket 接口地址
    const TICKET_API = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token={%accessToken}";
    // 获取关注列表的openId
    const SUBSCRIBE_API = "https://api.weixin.qq.com/cgi-bin/user/get?access_token={%accessToken}&next_openid={%nextOpenid}";
    // 创建带参数二维码的接口地址
    const QRCODE_API = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={%accessToken}';
    // 获取模版ID的接口地址
    const TEMPLATE_ID_API = 'https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token={%accessToken}';
    // 发送模版消息接口
    const SEND_TEMPLATE_API = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={%accessToken}';
    // 删除模版消息接口
    const DEL_TEMPLATE_API = 'https://api.weixin.qq.com/cgi-bin/template/del_private_template?access_token={%accessToken}';
    // 获取模版消息列表
    const TEMPLATE_LIST_API = 'https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token={%accessToken}';
    // 获取卡券ticket
    const CARD_TICKET_PAI = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={%accessToken}&type=wx_card';

    /**
     * 判断是否成功
     * @param array $response
     * @return bool
     * User: hjun
     * Date: 2018-12-12 15:04:11
     * Update: 2018-12-12 15:04:11
     * Version: 1.00
     */
    private function isSuccess($response = [])
    {
        if (is_string($response)) {
            $response = jsonDecodeToArr($response);
        }
        return $response['errcode'] === 0 || empty($response['errcode']);
    }

    /**
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc:  获取商家的微信配置信息 包括 appId appSecret accessToken ticket
     * Date: 2017-11-14 10:25:25
     * Update: 2017-11-14 10:25:26
     * Version: 1.0
     */
    public function getWxConfigInfo($storeId = 0)
    {
        if (empty($storeId)) {
            $storeId = 0;
        } else {
            $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
            if (!($storeInfo['has_wx_config'] == 1)) {
                $storeId = 0;
            }
        }
        $key = md5("{$storeId}_wxConfig");
        $redis = Redis::getInstance();
        $config = $redis->hGetAll($key);
        if (empty($config['appid']) || empty($config['appsecret'])) {
            // 先获取真实的商家ID
            if ($storeId > 0) {
                $info = D('Store')->getStoreInfo($storeId)['data'];
                $storeId = $info['main_store_id'];
            }
            $where = [];
            $where['store_id'] = $storeId > 0 ? $storeId : 0;
            $where['appid'] = ['neq', ''];
            $where['appsecret'] = ['neq', ''];
            $where['isdelete'] = 0;
            $field = [
                'store_id,token,appid,appsecret',
                'accesstoken,accesstoken_timeout',
                'ticket,ticket_timeout'
            ];
            $field = implode(',', $field);
            $options = [];
            $options['where'] = $where;
            $options['field'] = $field;
            $result = $this->queryRow($options);
            if ($result['code'] !== 200) return $result;
            $config = $result['data'];
            // 没有配置则查找迅信的
            if (empty($config)) {
                $where['store_id'] = 0;
                $options['where'] = $where;
                $result = $this->queryRow($options);
                if ($result['code'] !== 200) return $result;
                $config = $result['data'];
            }
            // 如果商家有自己的接口 则通过商家自己接口获取access_token
            $config['access_token_api'] = $info['access_token_api'];
            $config['ticket_api'] = $info['ticket_api'];
            $config['card_ticket_api'] = $info['card_ticket_api'];
            $redis->hMset($key, $config);
        }
        // 更新accessToken 以及 ticket
        $result = $this->updateAccessToken($config);
        if ($result['code'] !== 200) return $result;
        $config = $result['data'];
        // 更新 ticket
        $result = $this->updateTicket($config);
        if ($result['code'] !== 200) return $result;
        $config = $result['data'];
        // 更新card_ticket
        $result = $this->updateCardTicket($config);
        if ($result['code'] !== 200) return $result;
        $config = $result['data'];
        return getReturn(200, '', $config);
    }

    /**
     * @param array $wxConfig
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 更新微信accessToken
     * Date: 2017-11-14 10:30:14
     * Update: 2017-11-14 10:30:15
     * Version: 1.0
     */
    public function updateAccessToken($wxConfig = [])
    {
        if (!empty($wxConfig['access_token_api'])) {
            $result = httpRequest($wxConfig['access_token_api'], 'get');
            $data = jsonDecodeToArr($result['data']);
            if (empty($data['data'])) {
                return getReturn(-1, "request error", $wxConfig);
            }
            $wxConfig['accesstoken'] = $data['data'];
        } else {
            // 如果access_token已经过期 则更新
            if ($wxConfig['accesstoken_timeout'] < NOW_TIME) {
                $url = str_replace('{%appId}', $wxConfig['appid'], self::ACCESS_TOKEN_API);
                $url = str_replace('{%appSecret}', $wxConfig['appsecret'], $url);
                $result = httpRequest($url, 'get');
                $data = jsonDecodeToArr($result['data']);
                if (empty($data['access_token'])) {
                    return getReturn(-1, $data['errcode'] . ':' . $data['errmsg'], $wxConfig);
                }
                $wxConfig['accesstoken'] = $data['access_token'];
                $wxConfig['accesstoken_timeout'] = NOW_TIME + $data['expires_in'] - 200;
                // 更新数据库和缓存
                $data = [];
                $data['accesstoken'] = $wxConfig['accesstoken'];
                $data['accesstoken_timeout'] = $wxConfig['accesstoken_timeout'];
                $where = [];
                $where['appid'] = $wxConfig['appid'];
                $where['appsecret'] = $wxConfig['appsecret'];
                $options = [];
                $options['where'] = $where;
                $result = $this->saveData($options, $data);
                if ($result['code'] !== 200) {
                    return getReturn(CODE_ERROR, $result['msg'], $wxConfig);
                }
                $redis = Redis::getInstance();
                $key = md5("{$wxConfig['store_id']}_wxConfig");
                $redis->hSet($key, 'accesstoken', $wxConfig['accesstoken']);
                $redis->hSet($key, 'accesstoken_timeout', $data['accesstoken_timeout']);
            }
        }
        return getReturn(200, '', $wxConfig);
    }

    /**
     * @param array $wxConfig
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 更新 ticket
     * Date: 2017-11-14 11:40:28
     * Update: 2017-11-14 11:40:29
     * Version: 1.0
     */
    public function updateTicket($wxConfig = [])
    {
        if (!empty($wxConfig['ticket_api'])) {
            $result = httpRequest($wxConfig['ticket_api'], 'get');
            $data = jsonDecodeToArr($result['data']);
            if (empty($data['data'])) {
                return getReturn(-1, "request error", $wxConfig);
            }
            $wxConfig['ticket'] = $data['data'];
        } else {
            // 如果过期了 重新获取
            if ($wxConfig['ticket_timeout'] < NOW_TIME) {
                // 获取前保证access_token最新
                $result = $this->updateAccessToken($wxConfig);
                if ($result['code'] !== 200) return $result;
                $wxConfig = $result['data'];
                $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::TICKET_API);
                $result = httpRequest($url, 'get');
                $data = jsonDecodeToArr($result['data']);
                if (!$this->isSuccess($data)) {
                    return getReturn(CODE_ERROR, $data['errmsg'] . ':' . $data['errcode'], $wxConfig);
                }
                $wxConfig['ticket'] = $data['ticket'];
                $wxConfig['ticket_timeout'] = NOW_TIME + $data['expires_in'] - 200;
                // 更新数据库和缓存
                $data = [];
                $data['ticket'] = $wxConfig['ticket'];
                $data['ticket_timeout'] = $wxConfig['ticket_timeout'];
                $where = [];
                $where['appid'] = $wxConfig['appid'];
                $where['appsecret'] = $wxConfig['appsecret'];
                $options = [];
                $options['where'] = $where;
                $result = $this->saveData($options, $data);
                if ($result['code'] !== 200) {
                    return getReturn(CODE_ERROR, $result['msg'], $wxConfig);
                }
                $redis = Redis::getInstance();
                $key = md5("{$wxConfig['store_id']}_wxConfig");
                $redis->hSet($key, 'ticket', $wxConfig['ticket']);
                $redis->hSet($key, 'ticket_timeout', $wxConfig['ticket_timeout']);
            }
        }
        return getReturn(200, '', $wxConfig);
    }

    /**
     * @param array $wxConfig
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 更新 ticket
     * Date: 2017-11-14 11:40:28
     * Update: 2017-11-14 11:40:29
     * Version: 1.0
     */
    public function updateCardTicket($wxConfig = [])
    {
        if (!empty($wxConfig['card_ticket_api'])) {
            $result = httpRequest($wxConfig['card_ticket_api'], 'get');
            $data = jsonDecodeToArr($result['data']);
            if (empty($data['data'])) {
                return getReturn(-1, "request error", $wxConfig);
            }
            $wxConfig['card_ticket'] = $data['data'];
        } else {
            // 如果过期了 重新获取
            if ($wxConfig['card_ticket_timeout'] < NOW_TIME) {
                // 获取前保证access_token最新
                $result = $this->updateAccessToken($wxConfig);
                if ($result['code'] !== 200) return $result;
                $wxConfig = $result['data'];
                $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::CARD_TICKET_PAI);
                $result = httpRequest($url, 'get');
                $data = jsonDecodeToArr($result['data']);
                if (!$this->isSuccess($data)) {
                    return getReturn(CODE_ERROR, $data['errmsg'] . ':' . $data['errcode'], $wxConfig);
                }
                $wxConfig['card_ticket'] = $data['ticket'];
                $wxConfig['card_ticket_timeout'] = NOW_TIME + $data['expires_in'] - 200;
                // 更新数据库和缓存
                $data = [];
                $data['card_ticket'] = $wxConfig['card_ticket'];
                $data['card_ticket_timeout'] = $wxConfig['card_ticket_timeout'];
                $where = [];
                $where['appid'] = $wxConfig['appid'];
                $where['appsecret'] = $wxConfig['appsecret'];
                $options = [];
                $options['where'] = $where;
                $result = $this->saveData($options, $data);
                if ($result['code'] !== 200) {
                    return getReturn(CODE_ERROR, $result['msg'], $wxConfig);
                }
                $redis = Redis::getInstance();
                $key = md5("{$wxConfig['store_id']}_wxConfig");
                $redis->hSet($key, 'card_ticket', $wxConfig['card_ticket']);
                $redis->hSet($key, 'card_ticket_timeout', $wxConfig['card_ticket_timeout']);
            }
        }
        return getReturn(200, '', $wxConfig);
    }

    /**
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取有独立授权的storeId数组
     * Date: 2017-11-17 09:45:22
     * Update: 2017-11-17 09:45:23
     * Version: 1.0
     */
    public function getAloneAccessStoreId()
    {
        $where = [];
        $where['appid'] = ['neq', ''];
        $where['appsecret'] = ['neq', ''];
        $where['store_id'] = ['not in', '0,6666'];
        $where['isdelete'] = NOT_DELETE;
        $options = [];
        $options['where'] = $where;
        $result = $this->queryField($options, 'store_id', true);
        return $result;
    }

    /**
     * @param int $storeId
     * @return bool ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 判断是有有独立授权
     * Date: 2017-11-17 10:33:14
     * Update: 2017-11-17 10:33:15
     * Version: 1.0
     */
    public function isAloneStore($storeId = 0)
    {
        $result = $this->getAloneAccessStoreId();
        if ($result['code'] !== 200) return false;
        $aloneId = $result['data'];
        return in_array($storeId, $aloneId);
    }

    /**
     * @param int $storeId
     * @param string $nextOpenId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取关注列表
     * Date: 2017-11-17 09:41:43
     * Update: 2017-11-17 09:41:44
     * Version: 1.0
     */
    public function getSubscribeList($storeId = 0, $nextOpenId = '')
    {
        if (!$this->isAloneStore($storeId) && $storeId > 0) $storeId = 0;
        $result = $this->getWxConfigInfo($storeId);
        if ($result['code'] !== 200) return $result;
        $wxConfig = $result['data'];
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::SUBSCRIBE_API);
        $url = str_replace('{%nextOpenid}', $nextOpenId, $url);
        $result = httpRequest($url, 'get');
        $data = jsonDecodeToArr($result['data']);
        if (!$this->isSuccess($data)) {
            return getReturn(-1, "{$data['errcode']}:{$data['errmsg']}");
        }
        return getReturn(200, '', $data);
    }

    /**
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 刷新当前店铺的关注者状态
     * Date: 2017-11-17 10:01:32
     * Update: 2017-11-17 10:01:33
     * Version: 1.0
     */
    public function refreshSubscribeState($storeId = 0)
    {
        // 获取关注者Openid列表
        $nextOpenid = '';
        $allNum = 0;
        $model = D('Member');
        $version = $model->max('version') + 1;
        $this->startTrans();
        do {
            $result = $this->getSubscribeList($storeId, $nextOpenid);
            if ($result['code'] !== 200) {
                $this->rollback();
                return $result;
            }
            $data = $result['data'];
            $nextOpenid = $data['next_openid'];
            $count = $data['count'];
            $total = $data['total'];
            $openId = $data['data']['openid'];
            $dev = [];
            $dev['store_id'] = $storeId;
            $dev['openid'] = $openId;
            $openId = implode(',', $openId);
            // 更新关注着状态
            if ($count > 0) {
                // 只更新不属于关注状态的人
                $where = [];
                $where['wx_openid'] = ['in', $openId];
                $where['subscribe'] = ['neq', 1];
                $options = [];
                $options['where'] = $where;
                $data = [];
                $data['subscribe'] = 1;
                $data['version'] = ++$version;
                $result = $model->saveData($options, $data);
                if ($result['code'] !== 200) {
                    $this->rollback();
                    return $result;
                }
            }
            // 累加已经获取的数量
            $allNum += $count;
        } while ($allNum < $total);
        $this->commit();
        $msg = $allNum > 0 ? "刷新关注状态成功" : "没有获取到关注者";
        return getReturn(200, $msg);
    }

    /**
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 上线前刷新所有人的关注状态
     * Date: 2017-11-17 10:46:01
     * Update: 2017-11-17 10:46:02
     * Version: 1.0
     */
    public function refreshAllSubscribe()
    {
        $result = $this->getAloneAccessStoreId();
        if ($result['code'] !== 200) return $result;
        $storeId = ['0'];
        $aloneId = $result['data'];
        $storeId = array_merge($storeId, $aloneId);
        foreach ($storeId as $value) {
            $result = $this->refreshSubscribeState($value);
            if ($result['code'] !== 200) return $result;
        }
        return $result;
    }

    /**
     * @param int $storeId 商家ID
     * @param string $actionName 二维码类型
     * @param string $sceneStr 字符串参数
     * @param int $sceneId ID参数
     * @param int $expireSeconds 过期时间 最大30天
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     *  data => [
     *      'ticket'=>'',
     *      'expire_seconds'  => '',
     *      'url' => ''
     *  ]
     * User: hj
     * Desc: 获取带参数的二维码的接口返回数据
     * Date: 2017-11-19 19:37:36
     * Update: 2017-11-19 19:37:38
     * Version: 1.0
     */
    public function getQRCode($storeId = 0, $actionName = 'QR_STR_SCENE', $sceneStr = '', $sceneId = 1, $expireSeconds = 2592000)
    {
        // 接口地址
        $result = $this->getWxConfigInfo($storeId);
        if ($result['code'] !== 200) return $result;
        $wxConfig = $result['data'];
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::QRCODE_API);
        $data = [];
        $data['action_name'] = $actionName;
        $data['action_info'] = [];
        $data['action_info']['scene'] = [];
        // 判断参数
        $actionName = strtoupper($actionName);
        switch ($actionName) {
            case 'QR_SCENE':
                // 临时的整型参数
                $data['expire_seconds'] = (int)$expireSeconds;
                $data['action_info']['scene']['scene_id'] = (int)$sceneId;
                break;
            case 'QR_STR_SCENE':
                // 临时的字符串参数
                $data['expire_seconds'] = (int)$expireSeconds;
                $data['action_info']['scene']['scene_str'] = $sceneStr;
                break;
            case 'QR_LIMIT_SCENE':
                // 永久的整型参数
                $data['action_info']['scene']['scene_id'] = (int)$sceneId;
                break;
            case 'QR_LIMIT_STR_SCENE':
                // 永久的字符串参数
                $data['action_info']['scene']['scene_str'] = $sceneStr;
                break;
            default:
                return getReturn(-1, '二维码类型未知');
                break;
        }
        // 调用接口
        $json = jsonEncode($data);
        $result = httpRequest($url, 'post', $json);
        if ($result['code'] !== 200) return $result;
        $data = jsonDecodeToArr($result['data']);
        if (!$this->isSuccess($data)) {
            return getReturn(-1, "{$data['errcode']}:{$data['errmsg']}");
        }
        return getReturn(200, '', $data);
    }

    /**
     * 获取关注商家的永久二维码
     * @param int $storeId
     * @param int $channelId
     * @param int $pid
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2019-06-18 19:36:39
     * Update: 2019-06-18 19:36:39
     * Version: 1.00
     */
    public function getLongSubQRCode($storeId = 0, $channelId = 0, $pid = 0)
    {
        $redis = Redis::getInstance();
        $sceneStr = "{$pid}-{$storeId}-{$channelId}";
        $key = "qrcode_long_$sceneStr";
        $data = $redis->hGetAll($key);
        if (empty($data)) {
            $result = $this->getQRCode($storeId, 'QR_LIMIT_STR_SCENE', $sceneStr);
            if ($result['code'] !== 200) return $result;
            $data = $result['data'];
            $redis->hMset($key, $data);
        }
        return getReturn(200, '', $data);
    }

    /**
     * @param int $storeId 商家ID
     * @param int $channelId 渠道ID
     * @param int $pid 推荐人ID 默认为0
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取关注商家的临时二维码 存入缓存 有效期30天
     * Date: 2017-11-19 19:51:53
     * Update: 2017-11-19 19:51:54
     * Version: 1.0
     */
    public function getTempSubQRCode($storeId = 0, $channelId = 0, $pid = 0)
    {
        $redis = Redis::getInstance();
        $sceneStr = "{$pid}-{$storeId}-{$channelId}";
        $key = "qrcode_$sceneStr";
        $data = $redis->hGetAll($key);
        if (empty($data)) {
            $result = $this->getQRCode($storeId, 'QR_STR_SCENE', $sceneStr);
            if ($result['code'] !== 200) return $result;
            $data = $result['data'];
            $redis->hMset($key, $data);
            $redis->expire($key, $data['expire_seconds']);
        }
        return getReturn(200, '', $data);
    }

    /**
     * 根据模版编号获取模版ID
     * @param int $storeId
     * @param string $templateNO
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-21 12:01:22
     * Update: 2017-12-21 12:01:22
     * Version: 1.00
     */
    public function getTemplateID($storeId = 0, $templateNO = '')
    {
        $result = $this->getWxConfigInfo($storeId);
        if ($result['code'] !== 200) return $result;
        $wxConfig = $result['data'];
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::TEMPLATE_ID_API);
        $data = [];
        $data['template_id_short'] = $templateNO;
        $result = httpRequest($url, 'post', jsonEncode($data));
        if ($result['code'] !== 200) return $result;
        $response = jsonDecodeToArr($result['data']);
        if (!$this->isSuccess($response)) {
            return getReturn(-1, $response['errmsg'], $response['errcode']);
        }
        $templateId = $response['template_id'];
        return getReturn(200, '', $templateId);
    }

    /**
     * 发送模版消息
     * @param int $storeId
     * @param string $postJsonData
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-21 13:51:18
     * Update: 2017-12-21 13:51:18
     * Version: 1.00
     */
    public function sendTemplateMsg($storeId = 0, $postJsonData = '')
    {
        $result = $this->getWxConfigInfo($storeId);
        if ($result['code'] !== 200) return $result;
        $wxConfig = $result['data'];
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::SEND_TEMPLATE_API);
        $result = httpRequest($url, 'post', $postJsonData);
        if ($result['code'] !== 200) return $result;
        $response = jsonDecodeToArr($result['data']);
        if (!$this->isSuccess($response)) {
            return getReturn(-1, $response['errmsg'], $response['errcode']);
        }
        return getReturn(200, '', $response['msgid']);
    }

    /**
     * 删除模版消息
     * @param int $storeId
     * @param string $templateId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-22 10:54:24
     * Update: 2017-12-22 10:54:24
     * Version: 1.00
     */
    public function delTemplate($storeId = 0, $templateId = '')
    {
        $result = $this->getWxConfigInfo($storeId);
        if ($result['code'] !== 200) return $result;
        $wxConfig = $result['data'];
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::DEL_TEMPLATE_API);
        $data = [];
        $data['template_id'] = $templateId;
        $result = httpRequest($url, 'post', jsonEncode($data));
        if ($result['code'] !== 200) return $result;
        $response = jsonDecodeToArr($result['data']);
        if (!$this->isSuccess($response)) {
            return getReturn(-1, $response['errmsg'], $response['errcode']);
        }
        return getReturn(200, '', $response['errmsg']);
    }

    /**
     * 根据标题获取模版消息 防止重复新增
     * @param int $storeId
     * @param string $title
     * @param string $templateNO
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-22 11:05:56
     * Update: 2017-12-22 11:05:56
     * Version: 1.00
     */
    public function getTemplateIDByTitle($storeId = 0, $title = '', $templateNO = '')
    {
        if ($this->isAloneStore($storeId)) {
            $saveId = $storeId;
        } else {
            $saveId = 0;
        }
        $redis = Redis::getInstance();
        $templateId = $redis->get("templateId_{$saveId}_{$templateNO}");
        if (!empty($templateId)) {
            return getReturn(200, '', $templateId);
        }
        $result = $this->getWxConfigInfo($storeId);
        if ($result['code'] !== 200) return $result;
        $wxConfig = $result['data'];
        $url = str_replace('{%accessToken}', $wxConfig['accesstoken'], self::TEMPLATE_LIST_API);
        $result = httpRequest($url, 'get');
        if ($result['code'] !== 200) return $result;
        $response = jsonDecodeToArr($result['data']);
        if (!$this->isSuccess($response)) {
            return getReturn(-1, $response['errmsg'], $response['errcode']);
        }
        $list = $response['template_list'];
        foreach ($list as $key => $value) {
            if ($value['title'] == $title) {
                $redis->set("templateId_{$saveId}_{$templateNO}", $value['template_id'], 7200);
                return getReturn(200, '', $value['template_id']);
            }
        }
        $result = $this->getTemplateID($storeId, $templateNO);
        if ($result['code'] !== 200) {
            return $result;
        }
        $redis->set("templateId_{$saveId}_{$templateNO}", $result['data'], 7200);
        return getReturn(200, '', $result['data']);
    }
}