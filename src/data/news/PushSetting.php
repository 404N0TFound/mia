<?php

namespace mia\miagroup\Data\News;

use \DB_Query;

class PushSetting extends DB_Query
{

    public $dbResource = 'miagroup';
    public $tableName = 'group_push_setting';
    public $mapping = [];

    public function getList($conditions)
    {
        if (empty($conditions) || !array_key_exists("user_id", $conditions)) {
            return [];
        }
        if (isset($conditions['user_id'])) {
            $where[] = ['user_id', $conditions['user_id']];
        }
        if (isset($conditions['type'])) {
            $where[] = ['type', $conditions['type']];
        }
        $data = $this->getRows($where);
        return $data;
    }

    /**
     * 添加设置
     * @param $insertData
     * @return bool
     */
    public function addTypeSet($insertData)
    {
        if(empty($insertData)) {
            return false;
        }
        $res = $this->insert($insertData);
        return $res;
    }

    /**
     * 修改设置
     * @param $setData
     * @param $where
     * @return int
     */
    public function updateSetting($setData, $where)
    {
        if (empty($setData) || empty($where)) {
            return 0;
        }
        $res = $this->update($setData, $where);
        return $res;
    }
}