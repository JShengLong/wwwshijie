<?php
namespace app\mini\controller;
use think\Request;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/14
 * Time: 15:01
 */
class Message extends Base
{
    public function index(Request $request){
        if ($request->isPost()) {
            if(!$this->isLogin()){
                $this->ajaxSuccess('',[]);
            }
            $page = input('page', 1);
            $list = db('message')
                ->where(['to'=>$this->mid])
                ->whereOr(['category'=>2])
//                ->where(' to='.$this->mid.' OR category=2 ')
                ->page($page, 10)
                ->order('id desc')
                ->select();
            $this->ajaxSuccess('success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }
    public function index1(Request $request){
        if ($request->isPost()) {
            if(!$this->isLogin()){
                $this->ajaxSuccess('',[]);
            }
            $page = input('page', 1);
            $list = db('message')
                ->where(['category' => 1,'to' => $this->mid])
                ->page($page, 10)
                ->order('id desc')
                ->select();
            $this->ajaxSuccess('success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }
    public function index2(Request $request){
        if ($request->isPost()) {
            if(!$this->isLogin()){
                $this->ajaxSuccess('',[]);
            }
            $page = input('page', 1);
            $list = db('message')
                ->where(['category' => 2])
                ->page($page, 10)
                ->order('id desc')
                ->select();
            $this->ajaxSuccess('success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }
    public function countNum(){
        if(!$this->isLogin()){
            $this->ajaxSuccess('',0);
        }
        $mess=db('message')
            ->where(['to'=>$this->mid,'category'=>1,'is_read'=>2])
            ->count();
        $mess1=db('message')
            ->where(['category'=>2])
            ->select();
        $num=0;
        foreach ($mess1 as $key=>$value){
            $m=db('membermessages')
                ->where(['message_id'=>$value['id'],'member_id'=>$this->mid])
                ->find();
            if(empty($m)){
                $num++;
            }
        }
        $this->ajaxSuccess('success',$mess+$num);
    }
}