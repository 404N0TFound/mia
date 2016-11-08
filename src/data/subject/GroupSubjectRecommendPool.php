<?php
namespace mia\miagroup\Data\Subject;

class GroupSubjectRecommendPool extends \DB_Query{
    
    protected $tableName = 'group_subject_recommend_pool';
    protected $dbResource = "miagroup";
    protected $mapping = [];
    
    /**
     * 获取推荐池列表
     * @return multitype:multitype:unknown
     */
    public function getRecommendSubjectIdList()
    {
        $where[] = ['status',0];
        $where[] = [':<','recom_time',date('Y-m-d H:i:s')];
        $orderBy = 'create_time ASC';
        $field = "id,subject_id,recom_time";
        $limit = 20;
        
        $result = $this->getRows($where,$field,$limit,0,$orderBy);
        
        $resultArr = array();
        $ids = array();
        $subjects = array();
        foreach ($result as $value) {
            $subjects[] = $value['subject_id'];
            $ids[] = $value['id'];
        }
        $resultArr['subjects'] = $subjects;
        $resultArr['ids'] = $ids;
    
        return $resultArr;
    }
    
    /**
     * 设置推荐池中被推荐的图片的状态为已经推荐过
     * @param array $ids
     * @param int $setStatus
     * @return boolean
     */
    public function setRecommendorStatus($ids, $status = 1){
        
        $setData[] = ['status',$status];
        $setData[] = ['recom_time',date('Y-m-d H:i:s')];
        $where[] = ['id',$ids];
        
        $affectRow = $this->update($setData,$where);
        if ($affectRow) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 加入推荐池
     */
    public function addRecommentPool($subjectIds,$dateTime){
        $setData = ['subject_id'=>$subjectIds,'create_time'=>date('Y-m-d H:i:s'),'recom_time'=>$dateTime,'status'=>0];
        $affect = $this->insert($setData);
        return $affect;
    }
    
}
