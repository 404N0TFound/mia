<?php
namespace mia\miagroup\Lib;

class Service extends \FS_Service {

    private $startTime;
    private $endTime;
    static private $count = 0;
    static private $logFlag;
    
    function __construct() {
        self::$count ++;
        parent::__construct();
        $this->startTime = gettimeofday(true);
        if (empty($this->ext_params['dvc_id']) && !empty($this->ext_params['unique_key'])) {
            $this->ext_params['dvc_id'] = $this->ext_params['unique_key'];
        }
    }
    
    function __destruct() {
        self::$count --;
        if (self::$count == 0 && !empty($this->params)) {
            $this->endTime = gettimeofday(true);
            $respTime = number_format(($this->endTime - $this->startTime), 4, '.', '');
            \F_Ice::$ins->mainApp->logger_access->info(array(
                //'current_uid'         => '',    //当前调用接口的用户ID
                'code'                => $this->code,                //接口返回code
                'msg'                => $this->msg,                //接口返回code
                'resp_time'           => $respTime,                  //接口响应时间
                'curl_params' => array(
                    "class" => \F_Ice::$ins->runner->request->class,
                    "action" => \F_Ice::$ins->runner->request->action,
                    "params" => $this->params,
                    "ext_params" => $this->ext_params,
                )
            ));
        }
    }
}