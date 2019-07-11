<?php
namespace Common\Common;

vendor('ApiSms.CCPRestSmsSDK');
class SendSMS {

    private $channel_num;// 当前总通道数

//    /**
//     * @var class
//     * 云之讯的类
//     */
//    private $ucpass;
//    /**
//     * @var string
//     * 开发者账号内的应用的id
//     */
//    private $appId;
    /**
     * @var class
     * 云通讯的类
     */
    private $ytxREST;
    private $sms_header;
    private $youe_account;
    private $youe_password;
    private $app_down_url;
    private $domain_name;
    private $is_zxb;
    private $is_qqcg;
    private $is_xunxin;
    private $is_lyh;
    private $is_mj;
    //mj add by czx
    private $client_id = "40edc0fc-5ffd-4eef-a179-a4e96d6fdb47";
    private $client_secret = "fe46c3aa-6faf-48df-a977-7d836580abd5";
    //private $send_url = "http://60.10.135.213:18089/paas/";
    private $send_url = "http://60.10.135.213:18089/paas/";

    private $store_id;
    private $channel_id;

    public function __construct($store_id = 0, $channel_id = 0, $platform_type = 0) {
//        $options=array();
//        $options['accountsid']='244228c98f71b7268b144a37daaeebc0';
//        $options['token']='90828a0f718d58d16ce7a636cebe770d';
//        $this->ucpass = new Ucpaas($options);
//        $this->appId = "084072b3d135480fba064d252ae3bd89";

        $this->store_id = $store_id;
        $this->channel_id = $channel_id;
        if ($channel_id == 40) {
            //主帐号,对应开官网发者主账号下的 ACCOUNT SID
            $accountSid = '8a216da86010e69001603a4a07b9126b';
            //主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
            $accountToken = 'a347547c99af41908386248d388b2bf2';
            //应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
            $ytxAppId = '8a216da86010e69001603a4a08131272';
        } else if ($channel_id == 33) {
            //主帐号,对应开官网发者主账号下的 ACCOUNT SID
            $accountSid = '8aaf070858af38370158bd1345490943';
            //主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
            $accountToken = '1513335010754f58b2d82c5564d4fad4';
            //应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
            $ytxAppId = '8aaf070858af38370158bd1345cc0947';
        } else if ($platform_type == 2) {
            //主帐号,对应开官网发者主账号下的 ACCOUNT SID
            $accountSid= '8aaf07085e08f898015e0dc0677701ea';
            //主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
            $accountToken= '02ef982c65574c37ab7c3d48f0e868f4';
            //应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
            $ytxAppId='8a216da85e4be3b3015e4c5c3b4c0076';
        }else if($channel_id == 58){
            $this->client_id = "40edc0fc-5ffd-4eef-a179-a4e96d6fdb47";
            $this->client_secret = "fe46c3aa-6faf-48df-a977-7d836580abd5";
        } else {
            //主帐号,对应开官网发者主账号下的 ACCOUNT SID
            $accountSid= '8aaf07085e08f898015e0dc0677701ea';
            //主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
            $accountToken= '02ef982c65574c37ab7c3d48f0e868f4';
            //应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
            $ytxAppId='8aaf07085e08f898015e0dc0690301f1';
        }
        //请求地址
        //生产环境（用户应用上线使用）：app.cloopen.com
        $serverIP='app.cloopen.com';
        //请求端口，生产环境和沙盒环境一致
        $serverPort='8883';
        //REST版本号，在官网文档REST介绍中获得。
        $softVersion='2013-12-26';

        $this->ytxREST = new \REST($serverIP,$serverPort,$softVersion);
        $this->ytxREST->setAccount($accountSid,$accountToken);
        $this->ytxREST->setAppId($ytxAppId);


        if ($channel_id == 20 || $channel_id == 8) {
            $this->channel_num = 1;
            // output_error(101, '短信发送失败，正在升级维护。','');
        } else {
            $this->channel_num = 2;
        }

        if ($channel_id == 33) {
            $this->is_zxb = 1;
            $this->channel_num = 1;
        }
        if ($channel_id == 40) {
            $this->is_lyh = 1;
            $this->channel_num = 1;
        }


        $bean = Model('mb_channel')->field("sms_header,youe_account,youe_password,app_down_url,domain_name")->where(array('channel_id'=>$channel_id))->find();


        if (empty($bean['sms_header'])) {
            $this->sms_header = '迅信';
        } else {
            $this->sms_header = $bean['sms_header'];

        }

        if ($platform_type == 2) {
            $this->sms_header = '全球采购';
            $this->is_qqcg = 1;
        } else {
            $this->is_qqcg = 0;
            if ($channel_id == 0) {
                $this->is_xunxin = 1;
            } else {
                $this->is_xunxin = 0;
            }
        }

        if (empty($bean['youe_account'])) {
            $this->channel_num = 1;
        } else {
            $this->youe_account = $bean['youe_account'];
        }
        if (empty($bean['youe_password'])) {
            $this->channel_num = 1;
        } else {
            $this->youe_password = $bean['youe_password'];
        }

        if (empty($bean['app_down_url'])) {
            $this->app_down_url = '';
        } else {
            $this->app_down_url = 'APP下载:'.$bean['app_down_url'];
        }

        if (empty($bean['domain_name'])) {
            $this->domain_name = '';
        } else {
            $this->domain_name = $bean['domain_name'];
        }
        if ($channel_id == 58){
            $this->is_mj = 1;
            $this->sms_header = '魔集商城';
            $this->channel_num = 1;
        }
    }

