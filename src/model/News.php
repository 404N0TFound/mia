<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\News\AppNewsInfo;
use mia\miagroup\Data\News\AppUserNews;
use mia\miagroup\Data\News\SystemNews;
use mia\miagroup\Data\News\UserNews;

class News {

    public $newsInfo;

    public $userNewsRelation;

    public function __construct() {
        $this->newsInfo = new AppNewsInfo();
        $this->userNewsRelation = new AppUserNews();
        $this->systemNews = new SystemNews();
        $this->userNews = new UserNews();
    }

    /**
     *@todo 发布一条消息
     *@param $type              enum 消息类型 enum('single','all')
     *@param $resourceType      enum 消息相关资源类型 enum('group','outlets')
     *@param $resourceSubType   enum 消息相关资源子类型 enum('group','img_comment','img_like','follow','mibean','order','score','coupon','productDetail','freebuy','special','outletsList')
     *@param $sendFromUserId    int  消息所属用户id
     *@param $toUserId          int  消息所属用户id
     *@param $resourceId        int  消息相关资源id
     *@param $content           string 消息内容
     *@return mixed
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

    /*================新消息系统================*/

    /**
     * 获取用户消息列表
     */
    public function getUserNewsList()
    {
        //redis
        //db
    }

    /**
     * 获取用户蜜芽圈首页互动消息计数
     */
    public function getUserGroupNewsNum()
    {

    }

    /**
     * 获取用户总未读消息计数
     */
    public function getUserAllNewsNum()
    {

    }

    /**
     * 修改用户消息状态：未读->已读
     */
    public function changeReadStatus()
    {

    }

    /**
     * 清空用户未读消息数
     */
    public function clearNewsNum()
    {
        //蜜芽圈首页互动消息计数
        //用户总未读消息计数
    }

    /**
     * 新增用户消息
     */
    public function addUserNews()
    {

    }

    /**
     * 新增系统消息
     */
    public function addSystemNews($insertData)
    {
        $res = $this->systemNews->addSystemNews($insertData);
        return $res;
    }

    /**
     * 获取用户未拉取的系统消息列表
     */
    public function getUnreadNewsList()
    {

    }

    /**
     * 批量给用户添加系统消息
     */
    public function batchAddUserSystemNews()
    {

    }
}
