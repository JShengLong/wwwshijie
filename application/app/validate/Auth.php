<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-05-13
 * Time: 13:56
 */

namespace app\app\validate;


use think\Validate;

class Auth extends Validate
{
    protected $rule = [
        'password'  =>  'require',
        'old_password' => 'require',
        'username' => 'require'
    ];

    protected $message = [
        'password.require'  =>  '密码不能为空',
        'old_password.require'  =>  '旧密码不能为空',
        'username.require'  =>  '用户名不能为空',
    ];

    protected $scene = [
        'edit' => ['password'],
        'login' => ['username','password'],
        'register' => ['username','password'],
        'resetPassword' => ['password','old_password']
    ];
}