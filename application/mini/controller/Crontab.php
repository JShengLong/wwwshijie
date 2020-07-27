<?php

namespace app\mini\controller;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/13
 * Time: 16:14
 */

use PHPMailer\PHPMailer\Exception;
use Redis\RedisPackage;
use sendsms\SendSms;
use think\Request;
use PHPMailer\PHPMailer\PHPMailer;

class Crontab extends Base
{
    protected $order_key_expire = 5;

    public function youjian()
    {
        $email = '3123682359@qq.com'; // 收件人邮箱
        $code  = rand(100000, 999999); // 验证码
        // 实例化PHPMailer核心类
        $mail = new PHPMailer();
        // 是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可默认关闭debug调试模式
        $mail->SMTPDebug = 0;
        // 使用smtp鉴权方式发送邮件
        $mail->isSMTP();
        // smtp需要鉴权 这个必须是true
        $mail->SMTPAuth = true;
        // 链接qq域名邮箱的服务器地址
        $mail->Host = 'smtp.qq.com';
        // 设置使用ssl加密方式登录鉴权
        $mail->SMTPSecure = 'ssl';
        // 设置ssl连接smtp服务器的远程服务器端口号
        $mail->Port = 465;
        // 设置发送的邮件的编码
        $mail->CharSet = 'UTF-8';
        // 设置发件人昵称 显示在收件人邮件的发件人邮箱地址前的发件人姓名
        $mail->FromName = '医疗检测样品';
        // smtp登录的账号 QQ邮箱即可
//        $mail->addAddress('2665442735@qq.com', 'qq');
        $mail->Username = '1183909358@qq.com'; // 你的QQ邮箱
        // smtp登录的密码 使用生成的授权码
        $mail->Password = 'zzjnliqksbabebii';
        // 设置发件人邮箱地址 同登录账号
//        $mail->setFrom('1183909358@qq.com', 'fajian');
        $mail->From = '2665442735@qq.com';// 你的QQ邮箱
        // 邮件正文是否为html编码 注意此处是一个方法
        $mail->isHTML(false);
        // 设置收件人邮箱地址
        $mail->addAddress($email);
        // 添加多个收件人 则多次调用方法即可
        //$mail->addAddress('87654321@163.com');
        // 添加该邮件的主题
        $mail->Subject = '医疗检测样品';
        // 添加邮件正文
         $mail->Body = "您的验证码为：<h1>$code</h1>，如非本人操作请忽略。";
        // 为该邮件添加附件
//        $mail->addAttachment('./uploads/3cd80b0be0776fcbec30c44e140f0d9e.jpg', '1.png');
        // $mail->addAttachment('build.php');
        // 发送邮件 返回状态
        try{
            $status = $mail->send();
            if ($status) {
                echo 'success';
            } else {
                echo 'fail';
            }
        }catch (Exception $exception) {
            dump($exception->getMessage());
        }
    }

