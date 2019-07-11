<?php

namespace Common\Model;

use Common\Util\WxApi;

/**
 * Class PendingOrderModel.class.php
 * User: hj
 * Date: 2017-10-27 00:27:51
 * Desc: 挂单方案
 * Update: 2017-10-27 00:27:54
 * Version: 1.0
 * @package Common\Model
 */
class PendingOrderModel extends BaseModel
{
    protected $tableName = 'mb_pendingorder';

    /**
     * 获取挂单方案的数据
     * @param int $pendId
     * @return array
     * User: hjun
     * Date: 2019-03-19 09:59:10
     * Update: 2019-03-19 09:59:10
     * Version: 1.00
     */
    public function getPendData($pendId = 0)
    {
        $field = [
            'a.*',
            'b.id num_id', 'b.name'
        ];
        $field = implode(',', $field);
        $where = [];
        $where['a.id'] = $pendId;
        $join = [
            'LEFT JOIN __MB_PENDINGORDER_NUMS__ b ON a.id = b.pend_order_id'
        ];
        $options = [];
        $options['alias'] = 'a';
        $options['field'] = $field;
        $options['join'] = $join;
        $options['where'] = $where;
        $list = $this->selectList($options);
        $data = $list[0];
        $data['pend_num'] = [];
        if (count($list) === 1) {
            return $data;
        }
        foreach ($list as $key => $value) {
            $data['pend_num'][] = [
                'id' => $value['num_id'],
                'text' => $value['name'],
            ];
        }
        return $data;
    }

    /**
     * 生成小程序码压缩包
     * @param int $pendId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2019-03-19 11:09:56
     * Update: 2019-03-19 11:09:56
     * Version: 1.00
     */
    public function buildMiniCode($pendId = 0)
    {
        $pendData = $this->getPendData($pendId);
        if (!empty($pendData['mini_code'])) {
            $data['url'] = $pendData['mini_code'];
            return getReturn(CODE_SUCCESS, 'success', $data);
        }
        if (empty($pendData['pend_num'])) {
            return getReturn(CODE_ERROR, '当前挂单方案暂无序号,无法生成小程序码');
        }
        $util = new WxApi($this->getStoreId(), 'mini');
        $basePath = DATA_PATH . "/mini_code/{$pendId}/";
        foreach ($pendData['pend_num'] as $key => $value) {
            $index = $key + 1;
            $result = $util->getMiniQRCode('pages/classify/classify', ['pend_num_id' => $value['id']]);
            if (!$util->isSuccess($result)) {
                $error = $util->getErrorMsg($result);
                return getReturn(CODE_ERROR, "生成{$value['text']}的小程序码错误,{$error}");
            }
            // 临时生成图片文件
            $path = "{$basePath}{$index}.png";
            makeDir($path);
            file_put_contents($path, $result);
            $this->execute("select 1");
        }
        // 生成压缩包
        $zipPath = "{$basePath}code.zip";
        zipDir($basePath, $zipPath);
        // 压缩完成之后上传至文件服务器
        $result = uploadLocalFileToRemote($zipPath, 11);
        // 上传完成之后删除本地文件
        del_dir_file($basePath, true);
        // 判断结果 存储压缩包路径
        $result = jsonDecodeToArr($result);
        if (!$result['result'] === 0) {
            return getReturn(CODE_ERROR, '下载文件失败,请重试');
        }
        $where = [];
        $where['id'] = $pendId;
        $this->where($where)->setField('mini_code', $result['datas']['ori_url']);
        $data['url'] = $result['datas']['ori_url'];
        return getReturn(CODE_SUCCESS, 'success', $data);
    }

    /**
     * 重置小程序码
     * @param int $pendId
     * @return array ['code'=>200, 'msg'=>'', 'data'=>null]
     * User: hjun
     * Date: 2019-04-22 15:28:07
     * Update: 2019-04-22 15:28:07
     * Version: 1.00
     */
    public function resetMiniCode($pendId = 0)
    {
        $pendData = $this->getPendData($pendId);
        if (empty($pendData['mini_code'])) {
            return getReturn(CODE_ERROR, '未生成小程序码,无需重置');
        }
        $where = [];
        $where['id'] = $pendId;
        $result = $this->where($where)->setField('mini_code', '');
        if (false === $result) {
            return getReturn(CODE_ERROR);
        }
        // 删除远程服务器上的文件
        $result = delRemoteFile($pendData['mini_code']);
        return getReturn(CODE_SUCCESS, '重置成功', $result);
    }
}