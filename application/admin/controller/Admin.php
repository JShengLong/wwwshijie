<?php

namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;
use app\admin\model\Authgroupaccess;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\Request;
use think\response\Json;

class Admin extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $cache = false;
    protected $modelName = 'Admin';  //模型名,用于add和update方法
    protected $orderField = 't.create_time desc';
    protected $indexField = ['t.id', 't.account', 't.name', 't.is_disable', 't.create_time', 't.lastlogintime ', 'CASE WHEN t.id = 1 THEN \'超级管理员\' ELSE g.title END groupName'];  //查，字段名
    protected $addField = ['account', 'password', 'name', 'is_disable','paypassword'];    //增，字段名
    protected $editField = ['account', 'password', 'name', 'is_disable','paypassword'];   //改，字段名
    protected $searchField = [];//条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age','productId' => 'p.name'],解释：参数名为productId,关联表字段p.name
    protected $pageLimit = 10;               //分页数
    protected $addTransaction = true;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction = true;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = true;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑

    //增，数据检测规则
    protected $add_rule
        = [
            'account|账号'  => 'require|unique:admin',
            'password|密码' => 'require',
            'name|名称'     => 'require',
        ];
    //改，数据检测规则
    protected $edit_rule
        = [
            'account|账号' => 'require',
            'name|名称'    => 'require',
        ];

    /**
     * 列表查询sql捕获
     * @param Query $sql
     * @return Query
     */
    public function indexQuery($sql)
    {
        return $sql->alias('t')
                   ->join("authgroupaccess a", "t.id = a.uid", 'left')
                   ->join("authgroup g", "a.group_id = g.id", 'left');
    }

    /**
     * 输出到新增视图的数据捕获
     * @param $data
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function addAssign($data)
    {
        $roles   = Db::name("authgroup")->field("id,title")->select();
        $roleArr = ["" => ""];
        if ($roles) {
            foreach ($roles as $k => $v) {
                $roleArr[$v['id']] = $v['title'];
            }
        } else {
            $this->error("请添加角色！");
        }
        $data['lists'] = [
            "rolelist" => $roleArr
        ];
        return $data;
    }

    /**
     * 成功添加数据后的数据捕获
     * @param $id @desc 添加后的id
     * @param $data @desc 接受的参数，包含追加的
     * @return mixed|void
     */
    public function addEnd($id, $data)
    {
        $group_id = Request::instance()->post("group_id/d");
        $ret      = Db::name("authgroupaccess")->insert(["uid" => $id, "group_id" => $group_id]);
        if (!$ret) {
            return json_err(-1, '添加失败');
        }
    }

    /**
     * 输出到编辑视图的数据捕获
     * @param $data
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function editAssign($data)
    {
        $groupId = Authgroupaccess::where('uid', $data['id'])->value('group_id');
        // 角色
        $roles   = Db::name("authgroup")->field("id,title")->select();
        $roleArr = ["" => ""];
        if ($roles) {
            foreach ($roles as $k => $v) {
                $roleArr[$v['id']] = $v['title'];
            }
        } else {
            $this->error("请添加角色！");
        }

        unset($data['data']['password']);
        unset($data['data']['paypassword']);

        $data['data']['group_id'] = $groupId;
        $data['lists']            = [
            "rolelist" => $roleArr
        ];
        return $data;
    }
    public function addData($data)
    {
        $data['password']=password_hash($data['password'], PASSWORD_DEFAULT);
        $data['paypassword']=password_hash($data['paypassword'], PASSWORD_DEFAULT);
        return $data;
    }

    /**
     * 编辑数据插入数据库前数据捕获（注意：在数据验证之前）
     * @param $data
     * @return mixed
     */
    public function editData($data)
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }else{
            $data['password']=password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (empty($data['paypassword'])) {
            unset($data['paypassword']);
        }else{
            $data['paypassword']=password_hash($data['paypassword'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    /**
     * 成功编辑数据后的数据捕获
     * @param $id @desc 编辑数据的id
     * @param $data @desc 接受的参数，包含追加的
     * @return mixed|Json|void
     * @throws Exception
     * @throws PDOException
     */
    public function editEnd($id, $data)
    {
        $group_id = Request::instance()->post('group_id/d');
        if ($group_id) {
            $updateGroup = Db::name("authgroupaccess")->insert(["uid" => $id, "group_id" => $group_id], true);
        } else {
            $updateGroup = Db::name("authgroupaccess")->where("uid", $id)->delete();
        }
        if (!$updateGroup) {
            return json_err(-1, '修改失败');
        }
    }

    /**
     * 成功删除数据后的数据捕获
     * @param $id @desc 要删除数据的id
     * @return mixed|void
     * @throws Exception
     * @throws PDOException
     */
    public function deleteEnd($id)
    {
        $ret_del_relation = Db::name("authgroupaccess")->where("uid", $id)->delete();
        if (!$ret_del_relation) {
            return json_err();
        }
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
        $ret = \app\admin\model\Admin::where("id", $id)->update(["is_disable" => 2]);
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

        $ret = \app\admin\model\Admin::where("id", $id)->update(["is_disable" => 1]);
        if (false !== $ret) {
            return json_suc();
        } else {
            return json_err();
        }
    }
}