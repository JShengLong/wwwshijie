<?php
namespace app\index\controller;
use think\Request;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/14
 * Time: 15:01
 */
class Message extends Signin
{
    /**
     * 消息列表
     * @param Request $request
     */
    public function index(Request $request){
        if ($request->isPost()) {
            $page = input('page', 1);
            $list = db('message')
                ->where(['to'=>$this->uid])
                ->whereOr(['category'=>2])
                ->page($page, 10)
                ->order('id desc')
                ->select();
            $this->ajaxSuccess('success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 订单消息列表
     * @param Request $request
     */
    public function index1(Request $request){
        if ($request->isPost()) {
            $page = input('page', 1);
            $list = db('message')
                ->where(['category' => 1,'to' => $this->uid])
                ->page($page, 10)
                ->order('id desc')
                ->select();
            $this->ajaxSuccess('success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 系统消息列表
     * @param Request $request
     */
    public function index2(Request $request){
        if ($request->isPost()) {
            $page = input('page', 1);
            $list = db('message')
                ->where(['category' => 2])
                ->page($page, 10)
                ->order('id desc')
                ->select();
            $this->ajaxSuccess('success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }
    /**
     * 未读数量
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function countNum(){
        $mess=db('message')
            ->where(['to'=>$this->uid,'category'=>1,'is_read'=>2])
            ->count();
        $mess1=db('message')
            ->where(['category'=>2])
            ->select();
        $num=0;
        foreach ($mess1 as $key=>$value){
            $m=db('membermessages')
                ->where(['message_id'=>$value['id']])
                ->find();
            if(empty($m)){
                $num++;
            }
        }
        $this->ajaxSuccess('success',$mess+$num);
    }
    /**
     * 清空未读数量
     * @param Request $request
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function numberOfRefreshes(Request $request)
    {
        if ($request->isPost()) {
            db()->startTrans();
            $res = db('message')
                ->where(['to' => $this->uid, 'is_read' => 2])
                ->update(['is_read' => 1]);
            if ($res === false) {
                db()->rollback();
            }
            $list = db('message')
                ->where(['category' => 2])
                ->select();
            foreach ($list as $key => $value) {
                $mess = db('membermessages')
                    ->where(['member_id' => $this->uid, 'message_id' => $value['id']])
                    ->find();
                if (empty($mess)) {
                    $add = [
                        'member_id'  => $this->uid,
                        'message_id' => $value['id'],
                    ];
                    db('membermessages')->insert($add);
                }
            }
            db()->commit();
            $this->ajaxSuccess('success',1);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }
}