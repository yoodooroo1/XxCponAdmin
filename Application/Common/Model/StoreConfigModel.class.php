<?php
/**
 * Created by PhpStorm.
 * User: hjun
 * Date: 2017/4/26
 * Time: 15:38
 */

namespace Common\Model;

use Think\Cache\Driver\Redis;

class StoreConfigModel extends BaseModel
{

    protected $tableName = 'mb_store_config';

    /**
     * @param int $storeId
     * @param string $field
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-19 15:04:09
     * Desc: 获取商家的一些设置信息
     * Update: 2017-10-19 15:04:10
     * Version: 1.0
     */
    public function getStoreConfig($storeId = 0, $field = '')
    {
        if (empty($storeId)) return getReturn(-1, '参数无效');
        // 默认获取全部字段
        if (empty($field)) $field = true;
        $info = $this
            ->field($field)
            ->find($storeId);
        if (false === $info) {
            logWrite("查询商家{$storeId}配置信息错误:" . $this->getDbError());
            return getReturn();
        }
        if (empty($info)) {
            $data = [];
            $data['store_id'] = $storeId;
            $this->addData([], $data);
        }
        if (empty($info['repeat_order_tip'])) $info['repeat_order_tip'] = -1;
        if (empty($info['repeat_order_tip_second'])) $info['repeat_order_tip_second'] = 10;
        if (empty($info['special_goods_title1'])) $info['special_goods_title1'] = '- 优选好物 -';
        if (empty($info['special_goods_title2'])) $info['special_goods_title2'] = 'RECOMMENDATION';
        if (empty($info['subscribe_alert']) && strpos($field, 'subscribe_alert') !== false) $info['subscribe_alert'] = -1;
        return getReturn(200, '', $info);
    }

    /**
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-19 15:04:25
     * Desc: 获取商家订单提醒设置
     * Update: 2017-10-19 15:04:26
     * Version: 1.0
     */
    public function getStoreRepeatOrderConfig($storeId = 0)
    {
        $field = 'repeat_order_tip,repeat_order_tip_second';
        return $this->getStoreConfig($storeId, $field);
    }

