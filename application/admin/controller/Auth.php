<?php

namespace app\admin\controller;

use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\response\Json;

class Auth extends Right
{
    /**
     * 角色组
     * @return mixed
     * @throws DbException
     */
    public function group()
    {
        $groups = Db::name("authgroup")->paginate($this->pageSize);

        $pagelist = $groups->render();
        $this->assign([
            "groups" => $groups,
            "pagelist" => $pagelist
        ]);
        return $this->fetch('auth/group/group');
    }

    /**
     * 添加角色
     * @return mixed|Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function add()
    {
        if (request()->isAjax()) {
            $title = trim(input("title"));
            if (empty($title)) {
                return json_err(-1, "请填写角色名称！");
            }
            $ruleStr = $this->formatRules();

            $data = [
                "title" => $title,
                "rules" => $ruleStr
            ];
            $ret = Db::name("authgroup")->insert($data);

            if ($ret) {
                return json_suc();
            } else {
                return json_err();
            }
        } else {
            $rules = Db::name("authrule")->select();
            $convertRule = convert_tree($rules, false, true, false);

            $ret = [];
            if ($convertRule) {
                foreach ($convertRule as $k => $v) {
                    $compnents = [];
                    $compnents['id'] = $v['id'];
                    $compnents['pId'] = $v['pid'];
                    $compnents['name'] = $v['title'];
                    if ($v['hasSub']) {
                        $compnents['open'] = 'true';
                    }
                    $ret[] = $compnents;
                }
            }

            $this->assign([
                "data" => [
                    "rules" => json_encode($ret, JSON_UNESCAPED_UNICODE)
                ]
            ]);
            return $this->fetch('auth/group/add');
        }
    }

    /**
     * 格式化权限参数
     */
    public function formatRules()
    {
        $rules = json_decode(input('rules'), true);
        $ruleArr = [];
        if ($rules) {
            foreach ($rules as $k => $v) {
                if (1 == $v['checked']) {
                    $ruleArr[] = $v['id'];
                }
                if (isset($v['children'])) {
                    foreach ($v['children'] as $g => $h) {
                        if (1 == $h['checked']) {
                            $ruleArr[] = $h['id'];
                        }
                        if (isset($h['children'])) {
                            foreach ($h['children'] as $m => $n) {
                                if (1 == $n['checked']) {
                                    $ruleArr[] = $n['id'];
                                }
                            }
                        }
                    }
                }
            }
        }
        $ruleStr = "";
        if ($ruleArr) {
            $ruleStr = implode(',', array_unique($ruleArr));
        }

        return $ruleStr;
    }

    /**
     * 编辑角色
     * @return mixed|Json
     * @throws Exception
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws PDOException
     */
    public function edit()
    {
        if (request()->isAjax()) {
            $id = (int)input("id");
            $title = trim(input("title"));
            if (empty($title)) {
                return json_err(-1, "请填写角色名称！");
            }

            $data = [
                "title" => $title,
            ];

            $info = Db::name("authgroup")->where("id", $id)->find();
            $ruleStr = $this->formatRules();

            if ($ruleStr || $info['rules'] != $ruleStr) {
                $data['rules'] = $ruleStr;
            }


            $ret = Db::name("authgroup")->where("id", $id)->update($data);

            if (false !== $ret) {
                return json_suc();
            } else {
                return json_err();
            }
        } else {
            $id = (int)input("id");
            $info = Db::name("authgroup")->where("id", $id)->find();
            $granted = explode(',', $info['rules']);

            $rules = Db::name("authrule")->select();
            $convertRule = convert_tree($rules, false, true, false);

            $ret = [];
            if ($convertRule) {
                foreach ($convertRule as $k => $v) {
                    $compnents = [];
                    $compnents['id'] = $v['id'];
                    $compnents['pId'] = $v['pid'];
                    $compnents['name'] = $v['title'];
                    if (in_array($v['id'], $granted)) {
                        $compnents['checked'] = 'true';
                    }
                    if ($v['hasSub']) {
                        $compnents['open'] = 'true';
                    }
                    $ret[] = $compnents;
                }
            }

            $this->assign([
                "info" => $info,
                "data" => [
                    "title" => $info['title'],
                    "rules" => json_encode($ret, JSON_UNESCAPED_UNICODE)
                ],
                "isEdit" => 1
            ]);
            return $this->fetch('auth/group/edit');
        }
    }

