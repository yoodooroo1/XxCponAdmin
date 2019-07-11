<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/9
 * Time: 17:13
 */

namespace Admin\Controller;


class Test extends AdminController
{
    public function test(){
        $cp = new \Dock\Controller\IndexController();
        $data['id'] = 1;
        $data['content']['id'] = 1;
        $rs = $cp->onlineCouponToOffline($data);
    }
}