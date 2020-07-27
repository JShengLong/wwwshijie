<?php
namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;
use think\Request;
use think\Session;

class Promotion extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $modelName  = 'Promotion';  //模型名,用于add和update方法
    protected $indexField = ['id','name','img','pid','pids','createTime','updateTime','createID'];  //查，字段名
    protected $addField   = ['name','img','pid','pids'];    //增，字段名
    protected $editField  = ['name','img','pid','pids'];   //改，字段名
    /**
     * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
     * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
     * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
     * @var array
     */
    protected $cache = false; //是否开启缓存查询，仅对后台查询生效，通过模型方式进行增，改，删的操作，都会刷新缓存
    protected $searchField = ['name'];
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
    public function addData($data)
    {
        $data['createTime']=now_datetime();
        $data['updateTime']=now_datetime();
        $data['createID']=Session::get("uid", "admin");
        return $data;
    }
    public function pageEach($item, $key)
    {
        $item->create_name=db('admin')->where(['id'=>$item->createID])->value(['name']);
        return $item;
    }

    /**
     * 促销商品列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function productList(){
        $id=input('id');
        $promotion=db('promotion')->where('id',$id)->find();

        $product_list=[];
        if($promotion['pid']){
            $pid=explode(',',$promotion['pid']);
            $pids=explode(',',$promotion['pids']);
            foreach ($pid as $key=> $value){
                $tuijian=1;//未推荐
                foreach ($pids as $val){
                    if($val==$value)
                    {
                        $tuijian=2;//推荐
                    }
                }
                $product_list[$key]=db('product')->where(['id'=>$value])->find();
                $product_list[$key]['tuijian']=$tuijian;
            }
        }

        $this->assign('list',$product_list);
        $this->assign('lists',['type'=>['',1=>'未推荐',2=>'已推荐']]) ;
        $this->assign('id',$id) ;
        return $this->fetch();
//        dump($product_list);
    }

    /**
     * 商品是否推荐
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function changeStatus(){
        $data=request()->param();
        $data=$data['id'];
        $res=0;
        if($data[2]==2){
            $promotion=db('promotion')->where('id',$data[1])->find();
            if($promotion['pids']){
                $pids=$promotion['pids'].','.$data[0];
            }else{
                $pids=$data[0];
            }
            $res=db('promotion')->where(['id'=>$data[1]])->update(['pids'=>$pids]);
        }else{
            $promotion=db('promotion')->where('id',$data[1])->find();

            $pids=explode(',',$promotion['pids']);

            $pids=array_diff($pids,[$data[0]]);
            if(empty($pids)){
                $res=db('promotion')->where(['id'=>$data[1]])->update(['pids'=>'']);
            }else{
                $pids=implode(',',$pids);
                $res=db('promotion')->where(['id'=>$data[1]])->update(['pids'=>$pids]);
            }
        }
        if($res==0) {
            return json_err();
        }
        return json_suc()   ;
    }

    /**
     * 新增促销商品
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function addProductList(){
        if(request()->isPost()){
            $productid=input('productid');
            $promotion=db('promotion')->where('id',input('id'))->find();
            if($promotion['pid']){
                $pidd=explode(',',$promotion['pid']);
                if(in_array($productid,$pidd)){
                    return json_err(-1,'此商品已加入该促销标签');
                }
                $pid=$promotion['pid'].','.$productid;
            }else{
                $pid=$productid;
            }
            $res=db('promotion')->where(['id'=>input('id')])->update(['pid'=>$pid]);
            if($res==0) {
                return json_err();
            }
            return json_suc();
        }else{
            $this->assign('id',input('id'));
            $this->assign('lists',['productid'=>$this->getProductList()]);
            return $this->fetch();
        }
    }

    /**
     * 删除促销商品
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function deleteProductList()
    {
        $data=request()->param();
        $data=$data['id'];
        $promotion=db('promotion')->where('id',$data[1])->find();

        $pid=explode(',',$promotion['pid']);
        $pids=explode(',',$promotion['pids']);
        if(in_array($data[0],$pid)){
            $saver['pid']=implode(',',array_diff($pid,[$data[0]]));
        }
        if(in_array($data[0],$pids)){
            $saver['pids']=implode(',',array_diff($pids,[$data[0]]));
        }
        $res=db('promotion')->where(['id'=>$data[1]])->update($saver);
        if($res==0) {
            return json_err();
        }
        return json_suc();
    }
}
