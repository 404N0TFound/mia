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
    
    /**
     * 帖子关联商品，并同步口碑
     */
    public function subject_relate_item() {
        $file_path = '/home/hanxiang/being_related_subjects';
        $data = file($file_path);
        $i = 0;
        $pointService = new \mia\miagroup\Service\PointTags();
        $koubeiService = new \mia\miagroup\Service\Koubei();
        foreach ($data as $v) {
            $i ++;
            $v = trim($v);
            list($subject_id, $item_id) = explode("\t", $v);
            $pointService->saveSubjectTags($subject_id, array('item_id' => $item_id));
            $koubeiService->setSubjectToKoubei($subject_id, $item_id);
            if ($i % 200 == 0) {
                sleep(1);
            }
        }
    }
}