<?php

namespace Common\Model;

/**
 * Class OpenStoreModel
 * User: hj
 * Date: 2017-10-26 23:51:53
 * Desc: 开户用的模型类
 * Update: 2017-10-26 23:51:56
 * Version: 1.0
 * @package Common\Model
 */
class OpenStoreModel extends BaseModel
{
    protected $tableName = 'store';

    protected $lastOptions;

    protected function _options_filter(&$options)
    {
        parent::_options_filter($options);
        $this->lastOptions = $options;
    }

    /**
     * @param int $storeId 商家ID
     * @param int $channelId 商家所在的渠道ID
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-27 00:40:52
     * Desc: 获取商家信息 以及 当前渠道的一些信息
     * Update: 2017-10-27 00:40:53
     * Version: 1.0
     */
    public function getStoreAndOpenStoreInfo($storeId = 0, $channelId = 0)
    {
        // 获取商家信息
        $where = [];
        $where['a.store_id'] = $storeId;
        $field = [
            'a.store_id,a.store_name,a.isshow,a.store_parenttype_id,a.store_childtype_id,a.store_type,a.proxy_brand_id',
            'a.store_address,a.lianxi_member_name,a.lianxi_member_tel,a.lianxi_id_number,a.store_remark,a.channel_id',
            'b.recommend_name,b.account_zhucehao,settlement,auto_accept_order',
            'c.member_name,c.member_tel,c.member_passwd'
        ];
        $field = implode(',', $field);
        $info = $this
            ->alias('a')
            ->field($field)
            ->join('__MB_ACCOUNT__ b ON b.store_id = a.store_id')
            ->join('__MEMBER__ c ON c.member_id = a.member_id')
            ->where($where)
            ->cache("openStore_{$storeId}")
            ->find();
        if (false === $info) return getReturn();
        // 解析品牌
        if (!empty($info)) {
            $info['proxy_brand_id'] = json_decode($info['proxy_brand_id'], 1);
            $brand = [];
            if (!empty($info['proxy_brand_id'])) {
                foreach ($info['proxy_brand_id'] as $key => $value) {
                    $brand[] = $value['brand_id'];
                }
                $info['proxy_brand_id'] = $brand;
            } else {
                $info['proxy_brand_id'] = [];
            }
            $info['member_passwd'] = '';
        }

        $channelId = empty($info['channel_id']) ? $channelId : $info['channel_id'];
        // 获取迅信号
        $result = D('XunXinNum')->getXXNum($channelId);
        if ($result['code'] !== 200) return $result;
        $xxNum = $result['data'];

        // 获取行业列表
        $result = D('Industry')->getIndustryList();
        if ($result['code'] !== 200) return $result;
        $indList = $result['data'];

        // 店铺分类
        $result = D('StoreType')->getStoreTypeList($channelId);
        if ($result['code'] !== 200) return $result;
        $storeType = $result['data']['list'];

        // 品牌
        $result = D('Brand')->getBrandList(0, $channelId);
        if ($result['code'] !== 200) return $result;
        $brandList = $result['data'];

        // 省市区
//        $result = D('Area')->getAreaList();
//        if ($result['code'] !== 200) return $result;
//        $area = $result['data'];

        // 获取提现设置
        $modelAC = D('AccountConfig');
        $result = $modelAC->getStoreAccountConfig($storeId);
        $default = [
            'account_type' => 0,
            'account_type_name' => '',
            'account_card_name' => '',
            'account_member_name' => '',
        ];
        $accountConfig = empty($result['data']) ? $default : $result['data'];
        // 获取银行列表
        $modelBank = D('Bank');
        $bankList = $modelBank->getBankList();
        $data = [];
        $data['store_info'] = array_merge($info, $accountConfig);
        $data['industry_list'] = $indList;
        $data['store_type'] = $storeType;
        $data['brand_list'] = $brandList;
        $data['area'] = [];
        $data['xx_num'] = $xxNum;
        $data['bank_list'] = $bankList;
        return getReturn(200, '', $data);
    }

