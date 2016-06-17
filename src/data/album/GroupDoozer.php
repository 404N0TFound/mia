<?php
namespace mia\miagroup\Data\Album;
use \DB_Query;

class GroupDoozer extends \DB_Query {
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_doozer';
    protected $mapping   = array(
        //TODO
    );
    
    /**
     * 推荐列表
     * @return array() 推荐列表
     */
    public function getGroupDoozerList() {
        $result = array();
	
        $where = array();
        $where[] = array(':eq','status',1);
	$experts = $this->getRows($where,array('user_id'));
        return $experts;
    }
}
