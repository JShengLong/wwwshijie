<?php
namespace app\admin\controller;
/**
 * Created by PhpStorm.
 * User: jsl
 * Date: 2019/9/10
 * Time: 13:42
 */

class AliappRefund extends Right
{
//APPID
    private $appId = '';
    //私钥
    private $rsaPrivateKey = '';
    //支付宝公钥
    private $alipayrsaPublicKey = '';
    //请求URL
    private $gatewayUrl = "";
    //签名方式
    private $signType = "";


    /**
     * 属性赋值
     */
    public function setConfig()
    {
        $payConfig                = getSettings('AliPayConfig');
        $this->appId              = $payConfig['appId'];
        $this->rsaPrivateKey      = $payConfig['rsaPrivateKey'];
        $this->alipayrsaPublicKey = $payConfig['alipayrsaPublicKey'];
        $this->gatewayUrl         = $payConfig['gatewayUrl'];
        $this->signType           = $payConfig['signType'];
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
    public function alipayRefund($out_trade_no,$out_request_no,$totalFee){
        $this->setConfig();
        require_once('alipayapp/aop/AopClient.php');
        require_once('alipayapp/aop/request/AlipayTradeRefundRequest.php');
        $aop = new \AopClient();
        $aop->gatewayUrl          = 'https://openapi.alipay.com/gateway.do';
        $aop->appId               = $this->appId;
        $aop->rsaPrivateKey       = $this->rsaPrivateKey;
        $aop->alipayrsaPublicKey  = $this->alipayrsaPublicKey;
        $aop->apiVersion          = '1.0';
        $aop->signType            = 'RSA2';
        $aop->postCharset         ='utf-8';
        $aop->format              ='json';
        $request                  = new \AlipayTradeRefundRequest();
        $bizcontent               = json_encode([
              'out_trade_no'=>$out_trade_no,
              'refund_amount'=> $totalFee,
              'refund_reason'=>'正常退款',
              "out_request_no"=>$out_request_no
        ]);
        $request->setBizContent($bizcontent);
        $result = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode =$result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            return true;
        } else {
            return false;
        }
    }
}
