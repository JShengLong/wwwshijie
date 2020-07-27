<?php
namespace wxpay;
use think\Exception;
use think\Validate;

/**
 * Created by PhpStorm.
 * User: 汲生龙
 * Date: 2019/9/19
 * Time: 13:38
 */
class Wxpay
{
    //接口API URL前缀
    const API_URL_PREFIX = 'https://api.mch.weixin.qq.com';
    //下单地址URL
    const UNIFIEDORDER_URL = "/pay/unifiedorder";
    //查询订单URL
    const ORDERQUERY_URL = "/pay/orderquery";
    //关闭订单URL
    const CLOSEORDER_URL = "/pay/closeorder";
    //微信退款
    const REFUND_URL='/secapi/pay/refund';

    //curl代理设置，只有需要代理的时候才设置，不需要代理，设置为0.0.0.0和0
    const CURL_PROXY_HOST = "0.0.0.0";
    const CURL_PROXY_PORT = 0;
    /**
     * @var string app微信APP支付 ,微信mp公众号支付,mini微信小程序支付
     */
    private $type='';
    /**
     * 订单号
     * @var string
     */
    public  $out_trade_no='';
    /**
     * 支付金额
     * @var string
     */
    public  $total_fee='';
    /**
     * 商品描述
     * @var string
     */
    public  $body='';
    /**
     * 微信app支付的APPID
     * @var string
     */
    public  $appid='';
    /**
     * 微信公众号支付APPID
     * @var string
     */
    public  $app_id='';
    /**
     * 微信小程序支付的APPID
     * @var string
     */
    public  $miniapp_id='';
    /**
     * 微信H5支付的APPID
     * @var string
     */
    public  $mwebapp_id='';
    /**
     * 商户号
     * @var string
     */
    public  $mch_id='';
    /**
     * 支付的key
     * @var string
     */
    public  $key='';
    /**
     * 支付回调
     * @var string
     */
    public  $notify_url='';
    /**
     * H5支付的场景信息
     * @var string
     */
    public  $scene_info='';

    /**
     * 支付类型
     * @var string
     */
    public $trade_type='';

    /**
     * openID
     * @var string
     */
    public $openid='';

