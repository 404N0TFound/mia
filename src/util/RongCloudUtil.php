<?php
namespace mia\miagroup\Util;

use \RongCloud\Api;
use \F_Ice;

class RongCloudUtil{
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
        $ret = json_decode($ret);
        return $this->succ($ret);
    }
    
    
}