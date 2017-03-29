<?php
namespace mia\miagroup\Daemon\Active;

use mia\miagroup\Service\Active as ActiveService;
use mia\miagroup\Data\Active\ActiveSubjectRelation as RelationData;

/*
 * 定时任务：取出帖子的评论数、赞数、分享数和帖子的评分调节值，
 * 根据计算规则，计算出图片热度
 */
class SetActiveSubjectHotvalue extends \FD_Daemon{
    private $relationData;
    private $activeService;
    public function __construct(){
        $this->activeService = new ActiveService();
        $this->relationData = new RelationData();
    }
    
    public function execute()
    {
        //获取所有在线活动
        $activeArrs = $this->activeService->getCurrentActive();
        if(!empty($activeArrs['data'])){
            foreach($activeArrs['data'] as $key=>$activeArr){
                //获取活动下的帖子（帖子里包括赞数，评论数）
                $subjectArrs = $this->activeService->getActiveSubjects($key, $type='all', 0, false, 0)['data']['subject_lists'];
                if(empty($subjectArrs)){
                    continue;
                }
                $subjectIds = array_column($subjectArrs, 'id');
                //根据帖子id获取帖子活动关联信息
                $relations = $this->relationData->getActiveSubjectBySids($subjectIds);
                
                foreach($subjectArrs as $subjectArr){
                    //获取帖子调节值
                    $regulate = $relations[$subjectArr['id']]['regulate'];
                    //根据评论、赞和调节值算出热度值
                    $hotValue = $subjectArr['fancied_count'] + $subjectArr['comment_count'] * 2 + $regulate;
                    //更新帖子热度值
                    $updateData = array();
                    $updateData['hot_value'] = $hotValue;
                    $this->relationData->updateActiveSubjectRelation($updateData,$relations[$subjectArr['id']]['id']);
                }
            }
        }
        
        return true;
    }

}
