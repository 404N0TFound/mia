<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

class HeadLineUserCategory extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_headline_user_category';

    protected $mapping = array();

    /**
     * 新增用户分类
     */
    public function addUserCategory($userId, $category) {
        if (empty($userId) || empty($category)) {
            return false;
        }
        $insertData['user_id'] = $userId;
        $insertData['category'] = $category;
        $data = $this->insert($insertData);
        return $data;
    }
    
    /**
     * 根据uid查询
     */
    public function getDataByUid($userId) {
        if (empty($userId)) {
            return false;
        }
        $where[] = array('user_id', $userId);
        $data = $this->getRow($where);
        return $data;
    }
    
    /**
     * 根据uid查询
     */
    public function setDataByUid($userId, $setData) {
        if (empty($userId) || empty($setData)) {
            return false;
        }
        $where[] = array('user_id', $userId);
        $affectRow = $this->update($setData, $where);
        return $affectRow;
    }
}
