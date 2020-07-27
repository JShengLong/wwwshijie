<?php

namespace app\index\controller;

use push\Push;
use Redis\RedisPackage;
use think\Exception;
use wxpay\Wxpay;
use alipay\Alipay;
use alipayapp\aop\AopClient;
use Yansongda\Pay\Pay as Ypay;

/**
 * Created by PhpStorm.
 * User: jsl
 * Date: 2019/6/4
 * Time: 11:05
 */
class Pay extends Base
{

    /**
     * TODO
     * 测试方法
     */
    public function ceshi()
    {

        $redis = new RedisPackage(['timeout' => 10]);
        $redis->set('qweqw1', 123213, 5);
        die;
        $data = [
            'out_trade_no' => '454815648456181',
            'total_fee'    => 50.00,
            'body'         => '商品',
            'openid'       => '12312'
        ];
        dump($this->yAliAppPay($data));
        die;
        $config = config('ypay_wx');
        $result = YPay::wechat($config)->app($data);//统一下单
//        $alipay = YPay::wechat($this->alibab_config)->app($data);
        dump(json_decode($result->getContent(), true));
        die;// laravel 框架中请直接 `return $alipay`
        $data['out_trade_no'] = "12312";
        $data['total_fee']    = 20.00;
        $data['body']         = "购买商品";
        $this->pay('ali', $data);
        $this->pay('wx', $data, 'app');
    }

    /**
     * 微信插件app支付
     * @param $params
     * @return bool|mixed
     */
    public function yWxAppPay($params)
    {
        $data                 = [
            'out_trade_no' => $params['out_trade_no'],
            'total_fee'    => $params['total_fee'],
            'body'         => $params['body'],
        ];
        $config               = config('ypay_wx');
        $config['notify_url'] = saver() . $config['notify_url'];
        $result               = YPay::wechat($config)->app($data);//统一下单
        return json_decode($result->getContent(), true);
    }

    /**
     * 微信插件小程序支付
     * @param $params
     * @return bool|mixed
     */
    public function yWxMiniPay($params)
    {
        $data                 = [
            'out_trade_no' => $params['out_trade_no'],
            'total_fee'    => $params['total_fee'],
            'body'         => $params['body'],
            'openid'       => $params['openid'],
        ];
        $config               = config('ypay_wx');
        $config['notify_url'] = saver() . $config['notify_url'];
        $result               = YPay::wechat($config)->miniapp($data);//统一下单
        return json_decode($result->getContent(), true);

    }

    /**
     * 插件支付宝app支付
     * @param $params
     * @return false|string
     */
    public function yAliAppPay($params)
    {
        $order                = [
            'out_trade_no' => $params['out_trade_no'],
            'total_amount' => $params['total_fee'],
            'subject'      => $params['body'],
        ];
        $config               = config('ypay_ali');
        $config['notify_url'] = saver() . $config['notify_url'];
        $result               = YPay::alipay($config)->app($order);//统一下单
        return $result->getContent();
    }

    /**
     * 微信app支付
     * @param $data
     * @return bool|mixed|\wxpay\json数据
     * @throws Exception
     */
    public function wxAppPay($data)
    {
        $wx_pay_data       = getSettings('wx_pay');//微信支付所有的配置
        $pay               = new Wxpay();
        $pay->appid        = $wx_pay_data['appid'];
        $pay->mch_id       = $wx_pay_data['mch_id'];
        $pay->key          = $wx_pay_data['key'];
        $pay->out_trade_no = $data['out_trade_no'];
        $pay->total_fee    = $data['total_fee'];
        $pay->body         = $data['body'];
        $pay->attach       = isset($data['attach']) ? $data['attach'] : '';
        $pay->trade_type   = 'APP';
        $pay->notify_url   = saver() . '/index/Pay/wxNotify';
        return $pay->getPayData();
    }

