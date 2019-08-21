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
        $log_str = "[Admin->Conpon->memberBind] ： " . " request_data->" . json_encode($req) . "\n" ;
        couponAdminLogs($log_str);
        //积分绑定
        if($req['credit']<=0){
            output_error('-100','积分不能小于0','积分不能小于0！');
        }
        $bind = M('store_member_bind');
        $rate = doubleval(doubleval($req['credit'])/ 1);
        $credit['rate'] = $rate;
        $credit['store_id'] = $this->store_id;
        $bind_rate = M('store_credit_rate');
        $check_rate = M('store_credit_rate')->where(array('store_id'=>$this->store_id))->find();
        if(!empty($check_rate)){
            $bind_rate->where(array('store_id'=>$this->store_id))->save($credit);
        }else{
            $credit['create_time'] = time();
            $bind_rate->data($credit)->add();
        }
        //会员卡绑定
        $m['fmch_id'] = $req['mch_id'];
        $m['fmch_key'] = $req['key'];
        $sign = $m['fmch_id'].'&'.$m['fmch_key'];
        $m['fsign'] = strtolower(md5($sign));
        $m['store_id'] = $this->store_id;
        $m['fshopid'] = $req['shopid'];
        $m['goods_deduction_state'] = $req['goods_deduction_state'];
        $m['price_sync_state'] = $req['price_sync_state'];
        $check = M('store_member_bind')->where(array('store_id'=>$this->store_id))->find();
        if(!empty($check)){
            $bind->where(array('store_id'=>$this->store_id))->save($m);
        }else{
            $m['create_time'] = time();
            $bind->data($m)->add();
        }
        $post_data['hp_mark	'] = 0;
        $post_data['hp_token'] = md5($post_data['hp_mark']. 'vjd8988998');
        $post_data['fmch_id'] = $req['mch_id'];
        $post_data['fmch_key'] = $req['key'];
        $post_data['fshopid'] = $req['shopid'];
        $post_data['goods_deduction_state'] = $req['goods_deduction_state'];
        $post_data['store_id'] = $this->store_id;
        $post_data['price_sync_state'] = $req['price_sync_state'];
        $post_data['user_type'] = 'seller';
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $url = $this->getXxUrl('Hp','bindErpStore');
        $xx_return = httpRequest($url, 'POST', $post_data, $headers);
        $log_str = "[Admin->Member->memberBind]  " . " returndata->" . json_encode($xx_return) . "\n" .
            "post_data:" . json_encode($post_data);
        couponAdminLogs($log_str);
        if (!$xx_return||!$xx_return['code'] ==200) {
            output_error('404','服务器繁忙','服务器繁忙');
        }
        $return_info = json_decode($xx_return['data'], true);
        output_data(array(),'保存成功');
    }
}