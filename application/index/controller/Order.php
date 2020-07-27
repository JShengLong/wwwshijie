<?php


namespace app\index\controller;


use think\Exception;
use think\exception\ErrorException;
use think\Request;


class Order extends Signin
{
    /**
     * 北京地区配送方式
     * @var array
     */
    protected $peisong_type1
        = [
            ['id' => 1, 'type' => '平台配送', 'is_check' => 1],
            ['id' => 2, 'type' => '站点自提', 'is_check' => 0],
            ['id' => 3, 'type' => '快递发货', 'is_check' => 0],
        ];
    /**
     * 非北京地区配送方式
     * @var array
     */
    protected $peisong_type2
        = [
            ['id' => 2, 'type' => '站点自提', 'is_check' => 1],
            ['id' => 3, 'type' => '快递发货', 'is_check' => 0],
            ['id' => 4, 'type' => '物流配送', 'is_check' => 0],
        ];

    /**
     * 站点自提-库房地址
     */
    public function storageRoom()
    {
        $data = getSettings('storage_room');
        $this->ajaxSuccess('success', $data);
    }


    /**
     * 直接购买-订单提交页面
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderDetail($params = [])
    {

        //商品id
        $p_id    = $params ? $params['p_id'] : input('p_id');
        $num     = $params ? $params['num'] : input('num');
        $sku_id  = $params ? $params['sku_id'] : input('sku_id');
        $product = db('product')
            ->where(['id' => $p_id, 'p_isDelete' => 2, 'p_isUp' => 2])
            ->find();
        $sku     = db('fa_item_sku')->where(['sku_id' => $sku_id])->find();
        if ($num > $sku['stock']) {
            $this->ajaxError('库存不足');
        }
        if (empty($product)) {
            $this->ajaxError('商品已删除或者已下架');
        }
        //默认收货地址
        $return['address'] = db('memberaddress')
            ->where(['memberId' => $this->uid, 'isDefault' => 1, 'isDelete' => 1])
            ->find();
        //根据地区选择配送方式
        if ($return['address']) {
            $name_path = explode('-', $return['address']['name_path']);
            //北京地区
            if ($name_path[0] == '北京市') {
                $return['is_beijing']   = 2;
                $return['peisong_type'] = $this->peisong_type1;
            } else {
                //非北京地区
                $return['is_beijing']   = 1;
                $return['peisong_type'] = $this->peisong_type2;
            }
        } else {
            //没有默认地址为空
            $return['is_beijing']   = 1;
            $return['peisong_type'] = [];
        }

        //商品id
        $products[0]['p_id'] = $p_id;
        //商品skuid
        $products[0]['sku_id'] = $sku_id;
        //商品sku
        $products[0]['sku'] = $this->sku($sku_id);
        //商品名称
        $products[0]['p_name'] = $product['p_name'];
        //商品图片
        $products[0]['p_img'] = $params ? $product['p_img'] : saver() . $product['p_img'];
        //商品数量
        $products[0]['p_num'] = $num;
        //商品单价
        $products[0]['p_price'] = bcmul($sku['price'], 1, 2);
        //商品总价
        $products[0]['p_price_num'] = bcmul($sku['price'], $num, 2);
        //商品阶梯价格
        $products[0]['p_price_ladder'] = $this->ladder($sku_id, $num);
        //商品重量
        $products[0]['p_weight'] = bcmul($sku['original_price'], $num, 2);
        //商品
        $return['product'] = $products;
        //满减运费金额
        $freight_num           = getSettings('freight_num', 'freight_num');
        $return['freight_num'] = $freight_num;
        //订单总数
        $return['num'] = $num;
        //订单总金额
        if ($products[0]['p_price_ladder'] > 0) {
            $return['price'] = $products[0]['p_price_ladder']*$num;
        } else {
            $return['price'] = bcmul($sku['price'], $num, 2);
        }
        //订单重量
        $return['weight'] = bcmul($sku['original_price'], $num, 2);
        $freight_min      = getSettings('freight_min', 'freight_min');
        //运费计算
        if ($return['is_beijing'] == 2) {
            //大于5000免运费
            if ($return['price'] >= $freight_num) {
                //是北京地区免运费
                $return['freight'] = 0;
                //剩余多少免运费
                $return['freight_ex']                = 0;
                $return['product'][0]['freight_tpl'] = 0;
            } else {
                //常温和冷藏需要运费
                if ($product['p_storage_mode'] == 1 || $product['p_storage_mode'] == 2) {
                    $freight_tpl                         = getSettings('freight_tpl', 'one');
                    $return['product'][0]['freight_tpl'] = $freight_tpl;
                    $return['product'][0]['freight']     = bcmul($products[0]['p_weight'], $freight_tpl, 2);
                    $return['freight']                   = bcmul($products[0]['p_weight'], $freight_tpl, 2);
                    if ($return['freight'] < $freight_min) {
                        $return['freight'] = $freight_min;
                    }
                } else {
                    //冷冻需要运费
                    $freight_tpl                         = getSettings('freight_tpl', 'two');
                    $return['product'][0]['freight_tpl'] = $freight_tpl;
                    $return['product'][0]['freight']     = bcmul($products[0]['p_weight'], $freight_tpl, 2);
                    $return['freight']                   = bcmul($products[0]['p_weight'], $freight_tpl, 2);
                    if ($return['freight'] < $freight_min) {
                        $return['freight'] = $freight_min;
                    }
                }
                //剩余多少免运费
                $return['freight_ex'] = bcsub($freight_num, $return['price'], 2);
            }
        } else {
            $return['freight_ex'] = number_format(0, 2);
            //常温和冷藏需要运费
            if ($product['p_storage_mode'] == 1 || $product['p_storage_mode'] == 2) {
                $freight_tpl                         = getSettings('freight_tpl', 'one');
                $return['product'][0]['freight_tpl'] = $freight_tpl;
                $return['product'][0]['freight']     = bcmul($products[0]['p_weight'], $freight_tpl, 2);
                $return['freight']                   = bcmul($products[0]['p_weight'], $freight_tpl, 2);
                if ($return['freight'] < $freight_min) {
                    $return['freight'] = $freight_min;
                }
            } else {
                //冷冻需要运费
                $freight_tpl                         = getSettings('freight_tpl', 'two');
                $return['product'][0]['freight_tpl'] = $freight_tpl;
                $return['product'][0]['freight']     = bcmul($products[0]['p_weight'], $freight_tpl, 2);
                $return['freight']                   = bcmul($products[0]['p_weight'], $freight_tpl, 2);
                if ($return['freight'] < $freight_min) {
                    $return['freight'] = $freight_min;
                }

            }

        }
        //积分折扣前的价格
        $return['totals'] = bcadd($return['price'], $return['freight'], 2);
        //用户信息
        $member = db('member')->where(['id' => $this->uid])->find();
        //用户当前积分
        $return['integral'] = $member['m_integral'];
        //积分和金额的比例
        $integral_bili = getSettings('integral', 'integral');
        //用户积分可抵扣金额
        $integral_total            = $member['m_integral'] / $integral_bili;
        $return['integral_total '] = $member['m_integral'] / $integral_bili;
        if ($return['integral'] > 0) {
            if ($integral_total > $return['price']) {
                $return['integral_sur'] = bcmul(bcsub($integral_total, $return['price'], 2), $integral_bili, 2);
                //积分抵扣之后剩余积分
                $return['integral_sur'] = bcmul(bcsub($integral_total, $return['price'], 2), $integral_bili, 2);
                //全部抵扣
                $return['integral_type'] = 1;
                $return['total']         = 0;
                $return['q_total']       = $return['price'];
                $return['s_integral']    = $return['price'] * $integral_bili;
            } else {
                $return['integral_sur'] = 0;
                //抵扣一部分
                $return['integral_type'] = 2;
                $return['total']         = $return['totals'] - $integral_total;
                $return['q_total']       = $integral_total;
                $return['s_integral']    = $integral_total * $integral_bili;
            }
        } else {
            $return['integral_sur'] = 0;
            //积分为零不能抵扣
            $return['integral_type'] = 3;
            $return['total']         = $return['totals'];
            $return['q_total']       = 0;
            $return['s_integral']    = 0;
        }
        $return['type']         = 'product';
        $storage_room           = getSettings('storage_room');
        $return['storage_room'] = $storage_room;
        if ($params) {
            return $return;
        } else {
            $this->ajaxSuccess('success', $return);
        }
    }

    /**
     * 直接购买-订单提交页面
     * @param string $c_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cartDetail($c_ids = '')
    {

        //购物车id
        $c_id = $c_ids ? $c_ids : input('c_id');
        if (empty($c_id)) {
            $this->ajaxError('请选择商品');
        }
        $product_list = db('cart')
            ->alias('t')
            ->join('product p', 't.c_pid=p.id')
            ->join('fa_item_sku f', 'f.sku_id=c_sku_id')
            ->where('t.c_id', 'in', $c_id)
            ->field('t.*,f.price,f.stock,f.original_price,p.p_name,p.p_img,p.p_storage_mode')
            ->order('t.c_id desc')
            ->select();
        //默认收货地址
        $return['address'] = db('memberaddress')
            ->where(['memberId' => $this->uid, 'isDefault' => 1, 'isDelete' => 1])
            ->find();
        //根据地区选择配送方式
        if ($return['address']) {
            $name_path = explode('-', $return['address']['name_path']);
            //北京地区
            if ($name_path[0] == '北京市') {
                $return['is_beijing']   = 2;
                $return['peisong_type'] = $this->peisong_type1;
            } else {
                //非北京地区
                $return['is_beijing']   = 1;
                $return['peisong_type'] = $this->peisong_type2;
            }
        } else {
            //没有默认地址为空
            $return['is_beijing']   = 1;
            $return['peisong_type'] = [];
        }
        $products = [];
        $num      = 0;
        $price    = 0;
        $weight   = 0;
        $freight  = 0;
        foreach ($product_list as $key => $value) {
            //商品skuid
            $products[$key]['sku_id'] = $value['c_sku_id'];
            //商品sku
            $products[$key]['sku'] = $this->sku($value['c_sku_id']);
            //商品id
            $products[$key]['p_id'] = $value['c_pid'];
            //商品名称
            $products[$key]['p_name'] = $value['p_name'];
            //商品图片
            $products[$key]['p_img'] = $c_ids ? $value['p_img'] : saver() . $value['p_img'];
            //商品数量
            $products[$key]['p_num'] = $value['c_num'];
            //商品单价
            $products[$key]['p_price'] = bcmul($value['price'], 1, 2);
            //商品总价
            $products[$key]['p_price_num'] = bcmul($value['price'],$value['c_num'],2);
            //阶梯价格
            $products[$key]['p_price_ladder'] = $this->ladder($value['c_sku_id'], $value['c_num']);
            //如果此商品的阶梯价格不为0就在总价上面加上阶梯价格
            if ($products[$key]['p_price_ladder'] > 0) {
                $price = $price + ($products[$key]['p_price_ladder']*$value['c_num']);
            } else {
                //如果此商品的阶梯价格为0就在总价上面加上商品原价
                $price = $price + bcmul($value['price'], $value['c_num'], 2);
            }
            //此商品重量
            $products[$key]['p_weight'] = bcmul($value['original_price'], $value['c_num'], 2);
            //订单商品总数量
            $num = $num + $value['c_num'];
            //订单商品总重量
            $weight = $weight + bcmul($value['original_price'], $value['c_num'], 2);
            //如果储存方式为常温和冷藏求运费模版
            if ($value['p_storage_mode'] == 1 || $value['p_storage_mode'] == 2) {
                //运费模版
                $freight_tpl = getSettings('freight_tpl', 'one');
                //运费模版
                $products[$key]['freight_tpl'] = $freight_tpl;
                //运费
                $products[$key]['freight'] = bcmul($products[$key]['p_weight'], $freight_tpl, 2);
                //总运费
                $freight = $freight + $products[$key]['freight'];
            } else {
                //冷冻需要运费
                $freight_tpl = getSettings('freight_tpl', 'two');
                //运费模版
                $products[$key]['freight_tpl'] = $freight_tpl;
                //运费
                $products[$key]['freight'] = bcmul($products[$key]['p_weight'], $freight_tpl, 2);
                //总运费
                $freight = $freight + $products[$key]['freight'];

            }

        }
        //商品详情
        $return['product'] = $products;
        //满减运费金额
        $freight_num           = getSettings('freight_num', 'freight_num');
        $return['freight_num'] = $freight_num;
        //订单总数
        $return['num'] = $num;
        //订单总金额
        $return['price'] = $price;
        //订单重量
        $return['weight'] = $weight;
        $freight_min      = getSettings('freight_min', 'freight_min');
        //运费计算
        if ($return['is_beijing'] == 2) {
            //大于5000免运费
            if ($return['price'] >= $freight_num) {
                //是北京地区免运费
                $return['freight'] = 0;
                //剩余多少免运费
                $return['freight_ex'] = 0;
            } else {
                //常温和冷藏需要运费
                $return['freight'] = $freight;
                //剩余多少免运费
                $return['freight_ex'] = bcsub($freight_num, $return['price'], 2);
                if ($return['freight'] < $freight_min) {
                    $return['freight'] = $freight_min;
                }
            }
        } else {
            $return['freight_ex'] = number_format(0, 2);
            //常温和冷藏需要运费
            $return['freight'] = $freight;
            if ($return['freight'] < $freight_min) {
                $return['freight'] = $freight_min;
            }

        }
        //应付款
        $return['totals'] = bcadd($return['price'], $return['freight'], 2);
        //用户信息
        $member = db('member')->where(['id' => $this->uid])->find();
        //用户当前积分
        $return['integral'] = $member['m_integral'];
        //积分和金额的比例
        $integral_bili = getSettings('integral', 'integral');
        //用户积分可抵扣金额
        $integral_total            = bcdiv($member['m_integral'], $integral_bili, 2);
        $return['integral_total '] = bcdiv($member['m_integral'], $integral_bili, 2);
        if ($return['integral'] > 0) {
            if ($integral_total > $return['price']) {
                //积分抵扣之后剩余积分
                $return['integral_sur'] = bcmul(bcsub($integral_total, $return['price'], 2), $integral_bili, 2);
                //全部抵扣
                $return['integral_type'] = 1;
                $return['total']         = 0;
                $return['q_total']       = $return['price'];
                $return['s_integral']    = $return['price'] * $integral_bili;
            } else {
                $return['integral_sur'] = 0;
                //抵扣一部分
                $return['integral_type'] = 2;
                $return['total']         = $return['totals'] - $integral_total;
                $return['q_total']       = $integral_total;
                $return['s_integral']    = $integral_total * $integral_bili;
            }
        } else {
            $return['integral_sur'] = 0;
            //积分为零不能抵扣
            $return['integral_type'] = 3;
            $return['total']         = $return['totals'];
            $return['q_total']       = 0;
            $return['s_integral']    = 0;
        }
        $storage_room           = getSettings('storage_room');
        $return['storage_room'] = $storage_room;
        $return['type']         = 'cart';
        if ($c_ids) {
            return $return;
        } else {
            $this->ajaxSuccess('', $return);
        }
    }

    /**
     * 生成支付订单
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function submitOrder(Request $request)
    {
        if ($request->isPost()) {
            //提交方式  product商品提交 cart购物车提交
            $type = input('type');

            //购物车id
            $c_id = input('c_id');
            //商品id
            $params['p_id'] = input('p_id');
            //购买数量
            $params['num'] = input('num');
            //skuid
            $params['sku_id'] = input('sku_id');
            //配送方式
            if ($type == 'cart') {
                $data = $this->cartDetail($c_id);
            } elseif ($type == 'product') {
                $data = $this->orderDetail($params);
            } else {
                $this->ajaxError('未知错误');
            }
            if (empty($data['address'])) {
                $this->ajaxError('请选择地址');
            }
            $peisong_type = input('peisong_type');
            if (empty($peisong_type)) {
                $this->ajaxError('请选择配送方式');
            }
            //是否是北京地区
            $isbj = input('isbj');
            //是否积分抵扣
            $isdikou = input('isdikou');

            $member = db('member')->where(['id' => $this->uid])->find();
            //商品总价
            $product_total = $data['price'];
            //运费
            $freight = 0;
            //不是北京地区
            if ($isbj == 1) {
                //是自提或者是物流 //运费为零
                if ($peisong_type == 2 || $peisong_type == 4) {
                    $freight = 0;
                } else {
                    //快递需要运费
                    $freight = $data['freight'];
                }
            } else {
                //平台配送和快递需要运费
                if ($peisong_type == 1 || $peisong_type == 3) {
                    //判断一下是否到免运费的条件
                    if ($data['price'] >= $data['freight_num']) {
                        //免运费
                        $freight = 0;
                    } else {
                        //需要运费
                        $freight = $data['freight'];
                    }
                } else {
                    //自提不需要运费
                    $freight = 0;
                }
            }
            //抵扣金额
            $dikou = 0;
            if ($isdikou == 2) {
                $dikou = $data['q_total'];
            }
            $integral_bili = getSettings('integral', 'integral');
            $p_name        = db('member')->where(['id' => $member['m_fatherId']])->value(['m_nickname']);
            $unpaid        = getSettings('cancel_time', 'cancel_time');
            $add           = [
                'o_mid'               => $this->uid,//用户id
                'o_sn'                => $this->make_order_sn(),//订单号
                'o_status'            => 1,//
                'o_regionId'          => $data['address']['regionId'],//省市县id
                'o_ptotal'            => $product_total,//商品总价
                'o_total'             => $product_total + $freight,//订单总价（不包括抵扣价格）
                'o_freight'           => $freight,//运费
                'o_integral'          => $dikou,//抵扣价格
                'o_integral_bili'     => $integral_bili,//抵扣比例
                'o_actual_payment'    => $product_total + $freight - $dikou,//实付款
                'o_num'               => $data['num'],
                'o_name'              => $data['address']['name'],
                'o_phone'             => $data['address']['phone'],
                'o_address'           => $data['address']['address'],
                'o_createtime'        => now_datetime(),
                'o_remark'            => input('remark'),
                'o_unpaid'            => date('Y-m-d H:i:s', strtotime('+' . $unpaid . ' hours')),
                'o_pname'             => $p_name,
                'o_distribution_mode' => $peisong_type,
                'o_qiwangtime'        => input('qiwangtime'),
                'xiadan_type'         => $type,
                'p_id'                => $params['p_id'],
                'sku_id'              => $params['sku_id'],
                'num'                 => $params['num'],
                'c_id'                => $c_id,
                'o_name_path'         => $data['address']['name_path'],
            ];
            db()->startTrans();
            try {
                //生成订单
                $res = db('onlineorder')->insertGetId($add);
                if ($res == false) {
                    db()->rollback();
                    $this->ajaxError('生成订单失败');
                }
                //循环商品
                foreach ($data['product'] as $key => $value) {
                    //查询库存是否满足
                    $sku = db('fa_item_sku')->where(['sku_id' => $value['sku_id']])->find();
                    if ($value['p_num'] > $sku['stock']) {
                        db()->rollback();
                        $this->ajaxError("{$value['p_name']}库存不足");
                    }
                    //减库存
                    $res2 = db('fa_item_sku')->where(['sku_id' => $value['sku_id']])->setDec('stock', $value['p_num']);
                    if ($res2 == false) {
                        db()->rollback();
                        $this->ajaxError('生成订单失败');
                    }
                    //加销量
                    $res3 = db('product')->where(['id' => $value['p_id']])->setInc('p_sales', $value['p_num']);
                    if ($res3 == false) {
                        db()->rollback();
                        $this->ajaxError('生成订单失败');
                    }
                    //组合sku
                    $attr_symbol_path = explode(',', $sku['attr_symbol_path']);
                    $sku_name         = [];
                    foreach ($attr_symbol_path as $k => $v) {
                        $sku_name[$k] = db('fa_item_attr_val')->where(['symbol' => $v])->value('attr_value');
                    }
                    $adds = [
                        'd_orderId'      => $res,//订单id
                        'd_productId'    => $value['p_id'],//商品id
                        'd_num'          => $value['p_num'],//购买数量
                        'd_price'        => $value['p_price_ladder'],//商品单价
                        'd_total'        => $value['p_price_ladder'] * $value['p_num'],//商品总价
                        'd_name'         => $value['p_name'],//商品名称
                        'd_img'          => $value['p_img'],//商品图片
                        'd_sku'          => implode('/', $sku_name) . ";",//sku
                        'd_sku_id'       => $value['sku_id'],//skuid
                        'd_refund'       => 1,//默认未退款
                        'd_weight'       => $value['p_weight'],//重量
                        'd_price_ladder' => $value['p_price_ladder'],//阶梯价格
                    ];
                    //生成订单详情
                    $res1 = db('orderdetails')->insert($adds);
                    if ($res1 == false) {
                        db()->rollback();
                        $this->ajaxError('生成订单失败');
                    }
                }
                if ($type == 'cart') {
                    db('cart')->where('c_id', 'in', $c_id)->setField('c_isDelete', 1);
                }
                if ($isdikou == 2&&$dikou>0) {
                    $result = integralLog($this->uid, $dikou*$integral_bili, -1, 2, '抵扣积分', '', $res);
                    if ($result == false) {
                        db()->rollback();
                        $this->ajaxError('积分扣除失败');
                    }
                }
                db()->commit();
                $this->ajaxSuccess('生成订单成功', $res);
            } catch (Exception $e) {
                db()->rollback();
                $this->ajaxError($e->getMessage());
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }


    /**
     * 查询订单
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function corder(Request $request)
    {
        if ($request->isPost()) {
            $id = input('post.id');
            if (empty($id)) {
                $this->ajaxError('id为空');
            }
            $order = db('onlineorder')
                ->where('o_id', $id)
                ->find();
            if (empty($order)) {
                $this->ajaxError('订单为空');
            }
            $member = db('member')->where('id', $this->uid)->find();
            if ($order['o_actual_payment'] > $member['m_total']) {
                $order['isdis'] = true;
            } else {
                $order['isdis'] = false;
            }
            $this->ajaxSuccess('success', $order);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 订单列表
     *
     * @author: Gavin
     * @time: 2019/9/7 14:40
     */
    public function order_list()
    {
        $memberId = $this->getAccountId();
//        $memberId = 1;
        $type                = input('type');
        $page                = input('page', 1);
        $where['o.SourceId'] = $memberId;
        if (!empty($type)) {
            $where['o.status'] = $type;
        }

        $orderList     = db('onlineorder')
            ->alias('o')
            ->join('orderdetails od', 'od.orderId=o.id', 'LEFT')
            ->join('product p', 'p.id=od.productId')
            ->field('p.pname,p.img,od.price,od.num,od.total,o.status,o.id as o_id')
            ->group('o.id')
            ->where($where)
            ->page($page, 10)
            ->select();
        $num           = db('onlineorder')
            ->alias('o')
            ->join('orderdetails od', 'od.orderId=o.id')
            ->join('product p', 'p.id=od.productId')
            ->group('o.id')
            ->where($where)
            ->count();
        $list['count'] = $num;
        $list['list']  = $orderList;

        if (empty($orderList)) {
            return $this->ajaxError("未查询到信息");
        }
        $this->ajaxSuccess($list);
    }

