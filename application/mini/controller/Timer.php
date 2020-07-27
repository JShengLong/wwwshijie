<?php
namespace app\mini\controller;
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
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function unpaid(){
        ignore_user_abort(true);
        set_time_limit(0);
        $date=now_datetime();
        $where['o_unpaid']=['lt',$date];
        $where['o_status']=1;
        $list=db('onlineorder')->where($where)->select();
        $num=0;
        foreach ($list as $key=>$value){
            db('onlineorder')->where(['o_id'=>$value['o_id']])->setField('o_status',6);
            $orderDetail=db('orderdetails')
                ->where(['d_orderId'=>$value['o_id']])
                ->select();
            foreach ($orderDetail as $k=>$v){
                db('fa_item_sku')->where(['sku_id'=>$v['d_sku_id']])->setInc('stock',$v['d_num']);
            }
            $num++;
        }
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
        $where['o_status']=3;
        $list=db('onlineorder')->where($where)->select();
        $num=0;
        foreach ($list as $key=>$value){
            db('onlineorder')->where(['o_id'=>$value['o_id']])->setField('o_status',5);
            $orderDetail=db('orderdetails')
                ->where(['d_orderId'=>$value['o_id']])
                ->select();
            $member=db('member')->where(['id'=>$value['o_mid']])->find();
            foreach ($orderDetail as $k=>$v){ $num++;
                $add=[
                    'comment'=>'此用户没有填写评价',
                    'star'=>5,
                    'createtime'=>now_datetime(),
                    'product_id'=>$v['d_productId'],
                    'account'=>$member['m_account'],
                    'order_sn'=>$value['o_sn'],
                    'is_show'=>1,
                    'sku'=>$v['d_sku'],
                    'num'=>$v['d_num'],
                ];
                db('comment')->insert($add);
            }

            $parent = db('member')->where(['id' => $member['m_fatherId']])->find();
            if ($parent && ($value['o_actual_payment'] - $value['o_freight']) > 0) {
                $integral = getSettings('integrals', 'integrals');
                integralLog($parent['id'], ($value['o_actual_payment'] - $value['o_freight']) * $integral, 1, 1, '下级购买商品', $member['id'], $value['o_id']);

            }

        }
        echo "完成未收货的订单：".$num."；";
    }
}