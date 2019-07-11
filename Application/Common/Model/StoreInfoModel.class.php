<?php

namespace Common\Model;


class StoreInfoModel extends BaseModel
{
    protected $tableName = "mb_store_info";

    /**
     * 获取商家的分销设置
     * @param int $storeId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-02 15:59:45
     * Update: 2018-02-02 15:59:45
     * Version: 1.00
     */
    public function getDistributionConfig($storeId = 0)
    {
        $field = [
            'rateswitch', 'integral_pv_switch'
        ];
        $level = 3;
        //for ($i = 0; $i <= $level; $i++) {
            $field[] = "rate0, rate1, rate2, rate3, vip1_rate0, vip2_rate0, vip3_rate0,
             vip1_rate1, vip2_rate1, vip3_rate1, vip1_rate2, vip2_rate2, vip3_rate2, vip1_rate3, vip2_rate3, vip3_rate3";
            $field[] = "integral_rate0, integral_rate1, integral_rate2, integral_rate3,
            vip1_integral_rate0, vip1_integral_rate1, vip1_integral_rate2, vip1_integral_rate3, vip2_integral_rate0,
            vip2_integral_rate1, vip2_integral_rate2, vip2_integral_rate3, vip3_integral_rate0, vip3_integral_rate1,
            vip3_integral_rate2, vip3_integral_rate3";
       // }
        $field = implode(',', $field);
        $where = [];
        $where['store_id'] = $storeId;
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        $result = $this->queryRow($options);
        if ($result['code'] !== 200) return $result;
        $info = $result['data'];
        if (empty($info)) return getReturn(-1, L('_SELECT_NOT_EXIST_'));
        $condition = [];
        $condition['callback_field'] = [
            'rate0' => ['rate0', 'numberX100'],
            'rate1' => ['rate1', 'numberX100'],
            'rate2' => ['rate2', 'numberX100'],
            'rate3' => ['rate3', 'numberX100'],
            'vip1_rate0' => ['vip1_rate0', 'numberX100'],
            'vip2_rate0' => ['vip2_rate0', 'numberX100'],
            'vip3_rate0' => ['vip3_rate0', 'numberX100'],
            'vip1_rate1' => ['vip1_rate1', 'numberX100'],
            'vip2_rate1' => ['vip2_rate1', 'numberX100'],
            'vip3_rate1' => ['vip3_rate1', 'numberX100'],
            'vip1_rate2' => ['vip1_rate2', 'numberX100'],
            'vip2_rate2' => ['vip2_rate2', 'numberX100'],
            'vip3_rate2' => ['vip3_rate2', 'numberX100'],
            'vip1_rate3' => ['vip1_rate3', 'numberX100'],
            'vip2_rate3' => ['vip2_rate3', 'numberX100'],
            'vip3_rate3' => ['vip3_rate3', 'numberX100'],
            'integral_rate0' => ['integral_rate0', 'numberX100'],
            'integral_rate1' => ['integral_rate1', 'numberX100'],
            'integral_rate2' => ['integral_rate2', 'numberX100'],
            'integral_rate3' => ['integral_rate3', 'numberX100'],
            'vip1_integral_rate0' => ['vip1_integral_rate0', 'numberX100'],
            'vip1_integral_rate1' => ['vip1_integral_rate1', 'numberX100'],
            'vip1_integral_rate2' => ['vip1_integral_rate2', 'numberX100'],
            'vip1_integral_rate3' => ['vip1_integral_rate3', 'numberX100'],
            'vip2_integral_rate0' => ['vip2_integral_rate0', 'numberX100'],
            'vip2_integral_rate1' => ['vip2_integral_rate1', 'numberX100'],
            'vip2_integral_rate2' => ['vip2_integral_rate2', 'numberX100'],
            'vip2_integral_rate3' => ['vip2_integral_rate3', 'numberX100'],
            'vip3_integral_rate0' => ['vip3_integral_rate0', 'numberX100'],
            'vip3_integral_rate1' => ['vip3_integral_rate1', 'numberX100'],
            'vip3_integral_rate2' => ['vip3_integral_rate2', 'numberX100'],
            'vip3_integral_rate3' => ['vip3_integral_rate3', 'numberX100'],
        ];
        $info = $this->transformInfo($info, $condition);
        // 开关变量
        $info['open_ctrl'] = $info['rateswitch'] == 0 && $info['integral_pv_switch'] == 0 ? 0 : 1;
        if ($info['open_ctrl'] == 0) {
            $info['distributionMode'] = 0;
        } elseif ($info['rateswitch'] == 1) {
            $info['distributionMode'] = 1;
        } else {
            $info['distributionMode'] = 2;
        }
        $result['data'] = $info;
        return $result;
    }

