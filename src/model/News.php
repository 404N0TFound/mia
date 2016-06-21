<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\News\AppNewsInfo;
use mia\miagroup\Data\News\AppUserNews;

class News {

    public $newsInfo;

    public $userNewsRelation;

    public function __construct() {
        $this->newsInfo = new AppNewsInfo();
        $this->userNewsRelation = new AppUserNews();
    }

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
     **/
    public function addNews($type, $resourceType, $resourceSubType, $sendFromUserId, $toUserId = 0, $resourceId = 0, $content = "") {
        $newsId = $this->newsInfo->addNewsInfo($type, $resourceType, $resourceSubType, $sendFromUserId, $toUserId, $resourceId, $content);
        if($newsId){
            $data = $this->userNewsRelation->addNewsUserRelation($newsId, $toUserId);
        }else{
            return false;
        }
        
        return $data;
    }
}
