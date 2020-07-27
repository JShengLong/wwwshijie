<?php

namespace app\mini\controller;

use think\Request;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/16
 * Time: 11:45
 */
class Category extends Base
{
    /**
     * 分类列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        if ($request->isPost()) {
            //所有的一级分类
            $return['category_list'] = db('category')
                ->where(['status' => 2, 'pid' => 0])
                ->order('sortOrder asc')
                ->select();
            //分类不为空
            if ($return['category_list']) {
                //二级分类
                foreach ($return['category_list'] as $key => $value) {
                    $return['category_list'][$key]['category_list2'] = db('category')
                        ->where(['status' => 2, 'pid' => $value['id']])
                        ->select();
                }
                //默认显示的商品列表
                $return['product_list'] = db('product')
                    ->where(['category1' => $return['category_list'][0]['id'], 'p_isDelete' => 2, 'p_isUp' => 2])
                    ->order('p_sort asc')
                    ->select();
                foreach ($return['product_list'] as $key => $value) {
                    $return['product_list'][$key]['p_img'] = saver() . $value['p_img'];
                }
            }
            $this->ajaxSuccess('Success', $return);
        } else {
            $this->ajaxError('无效请求方式');
        }
    }

    /**
     * 根据分类返回商品列表
     * @param Request $request
     */
    public function categoryProduct(Request $request)
    {
        if ($request->isPost()) {
            //一级分类id
            $category1 = input('category1');
            //二级分类id
            $category2 = input('category2');
            //一级分类id不能为空
            if (empty($category1)) {
                $this->ajaxError('一级分类不能为空');
            }
            //拼接where条件
            $where = "category1={$category1} and p_isDelete=2 and p_isUp=2";
            if ($category2) {
                $where .= " and p_category_id={$category2}";
            }
            //查询
            $product_list = db('product')
                ->where($where)
                ->order('p_sort desc')
                ->select();
            //图片加域名
            foreach ($product_list as $key => $value) {
                $product_list[$key]['p_img'] = saver() . $value['p_img'];
            }
            $this->ajaxSuccess('Success', $product_list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }
}