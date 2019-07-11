<?php

namespace Common\Util\DecorationComponents;

/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/9/25
 * Time: 15:20
 */
class Tpl extends BaseComponents
{
    private $modulesHtml;
    private $bgColor;
    protected $tplRelativePath = 'diy/tpl';

    /**
     * @return mixed
     */
    public function getModulesHtml()
    {
        return $this->modulesHtml;
    }

    /**
     * @param mixed $modulesHtml
     * @return $this
     */
    public function setModulesHtml($modulesHtml)
    {
        $this->modulesHtml = $modulesHtml;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBgColor()
    {
        return $this->bgColor;
    }

    /**
     * @param mixed $bgColor
     * @return $this
     */
    public function setBgColor($bgColor)
    {
        $this->bgColor = $bgColor;
        return $this;
    }
}