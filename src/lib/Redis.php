<?php
namespace mia\miagroup\Lib;

class Redis {
    
    public $redis;
    
    public function __construct(){
        $this->redis = \F_Ice::$ins->workApp->proxy_resource->get('redis://miagroup/default');
    }
    
    public function get($key){
        $data = $this->redis->get($key);
        $data_arr = json_decode($data,true);
        if(empty($data_arr)){
            return $data;
        }else{
            return $data_arr;
        }
        
    }
    
    public function set($key,$val){
        if(is_array($val)){
            $val = json_encode($val);
        }
        $data = $this->redis->set($key,$val);    
        return $data;
    }
    
}