<?php

namespace Common\Model;

use Think\HJPage;

class GoodsBrowsingHistoryModel extends BaseModel
{

    // 定义表名
    protected $tableName = 'mb_goods_browsing_history';

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        if (isFormal()) {
            $this->connection = array(
                'db_type' => 'mysql',
                'db_user' => 'xunxin_remote',
                'db_pwd' => 'HfyHI6Xs1n6U',
                'db_host' => '123.207.100.137',
                'db_port' => '3306',
                'db_name' => 'xunxin_db',
                'db_charset' => 'utf8',
                'db_params' => array(), // 非必须
            );
        }
        parent::__construct($name, $tablePrefix, $connection);
    }

    public function goods_visit_list($condition, $sort, $is_exp)
    {

        if ($is_exp == 'export') {
            $exportDepotData = $this
                ->alias('a')
                ->where($condition)
                ->field('a.*, a.member_id user_id, a.create_time  addtime, b.member_nickname, b.member_name')
                ->join(' xunxin_member as b  on a.member_id = b.member_id')
                ->order('a.create_time ' . $sort)
                ->select();
            $export_data = array();
            foreach ($exportDepotData as $value){
                $one = array();
                $one['create_time_info'] = date('Y-m-d H:i:s', $value['create_time']);
                $goodsData =  M("goods")->where(array('goods_id' => $value['goods_id']))->find();
                if (!empty($goodsData)){
                    $one['goods_info'] = "[".$value['goods_id']."]".$goodsData['goods_name'];
                }else{
                    $one['goods_info'] = "";
                }
                $one['member_name'] =  $value['member_name'];
                $one['member_nickname'] =  $value['member_nickname'];
                $one['page_tag'] =  $value['page_tag'];

                $export_data[] = $one;
            }
            $head = ['浏览时间','商品ID|商品名称', '用户账号','用户昵称' ,'url地址'];
            $data = array();
            $data['head'] = $head;
            $data['export_data'] = $export_data;
            return $data;
        }

        $count_data  = $this
            ->alias('a')
            ->where($condition)
            ->field('a.*, b.member_nickname, b.member_name')
            ->join(' xunxin_member as b  on a.member_id = b.member_id')
            ->select();
        $count = count($count_data);
        $page = new HJPage($count, 50);
        $show = $page->show();
        $goods_visit_data = $this
            ->alias('a')
            ->where($condition)
            ->field('a.*, a.member_id user_id, a.create_time  addtime, b.member_nickname, b.member_name')
            ->join(' xunxin_member as b  on a.member_id = b.member_id')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('a.create_time ' . $sort)
            ->select();
        foreach ($goods_visit_data as $key => $value){
            $goodsData =  M("goods")->where(array('goods_id' => $value['goods_id']))->find();
            if (!empty($goodsData)){
                $goods_visit_data[$key]['goods_name'] = $goodsData['goods_name'];
            }
        }
        $data = array();
        $data['count'] = $count;
        $data['show'] = $show;
        $data['goods_visit_data'] = $goods_visit_data;
        return $data;
    }

    public function goods_visit_summary($condition, $sort, $is_exp)
    {
        if ($is_exp == 'export') {
            $exportDepotData =  $this
                ->alias('a')
                ->where($condition)
                ->field('a.*, a.member_id user_id, a.create_time  addtime, count(*) sum_num, b.goods_name')
                ->join(' xunxin_goods as b on a.goods_id = b.goods_id')
                ->group('a.goods_id')
                ->order('sum_num ' . $sort)
                ->select();
            $export_data = array();
            foreach ($exportDepotData as $value){
                $one = array();
                $goodsData =  M("goods")->where(array('goods_id' => $value['goods_id']))->find();
                if (!empty($goodsData)){
                    $one['goods_info'] = "[".$value['goods_id']."]".$goodsData['goods_name'];
                }else{
                    $one['goods_info'] = "";
                }
                $one['sum_num'] =  $value['sum_num'];

                $export_data[] = $one;
            }
            $head = ['商品ID|商品名称', '浏览次数'];
            $data = array();
            $data['head'] = $head;
            $data['export_data'] = $export_data;
            return $data;
        }
        $count_data = $this->alias('a')->where($condition)
            ->group('a.goods_id')
            ->select();
        $count = count($count_data);
//        die($mb_statistics_goods_sales_model->_sql());
        $page = new HJPage($count, 50);
        $show = $page->show();
        $goods_visit_data = $this
            ->alias('a')
            ->where($condition)
            ->field('a.*, a.member_id user_id, a.create_time  addtime, count(*) sum_num, b.goods_name')
            ->join(' xunxin_goods as b on a.goods_id = b.goods_id')
            ->group('a.goods_id')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('sum_num ' . $sort)
            ->select();
        $data = array();
        $data['count'] = $count;
        $data['show'] = $show;
        $data['goods_visit_data'] = $goods_visit_data;
        return $data;
    }


    public function goods_visit_browse($condition, $sort, $is_exp)
    {
        if ($is_exp == 'export') {
            $exportDepotData =  $this
                ->alias('a')
                ->where($condition)
                ->field('a.*, a.member_id user_id, a.create_time  addtime, b.member_nickname, b.member_name')
                ->join(' xunxin_member as b  on a.member_id = b.member_id')
                ->order('a.create_time ' . $sort)
                ->select();
            $export_data = array();
            foreach ($exportDepotData as $value){
                $one = array();
                $one['create_time_info'] = date('Y-m-d H:i:s', $value['create_time']);
                $one['member_name'] =  $value['member_name'];
                $one['member_nickname'] =  $value['member_nickname'];
                $one['page_tag'] =  $value['page_tag'];

                $export_data[] = $one;
            }
            $head = ['浏览时间', '用户账号','用户昵称' ,'url地址'];
            $data = array();
            $data['head'] = $head;
            $data['export_data'] = $export_data;
            return $data;
        }
        $count_data  = $this
            ->alias('a')
            ->where($condition)
            ->field('a.*, b.member_nickname, b.member_name')
            ->join(' xunxin_member as b  on a.member_id = b.member_id')
            ->select();
        $count = count($count_data);
//        die($mb_statistics_goods_sales_model->_sql());
        $page = new HJPage($count, 50);
        $show = $page->show();
        $goods_visit_data = $this
            ->alias('a')
            ->where($condition)
            ->field('a.*, a.member_id user_id, a.create_time  addtime, b.member_nickname, b.member_name')
            ->join(' xunxin_member as b  on a.member_id = b.member_id')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('a.create_time ' . $sort)
            ->select();
        foreach ($goods_visit_data as $key => $value){
            $goodsData =  M("goods")->where(array('goods_id' => $value['goods_id']))->find();
            if (!empty($goodsData)){
                $goods_visit_data[$key]['goods_name'] = $goodsData['goods_name'];
            }
        }
        $data = array();
        $data['count'] = $count;
        $data['show'] = $show;
        $data['goods_visit_data'] = $goods_visit_data;
        return $data;
    }

}