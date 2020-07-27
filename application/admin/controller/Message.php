<?php
namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;

class Message extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $modelName  = 'Message';  //模型名,用于add和update方法
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
    protected $searchField = [];
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

    protected $type=[''=>'',1=>'发货通知',2=>'退款通知',3=>'图文消息',4=>'充值消息'];
    protected $category=[''=>'',1=>'系统消息',2=>'推送消息'];

    public function indexAssign($data)
    {

        $data['params']['m_account'] = trim(input('post.m_account'));
        $data['params']['m_nickname'] = trim(input('post.m_nickname'));
        $data['params']['title'] = trim(input('post.title'));
        $data['params']['type'] = trim(input('post.type'));
        $data['params']['category'] = trim(input('post.category'));
        $data['params']['send_timeStart'] = trim(input('post.send_timeStart'));
        $data['params']['send_timeEnd'] = trim(input('post.send_timeEnd'));
        $data['lists']=[
            'type'=>$this->type,
            'category'=>$this->category,
        ];
        return $data;
    }
    public function indexQuery($sql)
    {
        $where=' 1 ';

        if (trim(input('post.m_account'))) {
            // 精确
            if (input('post.m_accountCondition') == 1) {
                $where .= " AND m.m_account='" . trim(input('post.m_account')) . "'";
            } else {
                $where .= " AND m.m_account LIKE '%" . trim(input('post.m_account')) . "%'";
            }
        }
        if (trim(input('post.m_nickname'))) {
            // 精确
            if (input('post.m_nicknameCondition') == 1) {
                $where .= " AND m.m_nickname='" . trim(input('post.m_nickname')) . "'";
            } else {
                $where .= " AND m.m_nickname LIKE '%" . trim(input('post.m_nickname')) . "%'";
            }
        }
        if (trim(input('post.title'))) {
            // 精确
            if (input('post.titleCondition') == 1) {
                $where .= " AND t.title='" . trim(input('post.title')) . "'";
            } else {
                $where .= " AND t.title LIKE '%" . trim(input('post.title')) . "%'";
            }
        }

        if (input('post.type')) {
            $where .= " AND t.type='" . input("post.type") . "'";
        }
        if (input('post.category')) {
            $where .= " AND t.category='" . input("post.category") . "'";
        }

        // 发送时间
        if (input('post.send_timeStart') || input('post.send_timeEnd')) {
            // 起始日期
            if (input('post.send_timeStart')) {
                $where .= " AND t.send_time>='" . input("post.send_timeStart") . "'";
            }
            // 结束日期
            if (input('post.send_timeEnd')) {
                $where .= " AND t.send_time<='" . input("post.send_timeEnd") . " 23:59:59'";
            }
        }

        return $sql->alias('t')
            ->join('member m','m.id=t.to','left')
            ->where($where)
            ->field('t.*,m.m_account,m.m_nickname');
    }

}