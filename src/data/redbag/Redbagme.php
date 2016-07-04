<?php
namespace mia\miagroup\Data\Redbag;

use Ice;

class Redbagme extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'redbag_me';

    protected $mapping = array();

    /**
     * 记录红包入账信息
     */
    public function addRedbagInfoToMe($redbagData) {
        $data = $this->insert($redbagData);
        return $data;
    }
}