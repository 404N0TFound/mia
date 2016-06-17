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
                
        parent::__construct(F_Ice::$ins->workApp->config->get('busconf.rongcloud')['appKey'],F_Ice::$ins->workApp->config->get('busconf.rongcloud')['appSecret']);
        
    }
    
    
    
    
}