    /**
     * 订单详情
     *
     * @author: Gavin
     * @time: 2019/9/7 14:47
     */
    public function order_detail()
    {
        $id        = input('id');
        $where     = [
            'o.id' => $id
        ];
        $orderInfo = db('onlineorder')
            ->alias('o')
            ->join('orderdetails od', 'od.orderId=o.id', 'LEFT')
            ->join('product p', 'p.id=od.productId', 'LEFT')
            ->field('p.pname,p.img,od.price,od.num,od.total,o.status,o.expresssn,o.express_name,o.name as b_name,o.phone,o.address,o.remark,o.sn as order_sn,od.productId')
            ->where($where)
            ->select();
        if (empty($orderInfo)) {
            return $this->ajaxError("未查询到信息");
        }
        $this->ajaxSuccess($orderInfo);
    }

    /**
     * 退款/退货
     *
     * @author: Gavin
     * @time: 2019/9/7 15:16
     */
    public function order_refund()
    {
        // 1待付款，2待发货，3待收货 4已完成 5已取消 6退款中 7退款完成
        $id     = input('id');
        $status = db('onlineorder')->where(['id' => $id])->value('status');
        if (in_array($status, [1, 5, 7])) {
            $this->ajaxError('订单状态不允许');
        }
        if ($status == 6) {
            $this->ajaxError('退款处理中');
        }
        $where          = [
            'o.id' => $id
        ];
        $orderInfo      = db('onlineorder')
            ->alias('o')
            ->join('orderdetails od', 'od.orderId=o.id')
            ->join('product p', 'p.id=od.productId')
            ->field('p.pname,p.img,od.price,od.num,od.total,od.id as d_id,o.status')
            ->where($where)
            ->select();
        $info['info']   = $orderInfo;
        $info['refund'] = $this->refund;
        if (empty($info)) {
            return $this->ajaxError("未查询到信息");
        }
        $this->ajaxSuccess($info);
    }

