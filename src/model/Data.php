<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\Data\TaskResult;
use mia\miagroup\Data\Data\Task;

class Data
{
    public function __construct()
    {
        $this->taskData = new Task();
        $this->taskResultData = new TaskResult();
    }


    public function addDataTask($insertData)
    {
        $res = $this->taskData->addDataTask($insertData);
        return $res;
    }

    public function getTaskList($conditions)
    {
        if (!isset($conditions["page"]) || intval($conditions["page"]) <= 0) {
            $conditions["page"] = 1;
        }

        if (!isset($conditions["count"]) || intval($conditions["count"]) <= 0) {
            $conditions["count"] = 10;
        }
        $res = $this->taskData->getTaskList($conditions);
        return $res;
    }

    public function getTaskData($taskId, $page = 1, $count = 10)
    {
        if (empty($taskId)) {
            return $this->succ([]);
        }
        $res = $this->taskResultData->getTaskData($taskId,$page,$count);
        return $res;
    }
    
    public function updateDataTask($taskId,$updateData)
    {
        $res = $this->taskData->updateDataTask($taskId,$updateData);
        return $res;
    }
}