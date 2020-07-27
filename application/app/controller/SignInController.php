<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-05-13
 * Time: 11:12
 */

namespace app\app\controller;


use HXC\App\Common;
 use HXC\App\Auth;
use think\Cache;
use think\Controller;
use think\Request;
use think\Session;

class SignInController extends Controller
{
    use Common;

    public function _initialize()
    {
        $this->check();
    }

    /**
     * 是否登录
     */
    protected function check()
    {
        $request = Request::instance();
        $token = $request->param('token');
        if($token){
            $res = Cache::get($token)?true:false;
            if(!$res){
                die($this->notLogin());
            }
        }else{
            $res = Session::get('data','auth')?true:false;
            if(!$res){
                $this->error('没有登录');
            }
        }
        return $res;
    }
}