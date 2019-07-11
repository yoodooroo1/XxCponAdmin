<?php

/*
微信卡包api SDK V1.0
!!README!!：
base_info的构造函数的参数是必填字段，有set接口的可选字段。
针对某一种卡的必填字段（参照文档）仍然需要手动set（比如团购券Groupon的deal_detail），通过card->get_card()拿到card的实体对象来set。
ToJson就能直接转换为符合规则的json。
Signature是方便生成签名的类，具体用法见示例。
注意填写的参数是int还是string或者bool或者自定义class。
更具体用法见最后示例test，各种细节以最新文档为准。
*/

namespace Common\Util;

class WxCard
{
    const CARD_TYPE_GENERAL_COUPON = 'GENERAL_COUPON';
    const CARD_TYPE_GROUPON = 'GROUPON';
    const CARD_TYPE_DISCOUNT = 'DISCOUNT';
    const CARD_TYPE_GIFT = 'GIFT';
    const CARD_TYPE_CASH = 'CASH';
    const CARD_TYPE_MEMBER_CARD = 'MEMBER_CARD';
    const CARD_TYPE_SCENIC_TICKET = 'SCENIC_TICKET';
    const CARD_TYPE_MOVIE_TICKET = 'MOVIE_TICKET';

    //工厂
    private $CARD_TYPE = Array("GENERAL_COUPON",
        "GROUPON", "DISCOUNT",
        "GIFT", "CASH", "MEMBER_CARD",
        "SCENIC_TICKET", "MOVIE_TICKET");

    /**
     * 入口名称
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * 入口右侧提示语
     * @param string $tips
     */
    public function setTips($tips)
    {
        $this->tips = $tips;
    }

