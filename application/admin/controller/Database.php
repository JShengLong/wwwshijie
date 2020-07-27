<?php

namespace app\admin\controller;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/8
 * Time: 15:03
 */
use think\Db;
use think\Request;
use app\admin\lib\Database as Data;

class Database extends Right
{
    protected $databaseConfig =array();
    public function __construct()
    {
        parent::__construct();
        $this->databaseConfig = array(
            //数据库备份根路径（路径必须以 / 结尾）
            'path' => ROOT_PATH . '/Data/',
            //数据库备份卷大小 （该值用于限制压缩后的分卷最大长度。单位：B；建议设置20M）
            'part' => 20971520,
            //数据库备份文件是否启用压缩 （压缩备份文件需要PHP环境支持gzopen,gzwrite函数）
            'compress' => 0,
            //数据库备份文件压缩级别 （数据库备份文件的压缩级别，该配置在开启压缩时生效） 1普通 4一般 9最高
            'level' => 9,
        );
    }

    public function index()
    {
        $list = Db::query('SHOW TABLE STATUS');
        $list = array_map('array_change_key_case', $list); //全部小写
        foreach ($list as $key => $value) {
            $list[$key]['data_length'] = $this->formatSizeUnits($value['data_length']);
            if ($value['auto_increment'] == null) {
                $list[$key]['auto_increment'] = 0;
            }
        }
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    /**
     * 备份数据库
     */
    public function export()
    {
        //读取备份配置
        $config = $this->databaseConfig;
        if (!is_dir($config['path'])) {
            mkdir($config['path'], 0755, true);
        }
        //检查是否有正在执行的任务
        $lock = "{$config['path']}backup.lock";
        if (is_file($lock)) {
            return json_err(-1,'检测到有一个备份任务正在执行，请稍后再试！');
//            return $this->error('检测到有一个备份任务正在执行，请稍后再试！');
        } else {
            //创建锁文件
            file_put_contents($lock, time());
        }
        //检查备份目录是否可写
        if (!is_writeable($config['path'])) {
            return json_err(-1,'备份目录不存在或不可写，请检查后重试！');
//            return $this->error('备份目录不存在或不可写，请检查后重试！');
        }
        //生成备份文件信息
        $file = array(
            'name' => date('Ymd-His', time()),
            'part' => 1,
        );
        //缓存要备份的表
        //创建备份文件
        $Database = new Data($file, $config);
        if (false == $Database->create()) {
            return json_err(-1,'初始化失败，备份文件创建失败！');
//            return $this->error('初始化失败，备份文件创建失败！');
        }
        $list = Db::query('SHOW TABLE STATUS');
        $list = array_map('array_change_key_case', $list); //全部小写
        foreach ($list as $key => $value) {
            $Database->backup($value['name'], 0);
        }
        unlink($config['path'] . 'backup.lock');
        return json_suc();
    }
}