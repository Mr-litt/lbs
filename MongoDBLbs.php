<?php

class MongoDBLbs
{
    private $pageCount = 2;
    /** @var MongoDB\Driver\Manager */
    private $mongoManager;

    public function __construct($config = array())
    {
        $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port = isset($config['port']) ? $config['port'] : '27017';
        $manager = new MongoDB\Driver\Manager("mongodb://{$host}:{$port}");
        $this->setMongoManager($manager);
    }

    public function getMongoManager()
    {
        return $this->mongoManager;
    }

    public function setMongoManager($manager)
    {
        $this->mongoManager = $manager;
    }

    public function geoAdd($uin, $lon, $lat)
    {
        $document = array(
            'uin' => $uin,
            'loc' => array(
                'lon' =>  $lon,
                'lat' =>  $lat,
            ),
        );

        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update(
            ['uin' => $uin],
            $document,
            [ 'upsert' => true]
        );
        //出现noreply 可以改成确认式写入
        $manager = $this->getMongoManager();
        $writeConcern = new MongoDB\Driver\WriteConcern(1, 100);
        //$writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 100);
        $result = $manager->executeBulkWrite('db.location', $bulk, $writeConcern);

        if ($result->getWriteErrors()) {
            return false;
        }
        return true;
    }

    public function geoFind($uin)
    {
        $manager = $this->getMongoManager();
        $filter = array('uin' => $uin);
        $options = [
            'limit' => 1
        ];
        // 查询数据
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $manager->executeQuery('db.location', $query);
        $data = $cursor->toArray();
        $info = array();
        if ($data) {
            $info = (array) $data[0];
        }
        return $info;
    }

    public function geoNearFind($lon, $lat, $maxDistance = 0, $where = array(), $page = 0)
    {
        $filter = array(
            'loc' => array(
                '$near' => array($lon, $lat),
            ),
        );
        if ($maxDistance) {
            $filter['loc']['$maxDistance'] = $maxDistance;
        }
        if ($where) {
            $filter = array_merge($filter, $where);
        }
        $options = array();
        if ($page) {
            $page > 1 ? $skip = ($page - 1) * $this->pageCount : $skip = 0;
            $options = [
                'limit' => $this->pageCount,
                'skip' => $skip
            ];
        }

        $query = new MongoDB\Driver\Query($filter, $options);
        $manager = $this->getMongoManager();
        $cursor = $manager->executeQuery('db.location', $query);
        $list = $cursor->toArray();
        return $list;
    }

    public function geoNearFindReturnDistance($lon, $lat, $maxDistance = 0, $where = array(), $num = 0)
    {
        $params = array(
            'geoNear' => "location",
            'near' => array($lon, $lat),
            'spherical' => true, // spherical设为false（默认），dis的单位与坐标的单位保持一致，spherical设为true，dis的单位是弧度
            'distanceMultiplier' => 6371, // 计算成公里，坐标单位distanceMultiplier: 111。 弧度单位 distanceMultiplier: 6371
        );

        if ($maxDistance) {
            $params['maxDistance'] = $maxDistance;
        }
        if ($num) {
            $params['num'] = $num;
        }
        if ($where) {
            $params['query'] = $where;
        }

        $command = new MongoDB\Driver\Command($params);
        $manager = $this->getMongoManager();
        $cursor = $manager->executeCommand('db', $command);
        $response = (array) $cursor->toArray()[0];
        $list = $response['results'];
        return $list;
    }
}

/*
$lbs = new MongoDBLbs();
//$result = $lbs->geoAdd(1, 120.20, 20.20);
//$lbs->geoAdd(2, 125.20, 25.20);
//$result = $lbs->geoAdd(3, 113.20, 13.20);
//$result = $lbs->geoAdd(4, 108.20, 8.20);
//$result = $lbs->geoNearFind(120.20, 20.20, 10, array('uin' => array('$in' => array(2,3,4))), $page=1);
//$result = $lbs->geoNearFindReturnDistance(120.20, 20.20, 1500/6371, array('uin' => array('$in' => array(2,3,4))), 1);
var_dump($result);*/
