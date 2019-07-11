<?php

namespace Common\Util\DecorationComponents;

/**
 * 咨询模块
 * Class NewsModule
 * @package Common\Util\DecorationComponents
 * User: hjun
 * Date: 2018-09-25 18:27:36
 * Update: 2018-09-25 18:27:36
 * Version: 1.00
 */
class NewsModule extends BaseComponents
{
    protected $tplRelativePath = 'diy/module/news_module';

    private $news;

    /**
     * @return mixed
     */
    public function getNews()
    {
        return $this->news;
    }

    /**
     * @param mixed $news
     */
    public function setNews($news)
    {
        $this->news = $news;
    }

    public function __construct($module, $storeId, $memberId)
    {
        parent::__construct($module, $storeId, $memberId);
        $newsId = $module['content']['news_link']['action_data'];
        if (!empty($newsId)) {
            $news = D('NewList')->setStoreId($storeId)->getShowNews($newsId);
            if (!empty($news)) {
                $this->setNews($news);
            }
        }
    }
}
