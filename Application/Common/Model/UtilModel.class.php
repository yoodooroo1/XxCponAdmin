<?php

namespace Common\Model;

use Common\Util\Decoration;

/**
 * Class UtilModel
 * User: hj
 * Date: 2017-10-23 10:05:32
 * Desc: 工具模型 不连接实际的数据表
 * Update: 2017-10-23 10:05:33
 * Version: 1.0
 * @package Common\Model
 */
class UtilModel extends BaseModel
{
    protected $autoCheckFields = false;

    //@param int $channelId 渠道号
    //@param int $storeGrade 等级
    //@param int $storeType 0-商主 1-商子 2-连主 3-连子 4-独立 5-普通便利店（讯信）
    /**
     * @param int $storeId 商家ID
     * @param string $clientType web app wap 客户端类型
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取商家的跳转方式
     * Date: 2017-11-01 11:18:25
     * Update: 2017-11-01 11:18:26
     * Version: 1.0
     */
    static public function getLinkTypeOption($storeId = 0, $clientType = 'app')
    {
        $util = new Decoration($storeId);
        $util->setType($clientType);
        $option = $util->getLinkTypeOption();
        return getReturn(CODE_SUCCESS, 'success', $option);
    }

    /**
     * @param string $action
     * @param string $param
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取条转方式的描述
     * Date: 2017-11-01 15:18:44
     * Update: 2017-11-01 15:18:45
     * Version: 1.0
     */
    static public function getLinkName($action = '', $param = '')
    {
        $typeName = $desc = '';
        switch ($action) {
            case 'no_action':
                $typeName = '不做任何跳转';
                $desc = '不做任何跳转';
                break;
            case 'good_deal':
                $typeName = '系统功能';
                $desc = '精划算';
                break;
            case 'go_stores':
                $typeName = '系统功能';
                $desc = '逛商铺';
                break;
            case 'map':
            case 'mall_map':
                $typeName = '系统功能';
                $desc = '地图';
                break;
            case 'my_footprint':
                $typeName = '系统功能';
                $desc = '我的足迹';
                break;
            case 'mall_notice_list':
                $typeName = '系统功能';
                $desc = '市场公告';
                break;
            case 'shake_prize':
                $typeName = '系统功能';
                $desc = '摇奖品';
                break;
            case 'points_mall':
                $typeName = '系统功能';
                $desc = '积分商城';
                break;
            case 'service_center':
                $typeName = '系统功能';
                $desc = '服务中心';
                break;
            case 'find_good_store':
                $typeName = '系统功能';
                $desc = '找好店';
                break;
            case 'boutique_shopping':
                $typeName = '系统功能';
                $desc = '精品购';
                break;
            case 'day_shopping':
                $typeName = '系统功能';
                $desc = '每日购';
                break;
            case 'my_collection':
                $typeName = '系统功能';
                $desc = '我的收藏';
                break;
            case 'my_prize_gift':
                $typeName = '系统功能';
                $desc = '我的奖/礼品';
                break;
            case 'my_coupon':
                $typeName = '系统功能';
                $desc = '我的优惠券';
                break;
            case 'communication':
                $typeName = '系统功能';
                $desc = '消息';
                break;
            case 'sign_in':
                $typeName = '系统功能';
                $desc = '每日任务';
                break;
            case 'direct_payment':
                $typeName = '系统功能';
                $desc = '直接付款';
                break;
            case 'store_notice_list':
                $typeName = '系统功能';
                $desc = '活动公告';
                break;
            case 'apply_for_agent':
                $typeName = '系统功能';
                $desc = '申请代理';
                break;
            case 'search_goods':
                $typeName = '搜索商品';
                $desc = "关键字:{$param}";
                break;
            case 'mall_search_goods':
                $typeName = '搜索商品';
                $desc = "关键字:{$param}";
                break;
            case 'mall_class_goods':
                $typeName = '分类商品';
                // 1|2|3 取最后一个
                $id = explode('|', $param);
                $index = count($id) - 1;
                $id = $id[$index];
                $where = [];
                $where['id'] = $id;
                $className = M('mb_mallclass')->where($where)->getField('classname');
                $desc = "分类名称:{$className}";
                break;
            case 'child_class_goods':
                $typeName = '分类商品';
                $id = explode('|', $param);
                $index = count($id) - 1;
                $id = $id[$index];
                $where = [];
                $where['gc_id'] = $id;
                $className = M('goods_class')->where($where)->getField('gc_name');
                $desc = "分类名称:{$className}";
                break;
            case 'web_url':
                $typeName = '自定义链接';
                $url = $param;
                $desc = $url;
                break;
            case 'one_goods':
                $typeName = '单件商品';
                $where = [];
                $where['goods_id'] = $param;
                $goodsName = M('goods')->where($where)->getField('goods_name');
                $desc = "商品名称:{$goodsName}";
                break;
            case 'one_union_store':
                $typeName = '联盟商家';
                $where = [];
                $where['member_name'] = $param;
                $storeName = M('store')->where($where)->getField('store_name');
                $desc = "商家名称:{$storeName}";
                break;
            case 'one_notice':
                $typeName = '单条公告';
                $where = [];
                $where['notice_id'] = $param;
                $noticeTitle = M('mb_notice')->where($where)->getField('title');
                $desc = "公告标题:{$noticeTitle}";
                break;
            case 'one_news':
                $typeName = '单条资讯';
                $where = [];
                $where['newsid'] = $param;
                $newsTitle = M('newslist')->where($where)->getField('title');
                $desc = "资讯标题:{$newsTitle}";
                break;
            case 'tag_goods':
                $typeName = '标签商品';
                $where = [];
                $where['tag_id'] = $param;
                $tagName = M('goods_tag')->where($where)->getField('tag_name');
                $desc = "标签名称:{$tagName}";
                break;
            case 'special_offer':
                $typeName = "系统功能";
                $desc = "今日特价";
                break;
            case 'hot_sale_goods':
                // 热卖商品
                $typeName = "系统功能";
                $desc = "热销商品";
                break;
            case 'coupons_center':
                $typeName = '系统功能';
                $desc = '领券中心';
                break;
            default:
                break;
        }
        return ['name' => $typeName, 'desc' => $desc];
    }

