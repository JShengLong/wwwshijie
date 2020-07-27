<?php

namespace app\mini\controller;

use think\Request;

class Product extends Base
{
    /**
     * 微信分享海報
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUnlimited()
    {
        $appid     = getSettings('wx_pay', 'miniapp_id');
        $appsecret = getSettings('wx_pay', 'mini_secret');
        $get_token_url
                   = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
        $res       = $this->httpGet($get_token_url);
        $json_obj  = json_decode($res, true);
//        $post_data['scene']        = "?p_id=" . input('id');
        $post_data['width']        = 280;
        $post_data['path'] = 'pages/details/details?p_id=' . input('id');
        $url
                           = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token={$json_obj['access_token']}";
        $res               = $this->httpPost($url, json_encode($post_data, JSON_UNESCAPED_SLASHES));
        $file              = './static/product/' . input('id') . '.png';
        file_put_contents($file, $res);
        $product        = db('product')
            ->where(['id' => input('id')])
            ->find();
        $param['id']   = $product['id'];
        $param['img']   = $product['p_img'];
        $param['name']  = $product['p_name'];
        $param['price'] = $product['p_oldprice'];
        $param['code']  = $file;
        $this->main($param);
        $this->ajaxSuccess('success',saver()."/static/newposter/{$param['id']}.png");
    }

    /**
     * @Notes:合成分享海报
     * @Author:jsl
     * @Date: 2019/9/11
     * @Time: 10:48
     * @param $array
     */
    public function main($array)
    {
        $name       = $array['name'];//商品名称
        $money      = "￥ " . $array["price"];//商品价格
        $erweimaurl = $array["code"];//二维码地址
        list($wqrcode, $hqrcode) = getimagesize($erweimaurl);//解析二维码宽和高
        $erweimaurl = imagecreatefromjpeg($erweimaurl);//解析二维码图片信息
        $logourl    = '.'.$array["img"];//商品图片
        list($wlogo, $hlogo) = getimagesize($logourl);//解析商品图片的宽和高
        //商品图片的信息
        $ext = pathinfo($logourl);
        //判断商品图片的后缀名
        switch ($ext['extension']) {
            case 'jpg':
                //jpg解析
                $logourl = imagecreatefromjpeg($logourl);
                break;
            case "png":
                //png解析
                $logourl = imagecreatefrompng($logourl);
                break;
            case "jpeg":
                //jpeg解析
                $logourl = imagecreatefromjpeg($logourl);
                break;
            case "gif":
                //GIF解析
                $logourl = imagecreatefromgif($logourl);
                break;
        }
        //商品图片的信息
//        $logourl = imagecreatefromjpeg($logourl);
//        //创建画布
        $ii=522;
        $oo=716;
        $image_3 = imageCreatetruecolor($ii, $oo);
//        //给画布上色
        //白色
        $color = imagecolorallocate($image_3, 250, 250, 250);
        imagefill($image_3, 0, 0, $color);
        imageColorTransparent($image_3, $color);
        // 字体颜色
        $fontfile  = './static/ttf/Light.ttf'; // 字体文件
        $fontfile1 = './static/ttf/PingFang Regular.ttf'; // 字体文件
        $fontfile2 = './static/ttf/PingFang Medium.ttf'; // 字体文件
        $name = mb_strimwidth($name, 0, 20, "...", "UTF-8"); //限制标题字符数
        //
        $name1 = imagecolorallocate($image_3, 0, 0, 0);
        imagettftext($image_3, 20, 0, 10, 480, $name1, $fontfile1, $name);
        //金额位置
        $money1 = imagecolorallocate($image_3, 250, 10, 10);
        imagettftext($image_3, 30, 0, 10, 600 , $money1, $fontfile2, $money);
        // logo 位置
        imagecopyresampled($image_3, $logourl, 0, 0, 0, 0, $ii, 420, $wlogo, $hlogo);
        // 商品图位置
        // 二维码位置
        imagecopyresampled($image_3, $erweimaurl, 300, 500, 0, 0, $wqrcode*0.6, $hqrcode*0.6, $wqrcode, $hqrcode);
        // 缩小图片
        $image_p = imagecreatetruecolor($ii,$oo);
        imagecopyresampled($image_p, $image_3, 0, 0, 0, 0, $ii, $oo, $ii, $oo);
        // 生成图片
        imagepng($image_p, "./static/newposter/{$array['id']}.png");
    }

