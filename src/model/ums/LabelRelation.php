<?php
namespace mia\miagroup\Model\Ums;

class LabelRelation extends \DB_Query{
    
    protected $tableName = 'group_subject_label_relation';
    protected $dbResource = 'miagroupums';

    /**
     * 获取关联信息
     * @param unknown $label_id
     * @param unknown $subject_id
     * @return unknown
     */
    public function getLabelRelation($subject_id){
        $where[] = ['subject_id',$subject_id];
        $data = $this->getRows($where);
        return $data;
    }
    
    
}