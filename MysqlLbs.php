<?php

Class MysqlLbs
{
    private $pageCount = 10;
    /** @var PDO */
    private $pdo;

    public function __construct($config)
    {
        $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port = isset($config['port']) ? $config['port'] : '6309';
        $dbName = isset($config['dbName']) ? $config['dbName'] : 'test';
        $user = isset($config['username']) ? $config['username'] : 'root';
        $pass = isset($config['password']) ? $config['password'] : 'root';
        $dsn="mysql:host=$host;port=$port;dbname=$dbName";
        $this->setPdo(new PDO($dsn, $user, $pass));
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function setPdo($pdo)
    {
        $this->pdo = $pdo;
    }

    public function geoAdd($uin, $lon, $lat)
    {
        $pdo = $this->getPdo();
        $sql = 'INSERT INTO `markers`(`uin`, `lon`, `lat`) VALUES (?, ?, ?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($uin, $lon, $lat));
        return true;
    }

    public function geoFind($uin)
    {
        $pdo = $this->getPdo();
        $sql = 'SELECT `lat`, `lon` FROM `markers` WHERE `uin` = ? LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array('uin' => $uin));
        $uinLoc = array();
        if ($row = $stmt->fetch() !== false) {
            $uinLoc = $row;
        }
        return $uinLoc;
    }

    public function geoNearFind($lat, $log, $maxDistance = 0, $where = array(), $page = 0)
    {
        $pdo = $this->getPdo();
        $sql = "SELECT  
              id, (  
                3959 * acos (  
                  cos ( radians({$lat}) )  
                  * cos( radians( lat ) )  
                  * cos( radians( log ) - radians({$log}) )  
                  + sin ( radians({$lat}) )  
                  * sin( radians( lat ) )  
                )  
              ) AS distance  
            FROM markers";

        if ($where) {
            $sqlWhere = ' WHERE ';
            foreach ($where as $key => $value) {
                $sqlWhere .= "`{$key}` = {$value} ,";
            }
            $sql .= rtrim($sqlWhere, ',');
        }

        if ($maxDistance) {
            $sqlHaving = " HAVING distance < {$maxDistance}";
            $sql .= $sqlHaving;
        }

        $sql .= ' ORDER BY distance';

        if ($page) {
            $page > 1 ? $offset = ($page - 1) * $this->pageCount : $offset = 0;
            $sqlLimit = " LIMIT {$offset} , {$this->pageCount}";
            $sql .= $sqlLimit;
        }

        $stmt = $pdo->query($sql);
        $list = $stmt->fetchAll();

        return $list;
    }

}


