<?php

namespace mia\miagroup\Data\Subject;

class SubjectDownload extends \DB_Query
{
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_subject_download';
    protected $mapping = [];

    /*
     * 添加下载记录
     * */
    public function insertSubjectDownload($insert) {

        $data = $this->insert($insert);
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