<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2018/12/4
 * Time: 14:18
 */

namespace Common\Interfaces\M;

use Think\Model;

interface RechargeCard
{
    /**
     * 生成充值卡列表的查询条件
     * @param array $request
     * @return mixed
     * User: hjun
     * Date: 2018-12-04 15:00:40
     * Update: 2018-12-04 15:00:40
     * Version: 1.00
     */
    public function autoCardListQueryWhere($request = []);

    /**
     * 获取充值卡列表页的数据
     * @param int $page
     * @param int $limit
     * @param array $queryWhere
     * @return array
     * User: hjun
     * Date: 2018-12-04 14:23:50
     * Update: 2018-12-04 14:23:50
     * Version: 1.00
     */
    public function getCardListData($page = 1, $limit = 20, $queryWhere = []);

    /**
     * 获取某个充值卡数据
     * @param int $cardId
     * @return array
     * User: hjun
     * Date: 2018-12-04 14:45:06
     * Update: 2018-12-04 14:45:06
     * Version: 1.00
     */
    public function getCard($cardId = 0);

    /**
     * 获取缓存数据 只查询一次
     * @param int $cardId
     * @return array
     * User: hjun
     * Date: 2018-12-05 12:07:07
     * Update: 2018-12-05 12:07:07
     * Version: 1.00
     */
    public function getCardCache($cardId = 0);

    /**
     * 验证充值卡是否存在
     * @param int $cardId
     * @return boolean
     * User: hjun
     * Date: 2018-12-04 14:45:34
     * Update: 2018-12-04 14:45:34
     * Version: 1.00
     */
    public function validateCard($cardId = 0);

    /**
     * 验证金额是否正确
     * @param float $money
     * @return boolean
     * User: hjun
     * Date: 2018-12-04 16:18:47
     * Update: 2018-12-04 16:18:47
     * Version: 1.00
     */
    public function validateMoney($money = 0.00);

    /**
     * 验证营销礼包有效性
     * @param int $marketId
     * @return boolean
     * User: hjun
     * Date: 2018-12-04 21:20:48
     * Update: 2018-12-04 21:20:48
     * Version: 1.00
     */
    public function validateMarket($marketId = 0);

    /**
     * 自动完成金额
     * @param float $money
     * @return double
     * User: hjun
     * Date: 2018-12-04 21:27:57
     * Update: 2018-12-04 21:27:57
     * Version: 1.00
     */
    public function autoMoney($money = 0.00);

    /**
     * 自动完成营销礼包ID
     * @param int $marketId
     * @return string
     * User: hjun
     * Date: 2018-12-04 21:21:05
     * Update: 2018-12-04 21:21:05
     * Version: 1.00
     */
    public function autoMarketId($marketId = 0);

    /**
     * 自动完成营销礼包名称
     * @param int $marketId
     * @return string
     * User: hjun
     * Date: 2018-12-04 21:20:09
     * Update: 2018-12-04 21:20:09
     * Version: 1.00
     */
    public function autoMarketName($marketId = 0);

    /**
     * 自动完成状态
     * @param int $status
     * @return int
     * User: hjun
     * Date: 2018-12-04 21:21:26
     * Update: 2018-12-04 21:21:26
     * Version: 1.00
     */
    public function autoStatus($status = 0);

    /**
     * 根据操作获取字段规则
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2018-12-04 15:33:58
     * Update: 2018-12-04 15:33:58
     * Version: 1.00
     */
    public function getFieldsByAction($action = Model::MODEL_INSERT, $request = []);

    /**
     * 根据操作获取验证规则
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2018-12-04 15:33:58
     * Update: 2018-12-04 15:33:58
     * Version: 1.00
     */
    public function getValidateByAction($action = Model::MODEL_INSERT, $request = []);

    /**
     * 根据操作获取完成规则
     * @param int $action
     * @param array $request
     * @return array
     * User: hjun
     * Date: 2018-12-04 15:33:58
     * Update: 2018-12-04 15:33:58
     * Version: 1.00
     */
    public function getAutoByAction($action = Model::MODEL_INSERT, $request = []);

    /**
     * 根据操作获取类型规则
     * @param int $action
     * @param array $request
     * @return int
     * User: hjun
     * Date: 2018-12-04 15:35:19
     * Update: 2018-12-04 15:35:19
     * Version: 1.00
     */
    public function getTypeByAction($action = Model::MODEL_INSERT, $request = []);

    /**
     * 新增充值卡
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-04 14:42:26
     * Update: 2018-12-04 14:42:26
     * Version: 1.00
     */
    public function addCard($request = []);

    /**
     * 更新充值卡
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-04 14:42:39
     * Update: 2018-12-04 14:42:39
     * Version: 1.00
     */
    public function updateCard($request = []);

    /**
     * 删除充值卡
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-04 14:43:01
     * Update: 2018-12-04 14:43:01
     * Version: 1.00
     */
    public function deleteCard($request = []);

    /**
     * 修改充值卡的状态
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-05 11:43:28
     * Update: 2018-12-05 11:43:28
     * Version: 1.00
     */
    public function changeCardStatus($request = []);

    /**
     * 充值卡操作
     * @param int $action
     * @param array $request
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-04 14:44:39
     * Update: 2018-12-04 14:44:39
     * Version: 1.00
     */
    public function cardAction($action = Model::MODEL_INSERT, $request = []);

    /**
     * 删除礼包后(营销计划)的处理逻辑
     * @param int $marketId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-12-05 12:17:39
     * Update: 2018-12-05 12:17:39
     * Version: 1.00
     */
    public function afterDeleteMarket($marketId = 0);

    /**
     * 获取前端可显示的充值卡列表
     * @return array
     * User: hjun
     * Date: 2018-12-05 17:25:30
     * Update: 2018-12-05 17:25:30
     * Version: 1.00
     */
    public function getShowCardListData();

    /**
     * 修改充值次数
     * @param int $cardId
     * @param int $num
     * @return mixed
     * User: hjun
     * Date: 2018-12-05 20:17:35
     * Update: 2018-12-05 20:17:35
     * Version: 1.00
     */
    public function changeRechargeNum($cardId = 0, $num = 1);
}