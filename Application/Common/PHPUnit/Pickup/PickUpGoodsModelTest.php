<?php

namespace Common\PHPUnit;

class PickUpGoodsModelTest extends BaseTest
{
    public function testCommon()
    {
        $bool = null > -1;
    }

    public function testGetPickupGoods()
    {
        $req = [
            'min_stock' => '0',
            'max_stock' => '1',
            'min_sale' => '1',
            'max_sale' => '0',
        ];
        $model = D('DepotGoods');
        $where = $model->getSearchWhere($req)['data'];
        $data = $model->getPickupGoodsData(12, 1, 0, $where);
        $sql = $model->_sql();
    }

    public function testRefreshGoodsVersion()
    {
        $model = M('goods');
        $field = [
            'version', 'COUNT(1) num'
        ];
        $field = implode(',', $field);
        $options = [];
        $options['field'] = $field;
        $options['group'] = 'version';
        $options['having'] = 'num > 1';
        $list = $model->selectList($options);
        foreach ($list as $version) {
            $where = [];
            $where['version'] = $version['version'];
            $updates = $model->field('goods_id')->where($where)->select();
            foreach ($updates as $goods) {
                $where = [];
                $where['goods_id'] = $goods['goods_id'];
                $data = [];
                $data['version'] = $model->max('version') + 1;
                $model->where($where)->save($data);
            }

        }
    }

    public function testRefreshGoodsExtraGoodsVersion()
    {
        $model = D('GoodsExtra');
        $where = [];
        $where['version'] = 0;
        $list = $model->field('goods_id')->where($where)->select();
        $goodsModel = M('goods');
        $version = $goodsModel->max('version');
        foreach ($list as $goods) {
            $where = [];
            $where['goods_id'] = $goods['goods_id'];
            $data = [];
            $data['version'] = ++$version;
            $goodsModel->where($where)->save($data);
        }
    }

    public function testPick()
    {
        $model = D('DepotGoods');
        $field = [
            'goods_id', 'spec_id'
        ];
        $field = implode(',', $field);
        $order = 'goods_id,spec_id';
        $options = [];
        $options['field'] = $field;
        $options['order'] = $order;
//        $options['group'] = 'goods_id';
        $list = $model->selectList($options);
    }

    /**
     * 处理门店商品关联表
     * User: hjun
     * Date: 2018-10-30 18:13:37
     * Update: 2018-10-30 18:13:37
     * Version: 1.00
     */
    public function testSetPickupGoodsLink()
    {
        $pgModel = D('DepotGoods');
        $geModel = D('GoodsExtra');
        // 查出列表 统计商品ID
        $where = [];
        $where['is_delete'] = 0;
        $field = [
            'a.id', 'a.pickup_id', 'a.goods_id',
            'b.store_id'
        ];
        $field = implode(',', $field);
        $join = [
            '__MB_PICKUP_LIST__ b ON a.pickup_id = b.id'
        ];
        $goodsList = $pgModel->alias('a')->field($field)->where($where)->join($join)->select();
        $goodsIds = [];
        foreach ($goodsList as $goods) {
            $goodsIds[$goods['goods_id']] = $goods['goods_id'];
        }
        // 查出规格
        $where = [];
        $where['goods_id'] = getInSearchWhereByArr($goodsIds);
        $geList = $geModel->where($where)->getField('goods_id,spec_attr');


        // 处理数据
        $pgModel->startTrans();
        $pgGoods = [];
        foreach ($goodsList as $goods) {
            if ($goods['goods_id'] == '81411') {
                $t = 1;
            }
            $specs = jsonDecodeToArr($geList[$goods['goods_id']]);
            $i = 0;
            $storeId = $goods['store_id'];
            $pickupId = $goods['pickup_id'];
            $goodsId = $goods['goods_id'];
            foreach ($specs as $spec) {
                $specId = $spec['primary_id'];
                $specStock = $spec['spec_stock'] == '充足' ? -1 : $spec['spec_stock'];
                if ($i === 0) {
                    // 修改第一个规格
                    $where = [];
                    $where['id'] = $goods['id'];
                    $data = [];
                    $data['store_id'] = $storeId;
                    $data['spec_id'] = $specId;
                    $data['storage'] = $specStock;
                    $result = $pgModel->where($where)->save($data);
                    if (false === $result) {
                        $pgModel->rollback();
                        $this->assertEquals(1, 2);
                    }
                } else {
                    $pgGoods[] = [
                        'store_id' => $storeId,
                        'goods_id' => $goodsId,
                        'pickup_id' => $pickupId,
                        'spec_id' => $specId,
                        'storage' => $specStock,
                    ];
                }
                $i++;
            }
        }
        $result = $pgModel->addAll($pgGoods);
        if (false === $result) {
            $pgModel->rollback();
            $this->assertEquals(1, 2);
        }
        $pgModel->commit();
        $this->assertEquals(1, 1);
    }

