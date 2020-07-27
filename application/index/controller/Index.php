<?php

namespace app\index\controller;

use think\Db;
use think\Request;

class Index extends Signin
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
                ->where(['cate'=>2])
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
            $data['content']='<meta name="viewport"
          content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no"/>'.
                             $data['content'];
            $this->ajaxSuccess('',$data);
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
            $return['banner_list'] =  db('show')
                ->where(['cate'=>2])
                ->order('sort','asc')
                ->select();
            foreach ($return['banner_list'] as $key=> $item) {
                $return['banner_list'][$key]['img']=saver().$item['img'];
            }
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
                //商品图片加域名
                foreach ($return['promotion_list'][$key]['product_list'] as $k => $v) {
                    $return['promotion_list'][$key]['product_list'][$k]['p_img'] = saver() . $v['p_img'];
                }
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
            $mess=db('message')
                ->where(['to'=>$this->uid,'category'=>1,'is_read'=>2])
                ->count();
            $mess1=db('message')
                ->where(['category'=>2])
                ->select();
            $num=0;
            foreach ($mess1 as $key=>$value){
                $m=db('membermessages')
                    ->where(['message_id'=>$value['id']])
                    ->find();
                if(empty($m)){
                    $num++;
                }
            }
            $return['count']=$mess+$num;
            $this->ajaxSuccess('Success', $return);
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
     * 消息列表
     * @param Request $request
     */
    public function messageList(Request $request){
        if($request->isPost()){
            $page=input('page',1);
            $list=db('message')
//                ->where('to='.$this->uid.' or category=2')
                ->where("`to`={$this->uid} or category=2")
                ->order('id desc')
                ->page($page,10)
                ->select();
            $this->ajaxSuccess('success',$list);
        }else{
            $this->ajaxError('无效的请求方式');
        }
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
                $where .= ' and t.category1 =' . $category_id;
            }
            //名称不为空
            if (!empty($p_name)) {
                $his=db('history_search')
                    ->where(['content'=>$p_name,'mid'=>$this->uid])
                    ->find();
                if($his){
                    db('history_search')
                        ->where(['id'=>$his['id']])
                        ->setField('time',now_datetime());
                }else{
                    db('history_search')->insert([
                                                     'mid'     => $this->uid,
                                                     'content' => $p_name,
                                                     'time'    => now_datetime(),
                                                 ]);
                }
                $where .= " and t.p_name like '%" . $p_name . "%'";
            }
            //销量
            $sales = input('sales');
            //价格
            $price = input('price');
            //生成排序条件
            $sort = "p_sort desc";
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
        $this->ajaxSuccess('Success',$image);
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
                $return['url'] = "/uploads/" . $info->getSaveName();
                $return['urls'] = saver()."/uploads/" . $info->getSaveName();

                $this->ajaxSuccess('Success',$return);
            } else {
                // 上传失败获取错误信息
                $this->ajaxError($file->getError());
            }
        }
    }

}
