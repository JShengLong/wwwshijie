<?php

// +----------------------------------------------------------------------
// | Description: redis 缓存封装使用
// +----------------------------------------------------------------------
// | Author: phpAndy <383916522@qq.com>
// +----------------------------------------------------------------------

namespace Redis;

class RedisPackage
{
    protected static $handler = null;
    protected $options
        = [
            'host'       => '127.0.0.1',
            'port'       => 6379,
            'password'   => '123456',
            'select'     => 0,
            'timeout'    => 0,//关闭时间 0:代表不关闭
            'expire'     => 0,
            'persistent' => false,
            'prefix'     => '',
        ];

    public function __construct($options = [])
    {
        if (!extension_loaded('redis')) {   //判断是否有扩展(如果你的apache没reids扩展就会抛出这个异常)
            throw new \BadFunctionCallException('not support: redis');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $func          = $this->options['persistent'] ? 'pconnect' : 'connect';     //判断是否长连接
        self::$handler = new \Redis;
        self::$handler->$func($this->options['host'], $this->options['port'], $this->options['timeout']);

        if ('' != $this->options['password']) {
            self::$handler->auth($this->options['password']);
        }

        if (0 != $this->options['select']) {
            self::$handler->select($this->options['select']);
        }
    }

    /**
     * 写入缓存
     * @param string $key 键名
     * @param string $value 键值
     * @param int $exprie 过期时间 0:永不过期
     * @return bool
     */
    public static function set($key, $value, $exprie = 0)
    {
        if ($exprie == 0) {
            $set = self::$handler->set($key, $value);
        } else {
            $set = self::$handler->setex($key, $exprie, $value);
        }
        return $set;
    }

    /**
     * 读取缓存
     * @param string $key 键值
     * @return mixed
     */
    public static function get($key)
    {
        $fun = is_array($key) ? 'Mget' : 'get';
        return self::$handler->{$fun}($key);
    }

    /**
     * 获取值长度
     * @param string $key
     * @return int
     */
    public static function lLen($key)
    {
        return self::$handler->lLen($key);
    }

    /**
     * 将一个或多个值插入到列表头部
     * @param $key
     * @param $value
     * @return int
     */
    public static function LPush($key, $value)
    {
        return self::$handler->lPush($key, $value);
    }

    /**
     * 将一个或多个值插入到列表尾部
     * @param $key
     * @param $value
     * @return int
     */
    public static function rPush($key, $value)
    {
        return self::$handler->rPush($key, $value);
    }

    /**
     * 移出并获取列表的第一个元素
     * @param string $key
     * @return string
     */
    public static function lPop($key)
    {
        return self::$handler->lPop($key);
    }

    /**
     * 移出并获取列表的最后一个元素
     * @param string $key
     * @return string
     */
    public static function rPop($key)
    {
        return self::$handler->rPop($key);
    }

    /**
     *返回存储在
     *范围[开始，结束]。开始和停止被解释为索引：0第一个元素，
     *1秒。。。-1最后一个元素，-2倒数第二个。。。
     * @param string $键
     * @param int $开始
     * @param int $结束
     * @return数组，包含指定范围内的值。
     *@链接https://redis.io/commands/lrange
     *@示例
     *<pre>
     *$redis->rPush（'key1'，'A'）；
     *$redis->rPush（'key1'，'B'）；
     *$redis->rPush（'key1'，'C'）；
     *$redis->lRange（'key1'，0，-1）；//array（'A'，'B'，'C'）
     *</pre>
     */
    public static function lRange($key, $start, $end)
    {
        return self::$handler->lRange($key, $start, $end);
    }

    public static function del($key)
    {
        return self::$handler->del($key);
    }

    public static function hGetAll($key)
    {
        return self::$handler->hGetAll($key);
    }

    public static function hKeys($key)
    {
        return self::$handler->hKeys($key);
    }

    
    public static function hGet($key, $hashKey)
    {
        return self::$handler->hGet($key, $hashKey);
    }

    public static function hSet($key, $hashKey, $value)
    {
        return self::$handler->hSet($key, $hashKey, $value);
    }

    public static function hDel($key, $hashKey1, $hashKey2=null, $hashKeyN=null)
    {
        return self::$handler->hDel($key, $hashKey1, $hashKey2, $hashKeyN);
    }

    public static function incr($key)
    {
        return self::$handler->incr($key);
    }

}
