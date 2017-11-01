<?php

namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\User\User as UserData;
use mia\miagroup\Service\GroupTask as GroupTaskService;
use mia\miagroup\Data\User\UserTask as UserTaskData;

/**
 * 初始化任务
 * Class ImportNews
 * @package mia\miagroup\Daemon\Temp
 */
class Initgrouptask extends \FD_Daemon
{
    public function __construct()
    {
        $this->limit = 200;
        $this->runPath = \F_Ice::$ins->workApp->config->get('app.run_path');

        //创建记录文件，maxId
        if (!file_exists($this->runPath . "/tasklock")) {
            touch($this->runPath . "/tasklock");
            $f = fopen($this->runPath . "/tasklock", "r+");
            fwrite($f, "0");
            fclose($f);
        }
    }

    public function execute()
    {
        if (!file_exists($this->runPath . "/lock")) {
            exit;
        }
        $userData = new UserData();
        $groupTaskService = new GroupTaskService();
        $userTaskData = new UserTaskData();

        while (true) {
            $fp = fopen($this->runPath . "/tasklock", "r+");

            //进行排它型锁定，防止多个进程同时读文件，阻塞到获取锁
            if (flock($fp, LOCK_EX)) {
                //读取maxId
                $maxId = fread($fp, 1024);
                //操作数据库
                if ($maxId) {
                    $condition = " AND id > $maxId ";
                } else {
                    $condition = " ";
                }

                $sql = "SELECT id FROM users WHERE 1=1" . $condition . "ORDER BY id ASC LIMIT " . $this->limit;

                $userInfos = $userData->query($sql);
                if (empty($userInfos)) {
                    break;
                }

                //获取maxId
                $userIds = [];
                array_map(function ($v) use (&$userIds) {
                    $userIds[] = $v["id"];
                }, $userInfos);
                $maxUserId = max($userIds);


                //清空文件内容
                fseek($fp, 0, SEEK_SET);
                ftruncate($fp, 0);
                //写入最新maxId
                fwrite($fp, $maxUserId);
                //释放锁
                flock($fp, LOCK_UN);
                fclose($fp);
            }

            $postRes = $groupTaskService->checkUserTaskStatus($userIds, "first_post")["data"];
            $followRes = $groupTaskService->checkUserTaskStatus($userIds, "first_follow")["data"];
            $evaluateRes = $groupTaskService->checkUserTaskStatus($userIds, "first_evaluate")["data"];
            //任务id=4
            $finished_task = $userTaskData->getTaskList(["user_id" => $userIds]);
            $finished_task_by_id = [];
            foreach ($finished_task as $taskInfo) {
                $finished_task_by_id[$taskInfo["task_id"]][] = $taskInfo["user_id"];
            }
            foreach ($postRes as $k1 => $v1) {
                if ($v1['succ'] == 1 && !in_array($k1, $finished_task_by_id[4])) {
                    $userTaskData->addTaskResult([
                        "user_id" => $k1,
                        "task_id" => 4,
                        "processed_reward_name" => "+1元现金红包",
                        "finished_time" => $v1["time"],
                        "create_time" => date("Y-m-d H:i:s")
                    ]);
                }
            }
            //任务id=5
            foreach ($followRes as $k2 => $v2) {
                if ($v2['succ'] == 1 && !in_array($k2, $finished_task_by_id[5])) {
                    $userTaskData->addTaskResult([
                        "user_id" => $k2,
                        "task_id" => 5,
                        "processed_reward_name" => "+1蜜豆/账号",
                        "finished_time" => $v2["time"],
                        "create_time" => date("Y-m-d H:i:s")
                    ]);
                }
            }
            //任务id=7
            foreach ($evaluateRes as $k3 => $v3) {
                if ($v3['succ'] == 1 && !in_array($k3, $finished_task_by_id[7])) {
                    $userTaskData->addTaskResult([
                        "user_id" => $k3,
                        "task_id" => 7,
                        "processed_reward_name" => "+10蜜豆",
                        "finished_time" => $v3["time"],
                        "create_time" => date("Y-m-d H:i:s")
                    ]);
                }
            }
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}