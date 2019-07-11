<?php

namespace Common\PHPUnit;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected static $app = null;

    protected static $gardenId = 0;
    protected static $garden = [];
    protected static $admin = [];

    static public function setupBeforeClass()
    {
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        date_default_timezone_set('Asia/Shanghai');
        // 下面四行代码模拟出一个应用实例, 每一行都很关键, 需正确设置参数
        $appPath = realpath(__DIR__ . '/../../Dock/') . '/001-XunXin/';
        $thinkPath = __DIR__ . '/../../../Core';
        self::$app = new \Think\PhpunitHelper($appPath, $thinkPath);
        self::$app->setMVC('hdev.duinin.com', 'Admin', 'Test');
        $globalPath = realpath(APP_PATH . '../GLOBAL') . '/global.php';
        $content = file_get_contents($globalPath);
        preg_match_all('/define.*[\'"](\w+)[\'"].*((?<=[\'"]).+(?=[\'"])|true|false)/im',$content,$matches,PREG_SET_ORDER);
        $isTest = true;
        foreach ($matches as $data) {
            if ($data[1] === 'MODE') {
                $mode = $data[2];
                if (in_array($mode, ['common', 'home_common'])) {
                    $isTest = false;
                }
                break;
            }
        }
        if ($isTest) {
            $config = [
                'DB_TYPE' => 'mysql',// 数据库类型
                'DB_HOST' => '123.207.98.106', //'127.0.0.1',//测试 服务器地址
                'DB_NAME' => 'xunxin_test_db',// 数据库名
                'DB_USER' => 'admin', //'root',// 用户名
                'DB_PWD' => '9ewFk02t5QfXA8W2', //'9ewFk02t5QfXA8W2',// 密码
                'DB_PREFIX' => 'xunxin_',// 数据库表前缀
                'DB_PORT' => 3306,// 端口
                'DB_CHARSET' => 'utf8',
                'DB_PARAMS' => array(\PDO::ATTR_CASE => \PDO::CASE_NATURAL),
                'DATA_CACHE_PREFIX' => 'XX_',//缓存前缀
                'DATA_CACHE_TYPE' => 'Redis',//默认动态缓存为Redis
                'REDIS_HOST' => '123.207.98.106', //redis服务器ip，多台用逗号隔开；读写分离开启时，第一台负责写，其它[随机]负责读；
                'REDIS_PORT' => '6379',//端口号
                'DATA_CACHE_TIMEOUT' => '300',//超时时间
                'REDIS_PERSISTENT' => false,//是否长连接 false=短连接
                'REDIS_AUTH' => 'xunxin8988998',//AUTH认证密码
            ];
        } else {
            $config = [
                'DB_TYPE' => 'mysql',// 数据库类型
                'DB_HOST' => '118.89.53.225',//正式 服务器地址
                'DB_NAME' => 'xunxin_db',// 数据库名
                'DB_USER' => 'xunxin_remote',// 用户名
                'DB_PWD' => 'htxQrhOUKOpG',// 密码
                'DB_PREFIX' => 'xunxin_',// 数据库表前缀
                'DB_PORT' => 3306,// 端口
                'DB_CHARSET' => 'utf8',
                'DB_PARAMS' => array(\PDO::ATTR_CASE => \PDO::CASE_NATURAL),
                'DATA_CACHE_PREFIX' => 'XX_',//缓存前缀
                'DATA_CACHE_TYPE' => 'Redis',//默认动态缓存为Redis
                'REDIS_HOST' => '123.207.98.106', //redis服务器ip，多台用逗号隔开；读写分离开启时，第一台负责写，其它[随机]负责读；
                'REDIS_PORT' => '6380',//端口号
                'DATA_CACHE_TIMEOUT' => '300',//超时时间
                'REDIS_PERSISTENT' => false,//是否长连接 false=短连接
                'REDIS_AUTH' => 'xunxin8988998',//AUTH认证密码
            ];
        }

        self::$app->setTestConfig($config); // 一定要设置一个测试用的数据库,避免测试过程破坏生产数据
        self::$app->start();
//        self::clearDatabase();
//        self::addGarden();
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
    }

    /**
     * 测试添加园区
     * User: hjun
     * Date: 2018-05-10 16:06:07
     * Update: 2018-05-10 16:06:07
     * Version: 1.00
     */
    public static function addGarden()
    {
        $model = D('Garden');
        $data = [
            'garden_name' => 'phpunit',
            'manager_name' => 'phpunit',
            'manager_mobile' => '15805946549',
            'admin_name' => 'phpunit',
            'password' => '8988998',
            'end_time' => '2018-12-31',
            'garden_status' => '1',
        ];
        $result = $model->addGarden($data);
        $data = $result['data'];
        self::$garden = $model->find($data['garden_id']);
        self::$gardenId = self::$garden['garden_id'];
        self::$admin = M('admin')->find($data['admin_id']);

        // 添加费用
        $data = [
            [
                'item_name' => '公共卫生费',
                'garden_id' => self::$gardenId
            ],
            [
                'item_name' => '保护费',
                'garden_id' => self::$gardenId
            ],
        ];
        M('charging_items')->addAll($data);
        // 添加设备
        $data = [
            [
                'facility_name' => '空调',
                'garden_id' => self::$gardenId
            ],
            [
                'facility_name' => '衣柜',
                'garden_id' => self::$gardenId
            ],
            [
                'facility_name' => '椅子',
                'garden_id' => self::$gardenId
            ],
        ];
        M('facility')->addAll($data);
        // 添加合同模版
        $data = [
            [
                'tpl_name' => '模版1',
                'tpl_status' => '1',
                'garden_id' => self::$gardenId
            ],
            [
                'tpl_name' => '模版2',
                'tpl_status' => '1',
                'garden_id' => self::$gardenId
            ],
        ];
        M('contract_tpl')->addAll($data);
        // 添加二维码
        $data = [
            [
                'qr_name' => '二维码1',
                'garden_id' => self::$gardenId
            ],
            [
                'qr_name' => '二维码2',
                'garden_id' => self::$gardenId
            ],
        ];
        M('gathering_qrcode')->addAll($data);
    }

    /**
     * 清理数据库
     * User: hjun
     * Date: 2018-05-10 16:15:04
     * Update: 2018-05-10 16:15:04
     * Version: 1.00
     */
    public static function clearDatabase()
    {
        // 清理数据库
        $where = '1=1';
        M('admin')->where($where)->delete();
        M('garden')->where($where)->delete();
        M('building')->where($where)->delete();
        M('floor')->where($where)->delete();
        M('room')->where($where)->delete();
        M('contract')->where($where)->delete();
        M('bill')->where($where)->delete();
        M('bill_log')->where($where)->delete();
        M('trade_detail')->where($where)->delete();
        M('charging_items')->where($where)->delete();
        M('facility')->where($where)->delete();
        M('contract_tpl')->where($where)->delete();
        M('gathering_qrcode')->where($where)->delete();
        M('bill_charging_index')->where($where)->delete();
        if (self::$gardenId <= 1) {
            F("delGardenId", self::$gardenId);
        }
    }
}