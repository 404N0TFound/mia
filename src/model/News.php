<?php

namespace mia\miagroup\Model;

use mia\miagroup\Data\News\AppNewsInfo;
use mia\miagroup\Data\News\AppUserNews;
use mia\miagroup\Data\News\PushSetting;
use mia\miagroup\Data\News\SystemNews;
use mia\miagroup\Data\News\UserNews;
use mia\miagroup\Lib\Redis;

class News
{

    public $newsInfo;
    private $redis;
    public $userNewsRelation;
    private $config;

    public function __construct()
    {
        $this->redis = null;
        $this->newsInfo = new AppNewsInfo();
        $this->userNewsRelation = new AppUserNews();
        $this->systemNews = new SystemNews();
        $this->userNews = new UserNews();
        $this->pushSetting = new PushSetting();
        $this->config = \F_Ice::$ins->workApp->config->get('busconf.news');
        $this->newsSetLimit = 1000;
    }


    /*=============5.7新版本消息=============*/
    /**
     * 发送消息
     * @param $insertData array
     * @return mixed
     */
    public function postNews($insertData)
    {
        if (empty($insertData['user_id'])) {
            return false;
        }
        if (isset($insertData['id'])) {
            //更新
            $setData = $insertData;
            $where[] = ['id', $insertData['id']];
            $res = $this->userNews->updateNews($setData, $where);
        } else {
            //新增
            $res = $this->userNews->addUserNews($insertData);
        }
        return $res;
    }

    /**
     * 获取最新一条消息
     * @param $type
     * @param $toUserId
     * @param int $source_id
     * @param bool $by_day 是否获取当天的最新消息
     * @param array $status
     * @return mixed
     */
    public function getLastNews($type, $toUserId, $source_id = 0, $by_day = false, $status = [0, 1])
    {
        $conditions["user_id"] = $toUserId;
        $conditions["news_type"] = $type;
        if (!empty($source_id)) {
            $conditions["source_id"] = $source_id;
        }
        $conditions["status"] = $status;
        if ($by_day) {
            $conditions["by_day"] = $by_day;
        }
        $conditions["limit"] = 1;
        $res = $this->userNews->getNewsList($conditions);
        return $res;
    }

    /**
     * 获取不同分类计数
     * @param $userId
     * @param array $type array 包括多个最低级的news类型；或者total
     * @return int
     */
    public function getUserNewsCount($userId, $type = [])
    {
        if(empty($userId)) {
            return 0;
        }
        $conditions = [];
        if (count($type) == 1 && $type[0] == "total") {

        } else {
            $conditions["news_type"] = $type;
        }
        $conditions["user_id"] = $userId;
        $newsNum = $this->userNews->getUserNewsNum($conditions);
        return $newsNum;
    }

    /**
     * 按分类删除用户消息
     * @param $userId
     * @param $type array 最低级消息分类数组
     * @return boolean
     */
    public function delUserNews($userId, $type)
    {
        if (empty($userId) || empty($type)) {
            return false;
        }
        $where = [];
        $where["user_id"] = $userId;
        $where["news_type"] = $type;
        $res = $this->userNews->updateStatus($where);
        return $res;
    }

    /**
     * 根据id，查询用户news列表信息
     * @param $newsIds
     * @param $userId
     * @return array
     */
    public function getNewsInfoList($newsIds, $userId)
    {
        if (empty($newsIds) || empty($userId)) {
            return [];
        }
        $conditions["id"] = $newsIds;
        $conditions["user_id"] = $userId;
        $res = $this->userNews->getNewsList($conditions);
        $return = [];
        foreach ($res as $val) {
            $return[$val['id']] = $val;
        }
        return $return;
    }

    /**
     * 获取用户分类消息列表
     * @param $category
     * @param $userId
     * @param $limit
     * @return array
     */
    public function getBatchList($category, $userId, $limit)
    {
        if (empty($category) || empty($userId)) {
            return [];
        }
        $conditions["news_type"] = $category;
        $conditions["user_id"] = $userId;
        $conditions['limit'] = $limit;
        $conditions['fields'] = "id,create_time";
        $res = $this->userNews->getNewsList($conditions);

        return $res;
    }

