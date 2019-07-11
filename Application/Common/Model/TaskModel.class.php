<?php

namespace Common\Model;

/**
 * Class TaskModel
 * User: hj
 * Desc: 任务模型
 * Date: 2017-11-15 01:50:20
 * Update: 2017-11-15 01:50:20
 * Version: 1.0
 * @package Common\Model
 */
class TaskModel extends BaseModel
{
    protected $tableName = 'mb_task';

    // 正在执行的普通任务数量0
    protected $doingNum1 = 0;
    // 正在执行的常驻任务数量
    protected $doingNum2 = 0;

    /**
     * @param int $storeId
     * @param string $action
     * @param array $param
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 创建定时任务 需要检查时间间隔
     * Date: 2017-11-17 11:04:01
     * Update: 2017-11-17 11:04:02 2017-11-25 13:41:19
     * Version: 1.1
     */
    public function addTask($storeId = 0, $action = '', $param = [])
    {
        // 检查该店铺上一个该任务的信息
        $where = [];
        $where['store_id'] = $storeId;
        $where['task_action'] = $action;
        $info = $this->field('task_id,create_time,task_state')->where($where)->order('create_time DESC')->find();
        if (!empty($info)) {
            // 检查上一次任务是否执行完成
            if ($info['task_state'] == 1) return getReturn(-1, '已有相同任务在队列中,请耐心等待后台执行');
            if ($info['task_state'] == 2) return getReturn(-1, '任务正在执行中,请耐心等待结果');
            // 检查上一次执行该任务到现在的时间 因为定时任务1分钟执行一次,执行时间算有最大60s的延迟
            $limitTime = C("TASK_INTERVAL.{$action}") ? C("TASK_INTERVAL.{$action}") : 1800;
            if (NOW_TIME - $info['create_time'] - 60 < $limitTime) {
                $remainTime = $limitTime - (NOW_TIME - $info['create_time'] - 60);
                return getReturn(-1, "近期您已经执行过该任务,请{$remainTime}秒后再试");
            }
        }
        // 新增任务
        $data = [];
        $data['store_id'] = $storeId;
        $data['task_action'] = $action;
        $data['task_param'] = empty($param) ? "" : json_encode($param, JSON_UNESCAPED_UNICODE);
        $data['create_time'] = NOW_TIME;
        $data['day'] = date('d');
        $result = $this->addData([], $data);
        return $result;
    }

    /**
     * @param array $info
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 重置已经执行完成的常驻任务
     * Date: 2017-11-24 11:22:06
     * Update: 2017-11-24 11:22:07 2017-11-25 13:42:52
     * Version: 1.1
     */
    public function resetTask($info = [])
    {
        if (empty($info)) return getReturn(-1, '等待任务执行...');
        $lastTime = $info['create_time'];
        $limitTime = C("TASK_INTERVAL.{$info['task_action']}") ? C("TASK_INTERVAL.{$info['task_action']}") : 1800;
        if (NOW_TIME - $lastTime < $limitTime) return getReturn(-1, "执行间隔异常:" . (NOW_TIME - $lastTime));
        $data = [];
        $data['create_time'] = NOW_TIME;
        $data['task_state'] = 1;
        $data['day'] = date('d');
        $where = [];
        $where['task_id'] = $info['task_id'];
        $where['task_type'] = 2;
        $options = [];
        $options['where'] = $where;
        return $this->saveData($options, $data);
    }

