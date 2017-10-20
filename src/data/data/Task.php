<?php

namespace mia\miagroup\Data\Data;

class Task extends \DB_Query
{
    public $dbResource = 'miagroup';
    public $tableName = 'group_data_task';
    public $mapping = [];


    public function addDataTask($insertData)
    {
        $res = $this->insert($insertData);
        return $res;
    }

    public function getTaskList($conditions)
    {
        $where = [];
        $fields = "*";

        if(isset($conditions["count"])) {
            $limit = intval($conditions["count"]);
        } else {
            $limit = 10;
        }

        if (isset($conditions["page"])) {
            $offset = ($conditions["page"] - 1) * $limit;
        } else {
            $offset = 0;
        }

        $order = "create_time DESC";
        $data = $this->getRows($where, $fields, $limit, $offset, $order);
        if (!empty($data)) {
            array_walk($data, function (&$n) {
                $n["settings"] = json_decode($n["settings"], true);
            });
        }
        return $data;
    }
    
    public function updateDataTask($taskId,$updateData)
    {
        $where = [];
        $where[] = ['id',$taskId];
        $res = $this->update($updateData,$where);
        return $res;
    }
}