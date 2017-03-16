<?php
namespace mia\miagroup\Service;

use \F_Ice;
use mia\miagroup\Model\OperationLog as OperLogModel;
use mia\miagroup\Util\NormalUtil;

class OperationLog extends \mia\miagroup\Lib\Service {

    public $operlogModel = null;

    public function __construct() {
        parent::__construct();
        $this->operlogModel = new OperLogModel();
    }

    /**
     * 新增log
     * @param array $logInfo log信息
     * @return bool true/false
     */
    public function addOperLog($logInfo) {
        if (empty($logInfo)) {
            return $this->error(500);
        }

        if (!empty($logInfo['request_params'])) {
            $logInfo['request_params'] = json_encode($logInfo['request_params']);
        }
        $logInfo['oper_time'] = date('Y-m-d H:i:s',time());
        $insertRes = $this->operlogModel->addLog($logInfo);
        if($insertRes > 0){
            return $this->succ(true);
        }else{
            return $this->succ(false);
        }
    }

    
    
}