    /**
     * 保存分销设置
     * @param int $storeId
     * @param array $data
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2018-02-02 16:02:18
     * Update: 2018-02-02 16:02:18
     * Version: 1.00
     */
    public function saveDistributionConfig($storeId = 0, $data = [])
    {
        $info = $this->find($storeId);
        $method = empty($info) ? 'addData' : 'saveData';
        if ($data['distributionMode'] == 0) {
            $data['rateswitch'] = 0;
            $data['integral_pv_switch'] = 0;
        } elseif ($data['distributionMode'] == 1) {
            $data['rateswitch'] = 1;
            $data['integral_pv_switch'] = 0;
        } else {
            $data['rateswitch'] = 0;
            $data['integral_pv_switch'] = 1;
        }
        $modeName = ['rateswitch', 'integral_pv_switch'];
        $rateName = [
            'rateswitch' => ['rate0', 'rate1', 'rate2', 'rate3', 'vip1_rate0', 'vip2_rate0', 'vip3_rate0',
                'vip1_rate1', 'vip2_rate1', 'vip3_rate1', 'vip1_rate2', 'vip2_rate2', 'vip3_rate2', 'vip1_rate3', 'vip2_rate3', 'vip3_rate3'],
            'integral_pv_switch' => ['integral_rate0', 'integral_rate1', 'integral_rate2', 'integral_rate3',
                'vip1_integral_rate0', 'vip1_integral_rate1', 'vip1_integral_rate2', 'vip1_integral_rate3', 'vip2_integral_rate0',
                'vip2_integral_rate1', 'vip2_integral_rate2', 'vip2_integral_rate3', 'vip3_integral_rate0', 'vip3_integral_rate1',
                'vip3_integral_rate2', 'vip3_integral_rate3'],
        ];
        $rate0 = 0;
        $rate1 = 0;
        $rate2 = 0;
        $rate3 = 0;

        $integral0 = 0;
        $integral1 = 0;
        $integral2 = 0;
        $integral3 = 0;

        foreach ($modeName as $key => $value) {
           // $totalRate = 0;
            foreach ($rateName[$value] as $k => $val) {
                $data[$val] = round($data[$val] / 100, 2);
               // $totalRate += $data[$val];
                if ($val == 'rate0' || $val == 'vip1_rate0' || $val == 'vip2_rate0' || $val == 'vip3_rate0'){
                    if ($data[$val] > $rate0){
                        $rate0 = $data[$val];
                    }
                }else if($val == 'rate1' || $val == 'vip1_rate1' || $val == 'vip2_rate1' || $val == 'vip3_rate1'){
                    if ($data[$val] > $rate1){
                        $rate1 = $data[$val];
                    }
                }else if($val == 'rate2' || $val == 'vip1_rate2' || $val == 'vip2_rate2' || $val == 'vip3_rate2'){
                    if ($data[$val] > $rate2){
                        $rate2 = $data[$val];
                    }
                }else if($val == 'rate3' || $val == 'vip1_rate3' || $val == 'vip2_rate3' || $val == 'vip3_rate3'){
                    if ($data[$val] > $rate3){
                        $rate3 = $data[$val];
                    }
                }else if($val == 'integral_rate0' || $val == 'vip1_integral_rate0' || $val == 'vip2_integral_rate0' || $val == 'vip3_integral_rate0'){
                    if ($data[$val] > $integral0){
                        $integral0 = $data[$val];
                    }
                }else if($val == 'integral_rate1' || $val == 'vip1_integral_rate1' || $val == 'vip2_integral_rate1' || $val == 'vip3_integral_rate1'){
                    if ($data[$val] > $integral1){
                        $integral1 = $data[$val];
                    }
                }else if($val == 'integral_rate2' || $val == 'vip1_integral_rate2' || $val == 'vip2_integral_rate2' || $val == 'vip2_integral_rate3'){
                    if ($data[$val] > $integral2){
                        $integral2 = $data[$val];
                    }
                }else if($val == 'integral_rate3' || $val == 'vip1_integral_rate3' || $val == 'vip2_integral_rate3' || $val == 'vip3_integral_rate3'){
                    if ($data[$val] > $integral3){
                        $integral3 = $data[$val];
                    }
                }

            }
        }

//        if (($rate0 + $rate1 + $rate2 + $rate3) > 1 || ($integral0 + $integral1 + $integral2 + $integral3) > 1 ) {
//            return getReturn(-1, L('FXBLZHBNDYYB')/*分销比例总和不能大于100%*/);
//        }

        $field = [
            'rateswitch', 'integral_pv_switch', 'rate0', 'rate1', 'rate2', 'rate3', 'vip1_rate0', 'vip2_rate0', 'vip3_rate0',
            'vip1_rate1', 'vip2_rate1', 'vip3_rate1', 'vip1_rate2', 'vip2_rate2', 'vip3_rate2', 'vip1_rate3', 'vip2_rate3', 'vip3_rate3',
            'integral_rate0', 'integral_rate1', 'integral_rate2', 'integral_rate3','vip1_integral_rate0', 'vip1_integral_rate1',
            'vip1_integral_rate2', 'vip1_integral_rate3', 'vip2_integral_rate0',
            'vip2_integral_rate1', 'vip2_integral_rate2', 'vip2_integral_rate3', 'vip3_integral_rate0', 'vip3_integral_rate1',
            'vip3_integral_rate2', 'vip3_integral_rate3'
        ];
        $field = implode(',', $field);
        $where = [];
        $where['store_id'] = $storeId;
        $options = [];
        $options['field'] = $field;
        $options['where'] = $where;
        return $this->$method($options, $data);

    }

    public function transformInfo($info = [], $condition = [])
    {
        $info = parent::transformInfo($info, $condition);

        return $info;
    }
}