    /**
     * 发送给新会员帐号密码优惠劵等信息
     * @param $bizName 商家名称
     * @param $tel 会员电话
     * @param $mima 密码
     * @param $down_url 下载地址
     * @param $yhq_desc 优惠券详情
     * @return string
     * @throws Exception
     */
    public function sendNewMemberSms($bizName, $tel, $mima, $down_url, $yhq_desc, $channel=0) {
        $result = $this->checkStoreSms();
        if (!empty($result) && $result['status'] == -1) {
            return $result['error'];
        }
        $this->check_excessive($tel);
        $channel_id = $channel % $this->channel_num;
        $bizName = mb_convert_encoding($bizName,'utf-8','auto');
        $yhq_desc = mb_convert_encoding($yhq_desc,'utf-8','auto');

        // switch ($channel_id)
        // {
        //     case 0:
        if (empty($bizName)) {
            if ($this->is_zxb == 1) {
                $result = $this->ytxREST->sendTemplateSMS($tel,array($tel,$mima,$down_url),"138568");
                if($result == NULL ) {
                    return "result error!";
                }
                if($result->statusCode!=0) {
                    return $result->statusMsg;
                }else{
//                    $this->removeStoreSms();
                    $this->add_excessive($tel);
                    return "000000";
                }
            } else {
                $msg = "【".$this->sms_header."】注册成功,账号".$tel.",密码".$mima.",下载APP马上拥有会员特权".$down_url;
                if ($this->is_mj == 1){
                    $result_code = $this->smsMjSend($tel,$msg);
                }else{
                    $result_code = $this->smsChinaSend($tel,$msg);
                }

            }
        } else {
            if (empty($mima)) {
                if (empty($yhq_desc)) {
                    return "case null";
                } else {
                    if ($this->is_zxb == 1) {
                        $result = $this->ytxREST->sendTemplateSMS($tel,array($bizName,$yhq_desc,$tel,$down_url),"137906");
                        if($result == NULL ) {
                            return "result error!";
                        }
                        if($result->statusCode!=0) {
                            return $result->statusMsg;
                        }else{
//                            $this->removeStoreSms();
                            $this->add_excessive($tel);
                            return "000000";
                        }
                    } else {
                        $msg = "【".$this->sms_header."】".$bizName."的会员,您好!".$yhq_desc."已奉送至您的会员账号".$tel."登陆即可享用!下载地址".$down_url;
                         if ($this->is_mj == 1){
                             $result_code = $this->smsMjSend($tel,$msg);
                        }else {
                             $result_code = $this->smsChinaSend($tel, $msg);
                         }
                    }
                }
            } else {
                if (empty($yhq_desc)) {
                    if ($this->is_zxb == 1) {
                        $result = $this->ytxREST->sendTemplateSMS($tel,array($bizName,$tel,$mima,$down_url),"138569");
                        if($result == NULL ) {
                            return "result error!";
                        }
                        if($result->statusCode!=0) {
                            return $result->statusMsg;
                        }else{
//                            $this->removeStoreSms();
                            $this->add_excessive($tel);
                            return "000000";
                        }
                    } else {
                        $msg = "【".$this->sms_header."】恭喜您成为".$bizName."的会员,账号".$tel.",密码".$mima."。下载".$this->sms_header."APP体验专属会员特权!".$down_url;
                        if ($this->is_mj == 1){
                            $result_code = $this->smsMjSend($tel,$msg);
                        }else {
                            $result_code = $this->smsChinaSend($tel, $msg);
                        }
                    }
                } else {
                    if ($this->is_zxb == 1) {
                        $result = $this->ytxREST->sendTemplateSMS($tel,array($bizName,$yhq_desc,$tel,$mima,$down_url),"137910");
                        if($result == NULL ) {
                            return "result error!";
                        }
                        if($result->statusCode!=0) {
                            return $result->statusMsg;
                        }else{
//                            $this->removeStoreSms();
                            $this->add_excessive($tel);
                            return "000000";
                        }
                    } else {
                        $msg = "【".$this->sms_header."】恭喜您成为".$bizName."的会员,".$yhq_desc."已奉送至您的会员账号".$tel.",密码".$mima."。下载".$this->sms_header."APP体验专属会员特权!".$down_url;
                         if ($this->is_mj == 1){
                             $result_code = $this->smsMjSend($tel,$msg);
                         }else {
                             $result_code = $this->smsChinaSend($tel, $msg);
                         }
                    }
                }
            }
        }
        if ($result_code == "000000") {
            $this->add_excessive($tel);
            $this->removeStoreSms();
        }
        return $result_code;
        //     default:
        //         return "case null";
        // }
    }

