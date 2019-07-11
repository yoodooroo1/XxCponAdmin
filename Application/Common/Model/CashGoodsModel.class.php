<?php
/**
 * Created by PhpStorm.
 * User: honglj
 * Date: 16/6/24
 * Time: 10:50
 */

namespace Common\Model;

use Api\Controller\GoodsLibraryController;
use Common\Logic\CashCartLogic;
use Common\Model\BaseModel;
use Common\Logic\CartLogic;
use Common\Model\UtilModel;
use Think\Cache\Driver\Redis;

class CashGoodsModel extends BaseModel
{
    protected $tableName = 'goods';

    const SORT_DEFAULT = 0;
    const SORT_PRICE_ASC = 1;
    const SORT_PRICE_DESC = 2;
    const SORT_SALES_DESC = 3;
    const SORT_SALES_ASC = 4;

    private $mg_list_fstr = "a.goods_id,a.goods_barcode,a.store_id,a.goods_name,a.goods_price,a.goods_desc,a.goods_figure,a.is_abroad,a.goods_spec,a.goods_pv,a.goods_link,a.goods_audio,a.goods_storage,a.goods_image,a.spec_open,a.gc_id,a.goods_content,a.is_promote,a.is_qianggou,a.qianggou_start_time,a.qianggou_end_time,a.goods_promoteprice,a.wx_share_text,a.price_hide_desc,a.thirdpart_money_limit,a.credits_limit,a.allow_coupon,a.limit_buy,a.sales_base,a.is_booking,a.booking_delivery_time,a.goods_unit,a.print_ids";
    private $page_num = 10;
    private $g_list_fstr = "goods_id,goods_barcode,store_id,goods_name,goods_price,goods_desc,goods_figure,is_abroad,goods_spec,goods_pv,goods_link,goods_audio,goods_storage,goods_image,spec_open,gc_id,goods_content,is_promote,is_qianggou,qianggou_start_time,qianggou_end_time,goods_promoteprice,price_hide_desc,wx_share_text,thirdpart_money_limit,allow_coupon,limit_buy,sales_base,is_booking,booking_delivery_time,goods_barcode,goods_number,credits_limit,goods_unit,print_ids";
    private $g_desc_fstr = "goods_id,goods_barcode,store_id,goods_name,goods_price,goods_desc,goods_figure,is_abroad,goods_spec,goods_pv,goods_link,goods_audio,goods_storage,goods_image,spec_open,gc_id,goods_content,is_promote,is_qianggou,qianggou_start_time,qianggou_end_time,goods_promoteprice,price_hide_desc,wx_share_text,thirdpart_money_limit,allow_coupon,limit_buy,sales_base,is_booking,booking_delivery_time,goods_barcode,goods_number,mall_goods_class_1,mall_goods_class_2,mall_goods_class_3,credits_limit,freight_type,freight_tpl_id,goods_unit,print_ids";
    private $goods_field = "a.goods_id,a.store_id,a.goods_name,a.goods_price,a.goods_desc,a.goods_figure,a.is_abroad,a.goods_spec,a.goods_pv,a.goods_link,a.goods_audio,a.goods_storage,a.goods_image,a.spec_open,a.gc_id,a.goods_content,a.qianggou_start_time,a.qianggou_end_time,a.goods_promoteprice,a.price_hide_desc,a.limit_buy,c.is_qinggou,c.is_promote,c.min_promote_price,c.max_promote_price,c.min_stock,c.max_stock,c.all_sale_num,c.start_time,c.end_time,a.goods_unit,a.print_ids";
 
