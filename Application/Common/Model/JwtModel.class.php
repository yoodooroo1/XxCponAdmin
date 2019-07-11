<?php

namespace Common\Model;

use Firebase\JWT\JWT;

class JwtModel extends BaseModel
{
    // 不连接数据库 作为工具类
    protected $autoCheckFields = false;

    // 单例
    private static $_instance = null;

    /**
     * 初始化 载入JWT类库
     * User: hj
     * Date: 2017-11-24 00:56:40
     * Update: 2017-11-24 00:56:41
     * Version: 1.0
     */
    public function _initialize()
    {
        vendor('JWT.JWT');
        vendor('JWT.BeforeValidException');
        vendor('JWT.ExpiredException');
        vendor('JWT.SignatureInvalidException');
    }

    /**
     * @return $this ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 单例模式
     * Date: 2017-11-24 00:55:05
     * Update: 2017-11-24 00:55:06
     * Version: 1.0
     */
    static public function getInstance()
    {
        if (empty(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * 生成token
     * @param array $info 自定义信息
     * @return string token
     * User: hj
     * Desc:
     * Date: 2017-11-24 00:57:49
     * Update: 2017-11-24 00:57:50
     * Version: 1.0
     */
    public function encode($info = [])
    {
        // 私钥
        $key = C('JWT_KEY');

        /*
         * iss: jwt签发者
         * sub: jwt所面向的用户
         * aud: 接收jwt的一方
         * exp: jwt的过期时间，这个过期时间必须要大于签发时间
         * nbf: 定义在什么时间之前，该jwt都是不可用的.
         * iat: jwt的签发时间
         * jti: jwt的唯一身份标识，主要用来作为一次性token,从而回避重放攻击。
         */
        $playLoad = array(
            "iss" => "vjd",
            "iat" => NOW_TIME,
            // 有效期默认30天
            'exp' => NOW_TIME + 3600 * 24 * 30,
            'info' => $info
        );

        /**
         * IMPORTANT:
         * You must specify supported algorithms for your application. See
         * https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
         * for a list of spec-compliant algorithms.
         */
        return JWT::encode($playLoad, $key);
    }

    /**
     * 解析token
     * @param string $token 令牌
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc:
     * Date: 2017-11-24 01:45:41
     * Update: 2017-11-24 01:45:42
     * Version: 1.0
     */
    public function decode($token = '')
    {
        $key = C('JWT_KEY');

        /**
         * You can add a leeway to account for when there is a clock skew times between
         * the signing and verifying servers. It is recommended that this leeway should
         * not be bigger than a few minutes.
         *
         * Source: http://self-issued.info/docs/draft-ietf-oauth-json-web-token.html#nbfDef
         */
        try {
            // $leeway in seconds
            JWT::$leeway = 60;
            $playLoad = JWT::decode($token, $key, array('HS256'));
        } catch (\Exception $e) {
            return getReturn(-999, $e->getMessage());
        }

        // 对象转为数组
        $playLoad = (array)$playLoad;
        return getReturn(0, '', $playLoad);
    }
}