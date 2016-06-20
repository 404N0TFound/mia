<?php
namespace mia\miagroup\Service;

use \RongCloud\Api;
use \F_Ice;
use \FS_Service;

/**
 * 融云聊天室
 * @author user
 *
 */
class ChatRoom extends FS_Service{
    
    public $api = null;
    
    public function __construct(){
        
        $this->api  = new Api(F_Ice::$ins->workApp->config->get('busconf.rongcloud')['appKey'],F_Ice::$ins->workApp->config->get('busconf.rongcloud')['appSecret']);   
    }
    
    
    
    
    
    
    
    
    
}