<?php
namespace mia\miagroup\Model\Ums;

class RecommendPool extends \DB_Query{
    
    protected $tableName = 'group_subject_recommend_pool';
    protected $dbResource = 'miagroupums';
    
    /**
     * 加入推荐池
     */
    public function addRecommentPool($subjectIds,$dateTime){
        $setData = ['subject_id'=>$subjectIds,'create_time'=>'now()','recom_time'=>$dateTime,'status'=>0];
        $affect = $this->insert($setData);
        return $affect;
    }
    
}