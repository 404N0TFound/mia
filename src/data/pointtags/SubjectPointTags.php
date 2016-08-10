<?php
namespace mia\miagroup\Data\PointTags;

use Ice;

class SubjectPointTags extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_point_tags';

    protected $mapping = array();
    
    /**
     * 保存蜜芽圈帖子标记
     *
     * @param array $tagsInfo 帖子标记
     */
    public function saveSubjectTags($tagsInfo) {
        $insertTags = $this->insert($tagsInfo);
        return $insertTags;
    }

}