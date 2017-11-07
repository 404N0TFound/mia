<?php

namespace mia\miagroup\Service;

use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Service\UserRelation as UserRelationService;
use mia\miagroup\Lib\Redis;

class GroupTask extends \mia\miagroup\Lib\Service
{
    private $task_setting = [];

    /**
     * first_post 用户完成了首次发帖，（口碑贴除外）
     * first_follow 用户关注了所有的长文账号
     *
     * post 用户在当日完成了发帖
     * xiaoxiaole 用户在消消乐完成了发帖
     * partake 用户在当日完成了在活动内发帖
     * interact 用户当日在任何一长文内点赞、收藏
     */
    public function __construct()
    {
        $this->task_setting = \F_Ice::$ins->workApp->config->get('busconf.task.task_setting');
        parent::__construct();
    }

    /**
     * 批量检测用户是否完成任务
     * @param $userIds array 用户ID
     * @param $type string 任务类型
     * first_post  蜜芽圈首单
     * first_follow  关注官方账号
     */
    public function checkUserTaskStatus($userIds, $type)
    {
        if (!in_array(trim($type), ["first_post", "first_follow","first_evaluate"])) {
            return $this->error(500, "类型错误");
        }
        $return = [];
        switch ($type) {
            //首次发帖
            case "first_post":
                $subjectService = new SubjectService();
                $return = $subjectService->checkUserFirstPub($userIds, 1)['data'];
                break;
            //关注完所有长文用户
            case "first_follow":
                $userRelationService = new UserRelationService();
                $followIds = \F_Ice::$ins->workApp->config->get('busconf.userrelation.task_follow');
                $return = $userRelationService->getUserTaskFollow($userIds, $followIds)['data'];
                break;
            case "first_evaluate":
                $subjectService = new SubjectService();
                $return = $subjectService->checkUserFirstPub($userIds, 2)['data'];
                break;
        }
        return $this->succ($return);
    }


    /**
     * 检查关注任务，完成状态
     */
    public function checkFollowTask($userId)
    {
        if (empty($userId) || $this->task_setting["first_follow"] !== 1) {
            return $this->succ([]);
        }
        $preNode = \DB_Query::switchCluster(\DB_Query::MASTER);
        $res = $this->checkUserTaskStatus([$userId], "first_follow")["data"];
        if (isset($res[$userId]) && $res[$userId]["succ"] == 1) {
            $redis_task = new Redis('task/default');
            $redis_task_info = \F_Ice::$ins->workApp->config->get('busconf.rediskey.taskKey.member_task');
            $redis_task_hash_key = sprintf($redis_task_info["key"], substr($userId, -1), $userId);
            $redis_task->hSet($redis_task_hash_key, "first_follow", json_encode(["num" => 1, "time" => date("Y-m-d H:i:s"), "status" => 0, "reward" => "", "is_processed" => 1]));
            $redis_task->expireAt($redis_task_hash_key, strtotime(date('Y-m-d 23:59:59')));
        }
        \DB_Query::switchCluster($preNode);
        return $this->succ([]);
    }

    /**
     * 检查长文任务，完成状态 interact
     */
    public function checkBlogTask($userId)
    {
        if (empty($userId) || $this->task_setting["interact"] !== 1) {
            return $this->succ([]);
        }
        $redis_task = new Redis('task/default');
        $redis_task_info = \F_Ice::$ins->workApp->config->get('busconf.rediskey.taskKey.member_task');
        $redis_task_hash_key = sprintf($redis_task_info["key"], substr($userId, -1), $userId);
        if (!$redis_task->hExists($redis_task_hash_key, 'interact')) {
            $redis_task->hSet($redis_task_hash_key, "interact", json_encode(["num" => 1, "time" => date("Y-m-d H:i:s"), "status" => 0, "reward" => "", "is_processed" => 1]));
            $redis_task->expireAt($redis_task_hash_key, strtotime(date('Y-m-d 23:59:59')));
        }
        return $this->succ([]);
    }
}