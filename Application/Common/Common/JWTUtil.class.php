<?php

namespace Common\Common;

use Firebase\JWT\JWT;

/**
 * Token验证类
 * Class JWT
 * @package Common\Common
 * User: hjun
 * Date: 2018-01-06 03:04:28
 * Update: 2018-01-06 03:04:28
 * Version: 1.00
 */
class JWTUtil
{
    // 单例
    private static $_instance = null;

    /**
     * JWTUtil constructor.
     * 载入JWT类库
     */
    private function __construct()
    {
        vendor('JWT.JWT');
        vendor('JWT.BeforeValidException');
        vendor('JWT.ExpiredException');
        vendor('JWT.SignatureInvalidException');
    }

    /**
     * 单例模式
     * @return $this
     * User: hjun
     * Date: 2018-01-06 03:07:07
     * Update: 2018-01-06 03:07:07
     * Version: 1.00
     */
    static public function getInstance()
    {
        if (empty(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * 生成token
     * @param array $info 要保存的信息
     * @return string token
     * User: hjun
     * Date: 2018-01-06 03:12:44
     * Update: 2018-01-06 03:12:44
     * Version: 1.00
     */
    public function encode($info = array())
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
            // 有效期默认7小时
            'exp' => NOW_TIME + 3600 * 7,
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
     * 验证token 获得token中的信息
     * @param string $token
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-06 03:12:26
     * Update: 2018-01-06 03:12:26
     * Version: 1.00
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
        return getReturn(200, '', $playLoad);
    }
}