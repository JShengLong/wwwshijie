<?php

namespace app\mini\controller;

use think\Cache;
use think\Config;
use think\Controller;
use think\Session;
use think\Console;
use JPush\Client;

/**
 * Class Base
 * @package app\mini\controller
 * 基类控制器
 */
class Base extends Controller
{
    protected $mid = '';

    Public function _initialize()
    {

        if ($this->request->controller() == "Base") {
            exit('禁止访问');
        }
        parent::_initialize();
        $this->Open_cross_domain(true);
    }

    /**
     * 判断是否登录
     * @return bool
     */
    public function isLogin()
    {
        $token  = input('token');
        $member = Cache::get($token);
        if (empty($member)) {
            return false;
        }
        $this->mid = $member['id'];
        return true;
    }

    /**
     * Created by PhpStorm.
     * User: jsl
     * @param $type
     * 是否开启跨域
     */
    private function Open_cross_domain($type = false)
    {
        if ($type == true) {
            header('Access-Control-Allow-Origin: *');
//            header('Access-Control-Allow-Origin: http://localhost:8080');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, authKey, sessionId");
        }
    }

    // 登录错误
    public function ajaxLoginError($info)
    {
        $this->response(-1, $info, '');
    }

    /**
     * @param $info
     * 返回ajax失败
     */
    public function ajaxError($info)
    {
        $this->response(0, $info, '');
    }

    /**
     * @param string $data
     * @param string $info
     * 输出ajax成功
     */
    public function ajaxSuccess($info = '', $data = '')
    {
        $this->response(1, $info, $data);

    }

    /**
     * @param $code
     * @param $msg
     * @param $data
     * 自定义返回数据
     */
    public function ajaxMessage($code, $msg, $data)
    {
        $this->response($code, $msg, $data);
    }

