<?php
namespace mia\miagroup\Data\Label;

use Ice;

class SubjectLabelRelation extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_label_relation';

    protected $mapping = array();
    // TODO
    
    /**
     * 根据帖子ID分组批量查标签ID
     */
    public function getBatchSubjectLabelIds($subjectIds) {
        if (empty($subjectIds)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'subject_id', $subjectIds);
        $data = $this->getRows($where, '`subject_id`, `label_id`');
        $labelIdRes = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                if (!isset($labelIdRes[$v['subject_id']][$v['label_id']])) {
                    $labelIdRes[$v['subject_id']][$v['label_id']] = $v['label_id'];
                }
            }
        }
        return $labelIdRes;
    }

    /**
     * 保存蜜芽圈标签关系记录
     *
     * @param array $labelRelationInfo
     *            图片标签关系信息
     * @return bool
     */
    public function saveLabelRelation($labelRelationInfo) {
        $insertLabel = $this->insert($labelRelationInfo);
        return $insertLabel;
    }


    
    /**
     * 根据标签ID获取帖子列表
     */
    public function getSubjectListByLableIds($lableIds,$offset,$limit,$is_recommend=0)
    {
        if(!is_array($lableIds)){
            return [];
        }

        $where[] = ['label_id',$lableIds];
        $where[] = ['status',1];
        $orderBy = 'create_time DESC';
        if($is_recommend>0){
            $where[] = ['is_recommend',1];
            $orderBy = 'top_time DESC, recom_time DESC';
        }
        $data = $this->getRows($where,'subject_id',$limit,$offset,$orderBy);
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['subject_id']] = $value['subject_id'];
        }
        return $result;
    }

    /**
     * 批量获取标签下的精华帖子是否置顶
     */
    public function getLableSubjectsTopStatus($lableId, $subjectIds)
    {
        if (empty($subjectIds) || empty($lableId)) {
            return array();
        }
        $where[] = ['status',1];
        $where[] = ['label_id',$lableId];
        $where[] = ['subject_id',$subjectIds];
        $data = $this->getRows($where);
        $result = array();
        foreach ($data as $v) {
            $result[$v['subject_id']] = $v['is_top'];
        }
        return $result;
    }
    
    /**
     * 获取标签下是否有精选帖子
     */
    public function getLabelIsRecommendInfo($labelId){
        $sql = "SELECT a.* FROM {$this->tableName} a 
                    INNER JOIN group_subjects b ON a.subject_id=b.id
                    WHERE a.label_id={$labelId} AND a.status=1 AND a.is_recommend=1 AND b.status IN (1,2)";
        $result = $this->query($sql);
        return $result;
    }
    
}
