<?php
namespace app\mini\controller;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/20
 * Time: 17:32
 */
class Fenci extends Base
{
    public function index(){
        ignore_user_abort(true);
        set_time_limit(0);
       $url="https://restapi.amap.com/v3/config/district?keywords=湖北省&subdistrict=2&key=055e8665165910d15cfe00f8640980e3";
       $data=$this->httpGet($url);
       $data=json_decode($data,true);
//        $this->ajaxSuccess('1',$data);
       foreach ($data['districts'] as $key=>$value){
           $add=[
               'id'=>$value['adcode'],
               'parent_id'=>0,
               'name'=>$value['name'],
               'id_path'=>$value['adcode'],
               'name_path'=>$value['name'],
           ];
           db('region_copy1')->insert($add);
           foreach ($value['districts'] as $k=>$v){
               $add1=[
                   'id'=>$v['adcode'],
                   'parent_id'=>$value['adcode'],
                   'name'=>$v['name'],
                   'id_path'=>$value['adcode'].','.$v['adcode'],
                   'name_path'=>$value['name'].'-'.$v['name'],
               ];
               db('region_copy1')->insert($add1);
               foreach ($v['districts'] as $kk=>$vv){
                   $add2=[
                       'id'=>$vv['adcode'],
                       'parent_id'=>$v['adcode'],
                       'name'=>$vv['name'],
                       'id_path'=>$value['adcode'].','.$v['adcode'].','.$vv['adcode'],
                       'name_path'=>$value['name'].'-'.$v['name'].'-'.$vv['name'],
                   ];
                   db('region_copy1')->insert($add2);
               }
           }
       }

    }
    public function aa(){
        $this->send_to_one('15554257813','您于2020-03-25 13:37:02获得分享赚13.6元','分享赚',['q'=>1]);
    }

}