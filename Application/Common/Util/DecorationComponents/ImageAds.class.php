<?php

namespace Common\Util\DecorationComponents;

/**
 * 图片广告
 * Class ImageAds
 * @package Common\Util\DecorationComponents
 * User: hjun
 * Date: 2018-09-25 18:27:36
 * Update: 2018-09-25 18:27:36
 * Version: 1.00
 */
class ImageAds extends BaseComponents
{
    protected $tplRelativePath = 'diy/module/img_adv_module';

    protected $publicTplPath;

    /**
     * @return string
     */
    public function getPublicTplPath()
    {
        return $this->publicTplPath;
    }

    /**
     * @param string $publicTplPath
     */
    public function setPublicTplPath($publicTplPath)
    {
        $this->publicTplPath = $publicTplPath;
    }

    public function __construct($module, $storeId, $memberId)
    {
        parent::__construct($module, $storeId, $memberId);
        $this->setPublicTplPath(COMMON_PATH . '/Default/template/diy/module/img_adv_public_module.html');
    }
}