    public function addGoods(Request $request)
    {
        new SendSms();
//        $id='68skww';
//        $id=mb_convert_encoding($id, "gb2312", "utf-8");
//        $pwd='68skwwaaa';
//        $to='17669127735';
//        $code=rand(100000,999999);
//        $content="你好，你的短信验证码是{$code}";
//        $ContentS = mb_convert_encoding($content, "gb2312", "utf-8");
//        $url="http://service.winic.org:8009/sys_port/gateway/index.asp?id={$id}&pwd={$pwd}&to={$to}&content={$ContentS}";
//        $response=file_get_contents($url);
//        dump($response);die;
//
//
//        $randStr = str_shuffle('1234567890');
//        $code = substr($randStr,0,4);
//        $content = "你的验证码是：".$code."【中正云通信】";
//        $mobile = "17669127735";
//        $url="http://service.winic.org:8009/sys_port/gateway/index.asp?";
//        $data = "id=%s&pwd=%s&to=%s&Content=%s&time=";
//        $id = urlencode(iconv("utf-8","gb2312","68skww"));
//        $pwd = '68skwwaaa';
//        $to = $mobile;
//        $content = urlencode(iconv("UTF-8","GB2312",$content));
//        $rdata = sprintf($data, $id, $pwd, $to, $content);
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_POST,1);
//        curl_setopt($ch, CURLOPT_POSTFIELDS,$rdata);
//        curl_setopt($ch, CURLOPT_URL,$url);
//        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
//        $result = curl_exec($ch);
//        curl_close($ch);
//        dump($result);die;


//        return $result;
        if ($request->isPost()) {
            //商品id
            $product_id = input('post.product_id');
            //查询商品
            $product = db('product')->where(['id' => $product_id])->find();
            if (empty($product)) {
                $this->ajaxError('该商品已下架');
            }
            if ($product['p_stock'] <= 0) {
                $this->ajaxError('该商品已经秒杀完成');
            }
            //定义商品库存队列key
            $listKey = 'goods_' . $product_id;
            //创建连接redis对象
            $redis        = new RedisPackage();
            $orderKey     = "buy_order_" . $product_id;
            $getUserRedis = $redis->hKeys($orderKey);
            foreach ($getUserRedis as $key => $value) {
                $get  = $redis->hGet($orderKey, $value);
                $data = json_decode($get, true);
                if (time() >= $data['time'] + $this->order_key_expire) {
                    $redis->hDel($orderKey, $value);
                }
            }
            $getUserRedis1 = $redis->hGetAll($orderKey);
            //判断这个队列是否存在
            if (!$redis->lLen($listKey) && !count($getUserRedis1)) {
                for ($i = 1; $i <= $product['p_stock']; $i++) {
                    //将商品id push到列表中
                    $redis->rPush($listKey, $product_id);
                }
            }
            $this->ajaxSuccess('秒杀初始化完成');
        } else {
            $this->ajaxError('无效的请求方式');
        }


    }

    /**
     * 秒杀抢购
     * @param Request $request
     */
    public function kill(Request $request)
    {
//        if ($request->isPost()) {
        $product_id = 34;
        //用户的id
        $uuid = rand(100000, 999999);
        //创建连接redis对象
        //设置超时时间时间一到清除该用户的抢购数据
        $redis       = new RedisPackage();
        $listKey     = 'goods_' . $product_id;
        $orderKey    = "buy_order_" . $product_id;
        $failUserNum = "fail_user_num_" . $product_id;
        if (!$redis->hGet($orderKey, $product_id . '_' . $uuid)) {
            if ($goodsId = $redis->lPop($listKey)) {
                //秒杀成功
                //将幸运用户存在集合中
                $data = json_encode(['time' => time(), 'uuid' => $uuid]);
                $redis->hSet($orderKey, $product_id . '_' . $uuid, $data);
                //通知客户端
                $this->ajaxSuccess('恭喜你抢到了');
            } else {
                //秒杀失败
                //将失败用户计数
                $redis->incr($failUserNum);
                $this->ajaxError('没有抢到哦');
            }
        } else {
            $this->ajaxSuccess('恭喜你抢到了');
        }

//        }else{
//            $this->ajaxError('无效的请求方式');
//        }
    }

    //确认订单页面
    public function addOrder(Request $request)
    {
        if ($request->isPost()) {
            $product_id = input('post.product_id');
            //用户的id
            $uuid = $this->uid;
            //创建连接redis对象
            $redis    = new RedisPackage();
            $orderKey = "buy_order_" . $product_id;
            $get      = $redis->hGet($orderKey, $product_id . '_' . $uuid, $uuid);
            $get      = json_decode($get, true);
            if (time() < $get['time'] + $this->order_key_expire) {
                //返回默认收货地址
                //商品信息
                //其他订单提交页面数据
                $this->ajaxSuccess('success');
            } else {
                $redis->hDel($orderKey, $product_id . '_' . $uuid, $uuid);
                //该用户在redis缓存中不存在 ，判断为未抢到商品，返回数据供前端判断
                $this->ajaxError('error');
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     *生成订单
     * @param Request $request
     */
    public function setOrder(Request $request)
    {
        if ($request->isPost()) {
            $product_id = input('post.product_id');
            //用户的id
            $uuid = $this->uid;
            //创建连接redis对象
            $redis    = new RedisPackage();
            $orderKey = "buy_order_" . $product_id;
            $get      = $redis->hGet($orderKey, $product_id . '_' . $uuid, $uuid);
            $get      = json_decode($get, true);
            if (time() < $get['time'] + $this->order_key_expire) {
                //获取商品信息
                //生成订单
                //获取支付数据
            } else {
                $redis->hDel($orderKey, $product_id . '_' . $uuid, $uuid);
                //该用户在redis缓存中不存在 ，判断为未抢到商品，返回数据供前端判断
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    public function notify()
    {
        //支付回调
        //查询订单
        //更改订单状态
        //减少商品库存
        $product_id = 38;//商品id
        $uuid       = 40;//用户id
        $redis      = new RedisPackage();
        $orderKey   = "buy_order_" . $product_id;
        //删除该用户在redis的抢购数据
        $redis->hDel($orderKey, $product_id . '_' . $uuid, $uuid);
    }
}
