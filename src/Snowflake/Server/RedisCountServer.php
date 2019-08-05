<?php

namespace wantp\Snowflake\Server;


class RedisCountServer implements CountServerInterFace
{
    private $redis = null;

    /**
     * RedisCountService constructor.
     * @param $config
     * @throws \Exception
     */
    public function __construct($config)
    {
        $this->redis = new \Redis();
        if (!isset($config['host']) || !isset($config['port'])) {
            throw new \Exception('invalid redis config');
        }
        $this->redis->connect($config['host'], $config['port']);
        if (isset($config['auth'])) {
            $this->redis->auth($config['auth']);
        }
        if (isset($config['dbIndex'])) {
            $this->redis->select($config['dbIndex']);
        }
        return $this;
    }

    /**
     * Notes:getSequenceId
     * @author  zhangrongwang
     * @date 2018-12-26 10:42:20
     * @param $key
     * @return int $sequenceId
     */
    public function getSequenceId($key)
    {
        $sequenceId = $this->redis->incr($key) - 1;
        $this->redis->expire($key, 5);
        return $sequenceId;
    }
}