<?php

use think\Request;
use think\Db;
use think\Cache;

/**
 * 不同环境下获取真实的IP
 * @return array|false|string
 */
if (!function_exists('get_real_client_ip')) {
    function get_real_client_ip()
    {
        // 防止重复运行代码或者重复的来访者IP
        static $realclientip = NULL;
        if ($realclientip !== NULL) {
            return $realclientip;
        }
        //判断服务器是否允许$_SERVER
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $realclientip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realclientip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $realclientip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            //不允许就使用getenv获取
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $realclientip = getenv("HTTP_X_FORWARDED_FOR");
            } elseif (getenv("HTTP_CLIENT_IP")) {
                $realclientip = getenv("HTTP_CLIENT_IP");
            } else {
                $realclientip = getenv("REMOTE_ADDR");
            }
        }

        return $realclientip;
    }
}

if (!function_exists("info")) {
    /**
     * 信息返回
     * @param string $code
     * @param string $msg
     * @param string $data
     * @return array
     */
    function info($code = '', $msg = '', $data = '')
    {
        $result = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];
        return $result;
    }
}

/**
 * 根据小时判断早上 中午 下午 傍晚 晚上
 * @param  date $h [1-24]
 * @return string
 */
function get_curr_time_section($h = '')
{
    date_default_timezone_set('Asia/Shanghai');

    //如果没有传入参数，则取当前时间的小时
    if (empty($h)) {
        $h = date("H");
    }

    $str = '';

    if ($h < 11) $str = "早上好";
    else if ($h < 13) $str = "中午好";
    else if ($h < 17) $str = "下午好";
    else if ($h < 19) $str = "傍晚好";
    else $str = "晚上好";

    return $str;
}

/**
 * 格式化的当前日期
 *
 * @return false|string
 */
function now_datetime()
{
    return date("Y-m-d H:i:s");
}

/**
 * json返回
 * @param $code
 * @param $msg
 * @param $data
 * @return \think\response\Json
 */
function json_return($code = "", $msg = "", $data = "")
{
    return json(info($code, $msg, $data));
}

/**
 * json成功返回
 * @param int $code
 * @param string $msg
 * @param string $data
 * @return \think\response\Json
 */
function json_suc($code = 0, $msg = "操作成功！", $data = "")
{
    return json(info($code, $msg, $data));
}

/**
 * json失败返回
 * @param int $code
 * @param string $msg
 * @param string $data
 * @return \think\response\Json
 */
function json_err($code = -1, $msg = "操作失败！", $data = "")
{
    return json(info($code, $msg, $data));
}

/**
 * 是否移动端访问
 * @return bool
 */
