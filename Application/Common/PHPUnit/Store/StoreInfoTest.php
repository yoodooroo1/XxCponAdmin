<?php

namespace Common\PHPUnit;

class StoreInfoTest extends BaseTest
{
    public function testStoreInfo()
    {
        $info = D('StoreInfo')->getDistributionConfig(16758);
    }
}