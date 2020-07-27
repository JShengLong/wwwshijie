<?php

namespace app\mini\controller;

use think\Cache;

class Signin extends Base
{
    protected $uid;

    Public function _initialize()
    {
        parent::_initialize();
        $account = $this->getAccount();
        if (empty($account)) {
            $this->ajaxLoginError("您的登录已经失效，请重新登录");
        } else {
            $this->uid = $account["id"];
        }
    }

    // 获取账号信息
    function getAccount()
    {
        $token = input("token");
        if (empty($token)) {
            $this->ajaxLoginError("请登录");
        }
        $id     = Cache::get($token)["id"];
        $member = db("member")
            ->where(array("id" => $id, "m_isDisable" => 2))
            ->find();
        if ($member) {
            return $member;
        } else {
            Cache::rm($token);
            return null;
        }
    }

    // 获取账号ID
    function getAccountId()
    {
        return $this->getAccount()['id'];
    }

}
