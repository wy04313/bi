<?php declare(strict_types=1);

namespace App\Task;

use EasySwoole\Component\Singleton;
use EasySwoole\Component\TableManager;
use Swoole\Table;

// use App\Model\WUserOnlineModel;
// use App\Task\MyQueue;
// use EasySwoole\Queue\Job;

class OnlineUser
{
    use Singleton;

    protected $table;  // 储存用户信息的Table

    public function __construct()
    {
        TableManager::getInstance()->add('OnlineUsers', [
            'fd' => ['type' => Table::TYPE_INT, 'size' => 15],
            'created' => ['type' => Table::TYPE_INT, 'size' => 15],
            'last_heartbeat' => ['type' => Table::TYPE_INT, 'size' => 11],
        ]);
        $this->table = TableManager::getInstance()->get('OnlineUsers');
    }

    public function set($fd, $created)
    {
        return $this->table->set((string)$fd, [
            'fd' => $fd,
            'created' => $created,
            'last_heartbeat' => time()
        ]);
    }

    public function get($fd)
    {
        $info = $this->table->get((string)$fd);
        return is_array($info) ? $info : null;
    }

    public function update($fd)
    {
        $info = $this->get((string)$fd);
        if ($info) {
            $this->table->set((string)$info['fd'], [
                'fd' => $info['fd'],
                'created' => $info['created'],
                'last_heartbeat' => time()
            ]);
        }
    }

    public function delete($fd)
    {
        $fd = (string)$fd;
        $info = $this->get($fd);
        if ($info) {
            $this->table->del($fd);
        }
    }

    public function heartbeatCheck($ttl = 60)
    {
        foreach ($this->table as $v) {
            $time = time();
            if (($v['last_heartbeat'] + $ttl) < $time) {
                $this->delete($v['fd']);
            }
        }
    }

    public function table()
    {
        return $this->table;
    }



}
