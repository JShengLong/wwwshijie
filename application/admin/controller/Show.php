<?php
namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;

class Show extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $modelName  = 'Show';  //模型名,用于add和update方法
    protected $indexField = ['id','title','img','sort','itemid','type','cate'];  //查，字段名
    protected $addField   = ['id','title','img','sort','itemid','type','cate'];    //增，字段名
    protected $editField  = ['id','title','img','sort','itemid','type','cate'];   //改，字段名
    /**
     * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
     * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
     * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
     * @var array
     */
    protected $cache = false; //是否开启缓存查询，仅对后台查询生效，通过模型方式进行增，改，删的操作，都会刷新缓存
    protected $searchField = [];
    protected $orderField = 'sort desc';  //排序字段
    protected $pageLimit   = 10;               //分页数
    protected $addTransaction = false;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction = false;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = false;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑
    
    //增，数据检测规则
    protected $add_rule = [
        //'nickName|昵称'  => 'require|max:25'
        'title|标题' => 'require',
        'img|图片' => 'require',
        'sort|排序' => 'require',
        'cate|类别' => 'require',
    ];
    //改，数据检测规则
    protected $edit_rule = [
        //'nickName|昵称'  => 'require|max:25'
        'title|标题' => 'require',
        'img|图片' => 'require',
        'sort|排序' => 'require',
        'cate|类别' => 'require',
    ];
    protected $type=[''=>'',1=>'商品分类',2=>'商品详情',3=>'图文详情'];
    protected $cate=[''=>'',1=>'小程序',2=>'APP'];
    public function indexAssign($data)
    {
        $data['lists']=[
            'type'=>$this->type,
            'cate'=>$this->cate,
        ];
        return $data;
    }

    public function addAssign($data)
    {
        $data['lists']=[
            'type'=>$this->type,
            'cate'=>$this->cate,
        ];
        return $data;
    }
    public function editAssign($data)
    {
        $data['lists']=[
            'type'=>$this->type,
            'cate'=>$this->cate,
        ];
        return $data;
    }

    /**
     * 新增数据插入数据库前数据捕获（注意：在数据验证之前）
     * @param $data
     * @return mixed
     */
    public function addData($data){
        if ($data['sort'] < 0){
            return json_err(-1, "排序不能小于0！");
        }
        if(empty($data['title'])){
            return json_err(-1, "标题不能为空！");
        }
        if(empty($data['img'])){
            return json_err(-1, "图片不能为空！");
        }
        $arr=[];
        switch ($data['type']){
            case 1:
                $arr=db('category')->where('id',$data['itemid'])->find();
                break;
            case 2:
                $arr=db('product')->where('id',$data['itemid'])->find();
                break;
            case 3:
                $arr=db('notice')->where('id',$data['itemid'])->find();
                break;
        }
        if(empty($arr)){
            return json_err(-1,'请输入正确的对象ID');
        }
        $data['createTime']=now_datetime();
        $data['updateTime']=now_datetime();
        return $data;
    }

    /**
     * 编辑数据插入数据库前数据捕获（注意：在数据验证之前）
     * @param $data
     * @return mixed
     */
    public function editData($data){
        if ($data['sort'] < 0){
            return json_err(-1, "排序不能小于0！");
        }
        if(empty($data['title'])){
            return json_err(-1, "标题不能为空！");
        }
        if(empty($data['img'])){
            return json_err(-1, "图片不能为空！");
        }
        $arr=[];
        switch ($data['type']){
            case 1:
                $arr=db('category')->where('id',$data['itemid'])->find();
                break;
            case 2:
                $arr=db('product')->where('id',$data['itemid'])->find();
                break;
            case 3:
                $arr=db('notice')->where('id',$data['itemid'])->find();
                break;
        }
        if(empty($arr)){
            return json_err(-1,'请输入正确的对象ID');
        }
        $data['updateTime']=now_datetime();
        return $data;
    }

    public function addEnd($id, $data)
    {
        loadShowList();
    }
    public function editEnd($id, $data)
    {
        loadShowList();
    }
    public function deleteEnd($id)
    {
        loadShowList();
    }
}