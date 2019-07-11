<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2017/4/26
 * Time: 15:35
 */

namespace Common\Model;
class FormModel extends BaseModel
{
    protected $tableName = 'mb_form';
	
	/*表单列表
	 * params array $where  条件 
	 * params int $store_id 店铺ID
	 * params int $page 页数
	 * params int $limit 每页条数
	*/
	public function getFormList($store_id = '' ,$where = array() ,$page = 1,$limit = 15){
		$where['store_id'] = $store_id;
		$where['isdelete'] = 0;
		$start = ($page-1)*$limit;
		$lists =  $this->where($where)->field(true)->limit($start,$limit)->select(); 
		
		$api_url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?c=Form&a=formInfo";
		foreach($lists as &$li){
			$li['submit_item'] = $this->getFormParams($li['form_id'] , $store_id);
			$li['link_url'] = $api_url . (strpos($api_url, '?') !== false ? '&' : '?') . 'se=' . $store_id. '&form_id=' . $li['form_id'];  
		}   
		$result = array();  
		$result['code'] = 200;
		
		$result['msg'] = '加载成功';
		$result['data']['total'] = $this->where($where)->count(); 
		$result['data']['lists'] = $lists;
		return $result;   
	}        
	
	/* 获取表单详情 
	 * params int $form_id 表单ID
	 * params string $store_id 店铺ID
	*/
	public function getFormInfo($form_id = '' ,$store_id = ''){
		$where = array(); 
		$where['form_id'] = $form_id;
		$where['store_id'] = $store_id;
		$where['isdelete'] = 0;
		$info = $this->where($where)->field(true)->find();
		if(!empty($info)){
			$info['form_info'] = htmlspecialchars_decode($info['form_info']);   
			$info['submit_item'] = $this->getFormParams($form_id , $store_id);
		}
		return $info ; 
		
	} 

	/* 删除表单
	 * params int $form_id 表单ID
	 * params string $store_id 店铺ID
	*/
	public function delFormInfo($form_id = '' ,$store_id = ''){
		$where = array(); 
		$where['form_id'] = $form_id;
		$where['store_id'] = $store_id;
		$where['isdelete'] = 0;
		$check = $this->where($where)->save(array('isdelete'=>1));
		if($check !== false){
			return true;
		}else{
			return false;
		}
		
		
	} 	
	
	/*获取表单提交申请列表
	  * params string $store_id 店铺ID
	  * params array $where 条件
	  * params int $page 页数
	  * params int $limit 每页条数
	*/
	public function getFormOrderList($store_id = '' , $where =array(),$page = 1,$limit = 15){
		$where['store_id'] = $store_id;
		$where['isdelete'] = 0;
		$start = ($page-1)*$limit;
		$order_ids = M('mb_form_order')->where($where)->limit($start,$limit)->order('order_id DESC')->getField('order_id',true);
		$lists = array();
		foreach($order_ids as $order_id){
			$lists[] = $this->getFormOrderInfo($order_id,$store_id);
		}
		          
		$result = array();
		$result['code'] = 200;  
		$result['data']['total'] = M('mb_form_order')->where($where)->count();  
		$result['data']['lists'] = $lists;
		return $result;   
	}

    /*获取表单提交申请列表
      * params string $store_id 店铺ID
      * params array $where 条件
      * params int $page 页数
      * params int $limit 每页条数
    */
    public function getMemberFormOrderList($store_id = '' , $where =array(),$page = 1,$limit = 15){
        $where['store_id'] = $store_id;
        $where['isdelete'] = 0;
        $start = ($page-1)*$limit;
        $lists = M('mb_form_order')->where($where)->limit($start,$limit)->order('order_id DESC')->select();
        $formInfo = array();
        foreach($lists as &$list){
            if(empty($formInfo[$list['form_id']])){
                $formInfo[$list['form_id']] = $this->where(array('form_id'=>$list['form_id']))->field('form_name,isdelete')->find();
            }
            $list['from_info'] = $formInfo[$list['form_id']];
        }
        $result = array();
        $result['code'] = 200;
        $result['data']['total'] = M('mb_form_order')->where($where)->count();
        $result['data']['lists'] = $lists;
        return $result;
    }