    /**
     * @param int $storeId
     * @param array $data
     *  ['repeat_order_tip'=>1, 'repeat_order_tip_second'=>10]
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-19 15:08:01
     * Desc: 保存订单重复提醒设置
     * Update: 2017-10-19 15:08:04
     * Version: 1.0
     */
    public function saveStoreRepeatOrderConfig($storeId = 0, $data = [])
    {
        $storeId = (int)$storeId;
        if ($storeId <= 0) return getReturn(-1, '参数无效');
        if (empty($data['repeat_order_tip'])) return getReturn(-1, '请选择是否提醒');
        if ((int)$data['repeat_order_tip_second'] <= 0) return getReturn(-1, '提醒间隔必须大于0秒');
        $info = $this->field('store_id')->find($storeId);
        $where = [];
        $where['store_id'] = $storeId;
        $data['store_id'] = $storeId;
        $result = empty($info) ? $this->where($where)->add($data) : $this->where($where)->save($data);
        if (false === $result) {
            logWrite("修改商家{$storeId}订单重复提醒失败,参数:" . json_encode($data, JSON_UNESCAPED_UNICODE) . ',错误:' . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '修改成功', $result);
    }

    /**
     * @param int $storeId
     * @return array ['code'=>200,'msg'=>'','data'=>[]]
     * User: hj
     * Date: 2017-10-19 15:04:25
     * Desc: 获取商家订单提醒设置
     * Update: 2017-10-19 15:04:26
     * Version: 1.0
     */
    public function getStoreSpecialGoodsBackConfig($storeId = 0)
    {
        $field = 'special_goods_background,special_goods_title1,special_goods_title2';
        return $this->getStoreConfig($storeId, $field);
    }

    /**
     * @param int $storeId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 保存优选好物设置
     * Date: 2017-11-17 01:48:55
     * Update: 2017-11-17 01:48:56
     * Version: 1.0
     */
    public function saveStoreSpecialGoodsBackConfig($storeId = 0, $data = [])
    {
        $storeId = (int)$storeId;
        if ($storeId <= 0) return getReturn(-1, '参数无效');
//        if (empty($data['special_goods_background'])) return getReturn(-1, '请上传背景图片');
        $info = $this->field('store_id')->find($storeId);
        $where = [];
        $where['store_id'] = $storeId;
        $data['store_id'] = $storeId;
        $result = empty($info) ? $this->where($where)->add($data) : $this->where($where)->save($data);
        if (false === $result) {
            logWrite("修改商家{$storeId}优选好物北京失败,参数:" . json_encode($data, JSON_UNESCAPED_UNICODE) . ',错误:' . $this->getDbError());
            return getReturn();
        }
        return getReturn(200, '修改成功', $result);
    }

    /**
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 获取关注设置
     * Date: 2017-11-17 01:49:53
     * Update: 2017-11-17 01:49:54
     * Version: 1.0
     */
    public function getSubscribeSet($storeId = 0)
    {
        $field = 'subscribe_alert';
        return $this->getStoreConfig($storeId, $field);
    }

    /**
     * @param int $storeId
     * @param $status
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * Author: hj
     * Desc: 保存关注设置
     * Date: 2017-11-17 01:54:01
     * Update: 2017-11-17 01:54:02
     * Version: 1.0
     */
    public function saveSubscribe($storeId = 0, $status)
    {
        $where = [];
        $where['store_id'] = $storeId;
        $options = [];
        $options['field'] = 'subscribe_alert';
        $options['where'] = $where;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if ($info['subscribe_alert'] == $status) {
            $msg = $status == 1 ? "关注框已经开启" : "关注框已经关闭";
            return getReturn(-1, $msg);
        }
        unset($options['field']);
        $data = [];
        $data['subscribe_alert'] = $status;
        $result = $this->saveData($options, $data);
        return $result;
    }

    /**
     * 保存商家配置信息
     * @param int $storeId
     * @param array $field
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-11 16:24:47
     * Update: 2018-01-11 16:24:47
     * Version: 1.00
     */
    public function saveStoreConfig($storeId = 0, $field = [], $data = [])
    {
        if ($storeId <= 0) return getReturn(-1, L('INVALID_PARAM'));
        $where = [];
        $where['store_id'] = $storeId;
        $options = [];
        $options['where'] = $where;
        $options['field'] = implode(',', $field);
        return $this->saveData($options, $data);
    }

    /**
     * 获取商家配置
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-26 11:54:55
     * Update: 2018-01-26 11:54:55
     * Version: 1.00
     */
    public function getStoreConf($storeId = 0)
    {
        $redis = Redis::getInstance();
        $conf = $redis->hGetAll("mb_store_config:{$storeId}");
        if (empty($conf)) {
            $conf = $this->getStoreConfig($storeId)['data'];
            if (!empty($conf)) {
                $redis->hMset("mb_store_config:{$storeId}", $conf);
            }
        }
        return empty($conf) ? [] : $conf;
    }

    /**
     * 改变商家后台的默认语言设置
     * @param int $storeId
     * @param string $lang1
     * @param string $lang2
     * @param array $form
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-01-26 12:43:24
     * Update: 2018-01-26 12:43:24
     * Version: 1.00
     */
    public function changeLang($storeId = 0, $lang1 = '', $lang2 = '', $form = [])
    {
        $where = [];
        $where['store_id'] = $storeId;
        $data = [];
//        $data['default_lang'] = $lang1;
//        $data['mall_default_lang'] = empty($lang2) ? '' : $lang2;
        $data['currency_id'] = empty($form['currency_id']) ? 0 : $form['currency_id'];
        $areaIds = empty($form['area_ids']) ? '0' : implode(',', $form['area_ids']);
        $data['area_ids'] = $areaIds;
        $options = [];
        $options['where'] = $where;
        $result = $this->saveData($options, $data);
        if ($result['code'] !== 200) return $result;
        $redis = Redis::getInstance();
//        $redis->hSet("mb_store_config:{$storeId}", 'default_lang', $lang1);
//        $redis->hSet("mb_store_config:{$storeId}", 'mall_default_lang', $lang2);
        $redis->hSet("mb_store_config:{$storeId}", 'area_ids', $data['area_ids']);
        $redis->hSet("mb_store_config:{$storeId}", 'currency_id', $data['currency_id']);
        $redis->del("{$storeId}_storeInfo");
        return $result;
    }

    /**
     * 获取商家的国家列表
     * @param int $storeId
     * @return array
     * User: hjun
     * Date: 2018-03-19 13:48:49
     * Update: 2018-03-19 13:48:49
     * Version: 1.00
     */
    public function getStoreCountryList($storeId = 0)
    {
        $config = $this->getStoreConf($storeId);
        $countryId = explode(',', $config['area_ids']);
        $countryId = empty($countryId) ? 0 : $countryId;
        $countryList = M('mb_areas')
            ->field('area_id,area_name')
            ->where(['area_id' => ['in', $countryId]])
            ->select();
        return empty($countryList) ? [] : $countryList;
    }
}