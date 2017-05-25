<?php
namespace mia\miagroup\Data\Item;

use Ice;

class UserSupplierMapping extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'user_supplier_mapping';

    protected $mapping = array();

    /**
     * 通过用户ID批量查找关联关系
     */
    public function getBatchUserSupplierMapping($user_ids) 
    {
        $result = array();
        if (empty($user_ids)) {
            return $result;
        }
        $where = array();
        $where[] = ['user_id', $user_ids];
        $data = $this->getRows($where);
        if (!empty($data) && is_array($data)) {
            foreach ($data as $v) {
                $result[$v['user_id']] = $v;
            }
        }
        return $result;
    }

    /**
     * 通过商家ID查找关联关系
     */
    public function getMappingBySupplierId($supplier_id) 
    {
        $where = array();
        $where[] = ['supplier_id', $supplier_id];
        $data = $this->getRow($where);
        return $data;
    }

    /**
     * 添加商家和蜜芽圈用户的关联关系
     */
    public function addUserSupplierMapping($mapping_info) 
    {
        $data = $this->insert($mapping_info);
        return $data;
    }
    
    /**
     * 更新信息
     */
    public function updateMappingById($id, $mapping_info) {
        if (empty($id) || empty($mapping_info) || !is_array($mapping_info)) {
            return false;
        }
        $set_data = array();
        foreach ($mapping_info as $k => $v) {
            $set_data[] = array($k, $v);
        }
        $where[] = array('id', $id);
        $data = $this->update($where, $set_data);
        return $data;
    }
}