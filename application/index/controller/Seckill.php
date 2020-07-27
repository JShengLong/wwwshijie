<?php

namespace app\index\controller;

use Redis\RedisPackage;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/7
 * Time: 9:26
 */
class Seckill extends Signin
{
    private $goods_id;
    private $user_queue_key;
    private $goods_number_key;
    private $user_id;

    public function _initialize()
    {
        parent::_initialize();
        $goods_id = input("goods_id", '0', 'intval');
        if ($goods_id) {
            $this->goods_id         = $goods_id;
            $this->user_queue_key   = "goods_" . $goods_id . "_user";//当前商品队列的用户情况
            $this->goods_number_key = "goods" . $goods_id;//当前商品的库存队列
        }
        $this->user_id = $this->user_id ? $this->user_id : $this->uid;
    }

    /**
     * redis连接
     * @access private
     * @return resource
     * @author bieanju
     */
    private function connectRedis()
    {
        $redis = new RedisPackage();
        return $redis;
    }

    /**
     * 访问产品前先将当前产品库存队列
     * @access public
     * @author bieanju
     */
    public function before_detail()
    {

        $where['goods_id']   = $this->goods_id;
        $where['start_time'] = array("lt", now_datetime());
        $where['end_time']   = array("gt", now_datetime());
        $goods               = db("goods")
            ->where($where)
            ->field('goods_num,order_num,start_time,end_time')
            ->find();

        if (!$goods) {
            $this->ajaxError("当前秒杀已结束！");
        }
        if ($goods['goods_num'] > $goods['order_num']) {
            $redis        = $this->connectRedis();
            $getUserRedis = $redis->hGetAll("{$this->user_queue_key}");
            $gnRedis      = $redis->llen("{$this->goods_number_key}");
            /* 如果没有会员进来队列库存 */
            if (!count($getUserRedis) && !$gnRedis) {
                for ($i = 0; $i < $goods['goods_num']; $i++) {
                    $redis->lpush("{$this->goods_number_key}", 1);
                }
            }

            $resetRedis = $redis->llen("{$this->goods_number_key}");

            if (!$resetRedis) {
                $this->ajaxError("系统繁忙，请稍后抢购！");
            }
            $this->ajaxSuccess('成功');
        } else {
            $this->ajaxError("当前产品已经秒杀完！");
        }

    }

    /**
     * 抢购商品前处理当前会员是否进入队列
     * @access public
     * @author bieanju
     */
    public function goods_number_queue()
    {
        $model             = db("goods");
        $where['goods_id'] = $this->goods_id;
        $goods_info        = $model->where($where)->find();
        !$goods_info && $this->ajaxError("对不起当前商品不存在或已下架！");
        /* redis 队列 */
        $redis = $this->connectRedis();
        /* 进入队列 */
        $goods_number_key = $redis->llen("{$this->goods_number_key}");
        if (!$redis->hGet("{$this->user_queue_key}", $this->user_id)) {
            $goods_number_key = $redis->lpop("{$this->goods_number_key}");
        }

        if ($goods_number_key) {
            // 判断用户是否已在队列
            if (!$redis->hGet("{$this->user_queue_key}", $this->user_id)) {
                // 插入抢购用户信息
                $userinfo = array(
                    "user_id"     => $this->user_id,
                    "create_time" => time()
                );
                $redis->hSet("{$this->user_queue_key}", $this->user_id, serialize($userinfo));
                $this->ajaxReturn(array("status" => "1"));
            } else {
                $modelCart              = db("cart");
                $condition['user_id']   = $this->user_id;
                $condition['goods_id']  = $this->goods_id;
                $condition['prom_type'] = 1;
                $cartlist               = $modelCart->where($condition)->count();
                if ($cartlist > 0) {
                    $this->ajaxReturn(array("status" => "2"));
                } else {

                    $this->ajaxReturn(array("status" => "1"));

                }

            }

        } else {
            $this->ajaxReturn(array("status" => "-1", "msg" => "系统繁忙,请重试！"));
        }
    }

    public function clearRedis()
    {
        set_time_limit(0);
        $redis = $this->connectRedis();
        //$Rd = $redis->del("{$this->user_queue_key}");
        $Rd = $redis->hDel("goods49", '用户id');
        $a  = $redis->hGet("goods_49_user", '用户id');
        if (!$a) {
            dump($a);
        }
        if ($Rd == 0) {
            exit("Redis队列已释放！");
        }
    }
}