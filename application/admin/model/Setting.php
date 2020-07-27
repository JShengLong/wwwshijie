<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-06-26
 * Time: 10:19
 */

namespace app\admin\model;


use think\Cache;
use think\Model;

class Setting extends Model
{
    /**
     * 初始化
     */
    protected static function init()
    {
        $event_arr = ['afterWrite', 'afterDelete'];
        $model_name = self::getModel()->name;
        foreach ($event_arr as $k => $v) {
            self::{$v}(function ($model) use ($model_name) {
                Cache::clear($model_name . 'cache_data');
            });
        }
    }
}