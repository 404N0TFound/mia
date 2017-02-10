<?php
namespace mia\miagroup\Data\Active;

use Ice;

class Active extends \DB_Query {

    protected $dbResource = 'miagroup';
    protected $tableName = 'group_active';

    /**
     * 批量查活动信息
     */
    public function getBatchActiveInfos($activeIds = array(), $status = array(1)) {

    }

    
    
}
