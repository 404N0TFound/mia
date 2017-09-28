<?php

namespace mia\miagroup\Data\Data;

class TaskResult extends \DB_Query
{
    public $dbResource = 'miagroup';
    public $tableName = 'group_data_result';
    public $mapping = [];

    public function getTaskData($taskId, $page = 1, $count = 10)
    {
        $where[] = ["task_id", $taskId];
        $fields = "*";
        $limit = $count;
        $offset = ($page - 1) * $limit;
        $order = "id ASC";
        $data = $this->getRows($where, $fields, $limit, $offset, $order);
        if (!empty($data)) {
            array_walk($data, function (&$n) {
                $n["result"] = json_decode($n["result"], true);
            });
        }
        return $data;
    }
}