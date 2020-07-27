<?php
/**
 * Created by PhpStorm.
 * Auth: Administrator
 * Date: 2019-05-11
 * Time: 16:54
 */

namespace app\app\model;


use think\Model;

class User extends Model
{
    protected $hidden = ['password'];

    // 自动维护时间戳
    protected $autoWriteTimestamp = true;

    public function setPasswordAttr($val)
    {
        return password_hash($val,PASSWORD_DEFAULT);
    }
}