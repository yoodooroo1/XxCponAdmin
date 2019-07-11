<?php

namespace Common\Model;

/**
 * 小程序配置
 * Class MiniUtilModel
 * @package Common\Model
 * User: hjun
 * Date: 2019-03-11 12:03:38
 * Update: 2019-03-11 12:03:38
 * Version: 1.00
 */
class MiniUtilModel extends BaseModel
{
    protected $tableName = 'mb_mini_config';

    // 获取access_token的接口地址
    const ACCESS_TOKEN_API = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={%appId}&secret={%appSecret}";

    /**
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc:  获取商家的微信配置信息 包括 appId appSecret accessToken
     * Date: 2017-11-14 10:25:25
     * Update: 2017-11-14 10:25:26
     * Version: 1.0
     */
    public function getMiniConfigInfo($storeId = 0)
    {
        $key = ("{$storeId}_miniConfig");
        $config = S($key);
        if (empty($config['appid']) || empty($config['appsecret'])) {
            // 先获取真实的商家ID
            if ($storeId > 0) {
                $result = D('Store')->getStoreInfo($storeId);
                $info = $result['data'];
                $storeId = $info['main_store_id'];
            }
            $where = [];
            $where['store_id'] = $storeId > 0 ? $storeId : 0;
            $where['appid'] = ['neq', ''];
            $where['appsecret'] = ['neq', ''];
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
        }
        // 更新accessToken 以及 ticket
        $result = $this->updateAccessToken($config);
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
            if (empty($data['data'])) return getReturn(-1, "request error");
            $wxConfig['accesstoken'] = $data['data'];
        } else {
            // 如果access_token已经过期 则更新
            if ($wxConfig['accesstoken_timeout'] < NOW_TIME) {
                $url = str_replace('{%appId}', $wxConfig['appid'], self::ACCESS_TOKEN_API);
                $url = str_replace('{%appSecret}', $wxConfig['appsecret'], $url);
                $result = httpRequest($url, 'get');
                $data = jsonDecodeToArr($result['data']);
                if (empty($data['access_token'])) {
                    return getReturn(-1, $data['errcode'] . ':' . $data['errmsg']);
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
                if ($result['code'] !== 200) return $result;
                $key = ("{$wxConfig['store_id']}_miniConfig");
                S($key, $wxConfig);
            }
        }
        return getReturn(200, '', $wxConfig);
    }
}