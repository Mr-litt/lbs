<?php

class RedisLbs
{
    /** @var Redis */
    private $redis;

    public function __construct($config = array())
    {
        $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port = isset($config['port']) ? $config['port'] : '6379';
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
        $redis->geoAdd('markers', $lon, $lat, $uin);
        return true;
    }

    public function geoNearFind($uin, $maxDistance = 0, $unit = 'km')
    {
        $redis = $this->getRedis();
        $options = ['WITHDIST']; //显示距离
        $list = $redis->geoRadiusByMember('markers', $uin, $maxDistance, $unit, $options);
        return $list;
    }

}

/*
$lbs = new RedisLbs();
//$result = $lbs->geoAdd(1, 122.20, 20.0);
//$lbs->geoAdd(2, 100.20, 30.0);
//$lbs->geoAdd(3, 140.20, 40.0);
$result = $lbs->geoNearFind(1,100000);
var_dump($result);*/


