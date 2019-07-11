<?php

namespace Common\PHPUnit;

class PickupVersionTest extends BaseTest
{
    /**
     * 整体更新一遍版本号
     * User: hjun
     * Date: 2018-11-29 10:42:24
     * Update: 2018-11-29 10:42:24
     * Version: 1.00
     */
    public function testRefreshVersion()
    {
        $model = D('Depot');
        $list = $model->field('id')->select();
        $version = 0;
        foreach ($list as $depot) {
            $where = [];
            $where['id'] = $depot['id'];
            $data = [];
            $data['version'] = ++$version;
            $model->where($where)->save($data);
        }
    }
}