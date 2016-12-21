<?php
namespace mia\miagroup\Lib;

class Service extends \FS_Service {

    private $startTime;
    private $endTime;
    static private $logFlag;
    
    function __construct() {
        parent::__construct();
        $this->startTime = gettimeofday(true);
    }
    
    function __destruct() {
        if (self::$logFlag === null && !empty($this->params)) {
            self::$logFlag = 1; //设置日志标记，防止一次请求记录重复日志
            $this->endTime = gettimeofday(true);
            $respTime = number_format(($this->endTime - $this->startTime), 4);
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