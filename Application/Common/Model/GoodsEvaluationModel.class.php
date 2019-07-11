<?php
/**
 * Created by PhpStorm.
 * User: honglj
 * Date: 16/6/24
 * Time: 10:50
 */

namespace Common\Model;

use Common\Model\BaseModel;
use Common\Logic\CartLogic;
use Common\Model\UtilModel;
use Think\Cache\Driver\Redis;

class GoodsEvaluationModel extends BaseModel
{
    protected $tableName = 'mb_goods_evaluation';

    /*获取评价总数
     * params int $store_id 店铺ID  
     * params array $condition 附加查询条件
    */
    public function getEvaluationTotal($store_id ,$condition =  array()){
        $w = array();    
        $w['is_delete'] = 0;
        $w['type'] = 1;
        $w['store_id'] = $store_id;
		if(isset($condition['type'])){
			if($condition['type'] == '1'){
				$condition['goods_value'] = array('egt',4);
			}else if($condition['type'] == '2'){
				$condition['goods_value'] = array(array('egt',2),array('lt',4));
			}else if($condition['type'] == '3'){
				$condition['goods_value'] = array('lt',2);
			}else if($condition['type'] == '4'){
				$condition['imgs'] = array(array('neq',''),array('neq','[]'));
			}else if($condition['type'] == '5'){
				$condition['more_id'] = array('neq','0');
			}
			unset($condition['type']); 
		} 
		if(!empty($condition['keyword'])){
			$condition['_string'] = 'order_id LIKE "%'. $condition['keyword'] .'%" OR goods_name LIKE "%'.$condition['keyword'].'%"';
		}
		if(!empty($condition['start_time']) && empty($condition['end_time'])){
			$condition['add_time'] = array('egt',strtotime($condition['start_time']));
		}else if(empty($condition['start_time']) && !empty($condition['end_time'])){
			$condition['add_time'] = array('elt',strtotime($condition['end_time']));
		}else if(!empty($condition['start_time']) && !empty($condition['end_time'])){
			$condition['add_time'] = array(array('egt',strtotime($condition['start_time'])),array('elt',strtotime($condition['end_time'])));
		}

		if($condition['replay_type'] == '1'){
			$w = array();
			$w['_string'] = "replay_content != '' OR  (replay_imgs != '' AND replay_imgs != '[]') ";
			$condition['_complex'] = $w;
		}else if($condition['replay_type'] == '-1'){
			$w = array();
			$w['_string'] = "replay_content = '' AND  (replay_imgs = '' OR replay_imgs = '[]') ";
			$condition['_complex'] = $w;
		} 
		unset($condition['keyword']);      
		unset($condition['start_time']); 
		unset($condition['end_time']); 
		unset($condition['replay_type']); 
		
		if(!empty($condition)){
			$w['_complex'] = $condition; 
		}
        $total = $this->where($w)->count();

        return $total;
    }

