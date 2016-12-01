<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class StockWarehouse extends \DB_Query {

    protected $dbResource = 'miadefaultums';

    protected $tableStockWarehouse = 'stock_warehouse';
    
    /**
     * 根据供应商id批量查询仓库名
     */
    public function getBatchNameBySupplyIds($supplyIds) {
        $this->tableName = $this->tableStockWarehouse;
        if (empty($supplyIds)) {
            return false;
        }
        $where[] = ['supplier_id', $supplyIds];
        
        $data = $this->getRows($where, 'name,supplier_id');
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['supplier_id']][] = $v['name'];
            }
        }
        return $result;
    }
}