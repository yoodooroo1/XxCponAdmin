<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/18
 * Time: 10:02
 */

namespace Admin\Controller;

use think\Model;
class MemberController extends AdminController
{
    public function memberBind(){
        $data = $this->req;
        $url = 'http://dev-mapi.duinin.com/index.php?act=Hp&op=bindErpStore';
        $bind = M('member_bind');
        Model::startTrans();
        try{
            $m['fmch_id'] = $data['fmch_id'];
            $m['fmch_key'] = $data['fmch_key'];
            $m['store_id'] = session('store_id');
            $m['addtime'] = time();
            $bind->where($m)->add();
            $post_data['hp_mark	'] = 0;
            $post_data['hp_token'] = md5($post_data['hp_mark'] . session('key'));
            $post_data['fmch_id'] = $data['fmch_id'];
            $post_data['fmch_key'] = $data['fmch_key'];
            $post_data['store_id'] = session('store_id');
            $post_data['state'] = 'web';
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $return_data = httpRequest($url, 'POST', $post_data, $headers);
            $log_str = "[Admin->Member->memberBind]  " . " returndata->" . json_encode($return_data) . "\n" .
                "post_data:" . json_encode($post_data);
            CouponAdminLogs($log_str);
            $return_info = json_decode($return_data['data'], true);
            if (!$return_info) {
                $this->error("服务器忙...");
            }
            Model::commit();
        }catch (\Exception $e){
            Model::rollback();
        }
    }
}