    /**
     * 微信小程序支付
     * @param $data
     * @return bool|mixed|\wxpay\json数据
     * @throws Exception
     */
    public function wxMiniPay($data)
    {
        $wx_pay_data       = getSettings('wx_pay');//微信支付所有的配置
        $pay               = new Wxpay();
        $pay->appid        = $wx_pay_data['miniapp_id'];
        $pay->mch_id       = $wx_pay_data['mch_id'];
        $pay->key          = $wx_pay_data['key'];
        $pay->openid       = $data['openid'];
        $pay->out_trade_no = $data['out_trade_no'];
        $pay->total_fee    = $data['total_fee'];
        $pay->body         = $data['body'];
        $pay->attach       = isset($data['attach']) ? $data['attach'] : '';
        $pay->trade_type   = 'JSAPI';
        $pay->notify_url   = saver() . '/index/Pay/wxNotify';
        return $pay->getPayData();
    }

    /**
     * 微信公众号支付
     * @param $data
     * @return bool|mixed|\wxpay\json数据
     * @throws Exception
     */
    public function wxMpPay($data)
    {
        $wx_pay_data       = getSettings('wx_pay');//微信支付所有的配置
        $pay               = new Wxpay();
        $pay->appid        = $wx_pay_data['app_id'];
        $pay->mch_id       = $wx_pay_data['mch_id'];
        $pay->key          = $wx_pay_data['key'];
        $pay->openid       = $data['openid'];
        $pay->out_trade_no = $data['out_trade_no'];
        $pay->total_fee    = $data['total_fee'];
        $pay->body         = $data['body'];
        $pay->attach       = isset($data['attach']) ? $data['attach'] : '';
        $pay->trade_type   = 'JSAPI';
        $pay->notify_url   = saver() . '/index/Pay/wxNotify';
        return $pay->getPayData();
    }

    /**
     * 微信H5支付
     * @param $data
     * @return bool|mixed|\wxpay\json数据
     * @throws Exception
     */
    public function wxMwebPay($data)
    {
        $wx_pay_data       = getSettings('wx_pay');//微信支付所有的配置
        $pay               = new Wxpay();
        $pay->appid        = $wx_pay_data['mwebapp_id'];
        $pay->mch_id       = $wx_pay_data['mch_id'];
        $pay->key          = $wx_pay_data['key'];
        $pay->out_trade_no = $data['out_trade_no'];
        $pay->total_fee    = $data['total_fee'];
        $pay->body         = $data['body'];
        $pay->scene_info   = $data['scene_info'];
        $pay->attach       = isset($data['attach']) ? $data['attach'] : '';
        $pay->trade_type   = 'MWEB';
        $pay->notify_url   = saver() . '/index/Pay/wxNotify';
        return $pay->getPayData();
    }

    /**
     * 微信NATIVE支付
     * @param $data
     * @return bool|mixed|\wxpay\json数据
     * @throws Exception
     */
    public function wxNativePay($data)
    {
        $wx_pay_data       = getSettings('wx_pay');//微信支付所有的配置
        $pay               = new Wxpay();
        $pay->appid        = $wx_pay_data['appid'];
        $pay->mch_id       = $wx_pay_data['mch_id'];
        $pay->key          = $wx_pay_data['key'];
        $pay->out_trade_no = $data['out_trade_no'];
        $pay->total_fee    = $data['total_fee'];
        $pay->body         = $data['body'];
        $pay->attach       = isset($data['attach']) ? $data['attach'] : '';
        $pay->trade_type   = 'NATIVE';
        $pay->notify_url   = saver() . '/index/Pay/wxNotify';
        return $pay->getPayData();
    }

    /**
     * 支付宝app支付
     * @param $data
     */
    public function aliAppPay($data)
    {
        $siteName = getSettings('site', 'siteName');//商城名称
        $ali_pay  = getSettings('ali_pay');//支付宝的所有配置
        $config   = [
            'app_id'         => $ali_pay['app_id'],//APPID
            'ali_public_key' => $ali_pay['ali_public_key'],//支付宝公钥
            'private_key'    => $ali_pay['private_key'],//支付宝私钥
            'notify_url'     => saver() . '/index/pay/aliNotify',//你自己定义的回调地址
            'siteName'       => $siteName//商城名称
        ];
        $aliPay   = new Alipay($config);
        return $aliPay->getAppPay($data);
    }

