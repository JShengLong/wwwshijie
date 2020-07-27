<?php
/**
 * Created by PhpStorm.
 * Auth: Administrator
 * Date: 2019-04-18
 * Time: 17:07
 */

namespace app\app\controller\v1;


use think\Controller;

class TestController extends Controller
{
    public function index($id = '空')
    {
        return "当前API V1的路由，id:{$id}";
    }
}