    protected $memberId = 0;
    protected $store_id = 0;
    protected $storeId = 0;

    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);
        session_start();
        $req = getRequest();
        if (!empty($req['se'])) {
            $this->storeId = $req['se'];
        } elseif (!empty($req['store_id'])) {
            $this->storeId = $req['store_id'];
        }
        if (session('member_id')) {
            $this->memberId = session('member_id');
        } elseif (!empty($req['member_id'])) {
            $this->memberId = $req['member_id'];
        } elseif (!empty($req['f'])) {
            $this->memberId = $req['f'];
        }
        if (!empty($req['key']) && empty($this->memberId)) {
            $result = D('MemberToken')->getMemberInfoByToken($req['key']);
            if (isSuccess($result)) {
                $this->memberId = $result['data']['member_id'];
            }
        }
        $this->store_id = $this->storeId;
    }

    /**
     *获取供应商id列表
     * @param $store_id
     * @return mixed
     */
    public function getStoreIds($store_id)
    {
        $supplier_ids = D('SupplierAgent')->getStoreSupplierAgentId($store_id);
        if (empty($supplier_ids)) {
            $result = D('Store')->getStoreQueryId($store_id);
            $storeId = $result['data'];
            $storeIds = explode(',', $storeId);
            return count($storeIds) > 1 ? ['in', $storeId] : $storeId;
        } else {
            $store_ids = array();
            $store_ids[] = array('eq', $store_id);
            for ($i = 0; $i < count($supplier_ids); $i++) {
                $store_ids[] = array('eq', $supplier_ids[$i]);
            }
            $store_ids[] = 'or';
            return $store_ids;
        }
    }

    /**
     *分类ID数组化
     * @param $gc_id
     * @return array
     */
    public function getGcIds($gc_id)
    {
        $ids = explode('_', $gc_id);
        $store_ids = array();
        for ($i = 0; $i < count($ids); $i++) {
            $store_ids[] = array('eq', $ids[$i]);
            $store_ids[] = array('like', $ids[$i] . '|%');
            $store_ids[] = array('like', '%|' . $ids[$i]);
            $store_ids[] = array('like', '%|' . $ids[$i] . '|%');
        }
        $store_ids[] = 'or';
        return $store_ids;
    }

    /**
     * 获取商品数据
     * @param $store_id
     * @param $page
     * @param $now_gc_id
     * @param $sort_type
     * @param int $in_stock
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:16:45
     * Update: 2018-06-20 22:16:45
     * Version: 1.00
     */
    public function getGoodsList($store_id, $page, $now_gc_id, $sort_type, $in_stock = 0)
    {
        if ($now_gc_id == -2) {
            if ($sort_type == 0) {
                if ($in_stock == 1) {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'goods_storage' => array('neq', 0),
                            )
                        )
                        ->order('top DESC,sort DESC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                } else {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1
                            )
                        )
                        ->order('top DESC,sort DESC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                }
            } else if ($sort_type == 1) {
                if ($in_stock == 1) {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'goods_storage' => array('neq', 0),
                            )
                        )
                        ->order('goods_price ASC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                } else {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1
                            )
                        )
                        ->order('goods_price ASC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                }
            } else if ($sort_type == 2) {
                if ($in_stock == 1) {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'goods_storage' => array('neq', 0),
                            )
                        )
                        ->order('goods_price DESC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                } else {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1
                            )
                        )
                        ->order('goods_price DESC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                }
            } else if ($sort_type == 3) {
                if ($in_stock == 1) {
                    $goodsBeans = $this->table('xunxin_goods a')
                        ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                        ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                        ->where(
                            array(
                                'a.store_id' => $this->getStoreIds($store_id),
                                'a.isdelete' => 0,
                                'a.goods_state' => 1,
                                'a.goods_storage' => array('neq', 0),
                            )
                        )
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                } else {
                    $goodsBeans = $this->table('xunxin_goods a')
                        ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                        ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                        ->where(
                            array(
                                'a.store_id' => $this->getStoreIds($store_id),
                                'a.isdelete' => 0,
                                'a.goods_state' => 1,
                            )
                        )
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                }
            }
        } else if ($now_gc_id > 0) {
            if ($sort_type == 0) {
                if ($in_stock == 1) {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'goods_storage' => array('neq', 0),
                                'gc_id' => $this->getGcIds($now_gc_id),//array(array('eq', $now_gc_id), array('like', $now_gc_id . '|%'), array('like', '%|' . $now_gc_id), array('like', '%|' . $now_gc_id . '|%'), 'or'),
                            )
                        )
                        ->order('top DESC,sort DESC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                } else {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'gc_id' => $this->getGcIds($now_gc_id),
                            )
                        )
                        ->order('top DESC,sort DESC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                }
            } else if ($sort_type == 1) {
                if ($in_stock == 1) {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'goods_storage' => array('neq', 0),
                                'gc_id' => $this->getGcIds($now_gc_id),
                            )
                        )
                        ->order('goods_price ASC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                } else {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'gc_id' => $this->getGcIds($now_gc_id),
                            )
                        )
                        ->order('goods_price ASC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                }
            } else if ($sort_type == 2) {
                if ($in_stock == 1) {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'goods_storage' => array('neq', 0),
                                'gc_id' => $this->getGcIds($now_gc_id),
                            )
                        )
                        ->order('goods_price DESC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                } else {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'gc_id' => $this->getGcIds($now_gc_id),
                            )
                        )
                        ->order('goods_price DESC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                }
            } else if ($sort_type == 3) {
                if ($in_stock == 1) {
                    $goodsBeans = $this->table('xunxin_goods a')
                        ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                        ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                        ->where(
                            array(
                                'a.store_id' => $this->getStoreIds($store_id),
                                'a.isdelete' => 0,
                                'a.goods_state' => 1,
                                'a.goods_storage' => array('neq', 0),
                                'a.gc_id' => $this->getGcIds($now_gc_id),
                            )
                        )
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                } else {
                    $goodsBeans = $this->table('xunxin_goods a')
                        ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                        ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                        ->where(
                            array(
                                'a.store_id' => $this->getStoreIds($store_id),
                                'a.isdelete' => 0,
                                'a.goods_state' => 1,
                                'a.gc_id' => $this->getGcIds($now_gc_id),
                            )
                        )
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                }
            }
        } else {
            if ($sort_type == 0) {
                if ($in_stock == 1) {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'goods_storage' => array('neq', 0),
                                'gc_id' => array(array('eq', -1), array('eq', 0), 'or'),
                            )
                        )
                        ->order('top DESC,sort DESC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                } else {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'gc_id' => array(array('eq', -1), array('eq', 0), 'or'),
                            )
                        )
                        ->order('top DESC,sort DESC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                }
            } else if ($sort_type == 1) {
                if ($in_stock == 1) {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'goods_storage' => array('neq', 0),
                                'gc_id' => array(array('eq', -1), array('eq', 0), 'or'),
                            )
                        )
                        ->order('goods_price ASC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                } else {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'gc_id' => array(array('eq', -1), array('eq', 0), 'or'),
                            )
                        )
                        ->order('goods_price ASC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                }
            } else if ($sort_type == 2) {
                if ($in_stock == 1) {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'goods_storage' => array('neq', 0),
                                'gc_id' => array(array('eq', -1), array('eq', 0), 'or'),
                            )
                        )
                        ->order('goods_price DESC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                } else {
                    $goodsBeans = $this->field($this->g_list_fstr)
                        ->where(
                            array(
                                'store_id' => $this->getStoreIds($store_id),
                                'isdelete' => 0,
                                'goods_state' => 1,
                                'gc_id' => array(array('eq', -1), array('eq', 0), 'or'),
                            )
                        )
                        ->order('goods_price DESC')
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                }
            } else if ($sort_type == 3) {
                if ($in_stock == 1) {
                    $goodsBeans = $this->table('xunxin_goods a')
                        ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                        ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                        ->where(
                            array(
                                'a.store_id' => $this->getStoreIds($store_id),
                                'a.isdelete' => 0,
                                'a.goods_state' => 1,
                                'a.goods_storage' => array('neq', 0),
                                'a.gc_id' => array(array('eq', -1), array('eq', 0), 'or'),
                            )
                        )
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                } else {
                    $goodsBeans = $this->table('xunxin_goods a')
                        ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                        ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                        ->where(
                            array(
                                'a.store_id' => $this->getStoreIds($store_id),
                                'a.isdelete' => 0,
                                'a.goods_state' => 1,
                                'a.gc_id' => array(array('eq', -1), array('eq', 0), 'or'),
                            )
                        )
                        ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                }
            }
        }
        return $this->initGoodsBeans($store_id, $goodsBeans);
    }

    /**
     * 获取满足条件的商品总数量
     * @param $store_id
     * @param int $now_gc_id
     * @param int $in_stock
     * @return int
     * User: hjun
     * Date: 2018-06-20 22:17:00
     * Update: 2018-06-20 22:17:00
     * Version: 1.00
     */
    public function getGoodsNum($store_id, $now_gc_id = -2, $in_stock = 0)
    {
        if ($now_gc_id == -2) {
            if ($in_stock == 1) {
                return $this->where(
                    array(
                        'store_id' => $this->getStoreIds($store_id),
                        'isdelete' => 0,
                        'goods_state' => 1,
                        'goods_storage' => array('neq', 0),
                    )
                )->count();
            } else {
                return $this->where(
                    array(
                        'store_id' => $this->getStoreIds($store_id),
                        'isdelete' => 0,
                        'goods_state' => 1
                    )
                )->count();
            }
        } else if ($now_gc_id > 0) {
            if ($in_stock == 1) {
                return $this->where(
                    array(
                        'store_id' => $this->getStoreIds($store_id),
                        'isdelete' => 0,
                        'goods_state' => 1,
                        'goods_storage' => array('neq', 0),
                        'gc_id' => $this->getGcIds($now_gc_id),
                    )
                )->count();
            } else {
                return $this->where(
                    array(
                        'store_id' => $this->getStoreIds($store_id),
                        'isdelete' => 0,
                        'goods_state' => 1,
                        'gc_id' => $this->getGcIds($now_gc_id),
                    )
                )->count();
            }
        } else {
            if ($in_stock == 1) {
                return $this->where(
                    array(
                        'store_id' => $this->getStoreIds($store_id),
                        'isdelete' => 0,
                        'goods_state' => 1,
                        'goods_storage' => array('neq', 0),
                        'gc_id' => array(array('eq', -1), array('eq', 0), 'or'),
                    )
                )->count();
            } else {
                return $this->where(
                    array(
                        'store_id' => $this->getStoreIds($store_id),
                        'isdelete' => 0,
                        'goods_state' => 1,
                        'gc_id' => array(array('eq', -1), array('eq', 0), 'or'),
                    )
                )->count();
            }
        }
    }

    /**
     * 搜索商品统计总数
     * @param $store_id
     * @param $search
     * @param int $in_stock
     * @return int
     * User: hjun
     * Date: 2018-06-20 22:17:13
     * Update: 2018-06-20 22:17:13
     * Version: 1.00
     */
    public function searchGoodsListAllNum($store_id, $search, $in_stock = 0)
    {
        if ($in_stock == 1) {
            return $this->where(
                array(
                    'store_id' => $this->getStoreIds($store_id),
                    'isdelete' => 0,
                    'goods_state' => 1,
                    'goods_storage' => array('neq', 0),
                    'goods_desc' => getSearchArr($search),
                )
            )->count();
        } else {
            return $this->where(
                array(
                    'store_id' => $this->getStoreIds($store_id),
                    'isdelete' => 0,
                    'goods_state' => 1,
                    'goods_desc' => getSearchArr($search),
                )
            )->count();
        }
    }

    /**
     * 搜索商品
     * @param $store_id
     * @param $page
     * @param $search
     * @param int $in_stock
     * @param int $sort_type
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:17:30
     * Update: 2018-06-20 22:17:30
     * Version: 1.00
     */
    public function searchGoodsList($store_id, $page, $search, $in_stock = 0, $sort_type = 0)
    {
        $where = $this->getSearchWhere($store_id, $search, $sort_type, $in_stock);
        $goodsBeans = [];
        if ($sort_type == 0) {
            if ($in_stock == 1) {
                $goodsBeans = $this->field($this->g_list_fstr)
                    ->where($where)
                    ->order('top DESC,sort DESC')
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $goodsBeans = $this->field($this->g_list_fstr)
                    ->where($where)
                    ->order('top DESC,sort DESC')
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        } else if ($sort_type == 1) {
            if ($in_stock == 1) {
                $goodsBeans = $this->field($this->g_list_fstr)
                    ->where($where)
                    ->order('goods_price ASC')
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $goodsBeans = $this->field($this->g_list_fstr)
                    ->where($where)
                    ->order('goods_price ASC')
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        } else if ($sort_type == 2) {
            if ($in_stock == 1) {
                $goodsBeans = $this->field($this->g_list_fstr)
                    ->where($where)
                    ->order('goods_price DESC')
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $goodsBeans = $this->field($this->g_list_fstr)
                    ->where($where)
                    ->order('goods_price DESC')
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        } else if ($sort_type == 3) {
            if ($in_stock == 1) {
                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                    ->where($where)
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                    ->where($where)
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        }
        return $this->initGoodsBeans($store_id, $goodsBeans);
    }

    /**
     * 搜索商品
     * @param $channel_id
     * @param $search
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:18:04
     * Update: 2018-06-20 22:18:04
     * Version: 1.00
     */
    public function mallSearch($channel_id, $search)
    {
        $model_store = M("store");
        $sto = 'store_id = ';
        $store_id_list = $model_store->field('store_id')->where('channel_id = ' . $channel_id . ' and isshow = 0')->select();
        if ($store_id_list != false) {
            for ($i = 0; $i < count($store_id_list) - 1; $i++) {
                $sto = $sto . $store_id_list[$i]['store_id'] . ' or store_id = ';
            }
            $sto = $sto . $store_id_list[count($store_id_list) - 1]['store_id'];
        } else {
            return null;
        }
        $storeBeans = $model_store->field('store_id')->where('channel_id = ' . $channel_id . ' and isshow = 0 and store_name like \'%' . $search . '%\'')->select();
        return $storeBeans;
    }


    /**
     * 获取某个商品当前售价
     * @param $gs_id
     * @return double
     * User: hjun
     * Date: 2018-06-20 22:18:26
     * Update: 2018-06-20 22:18:26
     * Version: 1.00
     */
    public function getGoodsPrice($gs_id)
    {
        $ids = explode('|', $gs_id);
        if (count($ids) == 1) {
            $ids = explode('_', $gs_id);
            if (count($ids) > 1) {
                $gs_id = $ids[0] . '|' . $ids[1];
            }
        }
        if (count($ids) > 1) {
            if ($ids[1] == '0' || $ids[1] == '') {
                // 判断商品是否为促销或者抢购或无
                $sale = M('mb_sales')->field('newprice,islongtime,end_time,start_time')->where(array(
                    'gid' => $ids[0],
                    'isdelete' => 0
                ))->order('sales_id desc')->find();

                if ($sale['islongtime'] == 1 || (time() <= $sale['end_time'] && time() >= $sale['start_time'])) {
                    return $sale['newprice'];
                } else {
                    $goodsBean = $this->field('goods_price,goods_pv')
                        ->where(
                            array(
                                'goods_id' => $ids[0],
                                'isdelete' => 0,
                                'goods_state' => 1
                            )
                        )->find();
                    if (empty($goodsBean)) {
                        return 0;
                    } else {
                        if (session('?discount')) {
                            $discount = session('discount');
                            if ($discount < 1) {
                                if (session('?is_partner') && session('is_partner') == '1' && session('?discount_type') && session('discount_type') == '1') {
                                    return $goodsBean['goods_price'] - $goodsBean['goods_pv'] + $goodsBean['goods_pv'] * $discount;
                                } else {
                                    return $goodsBean['goods_price'] * $discount;
                                }
                            } else {
                                return $goodsBean['goods_price'];
                            }
                        } else {
                            return $goodsBean['goods_price'];
                        }
                    }
                }
            } else {
                $goodsBean = $this->field('goods_spec,is_promote,is_qianggou,spec_open')
                    ->where(
                        array(
                            'goods_id' => $ids[0],
                            'isdelete' => 0,
                            'goods_state' => 1
                        )
                    )->find();
                if (!empty($goodsBean['goods_spec']) && $goodsBean['goods_spec'] != '[]' && $goodsBean['spec_open'] != 1) {
                    $spec_list = json_decode($goodsBean['goods_spec']);
                    if (count($spec_list) > $ids[1]) {
                        if (session('?discount')) {
                            $discount = session('discount');
                            if ($discount < 1) {
                                if (session('?is_partner') && session('is_partner') == '1' && session('?discount_type') && session('discount_type') == '1') {
                                    return $spec_list[$ids[1]]->price - $spec_list[$ids[1]]->pv + $spec_list[$ids[1]]->pv * $discount;
                                } else {
                                    return $spec_list[$ids[1]]->price * $discount;
                                }
                            } else {
                                return $spec_list[$ids[1]]->price;
                            }
                        } else {
                            return $spec_list[$ids[1]]->price;
                        }
                    } else {
                        return 0;
                    }
                } else if ($goodsBean['spec_open'] == 1) {
                    $spec_option = M('goods_option')->where(array('specs' => $ids[1]))->field('goods_price,goods_pv,goods_promoteprice')->find();
                    if (!empty($spec_option)) {
                        if ($goodsBean['is_promote'] == 1 || $goodsBean['is_qianggou'] == 1) {
                            return $spec_option['goods_promoteprice'];
                        } else {
                            if (session('?discount')) {
                                $discount = session('discount');
                                if ($discount < 1) {
                                    if (session('?is_partner') && session('is_partner') == '1' && session('?discount_type') && session('discount_type') == '1') {
                                        return $spec_option['goods_price'] - $spec_option['goods_pv'] + $spec_option['goods_pv'] * $discount;
                                    } else {
                                        return $spec_option['goods_price'] * $discount;
                                    }
                                } else {
                                    return $spec_option['goods_price'];
                                }
                            } else {
                                return $spec_option['goods_price'];
                            }
                        }
                    }
                    return 0;
                } else {
                    return 0;
                }
            }
        } else if (count($ids) > 0) {
            // 判断商品是否为促销或者抢购或无
            $sale = M('mb_sales')->field('newprice,islongtime,end_time,start_time')->where(array(
                'gid' => $ids[0],
                'isdelete' => 0
            ))->order('sales_id desc')->find();

            if ($sale['islongtime'] == 1 || (time() <= $sale['end_time'] && time() >= $sale['start_time'])) {
                return $sale['newprice'];
            } else {
                $goodsBean = $this->field('goods_price,goods_pv')
                    ->where(
                        array(
                            'goods_id' => $ids[0],
                            'isdelete' => 0,
                            'goods_state' => 1
                        )
                    )->find();
                if (empty($goodsBean)) {
                    return 0;
                } else {
                    if (session('?discount')) {
                        $discount = session('discount');
                        if ($discount < 1) {
                            if (session('?is_partner') && session('is_partner') == '1' && session('?discount_type') && session('discount_type') == '1') {
                                return $goodsBean['goods_price'] - $goodsBean['goods_pv'] + $goodsBean['goods_pv'] * $discount;
                            } else {
                                return $goodsBean['goods_price'] * $discount;
                            }
                        } else {
                            return $goodsBean['goods_price'];
                        }
                    } else {
                        return $goodsBean['goods_price'];
                    }
                }
            }
        }
    }

    /**
     * 获取当前页第一个商品行数
     * @param $page
     * @return int
     * User: hjun
     * Date: 2018-06-20 22:19:34
     * Update: 2018-06-20 22:19:34
     * Version: 1.00
     */
    private function getFirstRow($page)
    {
        return ($page - 1) * $this->page_num;
    }

    /**
     * 获取购物车商品列表数据
     * @param $store_id
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:19:47
     * Update: 2018-06-20 22:19:47
     * Version: 1.00
     */
    public function getShopCartGoodsList($store_id)
    {
        $channel_id = M('store')->where(array(
            'store_id' => $store_id
        ))->getField('channel_id');
        if ($channel_id > 0) {
            return $this->getShopCartGoodsListWithMall($store_id);
        } else {
            return $this->getShopCartGoodsListWithSid($store_id);
        }
    }

    /**
     * 获取购物车商品列表数据
     * @param $store_id
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:21:15
     * Update: 2018-06-20 22:21:15
     * Version: 1.00
     */
    public function getShopCartGoodsListWithMall($store_id)
    {
        $cartTool = new CartLogic($store_id, $this->memberId);
        $items = $cartTool->getAll();
        $goodsBeans = array();
        $goods_id_array = array();
        foreach ($items as $k => $v) {
            $ids = explode('|', $k);
            if (!array_key_exists($ids[0], $goods_id_array)) {
                $goods_id_array[$ids[0]] = $ids[0];
            }
        }
        $id_array = array();
        foreach ($goods_id_array as $id) {
            if (!empty($id) && $id != 'undefined') {
                $id_array[] = $id;
            }
        }
        $goods_ids = 'goods_id = ';
        if (count($id_array) > 0) {
            for ($i = 0; $i < count($id_array) - 1; $i++) {
                $goods_ids = $goods_ids . $id_array[$i] . ' or goods_id = ';
            }
            $goods_ids = $goods_ids . $id_array[count($id_array) - 1];
        } else {
            return $goodsBeans;
        }
        $where = [];
        $where['isdelete'] = 0;
        $where['goods_state'] = 1;
        $where['goods_id'] = ['in', implode(',', $id_array)];
        $goodsBeans = $this->field($this->g_list_fstr)
            ->where($where)->select();

        $result = array();
        $goodsBeans = $this->initGoodsBeans($store_id, $goodsBeans);
        foreach ($items as $k => $v) {
            $ids = explode('|', $k);
            for ($i = 0; $i < count($goodsBeans); $i++) {
                if ($ids[0] == $goodsBeans[$i]['goods_id']) {
                    $goodsBean = $goodsBeans[$i];
                    if (!empty($goodsBean['is_spec'])) {
                        for ($j = 0; $j < count($goodsBean['spec_option']); $j++) {
                            if ($ids[1] == $goodsBean['spec_option'][$j]['specs']) {
                                if ($goodsBean['spec_option'][$j]['supplier_price']) {
                                    $goodsBean['supplier_price'] = number_format($goodsBean['spec_option'][$j]['supplier_price'], 2, '.', '');
                                }
                                $goodsBean['goods_price'] = number_format($goodsBean['spec_option'][$j]['goods_price'], 2, '.', '');
                                $goodsBean['new_price'] = number_format($goodsBean['spec_option'][$j]['new_price'], 2, '.', '');
                                $goodsBean['spec_name'] = '规格:' . $goodsBean['spec_option'][$j]['title'];
                                $goodsBean['goods_barcode'] = $goodsBean['spec_option'][$j]['goods_barcode'];
                                $goodsBean['buy_num'] = $v['num'];
                                $goodsBean['select_state'] = $v['state'];
                                // hj 2018-01-01 21:40:03 获取mj_id 和 mj_level
                                $goodsBean['mj_id'] = (int)$v['mj_id'];
                                $goodsBean['mj_level'] = (int)$v['mj_level'];

                                $goodsBean['goods_pv'] = $goodsBean['spec_option'][$j]['goods_pv'];
                                $goodsBean['spec_id'] = $goodsBean['spec_option'][$j]['specs'];
                                $goodsBean['gs_id'] = $goodsBean['goods_id'] . '|' . $goodsBean['spec_option'][$j]['specs'];
                                $goodsBean['gid_sid'] = $goodsBean['goods_id'] . '_' . $goodsBean['spec_option'][$j]['specs'];
                                $result[] = $goodsBean;
                            }
                        }
                    } else {
                        if ((int)$items[$goodsBean['goods_id'] . '|0']['num'] > 0) {
                            $goodsBean['spec_id'] = 0;
                            $goodsBean['gs_id'] = $goodsBean['goods_id'] . '|0';
                            $goodsBean['gid_sid'] = $goodsBean['goods_id'] . '_0';
                            $result[] = $goodsBean;
                        }
                    }
                }
            }
        }
//        var_dump($result);
//        die();
        return $result;
    }

    /**
     * 获取购物车商品列表数据
     * @param $store_id
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:21:50
     * Update: 2018-06-20 22:21:50
     * Version: 1.00
     */
    public function getShopCartGoodsListWithSid($store_id)
    {
        $cartTool = new CartLogic($store_id, $this->memberId);
        $items = $cartTool->getAll();

        $goodsBeans = array();
        $goods_id_array = array();
        foreach ($items as $k => $v) {
            $ids = explode('|', $k);
            if (!array_key_exists($ids[0], $goods_id_array)) {
                $goods_id_array[$ids[0]] = $ids[0];
            }
        }
        $id_array = array();
        foreach ($goods_id_array as $id) {
            if (!empty($id) && $id != 'undefined') {
                $id_array[] = $id;
            }
        }
        $goods_ids = 'goods_id = ';
        if (count($id_array) > 0) {
            for ($i = 0; $i < count($id_array) - 1; $i++) {
                $goods_ids = $goods_ids . $id_array[$i] . ' or goods_id = ';
            }
            $goods_ids = $goods_ids . $id_array[count($id_array) - 1];
        } else {
            return $goodsBeans;
        }

        $where = [];
        $where['isdelete'] = 0;
        $where['goods_state'] = 1;
        $where['goods_id'] = ['in', implode(',', $id_array)];
        $goodsBeans = $this->field($this->g_list_fstr)
            ->where($where)->select();

        $result = array();
        $goodsBeans = $this->initGoodsBeans($store_id, $goodsBeans);
        foreach ($items as $k => $v) {
            $ids = explode('|', $k);
            for ($i = 0; $i < count($goodsBeans); $i++) {
                if ($ids[0] == $goodsBeans[$i]['goods_id']) {
                    $goodsBean = $goodsBeans[$i];
                    if (!empty($goodsBean['is_spec'])) {
                        for ($j = 0; $j < count($goodsBean['spec_option']); $j++) {
                            if ($ids[1] == $goodsBean['spec_option'][$j]['specs']) {
                                if ($goodsBean['spec_option'][$j]['supplier_price']) {
                                    $goodsBean['supplier_price'] = number_format($goodsBean['spec_option'][$j]['supplier_price'], 2, '.', '');
                                }
                                $goodsBean['goods_price'] = number_format($goodsBean['spec_option'][$j]['goods_price'], 2, '.', '');
                                $goodsBean['new_price'] = number_format($goodsBean['spec_option'][$j]['new_price'], 2, '.', '');
                                $goodsBean['spec_name'] = '规格:' . $goodsBean['spec_option'][$j]['title'];
                                $goodsBean['goods_barcode'] = $goodsBean['spec_option'][$j]['goods_barcode'];
                                $goodsBean['buy_num'] = $v['num'];
                                $goodsBean['select_state'] = $v['state'];
                                // hj 2018-01-01 21:40:03 获取mj_id 和 mj_level
                                $goodsBean['mj_id'] = (int)$v['mj_id'];
                                $goodsBean['mj_level'] = (int)$v['mj_level'];

                                $goodsBean['goods_pv'] = $goodsBean['spec_option'][$j]['goods_pv'];
                                $goodsBean['spec_id'] = $goodsBean['spec_option'][$j]['specs'];
                                $goodsBean['gs_id'] = $goodsBean['goods_id'] . '|' . $goodsBean['spec_option'][$j]['specs'];
                                $goodsBean['gid_sid'] = $goodsBean['goods_id'] . '_' . $goodsBean['spec_option'][$j]['specs'];
                                $result[] = $goodsBean;
                            }
                        }
                    } else {
                        if ((int)$items[$goodsBean['goods_id'] . '|0']['num'] > 0) {
                            $goodsBean['spec_id'] = 0;
                            $goodsBean['gs_id'] = $goodsBean['goods_id'] . '|0';
                            $goodsBean['gid_sid'] = $goodsBean['goods_id'] . '_0';
                            $result[] = $goodsBean;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 获取某个商品数据
     * @param $gs_id
     * @param $store_id
     * @param $member_id
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:22:05
     * Update: 2018-06-20 22:22:05
     * Version: 1.00
     */
    public function getGoodsBeanWithGsId($gs_id, $store_id, $member_id)
    {
        $ids = explode('|', $gs_id);
        if (count($ids) == 1) {
            $ids = explode('_', $gs_id);
            if (count($ids) > 1) {
                $gs_id = $ids[0] . '|' . $ids[1];
                $ids = explode('|', $gs_id);
            }
        }
        if (count($ids) > 0) {
            $goodsBean = $this->getGoodsBean($ids[0], $store_id, $member_id);
            if (empty($goodsBean)) return [];
            $cartTool = new CartLogic($goodsBean['store_id'], $member_id ?: $this->memberId);
            $items = $cartTool->getAll();
            $flag = 0;
            if (count($ids) > 1 && $goodsBean['is_spec'] == 1) {
                for ($j = 0; $j < count($goodsBean['spec_option']); $j++) {
                    if ($ids[1] == $goodsBean['spec_option'][$j]['specs']) {
                        if ($goodsBean['spec_option'][$j]['supplier_price']) {
                            $goodsBean['supplier_price'] = number_format($goodsBean['spec_option'][$j]['supplier_price'], 2, '.', '');
                        }
                        $goodsBean['goods_price'] = number_format($goodsBean['spec_option'][$j]['goods_price'], 2, '.', '');
                        $goodsBean['new_price'] = number_format($goodsBean['spec_option'][$j]['new_price'], 2, '.', '');
                        $goodsBean['spec_name'] = '规格:' . $goodsBean['spec_option'][$j]['title'];
                        $goodsBean['spec_name_title'] = $goodsBean['spec_option'][$j]['title'];
                        $goodsBean['goods_barcode'] = $goodsBean['spec_option'][$j]['goods_barcode'];
                        $goodsBean['buy_num'] = $cartTool->getNumWithID($gs_id);
                        $goodsBean['select_state'] = $cartTool->getStateWithID($gs_id);
                        $goodsBean['mj_id'] = $cartTool->getMjWithId($gs_id);
                        $goodsBean['mj_level'] = $cartTool->getMjLevelWithId($gs_id);
                        $goodsBean['goods_pv'] = $goodsBean['spec_option'][$j]['goods_pv'];
                        $goodsBean['spec_id'] = $goodsBean['spec_option'][$j]['specs'];
                        $goodsBean['primary_id'] = $goodsBean['spec_option'][$j]['id'];
                        $goodsBean['gs_id'] = $goodsBean['goods_id'] . '|' . $goodsBean['spec_option'][$j]['specs'];
                        $flag = 1;
                    }
                }
            } else {
                if ($items[$goodsBean['goods_id'] . '|0']['num'] > 0) {
                    $goodsBean['gs_id'] = $goodsBean['goods_id'] . '|0';
                    $goodsBean['primary_id'] = '0';
                    $flag = 1;
                }
            }
            if ($flag == 0) {
                $goodsBean = null;
            }
            return $goodsBean;
        } else {
            return null;
        }
    }

    /**
     * 获取某个商品数据
     * @param $gs_id
     * @param $store_id
     * @param $member_id
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:22:05
     * Update: 2018-06-20 22:22:05
     * Version: 1.00
     */
    public function getCashGoodsBeanWithGsId($gs_id, $store_id, $member_id)
    {
        $ids = explode('|', $gs_id);
        if (count($ids) == 1) {
            $ids = explode('_', $gs_id);
            if (count($ids) > 1) {
                $gs_id = $ids[0] . '|' . $ids[1];
                $ids = explode('|', $gs_id);
            }
        }
        if (count($ids) > 0) {
            $goodsBean = $this->getGoodsBean($ids[0], $store_id, $member_id);
            if (empty($goodsBean)) return [];
            $cartTool = new CashCartLogic($goodsBean['store_id'], $member_id ?: $this->memberId);
            $items = $cartTool->getAll();
            $flag = 0;
            if (count($ids) > 1 && $goodsBean['is_spec'] == 1) {
                for ($j = 0; $j < count($goodsBean['spec_option']); $j++) {
                    if ($ids[1] == $goodsBean['spec_option'][$j]['specs']) {
                        if ($goodsBean['spec_option'][$j]['supplier_price']) {
                            $goodsBean['supplier_price'] = number_format($goodsBean['spec_option'][$j]['supplier_price'], 2, '.', '');
                        }
                        $goodsBean['goods_price'] = number_format($goodsBean['spec_option'][$j]['goods_price'], 2, '.', '');
                        $goodsBean['new_price'] = number_format($goodsBean['spec_option'][$j]['new_price'], 2, '.', '');
                        $goodsBean['spec_name'] = '规格:' . $goodsBean['spec_option'][$j]['title'];
                        $goodsBean['spec_name_title'] = $goodsBean['spec_option'][$j]['title'];
                        $goodsBean['goods_barcode'] = $goodsBean['spec_option'][$j]['goods_barcode'];
                        $goodsBean['buy_num'] = $cartTool->getNumWithID($gs_id);
                        $goodsBean['select_state'] = $cartTool->getStateWithID($gs_id);
                        $goodsBean['mj_id'] = $cartTool->getMjWithId($gs_id);
                        $goodsBean['mj_level'] = $cartTool->getMjLevelWithId($gs_id);
                        $goodsBean['goods_pv'] = $goodsBean['spec_option'][$j]['goods_pv'];
                        $goodsBean['spec_id'] = $goodsBean['spec_option'][$j]['specs'];
                        $goodsBean['primary_id'] = $goodsBean['spec_option'][$j]['id'];
                        $goodsBean['gs_id'] = $goodsBean['goods_id'] . '|' . $goodsBean['spec_option'][$j]['specs'];
                        $flag = 1;
                    }
                }
            } else {
                if ($items[$goodsBean['goods_id'] . '|0']['num'] > 0) {
                    $goodsBean['gs_id'] = $goodsBean['goods_id'] . '|0';
                    $goodsBean['primary_id'] = '0';
                    $flag = 1;
                }
            }
            if ($flag == 0) {
                $goodsBean = null;
            }
            return $goodsBean;
        } else {
            return null;
        }
    }

    /**
     *获取某个商品数据
     * @param string $goods_id 商品ID
     * @param string $store_id
     * @param string $member_id
     * @return array
     *
     */
    public function getGoodsBean($goods_id, $store_id, $member_id)
    {
        $field = "{$this->g_desc_fstr},goods_param_ctrl,goods_param_tpl_id,goods_param_content";
        $goodsBean = $this->field($field)
            ->where(
                array(
                    'goods_id' => $goods_id,
                    'isdelete' => 0,
                    'goods_state' => 1
                )
            )->find();
        if (empty($goodsBean)) {
            return null;
        } else {
            $temp_array = array();
            $temp_array[] = $goodsBean;
            $temp_array = $this->initGoodsBeans($store_id, $temp_array, 1, $member_id);
            return $temp_array[0];
        }
    }

    /**
     * 获取我的收藏商品列表数据
     * @param $member_id
     * @param $store_id
     * @param array $w
     * @return array
     * User: lwz
     * Date: 2018-06-20 22:22:54
     * Update: 2018-06-20 22:22:54
     * Version: 1.00
     */
    public function getCollectGoodsList($member_id, $store_id, $w = array())
    {
        if (isInWeb()) {
            return $this->getCollectGoodsListInWeb($member_id, $store_id, $w);
        }
        $goodsBeans = array();
        $where = array();
        $where['xunxin_mb_goods_collect.user_id'] = $member_id;
        $where['xunxin_mb_goods_collect.store_id'] = $this->getStoreIds($store_id);
        if (!empty($w['goods_name'])) {
            $where['xunxin_goods.goods_name'] = array('like', "%" . $w['goods_name'] . "%");
        }
        $id_array = M('mb_goods_collect')->join('xunxin_goods ON xunxin_goods.goods_id = xunxin_mb_goods_collect.goods_id')->field('xunxin_mb_goods_collect.goods_id,xunxin_goods.goods_name')->where($where)->order('xunxin_mb_goods_collect.top DESC , xunxin_mb_goods_collect.creat_time DESC')->select();
        $goods_ids = 'goods_id = ';
        if (count($id_array) > 0) {
            for ($i = 0; $i < count($id_array) - 1; $i++) {
                $goods_ids = $goods_ids . $id_array[$i]['goods_id'] . ' or goods_id = ';
            }
            $goods_ids = $goods_ids . $id_array[count($id_array) - 1]['goods_id'];
        } else {
            return $goodsBeans;
        }

        $goodsBeans = $this->field($this->g_list_fstr)
            ->where('isdelete = 0 and(' . $goods_ids . ')and goods_state = 1')->select();

        return $this->initGoodsBeans($store_id, $goodsBeans);
    }

    /**
     * 获取我的收藏商品列表数据
     * @param $member_id
     * @param $store_id
     * @param array $w
     * @return array
     * User: lwz
     * Date: 2018-06-20 22:22:54
     * Update: 2018-06-20 22:22:54
     * Version: 1.00
     */
    public function getCollectGoodsListInWeb($member_id, $store_id, $w = array())
    {
        $goodsBeans = array();
        $where = array();
        $where['xunxin_mb_goods_collect.user_id'] = $member_id;
        $where['xunxin_mb_goods_collect.store_id'] = $this->getStoreIds($store_id);
        $where['a.isdelete'] = 0;
        $where['a.goods_state'] = 1;
        if (!empty($w['goods_name'])) {
            $where['xunxin_goods.goods_name'] = array('like', "%" . $w['goods_name'] . "%");
        }
        $field = $this->mg_list_fstr . ' ,xunxin_mb_goods_collect.goods_id,xunxin_mb_goods_collect.creat_time';
        $goodsBeans = M('mb_goods_collect')->join('xunxin_goods as a ON a.goods_id = xunxin_mb_goods_collect.goods_id')->field($field)->where($where)->order('xunxin_mb_goods_collect.top DESC , xunxin_mb_goods_collect.creat_time DESC')->select();


        return $this->initGoodsBeans($store_id, $goodsBeans);
    }

    /**
     * 获取抢购商品数量
     * @param $store_id
     * @param $in_stock
     * @return int
     * User: hjun
     * Date: 2018-06-20 22:23:23
     * Update: 2018-06-20 22:23:23
     * Version: 1.00
     */
    public function getRushGoodsNum($store_id, $in_stock)
    {
        if ($in_stock == 1) {
            $swhere = 'isdelete=0 and (islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') and storeid=' . $store_id;
            $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);
            return $this->table('xunxin_goods a')
                ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                ->where(
                    array(
                        'a.isdelete' => 0,
                        'a.goods_state' => 1,
                        'a.goods_storage' => array('neq', 0),
                    )
                )->count();
        } else {
            $swhere = 'isdelete=0 and (islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') and storeid=' . $store_id;
            $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);
            return $this->table('xunxin_goods a')
                ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                ->where(
                    array(
                        'a.isdelete' => 0,
                        'a.goods_state' => 1,
                    )
                )->count();
        }
    }

    /**
     * 获取抢购商品列表数据
     * @param $store_id
     * @param $page
     * @param int $in_stock
     * @param int $sort_type
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-06-20 22:23:42
     * Update: 2018-06-20 22:23:42
     * Version: 1.00
     */
    public function getRushGoodsList($store_id, $page, $in_stock = 0, $sort_type = 0)
    {
        if ($sort_type == 0) {
            if ($in_stock == 1) {
                $swhere = 'isdelete=0 and (islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field($this->mg_list_fstr)->order('a.top DESC,a.sort DESC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'a.goods_storage' => array('neq', 0),
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $swhere = 'isdelete=0 and (islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field($this->mg_list_fstr)->order('a.top DESC,a.sort DESC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        } else if ($sort_type == 1) {
            if ($in_stock == 1) {
                $swhere = 'isdelete=0 and (islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field($this->mg_list_fstr)->order('a.goods_price ASC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'a.goods_storage' => array('neq', 0),
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $swhere = 'isdelete=0 and (islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field($this->mg_list_fstr)->order('a.goods_price ASC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        } else if ($sort_type == 2) {
            if ($in_stock == 1) {
                $swhere = 'isdelete=0 and (islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field($this->mg_list_fstr)->order('a.goods_price DESC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'a.goods_storage' => array('neq', 0),
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $swhere = 'isdelete=0 and (islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field($this->mg_list_fstr)->order('a.goods_price DESC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        } else if ($sort_type == 3) {
            if ($in_stock == 1) {
                $swhere = 'isdelete=0 and (islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $sql2 = $this->table('xunxin_mb_goods_exp a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field('a.goods_id,a.sales_vol')->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql2 . ') b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'a.goods_storage' => array('neq', 0),
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();

            } else {
                $swhere = 'isdelete=0 and (islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $sql2 = $this->table('xunxin_mb_goods_exp a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field('a.goods_id,a.sales_vol')->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql2 . ') b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();

            }
        }

        if (empty($goodsBeans)) {
            return array();
        } else {
            for ($i = 0; $i < count($goodsBeans); $i++) {
                $collectM = M('mb_goods_collect');
                $is_collect = $collectM->where(array(
                    'goods_id' => $goodsBeans[$i]['goods_id'],
                    'user_id' => session('member_id'),
                    'store_id' => $store_id,
                ))->find();
                if (!empty($is_collect)) {
                    $goodsBeans[$i]['is_love'] = 1;
                } else {
                    $goodsBeans[$i]['is_love'] = 0;
                }
            }
            return $this->initGoodsBeans($store_id, $goodsBeans);
        }
    }

    /**
     * 获取促销商品数量
     * @param $store_id
     * @param $in_stock
     * @return int
     * User: hjun
     * Date: 2018-06-20 22:24:09
     * Update: 2018-06-20 22:24:09
     * Version: 1.00
     */
    public function getSalesGoodsNum($store_id, $in_stock)
    {
        if ($in_stock == 1) {
            $swhere = 'isdelete=0 and islongtime=1 and storeid=' . $store_id;
            $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);
            return $this->table('xunxin_goods a')
                ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                ->where(
                    array(
                        'a.isdelete' => 0,
                        'a.goods_state' => 1,
                        'a.goods_storage' => array('neq', 0),
                    )
                )->count();
        } else {
            $swhere = 'isdelete=0 and islongtime=1 and storeid=' . $store_id;
            $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);
            return $this->table('xunxin_goods a')
                ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                ->where(
                    array(
                        'a.isdelete' => 0,
                        'a.goods_state' => 1,
                    )
                )->count();
        }
    }

    /**
     * 获取促销商品列表数据
     * @param $store_id
     * @param $page
     * @param int $in_stock
     * @param int $sort_type
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:24:37
     * Update: 2018-06-20 22:24:37
     * Version: 1.00
     */
    public function getSalesGoodsList($store_id, $page, $in_stock = 0, $sort_type = 0)
    {
        if ($sort_type == 0) {
            if ($in_stock == 1) {
                $swhere = 'isdelete=0 and ((islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') or islongtime=1) and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field($this->mg_list_fstr)->order('a.top DESC,a.sort DESC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'a.goods_storage' => array('neq', 0),
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $swhere = 'isdelete=0 and ((islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') or islongtime=1) and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field($this->mg_list_fstr)->order('a.top DESC,a.sort DESC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        } else if ($sort_type == 1) {
            if ($in_stock == 1) {
                $swhere = 'isdelete=0 and ((islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') or islongtime=1) and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field($this->mg_list_fstr)->order('a.goods_price ASC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'a.goods_storage' => array('neq', 0),
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $swhere = 'isdelete=0 and ((islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') or islongtime=1) and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field($this->mg_list_fstr)->order('a.goods_price ASC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        } else if ($sort_type == 2) {
            if ($in_stock == 1) {
                $swhere = 'isdelete=0 and ((islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') or islongtime=1) and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field($this->mg_list_fstr)->order('a.goods_price DESC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'a.goods_storage' => array('neq', 0),
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $swhere = 'isdelete=0 and ((islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') or islongtime=1) and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field($this->mg_list_fstr)->order('a.goods_price DESC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        } else if ($sort_type == 3) {
            if ($in_stock == 1) {
                $swhere = 'isdelete=0 and ((islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') or islongtime=1) and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $sql2 = $this->table('xunxin_mb_goods_exp a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field('a.goods_id,a.sales_vol')->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql2 . ') b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'a.goods_storage' => array('neq', 0),
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();

            } else {
                $swhere = 'isdelete=0 and ((islongtime=0 and end_time>' . time() . ' and start_time<' . time() . ') or islongtime=1) and storeid=' . $store_id;
                $sql = M('mb_sales')->field('gid')->where($swhere)->distinct(true)->select(false);

                $sql2 = $this->table('xunxin_mb_goods_exp a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.gid')
                    ->field('a.goods_id,a.sales_vol')->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql2 . ') b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                    ->where(
                        array(
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();

            }
        }

        if (empty($goodsBeans)) {
            return array();
        } else {
            $goodsBeans = $this->initGoodsBeans($store_id, $goodsBeans);
            $result = array();
            for ($i = 0; $i < count($goodsBeans); $i++) {
                if ($goodsBeans[$i]['state'] != 1) {
                    continue;
                }
                $collectM = M('mb_goods_collect');
                $is_collect = $collectM->where(array(
                    'goods_id' => $goodsBeans[$i]['goods_id'],
                    'user_id' => session('member_id'),
                    'store_id' => $store_id,
                ))->find();
                if (!empty($is_collect)) {
                    $goodsBeans[$i]['is_love'] = 1;
                } else {
                    $goodsBeans[$i]['is_love'] = 0;
                }
                $result[] = $goodsBeans[$i];
            }
            return $result;
        }
    }

    /**
     * 获取热卖商品数量
     * @param $store_id
     * @param $in_stock
     * @return int
     * User: hjun
     * Date: 2018-06-20 22:24:59
     * Update: 2018-06-20 22:24:59
     * Version: 1.00
     */
    public function getHotGoodsNum($store_id, $in_stock)
    {
        if ($in_stock == 1) {
            return $this->table('xunxin_goods a')
                ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                ->where(
                    array(
                        'a.store_id' => $this->getStoreIds($store_id),
                        'a.isdelete' => 0,
                        'a.goods_state' => 1,
                        'a.goods_storage' => array('neq', 0),
                        'b.is_hot' => 1,
                    )
                )->count();
        } else {
            return $this->table('xunxin_goods a')
                ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                ->where(
                    array(
                        'a.store_id' => $this->getStoreIds($store_id),
                        'a.isdelete' => 0,
                        'a.goods_state' => 1,
                        'b.is_hot' => 1,
                    )
                )->count();
        }
    }

    /**
     * 获取热卖商品列表数据
     * @param $store_id
     * @param $page
     * @param int $in_stock
     * @param int $sort_type
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:25:16
     * Update: 2018-06-20 22:25:16
     * Version: 1.00
     */
    public function getHotGoodsList($store_id, $page, $in_stock = 0, $sort_type = 0)
    {
        if ($sort_type == 0) {
            if ($in_stock == 1) {
                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('a.top DESC,a.sort DESC')
                    ->where(
                        array(
                            'a.store_id' => $this->getStoreIds($store_id),
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'a.goods_storage' => array('neq', 0),
                            'b.is_hot' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('a.top DESC,a.sort DESC')
                    ->where(
                        array(
                            'a.store_id' => $this->getStoreIds($store_id),
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'b.is_hot' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        } else if ($sort_type == 1) {
            if ($in_stock == 1) {
                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('a.goods_price ASC')
                    ->where(
                        array(
                            'a.store_id' => $this->getStoreIds($store_id),
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'a.goods_storage' => array('neq', 0),
                            'b.is_hot' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('a.goods_price ASC')
                    ->where(
                        array(
                            'a.store_id' => $this->getStoreIds($store_id),
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'b.is_hot' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        } else if ($sort_type == 2) {
            if ($in_stock == 1) {
                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('a.goods_price DESC')
                    ->where(
                        array(
                            'a.store_id' => $this->getStoreIds($store_id),
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'a.goods_storage' => array('neq', 0),
                            'b.is_hot' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('a.goods_price DESC')
                    ->where(
                        array(
                            'a.store_id' => $this->getStoreIds($store_id),
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'b.is_hot' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        } else if ($sort_type == 3) {
            if ($in_stock == 1) {
                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                    ->where(
                        array(
                            'a.store_id' => $this->getStoreIds($store_id),
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'a.goods_storage' => array('neq', 0),
                            'b.is_hot' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else {
                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)->order('b.sales_vol DESC')
                    ->where(
                        array(
                            'a.store_id' => $this->getStoreIds($store_id),
                            'a.isdelete' => 0,
                            'a.goods_state' => 1,
                            'b.is_hot' => 1,
                        )
                    )
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            }
        }

        if (empty($goodsBeans)) {
            return array();
        } else {
            return $this->initGoodsBeans($store_id, $goodsBeans);
        }
    }

    /**
     * 获取新上架的商品
     * @param string $store_id
     * @param int $page
     * @param int $limit
     * @param int $sort_type
     * @param int $in_stock
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:25:29
     * Update: 2018-06-20 22:25:29
     * Version: 1.00
     */
    public function getGoodsListsWithNew($store_id = '', $page = 1, $limit = -1, $sort_type = 0, $in_stock = 0)
    {
        $where = [];
        $where['a.store_id'] = $store_id;
        $where['a.isdelete'] = 0;
        $where['a.goods_state'] = 1;
        if ($in_stock == 1) {
            $where['a.goods_storage'] = ['neq', 0];
        }
        $order = '';
        switch ((int)$sort_type) {
            case 1:
                $order = "a.goods_price ASC";
                break;
            case 2:
                $order = "a.goods_price DESC";
                break;
            case 3:
                $order = "b.sales_vol DESC";
                break;
            default:
                $order = "onshelf_time DESC,top DESC,sort DESC,goods_id DESC";
                break;
        }
        if ($limit < 0) {
            $goodsBeans = $this
                ->alias('a')
                ->field($this->mg_list_fstr)
                ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                ->where($where)
                ->order($order)
                ->select();
        } else {
            $goodsBeans = $this
                ->alias('a')
                ->field($this->mg_list_fstr)
                ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
                ->where($where)
                ->order($order)
                ->limit(($page - 1) * $limit . ',' . $limit)
                ->select();
        }

        if (empty($goodsBeans)) {
            return array();
        } else {
            return $this->initGoodsBeans($store_id, $goodsBeans);
        }
    }

    /**
     * 获取指定ID串的商品列表数据
     * @param $store_id
     * @param $ids
     * @param int $limit_num
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:33:17
     * Update: 2018-06-20 22:33:17
     * Version: 1.00
     */
    public function getGoodsListWithIDs($store_id, $ids, $limit_num = -1)
    {
        if ($limit_num < 0) {
            $goodsBeans = $this->field($this->g_list_fstr)
                ->where('isdelete = 0 and(' . $ids . ')and goods_state = 1')
                ->order('top DESC,sort DESC')
                ->select();
        } else {
            $goodsBeans = $this->field($this->g_list_fstr)
                ->where('isdelete = 0 and(' . $ids . ')and goods_state = 1')
                ->order('top DESC,sort DESC')
                ->limit(0 . ',' . $limit_num)
                ->select();
        }

        if (empty($goodsBeans)) {
            return array();
        } else {
            return $this->initGoodsBeans($store_id, $goodsBeans);
        }
    }

    /**
     * 加载更多
     * @param $store_id
     * @param $ids
     * @param $start
     * @param $limit
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-06-20 22:33:33
     * Update: 2018-06-20 22:33:33
     * Version: 1.00
     */
    public function getMoreGoodsListWithIDs($store_id, $ids, $start, $limit)
    {
        $count = M('goods')->where('isdelete = 0 and(' . $ids . ')and goods_state = 1')->count();

        if ($count <= $limit) {
            $goodsBeans = M('goods')->field($this->g_list_fstr)
                ->where('isdelete = 0 and(' . $ids . ')and goods_state = 1')
                ->order('top DESC,sort DESC')
                ->limit(0, $limit)
                ->select();
            $next_start = 0;
        } else {
            $goodsBeans = M('goods')->field($this->g_list_fstr)
                ->where('isdelete = 0 and(' . $ids . ')and goods_state = 1')
                ->order('top DESC,sort DESC')
                ->limit($start, $limit)
                ->select();
            $num = count($goodsBeans);
            if ($num == $limit) {
                $next_start = $start + $limit;
            } else {
                $next_start = $limit - $num;
                $goodsBeans2 = M('goods')->field($this->g_list_fstr)
                    ->where('isdelete = 0 and(' . $ids . ')and goods_state = 1')
                    ->order('top DESC,sort DESC')
                    ->limit(0, $next_start)
                    ->select();
                $goodsBeans = array_merge($goodsBeans, $goodsBeans2);
            }
        }
        $rt = array();
        if (empty($goodsBeans)) {
            $rt['next_start'] = 0;
            return $rt;
        } else {
            $rt['next_start'] = $next_start;
            $rt['datas'] = $this->initGoodsBeans($store_id, $goodsBeans);
            return $rt;
        }
    }

    /**
     * 商城获取指定活动类型商品列表数据
     * @param $channel_id
     * @param $active_type
     * @param int $page
     * @param int $limit
     * @param int $type
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:34:02
     * Update: 2018-06-20 22:34:02
     * Version: 1.00
     */
    public function getActiveGoodsWithType($channel_id, $active_type, $page = 1, $limit = 0, $type = 1)
    {
        return $this->getActiveGoodsWithTypeAndSort($channel_id, $active_type, 0, 0, $page, $limit, $type);
    }

    /**
     * 商城获取指定活动类型商品总数
     * @param $channel_id
     * @param $active_type
     * @param $in_stock
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:34:11
     * Update: 2018-06-20 22:34:11
     * Version: 1.00
     */
    public function getActiveGoodsTotalNumWithType($channel_id, $active_type, $in_stock)
    {
        if ($active_type == 3) {
            $total = M('mb_active_case')->where(array(
                'channel_id' => $channel_id,
                'active_type' => 3,
                'active_state' => 1,
                'active_show' => 1,
                'is_del_mall' => 0
            ))->count();
        } else if ($active_type == 8) {
            if ($in_stock == 1) {
                $sql = M('mb_active_case')->field('goods_id')->where(array(
                    'channel_id' => $channel_id,
                    'goods_id' => array('neq', 0),
                    'active_type' => array('neq', 3),
                    'active_state' => 1,
                    'active_show' => 1,
                    'is_del_mall' => 0
                ))->select(false);

                $total = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                    ->where('a.goods_storage != 0 and a.isdelete = 0 and a.goods_state = 1')
                    ->distinct(true)->count();
            } else {
                $sql = M('mb_active_case')->field('goods_id')->where(array(
                    'channel_id' => $channel_id,
                    'goods_id' => array('neq', 0),
                    'active_type' => array('neq', 3),
                    'active_state' => 1,
                    'active_show' => 1,
                    'is_del_mall' => 0
                ))->select(false);

                $total = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                    ->where('a.isdelete = 0 and a.goods_state = 1')
                    ->distinct(true)->count();
            }
        } else if ($active_type == 2) {
            $sql = M('mb_active_case')->field('goods_id,active_sort')->where(array(
                'channel_id' => $channel_id,
                'active_type' => $active_type,
                'active_state' => 1,
                'active_show' => 1,
                'is_del_mall' => 0
            ))->select(false);

            $goodsBeans = $this->table('xunxin_goods a')
                ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                ->field($this->mg_list_fstr)
                ->where('a.isdelete = 0 and a.goods_state = 1')
                ->order('b.active_sort DESC')->distinct(true)
                ->select();
            $goodsinfo = $this->initGoodsBeans(-1, $goodsBeans);
            $goodsbean = array();
            foreach ($goodsinfo as $ginfo) {
                if ($ginfo['sheng_yu_time'] > 0) {
                    $goodsbean[] = $ginfo;
                }
            }
            return count($goodsbean);

        } else {

            if ($in_stock == 1) {
                $sql = M('mb_active_case')->field('goods_id')->where(array(
                    'channel_id' => $channel_id,
                    'active_type' => $active_type,
                    'active_state' => 1,
                    'active_show' => 1,
                    'is_del_mall' => 0
                ))->select(false);

                $total = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                    ->where('a.goods_storage != 0 and a.isdelete = 0 and a.goods_state = 1')
                    ->distinct(true)->count();
            } else {
                $sql = M('mb_active_case')->field('goods_id')->where(array(
                    'channel_id' => $channel_id,
                    'active_type' => $active_type,
                    'active_state' => 1,
                    'active_show' => 1,
                    'is_del_mall' => 0
                ))->select(false);

                $total = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                    ->where('a.isdelete = 0 and a.goods_state = 1')
                    ->distinct(true)->count();
            }
        }
        return $total;
    }

    /**
     * 获取限时抢购的SQL语句
     * @param int $channelId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-23 12:15:07
     * Update: 2018-01-23 12:15:07
     * Version: 1.00
     */
    public function getActiveTypeTwoSQL($channelId = 0)
    {
        $where = [];
        $where['c.channel_id'] = $channelId;
        $where['c.active_type'] = 2;
        $where['c.active_state'] = 1;
        $where['c.active_show'] = 1;
        $where['c.is_del_mall'] = 0;
        $join = [
            '__GOODS_EXTRA__ d ON c.goods_id = d.goods_id'
        ];
        $where['d.is_qinggou'] = 1;
        $where['d.start_time'] = ['elt', NOW_TIME];
        $where['d.end_time'] = ['gt', NOW_TIME];
        $sql = M('mb_active_case')
            ->alias('c')
            ->join($join)
            ->field('c.goods_id,c.active_sort,c.active_id')
            ->where($where)
            ->select(false);
        return $sql;
    }

    /**
     * 商城获取指定活动类型商品列表数据
     * @param $channel_id
     * @param $active_type
     * @param $in_stock
     * @param $sort_type
     * @param int $page
     * @param int $limit
     * @param int $type
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:34:45
     * Update: 2018-06-20 22:34:45
     * Version: 1.00
     */
    public function getActiveGoodsWithTypeAndSort($channel_id, $active_type, $in_stock, $sort_type, $page = 1, $limit = 0, $type = 1)
    {

        if ($limit > 0) {
            if ($active_type == 3) {
                $idsArr = M('mb_active_case')->field('store_id')->where(array(
                    'channel_id' => $channel_id,
                    'active_type' => $active_type,
                    'active_state' => 1,
                    'active_show' => 1,
                    'is_del_mall' => 0
                ))
                    ->order('active_sort DESC')
                    ->limit($limit)->select();
            } else if ($active_type == 8) {
                $sql = M('mb_active_case')->field('goods_id,active_sort')->where(array(
                    'channel_id' => $channel_id,
                    'goods_id' => array('neq', 0),
                    'active_type' => array('neq', 3),
                    'active_state' => 1,
                    'active_show' => 1,
                    'is_del_mall' => 0
                ))->select(false);

                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)
                    ->where('a.isdelete = 0 and a.goods_state = 1')
                    ->order('b.active_sort DESC')->distinct(true)
                    ->limit($limit)->select();
            } else {
                $where = [];
                $where['c.channel_id'] = $channel_id;
                $where['c.active_type'] = $active_type;
                $where['c.active_state'] = 1;
                $where['c.active_show'] = 1;
                $where['c.is_del_mall'] = 0;
                $join = [];
                if ($active_type === 2) {
                    $join = [
                        '__GOODS_EXTRA__ d ON c.goods_id = d.goods_id'
                    ];
                    $where['d.is_qinggou'] = 1;
                    $where['d.end_time'] = ['gt', NOW_TIME];
                    $where['d.start_time'] = ['elt', NOW_TIME];
                }
                $skip = ($page - 1) * $limit;
                $take = $limit;
                $limit = "{$skip},{$take}";
                $sql = M('mb_active_case')
                    ->alias('c')
                    ->join($join)
                    ->field('c.goods_id,c.active_sort,c.active_id')
                    ->where($where)
                    ->select(false);
                $goodsBeans = $this->table('xunxin_goods a')
                    ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                    ->field($this->mg_list_fstr)
                    ->where('a.isdelete = 0 and a.goods_state = 1')
                    ->order('b.active_sort DESC,b.active_id DESC')->distinct(true)
                    ->limit($limit)->select();
            }
        } else {
            if ($active_type == 3) {
                $idsArr = M('mb_active_case')->field('store_id')->where(array(
                    'channel_id' => $channel_id,
                    'active_type' => $active_type,
                    'active_state' => 1,
                    'active_show' => 1,
                    'is_del_mall' => 0
                ))
                    ->order('active_sort DESC')
                    ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
            } else if ($active_type == 8) {
                if ($sort_type == 0) {
                    if ($in_stock == 1) {
                        $sql = M('mb_active_case')->field('goods_id,active_sort')->where(array(
                            'channel_id' => $channel_id,
                            'goods_id' => array('neq', 0),
                            'active_type' => array('neq', 3),
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);

                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.goods_storage != 0 and a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('b.active_sort DESC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    } else {
                        $sql = M('mb_active_case')->field('goods_id,active_sort')->where(array(
                            'channel_id' => $channel_id,
                            'goods_id' => array('neq', 0),
                            'active_type' => array('neq', 3),
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);

                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('b.active_sort DESC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    }
                } else if ($sort_type == 1) {
                    if ($in_stock == 1) {
                        $sql = M('mb_active_case')->field('goods_id')->where(array(
                            'channel_id' => $channel_id,
                            'goods_id' => array('neq', 0),
                            'active_type' => array('neq', 3),
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);

                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.goods_storage != 0 and a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('a.goods_price ASC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    } else {
                        $sql = M('mb_active_case')->field('goods_id')->where(array(
                            'channel_id' => $channel_id,
                            'goods_id' => array('neq', 0),
                            'active_type' => array('neq', 3),
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);

                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('a.goods_price ASC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    }
                } else if ($sort_type == 2) {
                    if ($in_stock == 1) {
                        $sql = M('mb_active_case')->field('goods_id')->where(array(
                            'channel_id' => $channel_id,
                            'goods_id' => array('neq', 0),
                            'active_type' => array('neq', 3),
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);

                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.goods_storage != 0 and a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('a.goods_price DESC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    } else {
                        $sql = M('mb_active_case')->field('goods_id')->where(array(
                            'channel_id' => $channel_id,
                            'goods_id' => array('neq', 0),
                            'active_type' => array('neq', 3),
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);

                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('a.goods_price DESC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    }
                } else if ($sort_type == 3) {
                    if ($in_stock == 1) {
                        $sql = M('mb_active_case')->field('goods_id')->where(array(
                            'channel_id' => $channel_id,
                            'goods_id' => array('neq', 0),
                            'active_type' => array('neq', 3),
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);

                        $sql2 = $this->table('xunxin_mb_goods_exp a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field('b.goods_id,a.sales_vol')->select(false);

                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql2 . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.goods_storage != 0 and a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('b.sales_vol DESC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    } else {
                        $sql = M('mb_active_case')->field('goods_id')->where(array(
                            'channel_id' => $channel_id,
                            'goods_id' => array('neq', 0),
                            'active_type' => array('neq', 3),
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);

                        $sql2 = $this->table('xunxin_mb_goods_exp a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field('b.goods_id,a.sales_vol')->select(false);

                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql2 . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('b.sales_vol DESC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    }
                }
            } else {
                if ($sort_type == 0) {
                    if ($in_stock == 1) {
                        $sql = M('mb_active_case')->field('goods_id,active_sort')->where(array(
                            'channel_id' => $channel_id,
                            'active_type' => $active_type,
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);
                        if ($active_type == 2) {
                            $sql = $this->getActiveTypeTwoSQL($channel_id);
                        }
                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.goods_storage != 0 and a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('b.active_sort DESC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    } else {
                        $sql = M('mb_active_case')->field('goods_id,active_sort')->where(array(
                            'channel_id' => $channel_id,
                            'active_type' => $active_type,
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);
                        if ($active_type == 2) {
                            $sql = $this->getActiveTypeTwoSQL($channel_id);
                        }
                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('b.active_sort DESC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    }
                } else if ($sort_type == 1) {
                    if ($in_stock == 1) {
                        $sql = M('mb_active_case')->field('goods_id')->where(array(
                            'channel_id' => $channel_id,
                            'active_type' => $active_type,
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);
                        if ($active_type == 2) {
                            $sql = $this->getActiveTypeTwoSQL($channel_id);
                        }
                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.goods_storage != 0 and a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('a.goods_price ASC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    } else {
                        $sql = M('mb_active_case')->field('goods_id')->where(array(
                            'channel_id' => $channel_id,
                            'active_type' => $active_type,
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);
                        if ($active_type == 2) {
                            $sql = $this->getActiveTypeTwoSQL($channel_id);
                        }
                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('a.goods_price ASC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    }
                } else if ($sort_type == 2) {
                    if ($in_stock == 1) {
                        $sql = M('mb_active_case')->field('goods_id')->where(array(
                            'channel_id' => $channel_id,
                            'active_type' => $active_type,
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);
                        if ($active_type == 2) {
                            $sql = $this->getActiveTypeTwoSQL($channel_id);
                        }
                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.goods_storage != 0 and a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('a.goods_price DESC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    } else {
                        $sql = M('mb_active_case')->field('goods_id')->where(array(
                            'channel_id' => $channel_id,
                            'active_type' => $active_type,
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);
                        if ($active_type == 2) {
                            $sql = $this->getActiveTypeTwoSQL($channel_id);
                        }
                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('a.goods_price DESC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    }
                } else if ($sort_type == 3) {
                    if ($in_stock == 1) {
                        $sql = M('mb_active_case')->field('goods_id')->where(array(
                            'channel_id' => $channel_id,
                            'active_type' => $active_type,
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);
                        if ($active_type == 2) {
                            $sql = $this->getActiveTypeTwoSQL($channel_id);
                        }
                        $sql2 = $this->table('xunxin_mb_goods_exp a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field('b.goods_id,a.sales_vol')->select(false);

                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql2 . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.goods_storage != 0 and a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('b.sales_vol DESC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    } else {
                        $sql = M('mb_active_case')->field('goods_id')->where(array(
                            'channel_id' => $channel_id,
                            'active_type' => $active_type,
                            'active_state' => 1,
                            'active_show' => 1,
                            'is_del_mall' => 0
                        ))->select(false);
                        if ($active_type == 2) {
                            $sql = $this->getActiveTypeTwoSQL($channel_id);
                        }
                        $sql2 = $this->table('xunxin_mb_goods_exp a')
                            ->join('right join (' . $sql . ') b on a.goods_id = b.goods_id')
                            ->field('b.goods_id,a.sales_vol')->select(false);

                        $goodsBeans = $this->table('xunxin_goods a')
                            ->join('right join (' . $sql2 . ') b on a.goods_id = b.goods_id')
                            ->field($this->mg_list_fstr)
                            ->where('a.isdelete = 0 and a.goods_state = 1')
                            ->distinct(true)->order('b.sales_vol DESC')
                            ->limit($this->getFirstRow($page) . ',' . $this->page_num)->select();
                    }
                }
            }
        }
        if ($active_type == 3) {
            if (!empty($idsArr) && count($idsArr) > 0) {
                $ids = '';
                for ($i = 0; $i < count($idsArr); $i++) {
                    if ($i < count($idsArr) - 1) {
                        $ids .= "store_id=" . $idsArr[$i]['store_id'] . " or ";
                    } else {
                        $ids .= "store_id=" . $idsArr[$i]['store_id'];
                    }
                }
            }
            $storeBeans = M('store')->field('store_id,store_name,store_address,store_grade,store_label,latitude,longitude')
                ->where($ids)
                ->select();
            $lat = (session('latitude') == '') ? 0 : session('latitude');
            $lng = (session('longitude') == '') ? 0 : session('longitude');
            foreach ($storeBeans as $key => $sl) {
                $distance = $this->getDistance($lat, $lng, $sl['latitude'], $sl['longitude']);
                if ($distance < 1000) {
                    $storeBeans[$key]['distance_name'] = $distance . 'm';
                } else {
                    $storeBeans[$key]['distance_name'] = round($distance / 1000, 1) . 'km';
                }
            }
            return $storeBeans;
        } else {
            if (!empty($goodsBeans)) {

                if (($limit > 0 && $active_type == 2) || ($limit > 0 && $active_type == 6) || ($limit > 0 && $active_type == 7)) {

                    $goodsinfo = $this->initGoodsBeans(-1, $goodsBeans, 2);
                    if ($active_type == 2) {
                        $goodsbean = array();
                        foreach ($goodsinfo as $ginfo) {
                            if ($ginfo['sheng_yu_time'] > 0) {
                                $goodsbean[] = $ginfo;
                            }
                        }
                        return $goodsbean;
                    } else {
                        return $goodsinfo;
                    }

                } else {
                    $goodsinfo = $this->initGoodsBeans(-1, $goodsBeans);
                    if ($active_type == 2) {
                        $goodsbean = array();
                        foreach ($goodsinfo as $ginfo) {
                            if ($ginfo['sheng_yu_time'] > 0) {
                                $goodsbean[] = $ginfo;
                            }
                        }
                        return $goodsbean;
                    } else {
                        return $goodsinfo;
                    }
                }
            } else {
                return array();
            }
        }
        return array();
    }

    function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        return getDistance($lat1, $lng1, $lat2, $lng2);
    }

    /**
     * 初始化完整的商品数据
     * @param $store_id
     * @param $goodsBeans
     * @param int $type
     * @param string $initType
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:35:30
     * Update: 2018-06-20 22:35:30
     * Version: 1.00
     */
    public function initGoodsBeans($store_id, $goodsBeans, $type = 0, $member_id, $initType = CartLogic::INIT_TYPE_ALL)
    {
        $cartTool = new CashCartLogic(
            $store_id > 0 ? $store_id : $this->storeId,
            $member_id > 0 ? $member_id : $this->memberId);
        return $cartTool->initGoodsBeans($goodsBeans, $type, $initType);
    }

    /**
     * 设置商品会员、代理折扣
     * @param array $memberDiscountInfo
     * @param array $goodsBean
     * @param int $goodsId
     * @param int $goodsPrice
     * @param int $goodsPv
     * @param int $specId
     * @param $state
     * User: hjun
     * Date: 2018-01-19 16:32:09
     * Update: 2018-01-19 16:32:09
     * Version: 1.00
     */
    public function setGoodsBeanDiscount($memberDiscountInfo = [], &$goodsBean = [], $goodsId = 0, $goodsPrice = 0, $goodsPv = 0, $specId = 0, &$state)
    {
        if ($memberDiscountInfo['group_id'] > 0 && isset($memberDiscountInfo['discount'])) {
            $state = 4;
            $discount = round($memberDiscountInfo['discount'] / 10, 2);
            $discountType = $memberDiscountInfo['discount_type'];
            switch ((int)$discountType) {
                case 0:
                    $goodsBean['new_price'] = round($goodsPrice * $discount, 2);
                    break;
                case 1:
                    $goodsBean['new_price'] = round($goodsPrice - $goodsPv + $goodsPv * $discount, 2);
                    break;
                case 2:
                    $where = [];
                    $where['store_group_price_id'] = $memberDiscountInfo['store_group_price_id'];
                    $where['goods_id'] = $goodsId;
                    $where['spec_id'] = $specId;
                    $price = M('mb_store_group_price_data')->where($where)->getField('price');
                    $goodsBean['new_price'] = empty($price) ? $goodsPrice : $price;
                    break;
                default:
                    $goodsBean['new_price'] = $goodsPrice;
                    break;
            }
        } elseif ($memberDiscountInfo['vip_discount'] > 0) {
            $state = 3;
            $discount = round($memberDiscountInfo['vip_discount'] / 10, 2);
            $goodsBean['new_price'] = round($goodsPrice * $discount, 2);
        } else {
            $goodsBean['new_price'] = $goodsPrice;
        }
    }

    /**
     * 销量最好的商品
     * @param $num
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:38:17
     * Update: 2018-06-20 22:38:17
     * Version: 1.00
     */
    public function getMaxSaleGoods($num)
    {
        $Goods = M('goods');
        $goodslist = array();
        $w = array();
        $w['a.isdelete'] = 0;
        $w['a.goods_state'] = 1;
        $w['a.store_id'] = $this->store_id;
        $goodsBeans = $Goods
            ->alias('a')
            ->where($w)
            ->join('LEFT JOIN __MB_GOODS_EXP__ b ON b.goods_id = a.goods_id')
            ->order('b.sales_vol DESC')
            ->field('a.goods_id,a.store_id,a.goods_name,a.goods_price,a.goods_desc,a.goods_figure,a.goods_spec,a.goods_pv,a.goods_link,a.goods_audio,a.goods_storage,a.goods_image,a.spec_open,a.gc_id,a.goods_content,a.is_promote,a.is_qianggou,a.qianggou_start_time,a.qianggou_end_time,a.goods_promoteprice,b.sales_vol')
            ->limit($num)
            ->select();
        $goodslist = $this->initGoodsBeans($this->store_id, $goodsBeans, 1);

        return $goodslist;
    }

    /**
     * 最新上架的商品
     * @param $num
     * @return array
     * User: hjun
     * Date: 2018-06-20 22:41:28
     * Update: 2018-06-20 22:41:28
     * Version: 1.00
     */
    public function getMaxNewGoods($num)
    {
        $Goods = M('goods');
        $goodslist = array();
        $w = array();
        $w['a.isdelete'] = 0;
        $w['a.goods_state'] = 1;
        $w['a.store_id'] = $this->store_id;
        $goodsBeans = $Goods->alias('a')->where($w)->order('a.onshelf_time DESC')->field($this->g_desc_fstr)->limit($num)->select();

        $goodslist = $this->initGoodsBeans($this->store_id, $goodsBeans, 1);

        return $goodslist;
    }

    /*获取商品列表
    * type : 0 - 所有商品 1 - 收藏 2 - 搜索
    */
    public function Get_Goods_list($store_id, $type = '', $condition = array(), $order = '', $page = 1, $num = 8)
    {
        $first = ($page - 1) * $num;
        $goodsBeans = $this->table('xunxin_goods a')
            ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
            ->field($this->mg_list_fstr)->order($order)
            ->where($condition)
            ->limit($first . ',' . $num)->select();

        $goodslist = D('Goods')->initGoodsBeans($store_id, $goodsBeans, 1);
        return $goodslist;
    }

    /*获取商品数量*/
    public function Get_Goods_num($store_id, $type = '', $condition = array())
    {
        $goodsNum = $this->table('xunxin_goods a')
            ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
            ->where($condition)
            ->count();
        return $goodsNum;
    }

    /**
     * 获取购物车购买数量
     * @param $store_id
     * @return int
     * User: hjun
     * Date: 2018-06-20 22:42:01
     * Update: 2018-06-20 22:42:01
     * Version: 1.00
     */
    public function getShopCartNum($store_id)
    {
        $cartTool = new CartLogic($store_id ?: $this->storeId, $this->memberId);
        return $cartTool->getNum();
    }

    /**
     * 获取指定商城分类下的商品
     * @param int $classId
     * @param int $page
     * @param int $limit
     * @param int $type 1- 查出子分类的商品 2-只查出当前分类的商品
     * @param int $level 分类等级
     * @param array $req 查询条件
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-27 19:26:19
     * Version: 1.0
     */
    public function getClassIdGoods($classId = 0, $page = 1, $limit = 0, $type = 1, $level = 1, $req)
    {
        // 分类
        if (empty($classId)) return getReturn(200, '', []);
        if (is_array($classId) && !empty($classId)) {
            if (count($classId) == 1) {
                $classId = $classId[0];
            } else {
                $classId = ['in', $classId];
            }
        }

        // 查询该分类下的商品列表
        $where = [];
        $where['a.isdelete'] = 0;
        $where['a.goods_state'] = 1;
        $where['a.mall_goods_class_' . $level] = $classId;
        // 品牌
        if (!empty($req['brand'])) {
            $like = [];
            foreach ($req['brand'] as $brandId) {
                $like[] = ['like', "%\"{$brandId}\"%"];
            }
            $like[] = 'OR';
            $where['a.brand_id'] = $like;
        }
        $skip = ($page - 1) * $limit;
        $take = $limit;
        $this->alias('a')->field($this->mg_list_fstr)->where($where)->order('a.top DESC,a.sort DESC')->limit($skip, $take);

        // 筛选条件
        if ($req['isNew'] == 1) {
            $this->order('a.onshelf_time DESC,a.top DESC,a.sort DESC');
        } elseif (!empty($req['saleSort'])) {
            $order = $req['saleSort'] === 'asc' ? 'b.sales_vol ASC' : 'b.sales_vol DESC';
            $this->join('LEFT JOIN  __MB_GOODS_EXP__ b on a.goods_id = b.goods_id')->order($order);
        } elseif (!empty($req['priceSort'])) {
            $order = $req['priceSort'] === 'asc' ? 'a.goods_price ASC' : 'a.goods_price DESC';
            $this->order($order);
        }

        $list = $this->select();
        if (false === $list) {
            logWrite("查询主分类{$classId}下的商品出错:" . $this->getDbError());
            return getReturn();
        }
        $list = $this->initGoodsBeans(-1, $list);
        return getReturn(200, '', $list);
    }

    /**
     * 获取指定商城品牌下的商品
     * @param int $brandId
     * @param int $page
     * @param int $limit
     * @param array $req
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-27 19:26:19
     * Version: 1.0
     */
    public function getBrandIdGoods($brandId = 0, $page = 1, $limit = 0, $req = [])
    {
        // 品牌
        if (empty($brandId)) return getReturn(200, '', []);
        if (is_array($brandId)) {
            if (count($brandId) == 1) {
                $like = ['like', "%\"{$brandId[0]}\"%"];
            } else {
                $like = [];
                foreach ($brandId as $id) {
                    $like[] = ['like', "%\"{$id}\"%"];
                }
                $like[] = 'OR';
            }
        } else {
            $like = ['like', "%\"{$brandId}\"%"];
        }

        // 查询该分类下的商品列表
        $where = [];
        $where['a.isdelete'] = 0;
        $where['a.goods_state'] = 1;
        $where['a.brand_id'] = $like;

        // 分类
        if (is_array($req['classify']) && !empty($req['classify'])) {
            if (count($req['classify']) == 1) {
                $classId = $req['classify'][0];
            } else {
                $classId = ['in', $req['classify']];
            }
            $where['a.mall_goods_class_1'] = $classId;
        }
        $skip = ($page - 1) * $limit;
        $take = $limit;
        $this->alias('a')->field($this->mg_list_fstr)->where($where)->order('a.top DESC,a.sort DESC')->limit($skip, $take);

        // 筛选条件
        if ($req['isNew'] == 1) {
            $this->order('a.onshelf_time DESC,a.top DESC,a.sort DESC');
        } elseif (!empty($req['saleSort'])) {
            $order = $req['saleSort'] === 'asc' ? 'b.sales_vol ASC' : 'b.sales_vol DESC';
            $this->join('LEFT JOIN  __MB_GOODS_EXP__ b on a.goods_id = b.goods_id')->order($order);
        } elseif (!empty($req['priceSort'])) {
            $order = $req['priceSort'] === 'asc' ? 'a.goods_price ASC' : 'a.goods_price DESC';
            $this->order($order);
        }

        $list = $this->select();
        if (false === $list) {
            logWrite("查询主品牌{$brandId}下的商品出错:" . $this->getDbError());
            return getReturn();
        }
        $list = $this->initGoodsBeans(-1, $list);
        return getReturn(200, '', $list);
    }


    /**
     * 获取指定ID串的商品 增加分页
     * @param int $storeId
     * @param string $ids
     * @param int $page
     * @param int $limit
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-29 16:36:12
     * Version: 1.0
     */
    public function getGoodsListWithIDsByPage($storeId = 0, $ids = '', $page = 1, $limit = 0)
    {
        $skip = ($page - 1) * $limit;
        $take = $limit;
        $where = [];
        $where['store_id'] = $storeId;
        $where['isdelete'] = 0;
        $where['goods_state'] = 1;
        $where['goods_id'] = ['in', $ids];
        $goodsBeans = $this->field($this->g_list_fstr)
            ->where($where)
            ->order('top DESC,sort DESC')
            ->limit($skip, $take)
            ->select();
        if (empty($goodsBeans)) {
            return getReturn(200, '', []);
        } else {
            return getReturn(200, '', $this->initGoodsBeans($storeId, $goodsBeans));
        }
    }

    /**
     * 分页获取特价专区的商品列表
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-29 16:45:35
     * Version: 1.0
     */
    public function getNewPriceGoods($storeId = 0, $page = 1, $limit = 0)
    {
        $where = [];
        $where['a.store_id'] = $storeId;
        $where['a.goods_delete'] = 0;
        $where['a.goods_state'] = 1;
        $where['_string'] = 'a.is_qinggou = 1 OR a.is_promote = 1';
        $field = [
            'b.goods_id', 'b.goods_price', 'b.store_id', 'b.goods_figure', 'b.goods_image',
            'b.spec_open', 'b.is_qianggou', 'b.is_promote', 'b.qianggou_start_time',
            'b.qianggou_end_time', 'b.goods_name', 'b.goods_pv'
        ];
        $model = M('goods_extra');
        $list = $model
            ->alias('a')
            ->field($field)
            ->where($where)
            ->join('__GOODS__ b ON a.goods_id = b.goods_id')
            ->limit(($page - 1) * $limit, $limit)
            ->order('b.top DESC,b.sort DESC')
            ->select();
        $list = $this->initGoodsBeans($storeId, $list);
        return getReturn(200, '', $list);
    }

    /**
     * 分页获取精品选购的商品
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-29 16:47:14
     * Version: 1.0
     */
    public function getSpecialGoods($storeId = 0, $page = 1, $limit = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['goods_state'] = 1;
        $where['is_special'] = 1;
        $where['isdelete'] = 0;
//        $where['goods_storage'] = ['neq', 0];
        $options = [];
        $options['where'] = $where;
        $options['field'] = $this->g_list_fstr;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = 'top DESC,sort DESC';
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        if ($result['data']['total'] <= 0) {
            $list = $this->getGoodsList($storeId, $page, -2, 3);
        } else {
            $list = $this->initGoodsBeans($storeId, $list);
        }
        return getReturn(200, '', $list);
    }

    /**
     * 分页获取热门商品
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-09-29 16:55:43
     * Version: 1.0
     */
    public function getHotGoods($storeId = 0, $page = 1, $limit = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['isdelete'] = 0;
        $where['is_hot'] = 1;
        $ids = M('mb_goods_exp')->where($where)->getField('goods_id', true);
        $ids = empty($ids) ? '' : implode(',', $ids);
        return $this->getGoodsListWithIDsByPage($storeId, $ids, $page, $limit);
    }

    /**
     * @param int $tagId
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param int $sortType
     * @param int $inStock
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取标签商品
     * Date: 2017-11-07 12:49:11
     * Update: 2017-11-07 12:49:12
     * Version: 1.0
     */
    public function getTagGoods($tagId = 0, $storeId = 0, $page = 1, $limit = 0, $sortType = 0, $inStock = 0)
    {
        // 取出该标签的所有商品ID
        $model = M('goods_tag_link');
        $where = [];
        $where['tag_id'] = $tagId;
        $goodsId = $model->where($where)->getField('goods_id', true);
        if (false === $goodsId) return getReturn();
        // 查询商品
        $goodsId = implode(',', $goodsId);
        $goodsId = empty($goodsId) ? '' : $goodsId;
        $where = [];
        $where['a.goods_id'] = ['in', $goodsId];
        $where['a.isdelete'] = 0;
        $where['a.goods_state'] = 1;
        // 如果不是商城 只查看自己店的商品
        $storeType = UtilModel::getStoreType($storeId) . '';
        if (strpos('02', $storeType . '') === false) {
            $where['a.store_id'] = $storeId;
        } else {
            $storeId = -1;
        }
        // 是否有货
        if ($inStock == 1) {
            $where['a.goods_storage'] = ['neq', 0];
        }
        // 排序方式
        switch ((int)$sortType) {
            case 1:
                $order = "a.goods_price ASC";
                break;
            case 2:
                $order = "a.goods_price DESC";
                break;
            case 3:
                $order = "b.sales_vol DESC";
                break;
            default:
                $order = "a.onshelf_time DESC,a.top DESC,a.sort DESC,a.goods_id DESC";
                break;
        }
        $options = [];
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = $order;
        $options['alias'] = 'a';
        $options['join'] = ['LEFT JOIN __MB_GOODS_EXP__ b ON b.goods_id = a.goods_id'];
        $options['field'] = $this->mg_list_fstr;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        return empty($list) ? getReturn(200, '', []) : getReturn(200, '', $this->initGoodsBeans($storeId, $list));
    }

    /**
     *
     * @param int $tagId
     * @param int $storeId
     * @param int $inStock
     * @return int
     * User: hjun
     * Date: 2018-06-20 22:27:18
     * Update: 2018-06-20 22:27:18
     * Version: 1.00
     */
    public function getTagGoodsNum($tagId = 0, $storeId = 0, $inStock = 0)
    {
        // 取出该标签的所有商品ID
        $model = M('goods_tag_link');
        $where = [];
        $where['tag_id'] = $tagId;
        $goodsId = $model->where($where)->getField('goods_id', true);
        if (false === $goodsId) return 0;
        // 查询商品
        $goodsId = implode(',', $goodsId);
        $goodsId = empty($goodsId) ? '' : $goodsId;
        $where = [];
        $where['a.goods_id'] = ['in', $goodsId];
        $where['a.isdelete'] = 0;
        $where['a.goods_state'] = 1;
        // 如果不是商城 只查看自己店的商品
        $storeType = UtilModel::getStoreType($storeId) . '';
        if (strpos('02', $storeType . '') === false) {
            $where['a.store_id'] = $storeId;
        }
        // 是否有货
        if ($inStock == 1) {
            $where['a.goods_storage'] = ['neq', 0];
        }

        $options = [];
        $options['where'] = $where;
        $options['alias'] = 'a';
        $result = $this->queryTotal($options);
        return $result['code'] === -1 ? 0 : $result;
    }

    /**
     * 获取优惠券可用商品列表
     * @param string $type
     * @param int $memberCouponId
     * @param int $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @param int $orderType 排序类型
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-18 09:36:11
     * Update: 2017-12-18 09:36:11
     * Version: 1.00
     */
    public function getCouponGoods($type = '', $memberCouponId = 0, $storeId = 0, $page = 1, $limit = 0, $condition = [], $orderType = 1)
    {
        if ($type == 'mj_goods') {
            $mjId = $memberCouponId;
            $model = D('MjActivity');
            $where = [];
            $where['mj_id'] = $mjId;
            $field = [
                'discounts_name coupons_name,limit_goods_type,limit_goods',
                'mj_type', 'mj_rule', 'store_id'
            ];
            $options = [];
            $options['where'] = $where;
            $options['page'] = $page;
            $options['limit'] = $limit;
            $options['field'] = implode(',', $field);
            $result = $model->queryRow($options);
            $couponInfo = $result['data'];
            if (empty($couponInfo)) return getReturn(-1, L('RECORD_INVALID'));
            $couponInfo['coupons_name'] = $model->getDiscountNameAndRule($couponInfo)['data']['name'];
            $where = [];
            if ($couponInfo['limit_goods_type'] == 2) {
                $where['a.goods_id'] = ['in', $couponInfo['limit_goods']];
            }
            $where = array_merge($where, $condition);
        } else {
            $model = D('MemberCoupons');
            $where = [];
            $where['a.id'] = $memberCouponId;
            $field = [
                'a.id,a.coupons_name,a.limit_class_type,a.limit_class,a.limit_mall_class,a.limit_goods',
                'a.limit_type,a.limit_money_type,a.coupons_money,a.limit_money,a.coupons_type',
                'a.coupons_discount'
            ];
            $options = [];
            $options['alias'] = 'a';
            $options['where'] = $where;
            $options['page'] = $page;
            $options['limit'] = $limit;
            $options['field'] = implode(',', $field);
            $result = $model->queryRow($options);
            if ($result['code'] !== 200) return $result;
            $couponInfo = $result['data'];
            // 使用金额限制
            if (isset($couponInfo['limit_money_type'])) {
                switch ((int)$couponInfo['limit_money_type']) {
                    case 2:
                        switch ((int)$couponInfo['coupons_type']) {
                            case 1:
                                $couponInfo['coupons_value']['money'] = $couponInfo['coupons_money'];
                                $couponInfo['coupons_value']['limit'] = "满{$couponInfo['limit_money']}减{$couponInfo['coupons_money']}的优惠券";
                                break;
                            case 2:
                                $couponInfo['coupons_value']['money'] = $couponInfo['coupons_money'];
                                $discount = $couponInfo['coupons_discount'] * 10;
                                $couponInfo['coupons_value']['limit'] = "满{$couponInfo['limit_money']}打{$discount}折的优惠券";
                                break;
                            default:
                                break;
                        }
                        break;
                    default:
                        switch ((int)$couponInfo['coupons_type']) {
                            case 1:
                                $couponInfo['coupons_value']['money'] = $couponInfo['coupons_money'];
                                $couponInfo['coupons_value']['limit'] = "{$couponInfo['coupons_money']}元优惠券";
                                break;
                            case 2:
                                $couponInfo['coupons_value']['money'] = $couponInfo['coupons_money'];
                                $discount = $couponInfo['coupons_discount'] * 10;
                                $couponInfo['coupons_value']['limit'] = "{$discount}折优惠券";
                                break;
                            default:
                                break;
                        }

                        break;
                }
            }
            $where = [];
            $where['b.allow_coupon'] = 1;

            // 判断商品类型
            if ($couponInfo['limit_type'] == 1) {
                $where['_string'] = '(a.is_promote = 0 and a.is_qinggou = 0) OR (a.is_promote = 0 and a.is_qinggou = 1 and (a.start_time > '.time().' OR a.end_time < '.time().'))'; 
               
            }       
            // 判断限制
            switch ((int)$couponInfo['limit_class_type']) {
                case 1:
                    break;
                case 2:
                    $class = json_decode($couponInfo['limit_class'], 1);
                    $id = [];
                    foreach ($class as $k => $val) {
                        if (!empty($val['class_id'])) {
                            $id[] = $val['class_id'];
                        }
                    }
                    $where['a.goods_class_1'] = ['not in', implode(',', $id)];
                    $where['a.goods_class_2'] = ['not in', implode(',', $id)];
                    $where['a.goods_class_3'] = ['not in', implode(',', $id)];
                    break;
                case 3:
                    $where['a.mall_class_1'] = ['not in', $couponInfo['limit_mall_class']];
                    $where['a.mall_class_2'] = ['not in', $couponInfo['limit_mall_class']];
                    $where['a.mall_class_3'] = ['not in', $couponInfo['limit_mall_class']];
                    break;
                case 4:
                    $where['a.goods_id'] = ['in', $couponInfo['limit_goods']];
                    break;
            }

            $where = array_merge($where, $condition);
        }

        $model = D('GoodsExtra');
        $where['a.goods_delete'] = 0;
        $where['a.goods_state'] = 1;
        $storeType = UtilModel::getStoreType($storeId);
        if ($storeType == 0 || $storeType == 2) {
            $queryStoreNoSelf = D('Store')->getStoreQueryId($storeId, 2)['data'];
            $where['a.store_id'] = ['in', $queryStoreNoSelf];
        } else {
            $where['a.store_id'] = $storeId;
        }
        $field = [
            'a.goods_id,a.goods_name,a.goods_img,a.goods_fig,a.store_id,a.is_qinggou,a.is_promote',
            'a.min_goods_price,a.max_goods_price,a.min_promote_price,a.max_promote_price',
            'b.goods_price'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['join'] = [
            '__GOODS__ b ON a.goods_id = b.goods_id',
            'LEFT JOIN __MB_GOODS_EXP__ c ON a.goods_id = c.goods_id',
        ];
        $options['field'] = implode(',', $field);
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        // 排序 1-默认 2-销量降序 3-销量升序 4-价格降序 价格升序
        switch ((int)$orderType) {
            case 2:
                $order = 'c.sales_vol DESC';
                break;
            case 3:
                $order = 'c.sales_vol ASC';
                break;
            case 4:
                $order = 'a.min_goods_price DESC';
                break;
            case 5:
                $order = 'a.min_goods_price ASC';
                break;
            default:
                $order = 'b.top DESC,b.sort DESC';
                break;
        }
        $options['order'] = $order;
        $result = $model->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key] = $this->initGroupInfo($value);
        }
        $list = $this->initGoodsBeans($storeId, $list);
        $result['data']['list'] = $list;
        $data = [];
        $data['coupon_info'] = $couponInfo;
        $data['goods']['list'] = $list;
        $data['goods']['total'] = $result['data']['total'];
        return getReturn(200, '', $data);
    }


    /**
     * @param array $info
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 初始化列表的一些字段
     * Date: 2017-11-16 10:09:39
     * Update: 2017-11-16 10:09:39
     * Version: 1.0
     */
    private function initGroupInfo($info = [])
    {
        // 商品主图
        if (isset($info['goods_img'])) {
            $image = json_decode($info['goods_img'], 1);
            $info['goods_img'] = empty($image[0]['url']) ? '' : $image[0]['url'];
            if (empty($info['goods_img'])) {
                $image = json_decode($info['goods_fig'], 1);
                $info['goods_img'] = empty($image[0]['url']) ? '' : $image[0]['url'];
            }
        }
        // 商品原价 (是个范围)
        if (isset($info['is_promote']) || isset($info['is_qinggou'])) {
            if ($info['is_promote'] == 1 || $info['is_qinggou'] == 1) {
                $info['goods_price'] = $info['min_promote_price'] == $info['max_promote_price'] ?
                    $info['min_promote_price'] : "{$info['min_promote_price']}~{$info['max_promote_price']}";
            } else {
                $info['goods_price'] = $info['min_goods_price'] == $info['max_goods_price'] ?
                    $info['min_goods_price'] : "{$info['min_goods_price']}~{$info['max_goods_price']}";
            }
        }
        // 库存
        if (isset($info['goods_storage'])) {
            $info['goods_storage'] = $info['goods_storage'] == -1 ? "充足" : $info['goods_storage'];
        }
        return $info;
    }

    /**
     * 获取某个商品的原始数据
     * @param int $goodsId
     * @return array
     * User: hjun
     * Date: 2017-12-29 15:59:52
     * Update: 2017-12-29 15:59:52
     * Version: 1.00
     */
    public function getGoodsInfo($goodsId = 0)
    {
        $redis = Redis::getInstance();
        $redis->select(1);
        $info = $redis->hGetAll("goods:{$goodsId}");
        if (empty($info)) {
            $where = [];
            $where['isdelete'] = 0;
            $where['goods_id'] = $goodsId;
            $where['goods_state'] = 1;
            $options = [];
            $options['where'] = $where;
            $options['field'] = $this->g_desc_fstr;
            $result = $this->queryRow($options);
            if (empty($result['data'])) return [];
            $info = $result['data'];
            $info['is_elete'] = 0;
            $info['iselete'] = 0;
            $info['goods_state'] = 1;
            $redis->hMset("goods:{$goodsId}", $info);
        } else {
            if ($info['goods_state'] != 1 || $info['isdelete'] = 1) {
                return [];
            }
        }
        return $info;
    }

    /**
     * 获取商品列表中所有的商家ID数组
     * @param array $goodsList
     * @return array
     * User: hjun
     * Date: 2018-01-02 00:37:44
     * Update: 2018-01-02 00:37:44
     * Version: 1.00
     */
    public function getGoodsListStoreId($goodsList = [])
    {
        $storeId = [];
        foreach ($goodsList as $key => $value) {
            if (!in_array($value['store_id'], $storeId)) {
                $storeId[] = $value['store_id'];
            }
        }
        return $storeId;
    }

    /**
     * 商品列表按照store_id分组 并且还返回所有的store_id数组
     * @param array $goodsList
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-01 22:02:46
     * Update: 2018-01-01 22:02:46
     * Version: 1.00
     */
    public function groupGoodsListByStoreId($goodsList = [])
    {
        $list = [];
        foreach ($goodsList as $key => $value) {
            $list[$value['store_id']][] = $value;
        }
        return $list;
    }

    /**
     * 将商品列表按照mj_id 分组 mj_id 倒序排列
     * 如果活动结束了 则设置为0
     * @param array $goodsList
     * @param array $mjList 当前商家有的满减活动列表
     * @return array
     * User: hjun
     * Date: 2018-01-02 00:41:57
     * Update: 2018-01-02 00:41:57
     * Version: 1.00
     */
    public function groupGoodsListByMjId($goodsList = [], $mjList = [])
    {
        $list = [];
        $mjId = [];
        foreach ($goodsList as $key => $value) {
            $mjInfo = $this->getMjInfoFromMjList($value['mj_id'], $mjList);
            $value['mj_id'] = (int)$mjInfo['mj_id'];
            if (!in_array((int)$value['mj_id'], $mjId)) {
                $mjId[] = (int)$value['mj_id'];
            }
        }
        rsort($mjId);
        foreach ($mjId as $key => $value) {
            $list[$value] = [];
        }
        foreach ($goodsList as $key => $value) {
            $mjId = (int)$value['mj_id'];
            $mjInfo = $this->getMjInfoFromMjList($mjId, $mjList);
            $mjId = $this->mjHasGoods($value['goods_id'], $mjInfo) ? $mjInfo['mj_id'] : 0;
            $value['mj_id'] = $mjId;
            $value['mj_level'] = 0;
            $list[$mjId][] = $value;
        }
        return $list;
    }

    /**
     * 判断满减活动是否包含了该商品
     * @param int $goodsId
     * @param array $mjInfo
     * @return bool
     * User: hjun
     * Date: 2018-01-03 17:23:18
     * Update: 2018-01-03 17:23:18
     * Version: 1.00
     */
    public function mjHasGoods($goodsId = 0, $mjInfo = [])
    {
        switch ((int)$mjInfo['limit_goods_type']) {
            case 1:
                return true;
                break;
            case 2:
                return in_array($goodsId, $mjInfo['limit_goods']);
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * 满减活动按照store_id分组
     * @param array $mjList
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-01 22:50:49
     * Update: 2018-01-01 22:50:49
     * Version: 1.00
     */
    public function groupMjListByStoreId($mjList = [])
    {
        $list = [];
        foreach ($mjList as $key => $value) {
            $list[$value['store_id']][] = $value;
        }
        return $list;
    }

    /**
     * 计算某个活动下 商品的消费情况
     * @param array $mjInfo 活动信息
     *  "mjItem": {
     * "promoteFlag": "string,标志字样。例如：满减、折扣",
     * "promoteTitle": "string,活动标题。例如：已购满2件，已减7.16元; 满100元减20元",
     * "actLineTitle": "string,链接字样。例如：去凑单。无则为空 前端也不需要显示",
     * "actLinkUrl": "string,链接地址"
     * },
     * @param array $mjGoodsList 参与该活动的商品列表 包含商品价格 数量信息
     * @param array $otherData 其他数据
     * @return array
     * User: hjun
     * Date: 2018-01-02 00:35:16
     * Update: 2018-01-02 00:35:16
     * Version: 1.00
     */
    public function calculateMjDiscountsDetail($mjInfo = [], $mjGoodsList = [], $otherData = [])
    {
        $detail = [
            'promoteFlag' => $mjInfo['mj_type_name'],
            'promoteTitle' => (int)$mjInfo['mj_id'] <= 0 ? '' : $mjInfo['discounts_name'],
            'actLineTitle' => '',
            'actLinkUrl' => '',
            'reducePrice' => 0,
            'tap' => 1,   //是否未满足条件
            'level' => 0
        ];
        // 计算商品总价格 计算商品总件数 转换成分进行计算
        $totalPrice = 0;
        $totalNum = 0;
        foreach ($mjGoodsList as $key => $value) {
            if ($value['select_state'] == 1) {
                $totalPrice += $value['new_price'] * 100 * (int)$value['buy_num'];
                $totalNum += (int)$value['buy_num'];
            }
        }

        // 计算活动到达哪个等级 并计算优惠价格 或者 还需凑单
        $result = $this->getMjDetailByLevel($totalPrice, $totalNum, $mjInfo);
        $level = $result['level'];
        $reducePrice = $result['reducePrice'];
        $promoteTitle = $result['promoteTitle'];
        $needCD = $result['needCD'];

        if ($needCD == 1 && (int)$mjInfo['mj_id'] > 0) {
            $detail['actLineTitle'] = '去凑单';
            $detail['actLinkUrl'] = "http://{$otherData['domain']}/index.php?c=Coupon&a=couponGoods&type=mj_goods&id={$mjInfo['mj_id']}&se={$mjInfo['store_id']}&f={$otherData['member_id']}";
            $detail['tap'] = -1;
        }

        // 购物车数据修改
        $cartTool = new CartLogic($mjInfo['store_id'], $this->memberId);
        foreach ($mjGoodsList as $key => $value) {
            $goodsId = explode('|', $value['gs_id'])[0];
            $specId = explode('|', $value['gs_id'])[1];
            $cartTool->setMjId($goodsId, $specId, (int)$mjInfo['mj_id'], $level, false, false);
        }
        $cartTool->saveItem();

        $detail['level'] = $level;
        $detail['reducePrice'] = $reducePrice;
        $detail['promoteTitle'] = $promoteTitle;
        return $detail;
    }

    /**
     *
     * @param int $totalPrice 总消费 分为单位
     * @param int $totalNum 总消费数量
     * @param array $mjInfo 满减活动信息
     * @return array
     * User: hjun
     * Date: 2018-01-02 11:29:25
     * Update: 2018-01-02 11:29:25
     * Version: 1.00
     */
    public function getMjDetailByLevel($totalPrice = 0, $totalNum = 0, $mjInfo = [])
    {
        $detail = [];
        $level = 0;
        $reducePrice = 0;
        $promoteTitle = '';
        $maxLevel = 0;
        foreach ($mjInfo['mj_rule'] as $key => $value) {
            // 记录最大等级
            if ($value['level'] > $maxLevel) {
                $maxLevel = $value['level'];
            }

            // 记录达到的优惠信息
            switch ((int)$mjInfo['mj_type']) {
                case 1:
                    if ($totalPrice >= $value['limit'] * 100) {
                        $level = $value['level'];
                        $reducePrice = $value['discounts'];
                        $promoteTitle = "已购满{$value['limit']}元，已减{$value['discounts']}元";
                    }
                    break;
                case 2:
                    if ($totalNum >= $value['limit']) {
                        $level = $value['level'];
                        $reducePrice = $totalPrice * (1 - round($value['discounts'] / 10, 2));
                        $reducePrice = round($reducePrice / 100, 2);
                        $promoteTitle = "已购满{$value['limit']}件，已减{$reducePrice}元";
                    }
                    break;
                case 3:
                    if ($totalPrice >= $value['limit'] * 100) {
                        $level = 1;
                        // 计算减的次数
                        $disNum = floor($totalPrice / ($value['limit'] * 100));
                        // 封顶的话最大只能是封顶的次数
                        if ($value['is_top'] == 1) {
                            if ($disNum > $value['dis_num']) {
                                $disNum = $value['dis_num'];
                            }
                        }
                        $reducePrice = $disNum * ($value['discounts'] * 100);
                        $reducePrice = round($reducePrice / 100, 2);
                        $limitMoney = $disNum * ($value['limit'] * 100);
                        $limitMoney = round($limitMoney / 100, 2);
                        $promoteTitle = "已购满{$limitMoney}元，已减{$reducePrice}元";
                    }
                    break;
                default:
                    break;
            }
        }

        // 判断是否满足优惠条件 或 还有更高优惠
        $needCD = 0;
        if ($level > 0) {
            if ($level < $maxLevel) {
                $needCD = 1;
            }
        } else {
            $maxLevel = 1;
            $needCD = 1;
        }
        if ($needCD == 1) {
            foreach ($mjInfo['mj_rule'] as $key => $value) {
                if ($value['level'] == $maxLevel) {
                    switch ((int)$mjInfo['mj_type']) {
                        case 1:
                            $needMoney = round(($value['limit'] * 100 - $totalPrice) / 100, 2);
                            if (empty($promoteTitle)) {
                                $promoteTitle = "{$mjInfo['discounts_name']}，还差{$needMoney}元可减{$value['discounts']}元";
                            } else {
                                $promoteTitle .= "，还差{$needMoney}元可减{$value['discounts']}元";
                            }
                            break;
                        case 2:
                            $needNum = $value['limit'] - $totalNum;
                            if (empty($promoteTitle)) {
                                $promoteTitle = "{$mjInfo['discounts_name']}，还差{$needNum}件可打{$value['discounts']}折";
                            } else {
                                $promoteTitle .= "，还差{$needNum}件可打{$value['discounts']}折";
                            }
                            break;
                        case 3:
                            $promoteTitle = $mjInfo['discounts_name'];
                            break;
                        default:
                            break;
                    }
                }
            }
        }


        $detail['level'] = $level;
        $detail['reducePrice'] = $reducePrice;
        $detail['promoteTitle'] = $promoteTitle;
        $detail['needCD'] = $needCD;
        return $detail;
    }

    /**
     * 获取某个商品可以选择的活动列表
     * @param int $goodsId 商品ID
     * @param array $mjList 满减活动列表
     * @param array $mjInfo 该商品参加的满减活动
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-02 00:34:43
     * Update: 2018-01-02 00:34:43
     * Version: 1.00
     */
    public function getGoodsMjList($goodsId = 0, $mjList = [], $mjInfo = [])
    {
        $list = [];
        foreach ($mjList as $key => $value) {
            switch ((int)$value['limit_goods_type']) {
                case 1:
                    $value['id'] = $value['mj_id'];
                    $value['name'] = $value['discounts_name'];
                    $value['state'] = $mjInfo['mj_id'] == $value['mj_id'] ? 1 : 0;
                    $list[] = $value;
                    break;
                case 2:
                    $limitGoods = is_array($value['limit_goods']) ?
                        $value['limit_goods'] : json_decode($value['limit_goods'], 1);
                    if (in_array($goodsId, $limitGoods)) {
                        $value['id'] = $value['mj_id'];
                        $value['name'] = $value['discounts_name'];
                        $value['state'] = $mjInfo['mj_id'] == $value['mj_id'] ? 1 : 0;
                        $list[] = $value;
                    }
                    break;
                default:
                    if ((int)$value['mj_id'] <= 0) {
                        $value['id'] = $value['mj_id'];
                        $value['name'] = $value['discounts_name'];
                        $value['state'] = $mjInfo['mj_id'] == $value['mj_id'] ? 1 : 0;
                        $list[] = $value;
                    }
                    break;
            }
        }
        return $list;
    }

    /**
     * 根据mj_id从满减活动列表中获取满减信息
     * @param int $mjId
     * @param array $mjList
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-02 01:56:13
     * Update: 2018-01-02 01:56:13
     * Version: 1.00
     */
    public function getMjInfoFromMjList($mjId = 0, $mjList = [])
    {
        foreach ($mjList as $key => $value) {
            if ($value['mj_id'] == $mjId) {
                return $value;
            }
        }
        return [];
    }

    /**
     * 转换商品数据的字段
     * @param array $goodsInfo
     * @param array $otherData
     * @return array
     * User: hjun
     * Date: 2018-01-02 01:41:31
     * Update: 2018-01-02 01:41:31
     * Version: 1.00
     */
    public function transformGoodsField($goodsInfo = [], $otherData = [])
    {
        $info = [];
        $info['gId'] = $goodsInfo['gid_sid'];
        $info['gs_id'] = $goodsInfo['gs_id'];
        $info['shopBol'] = $goodsInfo['select_state'] == 1;
        $info['gName'] = $goodsInfo['goods_name'];
        $info['img'] = $goodsInfo['main_img'] . '?_90x90x2';
        $info['gLink'] = "http://{$otherData['domain']}//index.php?m=Service&c=Goods&a=goods_detail&id={$goodsInfo['gs_id']}&se={$goodsInfo['store_id']}&f={$otherData['member_id']}";
        $info['gPrice'] = $goodsInfo['new_price'];
        $info['num'] = $goodsInfo['buy_num'];
        $info['gTtl'] = $goodsInfo['new_price'] * $goodsInfo['buy_num'];
        $info['gAbroad'] = (int)$goodsInfo['is_abroad'] == 1;
        $info['gHot'] = (int)$goodsInfo['is_hot'] == 1;
        $info['gSnap'] = (int)$goodsInfo['state'] == 2;
        $info['gSale'] = (int)$goodsInfo['state'] == 1;
        $info['gVip'] = (int)$goodsInfo['viplevel'] > 0;
        $info['gVipLayer'] = (int)$goodsInfo['viplevel'];
        $info['gSpec'] = $goodsInfo['spec_name'];
        return $info;
    }

    /**
     * 获取购物车数据 格式按规定的
     * @param int $storeId
     * @param int $memberId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-29 16:10:43
     * Update: 2017-12-29 16:10:43
     * Version: 1.00
     */
    public function getCartData($storeId = 0, $memberId = 0)
    {
        $cartTool = new CartLogic($storeId, $memberId);
        return $cartTool->getCartData();
    }

    /**
     * 根据条件获取列表
     * @param int $goodsId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-25 18:14:17
     * Update: 2018-01-25 18:14:17
     * Version: 1.00
     */
    public function getGoodsBeanById($goodsId = 0)
    {
        $where = [];
        $where['goods_id'] = $goodsId;
        $options = [];
        $options['where'] = $where;
        $options['field'] = $this->g_desc_fstr;
        $result = $this->queryRow($options);
        return empty($result['data']) ? [] : $result['data'];
    }

    /**
     * 根据条件获取列表
     * @param array $where
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-25 18:14:17
     * Update: 2018-01-25 18:14:17
     * Version: 1.00
     */
    public function getGoodsListByWhere($where = [])
    {
        $options = [];
        $options['where'] = $where;
        $options['field'] = $this->g_list_fstr;
        $result = $this->queryList($options);
        return empty($result['data']['list']) ? [] : $result['data']['list'];
    }

    /**
     * 获取商品的商家ID
     * @param int $goodsId
     * @return int
     * User: hjun
     * Date: 2018-05-04 22:27:11
     * Update: 2018-05-04 22:27:11
     * Version: 1.00
     */
    public function getGoodsStoreId($goodsId = 0)
    {
        $where = [];
        $where['goods_id'] = $goodsId;
        $info = $this->field('store_id')->where($where)->cache(true)->find();
        return empty($info['store_id']) ? 0 : $info['store_id'];
    }

    public function isSortDefault($sortType)
    {
        return $sortType == self::SORT_DEFAULT;
    }

    public function isSortPriceAsc($sortType)
    {
        return $sortType == self::SORT_PRICE_ASC;
    }

    public function isSortPriceDesc($sortType)
    {
        return $sortType == self::SORT_PRICE_DESC;
    }

    public function isSortSalesDesc($sortType)
    {
        return $sortType == self::SORT_SALES_DESC;
    }

    public function isHasStock($inStock)
    {
        return $inStock == 1;
    }

    public function isSortSales($sortType)
    {
        return $sortType == self::SORT_SALES_ASC || $sortType == self::SORT_SALES_DESC;
    }

    /**
     * 获取搜索条件
     * @param $store_id
     * @param $keyword
     * @param $sort_type
     * @param $in_stock
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-05-09 10:49:28
     * Update: 2018-05-09 10:49:28
     * Version: 1.00
     */
    public function getSearchWhere($store_id, $keyword, $sort_type, $in_stock)
    {
        $where = [];
        $prefix = $this->isSortSalesDesc($sort_type) ? 'a.' : '';
        $where["{$prefix}store_id"] = $this->getStoreIds($store_id);
        $where["{$prefix}isdelete"] = 0;
        $where["{$prefix}goods_state"] = 1;
        $search["{$prefix}goods_desc"] = getSearchArr($keyword);
        if ($this->isHasStock($in_stock)) {
            $where["{$prefix}goods_storage"] = array('neq', 0);
        }
        // 增加条码搜索 "barcode":"" || goods_barcode
        $search['goods_barcode'] = $keyword;
        $search['goods_spec'] = ['like', '%"barcode":"' . $keyword . '"%'];

        // 多规格组合
        $optionWhere = [];
        $optionWhere['goods_barcode'] = $keyword;
        $goodsIds = M('goods_option')
            ->distinct(true)
            ->where($optionWhere)
            ->getField('goods_id', true);
        if (!empty($goodsIds)) {
            $search['goods_id'] = ['in', $goodsIds];
        }

        // 详情搜索 "text":"{$keyword}" ||
        $search['goods_figure'] = ['like', "%\"text\":\"%{$keyword}%\"%"];
        $search['goods_content'] = ['like', "%{$keyword}%"];
        $search['_logic'] = 'or';
        $where['_complex'] = $search;
        return $where;
    }

    public function getGoodsLists($store_id, $condition = array(), $order = '', $page = 1, $num = 8)
    {
        $first = ($page - 1) * $num;
        $goodsBeans = $this->table('xunxin_goods a')
            ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
            ->join('left join xunxin_goods_extra c on a.goods_id = c.goods_id')
            ->field($this->goods_field)->order($order)
            ->where($condition)
            ->limit($first . ',' . $num)->select();

        $goodslist = $this->initGoodsBeans($store_id, $goodsBeans, 1);
        return $goodslist;

    }
	
	public function getGoodsCount($store_id, $condition = array())
    {
       
        $num = $this->table('xunxin_goods a')
            ->join('left join xunxin_mb_goods_exp b on a.goods_id = b.goods_id')
            ->join('left join xunxin_goods_extra c on a.goods_id = c.goods_id')
            ->field($this->goods_field)
            ->where($condition)
            ->count();
        return $num;

    }

    /**
     * 根据分类ID获取商品列表
     * @param int $storeId 商家ID
     * @param int $memberId 会员ID
     * @param int $gcId 分类ID  -2=>全部分类; <=0=>未分类; >0=>有分类
     * @param int $sortType
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-30 14:49:39
     * Update: 2018-01-30 14:49:39
     * Version: 1.00
     */
    public function getGoodsListByClassId($storeId = 0, $memberId = 0, $gcId = 0, $sortType = 0, $page = 1, $limit = 0, $condition = [])
    {
        switch ((int)$sortType) {
            case self::SORT_PRICE_ASC:
                $order = 'goods_price ASC';
                break;
            case self::SORT_PRICE_DESC:
                $order = 'goods_price DESC';
                break;
            case self::SORT_SALES_DESC:
                $order = 'b.sales_vol DESC';
                break;
            case self::SORT_SALES_ASC:
                $order = 'b.sales_vol ASC';
                break;
            default:
                $order = 'top DESC,sort DESC';
                break;
        }
        $suffix = $this->isSortSales($sortType) ? 'a.' : '';
        $where = [];
        $where[$suffix . 'store_id'] = $this->getStoreIds($storeId);
        if ($gcId != -2) {
            $where[$suffix . 'gc_id'] = $gcId > 0 ? $this->getGcIds($gcId) : array(array('eq', -1), array('eq', 0), 'or');
        }
        $where[$suffix . 'isdelete'] = 0;
//        $where[$suffix . 'goods_storage'] = ['neq', 0];
        $where[$suffix . 'goods_state'] = 1;
        $where = array_merge($where, $condition);
        $options = [];
        $options['field'] = $this->g_list_fstr;
        if ($this->isSortSales($sortType)) {
            $options['alias'] = 'a';
            $options['join'] = [
                'LEFT JOIN __MB_GOODS_EXP__ b ON a.goods_id = b.goods_id'
            ];
            $fields = explode(',', $this->g_list_fstr);
            foreach ($fields as &$field){
                $field = "{$suffix}$field";
            }
            $options['field'] = $fields;
        }
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = $order;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $cartTool = new CartLogic($storeId, $memberId);
        $list = $cartTool->initGoodsBeans($result['data']['list']);
        // hjun 2017-06-12 09:45:55  判断商品是否是单个规格
        foreach ($list as $key => $value) {
            $specLength = count($value['spec_option']);
            $list[$key]['spec_length'] = $specLength;
            if ($specLength <= 1) {
                $list[$key]['goods_id'] = $value['goods_id'];
                $list[$key]['spec_id'] = empty($value['spec_option'][0]['specs']) ? 0 : $value['spec_option'][0]['specs'];
            }
            // 组装规格的字符串
            if ($specLength == 0) {
                $list[$key]['spec_string'] = '';
            } else {
                $names = [];
                foreach ($value['spec'] as $title) {
                    if (empty($title['title'])) {
                        $str = '';
                    } else {
                        $str = $title['title'] == L('SPEC') ? '' : "{$title['title']}:";
                    }
                    $specName = [];
                    foreach ($title['item'] as $spec) {
                        $specName[] = $spec['title'];
                    }
                    $specName = implode('、', $specName);
                    $names[] = "{$str}{$specName}";
                }
                $list[$key]['spec_string'] = implode(',', $names);
            }
        }

        // hjun 2017-12-28 10:54:55 判断商品是否在活动
        $mjList = D('MjActivity')->getMjList($storeId)['data']['list'];
        $hasFlagGoodsId = [];
        $hasSetFlag = 0;
        foreach ($mjList as $key => $value) {
            if ($value['limit_goods_type'] == 1) {
                foreach ($list as $k => $val) {
                    $list[$k]['mj_flag'] = 1;
                }
                $hasSetFlag = 1;
                break;
            } else {
                $hasFlagGoodsId = array_merge($hasFlagGoodsId, $value['limit_goods']);
            }
        }
        if ($hasSetFlag == 0) {
            foreach ($list as $key => $value) {
                $goodsId = explode('_', $value['goods_id'])[0];
                if (in_array($goodsId, $hasFlagGoodsId)) {
                    $list[$key]['mj_flag'] = 1;
                } else {
                    $list[$key]['mj_flag'] = 0;
                }
            }
        }
        $result['data']['list'] = $list;
        return $result;
    }

    //////////////////////////// ADMIN ///////////////////////////////////
    //////////////////////////// ADMIN ///////////////////////////////////
    //////////////////////////// ADMIN ///////////////////////////////////
    //////////////////////////// ADMIN ///////////////////////////////////
    /**
     * @param int $goodsId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取商品的多规格组合的数组
     * Date: 2017-11-15 17:56:58
     * Update: 2017-11-15 17:56:59
     * Version: 1.0
     */
    public function getGoodsSpecArr($goodsId = 0)
    {
        $where = [];
        $where['a.goods_id'] = $goodsId;
        $options = [];
        $options['alias'] = 'a';
        $options['where'] = $where;
        $options['field'] = 'a.id,a.title,b.id spec_id,b.title name';
        $options['join'] = [
            '__GOODS_SPEC_ITEM__ b ON b.specid = a.id'
        ];
        $result = D('GoodsSpec')->queryList($options);
        if ($result['code'] !== 200) return $result;
        $spec = $result['data']['list'];
        $specArr = [];
        foreach ($spec as $key => $value) {
            $item = [];
            $item['spec_id'] = $value['spec_id'];
            $item['spec_name'] = $value['name'];
            $specArr[$value['title']][] = $item;
        }
        return getReturn(200, '', $specArr);
    }

    /**
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 采集图片任务
     * Date: 2017-11-17 11:59:39
     * Update: 2017-11-17 11:59:40
     * Version: 1.0
     */
    public function collectGoodsImg($storeId = 0)
    {
        set_time_limit(500);
        logWrite("采集图片开始{$storeId}", 'INFO', 'write');
        $api = C('api_url');
        $param = [];
        $param['act'] = 'goods_library';
        $param['op'] = 'get_goods';
        $modelGoods = $this;
        $version = $this->max('version');
        logWrite("最大版本号:{$version}", 'INFO', 'write');
        // 搜索没有图片 有商品条码的商品
        $where = array();
        $where['store_id'] = $storeId;
        $where['isdelete'] = 0;
        $where['goods_state'] = 1;
        $where['goods_figure'] = array(array('eq', ''), array('exp', ' IS NULL '), array('eq', '[]'), array('like', "%[{\"url\":null}]%"), 'or');
        $where['goods_barcode'] = ['neq', ''];
        // 计算商家没有图片的商品总量
        $total = $modelGoods->field('goods_id')->where($where)->count();
        logWrite("没有图片的商品总量为:{$total}", 'INFO', 'write');
        if ($total <= 0) return getReturn(200, '没有需要采集的商品');
        // 一次采集2000个商品
        $count = (int)($total / 2000) + 1;
        $takeImg = 0;
        $controller = new GoodsLibraryController();
        for ($i = 0; $i < $count; $i++) {
            $goods_list = $modelGoods->field('goods_id,goods_barcode,goods_name')->where($where)->page($i + 1, 2000)->select();
            if (empty($goods_list)) {
                continue;
            }
            foreach ($goods_list as $key => $value) {
                $data = array();
                $returnInfo = $controller->getGoodsDataByBarcode($value['goods_barcode'], $value['goods_name']);
                $imgUrl = $returnInfo[0]['img'];
                $url = json_decode($imgUrl, true);
                logWrite("商品{$value['goods_id']}采集到的图片:{$imgUrl}", 'INFO');
                if (!is_array($url)) {
                    // 如果url是个字符串，不能是json。则封装成json
                    if (!empty($imgUrl)) {
                        $arr = array(
                            array('url' => $imgUrl)
                        );
                        $data['goods_figure'] = json_encode($arr);
                        $data['goods_image'] = json_encode($arr);
                    }
                } else {
                    // 如果是json，则直接设置
                    if (!empty($url[0]['url'])) {
                        $data['goods_figure'] = $imgUrl; // 设置第一个url的json字符串
                        $data['goods_image'] = $imgUrl;
                    }
                }
                // 有图片进行更新
                if (!empty($data['goods_image']) && !empty($data['goods_figure'])) {
                    $where = array();
                    $where['goods_id'] = $value['goods_id'];
                    $data['version'] = ++$version;
                    $result = $modelGoods->where($where)->save($data);
                    if ($result === false) {
                        return getReturn(-1, "采集中断,可能是服务器太忙了,已采集{$takeImg}张图片", $takeImg);
                    }
                    $takeImg++;
                }
                usleep(20);
            }
        }
        if ($takeImg > 0) {
            return getReturn(200, "已采集{$takeImg}张图片", $takeImg);
        } else {
            return getReturn(200, '没有采集到图片', $takeImg);
        }
    }

    /**
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 更新商品列表的信息
     * Date: 2017-11-24 10:23:05
     * Update: 2017-11-24 10:23:05
     * Version: 1.0
     */
    public function refreshGoods($storeId = 0)
    {
        set_time_limit(500);
        $extra = M('goods_extra');   //商品延伸表
        $goods = $this;
        $sales = M('mb_sales');
        $option = M('goods_option');

        try {
            do {
                $w1 = array();
                $w = array();
                if ($storeId > 0) $w1['store_id'] = $storeId;
                $version = $extra->where($w1)->max('version');
                $salesVersion = $extra->where($w1)->max('sales_version');
                logWrite("上次商品最大版本号:{$version},促销表最大版本号:{$salesVersion}");
                $version = empty($version) ? 0 : $version;
                $w['version'] = array('gt', $version);
                if ($storeId > 0) $w['store_id'] = $storeId;
                // 查询促销表中大于上次版本号的商品ID数组
                $where = [];
                $where['version'] = ['gt', $salesVersion];
                if ($storeId > 0) $where['storeid'] = $storeId;
                $goodsId = $sales->where($where)->getField('gid', true);
                if (!empty($goodsId)) {
                    unset($w['version']);
                    $idStr = implode(',', $goodsId);
                    $w['_string'] = "( version > {$version} OR ( goods_id IN ({$idStr}) AND spec_open != 1 ) )";
                }
                $count = $goods->where($w)->order('version')->count();
                logWrite("需要更新的商品数量:{$count}");
                // goods_id,store_id,goods_name,goods_price,goods_desc,goods_figure,
                // is_abroad,goods_spec,goods_pv,goods_link,goods_audio,goods_storage,
                // goods_image,spec_open,gc_id,goods_content,is_promote,is_qianggou,
                // qianggou_start_time,qianggou_end_time,goods_promoteprice,price_hide_desc,
                // wx_share_text,thirdpart_money_limit,allow_coupon,limit_buy,sales_base
                $field = [
                    'a.goods_id,a.goods_name,a.store_id,a.goods_state,a.isdelete,a.gc_id',
                    'a.goods_image,a.goods_figure,a.goods_content,a.goods_price,a.goods_storage',
                    'a.goods_barcode,a.goods_number,a.goods_spec,a.spec_open,a.version,a.is_promote,a.is_qianggou',
                    'a.qianggou_start_time,a.qianggou_end_time,a.goods_promoteprice,a.goods_addtime',
                    'a.mall_goods_class_1,a.mall_goods_class_2,a.mall_goods_class_3',
                    'a.goods_desc,a.is_abroad,a.goods_pv,a.goods_link,a.goods_audio,a.price_hide_desc',
                    'a.wx_share_text,a.thirdpart_money_limit,a.allow_coupon,a.limit_buy,a.sales_base',
                    'a.goods_pv', 'a.top', 'a.sort',
                    'b.store_name',
                    'c.is_hot'
                ];
                $w = [];
                $w['a.version'] = array('gt', $version);
                if (!empty($goodsId)) {
                    unset($w['a.version']);
                    $idStr = implode(',', $goodsId);
                    $w['_string'] = "( a.version > {$version} OR ( a.goods_id IN ({$idStr}) AND a.spec_open != 1 ) )";
                }
                if ($storeId > 0) $w['a.store_id'] = $storeId;
                $order = 'a.version';
                $lists = $goods
                    ->alias('a')
                    ->field(implode(',', $field))
                    ->join("__STORE__ b ON a.store_id = b.store_id")
                    ->join("LEFT JOIN __MB_GOODS_EXP__ c ON a.goods_id = c.goods_id")
                    ->where($w)->limit(2000)->order($order)->select();
                // Redis 存储商品表数据 商品表存进1号数据库
                foreach ($lists as $list) {
                    // 每个商品都存入缓存
                    $data = [];
                    $data['goods_id'] = $list['goods_id'];
                    $data['store_id'] = $list['store_id'];
                    $data['store_name'] = $list['store_name'];
                    $data['create_time'] = $list['goods_addtime'];
                    // 商品状态
                    $data['goods_state'] = $list['goods_state'];
                    // 删除状态
                    $data['goods_delete'] = $list['isdelete'];
                    // 是否热卖
                    $data['is_hot'] = (int)$list['is_hot'];
                    // 排序
                    $data['sort'] = $list['sort'];
                    $data['top'] = $list['top'];
                    // 商品分类
                    if (isset($list['gc_id'])) {
                        if ($list['gc_id'] <= 0) {
                            for ($level = 1; $level <= 3; $level++) {
                                $data["goods_class_{$level}"] = 0;
                            }
                        } else {
                            $classId = explode('|', $list['gc_id']);
                            for ($level = 1; $level <= 3; $level++) {
                                $value = $classId[$level - 1] <= 0 ? 0 : $classId[$level - 1];
                                $data["goods_class_{$level}"] = $value;
                            }
                        }
                    }
                    // 商城分类
                    $data['mall_class_1'] = $list['mall_goods_class_1'];
                    $data['mall_class_2'] = $list['mall_goods_class_2'];
                    $data['mall_class_3'] = $list['mall_goods_class_3'];
                    // 商品主图
                    $data['goods_img'] = $list['goods_image'];
                    // 商品简约图文
                    $data['goods_fig'] = $list['goods_figure'];
                    // 商品图文详情
                    $data['goods_content'] = $list['goods_content'];

                    $data['spec_attr'] = '';
                    $data['goods_spec'] = '';
                    $data['all_stock'] = 0;
                    // 规格类型
                    $data['spec_type'] = -1;
                    // 新增商品名称
                    $data['goods_name'] = $list['goods_name'];
                    // 商品PV
                    $data['goods_pv'] = 0;
                    // 放在判断前先清空 否则数据继承
                    // 规格
                    $goodsSpec = [];
                    // 规格属性
                    $specAttr = [];
                    /*不是新版多规格*/
                    if ($list['spec_open'] != 1) {
                        // hj 2017-11-15 15:17:09 没有开启多规格
                        $where = [];
                        $where['goods_id'] = $list['goods_id'];
                        $where['store_id'] = $list['store_id'];
                        $where['isdelete'] = 0;
                        $saleNum = M('mb_goods_exp')->where($where)->getField('sales_vol');
                        $saleNum = empty($saleNum) ? 0 : $saleNum;
                        $saleBase = empty($list['sales_base']) ? 0 : $list['sales_base'];
                        $specs = json_decode($list['goods_spec'], true);
                        if (empty($specs)) {
                            $data['goods_pv'] = (double)$list['goods_pv'];
                            $data['min_goods_price'] = $list['goods_price'];
                            $data['max_goods_price'] = $list['goods_price'];
                            $data['min_stock'] = $list['goods_storage'];
                            $data['max_stock'] = $list['goods_storage'];
                            $data['all_stock'] = $list['goods_storage'];
                            $data['sale_num'] = $saleNum;
                            $data['all_sale_num'] = $saleNum + $saleBase;
                            $data['spec_type'] = -1;
                            $data['spec_attr'] = [
                                'spec_id_0' => [
                                    'primary_id' => 0,
                                    'spec_id' => 0,
                                    "spec_name" => '',
                                    "spec_price" => $list['goods_price'],
                                    'spec_stock' => $list['goods_storage'] == -1 ? "充足" : $list['goods_storage'],
                                    'spec_goods_barcode' => $list['goods_barcode'],
                                    'spec_goods_number' => $list['goods_number'],
                                    'spec_sales_num' => $saleNum,
                                    'spec_sales_base' => $saleBase,
                                    'spec_all_sale_num' => $saleNum + $saleBase,
                                    'spec_promote_price' => 0,
                                    'spec_goods_pv' => $data['goods_pv'],
                                ]
                            ];
                        } else {
                            $data['spec_type'] = 1;
                            $min_goods_price = 0;
                            $max_goods_price = 0;
                            $min_stock = '';
                            $max_stock = '';
                            // 总库存
                            $all_stock = 0;
                            $allSpecSaleBase = 0;
                            foreach ($specs as $key => $spec) {
                                if ($min_goods_price == 0 || $min_goods_price > $spec['price']) {
                                    $min_goods_price = $spec['price'];
                                }
                                if ($max_goods_price == 0 || $max_goods_price < $spec['price']) {
                                    $max_goods_price = $spec['price'];
                                }
                                if (($min_stock == '' || $min_stock > $spec['storage']) && $spec['storage'] != -1) {
                                    $min_stock = $spec['storage'];
                                }
                                if ($max_stock == '' || $max_stock < $spec['storage'] || $spec['storage'] == -1) {
                                    if ($max_stock == -1 || $spec['storage'] == -1) {
                                        $max_stock = -1;
                                    } else {
                                        $max_stock = $spec['storage'];
                                    }
                                }
                                // 组装规格字段
                                $item = [];
                                $item['spec_id'] = $spec['spec_id'];
                                $item['spec_name'] = $spec['name'];
                                $goodsSpec[L('SPEC')][] = $item;
                                // 组装规格属性字段
                                $specSaleBase = (int)$spec['sales_base'];
                                $allSpecSaleBase += $specSaleBase;
                                $specAttr["spec_id_{$spec['spec_id']}"]['primary_id'] = $spec['spec_id'];
                                $specAttr["spec_id_{$spec['spec_id']}"]['spec_id'] = $spec['spec_id'];
                                $specAttr["spec_id_{$spec['spec_id']}"]['spec_price'] = (double)$spec['price'];
                                $specAttr["spec_id_{$spec['spec_id']}"]['spec_stock'] = $spec['storage'] == -1 ? "充足" : $spec['storage'];
                                $specAttr["spec_id_{$spec['spec_id']}"]['spec_goods_barcode'] = empty($spec['barcode']) ? '' : $spec['barcode'];
                                $specAttr["spec_id_{$spec['spec_id']}"]['spec_goods_number'] = empty($spec['goods_number']) ? '' : $spec['goods_number'];
                                $specAttr["spec_id_{$spec['spec_id']}"]['spec_goods_pv'] = (double)$spec['pv'];
                                $specAttr["spec_id_{$spec['spec_id']}"]['spec_sales_base'] = empty($spec['sales_base']) ? '' : $spec['sales_base'];
                                $specAttr["spec_id_{$spec['spec_id']}"]['spec_promote_price'] = 0;
                                $specAttr["spec_id_{$spec['spec_id']}"]['spec_goods_img'] = '';
                                $specAttr["spec_id_{$spec['spec_id']}"]['spec_name'] = $spec['name'];
                                $specAttr["spec_id_{$spec['spec_id']}"]['spec_sales_num'] = $saleNum;
                                $specAttr["spec_id_{$spec['spec_id']}"]['spec_all_sale_num'] = $saleNum + $specSaleBase;
                                // hj 2017-11-15 14:32:29 计算总库存
                                if ($spec['storage'] == -1) {
                                    $all_stock = -1;
                                } elseif ($all_stock != -1) {
                                    $all_stock += $spec['storage'];
                                }
                            }
                            if ($min_stock == '') {
                                $min_stock = $max_stock;
                            }
                            $data['min_stock'] = $min_stock;
                            $data['max_stock'] = $max_stock;
                            if ($min_goods_price == 0) {
                                $min_goods_price = $max_goods_price;
                            }
                            if ($max_goods_price == 0) {
                                $max_goods_price = $min_goods_price;
                            }
                            $data['min_goods_price'] = $min_goods_price;
                            $data['max_goods_price'] = $max_goods_price;
                            $data['all_stock'] = $all_stock;
                            $data['sale_num'] = $saleNum;
                            $data['all_sale_num'] = $saleNum + $allSpecSaleBase;
                            $data['goods_spec'] = empty($goodsSpec) ? "" : json_encode($goodsSpec, JSON_UNESCAPED_UNICODE);
                        }
                        $w1 = array();
                        $w1['gid'] = $list['goods_id'];
                        $field = [
                            'islongtime,newprice,start_time,end_time,isdelete,version'
                        ];
                        $saleinfo = $sales
                            ->field(implode(',', $field))
                            ->where($w1)->order('version DESC')->find();
                        $data['sales_version'] = empty($saleinfo['version']) ? 0 : $saleinfo['version'];
                        if ($saleinfo['isdelete'] == 1) $saleinfo = [];
                        if (empty($saleinfo)) {
                            $data['is_qinggou'] = 0;
                            $data['is_promote'] = 0;
                            $data['min_promote_price'] = 0;
                            $data['max_promote_price'] = 0;
                            $data['start_time'] = 0;
                            $data['end_time'] = 0;
                        } elseif ($saleinfo['islongtime'] == '1') {
                            $data['is_qinggou'] = 0;
                            $data['is_promote'] = 1;
                            $data['min_promote_price'] = $saleinfo['newprice'];
                            $data['max_promote_price'] = $saleinfo['newprice'];
                            $data['start_time'] = 0;
                            $data['end_time'] = 0;
                            // 设置促销价
                            if (!empty($specs)) {
                                $specAttr["spec_id_{$specs[0]['spec_id']}"]['spec_promote_price'] = (double)$saleinfo['newprice'];
                            } else {
                                $data['spec_attr']['spec_id_0']['spec_promote_price'] = (double)$saleinfo['newprice'];
                            }
                        } elseif ($saleinfo['islongtime'] == '0') {

                            if ($saleinfo['end_time'] >= NOW_TIME) {
                                $data['is_qinggou'] = 1;
                                $data['is_promote'] = 0;
                                $data['min_promote_price'] = $saleinfo['newprice'];
                                $data['max_promote_price'] = $saleinfo['newprice'];
                                $data['start_time'] = $saleinfo['start_time'];
                                $data['end_time'] = $saleinfo['end_time'];
                                if (!empty($specs)) {
                                    $specAttr["spec_id_{$specs[0]['spec_id']}"]['spec_promote_price'] = (double)$saleinfo['newprice'];
                                } else {
                                    $data['spec_attr']['spec_id_0']['spec_promote_price'] = (double)$saleinfo['newprice'];
                                }
                            } else {
                                $data['is_qinggou'] = 0;
                                $data['is_promote'] = 0;
                                $data['min_promote_price'] = 0;
                                $data['max_promote_price'] = 0;
                                $data['start_time'] = 0;
                                $data['end_time'] = 0;
                            }
                        }
                        if (!empty($specAttr)) {
                            $data['spec_attr'] = empty($specAttr) ? "" : json_encode($specAttr, JSON_UNESCAPED_UNICODE);
                        } else {
                            $data['spec_attr'] = empty($data['spec_attr']) ? "" : json_encode($data['spec_attr'], JSON_UNESCAPED_UNICODE);
                        }
                    } else {   /*开启多规格*/
                        // hj 2017-11-15 15:17:09 开启多规格
                        $data['spec_type'] = 2;
                        $w2 = array();
                        $w2['goods_id'] = $list['goods_id'];
                        $option_lists = $option
                            ->where($w2)
                            ->field('id,title,thumb,goods_promoteprice,goods_price,stock,goods_pv,specs,goods_barcode,goods_number,sales,sales_base')
                            ->select();
                        $min_goods_price = 0;
                        $max_goods_price = 0;
                        $min_stock = '';
                        $max_stock = '';
                        $all_stock = 0; // 总库存
                        // 规格属性
                        $specAttr = [];
                        $allSpecSaleBase = 0;
                        $allSpecSaleNum = 0;
                        foreach ($option_lists as $option_list) {
                            if ($min_goods_price == 0 || $min_goods_price > $option_list['goods_price']) {
                                $min_goods_price = $option_list['goods_price'];
                            }
                            if ($max_goods_price == 0 || $max_goods_price < $option_list['goods_price']) {
                                $max_goods_price = $option_list['goods_price'];
                            }
                            if (($min_stock == '' || $min_stock > $option_list['stock']) && $option_list['stock'] != -1) {
                                $min_stock = $option_list['stock'];
                            }
                            if ($max_stock == '' || $max_stock < $option_list['stock'] || $option_list['stock'] == -1) {
                                if ($max_stock == -1 || $option_list['stock'] == -1) {
                                    $max_stock = -1;
                                } else {
                                    $max_stock = $option_list['stock'];
                                }
                            }
                            // 组装规格属性字段
                            $allSpecSaleBase += (int)$option_list['sales_base'];
                            $allSpecSaleNum += (int)$option_list['sales'];
                            $specAttr["spec_id_{$option_list['specs']}"]['primary_id'] = $option_list['id'];
                            $specAttr["spec_id_{$option_list['specs']}"]['spec_id'] = $option_list['specs'];
                            $specAttr["spec_id_{$option_list['specs']}"]['spec_price'] = (double)$option_list['goods_price'];
                            $specAttr["spec_id_{$option_list['specs']}"]['spec_stock'] = $option_list['stock'] == -1 ? "充足" : $option_list['stock'];
                            $specAttr["spec_id_{$option_list['specs']}"]['spec_goods_barcode'] = $option_list['goods_barcode'];
                            $specAttr["spec_id_{$option_list['specs']}"]['spec_goods_number'] = $option_list['goods_number'];
                            $specAttr["spec_id_{$option_list['specs']}"]['spec_goods_pv'] = (double)$option_list['goods_pv'];
                            $specAttr["spec_id_{$option_list['specs']}"]['spec_sales_base'] = (int)$option_list['sales_base'];
                            $specAttr["spec_id_{$option_list['specs']}"]['spec_promote_price'] = (double)$option_list['goods_promoteprice'];
                            $specAttr["spec_id_{$option_list['specs']}"]['spec_goods_img'] = $option_list['thumb'];
                            $specAttr["spec_id_{$option_list['specs']}"]['spec_name'] = $option_list['title'];
                            $specAttr["spec_id_{$option_list['specs']}"]['spec_sales_num'] = (int)$option_list['sales'];
                            $specAttr["spec_id_{$option_list['specs']}"]['spec_all_sale_num'] = (int)$option_list['sales'] + (int)$option_list['sales_base'];
                            // hj 2017-11-15 14:44:03 计算总库存
                            if ($option_list['stock'] == -1) {
                                $all_stock = -1;
                            } elseif ($all_stock != -1) {
                                $all_stock += $option_list['stock'];
                            }
                        }

                        if ($min_stock == '') {
                            $min_stock = $max_stock;
                        }
                        $data['min_stock'] = $min_stock;
                        $data['max_stock'] = $max_stock;
                        $data['all_stock'] = $all_stock;
                        $data['sale_num'] = $allSpecSaleNum;
                        $data['all_sale_num'] = $allSpecSaleBase + $allSpecSaleNum;
                        $goodsSpec = $this->getGoodsSpecArr($list['goods_id'])['data'];
                        $data['goods_spec'] = empty($goodsSpec) ? "" : json_encode($goodsSpec, JSON_UNESCAPED_UNICODE);
                        $data['spec_attr'] = empty($specAttr) ? "" : json_encode($specAttr, JSON_UNESCAPED_UNICODE);
                        if (empty($option_lists)) {
                            $data['spec_attr'] = [
                                'spec_id_0' => [
                                    'primary_id' => 0,
                                    'spec_id' => 0,
                                    "spec_name" => '',
                                    "spec_price" => $list['goods_price'],
                                    'spec_stock' => $list['goods_storage'] == -1 ? "充足" : $list['goods_storage'],
                                    'spec_goods_barcode' => $list['goods_barcode'],
                                    'spec_goods_number' => $list['goods_number'],
                                    'spec_sales_num' => $data['sale_num'],
                                    'spec_sales_base' => $allSpecSaleBase,
                                    'spec_all_sale_num' => $data['all_sale_num'],
                                    'spec_promote_price' => 0,
                                    'spec_goods_pv' => 0,
                                ]
                            ];
                            $data['spec_attr'] = json_encode($data['spec_attr']);
                        }
                        if ($min_goods_price == 0) {
                            $min_goods_price = $max_goods_price;
                        }
                        if ($max_goods_price == 0) {
                            $max_goods_price = $min_goods_price;
                        }
                        $data['min_goods_price'] = $min_goods_price;
                        $data['max_goods_price'] = $max_goods_price;
                        if ($list['is_promote'] == '0' && $list['is_qianggou'] == '0') {
                            $data['is_qinggou'] = 0;
                            $data['is_promote'] = 0;
                            $data['min_promote_price'] = 0;
                            $data['max_promote_price'] = 0;
                            $data['start_time'] = 0;
                            $data['end_time'] = 0;
                        } elseif ($list['is_promote'] == '1') {
                            $data['is_qinggou'] = 0;
                            $data['is_promote'] = 1;
                            $min_promote_price = 0;
                            $max_promote_price = 0;
                            foreach ($option_lists as $olist) {
                                if ($min_promote_price == 0 || $min_promote_price > $olist['goods_promoteprice']) {
                                    $min_promote_price = $olist['goods_promoteprice'];
                                }
                                if ($max_promote_price == 0 || $max_promote_price < $olist['goods_promoteprice']) {
                                    $max_promote_price = $olist['goods_promoteprice'];
                                }
                            }
                            if ($min_promote_price == 0) {
                                $min_promote_price = $max_promote_price;
                            }
                            if ($max_promote_price == 0) {
                                $max_promote_price = $min_promote_price;
                            }
                            $data['min_promote_price'] = $min_promote_price;
                            $data['max_promote_price'] = $max_promote_price;
                            $data['start_time'] = 0;
                            $data['end_time'] = 0;
                        } elseif ($list['is_qianggou'] == '1' && $list['qianggou_end_time'] >= time()) {
                            $data['is_qinggou'] = 1;
                            $data['is_promote'] = 0;
                            $min_promote_price = 0;
                            $max_promote_price = 0;
                            foreach ($option_lists as $olist) {
                                if ($min_promote_price == 0 || $min_promote_price > $olist['goods_promoteprice']) {
                                    $min_promote_price = $olist['goods_promoteprice'];
                                }
                                if ($max_promote_price == 0 || $max_promote_price < $olist['goods_promoteprice']) {
                                    $max_promote_price = $olist['goods_promoteprice'];
                                }
                            }
                            if ($min_promote_price == 0) {
                                $min_promote_price = $max_promote_price;
                            }
                            if ($max_promote_price == 0) {
                                $max_promote_price = $min_promote_price;
                            }
                            $data['min_promote_price'] = $min_promote_price;
                            $data['max_promote_price'] = $max_promote_price;
                            $data['start_time'] = $list['qianggou_start_time'];
                            $data['end_time'] = $list['qianggou_end_time'];
                        } else {
                            $data['is_qinggou'] = 0;
                            $data['is_promote'] = 0;
                            $data['min_promote_price'] = 0;
                            $data['max_promote_price'] = 0;
                            $data['start_time'] = 0;
                            $data['end_time'] = 0;
                        }
                    }
                    $data['version'] = $list['version'];
                    $data['table_version'] = $extra->max('table_version') + 1;
                    $where = array();
                    $where['goods_id'] = $list['goods_id'];
                    $check = $extra->field('goods_id')->where($where)->find();
                    if (empty($check)) {
                        $extra->add($data);
                    } else {
                        $extra->where($where)->save($data);
                    }
                    D('StoreDecoration')->clearTplCache($data['store_id']);
                    usleep(20);
                }
            } while ($count > 2000);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            addExceptionLog($msg);
            return getReturn(-1, '同步异常');
        }
        return getReturn(200, '同步完成');
    }

    /**
     * @param array $goodsId
     * @param string $classId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 批量移动商品的商城分类
     * Date: 2017-11-27 12:08:57
     * Update: 2017-11-27 12:08:57
     * Version: 1.0
     */
    public function setMallGoodsClass($goodsId = [], $classId = '')
    {
        if (empty($goodsId) || empty($classId)) return getReturn(-1, L('INVALID_PARAM'));
        $maxVersion = $this->max('version');
        $classId = explode('|', $classId);
        $allData = [];
        $data = [];
        for ($i = 1; $i <= 3; $i++) {
            $data["mall_goods_class_{$i}"] = empty($classId[$i - 1]) ? 0 : $classId[$i - 1];
        }
        foreach ($goodsId as $key => $value) {
            $data['goods_id'] = $value;
            $data['version'] = ++$maxVersion;
            $allData[] = $data;
        }
        return $this->saveAllData([], $allData);
    }

    /**
     * @param int $storeId
     * @param array $goodsId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 彻底删除商品
     * Date: 2017-11-27 14:08:12
     * Update: 2017-11-27 14:08:13
     * Version: 1.0
     */
    public function deleteGoods($storeId = 0, $goodsId = [])
    {
        if (empty($goodsId)) return getReturn(-1, L('INVALID_PARAM'));
        $maxVersion = $this->max('version');
        $allData = [];
        $data = [];
        $data['isdelete'] = 2;
        foreach ($goodsId as $key => $value) {
            $data['goods_id'] = $value;
            $data['version'] = ++$maxVersion;
            $allData[] = $data;
        }
        return $this->saveAllData([], $allData);
    }

    /**
     * 将图片字符串有问题的字段设置为空 防止APP崩溃
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-05 14:16:40
     * Update: 2017-12-05 14:16:40
     * Version: 1.00
     */
    public function setEmptyGoodsImg()
    {
        // goods_figure goods_image [{"url":"null"}] [{"url":null}] [null] [{"url":null,"text":"","sort":-10000}]
        $where = [];
        $where['goods_image'] = '[{"url":null}]';
        $options = [];
        $options['where'] = $where;
        $count = $this->queryCount($options);
        $page = ceil($count / 1000);
        $i = 1;
        $maxVersion = $this->max('version');
        $data = [];
        do {
            $options['take'] = 1000;
            $options['skip'] = ($i - 1) * 1000;
            $options['field'] = 'goods_id';
            $result = $this->queryList($options);
            if ($result['code'] !== 200) return $result;
            $list = $result['data']['list'];
            if (!empty($list)) {
                foreach ($list as $key => $value) {
                    $item = [];
                    $item['goods_id'] = $value['goods_id'];
                    $item['goods_image'] = '';
                    $item['version'] = ++$maxVersion;
                    $data[] = $item;
                }
            }
            $i++;
        } while ($i <= $page);
        return $this->saveAllData([], $data);
    }

    /**
     * 清空 联谊超市 的规格字段
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-06 14:09:22
     * Update: 2017-12-06 14:09:22
     * Version: 1.00
     */
    public function setEmptySpec()
    {
        // store_id = 16806 AND isdelete = 0 AND goods_state = 1 AND spec_open = 0 AND goods_spec LIKE '%"name":"1"%'
        // AND goods_spec LIKE '%"name":"1"%' AND mall_goods_class_1 != 261 AND mall_goods_class_1 != 82 AND mall_goods_class_2 != 130 AND mall_goods_class_2 != 131
        $where = [];
        $where['store_id'] = 16806;
        $where['isdelete'] = 0;
        $where['goods_state'] = 1;
        $where['spec_open'] = 0;
        $where['goods_spec'] = ['like', '%"name":"1"%'];
        $where['mall_goods_class_1'] = ['not in', '261,82'];
        $where['mall_goods_class_2'] = ['not in', '130,131'];
        $options = [];
        $options['where'] = $where;
        $total = $this->queryCount($options);
        $count = ceil($total / 1000);
        $page = 1;
        $data = [];
        $maxVersion = $this->max('version');
        do {
            $options['page'] = $page;
            $options['limit'] = 1000;
            $options['field'] = 'goods_id';
            $result = $this->queryList($options);
            if ($result['code'] !== 200) return $result;
            $list = $result['data']['list'];
            foreach ($list as $key => $value) {
                $item = [];
                $item['goods_id'] = $value['goods_id'];
                $item['goods_spec'] = '';
                $item['version'] = ++$maxVersion;
                $data[] = $item;
            }
            $page++;
        } while ($page <= $count);
        return $this->saveAllData([], $data);
    }

    /**
     * 将商品表的版本号递增
     * 版本号相同超过100个就重新递增
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-06 14:27:34
     * Update: 2017-12-06 14:27:34
     * Version: 1.00
     */
    public function setVersion()
    {
        /*
         * SELECT COUNT(*) num,version FROM xunxin_goods
            GROUP BY version
            HAVING num > 1
            ORDER BY num DESC;
         */
        // 获取所有重复2次以上的版本号
        set_time_limit(300);
        $options = [];
        $options['field'] = 'version';
        $options['group'] = 'version';
        $options['having'] = 'COUNT(*) > 1';
        $result = $this->queryField($options, 'version', true);
        if ($result['code'] !== 200) return $result;
        $version = $result['data'];
        if (empty($version)) return getReturn(200, '没有需要设置的商品');
        $where = [];
        $where['version'] = ['in', $version];
        $options = [];
        $options['where'] = $where;
        $total = $this->queryCount($options);
        $count = ceil($total / 1000);
        $page = 1;
        $maxVersion = $this->max('version');
        $data = [];
        do {
            $options['page'] = $page;
            $options['limit'] = 1000;
            $options['field'] = 'goods_id';
            $result = $this->queryList($options);
            if ($result['code'] !== 200) return $result;
            $list = $result['data']['list'];
            foreach ($list as $key => $value) {
                $item = [];
                $item['goods_id'] = $value['goods_id'];
                $item['version'] = ++$maxVersion;
                $data[] = $item;
            }
            $page++;
        } while ($page <= $count);
        return $this->saveAllData([], $data);
    }

    /**
     * 移除商品满减标志
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-26 16:47:58
     * Update: 2017-12-26 16:47:58
     * Version: 1.00
     */
    public function removeGoodsMjFlag()
    {
        $where = [];
        $where['isdelete'] = 0;
        $where['goods_state'] = 1;
        $where['mj_activity_flag'] = 1;
        $options = [];
        $options['where'] = $where;
        $result = $this->queryTotal($options);
        if ($result['code'] === -1) return $result;
        $total = $result;
        $limit = 1000;
        $count = ceil($total / $limit);
        $page = 1;
        $data = [];
        $result = $this->queryMax([], 'version');
        if ($result['code'] !== 200) return $result;
        $maxVersion = $result['data'];
        do {
            $options['page'] = $page;
            $options['limit'] = $limit;
            $result = $this->queryList($options);
            if ($result['code'] !== 200) return $result;
            $list = $result['data']['list'];
            foreach ($list as $key => $value) {
                $item = [];
                $item['goods_id'] = $value['goods_id'];
                $item['version'] = $maxVersion++;
                $item['mj_activity_flag'] = 0;
                $data[] = $item;
            }
            $page++;
        } while ($page <= $count);
        return $this->saveAllData([], $data);
    }

    /**
     * 设置商品满减标志
     * @param array $condition
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-26 16:49:21
     * Update: 2017-12-26 16:49:21
     * Version: 1.00
     */
    public function setGoodsMjFlagByStoreId($condition = [])
    {
        $where = [];
        $where['isdelete'] = 0;
        $where['goods_state'] = 1;
        $where['mj_activity_flag'] = 0;
        $where = array_merge($where, $condition);
        $options = [];
        $options['where'] = $where;
        $result = $this->queryTotal($options);
        if ($result['code'] === -1) return $result;
        $total = $result;
        $limit = 1000;
        $count = ceil($total / $limit);
        $page = 1;
        $data = [];
        $result = $this->queryMax([], 'version');
        if ($result['code'] !== 200) return $result;
        $maxVersion = $result['data'];
        do {
            $options['page'] = $page;
            $options['limit'] = $limit;
            $result = $this->queryList($options);
            if ($result['code'] !== 200) return $result;
            $list = $result['data']['list'];
            foreach ($list as $key => $value) {
                $item = [];
                $item['goods_id'] = $value['goods_id'];
                $item['version'] = $maxVersion++;
                $item['mj_activity_flag'] = 1;
                $data[] = $item;
            }
            $page++;
        } while ($page <= $count);
        return $this->saveAllData([], $data);
    }

    /**
     * 将不在goods_extra表里的数据提高版本号
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-04-19 19:55:38
     * Update: 2018-04-19 19:55:38
     * Version: 1.00
     */
    public function cleanGoodsNullInExtra()
    {
        $this->alias('a');
        $join = [
            'LEFT JOIN __GOODS_EXTRA__ b ON a.goods_id = b.goods_id',
            '__STORE__  c ON a.store_id = c.store_id'
        ];
        $where = [];
        $where['b.goods_id'] = ['exp', 'IS NULL'];
        $list = $this->field('a.goods_id')->join($join)->where($where)->select();
        if (empty($list)) return getReturn(200, '没有需要清理的');
        $version = $this->max('version');
        $data = [];
        foreach ($list as $goods) {
            $item = [];
            $item['goods_id'] = $goods['goods_id'];
            $item['version'] = ++$version;
            $data[] = $item;
        }
        return $this->saveAllData([], $data);
    }

    public function updateVersionByStoreId($storeId = 0)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $options = [];
        $options['where'] = $where;
        $result = $this->queryTotal($options);
        if ($result['code'] === -1) return $result;
        $total = $result;
        $limit = 1000;
        $count = ceil($total / $limit);
        $page = 1;
        $data = [];
        $result = $this->queryMax([], 'version');
        if ($result['code'] !== 200) return $result;
        $maxVersion = $result['data'];
        do {
            $options['page'] = $page;
            $options['limit'] = $limit;
            $result = $this->queryList($options);
            if ($result['code'] !== 200) return $result;
            $list = $result['data']['list'];
            foreach ($list as $key => $value) {
                $item = [];
                $item['goods_id'] = $value['goods_id'];
                $item['version'] = ++$maxVersion;
                $data[] = $item;
            }
            $page++;
        } while ($page <= $count);
        return ($this->saveAllData([], $data));
    }

    public function insertToExp()
    {
        set_time_limit(0);
        $where = [];
        $where['b.goods_id'] = ['exp', 'IS NULL'];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = 'a.goods_id,a.store_id';
        $options['join'] = [
            'LEFT JOIN __MB_GOODS_EXP__ b ON a.goods_id = b.goods_id'
        ];
        $options['where'] = $where;
        $result = $this->queryTotal($options);
        if ($result['code'] === -1) return $result;
        $total = $result;
        $limit = 1000;
        $count = ceil($total / $limit);
        $page = 1;
        $maxVersion = M('mb_goods_exp')->max('version');
        $model = M('mb_goods_exp');
        do {
            $options['page'] = $page;
            $options['limit'] = $limit;
            $result = $this->queryList($options);
            if ($result['code'] !== 200) return $result;
            $list = $result['data']['list'];
            $data = [];
            foreach ($list as $key => $value) {
                $item = [];
                $item['goods_id'] = $value['goods_id'];
                $item['store_id'] = $value['store_id'];
                $item['version'] = ++$maxVersion;
                $data[] = $item;
            }
            if (!empty($data)) {
                $res = $model->addAll($data);
                if (false === $result) {
                    return getReturn(-1, $model->getError() . $model->getDbError(), $data);
                }
            }
            $page++;
        } while ($page <= $count);
        return $res;
    }
	
	/*获取简单的商品列表*/
	public function getsimpleGoodsList($store_id , $page = 1 , $limit = 10 ,$condition = array()){
		$where['xunxin_goods.store_id'] = $this->getStoreIds($store_id);
		$where['xunxin_goods.isdelete'] = 0 ;
		$where['xunxin_goods.goods_state'] = 1 ;
		if(!empty($condition['class_id'])){
			$where['xunxin_goods.gc_id'] = $this->getGcIds($condition['class_id']);
		}
		if(!empty($condition['keyword'])){
			$where['xunxin_goods.goods_name'] = array('like',"%".$condition['keyword']."%");
		}      
		$start = ($page - 1) * $limit; 
		$lists = M('goods')->join('xunxin_goods_extra ON xunxin_goods_extra.goods_id = xunxin_goods.goods_id')->where($where)->order('xunxin_goods.top DESC ,xunxin_goods.sort DESC')->limit($start , $limit )->field('xunxin_goods.goods_id,xunxin_goods.goods_name,xunxin_goods.goods_price,xunxin_goods.goods_figure,xunxin_goods_extra.min_goods_price,xunxin_goods_extra.max_goods_price,xunxin_goods_extra.min_promote_price,xunxin_goods_extra.max_promote_price,xunxin_goods_extra.is_qinggou,xunxin_goods_extra.is_promote,xunxin_goods_extra.start_time,xunxin_goods_extra.end_time')->select();
		foreach($lists as &$list){
			$list['goods_img'] = json_decode($list['goods_figure'],true)[0]['url']; 
            if(($list['is_promote'] == '1') || ($list['is_qinggou'] == '1' && $list['start_time'] < time() && $list['end_time'] > time())){
                if($list['min_promote_price'] ==  $list['max_promote_price']){
                    $list['goods_price'] = $list['min_promote_price'];
                }else{
                     $list['goods_price'] = $list['min_promote_price'] . '~' .  $list['max_promote_price'];
                }
            }else{
                if($list['min_goods_price'] ==  $list['max_goods_price']){
                    $list['goods_price'] = $list['min_goods_price'];
                }else{
                     $list['goods_price'] = $list['min_goods_price'] . '~' .  $list['max_goods_price'];
                }   
            }
		}
		return $lists;     
	}
}