    /**
     * 将门店商品关联表的store_id正确设置为门店的store_id
     * User: hjun
     * Date: 2018-10-30 18:11:52
     * Update: 2018-10-30 18:11:52
     * Version: 1.00
     */
    public function testSetStoreIdToPickupGoodsLink()
    {
        $pgModel = D('DepotGoods');
        $options = [];
        $options['field'] = 'pickup_id';
        $options['group'] = 'pickup_id';
        $list = $pgModel->selectList($options);
        $ids = [];
        foreach ($list as $goods) {
            $ids[] = $goods['pickup_id'];
        }
        $where = [];
        $where['id'] = getInSearchWhereByArr($ids);
        $model = D('PickUp');
        $options = [];
        $options['field'] = 'id,store_id';
        $options['where'] = $where;
        $list = $model->selectList($options);
        $results = [];
        foreach ($list as $pickup) {
            $where = [];
            $where['pickup_id'] = $pickup['id'];
            $data = [];
            $data['store_id'] = $pickup['store_id'];
            $results[] = $pgModel->where($where)->save($data);
        }
    }

    public function testGetPickupGoodsSelectData()
    {
        $model = D('GoodsExtra');
        $data = $model->setStoreId(564)->getPickupGoodsSelectData(12);
    }

    public function testRegex()
    {
        $model = D('DepotGoods');
        $result = $model->regex('12', 'integer');
    }

    public function testQuery()
    {
        $model1 = D('DepotSendDetail');
        $model2 = D('DepotOrderDetail');
        $goods = $model2->getGoods("174261");
        $goods = $model2->getGoods("174261");
    }

    public function testDepotToPickup()
    {
        $depotModel = M('mb_goods_depot');
        $pickModel = D('PickUp');
        $depots = $depotModel->select();
        $data = [];
        foreach ($depots as $depot) {
            $item = [];
            $item['store_id'] = $depot['store_id'];
            $item['store_name'] = $depot['depot_name'];
            $item['link_name'] = $depot['link_name'];
            $item['link_tel'] = $depot['link_tel'];
            $item['address'] = $depot['address'];
            $item['remark'] = $depot['remark'];
            $item['addtime'] = $depot['create_time'];
            $item['isdelete'] = $depot['is_delete'];
            $item['type'] = $pickModel::DEPOT;
            $data[] = $item;
        }
        $pickModel->addAll($data);
    }

    public function testBreak()
    {
        $pickups = [
            [
                'id' => 1,
                'rule' => [1, 2, 3]
            ],
            [
                'id' => 2,
                'rule' => [4, 5, 6]
            ],
        ];
        $goods = [1, 3, 5];
        $active = [];
        foreach ($pickups as $pickup) {
            foreach ($goods as $value) {
                foreach ($pickup['rule'] as $limit) {
                    if ($value === $limit) {
                        $active[] = $pickup;
                        break 2;
                    }
                }
            }
        }
    }
}