    /**
     * 申请退货/退款
     *
     * @author: Gavin
     * @time: 2019/9/7 15:28
     */
    public function apply_refund()
    {
        $orderId = input('id');
        $d_id    = input('d_id');
        $type    = input('type'); //退款原因
        $re_type = input('re_type'); //退款类型 1退款 2退款退货
        $total   = input('total'); //金额
//        $info = input('info'); //退款说明
//        $certificate = input('certificate'); //退款凭证
        $order = db('onlineorder')->where(['id' => $orderId])->find();
        if ($total > $order['total']) {
            $this->ajaxError('退款金额超出订单金额');
        }
        $data = [
            'orderId'      => $orderId,
            'oid'          => $d_id,
            'status'       => $re_type,
            'type'         => $type,
            'total'        => $total,
            'mid'          => $order['SourceId'],
            'createtime'   => now_datetime(),
            'refundReview' => 1,
            'sn'           => $this->make_sn(),
            'name'         => $order['name'],
            'phone'        => $order['phone'],
            'regionid'     => $order['aid'],
            'address'      => $order['address'],
            'rephone'      => $order['phone'],
//            'info' => $info,
        ];
        $res  = db('refund')->insert($data);
        //$orderRes = db('onlineorder')->where(['id' => $orderId])->update(['status' => 6]);
        $orderRes = db('orderdetails')->where(['id' => $d_id])->update(['istuihuo' => 2]);
        if ($res && $orderRes) {
            $this->ajaxSuccess('申请成功，等待审核');
        } else {
            $this->ajaxError('申请失败，请稍后再试');
        }
    }

