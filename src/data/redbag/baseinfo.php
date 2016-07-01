<?php
namespace mia\miagroup\Data\Redbag;

use Ice;

class Baseinfo extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'redbag_base_info';

    protected $mapping = array();

    /**
     * 根据红包Ids批量获取红包信息
     */
    public function getBatchRedbagByIds($ids) {
        if (empty($ids)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'redbag_id', $ids);
        $where[] = array(':eq', 'status', 2);
        $data = $this->getRows($where);
        
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['redbag_id']] = $v;
            }
        }
        return $result;
    }
}