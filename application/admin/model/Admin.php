<?php

namespace app\admin\model;


use think\Config;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;
use think\Session;

class Admin extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * 用户登录
     * @param string $account
     * @param string $password
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    static public function Login($account = "", $password = "")
    {
        // 登录频率限制（如果登录失败，记录session，频率超过一定数值直接打回）
        $last_login_fail = Session::get("last_login_fail", "admin");
        $login_fail_times = Session::get("login_fail_times", "admin");

        $login_fail_limit = Config::get("login_fail_times");
        $login_fail_unblock = Config::get("login_fail_unblock");
        $login_fail_unblock_seconds = $login_fail_unblock * 60 * 60;

        if ($login_fail_times && $login_fail_limit < $login_fail_times && $login_fail_unblock_seconds > time() - $last_login_fail) {
            return info(-1, "您已连续登录失败{$login_fail_limit}次，请{$login_fail_unblock}小时后重试！");
        }

        if (!$account || !$password) {
            return info(-1, "登录参数错误！");
        }

        $code = -1;

        // 登录记录
        $log = [];
        $log['ip'] = get_real_client_ip();
        $log['port'] = $_SERVER["SERVER_PORT"];
        $log['browser'] = $_SERVER['HTTP_USER_AGENT'];
        $log['user'] = input("account");
        $log['create_time'] = date('Y-m-d H:i:s');
        $log['status'] = 0;

        // 登录
        $admin = self::where("account", $account)->find();

        if ($admin) {
            if (1 == $admin['is_disable']) {
                $msg = "该账号已被禁用！";
            } else {
                if (password_verify($password, $admin['password'])) {
                    if (password_needs_rehash($admin['password'], PASSWORD_DEFAULT)) {
                        $admin['password'] = $password;
                        $admin->save();
                    }
                    $log['status'] = 1;
                    $code = 1;
                    $msg = "登录成功！";
                    Session::set('uid', $admin['id'], 'admin');
                    Session::set('uinfo', $admin, 'admin');
                    //清除连续登录失败次数
                    Session::set('login_fail_times', NULL, 'admin');
                } else {
                    $msg = "密码错误！";
                }
            }
        } else {
            $msg = "账号不存在！";
        }

        if (-1 == $code) {
            // 最近登录失败时间
            Session::set("last_login_fail", time(), "admin");
            // 累计连续登录失败次数（登录成功会清零）
            $times = Session::get("login_fail_times", "admin");
            if ($times) {
                Session::set("login_fail_times", $times + 1, "admin");
            } else {
                Session::set("login_fail_times", 1, "admin");
            }
        }

        $log['note'] = $msg;
        $insertLog = Db::table("adminloginlog")->insert($log);
        if (!$insertLog) {
            $code = -1;
            $msg = "登录日志写入失败！";
        }

        return info($code, $msg);
    }

    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }
}