<?php
namespace mia\miagroup\Daemon\Subject;

use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Model\Subject as SubjectModel;

/*
 * 定时任务：从蜜芽圈推荐池的表中定时取数据，设置为推荐
 */
class Grouprecommendpool extends \FD_Daemon{

    public function __construct(){
        $this->subjectModel = new SubjectModel();
    }
    
    public function execute()
    {
        //获取被推荐数据
        $result = $this->subjectModel->getRecommendSubjectIdList();
        //拿出被推荐图片ID的数组
        $subjects = $result['subjects'];
        //拿出相应推荐记录的ID，方便更新状态
        $ids = $result['ids'];
        if (count($subjects) <= 0 && count($ids) <= 0) {
            echo 'NOTICE:没有数据可供推荐！';
            exit;
        }
        $status = $this->_recommend($subjects, $ids);
        if ($status) {
            echo 'MESSAGE:推荐成功' . count($subjects) . '条！';
        } else {
            echo 'ERROR:推荐失败！';
        }
    }

    /**
     * 推荐
     * @param array $subjects
     * @param array $ids
     * @return boolean
     */
    private function _recommend($subjects, $ids)
    {

        $subjectService = new SubjectService();
        //先更新蜜芽圈图片表的图片为推荐状态
        $subjectService->subjectAddFine($subjects)['data'];
        //再更新被推荐池的状态
        $data = $this->subjectModel->setRecommendorStatus($ids);
        return $data;
    }

}
