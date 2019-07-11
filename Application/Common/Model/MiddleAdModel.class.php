<?php

namespace Common\Model;

use Admin\Controller\DecorationController;
use Common\Util\Decoration;
use Think\Upload;

class MiddleAdModel extends BaseModel
{
    protected $tableName = 'store';

    /**
     * @param int $storeId
     * @param int $storeType
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-25 01:49:22
     * Desc: 获取中间通告栏广告列表
     * Update: 2017-10-25 01:49:23
     * Version: 1.0
     */
    public function getMiddleAdList($storeId = 0, $storeType = 0, $condition = [])
    {
        $actionData = Decoration::ACTION_DATA_ADV;
        $where = [];
        $where['store_id'] = $storeId;
        $where = array_merge($where, $condition);
        $options = [];
        $options['where'] = $where;
        $options['field'] = 'advertisement';
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        $list = [];
        $info['advertisement'] = json_decode($info['advertisement'], 1);
//        if (!empty($info['advertisement'])) $info['advertisement'] = array_sort($info['advertisement'], 'sort', 'DESC');
        foreach ($info['advertisement'] as $key => $value) {
            $item = [];
            $item['mid_id'] = $key + 1;
            $item['mid_status'] = $value['status'];
            $item['mid_time'] = $value['edit_time'];
            $item['mid_sort'] = $value['sort'];
            transEmptyAction($value, $actionData);
            $item['mid_type_name'] = UtilModel::getLinkName($value['action'], $value[$actionData]);
            transSystemAction($value, $actionData);
            $item['mid_link'] = $value;
            $list[] = $item;
        }
        return getReturn(200, '', $list);
    }

    /**
     * @param int $storeId
     * @param int $midId
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-25 11:20:25
     * Desc: 获取中间通告栏信息
     * Update: 2017-10-25 11:20:27
     * Version: 1.0
     */
    public function getMiddleAdInfo($storeId = 0, $midId = 0, $condition = [])
    {
        $result = $this->getMiddleAdList($storeId, 0, $condition);
        if ($result['code'] !== 200) return $result;
        $list = $result['data'];
        $info = [];
        foreach ($list as $key => $value) {
            if ($midId == $key + 1) {
                $info = $value;
                break;
            }
        }
        return getReturn(200, '', $info);
    }

    /**
     * @param int $storeId
     * @param int $midId
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-25 11:59:04
     * Desc: 保存中间广告
     * Update: 2017-10-25 11:59:05
     * Version: 1.0
     */
    public function saveMiddleAd($storeId = 0, $midId = 0, $data = [])
    {
        $actionData = Decoration::ACTION_DATA_ADV;
//        if (empty($data['mid_link']['word'])) return getReturn(-1, '请输入广告标识');
        if (empty($data['mid_link']['purl'])) return getReturn(-1, '请上传广告图片');
        if ($data['mid_link']['action'] == 'system') {
            if (empty($data['mid_link'][$actionData])) return getReturn(-1, '请选择系统功能');
            revertSystemAction($data['mid_link'], $actionData);
        }
        // action 转换旧版本的导航type
        $data['mid_link']['type'] = UtilModel::actionToType($data['mid_link']['action'], UtilModel::getStoreType($storeId));
        // 获取参数标题
        if (empty($data['mid_link']['word'])) {
            $data['mid_link']['word'] = UtilModel::getParamTitle($data['mid_link']['action'], $data['mid_link'][$actionData]);
        }
        $result = UtilModel::checkLinkType($data['mid_link']['action'], $data['mid_link'][$actionData]);
        if ($result['code'] !== 200) return $result;
        $result = $this->getMiddleAdList($storeId);
        if ($result['code'] !== 200) return $result;
        $list = empty($result['data']) ? [] : $result['data'];
        if ($midId > 0) {
            foreach ($list as $key => $value) {
                if ($value['mid_id'] == $midId) {
                    $list[$key] = [];
                    $data['mid_link']['edit_time'] = NOW_TIME;
                    $list[$key] = $data;
                    break;
                }
            }
        } else {
            $data['mid_id'] = count($list) + 1;
            $data['mid_link']['edit_time'] = NOW_TIME;
            array_push($list, $data);
        }
        $adData = [];
        foreach ($list as $key => $value) {
            if ($value['mid_link']['action'] == 'system') {
                $value['mid_link']['action'] = $value['mid_link'][$actionData];
                $value['mid_link'][$actionData] = '';
            }
            $adData[] = $value['mid_link'];
        }
        $where = [];
        $where['store_id'] = $storeId;
        $data = [];
        $data['advertisement'] = json_encode($adData, JSON_UNESCAPED_UNICODE);
        $options = [];
        $options['where'] = $where;
        return $this->saveData($options, $data);
    }

    /**
     * 新版装修 保存中间通栏广告
     * @param int $storeId
     * @param array $midAdvs
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-09-20 15:59:07
     * Update: 2018-09-20 15:59:07
     * Version: 1.00
     */
    public function saveMidAdv($storeId = 0, $midAdvs = [])
    {
        // region 检查数据
        $advNum = getAdvNum($storeId);
        if (count($midAdvs) > $advNum) {
            return getReturn(CODE_ERROR, "通栏广告数量最多使用{$advNum}个");
        }
        foreach ($midAdvs as $key => $midAdv) {
            $index = $key + 1;
            if (empty($midAdv['img_url'])) {
                return getReturn(CODE_ERROR, "请上传第{$index}个通栏广告的图片");
            }
            if ($midAdv['action'] === Decoration::ACTION_SYSTEM && empty($midAdv['action_data'])) {
                return getReturn(CODE_ERROR, "请选择第{$index}个通栏广告的系统功能");
            }
            revertSystemAction($midAdv);
            $result = UtilModel::checkLinkType($midAdv['action'], $midAdv['action_data']);
            if (!isSuccess($result)) {
                return getReturn(CODE_ERROR, "第{$index}个通栏广告错误:{$result['msg']}");
            }
            $midAdv['show'] = $midAdv['show'] == 1 ? 1 : 0;
            $midAdv['type'] = UtilModel::actionToType($midAdv['action'], UtilModel::getStoreType($storeId)); // 兼容旧版APP
            $midAdv['advertise_name'] = empty($midAdv['advertise_name']) ? UtilModel::getParamTitle($midAdv['action'], $midAdv['action_data']) : $midAdv['advertise_name'];
            $midAdv['edit_time'] = NOW_TIME;
            $midAdvs[$key] = $midAdv;
        }
        // endregion

        // region 保存数据
        $adData = [];
        foreach ($midAdvs as $midAdv) {
            $adData[] = [
                'action' => $midAdv['action'],
                Decoration::ACTION_DATA_ADV => $midAdv['action_data'],
                'purl' => $midAdv['img_url'],
                'type' => $midAdv['type'],
                'status' => $midAdv['show'],
                'word' => $midAdv['advertise_name'],
                'edit_time' => $midAdv['edit_time']
            ];
        }
        $where = [];
        $where['store_id'] = $storeId;
        $data = [];
        $data['version'] = $this->max('version') + 1;
        $data['advertisement'] = jsonEncode($adData);
        $result = $this->where($where)->save($data);
        if (false === $result) {
            return getReturn(CODE_ERROR);
        }
        // endregion
        return getReturn(CODE_SUCCESS);
    }
}