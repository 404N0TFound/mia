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
    
    //融云sdk
    public $api = null;
    
    public function __construct(){
        $this->api  = new Api(F_Ice::$ins->workApp->config->get('busconf.rongcloud')['appKey'],F_Ice::$ins->workApp->config->get('busconf.rongcloud')['appSecret']);   
    }
    
    /**
     * 获取token
     * @param unknown $userId
     * @param unknown $name
     * @param unknown $portraitUri
     */
    public function getToken($userId, $name, $portraitUri){
        $ret = $this->api->getToken($userId, $name, $portraitUri);
        var_dump($ret);exit;
//         $ret = json_decode($ret);
//         return $this->succ($ret);
    }
    
    
    
    
    
    
    
    
    
}