    /**
     * 错误码
     * @param $msg
     * @return array
     */
    public function errorCode($msg)
    {
        return ['code' => 0, 'msg' => $msg];
    }

    /**
     * 正确码
     * @param $msg
     * @return array
     */
    public function successCode($msg)
    {
        return ['code' => 1, 'msg' => $msg];
    }


    /**
     * 处理订单
     * @param $sn
     * @return bool
     * @throws \think\Db\exception\ModelNotFoundException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function processingOrder($sn, $pay_type, $trade_type = "")
    {
        //检索订单
        $orders = db("onlineorder")->where(["o_sn" => $sn, "o_status" => 1])->find();
        if (empty($orders)) {
            return $this->errorCode('error:1');
        }
        $status = 2;
        if ($orders['o_distribution_mode'] == 2) {
            $status = 3;
        }
        $update   = [
            "o_status"   => $status,
            "o_payType"  => $pay_type,
            'o_paytime'  => now_datetime(),
            'trade_type' => strtolower($trade_type)

        ];
        $data['update']=$update;
        $data['sn']=$sn;
        error_log(print_r($data,1),3,'./notify.txt');
        $resorder = db("onlineorder")->where("o_id", $orders["o_id"])->update($update);
        db('member')->where(['id' => $orders['o_mid']])->setField('m_isbuy', 2);
        if ($resorder == false) {
            return $this->errorCode('error:2');
        }
        return $this->successCode('SUCCESS');
    }


    /**
     * @Notes:微信支付回调
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:56
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function wxNotify()
    {
        $xml = file_get_contents('php://input');//接收xml数据
        if (!$xml) {
            $this->ajaxError("xml数据异常");
        }
        $wx_pay_data = getSettings('wx_pay');//微信支付所有的配置
        $pay         = new Wxpay();
        $pay->key    = $wx_pay_data['key'];
        //将XML转为array
        $params = $pay->xml_to_data($xml);
//        dump($params);die;
        //存入数组
        foreach ($params as $key => $val) {
            $params[$key] = $val;
        }
        //参数为空,不进行处理
        if (count($params) < 1) {
            $this->ajaxError("error");
        }
        //验签
        if ($pay->validSign($params)) {
            if ($params["result_code"] == 'SUCCESS') {
                $sn = $params['out_trade_no'];
                if (empty($sn)) {
                    $this->ajaxError("订单不存在");
                }
                $trade_type = $params['trade_type'];
                if ($trade_type == "JSAPI") {
                    $sn = str_replace('mini', '', $sn);
                } else {
                    $sn = str_replace('app', '', $sn);
                }
                db()->startTrans();
                try {
                    $res = $this->processingOrder($sn, 1, $trade_type);
                    if ($res['code'] == 0) {
                        db()->rollback();
                        $this->ajaxError($res['msg']);
                    }
                    db()->commit();
                    $data['return_code'] = 'SUCCESS';
                    $data['return_msg']  = 'OK';
                    $xml                 = $pay->data_to_xml($data);
                    echo $xml;
                    die();
                } catch (\Exception $exception) {
                    db()->rollback();
                    $this->ajaxError($exception->getMessage());
                }
            }
        } else {
            $this->ajaxError("验签失败");
        }
    }

    /**
     * @Notes:支付宝支付回调
     * @Date: 2019/9/19
     * @Time: 16:01
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function aliNotify()
    {
        $ali_public_key          = getSettings('ali_pay', 'ali_public_key');
        $aop                     = new AopClient();
        $aop->alipayrsaPublicKey = $ali_public_key;
        //此处验签方式必须与下单时的签名方式一致
        $flag = $aop->rsaCheckV1($_POST, NULL, 'RSA2');
        if ($flag) {
            //状态 TRADE_SUCCESS
            $status = $_POST['trade_status'];
            if ($status == "TRADE_SUCCESS") {
                //订单sn
                $sn = $_POST['out_trade_no'];
                if (empty($sn)) {
                    $this->ajaxError("订单不存在");
                }
                $sn = str_replace('ali', '', $sn);
                db()->startTrans();
                try {
                    $res = $this->processingOrder($sn, 2);
                    if ($res['code'] == 0) {
                        db()->rollback();
                        $this->ajaxError($res['msg']);
                    }
                    db()->commit();
                    echo 'success';
                } catch (\Exception $exception) {
                    db()->rollback();
                    $this->ajaxError($exception->getMessage());
                }
            }
        } else {
            $this->ajaxError("验签失败");
        }
    }

    /**
     * 退款回调
     */
    public function refundNotify()
    {
        $xml = file_get_contents('php://input');
        if (!$xml) {
            $this->ajaxError("xml数据异常");
        }
        $pay = new Wxpay();
        //将XML转为array
        $params = $pay->xml_to_data($xml);
        //存入数组
        foreach ($params as $key => $val) {
            $params[$key] = $val;
        }
        //参数为空,不进行处理
        if (count($params) < 1) {
            $this->ajaxError("error");
        }
        $key    = MD5(getSettings('wx_pay', 'key'));
        $params = openssl_decrypt($params["req_info"], 'AES-256-ECB', $key);
        $params = $pay->xml_to_data($params);
        //验签
        if ($params["out_refund_no"]) {
            //返回状态为支付成功
            if ($params["refund_status"] == 'SUCCESS') {
                // 订单sn
                db()->startTrans();
                $sn = $params['out_refund_no'];
                if (empty($sn)) {
                    $this->ajaxError("订单不存在");
                }
                $sn1 = str_replace('mini', '', $sn);
                $sn2 = str_replace('app', '', $sn);
                // 检索订单
                $refund = db("refund")->where("sn={$sn1} or sn={$sn2}")->find();
                if (empty($refund)) {
                    $this->ajaxError("订单不存在");
                }

                $orderdetail = db('orderdetails')->where(['d_id' => $refund['oid']])->find();
                db('fa_item_sku')->where(['sku_id' => $orderdetail['d_sku_id']])->setInc('stock', $orderdetail['d_num']);
                $data1["refundReview"] = 3;
                $data1["endTime"]      = now_datetime();
                $res1                  = db("refund")->where(array("sn" => $sn))->update($data1);
                if (!$res1) {
                    db()->rollback();
                    $this->ajaxError("退款失败！");
                }
                db('product')->where(['id'=>$orderdetail['d_productId']])->setDec('p_sales',$orderdetail['d_num']);
                $res2 = db("orderdetails")->where(["d_id" => $refund["oid"]])->update(["d_refund" => 3]);

                if ($res2 == false) {
                    db()->rollback();
                    $this->ajaxError("退款失败！");
                }
                $where1 = 1;
                $where1 .= ' AND d_orderId = ' . $refund['order_id'];
                $where2 = 1;
                $where2 .= ' AND d_orderId = ' . $refund['order_id'] . ' and d_refund=3';
                $list1  = db('orderdetails')
                    ->where($where1)
                    ->count();
                $list2  = db('orderdetails')
                    ->where($where2)
                    ->count();
                if ($list1 == $list2) {
                    db('onlineorder')->where(['o_id' => $refund['order_id']])->update(['o_status' => 7]);
                }
                //消息模版
                $message_template = messageTemplate(1);
                //用户信息
                $member = db('member')->where(['id' => $refund['mid']])->find();
                //极光推送
                $push = new Push();
                //send_order类型为发货  id是当前订单的id
                $extras = ['type' => 'refund', 'id' => $refund['id']];
                //发送通知
                $push->send_to_one($member['m_account'], $message_template['alert'], $message_template['title'], $extras);

                //站内消息
                send_message($message_template['title'], $message_template['alert'], 2, 1, $member['id'], $refund['id']);

                db()->commit();
                //通知微信处理成功
                $this->replyNotify();
            }
        } else {
            $this->ajaxError("参数不正确");
        }
    }
}