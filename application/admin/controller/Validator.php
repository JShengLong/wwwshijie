<?php
/**
 * 验证器类
 * 所有自定义的验证规则放到这里来
 * Auth: Sh@dow
 * Date: 2019/2/22
 */
namespace app\admin\controller;

use think\Validate;

class Validator extends Validate
{
    /**
     * 匹配手机号
     */
    protected function isMobile($account){
        return isPhone($account) ? true : '账号格式错误（请填写手机号）！';
    }
}