    /*获取用户未评价总数
     * params int $store_id 店铺ID
     * params array $condition 附加查询条件
    */
    public function getNoEvaluationTotal($store_id ,$member_id){
        $m = M('mb_order_goods');
        $order = M('mb_order');
        $w = array();
        $w['issuccess'] = 1;
        $w['isdelete'] = 0;
        $w['storeid'] = $store_id;
        $w['buyer_id'] = $member_id;
        $order_ids = $order->where($w)->getField('order_id',true);

        $order_str = "-1" ;
        if(!empty($order_ids)){
            $order_str = implode(',',$order_ids);
        }
        $where = array();
        $where['xunxin_mb_order_goods.store_id'] = $store_id;
        $where['xunxin_mb_order_goods.order_id'] = array('in',$order_str);
        $where['xunxin_mb_goods_evaluation.evaluation_id'] = array(array('exp', 'IS NULL'),array('eq', ''),'or');
        $where['xunxin_mb_goods_evaluation.is_delete'] = array(array('exp', 'IS NULL'),array('neq', '0'),'or');
        $total =    $m->join('LEFT JOIN xunxin_mb_goods_evaluation ON xunxin_mb_order_goods.goods_id = xunxin_mb_goods_evaluation.goods_id and  xunxin_mb_order_goods.order_id = 	
						xunxin_mb_goods_evaluation.order_id')
            ->where($where)
            ->count();
        return $total;
    }

    /*通过评价ID 获取评价详情*/
    public function getEvaluationInfoById($evaluation_id,$store_id = ''){
        $where = array();
        $where['xunxin_mb_goods_evaluation.evaluation_id'] = $evaluation_id;
        $where['xunxin_mb_goods_evaluation.type'] = 1;
        //$where['xunxin_mb_goods_evaluation.is_show'] = 1;
        $where['xunxin_mb_goods_evaluation.is_delete'] = 0;
		$where['xunxin_mb_goods_evaluation.store_id'] = $store_id;     
		 
        $info =   $this->join('xunxin_member ON xunxin_member.member_id = xunxin_mb_goods_evaluation.member_id')
            ->join('LEFT JOIN xunxin_mb_storemember ON xunxin_mb_storemember.member_id = xunxin_mb_goods_evaluation.member_id AND xunxin_mb_goods_evaluation.store_id = xunxin_mb_storemember.store_id')
            ->where($where)
            ->field('xunxin_mb_goods_evaluation.*,xunxin_member.member_name,xunxin_member.member_nickname,xunxin_member.member_avatar,xunxin_mb_storemember.level')
            ->find();
		if($info['is_anonymous'] == '1'){
			$info['member_nickname'] = '匿名用户';
		}   	
        if($info['more_id'] > 0){
            $w = array();
            $w['type'] = 2;
            $w['evaluation_id'] = $info['more_id'];
            $more_info = $this->where($w)->field(true)->find();

            $time = ($more_info['add_time'] - $info['add_time'])/3600 ; //时间
            if($time >= 24){
                $more_info['time_str'] = '用户'.ceil($time/24).'天后追评';
            }else{
                $more_info['time_str'] = '用户'.ceil($time) . '小时后追评';
            }
            $info['more_info'] = $more_info	;
        }else{
            $info['more_info'] = array();
        }
        return $info;
    }

    /*通过订单ID,会员ID,商品ID 获取评价详情*/
    public function getEvaluationInfoByOrder($member_id,$order_id,$goods_id){
        $where = array();
        $where['xunxin_mb_goods_evaluation.store_id'] = $store_id;
        $where['xunxin_mb_goods_evaluation.member_id'] = $member_id;
        $where['xunxin_mb_goods_evaluation.goods_id'] = $goods_id;
        $where['xunxin_mb_goods_evaluation.type'] = 1;
        $where['xunxin_mb_goods_evaluation.is_show'] = 1;
        $where['xunxin_mb_goods_evaluation.is_delete'] = 0;
        $info =   $this->join('xunxin_member ON xunxin_member.member_id = xunxin_mb_goods_evaluation.member_id')
            ->join('LEFT JOIN xunxin_mb_storemember ON xunxin_mb_storemember.member_id = xunxin_mb_goods_evaluation.member_id AND xunxin_mb_goods_evaluation.store_id = xunxin_mb_storemember.store_id')
            ->where($where)
            ->field('xunxin_mb_goods_evaluation.*,xunxin_member.member_name,xunxin_member.member_nickname,xunxin_member.member_avatar,xunxin_mb_storemember.level')
            ->find();
		if($info['is_anonymous'] == '1'){
			$info['member_nickname'] = '匿名用户';
		}  	
        if($info['more_id'] > 0){
            $w = array();
            $w['type'] = 2;
            $w['evaluation_id'] = $info['more_id'];
            $more_info = $this->where($w)->field(true)->find();

            $time = ($more_info['add_time'] - $info['add_time'])/3600 ; //时间
            if($time >= 24){
                $more_info['time_str'] = '用户'.ceil($time/24).'天后追评';
            }else{
                $more_info['time_str'] = '用户'.ceil($time) . '小时后追评';
            }
            $info['more_info'] = $more_info	;
        }else{
            $info['more_info'] = array();
        }
        return $info;
    }

    /*前端统计评价分类和数量*/
    public function getEvaluationClass($store_id ,$goods_id ){
        $class = array();

        $w = array();
        $w['is_show'] = 1;
        $w['is_delete'] = 0;
        $w['type'] = 1;
        $w['store_id'] = $store_id;
        $w['goods_id'] = $goods_id;
        $da = array();
        $da['type'] = 0;
        $da['type_name'] = '全部';
        $da['num'] = $this->where($w)->count();
        $class[] = $da;

        $da1  =array();   //好评
        $da1['type'] = 1 ;
        $da1['type_name'] = '好评';
        $w1 = array();
        $w1['goods_value'] = array('egt',4);
        $w1['_complex'] = $w;
        $da1['num'] = $this->where($w1)->count();
        $class[] = $da1;

        $da2  =array();   //中评
        $da2['type'] = 2 ;
        $da2['type_name'] = '中评';
        $w2 = array();
        $w2['goods_value'] = array(array('egt',2),array('lt',4));
        $w2['_complex'] = $w;
        $da2['num'] = $this->where($w2)->count();
        $class[] = $da2;

        $da3  =array();   //差评
        $da3['type'] = 3 ;
        $da3['type_name'] = '差评';
        $w3 = array();
        $w3['goods_value'] = array('lt',2);
        $w3['_complex'] = $w;
        $da3['num'] = $this->where($w3)->count();
        $class[] = $da3;

        $da4  =array();   //有图
        $da4['type'] = 4 ;
        $da4['type_name'] = '有图';
        $w4 = array();
        $w4['imgs'] = array(array('neq',''),array('neq','[]'));
        $w4['_complex'] = $w;
        $da4['num'] = $this->where($w4)->count();
        $class[] = $da4;

        $da5  =array();   //追加评论
        $da5['type'] = 5 ;
        $da5['type_name'] = '追加评论';
        $w5 = array();
        $w5['more_id'] = array('neq','0');
        $w5['_complex'] = $w;
        $da5['num'] = $this->where($w5)->count();
        $class[] = $da5;
        return $class;

    }
    /**
     * 显示评价列表
     * @param $store_id 店铺ID
     * @param $page     页数
     * @param $limit    每页条数
     * @param $condition 附加条件  condition['type'] : 1-好评 2-中评 3-差评 4-有图 5-追加评论 6-未追加评论; condition['goods_id'] : 商品ID ;  condition['member_id'] : 会员ID
    condition['start_time'] 评价开始时间 ； condition['end_time'] 评价结束时间 ；  condition['replay_type'] : 回复状态 ：1 已回复 -1 未回复
    condition['is_show'] 是否隐藏
     * @return array
     */
    public function getEvaluationList($store_id, $page = 1 , $limit = 10  , $condition = array())
    {
        $where = array();
        $where['xunxin_mb_goods_evaluation.store_id'] = $store_id;

        $where['xunxin_mb_goods_evaluation.type'] = 1;
        $where['xunxin_mb_goods_evaluation.is_delete'] = 0;
        $where['xunxin_mb_storemember.store_id'] = $store_id;
        if(!empty($condition)){
            if($condition['is_show'] != ''){
                $where['xunxin_mb_goods_evaluation.is_show'] = $condition['is_show'];
            }
            if($condition['type'] == '1'){
                $where['xunxin_mb_goods_evaluation.goods_value'] = array('egt',4);
            }else if($condition['type'] == '2'){
                $where['xunxin_mb_goods_evaluation.goods_value'] = array(array('egt',2),array('lt',4));
            }else if($condition['type'] == '3'){
                $where['xunxin_mb_goods_evaluation.goods_value'] = array('lt',2);
            }else if($condition['type'] == '4'){
                $where['xunxin_mb_goods_evaluation.imgs'] = array(array('neq',''),array('neq','[]'));
            }else if($condition['type'] == '5'){
                $where['xunxin_mb_goods_evaluation.more_id'] = array('neq','0');
            }else if($condition['type'] == '6'){
                $where['xunxin_mb_goods_evaluation.more_id'] = array('eq','0');
            }  
            if(!empty($condition['goods_id'])){
                $where['xunxin_mb_goods_evaluation.goods_id'] = $condition['goods_id'];
            }
            if(!empty($condition['member_id'])){
                $where['xunxin_mb_goods_evaluation.member_id'] = $condition['member_id'];
            }
            if(!empty($condition['keyword'])){
                $where['_string'] = 'xunxin_mb_goods_evaluation.order_id LIKE "%'. $condition['keyword'] .'%" OR xunxin_mb_goods_evaluation.goods_name LIKE "%'.$condition['keyword'].'%"';
            }
            if(!empty($condition['start_time']) && empty($condition['end_time'])){
                $where['xunxin_mb_goods_evaluation.add_time'] = array('egt',strtotime($condition['start_time']));
            }else if(empty($condition['start_time']) && !empty($condition['end_time'])){
                $where['xunxin_mb_goods_evaluation.add_time'] = array('elt',strtotime($condition['end_time']));
            }else if(!empty($condition['start_time']) && !empty($condition['end_time'])){
                $where['xunxin_mb_goods_evaluation.add_time'] = array(array('egt',strtotime($condition['start_time'])),array('elt',strtotime($condition['end_time'])));
            }

            if($condition['replay_type'] == '1'){
                $w = array();
                $w['_string'] = "xunxin_mb_goods_evaluation.replay_content != '' OR  (xunxin_mb_goods_evaluation.replay_imgs != '' AND xunxin_mb_goods_evaluation.replay_imgs != '[]') ";
                $where['_complex'] = $w;
            }else if($condition['replay_type'] == '-1'){ 
                $w = array();
                $w['_string'] = "xunxin_mb_goods_evaluation.replay_content = '' AND  (xunxin_mb_goods_evaluation.replay_imgs = '' OR xunxin_mb_goods_evaluation.replay_imgs = '[]') ";
                $where['_complex'] = $w;
            }
        }

        $lists =   $this->join('xunxin_member ON xunxin_member.member_id = xunxin_mb_goods_evaluation.member_id')
            ->join('LEFT JOIN xunxin_mb_storemember ON xunxin_mb_storemember.member_id = xunxin_mb_goods_evaluation.member_id')
            ->where($where)
            ->field('xunxin_mb_goods_evaluation.*,xunxin_member.member_name,xunxin_member.member_nickname,xunxin_member.member_avatar,xunxin_mb_storemember.level')
            ->limit(($page - 1) * $limit, $limit)
            ->order('xunxin_mb_goods_evaluation.evaluation_id DESC')
            ->select();
       
        foreach($lists as &$list){ 
			if($list['is_anonymous'] == '1'){
				$list['member_nickname'] = '匿名用户';
			}  
            $list['imgs'] = json_decode($list['imgs'],true);  
			$list['replay_imgs'] = json_decode($list['replay_imgs'],true);			
            $list['goods_img'] = json_decode($list['goods_figure'],true)[0]['url'] ;               
            if($list['more_id'] > 0){    
                $w = array();
                $w['type'] = 2;     
                $w['evaluation_id'] = $list['more_id'];
                $more_info = $this->where($w)->field(true)->find();
				if(!empty($more_info)){
					$more_info['imgs'] = json_decode($more_info['imgs'],true);  
					$more_info['replay_imgs'] = json_decode($more_info['replay_imgs'],true);		
				}
                $time = ($more_info['add_time'] - $list['add_time'])/3600 ; //时间
                if($time >= 24){
                    $more_info['time_str'] = '用户'.ceil($time/24).'天后追评';
                }else{
                    $more_info['time_str'] = '用户'.ceil($time) . '小时后追评';
                }
                $list['more_info'] = $more_info	;
            }else{
                $list['more_info'] = array();
            }
        }
        return $lists;
    }




    /**
     * 前端显示会员未评价列表
     * @param $store_id 店铺ID
     * @param $member_id 会员ID
     * @param $page     页数
     * @param $limit    每页条数
     * @param $condition 附加条件 
     * @return array
     */
    public function getNoEvaluationListByMemberId($store_id, $member_id , $page = 1 , $limit = 10  , $condition = array())
    {
        $m = M('mb_order_goods');
        $order = M('mb_order');
        $w = array();  
        $w['issuccess'] = 1;
        $w['isdelete'] = 0; 
		$w['storeid'] = $store_id;
		$w['buyer_id'] = $member_id;
        $order_ids = $order->where($w)->getField('order_id',true);

        $order_str = "-1" ;
        if(!empty($order_ids)){
            $order_str = implode(',',$order_ids);
        }
	

        $where = array();
        $where['xunxin_mb_order_goods.store_id'] = $store_id;
        $where['xunxin_mb_order_goods.order_id'] = array('in',$order_str);
        $where['xunxin_mb_goods_evaluation.evaluation_id'] = array(array('exp', 'IS NULL'),array('eq', ''),'or');
		if(!empty($condition['og_id'])){
			 $where['xunxin_mb_order_goods.id'] = $condition['og_id'];
		}      
        $lists =    $m->join('LEFT JOIN xunxin_mb_goods_evaluation ON xunxin_mb_order_goods.goods_id = xunxin_mb_goods_evaluation.goods_id and  xunxin_mb_order_goods.order_id = 	
						xunxin_mb_goods_evaluation.order_id')
					  ->join('xunxin_goods ON xunxin_goods.goods_id = xunxin_mb_order_goods.goods_id')	
            ->where($where)
            ->field('xunxin_mb_order_goods.id,xunxin_mb_order_goods.goods_name,xunxin_mb_order_goods.spec_name,xunxin_goods.goods_figure,xunxin_mb_order_goods.goods_price,xunxin_mb_order_goods.gou_num')
            ->limit(($page - 1) * $limit, $limit)
            ->order('xunxin_mb_order_goods.id DESC')                          
            ->select();
        foreach($lists as &$list){
            $list['goods_img'] = json_decode($list['goods_figure'],true)[0]['url'];
        }
        return $lists;
    }

    /*前端用户点赞或取消点赞
     * params int $member_id 会员ID
     * params int $evaluation_id 评价ID
     * return array $rt
    */
    public function dealEvaluationPraise($member_id , $evaluation_id){
        $m = M('mb_goods_evaluation_praise');
        $w = array();
        $w['member_id'] = $member_id;
        $w['evaluation_id'] = $evaluation_id;
        $info = $m->where($w)->field(true)->find();
        $rt = array();
        $rt['code'] = 200;
		$evaluation_info = $this->where(array('evaluation_id'=>$evaluation_id))->field(true)->find();
		if(empty($member_id)){    
			$rt['code'] = -1;
			$rt['msg'] = '您还未登录';
		}
        else if(empty($info)){
            $da = array();
            $da['member_id'] = $member_id;
            $da['evaluation_id'] = $evaluation_id;
            $da['is_delete'] = 0;
            $da['add_time'] = time();
            if($m->add($da)){
                $this->where(array('evaluation_id'=>$evaluation_id))->setInc('praise_num',1);
				$rt['data']['status'] = 1;
				$rt['data']['praise_num'] = $evaluation_info['praise_num'] +1; 
            }else{ 
                $rt['code'] = -1;
                $rt['msg'] = '新增点赞记录失败';
            }
        }else{
            if($info['is_delete'] == '0'){
                $check = $m->where($w)->save(array('is_delete'=>1));
                if($check !== false){
                    $this->where(array('evaluation_id'=>$evaluation_id))->setDec('praise_num',1);
					$rt['data']['status'] = 0;
					$rt['data']['praise_num'] = $evaluation_info['praise_num']  - 1; 
                }else{
                    $rt['code'] = -1;
                    $rt['msg'] = '取消点赞记录失败';
                }    
            }else{    
                $check = $m->where($w)->save(array('is_delete'=>'0','add_time'=>time()));
                if($check !== false){
                    $this->where(array('evaluation_id'=>$evaluation_id))->setInc('praise_num',1);
					$rt['data']['status'] = 1;
					$rt['data']['praise_num'] = $evaluation_info['praise_num'] +1; 
                }else{
                    $rt['code'] = -1;
                    $rt['msg'] = '新增点赞记录失败';
                }
            }
        }
	 
        return $rt; 
    }

    /* 用户评价数据提交
     * params tinyint $type 评价类型 1-普通评价 2-追评
     * params int $store_id 店铺ID
     * params int $goods_id 商品ID
     * params int $member_id 会员ID
     * params array $data 评价的内容
     * params int $evaluation_id 追评时 追评的评价ID
     * return array $rt
     *
    */
    public function addMemberEvaluation($type = 1 , $store_id  = 0, $goods_id = 0 , $member_id = 0 , $order_id = 0 ,$data = array() , $evaluation_id = '' ){
        $rt = array();
        $rt['code'] = 200;
        if($type == '2' && empty($evaluation_id)){
            $rt['code'] = -1;
            $rt['msg'] = '追评参数缺失';
        }else{
            $w = array();
            $w['type'] = $type;
            $w['store_id'] = $store_id;
            $w['goods_id'] = $goods_id;
            $w['order_id'] = $order_id;        
            $w['member_id'] = $member_id;
            $w['is_delete'] = 0;
            $info = $this->where($w)->find(); 
            if(empty($info)){
                $info2 = $this->getEvaluationInfoById($evaluation_id,$store_id);
                if(!empty($info2) || ($type == '1')){
					$order_goods = M('mb_order_goods')->where(array('store_id'=>$store_id,'goods_id'=>$goods_id,'order_id'=>$order_id))->field('goods_name,goods_price,spec_name,gou_num')->find();
					$goods_figure = M('goods')->where(array('store_id'=>$store_id,'goods_id'=>$goods_id))->getField('goods_figure');
					
                    $data['type'] = $type;
                    $data['store_id'] = $store_id;
                    $data['goods_id'] = $goods_id;  
                    $data['order_id'] = $order_id;    
                    $data['goods_name'] = $order_goods['goods_name']; 
                    $data['goods_price'] = $order_goods['goods_price']; 
                    $data['goods_figure'] = $goods_figure; 
                    $data['gou_num'] = $order_goods['gou_num']; 
                    $data['member_id'] = $member_id;
                    $data['goods_value'] = empty($data['goods_value']) ? $info2['goods_value'] : $data['goods_value'];
                    $data['spec_name'] = $order_goods['spec_name'];
                    $data['logistics_value'] = empty($data['logistics_value']) ? $info2['logistics_value'] : $data['logistics_value'];
                    $data['service_value'] = empty($data['service_value']) ? $info2['service_value'] : $data['service_value'];
                    $data['add_time'] = time();
                    $data['is_show'] = 1;
                    $data['is_delete'] = 0;  
                    $more_id = $this->add($data);
					$rt['data'] = $more_id;   
                    if($more_id && $type == '2'){
                        $this->where(array('evaluation_id'=>$evaluation_id))->save(array('more_id'=>$more_id));
                    }else{
						if(!$more_id){
							$rt['code'] = -1;
							$rt['msg'] = '提交评价失败';
						}  
                    }
                }else{
                    $rt['code'] = -1;
                    $rt['msg'] = '首次评论不存在';
                }

            }else{
                $rt['code'] = -1;
                $rt['msg'] = '您已经评价过了';
            }

        }
        return $rt;
    }


    /*---------------------------底下主要是后台方法-------------------------------*/

    /*后台处理评论
     * params int $type : 类型 1：隐藏评论 2：删除评论 3： 编辑评论
     * params int $evaluation_id : 评论ID
     * params array $datas : 编辑评论时评论数据
    */
    public function dealEvaluation($type,$store_id , $evaluation_id ,$datas = array()){
        $info = $this->getEvaluationInfoById($evaluation_id,$store_id);
        $rt = array();
        $rt['code'] = 200;
        if(empty($info)){
            $rt['code'] = -1;
            $rt['msg'] = '该评论不存在';
        }else{
            if($type == '1'){
                if($info['is_show'] == '1'){
                    $check = $this->where(array('evaluation_id'=>$evaluation_id))->save(array('is_show'=>0));
                }else{
                    $check = $this->where(array('evaluation_id'=>$evaluation_id))->save(array('is_show'=>1));
                }
                if($check === false){
                    $rt['code'] = -1;
                    $rt['msg'] = '隐藏/显示 评论失败';
                }
            }else if($type == '2'){
                $check = $this->where(array('evaluation_id'=>$evaluation_id))->save(array('is_delete'=>1));
                if($check === false){
                    $rt['code'] = -1;
                    $rt['msg'] = '2：删除评论失败';
                }
            }else if($type == '3'){
                $data = array();
                $data['goods_value'] = $datas['goods_value'];
                $data['content'] = $datas['content'];
                $data['imgs'] = $datas['imgs'];
                $data['logistics_value'] = $datas['logistics_value'];
                $data['service_value'] = $datas['service_value'];
                $data['replay_content'] = $datas['replay_content'];
                $data['replay_imgs'] = $datas['replay_imgs'];
                $data['is_show'] = $datas['is_show'];

                /*判断是否有追评*/
                if(empty($datas['more_content']) && ($datas['more_imgs'] == '' || $datas['more_imgs'] == '[]')){
                    $data['more_id'] = 0;
                }
				
                $check1 = $this->where(array('evaluation_id'=>$evaluation_id))->save($data);
                if($check1 !== false){
                    if(!empty($datas['more_content']) || ($datas['more_imgs'] != '' && $datas['more_imgs'] != '[]')){   //有追评
                        $data2 = array();
                        $data2['type'] = 2;
                        $data2['store_id'] = $info['store_id'];
                        $data2['order_id'] = $info['order_id'];
                        $data2['goods_id'] = $info['goods_id'];
						$data2['goods_name'] = $info['goods_name']; 
						$data2['goods_price'] = $info['goods_price']; 
						$data2['goods_figure'] = $info['goods_figure']; 
						$data2['gou_num'] = $info['gou_num'];     
                        $data2['spec_name'] = $info['spec_name'];
                        $data2['member_id'] = $info['member_id'];
                        $data2['goods_value'] = $info['goods_value'];
                        $data2['is_anonymous'] = $info['is_anonymous'];
                        $data2['content'] = $datas['more_content'];
                        $data2['imgs'] = $datas['more_imgs'];
                        $data2['logistics_value'] = $info['logistics_value'];
                        $data2['service_value'] = $info['service_value'];
                        $data2['replay_content'] = $datas['more_replay_content'];
                        $data2['replay_imgs'] = $datas['more_replay_imgs'];
                        $data2['is_show'] = 1; 
                        $data2['is_delete'] = 0;
					  
                        if($info['more_id'] > 0){
                            $check2 = $this->where(array('evaluation_id'=>$info['more_id']))->save($data2);
                            if($check2 === false){
                                $rt['code'] = -1;
                                $rt['msg'] = '编辑追加评论失败';
                            }
                        }else{
                            $data2['add_time'] = time();
							   
                            $more_id = $this->add($data2);
                            if($more_id){
                                $this->where(array('evaluation_id'=>$evaluation_id))->save(array('more_id'=>$more_id));
                            }else{
                                $rt['code'] = -1;
                                $rt['msg'] = '编辑追加评论失败';
                            }
                        }
                    }else{
                        if($info['more_id'] > 0 ){
                            $this->where(array('evaluation_id'=>$info['more_id']))->save(array('is_delete'=>1));
                        }
                    }
                }else{
                    $rt['code'] = -1;
                    $rt['msg'] = '编辑评论失败';
                }
            }else{
                $rt['code'] = -1;
                $rt['msg'] = '参数错误';
            }
        }
	
		return $rt; 

    } 
}