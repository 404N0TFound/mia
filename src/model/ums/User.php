<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class User extends \DB_Query {

    protected $dbResource = 'miadefaultums';

    protected $tableUsers = 'users';
    
    protected $tableUserSupplierMapping = 'user_supplier_mapping';
    
    /**
     * 根据username查询uid
     */
    public function getUidByUserName($userName) {
        $this->tableName = $this->tableUsers;
        if (empty($userName)) {
            return false;
        }
        if (mb_strlen($userName, 'utf8') > 18) {
            $userName = mb_substr($userName, 0, 18, 'utf8');
            $where[] = array(':like_begin','username', $userName);
        } else {
            $where[] = array(':eq','username', $userName);
        }
        
        $data = $this->getRow($where, 'id');
        if (!empty($data)) {
            return $data['id'];
        } else {
            return false;
        }
    }
    
    /**
     * 根据nickname查询uid
     */
    public function getUidByNickName($nickName) {
        $this->tableName = $this->tableUsers;
        if (empty($nickName)) {
            return false;
        }
        $where[] = array('nickname', $nickName);
        $data = $this->getRow($where, 'id');
        if (!empty($data)) {
            return $data['id'];
        } else {
            return false;
        }
    }
    
    /**
     * 获取所有自主口碑商家
     */
    public function getAllKoubeiSupplier() {
        $this->dbResource = 'miagroupums';
        $this->tableName = $this->tableUserSupplierMapping;
        $data = $this->getRows();
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['supplier_id']] = $v['user_id'];
            }
        }
        return $result;
    }
}