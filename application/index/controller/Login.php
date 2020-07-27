<?php

namespace app\index\controller;

use sendsms\SendSms;
use think\Cache;
use think\Db;
use alipay\Alipay;
use easemob\Easemob;

class Login extends Base
{
    private $config        = [];
    private $client_id     = 'YXA6Khz5MKY5EeqrknUikNJD4w';
    private $client_secret = 'YXA6MS-IUZx3D559IznliI-lMPi91ZI';
    private $org_name      = '1412200604061759';
    private $app_name      = 'kefuchannelapp80983';

    public function _initialize()
    {
        $ali_pay      = getSettings('ali_pay');//支付宝的所有配置
        $this->config = [
            'app_id'         => $ali_pay['app_id'],//APPID
            'ali_public_key' => $ali_pay['ali_public_key'],//支付宝公钥
            'private_key'    => $ali_pay['private_key'],//支付宝私
            'partner'        => $ali_pay['partner']
        ];
    }

    public function aa(){
        $options['client_id']     = $this->client_id;
        $options['client_secret'] = $this->client_secret;
        $options['org_name']      = $this->org_name;
        $options['app_name']      = $this->app_name;
        $easemob                  = new Easemob($options);
        $easemob_info             = $easemob->createUser(19862940552, '123456q');
        if (isset($easemob_info['error']) && $easemob_info['error'] == 'duplicate_unique_property_exists') {
            $easemob->deleteUser(19862940552);
            $easemob_info = $easemob->createUser(19862940552, '123456q');
        }
        $data['easemob_uuid']     = $easemob_info['entities'][0]['uuid'];
        $data['easemob_username'] = 19862940552;
        $data['easemob_password'] = '123456q';
        $this->ajaxSuccess($data);
    }
    /**
     * 普通登录
     */
    public function login()
    {
        if (request()->isPost()) {
            //账号
            $account = input("account");
            //密码
            $password = input("password");
            //判断账号不能为空
            if (empty($account)) {
                $this->ajaxError("手机号码不能为空");
            }
            //判断密码不能为空
            if (empty($password)) {
                $this->ajaxError("密码不能为空");
            }
            //查询用户
            $member = db("member")
                ->where(array("m_account" => $account))
                ->find();
            //判断账号是否存在
            if (empty($member)) {
                $this->ajaxError("该账号不存在！");
            }
            //判断密码是否正确
            if (!password_verify($password, $member['m_password'])) {
                $this->ajaxError("密码不正确");
            }
            //判断账号是否注销
            if ($member["m_isDisable"] == 1) {
                $this->ajaxError("该账号已注销，请联系客服");
            }
            //获取当前用户的token
            $token = $this->generateToken($member);
            //缓存用户信息
            Cache::set($token, $member, config('token_expire'));
            $member["token"] = $token;
            //将null值转换成空字符串
            foreach ($member as $key => $value) {
                if ($value === null) {
                    $member[$key] = "";
                }
            }
            $this->ajaxSuccess('登录成功', $member);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 验证码登录
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function verificationCodeLogin()
    {
        if (request()->isPost()) {
            //账号
            $account = trim(input('post.account'));
            if (empty($account)) {
                $this->ajaxError('请输入手机号');
            }
            $code = trim(input('post.code'));
            if (empty($code)) {
                $this->ajaxError('请输入验证码');
            }
            $member_info = db('member')->where(['m_account' => $account])->find();
            if ($member_info['m_isDisable'] == 1) {
                $this->ajaxError('您的账号已被禁用');
            }
            $qt_sms = new SendSms();
            $res    = $qt_sms->check($account, $code, 'login');
            if ($res["code"] == 0) {
                $this->ajaxError($res["message"]);
            }
            $token = $this->generateToken($member_info);
            Cache::set($token, $member_info);
            $member_info["token"] = $token;
            foreach ($member_info as $key => $value) {
                if ($value === null) {
                    $member_info[$key] = "";
                }
            }
            $this->ajaxSuccess('登录成功', $member_info);
        } else {
            $this->ajaxError('无效的请求方式');
        }

    }

    /**
     * 微信小程序登陆
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wxMiniLogin()
    {
        $data   = request()->param();
        $res    = $this->WxDecode($data);
        $member = db('member')
            ->where('m_wx_openId', $res['openid'])
            ->find();
        //未绑定账号
        if (empty($member)) {
            $this->ajaxMessage(-2, '未绑定手机号', $res);
        }
        //获取token

        $token = $this->generateToken($member);
        //存储token
        Cache::set($token, $member, config('token_expire'));
        //返回token
        $member["token"] = $token;
        //去除数组中的null值
        foreach ($member as $key => $value) {
            if ($value === null) {
                $member[$key] = "";
            }
        }
        $return['member'] = $member;
        $return['res']    = $res;
        $this->ajaxSuccess('登录成功', $member);

    }

    /**
     * 微信小程序绑定手机号
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function wxMiniRegister()
    {
        $param = request()->param();
        if (empty($param['account'])) {
            $this->ajaxError("请输入手机号");
        }
        if (empty($param['code'])) {
            $this->ajaxError("请输入验证码");
        }
        $data['m_wx_openId'] = $param['openid'];
        $data['m_thumb']     = $param['thumb'];
        $data['m_nickname']  = $param['nickname'];
        $data['unionid']     = $param['unionid'];
        $invitation          = $param['invitation'];
        $isopenInvitation    = getSettings('isopenInvitation', 'isopenInvitation');
        if ($isopenInvitation == 1) {
            if (empty($invitation)) {
                $this->ajaxError('邀请码不能为空');
            }
        }
        if ($invitation) {
            $parent = db('member')->where(['m_account' => $invitation])->find();
            if (empty($parent)) {
                $this->ajaxError('输入的邀请码有误');
            }
            $data['m_fatherId']        = $parent['id'];
            $data['m_grandpaId']       = $parent['m_fatherId'];
            $data['m_grate_grandpaId'] = $parent['m_grandpaId'];
            if (empty($parent["m_pathtree"])) {
                $data["m_pathtree"] = ",{$parent["id"]},";
            } else {
                $data["m_pathtree"] = "{$parent["m_pathtree"]}{$parent["id"]},";
            }
        }
        db()->startTrans();
        $qt_sms = new SendSms();
        $res    = $qt_sms->check($param['account'], $param['code'], 'bind_phone');
        if ($res["code"] == 0) {
            db()->rollback();
            $this->ajaxError($res["message"]);
        }
        //用户默认头像
        $morenimg = getSettings('morenimg', 'morenimg');
        //默认支付密码
        $paypassword       = getSettings('paypassword', 'paypassword');
        $data["m_account"] = $param['account'];
        $data['m_thumb']   = $morenimg;
        //支付密码
        $data["m_payment_password"] = password_hash($paypassword, PASSWORD_DEFAULT);
        $data["m_isDisable"]        = 2;
        $data["m_isDelete"]         = 2;
        $data["m_createTime"]       = now_datetime();
        $data["m_updateTime"]       = now_datetime();
        $data["m_invitation_code"]  = '';

        $options['client_id']     = $this->client_id;
        $options['client_secret'] = $this->client_secret;
        $options['org_name']      = $this->org_name;
        $options['app_name']      = $this->app_name;
        $easemob                  = new Easemob($options);
        $easemob_info             = $easemob->createUser($param['account'], $paypassword);
        if (isset($easemob_info['error']) && $easemob_info['error'] == 'duplicate_unique_property_exists') {
            $easemob->deleteUser($param['account']);
            $easemob_info = $easemob->createUser($param['account'], $paypassword);
        }
        $data['easemob_uuid']     = $easemob_info['entities'][0]['uuid'];
        $data['easemob_username'] = $param['account'];
        $data['easemob_password'] = $paypassword;

        $add = db("member")->insertGetId($data);
        if ($add) {
            db('member')->where(['id' => $add])->setField('m_invitation_code', 'SHIJ' . sprintf("%05d", $add));
            $member = db('member')->where(['id' => $add])->find();
            $token  = $this->generateToken($member);
            //存储token
            Cache::set($token, $member, config('token_expire'));
            //返回token
            $member["token"] = $token;
            //去除数组中的null值
            foreach ($member as $key => $value) {
                if ($value === null) {
                    $member[$key] = "";
                }
            }
            db()->commit();
            $this->ajaxSuccess('绑定成功', $member);
        } else {
            db()->rollback();
            $this->ajaxError("绑定失败");
        }
    }

    /**
     * 第三方登陆
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ThirdPartyLanding()
    {
        if (request()->post()) {
            //微信或者支付宝
            $type = input('post.type');
//            $wx_type = input('post.wx_type');
            //唯一授权码
            $code   = input('post.code');
            $data1  = request()->param();
            $member = [];
            //微信登陆
            if ($type == 1) {
                $data = $this->getWxUser($data1);
                //微信个人信息
                $member = db('member')
                    ->where('unionid', $data['unionid'])
                    ->find();
                //支付宝登陆
            } elseif ($type == 2) {
                $AliPay = new Alipay($this->config);
                $data   = $AliPay->aliLogin($code);
                if ($data['code'] != 10000) {
                    $this->ajaxError('支付宝登录失败');
                }
                $member = db('member')
                    ->where('m_ali_token', $data['user_id'])
                    ->find();
                //未知的登陆方式
            } else {
                $this->ajaxError('未知错误');
            }
            //未绑定账号
            if (empty($member)) {
                $this->ajaxMessage(-2, '未绑定手机号', $data);
            }
            if($type==1){
                db('member')->where(['id'=>$member['id']])->setField('m_app_openId',$data['openid']);
            }
            //获取token
            $token = $this->generateToken($member);
            //存储token
            Cache::set($token, $member, config('token_expire'));
            //返回token
            $member["token"] = $token;
            //去除数组中的null值
            foreach ($member as $key => $value) {
                if ($value === null) {
                    $member[$key] = "";
                }
            }
            $this->ajaxSuccess('登录成功', $member);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * @Notes:发送验证码
     * @Author:jsl
     * @Date: 2019/9/5
     * @Time: 15:12
     */
    public function sendSms()
    {
        if (request()->post()) {
            $phone = input("post.phone");
            //注册 register 忘记密码 forget 修改密码 update_pwd 修改手机号 update_phone   修改支付密码 update_paypwd
            $scene = input("post.type");
            if (empty($scene)) {
                $this->ajaxError("无效请求方式");
            }
            $isEixts = Db::table("member")->where(["m_account" => $phone])->find();
            if ($scene == "register") {
                if ($isEixts) {
                    $this->ajaxError("此手机号已被注册，请前往登录");
                }
            } elseif ($scene == "forget" || $scene == "update_pwd" || $scene == "update_pwd" || $scene == "update_paypwd") {
                if (empty($isEixts)) {
                    $this->ajaxError("此手机号未注册");
                }
            }
            $snesms        = new SendSms();
            $data['phone'] = $phone;
            $data['code']  = rand(100000, 999999);
            $data['scene'] = $scene;
            //发送验证码
            $res = $snesms->SendSms($data, 'ali');
            if ($res["code"] == 1) {
                $this->ajaxSuccess($res["message"]);
            } else {
                $this->ajaxError($res["message"]);
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 支付宝获取授权码
     */
    public function aliAuth()
    {
        if (request()->isPost()) {
            $AliPay = new Alipay($this->config);
            $auth   = $AliPay->aliAuth();
            $this->ajaxSuccess('数据获取成功', $auth);
        } else {
            $this->ajaxError('无效的请求方式');
        }

    }

    /**
     * 第三方注册
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function ThirdPartyRegister()
    {
        if (request()->post()) {
            //微信或者支付宝
            $type = input('post.type');
            //唯一授权码
            $code    = input('post.code');
            $capthe  = input('capthe');
            $param   = input('param');
            $wx_type = input('post.wx_type');
            $data = [];
            //微信登陆
            if ($type == 1) {
                $wx_user=json_decode($param,true);
                $data['m_app_openId'] = $wx_user['openid'];
                $data['m_thumb']     = $wx_user['thumb'];
                $data['m_nickname']  = $wx_user['nickname'];
                $data['unionid']     = $wx_user['unionid'];
                //支付宝登陆
            } elseif ($type == 2) {
                $ali_user=json_decode($param,true);
                $data['m_ali_token'] = $ali_user['user_id'];
                $data['m_thumb']     = $ali_user['avatar'];
                $data['m_nickname']  = isset($ali_user['nick_name'])?$ali_user['nick_name']:'';
                //未知的登陆方式
            } else {
                $this->ajaxError('未知错误');
            }
            $account = input("account");//账号
            if (empty($account)) {
                $this->ajaxError("请输入手机号");
            }
            $user=db('member')->where(['m_account'=>$account])->find();
            if($user){
                $this->ajaxError('该手机号已注册');
            }
            if (empty($capthe)) {
                $this->ajaxError("请输入验证码");
            }
            $password  = input("password");//密码
            $password1 = input("password1");//确认密码
            if (empty($password)) {
                $this->ajaxError("请输入密码");
            }
            if (strlen($password) < 6 || !isNumAndLetter($password)) {
                $this->ajaxError("请输入至少6位数字+字母的密码");
            }
            if (empty($password1)) {
                $this->ajaxError("请输入确认密码");
            }
            if ($password != $password1) {
                $this->ajaxError("两次密码输入不一致");
            }
            $invitation       = input('invitation');
            $isopenInvitation = getSettings('isopenInvitation', 'isopenInvitation');
            if ($isopenInvitation == 1) {
                if (empty($invitation)) {
                    $this->ajaxError('邀请码不能为空');
                }
            }
            if ($invitation) {
                $parent = db('member')->where(['m_invitation_code' => $invitation])->find();
                if (empty($parent)) {
                    $this->ajaxError('输入的邀请码有误');
                }
                $data['m_fatherId']        = $parent['id'];
                $data['m_grandpaId']       = $parent['m_fatherId'];
                $data['m_grate_grandpaId'] = $parent['m_grandpaId'];
                if (empty($parent["m_pathtree"])) {
                    $data["m_pathtree"] = ",{$parent["id"]},";
                } else {
                    $data["m_pathtree"] = "{$parent["m_pathtree"]}{$parent["id"]},";
                }
            }
            db()->startTrans();
            $qt_sms = new SendSms();
            $res    = $qt_sms->check($account, $capthe, 'register');
            if ($res["code"] == 0) {
                db()->rollback();
                $this->ajaxError($res["message"]);
            }
            //用户默认头像
//            $morenimg = getSettings('morenimg', 'morenimg');
            //默认支付密码
            $paypassword        = getSettings('paypassword', 'paypassword');
            $data["m_account"]  = $account;
//            $data['m_thumb']    = $morenimg;
            $data["m_password"] = password_hash($password, PASSWORD_DEFAULT);
            //支付密码
//            $data["m_password"]        = password_hash($paypassword, PASSWORD_DEFAULT);
            $data["m_isDisable"]       = 2;
            $data["m_isDelete"]        = 2;
            $data["m_createTime"]      = now_datetime();
            $data["m_updateTime"]      = now_datetime();
            $data["m_invitation_code"] = '';

            $options['client_id']     = $this->client_id;
            $options['client_secret'] = $this->client_secret;
            $options['org_name']      = $this->org_name;
            $options['app_name']      = $this->app_name;
            $easemob                  = new Easemob($options);
            $easemob_info             = $easemob->createUser($account, $password);
            if (isset($easemob_info['error']) && $easemob_info['error'] == 'duplicate_unique_property_exists') {
                $easemob->deleteUser($account);
                $easemob_info = $easemob->createUser($account, $password);
            }
            $data['easemob_uuid']     = $easemob_info['entities'][0]['uuid'];
            $data['easemob_username'] = $account;
            $data['easemob_password'] = $password;

            $add = db("member")->insertGetId($data);
            if ($add) {
                db('member')->where(['id' => $add])->setField('m_invitation_code', 'SHIJ' . sprintf("%05d", $add));
                db()->commit();
                $this->ajaxSuccess('注册成功');
            } else {
                db()->rollback();
                $this->ajaxError("注册失败");
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 普通注册
     */
    public function register()
    {
        $account = input("account");//账号
        $code    = input("code");//验证码
        if (empty($account)) {
            $this->ajaxError("请输入手机号");
        }
        if (empty($code)) $this->ajaxError("请输入验证码");
        $password  = input("password");//密码
        $password1 = input("password1");//确认密码

        $member=db('member')
            ->where(['m_account'=>$account])
            ->find();
        if($member){
            $this->ajaxError('该账号已注册');
        }
        if (empty($password)) {
            $this->ajaxError("请输入密码");
        }
        if (strlen($password) < 6 || !isNumAndLetter($password)) {
            $this->ajaxError("请输入至少6位数字+字母的密码");
        }
        if (empty($password1)) {
            $this->ajaxError("请输入确认密码");
        }
        if ($password != $password1) {
            $this->ajaxError("两次密码输入不一致");
        }
        $invitation       = input('invitation');
        $isopenInvitation = getSettings('isopenInvitation', 'isopenInvitation');
        if ($isopenInvitation == 1) {
            if (empty($invitation)) {
                $this->ajaxError('邀请码不能为空');
            }
        }
        if ($invitation) {
            $parent = db('member')->where(['m_invitation_code' => trim($invitation)])->find();
            if (empty($parent)) {
                $this->ajaxError('输入的邀请码有误');
            }
            $data['m_fatherId']        = $parent['id'];
            $data['m_grandpaId']       = $parent['m_fatherId'];
            $data['m_grate_grandpaId'] = $parent['m_grandpaId'];
            if (empty($parent["m_pathtree"])) {
                $data["m_pathtree"] = ",{$parent["id"]},";
            } else {
                $data["m_pathtree"] = "{$parent["m_pathtree"]}{$parent["id"]},";
            }
        }
        db()->startTrans();
        $qt_sms = new SendSms();
        $res = $qt_sms->check($account, $code, 'register');
        if ($res["code"] == 0) {
            db()->rollback();
            $this->ajaxError($res["message"]);
        }

        $morenimg                  = getSettings('morenimg', 'morenimg');//用户默认头像
//        $paypassword               = getSettings('paypassword', 'paypassword');    //默认支付密码
        $data["m_account"]         = $account;  //账号
        $data["m_thumb"]           = $morenimg;//头像
        $data["m_nickname"]        = $account; //昵称
        $data["m_password"]        = password_hash($password, PASSWORD_DEFAULT);//密码
//        $data["m_password"]        = password_hash($paypassword, PASSWORD_DEFAULT);//支付密码
        $data["m_isDisable"]       = 2; //未注销
        $data["m_isDelete"]        = 2;//未删除
        $data["m_createTime"]      = now_datetime();  //注册时间
        $data["m_updateTime"]      = now_datetime();//修改时间
        $data["m_invitation_code"] = '';//邀请码
        $options['client_id']      = $this->client_id;//环信Client ID
        $options['client_secret']  = $this->client_secret;//环信Client Secret:
        $options['org_name']       = $this->org_name;//环信Orgname
        $options['app_name']       = $this->app_name;//appname
        $easemob                   = new Easemob($options);//实例化环信接口类
        $easemob_info              = $easemob->createUser($account, $password);//注册环信用户
        //如果存在用户删除该用户在创建
        if (isset($easemob_info['error']) && $easemob_info['error'] == 'duplicate_unique_property_exists') {
            $easemob->deleteUser($account);
            $easemob_info = $easemob->createUser($account, $password);
        }
        $data['easemob_uuid']     = $easemob_info['entities'][0]['uuid'];//环信UUID
        $data['easemob_username'] = $account; //环信账号
        $data['easemob_password'] = $password;//环信密码
        $add                      = db("member")->insertGetId($data);
        if ($add) {
            db('member')->where(['id' => $add])->setField('m_invitation_code', 'SHIJ' . sprintf("%05d", $add));
            db()->commit();
            $this->ajaxSuccess('注册成功');
        } else {
            db()->rollback();
            $this->ajaxError("注册失败");
        }
    }

    /**
     * 忘记密码
     */
    public function forget()
    {
        //账号
        $account = input("account");
        if (empty($account)) {
            $this->ajaxError("请输入手机号");
        }
        //验证码
        $code = input("code");
        if (empty($code)) {
            $this->ajaxError('请输入验证码');
        }
        //密码
        $password = input("password");
        //确认密码
        $password1 = input("password1");
        if (empty($password)) {
            $this->ajaxError("请输入密码");
        }
        if (strlen($password) < 6 || !isNumAndLetter($password)) {
            $this->ajaxError("请输入至少6位数字+字母的密码");
        }
        if (empty($password1)) {
            $this->ajaxError("请输入确认密码");
        }
        if ($password != $password1) {
            $this->ajaxError("两次密码输入不一致");
        }
        $qt_sms = new SendSms();
        $res    = $qt_sms->check($account, $code, 'forget');
        if ($res["code"] == 0) {
            db()->rollback();
            $this->ajaxError($res["message"]);
        }
        $update = db("member")
            ->where("m_account", $account)
            ->update(array("m_password" => password_hash($password, PASSWORD_DEFAULT)));
        if ($update) {
            db()->commit();
            $this->ajaxSuccess("修改成功");
        } else {
            db()->rollback();
            $this->ajaxError("您输入的密码与原密码一致");
        }
    }

    /**
     * 退出登录
     *
     */
    public function logout()
    {
        $token = input("token");
        $this->remove($token);
        $this->ajaxSuccess("退出登录成功");
    }

    /**
     * 统一接口
     */
    public function userAgreement()
    {
        $code = trim(input('code'));
        $data = getSettings($code, $code);
        if ($code == 'applogourl' || $code == 'minilogo') {
            $data = saver() . $data;
        }
        if ($code == "tuijianbiaoqian") {
            $data = explode('|', $data);
        }
        if($code=='shouyi'||$code=='user_agreement'||$code=='quanguo_rexian'||$code=='jifenguize')
        {
            $data="<meta name=\"viewport\"
          content=\"width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no\"/>".$data;
        }
        $this->ajaxSuccess('Success', $data);
    }

    public function edition(){
        $data=db('Edition')
            ->where(['id'=>1])
            ->find();
        $data['time']=strtotime($data['updateTime']);
        $this->ajaxSuccess('success',$data);
    }
}