<?php
/*******************************************************/
/********************后台公共函数库**********************/
/*******************************************************/

/**
 * 权限规则层级树结构
 * @param array $arr
 * @param bool $layer 层级结构
 * @param bool $third 是否要第三层权限
 * @return array
 */
function convert_tree($arr=[], $layer=true, $third=true, $indent=true){
    $refer = [];
    $tree  = [];
    foreach($arr as $k => $v){
        $arr[$k]['hasSub'] = 0;
        $refer[$v['id']] = & $arr[$k]; //创建主键的数组引用
    }
    foreach($arr as $k => $v){
        $pid = $v['pid'];  //获取当前分类的父级id
        if($pid == 0){
            $arr[$k]['lev'] = 0;
            $tree[] = & $arr[$k];  //顶级栏目
        }else{
            if(isset($refer[$pid])){
                $refer[$pid]['hasSub'] = 1;
                $lev = $refer[$pid]['lev'] + 1;
                $arr[$k]['lev'] = $lev;
                if ($indent) {
                    $arr[$k]['title'] = str_repeat('&nbsp;&nbsp;', $arr[$k]['lev']*5).'├'.$arr[$k]['title'];
                }

                if ($layer) {
                    //有层级结构
                    $refer[$pid]['sub'][] = & $arr[$k]; //如果存在父级栏目，则添加进父级栏目的子栏目数组中
                } else {
                    //无层级结构
                    if (1 == $lev || (2 == $lev && $third)) {
                        foreach($tree as $g=>$h) {
                            if ($h['id'] == $pid) {
                                array_splice($tree, $g+1, 0, [$arr[$k]]);
                            }
                        }
                    }
                }
            }
        }
    }
    return $tree;
}

/**
 * 所有下级权限
 */
function convert_tree_subs($id, $arr, $onlyId=true)
{
    $ret = [];
    if ($id && $arr) {
        $refer = [];
        foreach($arr as $k => $v){
            $refer[$v['id']] = & $arr[$k]; //创建主键的数组引用
        }

        foreach($arr as $k => $v){
            $pid = $v['pid'];  //获取当前分类的父级id
            if ($v['id'] == $id || $pid == $id || in_array($pid, $ret)) {
                if ($onlyId) {
                    $ret[] = $v['id'];
                } else {
                    $ret[] = $v;
                }
            }
        }
    }

    return $ret;
}