    /**
     * 入口跳转链接
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    function __construct($card_type, $base_info)
    {
        if (!in_array($card_type, $this->CARD_TYPE))
            exit("CardType Error");
        if (!$base_info instanceof BaseInfo)
            exit("base_info Error");
        $this->card_type = $card_type;
        switch ($card_type) {
            case $this->CARD_TYPE[0]:
                $this->general_coupon = new GeneralCoupon($base_info);
                break;
            case $this->CARD_TYPE[1]:
                $this->groupon = new Groupon($base_info);
                break;
            case $this->CARD_TYPE[2]:
                $this->discount = new Discount($base_info);
                break;
            case $this->CARD_TYPE[3]:
                $this->gift = new Gift($base_info);
                break;
            case $this->CARD_TYPE[4]:
                $this->cash = new Cash($base_info);
                break;
            case $this->CARD_TYPE[5]:
                $this->member_card = new MemberCard($base_info);
                break;
            case $this->CARD_TYPE[6]:
                $this->scenic_ticket = new ScenicTicket($base_info);
                break;
            case $this->CARD_TYPE[8]:
                $this->movie_ticket = new MovieTicket($base_info);
                break;
            default:
                exit("CardType Error");
        }
        return true;
    }

    function get_card()
    {
        switch ($this->card_type) {
            case $this->CARD_TYPE[0]:
                return $this->general_coupon;
            case $this->CARD_TYPE[1]:
                return $this->groupon;
            case $this->CARD_TYPE[2]:
                return $this->discount;
            case $this->CARD_TYPE[3]:
                return $this->gift;
            case $this->CARD_TYPE[4]:
                return $this->cash;
            case $this->CARD_TYPE[5]:
                return $this->member_card;
            case $this->CARD_TYPE[6]:
                return $this->scenic_ticket;
            case $this->CARD_TYPE[8]:
                return $this->movie_ticket;
            default:
                exit("GetCard Error");
        }
    }

    function toJson()
    {
        $data = [
            'card' => $this
        ];
        return urldecode(jsonEncode($data));
    }
}

class Sku
{
    function __construct($quantity)
    {
        $this->quantity = $quantity;
    }
}

class DateInfo
{
    function __construct($type, $arg0, $arg1 = null)
    {
        if (!is_int($type))
            exit("DateInfo.type must be integer");
        $this->type = $type;
        if ($type == 1)  //固定日期区间
        {
            if (!is_int($arg0) || !is_int($arg1))
                exit("begin_timestamp and  end_timestamp must be integer");
            $this->begin_timestamp = $arg0;
            $this->end_timestamp = $arg1;
        } else if ($type == 2)  //固定时长（自领取后多少天内有效）
        {
            if (!is_int($arg0))
                exit("fixed_term must be integer");
            $this->fixed_term = $arg0;
        } else
            exit("DateInfo.tpye Error");
    }
}

class BaseInfo
{
    // Code展示类型
    const CODE_TYPE_TEXT = 'CODE_TYPE_TEXT'; // 文本 仅适用于输码核销
    const CODE_TYPE_BARCODE = 'CODE_TYPE_BARCODE'; // 一维码 适用于扫码/输码核销
    const CODE_TYPE_QRCODE = 'CODE_TYPE_QRCODE'; // 二维码 适用于扫码/输码核销
    const CODE_TYPE_ONLY_QRCODE = 'CODE_TYPE_ONLY_QRCODE'; // 仅显示二维码 仅适用于扫码核销
    const CODE_TYPE_ONLY_BARCODE = 'CODE_TYPE_ONLY_BARCODE'; // 仅显示一维码
    const CODE_TYPE_NONE = 'CODE_TYPE_NONE'; // 不显示任何码型 仅适用于线上核销，开发者须自定义跳转链接跳转至H5页面，允许用户核销掉卡券，自定义cell的名称可以命名为“立即使用”

    /**
     * BaseInfo constructor.
     * @param string(128) $logo_url 卡券的商户logo，建议像素为300*300
     * @param string(12) $brand_name 商户名字,字数上限为12个汉字
     * @param string(16) $code_type Code展示类型
     * @param string $title 卡券名，字数上限为9个汉字 (建议涵盖卡券属性、服务及金额)
     * @param string $color 券颜色。按色彩规范标注填写Color010-Color100
     * @param string $notice 卡券使用提醒，字数上限为16个汉字
     * @param string(24) $service_phone 客服电话
     * @param string $description 卡券使用说明，字数上限为1024个汉字
     * @param DateInfo $date_info 使用日期，有效期的信息
     * @param Sku $sku 商品信息
     */
    function __construct($logo_url, $brand_name, $code_type, $title, $color, $notice, $service_phone, $description, $date_info, $sku)
    {
        if (!$date_info instanceof DateInfo)
            exit("date_info Error");
        if (!$sku instanceof Sku)
            exit("sku Error");
        $CODE_TYPE = [
            self::CODE_TYPE_TEXT, self::CODE_TYPE_BARCODE, self::CODE_TYPE_QRCODE, self::CODE_TYPE_ONLY_QRCODE,
            self::CODE_TYPE_ONLY_BARCODE, self::CODE_TYPE_NONE,
        ];
        if (!in_array($code_type, $CODE_TYPE))
            exit("code_type error");
        $this->logo_url = $logo_url;
        $this->brand_name = $brand_name;
        $this->code_type = $code_type;
        $this->title = $title;
        $this->color = $color;
        $this->notice = $notice;
        $this->service_phone = $service_phone;
        $this->description = $description;
        $this->date_info = $date_info;
        $this->sku = $sku;
    }

    function set_sub_title($sub_title)
    {
        $this->sub_title = $sub_title;
    }

    /**
     * 每人可核销的数量限制,不填写默认为50
     * @param int $use_limit
     * User: hjun
     * Date: 2018-12-07 15:03:55
     * Update: 2018-12-07 15:03:55
     * Version: 1.00
     */
    function set_use_limit($use_limit = 50)
    {
        if (!is_int($use_limit))
            exit("use_limit must be integer");
        $this->use_limit = $use_limit;
    }

