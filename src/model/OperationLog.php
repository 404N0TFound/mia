<?php
namespace mia\miagroup\Model;
use \mia\miagroup\Data\OperationLog\OperationLog as OperationLogData;

class OperationLog {
    protected $operationLogData = null;

    public function __construct() {
        $this->operationLogData = new OperationLogData();
    }

    //新增log
    public function addLog($insertData){
        $data = $this->operationLogData->addLog($insertData);
        return $data;
    }
    
}
