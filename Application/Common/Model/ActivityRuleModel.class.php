<?php

namespace Common\Model;
/**
 * Class ActivityRuleModel
 * @package Common\Model
 * Author: hj
 * Desc: 活动商品规则模型
 * Date: 2017-11-28 17:46:45
 * Update: 2017-11-28 17:46:46
 * Version: 1.0
 */
class ActivityRuleModel extends BaseModel
{
    protected $tableName = 'mb_active_rule';

    /**
     * @param int $channelId
     * @param int $type 1 精划算 2限时购 3找好店 4精品购 5每日购 6今日特价 7热销商品
     * @param int $limit
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 新增规则
     *  如果存在了就不新增
     *      1 - 1
     *      2 - 2
     *      3 - 0
     *      4 - 0
     *      5 - 0
     *      6 - 1
     *      7 - 4
     * Date: 2017-11-28 17:47:50
     * Update: 2017-11-28 17:47:51
     * Version: 1.0
     */
    public function addRule($channelId = 0, $type = 1, $limit = -1)
    {
        $param = [1, 2, 3, 4, 5, 6, 7];
        if (!in_array($type, $param)) return getReturn(-1, L('INVALID_PARAM'));
        // 如果指定了规则 就用指定的规则 否则使用默认的规则
        if ($limit >= 0) {
            $param = [0, 1, 2, 4];
            if (!in_array($limit, $param)) return getReturn(-1, L('INVALID_PARAM'));
        }
        $rule = [1 => 1, 2 => 2, 3 => 0, 4 => 0, 5 => 0, 6 => 1, 7 => 4];
        $text = [L('CHOICE_GOODS_2'), L('QG_KILLS_TIME_3'),
            L('FIND_GOOD_STORE'), L('CHOICE_GOODS_4'),
            L('TODAY_DISCOUNT_GOODS_2'), L('TODAY_DISCOUNT_GOODS_1'), L('HOT_GOODS_3')];
        $data = [];
        $data['channel_id'] = $channelId;
        $data['type'] = $type;
        $data['rule_text'] = $text[(int)$type - 1];
        $data['goods_limited'] = $limit >= 0 ? $limit : $rule[(int)$type];
        $data['create_time'] = NOW_TIME;
        return $this->addData([], $data);
    }

    /**
     * @param int $channelId
     * @param int $type
     * @return bool
     * Author: hj
     * Desc: 检查是否已经有规则了
     * Date: 2017-11-28 18:05:42
     * Update: 2017-11-28 18:05:43
     * Version: 1.0
     */
    public function checkHasRule($channelId = 0, $type = 1)
    {
        $where = [];
        $where['channel_id'] = $channelId;
        $where['type'] = $type;
        $options = [];
        $options['where'] = $where;
        $options['field'] = 'rule_id';
        $info = $this->queryRow($options)['data'];
        return !empty($info);
    }

    /**
     * @param int $channelId
     * @param int $type
     * @return int
     *  0 - 所有
     *  1 - 只降
     *  2 - 只抢
     *  4 - 只热
     * Author: hj
     * Desc: 获取当前渠道下 该类型活动的限制条件
     * Date: 2017-11-28 20:55:48
     * Update: 2017-11-28 20:55:48
     * Version: 1.0
     */
    public function getTypeRule($channelId = 0, $type = 1)
    {
        $where = [];
        $where['channel_id'] = $channelId;
        $where['type'] = $type;
        $options = [];
        $options['where'] = $where;
        $options['field'] = 'goods_limited';
        $info = $this->queryRow($options)['data'];
        return (int)$info['goods_limited'];
    }
}