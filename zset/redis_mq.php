<?php

class RedisSingleton
{
    private static $instance;

    private
        $redis,
        $config = ['host' => '127.0.0.1', 'port' => 25002];

    private function __construct()
    {
        $this->redis = new Redis();
        $this->redis->connect($this->config['host'], $this->config['port']);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new RedisSingleton();
        }
        return self::$instance->redis;
    }

}
