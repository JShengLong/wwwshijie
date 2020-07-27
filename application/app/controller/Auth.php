<?php
/**
 * Created by PhpStorm.
 * Auth: Administrator
 * Date: 2019-05-11
 * Time: 16:53
 */

namespace app\app\controller;


use HXC\App\Common;
use think\Controller;
use HXC\App\Auth as AuthTrait;

class Auth extends Controller
{
    use Common,AuthTrait;

    protected $username = 'name';//登录用户名，可选定user中的任意字段

    //登录方法login

    //注册方法register

    //找回密码resetPassword

}