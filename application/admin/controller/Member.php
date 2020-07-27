<?php

namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;
use think\Validate;
use think\Request;

class Member extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $modelName = 'Member';  //模型名,用于add和update方法
    protected $indexField = [];  //查，字段名
    protected $addField = ['id', 'm_nickname', 'm_account', 'm_password', 'm_createTime', 'm_updateTime', 'm_fatherId', 'm_grandpaId', 'm_grate_grandpaId', 'm_pathtree', 'm_total', 'm_isDisable', 'm_isDelete', 'm_thumb', 'm_level', 'm_invitation_code', 'm_read_message', 'm_teamTurnOver', 'm_oneself', 'm_lev', 'm_regionId', 'teamNum'];    //增，字段名
    protected $editField = ['id', 'm_nickname', 'm_account', 'm_password', 'm_createTime', 'm_updateTime', 'm_fatherId', 'm_grandpaId', 'm_grate_grandpaId', 'm_pathtree', 'm_total', 'm_isDisable', 'm_isDelete', 'm_thumb', 'm_level', 'm_invitation_code', 'm_read_message', 'm_teamTurnOver', 'm_oneself', 'm_lev', 'm_regionId', 'teamNum'];   //改，字段名
    /**
     * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
     * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
     * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
     * @var array
     */
    protected $cache = false; //是否开启缓存查询，仅对后台查询生效，通过模型方式进行增，改，删的操作，都会刷新缓存
    protected $searchField = [];
    protected $orderField = 'id desc';  //排序字段
    protected $pageLimit = 10;               //分页数
    protected $addTransaction = false;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction = false;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = false;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑

    //增，数据检测规则
    protected $add_rule
        = [
            //'nickName|昵称'  => 'require|max:25'
            'm_nickname|用户昵称' => 'require',
            'm_account|用户账号'  => 'require',
            'm_password|用户密码' => 'require',
            'm_thumb|用户头像'    => 'require',

        ];
    //改，数据检测规则
    protected $edit_rule
        = [
            //'nickName|昵称'  => 'require|max:25'

        ];
    protected $m_level = ["" => "", 1 => '普通账号', 2 => '销售账号'];

    /**
     * 列表查询sql捕获
     * @param $sql
     * @return mixed
     */
    public function indexQuery($sql)
    {
        $where = ' 1 ';
        if (trim(input('post.id'))) {
            // 精确
            if (input('post.idCondition') == 1) {
                $where .= " AND t.id='" . trim(input('post.id')) . "'";
            } else {
                $where .= " AND t.id LIKE '%" . trim(input('post.id')) . "%'";
            }
        }
        if (trim(input('post.m_account'))) {
            // 精确
            if (input('post.m_accountCondition') == 1) {
                $where .= " AND t.m_account='" . trim(input('post.m_account')) . "'";
            } else {
                $where .= " AND t.m_account LIKE '%" . trim(input('post.m_account')) . "%'";
            }
        }
        if (trim(input('post.m_nickname'))) {
            // 精确
            if (input('post.m_nicknameCondition') == 1) {
                $where .= " AND t.m_nickname='" . trim(input('post.m_nickname')) . "'";
            } else {
                $where .= " AND t.m_nickname LIKE '%" . trim(input('post.m_nickname')) . "%'";
            }
        }
        if (trim(input('post.p_nickname'))) {
            // 精确
            if (input('post.p_nicknameCondition') == 1) {
                $where .= " AND m.m_nickname='" . trim(input('post.p_nickname')) . "'";
            } else {
                $where .= " AND m.m_nickname LIKE '%" . trim(input('post.p_nickname')) . "%'";
            }
        }
        if (input('post.m_isDisable')) {
            $where .= " AND t.m_isDisable='" . input("post.m_isDisable") . "'";
        }
        // 注册时间
        if (input('post.createtimeStart') || input('post.createtimeEnd')) {
            // 起始日期
            if (input('post.createtimeStart')) {
                $where .= " AND t.m_createTime>='" . input("post.createtimeStart") . "'";
            }
            // 结束日期
            if (input('post.createtimeEnd')) {
                $where .= " AND t.m_createTime<='" . input("post.createtimeEnd") . " 23:59:59'";
            }
        }
        return $sql->alias('t')
                   ->join('member m', 'm.id=t.m_fatherId', 'left')
                   ->field('t.*,m.m_nickname as p_nickname')
                   ->where($where);
    }
    public function pageEach($item, $key)
    {
        $item->totals=db('onlineorder')
            ->where(['o_mid'=>$item->id])
            ->where('o_status !=6 and o_status!=7')
            ->sum('o_actual_payment');
        $item->order_num=db('onlineorder')
            ->where(['o_mid'=>$item->id])
            ->where('o_status !=6 and o_status!=7')
            ->count();
        return $item;
    }

    /**
     * 输出到列表视图的数据捕获
     * @param $data
     * @return mixed
     */
    public function indexAssign($data)
    {
        $data['params']['id']              = trim(input('post.id'));
        $data['params']['m_account']       = trim(input('post.m_account'));
        $data['params']['m_nickname']      = trim(input('post.m_nickname'));
        $data['params']['m_isDisable']     = trim(input('post.m_isDisable'));
        $data['params']['p_nickname']      = trim(input('post.p_nickname'));
        $createtimeStart                   = input('post.createtimeStart');
        $createtimeEnd                     = input('post.createtimeEnd');
        $data['params']['createtimeStart'] = $createtimeStart;
        $data['params']['createtimeEnd']   = $createtimeEnd;
        $data['lists']                     = [
            'hxc'         => [],
            'm_isDisable' => getDropdownList('isDisable'),
            'm_level'     => $this->m_level
        ];
        return $data;
    }

    public function editData($data)
    {
        $data['m_updateTime'] = now_datetime();
        return $data;
    }

    public function addData($data)
    {
        if (!isPhone($data['m_account'])) {
            return json_err(-1, '请填写正确的账号');
        }
        $member = db('member')->where(['m_account' => $data['m_account']])->find();
        if ($member) {
            return json_err('-1', '该账号已存在');
        }
        $data['m_createTime']      = now_datetime();
        $data['m_updateTime']      = now_datetime();
        $data['m_password']        = password_hash($data['m_password'], PASSWORD_DEFAULT);
        $data['m_invitation_code'] = $this->invter();
        return $data;
    }

    public function invter()
    {
        $str    = rand(100000, 999999);
        $member = db("member")->where("m_invitation_code", $str)->find();
        if (!empty($member)) {
            return $this->invter();
        }
        return $str;
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
        if (request()->isPost()) {
            $data        = input('post.');
            $id          = $data['id'][0];
            $field       = $data['id'][1];
            $val         = $data['id'][2];
            $map[$field] = $val;
            $res         = db($this->modelName)->where(['id' => $id])->update($map);
            if ($res) {
                return json_suc();
            } else {
                return json_err();
            }

        }
    }

    /**
     * 输出到编辑视图的数据捕获
     * @param $data
     * @return mixed
     */
    public function editAssign($data)
    {
        $member_level  = db('memberlevel')->column('level_id,level_name');
        $data['lists'] = [
            'hxc'          => [],
            'isDisable'    => getDropdownList('isDisable'),
            'member_level' => $member_level,
        ];
        return $data;
    }

    /**
     * 团队信息
     */
    public function info()
    {
        if (request()->isPost()) {

        } else {
            $member_id = input('id');
            $this->assign('searchId', $member_id);
        }
        return view();
    }

    /**
     * 获取数据
     */
    public function getNodes()
    {
        $searchId = (int)input('searchId') ? (int)input('searchId') : 0;

        $data = db('member')
            ->where(['m_isDelete' => 2])
            ->field('id,m_nickname,m_account,m_fatherId')
            ->select();
        foreach ($data as $key => $value) {
            if (empty($data[$key]['m_nickname'])) {
                $data[$key]['m_nickname'] = '暂无昵称';
            }
        }
        $returnData = $this->getTree3($data, 'id', 'm_fatherId', '', $searchId);
        return json($returnData);
    }

    /**
     * 版本3.0
     * 将标准二维数组换成树，利用递归方式实现
     * @param  array $list 待转换的数据集
     * @param  string $pk 唯一标识字段
     * @param  string $pid 父级标识字段
     * @param  string $child 子集标识字段
     * return  array
     */
    public function getTree3($list, $pk = 'id', $pid = 'm_fatherId', $child = 'child', $root = 1)
    {
        $tree = array();
        foreach ($list as $key => $val) {
            if ($val[$pid] == $root) {
                if (!empty($list)) {
                    $val['text'] = "姓名：{$val['m_nickname']} ，账号：{$val['m_account']}";
                    $child       = $this->getTree3($list, $pk, $pid, $child, $val[$pk]);
                    if (!empty($child)) {
                        $val['children'] = $child;
                    }
                }
                $tree[] = $val;
            }
        }
        return $tree;
    }

    /**
     * 余额变动
     * @return mixed|\think\response\Json
     * @throws \think\Db\exception\DataNotFoundException
     * @throws \think\Db\exception\ModelNotFoundException
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function total()
    {
        if (request()->isPost()) {
            $type = input('post.type');
            $num  = input('post.num');
            $id   = input('post.id');
            if ($type == 1) {
                $res = balanceLog($id, $num, 1, 1, '平台充值');
            } else {
                $res = balanceLog($id, $num, -1, 4, '积分兑换');
            }
            if ($res == false) {
                return json_err();
            }
            return json_suc();
        } else {
            $id = input('id');
            $this->assign('id', $id);
            $this->assign('lists', ['type' => [1 => '加', 2 => '减']]);
            return $this->fetch();
        }
    }

    /**
     * 积分变动
     * @return mixed|\think\response\Json
     * @throws \think\Db\exception\DataNotFoundException
     * @throws \think\Db\exception\ModelNotFoundException
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function integrals()
    {
        if (request()->isPost()) {
            $type = input('post.type');
            $num  = input('post.num');
            $id   = input('post.id');
            if ($type == 1) {
                $res = integralLog($id, $num, 1, 5, '平台充值');
            } else {
                $res = integralLog($id, $num, -1, 4, '积分兑换');
            }
            if ($res == false) {
                return json_err();
            }
            return json_suc();
        } else {
            $id = input('id');
            $this->assign('id', $id);
            $this->assign('lists', ['type' => [1 => '加', 2 => '减']]);
            return $this->fetch();
        }
    }

    /**
     * 积分明细
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function balance()
    {
        $id   = input('id');
        $list = db('balancelog')
            ->where(['b_mid' => $id])
            ->order('b_id desc')
            ->paginate(10);

        $pagelist   = $list->render();
        $countFiled = db('balancelog')
            ->where(['b_mid' => $id])
            ->count();
        $data       = [
            'list'       => $list,
            'pagelist'   => $pagelist,
            'countField' => $countFiled
        ];
        $this->assign($data);
        return $this->fetch();
    }

    /**
     * 积分明细
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function integral()
    {
        $id   = input('id');
        $list = db('integral')
            ->where(['b_mid' => $id])
            ->order('b_id desc')
            ->paginate(10);

        $pagelist   = $list->render();
        $countFiled = db('integral')
            ->where(['b_mid' => $id])
            ->count();
        $data       = [
            'list'       => $list,
            'pagelist'   => $pagelist,
            'countField' => $countFiled
        ];
        $this->assign($data);
        return $this->fetch();
    }

    /**
     * 放行
     * @param Request $request
     * @return Json
     */
    public function enable(Request $request)
    {
        if (!$request->isPost()) {
            return json_err();
        }
        $id  = $request->post('id');
        $ret = \app\admin\model\Member::where("id", $id)->update(["m_isfrone" => 2]);
        if (false !== $ret) {
            return json_suc();
        } else {
            return json_err();
        }
    }

    /**
     * 禁用
     * @param Request $request
     * @return Json
     */
    public function disable(Request $request)
    {
        if (!$request->isPost()) {
            return json_err();
        }
        $id = $request->post('id');

        $ret = \app\admin\model\Member::where("id", $id)->update(["m_isfrone" => 1]);
        if (false !== $ret) {
            return json_suc();
        } else {
            return json_err();
        }
    }

    public function deleteEnd($id)
    {
        db('queues')->where('member_id', $id)->delete();
    }
}