    /**
     * @Notes:生成不重复的退款单号
     * @Date: 2019/9/16
     * @Time: 9:04
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function make_sn()
    {
        $sn   = '800' . date('YmdHis') . rand(1000, 9999);
        $data = db("refund")->where(['sn' => $sn])->find();
        if ($data) {
            $sn = $this->make_sn();
        }
        return $sn;
    }

    /**
     * @Notes:生成不重复的订单号
     * @Date: 2019/9/16
     * @Time: 9:03
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function make_order_sn()
    {
        $sn   = date('Ymd') . time() . rand(100, 999);
        $data = db("onlineorder")->where(['o_sn' => $sn])->find();
        if ($data) {
            $sn = $this->make_order_sn();
        }
        return $sn;
    }

    /**
     * 退款进度
     *
     * @author: Gavin
     * @time: 2019/9/7 16:21
     */
    public function refund_detail()
    {
        $orderId   = input('id');
        $where     = [
            'r.orderId' => $orderId
        ];
        $orderInfo = db('refund')
            ->alias('r')
            ->join('onlineorder o', 'o.id=r.orderId')
//            ->join('orderdetails od','od.id=r.oid')
            ->join('orderdetails od', 'od.orderId=o.id')
            ->join('product p', 'p.id=od.productId')
            ->field('p.pname,p.img,od.price,od.num,o.total,r.status,r.total as r_total,r.createtime,r.sn,r.shopTime,r.endTime,r.type')
            ->where($where)
            ->find();

        $status = $this->refund;
        foreach ($status as $k => $v) {
            if ($orderInfo['type'] == $k) {
                $orderInfo['cause'] = $v;
            }
        }
        if (empty($orderInfo)) {
            return $this->ajaxError("未查询到信息");
        }
        $this->ajaxSuccess($orderInfo);
    }