    /*删除表单提交申请
      * params string $store_id 店铺ID
      * params array $where 条件
    */
	public function delFormOrder($store_id = '' ,$where = array()){
		$order = M('mb_form_order');
		$where['store_id'] = $store_id;
		$check = $order->where($where)->save(array('isdelete'=>1));
		if($check !== false){
			return true;
		}else{
			return false;
		}
	}
	
	/*获取表单提交申请详情
	  * params string $order_id 申请ID
	  * params array $store_id 店铺ID   
	*/
	public function getFormOrderInfo($order_id = '' ,$store_id = ''){
		$where = array(); 
		$where['xunxin_mb_form_order.order_id'] = $order_id;
		$where['xunxin_mb_form_order.store_id'] = $store_id;
		$where['xunxin_mb_form_order.isdelete'] = 0;
		$info =  M('mb_form_order')->join('xunxin_mb_form ON xunxin_mb_form.form_id=xunxin_mb_form_order.form_id')->where($where)->field('xunxin_mb_form_order.*,xunxin_mb_form.form_name')->find();
	
		if(!empty($info)){
			$info['opend_id'] = M('mb_storemember')->where(array('member_id'=>$info['member_id'],'store_id'=>$store_id))->getField('wx_openid');
			$items_arr = json_decode($info['items'],true);   //用户选取的选项
			$items = array();
			foreach($items_arr as $item){        
				$items[$item['item_id']] = $item;    
			}  	
			$w  = array();
			$w['input_show'] = 1;       
			$params = $this->getFormParams($info['form_id'],$store_id,$w);
			$chooseResults = array();
			foreach($params as &$param){
				$chooseResult = array();
				$chooseResult['name'] = $param['input_item_name'];
				$chooseResult['type'] = $param['input_type'];
					
				if(is_array($items[$param['item_id']]) && !empty($items[$param['item_id']])){
					$param['default'] = $items[$param['item_id']]['value'];
					if($param['input_type'] == 'checkbox'){
						$str = '';
						foreach($items[$param['item_id']]['value'] as $val){
							$str = $param['chooseParam'][$val] . ' ' . $str;
						} 
						$chooseResult['value'] = $str;
					}else if($param['input_type'] == 'radio' || $param['input_type'] == 'select'){
						$chooseResult['value'] = $param['chooseParam'][$items[$param['item_id']]['value']]; 
					}elseif($param['input_type'] == 'text' ||  $param['input_type'] == 'textarea' ||  $param['input_type'] == 'date' ||  $param['input_type'] == 'time' ||  $param['input_type'] == 'date_time' || $param['input_type'] == 'imgs' ){
						$chooseResult['value'] =  $items[$param['item_id']]['value'] ;
					}
				}else{  
					$chooseResult['value'] =  '' ; 
				}
				$chooseResults[] = $chooseResult;
				  
			}
			
			$log = M('mb_form_order_log');
			$w2 = array();  
			$w2['form'] = $info['form'];
			$w2['order_id'] = $info['order_id'];
			$w2['store_id'] = $store_id; 
			$w2['is_delete'] = 0;
			$log_list = $log->order('log_id DESC')->where($w2)->select();
			
			$info['log_list'] = $log_list; 
			$info['params'] = $params;   
			$info['chooseResult'] = $chooseResults;  
		}    
		return $info ; 
		
	}

