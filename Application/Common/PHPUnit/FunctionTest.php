<?php

namespace Common\PHPUnit;

use Common\Util\WxApi;
use Common\Util\WXBizDataCrypt;
use Think\Cache\Driver\Redis;

class FunctionTest extends BaseTest
{
    public function testTemp()
    {
        $storeId = '564';
        $arr = explode(',', $storeId);
        $this->assertEquals(1, count($arr));

        $storeId = 1 + 1 ?: 0;
        $this->assertEquals(2, $storeId);

        $req = [];
        $storeId = $req['se'] || $req['f'] || '132' ?: 0;
        $this->assertEquals(132, $storeId);

        $path = realpath(THINK_PATH . '../xxapi/') . '/';
    }

    public function testGetDefaultData()
    {
        $result = D('Store')->getStoreInfo(16948);
        $const = get_defined_constants();
        $data = getDefaultData('diy/defaultTplCtrlData');
    }

    public function testOpenids()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=17__EWG-QdFz7nJ0b54gliHrGjeipiowbx-pibckVNNcT4LHu5XebQi6S9XwcKXFjX6FwvMPfpbHjczobAxjdxrV77hplbcYCEAHwmx_AGGEwhUSNys3zrreVj3bHqXYZgfbVjm-a9zcfNpZfA6ZTXjADAFZD&next_openid=";
        $count = 0;
        $openid_list = [];
        do {
            $res = file_get_contents($url);
            $rs = json_decode($res, 1);
            if ($rs['errcode']) {
                die('微信接口调用错误，错误代码：' . $rs['errcode']);
            }
            $total = $rs['total'];
            $count += $rs['count'];
            $openid_list = array_merge($openid_list, $rs['data']['openid']);
        } while ($total > $count);
    }

    public function testDecode()
    {
        $util = new WxApi(3, 'mini');
        $result = [];
        $result[] = $util->getMiniCodeUN('123@1');
        $result[] = $util->getMiniCodeUN('123@2');
        $result[] = $util->getMiniCodeUN('123@3');

        $data = D('StoreMember')->getRelationData(16948, 6129737);
        $sql = D('StoreMember')->_sql();
        $appid = 'wx4f4bc4dec97d474b';
        $sessionKey = 'tiihtNczf5v6AKRyjwEUhQ==';

        $encryptedData = "CiyLU1Aw2KjvrjMdj8YKliAjtP4gsMZM
                QmRzooG2xrDcvSnxIMXFufNstNGTyaGS
                9uT5geRa0W4oTOb1WT7fJlAC+oNPdbB+
                3hVbJSRgv+4lGOETKUQz6OYStslQ142d
                NCuabNPGBzlooOmB231qMM85d2/fV6Ch
                evvXvQP8Hkue1poOFtnEtpyxVLW1zAo6
                /1Xx1COxFvrc2d7UL/lmHInNlxuacJXw
                u0fjpXfz/YqYzBIBzD6WUfTIF9GRHpOn
                /Hz7saL8xz+W//FRAUid1OksQaQx4CMs
                8LOddcQhULW4ucetDf96JcR3g0gfRK4P
                C7E/r7Z6xNrXd2UIeorGj5Ef7b1pJAYB
                6Y5anaHqZ9J6nKEBvB4DnNLIVWSgARns
                /8wR2SiRS7MNACwTyrGvt9ts8p12PKFd
                lqYTopNHR1Vf7XjfhQlVsAJdNiKdYmYV
                oKlaRv85IfVunYzO0IKXsyl7JCUjCpoG
                20f0a04COwfneQAGGwd5oa+T8yO5hzuy
                Db/XcxxmK01EpqOyuxINew==1";

        $iv = 'r7BXXKkLb8qrSNn05n0qiA==';

        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
    }

    public function testMiniCode()
    {
        $util = new WxApi(3, 'mini');
        $result = [];
        $result[] = $util->getMiniCodeUN('1');
        $result[] = $util->getMiniCodeUN('2');
        $result[] = $util->getMiniCodeUN('3');
        $result[] = $util->getMiniCodeUN('4');
        foreach ($result as $key => $img) {
            file_put_contents(DATA_PATH . "/mini_code/{$key}.png", $img);
        }
    }

    public function testMsg()
    {
//        $params = array();
//        $params['order_id'] = 27;
//        $send = A('SendMessage');
//        $send->sendWxMsg(564, 22, 6042225, $params);


        $url = "{$_SERVER['HTTP_HOST']}/index.php?c=SendMessage&a=sendWxMsg";
        $datas = array();
        $datas['type'] = 22;
        $datas['store_id'] = 564;
        $datas['is_api'] = 1;
        $datas['member_id'] = 6042225;
        $datas['order_id'] = 27;
        $result = httpRequest($url, 'POST', $datas);

    }

    public function testMiniQRCode()
    {
        $util = new WxApi(3, 'mini');
        $params = [];
        $params['pend_num_id'] = '1';
        $code = $util->getMiniQRCode('pages/classify/classify', $params);
        file_put_contents(RUNTIME_PATH . '/test3.jpg', $code);
    }

    public function testRedis()
    {
        $storeId = 564;
        $redis = Redis::getInstance();
        $data = [];
        $data['1:1'] = 1;
        $data['2:1'] = 2;
        $redis->hMset("main_tpl:{$storeId}", $data);
        $result = $redis->hGetAll("main_tpl:{$storeId}");
        S("main_tpl:{$storeId}", null);
        $result = $redis->hGetAll("main_tpl:{$storeId}");
    }

    function getGoodsQGNextTime($goods = [], $nowTime = NOW_TIME)
    {
        switch ($goods['qianggou_type']) {
            case 2:
                // 每天
                // 1. 取出每天的 时分秒
                $start = date('His', $goods['qianggou_start_time']);
                $end = date('His', $goods['qianggou_end_time']);
                // 2. 取今天的日期
                $day = date('Ymd', $nowTime);
                $endTime = strtotime("{$day}{$end}");
                // 3. 如果今天的结束时间都已经过期了  则更新为明天的时间
                if ($endTime <= $nowTime) {
                    $day = date("Ymd", $nowTime + 3600 * 24);
                }
                $startDate = "{$day}{$start}";
                $endDate = "{$day}{$end}";
                break;
            case 3:
                // 每周
                $start = date('His', $goods['qianggou_start_time']);
                $end = date('His', $goods['qianggou_end_time']);
                // 获取开始是星期几 结束是星期几
                $startDW = $goods['start_dw'];
                $endDW = $goods['end_dw'];
                // 今天星期几 根据今天星期几计算出开始和结束时间
                $todayDW = date('w', $nowTime);
                $todayDW = $todayDW == 0 ? 7 : $todayDW;
                $startDay = date('Ymd', $nowTime + ($startDW - $todayDW) * 3600 * 24);
                $endDay = date('Ymd', $nowTime + ($endDW - $todayDW) * 3600 * 24);
                $endTime = strtotime("{$endDay}{$end}");
                // 如果过期了 则更新为下周
                if ($endTime <= $nowTime) {
                    $startDay = date('Ymd', ($nowTime + 3600 * 24 * 7) + ($startDW - $todayDW) * 3600 * 24);
                    $endDay = date('Ymd', ($nowTime + 3600 * 24 * 7) + ($endDW - $todayDW) * 3600 * 24);
                }
                $startDate = "{$startDay}{$start}";
                $endDate = "{$endDay}{$end}";
                break;
            case 4:
                // 每月
                // 1. 取 日时分秒
                $start = date('His', $goods['qianggou_start_time']);
                $end = date('His', $goods['qianggou_end_time']);
                $startDay = $goods['start_dw'];
                $endDay = $goods['end_dw'];
                // 2. 取 本月 判断本月天数是否足够
                $monthDays = date('t', $nowTime);
                if ($endDay > $monthDays) {
                    $startDay = $monthDays - ($endDay - $startDay);
                    $endDay = $monthDays;
                }
                $month = date('Ym', $nowTime);
                $endTime = strtotime("{$month}{$endDay}{$end}");
                // 3. 如果结束时间都已经过期了  则更新为下个月
                if ($endTime <= $nowTime) {
                    $nextTime = getNextPeriodDate($nowTime);
                    $monthDays = date('t', $nextTime);
                    if ($endDay > $monthDays) {
                        $startDay = $monthDays - ($endDay - $startDay);
                        $endDay = $monthDays;
                    }
                    $month = date("Ym", $nextTime);
                }
                $startDate = "{$month}{$startDay}{$start}";
                $endDate = "{$month}{$endDay}{$end}";
                break;
            default:
                return ['start' => 0, 'end' => 0];
                break;
        }
        return ['start' => strtotime($startDate), 'end' => strtotime($endDate)];
    }

    public function testQG()
    {
        $model = D('Goods');
        $result = $model->refreshGoodsQG();
    }

    /**
     * 主题格子缓存重置
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2019-05-28 17:25:21
     * Update: 2019-05-28 17:25:21
     * Version: 1.00
     */
    public function testResetGird()
    {
        $model = D('ThemeGird');
        $list = $model->field('gird_id,gird_name,gird_modules,store_id')->select();
        $model->startTrans();
        foreach ($list as $key => $value) {
            $modules = jsonDecodeToArr($value['gird_modules']);
            foreach ($modules as $k => $module) {
                $module['dom_item'] = $model->getThemeGirdModuleDom($module, $value['store_id'], $value['gird_name']);
                $modules[$k] = $module;
            }
            $value['gird_modules'] = jsonEncode($modules);
            // 保存
            $model->save($value);
        }
        $model->commit();
    }

    public function testGoodsBean()
    {
        $bean = D('Goods')->getGoodsBeanByGsId(137437, 0, 564);
    }
}