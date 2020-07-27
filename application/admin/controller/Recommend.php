<?php
namespace app\admin\controller;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/13
 * Time: 11:35
 */
use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;
use think\Session;
use think\Validate;
use think\Request;

class Recommend extends Right implements curdInterface
{
    use curd, Common;
    protected $modelName  = 'Member';  //模型名,用于add和update方法
    protected $indexField = [];  //查，字段名
    protected $addField   = ['id','m_nickname','m_account','m_password','m_createTime','m_updateTime','m_fatherId','m_grandpaId','m_grate_grandpaId','m_pathtree','m_total','m_isDisable','m_isDelete','m_thumb','m_level','m_invitation_code','m_read_message','m_teamTurnOver','m_oneself','m_lev','m_regionId','teamNum'];    //增，字段名
    protected $editField  = ['id','m_nickname','m_account','m_password','m_createTime','m_updateTime','m_fatherId','m_grandpaId','m_grate_grandpaId','m_pathtree','m_total','m_isDisable','m_isDelete','m_thumb','m_level','m_invitation_code','m_read_message','m_teamTurnOver','m_oneself','m_lev','m_regionId','teamNum'];   //改，字段名
    protected $cache = false; //是否开启缓存查询，仅对后台查询生效，通过模型方式进行增，改，删的操作，都会刷新缓存
    protected $searchField = ['m_nickname'];
    protected $orderField = 'id desc';  //排序字段
    protected $pageLimit   = 10;               //分页数
    protected $addTransaction = false;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction = false;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = false;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑


    public function indexQuery($sql){
        $where=' m_level = 2 ';
        return $sql->where($where);
    }
    public function pageEach($item, $key)
    {
        $where=' 1 ';
        // 注册时间
        if (input('post.createtimeStart') || input('post.createtimeEnd')) {
            // 起始日期
            if (input('post.createtimeStart')) {
                $where .= " AND m_createTime>='" . input("post.createtimeStart") . "'";
            }
            // 结束日期
            if (input('post.createtimeEnd')) {
                $where .= " AND m_createTime<='" . input("post.createtimeEnd") . " 23:59:59'";
            }
        }
        $member=db('member')->where($where)->where(['m_fatherId'=>$item->id])->select();

        $item->member_num=$member?count($member):0;
        $total_num=0;
        $int_num=0;
        foreach ($member as $key=>$value){

            $order=db('onlineorder')->where(['o_mid'=>$value['id']])->where('o_status=4 or o_status=5')->sum('o_ptotal');

            $total_num=$total_num+$order;

            $int_num=$int_num+$value['m_integral_num'];
        }
        $item->total_num=number_format($total_num,2);
        $item->int_num=number_format($int_num,2);
        return $item;

//        $total_num=db('online')
    }
    public function indexAssign($data)
    {
        $data['params']['createtimeStart']=input('post.createtimeStart');
        $data['params']['createtimeEnd']=input('post.createtimeEnd');
        return $data;
    }

    /**
     * 客户列表
     * @return \think\response\View
     * @throws \think\Exception
     */
    public function member_list(){
        $id=input('id');
        $where='m_fatherId='.$id;
        if(input('m_isbuy')){
            $where.=' and m_isbuy='.input('m_isbuy');
        }
        $list=db('member')
            ->where($where)
            ->order('id desc')
            ->paginate(10)->each(function ($item, $key) {
                $shifu=db('onlineorder')->where(['o_mid'=>$item['id']])->where('o_status=4 or o_status=5')->sum('o_ptotal');
//                $yunfei=db('onlineorder')->where(['o_mid'=>$item['id']])->where('o_status=4 or o_status=5')->sum('o_freight');
                $item['total_num']=$shifu;
                return $item;
            });
        $pagelist=$list->render();
        $countFiled=db('member')
            ->where($where)
            ->count();
//        dump($list->toArray()['data']);dise;
        $data = [
            'list' => $list,
            'pagelist' => $pagelist,
            'countField' => $countFiled,
            'id'=>$id,
            'params'=>['m_isbuy'=>input('m_isbuy')]
        ];
        $this->assign($data);
        $this->assign('lists',[
                'm_isbuy'=>[''=>'',1=>'未采购',2=>'已采购']
            ]);
        return view();
    }

    /**
     * 客户订单列表
     * @return \think\response\View
     * @throws \think\Exception
     */
    public function order_list(){
        $id=input('id');
        $where='o_mid='.$id .' and (o_status=4 or o_status=5)';
        $list=db('onlineorder')
            ->where($where)
            ->order('o_id desc')
            ->paginate(10);
        $pagelist=$list->render();
        $countFiled=db('onlineorder')
            ->where($where)
            ->count();
        $data = [
            'list' => $list,
            'pagelist' => $pagelist,
            'countField' => $countFiled,
            'id'=>$id,
        ];
        $this->assign($data);
        return view();
    }
}