    /*获取表单提交申请详情
      * params string $order_id 申请ID
      * params array $store_id 店铺ID
    */
    public function getMemberFormOrderInfo($order_id = '' ,$member_id = '',$store_id = ''){
        $where = array();
        $where['order_id'] = $order_id;
        $where['member_id'] = $member_id;
        $where['store_id'] = $store_id;
        $where['isdelete'] = 0;
        $info =  M('mb_form_order')->where($where)->field(true)->find();
        if(!empty($info)){
            $form_name  = $this->where(array('form_id'=>$info['form_id']))->getField('form_name');
            $info['form_name'] = $form_name;
            $items_arr = json_decode($info['items'],true);   //用户选取的选项
            $items = array();
            foreach($items_arr as $item){
                $items[$item['item_id']] = $item;
            }
            $w  = array();
            $w['input_show'] = 1;
            $params = $this->getFormParams($info['form_id'],$store_id,$w);
            $chooseResults = array();
            foreach($params as &$param){
                $chooseResult = array();
                $chooseResult['name'] = $param['input_item_name'];
                $chooseResult['type'] = $param['input_type'];

                if(is_array($items[$param['item_id']]) && !empty($items[$param['item_id']])){
                    $param['default'] = $items[$param['item_id']]['value'];
                    if($param['input_type'] == 'checkbox'){
                        $str = '';
                        foreach($items[$param['item_id']]['value'] as $val){
                            $str = $param['chooseParam'][$val] . ' ' . $str;
                        }
                        $chooseResult['value'] = $str;
                    }else if($param['input_type'] == 'radio' || $param['input_type'] == 'select'){
                        $chooseResult['value'] = $param['chooseParam'][$items[$param['item_id']]['value']];
                    }elseif($param['input_type'] == 'text' ||  $param['input_type'] == 'textarea' ||  $param['input_type'] == 'date' ||  $param['input_type'] == 'time' ||  $param['input_type'] == 'date_time' || $param['input_type'] == 'imgs' ){
                        $chooseResult['value'] =  $items[$param['item_id']]['value'] ;
                    }
                }else{
                    $chooseResult['value'] =  '' ;
                }
                $chooseResults[] = $chooseResult;

            }

            $log = M('mb_form_order_log');
            $w2 = array();
            $w2['form_id'] = $info['form_id'];
            $w2['order_id'] = $info['order_id'];
            $w2['store_id'] = $store_id;
            $w2['is_delete'] = 0;
            $log_list = $log->order('log_id DESC')->where($w2)->select();
            $info['log_list'] = $log_list;
            //$info['params'] = $params;
            $info['chooseResult'] = $chooseResults;
        }
        return $info ;

    }
	
	/*获取表单变量详情
	 * params int $form_id : 表单ID
	 * params int $store_id : 店铺ID
	 * params int $item_id : 变量ID
	*/
	public function getFormItemInfo($item_id = '' ,$form_id = '' , $store_id = ''){
		$item = M('mb_form_item');
		$w = array();
		$w['item_id'] = $item_id;
		$w['form_id'] = $form_id;
		$w['store_id'] = $store_id;
		$w['is_delete'] = 0;
		$info = $item->where($w)->find();
		return $info; 
	}

   	
	
	/*获取表单变量列表
	 * params int $form_id : 表单ID
	 * params int $store_id : 店铺ID
	*/
	public function getFormParams($form_id = '' , $store_id = 0 , $where = array()){
		$where['store_id'] = $store_id;
		$where['form_id'] = $form_id;
		if (isInWap() || isInWeb()){
            $where['input_show'] = 1;
        }
		$where['is_delete'] = 0;
		$item = M('mb_form_item') ;
		$params_list  = $item->where($where)->field(true)->order('sort DESC')->select();
		
		foreach($params_list as &$list){
			if($list['input_type'] == 'checkbox' || $list['input_type'] == 'radio' || $list['input_type'] == 'select'){
				$list['chooseParam'] = explode('|',$list['input_remark']);
			}else{
				$list['chooseParam'] =$list['input_remark'];
			}
		}
		return $params_list;
	}

}   