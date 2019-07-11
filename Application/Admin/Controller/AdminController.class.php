<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/6
 * Time: 17:41
 */

namespace Admin\Controller;


/**
 * XUNXIN PC 后台管理 登入管理
 * ============================================================================
 * 版权所有 2005-2010 厦门微聚点科技有限公司，并保留所有权利。
 * 网站地址: http://www.vjudian.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业
 * 目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: youyan $
 * $Id: AuthController.class.php
 */
class AdminController extends BaseController
{

    //====================常用变量================//
    protected $admin_id = '';
    protected $loginname = '';
    protected $role = '';
    protected $group_id = '';
    //====================常用变量================//

    // 套餐权限信息
    protected $grant = [];
    // 菜单列表
    protected $menu = [];
    // ajax返回格式
    protected $result = array(
        'code' => -1,
        'msg' => '失败',
        'data' => null,
        'comment' => '备注说明'
    );
    // 请求参数数组
    protected $reqGet = [];
    protected $reqPost = [];
    protected $reqBody = [];
    //====================其他变量================//


    public function __construct()
    {
        session_start();
        parent::__construct();
        header("Content-Type:text/html;Charset=utf-8");
        if (session('admin_id') > 0) {
            // 初始化系统
            $this->initSystem();
        } else {
            session(null);
            header("Location:" . U('Auth/login'));
            exit;
        }
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    protected function initSystem()
    {
        // 请求参数获取
        $this->reqGet = $this->req;
        $this->reqPost = $this->req;
        $this->reqBody = I('put.');
        // 设置常用变量值
        $this->setAttr();

    }


    protected function setAttr()
    {
        // 初始化属性
        $this->admin_id = (int)session('login_info.id');
        $this->loginname = session('login_info.loginname');
//        $this->role = session('login_info.role');
//        $this->group_id = session('login_info.group_id');
        return true;
    }


    public function _empty()
    {
        $this->error("非法操作", 'admin.php', 1);
    }


    /**
     * 请求接口
     * @param string $param
     * @return mixed
     * User: hj
     * Date: 2017-09-07 20:16:28
     */
    protected function request_post_xunxin($act, $op, $param = '')
    {
        $apiUrl = C('new_api_url') . ("?act={$act}&op={$op}");
        return $this->request_post($apiUrl, $param);
    }

    /**
     * 请求接口
     * @param string $url
     * @param string $param
     * @return bool|mixed
     * User: hj
     * Date: 2017-09-07 20:21:03
     */
    protected function request_post($url = '', $param = '')
    {
        // die($url.$param);
        if (empty($url) || empty($param)) {
            return false;
        }

        $try = 0;
        $curl_errno = -1;
        do {
            $postUrl = $url;
            $curlPost = $param;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $postUrl);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
            $data = curl_exec($ch);
            $curl_errno = curl_errno($ch);
            curl_close($ch);
        } while ($curl_errno > 0 && ++$try < 3);

        return $data;
    }