    /**
     * @param $data
     * @param int $json_option
     */
    protected function ajaxReturn($data, $json_option = 0)
    {
        // 返回JSON数据格式到客户端 包含状态信息
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data, $json_option));
    }

    /**
     * @param $code
     * @param string $message
     * @param array $data
     * @return string
     * ajax返回数据格式
     */
    public function response($code, $message = '', $data = array())
    {
        if (!(is_numeric($code))) {
            return '';
        }
        $result = array(
            'code' => $code,
            'msg'  => $message,
            'data' => $data
        );
        exit($this->ajaxReturn($result));
    }

    /**
     * @param $token
     * @return mixed
     * 读取缓存
     */
    public function get($token)
    {
        return Cache::get($token);
    }

    /**
     * @param $token
     * @param $value
     * @return bool
     * 写入缓存
     */
    public function set($token, $value)
    {
        return Cache::set($token, $value);
    }

    /**
     * @param $token
     * @return bool
     * 删除缓存
     */
    public function remove($token)
    {
        return Cache::rm($token);
    }

    /**
     * 获取token
     * @param $params
     * @return string
     */
    protected function generateToken($params)
    {
        $expire = config('token_expire') == 0 ? 0 : time() + config('token_expire');
        //用户id-用户名-有效期-登录时间
        $token = base64_encode($params['id'] . '-' . $params['m_account'] . '-' . $expire . '-' . time());
        return $token;
    }

    /**
     * @author: zxf
     * @date: 2018-12-08
     * @description: 解密微信用户敏感数据
     * @return array
     */
    public function WxDecode($data)
    {
        vendor('wx.wxBizDataCrypt');
        $appid             = getSettings('wx_pay', 'miniapp_id');
        $appsecret         = getSettings('wx_pay', 'mini_secret');
        $grant_type        = "authorization_code"; //授权（必填）
        $code              = $data['code'];        //有效期5分钟 登录会话
        $rawData           = $data['rawData'];
        $rawData           = json_decode($rawData, true);
        $url
                           = "https://api.weixin.qq.com/sns/jscode2session?" . "appid=" . $appid . "&secret=" . $appsecret . "&js_code=" . $code . "&grant_type=" . $grant_type;
        $res               = json_decode($this->httpGet($url), true);
        $datas             = array();
        $datas['openid']   = $res['openid'];
        $datas['thumb']    = $rawData['avatarUrl'];
        $datas['nickname'] = $rawData['nickName'];
        $datas['unionid']  = isset($res['unionid']) ? $res['unionid'] : "";
        return $datas;

    }

    /**
     * get请求
     * @param $url
     * @return bool|string
     */
    public function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    /**
     * get请求
     * @param $url
     * @return bool|string
     */
    public function httpPost($url, $post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 快递100
     * @param $param
     * @return mixed
     */
    public function kuaidi($param)
    {
        //参数设置
        $post_data             = array();
        $post_data["customer"] = config('kuaidi100')['customer'];
        $key                   = config('kuaidi100')['key'];
        $post_data["param"]    = json_encode($param);
        $url                   = 'http://poll.kuaidi100.com/poll/query.do';
        $post_data["sign"]     = md5($post_data["param"] . $key . $post_data["customer"]);
        $post_data["sign"]     = strtoupper($post_data["sign"]);
        $o                     = "";
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";        //默认UTF-8编码格式
        }
        $post_data = substr($o, 0, -1);
        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $data   = str_replace("\"", '"', $result);
        $data   = json_decode($data, true);
        return $data;
    }

    /**
     * 获取微信openID
     * @param $code
     * @return array
     */
    public function getWxUser($code)
    {

        $appid    = getSettings('wx_pay', 'appid');
        $secret   = getSettings('wx_pay', 'secret');
        $get_token_url
                  = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";
        $res      = $this->httpGet($get_token_url);
        $json_obj = json_decode($res, true);

        $access_token = $json_obj['access_token'];
        $openid       = $json_obj['openid'];
        $get_user_info_url
                      = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $res          = $this->httpGet($get_user_info_url);
        //解析json
        $user_obj = json_decode($res, true);
        $thumb    = $user_obj['headimgurl'];
        $nickname = $user_obj['nickname'];

        $data             = array();
        $data['openid']   = $openid;
        $data['thumb']    = $thumb;
        $data['nickname'] = $nickname;
        $data['unionid']  = isset($user_obj['unionid']) ? $user_obj['unionid'] : "";
        return $data;
    }

    /**
     * 发送用户推送通知
     * @param $account 别名-用户账号
     * @param $alert 内容
     * @param $title 标题
     * @return bool
     */
    public function send_to_one($mid, $alert, $title, $extras)
    {
        $account = $mid;
        $config  = config('jpush');
        //获取app_key
        $app_key = $config['app_key'];
        //获取master_secret
        $master_secret = $config['master_secret'];
        $notification  = [
            'title'      => $title,//通知栏标题
            'builder_id' => 1, //通知栏样式
            'extras'     => $extras,//扩展字段 这里自定义 JSON 格式的 Key / Value 信息，以供业务使用。
        ];
        $opn           = ['apns_production' => true]; //apns_production true生产环境 false 测试环境
        try {
            //实例化极光接口
            $client = new Client($app_key, $master_secret);
            //调用发送通知接口
            $client->push()
                //推送范围 iOS Android winphone
                   ->setPlatform(['ios', 'android'])
                //推送的别名
                   ->addAlias($account)
                //推送Android的内容
                   ->androidNotification($alert, $notification)
                //推送iOS的内容
                   ->iosNotification($alert)
                //可选参数
                   ->options($opn)
                //发送
                   ->send();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 发送广播推送通知
     * @param $alert
     * @param $title
     * @param $extras
     * @return bool
     */
    public function send_to_all($alert, $title, $extras)
    {
        $config = config('jpush');
        //获取app_key
        $app_key = $config['app_key'];
        //获取master_secret
        $master_secret = $config['master_secret'];
        $notification  = [
            'title'      => $title,//通知栏标题
            'builder_id' => 1, //通知栏样式
            'extras'     => $extras,//扩展字段 这里自定义 JSON 格式的 Key / Value 信息，以供业务使用。
        ];
        $opn           = ['apns_production' => true]; //apns_production true生产环境 false 测试环境
        try {
            //实例化极光接口
            $client = new Client($app_key, $master_secret);
            //调用发送通知接口
            $client->push()
                //推送范围 iOS Android winphone
                   ->setPlatform(['ios', 'android'])
                //推送所有
                   ->addAllAudience()
                //推送Android的内容
                   ->androidNotification($alert, $notification)
                //推送iOS的内容
                   ->iosNotification($alert)
                //可选参数
                   ->options($opn)
                //发送
                   ->send();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 站内信和极光推送
     * @param $mid
     * @param $title
     * @param $info
     * @param $award
     */
    public function send($mid, $title, $info, $award, $extras)
    {
        $alert = "您于" . now_datetime() . "获得{$info}{$award}元";
        $this->send_to_one($mid, $alert, $title, $extras);
        send_message($title, $alert, 2, 1, $mid);
    }

    /**
     * 组合sku参数
     * @param $id
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sku($id)
    {
        $sku = db('fa_item_sku')->where(['sku_id' => $id])->find();
        //组合sku
        $attr_symbol_path = explode(',', $sku['attr_symbol_path']);
        $sku_name         = [];
        foreach ($attr_symbol_path as $k => $v) {
            $sku_name[$k] = db('fa_item_attr_val')->where(['symbol' => $v])->value('attr_value');
        }
        return implode("/", $sku_name) . ";";
    }

    /**
     * 根据阶梯价格算出商品总价格
     * @param $sku_id
     * @param $num
     * @return int|string
     */
    public function ladder($sku_id, $num)
    {
        $ladder = db('ladder')
            ->where('sku_id', $sku_id)
            ->order('num desc')
            ->select();
        $price  = 0;
        foreach ($ladder as $key => $value) {
            if ($num >= $value['num']) {
//                $price = bcmul($num, $value['price'], 2);
                $price = $value['price'];
                break;
            }
        }
        if ($price == 0) {
            $price = db('fa_item_sku')->where(['sku_id' => $sku_id])->value('price');
        }
        return $price;
    }

    /**
     * 生成二维码
     * @param $params
     * @return string
     */
    public function phpqrcode($params)
    {
        vendor("phpqrcode.phpqrcode");
        $data      = $params['url'];
        $file_name = md5(microtime(true));
        $new_file  = ROOT_PATH . "public/static/qrcode/{$file_name}.png";
        $level     = 'L';
        $size      = 4;
        $QRcode    = new \QRcode();
        ob_start();
        $QRcode->png($data, $new_file, $level, $size, 2);
        ob_end_clean();
        $url = saver() . "/static/qrcode/{$file_name}.png";
        return $url;
    }
}
