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
        return $ret;
    }
    
    /**
     * 创建聊天室
     * return 成功返回聊天室ID
     */
    public function chatroomCreate(){
        //生成唯一的聊天室ID和名字
        $ret = $this->api->chatroomCreate($data);
        //成功返回id
        return $ret;
    }
    
    /**
     * 销毁聊天室
     * @param $chatroomId 要销毁的聊天室的id
     * @return bool
     */
    public function chatroomDestroy($chatroomId){
        $ret = $this->api->chatroomDestroy($chatroomId);
        return $ret;
    }
    
    
    
    
}