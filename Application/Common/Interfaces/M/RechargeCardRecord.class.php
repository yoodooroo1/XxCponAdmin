<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/12/4
 * Time: 14:18
 */

namespace Common\Interfaces\M;

interface RechargeCardRecord
{
    /**
     * 生成充值记录页的查询条件
     * @param array $request
     * @return mixed
     * User: hjun
     * Date: 2018-12-04 14:25:31
     * Update: 2018-12-04 14:25:31
     * Version: 1.00
     */
    public function autoRecordListQueryWhere($request = []);

    /**
     * 获取充值记录列表的数据
     * @param int $page
     * @param int $limit
     * @param array $queryWhere
     * @return array
     * User: hjun
     * Date: 2018-12-04 14:23:50
     * Update: 2018-12-04 14:23:50
     * Version: 1.00
     */
    public function getRecordListData($page = 1, $limit = 20, $queryWhere = []);

    /**
     * 创建充值订单
     * @param int $memberId
     * @param int $cardId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-05 17:56:01
     * Update: 2018-12-05 17:56:01
     * Version: 1.00
     */
    public function createRechargeRecord($memberId = 0, $cardId = 0);

    /**
     * 获取会员的某条充值记录
     * @param int $recordId
     * @param int $memberId
     * @return array
     * User: hjun
     * Date: 2018-12-05 18:29:20
     * Update: 2018-12-05 18:29:20
     * Version: 1.00
     */
    public function getMemberRecord($recordId = 0, $memberId = 0);

    /**
     * 支付成功通知回调
     * @param string $orderSn
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-05 19:13:16
     * Update: 2018-12-05 19:13:16
     * Version: 1.00
     */
    public function paySuccessNotify($orderSn = '');
}