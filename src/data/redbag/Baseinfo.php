<?php
namespace mia\miagroup\Data\Redbag;

use Ice;

class Baseinfo extends \DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'redbag_base_info';

    protected $mapping = array();

    /**
     * 根据红包Ids批量获取红包信息
     * 申请状态 0 草稿 1申请中 2通过  3 拒绝  4再次审核 
     */
    public function getBatchRedbagByIds($ids, $status = array(2)) {
        if (empty($ids)) {
            return array(); 
        }
        $where = array();
        $where[] = array(':in', 'redbag_id', $ids);
        if (!empty($status)) {
            $where[] = ['status', $status];
        }
        
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