<?php
namespace mia\miagroup\Model;

use \mia\miagroup\Data\Praise\SubjectPraise;

class Praise {

    private $subjectPraiseData;

    public function __construct() {
        $this->subjectPraiseData = new SubjectPraise();
    }
    
    /**
     * 根据subjectIds批量分组查贊数
     * @params array() 图片ids
     * @return array() 图片赞数列表
     */
    public function getBatchSubjectPraises($subjectIds) {
        $subjectPraises = $this->subjectPraiseData->getBatchSubjectPraises($subjectIds);
        return $subjectPraises;
    }
    
    /**
     * 根据subjectIds批量分组获取选题的赞用户
     */
    public function getBatchSubjectPraiseUsers($subjectIds, $count = 20) {
        $subjectPraiseUser = $this->subjectPraiseData->getBatchSubjectPraiseUsers($subjectIds, $count);
        return $subjectPraiseUser;
    }
    
    /**
     * 批量查贊信息，用于查某用户的赞状态
     * @params array $subjectIds 图片ids
     * @param int $userId  登录用户id
     * @return array 某个用户的赞信息
     */
    public function getBatchSubjectIsPraised($subjectIds, $userId) {
        $isPraised = $this->subjectPraiseData->getBatchSubjectIsPraised($subjectIds, $userId);
        return $isPraised;
    }
    
    public function checkIsExistFanciedByUserId($iUserId, $iSubjectId)
    {
        return $this->subjectPraiseData->checkIsExistFanciedByUserId($iUserId, $iSubjectId);
    }
    
    /**
     * 更新赞
     * @param unknown $setData
     * @param unknown $id
     */
    public function updatePraiseById($setData,$id){
        return $this->subjectPraiseData->updatePraiseById($setData,$id);
    }
    
    public function updatePraise($setData,$where){
        return $this->subjectPraiseData->updatePraise($setData, $where);
    }
    
    public function insertPraise($setData){
        return $this->subjectPraiseData->insertPraise($setData);
    }
    
}