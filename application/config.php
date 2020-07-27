<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------

    'sms'                    => [
        'code_failure_time' => '5',//验证码失效时间，单位：分钟
        'resend'            => '60',//重新发送的时间，单位：秒
    ],
    //桥通短信账号密码
    'QtSms'                  => [
        'username' => 'QTTX010817',//账号
        'password' => 'lv112358',//密码
        'template' => '您好，您的验证码是{$code}。如非本人操作请勿泄露给他人。',
    ],
    //阿里巴巴短信服务
    'ALiBaBaSendSms'         => [
        'accessKeyId'     => 'LTAI4GGfN8cJAMcJauPMbkhf',
        'accessKeySecret' => 'i5cODmPDWcstpTAzqcxqDbFhSFX4w6',
        'region'          => 'cn-hangzhou',
        'SignName'        => '食界集采',//短信签名名称
        'TemplateCode'    => 'SMS_196618191',//短信模板ID

    ],
    //聚合短信服务
    'JuHeSendSms'            => [
        'key'     => '4fe2afce18b6ed90de06d79d836a0b98',//您申请的APPKEY
        'tpl_id'  => '197185',//您申请的短信模板ID，根据实际情况修改
        'url'     => 'http://v.juhe.cn/sms/send',//请求URL
        'sndName' => '万泽商城'//签名
    ],
    //快递100
    'kuaidi100'              => [
        'customer' => "EB1CD137B25224C049679D213CEAFB3A",
        'key'      => "grfSqzCC8379",
    ],
    //极光推送
    'jpush'                  => [
        'app_key'       => '52adfc4320c3a49d6fe4de16',
        'master_secret' => '6ae7cd8efd78e2a4e4051c47',
    ],
    'ypay_wx'                => [
        'appid'       => 'wx225b5284d8002417', // APP APPID
        'app_id'      => '', // 公众号 APPID
        'miniapp_id'  => 'wx225b5284d8002417', // 小程序 APPID
        'mch_id'      => '1562413051',//商户号
        'key'         => '1maRkpJFcIsst5PIPrJsWLI60fgo9P1f',
        'notify_url'  => '/index/pay/notify',
        'cert_client' => './cert/apiclient_cert.pem', // optional，退款等情况时用到
        'cert_key'    => './cert/apiclient_key.pem',// optional，退款等情况时用到
        'log'         => [ // optional
            'file'     => './logs/wechat.log',
            'level'    => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
            'type'     => 'single', // optional, 可选 daily.
            'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
        ],
        'http'        => [ // optional
            'timeout'         => 5.0,
            'connect_timeout' => 5.0,
            // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
        ],
//            'mode' => 'service', // optional, dev/hk;当为 `hk` 时，为香港 gateway。
    ],
    'ypay_ali'               => [
        'app_id'         => '2019120569712023',
        'notify_url'     => '/index/pay/notify',
        'return_url'     => 'http://www.aaa.com/',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiiCRMMUhk1Hj5UD61ZH9XaEHXeirprtiziU5zgYgeixqPOOMR8w8Iwpcu3JAhDPcohtG5IijRU6oMGh34mDYb7ze6h8Vx6wmtU43sPKwCb3jmQG9P/f9gD8no9p1/wB0OHL94EILH0H7eosDOBbGhCFD/R1oLtKCH6vfipKQgESELPbeeenP6X7RxotXk3asyGFwqBWzaHe8/1n7xF5xL6T92gevXf5mzwq1xfd1TANH4mVzA+uSS+2+PgfDXNWaf+1iXUFKpA8NUu5selMc3LhNaB9vKo8SkmjOh/rUpyhAj4H6nRqiODRhi0Tc+n7/WShXSRK2VrpYUyrzN1Q2OwIDAQAB',
        'private_key'    => 'MIIEpAIBAAKCAQEAiWSBl41+GtZIV3eCHEvWCd4+/RvW2B/omwiT03/la+ktbgTPO+dDV5esG2kFPGem7lKjM3cOrfwCs5HUzRv9YgNTPYeJDGbRESxGs2rLIK9pjp6Rqvj2lsk0gLodzl1y8MBThx5TIyVnFBIlIbc27O5U1LKeQc8RPP76STqQ9REcOekWXOD3qEUSW/CODkP3kU/GF/CL/Pf6+7iNg7Z35URU5DxW7YPjAC/LVnR4pY4hs8WjbOeqw8pDYVr/fzthpa5Pl3OFg9KjnocGv+ewuyy3kvpe9oUgXBNwdD997lU5EruZlpyrYLaZp1ljj0J2/NY/q2jc1moUsQ9wCzIDMwIDAQABAoIBAFSQqjsv+dhm4CwGAO2Nt/Zm9vovcnOgYDlcdDCP3hQnd7DqAOvsEkUNs/9DCtf9LIAwGQeKVly8aqVAM2zzt2fmiAutKquZdUFOsU2pg9FgZmZEL4C+sHhq64f4BnSIjECnAEKAZceSM/nkmTpBXOtEXq50KnhzDTcv8bm09JgBwmBKbsoP2wLTnAS+NhUS6ENbF1eRlHR0JTGw1gsIj0NDyecp1eWYYHt5605w7J6iOG2xg2DbvJLqQhT/n5FhDqanfKunjjGuQSsQtMK2DpxTjtv1IPQIG2ndBdQeTcPtAKb9o3T0+OlzQjGXWdi3CBeeHVUDp+Orl3DR6rWHtuECgYEAy/50Y+lEhhE1FEHA5oPsFXmYTfs8JddbwK7XWFdiNdowDHxumOtv+UlVnanwcI7vYqizurjZXb0+QTyGEAkiC39TKcfU2KBTZdlqNP7nx20jUEISlOncztn/mnXnJkY1/NmIPkTQBLBrk4x+djsX91uannMeVKC3+W8hKJfCrSsCgYEArGtaZ06yTncFpIPAETmI9SFNxbLd4PYy9NI+AY12gEokj2pUyUurKMKT60ndwzhnIfIXS+7Q8p+quy72Y88ytYmjSOyyjZsbgk1rt/QIMPp0NJyRJI3WaMYr2kBFNxXvTcPNtdplYj8mGlgtPjtfGthk8EndRGWM+R/HVztEThkCgYEAqPefWJKDG7ltCdZc/ZMQHcmWCiGrdHyplzQ7UwUuaATN/8mhojCBky7XJ7z3V4RNbJ7oICW0hhyWUFrdgLLP7E4OiHDpMHW8HoSzoCnzuhAATB4uXgJHz15qhXpbeCx303QrkchVtUycDp80sKHJ/C14KP6ZhOeK+6S9Bm6N5fkCgYAO20Uz550Hk8vhrBSNp2Z8sLzxzwK9Upl7bO441gai8UCLNv9NRP7fiTyTpo68Noz0aNbHDTHl5gohDN/gI8dbyxtNuk98UiQhqygf9qAKEFqY+fCrtKkH6CN5L9aG85XmHnwIMmRP/d77oCNt3FHKj2DLL6IoNZPbF3jmtE2jaQKBgQCzHKzOMFdMHFUVSGjhCwsGRebfcXX0T1AU5wZgSRbfIi9L+yzvbv2BTfFzVVUlNRbZKgFyu45JKRKb2y6wFtI/tQvC4T5umxlbGZQJVxs6OVESKHhnb2kwVxxQ+VVEH+Yj6KI87WRkLSR2QOfoizMAha7B8Mr6IA5smnpc0/MLxA==',
        'log'            => [ // optional
            'file'     => './logs/alipay.log',
            'level'    => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
            'type'     => 'single', // optional, 可选 daily.
            'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
        ],
        'http'           => [ // optional
            'timeout'         => 5.0,
            'connect_timeout' => 5.0,
            // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
        ],
//            'mode' => 'dev', // optional,设置此参数，将进入沙箱模式
    ],
    'ali_oss'=>[
        'accessKeyId'=>'LTAI4GGfN8cJAMcJauPMbkhf',
        'accessKeySecret'=>'i5cODmPDWcstpTAzqcxqDbFhSFX4w6',
        'bucket'=>'shijiejicai',
    ],
    //token有效期，单位秒，0：永久有效
    'token_expire'           => 12 * 60 * 60,
    // 应用调试模式
    'app_debug'              => true,
    // 应用Trace
    'app_trace'              => false,
    // 应用模式状态
    'app_status'             => '',
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => [],
    // 扩展函数文件
    'extra_file_list'        => [THINK_PATH . 'helper' . EXT],
    // 默认输出类型
    'default_return_type'    => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'PRC',
    // 是否开启多语言
    'lang_switch_on'         => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => '',
    // 默认语言
    'default_lang'           => 'zh-cn',
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'         => 'index',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // URL伪静态后缀
    'url_html_suffix'        => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 是否开启路由
    'url_route_on'           => true,
    // 路由使用完整匹配
    'route_complete_match'   => false,
    // 路由配置文件（支持配置多个）
    'route_config_file'      => ['route'],
    // 是否开启路由解析缓存
    'route_check_cache'      => false,
    // 是否强制使用路由
    'url_route_must'         => false,
    // 域名部署
    'url_domain_deploy'      => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => true,
    // 默认的访问控制器层
    'url_controller_layer'   => 'controller',
    // 表单请求类型伪装变量
    'var_method'             => '_method',
    // 表单ajax伪装变量
    'var_ajax'               => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'               => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'          => false,
    // 请求缓存有效期
    'request_cache_expire'   => null,
    // 全局请求缓存排除规则
    'request_cache_except'   => [],

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------
    'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写
        'auto_rule'    => 1,
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
    ],

    // 视图输出字符串内容替换
    'view_replace_str'       => [],
    // 默认跳转页面对应的模板文件
