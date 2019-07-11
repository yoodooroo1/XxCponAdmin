<?php

namespace Common\Model;

class AppNavModel extends BaseModel
{
    protected $tableName = 'mb_store_info';

    /**
     * @param int $storeId
     * @param int $storeType
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-25 00:16:52
     * Desc: 获取APP导航按钮列表
     * Update: 2017-10-25 00:16:53
     * Version: 1.0
     */
    public function getAppNavList($storeId = 0, $storeType = 0, $condition = [])
    {
        $where = [];
        $where['store_id'] = $storeId;
        $where = array_merge($where, $condition);
        // 5或8个导航
        $length = $storeType == self::MALL_MAIN_STORE ? 8 : 5;
        $field = [];
        for ($i = 1; $i <= $length; $i++) {
            $field[] = "item{$i}";
        }
        $field = implode(',', $field);
        $info = $this
            ->field($field)
            ->where($where)
            ->find();
        if (false === $info) {
            logWrite("查询商家{$storeId}APP导航失败:" . $this->getDbError());
            return getReturn();
        }
        $list = [];
        for ($i = 1; $i <= $length; $i++) {
            $item = [];
            $item['nav_id'] = "item{$i}";
            $item['nav_link'] = $this->initNav("item{$i}", json_decode($info["item{$i}"], 1), $storeId, $storeType);
            $item['nav_img'] = $item['nav_link']['imgurl'];
            if (strpos('02', $storeType . '') === false) {
                $item['nav_type_name'] = UtilModel::getAloneNavLinkName($item['nav_link']['type'], $item['nav_link']['weburl']);
                if (($item['nav_link']['type'] >= 2 && $item['nav_link']['type'] <= 8) ||
                    ($item['nav_link']['type'] >= 15 && $item['nav_link']['type'] <= 17)) {
                    $item['nav_link']['weburl'] = $item['nav_link']['type'];
                    $item['nav_link']['type'] = -1;
                }
            } else {
                $item['nav_type_name'] = UtilModel::getMallNavLinkName($item['nav_link']['type'], $item['nav_link']['weburl']);
                if (($item['nav_link']['type'] >= 2 && $item['nav_link']['type'] <= 13)) {
                    $item['nav_link']['weburl'] = $item['nav_link']['type'];
                    $item['nav_link']['type'] = -1;
                }
            }
            $list[] = $item;
        }
        $grant = D('Store')->getStoreGrantInfo($storeId)['data'];
        foreach ($list as $key => $value) {
            $type = strpos('02', $storeType . '') === false ? 2 : 8;
            if ($value['nav_link']['weburl'] == $type && $grant['credit_hide'] == 1) {
                unset($list[$key]);
            }
        }
        return getReturn(200, '', $list);
    }

    /**
     * @param int $storeId
     * @param int $storeType
     * @param string $navName
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-25 00:18:21
     * Desc: 获取APP导航按钮的信息
     * Update: 2017-10-25 00:18:22
     * Version: 1.0
     */
    public function getAppNavInfo($storeId = 0, $storeType = 0, $navName = '', $condition = [])
    {
        $result = $this->getAppNavList($storeId, $storeType, $condition);
        if ($result['code'] !== 200) return $result;
        $list = $result['data'];
        $info = [];
        foreach ($list as $key => $value) {
            if ($value['nav_id'] == $navName) {
                $info = $value;
                break;
            }
        }
        return getReturn(200, '', $info);
    }

