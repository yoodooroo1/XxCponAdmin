<?php

namespace Common\Model;

class StoreGroupPriceDataModel extends BaseModel
{
    protected $tableName = 'mb_store_group_price_data';

    protected $_validate = [
        ['price', 'require', '请输入价格', 0, 'regex', 3]
    ];

    /**
     * 获取某个价格表某个商品的价格
     * @param int $groupPriceId
     * @param int $goodsId
     * @param string $spec
     * @param array $condition
     * @return mixed
     * User: hjun
     * Date: 2018-01-11 17:55:28
     * Update: 2018-01-11 17:55:28
     * Version: 1.00
     */
    public function getGroupGoodsSpecPrice($groupPriceId = 0, $goodsId = 0, $spec = '', $condition = [])
    {
        $where = [];
        $where['store_group_price_id'] = $groupPriceId;
        $where['goods_id'] = $goodsId;
        $where['spec_id'] = "{$spec}";
        $where = array_merge($where, $condition);
        $options = [];
        $options['where'] = $where;
        $result = $this->queryField($options, 'price');
        if ($result['code'] !== 200) {
            return 0;
        }
        return empty($result['data']) ? 0 : $result['data'];
    }

    /**
     * 新增分组商品规格价格
     * @param int $priceId
     * @param int $storeId
     * @param int $channelId
     * @param int $goodsId
     * @param string $spec
     * @param int $price
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-12 17:01:32
     * Update: 2018-01-12 17:01:32
     * Version: 1.00
     */
    public function addGroupGoodsSpecPrice($priceId = 0, $storeId = 0, $channelId = 0, $goodsId = 0, $spec = '', $price = 0)
    {
        if ($priceId <= 0 || $storeId <= 0 || $goodsId <= 0 || $price < 0) {
            return getReturn(-1, L('INVALID_PARAM'));
        }
        $data = [];
        $data['store_group_price_id'] = $priceId;
        $data['store_id'] = $storeId;
        $data['channel_id'] = $channelId;
        $data['goods_id'] = $goodsId;
        $data['spec_id'] = $spec;
        $data['price'] = (double)$price;
        $maxVersion = $this->queryMax([], 'version')['data'];
        $data['version'] = ++$maxVersion;
        $options = [];
        $options['field'] = 'store_group_price_id,goods_id,spec_id,store_id,channel_id,price,version';
        return $this->addData($options, $data);
    }

    /**
     * 修改分组商品规格价格
     * @param int $priceId
     * @param int $storeId
     * @param int $channelId
     * @param int $goodsId
     * @param string $spec
     * @param int $price
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-12 17:30:51
     * Update: 2018-01-12 17:30:51
     * Version: 1.00
     */
    public function saveGroupGoodsSpecPrice($priceId = 0, $storeId = 0, $channelId = 0, $goodsId = 0, $spec = '', $price = 0)
    {
        if ($priceId <= 0 || $storeId <= 0 || $goodsId <= 0 || $price < 0) {
            return getReturn(-1, L('INVALID_PARAM'));
        }
        $where = [];
        $where['goods_id'] = $goodsId;
        $where['store_group_price_id'] = $priceId;
        $where['spec_id'] = $spec;
        $where['store_id'] = $storeId;
        $info = $this->where($where)->find();
        if (empty($info)) {
            return $this->addGroupGoodsSpecPrice($priceId, $storeId, $channelId, $goodsId, $spec, $price);
        }
        $data = [];
        $data['price'] = (double)$price;
        $maxVersion = $this->queryMax([], 'version')['data'];
        $data['version'] = ++$maxVersion;
        $options = [];
        $options['where'] = $where;
        $options['field'] = 'price,version';
        return $this->saveData($options, $data);
    }

