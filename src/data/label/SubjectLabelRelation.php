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
}
