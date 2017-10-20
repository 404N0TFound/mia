<?php

namespace mia\miagroup\Service;

use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Service\UserRelation as UserRelationService;

class GroupTask extends \mia\miagroup\Lib\Service
{
    public function __construct()
    {
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
        if (!in_array(trim($type), ["first_post", "first_follow"])) {
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
                $return = $userRelationService->getUserTaskFollow($userIds,$followIds)['data'];
                break;
        }
        return $this->succ($return);
    }

    /**
     * 添加任务完成结果到redis
     * first_post 用户完成了首次发帖，（口碑贴除外）
     * first_follow 用户关注了所有的长文账号
     *
     * post 用户在当日完成了发帖
     * xiaoxiaole 用户在消消乐完成了发帖
     * partake 用户在当日完成了在活动内发帖
     * interact 用户当日在任何一长文内点赞、收藏
     */
    public function recordTaskResult($userId, $taskType)
    {

    }
}