    /**
     * 批量修改价格
     * @param int $priceId
     * @param int $storeId
     * @param int $channelId
     * @param array $data
     * @param int $type 0-打折 1-抹去角和分 2-抹去分 3-自定义价格
     * @param int $discount
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-19 09:45:32
     * Update: 2018-01-19 09:45:32
     * Version: 1.00
     */
    public function batchSaveGroupGoodsSpecPrice($priceId = 0, $storeId = 0, $channelId = 0, $data = [], $type = 0, $discount = 0)
    {
        $saveData = [];
        $options = [];
        $maxVersion = $maxVersion = $this->queryMax([], 'version')['data'];
        $i = 1;
        $length = count($data);
        $keyCache = "import_price_list:{$priceId}";
        foreach ($data as $key => $value) {
            // 只检查第一个和最后一个
            if ($i == 1 || $i == $length) {
                $where = [];
                $where['goods_id'] = $value['goods_id'];
                $where['store_id'] = $storeId;
                $goods = M('goods')->field('goods_id')->where($where)->find();
                if (empty($goods)) return getReturn(405, L('_SELECT_NOT_EXIST_'));
            }
            foreach ($value['spec_attr'] as $k => $val) {
                $price = (double)$val['spec_price'];
                if ($price <= 0 && $type != 3) {
                    return getReturn(-1, "商品ID:{$value['goods_id']} {$value['goods_name']} 的代理价不正确");
                }
                switch ((int)$type) {
                    case 1:
                        if ($price >= 1) {
                            $price = floor($price);
                        }
                        break;
                    case 2:
                        if ($price >= 0.1) {
                            $price = floor($price * 10) / 10;
                        }
                        break;
                    case 3:
                        $price = (double)$val['agent_price'];
                        break;
                    default:
                        if ($discount <= 0 || $discount >= 10) {
                            return getReturn(-1, '商品ID:' . $value['goods_id'] . ',折扣请填写0.1-10内的数字');
                        }
                        $price = round($price * ($discount / 10), 2);
                        break;
                }
                $where = [];
                $where['store_group_price_id'] = $priceId;
                $where['goods_id'] = $value['goods_id'];
                $where['spec_id'] = $val['spec_id'];
                $where['store_id'] = $storeId;
                $option = [];
                $option['where'] = $where;
                $info = $this->queryRow($option)['data'];
                if (empty($info)) {
                    // 添加前要检查
                    $where = [];
                    $where['goods_id'] = $value['goods_id'];
                    $where['store_id'] = $storeId;
                    $goods = M('goods')->field('goods_id')->where($where)->find();
                    if (empty($goods)) return getReturn(405, L('_SELECT_NOT_EXIST_'));
                    $this->addGroupGoodsSpecPrice($priceId, $storeId, $channelId, $value['goods_id'], $val['spec_id'], $price);
                }
                $item = [];
                $item['price'] = $price;
                $item['version'] = ++$maxVersion;
                $saveData[] = $item;
                $options[] = $option;
            }
            $process = [];
            $process['info'] = "一共导入{$length}个商品,已读取{$i}个";
            S($keyCache, $process);
            $i++;
        }
        $process = [];
        $process['info'] = "一共导入{$length}个商品,已完成读取,正在存入数据库...";
        S($keyCache, $process);
        return $this->saveAllData($options, $saveData);
    }

    /**
     * 当商品的规格发生删除时,需要修改下规格ID
     * @param int $goodsId 商品ID
     * @param array $specArr 规格数据
     *
     * [
     *   '旧ID1' => '新ID1',
     *   '旧ID2' => '新ID2',
     *   '旧ID3' => '新ID3',
     *   '旧ID4' => 'del',
     *   '旧ID5' => 'del',
     * ]
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-04-16 14:16:14
     * Update: 2018-04-16 14:16:14
     * Version: 1.00
     */
    public function syncSpecData($goodsId = 0, $specArr = [])
    {
        logWrite("同步规格数据:" . json_encode($specArr, 256));
        if (empty($specArr)) return getReturn(200, 'success');
        $this->startTrans();
        $where = [];
        $where['goods_id'] = $goodsId;
        $version = $this->max('version');
        // 先删除规格
        foreach ($specArr as $oldSpecId => $newSpecId) {
            if ($newSpecId === 'del') {
                $where['spec_id'] = $oldSpecId;
                $specPriceData = $this->field('id')->where($where)->select();
                if (!empty($specPriceData)) {
                    foreach ($specPriceData as $spec){
                        $data = [];
                        $data['id'] = $spec['id'];
                        $data['spec_id'] = "delete_{$oldSpecId}";
                        $data['version'] = ++$version;
                        $result = $this->save($data);
                        if (false === $result) {
                            $this->rollback();
                            return getReturn(406);
                        }
                    }
                }
            }
        }
        foreach ($specArr as $oldSpecId => $newSpecId) {
            if ($oldSpecId !== $newSpecId && $newSpecId !== 'del') {
                // 将旧的规格置为新规格
                $where['spec_id'] = $oldSpecId;
                $specPriceData = $this->field('id')->where($where)->select();
                if (!empty($specPriceData)) {
                    foreach ($specPriceData as $spec){
                        $data = [];
                        $data['id'] = $spec['id'];
                        $data['spec_id'] = $newSpecId;
                        $data['version'] = ++$version;
                        $result = $this->save($data);
                        if (false === $result) {
                            $this->rollback();
                            return getReturn(406);
                        }
                    }
                }
            }
        }
        $this->commit();
        return getReturn(200, 'success');
    }
}