    /**
     * @param string $action
     * @param string $param
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 检查跳转方式
     * Date: 2017-11-02 01:13:13
     * Update: 2017-11-02 01:13:14
     * Version: 1.0
     */
    static public function checkLinkType($action = '', $param = '')
    {
        switch ($action) {
            case 'search_goods':
                if (empty($param)) return getReturn(-1, '请输入商品关键字');
                break;
            case 'mall_class_goods':
                // 1|2|3 取最后一个
                $id = explode('|', $param);
                $index = count($id) - 1;
                $id = $id[$index];
                $where = [];
                $where['id'] = $id;
                $count = M('mb_mallclass')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的分类不存在');
                break;
            case 'child_class_goods':
                $id = explode('|', $param);
                $index = count($id) - 1;
                $id = $id[$index];
                $where = [];
                $where['gc_id'] = $id;
                $count = M('goods_class')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的分类不存在');
                break;
            case 'web_url':
                if (empty($param)) return getReturn(-1, '请输入链接');
                if (strpos($param, 'http') === false) return getReturn(-1, '链接必须带http://');
                break;
            case 'one_goods':
                $where = [];
                $where['goods_id'] = $param;
                $count = M('goods')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '商品不存在');
                break;
            case 'one_union_store':
                $where = [];
                $where['member_name'] = $param;
                $count = M('store')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '商家不存在');
                break;
            case 'one_notice':
                $where = [];
                $where['notice_id'] = $param;
                $count = M('mb_notice')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '公告不存在');
                break;
            case 'one_news':
                $where = [];
                $where['newsid'] = $param;
                $count = M('newslist')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '资讯不存在');
                break;
            case 'tag_goods':
                $where = [];
                $where['tag_id'] = $param;
                $count = M('goods_tag')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '标签不存在');
                break;
            default:
                break;
        }
        return getReturn(200, '');
    }

    /**
     * @param int $storeId 商家ID
     * @param int $channelId 渠道ID
     * @param int $storeType 商家类型
     * @param array $grant 权限信息
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-23 10:34:28
     * Desc: 获取广告的跳转方式
     * Update: 2017-10-23 10:34:29
     * Version: 1.0
     */
    static public function getAdLinkOption($storeId = 0, $channelId = 0, $storeType = 0, $grant = [])
    {
        $storeType = $storeType . '';

        $linkOption = [['name' => '不做任何跳转', 'type' => 0]];

        // 系统功能
        $item = [];
        $item['name'] = '系统功能';
        $item['type'] = -1;
        $item['input_type'] = 'select';
        $item['tips'] = '请选择系统功能';
        // n_ctrl 表示权限中这个值为1就有这个选项 r_ctrl表示权限中这个值为1就没有这个选项
        $item['option'] = [
            ['name' => $item['tips'], 'value' => ''],
            ['name' => '摇一摇', 'value' => 9],
            ['name' => '积分商城', 'value' => 10, 'r_ctrl' => 'credit_hide'],
            ['name' => '我的奖/礼品', 'value' => 11],
            ['name' => '我的优惠券', 'value' => 12],
            ['name' => '服务中心', 'value' => 13],
            ['name' => '市场公告', 'value' => 14],
            ['name' => '我的收藏', 'value' => 15],
            ['name' => '签到', 'value' => 16],
            ['name' => '地图', 'value' => 17],
            ['name' => '精划算', 'value' => 18, 'storeType' => '02'],
            ['name' => '逛商铺', 'value' => 19, 'storeType' => '02'],
            ['name' => '我的足迹', 'value' => 20, 'storeType' => '02'],
            ['name' => '找好店', 'value' => 21, 'storeType' => '02'],
            ['name' => '精品购', 'value' => 22, 'storeType' => '02'],
            ['name' => '每日购', 'value' => 23, 'storeType' => '02'],
            ['name' => '直接付款', 'value' => 24, 'storeType' => '1345'],
            ['name' => '联盟商家', 'value' => 25, 'storeType' => '1345'],
            ['name' => '代理分组', 'value' => 26, 'n_ctrl' => 'partner_ctrl']
        ];
        foreach ($item['option'] as $key => $value) {
            if (isset($value['storeType']) && strpos($value['storeType'], $storeType) === false) {
                unset($item['option'][$key]);
            }
            if (isset($value['n_ctrl']) && (int)$grant[$value['n_ctrl']] !== 1) {
                unset($item['option'][$key]);
            }
            if (isset($value['r_ctrl']) && (int)$grant[$value['r_ctrl']] === 1) {
                unset($item['option'][$key]);
            }
        }
        $linkOption[] = $item;

        // 搜索商品
        $item = [];
        $item['name'] = '搜索商品';
        $item['type'] = 1;
        $item['input_type'] = 'text';
        $item['tips'] = '请输入商品关键字';
        $linkOption[] = $item;

        // 分类商品
        $item = [];
        $item['name'] = '分类商品';
        $item['type'] = 2;
        $item['input_type'] = 'select';
        $item['tips'] = '请选择商品分类';
        $item['option'] = [
            ['name' => $item['tips'], 'value' => '']
        ];
        // 获取分类 商城和子店不同
        $model = strpos('02', $storeType) === false ? D('GoodsClass') : D('MallGoodClass');
        $id = strpos('02', $storeType) === false ? $storeId : $channelId;
        $result = $model->getFirstLevelClass($id, 1, 0, [], 3);
        $option = $result['data'];
        foreach ($option as $key => $value) {
            $i = [];
            $i['name'] = $value['class_name'];
            $i['value'] = $value['class_id'];
            $item['option'][] = $i;
        }
        $linkOption[] = $item;

        // 自定义链接
        $item = [];
        $item['name'] = '自定义链接';
        $item['type'] = 3;
        $item['input_type'] = 'text';
        $item['tips'] = '链接必须带必须带http://';
        $linkOption[] = $item;

        // 单件商品
        $item = [];
        $item['name'] = '单件商品';
        $item['type'] = 4;
        $item['input_type'] = 'text';
        $item['tips'] = '请输入商品ID';
        $item['url'] = '/admin.php?m=Service&c=Goods&a=goods_list';
        $linkOption[] = $item;

        // 联盟店铺
        $item = [];
        $item['name'] = '联盟店铺';
        $item['type'] = 5;
        $item['input_type'] = 'select';
        $item['tips'] = '请选择联盟店铺';
        $item['option'] = [
            ['name' => $item['tips'], 'value' => '']
        ];
        // 查找联盟店铺列表
        $model = D('StoreUnion');
        $result = $model->getUnionStoreList($storeId);
        $option = $result['data'];
        // 有联盟才有这个跳转方式
        if (!empty($option)) {
            foreach ($option as $key => $value) {
                $i = [];
                $i['name'] = $value['store_name'];
                $i['value'] = $value['member_name'];
                $item['option'][] = $i;
            }
            $linkOption[] = $item;
        }


        // 单条公告
        $item = [];
        $item['name'] = '单条公告';
        $item['type'] = 6;
        $item['input_type'] = 'text';
        $item['tips'] = '请输入公告ID';
        $item['url'] = '/admin.php?m=Service&c=Notice&a=notice_list';
        $linkOption[] = $item;

        // 单条资讯
        $item = [];
        $item['name'] = '单条资讯';
        $item['type'] = 7;
        $item['input_type'] = 'text';
        $item['tips'] = '请输入资讯ID';
        $item['url'] = '/admin.php?m=Service&c=News&a=news_list_zx';
        $linkOption[] = $item;

        // 标签商品
        $item = [];
        $item['name'] = '标签商品';
        $item['type'] = 27;
        $item['input_type'] = 'select';
        $item['tips'] = '请选择商品标签';
        $item['option'] = [
            ['name' => $item['tips'], 'value' => '']
        ];
        // 查找商品标签
        $model = D('GoodsTag');
        $where = [];
        $where['tag_status'] = ['in', [1, 2]];
        $result = $model->getGoodsTag($storeId, $channelId, $where);
        $option = $result['data'];
        foreach ($option as $key => $value) {
            $i = [];
            $i['name'] = $value['tag_name'];
            $i['value'] = $value['tag_id'];
            $item['option'][] = $i;
        }
        $linkOption[] = $item;

        return $linkOption;
    }

    /**
     * @param int $storeId 商家ID
     * @param int $channelId 渠道ID
     * @param int $storeType 商家类型
     * @param array $grant
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-24 10:27:06
     * Desc: 获取商城导航的跳转方式
     * Update: 2017-10-24 10:27:07
     * Version: 1.0
     */
    static public function getMallNavLinkOption($storeId = 0, $channelId = 0, $storeType = 0, $grant = [])
    {
        $linkOption = [['name' => '不做任何跳转', 'type' => 0]];

        // 系统功能
        $item = [];
        $item['name'] = '系统功能';
        $item['type'] = -1;
        $item['input_type'] = 'select';
        $item['tips'] = '请选择系统功能';
        // n_ctrl 表示权限中这个值为1就有这个选项 r_ctrl表示权限中这个值为1就没有这个选项
        $item['option'] = [
            ['name' => $item['tips'], 'value' => ''],
            ['name' => '精华算', 'value' => 2],
            ['name' => '逛商铺', 'value' => 3],
            ['name' => '地图', 'value' => 4],
            ['name' => '我的足迹', 'value' => 5],
            ['name' => '市场公告', 'value' => 6],
            ['name' => '摇奖品', 'value' => 7],
            ['name' => '积分商城', 'value' => 8, 'r_ctrl' => 'credit_hide'],
            ['name' => '服务中心', 'value' => 9],
            ['name' => '找好店', 'value' => 10],
            ['name' => '精品购', 'value' => 11],
            ['name' => '每日购', 'value' => 12]
        ];
        foreach ($item['option'] as $key => $value) {
            if (isset($value['n_ctrl']) && (int)$grant[$value['n_ctrl']] !== 1) {
                unset($item['option'][$key]);
            }
            if (isset($value['r_ctrl']) && (int)$grant[$value['r_ctrl']] === 1) {
                unset($item['option'][$key]);
            }
        }
        $linkOption[] = $item;

        // 搜索商品
        $item = [];
        $item['name'] = '搜索商品';
        $item['type'] = 13;
        $item['input_type'] = 'text';
        $item['tips'] = '请输入商品关键字';
        $linkOption[] = $item;

        // 分类商品
        $item = [];
        $item['name'] = '分类商品';
        $item['type'] = 14;
        $item['input_type'] = 'select';
        $item['tips'] = '请选择商品分类';
        $item['option'] = [
            ['name' => $item['tips'], 'value' => '']
        ];
        // 获取分类 商城和子店不同
        $model = strpos('02', $storeType . '') === false ? D('GoodsClass') : D('MallGoodClass');
        $id = strpos('02', $storeType . '') === false ? $storeId : $channelId;
        $result = $model->getFirstLevelClass($id, 1, 0, [], 3);
        $option = $result['data'];
        foreach ($option as $key => $value) {
            $i = [];
            $i['name'] = $value['class_name'];
            $i['value'] = $value['class_id'];
            $item['option'][] = $i;
        }
        $linkOption[] = $item;

        // 自定义链接
        $item = [];
        $item['name'] = '自定义链接';
        $item['type'] = 1;
        $item['input_type'] = 'text';
        $item['tips'] = '链接必须带必须带http://';
        $linkOption[] = $item;

        // 单件商品
        $item = [];
        $item['name'] = '单件商品';
        $item['type'] = 15;
        $item['input_type'] = 'text';
        $item['tips'] = '请输入商品ID';
        $item['url'] = '/admin.php?m=Service&c=Goods&a=goods_list';
        $linkOption[] = $item;

        // 联盟店铺
        $item = [];
        $item['name'] = '联盟店铺';
        $item['type'] = 16;
        $item['input_type'] = 'select';
        $item['tips'] = '请选择联盟店铺';
        $item['option'] = [
            ['name' => $item['tips'], 'value' => '']
        ];
        // 查找联盟店铺列表
        $model = D('StoreUnion');
        $result = $model->getUnionStoreList($storeId);
        $option = $result['data'];
        // 有联盟才有这个跳转方式
        if (!empty($option)) {
            foreach ($option as $key => $value) {
                $i = [];
                $i['name'] = $value['store_name'];
                $i['value'] = $value['member_name'];
                $item['option'][] = $i;
            }
            $linkOption[] = $item;
        }


        // 单条公告
        $item = [];
        $item['name'] = '单条公告';
        $item['type'] = 17;
        $item['input_type'] = 'text';
        $item['tips'] = '请输入公告ID';
        $item['url'] = '/admin.php?m=Service&c=Notice&a=notice_list';
        $linkOption[] = $item;

        // 单条资讯
        $item = [];
        $item['name'] = '单条资讯';
        $item['type'] = 18;
        $item['input_type'] = 'text';
        $item['tips'] = '请输入资讯ID';
        $item['url'] = '/admin.php?m=Service&c=News&a=news_list_zx';
        $linkOption[] = $item;

        // 标签商品
        $item = [];
        $item['name'] = '标签商品';
        $item['type'] = 19;
        $item['input_type'] = 'select';
        $item['tips'] = '请选择商品标签';
        $item['option'] = [
            ['name' => $item['tips'], 'value' => '']
        ];
        // 查找商品标签
        $model = D('GoodsTag');
        $where = [];
        $where['tag_status'] = ['in', [1, 2]];
        $result = $model->getGoodsTag($storeId, $channelId, $where);
        $option = $result['data'];
        foreach ($option as $key => $value) {
            $i = [];
            $i['name'] = $value['tag_name'];
            $i['value'] = $value['tag_id'];
            $item['option'][] = $i;
        }
        $linkOption[] = $item;

        return $linkOption;
    }

    /**
     * @param int $storeId
     * @param int $channelId
     * @param int $storeType
     * @param array $grant
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-24 10:46:37
     * Desc: 获取单店导航的跳转方式
     * Update: 2017-10-24 10:46:38
     * Version: 1.0
     */
    static public function getAloneNavLinkOption($storeId = 0, $channelId = 0, $storeType = 0, $grant = [])
    {
        $linkOption = [['name' => '不做任何跳转', 'type' => 0]];

        // 系统功能
        $item = [];
        $item['name'] = '系统功能';
        $item['type'] = -1;
        $item['input_type'] = 'select';
        $item['tips'] = '请选择系统功能';
        // n_ctrl 表示权限中这个值为1就有这个选项 r_ctrl表示权限中这个值为1就没有这个选项
        $item['option'] = [
            ['name' => $item['tips'], 'value' => ''],
            ['name' => '积分商城', 'value' => 2, 'r_ctrl' => 'credit_hide'],
            ['name' => '摇奖品', 'value' => 3],
            ['name' => '我的收藏', 'value' => 4],
            ['name' => '直接支付', 'value' => 5],
            ['name' => '联盟商家', 'value' => 6],
            ['name' => '分组代理', 'value' => 7, 'n_ctrl' => 'partner_ctrl'],
            ['name' => '签到', 'value' => 8],
            ['name' => '服务中心', 'value' => 15],
            ['name' => '消息', 'value' => 16],
            ['name' => '活动公告', 'value' => 17],
        ];
        foreach ($item['option'] as $key => $value) {
            if (isset($value['n_ctrl']) && (int)$grant[$value['n_ctrl']] !== 1) {
                unset($item['option'][$key]);
            }
            if (isset($value['r_ctrl']) && (int)$grant[$value['r_ctrl']] === 1) {
                unset($item['option'][$key]);
            }
        }
        $linkOption[] = $item;

        // 搜索商品
        $item = [];
        $item['name'] = '搜索商品';
        $item['type'] = 9;
        $item['input_type'] = 'text';
        $item['tips'] = '请输入商品关键字';
        $linkOption[] = $item;

        // 分类商品
        $item = [];
        $item['name'] = '分类商品';
        $item['type'] = 10;
        $item['input_type'] = 'select';
        $item['tips'] = '请选择商品分类';
        $item['option'] = [
            ['name' => $item['tips'], 'value' => '']
        ];
        // 获取分类 商城和子店不同
        $model = strpos('02', $storeType) === false ? D('GoodsClass') : D('MallGoodClass');
        $id = strpos('02', $storeType) === false ? $storeId : $channelId;
        $result = $model->getFirstLevelClass($id, 1, 0, [], 3);
        $option = $result['data'];
        foreach ($option as $key => $value) {
            $i = [];
            $i['name'] = $value['class_name'];
            $i['value'] = $value['class_id'];
            $item['option'][] = $i;
        }
        $linkOption[] = $item;

        // 自定义链接
        $item = [];
        $item['name'] = '自定义链接';
        $item['type'] = 1;
        $item['input_type'] = 'text';
        $item['tips'] = '链接必须带必须带http://';
        $linkOption[] = $item;

        // 单件商品
        $item = [];
        $item['name'] = '单件商品';
        $item['type'] = 11;
        $item['input_type'] = 'text';
        $item['tips'] = '请输入商品ID';
        $item['url'] = '/admin.php?m=Service&c=Goods&a=goods_list';
        $linkOption[] = $item;

        // 联盟店铺
        $item = [];
        $item['name'] = '联盟店铺';
        $item['type'] = 12;
        $item['input_type'] = 'select';
        $item['tips'] = '请选择联盟店铺';
        $item['option'] = [
            ['name' => $item['tips'], 'value' => '']
        ];
        // 查找联盟店铺列表
        $model = D('StoreUnion');
        $result = $model->getUnionStoreList($storeId);
        $option = $result['data'];
        // 有联盟才有这个跳转方式
        if (!empty($option)) {
            foreach ($option as $key => $value) {
                $i = [];
                $i['name'] = $value['store_name'];
                $i['value'] = $value['member_name'];
                $item['option'][] = $i;
            }
            $linkOption[] = $item;
        }


        // 单条公告
        $item = [];
        $item['name'] = '单条公告';
        $item['type'] = 13;
        $item['input_type'] = 'text';
        $item['tips'] = '请输入公告ID';
        $item['url'] = '/admin.php?m=Service&c=Notice&a=notice_list';
        $linkOption[] = $item;

        // 单条资讯
        $item = [];
        $item['name'] = '单条资讯';
        $item['type'] = 14;
        $item['input_type'] = 'text';
        $item['tips'] = '请输入资讯ID';
        $item['url'] = '/admin.php?m=Service&c=News&a=news_list_zx';
        $linkOption[] = $item;

        // 标签商品
        $item = [];
        $item['name'] = '标签商品';
        $item['type'] = 18;
        $item['input_type'] = 'select';
        $item['tips'] = '请选择商品标签';
        $item['option'] = [
            ['name' => $item['tips'], 'value' => '']
        ];
        // 查找商品标签
        $model = D('GoodsTag');
        $where = [];
        $where['tag_status'] = ['in', [1, 2]];
        $result = $model->getGoodsTag($storeId, $channelId, $where);
        $option = $result['data'];
        foreach ($option as $key => $value) {
            $i = [];
            $i['name'] = $value['tag_name'];
            $i['value'] = $value['tag_id'];
            $item['option'][] = $i;
        }
        $linkOption[] = $item;

        return $linkOption;
    }


    /**
     * @param int $type
     * @param string $param
     * @param int $storeType
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-24 14:12:34
     * Desc: 获取广告跳转方式的描述
     * Update: 2017-10-24 14:12:35
     * Version: 1.0
     */
    static public function getAdLinkName($type = 0, $param = '', $storeType = 0)
    {
        $typeName = '';
        $desc = '';
        switch ((int)$type) {
            case 1:
                $typeName = '搜索商品';
                $desc = "关键字:$param";
                break;
            case 2:
                $typeName = '分类商品';
                // 商城和非商城
                if (strpos('02', $storeType . '') === false) {
                    $where = [];
                    $where['gc_id'] = $param;
                    $className = M('goods_class')->where($where)->getField('gc_name');
                } else {
                    $where = [];
                    $where['id'] = $param;
                    $className = M('mb_mallclass')->where($where)->getField('classname');
                }
                $desc = "分类名称:$className";
                break;
            case 3:
                $typeName = '自定义链接';
                $desc = "链接:$param";
                break;
            case 4:
                $typeName = '单件商品';
                $where = [];
                $where['goods_id'] = $param;
                $goodsName = M('goods')->where($where)->getField('goods_name');
                $desc = "商品名称:$goodsName";
                break;
            case 5:
                $typeName = '联盟店铺';
                $where = [];
                $where['member_name'] = $param;
                $storeName = M('store')->where($where)->getField('store_name');
                $desc = "店铺名称:$storeName";
                break;
            case 6:
                $typeName = '单条公告';
                $where = [];
                $where['notice_id'] = $param;
                $noticeTitle = M('mb_notice')->where($where)->getField('title');
                $desc = "标题:$noticeTitle";
                break;
            case 7:
                $typeName = '单条资讯';
                $where = [];
                $where['newsid'] = $param;
                $newsTitle = M('newslist')->where($where)->getField('title');
                $desc = "标题:$newsTitle";
                break;
            case 8:
                break;
            case 9:
                $typeName = '系统功能';
                $desc = "摇一摇";
                break;
            case 10:
                $typeName = '系统功能';
                $desc = "积分商城";
                break;
            case 11:
                $typeName = '系统功能';
                $desc = "我的奖/礼品";
                break;
            case 12:
                $typeName = '系统功能';
                $desc = "我的优惠券";
                break;
            case 13:
                $typeName = '系统功能';
                $desc = "服务中心";
                break;
            case 14:
                $typeName = '系统功能';
                $desc = "市场公告";
                break;
            case 15:
                $typeName = '系统功能';
                $desc = "我的收藏";
                break;
            case 16:
                $typeName = '系统功能';
                $desc = "签到";
                break;
            case 17:
                $typeName = '系统功能';
                $desc = "地图";
                break;
            case 18:
                $typeName = '系统功能';
                $desc = "精划算";
                break;
            case 19:
                $typeName = '系统功能';
                $desc = "逛商铺";
                break;
            case 20:
                $typeName = '系统功能';
                $desc = "我的足迹";
                break;
            case 21:
                $typeName = '系统功能';
                $desc = "找好店";
                break;
            case 22:
                $typeName = '系统功能';
                $desc = "精品购";
                break;
            case 23:
                $typeName = '系统功能';
                $desc = "每日购";
                break;
            case 24:
                $typeName = '系统功能';
                $desc = "直接付款";
                break;
            case 25:
                $typeName = '系统功能';
                $desc = "联盟商家";
                break;
            case 26:
                $typeName = '系统功能';
                $desc = "代理分组";
                break;
            case 27:
                $typeName = '标签商品';
                $where = [];
                $where['tag_id'] = $param;
                $tagName = M('goods_tag')->where($where)->getField('tag_name');
                $desc = "标签:$tagName";
                break;
            default:
                $typeName = '不做任何跳转';
                $desc = '不做任何跳转';
                break;
        }
        return ['name' => $typeName, 'desc' => $desc];
    }

    /**
     * @param int $type
     * @param string $param
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-24 14:31:50
     * Desc: 获取商城导航的跳转方式描述
     * Update: 2017-10-24 14:31:51
     * Version: 1.0
     */
    static public function getMallNavLinkName($type = 0, $param = '')
    {
        switch ((int)$type) {
            case 1:
                $typeName = '网址';
                $desc = "链接:$param";
                break;
            case 2:
                $typeName = '系统功能';
                $desc = "精划算";
                break;
            case 3:
                $typeName = '系统功能';
                $desc = "逛商铺";
                break;
            case 4:
                $typeName = '系统功能';
                $desc = "地图";
                break;
            case 5:
                $typeName = '系统功能';
                $desc = "我的足迹";
                break;
            case 6:
                $typeName = '系统功能';
                $desc = "市场公告";
                break;
            case 7:
                $typeName = '系统功能';
                $desc = "摇奖品";
                break;
            case 8:
                $typeName = '系统功能';
                $desc = "积分商城";
                break;
            case 9:
                $typeName = '系统功能';
                $desc = "服务中心";
                break;
            case 10:
                $typeName = '系统功能';
                $desc = "找好店";
                break;
            case 11:
                $typeName = '系统功能';
                $desc = "精品购";
                break;
            case 12:
                $typeName = '系统功能';
                $desc = "每日购";
                break;
            case 13:
                $typeName = "搜索商品";
                $desc = "关键字:$param";
                break;
            case 14:
                $typeName = '分类商品';
                $where = [];
                $where['id'] = $param;
                $className = M('mb_mallclass')->where($where)->getField('classname');
                $desc = "分类名称:$className";
                break;
            case 15:
                $typeName = '单件商品';
                $where = [];
                $where['goods_id'] = $param;
                $goodsName = M('goods')->where($where)->getField('goods_name');
                $desc = "商品名称:$goodsName";
                break;
            case 16:
                $typeName = '联盟店铺';
                $where = [];
                $where['member_name'] = $param;
                $storeName = M('store')->where($where)->getField('store_name');
                $desc = "店铺名称:$storeName";
                break;
            case 17:
                $typeName = '单条公告';
                $where = [];
                $where['notice_id'] = $param;
                $noticeTitle = M('mb_notice')->where($where)->getField('title');
                $desc = "标题:$noticeTitle";
                break;
            case 18:
                $typeName = '单条资讯';
                $where = [];
                $where['newsid'] = $param;
                $newsTitle = M('newslist')->where($where)->getField('title');
                $desc = "标题:$newsTitle";
                break;
            case 19:
                $typeName = '标签商品';
                $where = [];
                $where['tag_id'] = $param;
                $tagName = M('goods_tag')->where($where)->getField('tag_name');
                $desc = "标签:$tagName";
                break;
            default:
                $typeName = '不做任何跳转';
                $desc = '不做任何跳转';
                break;
        }
        return ['name' => $typeName, 'desc' => $desc];
    }

    static public function getAloneNavLinkName($type = 0, $param = '')
    {
        switch ((int)$type) {
            case 1:
                $typeName = '网址';
                $desc = "链接:$param";
                break;
            case 2:
                $typeName = '系统功能';
                $desc = "积分商城";
                break;
            case 3:
                $typeName = '系统功能';
                $desc = "摇奖品";
                break;
            case 4:
                $typeName = '系统功能';
                $desc = "我的收藏";
                break;
            case 5:
                $typeName = '系统功能';
                $desc = "直接支付";
                break;
            case 6:
                $typeName = '系统功能';
                $desc = "联盟商家";
                break;
            case 7:
                $typeName = '系统功能';
                $desc = "分组代理";
                break;
            case 8:
                $typeName = '系统功能';
                $desc = "签到";
                break;
            case 9:
                $typeName = "搜索商品";
                $desc = "关键字:$param";
                break;
            case 10:
                $typeName = '分类商品';
                $where = [];
                $where['gc_id'] = $param;
                $className = M('goods_class')->where($where)->getField('gc_name');
                $desc = "分类名称:$className";
                break;
            case 11:
                $typeName = '单件商品';
                $where = [];
                $where['goods_id'] = $param;
                $goodsName = M('goods')->where($where)->getField('goods_name');
                $desc = "商品名称:$goodsName";
                break;
            case 12:
                $typeName = '联盟店铺';
                $where = [];
                $where['member_name'] = $param;
                $storeName = M('store')->where($where)->getField('store_name');
                $desc = "店铺名称:$storeName";
                break;
            case 13:
                $typeName = '单条公告';
                $where = [];
                $where['notice_id'] = $param;
                $noticeTitle = M('mb_notice')->where($where)->getField('title');
                $desc = "标题:$noticeTitle";
                break;
            case 14:
                $typeName = '单条资讯';
                $where = [];
                $where['newsid'] = $param;
                $newsTitle = M('newslist')->where($where)->getField('title');
                $desc = "标题:$newsTitle";
                break;
            case 15:
                $typeName = '系统功能';
                $desc = "服务中心";
                break;
            case 16:
                $typeName = '系统功能';
                $desc = "消息";
                break;
            case 17:
                $typeName = '系统功能';
                $desc = "活动公告";
                break;
            case 18:
                $typeName = '标签商品';
                $where = [];
                $where['tag_id'] = $param;
                $tagName = M('goods_tag')->where($where)->getField('tag_name');
                $desc = "标签:$tagName";
                break;
            default:
                $typeName = '不做任何跳转';
                $desc = '不做任何跳转';
                break;
        }
        return ['name' => $typeName, 'desc' => $desc];
    }

    /**
     * @param int $type
     * @param string $param
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-24 22:19:50
     * Desc: 检查广告跳转方式的参数
     * Update: 2017-10-24 22:19:52
     * Version: 1.0
     */
    static public function checkAdLinkType($type = 0, $param = '', $storeId = 0)
    {
        switch ((int)$type) {
            case 1:
                if (empty($param)) return getReturn(-1, '请输入搜索关键字');
                break;
            case 2:
                // 商城和非商城
                $storeType = self::getStoreType($storeId);
                if (strpos('02', $storeType . '') === false) {
                    $where = [];
                    $where['gc_id'] = $param;
                    $count = M('goods_class')->where($where)->count();
                    if ($count <= 0) return getReturn(-1, '选择的商品分类已失效');
                } else {
                    $where = [];
                    $where['id'] = $param;
                    $count = M('mb_mallclass')->where($where)->count();
                    if ($count <= 0) return getReturn(-1, '选择的商品分类已失效');
                }
                break;
            case 3:
                if (strpos($param, 'http') === false) return getReturn(-1, '输入的链接必须带http://');
                break;
            case 4:
                $where = [];
                $where['goods_id'] = $param;
                $count = M('goods')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的商品已失效');
                break;
            case 5:
                $where = [];
                $where['member_name'] = $param;
                $count = M('store')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的店铺已失效');
                break;
            case 6:
                $where = [];
                $where['notice_id'] = $param;
                $count = M('mb_notice')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的公告已失效');
                break;
            case 7:
                $where = [];
                $where['newsid'] = $param;
                $count = M('newslist')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的资讯已失效');
                break;
            case 8:
            case 9:
            case 10:
            case 11:
            case 12:
            case 13:
            case 14:
            case 15:
            case 16:
            case 17:
            case 18:
            case 19:
            case 20:
            case 21:
            case 22:
            case 23:
            case 24:
            case 25:
            case 26:
                break;
            case 27:
                $where = [];
                $where['tag_id'] = $param;
                $count = M('goods_tag')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的标签已失效');
                break;
            default:
//                return getReturn(-1, '请选择跳转方式');
                break;
        }
        return getReturn(200, '');
    }

    /**
     * @param int $type
     * @param string $param
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-25 02:09:53
     * Desc: 检查商城APP导航跳转方式
     * Update: 2017-10-25 02:09:54
     * Version: 1.0
     */
    static public function checkMallNavLinkType($type = 0, $param = '')
    {
        switch ((int)$type) {
            case 1:
                if (strpos($param, 'http') === false) return getReturn(-1, '输入的链接必须带http://');
                break;
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 10:
            case 11:
            case 12:
                break;
            case 13:
                if (empty($param)) return getReturn(-1, '请输入搜索关键字');
                break;
            case 14:
                $where = [];
                $where['id'] = $param;
                $count = M('mb_mallclass')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的商品分类已失效');
                break;
            case 15:
                $where = [];
                $where['goods_id'] = $param;
                $count = M('goods')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的商品已失效');
                break;
            case 16:
                $where = [];
                $where['member_name'] = $param;
                $count = M('store')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的店铺已失效');
                break;
            case 17:
                $where = [];
                $where['notice_id'] = $param;
                $count = M('mb_notice')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的公告已失效');
                break;
            case 18:
                $where = [];
                $where['newsid'] = $param;
                $count = M('newslist')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的资讯已失效');
                break;
            case 19:
                $where = [];
                $where['tag_id'] = $param;
                $count = M('goods_tag')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的标签已失效');
                break;
            default:
                break;
        }
        return getReturn(200, '');
    }

    /**
     * @param int $type
     * @param string $param
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-25 02:09:43
     * Desc: 检查单店APP导航跳转方式
     * Update: 2017-10-25 02:09:44
     * Version: 1.0
     */
    static public function checkAloneNavLinkType($type = 0, $param = '')
    {
        switch ((int)$type) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
                break;
            case 9:
                if (empty($param)) return getReturn(-1, '请输入搜索关键字');
                break;
            case 10:
                $where = [];
                $where['gc_id'] = $param;
                $count = M('goods_class')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的商品分类已失效');
                break;
            case 11:
                $where = [];
                $where['goods_id'] = $param;
                $count = M('goods')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的商品已失效');
                break;
            case 12:
                $where = [];
                $where['member_name'] = $param;
                $count = M('store')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的店铺已失效');
                break;
            case 13:
                $where = [];
                $where['notice_id'] = $param;
                $count = M('mb_notice')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的公告已失效');
                break;
            case 14:
                $where = [];
                $where['newsid'] = $param;
                $count = M('newslist')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的资讯已失效');
                break;
            case 15:
            case 16:
            case 17:
                break;
            case 18:
                $where = [];
                $where['tag_id'] = $param;
                $count = M('goods_tag')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '选择的标签已失效');
                break;
            default:
                break;
        }
        return getReturn(200, '');
    }

    /**
     * @param int $storeId
     * @return mixed ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-24 21:56:48
     * Desc: 获取商家的类型
     * Update: 2017-10-24 21:56:49
     * Version: 1.0
     */
    static public function getStoreType($storeId = 0)
    {
        $model = D('Store');
        $info = $model->getStoreInfo($storeId)['data'];
        $channelType = (int)$info['channel_type'];
        $mainStore = (int)$info['main_store'];
        switch ($channelType) {
            case 0:
                $storeType = self::NORMAL_STORE;
                break;
            case 2:
                $storeType = $mainStore == 1 ? self::MALL_MAIN_STORE : self::MALL_CHILD_STORE;
                break;
            case 3:
                $storeType = self::ALONE_STORE;
                break;
            case 4:
                $storeType = $mainStore == 1 ? self::CHAIN_MAIN_STORE : self::CHAIN_MAIN_STORE;
                break;
            default:
                $storeType = 5;
                break;
        }
        return $storeType;
    }

    /**
     * @param int $channelType
     * @param int $mainStore
     * @return mixed ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 根据渠道类型和main_store获取商家类型
     * Date: 2017-11-02 08:26:54
     * Update: 2017-11-02 08:26:55
     * Version: 1.0
     */
    static public function getStoreTypeByChannel($channelType = 0, $mainStore = 0)
    {
        switch ((int)$channelType) {
            case 0:
                $storeType = self::NORMAL_STORE;
                break;
            case 2:
                $storeType = $mainStore == 1 ? self::MALL_MAIN_STORE : self::MALL_CHILD_STORE;
                break;
            case 3:
                $storeType = self::ALONE_STORE;
                break;
            case 4:
                $storeType = $mainStore == 1 ? self::CHAIN_MAIN_STORE : self::CHAIN_MAIN_STORE;
                break;
            default:
                $storeType = 5;
                break;
        }
        return $storeType;
    }

    /**
     * @param int $type
     * @param int $storeType
     * @return mixed ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 广告跳转方式转换成action
     * Date: 2017-11-02 08:33:49
     * Update: 2017-11-02 08:33:50
     * Version: 1.0
     */
    static public function adTypeToAction($type = 0, $storeType = 0)
    {
        switch ((int)$type) {
            case 1:
                $action = 'search_goods';
                break;
            case 2:
                $action = strpos('02', $storeType . '') === false ? 'child_class_goods' : 'mall_class_goods';
                break;
            case 3:
                $action = 'web_url';
                break;
            case 4:
                $action = 'one_goods';
                break;
            case 5:
                $action = 'one_union_store';
                break;
            case 6:
                $action = 'one_notice';
                break;
            case 7:
                $action = 'one_news';
                break;
            case 9:
                $action = 'shake_prize';
                break;
            case 10:
                $action = 'points_mall';
                break;
            case 11:
                $action = 'my_prize_gift';
                break;
            case 12:
                $action = 'my_coupon';
                break;
            case 13:
                $action = 'service_center';
                break;
            case 14:
                $action = strpos('02', $storeType . '') === false ? 'store_notice_list' : 'mall_notice_list';
                break;
            case 15:
                $action = 'my_collection';
                break;
            case 16:
                $action = 'sign_in';
                break;
            case 17:
                $action = 'map';
                break;
            case 18:
                $action = 'good_deal';
                break;
            case 19:
                $action = 'go_stores';
                break;
            case 20:
                $action = 'my_footprint';
                break;
            case 21:
                $action = 'find_good_store';
                break;
            case 22:
                $action = 'boutique_shopping';
                break;
            case 23:
                $action = 'day_shopping';
                break;
            case 24:
                $action = 'direct_payment';
                break;
            case 26:
                $action = 'apply_for_agent';
                break;
            case 27:
                $action = 'tag_goods';
                break;
            default:
                $action = 'no_action';
                break;
        }
        return $action;
    }

    /**
     * @param int $type
     * @param int $storeType
     * @return mixed ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 导航转换action
     * Date: 2017-11-03 00:27:17
     * Update: 2017-11-03 00:27:18
     * Version: 1.0
     */
    static public function navTypeToAction($type = 0, $storeType = 0)
    {
        switch ((int)$type) {
            case 1:
                $action = 'web_url';
                break;
            case 2:
                $action = strpos('02', $storeType . '') === false ? 'points_mall' : 'good_deal';
                break;
            case 3:
                $action = strpos('02', $storeType . '') === false ? 'shake_prize' : 'go_stores';
                break;
            case 4:
                $action = strpos('02', $storeType . '') === false ? 'my_collection' : 'map';
                break;
            case 5:
                $action = strpos('02', $storeType . '') === false ? 'direct_payment' : 'my_footprint';
                break;
            case 6:
                $action = strpos('02', $storeType . '') === false ? 'no_action' : 'mall_notice_list';
                break;
            case 7:
                $action = strpos('02', $storeType . '') === false ? 'apply_for_agent' : 'shake_prize';
                break;
            case 8:
                $action = strpos('02', $storeType . '') === false ? 'sign_in' : 'points_mall';
                break;
            case 9:
                $action = strpos('02', $storeType . '') === false ? 'search_goods' : 'service_center';
                break;
            case 10:
                $action = strpos('02', $storeType . '') === false ? 'child_class_goods' : 'find_good_store';
                break;
            case 11:
                $action = strpos('02', $storeType . '') === false ? 'one_goods' : 'boutique_shopping';
                break;
            case 12:
                $action = strpos('02', $storeType . '') === false ? 'one_union_store' : 'day_shopping';
                break;
            case 13:
                $action = strpos('02', $storeType . '') === false ? 'one_notice' : 'search_goods';
                break;
            case 14:
                $action = strpos('02', $storeType . '') === false ? 'one_news' : 'mall_class_goods';
                break;
            case 15:
                $action = strpos('02', $storeType . '') === false ? 'service_center' : 'one_goods';
                break;
            case 16:
                $action = strpos('02', $storeType . '') === false ? 'communication' : 'one_union_store';
                break;
            case 17:
                $action = strpos('02', $storeType . '') === false ? 'store_notice_list' : 'one_notice';
                break;
            case 18:
                $action = strpos('02', $storeType . '') === false ? 'tag_goods' : 'one_news';
                break;
            case 19:
                $action = 'tag_goods';
                break;
            default:
                $action = 'no_action';
                break;
        }
        return $action;
    }

    static public function getMallClassIdWithLevel($classId = '')
    {
        $model = D('MallGoodClass');
        $field = [
            'a.id', 'b.id pid', 'c.id ppid'
        ];
        $where = [];
        $where['a.id'] = $classId;
        $join = [
            'LEFT JOIN __MB_MALLCLASS__ b ON a.pid = b.id',
            'LEFT JOIN __MB_MALLCLASS__ c ON b.pid = c.id',
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['where'] = $where;
        $options['join'] = $join;
        $data = $model->selectRow($options);
        $level = [];
        if (!empty($data['ppid'])) {
            $level[] = $data['ppid'];
        }
        if (!empty($data['pid'])) {
            $level[] = $data['pid'];
        }
        $level[] = $classId;
        return implode('|', $level);
    }

    /**
     * @param string $action
     * @param string $param
     * @param int $storeId
     * @param int $memberId
     * @return mixed ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取跳转方式的url
     * Date: 2017-11-03 00:28:00
     * Update: 2017-11-03 00:28:00
     * Version: 1.0
     */
    static public function getLinkTypeUrl($action = '', $param = '', $storeId = 0, $memberId = 0)
    {
        if ($action === 'system') {
            $action = $param;
        }
        if (empty($memberId)) {
            $memberId = session('member_id');
        }
        $url = 'javascript:;';
        switch ($action) {
            case 'no_action':
                $url = 'javascript:;';
                break;
            case 'good_deal':
                // 精品购
                $url = "/index.php?m=Service&c=Goods&a=mall_goods_list&tp=1&se={$storeId}";
                break;
            case 'go_stores':
                // 逛商铺
                $url = "/index.php?m=Service&c=MallStore&a=store_classify&se={$storeId}";
                break;
            case 'map':
            case 'mall_map':
                // 地图
                $url = "/index.php?m=Service&c=Store&a=storemap&se={$storeId}";
                break;
            case 'my_footprint':
                // 我的足迹
                $url = "/index.php?m=Service&c=Goods&a=mall_goods_list&tp=9&se={$storeId}";
                break;
            case 'mall_notice_list':
            case 'store_notice_list':
                // '市场公告' 活动公告
                $url = "/index.php?m=Service&c=Bulletin&a=bulletinlist&se={$storeId}";
                break;
            case 'shake_prize':
                // 摇奖品
                $url = "javascript:layer.msg('功能暂未开放');";
                break;
            case 'points_mall':
                // 积分商城
                $url = "/index.php?m=Service&c=Credit&a=creditstore&se={$storeId}";
                break;
            case 'service_center':
                // 服务中心
                $url = "/index.php?m=Service&c=Store&a=serviceCenter&se={$storeId}";
                break;
            case 'find_good_store':
                // 找好店
                $url = "/index.php?m=Service&c=Goods&a=mall_goods_list&tp=3&se={$storeId}";
                break;
            case 'boutique_shopping':
                // 精品购
                $url = "/index.php?m=Service&c=Goods&a=mall_goods_list&tp=4&se={$storeId}";
                break;
            case 'day_shopping':
                // 每日购
                $url = "/index.php?m=Service&c=Goods&a=mall_goods_list&tp=5&se={$storeId}";
                break;
            case 'my_collection':
                // 我的收藏
                $url = "/index.php?m=Service&c=Goods&a=collection&se={$storeId}";
                break;
            case 'my_prize_gift':
                // '我的奖/礼品';
                $url = "/index.php?m=Service&c=Prize&a=index&se={$storeId}";
                break;
            case 'my_coupon':
                // '我的优惠券';
                $suffix = '';
                if (!empty($memberId)) {
                    $suffix = "&f={$memberId}";
                }
                $url = "/index.php?m=Service&c=Coupon&a=mycoupon&se={$storeId}{$suffix}";
                break;
            case 'communication':
                // 消息
                $url = "/index.php?c=Message&a=customerList&se={$storeId}&f={$memberId}";
                break;
            case 'sign_in':
                // '签到';
                $url = "/index.php?c=Marketing&a=dailyMarket&se={$storeId}&f={$memberId}";
                break;
            case 'direct_payment':
                // '直接付款';
                $url = "/index.php?m=Service&c=FacePay&a=index&se={$storeId}";
                break;
            case 'apply_for_agent':
                // '申请代理';
                $url = "/index.php?m=Service&c=Partner&a=index&se={$storeId}";
                break;
            case 'search_goods':
                // '搜索商品';
                $url = "/index.php?c=Goods&a=zhuanqu_list&search={$param}&title={$param}&se={$storeId}";
                break;
            case 'mall_search_goods':
                // 搜搜商品
                $url = "/index.php?m=Service&c=MallStore&a=Search_page&se={$storeId}&keyword={$param}";
                break;
            case 'mall_class_goods':
                // 分类商品
                if (empty($param)) {
                    break;
                }
                $id = explode('|', $param);
                $level = count($id);
                if (!in_array((int)$level, [1, 2, 3])) $level = 1;
                $classId = $id[$level - 1];
                $model = D('MallGoodClass');
                $where = [];
                $where['pid'] = $classId;
                $where['isdelete'] = 0;
                $count = $model->queryCount(['where' => $where]);
                // 没有底级分类就跳转到商品页
                if (empty($count)) {
                    $url = "/index.php?m=Service&c=MallStore&a=goodsList&level={$level}&class_id={$classId}&se={$storeId}";
                } else {
                    $url = "/index.php?m=Service&c=MallStore&a=goodsClassify&level={$level}&class_id={$classId}&se={$storeId}";
                }
                break;
            case 'child_class_goods':
                if (empty($param)) {
                    break;
                }
                $id = explode('|', $param);
                $level = count($id);
                if (!in_array((int)$level, [1, 2, 3])) $level = 1;
                $classId = $id[$level - 1];
                $model = D('GoodsClass');
                $where = [];
                $where['gc_id'] = $classId;
                $options = [];
                $options['where'] = $where;
                $title = $model->queryField($options, 'gc_name')['data'];
                $url = "/index.php?c=Goods&a=zhuanqu_list&gc_id={$param}&title={$title}&se={$storeId}";
                break;
            case 'web_url':
                // 自定义链接
                if (empty($param)) {
                    break;
                }
                $http = '';
                if (strpos($param, 'http') === false) $http = 'http://';
                $url = "{$http}{$param}";
                break;
            case 'one_goods':
                // 单件商品
                if (empty($param)) {
                    break;
                }
                $url = "/index.php?m=Service&c=Goods&a=goods_detail&id={$param}&se={$storeId}";
                break;
            case 'one_union_store':
                // 联盟店铺
                if (empty($param)) {
                    break;
                }
                $where = [];
                $where['member_name'] = $param;
                $storeId = D('Store')->queryField(['where' => $where], 'store_id')['data'];
                $url = "/index.php?se={$storeId}";
                break;
            case 'one_notice':
                // 单条公告
                if (empty($param)) {
                    break;
                }
                $url = "/index.php?m=Service&c=Bulletin&a=bulletindetail&notice_id={$param}&se={$storeId}";
                break;
            case 'one_news':
                // 单条资讯
                if (empty($param)) {
                    break;
                }
                $url = "/index.php?m=Service&c=MallNews&a=newsdetail&newid={$param}&se={$storeId}";
                break;
            case 'tag_goods':
                // 标签商品
                if (empty($param)) {
                    break;
                }
                $where = [];
                $where['tag_id'] = $param;
                $tagName = D('GoodsTag')->queryField(['where' => $where], 'tag_name')['data'];
                $url = "/index.php?c=Goods&a=tagGoods&tag_id={$param}&title={$tagName}&se={$storeId}";
                break;
            case 'special_offer':
                // 今日特价
                $url = "/index.php?m=Service&c=Goods&a=mall_goods_list&tp=6&se={$storeId}";
                break;
            case 'hot_sale_goods':
                // 热卖商品
                $url = "/index.php?m=Service&c=Goods&a=mall_goods_list&tp=7&se={$storeId}";
                break;
            case 'coupons_center':
                // 领券中心
                $url = "/index.php?c=Coupon&a=couponCenter&se={$storeId}";
                break;
            default:
                $url = 'javascript:;';
                break;
        }
        if (!empty($memberId) && strpos($url, '&f=') === false && strpos($url, 'javascript') === false) {
            if (strpos($url, '?') === false) {
                $url .= "?f={$memberId}";
            } else {
                $url .= "&f={$memberId}";
            }
        }
        return $url;
    }

    /**
     * @param string $action
     * @param int $storeType
     * @return mixed ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: action转换type
     * Date: 2017-11-03 16:24:31
     * Update: 2017-11-03 16:24:34
     * Version: 1.0
     */
    static public function actionToType($action = '', $storeType = 0)
    {
        $storeType = $storeType . '';
        switch ($action) {
            case 'no_action':
                $type = 0;
                break;
            case 'good_deal':
                // 划算
                $type = 2;
                break;
            case 'go_stores':
                // 逛商铺
                $type = 3;
                break;
            case 'map':
            case 'mall_map':
                // 地图
                $type = 4;
                break;
            case 'my_footprint':
                // 我的足迹
                $type = 5;
                break;
            case 'mall_notice_list':
                $type = 6;
                break;
            case 'store_notice_list':
                // 活动公告
                $type = 17;
                break;
            case 'shake_prize':
                // 摇奖品
                $type = strpos('02', $storeType) === false ? 3 : 7;
                break;
            case 'points_mall':
                // 积分商城
                $type = strpos('02', $storeType) === false ? 2 : 8;
                break;
            case 'service_center':
                // 服务中心
                $type = strpos('02', $storeType) === false ? 15 : 9;
                break;
            case 'find_good_store':
                // 找好店
                $type = 10;
                break;
            case 'boutique_shopping':
                // 精品购
                $type = 11;
                break;
            case 'day_shopping':
                // 每日购
                $type = 12;
                break;
            case 'my_collection':
                // 我的收藏
                $type = 4;
                break;
            case 'my_prize_gift':
                // '我的奖/礼品';
                $type = 11;
                break;
            case 'my_coupon':
                // '我的优惠券';
                $type = 12;
                break;
            case 'communication':
                // 消息
                $type = 16;
                break;
            case 'sign_in':
                // '签到';
                $type = 8;
                break;
            case 'direct_payment':
                // '直接付款';
                $type = 5;
                break;
            case 'apply_for_agent':
                // '申请代理';
                $type = 7;
                break;
            case 'search_goods':
                // '搜索商品';
                $type = strpos('02', $storeType) === false ? 9 : 13;
                break;
            case 'mall_search_goods':
                $type = 13;
                break;
            case 'mall_class_goods':
                // 分类商品
                $type = 15;
                break;
            case 'child_class_goods':
                $type = 10;
                break;
            case 'web_url':
                // 自定义链接
                $type = 1;
                break;
            case 'one_goods':
                // 单件商品
                $type = strpos('02', $storeType) === false ? 11 : 15;
                break;
            case 'one_union_store':
                // 联盟店铺
                $type = strpos('02', $storeType) === false ? 12 : 16;
                break;
            case 'one_notice':
                // 单条公告
                $type = strpos('02', $storeType) === false ? 13 : 17;
                break;
            case 'one_news':
                // 单条资讯
                $type = strpos('02', $storeType) === false ? 14 : 18;
                break;
            case 'tag_goods':
                // 标签商品
                $type = strpos('02', $storeType) === false ? 18 : 19;
                break;
            default:
                $type = 0;
                break;
        }
        return $type;
    }

    /**
     * @param string $action
     * @param string $param
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Desc: 获取跳转的标题
     * Date: 2017-11-13 23:23:18
     * Update: 2017-11-13 23:23:19
     * Version: 1.0
     */
    static public function getParamTitle($action = '', $param = '')
    {
        $title = '';
        switch ($action) {
            case 'good_deal':
                $title = '精划算';
                break;
            case 'go_stores':
                $title = '逛商铺';
                break;
            case 'map':
            case 'mall_map':
                $title = '地图';
                break;
            case 'my_footprint':
                $title = '我的足迹';
                break;
            case 'mall_notice_list':
                $title = '市场公告';
                break;
            case 'shake_prize':
                $title = '摇奖品';
                break;
            case 'points_mall':
                $title = '积分商城';
                break;
            case 'service_center':
                $title = '服务中心';
                break;
            case 'find_good_store':
                $title = '找好店';
                break;
            case 'boutique_shopping':
                $title = '精品购';
                break;
            case 'day_shopping':
                $title = '每日购';
                break;
            case 'my_collection':
                $title = '我的收藏';
                break;
            case 'my_prize_gift':
                $title = '我的奖/礼品';
                break;
            case 'my_coupon':
                $title = '我的优惠券';
                break;
            case 'communication':
                $title = '消息';
                break;
            case 'sign_in':
                $title = '每日任务';
                break;
            case 'direct_payment':
                $title = '直接付款';
                break;
            case 'store_notice_list':
                $title = '活动公告';
                break;
            case 'apply_for_agent':
                $title = '申请代理';
                break;
            case 'search_goods':
            case 'mall_search_goods':
                $title = "{$param}";
                break;
            case 'mall_class_goods':
                // 1|2|3 取最后一个
                $id = explode('|', $param);
                $index = count($id) - 1;
                $id = $id[$index];
                $where = [];
                $where['id'] = $id;
                $title = M('mb_mallclass')->where($where)->getField('classname');
                break;
            case 'child_class_goods':
                // 1|2|3 取最后一个
                $id = explode('|', $param);
                $index = count($id) - 1;
                $id = $id[$index];
                $where = [];
                $where['gc_id'] = $id;
                $title = M('goods_class')->where($where)->getField('gc_name');
                break;
            case 'one_goods':
                $where = [];
                $where['goods_id'] = $param;
                $title = M('goods')->where($where)->getField('goods_name');
                break;
            case 'one_union_store':
                $where = [];
                $where['member_name'] = $param;
                $title = M('store')->where($where)->getField('store_name');
                break;
            case 'one_notice':
                $where = [];
                $where['notice_id'] = $param;
                $title = M('mb_notice')->where($where)->getField('title');
                break;
            case 'one_news':
                $where = [];
                $where['newsid'] = $param;
                $title = M('newslist')->where($where)->getField('title');
                break;
            case 'tag_goods':
                $where = [];
                $where['tag_id'] = $param;
                $title = M('goods_tag')->where($where)->getField('tag_name');
                break;
            case 'coupons_center':
                $title = '领券中心';
                break;
            default:
                break;
        }
        return $title;
    }

    /**
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取调用API接口的token
     * Date: 2017-11-17 12:02:44
     * Update: 2017-11-17 12:02:45
     * Version: 1.0
     */
    static public function getApiToken()
    {
        $model = M('mb_user_token');
        return $model->getField('token');
    }

    /**
     * @param int $storeId
     * @param int $channelId
     * @param array $param
     * @return mixed ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 组装调用API的参数
     * Date: 2017-11-17 12:05:27
     * Update: 2017-11-17 12:05:28
     * Version: 1.0
     */
    static public function getApiParams($storeId = 0, $channelId = 0, $param = [])
    {
        $key = self::getApiToken();
        if (empty($key)) return false;
        $params = array(
            "user_type" => 'seller',
            "store_id" => $storeId,
            "key" => $key,
            "comchannel_id" => $channelId,
            "client" => "web"
        );
        foreach ($param as $k => $v) {
            $params[$k] = $v;
        }

        // 请求第一种方式
        $data = "";
        foreach ($params as $k => $v) {
            $data .= "$k=" . urlencode($v) . "&";
        }

        $data = substr($data, 0, -1);

        return $data;
    }

    /**
     * 检查数据集的系统功能的参数
     * @param $module
     * @param $dataset
     * @param $moduleIndex
     * @param $datasetIndex
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-05-16 10:06:49
     * Update: 2018-05-16 10:06:49
     * Version: 1.00
     */
    static public function checkDatasetSystemParam($module, $dataset, $moduleIndex, $datasetIndex)
    {
        if (empty($dataset['weburl'])) {
            switch ((int)$module['module_type']) {
                case ThemeGirdModel::MODULE_THEME:
                    return getReturn(-1, '请选择主题广告的系统功能');
                    break;
                case ThemeGirdModel::MODULE_TWO:
                    return getReturn(-1, "请选择位置-{$moduleIndex}的两列模块第{$datasetIndex}个的系统功能");
                    break;
                case ThemeGirdModel::MODULE_THREE:
                    return getReturn(-1, "请选择位置-{$moduleIndex}的三列模块第{$datasetIndex}个的系统功能");
                    break;
                case ThemeGirdModel::MODULE_FOUR:
                    return getReturn(-1, "请选择位置-{$moduleIndex}的四列模块第{$datasetIndex}个的系统功能");
                    break;
                case ThemeGirdModel::MODULE_ONE:
                    return getReturn(-1, "请选择位置-{$moduleIndex}的单列模块第{$datasetIndex}个的系统功能");
                    break;
                case ThemeGirdModel::MODULE_AD:
                    return getReturn(-1, "请选择轮播广告的系统功能");
                    break;
                default:
                    break;
            }
        }
        return getReturn(200, 'success');
    }

    /**
     * 获取模块相应位置的错误信息
     * @param $msg
     * @param $module
     * @param $moduleIndex
     * @param $datasetIndex
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-05-16 10:12:32
     * Update: 2018-05-16 10:12:32
     * Version: 1.00
     */
    static public function getModuleError($msg, $module, $moduleIndex, $datasetIndex)
    {
        switch ((int)$module['module_type']) {
            case ThemeGirdModel::MODULE_THEME:
                return getReturn(-1, "主题广告-{$msg}");
                break;
            case ThemeGirdModel::MODULE_TWO:
                return getReturn(-1, "位置-{$moduleIndex}的两列模块第{$datasetIndex}个-{$msg}");
                break;
            case ThemeGirdModel::MODULE_THREE:
                return getReturn(-1, "位置-{$moduleIndex}的三列模块第{$datasetIndex}个-{$msg}");
                break;
            case ThemeGirdModel::MODULE_FOUR:
                return getReturn(-1, "位置-{$moduleIndex}的四列模块第{$datasetIndex}个-{$msg}");
                break;
            case ThemeGirdModel::MODULE_ONE:
                return getReturn(-1, "位置-{$moduleIndex}的单列模块第{$datasetIndex}个-{$msg}");
                break;
            case ThemeGirdModel::MODULE_AD:
                return getReturn(-1, "轮播广告-{$msg}");
                break;
            default:
                break;
        }
        return getReturn(200, 'success');
    }

    /**
     * 检查商品模块的数据
     * @param $storeId
     * @param $module
     * @param $index
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-05-15 20:06:29
     * Update: 2018-05-15 20:06:29
     * Version: 1.00
     */
    static public function checkGoodsModule($storeId, $module, $index)
    {
        $storeType = self::getStoreType($storeId);
        switch ((int)$module['goods_data']['goods_source']) {
            case ThemeGirdModel::SOURCE_TAG_GOODS:
                $param = $module['goods_data']['tag_id'];
                if (empty($param)) {
                    return getReturn(406, "请选择位置-{$index} 商品模块的商品标签");
                }
                $where = [];
                $where['tag_id'] = $param;
                $count = M('goods_tag')->where($where)->count();
                if ($count <= 0) return getReturn(-1, '标签不存在');
                break;
            default:
                $param = $module['goods_data']['class_id'];
                if (empty($param)) {
                    return getReturn(406, "请选择位置-{$index} 商品模块的商品分类");
                }
                $id = explode('|', $param);
                $index = count($id) - 1;
                $id = $id[$index];
                if (isMall($storeType)) {
                    // 1|2|3 取最后一个
                    $where = [];
                    $where['id'] = $id;
                    $count = M('mb_mallclass')->where($where)->count();
                    if ($count <= 0) return getReturn(406, '选择的分类不存在');
                } else {
                    $where = [];
                    $where['gc_id'] = $id;
                    $count = M('goods_class')->where($where)->count();
                    if ($count <= 0) return getReturn(406, '选择的分类不存在');
                }
                break;
        }
        $goodsNum = $module['goods_data']['goods_num'];
        if (empty($goodsNum)) return getReturn(406, "请输入位置-{$index} 商品模块的商品数量");
        if ($goodsNum > 50) {
            return getReturn(406, "位置-{$index} 商品模块的商品数量最多50个");
        }
        return getReturn(200, 'success', $module);
    }

    /**
     * 获取商品模块的HTML代码
     * @param $storeId
     * @param $module
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-05-16 01:31:48
     * Update: 2018-05-16 01:31:48
     * Version: 1.00
     */
    static public function getModuleGoodsHTML($storeId, $module)
    {
        $html = '<div class="theme-goods swiper-list3">
  <ul class="swiper-wrapper">
    {$LI}
  </ul>
  <div class="swiper-button-prev"></div>
  <div class="swiper-button-next"></div>
</div>';
        $storeInfo = D('Store')->getStoreInfo($storeId)['data'];
        //  有价格才显示购物车按钮
        if ($module['goods_data']['show_goods_price'] == 1) {
            $price = '<p class="goods-price">' . $storeInfo['currency_symbol'] . '{$PRICE}</p><i class="myicon-cart-red"></i>';
        } else {
            $price = '';
        }
        if ($module['goods_data']['show_goods_name'] == 1) {
            $name = '<p class="goods-name">{$NAME}</p>';
        } else {
            $name = '';
        }
        $li = '<li class="swiper-slide">
      <a href="{$URL}">
        <div class="goods-img"><img data-src="{$GOODS_IMG}"></div>
        <div class="goods-info">
          {$NAME}
          {$PRICE}
        </div>
      </a>
    </li>';
        $modelGE = M('goods_extra');
        switch ((int)$module['goods_data']['goods_source']) {
            case ThemeGirdModel::SOURCE_TAG_GOODS:
                $tagId = $module['goods_data']['tag_id'];
                $where['tag_id'] = $tagId;
                $goodsIds = M('goods_tag_link')->where($where)->getField('goods_id', true);
                break;
            default:
                $classId = $module['goods_data']['class_id'];
                $id = explode('|', $classId);
                $level = count($id);
                $id = $id[$level - 1];
                $where = [];
                if (isMall($storeInfo['store_type'])) {
                    $where["mall_class_{$level}"] = $id;
                } else {
                    $where["goods_class_{$level}"] = $id;
                }
                $goodsIds = $modelGE->where($where)->getField('goods_id', true);
                break;
        }
        $lis = '';
        if (!empty($goodsIds)) {
            $where = [];
            $where['a.goods_id'] = ['in', $goodsIds];
            $where['a.goods_state'] = 1;
            $where['a.isdelete'] = 0;
            $list = M('goods')
                ->alias('a')
                ->field('a.goods_id,a.goods_name,a.store_id,a.goods_price,a.goods_image,a.goods_figure')
                ->where($where)
                ->order('a.sort DESC,a.top DESC')
                ->limit($module['goods_data']['goods_num'])
                ->select();
            $list = D('Goods')->initGoodsBeans($storeId, $list);
            foreach ($list as $goods) {
                $tempLi = str_replace('{$GOODS_IMG}', $goods['main_img'], $li);
                $tmpPrice = '';
                if (!empty($price)) {
                    $tmpPrice = str_replace('{$PRICE}', $goods['new_price'], $price);
                }
                $tmpName = '';
                if (!empty($name)) {
                    $tmpName = str_replace('{$NAME}', $goods['goods_name'], $name);
                }
                $tempLi = str_replace('{$NAME}', $tmpName, $tempLi);
                $tempLi = str_replace('{$PRICE}', $tmpPrice, $tempLi);
                $memberId = session('member_id') > 0 ? session('member_id') : 0;
                $url = U('Goods/goods_detail', ['id' => $goods['goods_id'], 'se' => $storeId, 'f' => $memberId]);
                $tempLi = str_replace('{$URL}', $url, $tempLi);
                $lis .= $tempLi;
            }
        }
        $html = str_replace('{$LI}', $lis, $html);
        return $html;
    }
}