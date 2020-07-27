<?php
namespace app\admin\controller;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/5
 * Time: 11:37
 */
class Extension extends Right
{
    public function index(){
        $list=db('Extension')->where(['id'=>1])->find();
        $this->assign('list',$list);
        return $this->fetch();
    }
    public function edit(){
        $name=input('name');
        if(empty($name)){
            return json_err(-1,'请输入名称');
        }
        $code=input('code');
        if(empty($code)){
            return json_err(-1,'请上传二维码');
        }
        $img=input('img');
        if(empty($name)){
            return json_err(-1,'请上传背景');
        }
        $data['code']=$code;
        $data['img']=$img;
        $data['name']=$name;
        $data['time']=now_datetime();
        $res=db('Extension')->where(['id'=>1])->update($data);
        if($res==false){
            return json_err();
        }
        return json_suc();
    }
}