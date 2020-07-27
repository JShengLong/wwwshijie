<?php
namespace app\admin\controller;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/13
 * Time: 15:23
 */
class Edition extends Right
{
    public function index(){
        if(request()->isPost()){
            $data=$this->request->param();
            unset($data["/admin/Edition/index"]);
            $res=db('Edition')->where(['id'=>1])
                ->update($data);
            if($res===false){
                return json_err();
            }
            return json_suc();
        }else{
            $data=db('Edition')
                ->where(['id'=>1])
                ->find();
            $this->assign('lists',['force'=>[1=>'否',2=>'是']]);
            $this->assign('data',$data);
            return view();
        }

    }
}