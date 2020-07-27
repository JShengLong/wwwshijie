<?php

namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;

class Setting extends Right implements curdInterface
{
    use Common, curd;

    protected $cache             = false;
    protected $modelName         = 'Setting';  //模型名,用于add和update方法
    protected $orderField        = '';
    protected $indexField        = ['id', 'module', 'code', 'val', 'name'];  //查，字段名
    protected $addField          = ['module', 'code', 'val', 'name'];    //增，字段名
    protected $editField         = ['module', 'code', 'val', 'name'];   //改，字段名
    protected $searchField       = ['name'];//条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age','productId' => 'p.name'],解释：参数名为productId,关联表字段p.name
    protected $pageLimit         = 20;               //分页数
    protected $addTransaction    = false;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction   = false;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = false;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑

    //增，数据检测规则
    protected $add_rule = [
    ];
    //改，数据检测规则
    protected $edit_rule = [
    ];


    /**
     * 清除配置缓存
     */
    public function clearCache()
    {
        cacheSettings();
        $this->success("数据缓存已清除！");
    }
}