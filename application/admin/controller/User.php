<?php

namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;

class User extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $cache = false;
    protected $modelName = 'User';  //模型名,用于add和update方法
    protected $indexField = ['id', 'name', 'password', 'create_time'];  //查，字段名
    protected $addField = ['id', 'name', 'password'];    //增，字段名
    protected $editField = ['id', 'name', 'password'];   //改，字段名
    /**
     * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
     * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
     * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
     * @var array
     */
    protected $searchField = ['name'];
    protected $orderField = 'create_time desc';  //排序字段
    protected $pageLimit = 10;               //分页数
    protected $addTransaction = false;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction = false;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = false;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑

    //增，数据检测规则
    protected $add_rule = [
        //'nickName|昵称'  => 'require|max:25'
        'name|用户名' => 'require',
        'password|密码' => 'require',

    ];
    //改，数据检测规则
    protected $edit_rule = [
        //'nickName|昵称'  => 'require|max:25'
        'name|用户名' => 'require',
    ];

    /**
     * 输出到编辑视图的数据捕获
     * @param $data
     * @return mixed
     */
    public function editAssign($data)
    {
        unset($data['data']['password']);
        return $data;
    }

    /**
     * 编辑 验证前处理
     * @param $data
     * @return mixed
     */
    public function editData($data)
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }
        return $data;
    }
}