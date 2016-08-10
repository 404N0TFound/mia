<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\PointTags\SubjectPointTags as TagsData;
class PointTags {
    
    public $tagsData;
    
    public function __construct() {
        $this->tagsData = new TagsData();
    }
    
    /**
     * 保存蜜芽圈帖子标记信息
     * @param $tagsInfo array() 帖子标记信息
     */
    public function saveSubjectTags($tagsInfo){
        $data = $this->tagsData->saveSubjectTags($tagsInfo);
        return $data;
    }
    
}