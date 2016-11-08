<?php
namespace mia\miagroup\Lib;

// 引入客户端文件
require_once __DIR__ . '/Thrift/Clients/ThriftClient.php';
use ThriftClient\ThriftClient;

class Thrift {
    
    protected $thriftClient;
    protected $service;
    
    public function __construct(){
        ThriftClient::config(\F_Ice::$ins->workApp->config->get('thrift.address'));
        // 初始化一个MiBean的实例
        $this->thriftClient = ThriftClient::instance($this->service);
    }
    
    public function agent($name,$param){
        $paramJson = json_encode($param);
        try {
            $request_startTime = gettimeofday(true);
            //处理
            $data = $this->thriftClient->$name($paramJson);
            $data = json_decode($data,true);
            $request_endTime = gettimeofday(true);
            
            //记录日志
            \F_Ice::$ins->mainApp->logger_remote->info(array(
                'third_server'  =>  'mibean',
                'type'          =>  'INFO',
                'request_param' =>  ['func_name'=>$name,'args'=>$param],
                'response_code' =>  $data['code'],
                'response_data' =>  $data['data'],
                'response_msg'  =>  $data['msg'],
                'resp_time'     =>  number_format(($request_endTime - $request_startTime), 4),
            ));
            
            //返回结果
            return $data;
        } catch (\Exception $e) {
            \F_Ice::$ins->mainApp->logger_remote->warn(array(
                'third_server'  =>  'mibean',
                'type'      =>  'ERROR',
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'code'      => $e->getCode(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
             ));
        }
    }
  
}