### 基础开发框架

## 扩展
>1. 支付扩展
>2. 短信扩展
>3. workerman定时器扩展
>4. 普通商城制度扩展
## 微信支付和支付宝支付
 1.微信支付和支付宝支付： 在数据库里setting表里配置，module为wx_pay的是微信配置，module为ali_pay是支付支付配置

 2.调用支付：调用pay控制器里的pay方法 
  ```
  $data=[
    'out_trade_no'=>123456789, //订单号 自己生成的
    'total_fee'=>0.01, //订单总金额
    'body'=>'购买商品'//购买信息
  ];
  $pay=>new Pay();
  //$type 'wx'微信支付 'ali'支付宝支付
  //$wxType app微信APP支付 ,微信mp公众号支付,mini微信小程序支付；支付支付可以不用填
  $response = $pay -> pay($type,$data,$wxType);
  if($response){
    dump($response);
  }else{
    echo '获取支付数据失败';
  }
  ```
3.微信支付和支付宝支付回调：在Pay控制器里 微信支付回调 wx_notify()方法，支付宝支付回调ali_notify() 业务逻辑自己去写 
  

## 短信插件

配置在config.php里面

```
'sms'=>[
        'code_failure_time' => '5',//验证码失效时间，单位：分钟
        'resend'  => '60',//重新发送的时间，单位：秒
    ],
    //桥通短信账号密码
    'QtSms' => [
        'username' => 'QTTX010113',//账号
        'password' => 'lv112358',//密码
        'template'=>'您好，您的验证码是{$code}。如非本人操作请勿泄露给他人。',
    ],
    //阿里巴巴短信服务
    'ALiBaBaSendSms'=>[
        'accessKeyId'=>'LTAIuLRzidb25zQT',
        'accessKeySecret'=>'Ul7WsXUoDutaIHLKW3RG6VG7rD8sjP',
        'region'=>'cn-hangzhou',
        'SignName'=>'趴趴墙',//短信签名名称
        'TemplateCode'=>'SMS_171187822',//短信模板ID

    ],
    //聚合短信服务
    'JuHeSendSms'=>[
        'key'=>'',//您申请的APPKEY
        'tpl_id'=>'',//您申请的短信模板ID，根据实际情况修改
        'url'=>'',//请求URL
        'sndName'=>'四通金服'//签名
    ],

```

1.在控制器里使用短信插件 只要写 `use QTSms\QTSms;`

2.调用发送短信接口
```
 $snesms=new SendSms();
 $data['phone']=$phone;//手机号
 $data['code']=rand(100000, 999999);//验证码
 $data['scene']=$scene;//发送场景 比如登录'login',注册'register',忘记密码 'forget'；
 //发送验证码
 //$type 'qt'公司短信平台，'ali'阿里巴巴短信服务，'juhe'聚合短信服务
 $res=$snesms->SendSms($data,$type);
 
```
3.返回数据：成功
`['code'=>1,'发送成功']`，失败`['code'=>0,'发送失败']`；

4.验证短信验证码
  ```
  $qt_sms = new SendSms();
  //$account 手机号，$code 验证码，$scene 发送场景
  $res = $qt_sms->check($account,$code,$scene);
  
  ```
  
 5.返回数据：成功`['code'=>1,'验证成功']`，失败`['code'=>0,'验证失败']`；
        
## workerman定时器插件

1.使用workerman必须composer加载依赖 `composer require topthink/think-worker`

此时，一般会因为框架版本要求过高，会有提示错误信息

在 `composer.json` 文件中的`"require"` 数组中先补充一条数据：`"topthink/think-worker":"0.1"`然后，执行命令：`composer update`

2.接下来在`public`下面新建`server.php`

```
server.php

<?php
/**
 * Created by PhpStorm.
 * User: jsl
 * Date: 2019/9/20
 * Time: 13:40
 */
define('APP_PATH', __DIR__ . '/../application/');
define("BIND_MODULE", "index/Worker");//cli访问的模块和控制器
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';

```
3.在index模块下有一个Worker.php控制器，在onWorkerStart()方法里面写定时器
```
use think\worker\Server;
use Workerman\Lib\Timer;
use app\index\controller\Timer as Timers; 

class Worker extends Server
{
    protected $processes = 3;//四进程
    protected $port = '2376';//监听端口
    public function onWorkerStart($work)
    {
        $Timer = new Timers();
        //第一个参数是定时时间 精确到0.001 毫秒 1是一秒
        Timer::add(1, function () use (&$Timer) {
            $Timer->index();//调用Timer控制器里面的index方法
            //接着在这里写定时任务的方法
        });
    }
}
```
`Timer::add();`第一个参数是定时时间 精确到0.001 毫秒 1是一秒，第二个参数是访问定时任务的方法。

4.Timer.php控制器里面写定时任务
```
public function index(){
    set_time_limit(0);
    if(time()%86400==0){
        //每天八点执行 以此类推 每加一小时就加3600
    }
    if(time()%86400==3600){
        //每天九点执行 以此类推 每加一小时就加3600
    }
    if(time()%86400==57600){
        //每天0点执行
    }
}

```
`set_time_limit(0);`//脚本超时函数 0是永不超时 单位秒

控制脚本在某个时间执行的话，需要自己在脚本里面判断

5.启动workerman服务` php server.php start -d`以守护进程方式启动
## 普通商城制度

本制度并不完善，所有的方法可以根据自己的业务逻辑更改编辑，在以后要的项目中继续完善，最终实现每个制度方法都在后台可配置，业务逻辑更加全面

1.`Distribution.php`控制器里面集成了很多商城的制度，如果不能满足业务需求可以自己写新的制度来完善这个控制器

2.在实例化`Distribution.php`控制器时需要传入订单的ID，在构造方法里面把所需要的数据赋值

3.`distribution($type=1, $info='分销奖')`方法为普通的分销方法，需要在后台`分销配置`配置好分销的层数和每层分销的比例即可 ，`$type`和`$info`可以根据自己的业务需求传入

4.`cancellationRatio($type=1, $info='复销奖')`方法为普通的复销方法，在`系统配置列表`配置好复销比例，`$type`和`$info`可以根据自己的业务需求传入
   
5.`teamTurnOver()`方法为新增团队业绩的方法

6.`regionalAgency()`方法为区域代理奖结算方法

7.`team_award($type=1,$info='团队奖')`方法为团队奖（带极差，平级截流），根据用户推荐关系判断出所有的上级，按照用户等级分组，给每个等级第一个人发送奖励

等级级别在系统配置表里`viplevel`字段 各等级奖励在系统配置表里`viparawd`字段

8.`repeat($type=1,$info='复消奖')`复销方法，根据订单购买的商品，按照商品里面配置的复销等级，给各个等级发送复消奖励，所有的配置在商品表里。可以根据自己的业务逻辑修改代码

9.`common.php`里`teamNum($id)`传入新注册或者其他业务逻辑需要增加用户id，给关系树上所有的上级团队人数加1，并判断是否满足升级条件
