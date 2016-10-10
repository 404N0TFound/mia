<?php
namespace mia\miagroup\Data\Praise;

class SubjectPraise extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_praises';

    protected $mapping = array();

    /**
     * 根据subjectIds批量分组获取选题的赞用户
     */
    public function getBatchSubjectPraiseUsers($subjectIds, $count = 20) {
        if (empty($subjectIds)) {
            return array();
        }
        $where = array();
        $field = 'subject_id, GROUP_CONCAT(user_id ORDER BY id DESC) AS ids';
        $where[] = array(':in', 'subject_id', $subjectIds);
        $where[] = array(':eq', 'status', 1);
        $subPraises = $this->getRows($where, $field, false, 0, 'subject_id', false, 'subject_id');
        $subPraisesList = array();
        if(!empty($subPraises)){
            foreach ($subPraises as $praises) {
                $ids = explode(',', $praises['ids']);
                if (count($ids) > $count) {
                    $ids = array_slice($ids, 0, $count);
                }
                foreach ($ids as $id) {
                    $subPraisesList[$praises['subject_id']][$id] = $id;
                }
            }
        }
        return $subPraisesList;
    }
    
    /**
     * 根据subjectIds批量分组查贊数
     * @params array() 图片ids
     * @return array() 图片赞数列表
     */
    public function getBatchSubjectPraises($subjectIds) {
        if (empty($subjectIds)) {
            return array();
        }
        $where = array();
        $field = 'subject_id, COUNT(1) AS total';
        $where[] = array(':in', 'subject_id', $subjectIds);
        $where[] = array(':eq', 'status', 1);
        $praiseNums = $this->getRows($where, $field, false, 0, false, false, 'subject_id');
        
        $praiseRes = array();
        if (!empty($praiseNums)) {
            foreach ($praiseNums as $praiseNum) {
                $praiseRes[$praiseNum['subject_id']] = intval($praiseNum['total']);
            }
        }
        return $praiseRes;
    }
    
    /**
     * 批量查贊信息，用于查某用户的赞状态
     * @params array $subjectIds 图片ids
     * @param int $userId  登录用户id
     * @return array 某个用户的赞信息
     */
    public function getBatchSubjectIsPraised($subjectIds, $userId) {
        if (intval($userId) <= 0 || empty($subjectIds)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'subject_id', $subjectIds);
        $where[] = array(':eq', 'user_id', $userId);
        $praises = $this->getRows($where);
        
        $praiseRes = array();
        if (!empty($praises)) {
            foreach ($praises as $praise) {
                $praiseRes[$praise['subject_id']] = $praise['status'];
            }
        }
        return $praiseRes;
    }
    
    public function checkIsExistFanciedByUserId($iUserId, $iSubjectId)
    {
        $where = array(["user_id", $iUserId], ["subject_id", $iSubjectId]);
        $fanciedRes = $this->getRow($where);
        if (!empty($fanciedRes)) {
            return $fanciedRes;
        } else {
            return array();
        }
    }
    
    
    /**
     * 更新赞
     * @param unknown $setData
     * @param unknown $id
     */
    public function updatePraiseById($setData,$id){
        $where[] = ['id',$id];
        $data = $this->update($setData,$where);
        if($data){
            return $id;
        }else{
            return 0;
        }
    }
    
    public function updatePraise($setData,$where){
        $data = $this->update($setData,$where);
        return $data;
    }
    
    public function insertPraise($setData){
        return $this->insert($setData);
    }
    
    /**
     * 根据赞ID获取赞信息
     */
    public function getPraisesByIds($praiseIds) {
        if (empty($praiseIds)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'id', $praiseIds);
        $praises = $this->getRows($where);
    
        $praiseRes = array();
        if (!empty($praises)) {
            foreach ($praises as $praise) {
                $praiseRes[$praise['id']] = $praise;
            }
        }
        return $praiseRes;
    }
}