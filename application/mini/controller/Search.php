<?php
namespace  app\mini\controller;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/15
 * Time: 9:12
 */
use think\Cache;
class Search extends Base
{
    /**
     * 搜索历史
     */
    public function historySearch()
    {
        if(!$this->isLogin()){
            $this->ajaxSuccess('',[]);
        }
        $list = db('history_search')
            ->where(['mid' => $this->mid])
            ->order('time desc')
            ->page(1, 10)
            ->select();
        $this->ajaxSuccess('success', $list);
    }
}