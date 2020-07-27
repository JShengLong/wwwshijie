<?php

namespace alipay;
/**
 * Created by PhpStorm.
 * User: 汲生龙
 * Date: 2019/9/19
 * Time: 14:37
 */

use alipayapp\aop\AopClient;
use alipayapp\aop\request\AlipayUserCertifyOpenQueryRequest;
use alipayapp\aop\request\AlipayUserCertifyOpenInitializeRequest;
use alipayapp\aop\request\AlipayUserCertifyOpenCertifyRequest;
use alipayapp\aop\SignData;
use alipayapp\aop\request\AlipayTradeAppPayRequest;
use alipayapp\aop\request\AlipaySystemOauthTokenRequest;
use alipayapp\aop\request\AlipayUserInfoShareRequest;
use alipayapp\aop\request\AlipayTradeRefundRequest;

class Alipay
{
    private $config
        = [
            'app_id'         => '',
            'private_key'    => '',
            'ali_public_key' => '',
            'gatewayUrl'     => 'https://openapi.alipay.com/gateway.do',
            'signType'       => 'RSA2',
            'siteName'       => '',
            'notify_url'     => '',
            'partner'        => ''
        ];

    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * @Notes:app支付
     * @Author:汲生龙
     * @Date: 2019/9/19
     * @Time: 15:41
     * @param $data
     * @param $body
     * @return string
     */
    public function getAppPay($data)
    {
        $name                   = $this->config['siteName'];
        $params['body']         = $name . "-" . $data['body'];
        $params["subject"]      = $name;
        $params["out_trade_no"] = $data['out_trade_no'];//此订单号为商户唯一订单号
        $params["total_amount"] = $data['total_fee'];//保留两位小数
        $params["product_code"] = 'QUICK_MSECURITY_PAY';
        $bizcontent             = json_encode($params);
        $aop                    = new AopClient();
        //沙箱测试支付宝开始
        $aop->gatewayUrl = $this->config['gatewayUrl'];
        //实际上线appid需真实的
        $aop->appId         = $this->config['app_id'];
        $aop->rsaPrivateKey = $this->config['private_key'];
        $aop->format        = "json";
        $aop->charset       = "UTF-8";
        $aop->signType      = $this->config['signType'];
        //**沙箱测试支付宝结束
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new AlipayTradeAppPayRequest();
        //支付宝回调地址
        $request->setNotifyUrl($this->config['notify_url']);
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
        return $response;
    }

    /*
     * 支付宝授权登录
     * @param $auth_code  授权码
     * @throws \Exception
     */
    public function aliLogin($auth_code)
    {
        //获取access_token
        $aop                     = new AopClient();
        $aop->gatewayUrl         = 'https://openapi.alipay.com/gateway.do';
        $aop->appId              = $this->config['app_id'];//appid
        $aop->rsaPrivateKey      = trim($this->config['private_key']);//私钥
        $aop->alipayrsaPublicKey = trim($this->config['ali_public_key']);
        $aop->apiVersion         = "1.0";
        $aop->signType           = "RSA2";
        $aop->postCharset        = "UTF-8";
        $aop->format             = "json";
        $request                 = new AlipaySystemOauthTokenRequest();
        $request->setGrantType("authorization_code");
        $request->setCode($auth_code);
        $result       = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultData   = (array)$result->$responseNode;
        //如果出现错误信息
        if (empty($resultData)) {
            $responseNode = "error_response";
            $resultData   = (array)$result->$responseNode;
        }

        //验签成功返回
        if (empty($resultData['access_token'])) {
            $resultCode = $result->$responseNode->code;
            $resultmsg  = $result->$responseNode->msg;
            $data       = array("code" => $resultCode, "msg" => $resultmsg, "data" => $resultData);
            return $resultData;
        }
        //获取用户信息
        $request      = new AlipayUserInfoShareRequest();
        $result       = $aop->execute($request, $resultData['access_token']);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode   = $result->$responseNode->code;
        $resultmsg    = $result->$responseNode->msg;
        $userData     = (array)$result->$responseNode;
        $data         = array("code" => $resultCode, "msg" => $resultmsg, "data" => $userData);

        return $userData;
    }

    //支付宝登录返回客户端参数
    public function aliAuth()
    {
        $sign_type = "RSA2";
        $parameter = array(
            'apiname'    => 'com.alipay.account.auth', // 服务对应的名称
            'app_id'     => $this->config['app_id'], // 支付宝分配给开发者的应用ID
            'app_name'   => 'mc', // 调用来源方的标识
            'auth_type'  => 'AUTHACCOUNT', // 标识授权类型 AUTHACCOUNT代表授权；LOGIN代表登录
            'biz_type'   => 'openservice', // 调用业务的类型
            'method'     => 'alipay.open.auth.sdk.code.get', // 接口名称
            'pid'        => $this->config['partner'], // 签约的支付宝账号对应的支付宝唯一用户号
            'product_id' => 'APP_FAST_LOGIN', // 产品码
            'scope'      => 'kuaijie', // 授权范围
            'target_id'  => MyOrderNo22(), // 商户标识该次用户授权请求的ID，该值在商户端应保持唯一
        );
        //生成需要签名的订单
        $orderInfo = $this->createLinkstring($parameter);
        //签名
        $sign = $this->rsaSign($orderInfo, $sign_type);
        //生成订单
        return $orderInfo . '&sign="' . $sign . '"&sign_type="' . $sign_type . '"';
    }

