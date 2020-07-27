<?php

namespace app\mini\controller;

use think\Db;
use think\Request;

class Index extends Base
{
    public function index()
    {
        return 'Hello world!';
    }

    /**
     * @Notes:banner轮播图
     * @Author:jsl
     * @Date: 2019/9/19
     * @Time: 9:04
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function banner()
    {
        if (request()->isPost()) {
            //轮播图缓存
            $list = db('show')
                ->where(['cate'=>1])
                ->order('sort','asc')
                ->select();
            foreach ($list as $key=> $item) {
                $list[$key]['img']=saver().$item['img'];
            }
            $this->ajaxSuccess('Success', $list);
        } else {
            $this->ajaxError('无效请求方式');
        }
    }

    /**
     * @Notes:公告列表
     * @Author:jsl
     * @Date: 2019/9/19
     * @Time: 9:06
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function notice()
    {
        if (request()->isPost()) {
            $list = getNoticeList();
            $this->ajaxSuccess($list);
        } else {
            $this->ajaxError('无效请求方式');
        }
    }

    /**
     * @Notes:公告详情
     * @Author:jsl
     * @Date: 2019/9/19
     * @Time: 9:09
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function noticeDetail()
    {
        if (request()->isPost()) {
            $id = input('id');
            if (empty($id)) {
                $this->ajaxError('ID为空');
            }
            $data = Db::table('notice')->where(['id' => $id])->find();
            $this->ajaxSuccess('', $data);
        } else {
            $this->ajaxError('无效请求方式');
        }
    }

    /**
     * 分类导航
     * @throws Db\exception\DataNotFoundException
     * @throws Db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function categoryList()
    {
        if (request()->isPost()) {
            $list = getCategoryList();
            $this->ajaxSuccess('Success', $list);
        } else {
            $this->ajaxError('无效的请求方式');
        }

    }

    /**
     * 首页数据
     * @param Request $request
     * @throws Db\exception\DataNotFoundException
     * @throws Db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function homePage(Request $request)
    {
        if ($request->isPost()) {
            //轮播图
            $return['banner_list'] = getShowList();
            //促销标签
            $return['promotion_list'] = db('promotion')
                ->order('id desc')
                ->select();
            //循环促销标签
            foreach ($return['promotion_list'] as $key => $value) {
                //图片加域名
                $return['promotion_list'][$key]['img'] = saver() . $value['img'];
                //推荐商品
                $return['promotion_list'][$key]['product_list'] = db('product')
                    ->where('id', 'in', $value['pids'])
                    ->where(['p_isDelete' => 2, 'p_isUp' => 2])
                    ->select();
                $count=count($return['promotion_list'][$key]['product_list'])>4?4:count($return['promotion_list'][$key]['product_list']);
//                halt($count);die;
                for($i=0;$i<$count;$i++){
                    $return['promotion_list'][$key]['product_list'][$i]=$return['promotion_list'][$key]['product_list'][$i];
                    $return['promotion_list'][$key]['product_list'][$i]['p_img'] = saver() .  $return['promotion_list'][$key]['product_list'][$i]['p_img'];
                }
//                //商品图片加域名
//                foreach ($return['promotion_list'][$key]['product_list'] as $k => $v) {
//                    $return['promotion_list'][$key]['product_list'][$k]['p_img'] = saver() . $v['p_img'];
//                }
            }
            //首页分类
            $return['home_category'] = db('category')
                ->where(['pid' => 0, 'type' => 1, 'status' => 2])
                ->order('sortOrder asc')
                ->select();
            foreach ($return['home_category'] as $key => $value) {
                //图片加域名
                $return['home_category'][$key]['img'] = saver() . $value['img'];
            }
            //推荐分类
            $return['recommend_category'] = db('category')
                ->where(['pid' => 0, 'type' => 2, 'status' => 2])
                ->order('sortOrder asc')
                ->select();
            foreach ($return['recommend_category'] as $key => $value) {
                //图片加域名
                $return['recommend_category'][$key]['img'] = saver() . $value['img'];
            }
            //首页推荐商品
            $return['product_list'] = db('product')
                ->where(['p_isDelete' => 2, 'p_isUp' => 2, 'p_isHot' => 2])
                ->order('p_sort desc')
                ->select();
            foreach ($return['product_list'] as $key => $value) {
                //图片加域名
                $return['product_list'][$key]['p_img'] = saver() . $value['p_img'];
            }
            $this->ajaxSuccess('Success', $return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 商品sku
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function productSku(Request $request)
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
            $product_detail['p_img'] = saver() . $product_detail['p_img'];

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

            $this->ajaxSuccess('Succsee', $product_detail);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 促销列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function promotionList(Request $request)
    {
        if ($request->isPost()) {
            $id    = input('id');
            $sales = input('sales'); //销量
            $price = input('price');//价格
            $sort  = "p_sort desc"; //生成排序条件
            if ($sales) {
                if ($sales == 1) {
                    $sort = "p_sales asc";//销量从少到多排序
                } else {
                    $sort = "p_sales desc";//销量从多到少排序
                }
            }
            if ($price) {
                if ($price == 1) {
                    $sort = "p_oldprice asc";//价格从少到多排序
                } else {
                    $sort = "p_oldprice desc"; //价格从多到少排序
                }
            }
            $promotion = db('promotion')
                ->where(['id' => $id])
                ->find();
            $list      = db('product')
                ->where('id', 'in', $promotion['pid'])
                ->order($sort)
                ->select();
            foreach ($list as $key => $value) {
                $list[$key]['p_img'] = saver() . $value['p_img'];
            }
            $this->ajaxSuccess('success',$list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * @Notes:上传图片(多图)
     * @Author:jsl
     * @Date: 2019/10/21
     * @Time: 8:43
     */
    public function uploadImages()
    {
        $file = request()->file('image');
        if (empty($file)) {
            $this->ajaxError('请上传图片');
        }
        $list = [];
        foreach ($file as $value) {
            //将传入的图片移动到框架应用根目录/public/uploads/ 目录下，ROOT_PATH是根目录下，DS是代表斜杠 /
            $info
                = $value->validate(['size' => 10 * 1024 * 1024, 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                $list[] = "/uploads/" . $info->getSaveName();
            } else {
                // 上传失败获取错误信息
                $this->ajaxError($file->getError());
            }
        }
        $image = implode(',', $list);
        $this->ajaxSuccess('Success', $image);
    }

    /**
     * @Notes:上传图片(单图)
     * @Author:jsl
     * @Date: 2019/10/21
     * @Time: 8:43
     */
    public function uploadImage()
    {
        $file = request()->file('image');
        if (empty($file)) {
            $this->ajaxError('请上传图片');
        }
        if ($file) {
            //将传入的图片移动到框架应用根目录/public/uploads/ 目录下，ROOT_PATH是根目录下，DS是代表斜杠 /
            $info
                = $file->validate(['size' => 10 * 1024 * 1024, 'ext' => 'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                $return['url']  = "/uploads/" . $info->getSaveName();
                $return['urls'] = saver() . "/uploads/" . $info->getSaveName();

                $this->ajaxSuccess('Success', $return);
            } else {
                // 上传失败获取错误信息
                $this->ajaxError($file->getError());
            }
        }
    }

}