    /**
     * 删除角色
     * @return Json|void
     * @throws Exception
     * @throws PDOException
     */
    public function del()
    {
        if (request()->isAjax()) {
            $id = (int)input("id");

            if ($id) {
                $ret = Db::name("authgroup")->delete($id);

                if ($ret) {
                    return json_suc();
                } else {
                    return json_err();
                }
            }
        }
    }

    /**
     * 权限规则列表
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function rule()
    {
        $rules = Db::name("authrule")->select();
        $formatRule = convert_tree($rules);

        $this->assign("rules", $formatRule);
        return $this->fetch('auth/auth/rule');
    }

    /**
     * 添加权限
     * @return mixed|Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function addRule()
    {
        if (request()->isAjax()) {
            $pid = (int)input("pid");
            $title = trim(input("title"));
            $name = strtolower(trim(input("name"))) ? strtolower(trim(input("name"))) : NULL;
            $faicon = trim(input("faicon"));
            $sort = (int)(input("sort"));

            if (0 > $pid) return json_err(-1, "错误的父级ID！");
            if (empty($title)) return json_err(-1, "请输入标题！");
            if (empty($faicon)) return json_err(-1, "请选择图标！");

            $data = [
                "name" => $name,
                "title" => $title,
                "pid" => $pid,
                "faicon" => $faicon,
                "sort" => $sort,
            ];

            try {
                $ret = Db::name("authrule")->insert($data);

                if ($ret) {
                    return json_suc(0, "添加成功！");
                } else {
                    return json_err(-1, "添加失败！");
                }
            } catch (\Exception $e) {
                return json_err(-1, $e->getMessage());
            }

        } else {
            // 规则列表
            $formatRule = $this->getRuleList();

            $this->assign("lists", [
                "rulelist" => $formatRule
            ]);
            return $this->fetch('auth/auth/add');
        }
    }

    /**
     * 获取格式化的权限规则列表
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getRuleList()
    {
        // 规则列表
        $rules = Db::name("authrule")->select();

        // 这里不要第三层的规则
        $rulesTree = convert_tree($rules, false, false);

        $formatRule = [
            "0" => "顶级"
        ];
        if ($rulesTree) {
            foreach ($rulesTree as $k => $v) {
                $formatRule[$v['id']] = $v['title'];
            }
        }

        return $formatRule;
    }

    /**
     * 更改权限
     * @return mixed|Json
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function editRule()
    {
        if (request()->isAjax()) {
            $id = (int)input("id");
            $pid = (int)input("pid");
            $title = trim(input("title"));
            $name = strtolower(trim(input("name"))) ? strtolower(trim(input("name"))) : NULL;
            $faicon = trim(input("faicon"));
            $sort = (int)(input("sort"));

            if (0 > $pid) return json_err(-1, "错误的父级ID！");
            if (empty($title)) return json_err(-1, "请输入标题！");
            if (empty($faicon)) return json_err(-1, "请选择图标！");

            $data = [
                "name" => $name,
                "title" => $title,
                "pid" => $pid,
                "faicon" => $faicon,
                "sort" => $sort,
            ];

            try {
                $ret = Db::name("authrule")->where("id", $id)->update($data);

                if (false !== $ret) {
                    return json_suc(0, "更新成功！");
                } else {
                    return json_err(-1, "更新失败！");
                }
            } catch (Exception $e) {
                return json_err(-1, $e->getMessage());
            }
        } else {
            $id = (int)input("id");
            $ruleDetail = Db::name("authrule")->where("id", $id)->find();
            if (!$ruleDetail) {
                $this->error("数据不存在！");
            }

            $formatRule = $this->getRuleList();

            $this->assign("lists", [
                "rulelist" => $formatRule
            ]);
            $this->assign("data", $ruleDetail);
            return $this->fetch('auth/auth/edit');
        }
    }

    /**
     * 删除权限
     * @return Json|void
     * @throws Exception
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws PDOException
     */
    public function delRule()
    {
        if (request()->isAjax()) {
            $id = (int)input("id");
            if (empty($id)) {
                return json_err(-1, "未知的ID！");
            }

            $rules = Db::name("authrule")->select();
            $convertRule = convert_tree_subs($id, $rules);

            if ($convertRule) {
                $ret = Db::name("authrule")->delete($convertRule);
                if ($ret) {
                    return json_suc();
                } else {
                    return json_err();
                }
            } else {
                return json_err();
            }
        }
    }
}
