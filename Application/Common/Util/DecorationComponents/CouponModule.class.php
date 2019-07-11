<?php

namespace Common\Util\DecorationComponents;

/**
 * 优惠券
 * Class CouponModule
 * @package Common\Util\DecorationComponents
 * User: hjun
 * Date: 2018-09-25 18:27:36
 * Update: 2018-09-25 18:27:36
 * Version: 1.00
 */
class CouponModule extends BaseComponents
{
    protected $tplRelativePath = 'diy/module/coupon_module';

    private $centerList;

    /**
     * @return mixed
     */
    public function getCenterList()
    {
        return $this->centerList;
    }

    /**
     * @param mixed $centerList
     */
    public function setCenterList($centerList)
    {
        $this->centerList = $centerList;
    }

    public function __construct($module, $storeId, $memberId)
    {
        parent::__construct($module, $storeId, $memberId);
        $model = D('CouponsCenter');
        $result = $model->getCenterCouponsList($storeId, $memberId);
        $list = $result['data']['list'];
        $this->setCenterList($list);
    }
}
