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
            $data = $this->thriftClient->$name($paramJson);
            $data = json_decode($data,true);
            return $data;
        } catch (\Exception $e) {
            return false;
        }
    }
  
}