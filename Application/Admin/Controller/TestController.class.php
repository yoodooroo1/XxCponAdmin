<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/9
 * Time: 17:13
 */

namespace Admin\Controller;


class TestController extends AdminController
{
    public function test(){
        $params = array();
        $params['store_id'] = session('store_id');
        $params['state'] = 0;
        $coupons_lists = M('xx_coupons_lists_record')->field('is_delete,state,store_id',true)->where($params)->select();
        output_data($coupons_lists);
    }
}