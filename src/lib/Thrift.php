<?php
namespace mia\miagroup\Lib;

// 引入客户端文件
require_once __DIR__ . '/Thrift/Clients/ThriftClient.php';
use ThriftClient\ThriftClient;

class Thrift {
    
    public $thriftClient;
    
    public function __construct($service){
        ThriftClient::config(\F_Ice::$ins->workApp->config->get('busconf.thrift.address'));
        // 初始化一个MiBean的实例
        $this->thriftClient = ThriftClient::instance($service);
    }
    
    public function agent($name,$param){
        $paramJson = json_encode($param);
        $data = $this->thriftClient->$name($paramJson);
        $data = json_decode($data,true);
        return $data;
    }
    
//     public function execute($route,$param){
//         $route = explode('/', $route);
//         $paramJson = json_encode($param);
        
//         // 初始化一个MiBean的实例
//         $client = ThriftClient::instance($route[0]);
//         $result = call_user_func(array($client,$route[1]),$paramJson);
        
//         return json_decode($result,true);
//     }
    
    
}