<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/6
 * Time: 14:16
 */

namespace Admin\Controller;



use Think\Model;

class CouponController extends AdminController
{
    public function index(){
        $coupon_list = $this->getMatchedLists();
        $this->assign('coupon_lists',$coupon_list);
        $this->display('index');
    }
    /**获取讯信优惠卷列表
     * URL : /admin.php?c=coupon&a=getXxCouponsList
     * return  {
    "result": 200,
    "code": 0,
    "datas": {}
    }
     */

    public function getXxCouponsList(){
        $params = array();
        $params['store_id'] = $this->store_id;
        $params['state'] = 0;
        $coupons_lists = M('xx_coupons_lists_record')->field('id,is_delete,state,store_id',true)->where($params)->select();
        output_data($coupons_lists);
    }


    /**获取第三方优惠卷列表
     * URL : /admin.php?c=coupon&a=getThirdCouponsList
     * return  {
    "result": 200,
    "code": 0,
    "datas": {}
    }
     */
    public function getThirdCouponsList(){
        $params = array();
        $params['store_id'] = $this->store_id;
        $params['state'] = 0;
        $coupons_lists = M('third_coupons_lists_record')->field('id,is_delete,state,store_id',true)->where($params)->select();
        output_data($coupons_lists);
    }


    /**获取优惠卷关联列表
     * URL : /admin.php?c=coupon&a=getMatchedLists
     * return  {
    "result": 200,
    "code": 0,
    "datas": {}
    }
     */
    public function getMatchedLists(){
        $params = array();
        $params['is_delete'] = 0;
        $params['store_id'] = $this->store_id;
        $coupon_list = M('coupons_match')->where($params)->field('state,is_delete,create_time',true)->select();
        return $coupon_list;
//        $this->assign('coupon_lists',$coupon_list);
//        $this->display('index');
    }

    /**增加优惠卷关联信息
     * URL : /admin.php?c=coupon&a=addCpMatching
     * return  {
    "result": 200,
    "code": 0,
    "msg":
    "datas": {}
    }
     */
    public function addCpMatching(){
        $match = M('coupons_match');
        M()->startTrans();
        try {
            if($this->store_id == NULL){
                output_error(-100,'会话过期，请重新登入');
            }
            $match_params = array('create_time' => time(),'store_id'=>$this->store_id);
            $match->data($match_params)->add();
            M()->commit();
        }catch (\Exception $e){
            M()->rollback();
        }
        output_data(array(),'新增成功');
    }

    /**删除优惠卷关联信息
     * URL : /admin.php?c=coupon&a=delCpMatching
     * return  {
    "result": 200,
    "code": 0,
    "msg":
    "datas": {}
    }
     */
    public function delCpMatching(){
        $req = $this->req;
        $match = M('coupons_match');
        $params = array();
        $params['id'] = $req['id'];
        $match_info = $match->where($params)->find();
        if(!$match_info['state'] == 0){
            output_error('-100','要先解除关联，才可以删除哦！');
        }
        if(!$match_info['is_delete'] == 0){
            output_error('-100','此关联已删除！');
        }
        M()->startTrans();
        try {
            $match_params =array('is_delete'=>1,'delete_time'=>time());
            $del = $match->where($params)->save($match_params);
            M()->commit();
            if(!$del) {
                output_error('-100','删除失败');
            }
            output_data(array(), '删除成功');
        }catch (\Exception $e){
            M()->rollback();
        }
    }

