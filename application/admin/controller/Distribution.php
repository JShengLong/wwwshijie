<?php

namespace app\admin\controller;

use think\Db;

/**
 * Created by PhpStorm.
 * Date: 2019/9/19
 * Time: 9:27
 */
class Distribution extends Right
{
    /**
     * @Notes:分销参数列表
     * @Author:jsl
     * @Date: 2019/9/19
     * @Time: 9:58
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $list = Db::table('distribution')->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * @Notes:新增等级
     * @Author:jsl
     * @Date: 2019/9/19
     * @Time: 9:59
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        if (request()->isPost()) {
            $parameter = (int)input('parameter');
            if (empty($parameter)) return json_err(-1, '请输入参数');
            $list = Db::table('distribution')->order('id desc')->select();
            $data = [
                'parameter' => $parameter,
                'level' => $list[0]['level'] + 1,
                'createTime' => now_datetime(),
                'updateTime' => now_datetime()
            ];
            $res = Db::table('distribution')->insert($data);
            if ($res) {
                loadDistributionList();
                return json_suc();
            } else {
                return json_err();
            }
        } else {
            return $this->fetch();
        }
    }

    /**
     * @Notes:编辑
     * @Author:jsl
     * @Date: 2019/9/19
     * @Time: 10:10
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        if (request()->isPost()) {
            $id = input('id');
            $parameter = (int)input('parameter');
            if (empty($parameter)) return json_err(-1, '请输入参数');
            $data = [
                'parameter' => $parameter,

                'updateTime' => now_datetime()
            ];
            $res = Db::table('distribution')->where('id', $id)->update($data);
            if ($res) {
                loadDistributionList();
                return json_suc();
            } else {
                return json_err();
            }
        } else {
            $id = input('id');
            $data = Db::table('distribution')->where('id', $id)->find();
            $this->assign('data', $data);
            return $this->fetch();
        }
    }

    /**
     * @Notes:删除
     * @Author:jsl
     * @Date: 2019/9/19
     * @Time: 10:11
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function delete()
    {
        if (request()->isPost()) {
            $id = input('id');
            $res = Db::table('distribution')->where(['id' => $id])->delete();
            if ($res) {
                $list = Db::table('distribution')->select();
                foreach ($list as $key => $value) {
                    $level = $key + 1;
                    Db::table('distribution')->where(['id' => $value['id']])->update(['level' => $level]);
                }
                loadDistributionList();
                return json_suc();
            }else{
                return json_err();
            }
        }
    }
}