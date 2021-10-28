<?php
namespace App\Model;

use EasySwoole\ORM\AbstractModel;

class BaseModel extends AbstractModel
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }
}
