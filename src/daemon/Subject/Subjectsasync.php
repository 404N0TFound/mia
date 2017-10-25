<?php

namespace mia\miagroup\Daemon\Subject;

use mia\miagroup\Service\Active;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Lib\Redis;

class Subjectsasync extends \FD_Daemon
{
    //任务开关
    private $task_setting = [];

    public function __construct()
    {
        $this->task_setting = \F_Ice::$ins->workApp->config->get('busconf.task.task_setting');
    }

    /**
     * first_post 用户完成了首次发帖，（口碑贴除外）
     * first_follow 用户关注了所有的长文账号
     *
     * post 用户在当日完成了发帖
     * xiaoxiaole 用户在消消乐完成了发帖
     * partake 用户在当日完成了在活动内发帖
     * interact 用户当日在任何一长文内点赞、收藏
     */
    public function execute()
    {
        $redis = new Redis('news/default');
        $redis_info = \F_Ice::$ins->workApp->config->get('busconf.rediskey.subjectKey.async_consume');
        $key = $redis_info["key"];
        $subjectService = new SubjectService();

        $redis_task = new Redis('task/default');
        $redis_task_info = \F_Ice::$ins->workApp->config->get('busconf.rediskey.taskKey.member_task');


        while (true) {
            //消息lpush压入表头的，取从表尾取
            $asyncInfo = $redis->rpop($key);
            if (!empty($asyncInfo)) {
                list($subjectId, $userId, $finishTime) = explode("_", $asyncInfo, 3);
                $redis_task_hash_key = sprintf($redis_task_info["key"], substr($userId, -1), $userId);

                $preNode = \DB_Query::switchCluster(\DB_Query::MASTER);
                //历史首次发帖
                if ($this->task_setting["first_post"] === 1) {
                    //对比历史第一次发帖id
                    $historyInfo = $subjectService->getFirstPubByTime([$userId], 1)["data"];
                    if ($historyInfo[$userId]["id"] == $subjectId) {
                        //首次发帖
                        $redis_task->hSet($redis_task_hash_key, "first_post", json_encode(["num" => 1, "time" => $historyInfo[$userId]["time"], "status" => 0, "reward" => "", "is_processed" => 1]));
                    }
                }
                //当天首次发帖
                if ($this->task_setting["post"] === 1) {
                    //对比当天第一次发帖id
                    $todayInfo = $subjectService->getFirstPubByTime([$userId], 1, date("Y-m-d"))["data"];
                    if ($todayInfo[$userId]["id"] == $subjectId) {
                        //首次发帖
                        $redis_task->hSet($redis_task_hash_key, "post", json_encode(["num" => 1, "time" => $todayInfo[$userId]["time"], "status" => 0, "reward" => "", "is_processed" => 1]));
                    }
                }

                //当天参加活动
                if ($this->task_setting["partake"] === 1 && !$redis_task->hExists($redis_task_hash_key, 'partake')) {
                    //判断活动id
                    $activeService = new Active();
                    $subjectActiveInfo = $activeService->getActiveSubjectBySids([$subjectId], [])["data"];
                    if (!empty($subjectActiveInfo)) {
                        //参加了活动
                        $redis_task->hSet($redis_task_hash_key, "partake", json_encode(["num" => 1, "time" => $finishTime, "status" => 0, "reward" => "", "is_processed" => 1]));
                    }
                }
                //当天参加消消乐
                if ($this->task_setting["xiaoxiaole"] === 1 && !$redis_task->hExists($redis_task_hash_key, 'xiaoxiaole')) {
                    $activeService = new Active();
                    $subjectActiveInfo = $activeService->getActiveSubjectBySids([$subjectId], [])["data"];
                    if (!empty($subjectActiveInfo)) {
                        //参加了活动
                        //判断活动id，是否是消消乐
                        $activeInfo = $activeService->getSingleActiveById($subjectActiveInfo[$subjectId]["active_id"], [], [])["data"];
                        if ($activeInfo["active_type"] == "xiaoxiaole") {
                            $redis_task->hSet($redis_task_hash_key, "xiaoxiaole", json_encode(["num" => 1, "time" => $finishTime, "status" => 0, "reward" => "", "is_processed" => 1]));
                        }
                    }
                }
                if ($redis_task->exists($redis_task_hash_key)) {
                    $redis_task->expireAt($redis_task_hash_key, strtotime(date('Y-m-d 23:59:59')));
                }
                \DB_Query::switchCluster($preNode);
            } else {
                break;
            }
        }
    }
}