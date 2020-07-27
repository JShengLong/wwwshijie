<?php
namespace app\mini\controller;

use think\Db;

/**
 * Created by PhpStorm.
 * User: jsl
 * Date: 2019/9/19
 * Time: 8:56
 */
class Distribution extends Base
{
    //用户id
    private $memberId = 6;
    //金额
    private $total = 10;
    //订单
    private $order = [];

    private $productList=[];
    /**
     * Distribution constructor.
     * @param $orderId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function __construct($orderId=1)
    {
        $order = Db::table('onlineorder')->where(['o_id' => $orderId])->find();
        $orderDetail=Db::table('orderdetails')->where(['d_orderId'=>$orderId])->select();
        $this->productIfo($orderDetail);
        $this->memberId = $order['o_mid'];
        $this->total = $order['o_total'];
        $this->order = $order;
    }

    /**
     * @Notes:商品详情
     * @Author:jsl
     * @Date: 2019/9/20
     * @Time: 9:41
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function productIfo($orderDetail)
    {
        $productList=[];
        foreach ($orderDetail as $key=>$value){
            $productList[]= Db::table('product')
                ->alias('t')
                ->join('orderdetails o','o.d_productId=t.p_id')
                ->where(['p_id' => $value['d_productId']])
                ->find();
        }
        $this->productList=$productList;
    }

    /**
     * @Notes:用户详情
     * @Author:jsl
     * @Date: 2019/9/20
     * @Time: 9:41
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function memberInfo()
    {
        $member = Db::table('member')->where(['id' => $this->memberId])->find();
        return $member;
    }

    /**
     * @Notes:分销（普通）
     * @Date: 2019/9/19
     * @Time: 13:31
     * @param $type 类型
     * @param $info 详情
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function distribution($type=1, $info='分销奖')
    {
        $member = $this->memberInfo();
        $res = [true];
        if ($member['m_pathtree']) {
            $distribution = getDistributionList();
            if (count($distribution) <= 3) { //三级分销或者更低走这个方法
                foreach ($distribution as $key => $value) { //循环分销等级
                    switch ($key)//判断等级
                    {
                        //一级分销
                        case 1:
                            //检测父级是否存在
                            $father_id = Db::table('member')->where(['id' => $member['m_fatherId'], 'm_isDelete' => 2])->value('id');
                            if ($father_id) {
                                //添加余额和余额记录
                                $res[] = balancelog($father_id, bcmul($this->total, $value, 2), 1, $type, $info);
                            }
                            break;
                        //二级分销
                        case 2:
                            //检测祖级是否存在
                            $grandpa_id = Db::table('member')->where(['id' => $member['m_grandpaId'], 'm_isDelete' => 2])->value('id');
                            if ($grandpa_id) {
                                //添加余额和余额记录
                                $res[] = balancelog($grandpa_id, bcmul($this->total, $value, 2), 1, $type, $info);
                            }
                            break;
                        //三级分销
                        case 3:
                            //检测太级是否存在
                            $m_grate_grandpaId = Db::table('member')->where(['id' => $member['m_grate_grandpaId'], 'm_isDelete' => 2])->value('id');
                            if ($m_grate_grandpaId) {
                                //添加余额和余额记录
                                $res[] = balancelog($m_grate_grandpaId, bcmul($this->total, $value, 2), 1, $type, $info);
                            }
                            break;
                    }
                }
            } else {//超过三级走这个方法
                $path = $member['m_pathtree'];
                //将关系树转换成数组
                $path = explode(",", $path);
                //将数组倒转、去空、截取
                $path = array_slice(array_filter(array_reverse($path)), 0, count($distribution));
                //循环关系数组
                foreach ($path as $key => $value) {
                    //检测上级是否存在
                    $parent = Db::table('member')->where(['id' => $value, 'm_isDelete' => 2])->find();
                    if (empty($parent)) {
                        //如果不存在就跳过本次循环
                        continue;
                    }
                    //添加余额和余额记录
                    $res[] = balancelog($value, bcmul($this->total, $distribution[$key + 1], 2), 1, $type, $info);
                }
            }
        }
        if (in_array(false, $res)) {
            return false;
        }
        return true;
    }

    /**
     * @Notes:复销（普通）
     * @Date: 2019/9/20
     * @Time: 9:15
     * @param $type 类型
     * @param $info 详情
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancellationRatio($type=1, $info='复销奖')
    {
        $member = $this->memberInfo();
        $parent = Db::table('member')->where(['id' => $member['m_fatherId'], 'isDelete' => 2])->find();
        $cancellationRatio = (int)getSettings('cancellationRatio', 'cancellationRatio');
        //检测上级是否存在 和 配置不能为空
        if ($parent && $cancellationRatio != '' && $cancellationRatio != 0) {
            $cancellationRatio = bcdiv($cancellationRatio, 100, 2);
            $total = bcmul($this->total, $cancellationRatio, 2);
            $res = balancelog($parent['id'], $total, 1, $type, $info);
            if ($res == false) {
                return false;
            }
        }
        return true;
    }

    /**
     * @Notes:新增团队业绩
     * @Author:jsl
     * @Date: 2019/9/20
     * @Time: 9:49
     * @return bool
     * @throws \think\Exceptionteam_award
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function teamTurnOver()
    {
        $member=$this->memberInfo();
        //给关系树上的所有人加团队业绩 可以根据业务要求加where条件
        $where['id']=array('in',$member['m_pathtree']);
        $res[]=Db::table('member')->where($where)->setInc('m_teamTurnOver',$this->total);
        //给自己加团队业绩 可以根据业务要求加where条件
        $where1['id']=$this->memberId;
        $res[]=Db::table('member')->where($where1)->setInc('m_oneself'.$this->total);
        if(in_array(false,$res)){
            return false;
        }
        return true;
    }

    /**
     * @Notes:区域代理
     * @Author:jsl
     * @Date: 2019/9/20
     * @Time: 10:30
     * @param $type 类型
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function regionalAgency($type=1){
        $region=$this->order['o_regionId'];
        $region=explode('-',$region);
        $res=[true];
        //省级代理
        $sheng=Db::table('member')->where('m_regionId',(int)$region[0])->find();
        if($sheng){
            $regionalAgency=getSettings('regionalAgency','sheng');
            $regionalAgency=bcdiv($regionalAgency,100,2);
            $total=bcmul($this->total,$regionalAgency,2);
            $res[]=balancelog($sheng['id'],$total,1,$type,'省级代理');
        }
        //市级代理
        $shi=Db::table('member')->where('m_regionId',(int)$region[1])->find();
        if($shi){
            $regionalAgency=getSettings('regionalAgency','shi');
            $regionalAgency=bcdiv($regionalAgency,100,2);
            $total=bcmul($this->total,$regionalAgency,2);
            $res[]=balancelog($shi['id'],$total,1,$type,'市级代理');
        }
        //县级代理
        $xian=Db::table('member')->where('m_regionId',(int)$region[2])->find();
        if($xian){
            $regionalAgency=getSettings('regionalAgency','xian');
            $regionalAgency=bcdiv($regionalAgency,100,2);
            $total=bcmul($this->total,$regionalAgency,2);
            $res[]=balancelog($xian['id'],$total,1,$type,'县级代理');
        }
        if(in_array(false,$res)){
            return false;
        }
        return true;
    }

    /**
     * @Notes:团队奖（极差，平级截流）
     * @Author:jsl
     * @Date: 2019/9/20
     * @Time: 11:11
     * @param int $type 类型
     * @param string $info 详情
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function team_award($type=1,$info='团队奖')
    {
        $member=$this->memberInfo();
        $viplevel=(int)getSettings('viplevel','viplevel');//会员等级
        $viparawd=getSettings('viparawd','viparawd');//各等级奖励
        $viparawd=explode('-',$viparawd);//将奖励转换成数组
        $res=[true];
        //判断是否存在关系树
        if($member['m_pathtree']){
            $path = $member['m_pathtree'];
            //将关系树转换成数组
            $path = explode(",", $path);
            //将数组倒转、去空
            $path = array_filter(array_reverse($path));
            $arr=[];
            //循环所有上级
            foreach ($path as $value){
                $parent = Db::table('member')->where(['id'=> $value])->find();
                //循环所有的等级
                for($i=1;$i<=$viplevel;$i++){
                    //检测符合等级的分成一组
                    if($parent['m_level']==$i){
                        $arr[$i][]=$value;
                    }
                }
            }
            //循环所有的等级奖励
            $viparawd=array_slice($viparawd,0,count($arr));
            foreach ($viparawd as $key =>$value){
                try{
                    $bili=bcdiv($value,100,2);//将比例除以100
                    $total=bcmul($this->total,$bili,2);//金额乘以比例
                    //给同等级的第一个人奖励 平级压缩
                    $res[]=balancelog($arr[$key+1][0],$total,1,$type,$info);
                }catch (\Exception $exception){
                    continue;
                }
            }
        }
        if(in_array(false,$res)){
            return false;
        }
        return true;
    }

    /**
     * @Notes:复消奖·
     * @Date: 2019/9/23
     * @Time: 9:51
     * @param int $type
     * @param string $info
     * @return bool
     * @throws Db\exception\DataNotFoundException
     * @throws Db\exception\ModelNotFoundException
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function repeat($type=1,$info='复消奖'){
        if($this->order['o_rebateflag']==1){
            return true;
        }
        $member=$this->memberInfo();
        $res=[true];
        foreach ($this->productList as $product) {
            $CommendOne = $product['p_commendone'];//一级复消额
            $CommendTwo = $product['p_commendtwo'];//二级分消额
            $CommendThree= $product['p_commendthree'];//三级复消额
            //查找上级
            $m_fatherId=Db::table('member')->where(['id'=>$member['m_fatherId']])->value('id');//上级
            //查找上上级
            $m_grandpaId=Db::table('member')->where(['id'=>$member['m_grandpaId']])->value('id');//上上级
            //查找上上上级
            $m_grate_grandpaId=Db::table('member')->where(['id'=>$member['m_grate_grandpaId']])->value('id');//上上上级
            if($CommendOne&&$m_fatherId){
                $res[]=balancelog($m_fatherId,bcmul($CommendOne,$product['d_num'],2),1,$type,$info);
            }
            if($CommendTwo&&$m_grandpaId){
                $res[]=balancelog($m_grandpaId,bcmul($CommendTwo,$product['d_num'],2),1,$type,$info);
            }
            if($CommendThree&&$m_grate_grandpaId){
                $res[]=balancelog($m_grate_grandpaId,bcmul($CommendThree,$product['d_num'],2),1,$type,$info);
            }
        }
        if(in_array(false,$res)){
            return false;
        }
        return true;
    }
}