    /**
     * @param int $storeId
     * @param string $navName
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date:　2017-10-25 02:14:44
     * Desc:　保存APP导航按钮
     * Update:　2017-10-25 02:14:45
     * Version: 1.0
     */
    public function saveAppNav($storeId = 0, $navName = '', $data = [])
    {
        if (empty($data['nav_link']['title'])) return getReturn(-1, '请输入导航名称');
        if (empty($data['nav_link']['imgurl'])) return getReturn(-1, '请上传导航图片');
        if ($data['nav_link']['type'] == -1) {
            if (empty($data['nav_link']['weburl'])) return getReturn(-1, '请选择系统功能');
            $data['nav_link']['type'] = $data['nav_link']['weburl'];
            $data['nav_link']['weburl'] = '';
        }
        $storeType = UtilModel::getStoreType($storeId);
        if (strpos('02', $storeType . '') === false) {
            $result = UtilModel::checkAloneNavLinkType($data['nav_link']['type'], $data['nav_link']['weburl']);
        } else {
            $result = UtilModel::checkMallNavLinkType($data['nav_link']['type'], $data['nav_link']['weburl']);
        }
        if ($result['code'] !== 200) return $result;
        $where = [];
        $where['store_id'] = $storeId;
        $count = $this->where($where)->count();
        $item = json_encode($data['nav_link'], JSON_UNESCAPED_UNICODE);
        $data = [];
        $data['store_id'] = $storeId;
        $data[$navName] = $item;
        if ($count > 0) {
            $result = $this->where($where)->save($data);
        } else {
            $result = $this->add($data);
        }
        if ($result === false) {
            logWrite("保存APP导航{$storeId}{$navName}失败:" . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '', $result);
    }

    /**
     * @param string $navName
     * @param array $info
     * @param int $storeId
     * @param int $storeType
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-25 00:27:12
     * Desc: 初始化导航参数
     * Update: 2017-10-25 00:27:13
     * Version: 1.0
     */
    private function initNav($navName = '', $info = [], $storeId = 0, $storeType = 0)
    {
        if (empty($info)) {
            switch ($navName) {
                case 'item1':
                    switch ((int)$storeType) {
                        // 商城
                        case 0:
                        case 2:
                            $info = $this->setNavInfo(2, '', '精划算', 'http://file.duinin.com/data/item/mall_item1.png');
                            break;
                        // 商城子店
                        case 1:
                        case 3:
                        case 4:
                        case 5:
                            $info = $this->setNavInfo(2, '', '积分商城', 'http://file.duinin.com/data/item/jifenduihuan.png');
                            break;
                        default:
                            break;
                    }
                    break;
                case 'item2':
                    switch ((int)$storeType) {
                        // 商城
                        case 0:
                        case 2:
                            $info = $this->setNavInfo(3, '', '逛商铺', 'http://file.duinin.com/data/item/mall_item2.png');
                            break;
                        // 商城子店
                        case 1:
                        case 3:
                        case 4:
                        case 5:
                            $info = $this->setNavInfo(3, '', '摇奖品', 'http://file.duinin.com/data/item/yaoyao.png');
                            break;
                        default:
                            break;
                    }
                    break;
                case 'item3':
                    switch ((int)$storeType) {
                        // 商城
                        case 0:
                        case 2:
                            $info = $this->setNavInfo(4, '', '地图', 'http://file.duinin.com/data/item/mall_item3.png');
                            break;
                        // 商城子店
                        case 1:
                        case 4:
                            $info = $this->setNavInfo(4, '', '我的收藏', 'http://file.duinin.com/data/item/wodeshouchang.png');
                            break;
                        // 连锁子店
                        case 3:
                            $info = $this->setNavInfo(15, '', '服务中心', 'http://file.duinin.com/data/item/store_serve.png');
                            break;
                        // 普通
                        case 5:
                            $info = $this->setNavInfo(8, '', '签到', 'http://file.duinin.com/data/item/sign.png');
                            break;
                        default:
                            break;
                    }
                    break;
                case 'item4':
                    switch ((int)$storeType) {
                        // 商城
                        case 0:
                        case 2:
                            $info = $this->setNavInfo(5, '', '我的足迹', 'http://file.duinin.com/data/item/mall_item4.png');
                            break;
                        // 商城子店
                        case 1:
                            $info = $this->setNavInfo(15, '', '服务中心', 'http://file.duinin.com/data/item/store_serve.png');
                            break;
                        // 连锁子店
                        case 3:
                            $info = $this->setNavInfo(8, '', '签到', 'http://file.duinin.com/data/item/sign.png');
                            break;
                        // 独立
                        case 4:
                            $info = $this->setNavInfo(5, '', '直接付款', 'http://file.duinin.com/data/item/zhijiezhifu.png');
                            break;
                        // 普通
                        case 5:
                            $info = $this->setNavInfo(4, '', '我的收藏', 'http://file.duinin.com/data/item/wodeshouchang.png');
                            break;
                        default:
                            break;
                    }
                    break;
                case 'item5':
                    switch ((int)$storeType) {
                        // 商城
                        case 0:
                        case 2:
                            $info = $this->setNavInfo(6, '', '市场公告', 'http://file.duinin.com/data/item/mall_item5.png');
                            break;
                        // 商城子店
                        case 1:
                            $info = $this->setNavInfo(17, '', '活动公告', 'http://file.duinin.com/data/item/store_notice.png');
                            break;
                        // 连锁子店
                        case 3:
                            $info = $this->setNavInfo(16, '', '消息', 'http://file.duinin.com/data/item/mall_item0.png');
                            break;
                        // 独立
                        case 4:
                            $info = $this->setNavInfo(15, '', '服务中心', 'http://file.duinin.com/data/item/store_serve.png');
                            break;
                        // 普通
                        case 5:
                            $grant = D('Store')->getStoreGrantInfo($storeId)['data'];
                            $type = $grant['partner_ctrl'] == 1 ? 6 : 5;
                            $title = $grant['partner_ctrl'] == 1 ? '联盟商家' : '直接付款';
                            $img = $grant['partner_ctrl'] == 1 ? 'http://file.duinin.com/data/item/lianmengshangjia.png' : 'http://file.duinin.com/data/item/zhijiezhifu.png';
                            $info = $this->setNavInfo($type, '', $title, $img);
                            break;
                        default:
                            break;
                    }
                    break;
                case 'item6':
                    switch ((int)$storeType) {
                        // 商城
                        case 0:
                        case 2:
                            $info = $this->setNavInfo(7, '', '摇奖品', 'http://file.duinin.com/data/item/mall_item6.png');
                            break;
                        // 商城子店
                        case 1:
                            break;
                        // 连锁子店
                        case 3:
                            break;
                        // 独立
                        case 4:
                            break;
                        // 普通
                        case 5:
                            break;
                        default:
                            break;
                    }
                    break;
                case 'item7':
                    switch ((int)$storeType) {
                        // 商城
                        case 0:
                        case 2:
                            $info = $this->setNavInfo(8, '', '积分商城', 'http://file.duinin.com/data/item/mall_item7.png');
                            break;
                        // 商城子店
                        case 1:
                            break;
                        // 连锁子店
                        case 3:
                            break;
                        // 独立
                        case 4:
                            break;
                        // 普通
                        case 5:
                            break;
                        default:
                            break;
                    }
                    break;
                case 'item8':
                    switch ((int)$storeType) {
                        // 商城
                        case 0:
                        case 2:
                            $info = $this->setNavInfo(9, '', '服务中心', 'http://file.duinin.com/data/item/mall_item8.png');
                            break;
                        // 商城子店
                        case 1:
                            break;
                        // 连锁子店
                        case 3:
                            break;
                        // 独立
                        case 4:
                            break;
                        // 普通
                        case 5:
                            break;
                        default:
                            break;
                    }
                    break;
                default:
                    break;
            }
        }
        return $info;
    }

    private function setNavInfo($type = 0, $param = '', $title = '', $img = '')
    {
        return ['type' => $type, 'weburl' => $param, 'title' => $title, "imgurl" => $img];
    }
}