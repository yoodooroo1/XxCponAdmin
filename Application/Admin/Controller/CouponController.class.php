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
    //优惠卷清单
    public function index(){
        $coupon = M('coupon');
        $where = "id>0";
        $count = $coupon->where($where)->count();
        $p = $this->getpage($count,8);
        $list = $coupon->field(true)->where($where)->order('id desc')->limit($p->firstRow, $p->listRows)->select();
        $this->assign('select', $list);
        $this->assign('page', $p->show());
        $this->display('index');
    }

    //获取优惠卷信息
    public function getCouponList(){
        $data = array();
        $lists = M('coupon')->select();
        $data['lists'] = $lists;
        $this->assign('data',$data);
        $this->display('add');
    }

    //新增优惠卷互换
    public function add(){
        $data = $this->req;
        $log_str = "[Admin->coupon->save]  "."\n". "post_data:".json_encode($data);
        CouponAdminLogs($log_str);
        $m['online'] = $data['online'];
        $m['addtime'] = time();
        $m['offline'] = $data['online'];
        $res = M('coupon')->add($m);
        if(!$res) {
            exit(json_encode(array('code' => 1, 'msg' => '新增失败')));
        }else{
            exit(json_encode(array('code' => 0, 'msg' => '新增成功')));
        }
    }

    //删除优惠卷互换
    public function delete(){
        $data = $this->req;
        $log_str = "[Admin->coupon->delete]  "."\n". "post_data:".json_encode($data);
        CouponAdminLogs($log_str);
        $m['id'] = $data['id'];
        $res = M('coupon')->where($m)->delete();
        if(!$res) {
            exit(json_encode(array('code' => 1, 'msg' => '删除失败')));
        }else{
            exit(json_encode(array('code' => 0, 'msg' => '删除成功')));
        }
    }

    //编辑互换信息
    public function edit(){
        $this->display('edit');
    }

    //优惠卷互换
    public function match(){
        $data = $this->req;
        $log_str = "[Admin->coupon->match]  "."\n". "post_data:".json_encode($data);
        CouponAdminLogs($log_str);
        $m['status'] = 1;
        $res = M('coupon')->where(array('id'=>$data['id']))->save($m);
        if(!$res) {
            exit(json_encode(array('code' => 1, 'msg' => '关联互换失败')));
        }else{
            exit(json_encode(array('code' => 0, 'msg' => '关联互换成功')));
        }
    }

    //解除优惠互换
    public function depart(){
        $data = $this->req;
        $log_str = "[Admin->coupon->depart]  "."\n". "post_data:".json_encode($data);
        CouponAdminLogs($log_str);
        $m['status'] = 0;
        $res = M('coupon')->where(array('id'=>$data['id']))->save($m);
        if(!$res) {
            exit(json_encode(array('code' => 1, 'msg' => '解除关联失败')));
        }else{
            exit(json_encode(array('code' => 0, 'msg' => '解除关联成功')));
        }
    }

    //同步信息
    public function fresh(){
        exit(json_encode(array('code' => 0, 'msg' => '同步成功')));
    }
    //分页处理
    function getpage($count, $pagesize = 10) {
        $p = new \Think\Page($count, $pagesize);
        $p->setConfig('header', '<li class="rows">共<b>%TOTAL_ROW%</b>条记录&nbsp;第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</li>');
        $p->setConfig('prev', '上一页');
        $p->setConfig('next', '下一页');
        $p->setConfig('last', '末页');
        $p->setConfig('first', '首页');
        $p->setConfig('theme', '%FIRST%%UP_PAGE%%LINK_PAGE%%DOWN_PAGE%%END%%HEADER%');
        $p->lastSuffix = false;//最后一页不显示为总页数
        return $p;
    }

    //
    function match_s(){
        $data =$this->req;
        $log_str = "[Admin->coupon->match]  "."\n". "post_data:".json_encode($data);
        CouponAdminLogs($log_str);
        Model::startTrans();
        try{
            $coupon_info = M('coupon')->where(array())->find();
            $post_info[''] = $coupon_info[''];
            $headers = array("Content-Type : application/json;charset=UTF-8");
            $return_data = httpRequest('http://erpds_test.duinin.com/Dock.php?c=index&a=onlineCouponToOffline',
                'POST',$post_info,$headers);
            $log_str = "[Admin->coupon->match]  "."\n". "return_data:".json_encode($return_data);
            CouponAdminLogs($log_str);
            if(!$coupon_info['match_status'] == 0) {
                exit(json_encode(array('code' => 1, 'msg' => '此优惠卷已关联过')));
            }
            if ($return_data['code'] == 200) {
                $m['id'] = $data['id'];
                $m['match_status'] = 1;
                $m['nid'] = '';
                M('coupon')->where($m)->save();

            }else{
                exit(json_encode(array('code' => 1, 'msg' => '关联失败')));
            }
            Model::commit();
        }catch (\Exception $e){
            Model::rollback();
        }
    }

    function depart_s(){
        $data = $this->req;
        $log_str = "[Admin->coupon->depart]  "."\n". "post_data:".json_encode($data);
        CouponAdminLogs($log_str);
        Model::startTrans();
        try{
            $coupon_info = M('')->where(array())->find();
            $post_info[''] = $coupon_info[''];
            $headers = array("Content-Type : application/json;charset=UTF-8");
            $return_data = httpRequest('',
                'POST',$post_info,$headers);
            $log_str = "[Admin->coupon->depart]  "."\n". "return_data:".json_encode($return_data);
            CouponAdminLogs($log_str);
            if(!$coupon_info['match_status'] == 1) {
                exit(json_encode(array('code' => 1, 'msg' => '此优惠卷已关联过')));
            }
            if ($return_data['code'] == 200) {

            }else{
                exit(json_encode(array('code' => 1, 'msg' => '关联失败')));
            }
            Model::commit();
        }catch (\Exception $e){
            Model::rollback();
        }
    }
}