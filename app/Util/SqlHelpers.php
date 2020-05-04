<?php


namespace App\Util;


use Doctrine\DBAL\Connection;

class SqlHelpers
{
    private $connection;
    public function __construct(Connection $connection)
    {
        $this->connection=$connection;
    }

    public function ejecutarSP($sql, $parametros=null){
        $stmt = $this->connection->prepare($sql);
        foreach ($parametros as $par=>$val){
            $stmt->bindParam($par, $val[0], $val[1], $val[2]);
        }
        $stmt->execute();
        $res =  $stmt->fetchAll();
        return $res;
    }
}