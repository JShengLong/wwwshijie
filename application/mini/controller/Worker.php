<?php
namespace app\mini\controller;

use think\worker\Server;
use Workerman\Lib\Timer;

class Worker extends Server
{
    protected $processes = 10;//四进程
    protected $port = '2376';//监听端口
    public function onWorkerStart($work)
    {
        $Timer = new \app\mini\controller\Timer();
        //每秒
        Timer::add(60, function () use (&$Timer) {
            $Timer->unpaid();
            $Timer->unreceivedGoods();
        });
    }
}