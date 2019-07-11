<?php

namespace Common\Util\DecorationComponents;

/**
 * 在线客服
 * Class CustomerModule
 * @package Common\Util\DecorationComponents
 * User: hjun
 * Date: 2018-09-25 18:27:36
 * Update: 2018-09-25 18:27:36
 * Version: 1.00
 */
class CustomerModule extends BaseComponents
{
    protected $tplRelativePath = 'diy/module/customer_msg_module';

    protected $unReadNum;

    /**
     * @return mixed
     */
    public function getUnReadNum()
    {
        return $this->unReadNum;
    }

    /**
     * @param mixed $unReadNum
     */
    public function setUnReadNum($unReadNum)
    {
        $this->unReadNum = $unReadNum;
    }

    public function __construct($module, $storeId, $memberId)
    {
        parent::__construct($module, $storeId, $memberId);
        if (!empty($memberId)) {
            $member = D('Member')->getMemberInfo($memberId)['data'];
            $memberName = $member['member_name'];
            $num = getAllUnreadNum($memberName , $this->storeId);
            $this->setUnReadNum($num);
        }
    }
}