    /**
     * 设置已读状态
     * @param $userId
     * @param $allType array 最低分类数组
     * @return bool
     */
    public function setReadStatus($userId, $allType)
    {
        if (empty($userId) || empty($allType)) {
            return false;
        }
        $res = $this->userNews->changeReadStatus($userId, $allType);
        return $res;
    }


    /**
     * 获取用户push设置
     * @param $userId
     * @return array
     */
    public function getUserPushSetting($userId)
    {
        if (empty($userId)) {
            return [];
        }
        $conditions['user_id'] = $userId;
        $res = $this->pushSetting->getList($conditions);
        return $res;
    }

    /**
     * 获取单个type的设置详情
     * @param $userId
     * @param $type
     * @return array
     */
    public function getTypeSet($userId, $type)
    {
        if (empty($userId) || empty($type)) {
            return [];
        }
        $conditions['user_id'] = $userId;
        $conditions['type'] = $type;
        $res = $this->pushSetting->getList($conditions);
        return $res;
    }

    /**
     * 修改push设置
     * @param $userId
     * @param $type
     * @param int $value
     * @return int
     */
    public function pushSet($userId, $type, $value = 1)
    {
        if (empty($userId) || empty($type)) {
            return 0;
        }
        $setData[] = ['value', $value];
        $where[] = ['user_id', $userId];
        $where[] = ['type', $type];
        $res = $this->pushSetting->updateSetting($setData, $where);
        return $res;
    }

    /**
     * 添加push设置
     * @param $insertData
     * @return bool
     */
    public function addTypeSet($insertData)
    {
        $res = $this->pushSetting->addTypeSet($insertData);
        return $res;
    }