function isMobile()
{
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    if (isset ($_SERVER['HTTP_VIA'])) {
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    if (isset ($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
        );
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    if (isset ($_SERVER['HTTP_ACCEPT'])) {
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}

/**
 * 清除模版缓存 不删除 temp目录
 */
function clear_temp_cache()
{
    $temp_files = (array)glob(TEMP_PATH . DS . '/*.php');
    array_map(function ($v) {
        if (file_exists($v)) @unlink($v);
    }, $temp_files);
    return true;
}

/**
 * 重新加载配置缓存信息
 */
function loadCache()
{
    $settings = Db::name("setting")->select();
    $refer = [];
    if ($settings) {
        foreach ($settings as $k => $v) {
            $refer[$v['module']][$v['code']] = $v['val'];
        }
    }
    return $refer;
}

/**
 * 配置缓存
 * 加载系统配置并缓存
 */
function cacheSettings()
{
    Cache::set("settings", NULL);
    $settings = loadCache();
    Cache::set("settings", $settings, 0);
    return $settings;
}

/**
 * 获取配置缓存信息
 */
function getSettings($module = "", $code = "")
{
    $settings = Cache::get("settings");
    if (empty($settings)) {
        $settings = cacheSettings();
    }

    if (empty($settings)) {
        return NULL;
    }

    if (empty($code)) {
        if (array_key_exists($module, $settings)) {
            return $settings[$module];
        }
    } else {
        if (array_key_exists($module, $settings) && array_key_exists($code, $settings[$module])) {
            return $settings[$module][$code];
        } else {
            return NULL;
        }
    }
}

/**
 * 重新加载下拉表缓存信息
 */
function loadDropdownList()
{
    $data = Db::name("dropdown")->select();
    $refer = [];
    if ($data) {
        foreach ($data as $k => $v) {
            $refer[$v['module']][$v['code']] = $v['val'];
        }
    }
    return $refer;
}

/**
 * 获取下拉框，或者值
 * 没有模板名称返回所有，有模板返回对应下拉框，有code返回对应名称
 *
 * @param string $module 模板名称
 * @param string $code code
 * @param bool $hasEmpty 是否包含空值
 * @return array|mixed|null
 */
function getDropdownList($module = '', $code = '', $hasEmpty = true)
{
    $dropdown = Cache::get("dropdown");

    // 如果缓存没有数据
    if (empty($dropdown)) {
        $dropdown = loadDropdown();
    }

    if (empty($dropdown)) {
        return null;
    }

    if (empty($module)) {
        return $dropdown;
    }

    // 如果没有code
    if (empty($code)) {

        // 是否包含空值
        if ($hasEmpty) {

            $dropdownList = array("" => "");
            $dropdownList = $dropdownList + $dropdown[$module];
            return $dropdownList;
        } else {
            return $dropdown[$module];
        }
    } else {
        if (empty($dropdown[$module])) {
            return null;
        } else {
            return $dropdown[$module][$code];
        }
    }
}

/**
 * 加载下拉框
 */
function loadDropdown()
{
    Cache::set('dropdown', NULL);
    $dropdown = selDropdown();
    Cache::set('dropdown', $dropdown, 0);
    return $dropdown;
}

/**
 * 检索下拉框
 * @return array
 */
function selDropdown()
{
    $data = Db::name('dropdown')->select();
    $refer = [];
    if ($data) {
        foreach ($data as $k => $v) {
            $refer[$v['module']][$v['code']] = $v['val'];
        }
    }
    return $refer;
}

/**
 * 手机号格式检测
 * @param $str
 * @return bool
 */
function isPhone($str)
{
    return (preg_match("/^1[3456789]\d{9}$/", $str)) ? true : false;
}

/**
 * @param $key
 * @param $val
 */
function set($key, $val)
{
    // 获取token
    $token = trim(input("token"));

    // 如果没有token，应该是web登录
    if (empty($token)) {
        session($key, $val);
    } else {
        // 获取保持的数据
        $session = cache($token);

        // 如果没有数据
        if ($session) $session = array();
        $session[$key] = $val;

        // 保存缓存
        cache($token, $session);
    }
}

/**
 * @param $key
 * @return mixed
 */
function get($key)
{
    // 获取token
    $token = trim(input("token"));

    // 如果没有token，应该是web登录
    if (empty($token)) {
        return session($key);
    } else {
        // 获取保持的数据
        $session = cache($token);

        // 如果没有数据
        if ($session) $session = array();
        return $session[$key];
    }
}

/**
 * 解析单图
 * @param $image
 * @return array
 */
function generate_single_image($image)
{
    if (empty($image)) {
        return array(
            "preview" => "[]",
            "previewConfig" => "[]",
        );
    } else {
        $config[] = array(
            "url" => url("tool/deleteImage"),
            "key" => 0,
        );

        $image = array($image);
        return array(
            "preview" => json_encode($image),
            "previewConfig" => json_encode($config),
        );
    }
}

/**
 * 域名
 */
function saver()
{
    return Request::instance()->domain();
}

/**
 * 验证银行卡号是否有效(前提为16位或19位数字组合)
 *
 * @param $cardNum                      银行卡号
 * @return bool                         有效返回true,否则返回false
 */
function isBankCard($cardNum)
{
    // 第一步,反转银行卡号
    $cardNum = strrev($cardNum);

    // 第二步,计算各位数字之和
    $sum = 0;
    for ($i = 0; $i < strlen($cardNum); ++$i) {
        $item = substr($cardNum, $i, 1);
        //
        if ($i % 2 == 0) {
            $sum += $item;
        } else {
            $item *= 2;
            $item = $item > 9 ? $item - 9 : $item;
            $sum += $item;
        }
    }

    // 第三步,判断数字之和余数是否为0
    if ($sum % 10 == 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * 验证身份证号是否正确
 *
 * @param $number
 * @return bool
 */
function isIdentityCard($number)
{
    //验证长度
    if (strlen($number) != 18) {
        return false;
    }

    //验证是否符合规则
    if (!preg_match("/(\d{18})|(\d{17}(\d|X|x))/i", $number)) {
        return false;
    }

    //每位数对应的乘数因子
    $factors = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);

    //计算身份证号前17位的和
    $sum = 0;
    for ($index = 0; $index < 17; ++$index) {
        $num = substr($number, $index, 1);

        $sum += $num * $factors[$index];
    }

    //将和对11取余
    $mod = $sum % 11;

    //根据获得的余数，获取验证码
    $verifyCode = "";
    switch ($mod) {
        case 0:
            $verifyCode = "1";
            break;
        case 1:
            $verifyCode = "0";
            break;
        case 2:
            $verifyCode = "X";
            break;
        default:
            $verifyCode = 12 - $mod;
            break;
    }

    //核对校验码和身份证最后一位
    if ($verifyCode == substr($number, -1, 1)) {
        return true;
    }

    return false;
}

/**
 * 验证汉字
 * @param $str
 * @return bool
 */
function isChinese($str)
{
    return (preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $str)) ? true : false;
}

// 是否同时包含数字和英文，必须同时包含数字和英文
function isNumAndLetter($val)
{
    return (preg_match("/[A-Za-z]/", $val) && preg_match("/\d/", $val)) ? true : false;
}

// 是否超过最大长度
function isMaxLength($val, $max)
{
    return (strlen($val) <= (int)$max);
}

// 是否超过最小长度
function isMinLength($val, $min)
{
    return (strlen($val) >= (int)$min);
}

function make_order_sn()
{
    return date('YmdHis') . time() . rand(1000, 9999);
}

function isNumeric($val)

{
    return (preg_match('/^[-+]?[0-9]*.?[0-9]+$/', $val)) ? true : false;
}

/**
 * 跨域名访问
 */
function origin_header()
{
    $url = Request::instance()->domain();
    $domain = str_replace('http://', '', $url);
    $domain = str_replace('https://', '', $domain);
    $origin = [
        '127.0.0.1',
        'localhost',
        '192.168.116.1',
        '10.221.1.122'
    ];
    $str_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    if (in_array($domain, $origin)) {
        header('Access-Control-Allow-Origin:' . $str_origin);
        header("Access-Control-Request-Method:GET,POST,PUT,DELETE,OPTIONS");
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Allow-Headers:x-requested-with,content-type,Jwt-Token');
        if (Request::instance()->method() == 'OPTIONS') exit;
        if (Request::instance()->isAjax()) {
            header('Content-Type:text/json; charset=utf8');
        } else {
            header('Content-Type:text/html; charset=utf8');
        }
    }
}

/**
 * @Notes:获取轮播图缓存
 * @Author:jsl
 * @Date: 2019/9/6
 * @Time: 10:43
 * @return mixed
 * @throws Db\exception\DataNotFoundException
 * @throws Db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getShowList()
{
    $showList = Cache::get("showList");
    if (empty($showList)) {
        loadShowList();
        $showList = Cache::get("showList");
    }
    return $showList;
}

/**
 * @Notes:重新加载轮播图缓存
 * @Author:jsl
 * @Date: 2019/9/6
 * @Time: 10:42
 * @throws Db\exception\DataNotFoundException
 * @throws Db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function loadShowList()
{
    Cache::set("showList", NULL);
    $showList = Db::table("show")->where(['cate'=>1])->order("sort desc")->select();
    foreach ($showList as $key => $value) {
        $showList[$key]["img"] = saver() . $value["img"];
    }
    Cache::set("showList", $showList);
}

/**
 * @Notes:获取公告缓存
 * @Author:jsl
 * @Date: 2019/9/6
 * @Time: 10:45
 * @return mixed
 * @throws Db\exception\DataNotFoundException
 * @throws Db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getNoticeList()
{
    $NoticeList = Cache::get("NoticeList");
    if (empty($NoticeList)) {
        loadNoticeList();
        $NoticeList = Cache::get("NoticeList");
    }
    return $NoticeList;
}

/**
 * @Notes:重新加载公告缓存
 * @Author:jsl
 * @Date: 2019/9/6
 * @Time: 10:45
 * @throws Db\exception\DataNotFoundException
 * @throws Db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function loadNoticeList()
{
    Cache::set("NoticeList", NULL);
    $NoticeList = Db::table("notice")->order("id desc")->select();
    Cache::set("NoticeList", $NoticeList);
}

/**
 * @Notes:获取商品分类缓存
 * @Author:jsl
 * @Date: 2019/9/6
 * @Time: 10:45
 * @return mixed
 * @throws Db\exception\DataNotFoundException
 * @throws Db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getCategoryList()
{
    $CategoryList = Cache::get("CategoryList");
    if (empty($CategoryList)) {
        loadCategoryList();
        $CategoryList = Cache::get("CategoryList");
    }
    return $CategoryList;
}

/**
 * @Notes:重新加载商品分类缓存
 * @Author:jsl
 * @Date: 2019/9/6
 * @Time: 10:45
 * @throws Db\exception\DataNotFoundException
 * @throws Db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function loadCategoryList()
{
    Cache::set("CategoryList", NULL);
    $CategoryList = Db::table("category")->where('status', 2)->order("sortOrder desc")->select();
//    foreach ($CategoryList as $key=>$value){
//        $CategoryList[$key]['img']=saver().$value['img'];
//    }
    Cache::set("CategoryList", $CategoryList);
}

/**
 * @Notes:
 * @Author:jsl
 * @Date: 2019/9/19
 * @Time: 10:34
 * @return false|mixed|PDOStatement|string|\think\Collection
 * @throws Db\exception\DataNotFoundException
 * @throws Db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getDistributionList()
{
    $DistributionList = Cache::get("DistributionList");
    if (empty($DistributionList)) {
        $DistributionList = loadDistributionList();
    }
    return $DistributionList;
}

/**
 * @Notes:加载分销配置
 * @Author:jsl
 * @Date: 2019/9/19
 * @Time: 10:34
 * @return false|PDOStatement|string|\think\Collection
 * @throws Db\exception\DataNotFoundException
 * @throws Db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function loadDistributionList()
{
    Cache::set("DistributionList", NULL);
    $DistributionList = Db::table("distribution")->field('id,parameter,level')->select();
    $list = [];
    if ($DistributionList) {
        foreach ($DistributionList as $key => $value) {
            $list[$value['level']] = bcdiv($value['parameter'], 100, 2);
        }
    }
    Cache::set("DistributionList", $list);
    return $list;
}

/**
 * @Notes:余额变化记录
 * @Author:jsl
 * @Date: 2019/9/19
 * @Time: 10:55
 * @param $userId 用户id
 * @param $num  数量
 * @param $code 1加-1减
 * @param $type 类型
 * @param $info 详情
 * @return bool true和false
 * @throws \think\Exception
 * @throws Db\exception\DataNotFoundException
 * @throws Db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function balanceLog($userId, $num, $code, $type, $info, $oid = '')
{
    $member = Db::table('member')->where(['id' => $userId])->find();
    if ($code > 0) {
        $res[] = Db::table('member')->where('id=' . $userId)->setInc('m_total', $num);
        $after = $member['m_total'] + $num;
    } else {
        $res[] = Db::table('member')->where('id=' . $userId . ' and m_total>=' . $num)->setDec('m_total', $num);
        $after = $member['m_total'] - $num;
    }
    $data = [
        'b_total' => $num,
        'b_before' => $member['m_total'],
        'b_after' => $after,
        'b_createTime' => now_datetime(),
        'b_type' => $type,
        'b_info' => $info,
        'b_isplus' => $code,
        'b_mid' => $userId,
        'b_oid' => $oid
    ];

    $res[] = Db::table('balancelog')->insert($data);
    if (in_array(false, $res)) {
        return false;
    }
    return true;
}

/**
 * @Notes:余额变化记录
 * @Author:jsl
 * @Date: 2019/9/19
 * @Time: 10:55
 * @param $userId 用户id
 * @param $num  数量
 * @param $code 1加-1减
 * @param $type 类型
 * @param $info 详情
 * @return bool true和false
 * @throws \think\Exception
 * @throws Db\exception\DataNotFoundException
 * @throws Db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function integralLog($userId, $num, $code, $type, $info, $nid = 0, $item_id = '')
{
    $member = Db::table('member')->where(['id' => $userId])->find();
    if ($code > 0) {
        if ($type == 1) {
            $res[] = Db::table('member')->where('id=' . $userId)->setInc('m_integral_num', $num);
        }
        $res[] = Db::table('member')->where('id=' . $userId)->setInc('m_integral', $num);
        $after = $member['m_integral'] + $num;
    } else {
        $res[] = Db::table('member')->where('id=' . $userId . ' and m_integral>=' . $num)->setDec('m_integral', $num);
        $after = $member['m_integral'] - $num;
    }
    $data = [
        'b_total' => $num,
        'b_before' => $member['m_integral'],
        'b_after' => $after,
        'b_createTime' => now_datetime(),
        'b_type' => $type,
        'b_info' => $info,
        'b_isplus' => $code,
        'b_mid' => $userId,
        'b_nid' => $nid,
        'b_item_id' => $item_id
    ];
    $res[] = Db::table('integral')->insert($data);
    if (in_array(false, $res)) {
        return false;
    }
    return true;
}

/**
 * @Notes:增加团队人数
 * @Author:jsl
 * @Date: 2019/9/20
 * @Time: 11:36
 * @param $id
 * @return bool
 * @throws \think\Exception
 * @throws Db\exception\DataNotFoundException
 * @throws Db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function teamNum($id)
{
    $member = Db::table('member')->where(['m_id' => $id])->find();
    if ($member['m_pathtree']) {
        $where['id'] = array('in', $member['m_pathtree']);
        //给关系树上所有的上级都加1
        $res = Db::table('member')->where($where)->setInc('teamNum', 1);
        if ($res == false) {
            return false;
        }
        //读取出升级条件
        $vipnum = getSettings('vipnum', 'vipnum');
        //将条件转换成数组
        $vipnum = explode("-", $vipnum);
        //数组倒转
        $vipnum = array_reverse($vipnum);
        $path = $member['m_pathtree'];
        //将关系树转换成数组
        $path = explode(",", $path);
        //将数组倒转、去空
        $path = array_filter(array_reverse($path));
        //循环所有的上级
        foreach ($path as $value) {
            //检测上级时候存在
            $parent = Db::table('member')->where(['m_id' => $value, 'm_isDelete'])->find();
            if (empty($parent)) {
                continue;
            }
            //循环后台配置的升级条件
            foreach ($vipnum as $key => $val) {
                //满足升级条件的
                if ($parent['teamNum'] >= $val) {
                    Db::table('member')->where(['id' => $value])->update(['m_level' => $key + 1]);
                    break;
                }
            }
        }
        return true;
    }
    return true;
}

/**
 * 省级联动
 *
 * @author: Gavin
 * @time: 2019/9/7 10:09
 */
function getRegionList($parentId)
{
    if (isset($parentId)) {
        $list = array("" => "");
        $regionLists = db("region")->where("parent_id='$parentId'")->cache(true)->select();
        for ($j = 0; $j < count($regionLists); $j++) {
            $region = $regionLists[$j];
            $key = $region['id'];
            $list[$key] = $region['name'];
        }
        return $list;
    } else {
        return null;
    }
}

/**
 * 发送消息
 *
 * @param $title 消息标题
 * @param $message 消息内容
 * @param $type 类型 1发货通知2退款通知3图文消息
 * @param $category 分类 1系统消息 2推送消息
 * @param int $to 接收人
 * @author: Gavin
 * @time: 2019/9/18 9:12
 */
function send_message($title, $message, $type, $category, $to = 0, $item_id = 0)
{
    $messages = array(
        'title' => $title,
        'message' => $message,
        'type' => $type,//类型 1商家消息2买家个体消息3买家全体消息
        'category' => $category,//分类 1系统消息 2活动消息 3订单消息
        'send_time' => now_datetime(),
        'to' => $to,
        'item_id' => $item_id,
    );
    return db('message')->insert($messages);

}

//解析多图
function generate_multi_image($imageList)
{
    if (empty($imageList)) {
        return array(
            "preview" => "[]",
            "previewConfig" => "[]",
        );
    } else {
        $config = array();
        foreach ($imageList as $key => $image) {
            $data = array(
                "url" => url("Tool/deleteImage"),
                "key" => $key,
            );
            $config[] = $data;
        }

        return array(
            "preview" => json_encode($imageList),
            "previewConfig" => json_encode($config),
        );
    }
}

/**
 * 旧的公排制度
 * @param $id
 * @return array
 * @throws Db\exception\DataNotFoundException
 * @throws Db\exception\ModelNotFoundException
 * @throws \think\Exception
 * @throws \think\exception\DbException
 * @throws \think\exception\PDOException
 */
function operationOrders($id)
{
    $orders = db("onlineorder")->lock(true)->where(array("o_id" => $id))->find();
    if (empty($orders)) {
        return errorCode('订单不存在');
    }
    //用户详情
    $member_info = db('member')->where(['id' => $orders['o_mid']])->find();
    //查询上级
    $parent = db('member')->where('id', $member_info['m_fatherId'])->find();
    //获取比例
    $bili = getSettings('bili', 'bili') / 100;
    //算出20%的奖金
    $product_price = getSettings('product_price', 'product_price');
    $total = $product_price * $bili;
    //推荐奖
    $tuijian = getSettings('tuijian', 'tuijian');

    if ($parent) {
        $res2 = balanceLog($member_info['m_fatherId'], $tuijian, 1, 1, '推荐奖');
        if ($res2 == false) {
            return errorCode('推荐奖发放失败');
        }
        //奖金池奖金
        $bonuspool = db('bonuspool')->where(['id' => 1])->value('total');

        if ($parent['identity'] == 1 && $member_info['identity'] == 1) {
            //把20%给上级
            $res2 = balanceLog($member_info['m_fatherId'], $total, 1, 1, '推客奖励');
            if ($res2 == false) {
                return errorCode('推客奖励发放失败');
            }
        } else {
            if ($bonuspool < $product_price) {
                //没达到3100的话  就加上20%的奖金
                $res_bonuspool = db('bonuspool')->where(['id' => 1])->setInc('total', $total);
                if ($res_bonuspool == false) {
                    return errorCode('奖金增加失败');
                }
                //判断加上20%的奖金是否达到商品的同一价格
                if ($bonuspool + $total >= $product_price) {
                    //查询第一个排队的人
                    $queues_info = db('queues')->where(['is_end' => 2])->find();
                    //如果有第一个排队的人
                    if ($queues_info) {
                        $member_queues = db('member')->where(['id' => $queues_info['member_id']])->find();
                        if (empty($member_queues)) {
                            return errorCode('第一个公排的人不存在');
                        }
                        //把奖金池的奖金给第一个人
                        $res3 = balanceLog($queues_info['member_id'], $queues_info['total'], 1, 1, '排队完成');
                        if ($res3 == false) {
                            return errorCode('奖金发放失败');
                        }
                        //结束第一个人的排队
                        $queues_info_res = db('queues')->where(['id' => $queues_info['id']])->update(['over_time' => now_datetime(), 'is_end' => 1]);
                        if ($queues_info_res == false) {
                            return errorCode('结束排队失败');
                        }
                        //刷新奖金池
                        $res_bonuspool = db('bonuspool')->where(['id' => 1])->setField('total', 0);
                        if ($res_bonuspool == false) {
                            return errorCode('奖金池刷新失败');
                        }
                    }
                }
            } else {
                //查询第一个排队的人
                $queues_info = db('queues')->where(['is_end' => 2])->find();
                if ($queues_info) {
                    //如果有第一个排队的人
                    //把奖金池的奖金给第一个人
                    $res3 = balanceLog($queues_info['member_id'], $queues_info['total'], 1, 1, '排队完成');
                    if ($res3 == false) {
                        return errorCode('奖金发放失败');
                    }
                    //结束第一个人的排队
                    $queues_info_res = db('queues')->where(['id' => $queues_info['id']])->update(['over_time' => now_datetime(), 'is_end' => 1]);
                    if ($queues_info_res == false) {
                        return errorCode('结束排队失败');
                    }
                    //刷新奖金池
                    $res_bonuspool = db('bonuspool')->where(['id' => 1])->setField('total', $total);
                    if ($res_bonuspool == false) {
                        return errorCode('奖金池刷新失败');
                    }
                }
            }
        }
    }
    $o_paiduisn = '';
    //如果选择了排队
    if ($orders['o_is_queues'] == 2) {
        //选择排队
        $queues_data = [
            //用户id
            'member_id' => $member_info['id'],
            //排队号码
            'sn' => date('YmdHis') . $member_info['id'] . rand(100, 999),
            //排队金额
            'total' => $product_price,

            'total_num' => 0,
            //创建时间
            'create_time' => now_datetime(),
            //不结束
            'is_end' => 2,
        ];
        //新增排队
        $res = db('queues')->insertGetId($queues_data);
        if ($res == false) {
            return errorCode('新增排队失败');
        }
        $o_paiduisn = $res;
    }
    return successCode($o_paiduisn);
}

function operationOrder($id)
{
//检索订单
    $orders = db("onlineorder")->lock(true)->where(array("o_id" => $id))->find();
//    dump($orders);die;
    if (empty($orders)) {
        return errorCode('订单不存在');
    }
    //订单详情
    $detail_order = db('orderdetails')->where('d_orderId', $orders['o_id'])->find();
    //商品详情
    $product_info = db('product')->where(['id' => $detail_order['d_productId']])->find();
    //用户详情
    $member_info = db('member')->where(['id' => $orders['o_mid']])->find();
    //查询上级
    $parent = db('member')->where('id', $member_info['m_fatherId'])->find();
    //获取比例
    $bili = getSettings('bili', 'bili') / 100;
    //算出20%的奖金
    $product_price = getSettings('product_price', 'product_price');
    $total = $product_price * $bili;

    if (empty($parent)) {
        //上级不存在
        //商品的同一价格
        $product_price = getSettings('product_price', 'product_price');
        //奖金池奖金
        $bonuspool = db('bonuspool')->where(['id' => 1])->value('total');
        if ($bonuspool < $product_price) {
            //没达到3100的话  就加上20%的奖金
            $res_bonuspool = db('bonuspool')->where(['id' => 1])->setInc('total', $total);
            if ($res_bonuspool == false) {
                return errorCode('奖金增加失败');
            }
            //判断加上20%的奖金是否达到商品的同一价格
            if ($bonuspool + $total >= $product_price) {
                //查询第一个排队的人
                $queues_info = db('queues')->where(['is_end' => 2])->find();
                //如果有第一个排队的人
                if ($queues_info) {
                    $member_queues = db('member')->where(['id' => $queues_info['member_id']])->find();
                    if (empty($member_queues)) {
                        return errorCode('第一个公排的人不存在');
                    }
                    //把奖金池的奖金给第一个人
                    $res3 = balanceLog($queues_info['member_id'], $queues_info['total'], 1, 1, '排队完成');
                    if ($res3 == false) {
                        return errorCode('奖金发放失败');
                    }
                    //结束第一个人的排队
                    $queues_info_res = db('queues')->where(['id' => $queues_info['id']])->update(['over_time' => now_datetime(), 'is_end' => 1]);
                    if ($queues_info_res == false) {
                        return errorCode('结束排队失败');
                    }
                    //刷新奖金池
                    $res_bonuspool = db('bonuspool')->where(['id' => 1])->setField('total', 0);
                    if ($res_bonuspool == false) {
                        return errorCode('奖金池刷新失败');
                    }
                }
            }
        } else {
            //查询第一个排队的人
            $queues_info = db('queues')->where(['is_end' => 2])->find();
            if ($queues_info) {
                //如果有第一个排队的人
                //把奖金池的奖金给第一个人
                $res3 = balanceLog($queues_info['member_id'], $queues_info['total'], 1, 1, '排队完成');
                if ($res3 == false) {
                    return errorCode('奖金发放失败');
                }
                //结束第一个人的排队
                $queues_info_res = db('queues')->where(['id' => $queues_info['id']])->update(['over_time' => now_datetime(), 'is_end' => 1]);
                if ($queues_info_res == false) {
                    return errorCode('结束排队失败');
                }
                //刷新奖金池
                $res_bonuspool = db('bonuspool')->where(['id' => 1])->setField('total', $total);
                if ($res_bonuspool == false) {
                    return errorCode('奖金池刷新失败');
                }
            }
        }
    } else {
        $tuijian = getSettings('tuijian', 'tuijian');
        //给上级加推荐奖奖金
        $res2 = balanceLog($member_info['m_fatherId'], $tuijian, 1, 1, '推荐奖');
        if ($res2 == false) {
            return errorCode('推荐奖发放失败');
        }
        //查询上级购买的不排队的订单
        $parent_order = db('onlineorder')->where('o_mid=' . $parent['id'] . ' and o_is_queues=1 and o_queues>0')->find();
        //存在不排队的订单
        if ($parent_order) {
            //把20%给上级
            $res2 = balanceLog($member_info['m_fatherId'], $total, 1, 1, '不排队订单获得');
            if ($res2 == false) {
                return errorCode('不排队奖励发放失败');
            }
            //扣除订单的奖金次数
            $parent_order_res = db('onlineorder')->where('o_id=' . $parent_order['o_id'])->setDec('o_queues', 1);
            if ($parent_order_res == false) {
                return errorCode('推荐次数减少失败');
            }
        } else {
            //没有未排队的订单
            //商品统一价格
            $product_price = getSettings('product_price', 'product_price');
            //奖金池奖金
            $bonuspool = db('bonuspool')->where(['id' => 1])->value('total');
            //判断奖金池奖金是否小于商品统一价格
            if ($bonuspool < $product_price) {
                //奖金池加上20%
                $res_bonuspool = db('bonuspool')->where(['id' => 1])->setInc('total', $total);
                if ($res_bonuspool == false) {
                    return errorCode('奖金增加失败');
                }
                //判断加上20%的奖金是否达到商品的同一价格
                if ($bonuspool + $total >= $product_price) {
                    //查询第一个排队的人
                    $queues_info = db('queues')->where(['is_end' => 2])->find();
                    //存在第一个排队的人
                    if ($queues_info) {
                        //把奖金池奖金给第一个人
                        $res3 = balanceLog($queues_info['member_id'], $queues_info['total'], 1, 1, '排队完成');
                        if ($res3 == false) {
                            return errorCode('奖金发放失败');
                        }
                        //结束第一个人的排队
                        $queues_info_res = db('queues')->where(['id' => $queues_info['id']])->update(['over_time' => now_datetime(), 'is_end' => 1]);
                        if ($queues_info_res == false) {
                            return errorCode('结束排队失败');
                        }
                        //刷新奖金池
                        $res_bonuspool = db('bonuspool')->where(['id' => 1])->setField('total', 0);
                        if ($res_bonuspool == false) {
                            return errorCode('刷新奖金池失败');
                        }
                    }
                }
            } else {
                //查询第一个排队的人
                $queues_info = db('queues')->where(['is_end' => 2])->find();
                //存在第一个排队的人
                if ($queues_info) {
                    //把奖金池奖金给第一个人
                    $res3 = balanceLog($queues_info['member_id'], $queues_info['total'], 1, 1, '排队完成');
                    if ($res3 == false) {
                        return errorCode('奖金发放失败');
                    }
                    //结束第一个人的排队
                    $queues_info_res = db('queues')->where(['id' => $queues_info['id']])->update(['over_time' => now_datetime(), 'is_end' => 1]);
                    if ($queues_info_res == false) {
                        return errorCode('结束排队失败');
                    }
                    //刷新奖金池
                    $res_bonuspool = db('bonuspool')->where(['id' => 1])->setField('total', $total);
                    if ($res_bonuspool == false) {
                        return errorCode('刷新奖金池失败');
                    }
                }
            }
        }
    }
    $o_paiduisn = '';
    //如果选择了排队
    if ($orders['o_is_queues'] == 2) {
        //选择排队
        $queues_data = [
            //用户id
            'member_id' => $member_info['id'],
            //排队号码
            'sn' => date('YmdHis') . $member_info['id'] . rand(100, 999),
            //排队金额
            'total' => $product_info['p_oldprice'],

            'total_num' => 0,
            //创建时间
            'create_time' => now_datetime(),
            //不结束
            'is_end' => 2,
        ];
        //新增排队
        $res = db('queues')->insertGetId($queues_data);
        if ($res == false) {
            return errorCode('新增排队失败');
        }
        $o_paiduisn = $res;
    }
    return successCode($o_paiduisn);

}

/**
 * 错误码
 * @param $msg
 * @return array
 */
function errorCode($msg)
{
    return ['code' => 0, 'msg' => $msg];
}

/**
 * 正确码
 * @param $msg
 * @return array
 */
function successCode($msg)
{
    return ['code' => 1, 'msg' => $msg];
}

/*
  * 生成定长22位的订单码
  * */
function MyOrderNo22()
{
    $code = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    $code .= randCodeM(22 - strlen($code), 1);
    return $code;
}

/*
  * 产生随机字符
  * $length  int 生成字符传的长度
  * $numeric  int  , = 0 随机数是大小写字符+数字 , = 1 则为纯数字
 */
function randCodeM($length, $numeric = 0)
{
    $seed = base_convert(md5(print_r($_SERVER, 1) . microtime()), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    $hash = '';
    $max = strlen($seed) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $seed[mt_rand(0, $max)];
    }
    return $hash;
}

/**
 * 消息模版
 * @param $id
 * @return array|false|PDOStatement|string|\think\Model
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function messageTemplate($id)
{
    return db('message_template')->where(['id' => $id])->find();
}

/**
 * 生成邀请码
 * @return int
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function invter()
{
    $pattern = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
    $a = $pattern[rand(0, strlen($pattern) - 1)];
    $b = $pattern[rand(0, strlen($pattern) - 1)];
    $c = $pattern[rand(0, strlen($pattern) - 1)];
    $d = $pattern[rand(0, strlen($pattern) - 1)];
    $str = rand(10000, 99999);
    $coded = $a . $b . $c . $d . $str;
    $member = db("member")->where("m_invitation_code", $coded)->find();
    if (!empty($member)) {
        return invter();
    }
    return $coded;
}

/**
 * 字符串截取用省略号代替
 * @param $text
 * @param $length
 * @return string
 */
function subtext($text, $length)
{
    if (mb_strlen($text, 'utf8') > $length) {
        return mb_substr($text, 0, $length, 'utf8') . '……';
    } else {
        return $text;
    }

}

function imgArr($img){
    $str='';
    if($img){
        $imgs=explode(',',$img);
        foreach ($imgs as $key=>$value){
            $str.="<img src=".$value.">";
        }
    }
    echo $str;
}
/**
 * @return \ArrayObject
 */
function arrayObject(){
    return new \ArrayObject();
}

