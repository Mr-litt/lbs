<?php

class RedisLbs
{
    /** @var Redis */
    private $redis;

    public function __construct($config = array())
    {
        $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port = isset($config['port']) ? $config['port'] : '6309';
        $redis = new Redis();
        $redis->connect($host, $port);
        $this->setRedis($redis);
    }

    public function getRedis()
    {
        return $this->redis;
    }

    public function setRedis($redis)
    {
        $this->redis = $redis;
    }

    public function geoAdd($uin, $lon, $lat)
    {
        $redis = $this->getRedis();
        $redis->geoAdd('markers', $uin, $lon, $lat);
        return true;
    }

    public function geoNearFind($uin, $maxDistance = 0, $unit = 'km')
    {
        $redis = $this->getRedis();
        $list = $redis->geoRadiusByMember('markers', $uin, $maxDistance, $unit);
        return $list;
    }

}

