<?php

namespace app\admin\controller;
/**
 * Created by PhpStorm.
 * User: jsl
 * Date: 2019/9/10
 * Time: 11:07
 */

use think\Config;

class WechatRefund extends Right
{
    /**
     * 微信退款(POST)
     * @param string(28) $out_trade_no 在微信支付的时候,微信服务器生成的订单流水号,在支付通知中有返回
     * @param string $out_refund_no 商户系统内部的退款单号
     * @param string $total_fee 微信支付的时候支付的总金额(单位:分)
     * @param string $refund_fee 此次要退款金额(单位:分)
     * @return string  xml格式的数据
     */
    public function refund($out_trade_no, $out_refund_no, $total_fee, $refund_fee)
    {
        $config = Config::get('WechatRefundConfig');
        $appId  = $config["appId"];
        $mch_id = $config["mch_id"];
        //退款参数
        $notify_url          = $config["notify_url"];
        $refundOrder         = array(
            'appid'         => $appId,
            'mch_id'        => $mch_id,
            'nonce_str'     => $this->getRandomString(),
            'out_trade_no'  => $out_trade_no,
            'out_refund_no' => $out_refund_no,
            'total_fee'     => $total_fee * 100,
            'refund_fee'    => $refund_fee * 100,
            'notify_url'    => $notify_url
        );
        $refundOrder['sign'] = $this->makeSign($refundOrder);
        //请求数据,进行退款
        $xmldata = $this->data_to_xml($refundOrder);
        $url     = $config["url"];
        $res     = $this->curl($xmldata, $url);
        if (!$res) {
            return false;
        }
        $content = $this->xml_to_data($res);
        if (strval($content['result_code']) == 'FAIL') {
            return false;
        }
        if (strval($content['return_code']) == 'FAIL') {
            return false;
        }
        return $content;
    }

    /**
     * 产生一个指定长度的随机字符串,并返回给用户
     * @param type $len 产生字符串的长度
     * @return string 随机字符串
     */
    private function getRandomString($len = 32)
    {
        $chars    = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
        );
        $charsLen = count($chars) - 1;
        // 将数组打乱
        shuffle($chars);
        $output = "";
        for ($i = 0; $i < $len; $i++) {
            $output .= $chars[mt_rand(0, $charsLen)];
        }
        return $output;
    }

    /**
     * 生成签名
     * @return 签名
     */
    public function MakeSign($params)
    {
        $key = Config::get("WechatRefundConfig")["key"];
        //签名步骤一：按字典序排序数组参数
        ksort($params);
        $string = $this->ToUrlParams($params);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $key;//$this->key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 将参数拼接为url: key=value&key=value
     * @param   $params
     * @return  string
     */
    public function ToUrlParams($params)
    {
        $string = '';
        if (!empty($params)) {
            $array = array();
            foreach ($params as $key => $value) {
                $array[] = $key . '=' . $value;
            }
            $string = implode("&", $array);
        }
        return $string;
    }

    /**
     * @Notes:输出xml字符
     * @Author:jsl
     * @Date: 2019/9/10
     * @Time: 11:14
     * @param $params
     * @return bool|string
     */
    public function data_to_xml($params)
    {
        header("Content-type: text/xml");
        if (!is_array($params) || count($params) <= 0) {
            return false;
        }
        $xml = "<xml>";
        foreach ($params as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
//        echo $xml;die;
        return $xml;
    }

    /**
     * @Notes:http请求
     * @Author:jsl
     * @Date: 2019/9/10
     * @Time: 11:14
     * @param string $param
     * @param $url
     * @return mixed
     */
    function curl($param = "", $url)
    {
        $isdir = $_SERVER['DOCUMENT_ROOT'] . "/cert";//证书存放目录

        $postUrl  = $url;
        $curlPost = $param;
        $ch       = curl_init();                                      //初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl);                 //抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);                    //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);            //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);                      //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);           // 增加 HTTP Header（头）里的字段
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);        // 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');//证书类型
        curl_setopt($ch, CURLOPT_SSLCERT, $isdir . 'apiclient_cert.pem'); //这个是证书的位置绝对路径
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');//CURLOPT_SSLKEY中规定的私钥的加密类型
        curl_setopt($ch, CURLOPT_SSLKEY, $isdir . 'apiclient_key.pem'); //这个也是证书的位置绝对路径
        $data = curl_exec($ch);                                 //运行curl
        curl_close($ch);
        return $data;
    }

    /**
     * @Notes:将xml转为array
     * @Author:jsl
     * @Date: 2019/9/10
     * @Time: 11:14
     * @param $xml
     * @return bool|mixed
     */
    public function xml_to_data($xml)
    {
        if (!$xml) {
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }


}
