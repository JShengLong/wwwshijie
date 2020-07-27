<?php

namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;
use push\Push;
use wxpay\Wxpay;
use alipay\Alipay;

class Refund extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $modelName = 'Refund';  //模型名,用于add和update方法
    protected $indexField = [];  //查，字段名
    protected $addField = ['id', 'orderId', 'oid', 'status', 'type', 'total', 'mid', 'info', 'certificate', 'createtime', 'refundReview', 'shopTime', 'endTime', 'sn', 'snone', 'name', 'phone', 'regionid', 'address', 'expressid', 'rephone'];    //增，字段名
    protected $editField = ['id', 'orderId', 'oid', 'status', 'type', 'total', 'mid', 'info', 'certificate', 'createtime', 'refundReview', 'shopTime', 'endTime', 'sn', 'snone', 'name', 'phone', 'regionid', 'address', 'expressid', 'rephone'];   //改，字段名
    /**
     * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
     * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
     * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
     * @var array
     */
    protected $cache = false; //是否开启缓存查询，仅对后台查询生效，通过模型方式进行增，改，删的操作，都会刷新缓存
    protected $searchField = ['sn'];
    protected $orderField = 'id desc';  //排序字段
    protected $pageLimit = 10;               //分页数
    protected $addTransaction = false;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction = false;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
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


    /**
     * 列表查询sql捕获
     * @param $sql
     * @return mixed
     */
    public function indexQuery($sql)
    {
        $sql = $sql->alias('r')
                   ->join('orderdetails o', 'o.d_id=r.oid', 'left')
                   ->join('member m', 'm.id = r.mid', 'left')
                   ->field('r.*,m.m_account,m.m_nickname,o.*');
        return $sql;
    }

    /**
     * 输出到列表视图的数据捕获
     * @param $data
     * @return mixed
     */
    public function indexAssign($data)
    {
        $data['lists'] = [
            'status'       => getDropdownList('refundStatus'),
            'refundReview' => getDropdownList('refundReview'),
            'refundType'   => getDropdownList('refundType'),
        ];
        return $data;
    }

    /**
     * 确认退款
     *
     * @author: Gavin
     * @time: 2019/9/7 11:01
     */
    public function return_check()
    {
        if (request()->isPost()) {
            $id     = input('post.id');
            $refund = db("refund")->where(["id" => $id])->find();
            if (empty($refund)) {
                return json_err("-1", "退款订单不存在");
            }
            $total = input('total', $refund['total']);
            db('refund')->where(["id" => $id])->setField('total', $total);
            $refund = db("refund")->where(["id" => $id])->find();

            $order  = db("onlineorder")->where(["o_id" => $refund["order_id"]])->find();

            if (empty($order)) {
                return json_err(-1, "商品订单不存在或者未付款");
            }
            $wx_pay     = getSettings('wx_pay');//微信支付所有的配置
            $ali_pay    = getSettings('ali_pay');//支付宝的所有配置
            $ali_config = [
                'app_id'         => $ali_pay['app_id'],//APPID
                'ali_public_key' => $ali_pay['ali_public_key'],//支付宝公钥
                'private_key'    => $ali_pay['private_key'],//支付宝私钥
            ];
            db()->startTrans();
            if ($refund["status"] == 1) {
                $save = [
                    "refundReview" => 2,
                    "shopTime"     => now_datetime(),
                ];
                $res1 = db("refund")->where(["id" => $id])->update($save);
                if ($res1 == false) {
                    db()->rollback();
                    return json_err(-1, '更新订单状态失败');
                }
            }
            if ($refund['total'] > 0) {
                switch ($order['o_payType']) {
                    case 1://微信退款
                        $wechat_refund = new Wxpay();
                        if ($order['trade_type'] == 'app') {
                            $wechat_refund->appid = $wx_pay['appid'];
                        } else {
                            $wechat_refund->appid = $wx_pay['miniapp_id'];
                        }
                        $wechat_refund->mch_id            = $wx_pay['mch_id'];
                        $wechat_refund->key               = $wx_pay['key'];
                        $wechat_refund->notify_refund_url = saver() . '/index/pay/refundNotify';
                        if($order['trade_type']=='jsapi'){
                            $order['o_sn']=$order['o_sn'].'mini';
                        }else{
                            $order['o_sn']=$order['o_sn'].'app';
                        }
                        $refund_data                      = [
                            'out_trade_no'  => $order['o_sn'],
                            'out_refund_no' => $refund["sn"],
                            'total_fee'     => $order['o_actual_payment'],
                            'refund_fee'    => $refund['total'],
                        ];
                        $response                         = $wechat_refund->refund($refund_data);
                        if ($response == false) {
                            db()->rollback();
                            return json_err(-1, '退款失败！');
                        }
                        break;
                    case 2://支付宝退款
                        $aliRefund = new Alipay($ali_config);
                        $response  = $aliRefund->alipayRefund($order["o_sn"].'ali', $refund["sn"], $refund["total"]);
                        if ($response) {
                            $re = $this->refundNotify($id, 2);
                            if ($re == false) {
                                db()->rollback();
                                return json_err();
                            }
                        } else {
                            db()->rollback();
                            return json_err();
                        }
                        break;
                    case 3://余额退款
                        $res = balanceLog($refund['mid'], $refund['total'], 1, 3, '商品退款', $refund['id']);
                        if ($res == false) {
                            db()->rollback();
                            return json_err(-1, '钱包退款失败');
                        }
                        $re = $this->refundNotify($id, 3);
                        if ($re == false) {
                            db()->rollback();
                            return json_err(-1, '回调失败');
                        }
                        break;
                }

            }
            if ($refund['integral'] > 0) {
                $res = integralLog($refund['mid'], $refund['integral'], 1, 3, '商品退款', '', $id);
                if ($res == false) {
                    db()->rollback();
                    return json_err();
                }
                if ($refund['total'] == 0) {
                    $re = $this->refundNotify($id, '');
                    if ($re == false) {
                        db()->rollback();
                        return json_err(-1, '回调失败');
                    }
                }
            }
            db()->commit();
            return json_suc();
        } else {
            $id     = input('id');
            $refund = db("refund")->where(["id" => $id])->find();
            $this->assign('data', $refund);
            return view();
        }
    }

    /**
     * 退款同步回调
     * @param $id
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function refundNotify($id, $refund_type)
    {
        if (empty($id)) {
            return false;
        }
        // 检索订单
        $refund = db("refund")->where(array("id" => $id))->find();
        if (empty($refund)) {
            return false;
        }
        $orderdetail=db('orderdetails')->where(['d_id'=>$refund['oid']])->find();
        db('product')->where(['id'=>$orderdetail['d_productId']])->setDec('p_sales',$orderdetail['d_num']);
        db('fa_item_sku')->where(['sku_id'=>$orderdetail['d_sku_id']])->setInc('stock',$orderdetail['d_num']);
        $data1["refundReview"] = 3;
        $data1["endTime"]      = now_datetime();
        $data1["refund_type"]  = $refund_type;
        $res1                  = db("refund")->where(array("id" => $id))->update($data1);
        if (!$res1) {
            return false;
        }
        $res2 = db("orderdetails")->where(["d_id" => $refund["oid"]])->update(["d_refund" => 3]);

        if ($res2 === false) {
            return false;
        }
        $where1 = 1;
        $where1 .= ' AND d_orderId = ' . $refund['order_id'];
        $where2 = 1;
        $where2 .= ' AND d_orderId = ' . $refund['order_id'] . ' and d_refund=3';
        $list1  = db('orderdetails')
            ->where($where1)
            ->count();
        $list2  = db('orderdetails')
            ->where($where2)
            ->count();
        if ($list1 == $list2) {
            db('onlineorder')->where(['o_id' => $refund['order_id']])->update(['o_status' => 7]);
        }
        //消息模版
        $message_template = messageTemplate(1);
        //用户信息
        $member = db('member')->where(['id' => $refund['mid']])->find();
        //极光推送
        $push = new Push();
        //send_order类型为发货  id是当前订单的id
        $extras = ['type' => 'refund', 'id' => $id];
        //发送通知
        $push->send_to_one($member['m_account'], $message_template['alert'], $message_template['title'], $extras);
        //站内消息
        send_message($message_template['title'], $message_template['alert'], 2, 1, $member['id'], $id);
        return true;
    }

    /**
     * @Notes:商家确认退货申请
     * @Author:jsl
     * @Date: 2019/9/16
     * @Time: 9:12
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function send()
    {
        if (request()->isPost()) {
            $id      = input('post.id');
            $name    = input('post.name');
            $phone   = input('post.phone');
            $address = input('post.address');
            $refund  = db("refund")->where(["id" => $id])->find();
            if (empty($refund)) {
                return json_err("-1", "退款订单不存在");
            }
            $save = [
                "refundReview" => 2,
                "shopTime"     => now_datetime(),
                'name'         => $name,
                'phone'        => $phone,
                'address'      => $address,
            ];
            $res  = db("refund")->where(["id" => $id])->update($save);
            if ($res == false) {
                //消息模版
                $message_template = messageTemplate(1);
                //用户信息
                $member = db('member')->where(['id' => $refund['mid']])->find();
                //极光推送
                $push = new Push();
                //send_order类型为发货  id是当前订单的id
                $extras = ['type' => 'refund', 'id' => $id];
                //发送通知
                $push->send_to_one($member['m_account'], $message_template['alert'], $message_template['title'], $extras);

                //站内消息
                send_message($message_template['title'], $message_template['alert'], 2, 1, $member['id'], $id);

                return json_err();
            }
            return json_suc();
        } else {
            $id              = input('id');
            $refund          = db('refund')->order('id desc')->find();
            $data['name']    = $refund['name'] ? '' : $refund['name'];
            $data['phone']   = $refund['phone'] ? '' : $refund['phone'];
            $data['address'] = $refund['address'] ? '' : $refund['address'];
            $this->assign('data', $data);
            $this->assign('id', $id);
            return view();
        }
    }

    /**
     * @Notes:驳回退款申请
     * @Author:jsl
     * @Date: 2019/9/16
     * @Time: 9:15
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function reject()
    {
        if (request()->isPost()) {
            $id     = input('post.id');
            $info   = input('post.info');
            $refund = db("refund")->where(["id" => $id])->find();
            if (empty($refund)) {
                return json_err("-1", "退款订单不存在");
            }
            $save = [
                "refundReview" => 4,
                "shopTime"     => now_datetime(),
                "reject_info"  => $info,
            ];
            db()->startTrans();
            $res = db("refund")->where(["id" => $id])->update($save);
            if ($res === false) {
                db()->rollback();
                return json_err();
            }
            $res2 = db("orderdetails")->where(array("d_id" => $refund["oid"]))->update(array("d_refund" => 4));
            if ($res2 === false) {
                db()->rollback();
                return json_err();
            }
            //消息模版
            $message_template = messageTemplate(1);
            //用户信息
            $member = db('member')->where(['id' => $refund['mid']])->find();
            //极光推送
            $push = new Push();
            //send_order类型为发货  id是当前订单的id
            $extras = ['type' => 'refund', 'id' => $id];
            //发送通知
            $push->send_to_one($member['m_account'], $message_template['alert'], $message_template['title'], $extras);

            //站内消息
            send_message($message_template['title'], $message_template['alert'], 2, 1, $member['id'], $id);

            db()->commit();
            return json_suc();
        } else {
            $id = input('id');
            $this->assign('id', $id);
            return view();
        }
    }

    /**
     * @Notes:  退款详细信息
     * @Author: 红星闪闪丶放光芒
     * @Date: 2019\9\21 0021 10:49
     */
    public function info()
    {
        $id = input('id');
        if (empty($id)) {
            return json_err(-1, 'id不能为空');
        }
        $info        = db($this->modelName)->where(['id' => $id])->find();
        $info['img'] = explode(',', $info['certificate']);
        if ($info) {
            $this->assign('data', $info);
            return $this->fetch();
        } else {
            return json_err(-1, "未查询到信息");
        }
    }

    /**
     * 查看物流
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function logistics(){
        $id=input('id');
        $data=db('refund')
            ->where(['id'=>$id])
            ->find();
        $list=json_decode($data['express'],true);
        $this->assign('list',$list);
        return view();
    }
}