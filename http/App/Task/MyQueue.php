<?php

namespace App\Task;

use EasySwoole\Component\Singleton;
use EasySwoole\Queue\Queue;

class MyQueue extends Queue
{
    use Singleton;
}
