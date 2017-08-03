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
        return $data[0]['count'];
    }

    /*
     * 添加下载记录
     * */
    public function insertSubjectDownload() {

    }

    /*
     * 更新下载记录
     * */
    public function updateSubjectDownload() {

    }

}