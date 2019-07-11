<?php

namespace Common\PHPUnit\Wx;

use Common\PHPUnit\BaseTest;
use Common\Util\BaseInfo;
use Common\Util\DateInfo;
use Common\Util\Signature;
use Common\Util\Sku;
use Common\Util\WxApi;
use Common\Util\WxCard;

class WxApiTest extends BaseTest
{
    public function testCreateCard()
    {
        // 创建会员卡
        $json = file_get_contents(DATA_PATH . '/no_diy_code_no_jump.json');
        $wxApi = new WxApi(6666);
        $result = $wxApi->createCard($json);
        if (!isSuccess($result)) {
            return null;
        }
        /*// 设置填写字段
        $cardId = $result['data']['card_id'];
        $json = file_get_contents(DATA_PATH . '/jump_field.json');
        $json = str_replace('{CARD_ID}', $cardId, $json);
        $result = $wxApi->setActiveUserForm($json);
        if (!isSuccess($result)) {
            return null;
        }
        // 获取开卡组件链接
        $result = $wxApi->getUserCreateMemberCardUrl($cardId, 'test');*/
        dump($result);
    }

    public function testCreateCard2()
    {
        // 创建会员卡
        $json = file_get_contents(DATA_PATH . '/diy_code_no_jump.json');
        $wxApi = new WxApi(6666);
        $result = $wxApi->createCard($json);
        dump($result);
    }

    public function testSetWhite()
    {
        $wxApi = new WxApi(6666);
        $result = $wxApi->setCardWhite([], ['huangjun1263']);
        dump($result);
    }

    public function testGetCardList()
    {
        $wxApi = new WxApi(6666);
        $result = $wxApi->getActiveCardList();
        dump($result);
    }

    public function testDeleteCard()
    {
        $wxApi = new WxApi(6666);
        $result = $wxApi->getActiveCardList();
        $ids = $result['data']['card_id_list'];
        foreach ($ids as $id) {
            $wxApi->deleteCard($id);
        }
    }

    public function testGetCardQRCode()
    {
        $wxApi = new WxApi(16801);
        $result = $wxApi->getTakeOneCardQRCode('pd8av52lJpu8QwVHYk-PofKqYaK4');
        dump($result);
    }

    public function testShortUrl()
    {
        $wxApi = new WxApi(16801);
        $result = $wxApi->getShortUrl('http://700109.duinin.com/index.php?se=16875&c=Common&a=takeWxCard');
        dump($result);
    }

    public function testCard()
    {
        //------------------------set base_info-----------------------------
        $base_info = new BaseInfo("http://www.supadmin.cn/uploads/allimg/120216/1_120216214725_1.jpg", "海底捞",
            0, "132元双人火锅套餐", "Color010", "使用时向服务员出示此券", "020-88888888",
            "不可与其他优惠同享\n 如需团购券发票，请在消费时向商户提出\n 店内均可使用，仅限堂食\n 餐前不可打包，餐后未吃完，可打包\n 本团购券不限人数，建议2人使用，超过建议人数须另收酱料费5元/位\n 本单谢绝自带酒水饮料", new DateInfo(1, 1397577600, 1399910400), new Sku(50000000));
        $base_info->set_sub_title("");
        $base_info->set_use_limit(1);
        $base_info->set_get_limit(3);
        $base_info->set_use_custom_code(false);
        $base_info->set_bind_openid(false);
        $base_info->set_can_share(true);
        $base_info->set_url_name_type(1);
        $base_info->set_custom_url("http://www.qq.com");
        //---------------------------set_card--------------------------------

        $card = new WxCard("MEMBER_CARD", $base_info);
        $memberCard = $card->get_card();


        //--------------------------to json--------------------------------
        $json = $card->toJson();

        //----------------------check signature------------------------
        $signature = new Signature();
        $signature->add_data("875e5cc094b78f230b0588c2a5f3c49f");
        $signature->add_data("wx57bf46878716c27e");
        $signature->add_data("213168808");
        $signature->add_data("12345");
        $signature->add_data("55555");
        $sign = $signature->get_signature();
    }
}