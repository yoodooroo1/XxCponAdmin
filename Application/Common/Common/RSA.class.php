<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/1/6
 * Time: 5:10
 */

namespace Common\Common;

use Think\Exception;

/**
 * RSA加密类
 * Class RSA
 * @package Common\Common
 * User: hjun
 * Date: 2018-01-06 05:11:17
 * Update: 2018-01-06 05:11:17
 * Version: 1.00
 */
class RSA
{
    // 公钥
    private $pubKey = '';

    // 公钥资源
    private $pubKeySource = '';

    // 私钥资源
    private $priKeySource = '';

    // 私钥
    private $priKey = '';

    /**
     * 生成公钥私钥
     * RSA constructor.
     * @param string $pubKey 公钥
     * @param string $priKey 私钥
     * @param int $bits 字节数 512 1024 2048 4096
     * @param int $type 加密类型
     * @throws Exception
     */
    public function __construct($pubKey = '', $priKey = '', $bits = 1024, $type = OPENSSL_KEYTYPE_RSA)
    {
        if (empty($pubKey) || empty($priKey)) {
            $config = array(
                //"digest_alg" => "sha512",
                "private_key_bits" => $bits,
                "private_key_type" => $type,
            );
            //创建公钥和私钥   返回资源
            $res = openssl_pkey_new($config);
            openssl_pkey_export($res, $priKey);
            $pubKey = openssl_pkey_get_details($res);
            $pubKey = $pubKey["key"];
        }
        $this->pubKey = $pubKey;
        $this->priKey = $priKey;//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
        $this->pubKeySource = openssl_pkey_get_public($this->pubKey);//这个函数可用来判断公钥是否是可用的
        $this->priKeySource = openssl_pkey_get_private($this->priKey);
        if (false === $this->pubKeySource || false === $this->priKeySource) {
            E('密钥生成错误');
        }
    }

    /**
     * @return string
     */
    public function getPubKey()
    {
        return $this->pubKey;
    }

    /**
     * @param string $pubKey
     */
    public function setPubKey($pubKey)
    {
        $this->pubKey = $pubKey;
    }

    /**
     * @return resource|string
     */
    public function getPubKeySource()
    {
        return $this->pubKeySource;
    }

    /**
     * @param resource|string $pubKeySource
     */
    public function setPubKeySource($pubKeySource)
    {
        $this->pubKeySource = $pubKeySource;
    }

    /**
     * @return bool|resource|string
     */
    public function getPriKeySource()
    {
        return $this->priKeySource;
    }

    /**
     * @param bool|resource|string $priKeySource
     */
    public function setPriKeySource($priKeySource)
    {
        $this->priKeySource = $priKeySource;
    }

    /**
     * @return string
     */
    public function getPriKey()
    {
        return $this->priKey;
    }

    /**
     * @param string $priKey
     */
    public function setPriKey($priKey)
    {
        $this->priKey = $priKey;
    }



    /**
     * 获取公钥和私钥数组
     * @return array
     * User: hjun
     * Date: 2018-01-06 11:50:50
     * Update: 2018-01-06 11:50:50
     * Version: 1.00
     */
    public function getPubAndPriKey()
    {
        return array(
            'pub_key' => $this->pubKey,
            'pri_key' => $this->priKey
        );
    }

    /**
     * 使用私钥解密数据
     * @param $data
     * @return string
     * User: hjun
     * Date: 2018-01-06 10:44:46
     * Update: 2018-01-06 10:44:46
     * Version: 1.00
     */
    public function decryptByPriKey($data)
    {
        openssl_private_decrypt(base64_decode($data), $decrypted, $this->priKey);//私钥解密
        return $decrypted;
    }

    /**
     * 使用公钥加密数据 返回的是base64_encode之后的数据
     * @param $data
     * @return string
     * User: hjun
     * Date: 2018-01-06 12:23:57
     * Update: 2018-01-06 12:23:57
     * Version: 1.00
     */
    public function encryptByPubKey($data)
    {
        openssl_public_encrypt($data, $encrypted, $this->pubKey);//公钥加密
        return base64_encode($encrypted);// base64传输
    }

}