    /**
     * 每人可领券的数量限制,不填写默认为50
     * @param int $get_limit
     * User: hjun
     * Date: 2018-12-07 15:04:13
     * Update: 2018-12-07 15:04:13
     * Version: 1.00
     */
    function set_get_limit($get_limit = 50)
    {
        if (!is_int($get_limit))
            exit("get_limit must be integer");
        $this->get_limit = $get_limit;
    }

    /**
     * Code码可由微信后台随机分配，同时支持商户自定义
     *
     * 自定义Code码
     * 通常为商户在现有业 务已有一套Code码体系。
     * "use_custom_code"：true ，仅支持API创建
     * 卡券投放接口中填入code字段值
     * 仅支持调用API接口核销
     *
     * 非自定义Code码
     * 可使用微信的Code码体 系完成投放、核销。
     * "use_custom_code"：false ，支持API创建、公众平台创建 （默认为非自定义Code码）
     * 卡券投放接口中无需填写code字段，由微信后台分配
     *    支持卡券核销助手公众号核销、公众平台网页核销、API接口核销
     *
     * 导入code模式
     * 商户须用自己的code码体系 ，且要通过微信渠道下发卡券 （如：二维码/群发／货架等）
     * "use_custom_code":tru e且get_custom_code_mode: " GET_CUSTOM_CODE_MODE_DEPOSIT "
     * 卡券侧随机在导入的code中下发，不可指定，投放接口不可传code字段
     * 核销时许同时传入card_id和code，仅支持API
     *
     *
     * @param boolean $use_custom_code
     * User: hjun
     * Date: 2018-12-07 14:56:51
     * Update: 2018-12-07 14:56:51
     * Version: 1.00
     */
    function set_use_custom_code($use_custom_code = false)
    {
        $this->use_custom_code = $use_custom_code;
    }

    /**
     * 是否指定用户领取，填写true或false 。默认为false。通常指定特殊用户群体 投放卡券或防止刷券时选择指定用户领取。
     * @param boolean $bind_openid
     * User: hjun
     * Date: 2018-12-07 15:02:40
     * Update: 2018-12-07 15:02:40
     * Version: 1.00
     */
    function set_bind_openid($bind_openid = false)
    {
        $this->bind_openid = $bind_openid;
    }

    /**
     * 卡券领取页面是否可分享。
     * @param boolean $can_share
     * User: hjun
     * Date: 2018-12-07 15:04:43
     * Update: 2018-12-07 15:04:43
     * Version: 1.00
     */
    function set_can_share($can_share = false)
    {
        $this->can_share = $can_share;
    }

    /**
     * 门店位置poiid。 调用 POI门店管理接 口 获取门店位置poiid。具备线下门店 的商户为必填。
     * @param array $location_id_list
     * User: hjun
     * Date: 2018-12-07 15:03:20
     * Update: 2018-12-07 15:03:20
     * Version: 1.00
     */
    function set_location_id_list($location_id_list = [])
    {
        $this->location_id_list = $location_id_list;
    }

    function set_url_name_type($url_name_type)
    {
        if (!is_int($url_name_type))
            exit("url_name_type must be int");
        $this->url_name_type = $url_name_type;
    }

    /**
     * 自定义跳转的URL
     * @param string $custom_url
     * User: hjun
     * Date: 2018-12-07 15:02:15
     * Update: 2018-12-07 15:02:15
     * Version: 1.00
     */
    function set_custom_url($custom_url = '')
    {
        $this->custom_url = $custom_url;
    }
}

class CardBase
{
    public function __construct($base_info)
    {
        $this->base_info = $base_info;
    }
}

class GeneralCoupon extends CardBase
{
    function set_default_detail($default_detail)
    {
        $this->default_detail = $default_detail;
    }
}

class Groupon extends CardBase
{
    function set_deal_detail($deal_detail)
    {
        $this->deal_detail = $deal_detail;
    }
}

class Discount extends CardBase
{
    function set_discount($discount)
    {
        $this->discount = $discount;
    }
}

