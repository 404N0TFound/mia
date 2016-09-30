<?php
namespace mia\miagroup\Data\Koubei;

use Ice;

class KoubeiScore extends \DB_Query {

    protected $dbResource = 'miaBI';

    protected $tableName = 'resultset';

    protected $mapping = array();
    
    /**
     * 获取新修正的口碑评分
     */
    public function getListById($id = 0, $limit = 100) {
        if (intval($id) > 0) {
            $where[] = [':ge','id', $id];
        }
        $data = $this->getRows($where, '*', $limit);
        return $data;
    }
    
    /**
     * 获取当前最大ID
     */
    public function getMaxId() {
        $where = array();
        $data = $this->getRow($where, 'MAX(`id`) as id');
        return intval($data['id']);
    }
}