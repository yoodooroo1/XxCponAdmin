<?php

namespace Common\Util\DecorationComponents;

/**
 * 公告
 * Class NoticeModule
 * @package Common\Util\DecorationComponents
 * User: hjun
 * Date: 2018-09-25 18:27:36
 * Update: 2018-09-25 18:27:36
 * Version: 1.00
 */
class NoticeModule extends BaseComponents
{
    protected $tplRelativePath = 'diy/module/notice_module';

    private $noticeList;

    /**
     * @return mixed
     */
    public function getNoticeList()
    {
        return $this->noticeList;
    }

    /**
     * @param mixed $noticeList
     */
    public function setNoticeList($noticeList)
    {
        $this->noticeList = $noticeList;
    }

    public function __construct($module, $storeId, $memberId)
    {
        parent::__construct($module, $storeId, $memberId);
        $list = D('Notice')->getIndexNoticeList($storeId);
        $storeInfo = $this->getStoreInfo();
        if (!empty($storeInfo['order_sharkmoney']) && !empty($storeInfo['order_sharknum'])) {
            $title = L('ACT_SENTENCE', array('money' => $storeInfo["order_sharkmoney"], 'currencyUnit' => $storeInfo['currency_unit'], 'num' => $storeInfo["order_sharknum"]));
            $list[] = [
                'title' => $title,
                'notice_type' => 2,
                'notice_web_url' => 'javascript:;'
            ];
        }
        $this->setNoticeList($list);
    }

}