class Gift extends CardBase
{
    function set_gift($gift)
    {
        $this->gift = $gift;
    }
}

class Cash extends CardBase
{
    function set_least_cost($least_cost)
    {
        $this->least_cost = $least_cost;
    }

    function set_reduce_cost($reduce_cost)
    {
        $this->reduce_cost = $reduce_cost;
    }
}

class MemberCard extends CardBase
{
    // 必设置字段
    public $supply_bonus = false; // 显示积分
    public $supply_balance = false; // 储值
    public $prerogative = '会员特权说明'; // 特权说明

    /**
     * 显示积分，填写true或false，
     * 如填写true，积分相关字段均为必填
     * 若设置为true则后续不可以被关闭
     * @param boolean $supply_bonus
     * User: hjun
     * Date: 2018-12-07 14:38:20
     * Update: 2018-12-07 14:38:20
     * Version: 1.00
     */
    function set_supply_bonus($supply_bonus = false)
    {
        $this->supply_bonus = $supply_bonus;
    }

    /**
     * 是否支持储值，填写true或false。
     * 如填写true，储值相关字段均为必 填
     * 若设置为true则后续不可以被关闭。该字段须开通储值功能后方可使用， 详情见： 获取特殊权限
     * @param boolean $supply_balance
     * User: hjun
     * Date: 2018-12-07 14:39:03
     * Update: 2018-12-07 14:39:03
     * Version: 1.00
     */
    function set_supply_balance($supply_balance = false)
    {
        $this->supply_balance = $supply_balance;
    }

    /**
     * 积分清零规则。
     * @param string(128) $bonus_cleared
     * User: hjun
     * Date: 2018-12-07 14:39:41
     * Update: 2018-12-07 14:39:41
     * Version: 1.00
     */
    function set_bonus_cleared($bonus_cleared = '')
    {
        $this->bonus_cleared = $bonus_cleared;
    }

    /**
     * 积分规则。
     * @param string(128) $bonus_rules
     * User: hjun
     * Date: 2018-12-07 14:41:11
     * Update: 2018-12-07 14:41:11
     * Version: 1.00
     */
    function set_bonus_rules($bonus_rules = '')
    {
        $this->bonus_rules = $bonus_rules;
    }

    /**
     * 储值说明
     * @param string(128) $balance_rules
     * User: hjun
     * Date: 2018-12-07 14:41:32
     * Update: 2018-12-07 14:41:32
     * Version: 1.00
     */
    function set_balance_rules($balance_rules = '')
    {
        $this->balance_rules = $balance_rules;
    }

    /**
     * 会员卡特权说明,限制1024汉字
     * @param string(3072) $prerogative
     * User: hjun
     * Date: 2018-12-07 14:42:09
     * Update: 2018-12-07 14:42:09
     * Version: 1.00
     */
    function set_prerogative($prerogative = '')
    {
        $this->prerogative = $prerogative;
    }

    function set_bind_old_card_url($bind_old_card_url)
    {
        $this->bind_old_card_url = $bind_old_card_url;
    }

    /**
     * 激活会员卡的url
     * @param string(128) $activate_url
     * User: hjun
     * Date: 2018-12-07 14:43:09
     * Update: 2018-12-07 14:43:09
     * Version: 1.00
     */
    function set_activate_url($activate_url = '')
    {
        $this->activate_url = $activate_url;
    }
}

class ScenicTicket extends CardBase
{
    function set_ticket_class($ticket_class)
    {
        $this->ticket_class = $ticket_class;
    }

    function set_guide_url($guide_url)
    {
        $this->guide_url = $guide_url;
    }
}

class MovieTicket extends CardBase
{
    function set_detail($detail)
    {
        $this->detail = $detail;
    }
}

class Signature
{
    function __construct()
    {
        $this->data = array();
    }

    function add_data($str)
    {
        array_push($this->data, (string)$str);
    }

    function get_signature()
    {
        sort($this->data, SORT_STRING);
        return sha1(implode($this->data));
    }
}