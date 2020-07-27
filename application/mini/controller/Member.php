<?php

namespace app\mini\controller;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/31
 * Time: 14:46
 */

use alioss\Alioss;
use think\Db;
use sendsms\SendSms;
use think\Exception;
use think\Request;

class Member extends Signin
{
    public function isLogin()
    {
        $this->ajaxSuccess('success', '');
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

    /**
     * 用户信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function member()
    {
        if (request()->isPost()) {
            //查询用户信息
            $member = db('member')->where('id', $this->uid)->find();
            //将数组中的null值转换成空数组
            foreach ($member as $key => $value) {
                if ($value === null) {
                    $member[$key] = '';
                }
            }
            //返回个人信息
            $this->ajaxSuccess('个人信息请求成功', $member);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     *绑定推荐人
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function bindingRecommend($in = '')
    {
        $member = db('member')->where('id', $this->uid)->find();
        if ($member['m_fatherId']) {
            $this->ajaxError('你已绑定推荐人');
        }
        $invitation = input('post.invitation') ? input('post.invitation') : $in;
        $parent     = db('member')->where(['m_invitation_code' => $invitation])->find();
        if ($parent['id'] == $this->uid) {
            $this->ajaxError('不能绑定自己的邀请码');
        }
        if (empty($parent)) {
            $this->ajaxError('输入的邀请码有误');
        }
        $data['m_fatherId']        = $parent['id'];
        $data['m_grandpaId']       = $parent['m_fatherId'];
        $data['m_grate_grandpaId'] = $parent['m_grandpaId'];
        if (empty($parent["m_pathtree"])) {
            $data["m_pathtree"] = ",{$parent["id"]},";
        } else {
            $data["m_pathtree"] = "{$parent["m_pathtree"]}{$parent["id"]},";
        }
        $res = db('member')->where('id', $this->uid)->update($data);
        if ($in) {
            return $res;
        }
        if ($res == false) {
            $this->ajaxError('绑定失败');
        }
        $this->ajaxSuccess('绑定成功');
    }

    /**
     * 修改昵称
     */
    public function updateNickname()
    {
        if (request()->isPost()) {
            //昵称
            $name = input("post.name");
            $img  = input("post.img");
            $in   = input('post.in');
            if (empty($name)) {
                $this->ajaxError("昵称不能为空");
            }
            if ($in) {
                $res = $this->bindingRecommend($in);
                if ($res == false) {
                    $this->ajaxError("修改失败");
                }
            }
            //判断是否是汉字
//            if (!isChinese($name)) {
//                $this->ajaxError('请输入汉字昵称');
//            }
            //判断昵称长度
            $num = $this->strLength($name);
            if ($num > 20) {
                $this->ajaxError('输入的长度不能大于20');
            }
            if ($img) {
                $update = array("m_nickname" => $name, 'm_thumb' => $img);
            } else {
                $update = array("m_nickname" => $name);
            }
            //更新昵称
            try {
                $res = db("member")->where("id", $this->uid)->update($update);
                if ($res !== false) {
                    $this->ajaxSuccess("修改成功");
                } else {
                    $this->ajaxError("修改失败");
                }
            } catch (\Exception $e) {
                $this->ajaxError($e->getMessage());
            }

        }
    }

    /**
     * 中英混合字符串长度判断
     * @param $str
     * @param string $charset
     * @return float
     */
    function strLength($str, $charset = 'utf-8')
    {
        if ($charset == 'utf-8') {
            $str = iconv('utf-8', 'gb2312', $str);
        }
        $num   = strlen($str);
        $cnNum = 0;
        for ($i = 0; $i < $num; $i++) {
            if (ord(substr($str, $i + 1, 1)) > 127) {
                $cnNum++;
                $i++;
            }
        }
        $enNum  = $num - ($cnNum * 2);
        $number = ($enNum / 2) + $cnNum;
        return ceil($number);
    }

    /**
     * @Notes:修改头像
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:50
     */
    public function updateHeadImg()
    {
        $file = request()->file('image');
        if (empty($file)) $this->error('请上传图片');
        // 要上传图片的本地路径
        $filePath = $file->getRealPath();
        $name     = $file->getInfo('name');

        $name   = explode('.', $name);
        $name   = array_reverse($name);
        $oss    = new Alioss();
        $object = 'image' . date('YmdHis') . rand(1000, 9999) . md5(microtime(true)) . '.' . $name[0];
        $res    = $oss->oss($object, $filePath);
        if ($res != false) {
            $url = $res['info']['url'];
            if ($url) {
                $update = ['m_thumb' => $url];
                $res1   = db("member")->where("id", $this->uid)->update($update);
                if ($res1 !== false) {
                    $this->ajaxSuccess('上传成功', ['url' => $url, 'img' => $url]);
                } else {
                    $this->ajaxError("修改失败");
                }
            } else {
                $this->ajaxError("上传失败");
            }

        } else {
            $this->ajaxError('上传图片失败');
        }
    }

    /**
     * 我的收货地址列表
     */
    public function myAddress()
    {
        if (request()->isPost()) {
            //页数
            $page = input("post.page", 1);
            //收货地址列表
            $list = db("memberaddress")
                ->where(["memberId" => $this->uid, "isDelete" => 1])
                ->order("isDefault asc,id desc")
                ->page($page, 10)
                ->select();
            //返回信息
            $this->ajaxSuccess('收货地址请求成功', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * @Notes:设置默认地址
     * @Author:jsl
     * @Date: 2019/9/7
     * @Time: 14:23
     *
     */
    public function setDefault()
    {
        if (request()->isPost()) {
            //地址id
            $id = input("post.id");
            //判断id是否为空
            if (empty($id)) {
                $this->ajaxError("参数错误");
            }
            //开启事务
            Db::startTrans();
            try {
                //所有的地址全部改成不是默认地址
                $res = Db::table("memberaddress")->where(["memberId" => $this->uid])->update(array("isDefault" => 2));
                if ($res === false) {
                    //事务回滚
                    Db::rollback();
                    $this->ajaxError("设置失败");
                }
                //给这个地址改为默认地址
                $res1 = Db::table("memberaddress")->where(["id" => $id])->update(array("isDefault" => 1));
                if ($res1 === false) {
                    //事务回滚
                    Db::rollback();
                    $this->ajaxError("设置失败");
                }
                //事务提交
                Db::commit();
                $this->ajaxSuccess("设置成功");
            } catch (\Exception $exception) {
                //获取异常  事务回滚
                Db::rollback();
                $this->ajaxError($exception->getMessage());
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * @Notes:删除地址
     * @Author:jsl
     * @Date: 2019/9/7
     * @Time: 14:28
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function deleteAddress()
    {
        if (request()->isPost()) {
            $id = input("post.id");
            //地址id
            if (empty($id)) {
                $this->ajaxError("参数错误");
            }
            //查询是否存在这个地址
            $address = Db::table("memberaddress")->where(["id" => $id])->find();
            if (empty($address)) {
                $this->ajaxError("地址为空");
            }
            try {
                //存在的话删除
                $res = Db::table("memberaddress")->where(["id" => $id])->update(array("isDelete" => 2));
                if ($res === false) {
                    $this->ajaxError("删除失败");
                }
                $this->ajaxSuccess("删除成功");
            } catch (\Exception $exception) {
                $this->ajaxError($exception->getMessage());
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * @Notes:添加/编辑 地址
     * @Author:jsl
     * @Date: 2019/9/7
     * @Time: 15:19
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function instartAddress()
    {
        if (request()->isPost()) {
            //地址id
            $id = input("post.id");
            //收货人姓名
            $name = input("post.name");
            if (empty($name)) {
                $this->ajaxError("姓名不能为空");
            }
            //收货人电话
            $phone = input("post.phone");
            if (empty($phone)) {
                $this->ajaxError("手机号不能为空");
            }
            if (!isPhone($phone)) {
                $this->ajaxError("请填写正确的手机号");
            }
            //地址id
            $regionId = input("post.regionId");
            if (empty($regionId)) {
                $this->ajaxError("请选择地址");
            }
            $name_path = input('name_path');

            //查询出省市县
//            $region = Db::table("region")->where(["id" => $regionId])->find();
            //详细地址
            $address = input("post.address");
            if (empty($address)) {
                $this->ajaxError("请填写详细地址");
            }
            //id为空的话 新增地址
            $adds = db('memberaddress')->where(['isDelete' => 1, 'memberId' => $this->uid])->select();

            if (empty($id)) {
                if (empty($adds)) {
                    $isDefault = 1;
                } else {
                    $isDefault = 2;
                }
                $data = [
                    "memberId"  => $this->uid,//用户id
                    "regionId"  => $regionId,//省市县id
                    "address"   => $address,//详细地址
                    "name"      => $name,//收货人姓名
                    "phone"     => $phone,//收货人电话
                    "isDefault" => $isDefault,//非默认
                    "isDelete"  => 1,//不删除
                    "name_path" => $name_path,//省市县

                ];
                try {
                    //新增数据
                    $res = Db::table("memberaddress")->insert($data);
                    if ($res == false) {
                        $this->ajaxError("添加失败");
                    }
                    $this->ajaxSuccess("添加成功");
                } catch (\Exception $exception) {
                    $this->ajaxError($exception->getMessage());
                }
                //id不为空就是修改地址
            } else {
                $data = [
                    "regionId"  => $regionId,//省市县id
                    "address"   => $address,//详细地址
                    "name"      => $name,//收货人姓名
                    "phone"     => $phone,//收货人电话
                    "name_path" => $name_path,//省市县
                ];
                try {
                    //编辑数据
                    $res = Db::table("memberaddress")->where(["id" => $id])->update($data);
                    if ($res === false) {
                        $this->ajaxError("编辑失败");
                    }
                    $this->ajaxSuccess("编辑成功");
                } catch (\Exception $exception) {
                    $this->ajaxError($exception->getMessage());
                }
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * @Notes:编辑地址页面的数据
     * @Author:jsl
     * @Date: 2019/9/7
     * @Time: 15:22
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function editAddress()
    {
        if (request()->isPost()) {
            //地址id
            $id = input("post.id");
            if (empty($id)) {
                $this->ajaxError("参数错误");
            }
            //查询数据
            $address = Db::table("memberaddress")->where(["id" => $id])->find();
            $this->ajaxSuccess('请求成功', $address);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 订单列表
     */
    public function orderList()
    {
        if (request()->isPost()) {
            $page      = input('post.page', 1);
            $status    = input('post.status');
            $member_id = $this->uid;
            //1待支付2待服务3已完成4已取消
            if (empty($status)) {
                $where = ['o_mid' => $member_id];
            } else {
                $where = ['o_mid' => $member_id, 'o_status' => $status];
            }
            //订单列表
            $order_list = db('onlineorder')
                ->where($where)
                ->page($page, 10)
                ->order('o_id desc')
                ->field('o_id,o_sn,o_num,o_total,o_status')
                ->select();
            //循环查询订单里面的商品
            foreach ($order_list as $key => $value) {
                $order_list[$key]['detail'] = db('orderdetails')
                    ->alias('t')
                    ->join('product p', 'p.id=t.d_productId', 'left')
                    ->join('category c', 'c.id=p.p_category_id', 'left')
                    ->where(['t.d_orderId' => $value['o_id']])
                    ->field('d_num,d_price,d_total,p_name,p_img,p_oldprice,c.name as cate_name')
                    ->select();
                //图片加前缀
                foreach ($order_list[$key]['detail'] as $k => $v) {
                    $order_list[$key]['detail'][$k]['p_img'] = saver() . $v['p_img'];
                }
            }
            $this->ajaxSuccess('请求订单列表成功', $order_list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     *
     * 删除订单
     */
    public function delOrder()
    {
        if (request()->isPost()) {
            //订单id
            $id = input('post.id');
            if (empty($id)) {
                $this->ajaxError('参数错误');
            }
            $res = db('onlineorder')->where(['o_id' => $id])->setField('o_isDelete', 1);
            if ($res == false) {
                $this->ajaxError('删除失败');
            } else {
                $this->ajaxSuccess('删除成功');
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 订单详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderDetail()
    {
        if (request()->isPost()) {
            //订单id
            $id = input('post.id');
            if (empty($id)) {
                $this->ajaxError('参数错误');
            }
            $order_detail                   = db('onlineorder')
                ->where(['o_id' => $id])
                ->find();
            $order_detail['name_path']      = db('region')
                ->where(['id' => $order_detail['o_regionId']])
                ->value('name_path');
            $order_detail['product_detail'] = db('orderdetails')
                ->alias('t')
                ->join('product p', 'p.id=t.d_productId', 'left')
                ->join('category c', 'c.id=p.p_category_id', 'left')
                ->where(['t.d_orderId' => $id])
                ->field('d_num,d_price,d_total,p_name,p_img,p_oldprice,c.name as cate_name')
                ->select();
            //图片加前缀
            foreach ($order_detail['product_detail'] as $k => $v) {
                $order_detail['product_detail'][$k]['r_id']  = db('refund')
                    ->where(['order_id' => $id, 'oid' => $v['d_id']])
                    ->order('id desc')
                    ->value('id');
                $order_detail['product_detail'][$k]['p_img'] = saver() . $v['p_img'];
            }
            $this->ajaxSuccess('获取订单详情成功', $order_detail);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 取消订单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelOrder()
    {
        if (request()->isPost()) {
            $id = input('post.id');
            if (empty($id)) {
                $this->ajaxError('参数错误');
            }
            $order = db('onlineorder')
                ->where(['o_id' => $id, 'o_status' => 1])
                ->find();
            if (empty($order)) {
                $this->ajaxError('订单不存在或者已支付');
            }
            $orderDetail = db('orderdetails')
                ->where(['d_orderId' => $id])
                ->select();
            $result      = [1];
            db()->startTrans();
            try {
                foreach ($orderDetail as $value) {
                    $result[]
                        = db('fa_item_sku')->where(['sku_id' => $value['d_sku_id']])->setInc('stock', $value['d_num']);
                }
                if (in_array(0, $result)) {
                    db()->rollback();
                    $this->ajaxError('取消失败，请重试');
                }
                $res = db('onlineorder')
                    ->where(['o_id' => $id, 'o_status' => 1])
                    ->setField('o_status', 6);
                if ($res == false) {
                    db()->rollback();
                    $this->ajaxError('取消失败，请重试');
                }
                if ($order['o_integral'] > 0) {
                    $result[]
                        = integralLog($this->uid, $order['o_integral'] * $order['o_integral_bili'], 1, 3, '取消订单', '', $id);
                }
                db()->commit();
                $this->ajaxSuccess('取消成功');
            } catch (\Exception $exception) {
                db()->rollback();
                $this->ajaxError($exception->getMessage());
            }

        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 订单支付
     * @throws Db\exception\ModelNotFoundException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function payOrder()
    {
        if (request()->isPost()) {
            $id               = input('post.id');
            $payment_password = input('post.payment_password');
            if (empty($id)) {
                $this->ajaxError('参数错误');
            }

            $pay_type = input('post.pay_type');
            if (empty($pay_type)) {
                $this->ajaxError('参数错误');
            }
            $pay_type_all = ['mini', 'app', 'ali', 'money'];
            if (!in_array($pay_type, $pay_type_all)) {
                $this->ajaxError('无效的支付方式');
            }
            //用户信息
            $member_info = db('member')
                ->where(['id' => $this->uid])
                ->find();
            if ($pay_type == "money") {
                if ($member_info['m_payment_password'] == '') {
                    $this->ajaxError('请设置支付密码');
                }
                if (empty($payment_password)) {
                    $this->ajaxError('请输入支付密码');
                }
                //验证支付密码是否正确
                if (!password_verify($payment_password, $member_info['m_payment_password'])) {
                    $this->ajaxError('支付密码不正确，请重新输入');
                }
            }
            if (empty($id)) {
                $this->ajaxError('参数错误');
            }
            $order = db('onlineorder')
                ->where(['o_id' => $id, 'o_status' => 1])
                ->find();
            if (empty($order)) {
                $this->ajaxError('订单不存在或者已支付');
            }
            //实例化支付类
            $pay = new Pay();
            //返回数据
            $return['payData'] = '';//支付数据为空则不用调起支付
            $return['orderid'] = $id;//订单id
            $return['payType'] = $pay_type;
            //是余额支付和扣积分开启事务
            if ($pay_type == "money" || $order['o_integral'] > 0) {
                db()->startTrans();
            }
            //判断实付款是否大于零
            if ($order['o_actual_payment'] > 0) {
                //不是余额支付
                if ($pay_type != 'money') {
                    $data['out_trade_no'] = $order['o_sn'] . $pay_type;
                    $data['total_fee']    = $order['o_actual_payment'];
                    $data['openid']       = $member_info['m_wx_openId'];
                    $data['body']         = "购买商品";
                    $result               = false;
                    if ($pay_type == "app") {
                        $result = $pay->wxAppPay($data);
                    } elseif ($pay_type == 'mini') {
                        $result = $pay->wxMiniPay($data);
                    } elseif ($pay_type == "ali") {
                        //支付宝支付
                        $result = $pay->aliAppPay($data);
                    }
                    //支付数据
                    $return['payData'] = $result;
                } else {
                    //扣除余额
                    $result = balanceLog($this->uid, $order['o_actual_payment'], -1, 2, '购买商品', $id);
                    if ($result == false) {
                        //是余额支付或者积分扣除事务回滚
                        db()->rollback();
                        $this->ajaxError('支付失败，请稍后重试');
                    }
                    //修改订单状态为已支付
                    $res = $pay->processingOrder($order['o_sn'], 3, $pay_type);
                    if ($res['code'] == 0) {
                        db()->rollback();
                        $this->ajaxError($res['msg']);
                    }
                    $return['payData'] = '余额支付';
                }
            } else {
                //修改订单状态为已支付
                $res = $pay->processingOrder($order['o_sn'], 3, $pay_type);
                if ($res['code'] == 0) {
                    db()->rollback();
                    $this->ajaxError($res['msg']);
                }
            }
//            if ($order['o_integral'] > 0) {
//                $result
//                    = integralLog($this->uid, $order['o_integral'] * $order['o_integral_bili'], -1, 2, '抵扣积分', '', $id);
//                if ($result == false) {
//                    db()->rollback();
//                    $this->ajaxError('支付失败，请稍后重试');
//                }
//                if ($pay_type == "money" && $order['o_actual_payment'] > 0) {
//                    //扣除余额
//                    $result = balanceLog($this->uid, $order['o_total'], -1, 2, '购买商品', $id);
//                    if ($result == false) {
//                        //是余额支付或者积分扣除事务回滚
//                        db()->rollback();
//                        $this->ajaxError('支付失败，请稍后重试');
//                    }
//                }
//            }
            db()->commit();
            $this->ajaxSuccess('支付数据获取成功', $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 确认订单
     * @throws Db\exception\ModelNotFoundException
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function confirmOrder()
    {
        if (request()->isPost()) {
            $id = input('post.id');
            if (empty($id)) {
                $this->ajaxError('参数错误');
            }
            $order = db('onlineorder')
                ->where(['o_id' => $id, 'o_status' => 3])
                ->find();
            if (empty($order)) {
                $this->ajaxError('订单不存在');
            }
//            $payment_password = input('post.payment_password');
//            if (empty($payment_password)) {
//                $this->ajaxError('请输入支付密码');
//            }
            //用户信息
            $member_info = db('member')
                ->where(['id' => $this->uid])
                ->find();
//            if (empty($member_info['m_payment_password'])) {
//                $this->ajaxError('请先设置支付密码');
//            }
//            //验证支付密码是否正确
//            if (!password_verify($payment_password, $member_info['m_payment_password'])) {
//                $this->ajaxError('支付密码不正确，请重新输入');
//            }
            db()->startTrans();
            $parent = db('member')->where(['id' => $member_info['m_fatherId']])->find();
            if ($parent && ($order['o_actual_payment'] - $order['o_freight']) > 0) {
                $integral = getSettings('integrals', 'integrals');
                $res
                          = integralLog($parent['id'], ($order['o_actual_payment'] - $order['o_freight']) * $integral, 1, 1, '下级购买商品', $member_info['id'], $id);
                if ($res == false) {
                    db()->rollback();
                    $this->ajaxSuccess('订单确认失败');
                }
            }
            $res = db('onlineorder')
                ->where(['o_id' => $id, 'o_status' => 3])
                ->update([
                    'o_status'  => 4,
                    'o_endtime' => now_datetime(),
                ]);
            if ($res == false) {
                db()->rollback();
                $this->ajaxSuccess('订单确认失败');
            }
            db()->commit();
            $this->ajaxSuccess('确认成功');
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 奖励明细
     */
    public function integralDetails()
    {
        if (request()->isPost()) {
            $page = input('post.page', 1);
            $list = db('balancelog')
                ->where(['b_mid' => $this->uid, 'b_type' => 1])
                ->order('b_id desc')
                ->page($page, 100)
                ->select();
            $this->ajaxSuccess('获取明细成功', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * @Notes:修改支付密码
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:46
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function dealPwd()
    {
        //手机号
        $account = input("account");
        //验证码
        $code = input("code");
        //判断手机号是否为空
        if (empty($account)) {
            $this->ajaxError("请输入手机号");
        }
        //判断验证码是否为空
        if (empty($code)) {
            $this->ajaxError("请输入验证码");
        }
        //新的支付密码
        $password = input("password");
        //确认支付密码
        $password1 = input("password1");
        //判断支付密码是否和旧密码一致
        $member = db("member")->where("m_account", $account)->find();
        if (password_verify($password, $member['m_payment_password'])) {
            $this->ajaxError("您输入的密码与原密码一致");
        }
        //判断密码是否为空
        if (empty($password)) {
            $this->ajaxError("请输入密码");
        }
        //密码必须为数字
        if (!isNumeric($password)) {
            $this->ajaxError("密码必须为数字");
        }
        //密码为6位
        if (strlen($password) != 6) {
            $this->ajaxError("密码长度为6位！");
        }
        //确认密码不能为空
        if (empty($password1)) {
            $this->ajaxError("请输入确认密码");
        }
        //两次密码必须一致
        if ($password != $password1) {
            $this->ajaxError("两次密码输入不一致");
        }
        //实例化短信接口
        $qt_sms = new SendSms();
        //判断验证码
        $res = $qt_sms->check($account, $code, "update_paypwd");
        //返回错误信息
        if ($res["code"] == 0) {
            $this->ajaxError($res["message"]);
        }
        //修改支付密码
        $update = db("member")
            ->where("m_account", $account)
            ->update(array("m_payment_password" => password_hash($password, PASSWORD_DEFAULT)));
        if ($update) {
            $this->ajaxSuccess("修改成功");
        } else {
            $this->ajaxError("修改失败");
        }
    }

    /**
     * @Notes:修改登录密码
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:46
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function updatePwd()
    {
        //用户id
        $uid = $this->getAccountId();
        //手机号
        $account = input("account");
        //验证码
        $code = input("code");
        //判断手机号是否为空
        if (empty($account)) {
            $this->ajaxError("请输入手机号");
        }
        //判断验证码是否为空
        if (empty($code)) {
            $this->ajaxError("请输入验证码");
        }
        //接收token
        $token = input("token");
        //新密码
        $pwd = input("pwd");
        //确认密码
        $pwd1 = input("pwd1");
        //判断密码是否为空
        if (empty($pwd)) {
            $this->ajaxError("请输入新密码");
        }
        //判断确认密码是否为空
        if (empty($pwd1)) {
            $this->ajaxError("请输入确认密码");
        }
        //判断密码格式
        if (strlen($pwd) < 6 || !isNumAndLetter($pwd)) {
            $this->ajaxError("请输入至少6位数字+字母的密码");
        }
        //判断两次密码是否一致
        if ($pwd != $pwd1) {
            $this->ajaxError("两次新密码密码输入不一致");
        }
        //实例化短信接口
        $qt_sms = new SendSms();
        //验证验证码
        $res = $qt_sms->check($account, $code, "update_pwd");
        //返回错误信息
        if ($res["code"] == 0) {
            $this->ajaxError($res["message"]);
        }
        //修改密码
        $res
            = db("member")->where(array("id" => $uid))->update(array("m_password" => password_hash($pwd, PASSWORD_DEFAULT)));
        if ($res != false) {
            //清除登陆信息
            $this->remove($token);

            $this->ajaxSuccess("密码修改成功");
        } else {
            $this->ajaxError("新旧密码不能一致");
        }
    }

    /**
     * @Notes:修改手机号
     * @Author:jsl
     * @Date: 2019/8/19
     * @Time: 16:46
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function updatePhone()
    {
        //新手机号
        $phone = input("phone");
        //验证码
        $code = input("code");
        //登录密码
        $password = input('password');
        $type     = input('type', 'app');
        //判断手机号是否为空
        if (empty($phone)) {
            $this->ajaxError("请输入新手机号");
        }
        //判断验证码是否为空
        if (empty($code)) {
            $this->ajaxError("请输入验证码");
        }
        if ($type != 'mini') {
            //判断验证码是否为空
            if (empty($password)) {
                $this->ajaxError("请输入登录密码");
            }
            //判断支付密码是否和旧密码一致
            $member = db("member")->where("id", $this->uid)->find();
            if (!password_verify($password, $member['m_password'])) {
                $this->ajaxError("您输入的密码与密码不一致");
            }
        }
        //实例化短信接口
        $qt_sms = new SendSms();
        //验证验证码
        $res = $qt_sms->check($phone, $code, "update_phone");
        //返回错误信息
        if ($res["code"] == 0) {
            $this->ajaxError($res["message"]);
        }
        //修改密码
        $res = db("member")->where(array("id" => $this->uid))->update(array("m_account" => $phone));
        if ($res != false) {
            $this->ajaxSuccess("手机号修改成功");
        } else {
            $this->ajaxError("新旧手机号不能一致");
        }
    }

    /**
     * 设置是否推送
     * @param Request $request
     */
    public function setPush(Request $request)
    {
        if ($request->isPost()) {
            $is_push = input('is_push');//是否推送1否2是
            db('member')->where(['id' => $this->uid])->setField('m_ispush', $is_push);
            $this->ajaxSuccess('设置成功');
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 用户反馈
     * @param Request $request
     */
    public function feedback(Request $request)
    {
        if ($request->isPost()) {
            $info = trim(htmlspecialchars(input('info')));//反馈内容
            if (empty($info)) {
                $this->ajaxError('请输入反馈内容');
            }
            $img              = input('img');//反馈图片
            $data['userid']   = $this->uid;
            $data['info']     = $info;
            $data['img']      = $img;
            $data['creatime'] = now_datetime();
            $res              = db('feedback')->insert($data);
            if ($res = false) {
                $this->ajaxError('反馈失败');
            }
            $this->ajaxSuccess('反馈成功');
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 账号注销
     * @param Request $request
     */
    public function cancellation(Request $request)
    {
        if ($request->isPost()) {
            $token = input('token');
            $res   = db('member')->where(['id' => $this->uid])->setField('m_isDisable', 1);
            if ($res = false) {
                $this->remove($token);
                $this->ajaxError('注销失败');
            }
            $this->ajaxSuccess('注销成功');
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 我的收藏列表
     * @param Request $request
     */
    public function myCollection(Request $request)
    {
        if ($request->isPost()) {
            $page = input('page', 1);
            $list = db('collection')
                ->alias('t')
                ->join('product p', 't.product_id=p.id')
                ->field('p.id,p.p_name,p_img,p_oldprice,p_sales')
                ->where(['t.member_id' => $this->uid, 'p.p_isDelete' => 2, 'p_isUp' => 2])
                ->order('id desc')
                ->page($page, 10)
                ->select();
            foreach ($list as $key => $value) {
                $list[$key]['p_img'] = saver() . $value['p_img'];
            }
            $this->ajaxSuccess('success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 我的推荐
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myRecommend(Request $request)
    {
        if ($request->isPost()) {
            $where      = " m_fatherId={$this->uid} ";
            $start_time = input('start_time');
            $end_time   = input('end_time');
            $k_time     = input('ktime');
            if ($k_time == 0) {
                if ($start_time || $end_time) {
                    // 起始日期
                    if ($start_time) {
                        $where .= " AND m_createTime>='" . $start_time . "'";
                    }
                    // 结束日期
                    if ($end_time) {
                        $where .= " AND m_createTime<='" . $end_time . " 23:59:59'";
                    }
                }
            } else {
                if ($k_time == 1) {
                    $where .= " AND m_createTime>='" . date("Y-m-d", strtotime("-7 day")) . "'";
                } else {
                    $where .= " AND m_createTime>='" . date("Y-m-d", strtotime("-1 month")) . "'";
                }
            }
            $member = db('member')->where($where)->select();
            //推荐人数
            $return['recommend']
                          = count($member);
            $integral_num = 0;
            foreach ($member as $value) {
                $integral_num = $integral_num + $value['m_integral_num'];
            }
            //积分总数
            $return['integral_num'] = number_format($integral_num, 2);
            //分页
            $page = input('page', 1);
            //是否采购
            $isbuy = input('isbuy', 2);
            if ($isbuy) {
                $where .= " and m_isbuy={$isbuy}";
            }
            $list           = db('member')
                ->where($where)
                ->page($page, 10)
                ->order('id desc')
                ->select();
            $return['list'] = $list;
            $this->ajaxSuccess('success', $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 客户订单
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function customerOrder(Request $request)
    {
        if ($request->isPost()) {
            $m_id = input('m_id');//客户id
            $page = input('page', 1);//分页默认1
            $list = db('onlineorder')
                ->where(['o_mid' => $m_id, 'o_isDelete' => 2])
                ->where('o_status', 'in', '2,3,4,5')
                ->order('o_id desc')
                ->page($page, 10)
                ->select();
            foreach ($list as $key => $value) {
                $list[$key]['order_detail'] = db('orderdetails')
                    ->where(['d_orderId' => $value['o_id']])
                    ->select();
                foreach ($list[$key]['order_detail'] as $k => $v) {
                    $list[$key]['order_detail'][$k]['d_img'] = saver() . $v['d_img'];
                }
            }
            $this->ajaxSuccess('success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 收款信息
     * @param Request $request
     */
    public function rechargeInfo(Request $request)
    {
        if ($request->isPost()) {
            $return                 = getSettings('recharge');
            $return['shoukuancode'] = saver() . $return['shoukuancode'];
            $this->ajaxSuccess('success', $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 充值
     * @param Request $request
     */
    public function recharge(Request $request)
    {
        if ($request->isPost()) {
            //充值金额
            $total = input('total');
//            if (empty($total)) {
//                $this->ajaxError('请输入充值金额');
//            }
            //充值类型1扫码充值2银行卡充值
            $type = input('type');
//            if (empty($type)) {
//                $this->ajaxError('请选择充值方式');
//            }
            //支付凭证
            $code = input('code');
            if (empty($code)) {
                $this->ajaxError('请上传支付凭证');
            }
//            $payment_password = input('post.payment_password');
//            if (empty($payment_password)) {
//
//                $this->ajaxError('请输入支付密码');
//            }
//            //用户信息
//            $member_info = db('member')
//                ->where(['id' => $this->uid])
//                ->find();
//            if (empty($member_info['m_payment_password'])) {
//                $this->ajaxError('请先设置支付密码');
//            }
//            //验证支付密码是否正确
//            if (!password_verify($payment_password, $member_info['m_payment_password'])) {
//                $this->ajaxError('支付密码不正确，请重新输入');
//            }

            $data = [
                'mid'        => $this->uid,
                'total'      => $total,
                'type'       => $type,
                'status'     => 1,
                'createTime' => now_datetime(),
                'code'       => $code,
            ];
            $res  = db('recharge')->insert($data);
            if ($res == false) {
                $this->ajaxError('提交失败');
            }
            $this->ajaxSuccess('提交成功，等待管理员审核');
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 充值列表
     */
    public function rechargeList()
    {
        $page = input('page', 1);
        $list = db('recharge')
            ->where(['mid' => $this->uid])
            ->order('id desc')
            ->page($page, 10)
            ->select();
        $this->ajaxSuccess('success', $list);
    }

    /**
     * 钱包记录
     * @param Request $request
     */
    public function myWallet(Request $request)
    {
        if ($request->isPost()) {
            $page = input('page', 1);
            $type = input('type', 1);
            $list = db('balancelog')
                ->where('b_mid', $this->uid)
                ->where('b_isplus', $type)
                ->page($page, 10)
                ->order('b_id desc')
                ->select();
            foreach ($list as $key => $value) {
                if ($value['b_type'] = 1) {
                    $r_type = db('recharge')->where(['id' => $value['b_oid']])->value('type');
                    if ($r_type == 1) {
                        $list[$key]['r_type'] = '扫码充值';
                    } else {
                        $list[$key]['r_type'] = '银行卡充值';
                    }
                } else {
                    $list[$key]['r_type'] = $value['b_info'];
                }
            }
            $this->ajaxSuccess('success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 钱包详情
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function walletInfo(Request $request)
    {
        if ($request->isPost()) {
            $id     = input('id');
            $return = db('balancelog')
                ->where('b_id', $id)
                ->find();
//            if ($return['b_type'] == 1) {
//                $r_type = db('recharge')->where(['id' => $return['b_oid']])->value('type');
//                if ($r_type == 1) {
//                    $return['r_type'] = '扫码充值';
//                } else {
//                    $return['r_type'] = '银行卡充值';
//                }
//            } else {
//                $return['r_type'] = '';
//            }
            $this->ajaxSuccess('success', $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 充值详情
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rechargeDetail(Request $request)
    {
        if ($request->isPost()) {

            $id             = input('id');
            $return         = db('recharge')
                ->where('id', $id)
                ->find();
            $return['code'] = saver() . $return['code'];
            $this->ajaxSuccess('success', $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 积分列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function integralList(Request $request)
    {
        if ($request->isPost()) {
            $page  = input('page', 1);
            $type  = input('type');
            $where = " t.b_mid={$this->uid} ";
            if ($type) {
                $where .= " and t.b_isplus={$type}";
            }
            $return = db('integral')
                ->alias('t')
                ->join('member m', 't.b_nid=m.id', 'left')
                ->where($where)
                ->page($page, 10)
                ->order('t.b_id desc')
                ->field('t.*,m.m_nickname,m.m_thumb')
                ->select();
            $this->ajaxSuccess('success', $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 订单列表
     * @param Request $request
     */
    public function myOrderList(Request $request)
    {
        if ($request->isPost()) {
            //状态 空 全部 1待支付2待发货3待收货4已完成5已评价6已取消7交易关闭
            $status = input('status');
            $page   = input('page', 1);
            $where  = "o_isDelete = 2 and o_mid={$this->uid}";
            if ($status) {
                $where .= " and o_status={$status}";
            }
            $order_list = db('onlineorder')
                ->where($where)
                ->order('o_id desc')
                ->page($page, 10)
                ->select();
            foreach ($order_list as $key => $value) {
                $order_list[$key]['order_detail'] = db('orderdetails')
                    ->where(['d_orderId' => $value['o_id']])
                    ->order('d_id desc')
                    ->select();
                foreach ($order_list[$key]['order_detail'] as $k => $v) {
                    $order_list[$key]['order_detail'][$k]['d_img'] = saver() . $v['d_img'];
                }
            }
            $this->ajaxSuccess('success', $order_list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 订单详情
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderDetails(Request $request)
    {
        if ($request->isPost()) {
            $o_id                  = input("o_id");
            $order                 = db('onlineorder')->where(['o_id' => $o_id])->find();
            $order['name_path']    = db('region')->where(['id' => $order['o_regionId']])->value('name_path');
            $order['order_detail'] = db('orderdetails')
                ->where(['d_orderId' => $order['o_id']])
                ->order('d_id desc')
                ->select();
            //待支付计算还有多长时间自动取消订单
            if ($order['o_status'] == 1) {
                $fromTime = strtotime(now_datetime());
                $toTime   = strtotime($order['o_unpaid']);
                //计算时间差
                $newTime                = $toTime - $fromTime;
                $order['o_unpaid_time'] = '';
                if (round($newTime / 86400) != 0) {
                    $order['o_unpaid_time'] .= round($newTime / 86400) . '天';
                }
                if (round($newTime % 86400 / 3600) != 0) {
                    $order['o_unpaid_time'] .= round($newTime % 86400 / 3600) . '小时';
                }
                if (round($newTime % 86400 % 3600 / 60) != 0) {
                    $order['o_unpaid_time'] .= round($newTime % 86400 % 3600 / 60) . '分钟';
                }
            } else {
                $order['o_unpaid_time'] = '';
            }
            foreach ($order['order_detail'] as $k => $v) {
                $order['order_detail'][$k]['r_id']  = db('refund')
                    ->where(['order_id' => $o_id, 'oid' => $v['d_id']])
                    ->order('id desc')
                    ->value('id');
                $order['order_detail'][$k]['d_img'] = saver() . $v['d_img'];
            }
            $this->ajaxSuccess('success', $order);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 查询物流
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function viewLogistics(Request $request)
    {
        if ($request->isPost()) {
            //订单id
            $o_id = input("o_id");
            //查询订单
            $return['order'] = db('onlineorder')->where(['o_id' => $o_id])->find();
            //查询省市县
            $return['order']['name_path']
                                             = db('region')->where(['id' => $return['order']['o_regionId']])->value('name_path');
            $return['order']['order_detail'] = db('orderdetails')
                ->where(['d_orderId' => $return['order']['o_id']])
                ->order('d_id desc')
                ->select();
            foreach ($return['order']['order_detail'] as $k => $v) {
                $return['order']['order_detail'][$k]['d_img'] = saver() . $v['d_img'];
            }
            //站点自提
            if ($return['order']['o_distribution_mode'] == 2) {
                $return['logistics'] = getSettings('storage_room');
            } elseif ($return['order']['o_distribution_mode'] == 1 || $return['order']['o_distribution_mode'] == 4) {
                //平台配送和物流配送
                $return['logistics'] = $return['order']['o_info'];
            } elseif ($return['order']['o_distribution_mode'] == 3) {
                //快递配送
                if ($return['order']['o_isexp'] == 1) {
                    //单物流单号
                    $express = db('express')->where(['id' => $return['order']['o_expressId']])->find();
                    //物流公司编码
                    $param['com'] = $express['code'];
                    //物流单号
                    $param['num'] = $return['order']['o_expresssn'];
                    //手机号
                    $param['phone'] = $return['order']['o_phone'];
                    //快递100查询
                    $data = $this->kuaidi($param);
                    if ($data['status'] == 200) {
                        $data['ename'] = db('express')
                            ->where('code', $data['com'])
                            ->value('name');
                    }
                    $return['logistics'] = $data;
                } else {
                    //多物流单号
                    $express   = json_decode($return['order']['o_expresssns'], true);
                    $logistics = [];
                    foreach ($express as $key => $value) {
                        $param['com']   = $value['express'];
                        $param['num']   = $value['expresssn'];
                        $param['phone'] = $return['order']['o_phone'];
                        $data           = $this->kuaidi($param);
                        if ($data['status'] == 200) {
                            $data['ename'] = db('express')
                                ->where('code', $data['com'])
                                ->value('name');
                        }
                        $logistics[$key] = $data;
                    }
                    $return['logistics'] = $logistics;
                }
            }
            $this->ajaxSuccess('success', $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 退款提交页面数据
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refundProduct(Request $request)
    {
        if ($request->isPost()) {
            //订单id
            $o_id = input('o_id');
            //订单详情id
            $d_id = input('d_id');
            //订单
            $order = db('onlineorder')->where(['o_id' => $o_id])->find();
            //订单详情
            $order_detail = db('orderdetails')
                ->where(['d_id' => $d_id])
                ->find();
            //退款金额
            $total = 0;
            //退款积分
            $integral  = 0;
            $integrals = $order['o_integral_bili'];
            //判断是否有积分抵扣
            if ($order['o_integral'] > 0) {
                //判断是否是全部抵扣
                if (($order['o_actual_payment'] - $order['o_freight']) > 0) {
                    //不是全部抵扣
                    $bili = $order_detail['d_total'] / $order['o_ptotal'];
                    //按照比例算出退款金额
                    $total = $bili * $order['o_actual_payment'];
                    //退款积分
                    $integral = $bili * $order['o_integral'] * $integrals;
                } else {
                    $bili = $order_detail['d_total'] / $order['o_ptotal'];
//                    halt($order_detail['d_total'].'-'.$order['o_ptotal']);
                    //退款积分
                    $integral = $bili * $order['o_integral'] * $integrals;
                }

            } else {
                //没有积分抵扣全部退商品价格
                $total = $order_detail['d_total'];
            }

            $order_detail['d_img']      = saver() . $order_detail['d_img'];
            $order_detail['r_total']    = $total;
            $order_detail['r_integral'] = $integral;
            $order_detail['r_channel']  = $order['o_payType'];
            $this->ajaxSuccess('success', $order_detail);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 提交退款
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function refund(Request $request)
    {
        if ($request->isPost()) {
            //退款原因
            $info = input('info');
            //图片
            $img = input('img');
            //联系电话
            $phone = input('phone');
            //订单id
            $o_id = input('o_id');
            //订单详情id
            $d_id = input('d_id');
            //方式
            $status = input('status');
            //订单
            $order = db('onlineorder')->where(['o_id' => $o_id])->find();
            //订单详情
            $order_detail = db('orderdetails')->where(['d_id' => $d_id])->find();
            //退款金额
            $total = 0;
            //退款积分
            $integral  = 0;
            $integrals = $order['o_integral_bili'];
            //判断是否有积分抵扣
            if ($order['o_integral'] > 0) {
                //判断是否是全部抵扣
                if (($order['o_actual_payment'] - $order['o_freight']) > 0) {
                    //不是全部抵扣
                    $bili = $order_detail['d_total'] / $order['o_ptotal'];
                    //按照比例算出退款金额
                    $total = $bili * $order['o_actual_payment'];
                    //退款积分
                    $integral = $bili * $order['o_integral'] * $integrals;
                } else {
                    $bili = $order_detail['d_total'] / $order['o_ptotal'];
//                    halt($order_detail['d_total'].'-'.$order['o_ptotal']);
                    //退款积分
                    $integral = $bili * $order['o_integral'] * $integrals;
                }

            } else {
                //没有积分抵扣全部退商品价格
                $total = $order_detail['d_total'];
            }
            $data = [
                'order_id'     => $o_id,
                'oid'          => $d_id,
                'status'       => $status,
                'total'        => $total,
                'mid'          => $this->uid,
                'info'         => $info,
                'certificate'  => $img,
                'createtime'   => now_datetime(),
                'refundReview' => 1,
                'sn'           => "500" . make_order_sn(),
                'integral'     => $integral,
                'refund_type'  => $order['o_payType'],
                'rephone'      => $phone,
            ];
            db()->startTrans();
            try {
                //将订单详情里面改为提交退款
                $res = db('orderdetails')->where(['d_id' => $d_id])->setField('d_refund', 2);
                if ($res === false) {
                    db()->rollback();
                    $this->ajaxError('更改订单状态失败');
                }
                //新增退款记录
                $add = db('refund')->insert($data);
                if ($add === false) {
                    db()->rollback();
                    $this->ajaxError('提交退款失败');
                }
                db()->commit();
                $this->ajaxSuccess('提交退款成功');
            } catch (\Exception $e) {
                db()->rollback();
                $this->ajaxError($e->getMessage());
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 退款/售后列表
     * @param Request $request
     */
    public function refundList(Request $request)
    {
        if ($request->isPost()) {
            $page        = input('page', 1);
            $return_list = db('refund')
                ->alias('t')
                ->join('orderdetails o', 'o.d_id=t.oid')
                ->order('t.id desc')
                ->where('t.isDelete=2 and mid=' . $this->uid)
                ->page($page, 10)
                ->select();
            foreach ($return_list as $key => $value) {
                $return_list[$key]['d_img'] = saver() . $value['d_img'];
            }
            $this->ajaxSuccess('success', $return_list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 删除退款订单
     * @param Request $request
     */
    public function delRefund(Request $request)
    {
        if ($request->isPost()) {
            $id  = input('id');
            $res = db('refund')
                ->where(['id' => $id])
                ->setField('isDelete', 1);
            if ($res == false) {
                $this->ajaxError('删除失败');
            }
            $this->ajaxSuccess('success');
        } else {
            $this->ajaxError('未知的请求方式');
        }
    }

    /**
     * 退款详情
     * @param Request $request
     */
    public function refundDetail(Request $request)
    {
        if ($request->isPost()) {
            $id                     = input('id');
            $refund_detail          = db('refund')
                ->alias('t')
                ->join('orderdetails o', 'o.d_id=t.oid')
                ->where(['t.id' => $id])
                ->find();
            $refund_detail['d_img'] = saver() . $refund_detail['d_img'];
            $this->ajaxSuccess('success', $refund_detail);
        } else {
            $this->ajaxError('未知的请求方式');
        }
    }

    /**
     * 物流公司
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function expressCode()
    {
        $list = db('express')
            ->select();
        $this->ajaxSuccess('success', $list);
    }

    /**
     * 提交退款物流信息
     * @param Request $request
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function refundSn(Request $request)
    {
        if ($request->isPost()) {
            $id              = input('id');
            $param           = input('param');
            $data['express'] = $param;
            $res             = db('refund')->where(['id' => $id])->update($data);
            if ($res == false) {
                $this->ajaxError('提交失败');
            }
            $this->ajaxSuccess('提交成功');
        } else {
            $this->ajaxError('未知的请求方式');
        }
    }

    /**
     * 搜索历史
     */
    public function historySearch()
    {
        $list = db('history_search')
            ->where(['mid' => $this->uid])
            ->order('time desc')
            ->page(1, 10)
            ->select();
        $this->ajaxSuccess('success', $list);
    }

    /**
     * 删除历史记录
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function emptyHistorySearch(Request $request)
    {
        if ($request->isPost()) {
            $res = db('history_search')
                ->where(['mid' => $this->uid])
                ->delete();
            if ($res == false) {
                $this->ajaxError('删除失败');
            } else {
                $this->ajaxSuccess('删除成功');
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**'
     * 消息列表
     * @param Request $request
     */
    public function news(Request $request)
    {
        if ($request->isPost()) {
            $type = input('type', 1);
            $page = input('page', 1);
            $list = db('message')
                ->where(['category' => $type, 'to' => $this->uid])
                ->page($page, 10)
                ->order('id desc')
                ->select();
            $this->ajaxSuccess('success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 已读消息
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function newRead(Request $request)
    {
        if ($request->isPost()) {
            $id      = input('id');
            $message = db('message')
                ->where(['id' => $id])
                ->find();
            if ($message['category'] == 1) {
                db('message')
                    ->where(['id' => $id])
                    ->setField('is_read', 1);
            } else {
                db('membermessages')
                    ->insert(['member_id' => $this->uid, 'message_id' => $id]);
            }
            $this->ajaxSuccess('success');

        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 推广
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function extension()
    {
        if (request()->isPost()) {
            vendor("phpqrcode.phpqrcode");
            $id = $this->getAccountId();
            $re = Db::table('member')
                ->where('id', $id)
                ->field('m_invitation_code,m_level')
                ->find();
//            if ($re['m_level'] == 1) {
//                $this->ajaxError('请去购买商品或者升级代理');
//            }
//            $order = db('onlineorder')->where('o_mid=' . $id . ' and (o_status=2 or o_status=3 or o_status = 5)')->select();
//            if (empty($order)) {
//                $this->ajaxError('请去购买商品或者升级代理');
//            }
            $invitecode = $re['m_invitation_code'];
            $data       = "http://{$_SERVER["HTTP_HOST"]}/#/registered?invter={$invitecode}";//二维码内容
            $new_file   = ROOT_PATH . "public/static/qrcode/{$invitecode}.jpg";
            $level      = 'L';
            $size       = 4;
            $QRcode     = new \QRcode();
            ob_start();
            $QRcode->png($data, $new_file, $level, $size, 2);
            ob_end_clean();
            $url            = "/static/qrcode/{$invitecode}.jpg";
            $data           = array();
            $data["url"]    = $url;
            $data["invter"] = $re["m_invitation_code"];
            $this->ajaxSuccess('', $data);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 我的好友
     */
    public function myTeam()
    {
        if (request()->isPost()) {
            $page = input('post.page', 1);
            $list = db('member')
                ->where(['m_fatherId' => $this->uid])
                ->field('m_nickname,m_thumb,m_createTime')
                ->page($page, 10)
                ->select();
            $this->ajaxSuccess('', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    public function express()
    {
        $id      = input('id');
        $order   = db('onlineorder')->where(['o_id' => $id])->find();
        $express = db('express')->where(['id' => $order['o_expressId']])->find();
        $url
                 = "https://m.kuaidi100.com/app/query/?com=" . $express["code"] . "&nu=" . $order["o_expresssn"] . "&coname=" . $order['o_id'] . "&callbackurl=http://{$_SERVER['HTTP_HOST']}/index/Timer/Orders";
        $this->ajaxSuccess('', $url);
    }

    /**
     * 我的排队
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myLineUp()
    {
        if (request()->isPost()) {
            $queues       = db('queues')->where(['member_id' => $this->uid, 'is_end' => 2])->limit(4)->select();
            $queues_count = db('queues')->where(['member_id' => $this->uid, 'is_end' => 2])->count();
            if (empty($queues)) {
                $return['info'] = '暂无排队';
            } else {
                $return['info'] = '排队中';
            }

            $queues_all           = db('queues')->where(['is_end' => 2])->count();
            $return['queues_all'] = 0;
            if ($queues_all !== '') {
                $return['queues_all'] = $queues_all;
            }
            $return['queues_ones'] = [];
            if ($queues) {
                foreach ($queues as $key => $value) {
                    $return['queues_ones'][$key] = $value['id'];
                }
                if ($queues_count > 6) {
                    $return['queues_ones'][4] = '.';
                    $return['queues_ones'][5] = '.';
                    $return['queues_ones'][6] = '.';
                }
            }
            $return['queues_list']  = db('queues')->where(['member_id' => $this->uid])->select();
            $return['queues_over']  = db('queues')->where(['is_end' => 1])->count();
            $total                  = getSettings('product_price', 'product_price');
            $return['queues_overs'] = number_format($return['queues_over'] * $total, 2, '.', '');
            $this->ajaxSuccess('', $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    public function rexian()
    {
        $data = getSettings('quanguo_rexian', 'quanguo_rexian');
        $this->ajaxSuccess('', $data);
    }

    /**
     * 提现页面数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdrawInfo()
    {
        if (request()->isPost()) {
            $member                  = db('member')->where(['id' => $this->uid])->find();
            $return['total']         = $member['m_total'];
            $return['withdraw_min']  = getSettings('withdraw', 'withdraw_min');
            $return['withdraw_bili'] = getSettings('withdraw', 'withdraw_bili');
            $this->ajaxSuccess("", $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 提现
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdraw()
    {
        if (request()->isPost()) {
            $member          = db('member')->where(['id' => $this->uid])->find();
            $return['total'] = $member['m_total'];
            $withdraw_min    = getSettings('withdraw', 'withdraw_min');
            $withdraw_bili   = getSettings('withdraw', 'withdraw_bili');
            $total           = input('post.total');
            if ($member['m_isfrone'] == 1) {
                $this->ajaxError('您的账号已被冻结，请联系管理员');
            }
            if (empty($member['m_bankcard'])) {
                $this->ajaxError('请先去绑定银行卡');
            }
            if (empty($total)) {
                $this->ajaxError('请输提现金额');
            }
            if ($total <= 0) {
                $this->ajaxError('请输正确的提现金额');
            }
            if ($total < $withdraw_min) {
                $this->ajaxError('提现金额不能低于' . $withdraw_min);
            }
            $data = [
                'member_id'      => $this->uid,
                'account'        => $member['m_account'],
                'status'         => 1,
                'apply_money'    => $total - ($total * ($withdraw_bili / 100)),
                'apply_poundage' => $total * ($withdraw_bili / 100),
                'apply_balance'  => $total,
                'createtime'     => now_datetime(),
                'updatetime'     => now_datetime(),
                'card_code'      => $member['m_bankcard'],
                'card_holder'    => $member['m_holder'],
                'bank_name'      => $member['m_bankname'],
            ];
            db()->startTrans();
            try {
                $res = db('withdraw')->insert($data);
                if ($res == false) {
                    db()->rollback();
                    $this->ajaxError('提现失败');
                }
                $res1 = balanceLog($this->uid, $total, -1, 1, '提现');
                if ($res1 == false) {
                    db()->rollback();
                    $this->ajaxError('提现失败');
                }
                db()->commit();
                $this->ajaxSuccess('恭喜你成功提现' . $total);
            } catch (\Exception $exception) {
                db()->rollback();
                $this->ajaxError($exception->getMessage());
            }
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 设置银行卡
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function bindBankCard()
    {
        if (request()->isPost()) {
            $data = request()->param();
            if (empty($data['bankcard'])) {
                $this->ajaxError('请输入银行卡号');
            }
            if (!isBankCard($data['bankcard'])) {
                $this->ajaxError('请输入正确的银行卡号');
            }
            if (empty($data['holder'])) {
                $this->ajaxError('请输入开户人姓名');
            }
            if (empty($data['bankname'])) {
                $this->ajaxError('请输入开户行');
            }
            $add = [
                'm_bankcard'   => $data['bankcard'],
                'm_holder'     => $data['holder'],
                'm_bankname'   => $data['bankname'],
                'm_updateTime' => now_datetime()
            ];
            $res = db('member')->where(['id' => $this->uid])->update($add);
            if ($res == false) {
                $this->ajaxError('保存失败');
            }
            $this->ajaxSuccess('保存成功');
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 申请区域代理
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function apply()
    {
        if (request()->isPost()) {
            $data = request()->param();
            if (empty($data['name'])) {
                $this->ajaxError('请输入代理人姓名');
            }
            if (empty($data['phone'])) {
                $this->ajaxError('请输入联系电话');
            }
            if (empty($data['regionId'])) {
                $this->ajaxError('请选择代理区域');
            }
            if (empty($data['info'])) {
                $this->ajaxError('请输入申请理由');
            }
            $aa = db('agency')->where(['regionId' => $data['regionId']])->find();
            if ($aa) {
                $this->ajaxError('此区域已有代理');
            }
            $region = db('region')->where(['id' => $data['regionId']])->find();
            if (empty($region)) {
                $this->ajaxError('你选择的区域不存在');
            }
            $bb = db('apply')->where(['regionId' => $data['regionId'], 'status' => ['in', [1, 2]]])->find();
            if ($bb) {
                $this->ajaxError('此区域已有人申请');
            }
            $add = [
                'name'       => $data['name'],
                'phone'      => $data['phone'],
                'regionId'   => $data['regionId'],
                'info'       => $data['info'],
                'createTime' => now_datetime(),
                'memberid'   => $this->uid,
                'status'     => 1,
            ];
            $res = db('apply')->insert($add);
            if ($res == false) {
                $this->ajaxError('申请失败');
            }
            $this->ajaxSuccess('申请成功');
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 提现记录
     * @param Request $request
     */
    public function withdrawInfos(Request $request)
    {
        if ($request->isPost()) {
            $page = input('post.page', 1);
            //提现状态：1申请中，2成功，3拒绝
            $status = input('post.status');
            $where  = "member_id=" . $this->uid;
            if ($status) {
                $where .= " and status=" . $status;
            }
            $list = db('withdraw')
                ->where($where)
                ->page($page, 10)
                ->order('id desc')
                ->select();
            $this->ajaxSuccess('Success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 收益说明
     * @param Request $request
     */
    public function shouyi(Request $request)
    {
        if ($request->isPost()) {
            $data = getSettings('shouyi', 'shouyi');
            $this->ajaxSuccess('Success', $data);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }
}