    /**
     * 其他描述
     * @var string
     */
    public $attach='';
    /**
     * 退款回调通知
     * @var string
     */
    public $notify_refund_url='';
    /**
     * 支付数据-统一下单
     * @return bool|mixed|json数据
     * @throws Exception
     */
    public function getPayData()
    {
        new Validate();
        if(empty($this->appid)){
            throw new Exception('APPID不能为空');
        }
        if(empty($this->mch_id)){
            throw new Exception('商户号不能为空');
        }
        if(empty($this->out_trade_no)){
            throw new Exception('订单号不能为空');
        }
        if(empty($this->total_fee)){
            throw new Exception('商品价格不能为空');
        }
        if(empty($this->body)){
            throw new Exception('商品描述不能为空');
        }
//        if(empty($this->attach)){
//            throw new Exception('附加数据不能为空');
//        }
        if(empty($this->trade_type)){
            throw new Exception('交易类型不能为空');
        }
        if(empty($this->notify_url)){
            throw new Exception('回调地址不能为空');
        }
        $params['appid']            = $this->appid;                     //APPID
        $params['mch_id']           = $this->mch_id;                    //商户号
        $params['nonce_str']        = $this->getRandomString();         //随机串
        $params['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];          //终端ip
        $params['body']             = $this->attach.$this->body;  //商品描述
        $params['notify_url']       = $this->notify_url;                //回调地址
//        $params['attach']           = $this->attach;                    //附加数据
        $params['out_trade_no']     = $this->out_trade_no;              //订单号
        $params['total_fee']        = $this->total_fee*100;             //金额
        $params['trade_type']       = $this->trade_type;                //交易类型

        if($params['trade_type']=='JSAPI'){
            if(empty($this->openid)){
                throw new Exception('openID不能为空');
            }
            $params['openid'] = $this->openid;
        }

        if($params['trade_type']=='MWEB'){
            if(empty($this->scene_info)){
                throw new Exception('场景信息不能为空');
            }
            $params['scene_info'] = $this->scene_info;
        }
        //获取签名数据
        $sign = $this->MakeSign($params);
        //签名
        $params['sign']=$sign;
        //数组装xml
        $xml = $this->data_to_xml($params);
        //预下单请求
        $response = $this->postXmlCurl($xml, self::API_URL_PREFIX . self::UNIFIEDORDER_URL);
        if(!$response){
            throw new Exception('预下单失败');
        }
        //xml转数组
        $result = $this->xml_to_data($response);
        if (!empty($result['result_code']) && !empty($result['err_code'])) {
            throw new Exception($result['err_code_des']);
        }
        //验签
        if($this->validSign($result)){
            if($this->trade_type=='APP'){
                return $this->getAppApiParameters($result);
            }elseif($this->trade_type=="JSAPI"){
                return $this->getJsApiParameters($result);
            }else{
                return $result;
            }
        }else{
            throw new Exception('验签失败');
        }
    }


    /**
     * 生成app支付参数
     * @param $result
     * @return mixed
     */
    public function getAppApiParameters($result){
        $params['appid'] = $this->appid;
        $params['partnerid'] = $this->mch_id;
        $params['prepayid'] = $result['prepay_id'];
        $params['package'] = 'Sign=WXPay';
        $params['noncestr'] = $this->getRandomString();
        $params['timestamp'] = time();
        $params['sign'] = $this->MakeSign($params);
        return $params;

    }

    /**
     * 微信退款(POST)
     * @param $data
     * @return bool|mixed
     * @throws Exception
     */
    public function refund($data)
    {
        if(empty($this->appid)){
            throw new Exception('APPID不能为空');
        }
        if(empty($this->mch_id)){
            throw new Exception('商户号不能为空');
        }
        if(empty($this->notify_refund_url)){
            throw new Exception('退款回调不能为空');
        }
        //退款参数
        $refundOrder = array(
            'appid'         => $this->appid,
            'mch_id'        => $this->mch_id,
            'nonce_str'     => $this->getRandomString(),
            'out_trade_no'  => $data['out_trade_no'],
            'out_refund_no' => $data['out_refund_no'],
            'total_fee'     => $data['total_fee'] * 100,
            'refund_fee'    => $data['refund_fee'] * 100,
            'notify_url'    => $this->notify_refund_url
        );
        $refundOrder['sign'] = $this->makeSign($refundOrder);
        $xml_data = $this->data_to_xml($refundOrder);
        $url = self::API_URL_PREFIX.self::REFUND_URL;
        $res = $this->postXmlCurl($xml_data, $url,true);
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
     * 生成jsapi支付参数
     * @param array $result 统一支付接口返回的数据
     * @return json数据
     */
    public function getJsApiParameters($result)
    {
        if (!array_key_exists("appid", $result) || !array_key_exists("prepay_id", $result) || $result['prepay_id'] == "") {
            return "";
        }
        $params = array();
        $params['appId'] = $result["appid"];
        $timeStamp = time();
        $params['timeStamp'] = "$timeStamp";
        $params['nonceStr'] = $this->getRandomString();
        $params['package'] = "prepay_id=" . $result['prepay_id'];
        $params['signType'] = "MD5";
        $params['paySign'] = $this->MakeSign($params);
        return $params;
    }
    /**
     * @Notes:输出xml字符
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:57
     * @param $params
     * @return bool|string
     */
    public function data_to_xml($params)
    {
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
        return $xml;
    }
    /**
     * @Notes:将xml转为array
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:57
     * @param $xml
     * @return bool|mixed
     *
     *
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
    /**
     * @Notes:验签
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:56
     * @param $array
     * @return bool
     */
    public function validSign($array)
    {
        if ("SUCCESS" == $array["return_code"]) {
            $signRsp = strtolower($array["sign"]);
            unset($array["sign"]);
            $sign = strtolower($this->MakeSign($array));
            if ($sign == $signRsp) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    /**
     * @Notes:生成签名
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:57
     * @param $params
     * @return string
     */
    public function MakeSign($params)
    {
        //签名步骤一：按字典序排序数组参数
        ksort($params);
        $string = $this->ToUrlParams($params);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * @Notes:将参数拼接为url: key=value&key=value
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:57
     * @param $params
     * @return string
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
     * @Notes:产生一个指定长度的随机字符串,并返回给用户
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:57
     * @param int $len
     * @return string
     */
    public function getRandomString($len = 32)
    {
        $chars = array(
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
     * @Notes:以post方式提交xml到对应的接口url
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:57
     * @param $xml
     * @param $url
     * @param bool $useCert
     * @param int $second
     * @return bool|mixed
     */
    private function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $pem_dir = $_SERVER['DOCUMENT_ROOT'] . "/cert/";//证书存放目录
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if ($useCert == true) {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $pem_dir . 'apiclient_cert.pem');
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $pem_dir . 'apiclient_key.pem');
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return false;
        }
    }
    /**
     * @Notes:错误代码
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:57
     * @param $code
     * @return mixed
     */
    public function error_code($code)
    {
        $errList = array(
            'NOAUTH' => '商户未开通此接口权限',
            'NOTENOUGH' => '用户帐号余额不足',
            'ORDERNOTEXIST' => '订单号不存在',
            'ORDERPAID' => '商户订单已支付，无需重复操作',
            'ORDERCLOSED' => '当前订单已关闭，无法支付',
            'SYSTEMERROR' => '系统错误!系统超时',
            'APPID_NOT_EXIST' => '参数中缺少APPID',
            'MCHID_NOT_EXIST' => '参数中缺少MCHID',
            'APPID_MCHID_NOT_MATCH' => 'appid和mch_id不匹配',
            'LACK_PARAMS' => '缺少必要的请求参数',
            'OUT_TRADE_NO_USED' => '同一笔交易不能多次提交',
            'SIGNERROR' => '参数签名结果不正确',
            'XML_FORMAT_ERROR' => 'XML格式错误',
            'REQUIRE_POST_METHOD' => '未使用post传递参数 ',
            'POST_DATA_EMPTY' => 'post数据不能为空',
            'NOT_UTF8' => '未使用指定编码格式',
        );
        if (array_key_exists($code, $errList)) {
            return $errList[$code];
        }
    }

}