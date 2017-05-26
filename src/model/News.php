<?php

namespace mia\miagroup\Model;

use mia\miagroup\Data\News\AppNewsInfo;
use mia\miagroup\Data\News\AppUserNews;
use mia\miagroup\Data\News\SystemNews;
use mia\miagroup\Data\News\UserNews;
use mia\miagroup\Lib\Redis;

class News
{

    public $newsInfo;
    private $redis;
    public $userNewsRelation;

    public function __construct()
    {
        $this->redis = null;
        $this->newsInfo = new AppNewsInfo();
        $this->userNewsRelation = new AppUserNews();
        $this->systemNews = new SystemNews();
        $this->userNews = new UserNews();
    }

    /**
     *发布一条消息 | 旧版本
     * @param $type              enum 消息类型 enum('single','all')
     * @param $resourceType      enum 消息相关资源类型 enum('group','outlets')
     * @param $resourceSubType   enum 消息相关资源子类型 enum('group','img_comment','img_like','follow','mibean','order','score','coupon','productDetail','freebuy','special','outletsList')
     * @param $sendFromUserId    int  消息所属用户id
     * @param $toUserId          int  消息所属用户id
     * @param $resourceId        int  消息相关资源id
     * @param $content           string 消息内容
     * @return mixed
     **/
    public function addNews($type, $resourceType, $resourceSubType, $sendFromUserId, $toUserId = 0, $resourceId = 0, $content = "")
    {
        $newsId = $this->newsInfo->addNewsInfo($type, $resourceType, $resourceSubType, $sendFromUserId, $toUserId, $resourceId, $content);
        if ($newsId) {
            $data = $this->userNewsRelation->addNewsUserRelation($newsId, $toUserId);
        } else {
            return false;
        }
        return $data;
    }

    /*================新消息系统================*/

    /**
     * 获取不同分类的用户的redis消息列表，redis实例
     * @return string
     */
    public function getRedisKey($type, $userId)
    {
        switch ($type) {
            case "outlets":
                $redisKey = "user_news_list_outlets";
                break;
            case "group":
                $redisKey = "user_news_list_group";
                break;
        }
        if ($this->redis) {
            $redis = $this->redis;
        } else {
            $redis = new Redis('news/default');
            $this->redis = $redis;
        }
        $user_list_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.newsKey.' . $redisKey . '.key'), intval($userId));
        return [$redis, $user_list_key];
    }

