<?php
namespace push;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/23
 * Time: 9:56
 */
use JPush\Client;
use think\Config;

class Push {
    protected  $app_key='';//极光推送app_key
    protected  $master_secret =' ';//极光推送master_secret

    public function __construct()
    {
        $config=Config::get('jpush');
        //获取app_key
        $this->app_key=$config['app_key'];
        //获取master_secret
        $this->master_secret=$config['master_secret'];
    }

    /**
     * 发送用户推送通知
     * @param $account 别名-用户账号
     * @param $alert 内容
     * @param $title 标题
     * @return bool
     */
    public function send_to_one($account,$alert,$title,$extras){
        $notification =[
            'title'=>$title,//通知栏标题
            'builder_id'=>1, //通知栏样式
            'extras'=>$extras,//扩展字段 这里自定义 JSON 格式的 Key / Value 信息，以供业务使用。
        ];

        $opn=['apns_production'=>true]; //apns_production true生产环境 false 测试环境
        try{
            //实例化极光接口
            $client = new Client($this->app_key, $this->master_secret);
            //调用发送通知接口
            $client->push()
                //推送范围 iOS Android winphone
                ->setPlatform(['ios', 'android'])
                //推送的别名
                ->addAlias($account)
                //推送Android的内容
                ->androidNotification($alert,$notification)
                //推送iOS的内容
                ->iosNotification($alert)
                //可选参数
                ->options($opn)
                //发送
                ->send();
            return true;
        }catch (\Exception $e){
            return $e->getMessage();
            return false;
        }
    }

    /**
     * 发送广播推送通知
     * @param $alert
     * @param $title
     * @param $extras
     * @return bool
     */
    public function send_to_all($alert,$title,$extras){
        $notification =[
            'title'=>$title,//通知栏标题
            'builder_id'=>1, //通知栏样式
            'extras'=>$extras,//扩展字段 这里自定义 JSON 格式的 Key / Value 信息，以供业务使用。
        ];
        $opn=['apns_production'=>true]; //apns_production true生产环境 false 测试环境
        try{
            //实例化极光接口
            $client = new Client($this->app_key, $this->master_secret);
            //调用发送通知接口
            $client->push()
                //推送范围 iOS Android winphone
                ->setPlatform(['ios', 'android'])
                //推送所有
                ->addAllAudience()
                //推送Android的内容
                ->androidNotification($alert,$notification)
                //推送iOS的内容
                ->iosNotification($alert)
                //可选参数
                ->options($opn)
                //发送
                ->send();
            return true;
        }catch (\Exception $e){
            return false;
        }
    }
}