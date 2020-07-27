<?php

namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;
use sendsms\SendSms;
use think\Session;
use push\Push;

class Onlineorder extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $modelName  = 'Onlineorder';  //模型名,用于add和update方法
    protected $indexField = [];  //查，字段名
    protected $addField   = ['o_id', 'o_mid', 'o_sn', 'o_payType', 'o_status', 'o_regionId', 'o_total', 'o_num', 'o_name', 'o_phone', 'o_expressId', 'o_expresssn', 'o_address', 'o_paytime', 'o_sendtime', 'o_endtime', 'o_remark'];    //增，字段名
    protected $editField  = ['o_id', 'o_mid', 'o_sn', 'o_payType', 'o_status', 'o_regionId', 'o_total', 'o_num', 'o_name', 'o_phone', 'o_expressId', 'o_expresssn', 'o_address', 'o_paytime', 'o_sendtime', 'o_endtime', 'o_remark'];   //改，字段名
    /**
     * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
     * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
     * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
     * @var array
     */
    protected $cache             = false; //是否开启缓存查询，仅对后台查询生效，通过模型方式进行增，改，删的操作，都会刷新缓存
    protected $searchField       = ['o_name', 'o_pname', 'o_distribution_mode', 'o_phone', 'o_is_queues', 'o_sn', 'o_payType', 'o_createtime', 'o_status', ['p_name' => "product.p_name"], ['m_account' => 'member.m_account']];
    protected $orderField        = 'o_id desc';  //排序字段
    protected $pageLimit         = 10;               //分页数
    protected $addTransaction    = false;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction   = false;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = false;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑

    //增，数据检测规则
    protected $add_rule
        = [
            //'nickName|昵称'  => 'require|max:25'
            'o_id|'                     => 'require',
            'o_mid|用户id'                => 'require',
            'o_sn|订单号'                  => 'require',
            'o_status|1待支付2待发货3待收货4已收货' => 'require',
            'o_regionId|省市区id'          => 'require',
            'o_total|订单总金额'             => 'require',
            'o_num|订单商品总数量'             => 'require',
            'o_name|收货人姓名'              => 'require',
            'o_phone|收货人电话'             => 'require',

        ];
    //改，数据检测规则
    protected $edit_rule
                                   = [
            //'nickName|昵称'  => 'require|max:25'
            'o_id|'                     => 'require',
            'o_mid|用户id'                => 'require',
            'o_sn|订单号'                  => 'require',
            'o_status|1待支付2待发货3待收货4已收货' => 'require',
            'o_regionId|省市区id'          => 'require',
            'o_total|订单总金额'             => 'require',
            'o_num|订单商品总数量'             => 'require',
            'o_name|收货人姓名'              => 'require',
            'o_phone|收货人电话'             => 'require',

        ];
    protected $o_refund            = ['' => '', 1 => '未提交退款', 2 => '退款中', 3 => '退款成功', 4 => '退款失败'];
    protected $o_distribution_mode = ['' => '', 1 => '平台配送', 2 => '站点自提', 3 => '快递发货', 4 => '物流配送'];

    /**
     * 输出到列表视图的数据捕获
     * @param $data
     * @return mixed
     */
    public function indexAssign($data)
    {
        Session::set('Online_order_o_status', input('o_status'));
        $data['lists']         = [
            'hxc'                 => [],
            'o_payType'           => getDropdownList('payType'),
            'o_status'            => getDropdownList('orderStatus'),
            'pageSize'            => getDropdownList('pageSize'),
            'o_refund'            => $this->o_refund,
            'o_distribution_mode' => $this->o_distribution_mode
        ];
        $data['params']['mid'] = input('mid');
        return $data;
    }

    /**
     * @param $item
     * @param $key
     * @return mixed|void
     */
    public function pageEach($item, $key)
    {
        $item->name_path = db('region')->where(['id' => $item->o_regionId])->value('name_path');
        return $item;
    }

    /**
     * 订单详情
     *
     * @author: Gavin
     * @time: 2019/9/7 9:37
     */
    public function order_info()
    {
        $oid   = input('id');
        $where = 1;
        $where .= ' AND o.d_orderId = ' . $oid;
        $list  = db('orderdetails')
            ->alias("o")
            ->join('product p', 'p.id = o.d_productId', 'left')
            ->join('onlineorder g', 'o.d_orderId = g.o_id', 'left')
            ->where($where)
            ->field('o.*,p.p_name')
            ->order('o.d_id desc')
            ->select();
        $this->assign('list', $list);
        return view();
    }

    /**
     * 物流信息
     *
     * @author: Gavin
     * @time: 2019/9/7 9:40
     */
    public function logistics()
    {
        $id = input('id');
        // 订单详情
        $data = db('onlineorder')
            ->alias("o")
            ->join('express e', 'o.o_expressId = e.id', 'left')
            ->field('o.o_expressId as logistics,e.code,o_expresssn,o_expresssns,o_isexp')
            ->where(['o.o_id' => $id])
            ->find();
        $exp  = json_decode($data['o_expresssns'], true);
        $this->assign("data", $data);
        $this->assign("exp", $exp);
        return view();
    }

    /**
     * 发货
     *
     * @author: Gavin
     * @time: 2019/9/7 9:43
     */
    public function shipments()
    {
        if (request()->isPost()) {
            $id               = input('post.id');
            $unreceived_goods = getSettings('timer', 'unreceived_goods');
            if (input("post.express") && input("post.expresssn")) {
                $data['o_expressId'] = input("post.express");//物流公司
                $data['o_expresssn'] = input("post.expresssn");//物流单号
                $data['o_isexp']     = 1;//物流单号
            } else {
                $exps = input('expresssns');
                $exps = explode('|', $exps);
                $arr  = [];
                foreach ($exps as $key => $value) {
                    $ex = explode(',', $value);

                    $expre = db('express')->where('name', $ex[0])->value('code');
                    if (empty($expre)) {
                        return json_err(-1, '请填写正确的物流公司名称');
                    }
                    $arr[$key]['express']   = $expre;
                    $arr[$key]['expresssn'] = $ex[1];
                }
                $data['o_isexp']      = 2;//物流单号
                $data['o_expresssns'] = json_encode($arr);//物流单号
//                dump($arr);die;
            }
            $order                      = db('onlineorder')
                ->where(['o_id' => $id])
                ->find();
            $data['o_status']           = 3;
            $data['o_sendtime']         = now_datetime();
            $data['o_unreceived_goods'] = date('Y-m-d H:i:s', strtotime('+' . $unreceived_goods . ' hours'));
            $result                     = db('onlineorder')
                ->where(['o_id' => $id])
                ->update($data);
            if ($result !== false) {
                //消息模版
                $message_template = messageTemplate(2);
                //用户信息
                $member = db('member')->where(['id' => $order['o_mid']])->find();
                //极光推送
                $push = new Push();
                //send_order类型为发货  id是当前订单的id
                $extras = ['type' => 'order', 'id' => $id];
                //发送通知
                $push->send_to_one($member['m_account'], $message_template['alert'], $message_template['title'], $extras);
                //站内消息
                send_message($message_template['title'], $message_template['alert'], 1, 1, $member['id'], $id);

                return json_suc();
            } else {
                return json_err();
            }
        }
        $id = input('id');
        // 订单详情
        $data = db('onlineorder')
            ->alias('o')
            ->join('region r', 'o.o_regionId = r.id', 'left')
            ->field('o.*,r.name_path')
            ->where(['o.o_id' => $id])
            ->find();
        $this->assign('lists', array(
            'express' => $this->getExpressList()
        ));

        $this->assign('data', $data);
        return view();
    }

    /**
     * 发货
     *
     * @author: Gavin
     * @time: 2019/9/7 9:43
     */
    public function shipments1()
    {
        if (request()->isPost()) {
            $id                         = input('post.id');
            $unreceived_goods           = getSettings('timer', 'unreceived_goods');
            $data['o_info']             = input('info');
            $order                      = db('onlineorder')
                ->where(['o_id' => $id])
                ->find();
            $data['o_status']           = 3;
            $data['o_sendtime']         = now_datetime();
            $data['o_unreceived_goods'] = date('Y-m-d H:i:s', strtotime('+' . $unreceived_goods . ' hours'));
            $result                     = db('onlineorder')
                ->where(['o_id' => $id])
                ->update($data);
            if ($result !== false) {
                //消息模版
                $message_template = messageTemplate(2);
                //用户信息
                $member = db('member')->where(['id' => $order['o_mid']])->find();
                if ($member['m_ispush'] == 2) {
                    //极光推送
                    $push = new Push();
                    //send_order类型为发货  id是当前订单的id
                    $extras = ['type' => 'send_order', 'id' => $id];
                    //发送通知
                    $push->send_to_one($member['m_account'], $message_template['alert'], $message_template['title'], $extras);
                }
                //站内消息
                send_message($message_template['title'], $message_template['alert'], 1, 1, $member['id'], $id);

                return json_suc();
            } else {
                return json_err();
            }
        }
        $id = input('id');
        // 订单详情
        $data = db('onlineorder')
            ->alias('o')
            ->join('region r', 'o.o_regionId = r.id', 'left')
            ->field('o.*,r.name_path')
            ->where(['o.o_id' => $id])
            ->find();
        $this->assign('lists', array(
            'express' => $this->getExpressList()
        ));

        $this->assign('data', $data);
        return view();
    }

    /**
     * 获取物流公司下拉框
     *
     * @author: Gavin
     * @time: 2019/9/7 9:58
     */
    public function getExpressList()
    {
        $list     = array("" => "");
        $products = db('express')->field("id,name")->select();
        for ($j = 0; $j < count($products); $j++) {
            $product    = $products[$j];
            $key        = $product['id'];
            $list[$key] = $product['name'];
        }
        return $list;
    }

    /**
     * 发货信息
     *
     * @author: Gavin
     * @time: 2019/9/7 11:20
     */
    public function receiptedit()
    {
        if (request()->isPost()) {

            // ID
            $id = input("post.id");
            // 没有ID
            if (empty($id)) {
                return json(['code' => -1, 'msg' => '订单不存在']);
            }
            // 更新数据
            $data = array();
            // 姓名
            $data['o_name'] = input('post.o_name');
            // 手机
            $data['o_phone'] = input('post.o_phone');
            // 地区
            $data['o_regionId'] = input('post.o_regionId');
            // 详细地址
            $data['o_address'] = input('post.o_address');
            // 更新数据
//            dump($data);die;
            $result = db('onlineorder')
                ->where(array("o_id" => $id))
                ->update($data);

            if ($result !== false) {
                return json(['code' => 0, 'msg' => '更新成功']);
            } else {
                return json(['code' => -1, 'msg' => '更新失败']);
            }
        } else {
            // ID
            $id = intval(input('id'));
            // 判断是否存在
            $data = db('onlineorder')
                ->alias('o')
                ->field('o.*')
                ->where(array('o.o_id' => $id))
                ->find();
            // 判断是否存在
            if (empty($data)) {
                $this->error("数据不存在");
            }
            $this->regionId($data['o_regionId'], $lists, $data);
            $this->assign('lists', $lists);
            $this->assign("data", $data);
            return view();
        }
    }

    /**
     * 修改价格
     * @return \think\response\Json|\think\response\View
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edits()
    {
        if (request()->isPost()) {
            $id    = input('o_id');
            $type  = input('type');
            $total = input('total');
            if ($total <= 0 & $type) {
                return json_err(-1, '请输入正确的优惠价格');
            }
            $order  = db('onlineorder')
                ->where(['o_id' => $id])
                ->find();
            $totals = $order['o_actual_payment'];
            if ($type == 1) {
                $youhui = bcsub($order['o_actual_payment'], $order['o_freight'], 2);
                if ($youhui > 0) {
                    if ($youhui >= $total) {
                        $totals = $order['o_actual_payment'] - $total;
                    } else {
                        return json_err(-1, '请输入正确的优惠区间价格');
                    }
                } else {
                    return json_err(-1, '暂无可优惠区间');
                }
            } else {
                if ($order['o_freight'] > 0) {
                    if (($order['o_freight'] - $total) >= 0) {
                        $totals = $order['o_actual_payment'] - $total;
                    } else {
                        return json_err(-1, '请输入正确的优惠区间价格');
                    }
                } else {
                    return json_err(-1, '暂无可优惠区间');
                }
            }
            $update = [
                'o_discount_type'  => $type,
                'o_discount'       => $total,
                'o_remark'         => input('o_remark'),
                'o_actual_payment' => $totals,
                'o_sn'             => $this->make_order_sn(),//订单号
            ];

            $res = db('onlineorder')->where(['o_id' => $id])->update($update);
            if ($res == false) {
                return json_err();
            }
            return json_suc();
        } else {
            $id             = input('id');
            $order          = db('onlineorder')
                ->where(['o_id' => $id])
                ->find();
            $order['type']  = '';
            $order['total'] = '';
            if (($order['o_actual_payment'] - $order['o_freight']) > 0) {
                $order['qujian'] = "￥0至￥" . ($order['o_actual_payment'] - $order['o_freight']) . "（请输入两位小数）";
            } else {
                $order['qujian'] = "暂无可优惠区间";
            }
            $this->assign('data', $order);

            $this->assign('lists', ['type' => ['' => '不优惠', 1 => '商品价格', 2 => '运费']]);
            return view();
        }
    }

    /**
     * 计算区间价格
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saves()
    {
        $total  = (float)trim(input('total'));
        $type   = input('type');
        $id     = input('id');
        $order  = db('onlineorder')
            ->where(['o_id' => $id])
            ->find();
        $totals = $order['o_actual_payment'];
        if ($type == 1) {
            $youhui = bcsub($order['o_actual_payment'], $order['o_freight'], 2);
//            $youhui = (float)$order['o_actual_payment'] -(float)$order['o_freight'];
            if ($youhui > 0 && $total > 0) {
                if ($youhui > $total || $youhui == $total) {
                    $totals = $order['o_actual_payment'] - $total;
                }
            }
        } else {
            if ($order['o_freight'] > 0) {
                if (($order['o_freight'] - $total) > 0 || ($order['o_freight'] - $total) == 0) {
                    $totals = $order['o_actual_payment'] - $total;
                }
            }
        }
        if ($type == 1) {
            if (($order['o_actual_payment'] - $order['o_freight']) > 0) {
                $return['qujian'] = "￥0至￥" . ($order['o_actual_payment'] - $order['o_freight']) . "（请输入两位小数）";
            } else {
                $return['qujian'] = "暂无可优惠区间";
            }
        } else {
            if ($order['o_freight'] > 0) {
                $return['qujian'] = "￥0至￥" . $order['o_freight'] . "（请输入两位小数）";
            } else {
                $return['qujian'] = "暂无可优惠区间";
            }
        }
        $return['totals'] = $totals;
        return json_suc(0, 'success', $return);
    }

    /**
     * @Notes:生成不重复的订单号
     * @Date: 2019/9/16
     * @Time: 9:03
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function make_order_sn()
    {
        $sn   = date('Ymd') . time() . rand(100, 999);
        $data = db("onlineorder")->where(['o_sn' => $sn])->find();
        if ($data) {
            $sn = $this->make_order_sn();
        }
        return $sn;
    }

    /**
     * 列表查询sql捕获
     * @param $sql
     * @return mixed
     */
    public function indexQuery($sql)
    {
        Session::set('Online_order_o_status', input('post.o_status'));
        Session::set('Online_order_o_distribution_mode', input('post.o_distribution_mode'));
        $where = ' 1 ';
        if (input('mid')) {
            $where .= 'and o_mid=' . input('mid');
        }
        $sql = $sql->alias('od')
            ->join('member m', 'od.o_mid = m.id', 'LEFT')
            ->join('region r', 'od.o_regionId = r.id', 'LEFT')
            ->join('express e', 'e.id = od.o_expressId', 'LEFT')
            ->where($where);

        return $sql;
    }

    public function export_excel()
    {
        $xlsName = "订单信息";
        $xlsCell = array(
            array('o_id', '序号'),
            array('o_sn', '订单编号'),
            array('o_name', '收货人姓名'),
            array('o_phone', '收货人电话'),
            array('name_path', '收货人地址'),
            array('o_createtime', '下单时间'),
            array('o_paytime', '支付时间'),
            array('o_distribution_mode', '配送方式'),
            array('d_name', '产品名称'),
            array('d_sku', '规格'),
            array('p_baozhuang', '单位'),
            array('d_num', '数量'),
            array('d_refund', '商品状态'),
            array('o_remark', '订单备注'),
            array('ex', '物流信息'),
        );
        $whereData  = Session::get('',$this->modelName);
        $where=[];
//        halt($whereData);
        unset($whereData['pageSize']);
        foreach ($whereData as $key=>$value){

            if($value['field']=='m_account'){
                continue;
            }
            if ($value['condition'] == 0) {
                $where[$value['field'] ?: $key] = $value['val'];
            } else {
                $where[$value['field'] ?: $key] = ['like', "%{$value['val']}%"];
            }
        }
        $xlsData = db('orderdetails')
            ->alias('de')
            ->join('onlineorder od', 'od.o_id = de.d_orderId', 'LEFT')
            ->join('region r', 'od.o_regionId = r.id', 'LEFT')
            ->join('product p', 'p.id=de.d_productId', 'left')
            ->where($where)
            ->select();

        foreach ($xlsData as $key => $value) {
            switch ($value['o_status']) {
                case 1:
                    $xlsData[$key]['o_status'] = '待支付';
                    break;
                case 2:
                    $xlsData[$key]['o_status'] = '待发货';
                    break;
                case 3:
                    $xlsData[$key]['o_status'] = '待收货';
                    break;
                case 4:
                    $xlsData[$key]['o_status'] = '已完成';
                    break;
                case 5:
                    $xlsData[$key]['o_status'] = '已评价';
                    break;
                case 6:
                    $xlsData[$key]['o_status'] = '已取消';
                    break;
                case 7:
                    $xlsData[$key]['o_status'] = '交易关闭';
                    break;
            }
            switch ($value['d_refund']){
                case 1:
                    $xlsData[$key]['d_refund'] = '未提交退款';
                    break;
                case 2:
                    $xlsData[$key]['d_refund'] = '退款中';
                    break;
                case 3:
                    $xlsData[$key]['d_refund'] = '退款成功';
                    break;
                case 4:
                    $xlsData[$key]['d_refund'] = '再次退款';
                    break;
            }
            switch ($value['o_distribution_mode']) {
                case 1:
                    $xlsData[$key]['o_distribution_mode'] = '平台配送';
                    break;
                case 2:
                    $xlsData[$key]['o_distribution_mode'] = '站点自提';
                    break;
                case 3:
                    $xlsData[$key]['o_distribution_mode'] = '快递发货';
                    break;
                case 4:
                    $xlsData[$key]['o_distribution_mode'] = '物流配送';
                    break;
            }
            $xlsData[$key]['name_path'] = $value['name_path'] . $value['o_address'];
            $xlsData[$key]['ex']        = '';
        }
        $this->exportExcel($xlsName, $xlsCell, $xlsData, '订单信息');
    }

    /**
     * 订单导入
     * 1.  2.
     */
    public function input()
    {

        /******************导入文件处理*******************/
        $file       = $_FILES['myfile'];
        $tmp_file   = $file['tmp_name'];
        $file_types = explode(".", $file['name']);
        $file_type  = $file_types[count($file_types) - 1];

        //判别是不是.xls文件，判别是不是excel文件
        if (strtolower($file_type) != "xlsx" && strtolower($file_type) != "xls") {
            return json_err(-1, "不是Excel文件，重新上传");
        }

        /*设置上传路径*/
        $savePath = $_SERVER['DOCUMENT_ROOT'] . "/uploads/excel/";
        $saveRoot = str_replace('\\', '/', $savePath);
        //var_dump($saveRoot);die;
        /*以时间来命名上传的文件*/
        $str = date('Ymdhis') . rand(1000, 9999);

        $file_name = $str . "." . $file_type;

        /*是否上传成功*/
        if (!copy($tmp_file, $saveRoot . $file_name)) {

            return json_err(-1, '上传失败');

        }
        $res = $this->import_excel($saveRoot . $file_name);
        for ($i = 0; $i <= count($res); $i++) {

            if ($i >= 2) {
                $arr[] = $res[$i];
            }
        }

        $temp_arr = [];
        foreach ($arr as $key => $vo) {
            $temp_arr[]         = $vo[0];
            $data[$key]['o_id'] = $vo[0];
            $data[$key]['ex']   = $vo[13];
        }
        $unreceived_goods = getSettings('timer', 'unreceived_goods');
        $res1             = [1];
        db()->startTrans();
        if ($temp_arr) {
            $temp_str   = trim(trim(implode(',', $temp_arr), ','));
            $temp_datas = db('Onlineorder')->where('o_id', 'in', $temp_str)->select();
            foreach ($temp_datas as $g1 => $h1) {
                foreach ($data as $g2 => $h2) {
                    $h2['ex'] = $this->trimall($h2['ex']);
                    if ($h1['o_id'] == $h2['o_id'] && $h1['o_status'] == 2 && $h2['ex'] !== '') {
                        switch ($h1['o_distribution_mode']) {
                            case 1:
                                $res1[] = db('onlineorder')
                                    ->where(['o_id' => $h1['o_id']])
                                    ->update([
                                        'o_status'           => 3,
                                        'o_info'             => trim($h2['ex']),
                                        'o_unreceived_goods' => date('Y-m-d H:i:s', strtotime('+' . $unreceived_goods . ' hours')),
                                        'o_sendtime'         => now_datetime(),
                                    ]);
                                break;
                            case 2:
                                $res1[] = true;
                                break;
                            case 3:
                                halt(1);
                                $exps = explode('|', $h2['ex']);
                                if (count($exps) == 1) {
                                    $expre = db('express')->where('name', 'like', "'%".trim($exps[0])."%'")->value('id');
                                    if (empty($expre)) {
                                        db()->rollback();
                                        return json_err(-1, '请填写正确的物流公司名称');
                                    }
                                    $res1[] = db('onlineorder')
                                        ->where(['o_id' => $h1['o_id']])
                                        ->update([
                                            'o_expressId'        => trim($expre),
                                            'o_expresssn'        => trim($exps[1]),
                                            'o_isexp'            => 1,
                                            'o_status'           => 3,
                                            'o_info'             => $h2['ex'],
                                            'o_unreceived_goods' => date('Y-m-d H:i:s', strtotime('+' . $unreceived_goods . ' hours')),
                                            'o_sendtime'         => now_datetime(),
                                        ]);
                                } else {
                                    $arr = [];
                                    foreach ($exps as $key => $value) {
                                        $ex    = explode(',', $value);
                                        $expre = db('express')->where('name', trim($ex[0]))->value('code');
                                        if (empty($expre)) {
                                            db()->rollback();
                                            return json_err(-1, '请填写正确的物流公司名称');
                                        }
                                        $arr[$key]['express']   = $expre;
                                        $arr[$key]['expresssn'] = trim($ex[1]);
                                    }
                                    $res1[] = db('onlineorder')
                                        ->where(['o_id' => $h1['o_id']])
                                        ->update([
                                            'o_expresssns'       => json_encode($arr),
                                            'o_isexp'            => 2,
                                            'o_status'           => 3,
                                            'o_info'             => $h2['ex'],
                                            'o_unreceived_goods' => date('Y-m-d H:i:s', strtotime('+' . $unreceived_goods . ' hours')),
                                            'o_sendtime'         => now_datetime(),
                                        ]);
                                }
                                break;
                            case 4:
                                $res1[] = db('onlineorder')
                                    ->where(['o_id' => $h1['o_id']])
                                    ->update([
                                        'o_status'           => 3,
                                        'o_info'             => trim($h2['ex']),
                                        'o_unreceived_goods' => date('Y-m-d H:i:s', strtotime('+' . $unreceived_goods . ' hours')),
                                        'o_sendtime'         => now_datetime(),
                                    ]);
                                break;
                        }
                    }
                }
            }
        }
        if (count($res) == 1) {
            return json_err(-1, '请填写物流信息');
        }
        if (in_array(false, $res)) {
            db()->rollback();
            return json_err(-1, '更新物流信息失败');
        }
        db()->commit();
        return json_err(0, '成功');

    }


    /**
     * 导入excel文件
     * @param $file
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function import_excel($file)
    {
        // 判断文件是什么格式
        $type = pathinfo($file);
        $type = strtolower($type["extension"]);
        $type = $type === 'csv' ? $type : 'Excel5';
        ini_set('max_execution_time', '0');
        vendor("PHPExcel.PHPExcel");
        // 判断使用哪种格式
        $objReader   = \PHPExcel_IOFactory::createReader($type);
        $objPHPExcel = $objReader->load($file);
        $sheet       = $objPHPExcel->getSheet(0);
        // 取得总行数
        $highestRow = $sheet->getHighestRow();
        // 取得总列数
        $highestColumn = $sheet->getHighestColumn();
        //循环读取excel文件,读取一条,插入一条
        $data = array();
        //从第一行开始读取数据
        for ($j = 1; $j <= $highestRow; $j++) {
            //从A列读取数据
            for ($k = 'A'; $k <= $highestColumn; $k++) {
                // 读取单元格
                $data[$j][] = $objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();
            }
        }
        return $data;
    }

    public function trimall($str)//删除空格
    {
        $oldchar = array(" ", "　", "\t", "\n", "\r");
        $newchar = array("", "", "", "", "");
        return str_replace($oldchar, $newchar, $str);
    }
}