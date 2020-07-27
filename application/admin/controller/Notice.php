<?php
namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;
use push\Push;

class Notice extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $modelName  = 'Notice';  //模型名,用于add和update方法
    protected $indexField = ['id','title','content','createTime','updateTime','type','introduction'];  //查，字段名
    protected $addField   = ['id','title','content','createTime','updateTime','type','introduction'];    //增，字段名
    protected $editField  = ['id','title','content','createTime','updateTime','type','introduction'];   //改，字段名
    /**
     * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
     * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
     * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
     * @var array
     */
    protected $cache = false; //是否开启缓存查询，仅对后台查询生效，通过模型方式进行增，改，删的操作，都会刷新缓存
    protected $searchField = ['title'];
    protected $orderField = '';  //排序字段
    protected $pageLimit   = 10;               //分页数
    protected $addTransaction = false;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction = false;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = false;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑
    
    //增，数据检测规则
    protected $add_rule = [
        //'nickName|昵称'  => 'require|max:25'
        'title|标题' => 'require',
        'content|内容' => 'require',
        'introduction|简介' => 'require',

    ];
    //改，数据检测规则
    protected $edit_rule = [
        //'nickName|昵称'  => 'require|max:25'
        'title|标题' => 'require',
        'content|内容' => 'require',
        'introduction|简介' => 'require',
    ];

    /**
     * 新增数据插入数据库前数据捕获（注意：在数据验证之前）
     * @param $data
     * @return mixed
     */
    public function addData($data){
        $data["createTime"]=now_datetime();
        return $data;
    }

    public function addEnd($id, $data)
    {
        loadNoticeList();
    }

    /**
     * 编辑数据插入数据库前数据捕获（注意：在数据验证之前）
     * @param $data
     * @return mixed
     */
    public function editData($data)
    {
        $data["updateTime"]=now_datetime();
        return $data;
    }
    public function editEnd($id, $data)
    {
        loadNoticeList();
    }

    /**
     * 公告详情
     * @author: Gavin
     * @time: 2019/9/21 9:35
     */
    public function content_detail()
    {
        $id = input('id');
        $content = db('notice')->where(['id' => $id])->value('content');
        $this->assign('content',$content);
        return view();
    }
    public function send(){
        if(request()->isPost()){
            $id=input('id');
            $title=input('title');
            $message=input('message');
            //极光推送
            $push=new Push();
            //notice类型为活动消息  id是当前订单的id
            $extras=['type'=>'notice','id'=>$id];
            //发送通知
            $push->send_to_all($message,$title,$extras);

            send_message($title,$message,3,2,0,$id);

            return json_suc();

        }else{
            $id=input('id');
            $data=db('notice')->where(['id'=>$id])->find();
            $this->assign('data',$data);
            $this->assign('id',$id);
            return view();
        }
    }
}