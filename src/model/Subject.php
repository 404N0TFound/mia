<?php
namespace mia\miagroup\Model;
class Subject {
    public function getSubjectByIds($subjectIds) {
        $subjectData = new \mia\miagroup\Data\Subject\Subject();
        $subjects = $subjectData->getBatchSubjects($subjectIds);
        $videoData = new \mia\miagroup\Data\Subject\Video();
        $videos = $videoData->getVideoBySubjectIds($subjectIds);
        var_dump($videos);exit;
        return $subjects;
    }
}
