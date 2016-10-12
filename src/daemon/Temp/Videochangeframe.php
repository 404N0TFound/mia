<?php
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\Subject\Video;
use mia\miagroup\Service\Subject as SubjectService;

class Videochangeframe extends \FD_Daemon
{
    public function __construct()
    {
        $this->subjectService = new SubjectService();
    }

    public function execute()
    {
        $videotData = new Video();
        //视频总数
        $videoNum = $videotData->query('SELECT count(*) as num FROM group_subject_video as video LEFT join group_subjects as subject on video.subject_id=subject.id WHERE video.status = 1 and subject.source=3', \DB_Query::RS_ARRAY);

        $nums = $videoNum[0]['num'];
        $limit = 100;
        $end = floor($nums / $limit) + 1;
        for ($i = 1; $i <= $end; $i++) {
            $start = ($i - 1) * $limit;
            $videoList = $videotData->query('SELECT video.subject_id FROM group_subject_video as video LEFT join group_subjects as subject on video.subject_id=subject.id WHERE video.status = 1 and subject.source=3 limit '.$start.','.$limit, \DB_Query::RS_ARRAY);
            foreach ($videoList as $v) {
                if (empty($v['subject_id'])) {
                    continue;
                }
                //修改首帧
                $this->subjectService->changeVideoFrame($v['subject_id']);
            }
        }
    }
}