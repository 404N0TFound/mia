<?php

namespace mia\miagroup\Data\Subject;

class SubjectDownload extends \DB_Query
{
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_subject_download';
    protected $mapping = [];

    /**
     * 获取帖子下载信息
     * @param $conditions
     * @return array
     */
    public function getDownloadInfo($conditions)
    {
        if (!isset($conditions['user_id'])) {
            return [];
        }

        if (isset($conditions['user_id'])) {
            $where[] = ['user_id', $conditions['user_id']];
        }
        if (isset($conditions['source_type'])) {
            $where[] = ['source_type', $conditions['source_type']];
        }

        if (isset($conditions['source_id'])) {
            $where[] = ['source_id', $conditions['source_id']];
        }

        if (empty($where)) {
            return [];
        }
        $field = 'count';
        $data = $this->getRows($where, $field);
        $res = [];
        if(!empty($data)) {
            $res['count'] = $data[0]['count'];
        }
        return $res;
    }

    /*
     * 添加下载记录
     * */
    public function insertSubjectDownload($insert) {

        $data = $this->insert($insert);
        return $data;
    }

    /*
     * 更新下载记录
     * */
    public function updateSubjectDownload($setData, $where) {

        $data = $this->update($setData, $where);
        return $data;
    }

    /**
     * 获取帖子下载计数
     */
    public function getDownloadNum($subjectIds)
    {
        if(empty($subjectIds)) {
            return [];
        }
        $where[] = ['source_id', $subjectIds];
        $where[] = ['source_type', 1];
        $data = $this->getRows($where, 'source_id,count', FALSE, 0, FALSE, FALSE, "source_id");
        if(empty($data)) {
            return [];
        }
        $res = [];
        foreach ($data as $val) {
            $res[$val["source_id"]] = $val["count"];
        }
        return $res;
    }

}