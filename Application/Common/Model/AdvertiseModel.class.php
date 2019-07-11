<?php

namespace Common\Model;

use Common\Util\Decoration;

class AdvertiseModel extends BaseModel
{

    // 定义表名
    protected $tableName = 'mb_advertise';

    /**
     * @param int $advId
     * @param int $storeId
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-24 21:33:45
     * Desc: 获取广告的信息
     * Update: 2017-10-24 21:33:46
     * Version: 1.0
     */
    public function getAdvertiseInfo($advId = 0, $storeId = 0, $condition = [])
    {
        $where = [];
        $where['advertise_id'] = $advId;
        $where = array_merge($where, $condition);
        $result = $this->getAdvertiseList($storeId, $where);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'][0];
        return getReturn(200, '', $info);
    }

    /**
     * @param int $storeId
     * @param array $condition
     * @param int $adNum
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-24 21:46:30
     * Desc: 获取广告列表
     * Update: 2017-10-24 21:46:31
     * Update: 2017-11-02 21:49:18 广告统一action
     * Version: 1.1
     */
    public function getAdvertiseList($storeId = 0, $condition = [], $adNum = 0)
    {
        $actionData = Decoration::ACTION_DATA_ADV;
        $where = [];
        $where['store_id'] = $storeId;
        $where = array_merge($where, $condition);
        $options = [];
        $options['where'] = $where;
        $options['take'] = $adNum;
        $result = $this->queryList($options);
        if ($result['code'] !== 200) return $result;
        $list = $result['data']['list'];
        foreach ($list as $key => $value) {
            // 解析
            $list[$key]['advertise_link'] = jsonDecodeToArr($value['advertise_link']);
            if (empty($list[$key]['advertise_link']) || !is_array($list[$key]['advertise_link'])) {
                $list[$key]['advertise_link'] = ['type' => 0, $actionData => '', 'word' => '', 'action' => Decoration::ACTION_NO];
            }
            transEmptyAction($list[$key]['advertise_link'], $actionData);
            // 改变action前获取描述
            $list[$key]['type_name'] = UtilModel::getLinkName($list[$key]['advertise_link']['action'], $list[$key]['advertise_link'][$actionData]);
            // 改变action 如果是系统功能的话
            transSystemAction($list[$key]['advertise_link'], $actionData);
        }
        return getReturn(200, '', $list);
    }

    /**
     * @param int $advId
     * @param int $storeId
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-24 22:28:08
     * Desc: 保存广告 新增广告
     * Update: 2017-10-24 22:28:09
     * Version: 1.0
     */
    public function saveAdvertise($advId = 0, $storeId = 0, $data = [])
    {
        $actionData = Decoration::ACTION_DATA_ADV;
        if ($advId > 0) {
            $result = $this->getAdvertiseInfo($advId, $storeId);
            if ($result['code'] !== 200) return $result;
            $info = $result['data'];
            if (empty($info)) return getReturn(-1, '广告不存在或者已被管理员删除');
        }
//        if (empty($data['advertise_name'])) return getReturn(-1, '请输入广告名称');
        if (empty($data['purl'])) return getReturn(-1, '请上传广告图片');
        // 转换跳转方式
        if ($data['advertise_link']['action'] == 'system') {
            if (empty($data['advertise_link'][$actionData])) return getReturn(-1, '请选择系统功能');
            revertSystemAction($data['advertise_link'], $actionData);
        }
        // 检查跳转参数
        $result = UtilModel::checkLinkType($data['advertise_link']['action'], $data['advertise_link'][$actionData]);
        if ($result['code'] !== 200) return $result;
        // action 转换旧版本的导航type
        $data['advertise_link']['type'] = UtilModel::actionToType($data['advertise_link']['action'], UtilModel::getStoreType($storeId));
        // 获取参数标题
        if (empty($data['advertise_link']['word'])) {
            $data['advertise_link']['word'] = UtilModel::getParamTitle($data['advertise_link']['action'], $data['advertise_link'][$actionData]);
        }
        $data['store_id'] = $storeId;
        $data['advertise_link'] = json_encode($data['advertise_link'], JSON_UNESCAPED_UNICODE);
        if ($advId > 0) {
            $where = [];
            $where['advertise_id'] = $advId;
            $options = [];
            $options['where'] = $where;
            $result = $this->saveData($options, $data);
        } else {
            $result = $this->addData([], $data);
        }
        return $result;
    }

    /**
     * 新版商城装修 保存轮播广告
     * @param int $storeId
     * @param array $advList
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-09-20 15:28:35
     * Update: 2018-09-20 15:28:35
     * Version: 1.00
     */
    public function saveAdvList($storeId = 0, $advList = [])
    {
        // region 检查参数
        $advNum = getAdvNum($storeId);
        if (count($advList) > $advNum) {
            return getReturn(CODE_ERROR, "轮播广告数量最多使用{$advNum}个");
        }
        foreach ($advList as $key => $adv) {
            $index = $key + 1;
            if (empty($adv['img_url'])) {
                return getReturn(CODE_ERROR, "请上传第{$index}个轮播广告的图片");
            }
            if ($adv['action'] === Decoration::ACTION_SYSTEM && empty($adv['action_data'])) {
                return getReturn(CODE_ERROR, "请选择第{$index}个轮播广告的系统功能");
            }
            revertSystemAction($adv);
            $result = UtilModel::checkLinkType($adv['action'], $adv['action_data']);
            if (!isSuccess($result)) {
                return getReturn(CODE_ERROR, "第{$index}个轮播广告错误:{$result['msg']}");
            }
            // 数据处理
            $adv['word'] = empty($adv['word']) ? UtilModel::getParamTitle($adv['action'], $adv['action_data']) : $adv['word'];
            $adv['type'] = UtilModel::actionToType($adv['action'], UtilModel::getStoreType($storeId)); // 兼容旧版APP
            $adv['name'] = empty($adv['name']) ? $adv['word'] : $adv['name'];
            $advList[$key] = $adv;
        }
        // endregion

        // region 保存数据
        $results = [];
        // 先删除
        $where = [];
        $where['store_id'] = $storeId;
        $results[] = $this->where($where)->delete();

        // 再新增
        $data = [];
        foreach ($advList as $adv) {
            $item = [];
            $item['store_id'] = $storeId;
            $item['advertise_name'] = $adv['name'];
            $item['purl'] = $adv['img_url'];
            $item['state'] = 1;
            $item['advertise_link'] = [
                'action' => $adv['action'],
                Decoration::ACTION_DATA_ADV => $adv['action_data'],
                'word' => $adv['word'],
                'type' => $adv['type'],
            ];
            $item['advertise_link'] = jsonEncode($item['advertise_link']);
            $data[] = $item;
        }
        if (!empty($data)) {
            $results[] = $this->addAll($data);
        }

        if (isTransFail($results)) {
            return getReturn(CODE_ERROR);
        }
        // endregion
        return getReturn(CODE_SUCCESS, L('SAVE_SUCCESSFUL'));
    }
}