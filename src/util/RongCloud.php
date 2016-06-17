<?php
namespace mia\miagroup\Util;

use \RongCloud\Api;

/**
 * 融云聊天室
 * @author user
 *
 */
class RongCloud extends Api{
    
    public function __construct(){
        
        $miaAppKey = 'cpj2xarljzbdn';
        $miaAppSecret = '8o0sQ3ajLcfG5q';
        
        parent::__construct($miaAppKey,$miaAppSecret);
        
    }
    
    
    
    
}