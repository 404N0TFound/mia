<?php

namespace mia\miagroup\Data\Subject;

class SubjectCollect extends \DB_Query
{
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_subject_collect';
    protected $mapping = [];

    /**
     * 获取帖子收藏信息
     * @param $conditions
     * @return array
     */
    public function getCollectInfo($conditions)
    {
        if (!isset($conditions['user_id']) && !isset($conditions['update_time'])) {
            return [];
        }

        if (isset($conditions['user_id'])) {
            $where[] = ['user_id', $conditions['user_id']];
        }
        if (isset($conditions['type'])) {
            $where[] = ['source_type', $conditions['type']];
        }
        if (isset($conditions['status'])) {
            $where[] = ['status', $conditions['status']];
        }

        if (isset($conditions['source_id'])) {
            $where[] = ['source_id', $conditions['source_id']];
        }
        $limit = FALSE;
        if (isset($conditions['limit'])) {
            $limit = $conditions['limit'];
        }

        $offset = 0;
        if (isset($conditions['offset'])) {
            $offset = $conditions['offset'];
        }

        $orderBy = FALSE;
        if (isset($conditions['order'])) {
            $orderBy = $conditions['order'];
        }

        if (empty($where)) {
            return [];
        }
        $data = $this->getRows($where, 'id,source_id,status', $limit, $offset, $orderBy);
        $res = [];
        foreach ($data as $val) {
            $res[$val["id"]] = $val;
        }
        return $res;
    }


    /**
     * 添加收藏
     */
    public function addCollection($insertData)
    {
        $data = $this->insert($insertData);
        return $data;
    }

    /**
     * 修改收藏状态
     */
    public function updateCollect($setData, $where)
    {
        $data = $this->update($setData, $where);
        return $data;
    }

    /**
     * 获取帖子收藏计数
     */
    public function getCollectNum($subjectIds)
    {
        if(empty($subjectIds)) {
            return [];
        }
        $where[] = ['source_id', $subjectIds];
        $where[] = ['status', 1];
        $data = $this->getRows($where, 'source_id,count(id) as num', FALSE, 0, FALSE, FALSE, "source_id");
        if(empty($data)) {
            return [];
        }
        $res = [];
        foreach ($data as $val) {
            $res[$val["source_id"]] = $val["num"];
        }
        return $res;
    }
}