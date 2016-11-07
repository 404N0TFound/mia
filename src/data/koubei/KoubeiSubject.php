<?php
namespace mia\miagroup\Data\Koubei;

class KoubeiSubject extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'koubei_subjects';

    protected $mapping = array();
    
    /**
     * 新增待转换口碑的蜜芽帖
     * @param array $koubeiData
     */
    public function saveKoubeiSubject($data){
        $result = $this->insert($data);
        return $result;
    }
    
    /**
     * 更新
     */
    public function updateKoubeiSubject($subjectData, $subjectId) {
        if (empty($subjectData) || !is_array($subjectData)) {
            return false;
        }
        $setData = array();
        $where = array();
        $where[] = ['subject_id', $subjectId];
        foreach ($subjectData as $k => $v) {
            $setData[] = [$k, $v];
        }
        $result = $this->update($setData, $where);
        return $result;
    }
}