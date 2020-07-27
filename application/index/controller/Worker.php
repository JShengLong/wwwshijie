<?php
namespace app\index\controller;

use think\worker\Server;
use Workerman\Lib\Timer;

class Worker extends Server
{
    protected $processes = 10;//四进程
    protected $port = '2376';//监听端口
    public function onWorkerStart($work)
    {
        $Timer = new \app\index\controller\Timer();
        //每秒
        Timer::add(1, function () use (&$Timer) {
            $Timer->unpaid();
//            $Timer->unreceivedGoods();
        });
    }
}