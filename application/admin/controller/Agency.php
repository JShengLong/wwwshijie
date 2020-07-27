<?php
namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;
use think\Session;

class Agency extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $modelName  = 'Agency';  //模型名,用于add和update方法
    protected $indexField = [];  //查，字段名
    protected $addField   = ['account','regionId'];    //增，字段名
    protected $editField  = ['account','regionId'];   //改，字段名
    /**
     * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
     * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
     * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
     * @var array
     */
    protected $cache = false; //是否开启缓存查询，仅对后台查询生效，通过模型方式进行增，改，删的操作，都会刷新缓存
    protected $searchField = ['account'];
    protected $orderField = 'id desc';  //排序字段
    protected $pageLimit   = 10;               //分页数
    protected $addTransaction = true;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction = true;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = true;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑
    
    //增，数据检测规则
    protected $add_rule = [
        //'nickName|昵称'  => 'require|max:25'
        'account|用户账号' => 'require',
        'regionId|区域id' => 'require',

    ];
    //改，数据检测规则
    protected $edit_rule = [
        //'nickName|昵称'  => 'require|max:25'
        'account|用户账号' => 'require',
        'regionId|区域id' => 'require',

    ];
    public function indexQuery($sql)
    {
        return $sql->alias('t')
            ->join('region r','r.id=t.regionId','left')
            ->field('t.*,r.name_path');
    }

    public function addAssign($data)
    {
        $this->regionId(0, $lists, $data1);
        $data=[
            'lists'=>$lists,
            'data'=>$data1
        ];
        return $data;
    }
    public function editAssign($data)
    {
        $list=db('Agency')->where(['id'=>$data['id']])->find();
        $this->regionId($list['regionId'], $lists, $data1);
//        dump($lists);die;
        $data=[
            'lists'=>$lists,
            'data'=>$data1,
            'list'=>$list
        ];
        Session::set('region_edit',$list['regionId']);
        return $data;
    }
    public function editData($data)
    {
        $data1=request()->param();
        if(empty($data1['account'])){
            return json_err(-1,'请输入用户账号');
        }
        if(empty($data1['areaId'])){
            return json_err(-1,'请选择地区');
        }
        $member=db('member')->where('m_account',$data1['account'])->find();
        if(empty($member)){
            return json_err(-1,'该用户不存在');
        }
//        $agency=db('Agency')->where(['regionId'=>$data1['areaId']])->find();
//        if($agency){
//            return json_err(-1,'该区域已经存在代理');
//        }
        $data['account']=$data1['account'];
        $data['regionId']=$data1['areaId'];
        $data['updateTime']=now_datetime();
        return $data;
    }
    public function editEnd($id, $data)
    {
        $agency=db('Agency')->where(['id'=>$id])->find();
        db('member')->where(['m_account'=>$agency['account']])->setField('m_level',2);
        db('region')->where(['id'=>$agency['regionId']])->setField('isdaili',2);
        $re_id=Session::get('region_edit');
        db('region')->where(['id'=>$re_id])->setField('isdaili','');

    }

    public function addData($data)
    {
        $data1=request()->param();
        if(empty($data1['account'])){
           return json_err(-1,'请输入用户账号');
        }
        if(empty($data1['areaId'])){
           return json_err(-1,'请选择地区');
        }
        $member=db('member')->where('m_account',$data1['account'])->find();
        if(empty($member)){
            return json_err(-1,'该用户不存在');
        }
        $agency=db('Agency')->where(['regionId'=>$data1['areaId']])->find();
        if($agency){
            return json_err(-1,'该区域已经存在代理');
        }
        $data['account']=$data1['account'];
        $data['regionId']=$data1['areaId'];
        $data['updateTime']=now_datetime();
        $data['createTime']=now_datetime();
        return $data;
    }
    public function addEnd($id, $data)
    {
        $agency=db('Agency')->where(['id'=>$id])->find();
        db('member')->where(['m_account'=>$agency['account']])->setField('m_level',2);
        db('region')->where(['id'=>$agency['regionId']])->setField('isdaili',2);
    }
    public function deleteData($data)
    {
        $agency=db('Agency')->where(['account'=>$data['account']])->find();
        if(empty($agency)){
           db('member')->where(['m_account'=>$data['account']])->setField('m_level',1);
        }
        db('region')->where(['id'=>$data['regionId']])->setField('isdaili','');
        db('memberaddress')->where(['account'=>$data['account']])->setField(['isDelete'=>2]);
    }

}