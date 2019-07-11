<?php

namespace Common\PHPUnit;

class RechargeTest extends BaseTest
{
    /**
     * 充值记录补充推荐人
     * User: hjun
     * Date: 2019-06-21 16:28:22
     * Update: 2019-06-21 16:28:22
     * Version: 1.00
     */
    public function testRecommend()
    {
        $model = D('RechargeCardRecord');
        $field = [
            'record_id', 'store_id', 'member_id'
        ];
        $options = [];
        $options['field'] = $field;
        $list = $model->selectList($options);
        $updateList = [];
        foreach ($list as $value) {
            $recommendInfo = getRecommendInfo($value['member_id'], $value['store_id']);
            if (!empty($recommendInfo['recommend_id'])) {
                $data = [];
                $data['record_id'] = $value['record_id'];
                $data['recommend_id'] = $recommendInfo['recommend_id'];
                $data['recommend_name'] = $recommendInfo['recommend_name'];
                $data['recommend_nickname'] = $recommendInfo['recommend_nickname'];
                $updateList[] = $data;
            }
        }
        if (!empty($updateList)) {
            $sql = buildSaveAllSQL('xunxin_mb_recharge_card_record', $updateList, 'record_id');
            $result = $model->execute($sql);
        }

    }
}