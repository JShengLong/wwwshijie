<?php

namespace app\admin\controller;

use PDOStatement;
use think\Auth;
use think\Collection;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class Authority extends Auth
{

    /**
     * 获取权限列表
     * @param int $uid
     * @param int $type
     * @return array|false|PDOStatement|string|Collection
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getAuthList($uid, $type)
    {
        //读取用户所属用户组
        $groups = $this->getGroups($uid);
        $ids = []; //保存用户所属用户组设置的所有权限规则id
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            return [];
        }
        $map = array(
            'id' => ['in', $ids],
            'type' => $type,
            'status' => 1,
        );
        //读取用户组所有权限规则
        $rules = Db::name($this->config['auth_rule'])->where($map)->field('id,name,title,status,pid,faicon,sort')->order("sort ASC")->select();

        return $rules;
    }
}