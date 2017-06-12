<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Active extends \DB_Query {

    protected $dbResource = 'miadefaultums';

    protected $tableActive = 'group_active';


    public function getGroupActiveData($month)
    {
        $this->dbResource = 'miagroupums';
        $this->tableName = $this->tableActive;
        $time = date("Y-m-d", strtotime("-".$month." month"));
        $where[] = ['status',1];
        $field = "id, title";
        $where[] = [':gt', 'created', $time];
        $res = $this->getRows($where, $field);
        return $res;
    }
}