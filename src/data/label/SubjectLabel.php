<?php
namespace mia\miagroup\Data\Label;
use Ice;
class SubjectLabel extends \DB_Query {
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_subject_label';
    protected $mapping   = array(
        //TODO
    );
    
    /**
     * 批量查标签信息
     * @params array() $labelIdArr 标签ids
     * @return array() 图片标签信息列表
     */
    public function getBatchLabelInfos($labelIds) {
        if (empty($labelIds)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'id', $labelIds);
        $where[] = array(':eq', 'status', 1);
        $labelInfos = $this->getRows($where, '`id`, `title`, `is_hot`');
        $labelsRes = array();
        $result = array();
        if (!empty($labelInfos)) {
            foreach ($labelInfos as $labelInfo) {
                $labelsRes[$labelInfo['id']] = $labelInfo;
            }
            foreach ($labelIds as $labelId) {
                if (!empty($labelsRes[$labelId])) {
                    $result[$labelId] = $labelsRes[$labelId];
                }
            }
        }
        return $result;
    }
}
