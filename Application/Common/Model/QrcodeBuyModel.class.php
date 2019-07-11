<?php

namespace Common\Model;

class QrcodeBuyModel extends BaseModel
{
    protected $tableName = 'mb_qrcode_buy';

    /**
     * 获取扫码的商品编号
     * @param $buyCode
     * @return array
     * User: czx
     * Date: 2018/3/19 9:49:24
     * Update: 2018/3/19 9:49:24
     * Version: 1.00
     */
    public function getQrcodeBuyGoodsId($buyCode)
    {
        $qrcodeOrder = M('mb_qrcode_buy')->where(array('code' => $buyCode))->find();
        $goodsIdArray = array();
        if (!empty(json_decode($qrcodeOrder['order_content'])[0]['goods_id'])){
          $goodsIdArray[] = json_decode($qrcodeOrder['order_content'])[0]['goods_id'];
        }
        return getReturn(200,'成功', $goodsIdArray);
    }

}