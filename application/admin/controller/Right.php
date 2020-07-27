<?php
namespace app\admin\controller;

/**
 * main区域需要一个模板布局
 * Class Right
 * @package app\admin\controller
 */
class Right extends Signin
{
    public function _initialize()
    {
        parent::_initialize();
        $this->view->engine->layout('common/layout');
    }

    /**
     * 获取分类下拉框
     * @return array
     */
    public function getProductList(){
        $list = array("" => "");
        $categorys = db('product')->field("id,p_name")->order("id ASC")->select();
        for ($j = 0; $j < count($categorys); $j++) {
            $category = $categorys[$j];
            $key = $category['id'];
            $list[$key] = $category['p_name'];
        }
        return $list;
    }
    public function getProductLists(){
        $list = array("" => "");
        $categorys = db('product')->field("id,p_name")->order("id ASC")->select();
        for ($j = 0; $j < count($categorys); $j++) {
            $category = $categorys[$j];
            $key = $category['id'];
            $list[$key] = $category['p_name'];
        }
        return $list;
    }
    /**
     * 获取分类下拉框
     * @return array
     */
    public function getCategoryList(){
        $list = array("" => "");
        $categorys = db('category')->field("id,name")->where('pid=0 and status=2')->order("id ASC")->select();
        for ($j = 0; $j < count($categorys); $j++) {
            $category = $categorys[$j];
            $key = $category['id'];
            $list[$key] = $category['name'];
        }
        return $list;
    }
    public function getCategoryLists(){
        $list = array(0 => "顶级");
        $categorys = db('category')->field("id,name")->where('pid=0 and status=2')->order("id ASC")->select();
        for ($j = 0; $j < count($categorys); $j++) {
            $category = $categorys[$j];
            $key = $category['id'];
            $list[$key] = $category['name'];
        }
        return $list;
    }

    public function getCategoryLists1(){
        $list = array("" => "");
        $categorys = db('category')->field("id,name")->where('pid=0 and status=2')->order("id ASC")->select();
        for ($j = 0; $j < count($categorys); $j++) {
            $category = $categorys[$j];
            $key = $category['id'];
            $list[$key] = $category['name'];
        }
        return $list;
    }
}