    // 对签名字符串转义
    public function createLinkstring($para)
    {
        $arg = "";
        while (list($key, $val) = each($para)) {
            $arg .= $key . '="' . $val . '"&';
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return $arg;
    }

    // 签名生成订单信息
    public function rsaSign($data, $signtype = "RSA")
    {
        $search = [
            "-----BEGIN RSA PRIVATE KEY-----",
            "-----END RSA PRIVATE KEY-----",
            "\n",
            "\r",
            "\r\n",
        ];
        $priKey = str_replace($search, "", $this->config['private_key']);
        $priKey = $search[0] . PHP_EOL . wordwrap($priKey, 64, "\n", true) . PHP_EOL . $search[1];
        $res    = openssl_pkey_get_private($priKey);
        if ("RSA2" == $signtype) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }
        openssl_free_key($res);

        $sign = base64_encode($sign);
        $sign = urlencode($sign);
        return $sign;
    }

    /**
     * @Notes:
     * @Author:jsl
     * @Date: 2019/9/10
     * @Time: 13:43
     * @param $out_trade_no
     * @param $out_request_no
     * @param $totalFee
     * @return bool
     * @throws \Exception
     */
    public function alipayRefund($out_trade_no, $out_request_no, $totalFee)
    {
        $aop                     = new AopClient();
        $aop->gatewayUrl         = $this->config['gatewayUrl'];
        $aop->appId              = $this->config['app_id'];
        $aop->rsaPrivateKey      = $this->config['private_key'];
        $aop->alipayrsaPublicKey = $this->config['ali_public_key'];
        $aop->apiVersion         = '1.0';
        $aop->signType           = 'RSA2';
        $aop->postCharset        = 'utf-8';
        $aop->format             = 'json';
        $request                 = new AlipayTradeRefundRequest();
        $bizcontent              = json_encode([
            'out_trade_no'   => $out_trade_no,
            'refund_amount'  => $totalFee,
            'refund_reason'  => '正常退款',
            "out_request_no" => $out_request_no
        ]);
        $request->setBizContent($bizcontent);
        $result       = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode   = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 支付宝实名认证初始化
     * @param $params
     * @return bool|\SimpleXMLElement
     */
    public function alirenzheng($params)
    {
        $aop = new AopClient ();
        new SignData();
        $aop->gatewayUrl                = $this->config['gatewayUrl'];
        $aop->appId                     = $this->config["app_id"];
        $aop->rsaPrivateKey             = $this->config["private_key"];
        $aop->alipayrsaPublicKey        = $this->config["ali_public_key"];
        $aop->apiVersion                = '1.0';
        $aop->signType                  = 'RSA2';
        $aop->postCharset               = 'UTF-8';
        $aop->format                    = 'json';
        $request                        = new AlipayUserCertifyOpenInitializeRequest();
        $param                          = array();
        $param["outer_order_no"]        = $params["outer_order_no"];
        $param["biz_code"]              = "FACE";
        $data                           = array();
        $data["identity_type"]          = "CERT_INFO";
        $data["cert_type"]              = "IDENTITY_CARD";
        $data["cert_name"]              = $params["name"];
        $data["cert_no"]                = $params["card"];
        $param["identity_param"]        = $data;
        $data1                          = array();
        $data1["return_url"]            = "qttx://qudaka:8888/AliResultActivity";
        $param["merchant_config"]       = $data1;
        $param["face_contrast_picture"] = "xydasf==";
        $request->setBizContent(json_encode($param));
        $result       = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode   = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            return $result->$responseNode->certify_id;
        } else {
            return false;
        }
    }

    /**
     * 支付宝实名认证
     * @param $certify_id
     * @return \alipayapp\aop\构建好的、签名后的最终跳转URL（GET）或String形式的form（POST）
     */
    public function certify($certify_id)
    {
        $aop                     = new AopClient ();
        $aop->gatewayUrl         = $this->config['gatewayUrl'];
        $aop->appId              = $this->config["app_id"];
        $aop->rsaPrivateKey      = $this->config["private_key"];
        $aop->alipayrsaPublicKey = $this->config["ali_public_key"];
        $aop->apiVersion         = '1.0';
        $aop->signType           = 'RSA2';
        $aop->postCharset        = 'UTF-8';
        $aop->format             = 'json';
        $request                 = new AlipayUserCertifyOpenCertifyRequest();
        $param                   = array();
        $param["certify_id"]     = $certify_id;
        $request->setBizContent(json_encode($param));
        $result = $aop->pageExecute($request, "GET");
        return $result;
    }

    /**
     * 支付宝实名认证查询
     * @param $certify_id
     * @return bool|\SimpleXMLElement
     */
    public function queryCertify($certify_id)
    {
        $aop                     = new AopClient ();
        $aop->gatewayUrl         = $this->config['gatewayUrl'];
        $aop->appId              = $this->config["app_id"];
        $aop->rsaPrivateKey      = $this->config["private_key"];
        $aop->alipayrsaPublicKey = $this->config["ali_public_key"];
        $aop->apiVersion         = '1.0';
        $aop->signType           = 'RSA2';
        $aop->postCharset        = 'UTF-8';
        $aop->format             = 'json';
        $request                 = new AlipayUserCertifyOpenQueryRequest ();
        $param["certify_id"]     = $certify_id;
        $request->setBizContent(json_encode($param));
        $result       = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode   = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            return $result->$responseNode;
        } else {
            return false;
        }
    }
}
