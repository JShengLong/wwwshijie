<?php
namespace app\index\controller;
/**
 * Created by PhpStorm.
 * Date: 2019/9/20
 * Time: 14:39
 * 定时任务 每写一个方法在Worker.php里面调用一次
 */
class Timer extends Base
{
    /**
     * 取消未支付的订单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function unpaid(){
//        ignore_user_abort(true);
//        set_time_limit(0);
//        $date=now_datetime();
//        $where['o_unpaid']=['lt',$date];
//        $where['o_status']=1;
//        $list=db('onlineorder')->where($where)->select();
        $num=0;
//        foreach ($list as $key=>$value){
//            db('onlineorder')->where(['o_id'=>$value['o_id']])->setField('o_status',4);
//            $orderDetail=db('orderdetails')
//                ->where(['d_orderId'=>$value['o_id']])
//                ->find();
//            db('product')->where(['id'=>$orderDetail['d_productId']])->setInc('p_stock',$orderDetail['d_num']);
//            $num++;
//        }
        echo $num;
    }

    /**
     * 完成未收货的订单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function unreceivedGoods(){
        ignore_user_abort(true);
        set_time_limit(0);
        $date=now_datetime();
        $where['o_unreceived_goods']=['lt',$date];
        $where['o_status']=2;
        $list=db('onlineorder')->where($where)->select();
        $num=0;
        foreach ($list as $key=>$value){
            db('onlineorder')->where(['o_id'=>$value['o_id']])->setField('o_status',5);
            db('member')->where(['m_account'=>$value['o_agency']])->setField('m_isfrone',1);
            $num++;
        }
        echo "完成未收货的订单：".$num."；";
    }
    public function Orders()
    {
        $type = input("coname");
        header("location:http://{$_SERVER['HTTP_HOST']}/#/orderDetails?id={$type}");
    }
}