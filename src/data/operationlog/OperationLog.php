<?php
namespace mia\miagroup\Data\OperationLog;

class OperationLog extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_opration_log';

    protected $mapping = array();
    
    /**
     * 记录蜜芽圈请求日志
     * @param array $setInfo 请求参数
     * @return int $logId 日志id
     */
    public function addLog($setInfo){
        $logId = $this->insert($setInfo);
        return $logId;
    }
    
    
}