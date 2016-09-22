<?php
namespace mia\miagroup\Data\Audit;

use \DB_Query;

class SensitiveWord extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'sensitive_word';

    protected $mapping = array();

    /**
     * 获取敏感词表
     */
    public function getSensitiveWord($type = array()) {
        $where = array();
        if (!empty($type)) {
            $where[] = array('type', $type);
        }
        $data = $this->getRows($where);
        return $data;
    }
}