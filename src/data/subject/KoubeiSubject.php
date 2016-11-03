<?php
namespace mia\miagroup\Data\Subject;

class KoubeiSubject extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'koubei_subjects';

    protected $mapping = array();
    
    /**
     * 新增口碑贴信息
     * @param array $koubeiData
     */
    public function saveKoubeiSubject($koubeiData){
        $result = $this->insert($koubeiData);
        return $result;
    }
    
    
}