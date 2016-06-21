<?php
namespace mia\miagroup\Data\News;

use \DB_Query;

class AppNewsInfo extends DB_Query{
 
    public $dbResource = 'miagroup';
    public $tableName = 'app_news_info';
    public $mapping = [];

    /**
     *@todo 发布一条消息
     *
     *@param $type              enum 消息类型 enum('single','all')
     *@param $resourceType      enum 消息相关资源类型 enum('group','outlets')
     *@param $resourceSubType   enum 消息相关资源子类型 enum('group','img_comment','img_like','follow','mibean','order','score','coupon','productDetail','freebuy','special','outletsList')
     *@param $sendFromUserId    int  消息所属用户id
     *@param $toUserId          int  消息所属用户id
     *@param $resourceId        int  消息相关资源id
     *@param $content           text 消息内容
     *
     *
     **/
    public function addNewsInfo($type, $resourceType, $resourceSubType, $sendFromUserId, $toUserId = 0, $resourceId = 0, $content = "") {
        $newInfoSet = array("content" => htmlspecialchars($content), "type" => $type, "resource_type" => $resourceType, "resource_sub_type" => $resourceSubType, "send_from_id" => $sendFromUserId, "resource_id" => $resourceId, "created" => date("Y-m-d H:i:s", time()));
        
        $data = $this->insert($newInfoSet);
        return $data;
    }
    
    
    
    
    
    
}