    /**
     * 获取用户消息列表
     */
    public function getUserNewsList($userId, $type)
    {
        if(!in_array($type,["outlets","group"])) {
            return [];
        }
        //从redis取链表
        list($redis, $user_list_key) = $this->getRedisKey($type, $userId);
        //$redis->del($user_list_key);
        $user_news_id_list = $redis->lRange($user_list_key, 0, -1);

        if (!empty($user_news_id_list)) {
            return $user_news_id_list;
        }
        //链表为空从db取数据
        $conditions = [];
        $conditions["user_id"] = $userId;
        $conditions["limit"] = 1000;
        $conditions["order_by"] = "create_time DESC";
        switch ($type) {
            case "outlets"://特卖
                $conditions["news_type"] = \F_Ice::$ins->workApp->config->get('busconf.news.outlets');
                break;
            case "group"://社交
                $conditions["news_type"] = \F_Ice::$ins->workApp->config->get('busconf.news.group');
                break;
        }
        $user_news_id_list = $this->userNews->getUserNewIdList($conditions);

        //设置redis
        foreach ($user_news_id_list as $val) {
            $redis->rpush($user_list_key, $val);
        }
        $redis->ltrim($user_list_key, 0, 999);
        return $user_news_id_list;
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
    public function changeReadStatus($userId)
    {
        if(empty($userId)) {
            return false;
        }
        $res = $this->userNews->changeReadStatus($userId);
        return $res;
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
    public function addUserNews($insertData, $type)
    {
        $typeConfig = \F_Ice::$ins->workApp->config->get('busconf.news');
        if (!in_array($type, $typeConfig["all_type"]) || empty($insertData['user_id'])) {
            return 0;
        }
        if (in_array($type, $typeConfig["group"])) {
            $curType = "group";
        }
        if (in_array($type, $typeConfig["outlets"])) {
            $curType = "outlets";
        }
        $res = $this->userNews->addUserNews($insertData);
        //redis list 添加数据
        list($redis, $user_list_key) = $this->getRedisKey($curType, $insertData['user_id']);
        $redis->lpush($user_list_key, $res);
        $redis->ltrim($user_list_key, 0, 999);
        //TODO 消息计数增加
        return $res;
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
    public function getPullList($userId, $maxSystemId, $create_date)
    {
        //TODO:: 用户分组 $userId 处理

        //查询条件
        if (empty($maxSystemId) && !empty($create_date)) {
            $conditions["gt"]["create_time"] = $create_date;
        } else {
            $conditions["gt"]["id"] = intval($maxSystemId);
        }
        $conditions["lt"]["send_time"] = date("Y-m-d H:i:s");
        $conditions["gt"]["abandon_time"] = date("Y-m-d H:i:s");
        $conditions["status"] = 1;

        $res = $this->systemNews->getSystemNewsList($conditions);
        if (empty($res)) {
            return [];
        }
        array_walk($res, function (&$n) use ($userId) {
            $n["user_id"] = $userId;
        });
        return $res;
    }


    /**
     * 获取某个用户消息里最大的系统消息ID
     */
    public function getMaxSystemId($userId)
    {
        if (empty($userId)) {
            return false;
        }
        //查询条件
        $conditions["user_id"] = $userId;
        $conditions["gt"]["news_id"] = 0;

        $res = $this->userNews->getMaxSystemId($conditions);
        return $res;
    }


    /**
     * 批量给用户添加系统消息
     */
    public function batchAddUserSystemNews($systemNewsList)
    {
        if (empty($systemNewsList)) {
            return 0;
        }
        $insertData = [];
        $count = 0;
        $typeConfig = \F_Ice::$ins->workApp->config->get('busconf.news');

        foreach ($systemNewsList as $value) {
            $insertData["news_type"] = $value["news_type"];
            $insertData["user_id"] = $value["user_id"];
            $insertData["news_id"] = $value["id"];
            //$insertData["ext_info"] = $value["ext_info"];  //系统消息的额外信息自己去取，避免个人消息表插入过多数据
            $insertData["create_time"] = date("Y-m-d H:i:s");//创建时间即为拉取时间
            $res = $this->userNews->addUserNews($insertData);
            //redis 链表添加数据
            if (!in_array($value["news_type"], $typeConfig["all_type"]) || empty($insertData['user_id'])) {
                continue;
            }
            if (in_array($value["news_type"], $typeConfig["group"])) {
                $curType = "group";
            }
            if (in_array($value["news_type"], $typeConfig["outlets"])) {
                $curType = "outlets";
            }
            list($redis, $user_list_key) = $this->getRedisKey($curType, $insertData['user_id']);
            $redis->lpush($user_list_key, $res);
            $redis->ltrim($user_list_key, 0, 999);
            //TODO 消息计数增加
            $count++;
            $insertData = [];
        }
        return intval($count);
    }

    /**
     * 批量获取用户消息
     */
    public function getBatchNewsInfo($newsIds, $userId)
    {
        if (empty($newsIds) || empty($userId)) {
            return [];
        }
        $conditions["id"] = $newsIds;
        $conditions["user_id"] = $userId;

        $newsInfo = $this->userNews->getNewsList($conditions);
        //还原顺序
        $res = [];
        foreach ($newsInfo as $news) {
            $res[$news["id"]] = $news;
        }

        $return = [];
        foreach ($newsIds as $val) {
            $return[$val] = $res[$val];
        }
        return array_values($return);
    }

    /**
     * 批量获取系统消息列表
     */
    public function getSystemNewsList($newsIds)
    {
        if (empty($newsIds)) {
            return [];
        }
        $conditions["id"] = $newsIds;
        $res = $this->systemNews->getSystemNewsList($conditions);
        $return = [];
        foreach ($res as $val) {
            $return[$val["id"]] = $val;
        }
        return $return;
    }

}