    /**
     * @param array $taskInfo
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 执行一个任务
     * Date: 2017-11-26 18:01:49
     * Update: 2017-11-26 18:01:50
     * Version: 1.0
     */
    public function execOneTask($taskInfo = [])
    {
        if (empty($taskInfo['task_id'])) return getReturn(-1, '未知任务');
        // 检查上一个该任务的执行时间
        // 普通任务上一次执行的时间是创建时间加上最大60S延迟
        // 常驻任务上一次执行的时间上次时间create_time 因为执行完后会更新一遍
        if ($taskInfo['task_type'] == 1) {
            $where = [];
            $where['store_id'] = $taskInfo['store_id'];
            $where['task_action'] = $taskInfo['task_action'];
            $where['task_state'] = 3;
            $lastTime = $this->where($where)->order('create_time DESC')->getField('create_time');
            if (false === $lastTime) return $this->getFalseReturn();
            $lastTime = $lastTime + 60;
        } else {
            $lastTime = $taskInfo['create_time'];
        }
        // 执行任务 符合间隔才执行
        $limitTime = C("TASK_INTERVAL.{$taskInfo['task_action']}") ? C("TASK_INTERVAL.{$taskInfo['task_action']}") : 1800;
        if (NOW_TIME - $lastTime < $limitTime) {
            $result = getReturn(-1, "执行间隔异常:" . (NOW_TIME - $lastTime));
        } else {
            switch ($taskInfo['task_action']) {
                case 'clear_coupons_center_today_num':
                    // 清理每天的领取记录
                    $result = D('CouponsCenter')->clearTodayTakeNum();
                    break;
                case 'message_tip':
                    // 发送微信消息提醒
                    $result = D('MessageTip')->sendTemplateMsg();
                    break;
                case 'clean_null_goods_in_extra':
                    $result = D('Goods')->cleanGoodsNullInExtra();
                    break;
                case "refresh_goods":
                    // 同步商品
                    $result = D('Goods')->refreshGoods();
                    break;
                case 'add_group_order':
                    // 团购下单
                    $result = D('GroupBuyOrder')->addGroupOrderToOrder();
                    break;
                case 'rollback_group_order_goods':
                    // 未成团订单回滚库存销量
                    $result = D('GroupBuyOrder')->rollbackOrderGoods();
                    break;
                case "collect_img":
                    // 执行采集图片
                    $result = D('Goods')->collectGoodsImg($taskInfo['store_id']);
                    break;
                case 'refresh_goods_qianggou':
                    // 刷新商品的抢购时间
                    $result = D('Goods')->refreshGoodsQG();
                    break;
                default:
                    $result = getReturn(-1, '未知任务');
                    break;
            }
        }
        // 执行完成后,更新状态 执行成功就成功,失败则设置为未执行 常驻任务都重置为未执行
        $where = [];
        $where['task_id'] = $taskInfo['task_id'];
        $data = [];
        $data['task_state'] = $result['code'] === 200 && $taskInfo['task_type'] == 1 ? 3 : 1;
        if ($taskInfo['task_type'] == 2) {
            if ($result['code'] === 200) {
                $data['create_time'] = NOW_TIME;
                $data['day'] = date('d');
            }
        }
        $options = [];
        $options['where'] = $where;
        $this->saveData($options, $data);
        return $result;
    }

    /**
     * @param int $limit 获取的任务数量
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取没有执行的任务 一次获取10个任务 常驻任务全部执行
     * Date: 2017-11-15 01:51:43
     * Update:  2017-11-24 09:30:06
     * Version: 1.1
     */
    public function getNoExecTask($limit = 10)
    {
        $where = [];
        $where['task_state'] = 1;
        $field = "task_id,task_action,task_param,task_state,store_id,task_type,create_time";
        $options = [];
        $options['where'] = $where;
        $options['take'] = $this->doingNum2 + ($limit - $this->doingNum1);
        $options['field'] = $field;
        $options['order'] = 'task_type DESC,create_time ASC,task_id ASC';
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        // 将参数体解析出来
        foreach ($list as $key => $value) {
            $list[$key]['task_param'] = json_decode($value['task_param'], 1);
        }
        return getReturn(200, '', $list);
    }

