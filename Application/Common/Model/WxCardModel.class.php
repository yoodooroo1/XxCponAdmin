<?php

namespace Common\Model;

/**
 * 微信会员卡
 * Class WxCardModel
 * @package Common\Model
 * User: hjun
 * Date: 2018-12-25 21:38:06
 * Update: 2018-12-25 21:38:06
 * Version: 1.00
 */
class WxCardModel extends BaseModel
{
    const USER_GET_CARD = 'user_get_card';
    const USER_DEL_CARD = 'user_del_card';
    const USER_GIFTING_CARD = 'user_gifting_card';
    const USER_VIEW_CARD = 'user_view_card';

    protected $tableName = 'mb_store_member_wx_card';

    public function handleEvent($event = self::USER_GET_CARD, $xmlObj)
    {
        switch ($event) {
            case self::USER_GET_CARD:
                $this->handleUserGetCard($xmlObj);
                break;
            case self::USER_DEL_CARD:
                $this->handleUserDelCard($xmlObj);
                break;
            case self::USER_GIFTING_CARD:
                $this->handleUserGiftCard($xmlObj);
                break;
            case self::USER_VIEW_CARD:
                $this->handleUserViewCard($xmlObj);
                break;
            default:
                break;
        }
    }

    /**
     * 处理用户领取卡券
     * @param $xmlObj
     * User: hjun
     * Date: 2018-12-25 22:24:56
     * Update: 2018-12-25 22:24:56
     * Version: 1.00
     */
    public function handleUserGetCard($xmlObj)
    {
        $data = jsonDecodeToArr(jsonEncode($xmlObj));
        logWrite("领券:" . jsonEncode($data));
        $this->startTrans();
        // 解析场景值
        $outerStr = explode('@', $data['OuterStr']);
        $storeId = $outerStr[0];
        $memberId = $outerStr[2];
        $openId = $data['FromUserName'];
        $cardId = $data['CardId'];
        $where = [];
        $where['store_id'] = $storeId;
        $where['member_id'] = $memberId;
        $where['openid'] = $openId;
        $where['card_id'] = $cardId;
        $info = $this->where($where)->lock(true)->find();
        if (!empty($info)) {
            $this->commit();
            logWrite("领取完毕");
            exit('SUCCESS');
        }
        $result = $this->addMemberCard($data);
        if (false === $result) {
            $this->rollback();
            exit('FAIL');
        }
        $this->commit();
        exit('SUCCESS');
    }

    public function handleUserDelCard($xmlObj)
    {
        $data = jsonDecodeToArr(jsonEncode($xmlObj));
        logWrite("删除:" . jsonEncode($data));
    }

    public function handleUserGiftCard($xmlObj)
    {
        $data = jsonDecodeToArr(jsonEncode($xmlObj));
        logWrite("转增:" . jsonEncode($data));
    }

    public function handleUserViewCard($xmlObj)
    {
        $data = jsonDecodeToArr(jsonEncode($xmlObj));
        logWrite("进入:" . jsonEncode($data));
    }

    /**
     * 获取用户会员卡信息
     * @param int $memberId
     * @param string $openId
     * @return array
     * User: hjun
     * Date: 2018-12-25 21:49:44
     * Update: 2018-12-25 21:49:44
     * Version: 1.00
     */
    public function getMemberCard($memberId = 0, $openId = '')
    {
        $storeId = $this->getStoreId();
        $result = D('Store')->getStoreInfo($storeId);
        $storeInfo = $result['data'];
        $where = [];
        $where['store_id'] = $this->getStoreId();
        $where['member_id'] = $memberId;
        $where['openid'] = $openId;
        $where['card_id'] = $storeInfo['wx_card_id'];
        $info = $this->where($where)->find();
        return $info;
    }

    /**
     * 添加会员卡
     * @param array $request
     * @return mixed
     * User: hjun
     * Date: 2018-12-25 22:27:38
     * Update: 2018-12-25 22:27:38
     * Version: 1.00
     */
    public function addMemberCard($request = [])
    {
        $outerStr = explode('@', $request['OuterStr']);
        $from = $outerStr[0];
        $storeId = $outerStr[1];
        $memberId = $outerStr[2];
        $data = [];
        $data['store_id'] = $storeId;
        $data['member_id'] = $memberId;
        $data['openid'] = $request['FromUserName'];
        $data['card_id'] = $request['CardId'];
        $data['card_code'] = $request['UserCardCode'];
        $data['outer_str'] = $from;
        $data['create_time'] = $request['CreateTime'];
        $result = $this->add($data);
        return $result;
    }
}