    /**
     * @param int $storeId
     * @param array $data
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-27 01:37:41
     * Desc: 保存开户后的其他一些信息
     * Update: 2017-10-27 01:37:42
     * Version: 1.0
     */
    public function saveOpenStoreInfo($storeId = 0, $data = [])
    {
        if (empty($storeId)) return getReturn(-1, '参数错误');
        // 清除缓存
        S("openStore_{$storeId}", null);
        $this->startTrans();
        $oldData = $data;
        // 组装品牌ID
        $brand = [];
        if (!empty($data['proxy_brand_id'])) {
            foreach ($data['proxy_brand_id'] as $key => $value) {
                $item = [];
                $item['brand_id'] = $value;
                $brand[] = $item;
            }
        }
        $data['proxy_brand_id'] = json_encode($brand, JSON_UNESCAPED_UNICODE);
        // 提现设置
        $config = [];
        $config['account_type'] = $data['account_type'];
        $config['account_type_name'] = $data['account_type_name'];
        $config['account_card_name'] = $data['account_card_name'];
        $config['account_member_name'] = $data['account_member_name'];
        // 筛选需要的字段
        $field = [
            'store_address,member_tel,store_parenttype_id,store_childtype_id',
            'store_type,lianxi_member_name,lianxi_member_tel,proxy_brand_id,store_remark',
            'lianxi_id_number,isshow,auto_accept_order,settlement,store_name'
        ];
        $field = implode(',', $field);
        $data = $this->field($field)->create($data);
        if (false === $data) {
            $this->rollback();
            return getReturn(-1, $this->getError());
        }
        $data['version'] = $this->max('version') + 1;
        $where = [];
        $where['store_id'] = $storeId;
        $result = $this->where($where)->save($data);
        if (false === $result) {
            $this->rollback();
            return getReturn();
        }
        // 更新 mb_account 字段
        $where = [];
        $where['store_id'] = $storeId;
        $data = [];
        $data['account_zhucehao'] = $oldData['account_zhucehao'];
        $data['recommend_name'] = $oldData['recommend_name'];
        $result = M('mb_account')->where($where)->save($data);
        if (false === $result) {
            $this->rollback();
            return getReturn();
        }
        // 更新member表字段
        $where = [];
        $where['store_id'] = $storeId;
        $memberId = $this->where($where)->getField('member_id');
        if (false === $memberId) {
            $this->rollback();
            return getReturn();
        }
        $data = [];
        $data['member_tel'] = $oldData['member_tel'];
        $data['version'] = M('member')->max('version') + 1;
        $where = [];
        $where['member_id'] = $memberId;
        $model = M('Member');
        $result = $model->strict(true)->where($where)->save($data);
        if (false === $result) {
            $this->rollback();
            return getReturn();
        }

        // 更新account_config表
        $model = D('AccountConfig');
        $data['account_type'] = 0;
        $result = $model->setStoreAccountConfig($storeId, $config);
        if (200 !== $result['code']) {
            $this->rollback();
            return $result;
        }
        $this->commit();
        return getReturn(200, '');
    }

    /**
     * 清除列表缓存
     * @param int $channelId
     * User: hjun
     * Date: 2019-03-19 17:40:43
     * Update: 2019-03-19 17:40:43
     * Version: 1.00
     */
    public function clearChildStoreListCache($channelId = 0)
    {
        $childStoreCacheKey = S("child_store_list_cache_key:{$channelId}");
        foreach ($childStoreCacheKey as $key) {
            S($key, null);
        }
    }

    /**
     * @param int $channelId
     * @param int $page
     * @param int $limit
     * @param array $condition
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-27 12:23:20
     * Desc: 获取子店列表
     * Update: 2017-10-27 12:23:21
     * Version: 1.0
     */
    public function getChildStoreList($channelId = 0, $page = 1, $limit = 0, $condition = [])
    {
        $childStoreCacheKey = S("child_store_list_cache_key:{$channelId}");
        $where = [];
        $where['a.channel_id'] = $channelId;
        $where['a.main_store'] = 0;
        $where['a.isdelete'] = 0;
        $where = array_merge($where, $condition);
        $skip = ($page - 1) * $limit;
        $take = $limit;
        $total = $this
            ->alias('a')
            ->field('a.store_id')
            ->where($where)
            ->join('__MB_STORE_TYPE__ b ON b.store_type_id = a.store_type')
            ->join('__MB_ACCOUNT__ c ON c.store_id = a.store_id')
            ->cache(true)
            ->count();
        $key = md5(serialize($this->lastOptions));
        if (!in_array($key, $childStoreCacheKey)) {
            $childStoreCacheKey[] = $key;
        }
        $field = [
            'a.store_id,a.store_name,a.member_name,a.lianxi_member_name,a.lianxi_member_tel',
            'a.isshow,b.store_type_name,c.end_time',
//            'd.store_name parent_store1,e.store_name parent_store2'
        ];
        $field = implode(',', $field);
        $list = $this
            ->alias('a')
            ->field($field)
            ->where($where)
            ->join('__MB_STORE_TYPE__ b ON b.store_type_id = a.store_type')
            ->join('__MB_ACCOUNT__ c ON c.store_id = a.store_id')
//            ->join('LEFT JOIN __STORE__ d ON d.main_store = 1 AND d.channel_id = a.channel_id')
//            ->join('LEFT JOIN __STORE__ e ON e.flagstore = 1 AND e.store_type = a.store_type')
            ->limit($skip, $take)
            ->order('c.end_time DESC,a.store_id DESC')
            ->cache(true)
            ->select();
        $key = md5(serialize($this->lastOptions));
        if (!in_array($key, $childStoreCacheKey)) {
            $childStoreCacheKey[] = $key;
        }
        if (false === $list) return getReturn();
        $data = [];
        $data['total'] = $total;
        $data['list'] = $list;
        S("child_store_list_cache_key:{$channelId}", $childStoreCacheKey);
        return getReturn(200, '', $data);
    }
}