    /**
     * @return array
     * Author: hj
     * Desc: 检查当前是否可以执行任务
     * Date: 2017-11-24 09:56:07
     * Update: 2017-11-24 09:56:08
     * Version: 1.0
     */
    public function checkTaskExec()
    {
        $where = [];
        $where['task_state'] = 2;
        $where['task_type'] = 1;
        $options = [];
        $options['where'] = $where;
        $this->doingNum1 = $this->queryCount($options);
        if ($this->doingNum1 >= 10) return getReturn(-1, '等待任务执行...');
        $where = [];
        $where['task_state'] = 1;
        $where['task_type'] = 2;
        $options = [];
        $options['where'] = $where;
        $this->doingNum2 = $this->queryCount($options);
        return getReturn(200, '');
    }

    /**
     * @param int $limit
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 执行任务 一次执行10条
     * Date: 2017-11-15 01:59:44
     * Update: 2017-11-15 01:59:45
     * Version: 1.0
     */
    public function execTask($limit = 10)
    {
        // 前置操作
        $this->_beforeExecTask();

        // 判断是否可执行任务
        $result = $this->checkTaskExec();
        if ($result['code'] !== 200) return getReturn(200, $result['msg']);

        // 1. 获取未执行的任务
        $result = $this->getNoExecTask($limit);
        if ($result['code'] !== 200) return getReturn(200);
        $list = $result['data'];
        if (empty($list)) return getReturn(200, '没有可执行的任务');

        // 2. 每个任务都设置为执行中
        $taskId = [];
        foreach ($list as $value) {
            $taskId[] = $value['task_id'];
        }
        $taskId = implode(',', $taskId);
        $where = [];
        $where['task_id'] = ['in', $taskId];
        $data = [];
        $data['task_state'] = 2;
        $options = [];
        $options['where'] = $where;
        $result = $this->saveData($options, $data);
        if ($result['code'] !== 200) {
            return $result;
        }

        // 3.执行任务
        $msg = [];
        $data = [];
        foreach ($list as $key => $value) {
            $result = $this->execOneTask($value);
            $msg[] = $result['msg'];
            $data[] = $result['data'];
        }
        return getReturn(200, $msg, $data);
    }

    /**
     * Author: hj
     * Desc: 执行任务前
     * Date: 2017-11-24 10:15:51
     * Update: 2017-11-24 10:15:52
     * Version: 1.0
     */
    protected function _beforeExecTask()
    {
        // 删除不是当天的执行过的任务
        $where = [];
        $today = date('d');
        $where['task_state'] = ['neq', 1];
        $where['day'] = ['neq', $today];
        $where['task_type'] = 1;
        $options = [];
        $options['where'] = $where;
        $this->delData($options);
    }

    /**
     * Author: hj
     * Desc: 执行任务后
     * Date: 2017-11-24 10:15:22
     * Update: 2017-11-24 10:15:23
     * Version: 1.0
     */
    protected function _afterExecTask()
    {

    }


    /**
     * @param int $storeId
     * @param string $action
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取任务的执行状态
     * Date: 2017-11-22 15:11:55
     * Update: 2017-11-22 15:11:56
     * Version: 1.0
     */
    public function getTaskStatus($storeId = 0, $action = '')
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['task_action'] = $action;
        $options = [];
        $options['where'] = $where;
        $options['order'] = 'create_time DESC';
        $result = $this->queryField($options, 'task_state');
        if ($result['code'] !== 200) return $result;
        $taskState = (int)$result['data'];
        return getReturn(200, '', $taskState);
    }

    /**
     * @param int $storeId
     * @param string $action
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 取消任务
     * Date: 2017-11-22 15:54:17
     * Update: 2017-11-22 15:54:18
     * Version: 1.0
     */
    public function cancelTask($storeId = 0, $action = '')
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where['task_action'] = $action;
        $where['task_state'] = 1;
        $options = [];
        $options['where'] = $where;
        $options['order'] = 'create_time DESC';
        $result = $this->delData($options);
        return $result;
    }
}