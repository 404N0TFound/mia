<?php
namespace mia\miagroup\Remote;

use mia\miagroup\Lib\Thrift;

class MiBean extends Thrift{
    
    public function __construct(){
        parent::__construct('MiBean');
    }
    /**
     * 增加蜜豆
     * @param unknown $param
     */
    public function add($param){
        $data = $this->agent('add', $param);
        return $data;
    }
    /**
     * 减少蜜豆
     */
    public function sub($param){
        $data = $this->agent('sub', $param);
        return $data;
    }
    
}