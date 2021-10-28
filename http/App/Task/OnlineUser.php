<?php declare(strict_types=1);

namespace App\Task;

use EasySwoole\Component\Singleton;
use EasySwoole\Component\TableManager;
use Swoole\Table;
use App\Model\WUserOnlineModel;
use App\Task\MyQueue;
use EasySwoole\Queue\Job;

class OnlineUser
{
    use Singleton;

    protected $table;  // 储存用户信息的Table

    public function __construct()
    {
        TableManager::getInstance()->add('OnlineUsers', [
            'fd' => ['type' => Table::TYPE_INT, 'size' => 8],
            'tag' => ['type' => Table::TYPE_STRING, 'size' => 2],
            'tags' => ['type' => Table::TYPE_STRING, 'size' => 10],
            'task_id' => ['type' => Table::TYPE_INT, 'size' => 8],
            'last_heartbeat' => ['type' => Table::TYPE_INT, 'size' => 4],
        ]);

        $this->table = TableManager::getInstance()->get('OnlineUsers');
    }

    public function set($fd, $tag, $tags, $task_id)
    {
        return $this->table->set((string)$fd, [
            'fd' => $fd,
            'tag' => $tag,
            'tags' => $tags,
            'task_id' => (Int)$task_id,
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
        $info = $this->get($fd);
        if ($info) {
            $this->table->set((string)$info['fd'], [
                'fd' => $info['fd'],
                'tag' => $info['tag'],
                'tags' => $info['tags'],
                'task_id' => $info['task_id'],
                'last_heartbeat' => time()
            ]);
        }
    }

    public function delete($fd)
    {
        $info = $this->get((string)$fd);
        if ($info) {
            $this->table->del((string)$info['fd']);
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

    public function online($tags){
        // 取出work_id,写入
        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        $redis->select(15);
        $sess = json_decode($redis->get($tags), true);
        $task = $sess['task'];

        $data = [
            'cate' => 'online',
            'worker' => $task['worker_id_cn'],
            'msg' => '刚从工位（'.$task['station_id_cn'].'）上线了',
            'time' => date('Y-m-d H:i:s'),
        ];

        $job = new Job();
        $job->setJobData($data);
        MyQueue::getInstance()->producer()->push($job);

        $redis->select(14);
        $redis->LPUSH('wy_baba', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);
        go(function ()use($task){
            $year = intval(date('Y'));
            $month = intval(date('m'));
            $day = intval(date('d'));
            $userOnline = WUserOnlineModel::create()->field(['id','start','end'])->where([
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'user_id' => $task['worker_id']
            ])->get();

            $now = time();
            if($userOnline) {
                $userOnline['start'] ? $userOnline->end = $now : $userOnline->start = $now;
                $userOnline->update();
            } else {
                $model = WUserOnlineModel::create([
                    'year' => $year,
                    'month' => $month,
                    'day' => $day,
                    'user_id' => $task['worker_id'],
                    'start' => $now
                ]);
                $model->save();
            }
        });
    }

    public function offline($fd){
        $info = $this->get($fd);
        $this->delete($fd);
        $tags = $info['tags'];
        $redis = \EasySwoole\Pool\Manager::getInstance()->get('redis')->getObj();
        $redis->select(15);
        $userSess = $redis->get($tags);
        if($userSess) {
            $sess = json_decode($redis->get($tags), true);
            $task = $sess['task'];


            $data = [
                'cate' => 'offline',
                'worker' => $task['worker_id_cn'],
                'msg' => '已从工位（'.$task['station_id_cn'].'）下线了',
                'time' => date('Y-m-d H:i:s'),
            ];

            $job = new Job();
            $job->setJobData($data);
            MyQueue::getInstance()->producer()->push($job);

            $redis->select(14);
            $redis->LPUSH('wy_baba', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $year = intval(date('Y'));
            $month = intval(date('m'));
            $day = intval(date('d'));
            $userOnline = WUserOnlineModel::create()->update([
                'end' => time()
            ], [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'user_id' => $task['worker_id']
            ]);

        }
        \EasySwoole\Pool\Manager::getInstance()->get('redis')->recycleObj($redis);
    }
}
