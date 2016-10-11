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
        $where[] = [':eq', 'status', 1];
        $field = 'count(*) as nums';
        //视频总数
        $videoNum = $videotData->getRows($where, $field);
        $nums = $videoNum[0]['nums'];

        $limit = 20;
        $end = floor($nums / $limit) + 1;
        for ($i = 1; $i <= $end; $i++) {
            $start = ($i - 1) * $limit;
            $videoList = $videotData->getRows([], 'subject_id', $limit, $start);
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