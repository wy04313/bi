<?php

namespace App\Task;

use EasySwoole\Task\AbstractInterface\TaskInterface;
use App\Model\WMesRelationModel;
use App\Model\WBarcodeModel;

class SyncBarcode0 implements TaskInterface
{

    public function run(int $taskId, int $workerIndex)
    {
        $relation = new WMesRelationModel();
        $list = $relation->withTotalCount()
            ->field('id,main_barcode')
            ->where('barcode_id = 0 and is_delete = 0')
            ->order('id','asc')
            ->get();
        if($list) {
            $id = WBarcodeModel::create()->where('BarCode', $list->main_barcode)->val('id');
            if($id)
                WMesRelationModel::create()->update([
                    'barcode_id'  => $id,
                ], ['main_barcode' => $list->main_barcode]);
            else
                WBarcodeModel::create()->data([
                        'sync_type'  => 1,
                        'BarCode'  => $list->main_barcode,
                        'created' => time()
                    ], false)->save();

        }
    }

    public function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        // 异常处理,进入队列
        echo $throwable.PHP_EOL;
    }
}
