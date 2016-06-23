<?php
namespace mia\miagroup\Util;

use \F_Ice;

class RongCloudUtil{
    //融云sdk
    public $api = null;
    
    public function __construct(){
        $this->api  = new RongCloudAPI(F_Ice::$ins->workApp->config->get('busconf.rongcloud')['appKey'],F_Ice::$ins->workApp->config->get('busconf.rongcloud')['appSecret']);
    }
    
    /**
     * 获取token
     * @param unknown $userId
     * @param unknown $name
     * @param unknown $portraitUri
     */
    public function getToken($userId, $name, $portraitUri){
        $ret = $this->api->getToken($userId, $name, $portraitUri);
        if($ret){
            return $ret['token'];
        }else{
            return false;
        }
    }
    
    /**
     * 创建聊天室
     * return 成功返回聊天室ID
     */
    public function chatroomCreate($data){
        //生成唯一的聊天室ID和名字
        $ret = $this->api->chatroomCreate($data);
        if($ret['code'] == 200){
            //成功返回id
            return $data;
        }else{
            return false;
        }

    }
    
    /**
     * 销毁聊天室
     * @param $chatroomId 要销毁的聊天室的id
     * @return bool
     */
    public function chatroomDestroy($chatroomId){
        $ret = $this->api->chatroomDestroy($chatroomId);
        if($ret['code'] == 200){
            return true;
        }else{
            return false;
        }
    }
    
    
    
    
}