    /**
     * 发送建议短信给商家
     * @param $tel 商家电话
     * @param $bizName 商家名称
     * @param $sender 发送者名称
     * @param $stel 发送者电话
     * @return string
     * @throws Exception
     */
    public function sendAdviceSms($tel, $bizName, $sender, $stel,$channel=0) {

        $this->check_excessive($tel);
        $channel_id = $channel % $this->channel_num;
        $bizName = mb_convert_encoding($bizName,'utf-8','auto');
        $sender = mb_convert_encoding($sender,'utf-8','auto');
        $stel = mb_convert_encoding($stel,'utf-8','auto');
//        switch ($channel_id)
//        {
//
//            case 0:
        $msg = "【".$this->sms_header."】老板你好，我是".$sender."，我们给您提供了商城".$bizName."的装修方案，详情请进入".$this->sms_header."商户版查收，如有疑问请联系：".$stel."。";
        $result_code = $this->smsChinaSend($tel,$msg);
        if ($result_code == "000000") {
            $this->add_excessive($tel);
        }
        return $result_code;

//            case 1:
//                $templateId = "12125";
//                $content = json_decode($this->ucpass->templateSMS($this->appId,$tel,$templateId,$sender.",".$bizName.",".$stel),true);
//                $result_code = $content['resp']['respCode'];
//                return $result_code;
//            default:
//                return "case null";
//        }

    }

    /**
     * 发送订单提醒给商家
     * @param $member_tel 商家电话
     * @param $nowtime 下单时间
     * @param $order_id 订单id
     * @return string
     */
    public function sendOrderNotice($member_tel,$nowtime,$order_id,$channel=0) {
       // $this->check_excessive($member_tel);
        $channel_id = $channel % $this->channel_num;
        switch ($channel_id) {
//             case 0:
//                 if ($this->is_zxb == 1) {
//                     $result = $this->ytxREST->sendTemplateSMS($member_tel, array($nowtime, $order_id), "140525");
//                 } else if ($this->is_xunxin == 1) {
//                     $result = $this->ytxREST->sendTemplateSMS($member_tel, array($nowtime, $order_id), "201683");
//                 } else if ($this->is_qqcg == 1) {
//                     $result = $this->ytxREST->sendTemplateSMS($member_tel, array($nowtime, $order_id), "202672");
//                 } else {
//                     $msg = "【" . $this->sms_header . "】订单提醒：有会员在" . $nowtime . "提交了新的订单，请及时处理订单！订单号为：" . $order_id . "。";
//                     $result_code = $this->smsChinaSend($member_tel, $msg);
//                     return $result_code;
//                 }
//                 if ($result == NULL) {
//                     return "result error!";
//                 }
//                 if ($result->statusCode != 0) {
//                     return $result->statusMsg;
//                 } else {
//                     return "000000";
//                 }
            case 0:
                if ($this->is_lyh == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($member_tel, array($nowtime, $order_id), "223007");
                    if ($result == NULL) {
                        return "result error!";
                    }
                    if ($result->statusCode != 0) {
                        return $result->statusMsg;
                    } else {
                        return "000000";
                    }
                } else {
                    $msg = "【" . $this->sms_header . "】订单提醒：有会员在" . $nowtime . "提交了新的订单，请及时处理订单！订单号为：" . $order_id . "。";
                     if ($this->is_mj == 1){
                         $result_code = $this->smsMjSend($member_tel,$msg);
                     }else {
                         $result_code = $this->smsChinaSend($member_tel, $msg);
                     }
                    return $result_code;
                }

            //     case 1:
            //         $templateId = "11823";
            //         $content = json_decode($this->ucpass->templateSMS($this->appId,$member_tel,$templateId,$nowtime.",".$order_id),true);
            //         $result_code = $content['resp']['respCode'];
            //         return $result_code;
            default:
                return "case null";

        }
    }

    /**
     * 发送找回密码的验证码
     * @param $to 用户手机
     * @param $param 验证码
     * @return string
     */
    public function sendFindPassword($to,$param,$channel=0) {
        // $result = $this->checkStoreSms();
        if (!empty($result) && $result['status'] == -1) {
            return $result['error'];
        }
        $this->check_excessive($to);
        $channel_id = $channel % $this->channel_num;
        switch ($channel_id)
        {
            case 0:
                if ($this->is_lyh == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to,array($param),"223005");
                } else if ($this->is_zxb == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to,array($param),"138570");
                } else if ($this->is_xunxin == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to,array($param),"201675");
                } else if ($this->is_qqcg == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to,array($param),"202728");
                } else {
                    $msg = "【".$this->sms_header."】亲爱的会员，验证码为".$param."，您正在通过手机验证找回密码，此验证码5分钟内有效！若非本人操作，请忽略本短信。";
                     if ($this->is_mj == 1){
                         $result_code = $this->smsMjSend($to,$msg);
                     }else {
                         $result_code = $this->smsChinaSend($to, $msg);
                     }
                    if ($result_code == "000000") {
                    //     $this->removeStoreSms();
                        $this->add_excessive($to);
                    }
                    return $result_code;
                }
                if($result == NULL ) {
                    return "result error!";
                }
                if($result->statusCode!=0) {
                    return $result->statusMsg;
                }else{
//                    $this->removeStoreSms();
                    $this->add_excessive($to);
                    return "000000";
                }
            case 1:
                $msg = "【".$this->sms_header."】亲爱的会员，验证码为".$param."，您正在通过手机验证找回密码，此验证码5分钟内有效！若非本人操作，请忽略本短信。";
                 if ($this->is_mj == 1){
                     $result_code = $this->smsMjSend($to,$msg);
                }else {
                     $result_code = $this->smsChinaSend($to, $msg);
                 }
                if ($result_code == "000000") {
                //     $this->removeStoreSms();
                    $this->add_excessive($to);
                }
                return $result_code;
            case 58:
                $msg = "【".$this->sms_header."】亲爱的会员，验证码为".$param."，您正在通过手机验证找回密码，此验证码5分钟内有效！若非本人操作，请忽略本短信。";
                if ($this->is_mj == 1){
                    $result_code = $this->smsMjSend($to,$msg);
                }else {
                    $result_code = $this->smsChinaSend($to, $msg);
                }
                if ($result_code == "000000") {
                    //     $this->removeStoreSms();
                    $this->add_excessive($to);
                }
                return $result_code;