    /**
     * 确认订单
     *
     * @author: Gavin
     * @time: 2019/9/9 16:23
     */
    public function confirm_order()
    {
        $p_id     = input('p_id');
        $num      = input('num', 1);
        $where    = [
            'id'       => $p_id,
            'isDelete' => 2,
            'isUp'     => 2,
        ];
        $memberId = $this->getAccountId();
//        $memberId = 1;
        $level = db('member')->where(['id' => $memberId])->value('identity');
        switch ($level) { //用户身份 : 1, 普通会员 2, 店主3 店铺
            case 1:
                $field = 'vipPrice';
                break;
            case 2:
                $field = 'mastPrice';
                break;
            case 3:
                $field = 'storePrice';
                break;
            default:
                $field = 'total';
        }
        $productInfo                = db('product')
            ->field("id,pname,img,{$field} as m_price,stock,salesvolume,sharePrice")
            ->where($where)
            ->find();
        $productInfo['num']         = $num;
        $total_price                = floatval($productInfo['m_price'] * $num);
        $productInfo['total_price'] = number_format($total_price, 2, '.', '');

        if (empty($productInfo)) {
            return $this->ajaxError("未查询到信息");
        }
        if (input('act') == 'add') {
            $address_id = input('address_id');
            $share_id   = input('share_id', 0);  //分享人id
            $address    = db('memberaddress')->where(['id' => $address_id])->find();
            if (empty($address)) {
                $this->ajaxError('地址无效');
            }
            $pay_type              = input('pay_type');
            $productInfo['remark'] = trim(input('remark'));
            $order_sn              = $this->make_order_sn();
            $res                   = $this->add_order($memberId, $order_sn, $productInfo, $address, $share_id);
            $productRes            = $this->set_product($p_id, $productInfo);
            if ($res && $productRes) {
//                $this->ajaxSuccess('支付');

                //调用支付方法
                $pay   = new Pay();
                $param = [
                    'out_trade_no' => $order_sn,
                    'total_fee'    => $productInfo['total_price'],
                ];
                if ($pay_type == 'wx') {
                    $result = $pay->pay($param, 2, 'wx');
                } elseif ($pay_type == 'ali') {
                    $result = $pay->pay($param, 2, 'ali');
                }
                if ($result != false) {
                    $data['payType'] = $pay_type;
                    $data['payData'] = $result;
                    $this->ajaxSuccess($result);
                } else {
                    $this->ajaxError("支付失败，请重试");
                }
            } else {
                $this->ajaxError('订单提交失败');
            }
        }
        $this->ajaxSuccess($productInfo);
    }

