<?php

namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;
use push\Push;
use think\Session;

class Recharge extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $modelName  = 'Recharge';  //模型名,用于add和update方法
    protected $indexField = [];  //查，字段名
    protected $addField   = [];    //增，字段名
    protected $editField  = [];   //改，字段名
    /**
     * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
     * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
     * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
     * @var array
     */
    protected $cache             = false; //是否开启缓存查询，仅对后台查询生效，通过模型方式进行增，改，删的操作，都会刷新缓存
    protected $searchField       = ['type', 'status','id',['createTime1'=>['createTime','time_start']],['createTime2'=>['createTime','time_end']],['account'=>['m.m_account','relation']],['nickname'=>['m.m_nickname','relation']]];
    protected $orderField        = 'id desc';  //排序字段
    protected $pageLimit         = 10;        //分页数
    protected $addTransaction    = false;     //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction   = false;     //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = false;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑

    //增，数据检测规则
    protected $add_rule
        = [
            //'nickName|昵称'  => 'require|max:25'

        ];
    //改，数据检测规则
    protected $edit_rule
                     = [
            //'nickName|昵称'  => 'require|max:25'

        ];
    protected $type  = ['' => '', 1 => '扫码充值', 2 => '银行卡充值'];
    protected $stats = ['' => '', 1 => '待审核', 2 => '审核通过', 3 => '审核驳回'];

    public function indexAssign($data)
    {
        $data['lists'] = [
            'type'   => $this->type,
            'status' => $this->stats,
        ];
        return $data;
    }

    public function indexQuery($sql)
    {
        return $sql->alias('t')
            ->join('member m','m.id=t.mid')
            ->field('t.*,m.m_nickname,m.m_account');

    }

//    public function pageEach($item, $key)
//    {
//        $item->m_account = db('member')->where(['id' => $item->mid])->value('m_account');
//        return $item;
//    }

    /**
     * 修改指定字段
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author: Gavin
     * @time: 2019/9/19 16:52
     */
    public function changeStatus()
    {
        if (request()->isPost()) {
            $data        = input('post.');
            $id          = $data['id'][0];
            $field       = $data['id'][1];
            $val         = $data['id'][2];
            $map[$field] = $val;
            $recharge    = db($this->modelName)->where(['id' => $id])->find();
            db()->startTrans();
            $res = db($this->modelName)->where(['id' => $id])->update($map);
            if ($res === false) {
                db()->rollback();
                return json_err();
            }
            $re = balanceLog($recharge['mid'], $recharge['total'], 1, 1, '充值', $id);
            if ($re === false) {
                db()->rollback();
                return json_err();
            }
            db()->commit();
            return json_suc();
        }
    }

    public function changeStatus1()
    {
        if (request()->isPost()) {
            $id          = input('id');
            $total       = input('total');
            $paypassword = input('paypassword');
            $uid         = Session::get('uid', 'admin');
            $admin       = db('admin')
                ->where(['id' => $uid])
                ->find();
            if (empty($total) || $total <= 0) {
                return json_err(-1, '请输入正确的金额');
            }
            if (empty($paypassword)) {
                return json_err(-1, '请输入支付密码');
            }
            if (!password_verify($paypassword, $admin['paypassword'])) {
                return json_err(-1, '支付密码错误');
            }
            $recharge = db($this->modelName)->where(['id' => $id])->find();
            db()->startTrans();
            $res = db($this->modelName)->where(['id' => $id])->update(['status' => 2, 'updateTime' => now_datetime(), 'total' => $total]);
            if ($res === false) {
                db()->rollback();
                return json_err();
            }
            $re = balanceLog($recharge['mid'], $total, 1, 1, '充值', $id);
            if ($re === false) {
                db()->rollback();
                return json_err();
            }
            //消息模版
            $message_template = messageTemplate(4);
            //用户信息
            $member = db('member')->where(['id' => $recharge['mid']])->find();
            //极光推送
            $push = new Push();
            //send_order类型为发货  id是当前订单的id
            $extras = ['type' => 'recharge', 'id' => $id];
            //发送通知
            $push->send_to_one($member['m_account'], $message_template['alert'], $message_template['title'], $extras);

            //站内消息
            send_message($message_template['title'], $message_template['alert'], 4, 1, $member['id'], $id);
            db()->commit();
            return json_suc();
        } else {
            $id = input('id');
            $this->assign('id', $id);
            return view();
        }
    }

    public function changeStatus2()
    {
        if (request()->isPost()) {
            $id   = input('id');
            $info = input('info');
            $res
                  = db($this->modelName)->where(['id' => $id])->update(['status' => 3, 'updateTime' => now_datetime(), 'info' => $info]);
            if ($res === false) {
                return json_err();
            }
            $recharge = db($this->modelName)->where(['id' => $id])->find();
            //消息模版
            $message_template = messageTemplate(3);
            //用户信息
            $member = db('member')->where(['id' => $recharge['mid']])->find();
            //极光推送
            $push = new Push();
            //send_order类型为发货  id是当前订单的id
            $extras = ['type' => 'recharge', 'id' => $id];
            //发送通知
            $push->send_to_one($member['m_account'], $message_template['alert'], $message_template['title'], $extras);

            //站内消息
            send_message($message_template['title'], $message_template['alert'], 4, 1, $member['id'], $id);
            return json_suc();
        } else {
            $id = input('id');
            $this->assign('id', $id);
            return view();
        }
    }
}