//            case 1:
//                $templateId = "5736";
//                $content = json_decode($this->ucpass->templateSMS($this->appId,$to,$templateId,$param),true);
//                $result_code = $content['resp']['respCode'];
//                return $result_code;
            default:
                return "case null";
        }
    }

    /**
     * 发送注册的验证码
     * @param $to 用户手机
     * @param $param 验证码
     * @return string
     */
    public function sendRegisterCode($to,$param,$channel=0) {
        // $result = $this->checkStoreSms();
        if (!empty($result) && $result['status'] == -1) {
            return $result['error'];
        }
        $this->check_excessive($to);
        $channel_id = $channel % $this->channel_num;
        switch ($channel_id)
        {
            case 0:
                if ($this->is_lyh == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to,array($param),"223004");
                } else if ($this->is_zxb == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to,array($param),"137876");
                } else if ($this->is_xunxin == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to,array($param),"201671");
                } else if ($this->is_qqcg == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to,array($param),"202729");
                } else {
                    $msg = "【".$this->sms_header."】亲爱的会员，验证码为".$param."，您正在使用手机快速注册，此验证码5分钟内有效！若非本人操作，请忽略本短信。";
                    if ($this->is_mj == 1){
                        $result_code = $this->smsMjSend($to,$msg);
                    }else {
                        $result_code = $this->smsChinaSend($to, $msg);
                    }
                    if ($result_code == "000000") {
                    //     $this->removeStoreSms();
                        $this->add_excessive($to);
                    }
                    return $result_code;
                }
                if($result == NULL ) {
                    return "result error!";
                }
                if($result->statusCode!=0) {
                    return $result->statusMsg;
                }else{
//                    $this->removeStoreSms();
                    $this->add_excessive($to);
                    return "000000";
                }
            case 1:
                $msg = "【".$this->sms_header."】亲爱的会员，验证码为".$param."，您正在使用手机快速注册，此验证码5分钟内有效！若非本人操作，请忽略本短信。";
                if ($this->is_mj == 1){
                    $result_code = $this->smsMjSend($to,$msg);
                }else {
                   $result_code = $this->smsChinaSend($to, $msg);
                }
                if ($result_code == "000000") {
                //     $this->removeStoreSms();
                    $this->add_excessive($to);
                }
                return $result_code;
//            case 1:
//                $templateId = "5735";
//                $content = json_decode($this->ucpass->templateSMS($this->appId,$to,$templateId,$param),true);
//                $result_code = $content['resp']['respCode'];
//                return $result_code;
            case 58:
                $msg = "【".$this->sms_header."】亲爱的会员，验证码为".$param."，您正在使用手机快速注册，此验证码5分钟内有效！若非本人操作，请忽略本短信。";
                $result_code = $this->smsChinaSend($to,$msg);
                if ($result_code == "000000") {
                    //     $this->removeStoreSms();
                    $this->add_excessive($to);
                }
                return $result_code;
            default:
                return "case null";
        }
    }


    /**
     * 验证码通用提醒
     * @param $to 用户手机
     * @param $param 验证码
     * @return string
     */
    public function sendCommonCode($to,$param,$channel=0) {
        // $result = $this->checkStoreSms();
        if (!empty($result) && $result['status'] == -1) {
            return $result['error'];
        }
        $this->check_excessive($to);
        $channel_id = $channel % $this->channel_num;
        switch ($channel_id)
        {
            case 0:
                if ($this->is_lyh == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to,array($param),"223006");
                } else if ($this->is_zxb == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to,array($param),"137882");
                } else if ($this->is_xunxin == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to,array($param),"201680");
                } else if ($this->is_qqcg == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to,array($param),"202730");
                } else {
                    $msg = "【".$this->sms_header."】验证码为".$param."，此验证码5分钟内有效！若非本人操作，请忽略本短信。";
                   if ($this->is_mj == 1){
                       $result_code = $this->smsMjSend($to, $msg);
                   }else {
                       $result_code = $this->smsChinaSend($to, $msg);
                   }
                    if ($result_code == "000000") {
                    //     $this->removeStoreSms();
                        $this->add_excessive($to);
                    }
                    return $result_code;
                }
                if($result == NULL ) {
                    return "result error!";
                }
                if($result->statusCode!=0) {
                    return $result->statusMsg;
                }else{
//                    $this->removeStoreSms();
                    $this->add_excessive($to);
                    return "000000";
                }
            case 1:
                $msg = "【".$this->sms_header."】验证码为".$param."，此验证码5分钟内有效！若非本人操作，请忽略本短信。";
                if ($this->is_mj == 1){
                    $result_code = $this->smsMjSend($to, $msg);
                }else {
                    $result_code = $this->smsChinaSend($to, $msg);
                }
                if ($result_code == "000000") {
                //     $this->removeStoreSms();
                    $this->add_excessive($to);
                }
                return $result_code;
            case 58:
                $msg = "【".$this->sms_header."】验证码为".$param."，此验证码5分钟内有效！若非本人操作，请忽略本短信。";
                if ($this->is_mj == 1){
                    $result_code = $this->smsMjSend($to, $msg);
                }else {
                    $result_code = $this->smsChinaSend($to, $msg);
                }
                if ($result_code == "000000") {
                    //     $this->removeStoreSms();
                    $this->add_excessive($to);
                }
                return $result_code;
