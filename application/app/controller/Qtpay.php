<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-06-21
 * Time: 10:44
 */

namespace app\app\controller;

use HXC\Pay\Pay;
use think\Controller;

/**
 * Class Qtpay
 * @package app\app\controller
 */
class Qtpay extends Controller
{
    use Pay;

    /**
     * 假支付：配置文件中，修改pay=》env参数为dev即可，线上环境为production
     */

    /**
     * 支付接口：/app/qtpay/pay
     * 参数：
     * 【tpye】：wechat，alipay
     * 【func】:app（app支付，微信/支付宝哦）,mp（公众号支付，微信）,wap（手机网站支付，微信/支付宝）,mini（小程序支付，微信/支付宝）,web（网页支付，支付宝）
     */

    /**
     * 获取订单
     * @param $type 支付类型
     * @return array
     */
    protected function getOrder($type)
    {
        /**
         * 实现你自己的逻辑
         */
        $order_sn = time();//订单号，此处为举例子，需自己实现
        $total = 0.01;//单位：元

        if($type === 'wechat'){//微信支付
            $order = [
                'out_trade_no' => $order_sn,
                'total_fee' => bcmul($total,100), //单位：分
                'body' => 'test body - 测试',
                'openid' => 'oydxwwjsGw-eJKDZbywFkbHO1O0w',
            ];
        }elseif($type === 'alipay'){//支付宝支付
            $order = [
                'out_trade_no' => $order_sn,
                'total_amount' => $total, //单位：元
                'subject' => 'test subject - 测试',
            ];
        }else{//开发环境，假支付
            $order = [
                'id' => 1,
                'total' => $total
            ];
        }
        return $order;
    }

    /**
     * 支付回调的统一处理
     * @param $data
     * @param $flag
     */
    protected function notify($data,$flag)
    {
        if($flag === 'wx'){//微信的回调

            $order_sn = $data['out_trade_no'];//支付的订单号
            $user_open_id = $data['openid'];//支付用户的openid
            $mch_id = $data['mch_idv'];//收款的商户号
            $cash_fee = bcdiv($data['cash_fee'],100,2);//支付金额，单位：元

        }elseif($flag === 'ali'){//支付宝的回调

            // 请自行对 trade_status 进行判断及其它逻辑进行判断，在支付宝的业务通知中，只有交易通知状态为 TRADE_SUCCESS 或 TRADE_FINISHED 时，支付宝才会认定为买家付款成功。
            // 1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号；
            // 2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额）；
            // 3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）；
            // 4、验证app_id是否为该商户本身。
            // 5、其它业务逻辑情况

        }else{//假支付处理

            //自己实现逻辑

        }
    }
}