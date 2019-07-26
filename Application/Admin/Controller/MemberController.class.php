<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/18
 * Time: 10:02
 */

namespace Admin\Controller;

class MemberController extends AdminController
{
    /**会员绑定接口
     * URL : /admin.php?c=coupon&a=memberBind
     * $params string $fmch_id
     * $params string $fsign
     * return  {
    "result": 200,
    "code": 0,
    "datas": {}
    }
     */
    //会员绑定接口
    public function memberBind(){
        $req = $this->req;
        $bind = M('member_bind');
        M()->startTrans();
        try{
            $m['fmch_id'] = $req['fmch_id'];
            $m['fsign'] = $req['fsign'];
            $m['store_id'] = $this->store_id;
            $m['addtime'] = time();
            $m['state'] = 1;
            $bind->data($m)->add();
            $post_data['hp_mark	'] = 0;
            $post_data['hp_token'] = md5($post_data['hp_mark']. 'vjd8988998');
            $post_data['fmch_id'] = $req['fmch_id'];
            $post_data['fsign'] = $req['fsign'];
            $post_data['store_id'] = $this->store_id;
            $post_data['state'] = 'web';
            $post_data['user_type'] = 'seller';
            $headers = array("Content-Type : text/html;charset=UTF-8");
            $url = $this->getXxUrl('Hp','bindErpStore');
            $xx_return = httpRequest($url, 'POST', $post_data, $headers);
            $log_str = "[Admin->Member->memberBind]  " . " returndata->" . json_encode($xx_return) . "\n" .
                "post_data:" . json_encode($post_data);
            CouponAdminLogs($log_str);
            if (!$xx_return||!$xx_return['code'] ==200) {
                output_error('404','服务器繁忙','');
            }
            $return_info = json_decode($xx_return['data'], true);
            if(!$return_info||!$return_info['result']==0){
                output_error('100','绑定失败');
            }
            M()->commit();
            output_data(array('msg'=>"绑定成功"));
        }catch (\Exception $e){
            M()->rollback();
        }
    }
}