//            case 1:
//                $templateId = "24553";
//                $content = json_decode($this->ucpass->templateSMS($this->appId,$to,$templateId,$param),true);
//                $result_code = $content['resp']['respCode'];
//                return $result_code;
            default:
                return "case null";
        }
    }
    /**
     * 发送开户短信给商家
     * @param $to 商家手机
     * @param $storename 商家名称
     * @param $username 账号
     * @param $password 密码
     * @return string
     */
    public function sendOpenAccount($to,$storename,$username,$password,$channel=0) {
        $this->check_excessive($to);
        $channel_id = $channel % $this->channel_num;
        if ($this->domain_name == 'dev.duinin.com') {
            if ($this->is_qqcg == 1) {
                $url = "http://t.cn/RpZAaoE";
            } else {
                $url = "http://t.cn/ROtnSyM";
            }
        } else {
            if ($this->is_qqcg == 1) {
                $url = "http://t.cn/RpZwtnL";
            } else {
                $url = "http://t.cn/ROtnSyM";
            }
        }
        switch ($channel_id) {
            case 0:

                if ($this->is_zxb == 1) {
//                     $result = $this->ytxREST->sendTemplateSMS($to, array($username, $password, $app_down_url), "137889");
                } else if ($this->is_xunxin == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to, array($username, $password, $url), "251979");
                } else if ($this->is_qqcg == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to, array($username, $password, $url), "251975");
                } else {
                    $msg = "【".$this->sms_header."】您的商城已开通,账号:" . $username . ",密码:" . $password . ",点击链接查看开通信息:".$url;
                    $result_code = $this->smsChinaSend($to, $msg);
                    if ($result_code == "000000") {
                        $this->add_excessive($to);
                    }
                    return $result_code;
                }
                if ($result == NULL) {
                    return "result error!";
                }
                if ($result->statusCode != 0) {
                    return $result->statusMsg;
                } else {
                    $this->add_excessive($to);
                    return "000000";
                }
            case 1:
                $msg = "【".$this->sms_header."】您的商城已开通,账号:" . $username . ",密码:" . $password . ",点击链接查看开通信息:".$url;
                $result_code = $this->smsChinaSend($to, $msg);
                if ($result_code == "000000") {
                    $this->add_excessive($to);
                }
                return $result_code;
            //     case 1:
            //         $templateId = "5734";
            //         $content = json_decode($this->ucpass->templateSMS($this->appId,$to,$templateId,$storename.",".$username.",".$password),true);
            //         $result_code = $content['resp']['respCode'];
            //         return $result_code;
            //     default:
            default:
                return "case null";
        }
    }

    /**
     * 发送审核不通过短信给商家
     * @param $to 商家手机
     * @param $storename 商家名称
     * @param $username 账号
     * @param $password 密码
     * @return string
     */
    public function sendRefundAccount($to,$storename,$channel=0) {

        $this->check_excessive($to);
        $channel_id = 0;
        switch ($channel_id)
        {
            case 0:
                if ($this->is_zxb == 1) {
                    $result = $this->ytxREST->sendTemplateSMS($to, array($storename), "138567");
                } else if ($this->is_xunxin) {
                    $result = $this->ytxREST->sendTemplateSMS($to, array($storename), "201685");
                } else if ($this->is_qqcg) {
                    $result = $this->ytxREST->sendTemplateSMS($to, array($storename), "202671");
                } else {
                    $msg = "【".$this->sms_header."】亲爱的用户，您申请的“".$storename."”资料审核不通过,请重新填写资料信息。";
                    $result_code = $this->smsChinaSend($to,$msg);
                    if ($result_code == "000000") {
                        $this->add_excessive($to);
                    }
                    return $result_code;
                }
                if($result == NULL ) {
                    return "result error!";
                }
                if($result->statusCode!=0) {
                    return $result->statusMsg;
                }else{
                    $this->add_excessive($to);
                    return "000000";
                }
            case 1:
                $msg = "【".$this->sms_header."】亲爱的用户，您申请的“".$storename."”资料审核不通过,请重新填写资料信息。";
                $result_code = $this->smsChinaSend($to,$msg);
                if ($result_code == "000000") {
                    $this->add_excessive($to);
                }
                return $result_code;
            default:
                return "case null";
        }

    }

    /**
     * 发送开通运营权短信
     * @param $to 商家手机
     * @param $storename 商家名称
     * @param $username 账号
     * @param $password 密码
     * @return string
     */
    public function sendOpenOperation($to,$username,$password) {
        $this->check_excessive($to);

        $msg = "【".$this->sms_header."】您已通过全球采购运营商资格审核，账号：".$username."，密码:".$password."，登录 duinin.com 或关注“全球采购运营商”公众号获取更多资讯。";
        $result_code = $this->smsChinaSend($to,$msg);
        if ($result_code == "000000") {
            $this->add_excessive($to);
            return $result_code;
        }

        return "case null";

    }

    /**
     * “短信中国”的发送短信方法，也是默认的通道（非http方式）
     * @param $mobile 手机
     * @param $msg 消息内容
     * @return string
     */
    /*public function smsChinaSend($mobile,$msg) {
        $interfaceAddress="http://webservice.smsadmin.cn/SGIP/SGIPService.php?WSDL";  //远程服务器接口地址
        $client=new SoapClient($interfaceAddress);
        $uid="microunite";        //必选参数,用户ID,即在通用短信平台上注册的用户ID
        $uid=mb_convert_encoding($uid,'utf-8','auto');
        $pwd="vjdxx8988998";     //必选参数，用户密码
        //$mobile="";  //必选参数，接收短信的手机号码,批量提交时一次最多只能提交999个号码,每个号码之间用英文下的逗号(,)分隔
        //$msg="【".$this->sms_header."】您本次的注册验证码2222，在5分钟内输入有效。";  //必选参数，短信内容
        $msg=mb_convert_encoding($msg,'utf-8','auto');
        $lindid="";             //可选参数，提交短信批次流水号，用户自定义，数字、字母组合，最大长度不能超过32位；用于匹配返回的状态报告及回复信息；
        $dtime="";              //可选能数，短信定时发送时的定时时间
        $char= "utf-8";
        $uid = urlencode($uid); //用户ID进行url转码
        $msg = urlencode($msg); //短信内容进行url转码
        $response=$client->sendSms($uid,$pwd,$mobile,$msg,$lindid,$dtime,$char);  //调用短信发送方式sendSms
        $result = simplexml_load_string($response);
        if ($result->status == "0") {
            return "000000";
        }
        return $result->status;      //返回短信提交响应结果
    }
    */

    /**
     * 通用短信平台HTTP接口POST_urldecode方式发送短信
     * 短信内容中带有空格、换行等特殊字符时调用此方法
     * @param $mobile 手机
     * @param $msg 消息内容
     * @return string
     */
    public function smsChinaSend($mobile,$msg) {
        $url="http://www.smsadmin.cn/smsmarketing/wwwroot/api/post_send_urldecode/";   //通用短信平台接口地址
        $uid=$this->youe_account;         //您在通用短信平台上注册的用户ID
        if (empty($uid)) {
            return "no sms tool";
        }
        $uid=mb_convert_encoding($uid,'GB2312','UTF-8'); //内容为UTF-8时转码成GB2312
        $pwd=$this->youe_password;         //用户密码
        //$msg="您本次的注册验证码678123，在30分钟内输入有效。POST_URLDECODE提交。【通用短信平台】";         //要发送的短信内容，必须要加签名，签名格式：【签名内容】
        $msg=mb_convert_encoding($msg,'GB2312','UTF-8'); //内容为UTF-8时转码成GB2312
        //$mobile="13712345678;13712345679";      //接收短信的手机号码，多个手机号码用英文下的分号(;)间隔,最多不能超过1000个手机号码。
        $uid = urlencode($uid);
        $msg = urlencode($msg);
        $params = array(
            "uid"=>$uid,
            "pwd"=>$pwd,
            "mobile"=>$mobile,
            "msg"=>$msg,
            "dtime"=>"",   //为空，表示立即发送短信;写入时间即为定时发送短信时间，时间格式：0000-00-00 00:00:00
            "linkid"=>""   //为空，表示没有流水号;写入流水号,获取状态报告和短信回复时返回流水号,流水号格式要求:最大长度不能超过32位，数字、字母、数字字母组合的字符串
        );
        $results = $this->curl_post($url,$params);
        $results = mb_convert_encoding($results,'GBK','GB2312');
        $str = "0发送成功!";
        $str = mb_convert_encoding($str,'GBK','UTF-8');
        if ($results == $str) {
            return "000000";
        }
        $results = mb_convert_encoding($results,'UTF-8','auto');
        return $results;
        /* 提交成功返回值格式：
        0发送成功! */
    }

    /**
     * 通过POST方式提交
     */
    function posttohosts($url, $data){
        $url = parse_url($url);
        if (!$url) return "couldn't parse url";
        if (!isset($url['port'])) { $url['port'] = ""; }
        if (!isset($url['query'])) { $url['query'] = ""; }
        $encoded = "";
        while (list($k,$v) = each($data)){
            $encoded .= ($encoded ? "&" : "");
            $encoded .= rawurlencode($k)."=".rawurlencode($v);
        }
        $fp = fsockopen($url['host'], $url['port'] ? $url['port'] : 80);
        if (!$fp) return "Failed to open socket to $url[host]";
        fputs($fp, sprintf("POST %s%s%s HTTP/1.0\n", $url['path'], $url['query'] ? "?" : "", $url['query']));
        fputs($fp, "Host: $url[host]\n");
        fputs($fp, "Content-type: application/x-www-form-urlencoded\n");
        fputs($fp, "Content-length: " . strlen($encoded) . "\n");
        fputs($fp, "Connection: close\n\n");
        fputs($fp, "$encoded\n");
        $line = fgets($fp,1024);
        if (!preg_match("{^HTTP/1\.. 200}", $line)) return;
        $results = "";
        $inheader = 1;
        while(!feof($fp)){
            $line = fgets($fp,1024);
            if ($inheader && ($line == "\n" || $line == "\r\n")){
                $inheader = 0;
            }elseif (!$inheader){
                $results .= $line;
            }
        }
        fclose($fp);
        return $results;
    }

    private function curl_post($url, $params)
    {
        $senddata = http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');    //为了支持cookie
        curl_setopt($ch, CURLOPT_POSTFIELDS, $senddata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, FALSE); //制获取一个新的连接，替代缓存中的连接。关闭
        curl_setopt($ch, CURLOPT_FORBID_REUSE, FALSE); //在完成交互以后强迫断开连接，不能重用。关闭
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);  //连接超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); //数据传输的最大允许时间
        $result = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        //$info  = curl_getinfo($ch);
        curl_close($ch);
        //$log = json_encode($info);
        //putLog($log);
        if ($curl_errno > 0) {
            return "CURL Error:" . $curl_error . "(" . $curl_errno . ")";
        }
        return $result;
    }

    /**
     * 检查该号码今日发送次数是否超标
     */
    function check_excessive($tel) {
        $model = Model('mb_identify_code');
        $code_data = $model->where(array('tel' => $tel))->find();
        if(!empty($code_data)) {
            if (date('Ymd', $code_data['time']) == date('Ymd')) {
                //同一天
                if ($code_data['today_number'] >= 10) {
                    output_error(1001, '您今日的短信验证次数已用完！','同一天验证次数超10次');
                }
            } else {
                /* if ($code_data['today_number'] >= 10) { */
                    $model->where(array('tel' => $tel))->update(
                        array(
                            'time' => TIMESTAMP,
                            'today_number' => 0
                        )
                    );
                /* } */
            } 
        }
    }     

    /**
     * 发送成功才增加每日发送次数
     */
    function add_excessive($tel) {
        $model = Model('mb_identify_code');
        $code_data = $model->where(array('tel' => $tel))->find();
        if(!empty($code_data)) {

            $today_number = $code_data['today_number'] + 1;
            
            $model->where(array('tel' => $tel))->update(
                array(
                    'time' => TIMESTAMP,
                    'today_number' => $today_number
                )
            );
        } else {
            $data=array();
            $data['tel'] = $tel;
            $data['code'] = '';
            $data['time'] = TIMESTAMP;
            $data['today_number'] = 1;
            $model->insert($data);
        }
    }


    /**
     * 分享链接的下级购买的通知
     * @param $tel 电话
     * @param $friend 朋友名称
     * @param $buynum 购买数量＋商品名
     * @param $balance 返现金额
     * @return string
     * @throws Exception
     */
    public function sendShareLinkLowerBuySuccessSms($tel, $friend, $buynum, $balance, $time, $channel=0) {
        if (!empty($result) && $result['status'] == -1) {
            return $result['error'];
        }
        $this->check_excessive($tel);

        $friend = mb_convert_encoding($friend,'utf-8','auto');
        $buynum = mb_convert_encoding($buynum,'utf-8','auto');
        $balance = mb_convert_encoding($balance,'utf-8','auto');
        $time = mb_convert_encoding($time,'utf-8','auto');
        $result = $this->ytxREST->sendTemplateSMS($tel,array($friend,$buynum,$balance),"177020");
        if($result == NULL ) {
            return "result error!";
        }
        if($result->statusCode!=0) {
            return $result->statusMsg;
        }else{
            $this->add_excessive($tel);
            return "000000";
        }
    }

    /**
     * H5购买短信通知内容
     * @param $tel 发送电话
     * @param $month 月份
     * @param $day 日期
     * @param $buynum 购买数量+商品名
     * @param $stel 收货电话
     * @param $address 收货地址
     * @return string
     * @throws Exception
     */
    public function hfBuySuccessSms($tel, $month, $day, $buynum, $channel=0) {
        if (!empty($result) && $result['status'] == -1) {
            return $result['error'];
        }
        $this->check_excessive($tel);
        $month = mb_convert_encoding($month,'utf-8','auto');
        $day = mb_convert_encoding($day,'utf-8','auto');
        $buynum = mb_convert_encoding($buynum,'utf-8','auto');

        $result = $this->ytxREST->sendTemplateSMS($tel,array($month,$day,$buynum),"176128");
        if($result == NULL ) {
            return "result error!";
        }
        if($result->statusCode!=0) {
            return $result->statusMsg;
        }else{
            $this->add_excessive($tel);
            return "000000";
        }
    }

    /*检查店铺是否符合发短信条件
     */
    public function checkStoreSms(){
        $channel = Model('mb_channel');
        $store = Model('store');
        $rt = array();
        if($this->channel_id == 0){
            if (!empty($this->store_id)) {
                $store_data = $store->where(array('store_id'=>$this->store_id))->field('sms_num')->find();
                $sms_num = $store_data['sms_num'];
                if($sms_num < 1){
                    $rt['status'] = -1;
                    $rt['error'] = '短信条数不足,请充值';
                }
            }
        }else if(!empty($this->channel_id)){
            $sms_info = $channel->where(array('channel_id'=>$this->channel_id))->field('youe_is_platform,sms_header,store_type')->find();
//            if(empty($sms_info['sms_header'])){
//                $rt['status'] = -1;
//                $rt['error'] = '店铺短信签名未设置';
//            }else{
            if($sms_info['youe_is_platform'] == '1'){  //使用迅信平台短信
                if ($sms_info['store_type'] != 3) {
                    $store_data = $store->where(array('channel_id'=>$this->channel_id,'main_store'=>1))->field('sms_num')->find();
                    $sms_num = $store_data['sms_num'];
                } else {
                    $store_data = $store->where(array('channel_id'=>$this->channel_id))->field('sms_num')->find();
                    $sms_num = $store_data['sms_num'];
                }
                if($sms_num < 1){
                    $rt['status'] = -1;
                    $rt['error'] = '短信条数不足,请充值';
                }
            }
//            }
        }
        return $rt;
    }


    /*发短信成功后，扣条数
     */
    public function removeStoreSms(){
        $channel = Model('mb_channel');
        $store = Model('store');
        $rt = array();
        if($this->channel_id == 0){
            if (!empty($this->store_id)) {
                $store_data = $store->where(array('store_id'=>$this->store_id))->field('sms_num')->find();
                $sms_num = $store_data['sms_num'] - 1;
                if($sms_num < 0){
                    $sms_num = 0;
                }
                $store->where(array('store_id'=>$this->store_id))->update(array('sms_num'=>$sms_num));
            }
        }else if(!empty($this->channel_id)){
            $sms_info = $channel->where(array('channel_id'=>$this->channel_id))->field('youe_is_platform,sms_header,store_type')->find();
//            if(empty($sms_info['sms_header'])){
//                $rt['status'] = -1;
//                $rt['error'] = '店铺短信签名未设置';
//            }else{
            if($sms_info['youe_is_platform'] == '1'){  //使用迅信平台短信
                if ($sms_info['store_type'] != 3) {
                    $store_data = $store->where(array('channel_id'=>$this->channel_id,'main_store'=>1))->field('sms_num')->find();
                    $sms_num = $store_data['sms_num'] - 1;
                    if($sms_num < 0){
                        $sms_num = 0;
                    }
                    $store->where(array('channel_id'=>$this->channel_id,'main_store'=>1))->update(array('sms_num'=>$sms_num));
                } else {
                    $store_data = $store->where(array('channel_id'=>$this->channel_id))->field('sms_num')->find();
                    $sms_num = $store_data['sms_num'] - 1;
                    if($sms_num < 0){
                        $sms_num = 0;
                    }
                    $store->where(array('channel_id'=>$this->channel_id))->update(array('sms_num'=>$sms_num));
                }

            }
//            }
        }
        return $rt;

    }

    /**
     * 通用短信平台HTTP接口POST_urldecode方式发送短信
     * 短信内容中带有空格、换行等特殊字符时调用此方法
     * @param $mobile 手机
     * @param $msg 消息内容
     * @return string
     */
    public function smsMjSend($tel, $content) {
        $url= $this->send_url."accreditation";
        //第一步获取授权码
        $postData = array();
        $postData['clientId'] = $this->client_id;
        $postData['clientScr'] = $this->client_secret;
        $headArr= array();
        $headArr[] = "Content-type:application/json;charset=utf-8";

        $returnData = httpRequest($url, "post", json_encode($postData), $headArr);
        $accreditData = json_decode($returnData['data'],true);

        $sendUrl = $this->send_url."Interface/".$accreditData['message']."/ShortMessage";
       // die("--".$sendUrl);
        $sendData = array();
        $sendData['contactPhone'] = $tel;
        $sendData['content'] = $content;
        $sendData['senderId'] = '';
        $sendData['senderName'] = '';
        $sendData['access_token'] = $accreditData['message'];
       // die($sendUrl."---".json_encode($sendData));
        $returnData = httpRequest($sendUrl, "post", json_encode($sendData), $headArr);
        $sendReturnData = json_encode($returnData['data']);
        if($sendReturnData['status']){
            return "000000";
        }else{
            return $sendReturnData['message'];
        }
    }

}

?>
