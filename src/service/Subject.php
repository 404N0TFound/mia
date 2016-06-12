<?php
namespace mia\miagroup\Service;
class Subject extends \FS_Service {
    public function hello($name) {
        $subjectModel = new \mia\miagroup\Model\Subject();
        $data = $subjectModel->getSubjectByIds(array(17, 20));
        return $this->succ($data);
    }
}
