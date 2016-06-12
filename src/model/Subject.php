<?php
namespace mia\miagroup\Model;
class Subject {
    public function getSubjectByIds($subjectIds) {
        $dataObj = new \mia\miagroup\Data\Subject\Subject();
        $subjects = $dataObj->getBatchSubjects($subjectIds);
        return $subjects;
    }
}
