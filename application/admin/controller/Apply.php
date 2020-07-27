<?php
namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;

class Apply extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $modelName  = 'Apply';  //模型名,用于add和update方法
    protected $indexField = [];  //查，字段名
    protected $addField   = [];    //增，字段名
    protected $editField  = [];   //改，字段名
    /**
     * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
     * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
     * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
     * @var array
     */
    protected $cache = false; //是否开启缓存查询，仅对后台查询生效，通过模型方式进行增，改，删的操作，都会刷新缓存
    protected $searchField = ['name','phone','memberid','m_account'];
    protected $orderField = 'id desc';  //排序字段
    protected $pageLimit   = 10;               //分页数
    protected $addTransaction = false;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction = false;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = false;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑
    
    //增，数据检测规则
    protected $add_rule = [
        //'nickName|昵称'  => 'require|max:25'

    ];
    //改，数据检测规则
    protected $edit_rule = [
        //'nickName|昵称'  => 'require|max:25'

    ];
    public function indexQuery($sql)
    {
        return $sql->alias('t')
            ->join('member m','m.id=t.memberid','left')
            ->field('t.*,m.m_account');
    }

    public function change_status(){
        $id=input('id');
        $apply=db('Apply')->where('id',$id)->where('status',1)->find();
        if(empty($apply)){
            return json_err(-1,'该申请已通过或者不存在');
        }
        $aa=db('agency')->where(['regionId'=>$apply['regionId']])->find();
        if($aa){
            return json_err(-1,'该区域已存在代理');
        }
        $member=db('member')->where(['id'=>$apply['memberid']])->find();
        db()->startTrans();
        db('member')->where(['id'=>$apply['memberid']])->setField('m_level',2);
        db('region')->where(['id'=>$apply['regionId']])->setField('isdaili',2);
        $data=[
            'account'=>$member['m_account'],
            'regionId'=>$apply['regionId'],
            'createTime'=>now_datetime(),
            'updateTime'=>now_datetime(),
        ];
        $res=db('agency')->insert($data);
        if($res==false){
            db()->rollback();
            return json_err();
        }
        $res1=db('Apply')->where('id',$id)->where('status',1)->setField('status',2);
        if($res1==false){
            db()->rollback();
            return json_err();
        }
        db()->commit();
        return json_suc();
    }
    public function change_status1(){
        $id=input('id');
        $apply=db('Apply')->where('id',$id)->where('status',1)->find();
        if(empty($apply)){
            return json_err(-1,'该申请已驳回或者不存在');
        }
        $res1=db('Apply')->where('id',$id)->where('status',1)->setField('status',2);
        if($res1==false){
            return json_err();
        }
        return json_suc();
    }
}