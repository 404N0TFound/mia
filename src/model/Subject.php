<?php
namespace mia\miagroup\Model;
class Subject {
    public function getSubjectByIds($subjectIds) {
        //获取帖子的基本信息
        $subjectData = new \mia\miagroup\Data\Subject\Subject();
        $subjects = $subjectData->getBatchSubjects($subjectIds);
        if (empty($subjects)) {
            return array();
        }
        //获取帖子附属的视频信息
        $videoData = new \mia\miagroup\Data\Subject\Video();
        $videos = $videoData->getVideoBySubjectIds($subjectIds);
        foreach ($subjects as $subjectId => $subject) {
            if (!empty($videos[$subjectId])) {
                $subjects[$subjectId]['video_info'] = $videos[$subjectId];
            }
        }
        return $subjects;
    }
}
