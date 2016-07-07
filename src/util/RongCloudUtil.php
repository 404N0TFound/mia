<?php
namespace mia\miagroup\Util;

use \F_Ice;
use \mia\miagroup\Lib\RongCloudAPI;

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
    
    /**
     * 用户向用户发送消息
     */
    public function messagePublish($fromUserId, $toUserId = array(), $objectName, $content, $pushContent='', $pushData = ''){
        $result = $this->api->messagePublish($fromUserId, $toUserId, $objectName, $content, $pushContent, $pushData);
        return $result;
    }
    
    /**
     * 用户向聊天室内发送消息
     */
    public function messageChatroomPublish($fromUserId, $toChatroomId = array(), $objectName, $content){
        $result = $this->api->messageChatroomPublish($fromUserId, $toChatroomId, $objectName, $content);
        return $result;
    }
    
    /**
     * 获取聊天室的在线人数
     */
    public function getChatroomUserNumber($chatroomId){
        $result = $this->api->userChatroomQuery($chatroomId);
        if(!$result){
            return false;
        }else{
            return $result['total'];
        }
    }
    
    /**
     * 获取聊天室消息的历史记录
     * 指定北京时间某天某小时，格式为2014010101,表示：2014年1月1日凌晨1点
     */
    public function messageHistory($date){
        $result = $this->api->messageHistory($date);
        if(!$result){
            return false;
        }else{
            return $result['url'];
        }
    }
    
    /**
     * 加入聊天室
     * @param unknown $userId
     * @param unknown $chatroomId
     */
    public function joinChatRoom(array $userId,$chatroomId){
        $result = $this->api->chatroomJoin($userId, $chatroomId);
        if($result['code'] == 200){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 封禁用户 方法
     * @param $userId   用户 Id。（必传）
     * @param $minute   封禁时长,单位为分钟，最大值为43200分钟。（必传）
     * @return mixed
     */
    public function disableUser($userId,$minute)
    {
        $result = $this->api->userBlock($userId, $minute);
        if($result['code'] == 200)
            return true;
        return false;
    }

    /**
     * 添加敏感词
     * @param $word 敏感词，最长不超过 32 个字符。（必传）
     * @return mixed
     */
    public function wordfilterAdd($word)
    {
        $result = $this->api->wordfilterAdd($word);
        if ($result['code'] == 200) {
            return true;
        }

        return false;
    }

    /**
     * 移除敏感词
     * @param $word 敏感词，最长不超过 32 个字符。（必传）
     * @return mixed
     */
    public function wordfilterDelete($word)
    {
        $result = $this->api->wordfilterDelete($word);
        if ($result['code'] == 200) {
            return true;
        }

        return false;
    }

    /**
     * 查询敏感词列表
     * @return array
     */
    public function wordfilterList()
    {
        $result = $this->api->wordfilterList();
        if ($result['code'] == 200) {
            return $result['words'];
        }

        return false;
    }
    
    
}