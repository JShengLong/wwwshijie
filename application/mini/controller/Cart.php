<?php

namespace app\mini\controller;

class Cart extends Signin
{
    /**
     * 添加购物车
     *
     * @author: Gavin
     * @time: 2019/9/9 16:28
     */
    public function addCart()
    {
        if (request()->isPost()) {
            //商品id
            $product_id = input('product_id');
            //skuid
            $sku_id = input('sku_id');
            if (empty($sku_id)) {
                $this->ajaxError('请选择商品规格');
            }
            //数量
            $num = input('num', 1);
            //用户id
            $memberId = $this->uid;
            //商品
            $product = db('product')
                ->where(['id' => $product_id, 'p_isDelete' => 2, 'p_isUp' => 2])
                ->find();
            if (empty($product)) {
                $this->ajaxError('商品已下架或者已删除');
            }

            $sku = db('fa_item_sku')->where(['sku_id' => $sku_id])->find();
            if (empty($sku)) {
                $this->ajaxError('该规格不存在');
            }
            $where = [
                'c_mid'    => $memberId,
                'c_pid'    => $product_id,
                'c_sku_id' => $sku_id,
                'c_isDelete'=>2,
            ];
            //查询是否存在这个此用户此商品此规格的商品
            $cart = db('cart')->where($where)->find();
            if ($cart) {
                //存在的话加购物车的数量加上添加的数量
                $new_num = $cart['c_num'] + $num;

                $ladder=db('ladder')
                    ->where(['sku_id'=>$sku_id])
                    ->order('num desc')
                    ->select();
                $update['c_num']=$new_num;
                foreach ($ladder as $key=>$value){
                    if($new_num>=$value['num']){
                        $update['c_price']=$value['price'];
                    }
                    break;
                }
                //修改数量
                $res = db('cart')
                    ->where(['c_id' => $cart['c_id']])
                    ->update($update);
                if ($res) {
                    $this->ajaxSuccess('添加成功');
                } else {
                    $this->ajaxError('添加失败');
                }
            }
            //不存在的话新增数据
            $data = [
                'c_pid'        => $product_id,
                'c_mid'        => $memberId,
                'c_sku_id'     => $sku_id,
                'c_num'        => $num,
                'c_createTime' => now_datetime(),
                'c_price'      => $sku['price']
            ];
            $ladder=db('ladder')
                ->where(['sku_id'=>$sku_id])
                ->order('num desc')
                ->select();
            foreach ($ladder as $key=>$value){
                if($num>=$value['num']){
                    $data['c_price']=$value['price'];
                }
                break;
            }
            $res  = db('cart')->insert($data);
            if ($res) {
                $this->ajaxSuccess('添加成功');
            } else {
                $this->ajaxError('添加失败');
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 购物车列表
     */
    public function cartList()
    {
        if (request()->isPost()) {
            //用户id
            $member_id = $this->getAccountId();
            //分页
            //购物车列表
            $list = db('cart')
                ->alias('t')
                ->join('product p', 'p.id=t.c_pid')
                ->join('category c', 'c.id=p.p_category_id', 'left')
                ->where('t.c_mid=' . $member_id. ' and c_isDelete=2')
                ->field('t.*,p.p_name,p.p_img')
                ->order('c_id desc')
                ->select();
            $total=0;
            foreach ($list as $key => $value) {
                if($value['c_issel']==2){
                    $total=$total+($value['c_num']*$value['c_price']);
                }
                $sku              = db('fa_item_sku')
                    ->where(['sku_id' => $value['c_sku_id']])
                    ->find();
                $attr_symbol_path = explode(',', $sku['attr_symbol_path']);
                $sku_name         = [];
                foreach ($attr_symbol_path as $k => $v) {

                    $sku_name[$k] = db('fa_item_attr_val')
                        ->where(['symbol' => $v])
                        ->value('attr_value');
                }
                $list[$key]['sku_name']         = implode('/', $sku_name) . ";";
                $list[$key]['attr_symbol_path'] = $attr_symbol_path;
            }
            $return['sel']=2;
            if(empty($list)){
                $return['sel']=1;
            }
            foreach ($list as $key => $value) {
                if($value['c_issel']==1){
                    $return['sel']=1;
                }
                $list[$key]['p_img'] = saver() . $value['p_img'];
            }

            $return['list']=$list;
            $return['tot']=number_format($total,2,'.','');
            $this->ajaxSuccess('购物车列表获取成功', $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 修改购物车数量
     *
     * @author: Gavin
     * @time: 2019/9/10 16:06
     */
    public function setCartNum()
    {
        if (request()->isPost()) {
            $id  = input('post.c_id');
            $num = input('post.num');
            if (empty($id) || empty($num)) {
                $this->ajaxError('参数不能为空');
            }
            $cart=db('cart')->where(['c_id'=>$id])
                ->find();

            $sku=db('fa_item_sku')->where(['sku_id'=>$cart['c_sku_id']])
                ->find();

            $ladder=db('ladder')
                ->where(['sku_id'=>$cart['c_sku_id']])
                ->order('num desc')
                ->select();

            $update['c_num']=$num;
            $a=0;
            foreach ($ladder as $key=>$value){
                if($num>=$value['num']){
                    $update['c_price']=$value['price'];
                    $a=1;
                    break;
                }
            }
            if($a==0){
                $update['c_price']=$sku['price'];
            }
            $res = db('cart')
                ->where(['c_id' => $id])
                ->update($update);
            if ($res) {
                $this->ajaxSuccess('成功');
            } else {
                $this->ajaxError('失败');
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 购物车设置选中
     *
     * @author: Gavin
     * @time: 2019/9/10 17:30
     */
    public function cartSelection()
    {
        if (request()->isPost()) {
            $c_id    = input('post.c_id');
            $c_issel = input('post.c_issel');
            db('cart')
                ->where('c_id', $c_id)
                ->setField('c_issel', $c_issel);
            $this->ajaxSuccess('设置成功');
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }
    /**
     * 购物车设置选中全部
     *
     * @author: Gavin
     * @time: 2019/9/10 17:30
     */
    public function cartSelectionAll()
    {
        if (request()->isPost()) {
            $c_issel = input('post.sel');
            db('cart')
                ->where('c_mid',$this->uid)
                ->where('c_isDelete',2)
                ->setField('c_issel', $c_issel);
            $this->ajaxSuccess('设置成功');
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 删除购物车
     *
     * @author: Gavin
     * @time: 2019/9/10 17:30
     */
    public function delCart()
    {
        if (request()->isPost()) {
            $ids = input('post.ids');
            $res = db('cart')
                ->where('c_id', 'in', $ids)
                ->delete();
            if (empty($res)) {
                return $this->ajaxError("未查询到信息");
            }
            $this->ajaxSuccess('删除成功');
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 购物车提交到结算页面数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function payCart()
    {
        if (request()->isPost()) {
            //购物车id数组
            $ids = input('ids/a');
            if (empty($ids)) {
                $this->ajaxError('请选择商品');
            }
            $member_id = $this->getAccountId();
            $return    = [];
            //插询默认地址
            $return['address'] = db('memberaddress')
                ->where('memberId=' . $member_id . ' and isDefault=1  and isDelete = 1')
                ->find();
            if ($return['address']) {
                $return['is_address'] = 2;//有默认地址
            } else {
                $return['is_address'] = 1;//无默认地址
            }
            $return['list'] = db('cart')
                ->alias('t')
                ->join('product p', 'p.id=t.c_pid')
                ->join('category c', 'c.id=p.p_category_id')
                ->where('t.c_id', 'in', $ids)
                ->field('t.*,p.p_name,p.p_img,p.p_oldprice,p.p_integral,c.name as cate_name,p.p_stock')
                ->order('t.c_id desc')
                ->select();
            foreach ($return['list'] as $key => $value) {
                $return['list'][$key]['p_img'] = saver() . $value['p_img'];
            }
            $result                = $this->calculate_price($return['list']);
            $return['goods_price'] = $result['result']['goods_price'];
            $return['goods_num']   = $result['result']['num'];
            $this->ajaxSuccess('获取结算数据成功', $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 商品详情提交到结算页面数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function payProduct()
    {
        if (request()->isPost()) {
            //商品id
            $product_id = input('post.product_id');
            $num        = input('post.num');
            if (empty($product_id)) {
                $this->ajaxError('参数错误');
            }
            if (empty($num)) {
                $this->ajaxError('请选择商品数量');
            }
            $member_id = $this->getAccountId();
            $return    = [];
            //插询默认地址
            $return['address'] = db('memberaddress')
                ->where('memberId=' . $member_id . ' and isDefault=1 and isDelete = 1')
                ->find();
            if ($return['address']) {
                $return['is_address'] = 2;//有默认地址
            } else {
                $return['is_address'] = 1;//无默认地址
            }
            $return['list']              = db('product')
                ->where('id', $product_id)
                ->field('p_name,p_img,p_oldprice,p_integral,p_category_id')
                ->find();
            $return['list']['cate_name'] = db('category')
                ->where('id', $return['list']['p_category_id'])
                ->value('name');
            $return['list']['p_img']     = saver() . $return['list']['p_img'];
            $return['list']['goods_num'] = $num;
            $return['goods_num']         = $num;
            $return['goods_price']       = bcmul($return['list']['p_integral'], $num, 2);
            $this->ajaxSuccess('获取结算数据成功', $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     *  计算购物车商品价格
     * @param array $order_goods
     * @return array
     */
    public function calculate_price($order_goods = array())
    {
        $goods_price = 0;
        $anum        = 0;
        foreach ($order_goods as $key => $val) {
            $order_goods[$key]['goods_fee'] = bcmul($val['c_num'], $val['p_integral'], 2);    // 小计
            $goods_price                    += $order_goods[$key]['goods_fee']; // 商品总价
            $anum                           += $val['c_num']; // 购买数量
        }
        $result = array(
            'goods_price' => number_format($goods_price, 2, '.', ''), // 商品价格
            'num'         => $anum, // 商品总共数量
        );
        return array('status' => 1, 'msg' => "计算价钱成功", 'result' => $result); // 返回结果状态
    }

    /**
     * 修改商品库存
     *
     * @author: Gavin
     * @time: 2019/9/11 15:26
     */
    public function setCartProduct($cart)
    {
        foreach ($cart as $k => $v) {
            db('product')
                ->where(['id' => $v['c_pid']])
                ->setInc('p_sales', $v['c_num']);
            db('product')
                ->where(['id' => $v['c_pid']])
                ->setDec('p_stock', $v['c_num']);
        }
    }
}