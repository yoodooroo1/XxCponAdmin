<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/6
 * Time: 14:16
 */

namespace Admin\Controller;



use Think\Db;

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


}