    /**
     * 添加订单
     *
     * @author: Gavin
     * @time: 2019/9/9 16:51
     */
    public function add_order($memberId, $order_sn, $productInfo, $address, $share_id)
    {
        $data    = [
            'SourceId'   => $memberId,
            'sn'         => $order_sn,
            'status'     => 1,
            'createTime' => now_datetime(),
            'name'       => $address['name'],
            'phone'      => $address['phone'],
            'aid'        => $address['id'],
            'address'    => $address['address'],
            'total'      => $productInfo['total_price'],
            'remark'     => $productInfo['remark'],
        ];
        $orderId = db('onlineorder')->insertGetId($data);
        if (empty($orderId)) {
            return -1;
        }
        $detailData = [
            'orderId'    => $orderId,
            'productId'  => $productInfo['id'],
            'num'        => $productInfo['num'],
            'price'      => $productInfo['m_price'],
            'total'      => $productInfo['total_price'],
            'share_id'   => $share_id,
            'sharePrice' => $productInfo['sharePrice'],
        ];
        return db('orderdetails')->insert($detailData);
    }

    /**
     * 下单成功，修改商品
     *
     * @author: Gavin
     * @time: 2019/9/11 15:00
     */
    public function set_product($product_id, $productInfo)
    {
        $product = db('product')->where(['id' => $product_id])->field('stock,salesvolume')->find();
        $data    = [
            'stock'       => $product['stock'] - $productInfo['num'],
            'salesvolume' => $product['salesvolume'] + $productInfo['num'],
        ];
        return db('product')->where(['id' => $product_id])->update($data);
    }