    /*=============5.7新版本消息end=============*/


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
     * @return array
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
            case "group_index_count":
                $redisKey = "group_index_count";
                break;
            case "group_count":
                $redisKey = "group_count";
                break;
            case "outlets_count":
                $redisKey = "outlets_count";
                break;
        }
        if ($this->redis) {
            $redis = $this->redis;
        } else {
            $redis = new Redis('news/default');
            $this->redis = $redis;
        }
        $user_list_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.newsKey.' . $redisKey . '.key'), intval($userId));
        $expire_time = \F_Ice::$ins->workApp->config->get('busconf.rediskey.newsKey.' . $redisKey . '.expire_time');
        return [$redis, $user_list_key, $expire_time];
    }


    /**
     * 获取用户消息列表
     */
    public function getUserNewsList($userId, $type)
    {
        if (!in_array($type, ["outlets", "group"])) {
            return [];
        }
        //从redis取,有序集合
        list($redis, $user_list_key, $expire_time) = $this->getRedisKey($type, $userId);
        $redis->del($user_list_key);

        $user_news_id_list = $redis->zRevRange($user_list_key, 0, -1);

        if (!empty($user_news_id_list)) {
            return $user_news_id_list;
        }
        //有序集合为空从db取数据
        $conditions = [];
        $conditions["user_id"] = $userId;
        $conditions["limit"] = 1000;
        $conditions["order_by"] = "create_time DESC";
        switch ($type) {
            case "outlets"://特卖
                $conditions["news_type"] = $this->config["outlets"];
                break;
            case "group"://社交
                $conditions["news_type"] = $this->config["group"];
                break;
        }
        $user_news_id_list = $this->userNews->getUserNewIdList($conditions);

        //设置redis,key是id，val是时间
        foreach ($user_news_id_list as $key=>$val) {
            $redis->zAdd($user_list_key, $val, $key);
        }
        $num = $redis->zCard($user_list_key);
        if($num > $this->newsSetLimit) {
            $redis->zRemRangeByRank($user_list_key, 0, $num - $this->newsSetLimit - 1);
        }
        $user_news_id_list = $redis->zRevRange($user_list_key, 0, -1);
        return $user_news_id_list;
    }


    /**
     * 获取用户蜜芽圈首页互动消息计数
     * @param int $userId
     * @return int
     */
    public function getUserGroupNewsNum($userId)
    {
        list($redis, $user_group_index_count, $expire_time) = $this->getRedisKey("group_index_count", $userId);
        //$redis->del($user_group_index_count);
        $groupIndexNum = $redis->get($user_group_index_count);
        if ($groupIndexNum !== false) {
            return $groupIndexNum;
        }
        $conditions = [];
        $conditions["news_type"] = $this->config["group_index"];
        $conditions["user_id"] = $userId;
        $groupIndexNum = $this->userNews->getUserNewsNum($conditions);
        $redis->setex($user_group_index_count, $groupIndexNum, $expire_time);
        return $groupIndexNum;
    }


    /**
     * 获取用户总未读消息计数
     * @param int $userId
     * @return array
     */
    public function getUserAllNewsNum($userId)
    {
        //社交消息计数
        list($redis, $user_group_count, $expire_time) = $this->getRedisKey("group_count", $userId);
        //$redis->del($user_group_count);
        $groupNum = $redis->get($user_group_count);
        if ($groupNum === false) {
            $conditions = [];
            $conditions["news_type"] = $this->config["group"];
            $conditions["user_id"] = $userId;
            $groupNum = $this->userNews->getUserNewsNum($conditions);
            $redis->setex($user_group_count, $groupNum, $expire_time);
        }

        //特卖消息计数
        list($redis, $user_outlets_count, $expire_time) = $this->getRedisKey("outlets_count", $userId);
        //$redis->del($user_outlets_count);
        $outletsNum = $redis->get($user_outlets_count);
        if ($outletsNum === false) {
            $conditions = [];
            $conditions["news_type"] = $this->config["outlets"];
            $conditions["user_id"] = $userId;
            $outletsNum = $this->userNews->getUserNewsNum($conditions);
            $redis->setex($user_outlets_count, $outletsNum, $expire_time);
        }
        return [$outletsNum, $groupNum];
    }


    /**
     * 修改用户消息状态：未读->已读
     * @param int $userId
     * @return mixed
     */
    public function changeReadStatus($userId)
    {
        if (empty($userId)) {
            return false;
        }
        $res = $this->userNews->changeReadStatus($userId);
        return $res;
    }


    /**
     * 清空用户未读消息数
     */
    public function clearNewsNum($userId)
    {
        //蜜芽圈首页互动消息计数
        list($redis, $resisKey, $expire_time) = $this->getRedisKey("group_count", $userId);
        $redis->setex($resisKey, 0, $expire_time);
        list($redis, $resisKey, $expire_time) = $this->getRedisKey("outlets_count", $userId);
        $redis->setex($resisKey, 0, $expire_time);
        //用户总未读消息计数
        list($redis, $resisKey, $expire_time) = $this->getRedisKey("group_index_count", $userId);
        $redis->setex($resisKey, 0, $expire_time);
    }


    /**
     * 获取消息所属的大类
     * @param $type string 消息小分类
     * @return array
     */
    public function getCurType($type)
    {
        if (!in_array($type, $this->config["all_type"])) {
            return [];
        }
        if (in_array($type, $this->config["group"])) {
            $curType[] = "group";
        }
        if (in_array($type, $this->config["outlets"])) {
            $curType[] = "outlets";
        }
        if (in_array($type, $this->config["group_index"])) {
            $curType[] = "group_index";
        }
        return $curType;
    }


    /**
     * 新增用户消息
     */
    public function addUserNews($insertData, $type)
    {
        if (empty($insertData['user_id'])) {
            return 0;
        }
        $curType = $this->getCurType($type);

        $res = $this->userNews->addUserNews($insertData);
        //redis list 添加数据（在列表不为空的情况下）
//        list($redis, $user_list_key, $expire_time) = $this->getRedisKey($curType, $insertData['user_id']);
//        $user_news_id_list = $redis->zRevRange($user_list_key, 0, -1);
//        if (!empty($user_news_id_list)) {
//            $redis->zAdd($user_list_key, $res, strtotime($insertData['create_time']));
//            $num = $redis->zCard($user_list_key);
//            if($num > $this->newsSetLimit) {
//                $redis->zRemRangeByRank($user_list_key, 0, $num - $this->newsSetLimit - 1);
//            }
//        }
        //消息计数增加
//        if (in_array("group", $curType)) {
//            list($redis, $resisKey, $expire_time) = $this->getRedisKey("group_count", $insertData['user_id']);
//            $redis->incr($resisKey);
//            $redis->expire($resisKey, $expire_time);
//        }
//        if (in_array("outlets", $curType)) {
//            list($redis, $resisKey, $expire_time) = $this->getRedisKey("outlets_count", $insertData['user_id']);
//            $redis->incr($resisKey);
//            $redis->expire($resisKey, $expire_time);
//        }
//        if (in_array("group_index", $curType)) {
//            list($redis, $resisKey, $expire_time) = $this->getRedisKey("index_group_count", $insertData['user_id']);
//            $redis->incr($resisKey);
//            $redis->expire($resisKey, $expire_time);
//        }
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
     * @todo 用户分组 $userId 处理
     */
    public function getPullList($userId, $maxSystemId, $create_date)
    {
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
    public function batchAddUserSystemNews($systemNewsList, $userId)
    {
        if (empty($systemNewsList)) {
            return 0;
        }
        $insertData = [];
        $count = 0;

        //判断消息列表缓存是否存在
        list($redis, $user_list_key_group, $expire_time) = $this->getRedisKey("group", $userId);
        list($redis, $user_list_key_outlets, $expire_time) = $this->getRedisKey("outlets", $userId);

        $user_news_id_list_group = $redis->lRange($user_list_key_group, 0, -1);
        $user_news_id_list_outlets = $redis->lRange($user_list_key_outlets, 0, -1);
        $check_group = 0;
        if (!empty($user_news_id_list_group)) {
            $check_group = 1;
        }
        $check_outlets = 0;
        if (!empty($user_news_id_list_outlets)) {
            $check_outlets = 1;
        }

        foreach ($systemNewsList as $value) {
            $insertData["news_type"] = $value["news_type"];
            $insertData["user_id"] = $value["user_id"];
            $insertData["news_id"] = $value["id"];
            $insertData["send_user"] = $value["send_user"];
            //$insertData["ext_info"] = $value["ext_info"];  //系统消息的额外信息自己去取，避免个人消息表插入过多数据
            $source_id = json_decode($value["ext_info"], true)["source_id"];
            if (!empty($source_id)) {
                $insertData["source_id"] = $source_id;
            }

            $insertData["create_time"] = date("Y-m-d H:i:s");//创建时间即为拉取时间
            $res = $this->userNews->addUserNews($insertData);
            //redis 消息list添加数据
            if (!in_array($value["news_type"], $this->config["all_type"]) || empty($insertData['user_id'])) {
                continue;
            }
            if (in_array($value["news_type"], $this->config["group"]) && $check_group == 1) {
                $curType = "group";
                list($redis, $user_list_key, $expire_time) = $this->getRedisKey($curType, $insertData['user_id']);
                $redis->zAdd($user_list_key, $res, strtotime($insertData["create_time"]));
                $num = $redis->zCard($user_list_key);
                if($num > $this->newsSetLimit) {
                    $redis->zRemRangeByRank($user_list_key, 0, $num - $this->newsSetLimit - 1);
                }
            }
            if (in_array($value["news_type"], $this->config["outlets"]) && $check_outlets == 1) {
                $curType = "outlets";
                list($redis, $user_list_key, $expire_time) = $this->getRedisKey($curType, $insertData['user_id']);
                $redis->zAdd($user_list_key, $res, strtotime($insertData["create_time"]));
                $num = $redis->zCard($user_list_key);
                if($num > $this->newsSetLimit) {
                    $redis->zRemRangeByRank($user_list_key, 0, $num - $this->newsSetLimit - 1);
                }
            }
            //消息计数增加
            $curType = $this->getCurType($value["news_type"]);
            if (in_array("group", $curType)) {
                list($redis, $resisKey, $expire_time) = $this->getRedisKey("group_count", $insertData['user_id']);
                $redis->incr($resisKey);
                $redis->expire($resisKey, $expire_time);
            }
            if (in_array("outlets", $curType)) {
                list($redis, $resisKey, $expire_time) = $this->getRedisKey("outlets_count", $insertData['user_id']);
                $redis->incr($resisKey);
                $redis->expire($resisKey, $expire_time);
            }
            if (in_array("group_index", $curType)) {
                list($redis, $resisKey, $expire_time) = $this->getRedisKey("index_group_count", $insertData['user_id']);
                $redis->incr($resisKey);
                $redis->expire($resisKey, $expire_time);
            }
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