    /**
     * 商品列表
     */
    public function productList()
    {
        if (request()->isPost()) {
            //页数
            $page = input('post.page', 1);
            //一级分类id
            $category_id = input('post.category_id');
            //搜索名称
            $p_name = input('post.p_name');
            //生成搜索条件
            $where = "t.p_isUp = 2  and p_isDelete=2";
            //分类id不为空
            if (!empty($category_id)) {
                $where .= ' and (t.category1 =' . $category_id . " or p_category_id=" . $category_id . ")";
            }
            //名称不为空
            if (!empty($p_name)) {
                if ($this->isLogin()) {
                    $his = db('history_search')
                        ->where(['content' => $p_name, 'mid' => $this->mid])
                        ->find();
                    if ($his) {
                        db('history_search')
                            ->where(['id' => $his['id']])
                            ->setField('time', now_datetime());
                    } else {
                        db('history_search')->insert([
                                                         'mid'     => $this->mid,
                                                         'content' => $p_name,
                                                         'time'    => now_datetime(),
                                                     ]);
                    }
                }

                $where .= " and t.p_name like '%" . $p_name . "%'";
            }
            //销量
            $sales = input('sales');
            //价格
            $price = input('price');
            //生成排序条件
            $sort = "p_sort asc";
            if ($sales) {
                if ($sales == 1) {
                    //销量从少到多排序
                    $sort = "p_sales asc";
                } else {
                    //销量从多到少排序
                    $sort = "p_sales desc";
                }
            }
            if ($price) {
                if ($price == 1) {
                    //价格从少到多排序
                    $sort = "p_oldprice asc";
                } else {
                    //价格从多到少排序
                    $sort = "p_oldprice desc";
                }
            }
            $list = db('product')
                ->alias('t')
                ->where($where)
                ->order($sort)
                ->field('t.id,p_name,p_img,p_oldprice,p_sales')
                ->page($page, 10)
                ->select();

            foreach ($list as $key => $value) {
                $list[$key]['p_img'] = saver() . $value['p_img'];
            }
            $this->ajaxSuccess('Success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

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
            }
            $product_detail['p_img']   = saver() . $product_detail['p_img'];
            $product_detail['p_imgs']  = $imgs;
            //将数组中的null值换成空字符串
            foreach ($product_detail as $key => $value) {
                if ($value === null) {
                    $product_detail[$key] = '';
                }
            }
            //sku
            $product_detail['sku'] = db('fa_item_attr_key')
                ->where(['item_id' => $p_id])
                ->select();
            foreach ($product_detail['sku'] as $key => $value) {
                $product_detail['sku'][$key]['fa_item_attr_val'] = db('fa_item_attr_val')
                    ->where(['attr_key_id' => $value['attr_key_id']])
                    ->select();

                $product_detail['fa_item_attr_val'][$key] = $product_detail['sku'][$key]['fa_item_attr_val'];
                foreach ($product_detail['fa_item_attr_val'][$key] as $k => $v) {
                    $product_detail['fa_item_attr_val'][$key][$k]['attr_name'] = $value['attr_name'];
                    if ($k == 0) {
                        $product_detail['fa_item_attr_val'][$key][$k]['is_click'] = 0;
                    } else {
                        $product_detail['fa_item_attr_val'][$key][$k]['is_click'] = 1;
                    }
                }
//                $product_detail['fa_item_attr_val'][$key]['attr_name']=
            }
            //sku参数
            $product_detail['sku_data'] = db('fa_item_sku')
                ->where(['item_id' => $p_id])
                ->select();
            foreach ($product_detail['sku_data'] as $key => $value) {
                $product_detail['sku_data'][$key]['itemss'] = $value['attr_symbol_path'];
            }
//            dump($product_detail['sku_data']);die;
            //规格阶梯价格
            foreach ($product_detail['sku_data'] as $key => $value) {
                $product_detail['sku_data'][$key]['ladder'] = db('ladder')
                    ->where(['sku_id' => $value['sku_id']])
                    ->order('sort asc')
                    ->select();
            }
            $this->isLogin();
            //是否收藏
            $product_detail['is_collection'] = db('collection')
                ->where(['product_id' => $p_id, 'member_id' => $this->mid])
                ->find();
            if ($product_detail['is_collection']) {
                $product_detail['is_collection'] = 2;
            } else {
                $product_detail['is_collection'] = 1;
            }
            //评价数量
            if ($this->isLogin()) {
                $member                        = db('member')->where(['id' => $this->mid])->find();
                $product_detail['comment_num'] = db('comment')
                    ->alias('t')
                    ->join('member m', 't.account=m.m_account')
                    ->where(['product_id' => $p_id])
                    ->where('account=' . $member['m_account'] . ' or is_show=1')
                    ->count();
            } else {
                $product_detail['comment_num'] = db('comment')
                    ->alias('t')
                    ->join('member m', 't.account=m.m_account')
                    ->where(['product_id' => $p_id])
                    ->where('is_show=1')
                    ->count();
//                $product_detail['comment_num'] = 0;
            }

            if ($this->isLogin()) {
                $member = db('member')->where(['id' => $this->mid])->find();
                //评价的信息
                $product_detail['comment_list'] = db('comment')
                    ->alias('t')
                    ->join('member m', 't.account=m.m_account')
                    ->field('t.*,m.m_nickname,m.m_thumb')
                    ->where('product_id', $p_id)
                    ->where('account=' . $member['m_account'] . ' or is_show=1')
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
                    if ($product_detail['comment_list']['img']) {
                        $comment_imgs = explode(',', $product_detail['comment_list']['img']);
                        if ($comment_imgs) {
                            //给图片加域名
                            foreach ($comment_imgs as $key => $value) {
                                $comment_imgs[$key] = saver() . $value;
                            }
                            $product_detail['comment_list']['img'] = $comment_imgs;
                        } else {
                            $product_detail['comment_list']['img'] = [];
                        }
                    }
                }
            } else {
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
                    if ($product_detail['comment_list']['img']) {
                        $comment_imgs = explode(',', $product_detail['comment_list']['img']);
                        if ($comment_imgs) {
                            //给图片加域名
                            foreach ($comment_imgs as $key => $value) {
                                $comment_imgs[$key] = saver() . $value;
                            }
                            $product_detail['comment_list']['img'] = $comment_imgs;
                        } else {
                            $product_detail['comment_list']['img'] = [];
                        }
                    }
                }
//                $product_detail['comment_list'] = null;
            }
            $params['url']            = "http://www.baidu.com";
            $product_detail['qrcode'] = $this->phpqrcode($params);
            $this->ajaxSuccess('Success', $product_detail);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 收藏和取消收藏
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function collection(Request $request)
    {
        if ($request->isPost()) {
            //商品id
            $p_id = input('post.p_id');
            $this->isLogin();
            $res = db('collection')
                ->where(['product_id' => $p_id, 'member_id' => $this->mid])
                ->find();
            if ($res) {
                db('collection')->where(['id' => $res['id']])->delete();
            } else {
                $data['product_id'] = $p_id;
                $data['member_id']  = $this->mid;
                $data['createtime'] = now_datetime();
                $data['is_del']     = 2;
                db('collection')->insert($data);
            }
            $this->ajaxSuccess('Success');
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 商品评价列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function productComment(Request $request)
    {
        if ($request->isPost()) {
            $p_id   = input('p_id');
            $page   = input('page');
            if(!$this->isLogin()){
                $list   = db('comment')
                    ->alias('t')
                    ->join('member m', 'm.m_account=t.account')
                    ->where(['t.product_id' => $p_id])
                    ->where('is_show=1')
                    ->order('t.id desc')
                    ->field('t.*,m.m_nickname,m.m_thumb')
                    ->page($page, 10)
                    ->select();
                foreach ($list as $key => $value) {
                    $star = [];
                    for ($i = 0; $i < $value['star']; $i++) {
                        $star[$i] = 1;
                    }
                    $stars = [];
                    for ($i = 0; $i < 5 - $value['star']; $i++) {
                        $stars[$i] = 1;
                    }
                    $list[$key]['star']    = $star;
                    $list[$key]['stars']   = $stars;
//                    $list[$key]['m_thumb'] = saver() . $value['m_thumb'];
                    if ($value['img']) {
                        $img  = explode(',', $value['img']);
                        $imga = [];
                        foreach ($img as $k => $v) {
                            $imga[$k] = saver() . $v;
                        }
                        $list[$key]['img'] = $imga;
                    } else {
                        $list[$key]['img'] = [];
                    }
                }
            }else{
                $member=db('member')->where(['id'=>$this->mid])->find();
                $list   = db('comment')
                    ->alias('t')
                    ->join('member m', 'm.m_account=t.account')
                    ->where(['t.product_id' => $p_id])
                    ->where('account=' . $member['m_account'] . ' or is_show=1')
                    ->order('t.id desc')
                    ->field('t.*,m.m_nickname,m.m_thumb')
                    ->page($page, 10)
                    ->select();
                foreach ($list as $key => $value) {
                    $star = [];
                    for ($i = 0; $i < $value['star']; $i++) {
                        $star[$i] = 1;
                    }
                    $stars = [];
                    for ($i = 0; $i < 5 - $value['star']; $i++) {
                        $stars[$i] = 1;
                    }
                    $list[$key]['star']    = $star;
                    $list[$key]['stars']   = $stars;
//                    $list[$key]['m_thumb'] = saver() . $value['m_thumb'];
                    if ($value['img']) {
                        $img  = explode(',', $value['img']);
                        $imga = [];
                        foreach ($img as $k => $v) {
                            $imga[$k] = saver() . $v;
                        }
                        $list[$key]['img'] = $imga;
                    } else {
                        $list[$key]['img'] = [];
                    }
                }
            }
            $this->ajaxSuccess('success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    public function comment(Request $request)
    {
        if ($request->isPost()) {
            $this->isLogin();
            $info   = input('info');
            $p_ids  = input('p_ids');
            $text   = input('text');
            $imgs   = input('imgs');
            $sn     = input('sn');
            $sku    = input('sku');
            $num    = input('num');
            $info   = json_decode($info, true);
            $p_ids  = json_decode($p_ids, true);
            $text   = json_decode($text, true);
            $imgs   = json_decode($imgs, true);
            $sn     = json_decode($sn, true);
            $sku    = json_decode($sku, true);
            $num    = json_decode($num, true);
            $member = db('member')->where(['id' => $this->mid])->find();
            $res    = [1];
            db()->startTrans();
            foreach ($p_ids as $key => $value) {
                $add   = [
                    'comment'    => $text[$key],
                    'img'        => implode(',', $imgs[$key]),
                    'star'       => $info[$key],
                    'createtime' => now_datetime(),
                    'account'    => $member['m_account'],
                    'product_id' => $value,
                    'order_sn'   => $sn[$key],
                    'is_show'    => 1,
                    'sku'        => $sku[$key],
                    'num'        => $num[$key],
                ];
                $res[] = db('comment')->insert($add);
            }
            $res[] = db('onlineorder')->where(['o_sn' => $sn[0]])->update(['o_status' => 5]);
            if (in_array(0, $res)) {
                db()->rollback();
                $this->ajaxError('发布失败');
            }
            db()->commit();
            $this->ajaxSuccess('发布成功');
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }
    /**
     * 推荐商品列表
     * @param Request $request
     */
    public function recommendProduct(Request $request)
    {
        if ($request->isPost()) {
            $product_list   = [];
            if (!$this->isLogin()) {
                $product_list = db('product')
                    ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                    ->order('p_sales desc')
                    ->limit(4)
                    ->select();
            }else{
                $order          = db('onlineorder')
                    ->where(['o_mid' => $this->mid])
                    ->select();
                $history_search = db('history_search')
                    ->where(['mid' => $this->mid])
                    ->select();
                if (empty($order) && empty($history_search)) {
                    $product_list = db('product')
                        ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                        ->order('p_sales desc')
                        ->limit(4)
                        ->select();
                } elseif ($order && empty($history_search)) {
                    $pid = [];
                    $i   = 0;
                    foreach ($order as $key => $value) {
                        $detail = db('orderdetails')
                            ->where(['d_orderId' => $value['o_id']])
                            ->group('d_productId')
                            ->select();
                        foreach ($detail as $k => $v) {
                            $pid[$i] = $v['d_productId'];
                            $i++;
                        }
                    }
                    $pid = array_unique($pid);
                    $pid = array_merge($pid);
                    $a   = db('product')
                        ->where('id', 'in', $pid)
                        ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                        ->limit(4)
                        ->order('id desc')
                        ->select();
                    if (count($a) < 4) {
                        $b            = db('product')
                            ->where('id', 'not in', $pid)
                            ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                            ->limit(4 - count($a))
                            ->order('p_sales desc')
                            ->select();
                        $product_list = array_merge($a, $b);
                    } else {
                        $product_list = array_merge($a);
                    }
                } elseif ($order && $history_search) {
                    $pid = [];
                    $i   = 0;
                    foreach ($order as $key => $value) {
                        $detail = db('orderdetails')
                            ->where(['d_orderId' => $value['o_id']])
                            ->group('d_productId')
                            ->select();
                        foreach ($detail as $k => $v) {
                            $pid[$i] = $v['d_productId'];
                            $i++;
                        }
                    }
                    $pid = array_unique($pid);
                    $pid = array_merge($pid);

                    $pids = [];
                    foreach ($history_search as $key => $value) {
                        $product = db('product')
                            ->where("p_name like '%" . $value['content'] . "%'")
                            ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                            ->select();
                        foreach ($product as $k => $v) {
                            $pids[] = $v['id'];
                        }
                    }
                    $pids = array_unique($pids);
                    $pids = array_merge($pids);
                    $a   = db('product')
                        ->where('id', 'in', $pid)
                        ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                        ->limit(4)
                        ->order('id desc')
                        ->select();
                    $product_list = array_merge($a);
                    if (count($a) < 4) {
                        $b            = db('product')
                            ->where('id', 'not in', $pid)
                            ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                            ->limit(4 - count($a))
                            ->order('p_sales desc')
                            ->select();
                        $product_list = array_merge($product_list, $b);
                        if (count($b) + count($a) < 4) {
                            $c            = db('product')
                                ->where('id', 'not in', $pid)
                                ->where('id', 'in', $pids)
                                ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                                ->limit(4 - count($a) - count($b))
                                ->order('p_sales desc')
                                ->select();
                            $product_list = array_merge($product_list, $c);
                            if (count($b) + count($a) + count($c) < 4) {
                                $d            = db('product')
                                    ->where('id', 'not in', $pid)
                                    ->where('id', 'not in', $pids)
                                    ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                                    ->limit(4 - count($a) - count($b)-count($c))
                                    ->order('p_sales desc')
                                    ->select();
                                $product_list = array_merge($product_list, $d);
                            }
                        }
                    }
                } elseif (empty($order) && $history_search) {
                    $pids = [];
                    foreach ($history_search as $key => $value) {
                        $product = db('product')
                            ->where("p_name like '%" . $value['content'] . "%'")
                            ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                            ->select();
                        foreach ($product as $k => $v) {
                            $pids[] = $v['id'];
                        }
                    }
                    $pids = array_unique($pids);
                    $pids = array_merge($pids);
                    $product_list = db('product')
                        ->where('id', 'in', $pids)
                        ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                        ->limit(4)
                        ->order('id desc')
                        ->select();
                    $product_list = array_merge($product_list);
                    if(count($product_list)<4){
                        $b            = db('product')
                            ->where('id', 'not in', $pids)
                            ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                            ->limit(4 - count($product_list))
                            ->order('p_sales desc')
                            ->select();
                        $product_list = array_merge($product_list,$b);
                    }
                }
            }
            foreach ($product_list as $key => $value) {
                $product_list[$key]['p_img'] = saver() . $value['p_img'];
            }
            $this->ajaxSuccess('Success', $product_list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }
    /**
     * 查询订单是否支付成功
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderQuery()
    {
        set_time_limit(0);
        $id = input('post.id');
        sleep(2);//延时两秒
        $order = db('onlineorder')->where(['o_id' => $id])->find();
        while ($order['o_status'] != 2) {
            sleep(2);//延时两秒
            $order = db('onlineorder')->where(['o_id' => $id])->find();
        }
        $this->ajaxSuccess('支付成功', $order);
    }
}