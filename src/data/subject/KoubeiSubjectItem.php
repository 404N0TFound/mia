<?php
namespace mia\miagroup\Data\Subject;

class KoubeiSubjectItem extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'koubei_subject_item';

    protected $mapping = array();
    
    /**
     * 新增口碑贴关联商品信息
     * @param array $subjectItemData
     */
    public function saveKoubeiSubjectItem($subjectItemData){
        $result = $this->insert($subjectItemData);
        return $result;
    }
    
    
}
