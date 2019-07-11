<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2017/4/26
 * Time: 15:35
 */

namespace Common\Model;
class FacePayModel extends BaseModel
{

    protected $tableName = 'mb_facepay';

    /**
     * 获取直付订单列表
     * @param string $storeId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @param array $otherField
     * @param string $otherOrder
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-19 17:47:12
     * Update: 2017-12-19 17:47:12
     * Version: 1.00
     */
    public function getFacePayList($storeId = '', $page = 1, $limit = 0, $condition = [], $otherField = [], $otherOrder = '')
    {
        $where = [];
        $where['a.isdelete'] = 0;
        $where['a.pay_success'] = 1;
        $where['a.store_id'] = ['in', $storeId];
        $where = array_merge($where, $condition);
        $field = [
            'a.id,a.store_id,a.member_name,a.allmoney,a.paymoney,a.pay_name,a.create_time',
            'a.balance,a.third_money,a.platform_balance',
            'a.coupons_exmoney,a.credits_exmoney',
            'a.platform_balance','a.platform_coupons_exmoney','a.platform_credits_exmoney',
            'b.store_name pickup_name',
            'c.member_nickname',
            'd.othername'
        ];
        $order = 'a.id DESC';
        if (!empty($otherField)) $field = $otherField;
        if (!empty($otherOrder)) $order = $otherOrder;
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = implode(',', $field);
        $options['join'] = [
            'LEFT JOIN __MB_PICKUP_LIST__ b ON a.pickid = b.id',
            '__MEMBER__ c ON a.buyer_id = c.member_id',
            '__MB_STOREMEMBER__ d ON a.buyer_id = d.member_id AND a.store_id = d.store_id'
        ];
        $options['where'] = $where;
        $options['page'] = $page;
        $options['limit'] = $limit;
        $options['order'] = $order;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            $list[$key]['allmoney'] = round($value['allmoney'], 2);
            $list[$key]['paymoney'] = round($value['paymoney'], 2);
            $list[$key]['balance'] = round($value['balance'], 2);
            $list[$key]['third_money'] = round($value['third_money'], 2);
            $list[$key]['platform_balance'] = round($value['platform_balance'], 2);
            $list[$key]['all_balance'] = $list[$key]['balance'] + $list[$key]['third_money'] + $list[$key]['platform_balance'];
            if (empty($value['pickup_name'])){
                $storeName = M('store')->where(['store_id'=>$value['store_id']])->getField('store_name');
            }
            $list[$key]['pickup_name'] = empty($value['pickup_name']) ? "$storeName" : "门店【{$value['pickup_name']}】";
            $list[$key]['remark_name'] = empty($value['othername']) ?
                (empty($value['member_nickname']) ? '' : "({$value['member_nickname']})") :
                ("({$value['othername']})");

            // 店内优惠
            $arr = ['coupons_exmoney','credits_exmoney'];
            foreach ($arr as $k=>$val){
                $list[$key]['store_reduced'] += round($value[$val], 2);
            }
            // 平台优惠
            $arr = ['platform_coupons_exmoney','platform_credits_exmoney'];
            foreach ($arr as $k=>$val){
                $list[$key]['platform_reduced'] += round($value[$val], 2);
            }

        }
        $result['data']['list'] = $list;

        // 计算总额
        $total = [];
        $total['allmoney'] = $this->querySum($options, 'allmoney')['data'];
        $total['paymoney'] = $this->querySum($options, 'paymoney')['data'];
        $total['balance'] = $this->querySum($options, 'a.balance')['data'];
        $total['third_money'] = $this->querySum($options, 'a.third_money')['data'];
        $total['platform_balance'] = $this->querySum($options, 'a.platform_balance')['data'];
        $total['coupons_exmoney'] = $this->querySum($options, 'a.coupons_exmoney')['data'];
        $total['credits_exmoney'] = $this->querySum($options, 'a.credits_exmoney')['data'];
        $total['platform_coupons_exmoney'] = $this->querySum($options, 'a.platform_coupons_exmoney')['data'];
        $total['platform_credits_exmoney'] = $this->querySum($options, 'a.platform_credits_exmoney')['data'];
        $arr = ['coupons_exmoney','credits_exmoney'];
        foreach ($arr as $k=>$val){
            $total['store_reduced'] += round($total[$val], 2);
        }
        $arr = ['platform_coupons_exmoney','platform_credits_exmoney'];
        foreach ($arr as $k=>$val){
            $total['platform_reduced'] += round($total[$val], 2);
        }
        $total['all_balance'] = $total['balance'] + $total['third_money'] + $total['platform_balance'];
        $result['data']['sum'] = $total;
        return $result;
    }

    /**
     * 获取直付订单的信息
     * @param int $id
     * @param array $condition
     * @param array $otherField
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-20 10:57:27
     * Update: 2017-12-20 10:57:27
     * Version: 1.00
     */
    public function getFacePayInfo($id = 0, $condition = [], $otherField = [])
    {
        $where = [];
        $where['a.isdelete'] = 0;
        $where['a.pay_success'] = 1;
        $where['a.id'] = $id;
        $where = array_merge($where, $condition);
        // 正式环境没有platform_credits_exmoney
        $field = [
            'a.id,a.pay_id,a.store_id,a.allmoney',
            'a.coupons_exmoney,a.credits_exmoney,a.balance',
            'a.platform_coupons_exmoney,a.platform_balance',
            'a.third_money',
            'a.paymoney',
            'a.member_name,a.pay_name,a.create_time,a.ps',
            'a.pickid'
        ];
        if (!empty($otherField)) $field = $otherField;
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = implode(',', $field);
        $options['where'] = $where;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, L('RECORD_INVALID'));

        // 自提点名称
        /*if ($info['pickid'] > 0) {
            $where = [];
            $where['id'] = $info['pickid'];
            $info['pickup_name'] = D('PickUp')->queryField(['where' => $where], 'store_name')['data'];
        }
        $info['pickup_name'] = empty($info['pickup_name']) ? "总店" : "门店【{$info['pickup_name']}】";*/
        // 店铺名称
        $storeInfo = D('Store')->getStoreInfo($info['store_id'])['data'];
        $info['store_name'] = $storeInfo['store_name'];
        // 金额做数值格式化
        $moneyArr = [
            'allmoney', 'coupons_exmoney', 'credits_exmoney', 'balance',
            'platform_coupons_exmoney', 'platform_credits_exmoney', 'platform_balance',
            'third_money', 'paymoney'
        ];
        foreach ($moneyArr as $key => $value) {
            $info[$value] = round($info[$value], 2);
        }
        // 第三方金额
        if ($info['third_money'] > 0){
            $where = [];
            $where['store_id'] = $info['main_store_id'];
            $info['money_name'] = M('mb_thirdpart_money')->where($where)->getField('moneyname');
        }
        $info['money_name'] = empty($info['money_name']) ? "第三方余额" : $info['money_name'];
        $result['data'] = $info;
        return $result;
    }
}