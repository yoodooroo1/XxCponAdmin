<?php

namespace Common\Util\DecorationComponents;

use phpDocumentor\Reflection\Types\This;
use Think\View;

/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/9/25
 * Time: 16:02
 */
class BaseComponents extends View
{
    protected $storeId; // 商家ID
    protected $memberId; // 会员ID
    protected $storeInfo; // 商家信息
    protected $auth; // 商家权限
    protected $storeMember; // 会员商家信息
    protected $tplRelativePath;
    protected $module;
    protected $priceHideDesc;
    protected $moduleKey; // 当前模块类存储在redis中的key值

    /**
     * @return mixed
     */
    public function getModuleKey()
    {
        return $this->moduleKey;
    }

    /**
     * @param mixed $moduleKey
     */
    public function setModuleKey($moduleKey)
    {
        $this->moduleKey = $moduleKey;
    }

    /**
     * @var self
     */
    protected $self;

    /**
     * @return BaseComponents
     */
    public function getSelf()
    {
        return $this->self;
    }

    /**
     * @param BaseComponents $self
     */
    public function setSelf($self)
    {
        $this->self = $self;
    }

    /**
     * @return mixed
     */
    public function getPriceHideDesc()
    {
        return $this->priceHideDesc;
    }

    /**
     * @param mixed $priceHideDesc
     */
    public function setPriceHideDesc($priceHideDesc)
    {
        $this->priceHideDesc = $priceHideDesc;
    }

    /**
     * @return mixed
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * @param mixed $auth
     */
    public function setAuth($auth)
    {
        $this->auth = $auth;
    }

    /**
     * @return mixed
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param mixed $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param mixed $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMemberId()
    {
        return $this->memberId;
    }

    /**
     * @param mixed $memberId
     * @return $this
     */
    public function setMemberId($memberId)
    {
        $this->memberId = $memberId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStoreInfo()
    {
        return $this->storeInfo;
    }

    /**
     * @param mixed $storeInfo
     * @return $this;
     */
    public function setStoreInfo($storeInfo)
    {
        $this->storeInfo = $storeInfo;
        return $this;
    }

    public function getTplPath($relativePath = '', $ext = 'html')
    {
        return realpath(COMMON_PATH . 'Default/template') . '/' . $relativePath . ".{$ext}";
    }

    /**
     * @return mixed
     */
    public function getTplRelativePath()
    {
        return $this->tplRelativePath;
    }

    /**
     * @param mixed $tplRelativePath
     * @return $this
     */
    public function setTplRelativePath($tplRelativePath)
    {
        $this->tplRelativePath = $tplRelativePath;
        return $this;
    }

    public function __construct($module, $storeId, $memberId)
    {
        $this->setModule($module);
        $this->setStoreId($storeId);
        $this->setMemberId($memberId);
        if (!empty($storeId)) {
            $model = D('Store');
            $storeInfo = $model->getStoreInfo($storeId)['data'];
            $this->setStoreInfo($storeInfo);
            $auth = $model->getStoreGrantInfo($storeId)['data'];
            $this->setAuth($auth);
            // 如果有价格隐藏权限、并且不是代理商，则无法看到价格
            if ($auth['price_ctrl'] == 1) {
                if ($storeInfo['price_is_hide'] == 1 && session('is_partner') != 1) {
                    $desc = empty($storeInfo['price_hide_desc']) ? L('PRICE_HIDE') : $storeInfo['price_hide_desc'];
                    $this->setPriceHideDesc($desc);
                }
            }
        }
        $this->setSelf($this);
    }

    public function toHtml()
    {
        $ref = new \ReflectionClass($this);
        $propertise = $ref->getProperties();
        $propertise = jsonEncode($propertise);
        $propertise = jsonDecodeToArr($propertise);
        foreach ($propertise as $property) {
            $name = $property['name'];
            $getter = 'get' . ucfirst($name);
            if (method_exists($this, $getter)) {
                $this->tVar[$name] = $this->$getter();
            }
        }
        $html = '';
        $path = $this->getTplPath($this->getTplRelativePath());
        if (is_file($path)) {
            $html = $this->fetch($path);
        }
        return $html;
    }
}