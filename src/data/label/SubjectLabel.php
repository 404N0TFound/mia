<?php
namespace mia\miagroup\Data\Label;

use Ice;

class SubjectLabel extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_label';

    protected $mapping = array();
    // TODO
    
    /**
     * 批量查标签信息
     *
     * @param
     *            s array() $labelIdArr 标签ids
     * @return array() 图片标签信息列表
     */
    public function getBatchLabelInfos($labelIds) {
        if (empty($labelIds)) {
            return array();
        }
        $where = array();
        $where[] = array(
            ':in',
            'id',
            $labelIds
        );
        $where[] = array(
            ':eq',
            'status',
            1
        );
        $labelInfos = $this->getRows($where, '`id`, `title`, `is_hot`');
        $labelsRes = array();
        $result = array();
        if (! empty($labelInfos)) {
            foreach ($labelInfos as $labelInfo) {
                $labelsRes[$labelInfo['id']] = $labelInfo;
            }
            foreach ($labelIds as $labelId) {
                if (! empty($labelsRes[$labelId])) {
                    $result[$labelId] = $labelsRes[$labelId];
                }
            }
        }
        return $result;
    }

    /**
     * 判断标签记录是否存在(用于图片发布，避免主辅库不同步，从主库查)
     *
     * @param string $labelTitle
     *            标签标题
     * @return bool
     */
    public function checkIsExistByLabelTitle($labelTitle) {
        $labelTitle = md5($labelTitle);
        
        $where[] = array(
            "title_md5",
            $labelTitle
        );
        $LabelRes = $this->getRow($where);
        
        return $LabelRes;
    }

    /**
     * 保存蜜芽圈标签
     *
     * @param array $labelInfo
     *            标签信息
     * @return int 标签id
     */
    public function addLabel($labelTitle) {
        $labelTitleMd5 = md5($labelTitle);
        $setData = [
            'title_md5' => $labelTitleMd5
        ];
        $LabelId = $this->insert($setData);
        
        return $LabelId;
    }
}
