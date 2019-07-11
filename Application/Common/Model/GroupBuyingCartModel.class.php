<?php

namespace Common\Model;
class GroupBuyingCartModel extends BaseModel
{
    protected $tableName = 'mb_group_buying_cart';

    /**
     * 参加团购
     * 加入购物车 检查库存
     * @param int $groupId
     * @param int $storeId
     * @param int $memberId
     * @param array $selectData
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-08 01:17:44
     * Update: 2018-02-08 01:17:44
     * Version: 1.00
     */
    public function joinGroup($groupId = 0, $storeId = 0, $memberId = 0, $selectData = [])
    {
        $result = D('GroupBuying')->getGroupInfo($storeId, $groupId);
        if ($result['code'] !== 200) return getReturn(-1, '团购结束或不存在');
        $groupInfo = $result['data'];
        $goodsId = $groupInfo['goods_id'];
        $goodsSpec = $groupInfo['spec_attr'];
        logWrite("商品{$goodsId}规格:" . json_encode($goodsSpec));
        $buyNum = $selectData['goods_num'];
        // 限购检查
        $result = D('GroupBuyingLimit')->validateBuyNum($groupInfo, $memberId, $buyNum);
        if (!isSuccess($result)) {
            return $result;
        }
        $selectSpec = trim($selectData['selected_specIdString'], '_');
        $selectSpec = empty($selectSpec) ? '0' : $selectSpec;
        $selectSpecAttr = $goodsSpec["spec_id_{$selectSpec}"];
        if (empty($selectSpecAttr)) return getReturn(-1, '商品规格已变动,请重新选择', $selectData);
        if ($buyNum > $selectSpecAttr['spec_stock']) return getReturn(-1, '商品库存不足');
        $cartData = [];
        $cartData['goods_id'] = $goodsId;
        $cartData['group_id'] = $groupId;
        $cartData['buy_num'] = $buyNum;
        $cartData['goods_price'] = round($selectSpecAttr['spec_group_price'], 2);
        $cartData['total_price'] = $buyNum * $cartData['goods_price'];
        $cartData['spec_id'] = $selectSpec;
        $cartData['goods_name'] = $groupInfo['goods_name'];
        $cartData['goods_image'] = empty($selectSpecAttr['spec_goods_img']) ? $groupInfo['goods_image'] : $selectSpecAttr['spec_goods_img'];
        // 组装规格信息
        $cartData['spec_name'] = "";
        foreach ($groupInfo['goods_spec'] as $key => $value) {
            $cartData['spec_name'] .= "{$key}:";
            foreach ($value as $k => $val) {
                foreach ($selectData['selected_specIdArr'] as $kk => $vv) {
                    if ($vv == $val['spec_id']) {
                        $cartData['spec_name'] .= "{$val['spec_name']},";
                    }
                }
            }

        }
        $data = [];
        $data['cart_data'] = json_encode($cartData, JSON_UNESCAPED_UNICODE);
        $where = [];
        $where['member_id'] = $memberId;
        $where['store_id'] = $storeId;
        $info = $this->where($where)->find();
        if (!empty($info)) {
            $options = [];
            $options['where'] = ['cart_id' => $info['cart_id']];
            $result = $this->saveData($options, $data);
        } else {
            $data['member_id'] = $memberId;
            $data['store_id'] = $storeId;
            $result = $this->addData([], $data);
        }
        return $result;
    }
}