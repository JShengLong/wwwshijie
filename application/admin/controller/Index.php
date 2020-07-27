<?php

namespace app\admin\controller;

use function GuzzleHttp\Psr7\build_query;
use think\Cache;
use Redis\RedisPackage;

class Index extends Signin
{

    public function index()
    {
//        halt($this->getMenu());
        $this->assign("roleList", $this->getMenu());
        return $this->fetch();
    }

    public function main()
    {
//        $member=db('member')->where(['id'=>42])->find();
////        $redis=new RedisPackage();
////        $redis::set('token',serialize($member));
////        die;
//        echo db('member')->fetchSql(true)->count();die;
        $list=db('statistics')->order('stort asc')->select();
        foreach ($list as $key=>$value){
            $num=db()->query($value['sql']);
            $list[$key]['num']=$num[0]['tp_count'];
        }
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 清除所有缓存（数据缓存、模板缓存）
     */
    public function clearCacheData()
    {
        // 清除数据缓存
        Cache::clear();
        // 清除模板缓存
        clear_temp_cache();
        // 重新加载缓存
        cacheSettings();

        $this->success("缓存信息已刷新！");
    }
}