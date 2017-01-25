<?php
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\Subject\Subject as SubjectData;

/**
 * 帖子相关-临时脚本
 */
 
class Subject extends \FD_Daemon {

    public function __construct() {
        $this->subjectData = new SubjectData();
    }

    public function execute() {
        
    }
    
    /**
     * 删除帖子
     */
    public function delete_subject() {
        $file_path = '/home/hanxiang/all_share_baby_ids.txt';
        $data = file($file_path);
        $i = 0;
        foreach ($data as $v) {
            $i ++;
            $subject_ids[] = trim($v);
            if ($i % 1000 == 0) {
                $this->subjectData->deleteSubjects($subject_ids, 0);
                $subject_ids = [];
                sleep(1);
            }
        }
    }
}