    /**
     * 设置API的传递参数
     * @param mixed $param
     * @return boolean
     */
    protected function getApiParams($param = array())
    {

        $key = $this->getApiToken();
        if (empty($key)) {
            return false;
        }
        $params = array(
            "user_type" => 'seller',
            "store_id" => session('store_id'),
            "key" => $key,
            "comchannel_id" => session('channel_id'),
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
     * 获得要调用的API的通行证
     */
    protected function getApiToken()
    {
        $Token = M('Mb_user_token');
        $where = array();
        $where['member_id'] = session('member_id');
        $key = $Token->where($where)->find();
        return $key['token'];
    }


    /**
     * 获得其他界面的补充条件
     * @param mixed $condition
     * @param mixed $where
     * @return mixed
     */
    protected function getOtherWhere($where, $condition)
    {
        if (!empty($condition) && is_array($condition)) {

            foreach ($condition as $k => $v) {
                $where[$k] = $v;
            }
        }
        return $where;
    }


    /**
     * 设置各种上限的session
     */
    protected function setLimitNumSession()
    {
        $limit = $this->getLimitNum();
        session('seller_num', $limit['seller_num']);
        session('member_num', $limit['member_num']);
        session('goods_num', $limit['goods_num']);
        session('advertise_num', $limit['advertise_num']);
        session('goods_code', $limit['goods_code']);

    }

    /**
     * 获得session
     * @param mixed $flag
     */
    protected function getLimiteNumSession($flag = false)
    {
        if ($flag) {
            $this->setLimitNumSession();
        } else {
            if (!session('?seller_num') || !session('?member_num') || !session('?goods_num')) {
                $this->setLimitNumSession();
            }
        }
    }

    /**
     * 导出excel数据
     * hjun 2017年2月23日 09:14:38
     * @param string $fileName 文件名称
     * @param array $headArr 标题数组
     * @param array $data 数据
     */
    protected function getExcel($fileName, $headArr, $data)
    {
        //导入PHPExcel类库，因为PHPExcel没有用命名空间，只能inport导入
        vendor("PHPExcel.PHPExcel");

        $date = date("Y_m_d", time());
        $fileName .= "_{$date}.xls";

        //创建PHPExcel对象，注意，不能少了\
        $objPHPExcel = new \PHPExcel();
        $objProps = $objPHPExcel->getProperties();

        //设置表头
        $key = ord("A");
        $num = ord("A");
        //print_r($headArr);exit;
        foreach ($headArr as $v) {
            $colum = chr($key);
            if ($key - 65 >= 26) {
                $key = ord("A");
                $colum = chr($num) . chr($key);
                $num++;
            }
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);
            $key += 1;
        }
        $column = 2;
        $objActSheet = $objPHPExcel->getActiveSheet();

        //print_r($data);exit;
        foreach ($data as $key => $rows) { //行写入
            $span = ord("A");
            $num = ord("A");
            $start_ch = chr($span);
            foreach ($rows as $keyName => $value) {// 列写入
                $j = chr($span);
                if ($span - 65 >= 26) {
                    $span = ord("A");
                    $j = chr($num) . chr($span);
                    $num++;
                }
                $objActSheet->setCellValue($j . $column, $value);
                $objActSheet->getColumnDimension($j)->setWidth(25);
                $span++;
            }
            $end_ch = $j;
            $column++;
        }
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objActSheet->getStyle($start_ch . "1:" . $end_ch . "1")->applyFromArray(array('font' => array('bold' => true)));
        $objPHPExcel->getActiveSheet()->getStyle('C')->getNumberFormat()->setFormatCode("@");
        $fileName = iconv("utf-8", "gb2312", $fileName);
        //重命名表
        //$objPHPExcel->getActiveSheet()->setTitle('test');
        //设置活动单指数到第一个表,所以Excel打开这是第一个表
        $objPHPExcel->setActiveSheetIndex(0);
        ob_end_clean();//清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"$fileName\"");
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output'); //文件通过浏览器下载
        exit;
    }

    /**
     * @creator Jimmy
     * @data 2016/8/22
     * @desc 数据导出到excel(csv文件)
     * @param string $filename 导出的csv文件名称 如date("Y年m月j日").'-PB机构列表.csv'
     * @param array $tileArray 所有列名称
     * @param array $dataArray 所有列数据
     */
    protected function exportToExcel($filename, $tileArray = [], $dataArray = [])
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        $date = date("Y_m_d", time());
        $filename .= "_{$date}.csv";
        ob_end_clean();
        ob_start();
        header("Content-Type: text/csv");
        header("Content-Disposition:filename=" . $filename);
        $fp = fopen('php://output', 'w');
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fp, $tileArray);
        $index = 0;
        foreach ($dataArray as $item) {
            if ($index == 1000) {
                $index = 0;
                ob_flush();
                flush();
            }
            $index++;
            fputcsv($fp, $item);
        }

        ob_flush();
        flush();
        ob_end_clean();
        exit;
    }


    /**
     * hjun
     * 2017年3月8日 09:02:16
     * 检查CURD权限
     *
     * 弃用 2017-09-09 20:40:37
     * @param string $limit 权限
     * @param int $type 请求类型，ajax还是普通请求
     * @return mixed
     */
    protected function checkCurd($limit = '', $type = 0)
    {
        return true;
    }



    protected function getIp()
    {
        $ip = '未知IP';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $this->is_ip($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $ip;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $this->is_ip($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $ip;
        } else {
            return $this->is_ip($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : $ip;
        }
    }

    protected function is_ip($str)
    {
        $ip = explode('.', $str);
        for ($i = 0; $i < count($ip); $i++) {
            if ($ip[$i] > 255) {
                return false;
            }
        }
        return preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $str);
    }



    public function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

}