<?php
namespace mia\miagroup\Lib;

class Redis {
    
    public $redis;
    
    public function __construct($cluster='miagroup/default'){
        $dsn = 'redis://'.$cluster;
        $this->redis = \F_Ice::$ins->workApp->proxy_resource->get($dsn);
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
    
    public function mget($keys){
        if (empty($keys) || !is_array($keys)) {
            return array();
        }
        $data = $this->redis->mget($keys);
        $result = array();
        foreach ($keys as $key) {
            $shift_data = array_shift($data);
            $array_data = json_decode($shift_data, true);
            if (empty($array_data)) {
                $result[$key] = $shift_data;
            } else {
                $result[$key] = $array_data;
            }
        }
        return $result;
    }
    
    public function set($key,$val){
        if(is_array($val)){
            $val = json_encode($val);
        }
        $data = $this->redis->set($key,$val);    
        return $data;
    }
    
    public function setex($key,$val,$expires){
        if(is_array($val)){
            $val = json_encode($val);
        }
        $data = $this->redis->setex($key,$expires,$val);
        return $data;
    }
    
    public function __call($method,$param){
        $data = call_user_func_array(array($this->redis,$method),$param);
        return $data;
    }
    
    
}