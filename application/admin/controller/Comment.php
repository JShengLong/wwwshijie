<?php
namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;

class Comment extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $modelName  = 'Comment';  //模型名,用于add和update方法
    protected $indexField = ['id','comment','img','star','createtime','product_id','account','order_sn','is_show'];  //查，字段名
    protected $addField   = ['id','comment','img','star','createtime','product_id','account','order_sn','is_show'];    //增，字段名
    protected $editField  = ['id','comment','img','star','createtime','product_id','account','order_sn','is_show'];   //改，字段名
    /**
     * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
     * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
     * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
     * @var array
     */
    protected $cache = false; //是否开启缓存查询，仅对后台查询生效，通过模型方式进行增，改，删的操作，都会刷新缓存
    protected $searchField = ['star','account'];
    protected $orderField = 'id desc';  //排序字段
    protected $pageLimit   = 10;               //分页数
    protected $addTransaction = false;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction = false;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = false;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑
    
    //增，数据检测规则
    protected $add_rule = [
        //'nickName|昵称'  => 'require|max:25'

    ];
    //改，数据检测规则
    protected $edit_rule = [
        //'nickName|昵称'  => 'require|max:25'

    ];
    protected $star=[''=>'',1=>1,2=>2,3=>3,4=>4,5=>5];
    /**
     * 输出到列表视图的数据捕获
     * @param $data
     * @return mixed
     */
    public function indexAssign($data)
    {
        $data['lists'] = [
            'is_show' => getDropdownList('is_show'),
            'star' => $this->star,
        ];
        return $data;
    }
    public function pageEach($item, $key)
    {
        $item->pname=db('product')->where(['id'=>$item->product_id])->value(['p_name']);
        return $item;
    }

    /**
     * 评论详情
     *
     * @author: Gavin
     * @time: 2019/9/7 11:19
     */
    public function comment_info()
    {
        $id = input("id");
        // 没有ID
        if (empty($id)) {
            return json(['code' => -1,'msg' => '评论不存在']);
        }

        $info = db('comment')->where(['id' => $id])->find();
        $this->assign('info',$info);
        return view();
    }

    /**
     * 修改指定字段
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author: Gavin
     * @time: 2019/9/19 16:52
     */
    public function change_status()
    {
        if(request()->isPost()){
            $data = input('post.');
            $id = $data['id'][0];
            $field = $data['id'][1];
            $val = $data['id'][2];
            $map[$field] = $val;
            $res = db($this->modelName)->where(['id' => $id])->update($map);
            if($res){
                return json_suc();
            }else{
                return json_err();
            }

        }
    }
}