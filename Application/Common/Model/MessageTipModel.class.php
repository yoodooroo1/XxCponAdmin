<?php

namespace Common\Model;
/**
 * Class MessageTipModel
 * 消息提醒
 * @package Common\Model
 * User: hjun
 * Date: 2017-12-21 09:27:54
 */
class MessageTipModel extends BaseModel
{
    protected $tableName = 'mb_message_tip_task';

    // 验证字段1,验证规则,错误提示,[验证条件,附加规则,验证时间,参数列表]
    protected $_validate = [
        ['member_id', 'require', '请先登录', 0, 'regex', 3],
        ['store_id', 'require', '请设置商家', 0, 'regex', 3],
        ['type', '1,2', '请选择提醒类型', 0, 'in', 3],
        ['reminder_time', 'require', '请设置提醒时间', 0, 'regex', 3],
        ['param_type', '1,2', '请设置提醒的所属业务', 0, 'in', 3],
        ['param_id', 'require', '请设置所属业务ID', 0, 'regex', 3],
    ];

    /**
     * 添加提醒任务
     * @param int $memberId
     * @param int $storeId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-21 09:50:02
     * Update: 2017-12-21 09:50:02
     * Version: 1.00
     */
    public function addMsgTipTask($memberId = 0, $storeId = 0, $data = [])
    {
        $where = [];
        $where['member_id'] = $memberId;
        $where['store_id'] = $storeId;
        $where['status'] = 2;
        $where['param_id'] = $data['param_id'];
        $where['param_type'] = $data['param_type'];
        $info = $this->where($where)->find();
        if (!empty($info)) {
            return getReturn(-1, '您已设置了提醒');
        }
        // 检查提醒内容
        switch ((int)$data['type']) {
            case 1:
                $templateContent = json_decode($data['template_content'], 1);
                if (empty($templateContent)) {
                    return getReturn(-1, '请设置提醒内容');
                }
                break;
            case 2:
                if (empty($data['msg_content'])) {
                    return getReturn(-1, '请设置提醒内容');
                }
                break;
            default:
                break;
        }

        // 查找openid 和 phone
        $model = D('Member');
        $result = $model->getMemberInfo($memberId);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        $info['wx_openid'] = D('StoreMember')->getStoreMemberOpenId($storeId, $memberId);
        switch ((int)$data['type']) {
            case 1:
                if (empty($info['wx_openid'])) {
                    return getReturn(-1, '未绑定微信');
                }
                break;
            case 2:
                if (empty($info['member_tel'])) {
                    return getReturn(-1, '未绑定手机');
                }
                break;
            default:
                break;
        }
        $data['member_id'] = $memberId;
        $data['store_id'] = $storeId;
        $data['open_id'] = $info['wx_openid'];
        $data['phone'] = empty($data['member_tel']) ? '' : $data['member_tel'];
        $data['create_time'] = NOW_TIME;
        return $this->addData([], $data);
    }

    /**
     * 发送模版消息
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2017-12-21 14:00:33
     * Update: 2017-12-21 14:00:33
     * Version: 1.00
     */
    public function sendTemplateMsg()
    {
        // 查找 模版消息 未提醒 并且提醒时间大于当前时间的
        $where = [];
        $where['status'] = 2;
        $options = [];
        $options['where'] = $where;
        $total = $this->queryTotal($options);
        // 一次发100个
        $count = ceil($total / 100);
        $page = 1;
        $successData = [];
        do {
            $options['page'] = $page;
            $options['limit'] = 100;
            $result = $this->queryList($options);
            if ($result['code'] !== 200) return $result;
            $list = $result['data']['list'];
            logWrite("提醒消息列表：" . json_encode($list, JSON_UNESCAPED_UNICODE));
            if (!empty($list)) {
                foreach ($list as $key => $value) {
                    $hasSend = false;
                    switch ((int)$value['type']) {
                        case 1:
                            $reminderTime = date("YmdHi", $value['reminder_time']);
                            $nowTime = date("YmdHi");
                            // 提前5分钟提醒 超过提醒时间一天就不提醒了
                            if ($reminderTime - 3000 <= $nowTime && $nowTime - $reminderTime < 3600 * 24) {
                                if (!empty($value['store_id']) && !empty($value['template_content'])) {
                                    $hasSend = true;
                                    $result = D('WxUtil')->sendTemplateMsg($value['store_id'], $value['template_content']);
                                } else {
                                    $result = getReturn(-1, '该提醒内容不完全');
                                }
                            } else {
                                if ($nowTime - $reminderTime >= 3600 * 24) {
                                    $result = getReturn(4063, '已过期');
                                } else {
                                    $result = getReturn(-1, '提醒时间未到');
                                }
                            }
                            break;
                        default:
                            $result = getReturn(-1, '未知提醒类型');
                            break;
                    }
                    if ($result['code'] === 200) {
                        // 发送成功后设置为已提醒
                        $item = [];
                        $item['id'] = $value['id'];
                        $item['status'] = 1;
                        $successData[] = $item;
                    } elseif ($result['code'] === 4063) {
                        // 已过期
                        $item = [];
                        $item['id'] = $value['id'];
                        $item['status'] = 3;
                        $successData[] = $item;
                    } else {
                        if ($hasSend) {
                            // 发送失败把结果记录一下
                            $item = [];
                            $item['id'] = $value['id'];
                            $item['result'] = "{$result['code']}:{$result['msg']}";
                            $successData[] = $item;
                        }
                        logWrite("ID:{$value['id']}的提醒消息:{$result['msg']}");
                    }
                }
            }
            $page++;
        } while ($page <= $count);
        if (!empty($successData)) {
            return $this->saveAllData([], $successData);
        }
    }
}