    /**优惠卷关联
     * URL : /admin.php?c=coupon&a=couponsMatching
     * params int id 关联优惠卷ID
     * params string xx_coupons_id 讯信优惠卷ID
     * params string third_coupons_id 第三方优惠卷ID
     * return  {
     "result": 200,
     "code": 0,
     }
     */
    public function couponsMatching(){
        $req = $this->req;
        $log_str = "[Admin->Conpon->couponsMatching] 优惠卷关联： " . " request_data->" . json_encode($req) . "\n" ;
        XxCoupons($log_str);

        $xx_cp = M('xx_coupons_lists_record');
        $xx_params = array();
        $xx_params['store_id'] = $this->store_id;
        $xx_params['xx_coupons_id'] = $req['xx_coupons_id'];
        $xx_info = $xx_cp->where($xx_params)->find();
        $third_cp = M('third_coupons_lists_record');
        $third_params = array();
        $third_params['store_id'] = $this->store_id;
        $third_params['third_coupons_id'] = $req['third_coupons_id'];
        $third_info = $third_cp->where($third_params)->find();
        if(!$xx_info||!$third_info){
            $log_str = "[Admin->Conpon->couponsMatching] 优惠卷关联失败： " . "xx_coupons_id：".$xx_info['xx_coupons_id'].",third_coupons_id：" . $third_info['third_coupons_id'] ."error:参数错误". "\n" ;
            XxCoupons($log_str);
            output_error('-100','优惠卷参数错误!','');
        }
        if(!$xx_info['state'] == 0) {
            $log_str = "[Admin->Conpon->couponsMatching] 优惠卷关联失败： " . "xx_coupons_id：".$xx_info['xx_coupons_id'].",third_coupons_id：" . $third_info['third_coupons_id'] ."error:此讯信优惠卷已关联". "\n" ;
            XxCoupons($log_str);
            output_error('-100','讯信优惠卷已关联!','');
        }
        if(!$third_info['state'] == 0) {
            $log_str = "[Admin->Conpon->couponsMatching] 优惠卷关联失败： " . "xx_coupons_id：".$xx_info['xx_coupons_id'].",third_coupons_id：" . $third_info['third_coupons_id'] ."error:此第三方优惠卷已关联". "\n" ;
            XxCoupons($log_str);
            output_error('-100', '线下优惠卷已关联!', '');
        }
        $match = M('coupons_match');
        $match_info = $match->where(array('id'=>$req['id']))->find();
        if(!$match_info['is_delete'] == 0){
            $log_str = "[Admin->Conpon->couponsMatching] 优惠卷关联失败： " . "xx_coupons_id：".$xx_info['xx_coupons_id'].",third_coupons_id：" . $third_info['third_coupons_id'] ."error:关联已删除". "\n" ;
            XxCoupons($log_str);
            output_error('-100', '此关联已删除!', '');
        }

        M()->startTrans();
        try{
            $match_params = array();
            $match_params['store_id'] = $this->store_id;
            $match_params['xx_coupons_id'] = $xx_info['xx_coupons_id'];
            $match_params['third_coupons_id'] = $third_info['third_coupons_id'];
            $match_params['state'] = 1;
            $match_params['create_time'] = time();
            $add = $match->where(array('id'=>$req['id']))->save($match_params);
            if(!$add){
                output_error('100','关联失败','');
            }
            $xx_cp->where($xx_params)->data(array('state'=>1))->save();
            $third_cp->where($third_params)->data(array('state'=>1))->save();
            M()->commit();
            $log_str = "[Admin->Conpon->couponsMatching] 优惠卷关联成功：" . "xx_coupons_id：".$xx_info['xx_coupons_id'].",third_coupons_id：" . $third_info['third_coupons_id'] . "\n" ;
            XxCoupons($log_str);
            output_data(array(),'优惠卷关联成功');
        }catch (\Exception $e){
            M()->rollback();
            $log_str = "[Admin->Conpon->couponsMatching] 优惠卷关联失败： " . "xx_coupons_id：".$xx_info['xx_coupons_id'].",third_coupons_id：" . $third_info['third_coupons_id'] ."error:数据库写入错误". "\n" ;
            XxCoupons($log_str);
            output_error('100','关联信息有误，关联失败','');
        }
    }


