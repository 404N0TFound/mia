<?php
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Model\Live as LiveModel;
use mia\miagroup\Data\Live\Live as LiveData;

/*
 * 定时任务：将直播累计观看量同步到数据库
 */
class Livecountsync extends \FD_Daemon{
    
    private $liveData;
    private $liveModel;

    public function __construct() {
        $this->liveModel = new LiveModel();
        $this->liveData = new LiveData();
    }
    
    public function execute() {
        //从队列中读取阅读量计数
        $readNums = $this->liveModel->getLiveCountRecord(2000);
        foreach ($readNums as $liveId => $typeCount) {
            //更新数据库计数
            $this->liveData->increaseBatchLiveCount($liveId, $typeCount);
        }
    }
}