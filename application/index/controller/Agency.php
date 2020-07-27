<?php
namespace app\index\controller;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/19
 * Time: 16:49
 */
class Agency extends Signin
{
    /**
     * 代理信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function member(){
        if (request()->isPost()) {
            $member_id = $this->uid;
            $member_info=db('member')->where('id',$member_id)->find();
            $agency=db('agency')
                ->alias('t')
                ->join('region r','t.regionId = r.id','left')
                ->where('t.account',$member_info['m_account'])
                ->field('t.*,r.name,r.name_path')
                ->select();
            $member_info['agency']=$agency;
            $this->ajaxSuccess('Success',$member_info);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }
    /**
     * 订单列表
     */
    public function orderList()
    {
        if (request()->isPost()) {
            $page = input('post.page', 1);
            $status = input('post.status');
            $member_id = $this->uid;
            $member_info=db('member')->where('id',$member_id)->find();
            //2待服务5已超时3已完成
            if (empty($status)) {
                $where = 'o_agency='. $member_info['m_account'].' and o_status in (2,3,5)';
            } else {
                $where = ['o_agency' =>$member_info['m_account'], 'o_status' => $status];
            }
            //订单列表
            $order_list = db('onlineorder')
                ->where($where)
                ->page($page, 10)
//                ->fetchSql(true)
                ->order('o_status asc,o_id asc')
                ->select();
//            $this->ajaxError($order_list);
            //循环查询订单里面的商品
            foreach ($order_list as $key => $value) {
                $order_list[$key]['detail'] = db('orderdetails')
                    ->alias('t')
                    ->join('product p', 'p.id=t.d_productId','left')
                    ->join('category c', 'c.id=p.p_category_id','left')
                    ->where(['t.d_orderId' => $value['o_id']])
                    ->field('d_num,d_price,d_total,p_name,p_img,p_oldprice,c.name as cate_name')
                    ->select();
                //图片加前缀
//                foreach ($order_list[$key]['detail'] as $k => $v) {
//                    $order_list[$key]['detail'][$k]['p_img'] = saver() . $v['p_img'];
//                }
                $order_list[$key]['name_path']=db('region')->where('id',$value['o_regionId'])->value('name_path');
            }
            $this->ajaxSuccess('Success', $order_list);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

    /**
     * 订单统计
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderStatistics(){
        if (request()->isPost()) {
            $member=db('member')->where(['id'=>$this->uid])->find();
            $date1=date('Y-m-d');
            //今日订单总量条件
            $where1="o_agency={$member['m_account']} and  o_createtime> '$date1' and (o_status = 2 or o_status = 3 or o_status = 5)";
            //今日订单总量
            $return['order_count']=db('onlineorder')->where($where1)->count();
            //累计单量条件
            $where2="o_agency={$member['m_account']} and (o_status = 2 or o_status = 3 or o_status = 5)";
            //累计单量
            $return['order_count_all']=db('onlineorder')->where($where2)->count();
            //订单总额
            $return['order_num']=db('onlineorder')->where($where2)->sum('o_total');
            //完成单量条件
            $where3="o_agency={$member['m_account']} and  o_status = 3 ";
            //完成单量
            $return['order_count_over']=db('onlineorder')->where($where3)->count();
            //超时单量条件
            $where4="o_agency={$member['m_account']} and  o_status = 5";
            //超时单量
            $return['order_count_cahoshi']=db('onlineorder')->where($where4)->count();

            $agency=db('agency')
                ->alias('t')
                ->join('region r','t.regionId = r.id','left')
                ->where('t.account',$member['m_account'])
                ->field('t.*,r.name,r.name_path')
                ->select();
            foreach ($agency as $key=>$value){
                $where="o_agency={$member['m_account']} and o_regionId={$value['regionId']} and (o_status = 2 or o_status = 3 or o_status = 5)";
                $agency[$key]['count']=db('onlineorder')->where($where)->count();
            }
            $return['agency']=$agency;
            $this->ajaxSuccess('Success',$return);
        } else {
            $this->ajaxError('无效的请求方式');
        }
    }

}