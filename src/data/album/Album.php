<?php
namespace mia\miagroup\Data\Album;

use \DB_Query;

class Album extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_album';

    protected $mapping = array();

    /**
     * 专辑列表
     * @params array() user_id 用户ID
     * @return array() 专辑列表
     */
    public function getAlbumList($params) {
        $result = array();
        
        $where = array();
        $where[] = array(':eq', 'user_id', $params['user_id']);
        
        $orderBy = array('create_time DESC');
        $experts = $this->getRows($where, array('id','user_id','title'), $limit, $offset, $orderBy);
        return $experts;
    }
}
