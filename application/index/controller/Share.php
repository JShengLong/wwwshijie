<?php

namespace app\index\controller;

use push\Push;
use think\Request;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/6/13
 * Time: 18:33
 */
class Share extends Base
{
    /**
     * 商品详情
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function productDetail(Request $request)
    {
        if ($request->isPost()) {
            //商品id
            $p_id = input('post.p_id');
            if (empty($p_id)) {
                $this->ajaxError('商品id为空');
            }
            //商品详情
            $product_detail = db('product')
                ->where('id', $p_id)
                ->find();
            //判断是否下架或者删除
            if ($product_detail['p_isDelete'] == 1 || $product_detail['p_isUp'] == 1) {
                $this->ajaxError('该商品已下架或者已删除');
            }
            //将字符串转换成数组
            $imgs = explode(',', $product_detail['p_imgs']);
            if ($imgs) {
                //给图片加域名
                foreach ($imgs as $key => $value) {
                    $imgs[$key] = saver() . $value;
                }
            }else{
                $imgs=[saver().'/uploads/images/aa.jpg'];
            }
            $product_detail['p_img'] = saver() . $product_detail['p_img'];
//            $product_detail['p_video'] = $product_detail['p_video'] != "" ? saver() . $product_detail['p_video'] : "";
            $product_detail['p_imgs'] = $imgs;
            //将数组中的null值换成空字符串
            foreach ($product_detail as $key => $value) {
                if ($value === null) {
                    $product_detail[$key] = '';
                }
            }
            $product_detail['sku']['sku_key']   = db('fa_item_attr_key')
                ->where(['item_id' => $p_id])
                ->find();
            $product_detail['sku']['sku_value'] = db('fa_item_attr_val')
                ->where(['item_id' => $p_id, 'attr_key_id' => $product_detail['sku']['sku_key']['attr_key_id']])
                ->select();

            $product_detail['sku']['sku_data'] = db('fa_item_sku')
                ->where(['item_id' => $p_id])
                ->select();

            foreach ($product_detail['sku']['sku_data'] as $key => $value) {
                $product_detail['sku']['sku_data'][$key]['ladder'] = db('ladder')
                    ->where(['sku_id' => $value['sku_id']])
                    ->order('sort asc')
                    ->select();
            }
            //评价数量
            $product_detail['comment_num'] = db('comment')
                ->alias('t')
                ->join('member m', 't.account=m.m_account')
                ->where(['product_id' => $p_id])
                ->where('is_show=1')
                ->count();
            //评价的信息
            $product_detail['comment_list'] = db('comment')
                ->alias('t')
                ->join('member m', 't.account=m.m_account')
                ->field('t.*,m.m_nickname,m.m_thumb')
                ->where('product_id', $p_id)
                ->where('is_show=1')
                ->order('id desc')
                ->find();
            if ($product_detail['comment_list']) {
                $product_detail['comment_list']['stars'] = 5 - $product_detail['comment_list']['star'];
                $arr1                                    = [];
                for ($i = 0; $i < $product_detail['comment_list']['star']; $i++) {
                    $arr1[$i] = $i;
                }
                $arr2 = [];
                for ($i = 0; $i < $product_detail['comment_list']['stars']; $i++) {
                    $arr2[$i] = $i;
                }
                $product_detail['comment_list']['star_arr1'] = $arr1;
                $product_detail['comment_list']['star_arr2'] = $arr2;
                //将评价者头像展示
//                    $product_detail['comment_list']['m_thumb'] = saver() . $product_detail['comment_list']['m_thumb'];
                //将字符串转换成数组
                if ($product_detail['comment_list']['img'] == '') {
                    $comment_imgs = explode(',', $product_detail['comment_list']['img']);
                    if ($comment_imgs == '') {
                        //给图片加域名
                        foreach ($comment_imgs as $key => $value) {
                            $comment_imgs[$key] = saver() . $value;
                        }
                        $product_detail['comment_list']['img'] = $comment_imgs;
                    } else {
                        $product_detail['comment_list']['img'] = [];
                    }
                } else {
                    $product_detail['comment_list']['img'] = [];
                }
            }
            $params['url']            = "http://www.baidu.com";
            $product_detail['qrcode'] = $this->phpqrcode($params);
            $this->ajaxSuccess('Success', $product_detail);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    public function push()
    {
        $message_template = messageTemplate(2);
        //用户信息
        //极光推送
        $push = new Push();
        //send_order类型为发货  id是当前订单的id
        $extras = ['type' => 'recharge', 'id' => 31];
        //发送通知
        $res= $push->send_to_one('13902255742', $message_template['alert'], $message_template['title'], $extras);
        halt($res);


    }
}