    /**
     * 取消订单
     *
     * @author: Gavin
     * @time: 2019/9/9 17:18
     */
    public function cancel_order()
    {
        $orderId = input('order_id');
        $order   = db('onlineorder')->where(['id' => $orderId])->find();
        if (empty($order)) {
            $this->ajaxError('订单不存在');
        }
        // 1待付款，2待发货，3待收货 4已完成 5已取消 6退款中 7退款完成 8待评价
        if ($order['status'] != 1) {
            $this->ajaxError('订单状态不允许');
        }
        $res = db('onlineorder')->where(['id' => $orderId])->update(['status' => 5]);
        if ($res) {
            $this->ajaxSuccess('订单取消成功');
        } else {
            $this->ajaxError('订单取消失败');
        }
    }

    /**
     * 上传图片
     */
    public function upload()
    {
        $file = request()->file('image');
        if ($file) {
            //将传入的图片移动到框架应用根目录/public/uploads/ 目录下，ROOT_PATH是根目录下，DS是代表斜杠 /
            $info
                = $file->validate(['size' => 2097152, 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
//                $url = saver() . "/uploads/" . $info->getSaveName();
                $url = '/uploads/' . $info->getSaveName();
                $this->ajaxSuccess($url);
            } else {
                // 上传失败获取错误信息
                $this->ajaxError($file->getError());
            }
        }
    }

    /**
     * 添加评价
     *
     * @author: Gavin
     * @time: 2019/9/11 16:45
     */
    public function add_comment()
    {
        //        $memberId = $this->getAccountId();
        $account = $this->getAccount()['account'];
//        $account = 188888888;
        $comment   = trim(input('comment'));
        $star      = input('star');
        $orderSn   = input('order_sn');
        $productId = input('product_id');
        $imgs      = input('img/a');
        $data      = [
            'comment'    => $comment,
            'img'        => serialize($imgs),
            'star'       => $star,
            'order_sn'   => $orderSn,
            'productid'  => $productId,
            'is_show'    => 1,
            'account'    => $account,
            'createtime' => now_datetime(),
        ];

        $res = db('comment')->insert($data);
        if ($res) {
            $this->ajaxSuccess('发布成功');
        } else {
            $this->ajaxError('发布失败');
        }
    }

    /**
     * 可提现收益
     *
     * @author: Gavin
     * @time: 2019/9/12 10:53
     */
    public function withdraw_total()
    {
        $memberId = $this->getAccountId();
//        $memberId = 1;
        $page      = input('page', 1);
        $where     = [
            'o.status' => ['in', [2, 3, 4, 8]],// 1待付款，2待发货，3待收货 4已完成 5已取消 6退款中 7退款完成 8待评价
            'share_id' => $memberId
        ];
        $orderList = db('onlineorder')
            ->alias('o')
            ->join('orderdetails od', 'od.orderId=o.id')
            ->join('product p', 'p.id=od.productId')
            ->field('o.id,o.createTime,o.status,od.price,od.total,p.sharePrice,p.id as p_id,p.pname,p.img')
            ->where($where)
            ->page($page, 10)
            ->select();
        foreach ($orderList as $k => $v) {
            switch ($v['status']) {
                case 2:
                    $orderList[$k]['status_info'] = '待发货';
                    break;
                case 3:
                    $orderList[$k]['status_info'] = '待收货';
                    break;
                case 4:
                    $orderList[$k]['status_info'] = '已完成';
                    break;
                case 8:
                    $orderList[$k]['status_info'] = '待评价';
                    break;
            }
        }

        $num           = db('onlineorder')
            ->alias('o')
            ->join('orderdetails od', 'od.orderId=o.id')
            ->join('product p', 'p.id=od.productId')
            ->where($where)
            ->count();
        $list['count'] = $num;
        $list['list']  = $orderList;
        if (empty($list)) {
            return $this->ajaxError("未查询到信息");
        }
        $this->ajaxSuccess($list);
    }

    /**
     * 确认收货
     *
     * @author: Gavin
     * @time: 2019/9/12 14:29
     */
    public function confirm_receipt()
    {
        $member_id = $this->getAccountId();
//        $member_id = 1;
        $order_id = input('id');
        $where    = [
            'id'       => $order_id,
            'SourceId' => $member_id,
        ];
        $order    = db('onlineorder')->where($where)->find();
        if ($order['status'] != 3) {  // 1待付款，2待发货，3待收货 4已完成 5已取消 6退款中 7退款完成 8待评价
            $this->ajaxError('订单状态不允许');
        }
        $res = db('onlineorder')->where($where)->update(['status' => 8]);
        if (false == $res) {
            $this->ajaxError('订单更新失败');
        }
        $parent_id   = db('member')->where(['id' => $member_id])->value('parentid');
        $identity    = db('member')->where(['id' => $parent_id])->value('identity');//会员等级
        $repeat_sale = getSettings('repeat_sale', 'repeat_sale');
        $ratio       = bcdiv($repeat_sale, 100, 2);
//        $total += $order['total'] * $ratio;
        $num = bcmul($order['total'], $ratio, 2);
//        $num = number_format($total, 2, '.', '');
        $log_res = accountLog($parent_id, $num, 1, '下级复销奖励');
        if (false == $log_res) {
            $this->ajaxError('下级复销奖励更新失败');
        }
        if ($identity == 2) {
            $team_award = team_award($parent_id, $num);
            if (false == $team_award) {
                $this->ajaxError('团队奖更新失败');
            }
            $extremeAward = extremeAward($parent_id, $num);
            if (false == $extremeAward) {
                $this->ajaxError('极差奖更新失败');
            }
        }

        $performance = achievement($member_id, $num);
        if (false == $performance) {
            $this->ajaxError('更新业绩失败');
        }
        $upgrade = upgrade($member_id);
        if (false == $upgrade) {
            $this->ajaxError('店铺升级失败');
        }
        $order_detail = db('orderdetails')->where(['orderId' => $order_id])->select();
        foreach ($order_detail as $k => $v) {
            if ($v['share_id'] != 0) {
                $log = accountLog($v['share_id'], $v['sharePrice'], 1, '佣金奖励');
                if (false == $log) {
                    $this->ajaxError('佣金奖励更新失败');
                }
            }
        }
        if ($identity == 2) {
            $reg = registration_subsidy($member_id);
            if (false == $reg) {
                $this->ajaxError('注册补贴更新失败');
            }
        }

        $this->ajaxSuccess('确认收货成功');
    }

    /**
     * 订单再次支付
     *
     * @author: Gavin
     * @time: 2019/9/16 17:10
     */
    public function pay_again()
    {
        $order_id = input('id');
        $pay_type = input('pay_type');
        $order    = db('onlineorder')->where(['id' => $order_id])->find();
        if (empty($order)) {
            $this->ajaxError('订单不存在或已取消');
        }
        if ($order['status'] != 1) {
            $this->ajaxError('订单状态不允许');
        }
        $total = db('orderdetails')->where(['orderId' => $order_id])->sum('total');

        //调用支付方法
        $pay   = new Pay();
        $param = [
            'out_trade_no' => $order['sn'],
            'total_fee'    => $total,
        ];
        if ($pay_type == 'wx') {
            $result = $pay->pay($param, 2, 'wx');
        } elseif ($pay_type == 'ali') {
            $result = $pay->pay($param, 2, 'ali');
        }
        if ($result != false) {
            $data['payType'] = $pay_type;
            $data['payData'] = $result;
            $this->ajaxSuccess($result);
        } else {
            $this->ajaxError("支付失败，请重试");
        }
    }
}