    /**优惠卷解除关联
     * URL : /admin.php?c=coupon&a=couponsSeparating
     * params int id 关联优惠卷ID
     * params string xx_coupons_id 讯信优惠卷ID
     * params string third_coupons_id 第三方优惠卷ID
     * return  {
     "result": 200,
    "code": 0,
    }
     */
    public function couponsSeparating(){
        $req = $this->req;
        $log_str = "[Admin->Conpon->couponsSeparating] 优惠卷解除关联： " . " request_data->" . json_encode($req) . "\n" ;
        XxCoupons($log_str);

        $xx_cp = M('xx_coupons_lists_record');
        $xx_params = array();
        $xx_params['store_id'] = $this->store_id;
        $xx_params['xx_coupons_id'] = $req['xx_coupons_id'];
        $xx_info = $xx_cp->where($xx_params)->find();
        $third_cp = M('third_coupons_lists_record');
        $third_params = array();
        $third_params['store_id'] = $this->store_id;
        $third_params['third_coupons_id'] = $req['third_coupons_id'];
        $third_info = $third_cp->where($third_params)->find();
        if(!$xx_info||!$third_info){
            $log_str = "[Admin->Conpon->couponsSeparating] 优惠卷关联失败： " . "xx_coupons_id：".$xx_info['xx_coupons_id'].",third_coupons_id：" . $third_info['third_coupons_id'] ."error:参数错误". "\n" ;
            XxCoupons($log_str);
            output_error('-100','优惠卷参数错误!','');
        }
        if($xx_info['state'] == 0) {
            $log_str = "[Admin->Conpon->addcouponsMatch] 优惠卷解除关联失败：" . "xx_coupons_id：".$xx_info['xx_coupons_id'].",third_coupons_id：" . $third_info['third_coupons_id'] ."error:讯信优惠卷未关联". "\n" ;
            XxCoupons($log_str);
            output_error('100','讯信优惠卷未关联!','');
        }
        if($third_info['state'] == 0){
            $log_str = "[Admin->Conpon->addcouponsMatch] 优惠卷解除关联失败：" . "xx_coupons_id：".$xx_info['xx_coupons_id'].",third_coupons_id：" . $third_info['third_coupons_id'] ."error:第三方优惠卷未关联". "\n" ;
            XxCoupons($log_str);
            output_error('100','线下优惠卷未关联!','');
        }

        M()->startTrans();
        try{
            $match = M('coupons_match');
            $match_params = array();
            $match_params['id'] = $req['id'];
            $match_params['store_id'] = $this->store_id;
            $match_params['xx_coupons_id'] = $req['xx_coupons_id'];
            $match_params['third_coupons_id'] = $req['third_coupons_id'];
            $del = $match->where($match_params)->data(array('state'=>0))->save();
            if(!$del){
                output_error('100','解除关联失败','');
            }
            $xx_cp->where($xx_params)->data(array('state'=>0))->save();
            $third_cp->where($third_params)->data(array('state'=>0))->save();
            M()->commit();
            $log_str = "[Admin->Conpon->addcouponsMatch] 优惠卷解除关联成功：" . "xx_coupons_id：".$xx_info['xx_coupons_id'].",third_coupons_id：" . $third_info['third_coupons_id'] . "\n" ;
            XxCoupons($log_str);
            output_data(array(),'解除关联成功');
        }catch (\Exception $e){
            M()->rollback();
            $log_str = "[Admin->Conpon->addcouponsMatch] 优惠卷解除关联失败：" . "xx_coupons_id：".$xx_info['xx_coupons_id'].",third_coupons_id：" . $third_info['third_coupons_id'] ."error:数据库写入错误". "\n" ;
            XxCoupons($log_str);
            output_error('100','关联信息有误，解除关联失败','');
        }
    }


    /**同步优惠卷信息
     * URL : /admin.php?c=coupon&a=syncCouponsData
     * params int id 关联优惠卷ID
     * params string xx_coupons_id 讯信优惠卷ID
     * params string third_coupons_id 第三方优惠卷ID
     * return  {
    "result": 200,
    "code": 0,
    }
     */
    public function syncCouponsData()
    {
        $post_data['version'] = 0;
        $post_data['key'] = $this->key;
        $post_data['client'] = 'web';
        $post_data['user_type'] = 'seller';
        $post_data['store_id'] = $this->store_id;
        $post_data['comchannel_id'] = 0;
        $headers = array("Content-Type : text/html;charset=UTF-8");
        $url = $this->getXxUrl('Coupons','syncdata');
        $xx_return = httpRequest($url, 'POST', $post_data, $headers);
        $log_str = "[Admin->Conpon->syncCouponsData] 优惠卷同步： " . " returndata->" . json_encode($xx_return) . "\n" .
            "post_data:" . json_encode($post_data);
        XxAdmin($log_str);
        $return_info = json_decode($xx_return['data'], true);
//        var_dump($return_info);
        if ($return_info['result'] == 0) {
            $coupons = $return_info['datas']['ditems'];    //citems (citems, optional): 正常使用的优惠 ditems (Array[string], optional): 删除的优惠券
            $cp = M('xx_coupons_lists_record');
            foreach ($coupons as $value){
                $check_params = array();
                $check_params['store_id'] = $this->store_id;
//                $check_params['xx_coupons_id'] = $value['coupons_id']; //检查正常优惠卷
                $check_params['xx_coupons_id'] = $value; //检查删除的优惠卷
                $check = $cp->where($check_params)->find();
                if(!$check){
                    $params = array();
//                    $params['xx_coupons_id'] = $value['coupons_id'];
//                    $params['xx_coupons_name'] = $value['coupons_name'];//同步正常优惠卷
                    $params['xx_coupons_id'] = $value;//同步删除优惠卷
                    $params['store_id'] = $this->store_id;
                    $add = $cp->data($params)->add();
                }
            }
        }

    }

    public function getThirdCouponsByXx(){

    }

}