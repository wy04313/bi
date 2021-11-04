<?php

namespace App\Task;

use EasySwoole\Component\Singleton;

class U8
{
    use Singleton;

    public function getAllFromU8($sql){
        $conn = new \PDO("sqlsrv:server=10.0.6.218;database=UFDATA_102_2021","sa","abc@123");
        $res = $conn->query($sql);
        return $res->fetchAll(\PDO::FETCH_ASSOC);
    }
}
