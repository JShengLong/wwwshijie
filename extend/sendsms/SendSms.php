<?php

namespace sendsms;

use think\Db;
use think\Config;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

/**
 * Created by PhpStorm.
 * User: 汲生龙
 * Date: 2019/9/21
 * Time: 9:29
 */
class SendSms
{
    public function SendSms($data, $smsType)
    {
        $config = Config::get('sms');
        $patt   = '/^1[3456789][0-9]{9}$/';
        if (empty($data['phone'])) {
            return [
                'code'    => 0,
                'message' => '请填写手机号'
            ];
        }
        if (preg_match($patt, $data['phone'])) {
            $sms_status = Db::table('qtsms')->where([
                'phone'  => $data['phone'],
                'scene'  => $data['scene'],
                'status' => 1
            ])->whereTime('create_time', '>', date('Y-m-d H:i:s', (time() - $config['resend'])))->find();
            if ($sms_status) {
                $time = (($config['resend']) - (strtotime(date('Y-m-d H:i:s', time())) - strtotime($sms_status['create_time'])));
                return [
                    'code'    => 0,
                    'message' => "{$time}秒后重新发送",
                ];
            }
            $response = ['code' => 0, 'message' => '无效的请求'];
            switch ($smsType) {
                case 'qt':
                    $response = $this->QtSendSms($data);
                    break;
                case 'ali':
                    $response = $this->ALiBaBaSendSms($data);
                    break;
                case 'juhe':
                    $response = $this->JuHeSendSms($data);
                    break;
            }
            if ($response['code'] > 0) {
                Db::table('qtsms')->where([
                    'phone'  => $data['phone'],
                    'scene'  => $data['scene'],
                    'status' => 1
                ])->update(['status' => 3]);
                $res = Db::table('qtsms')->insert([
                    'phone'       => $data['phone'],
                    'code'        => $data['code'],
                    'create_time' => date('Y-m-d H:i:s', time()),
                    'end_time'    => date('Y-m-d H:i:s', strtotime("+{$config['code_failure_time']}minute")),
                    'scene'       => $data['scene'],
                    'ip'          => $_SERVER["REMOTE_ADDR"]
                ]);
                if ($res == false) {
                    return [
                        'code'    => 0,
                        'message' => '验证码异常',
                    ];
                }
            }
            return $response;
        } else {
            return [
                'code'    => 0,
                'message' => '手机码不正确'
            ];
        }
    }