//    'dispatch_success_tmpl'  => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
//    'dispatch_error_tmpl'    => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_success_tmpl'  => APP_PATH . 'admin' . DS . 'view/common/dispatch_jump.tpl',
    'dispatch_error_tmpl'    => APP_PATH . 'admin' . DS . 'view/common/dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl'         => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log'   => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace' => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache' => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'            => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'think',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'             => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],

    //分页配置
    'paginate'           => [
//        'type'      => 'bootstrap',
        'type'      => 'layui\Layui',
        'var_page'  => 'page',
        'list_rows' => 10,
    ],

    // auth配置
    'auth'               => [
        'auth_on'           => 1, // 权限开关
        'auth_type'         => 1, // 认证方式，1为实时认证；2为登录认证。
        'auth_group'        => 'authgroup', // 用户组数据不带前缀表名
        'auth_group_access' => 'authgroupaccess', // 用户-用户组关系不带前缀表
        'auth_rule'         => 'authrule', // 权限规则不带前缀表
        'auth_user'         => 'member', // 用户信息不带前缀表
    ],

    // 登录失败次数
    'login_fail_times'   => 10,
    // 登录失败解冻时间(h)
    'login_fail_unblock' => 1,

    // 超级管理员
    'supermanager'       => [1],

    //图片上传配置
    'LOCAL'              => array(
        'rootPath' => $_SERVER['DOCUMENT_ROOT'] . "/uploads/",
        'relaPath' => "/uploads/",
        'savePath' => "",
        'maxSize'  => 5 * 1024 * 1024,
        'saveName' => array('uniqid', ''),
        'exts'     => array('jpg', 'gif', 'png', 'jpeg', 'mp4', 'MP4'),
        'autoSub'  => true,
        'subName'  => array('date', 'Ymd'),
    ),
];
