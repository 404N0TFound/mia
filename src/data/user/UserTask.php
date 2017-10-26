<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

class UserTask extends DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'member_task_final_log';

    protected $mapping = [];

    /**
     * 新增
     */
    public function addTaskResult($insertData) {
        $data = $this->insert($insertData);
        return $data;
    }
}