    /**
     * @Notes:验证短信验证码
     * @Author:jsl
     * @Date: 2019/9/21
     * @Time: 10:42
     * @param $phone 手机号码
     * @param $code 验证码
     * @param $scene 用途
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function check($phone, $code, $scene)
    {
        $sms_status = Db::table('qtsms')->where([
            'phone'  => $phone,
            'scene'  => $scene,
            'code'   => $code,
            'status' => 1
        ])->order('id desc')->find();
        if ($sms_status) {//验证成功
            if ($sms_status['end_time'] < date('Y-m-d H:i:s', time())) {
                return [
                    'code'    => 0,
                    'message' => '验证码已失效',
                ];
            } else {
                $res = Db::table('qtsms')->where('id', $sms_status['id'])->update(['status' => 2]);
                if ($res) {
                    return [
                        'code'    => 1,
                        'message' => '验证成功',
                    ];
                } else {
                    return [

                        'code'    => 0,
                        'message' => '验证异常',
                    ];
                }

            }
        } else {
            return [
                'code'    => 0,
                'message' => '您输入的验证码有误',
            ];
        }
    }

    /**
     * @Notes:阿里巴巴短信接口
     * @Author:汲生龙
     * @Date: 2019/9/21
     * @Time: 10:41
     * @param $data
     * @return array
     * @throws ClientException
     */
    public function ALiBaBaSendSms($data)
    {
        $config = Config::get('ALiBaBaSendSms');
        AlibabaCloud::accessKeyClient($config['accessKeyId'], $config['accessKeySecret'])
            ->regionId($config['region'])
            ->asDefaultClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId'      => $config['region'],
                        'PhoneNumbers'  => $data['phone'],//手机号
                        'SignName'      => $config['SignName'],//模版签名
                        'TemplateCode'  => $config['TemplateCode'],//模版ID
                        'TemplateParam' => json_encode(array(
                            "code" => $data["code"])),
                    ],
                ])
                ->request();
            $res    = $result->toArray();
            if ($res['Code'] == 'OK') {
                return [
                    'code'    => 1,
                    'message' => '短信发送成功'
                ];
            } else {
                return [
                    'code'    => 0,
                    'message' => '短信发送失败'
                ];
            }
        } catch (ClientException $e) {
            return [
                'code'    => 0,
                'message' => $e->getErrorMessage()
            ];
        } catch (ServerException $e) {
            return [
                'code'    => 0,
                'message' => $e->getErrorMessage()
            ];
        }
    }

    /**
     * @Notes:桥通的短信接口
     * @Author:汲生龙
     * @Date: 2019/9/21
     * @Time: 10:41
     * @param $data
     * @return array
     */
    public function QtSendSms($data)
    {
        $config = Config::get('QtSms');
        try {
            $username = $config['username']; //用户名
            $password = $config['password']; //密码
            $template = $config['template']; //模版
            $template = str_replace('{$code}', $data['code'], $template);//内容
            $ContentS = rawurlencode(mb_convert_encoding($template, "gb2312", "utf-8"));//短信内容做GB2312转码处理
            $url      = "https://sdk2.028lk.com/sdk2/LinkWS.asmx/BatchSend2?CorpID=" . $username . "&Pwd=" . $password . "&Mobile=" . $data['phone'] . "&Content=" . $ContentS . "&Cell=&SendTime=";
            $result   = file_get_contents($url);
            $re       = simplexml_load_string($result);
            if ($re[0] > 0) {
                return [
                    'code'    => 1,
                    'message' => '发送成功',
                ];
            } elseif ($re == 0) {
                return [
                    'code'    => 0,
                    'message' => '网络访问超时，请稍后再试！',
                ];
            } elseif ($re == -9) {
                return [
                    'code'    => 0,
                    'message' => '发送号码为空',
                ];
            } elseif ($re == -101) {
                return [
                    'code'    => 0,
                    'message' => '调用接口速度太快',
                ];
            } else {
                return [
                    'code'    => 0,
                    'message' => '发送失败',
                ];
            }
        } catch (\Exception $exception) {
            return [
                'code'    => 0,
                'message' => '网络错误,无法连接服务器',
            ];
        }
    }

    /**
     * @Notes:聚合短信
     * @Author:汲生龙
     * @Date: 2019/9/21
     * @Time: 11:00
     * @param $data
     * @return array
     */
    public function JuHeSendSms($data)
    {
        $config   = Config::get('JuHeSendSms');
        $smsConf  = array(
            'key'       => $config['key'], //您申请的APPKEY
            'mobile'    => $data['phone'], //接受短信的用户手机号码
            'tpl_id'    => $config['tpl_id'], //您申请的短信模板ID，根据实际情况修改
            'tpl_value' => '#code#=' . $data['code'] . '&#company#=' . $config['sndName'] //您设置的模板变量，根据实际情况修改
        );
        $url      = $config['url'];
        $params   = $smsConf;
        $ispost   = true;
        $httpInfo = array();
        $ch       = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if ($params) {
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        curl_close($ch);
        if ($response) {
            $result     = json_decode($response, true);
            $error_code = $result['error_code'];
            if ($error_code == 0) {
                //状态为0，说明短信发送成功
                return [
                    'code'    => 1,
                    'message' => '发送成功',
                ];
            } else {
                return [
                    'code'    => 0,
                    'message' => '发送失败',
                ];
            }
        } else {
            return [
                'code'    => 0,
                'message' => '发送失败',
            ];
        }
    }
}
