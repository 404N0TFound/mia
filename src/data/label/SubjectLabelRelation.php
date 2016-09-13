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
     * 根据userId获取标签
     */
    public function getLabelListByUid($userId)
    {
        if(empty($userId)){
            return [];
        }
        $where[] = ['user_id',$userId];
        $where[] = ['status',1];

        $data = $this->getRows($where,'label_id',false,false,'id desc');
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = $value['label_id'];
        }
        return $result;
    }

    
    /**
     * 根据标签ID获取帖子列表
     */
    public function getSubjectListByLableIds($lableIds,$offset,$limit)
    {
        if(!is_array($lableIds)){
            return [];
        }

        $where[] = ['label_id',$lableIds];
        $where[] = ['status',1];

        $data = $this->getRows($where,'subject_id',$limit,$offset,'id desc');
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['subject_id']] = $value['subject_id'];
        }
        return $result;
    }
}
